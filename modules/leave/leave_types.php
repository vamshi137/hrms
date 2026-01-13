<?php
require_once '../../middleware/hr_only.php';
require_once '../../config/db.php';

$database = new Database();
$conn = $database->getConnection();

$page_title = 'Leave Types Management';
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
                    <h1 class="h3 mb-0">Leave Types Management</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/hr_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="leave_approvals.php">Leave Management</a></li>
                            <li class="breadcrumb-item active">Leave Types</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addLeaveTypeModal">
                        <i class="fas fa-plus mr-2"></i>Add Leave Type
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Leave Types Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">All Leave Types</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Leave Type</th>
                                <th>Code</th>
                                <th>Max Days/Year</th>
                                <th>Carry Forward</th>
                                <th>Earning Leave</th>
                                <th>Requires Approval</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch leave types from database (we'll create this table)
                            $query = "SELECT * FROM leave_types ORDER BY id";
                            $stmt = $conn->prepare($query);
                            $stmt->execute();
                            
                            $count = 1;
                            while($leave_type = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($leave_type['leave_name']); ?></strong>
                                    <?php if($leave_type['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($leave_type['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $leave_type['leave_code'] ?? 'N/A'; ?></span>
                                </td>
                                <td>
                                    <?php echo $leave_type['max_days_per_year'] ?? 'Unlimited'; ?>
                                </td>
                                <td>
                                    <?php if($leave_type['allow_carry_forward'] == 1): ?>
                                        <span class="badge badge-success">Yes (Max: <?php echo $leave_type['max_carry_forward'] ?? '0'; ?>)</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($leave_type['is_earning_leave'] == 1): ?>
                                        <span class="badge badge-info">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($leave_type['requires_approval'] == 1): ?>
                                        <span class="badge badge-warning">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Auto Approved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_badge = $leave_type['status'] == 'Active' ? 'success' : 'danger';
                                    echo "<span class='badge badge-$status_badge'>{$leave_type['status']}</span>";
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-warning edit-leave-type" 
                                                data-id="<?php echo $leave_type['id']; ?>"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-leave-type" 
                                                data-id="<?php echo $leave_type['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($leave_type['leave_name']); ?>"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Leave Policy Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Leave Policy Configuration</h5>
            </div>
            <div class="card-body">
                <form id="leavePolicyForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Annual Leave Accrual Rate</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="accrual_rate" value="2.5" step="0.1">
                                    <div class="input-group-append">
                                        <span class="input-group-text">days/month</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Leave days earned per month</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Probation Period (Days)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="probation_days" value="90">
                                    <div class="input-group-append">
                                        <span class="input-group-text">days</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Leave eligibility after probation</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Max Consecutive Leave Days</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="max_consecutive" value="30">
                                    <div class="input-group-append">
                                        <span class="input-group-text">days</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notice Period for Planned Leave</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="notice_period" value="3">
                                    <div class="input-group-append">
                                        <span class="input-group-text">days</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Medical Certificate Required After</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="medical_cert_days" value="3">
                                    <div class="input-group-append">
                                        <span class="input-group-text">days</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="allow_half_day" id="allowHalfDay" checked>
                            <label class="form-check-label" for="allowHalfDay">Allow Half Day Leaves</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="allow_weekend_leave" id="allowWeekendLeave">
                            <label class="form-check-label" for="allowWeekendLeave">Allow Weekend Leaves</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="auto_approve_emergency" id="autoApproveEmergency" checked>
                            <label class="form-check-label" for="autoApproveEmergency">Auto Approve Emergency Leaves</label>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Save Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Leave Type Modal -->
<div class="modal fade" id="addLeaveTypeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Leave Type</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addLeaveTypeForm">
                    <div class="form-group">
                        <label>Leave Type Name *</label>
                        <input type="text" name="leave_name" class="form-control" required>
                        <small class="form-text text-muted">e.g., Casual Leave, Sick Leave</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Leave Code</label>
                        <input type="text" name="leave_code" class="form-control">
                        <small class="form-text text-muted">Short code (e.g., CL, SL)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maximum Days Per Year</label>
                                <input type="number" name="max_days_per_year" class="form-control" min="0" step="0.5">
                                <small class="form-text text-muted">0 for unlimited</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Days Per Request</label>
                                <input type="number" name="min_days_per_request" class="form-control" min="0" step="0.5" value="0.5">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Allow Carry Forward</label>
                                <select name="allow_carry_forward" class="form-control">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Carry Forward Days</label>
                                <input type="number" name="max_carry_forward" class="form-control" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Requires Approval</label>
                        <select name="requires_approval" class="form-control">
                            <option value="1">Yes (Manager/HR Approval)</option>
                            <option value="0">Auto Approved</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_earning_leave" value="1" id="isEarningLeave">
                            <label class="form-check-label" for="isEarningLeave">Is Earning Leave</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="allow_encashment" value="1" id="allowEncashment">
                            <label class="form-check-label" for="allowEncashment">Allow Leave Encashment</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_paid_leave" value="1" id="isPaidLeave" checked>
                            <label class="form-check-label" for="isPaidLeave">Paid Leave</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveLeaveType">Save Leave Type</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Leave Type Modal -->
<div class="modal fade" id="editLeaveTypeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Leave Type</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="editLeaveTypeContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLeaveTypeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete leave type: <strong id="deleteLeaveName"></strong>?</p>
                <p class="text-danger">Note: This will affect all existing leave records of this type.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteLeaveType">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Save new leave type
    $('#saveLeaveType').click(function() {
        const formData = $('#addLeaveTypeForm').serialize();
        
        $.ajax({
            url: '../../actions/leave_actions.php',
            type: 'POST',
            data: formData + '&action=add_leave_type',
            beforeSend: function() {
                $('#saveLeaveType').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
            },
            success: function(response) {
                const result = JSON.parse(response);
                if(result.success) {
                    $('#addLeaveTypeModal').modal('hide');
                    location.reload();
                } else {
                    alert(result.message);
                    $('#saveLeaveType').prop('disabled', false).html('Save Leave Type');
                }
            }
        });
    });
    
    // Edit leave type
    $('.edit-leave-type').click(function() {
        const leaveTypeId = $(this).data('id');
        
        $.ajax({
            url: '../../actions/leave_actions.php',
            type: 'GET',
            data: {
                action: 'get_leave_type',
                id: leaveTypeId
            },
            success: function(response) {
                $('#editLeaveTypeContent').html(response);
                $('#editLeaveTypeModal').modal('show');
            }
        });
    });
    
    // Delete leave type
    $('.delete-leave-type').click(function() {
        const leaveTypeId = $(this).data('id');
        const leaveName = $(this).data('name');
        
        $('#deleteLeaveName').text(leaveName);
        $('#deleteLeaveTypeModal').modal('show');
        
        $('#confirmDeleteLeaveType').off('click').on('click', function() {
            $.ajax({
                url: '../../actions/leave_actions.php',
                type: 'POST',
                data: {
                    action: 'delete_leave_type',
                    id: leaveTypeId
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
        });
    });
    
    // Save leave policy
    $('#leavePolicyForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../../actions/leave_actions.php',
            type: 'POST',
            data: $(this).serialize() + '&action=save_policy',
            success: function(response) {
                const result = JSON.parse(response);
                if(result.success) {
                    alert('Policy saved successfully!');
                } else {
                    alert(result.message);
                }
            }
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>