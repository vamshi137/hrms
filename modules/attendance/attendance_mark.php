<?php
require_once '../../middleware/employee_only.php';
require_once '../../config/db.php';
require_once '../../core/helpers.php';

$database = new Database();
$conn = $database->getConnection();
$current_user = Session::get('employee_id');

// Get today's date
$today = date('Y-m-d');

// Check if attendance already marked for today
$attendance_query = "SELECT * FROM attendance 
                    WHERE employee_id = :employee_id 
                    AND attendance_date = :today";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bindParam(':employee_id', $current_user);
$attendance_stmt->bindParam(':today', $today);
$attendance_stmt->execute();

$today_attendance = $attendance_stmt->fetch(PDO::FETCH_ASSOC);

// Get employee shift details
$employee_query = "SELECT e.*, s.shift_name, s.shift_start, s.shift_end, s.grace_minutes 
                   FROM employees e 
                   LEFT JOIN shifts s ON e.shift_id = s.id 
                   WHERE e.id = :id";
$employee_stmt = $conn->prepare($employee_query);
$employee_stmt->bindParam(':id', $current_user);
$employee_stmt->execute();
$employee = $employee_stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Mark Attendance';
require_once '../../includes/head.php';
require_once '../../includes/navbar.php';
require_once '../../includes/sidebar_employee.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">Mark Attendance</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/employee_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Mark Attendance</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <div class="current-time">
                        <h4 id="currentTime" class="mb-0"></h4>
                        <small id="currentDate" class="text-muted"></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Attendance Card -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Today's Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="attendance-card text-center p-4 mb-4">
                                    <div class="attendance-icon mb-3">
                                        <i class="fas fa-sign-in-alt fa-3x text-primary"></i>
                                    </div>
                                    <h5>Check In</h5>
                                    <?php if($today_attendance && $today_attendance['in_time']): ?>
                                        <p class="display-4 text-success">
                                            <?php echo date('H:i', strtotime($today_attendance['in_time'])); ?>
                                        </p>
                                        <p class="text-muted">Already checked in</p>
                                    <?php else: ?>
                                        <p class="display-4 text-muted">--:--</p>
                                        <button class="btn btn-primary btn-lg" id="checkInBtn" 
                                                <?php echo $today_attendance ? 'disabled' : ''; ?>>
                                            <i class="fas fa-clock mr-2"></i>Check In Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="attendance-card text-center p-4 mb-4">
                                    <div class="attendance-icon mb-3">
                                        <i class="fas fa-sign-out-alt fa-3x text-danger"></i>
                                    </div>
                                    <h5>Check Out</h5>
                                    <?php if($today_attendance && $today_attendance['out_time']): ?>
                                        <p class="display-4 text-success">
                                            <?php echo date('H:i', strtotime($today_attendance['out_time'])); ?>
                                        </p>
                                        <p class="text-muted">Already checked out</p>
                                    <?php elseif($today_attendance && $today_attendance['in_time']): ?>
                                        <p class="display-4 text-muted">--:--</p>
                                        <button class="btn btn-danger btn-lg" id="checkOutBtn">
                                            <i class="fas fa-clock mr-2"></i>Check Out Now
                                        </button>
                                    <?php else: ?>
                                        <p class="display-4 text-muted">--:--</p>
                                        <button class="btn btn-secondary btn-lg" disabled>
                                            <i class="fas fa-clock mr-2"></i>Check Out
                                        </button>
                                        <p class="text-muted small mt-2">Check in first to enable check out</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Details -->
                        <?php if($today_attendance): ?>
                        <div class="attendance-details mt-4">
                            <h6 class="mb-3">Attendance Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>Date</th>
                                                <td><?php echo date('d-m-Y', strtotime($today_attendance['attendance_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Shift</th>
                                                <td><?php echo $employee['shift_name'] ?? 'General Shift'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Shift Timing</th>
                                                <td>
                                                    <?php 
                                                    if($employee['shift_start']) {
                                                        echo date('H:i', strtotime($employee['shift_start'])) . ' - ' . 
                                                             date('H:i', strtotime($employee['shift_end']));
                                                    } else {
                                                        echo '09:00 - 18:00';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Hours</th>
                                                <td class="font-weight-bold">
                                                    <?php echo $today_attendance['total_working_hours'] ?? '0.00'; ?> hrs
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th>Status</th>
                                                <td>
                                                    <?php 
                                                    $status = $today_attendance['attendance_status'];
                                                    $badge_color = $status == 'Present' ? 'success' : 
                                                                  ($status == 'Absent' ? 'danger' : 'warning');
                                                    echo "<span class='badge badge-$badge_color'>$status</span>";
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Late Mark</th>
                                                <td>
                                                    <?php 
                                                    if($today_attendance['late_marks'] == 'Yes') {
                                                        echo '<span class="badge badge-warning">Late</span>';
                                                    } else {
                                                        echo '<span class="badge badge-success">On Time</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Overtime</th>
                                                <td><?php echo $today_attendance['overtime_hours'] ?? '0.00'; ?> hrs</td>
                                            </tr>
                                            <tr>
                                                <th>Early Exit</th>
                                                <td>
                                                    <?php 
                                                    if($today_attendance['early_exit'] == 'Yes') {
                                                        echo '<span class="badge badge-warning">Early Exit</span>';
                                                    } else {
                                                        echo '<span class="badge badge-success">Normal</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Shift Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Shift Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="shift-info">
                            <div class="d-flex align-items-center mb-3">
                                <div class="shift-icon mr-3">
                                    <i class="fas fa-business-time fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo $employee['shift_name'] ?? 'General Shift'; ?></h5>
                                    <p class="text-muted mb-0">Your assigned shift</p>
                                </div>
                            </div>
                            
                            <div class="shift-timing text-center mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="timing-box p-2 border rounded">
                                            <small class="text-muted d-block">Start Time</small>
                                            <h4 class="mb-0"><?php echo $employee['shift_start'] ? date('H:i', strtotime($employee['shift_start'])) : '09:00'; ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="timing-box p-2 border rounded">
                                            <small class="text-muted d-block">End Time</small>
                                            <h4 class="mb-0"><?php echo $employee['shift_end'] ? date('H:i', strtotime($employee['shift_end'])) : '18:00'; ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="shift-details">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-clock mr-2 text-primary"></i>
                                        Grace Period: <?php echo $employee['grace_minutes'] ?? '15'; ?> minutes
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-user mr-2 text-primary"></i>
                                        Employee: <?php echo $employee['full_name']; ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-id-badge mr-2 text-primary"></i>
                                        ID: <?php echo $employee['employee_code']; ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-calendar-day mr-2 text-primary"></i>
                                        Today: <?php echo date('l, d F Y'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Recent Attendance</h6>
                    </div>
                    <div class="card-body">
                        <div class="recent-attendance">
                            <?php
                            $recent_query = "SELECT * FROM attendance 
                                            WHERE employee_id = :employee_id 
                                            ORDER BY attendance_date DESC 
                                            LIMIT 5";
                            $recent_stmt = $conn->prepare($recent_query);
                            $recent_stmt->bindParam(':employee_id', $current_user);
                            $recent_stmt->execute();
                            $recent_attendance = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if(count($recent_attendance) > 0):
                            ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($recent_attendance as $record): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo date('D, d M', strtotime($record['attendance_date'])); ?></h6>
                                        <small class="text-muted">
                                            <?php 
                                            if($record['in_time'] && $record['out_time']) {
                                                echo date('H:i', strtotime($record['in_time'])) . ' - ' . 
                                                     date('H:i', strtotime($record['out_time']));
                                            } elseif($record['in_time']) {
                                                echo 'Checked in at ' . date('H:i', strtotime($record['in_time']));
                                            } else {
                                                echo 'No attendance';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php
                                        $status = $record['attendance_status'];
                                        $badge_color = $status == 'Present' ? 'success' : 
                                                      ($status == 'Absent' ? 'danger' : 'warning');
                                        echo "<span class='badge badge-$badge-color'>$status</span>";
                                        ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No attendance records found</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Monthly Attendance Summary</h5>
                <div>
                    <select id="monthFilter" class="form-control form-control-sm">
                        <?php
                        $months = [
                            '01' => 'January', '02' => 'February', '03' => 'March',
                            '04' => 'April', '05' => 'May', '06' => 'June',
                            '07' => 'July', '08' => 'August', '09' => 'September',
                            '10' => 'October', '11' => 'November', '12' => 'December'
                        ];
                        $current_month = date('m');
                        $current_year = date('Y');
                        
                        for($i = 0; $i < 6; $i++) {
                            $month = date('m', strtotime("-$i months"));
                            $year = date('Y', strtotime("-$i months"));
                            $selected = ($month == $current_month && $year == $current_year) ? 'selected' : '';
                            echo "<option value='$year-$month' $selected>" . 
                                 $months[$month] . " $year</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div id="attendanceCalendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-clock fa-4x text-primary mb-3"></i>
                    <h4 id="modalTime"></h4>
                    <p id="modalDate" class="text-muted"></p>
                    <p id="modalMessage" class="mt-3"></p>
                    
                    <div id="locationSection" class="mt-3" style="display: none;">
                        <div class="form-group">
                            <label>Your Location</label>
                            <input type="text" id="locationInput" class="form-control" 
                                   placeholder="Fetching location..." readonly>
                            <small class="form-text text-muted">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                Location will be recorded for attendance
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAttendance">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update current time
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit',
            hour12: true 
        });
        const dateString = now.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        $('#currentTime').text(timeString);
        $('#currentDate').text(dateString);
    }
    
    // Update time every second
    updateTime();
    setInterval(updateTime, 1000);
    
    // Check In button click
    $('#checkInBtn').click(function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
        const dateString = now.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        $('#modalTitle').text('Check In Confirmation');
        $('#modalTime').text(timeString);
        $('#modalDate').text(dateString);
        $('#modalMessage').html(`
            <p>Are you sure you want to check in now?</p>
            <p class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>
            Please ensure you are at your workplace location.</p>
        `);
        $('#locationSection').show();
        
        // Get location
        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Reverse geocoding using Nominatim (OpenStreetMap)
                $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, function(data) {
                    const location = data.display_name || `Lat: ${lat}, Lng: ${lng}`;
                    $('#locationInput').val(location);
                });
            }, function(error) {
                $('#locationInput').val('Location access denied or unavailable');
            });
        } else {
            $('#locationInput').val('Geolocation not supported by browser');
        }
        
        $('#attendanceModal').modal('show');
        $('#confirmAttendance').off('click').on('click', function() {
            markAttendance('check_in', $('#locationInput').val());
        });
    });
    
    // Check Out button click
    $('#checkOutBtn').click(function() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
        const dateString = now.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        $('#modalTitle').text('Check Out Confirmation');
        $('#modalTime').text(timeString);
        $('#modalDate').text(dateString);
        $('#modalMessage').html(`
            <p>Are you sure you want to check out now?</p>
            <p class="text-info"><i class="fas fa-info-circle mr-1"></i>
            Total working hours will be calculated automatically.</p>
        `);
        $('#locationSection').hide();
        
        $('#attendanceModal').modal('show');
        $('#confirmAttendance').off('click').on('click', function() {
            markAttendance('check_out', '');
        });
    });
    
    // Mark attendance function
    function markAttendance(action, location) {
        $.ajax({
            url: '../../actions/attendance_actions.php',
            type: 'POST',
            data: {
                action: action,
                location: location
            },
            beforeSend: function() {
                $('#confirmAttendance').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
            },
            success: function(response) {
                const result = JSON.parse(response);
                if(result.success) {
                    $('#attendanceModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(result.message);
                    $('#confirmAttendance').prop('disabled', false).text('Confirm');
                }
            },
            error: function() {
                alert('Error occurred while marking attendance');
                $('#confirmAttendance').prop('disabled', false).text('Confirm');
            }
        });
    }
    
    // Month filter change
    $('#monthFilter').change(function() {
        loadAttendanceCalendar($(this).val());
    });
    
    // Load attendance calendar
    function loadAttendanceCalendar(month) {
        $.ajax({
            url: '../../api/attendance_api.php',
            type: 'GET',
            data: {
                action: 'get_monthly_attendance',
                month: month
            },
            success: function(response) {
                $('#attendanceCalendar').html(response);
            }
        });
    }
    
    // Load initial calendar
    loadAttendanceCalendar($('#monthFilter').val());
});
</script>

<style>
.attendance-card {
    border: 2px solid #e0e0e0;
    border-radius: 15px;
    transition: all 0.3s;
}

.attendance-card:hover {
    border-color: var(--primary);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.attendance-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(102, 126, 234, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.shift-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(102, 126, 234, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.timing-box {
    transition: all 0.3s;
}

.timing-box:hover {
    border-color: var(--primary) !important;
    background: rgba(102, 126, 234, 0.05);
}

.current-time {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 15px;
    display: inline-block;
}

#currentTime {
    font-weight: 700;
    margin-bottom: 0;
}
</style>

<?php require_once '../../includes/footer.php'; ?>