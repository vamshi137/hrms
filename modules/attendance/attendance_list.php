<?php
require_once '../../middleware/hr_only.php';
require_once '../../config/db.php';

$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_employee = $_GET['employee'] ?? '';
$filter_department = $_GET['department'] ?? '';
$filter_status = $_GET['status'] ?? '';

$page_title = 'Attendance List';
require_once '../../includes/head.php';
require_once '../../includes/navbar.php';
require_once '../../includes/sidebar_hr.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">Attendance Management</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/hr_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Attendance List</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <a href="attendance_bulk_upload.php" class="btn btn-success">
                        <i class="fas fa-upload mr-2"></i>Bulk Upload
                    </a>
                    <a href="attendance_report.php" class="btn btn-info">
                        <i class="fas fa-chart-bar mr-2"></i>Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="date" class="form-control" 
                                       value="<?php echo $filter_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Employee</label>
                                <select name="employee" class="form-control select2">
                                    <option value="">All Employees</option>
                                    <?php
                                    $emp_query = "SELECT id, employee_code, full_name FROM employees 
                                                 WHERE status = 'Active' ORDER BY full_name";
                                    $emp_stmt = $conn->prepare($emp_query);
                                    $emp_stmt->execute();
                                    while($emp = $emp_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = $filter_employee == $emp['id'] ? 'selected' : '';
                                        echo "<option value='{$emp['id']}' $selected>
                                              {$emp['full_name']} ({$emp['employee_code']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department" class="form-control">
                                    <option value="">All Departments</option>
                                    <?php
                                    $dept_query = "SELECT * FROM departments WHERE status = 'Active'";
                                    $dept_stmt = $conn->prepare($dept_query);
                                    $dept_stmt->execute();
                                    while($dept = $dept_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = $filter_department == $dept['id'] ? 'selected' : '';
                                        echo "<option value='{$dept['id']}' $selected>{$dept['department_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Present" <?php echo $filter_status == 'Present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="Absent" <?php echo $filter_status == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="Half Day" <?php echo $filter_status == 'Half Day' ? 'selected' : ''; ?>>Half Day</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-0">Present</h6>
                                <h2 class="mb-0" id="presentCount">0</h2>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-0">Absent</h6>
                                <h2 class="mb-0" id="absentCount">0</h2>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-times fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-0">Late</h6>
                                <h2 class="mb-0" id="lateCount">0</h2>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-0">On Time</h6>
                                <h2 class="mb-0" id="ontimeCount">0</h2>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    Attendance for <?php echo date('d F Y', strtotime($filter_date)); ?>
                </h5>
                <div>
                    <button class="btn btn-sm btn-primary" onclick="exportAttendance()">
                        <i class="fas fa-download mr-1"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Total Hours</th>
                                <th>Late Mark</th>
                                <th>Overtime</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query based on filters
                            $query = "SELECT a.*, 
                                     e.employee_code, e.full_name, 
                                     d.department_name,
                                     TIMEDIFF(a.out_time, a.in_time) as working_time
                                     FROM attendance a
                                     LEFT JOIN employees e ON a.employee_id = e.id
                                     LEFT JOIN departments d ON e.department_id = d.id
                                     WHERE a.attendance_date = :date";
                            
                            $params = [':date' => $filter_date];
                            
                            if(!empty($filter_employee)) {
                                $query .= " AND a.employee_id = :employee";
                                $params[':employee'] = $filter_employee;
                            }
                            
                            if(!empty($filter_department)) {
                                $query .= " AND e.department_id = :department";
                                $params[':department'] = $filter_department;
                            }
                            
                            if(!empty($filter_status)) {
                                $query .= " AND a.attendance_status = :status";
                                $params[':status'] = $filter_status;
                            }
                            
                            $query .= " ORDER BY a.attendance_date DESC, e.full_name ASC";
                            
                            $stmt = $conn->prepare($query);
                            $stmt->execute($params);
                            $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Count statistics
                            $present_count = 0;
                            $absent_count = 0;
                            $late_count = 0;
                            $ontime_count = 0;
                            
                            $count = 1;
                            foreach($attendance_records as $record):
                                if($record['attendance_status'] == 'Present') $present_count++;
                                if($record['attendance_status'] == 'Absent') $absent_count++;
                                if($record['late_marks'] == 'Yes') $late_count++;
                                if($record['late_marks'] == 'No' && $record['attendance_status'] == 'Present') $ontime_count++;
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <img src="../../uploads/profile_photos/default_user.png" 
                                                 class="rounded-circle" width="32" height="32" 
                                                 alt="<?php echo htmlspecialchars($record['full_name']); ?>">
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($record['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo $record['employee_code']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $record['department_name'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if($record['in_time']): ?>
                                        <span class="badge badge-success">
                                            <?php echo date('h:i A', strtotime($record['in_time'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">No Check-in</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($record['out_time']): ?>
                                        <span class="badge badge-info">
                                            <?php echo date('h:i A', strtotime($record['out_time'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No Check-out</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($record['total_working_hours'] > 0): ?>
                                        <span class="font-weight-bold">
                                            <?php echo $record['total_working_hours']; ?> hrs
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($record['late_marks'] == 'Yes'): ?>
                                        <span class="badge badge-warning">Late</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">On Time</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($record['overtime_hours'] > 0): ?>
                                        <span class="badge badge-danger">
                                            <?php echo $record['overtime_hours']; ?> hrs
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $record['attendance_status'];
                                    $badge_color = $status == 'Present' ? 'success' : 
                                                  ($status == 'Absent' ? 'danger' : 'warning');
                                    echo "<span class='badge badge-$badge_color'>$status</span>";
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewAttendance(<?php echo $record['id']; ?>)"
                                                title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editAttendance(<?php echo $record['id']; ?>)"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteAttendance(<?php echo $record['id']; ?>)"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Employees without attendance -->
                            <?php
                            $absent_query = "SELECT e.*, d.department_name 
                                            FROM employees e
                                            LEFT JOIN departments d ON e.department_id = d.id
                                            WHERE e.status = 'Active' 
                                            AND e.id NOT IN (
                                                SELECT employee_id FROM attendance 
                                                WHERE attendance_date = :date
                                            )";
                            
                            if(!empty($filter_department)) {
                                $absent_query .= " AND e.department_id = :dept";
                            }
                            
                            $absent_stmt = $conn->prepare($absent_query);
                            $absent_stmt->bindParam(':date', $filter_date);
                            if(!empty($filter_department)) {
                                $absent_stmt->bindParam(':dept', $filter_department);
                            }
                            $absent_stmt->execute();
                            $absent_employees = $absent_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach($absent_employees as $emp):
                                if(!empty($filter_employee) && $emp['id'] != $filter_employee) continue;
                                $absent_count++;
                            ?>
                            <tr class="table-danger">
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <img src="../../uploads/profile_photos/default_user.png" 
                                                 class="rounded-circle" width="32" height="32" 
                                                 alt="<?php echo htmlspecialchars($emp['full_name']); ?>">
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($emp['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo $emp['employee_code']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $emp['department_name'] ?? 'N/A'; ?></td>
                                <td colspan="3" class="text-center">
                                    <span class="badge badge-danger">ABSENT</span>
                                </td>
                                <td colspan="4" class="text-center">
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="markAttendance(<?php echo $emp['id']; ?>)">
                                        <i class="fas fa-plus mr-1"></i>Mark Attendance
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Attendance Modal -->
<div class="modal fade" id="viewAttendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="attendanceDetails">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Attendance</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="editAttendanceForm">
                <!-- Form loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Mark Attendance Modal -->
<div class="modal fade" id="markAttendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Attendance</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="manualAttendanceForm">
                    <input type="hidden" id="manualEmployeeId">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="manualDate" class="form-control" 
                               value="<?php echo $filter_date; ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check In Time</label>
                                <input type="time" id="manualInTime" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check Out Time</label>
                                <input type="time" id="manualOutTime" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="manualStatus" class="form-control">
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Comp Off">Comp Off</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea id="manualRemarks" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveManualAttendance()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
// Update statistics
$(document).ready(function() {
    $('#presentCount').text(<?php echo $present_count; ?>);
    $('#absentCount').text(<?php echo $absent_count; ?>);
    $('#lateCount').text(<?php echo $late_count; ?>);
    $('#ontimeCount').text(<?php echo $ontime_count; ?>);
    
    // Initialize DataTable
    $('.datatable').DataTable({
        "pageLength": 25,
        "responsive": true,
        "order": [[0, 'asc']],
        "dom": '<"top"fl<"clear">>rt<"bottom"ip<"clear">>',
        "language": {
            "search": "Search Employee:",
            "lengthMenu": "Show _MENU_ entries"
        }
    });
});

function resetFilters() {
    window.location.href = 'attendance_list.php';
}

function viewAttendance(id) {
    $.ajax({
        url: '../../api/attendance_api.php',
        type: 'GET',
        data: {
            action: 'get_attendance_details',
            id: id
        },
        success: function(response) {
            $('#attendanceDetails').html(response);
            $('#viewAttendanceModal').modal('show');
        }
    });
}

function editAttendance(id) {
    $.ajax({
        url: '../../api/attendance_api.php',
        type: 'GET',
        data: {
            action: 'get_attendance_edit_form',
            id: id
        },
        success: function(response) {
            $('#editAttendanceForm').html(response);
            $('#editAttendanceModal').modal('show');
        }
    });
}

function deleteAttendance(id) {
    if(confirm('Are you sure you want to delete this attendance record?')) {
        $.ajax({
            url: '../../actions/attendance_actions.php',
            type: 'POST',
            data: {
                action: 'delete',
                id: id
            },
            success: function(response) {
                const result = JSON.parse(response);
                if(result.success) {
                    location.reload();
                } else {
                    alert(result.message);
                }
            }
        });
    }
}

function markAttendance(employeeId) {
    $('#manualEmployeeId').val(employeeId);
    $('#markAttendanceModal').modal('show');
}

function saveManualAttendance() {
    const data = {
        action: 'manual_mark',
        employee_id: $('#manualEmployeeId').val(),
        date: $('#manualDate').val(),
        in_time: $('#manualInTime').val(),
        out_time: $('#manualOutTime').val(),
        status: $('#manualStatus').val(),
        remarks: $('#manualRemarks').val()
    };
    
    $.ajax({
        url: '../../actions/attendance_actions.php',
        type: 'POST',
        data: data,
        success: function(response) {
            const result = JSON.parse(response);
            if(result.success) {
                $('#markAttendanceModal').modal('hide');
                location.reload();
            } else {
                alert(result.message);
            }
        }
    });
}

function exportAttendance() {
    const date = '<?php echo $filter_date; ?>';
    const employee = '<?php echo $filter_employee; ?>';
    const department = '<?php echo $filter_department; ?>';
    const status = '<?php echo $filter_status; ?>';
    
    window.location.href = `../../api/attendance_api.php?action=export&date=${date}&employee=${employee}&department=${department}&status=${status}`;
}
</script>

<?php require_once '../../includes/footer.php'; ?>