<?php
require_once '../config/db.php';
require_once '../core/auth.php';

session_start();
$auth = new Auth();

// Check if user is logged in
if(!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$database = new Database();
$conn = $database->getConnection();
$employee_id = $auth->getCurrentUser()['employee_id'];

if(empty($employee_id)) {
    echo json_encode(['success' => false, 'message' => 'Employee ID not found']);
    exit();
}

switch($action) {
    case 'check_in':
        checkIn($conn, $employee_id);
        break;
        
    case 'check_out':
        checkOut($conn, $employee_id);
        break;
        
    case 'bulk_upload':
        bulkUploadAttendance($conn, $auth);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function checkIn($conn, $employee_id) {
    try {
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Check if already checked in today
        $check_query = "SELECT id FROM attendance 
                       WHERE employee_id = :employee_id 
                       AND attendance_date = :today";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':employee_id', $employee_id);
        $check_stmt->bindParam(':today', $today);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            throw new Exception("Already checked in today");
        }
        
        // Get employee shift details
        $employee_query = "SELECT e.*, s.shift_start, s.shift_end, s.grace_minutes 
                          FROM employees e 
                          LEFT JOIN shifts s ON e.shift_id = s.id 
                          WHERE e.id = :id";
        $employee_stmt = $conn->prepare($employee_query);
        $employee_stmt->bindParam(':id', $employee_id);
        $employee_stmt->execute();
        $employee = $employee_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate late mark
        $late_mark = 'No';
        if($employee['shift_start']) {
            $shift_start = strtotime($employee['shift_start']);
            $check_in_time = strtotime($current_time);
            $grace_minutes = $employee['grace_minutes'] ?? 15;
            
            // Check if late (after shift start + grace period)
            if($check_in_time > ($shift_start + ($grace_minutes * 60))) {
                $late_mark = 'Yes';
            }
        }
        
        // Check for early entry
        $early_entry = 'No';
        if($employee['shift_start']) {
            $shift_start = strtotime($employee['shift_start']);
            $check_in_time = strtotime($current_time);
            
            // Check if early (more than 30 minutes before shift)
            if($check_in_time < ($shift_start - (30 * 60))) {
                $early_entry = 'Yes';
            }
        }
        
        // Insert attendance record
        $query = "INSERT INTO attendance (
            employee_id, 
            attendance_date, 
            in_time, 
            late_marks,
            early_entry,
            attendance_status
        ) VALUES (
            :employee_id, 
            :attendance_date, 
            :in_time, 
            :late_marks,
            :early_entry,
            'Present'
        )";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':attendance_date', $today);
        $stmt->bindParam(':in_time', $current_time);
        $stmt->bindParam(':late_marks', $late_mark);
        $stmt->bindParam(':early_entry', $early_entry);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to mark check-in: " . implode(', ', $stmt->errorInfo()));
        }
        
        // Log activity
        logActivity($employee_id, 'Check In', 'Attendance', "Checked in at $current_time");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully checked in at ' . date('h:i A', strtotime($current_time)),
            'data' => [
                'time' => $current_time,
                'date' => $today,
                'late_mark' => $late_mark
            ]
        ]);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function checkOut($conn, $employee_id) {
    try {
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Get today's attendance record
        $attendance_query = "SELECT * FROM attendance 
                            WHERE employee_id = :employee_id 
                            AND attendance_date = :today";
        $attendance_stmt = $conn->prepare($attendance_query);
        $attendance_stmt->bindParam(':employee_id', $employee_id);
        $attendance_stmt->bindParam(':today', $today);
        $attendance_stmt->execute();
        
        if($attendance_stmt->rowCount() === 0) {
            throw new Exception("No check-in record found for today");
        }
        
        $attendance = $attendance_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($attendance['out_time']) {
            throw new Exception("Already checked out today");
        }
        
        // Get employee shift details
        $employee_query = "SELECT e.*, s.shift_start, s.shift_end 
                          FROM employees e 
                          LEFT JOIN shifts s ON e.shift_id = s.id 
                          WHERE e.id = :id";
        $employee_stmt = $conn->prepare($employee_query);
        $employee_stmt->bindParam(':id', $employee_id);
        $employee_stmt->execute();
        $employee = $employee_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate working hours
        $in_time = strtotime($attendance['in_time']);
        $out_time = strtotime($current_time);
        $working_seconds = $out_time - $in_time;
        $working_hours = round($working_seconds / 3600, 2);
        
        // Calculate overtime
        $overtime_hours = 0;
        $shortage_hours = 0;
        $early_exit = 'No';
        
        if($employee['shift_end']) {
            $shift_end = strtotime($employee['shift_end']);
            $shift_duration = 9; // Default 9 hours shift
            
            // Calculate if left early
            if($out_time < $shift_end) {
                $early_exit = 'Yes';
            }
            
            // Calculate standard working hours
            $standard_hours = $shift_duration;
            
            if($working_hours > $standard_hours) {
                $overtime_hours = $working_hours - $standard_hours;
            } elseif($working_hours < $standard_hours) {
                $shortage_hours = $standard_hours - $working_hours;
            }
        }
        
        // Update attendance record
        $query = "UPDATE attendance SET
            out_time = :out_time,
            total_working_hours = :total_working_hours,
            overtime_hours = :overtime_hours,
            shortage_hours = :shortage_hours,
            early_exit = :early_exit
            WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':out_time', $current_time);
        $stmt->bindParam(':total_working_hours', $working_hours);
        $stmt->bindParam(':overtime_hours', $overtime_hours);
        $stmt->bindParam(':shortage_hours', $shortage_hours);
        $stmt->bindParam(':early_exit', $early_exit);
        $stmt->bindParam(':id', $attendance['id']);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to mark check-out: " . implode(', ', $stmt->errorInfo()));
        }
        
        // Log activity
        logActivity($employee_id, 'Check Out', 'Attendance', 
                   "Checked out at $current_time (Worked: {$working_hours} hours)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully checked out at ' . date('h:i A', strtotime($current_time)),
            'data' => [
                'time' => $current_time,
                'working_hours' => $working_hours,
                'overtime' => $overtime_hours
            ]
        ]);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function bulkUploadAttendance($conn, $auth) {
    try {
        // Check if user has permission (HR or Admin)
        $current_user = $auth->getCurrentUser();
        $allowed_roles = ['Super Admin', 'Admin', 'HR'];
        
        if(!in_array($current_user['role_name'], $allowed_roles)) {
            throw new Exception("Permission denied. Only HR/Admin can upload attendance.");
        }
        
        if(!isset($_FILES['attendance_file']) || $_FILES['attendance_file']['error'] != UPLOAD_ERR_OK) {
            throw new Exception("Please select a file to upload");
        }
        
        $file = $_FILES['attendance_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if($file_ext != 'csv') {
            throw new Exception("Only CSV files are allowed");
        }
        
        // Process CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if($handle === false) {
            throw new Exception("Failed to open file");
        }
        
        // Skip header row
        fgetcsv($handle);
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        $conn->beginTransaction();
        
        while(($data = fgetcsv($handle)) !== false) {
            if(count($data) < 4) {
                $errors[] = "Invalid data format in row";
                $error_count++;
                continue;
            }
            
            $employee_code = trim($data[0]);
            $attendance_date = trim($data[1]);
            $in_time = trim($data[2]);
            $out_time = trim($data[3]);
            
            // Validate data
            if(empty($employee_code) || empty($attendance_date)) {
                $errors[] = "Missing required data for employee: $employee_code";
                $error_count++;
                continue;
            }
            
            try {
                // Get employee ID from code
                $emp_query = "SELECT id FROM employees WHERE employee_code = :code";
                $emp_stmt = $conn->prepare($emp_query);
                $emp_stmt->bindParam(':code', $employee_code);
                $emp_stmt->execute();
                
                if($emp_stmt->rowCount() === 0) {
                    throw new Exception("Employee not found: $employee_code");
                }
                
                $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
                $employee_id = $employee['id'];
                
                // Check if attendance already exists
                $check_query = "SELECT id FROM attendance 
                               WHERE employee_id = :employee_id 
                               AND attendance_date = :attendance_date";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bindParam(':employee_id', $employee_id);
                $check_stmt->bindParam(':attendance_date', $attendance_date);
                $check_stmt->execute();
                
                if($check_stmt->rowCount() > 0) {
                    // Update existing record
                    $update_query = "UPDATE attendance SET
                        in_time = :in_time,
                        out_time = :out_time,
                        total_working_hours = :total_hours
                        WHERE employee_id = :employee_id 
                        AND attendance_date = :attendance_date";
                    
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':employee_id', $employee_id);
                    $update_stmt->bindParam(':attendance_date', $attendance_date);
                    $update_stmt->bindParam(':in_time', $in_time);
                    $update_stmt->bindParam(':out_time', $out_time);
                    
                    // Calculate working hours
                    $total_hours = 0;
                    if($in_time && $out_time) {
                        $in_timestamp = strtotime($in_time);
                        $out_timestamp = strtotime($out_time);
                        if($out_timestamp > $in_timestamp) {
                            $total_hours = round(($out_timestamp - $in_timestamp) / 3600, 2);
                        }
                    }
                    $update_stmt->bindParam(':total_hours', $total_hours);
                    
                    if(!$update_stmt->execute()) {
                        throw new Exception("Failed to update attendance");
                    }
                } else {
                    // Insert new record
                    $insert_query = "INSERT INTO attendance (
                        employee_id, 
                        attendance_date, 
                        in_time, 
                        out_time,
                        total_working_hours,
                        attendance_status
                    ) VALUES (
                        :employee_id, 
                        :attendance_date, 
                        :in_time, 
                        :out_time,
                        :total_hours,
                        'Present'
                    )";
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bindParam(':employee_id', $employee_id);
                    $insert_stmt->bindParam(':attendance_date', $attendance_date);
                    $insert_stmt->bindParam(':in_time', $in_time);
                    $insert_stmt->bindParam(':out_time', $out_time);
                    
                    // Calculate working hours
                    $total_hours = 0;
                    if($in_time && $out_time) {
                        $in_timestamp = strtotime($in_time);
                        $out_timestamp = strtotime($out_time);
                        if($out_timestamp > $in_timestamp) {
                            $total_hours = round(($out_timestamp - $in_timestamp) / 3600, 2);
                        }
                    }
                    $insert_stmt->bindParam(':total_hours', $total_hours);
                    
                    if(!$insert_stmt->execute()) {
                        throw new Exception("Failed to insert attendance");
                    }
                }
                
                $success_count++;
                
            } catch(Exception $e) {
                $errors[] = "Error for $employee_code: " . $e->getMessage();
                $error_count++;
            }
        }
        
        fclose($handle);
        
        if($success_count > 0) {
            $conn->commit();
            
            // Log activity
            logActivity($current_user['id'], 'Bulk Upload', 'Attendance', 
                       "Uploaded attendance for $success_count employees");
            
            $message = "Successfully uploaded $success_count attendance records.";
            if($error_count > 0) {
                $message .= " $error_count records failed.";
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'data' => [
                    'success' => $success_count,
                    'failed' => $error_count,
                    'errors' => $errors
                ]
            ]);
        } else {
            $conn->rollBack();
            throw new Exception("No records were uploaded. " . implode(', ', $errors));
        }
        
    } catch(Exception $e) {
        if(isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function logActivity($user_id, $action, $module, $details = '') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, module, details, ip_address, user_agent) 
              VALUES (:user_id, :action, :module, :details, :ip, :ua)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':module', $module);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':ua', $_SERVER['HTTP_USER_AGENT']);
    
    return $stmt->execute();
}
?>