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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$database = new Database();
$conn = $database->getConnection();

switch($action) {
    case 'get_monthly_attendance':
        getMonthlyAttendance($conn, $auth);
        break;
        
    case 'get_attendance_details':
        getAttendanceDetails($conn);
        break;
        
    case 'get_attendance_edit_form':
        getAttendanceEditForm($conn);
        break;
        
    case 'export':
        exportAttendance($conn);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getMonthlyAttendance($conn, $auth) {
    try {
        $month = $_GET['month'] ?? date('Y-m');
        $employee_id = $auth->getCurrentUser()['employee_id'];
        
        // Get start and end date of month
        $start_date = date('Y-m-01', strtotime($month));
        $end_date = date('Y-m-t', strtotime($month));
        
        // Get all days in month
        $days_in_month = date('t', strtotime($month));
        $current_month = date('m', strtotime($month));
        $current_year = date('Y', strtotime($month));
        
        // Get attendance for the month
        $query = "SELECT attendance_date, attendance_status, 
                  in_time, out_time, total_working_hours,
                  late_marks, overtime_hours
                  FROM attendance 
                  WHERE employee_id = :employee_id 
                  AND attendance_date BETWEEN :start_date AND :end_date
                  ORDER BY attendance_date";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $attendance_data = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attendance_data[$row['attendance_date']] = $row;
        }
        
        // Generate calendar HTML
        $html = '<div class="attendance-calendar">';
        $html .= '<div class="calendar-header d-flex justify-content-between mb-3">';
        $html .= '<button class="btn btn-sm btn-secondary" onclick="prevMonth()">';
        $html .= '<i class="fas fa-chevron-left mr-1"></i> Previous</button>';
        $html .= '<h5 class="mb-0">' . date('F Y', strtotime($month)) . '</h5>';
        $html .= '<button class="btn btn-sm btn-secondary" onclick="nextMonth()">';
        $html .= 'Next <i class="fas fa-chevron-right ml-1"></i></button>';
        $html .= '</div>';
        
        $html .= '<div class="calendar-grid">';
        
        // Day headers
        $html .= '<div class="calendar-week">';
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach($days as $day) {
            $html .= '<div class="calendar-day-header">' . $day . '</div>';
        }
        $html .= '</div>';
        
        // Get first day of month
        $first_day = date('w', strtotime($start_date));
        
        // Generate days
        $day_count = 1;
        $html .= '<div class="calendar-week">';
        
        // Empty cells for days before first day
        for($i = 0; $i < $first_day; $i++) {
            $html .= '<div class="calendar-day empty"></div>';
        }
        
        // Actual days
        for($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
            $is_today = ($date == date('Y-m-d')) ? 'today' : '';
            $is_weekend = (date('w', strtotime($date)) == 0 || date('w', strtotime($date)) == 6) ? 'weekend' : '';
            
            $attendance = $attendance_data[$date] ?? null;
            $attendance_class = '';
            $attendance_info = '';
            
            if($attendance) {
                switch($attendance['attendance_status']) {
                    case 'Present':
                        $attendance_class = 'present';
                        $attendance_info = '<div class="attendance-time">';
                        if($attendance['in_time']) {
                            $attendance_info .= '<small>' . date('H:i', strtotime($attendance['in_time'])) . '</small>';
                        }
                        if($attendance['out_time']) {
                            $attendance_info .= '<small>' . date('H:i', strtotime($attendance['out_time'])) . '</small>';
                        }
                        $attendance_info .= '</div>';
                        break;
                    case 'Absent':
                        $attendance_class = 'absent';
                        break;
                    case 'Half Day':
                        $attendance_class = 'halfday';
                        break;
                    default:
                        $attendance_class = 'other';
                }
            }
            
            $html .= '<div class="calendar-day ' . $is_today . ' ' . $is_weekend . ' ' . $attendance_class . '">';
            $html .= '<div class="day-number">' . $day . '</div>';
            $html .= $attendance_info;
            $html .= '</div>';
            
            // Start new week
            if(($first_day + $day) % 7 == 0 && $day != $days_in_month) {
                $html .= '</div><div class="calendar-week">';
            }
        }
        
        // Empty cells for remaining days
        $remaining_days = (7 - (($first_day + $days_in_month) % 7)) % 7;
        for($i = 0; $i < $remaining_days; $i++) {
            $html .= '<div class="calendar-day empty"></div>';
        }
        
        $html .= '</div>'; // Close last week
        $html .= '</div>'; // Close calendar-grid
        
        // Legend
        $html .= '<div class="calendar-legend mt-4">';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-3 mb-2"><span class="legend-box present"></span> Present</div>';
        $html .= '<div class="col-md-3 mb-2"><span class="legend-box absent"></span> Absent</div>';
        $html .= '<div class="col-md-3 mb-2"><span class="legend-box halfday"></span> Half Day</div>';
        $html .= '<div class="col-md-3 mb-2"><span class="legend-box weekend"></span> Weekend</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // Close attendance-calendar
        
        echo $html;
        
    } catch(Exception $e) {
        echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

function getAttendanceDetails($conn) {
    try {
        $id = $_GET['id'] ?? 0;
        
        if(empty($id)) {
            throw new Exception("Attendance ID is required");
        }
        
        $query = "SELECT a.*, 
                 e.employee_code, e.full_name, e.designation_id,
                 d.department_name, ds.designation_name,
                 s.shift_name, s.shift_start, s.shift_end
                 FROM attendance a
                 LEFT JOIN employees e ON a.employee_id = e.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN designations ds ON e.designation_id = ds.id
                 LEFT JOIN shifts s ON e.shift_id = s.id
                 WHERE a.id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() === 0) {
            throw new Exception("Attendance record not found");
        }
        
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $html = '<div class="attendance-details">';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<h6>Employee Information</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><th>Name:</th><td>' . htmlspecialchars($attendance['full_name']) . '</td></tr>';
        $html .= '<tr><th>Employee Code:</th><td>' . $attendance['employee_code'] . '</td></tr>';
        $html .= '<tr><th>Department:</th><td>' . ($attendance['department_name'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><th>Designation:</th><td>' . ($attendance['designation_name'] ?? 'N/A') . '</td></tr>';
        $html .= '<tr><th>Shift:</th><td>' . ($attendance['shift_name'] ?? 'General') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-6">';
        $html .= '<h6>Attendance Details</h6>';
        $html .= '<table class="table table-sm">';
        $html .= '<tr><th>Date:</th><td>' . date('d-m-Y', strtotime($attendance['attendance_date'])) . '</td></tr>';
        $html .= '<tr><th>Status:</th><td>';
        
        $status = $attendance['attendance_status'];
        $badge_color = $status == 'Present' ? 'success' : 
                      ($status == 'Absent' ? 'danger' : 'warning');
        $html .= '<span class="badge badge-' . $badge_color . '">' . $status . '</span>';
        
        $html .= '</td></tr>';
        
        if($attendance['in_time']) {
            $html .= '<tr><th>Check In:</th><td>' . date('h:i A', strtotime($attendance['in_time'])) . '</td></tr>';
        }
        
        if($attendance['out_time']) {
            $html .= '<tr><th>Check Out:</th><td>' . date('h:i A', strtotime($attendance['out_time'])) . '</td></tr>';
        }
        
        if($attendance['total_working_hours'] > 0) {
            $html .= '<tr><th>Working Hours:</th><td>' . $attendance['total_working_hours'] . ' hours</td></tr>';
        }
        
        $html .= '<tr><th>Late Mark:</th><td>';
        $html .= $attendance['late_marks'] == 'Yes' ? 
                 '<span class="badge badge-warning">Late</span>' : 
                 '<span class="badge badge-success">On Time</span>';
        $html .= '</td></tr>';
        
        if($attendance['overtime_hours'] > 0) {
            $html .= '<tr><th>Overtime:</th><td>' . $attendance['overtime_hours'] . ' hours</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Additional information
        $html .= '<div class="row mt-3">';
        $html .= '<div class="col-md-12">';
        $html .= '<h6>Additional Information</h6>';
        
        $html .= '<div class="row">';
        if($attendance['early_entry'] == 'Yes') {
            $html .= '<div class="col-md-3">';
            $html .= '<div class="alert alert-info p-2 text-center">';
            $html .= '<i class="fas fa-arrow-down mr-1"></i> Early Entry';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if($attendance['early_exit'] == 'Yes') {
            $html .= '<div class="col-md-3">';
            $html .= '<div class="alert alert-warning p-2 text-center">';
            $html .= '<i class="fas fa-arrow-up mr-1"></i> Early Exit';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if($attendance['shortage_hours'] > 0) {
            $html .= '<div class="col-md-3">';
            $html .= '<div class="alert alert-danger p-2 text-center">';
            $html .= '<i class="fas fa-clock mr-1"></i> Shortage: ' . $attendance['shortage_hours'] . ' hrs';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if($attendance['overtime_hours'] > 0) {
            $html .= '<div class="col-md-3">';
            $html .= '<div class="alert alert-success p-2 text-center">';
            $html .= '<i class="fas fa-plus-circle mr-1"></i> Overtime: ' . $attendance['overtime_hours'] . ' hrs';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Shift comparison
        if($attendance['shift_start'] && $attendance['shift_end']) {
            $html .= '<div class="row mt-3">';
            $html .= '<div class="col-md-12">';
            $html .= '<h6>Shift Comparison</h6>';
            
            $shift_start = strtotime($attendance['shift_start']);
            $shift_end = strtotime($attendance['shift_end']);
            $in_time = $attendance['in_time'] ? strtotime($attendance['in_time']) : null;
            $out_time = $attendance['out_time'] ? strtotime($attendance['out_time']) : null;
            
            $html .= '<div class="progress" style="height: 30px;">';
            
            // Calculate percentages
            $shift_duration = $shift_end - $shift_start;
            
            if($in_time) {
                $in_position = (($in_time - $shift_start) / $shift_duration) * 100;
                $in_position = max(0, min(100, $in_position));
                
                $html .= '<div class="progress-bar bg-success" style="width: ' . $in_position . '%">';
                $html .= 'In: ' . date('H:i', $in_time);
                $html .= '</div>';
            }
            
            if($out_time) {
                $out_position = (($out_time - $shift_start) / $shift_duration) * 100;
                $out_position = max(0, min(100, $out_position));
                
                $html .= '<div class="progress-bar bg-info" style="width: ' . ($out_position - $in_position) . '%">';
                $html .= 'Working';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            
            $html .= '<div class="d-flex justify-content-between mt-2">';
            $html .= '<small>Shift Start: ' . date('H:i', $shift_start) . '</small>';
            $html .= '<small>Shift End: ' . date('H:i', $shift_end) . '</small>';
            $html .= '</div>';
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        echo $html;
        
    } catch(Exception $e) {
        echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

function getAttendanceEditForm($conn) {
    try {
        $id = $_GET['id'] ?? 0;
        
        if(empty($id)) {
            throw new Exception("Attendance ID is required");
        }
        
        $query = "SELECT a.*, e.full_name, e.employee_code 
                 FROM attendance a
                 LEFT JOIN employees e ON a.employee_id = e.id
                 WHERE a.id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() === 0) {
            throw new Exception("Attendance record not found");
        }
        
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $html = '<form id="editAttendanceFormData">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Employee</label>';
        $html .= '<input type="text" class="form-control" value="' . htmlspecialchars($attendance['full_name']) . ' (' . $attendance['employee_code'] . ')" readonly>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Date</label>';
        $html .= '<input type="date" name="attendance_date" class="form-control" 
                        value="' . $attendance['attendance_date'] . '">';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Check In Time</label>';
        $html .= '<input type="time" name="in_time" class="form-control" 
                        value="' . ($attendance['in_time'] ?? '') . '">';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Check Out Time</label>';
        $html .= '<input type="time" name="out_time" class="form-control" 
                        value="' . ($attendance['out_time'] ?? '') . '">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Status</label>';
        $html .= '<select name="attendance_status" class="form-control">';
        
        $statuses = ['Present', 'Absent', 'Half Day', 'Comp Off', 'Comp Working'];
        foreach($statuses as $status) {
            $selected = $attendance['attendance_status'] == $status ? 'selected' : '';
            $html .= '<option value="' . $status . '" ' . $selected . '>' . $status . '</option>';
        }
        
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Late Mark</label>';
        $html .= '<select name="late_marks" class="form-control">';
        $html .= '<option value="No" ' . ($attendance['late_marks'] == 'No' ? 'selected' : '') . '>No</option>';
        $html .= '<option value="Yes" ' . ($attendance['late_marks'] == 'Yes' ? 'selected' : '') . '>Yes</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Early Exit</label>';
        $html .= '<select name="early_exit" class="form-control">';
        $html .= '<option value="No" ' . ($attendance['early_exit'] == 'No' ? 'selected' : '') . '>No</option>';
        $html .= '<option value="Yes" ' . ($attendance['early_exit'] == 'Yes' ? 'selected' : '') . '>Yes</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Total Working Hours</label>';
        $html .= '<input type="number" name="total_working_hours" class="form-control" 
                        value="' . ($attendance['total_working_hours'] ?? 0) . '" step="0.01">';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-group">';
        $html .= '<label>Overtime Hours</label>';
        $html .= '<input type="number" name="overtime_hours" class="form-control" 
                        value="' . ($attendance['overtime_hours'] ?? 0) . '" step="0.01">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="form-group">';
        $html .= '<label>Remarks</label>';
        $html .= '<textarea name="remarks" class="form-control" rows="3">' . 
                 htmlspecialchars($attendance['remarks'] ?? '') . '</textarea>';
        $html .= '</div>';
        
        $html .= '</form>';
        
        $html .= '<div class="text-right mt-3">';
        $html .= '<button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Cancel</button>';
        $html .= '<button type="button" class="btn btn-primary" onclick="saveAttendanceEdit(' . $id . ')">Save Changes</button>';
        $html .= '</div>';
        
        echo $html;
        
    } catch(Exception $e) {
        echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

function exportAttendance($conn) {
    try {
        // Get filter parameters
        $date = $_GET['date'] ?? date('Y-m-d');
        $employee = $_GET['employee'] ?? '';
        $department = $_GET['department'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // Build query
        $query = "SELECT 
                 e.employee_code,
                 e.full_name,
                 d.department_name,
                 a.attendance_date,
                 a.in_time,
                 a.out_time,
                 a.total_working_hours,
                 a.late_marks,
                 a.overtime_hours,
                 a.attendance_status,
                 a.remarks
                 FROM attendance a
                 LEFT JOIN employees e ON a.employee_id = e.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE a.attendance_date = :date";
        
        $params = [':date' => $date];
        
        if(!empty($employee)) {
            $query .= " AND a.employee_id = :employee";
            $params[':employee'] = $employee;
        }
        
        if(!empty($department)) {
            $query .= " AND e.department_id = :department";
            $params[':department'] = $department;
        }
        
        if(!empty($status)) {
            $query .= " AND a.attendance_status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY e.full_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=attendance_' . $date . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add headers
        fputcsv($output, [
            'Employee Code',
            'Employee Name',
            'Department',
            'Date',
            'Check In',
            'Check Out',
            'Total Hours',
            'Late Mark',
            'Overtime Hours',
            'Status',
            'Remarks'
        ]);
        
        // Add data
        foreach($records as $record) {
            fputcsv($output, [
                $record['employee_code'],
                $record['full_name'],
                $record['department_name'],
                $record['attendance_date'],
                $record['in_time'] ? date('H:i', strtotime($record['in_time'])) : '',
                $record['out_time'] ? date('H:i', strtotime($record['out_time'])) : '',
                $record['total_working_hours'],
                $record['late_marks'],
                $record['overtime_hours'],
                $record['attendance_status'],
                $record['remarks']
            ]);
        }
        
        fclose($output);
        exit();
        
    } catch(Exception $e) {
        echo 'Error exporting data: ' . $e->getMessage();
    }
}
?>