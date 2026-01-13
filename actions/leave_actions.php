<?php
require_once '../config/db.php';
require_once '../core/auth.php';
require_once '../core/validator.php';
require_once '../core/uploader.php';

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

switch($action) {
    case 'add_leave_type':
        addLeaveType($conn, $auth);
        break;
        
    case 'get_leave_type':
        getLeaveType($conn);
        break;
        
    case 'update_leave_type':
        updateLeaveType($conn, $auth);
        break;
        
    case 'delete_leave_type':
        deleteLeaveType($conn, $auth);
        break;
        
    case 'save_policy':
        saveLeavePolicy($conn, $auth);
        break;
        
    case 'apply_leave':
        applyLeave($conn, $auth);
        break;
        
    case 'get_leave_balance':
        getLeaveBalance($conn, $auth);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addLeaveType($conn, $auth) {
    try {
        $data = $_POST;
        
        // Validate required fields
        if(empty($data['leave_name'])) {
            throw new Exception("Leave type name is required");
        }
        
        // Check if leave type already exists
        $check_query = "SELECT id FROM leave_types WHERE leave_name = :leave_name";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':leave_name', $data['leave_name']);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            throw new Exception("Leave type already exists");
        }
        
        // Insert leave type
        $query = "INSERT INTO leave_types (
            leave_name,
            leave_code,
            description,
            max_days_per_year,
            min_days_per_request,
            allow_carry_forward,
            max_carry_forward,
            requires_approval,
            is_earning_leave,
            allow_encashment,
            is_paid_leave,
            status,
            created_by,
            created_at
        ) VALUES (
            :leave_name,
            :leave_code,
            :description,
            :max_days_per_year,
            :min_days_per_request,
            :allow_carry_forward,
            :max_carry_forward,
            :requires_approval,
            :is_earning_leave,
            :allow_encashment,
            :is_paid_leave,
            :status,
            :created_by,
            NOW()
        )";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':leave_name', $data['leave_name']);
        $stmt->bindParam(':leave_code', $data['leave_code']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':max_days_per_year', $data['max_days_per_year']);
        $stmt->bindParam(':min_days_per_request', $data['min_days_per_request']);
        $stmt->bindParam(':allow_carry_forward', $data['allow_carry_forward']);
        $stmt->bindParam(':max_carry_forward', $data['max_carry_forward']);
        $stmt->bindParam(':requires_approval', $data['requires_approval']);
        $stmt->bindParam(':is_earning_leave', $data['is_earning_leave']);
        $stmt->bindParam(':allow_encashment', $data['allow_encashment']);
        $stmt->bindParam(':is_paid_leave', $data['is_paid_leave']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':created_by', $auth->getCurrentUser()['id']);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to save leave type: " . implode(', ', $stmt->errorInfo()));
        }
        
        // Log activity
        logActivity($auth->getCurrentUser()['id'], 'Add', 'Leave Type', "Added leave type: {$data['leave_name']}");
        
        echo json_encode(['success' => true, 'message' => 'Leave type added successfully']);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getLeaveType($conn) {
    try {
        $id = $_GET['id'] ?? 0;
        
        if(empty($id)) {
            throw new Exception("Leave type ID is required");
        }
        
        $query = "SELECT * FROM leave_types WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() === 0) {
            throw new Exception("Leave type not found");
        }
        
        $leave_type = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generate edit form HTML
        $html = '<form id="editLeaveTypeForm">';
        $html .= '<input type="hidden" name="id" value="' . $leave_type['id'] . '">';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Leave Type Name *</label>';
        $html .= '<input type="text" name="leave_name" class="form-control" 
                        value="' . htmlspecialchars($leave_type['leave_name']) . '" required>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Leave Code</label>';
        $html .= '<input type="text" name="leave_code" class="form-control" 
                        value="' . htmlspecialchars($leave_type['leave_code']) . '">';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Description</label>';
        $html .= '<textarea name="description" class="form-control" rows="3">' . 
                 htmlspecialchars($leave_type['description']) . '</textarea>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Maximum Days Per Year</label>';
        $html .= '<input type="number" name="max_days_per_year" class="form-control" 
                        value="' . $leave_type['max_days_per_year'] . '" min="0" step="0.5">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Minimum Days Per Request</label>';
        $html .= '<input type="number" name="min_days_per_request" class="form-control" 
                        value="' . $leave_type['min_days_per_request'] . '" min="0" step="0.5">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Allow Carry Forward</label>';
        $html .= '<select name="allow_carry_forward" class="form-control">';
        $html .= '<option value="0"' . ($leave_type['allow_carry_forward'] == 0 ? ' selected' : '') . '>No</option>';
        $html .= '<option value="1"' . ($leave_type['allow_carry_forward'] == 1 ? ' selected' : '') . '>Yes</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Max Carry Forward Days</label>';
        $html .= '<input type="number" name="max_carry_forward" class="form-control" 
                        value="' . $leave_type['max_carry_forward'] . '" min="0">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Requires Approval</label>';
        $html .= '<select name="requires_approval" class="form-control">';
        $html .= '<option value="1"' . ($leave_type['requires_approval'] == 1 ? ' selected' : '') . '>Yes</option>';
        $html .= '<option value="0"' . ($leave_type['requires_approval'] == 0 ? ' selected' : '') . '>Auto Approved</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<div class="form-check">';
        $html .= '<input type="checkbox" class="form-check-input" name="is_earning_leave" value="1" 
                         id="editIsEarningLeave"' . ($leave_type['is_earning_leave'] == 1 ? ' checked' : '') . '>';
        $html .= '<label class="form-check-label" for="editIsEarningLeave">Is Earning Leave</label>';
        $html .= '</div>';
        $html .= '<div class="form-check">';
        $html .= '<input type="checkbox" class="form-check-input" name="allow_encashment" value="1" 
                         id="editAllowEncashment"' . ($leave_type['allow_encashment'] == 1 ? ' checked' : '') . '>';
        $html .= '<label class="form-check-label" for="editAllowEncashment">Allow Leave Encashment</label>';
        $html .= '</div>';
        $html .= '<div class="form-check">';
        $html .= '<input type="checkbox" class="form-check-input" name="is_paid_leave" value="1" 
                         id="editIsPaidLeave"' . ($leave_type['is_paid_leave'] == 1 ? ' checked' : '') . '>';
        $html .= '<label class="form-check-label" for="editIsPaidLeave">Paid Leave</label>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Status</label>';
        $html .= '<select name="status" class="form-control">';
        $html .= '<option value="Active"' . ($leave_type['status'] == 'Active' ? ' selected' : '') . '>Active</option>';
        $html .= '<option value="Inactive"' . ($leave_type['status'] == 'Inactive' ? ' selected' : '') . '>Inactive</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '</form>';
        
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>';
        $html .= '<button type="button" class="btn btn-primary" onclick="updateLeaveType()">Update Leave Type</button>';
        $html .= '</div>';
        
        echo $html;
        
    } catch(Exception $e) {
        echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

function updateLeaveType($conn, $auth) {
    try {
        $data = $_POST;
        
        if(empty($data['id'])) {
            throw new Exception("Leave type ID is required");
        }
        
        $query = "UPDATE leave_types SET
            leave_name = :leave_name,
            leave_code = :leave_code,
            description = :description,
            max_days_per_year = :max_days_per_year,
            min_days_per_request = :min_days_per_request,
            allow_carry_forward = :allow_carry_forward,
            max_carry_forward = :max_carry_forward,
            requires_approval = :requires_approval,
            is_earning_leave = :is_earning_leave,
            allow_encashment = :allow_encashment,
            is_paid_leave = :is_paid_leave,
            status = :status,
            updated_by = :updated_by,
            updated_at = NOW()
            WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':leave_name', $data['leave_name']);
        $stmt->bindParam(':leave_code', $data['leave_code']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':max_days_per_year', $data['max_days_per_year']);
        $stmt->bindParam(':min_days_per_request', $data['min_days_per_request']);
        $stmt->bindParam(':allow_carry_forward', $data['allow_carry_forward']);
        $stmt->bindParam(':max_carry_forward', $data['max_carry_forward']);
        $stmt->bindParam(':requires_approval', $data['requires_approval']);
        $stmt->bindParam(':is_earning_leave', $data['is_earning_leave']);
        $stmt->bindParam(':allow_encashment', $data['allow_encashment']);
        $stmt->bindParam(':is_paid_leave', $data['is_paid_leave']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':updated_by', $auth->getCurrentUser()['id']);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to update leave type: " . implode(', ', $stmt->errorInfo()));
        }
        
        // Log activity
        logActivity($auth->getCurrentUser()['id'], 'Update', 'Leave Type', "Updated leave type: {$data['leave_name']}");
        
        echo json_encode(['success' => true, 'message' => 'Leave type updated successfully']);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteLeaveType($conn, $auth) {
    try {
        $id = $_POST['id'] ?? 0;
        
        if(empty($id)) {
            throw new Exception("Leave type ID is required");
        }
        
        // Check if leave type is being used
        $check_query = "SELECT COUNT(*) as count FROM leave_applications WHERE leave_type_id = :id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':id', $id);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result['count'] > 0) {
            throw new Exception("Cannot delete leave type. It is being used in leave applications.");
        }
        
        // Get leave type name for logging
        $name_query = "SELECT leave_name FROM leave_types WHERE id = :id";
        $name_stmt = $conn->prepare($name_query);
        $name_stmt->bindParam(':id', $id);
        $name_stmt->execute();
        $leave_type = $name_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete leave type
        $delete_query = "DELETE FROM leave_types WHERE id = :id";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bindParam(':id', $id);
        
        if(!$delete_stmt->execute()) {
            throw new Exception("Failed to delete leave type");
        }
        
        // Log activity
        logActivity($auth->getCurrentUser()['id'], 'Delete', 'Leave Type', "Deleted leave type: {$leave_type['leave_name']}");
        
        echo json_encode(['success' => true, 'message' => 'Leave type deleted successfully']);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function applyLeave($conn, $auth) {
    try {
        $employee_id = $auth->getCurrentUser()['employee_id'];
        $user_id = $auth->getCurrentUser()['id'];
        $data = $_POST;
        
        // Validate required fields
        $required_fields = ['leave_type_id', 'from_date', 'to_date', 'reason'];
        foreach($required_fields as $field) {
            if(empty($data[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Validate dates
        if(!Validator::validateDate($data['from_date']) || !Validator::validateDate($data['to_date'])) {
            throw new Exception("Invalid date format");
        }
        
        $from_date = $data['from_date'];
        $to_date = $data['to_date'];
        
        if(strtotime($from_date) > strtotime($to_date)) {
            throw new Exception("From date cannot be after To date");
        }
        
        // Calculate number of days
        $number_of_days = calculateLeaveDays($from_date, $to_date, $data['half_day_type'] ?? '');
        
        // Check leave balance
        $balance = checkLeaveBalance($conn, $employee_id, $data['leave_type_id'], $number_of_days);
        
        if(!$balance['has_sufficient']) {
            throw new Exception("Insufficient leave balance. Available: {$balance['available']} days, Required: $number_of_days days");
        }
        
        // Check for overlapping leave applications
        $overlap_query = "SELECT id FROM leave_applications 
                         WHERE employee_id = :employee_id 
                         AND leave_status IN ('Pending', 'Approved')
                         AND (
                            (from_date BETWEEN :from_date AND :to_date) OR
                            (to_date BETWEEN :from_date AND :to_date) OR
                            (:from_date BETWEEN from_date AND to_date)
                         )";
        
        $overlap_stmt = $conn->prepare($overlap_query);
        $overlap_stmt->bindParam(':employee_id', $employee_id);
        $overlap_stmt->bindParam(':from_date', $from_date);
        $overlap_stmt->bindParam(':to_date', $to_date);
        $overlap_stmt->execute();
        
        if($overlap_stmt->rowCount() > 0) {
            throw new Exception("You already have a leave application for these dates");
        }
        
        // Get employee details for reporting manager
        $emp_query = "SELECT reporting_manager_id FROM employees WHERE id = :id";
        $emp_stmt = $conn->prepare($emp_query);
        $emp_stmt->bindParam(':id', $employee_id);
        $emp_stmt->execute();
        $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Handle file uploads
        $supporting_docs = [];
        if(isset($_FILES['supporting_docs']) && !empty($_FILES['supporting_docs']['name'][0])) {
            $uploader = new Uploader('../uploads/leave_docs/', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
            
            foreach($_FILES['supporting_docs']['name'] as $key => $name) {
                if($_FILES['supporting_docs']['error'][$key] == UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['supporting_docs']['name'][$key],
                        'type' => $_FILES['supporting_docs']['type'][$key],
                        'tmp_name' => $_FILES['supporting_docs']['tmp_name'][$key],
                        'error' => $_FILES['supporting_docs']['error'][$key],
                        'size' => $_FILES['supporting_docs']['size'][$key]
                    ];
                    
                    $result = $uploader->upload($file, 'leave_' . time() . '_' . $key);
                    
                    if($result['success']) {
                        $supporting_docs[] = $result['file_path'];
                    }
                }
            }
        }
        
        // Generate application number
        $application_number = 'LV-' . date('Ym') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Start transaction
        $conn->beginTransaction();
        
        // Insert leave application
        $query = "INSERT INTO leave_applications (
            employee_id,
            application_number,
            leave_application_date,
            leave_type_id,
            from_date,
            to_date,
            number_of_days,
            half_day_type,
            leave_category,
            mode_of_application,
            reason,
            contact_number,
            contact_address,
            handover_notes,
            supporting_docs,
            reporting_manager_id,
            manager_approval,
            hr_approval,
            leave_status,
            applied_by,
            applied_at
        ) VALUES (
            :employee_id,
            :application_number,
            CURDATE(),
            :leave_type_id,
            :from_date,
            :to_date,
            :number_of_days,
            :half_day_type,
            :leave_category,
            :mode_of_application,
            :reason,
            :contact_number,
            :contact_address,
            :handover_notes,
            :supporting_docs,
            :reporting_manager_id,
            'Pending',
            'Pending',
            'Pending',
            :applied_by,
            NOW()
        )";
        
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':application_number', $application_number);
        $stmt->bindParam(':leave_type_id', $data['leave_type_id']);
        $stmt->bindParam(':from_date', $from_date);
        $stmt->bindParam(':to_date', $to_date);
        $stmt->bindParam(':number_of_days', $number_of_days);
        $stmt->bindParam(':half_day_type', $data['half_day_type']);
        $stmt->bindParam(':leave_category', $data['leave_category']);
        $stmt->bindParam(':mode_of_application', $data['mode_of_application']);
        $stmt->bindParam(':reason', $data['reason']);
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':contact_address', $data['contact_address']);
        $stmt->bindParam(':handover_notes', $data['handover_notes']);
        $stmt->bindValue(':supporting_docs', !empty($supporting_docs) ? json_encode($supporting_docs) : null);
        $stmt->bindParam(':reporting_manager_id', $employee['reporting_manager_id']);
        $stmt->bindParam(':applied_by', $user_id);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to submit leave application: " . implode(', ', $stmt->errorInfo()));
        }
        
        $application_id = $conn->lastInsertId();
        
        // Deduct leave balance (temporary hold)
        deductLeaveBalance($conn, $employee_id, $data['leave_type_id'], $number_of_days);
        
        // Send notification to reporting manager
        sendLeaveNotification($conn, $employee_id, $employee['reporting_manager_id'], $application_id);
        
        // Commit transaction
        $conn->commit();
        
        // Log activity
        logActivity($user_id, 'Apply', 'Leave', "Applied for leave (ID: $application_number)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Leave application submitted successfully',
            'application_id' => $application_number
        ]);
        
    } catch(Exception $e) {
        if(isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function calculateLeaveDays($from_date, $to_date, $half_day_type = '') {
    $start = new DateTime($from_date);
    $end = new DateTime($to_date);
    $end->modify('+1 day'); // Include end date
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $days = 0;
    foreach($period as $date) {
        // Skip weekends (Saturday = 6, Sunday = 0)
        $day_of_week = $date->format('w');
        if($day_of_week == 0 || $day_of_week == 6) {
            continue;
        }
        
        $days++;
    }
    
    // Adjust for half day
    if($half_day_type && $days == 1) {
        $days = 0.5;
    }
    
    return $days;
}

function checkLeaveBalance($conn, $employee_id, $leave_type_id, $requested_days) {
    $query = "SELECT COALESCE(balance, 0) as available 
              FROM leave_balance 
              WHERE employee_id = :employee_id 
              AND leave_type_id = :leave_type_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':leave_type_id', $leave_type_id);
    $stmt->execute();
    
    $balance = $stmt->fetch(PDO::FETCH_ASSOC);
    $available = $balance ? $balance['available'] : 0;
    
    return [
        'available' => $available,
        'has_sufficient' => $available >= $requested_days
    ];
}

function deductLeaveBalance($conn, $employee_id, $leave_type_id, $days) {
    // Check if balance record exists
    $check_query = "SELECT id FROM leave_balance 
                   WHERE employee_id = :employee_id 
                   AND leave_type_id = :leave_type_id";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':employee_id', $employee_id);
    $check_stmt->bindParam(':leave_type_id', $leave_type_id);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        // Update existing balance
        $update_query = "UPDATE leave_balance 
                        SET balance = balance - :days,
                        updated_at = NOW()
                        WHERE employee_id = :employee_id 
                        AND leave_type_id = :leave_type_id";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':employee_id', $employee_id);
        $update_stmt->bindParam(':leave_type_id', $leave_type_id);
        $update_stmt->bindParam(':days', $days);
        $update_stmt->execute();
    } else {
        // Insert new balance (shouldn't happen but just in case)
        $insert_query = "INSERT INTO leave_balance (employee_id, leave_type_id, balance)
                        VALUES (:employee_id, :leave_type_id, -:days)";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':employee_id', $employee_id);
        $insert_stmt->bindParam(':leave_type_id', $leave_type_id);
        $insert_stmt->bindParam(':days', $days);
        $insert_stmt->execute();
    }
}

function sendLeaveNotification($conn, $employee_id, $manager_id, $application_id) {
    // Get employee details
    $emp_query = "SELECT full_name, employee_code FROM employees WHERE id = :id";
    $emp_stmt = $conn->prepare($emp_query);
    $emp_stmt->bindParam(':id', $employee_id);
    $emp_stmt->execute();
    $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get application details
    $app_query = "SELECT application_number, from_date, to_date FROM leave_applications WHERE id = :id";
    $app_stmt = $conn->prepare($app_query);
    $app_stmt->bindParam(':id', $application_id);
    $app_stmt->execute();
    $application = $app_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notification
    $notification_query = "INSERT INTO notifications (
        user_id,
        title,
        message,
        type,
        status,
        created_at
    ) VALUES (
        :user_id,
        :title,
        :message,
        :type,
        'Unread',
        NOW()
    )";
    
    $notification_stmt = $conn->prepare($notification_query);
    
    $title = "New Leave Application";
    $message = "{$employee['full_name']} ({$employee['employee_code']}) has applied for leave from " . 
               date('d-m-Y', strtotime($application['from_date'])) . " to " . 
               date('d-m-Y', strtotime($application['to_date'])) . 
               ". Application ID: {$application['application_number']}";
    
    $notification_stmt->bindParam(':user_id', $manager_id);
    $notification_stmt->bindParam(':title', $title);
    $notification_stmt->bindParam(':message', $message);
    $notification_stmt->bindValue(':type', 'Leave Approval');
    
    $notification_stmt->execute();
    
    // Also send email notification (if email system is configured)
    sendEmailNotification($manager_id, $title, $message);
}

function sendEmailNotification($user_id, $subject, $body) {
    // This is a placeholder for email notification
    // In production, implement actual email sending
    // using PHPMailer or similar library
    
    // Get user email
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT email FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && !empty($user['email'])) {
        // Send email here
        // mail($user['email'], $subject, $body);
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