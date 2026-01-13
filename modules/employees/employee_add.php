<?php
require_once '../../middleware/hr_only.php';
require_once '../../config/db.php';
require_once '../../core/helpers.php';
$page_title = 'Add Employee';
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
                    <h1 class="h3 mb-0">Add New Employee</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/hr_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="employees_list.php">Employees</a></li>
                            <li class="breadcrumb-item active">Add New</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Employee Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Employee Information</h5>
            </div>
            <div class="card-body">
                <form action="../../actions/employee_actions.php" method="POST" enctype="multipart/form-data" id="employeeForm">
                    <input type="hidden" name="action" value="add">
                    
                    <!-- Personal Details -->
                    <div class="form-section mb-5">
                        <h5 class="section-title">
                            <i class="fas fa-user mr-2"></i>Personal Details
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Gender *</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Birth *</label>
                                    <input type="date" name="dob" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Blood Group</label>
                                    <select name="blood_group" class="form-control">
                                        <option value="">Select Blood Group</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Marital Status</label>
                                    <select name="marital_status" class="form-control">
                                        <option value="">Select Status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Nationality</label>
                                    <input type="text" name="nationality" class="form-control" value="Indian">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Profile Photo</label>
                                    <div class="custom-file">
                                        <input type="file" name="profile_photo" class="custom-file-input" 
                                               accept="image/*">
                                        <label class="custom-file-label">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">Max size: 2MB, JPG/PNG format</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="form-section mb-5">
                        <h5 class="section-title">
                            <i class="fas fa-address-book mr-2"></i>Contact Details
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Present Address *</label>
                                    <textarea name="present_address" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Permanent Address</label>
                                    <textarea name="permanent_address" class="form-control" rows="3"></textarea>
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" id="sameAsPresent">
                                        <label class="form-check-label" for="sameAsPresent">Same as Present Address</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mobile Number *</label>
                                    <input type="text" name="mobile_number" class="form-control" required 
                                           pattern="[0-9]{10}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Personal Email *</label>
                                    <input type="email" name="personal_email" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Emergency Contact Number</label>
                                    <input type="text" name="emergency_contact_number" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Identity Details -->
                    <div class="form-section mb-5">
                        <h5 class="section-title">
                            <i class="fas fa-id-card mr-2"></i>Identity Details
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Aadhaar Number *</label>
                                    <input type="text" name="aadhaar_number" class="form-control" 
                                           pattern="[0-9]{12}" required>
                                    <small class="form-text text-muted">12-digit Aadhaar number</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PAN Number *</label>
                                    <input type="text" name="pan_number" class="form-control uppercase"
                                           pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" required>
                                    <small class="form-text text-muted">Format: ABCDE1234F</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Passport Number</label>
                                    <input type="text" name="passport_number" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Passport Valid From</label>
                                    <input type="date" name="passport_valid_from" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Passport Valid To</label>
                                    <input type="date" name="passport_valid_to" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Driving License</label>
                                    <input type="text" name="driving_license" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>DL Valid To</label>
                                    <input type="date" name="dl_valid_to" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <div class="form-section mb-5">
                        <h5 class="section-title">
                            <i class="fas fa-briefcase mr-2"></i>Employment Details
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Organization *</label>
                                    <select name="org_id" class="form-control" required>
                                        <option value="">Select Organization</option>
                                        <?php
                                        $org_query = "SELECT * FROM organizations WHERE status = 'Active'";
                                        $org_stmt = $conn->prepare($org_query);
                                        $org_stmt->execute();
                                        while($org = $org_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$org['id']}'>{$org['company_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Branch *</label>
                                    <select name="branch_id" class="form-control" required>
                                        <option value="">Select Branch</option>
                                        <?php
                                        $branch_query = "SELECT * FROM branches WHERE status = 'Active'";
                                        $branch_stmt = $conn->prepare($branch_query);
                                        $branch_stmt->execute();
                                        while($branch = $branch_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$branch['id']}'>{$branch['branch_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Department *</label>
                                    <select name="department_id" class="form-control" required>
                                        <option value="">Select Department</option>
                                        <?php
                                        $dept_query = "SELECT * FROM departments WHERE status = 'Active'";
                                        $dept_stmt = $conn->prepare($dept_query);
                                        $dept_stmt->execute();
                                        while($dept = $dept_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$dept['id']}'>{$dept['department_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Designation *</label>
                                    <select name="designation_id" class="form-control" required>
                                        <option value="">Select Designation</option>
                                        <?php
                                        $desg_query = "SELECT * FROM designations WHERE status = 'Active'";
                                        $desg_stmt = $conn->prepare($desg_query);
                                        $desg_stmt->execute();
                                        while($desg = $desg_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$desg['id']}'>{$desg['designation_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Joining *</label>
                                    <input type="date" name="date_of_joining" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Employment Type *</label>
                                    <select name="employment_type" class="form-control" required>
                                        <option value="Permanent">Permanent</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Consultant">Consultant</option>
                                        <option value="Fixed">Fixed Term</option>
                                        <option value="Project">Project Based</option>
                                        <option value="Govt">Government</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Shift</label>
                                    <select name="shift_id" class="form-control">
                                        <option value="">Select Shift</option>
                                        <?php
                                        $shift_query = "SELECT * FROM shifts WHERE status = 'Active'";
                                        $shift_stmt = $conn->prepare($shift_query);
                                        $shift_stmt->execute();
                                        while($shift = $shift_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$shift['id']}'>{$shift['shift_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Grade</label>
                                    <input type="text" name="grade" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reporting Manager</label>
                                    <select name="reporting_manager_id" class="form-control select2">
                                        <option value="">Select Manager</option>
                                        <?php
                                        $manager_query = "SELECT id, employee_code, full_name FROM employees 
                                                         WHERE status = 'Active' ORDER BY full_name";
                                        $manager_stmt = $conn->prepare($manager_query);
                                        $manager_stmt->execute();
                                        while($manager = $manager_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='{$manager['id']}'>{$manager['full_name']} ({$manager['employee_code']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Employee Code *</label>
                                    <div class="input-group">
                                        <input type="text" name="employee_code" class="form-control" 
                                               value="<?php echo generateEmployeeCode(); ?>" required readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="generateEmployeeCode()">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Training Period (Months)</label>
                                    <input type="number" name="training_period" class="form-control" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Probation Period (Months)</label>
                                    <input type="number" name="probation_period" class="form-control" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Confirmation Date</label>
                                    <input type="date" name="confirmation_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" class="form-control" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statutory Details -->
                    <div class="form-section mb-5">
                        <h5 class="section-title">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>Statutory Details
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>UAN Number</label>
                                    <input type="text" name="uan_number" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PF Number</label>
                                    <input type="text" name="pf_number" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ESIC Number</label>
                                    <input type="text" name="esic_number" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Commitment From Date</label>
                                    <input type="date" name="commitment_from" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Commitment To Date</label>
                                    <input type="date" name="commitment_to" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg mr-3">
                            <i class="fas fa-save mr-2"></i>Save Employee
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <a href="employees_list.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-generate employee code
    function generateEmployeeCode() {
        const prefix = 'EMP';
        const year = new Date().getFullYear();
        const random = Math.random().toString(36).substr(2, 5).toUpperCase();
        $('[name="employee_code"]').val(prefix + year + random);
    }
    
    window.generateEmployeeCode = generateEmployeeCode;
    
    // Same as present address
    $('#sameAsPresent').change(function() {
        if($(this).is(':checked')) {
            const presentAddress = $('[name="present_address"]').val();
            $('[name="permanent_address"]').val(presentAddress);
        }
    });
    
    // Form validation
    $('#employeeForm').submit(function(e) {
        // Validate Aadhaar
        const aadhaar = $('[name="aadhaar_number"]').val();
        if(aadhaar && !/^\d{12}$/.test(aadhaar)) {
            alert('Aadhaar number must be 12 digits');
            e.preventDefault();
            return false;
        }
        
        // Validate PAN
        const pan = $('[name="pan_number"]').val();
        if(pan && !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(pan)) {
            alert('Invalid PAN format. Format: ABCDE1234F');
            e.preventDefault();
            return false;
        }
        
        // Validate Mobile
        const mobile = $('[name="mobile_number"]').val();
        if(!/^\d{10}$/.test(mobile)) {
            alert('Mobile number must be 10 digits');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // File input label
    $('.custom-file-input').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    // Auto-uppercase for PAN
    $('.uppercase').on('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>