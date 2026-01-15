<?php
require_once '../../middleware/employee_only.php';
require_once '../../config/db.php';
require_once '../../core/helpers.php';

$database = new Database();
$conn = $database->getConnection();
$employee_id = Session::get('employee_id');

// Get employee details
$emp_query = "SELECT e.*, d.department_name, ds.designation_name 
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN designations ds ON e.designation_id = ds.id
             WHERE e.id = :id";
$emp_stmt = $conn->prepare($emp_query);
$emp_stmt->bindParam(':id', $employee_id);
$emp_stmt->execute();
$employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);

// Get leave balance
$balance_query = "SELECT lt.id, lt.leave_name, COALESCE(lb.balance, 0) as balance
                 FROM leave_types lt
                 LEFT JOIN leave_balance lb ON lt.id = lb.leave_type_id AND lb.employee_id = :employee_id
                 WHERE lt.status = 'Active'
                 ORDER BY lt.leave_name";
$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->bindParam(':employee_id', $employee_id);
$balance_stmt->execute();
$leave_balance = $balance_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Apply for Leave';
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
                    <h1 class="h3 mb-0">Apply for Leave</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/employee_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Apply Leave</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <a href="leave_my_requests.php" class="btn btn-info">
                        <i class="fas fa-history mr-2"></i>My Leave History
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <div class="row">
            <!-- Leave Balance Summary -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">My Leave Balance</h5>
                    </div>
                    <div class="card-body">
                        <div class="leave-balance-summary">
                            <?php foreach($leave_balance as $balance): ?>
                            <div class="balance-item d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-0"><?php echo $balance['leave_name']; ?></h6>
                                    <small class="text-muted">Available Balance</small>
                                </div>
                                <div class="text-right">
                                    <h4 class="mb-0 text-primary"><?php echo $balance['balance']; ?></h4>
                                    <small class="text-muted">days</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="employee-info mt-4 pt-3 border-top">
                            <h6 class="mb-3">Employee Information</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Employee Code</small>
                                    <p class="mb-2 font-weight-bold"><?php echo $employee['employee_code']; ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Department</small>
                                    <p class="mb-2 font-weight-bold"><?php echo $employee['department_name']; ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Designation</small>
                                    <p class="mb-2 font-weight-bold"><?php echo $employee['designation_name']; ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Date of Joining</small>
                                    <p class="mb-0 font-weight-bold"><?php echo date('d-m-Y', strtotime($employee['date_of_joining'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Leave Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="leave-stats">
                            <?php
                            // Get leave statistics
                            $stats_query = "SELECT 
                                COUNT(*) as total_applications,
                                SUM(CASE WHEN leave_status = 'Approved' THEN 1 ELSE 0 END) as approved,
                                SUM(CASE WHEN leave_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                                SUM(CASE WHEN leave_status = 'Pending' THEN 1 ELSE 0 END) as pending,
                                COALESCE(SUM(number_of_days), 0) as total_days
                                FROM leave_applications 
                                WHERE employee_id = :employee_id 
                                AND YEAR(from_date) = YEAR(CURDATE())";
                            $stats_stmt = $conn->prepare($stats_query);
                            $stats_stmt->bindParam(':employee_id', $employee_id);
                            $stats_stmt->execute();
                            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stat-box p-2 border rounded">
                                        <h3 class="mb-0 text-primary"><?php echo $stats['total_applications']; ?></h3>
                                        <small class="text-muted">Total Applications</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-box p-2 border rounded">
                                        <h3 class="mb-0 text-success"><?php echo $stats['approved']; ?></h3>
                                        <small class="text-muted">Approved</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box p-2 border rounded">
                                        <h3 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h3>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box p-2 border rounded">
                                        <h3 class="mb-0 text-danger"><?php echo $stats['rejected']; ?></h3>
                                        <small class="text-muted">Rejected</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="total-days text-center mt-3">
                                <h5 class="mb-0">Total Leave Days: <?php echo $stats['total_days']; ?></h5>
                                <small class="text-muted">This Year</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Application Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Leave Application Form</h5>
                    </div>
                    <div class="card-body">
                        <form id="leaveApplicationForm" enctype="multipart/form-data">
                            <div class="form-section mb-4">
                                <h6 class="section-title">Leave Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Leave Type *</label>
                                            <select name="leave_type_id" class="form-control" id="leaveTypeSelect" required>
                                                <option value="">Select Leave Type</option>
                                                <?php foreach($leave_balance as $balance): ?>
                                                <option value="<?php echo $balance['id']; ?>" 
                                                        data-balance="<?php echo $balance['balance']; ?>">
                                                    <?php echo $balance['leave_name']; ?> 
                                                    (Available: <?php echo $balance['balance']; ?> days)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="leaveBalanceInfo" class="mt-1"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Application Date</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo date('d-m-Y'); ?>" readonly>
                                            <small class="form-text text-muted">Current date</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>From Date *</label>
                                            <input type="date" name="from_date" class="form-control" id="fromDate" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>To Date *</label>
                                            <input type="date" name="to_date" class="form-control" id="toDate" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Number of Days</label>
                                            <input type="text" name="number_of_days" class="form-control" 
                                                   id="numberOfDays" readonly>
                                            <small class="form-text text-muted">Calculated automatically</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Leave Category</label>
                                            <select name="leave_category" class="form-control">
                                                <option value="Planned">Planned Leave</option>
                                                <option value="Emergency">Emergency Leave</option>
                                                <option value="Medical">Medical Leave</option>
                                                <option value="Personal">Personal Leave</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>First Half / Second Half</label>
                                            <select name="half_day_type" class="form-control">
                                                <option value="">Full Day</option>
                                                <option value="First Half">First Half</option>
                                                <option value="Second Half">Second Half</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Mode of Application</label>
                                            <select name="mode_of_application" class="form-control">
                                                <option value="Online">Online Application</option>
                                                <option value="Email">Email</option>
                                                <option value="Phone">Phone</option>
                                                <option value="In Person">In Person</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section mb-4">
                                <h6 class="section-title">Reason & Details</h6>
                                <div class="form-group">
                                    <label>Reason for Leave *</label>
                                    <textarea name="reason" class="form-control" rows="4" 
                                              placeholder="Please provide detailed reason for leave..." required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Contact Details During Leave</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" name="contact_number" class="form-control" 
                                                   placeholder="Contact Number">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="contact_address" class="form-control" 
                                                   placeholder="Address during leave">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Handover Notes (Optional)</label>
                                    <textarea name="handover_notes" class="form-control" rows="3" 
                                              placeholder="Work/tasks to be handled during your absence..."></textarea>
                                </div>
                            </div>

                            <div class="form-section mb-4">
                                <h6 class="section-title">Supporting Documents</h6>
                                <div class="form-group">
                                    <label>Upload Supporting Documents</label>
                                    <div class="custom-file">
                                        <input type="file" name="supporting_docs[]" class="custom-file-input" 
                                               id="supportingDocs" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                        <label class="custom-file-label" for="supportingDocs">Choose files</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        You can upload multiple files (Max: 5MB each). 
                                        Supported formats: PDF, JPG, PNG, DOC, DOCX
                                    </small>
                                    <div id="filePreview" class="mt-2"></div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="medical_certificate" 
                                               id="medicalCertificate">
                                        <label class="form-check-label" for="medicalCertificate">
                                            I will submit medical certificate (if applicable)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="section-title">Approval Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Reporting Manager</label>
                                            <?php
                                            $manager_query = "SELECT full_name FROM employees WHERE id = :id";
                                            $manager_stmt = $conn->prepare($manager_query);
                                            $manager_stmt->bindParam(':id', $employee['reporting_manager_id']);
                                            $manager_stmt->execute();
                                            $manager = $manager_stmt->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo $manager['full_name'] ?? 'Not Assigned'; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>HR Approver</label>
                                            <input type="text" class="form-control" value="HR Department" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Your leave application will be forwarded to your Reporting Manager for approval.
                                    After manager approval, it will go to HR for final approval.
                                </div>
                            </div>

                            <div class="form-group mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg mr-3">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Application
                                </button>
                                <button type="reset" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-redo mr-2"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leave Calendar Preview Modal -->
<div class="modal fade" id="calendarPreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Leave Dates Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="calendarPreview"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize date pickers
    const today = new Date().toISOString().split('T')[0];
    $('#fromDate').attr('min', today);
    $('#toDate').attr('min', today);
    
    // File input preview
    $('#supportingDocs').on('change', function() {
        const files = $(this)[0].files;
        const preview = $('#filePreview');
        preview.empty();
        
        if(files.length > 0) {
            let fileList = $('<ul class="list-unstyled"></ul>');
            
            for(let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = (file.size / (1024*1024)).toFixed(2);
                const fileItem = $(`
                    <li class="mb-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-file mr-2 text-primary"></i>
                            <div class="flex-grow-1">
                                <small class="d-block">${file.name}</small>
                                <small class="text-muted">${fileSize} MB</small>
                            </div>
                        </div>
                    </li>
                `);
                fileList.append(fileItem);
            }
            
            preview.html(fileList);
        }
    });
    
    // Leave type selection - show balance
    $('#leaveTypeSelect').change(function() {
        const selectedOption = $(this).find('option:selected');
        const balance = selectedOption.data('balance');
        
        if(balance !== undefined) {
            let balanceInfo = '';
            if(balance <= 0) {
                balanceInfo = `<small class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>No leave balance available</small>`;
            } else if(balance < 5) {
                balanceInfo = `<small class="text-warning"><i class="fas fa-exclamation-circle mr-1"></i>Low balance: ${balance} days remaining</small>`;
            } else {
                balanceInfo = `<small class="text-success"><i class="fas fa-check-circle mr-1"></i>Sufficient balance: ${balance} days available</small>`;
            }
            $('#leaveBalanceInfo').html(balanceInfo);
        } else {
            $('#leaveBalanceInfo').html('');
        }
        
        calculateLeaveDays();
    });
    
    // Date change handlers
    $('#fromDate, #toDate').change(function() {
        calculateLeaveDays();
    });
    
    // Calculate leave days
    function calculateLeaveDays() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const halfDayType = $('select[name="half_day_type"]').val();
        
        if(fromDate && toDate) {
            const start = new Date(fromDate);
            const end = new Date(toDate);
            
            if(start > end) {
                alert('To date cannot be before From date');
                $('#toDate').val(fromDate);
                return;
            }
            
            // Calculate difference in days
            const timeDiff = end.getTime() - start.getTime();
            let daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            
            // Adjust for half day
            if(halfDayType && daysDiff === 1) {
                daysDiff = 0.5;
            }
            
            $('#numberOfDays').val(daysDiff);
        }
    }
    
    // Form submission
    $('#leaveApplicationForm').submit(function(e) {
        e.preventDefault();
        
        // Validate form
        const leaveType = $('#leaveTypeSelect').val();
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const numberOfDays = parseFloat($('#numberOfDays').val());
        const selectedOption = $('#leaveTypeSelect').find('option:selected');
        const balance = selectedOption.data('balance');
        
        if(!leaveType || !fromDate || !toDate) {
            alert('Please fill all required fields');
            return false;
        }
        
        if(balance !== undefined && numberOfDays > balance) {
            if(!confirm(`You only have ${balance} days balance but are applying for ${numberOfDays} days. Do you want to proceed?`)) {
                return false;
            }
        }
        
        // Create FormData object for file upload
        const formData = new FormData(this);
        
        // Show loading
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...');
        
        // Submit via AJAX
        $.ajax({
            url: '../../actions/leave_actions.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const result = JSON.parse(response);
                if(result.success) {
                    alert('Leave application submitted successfully! Application ID: ' + result.application_id);
                    window.location.href = 'leave_my_requests.php';
                } else {
                    alert('Error: ' + result.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Error submitting application. Please try again.');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Preview calendar
    $('#previewCalendarBtn').click(function() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        
        if(!fromDate || !toDate) {
            alert('Please select dates first');
            return;
        }
        
        // Load calendar preview via AJAX
        $.ajax({
            url: '../../api/leave_api.php',
            type: 'GET',
            data: {
                action: 'get_calendar_preview',
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                $('#calendarPreview').html(response);
                $('#calendarPreviewModal').modal('show');
            }
        });
    });
});
</script>

<style>
.leave-balance-summary .balance-item {
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
    border-left: 4px solid #667eea;
}

.leave-balance-summary .balance-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
    transition: all 0.3s;
}

.section-title {
    color: #667eea;
    font-weight: 600;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    margin-bottom: 20px;
}

.stat-box {
    transition: all 0.3s;
}

.stat-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.custom-file-label::after {
    content: "Browse";
}
</style>

<?php require_once '../../includes/footer.php'; ?>