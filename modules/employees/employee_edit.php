<?php
require_once '../../middleware/hr_only.php';
require_once '../../config/db.php';

// Check if employee ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: employees_list.php?error=Employee ID required');
    exit();
}

$employee_id = $_GET['id'];

// Fetch employee data
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT e.*, d.department_name, ds.designation_name, o.company_name, b.branch_name
          FROM employees e
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN designations ds ON e.designation_id = ds.id
          LEFT JOIN organizations o ON e.org_id = o.id
          LEFT JOIN branches b ON e.branch_id = b.id
          WHERE e.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $employee_id);
$stmt->execute();

if($stmt->rowCount() === 0) {
    header('Location: employees_list.php?error=Employee not found');
    exit();
}

$employee = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Edit Employee: ' . $employee['full_name'];
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
                    <h1 class="h3 mb-0">Edit Employee</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/hr_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="employees_list.php">Employees</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <a href="employee_view.php?id=<?php echo $employee_id; ?>" class="btn btn-info">
                        <i class="fas fa-eye mr-2"></i>View
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Employee Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Employee Information</h5>
                <p class="mb-0 text-muted">Employee Code: <?php echo $employee['employee_code']; ?></p>
            </div>
            <div class="card-body">
                <form action="../../actions/employee_actions.php" method="POST" enctype="multipart/form-data" id="employeeForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $employee_id; ?>">
                    
                    <!-- Personal Details -->
                    <div class="form-section mb-5">
                        <h5 class="section-title">
                            <i class="fas fa-user mr-2"></i>Personal Details
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Gender *</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="Male" <?php echo $employee['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $employee['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $employee['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Birth *</label>
                                    <input type="date" name="dob" class="form-control" 
                                           value="<?php echo $employee['dob']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Blood Group</label>
                                    <select name="blood_group" class="form-control">
                                        <option value="">Select Blood Group</option>
                                        <?php
                                        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                                        foreach($blood_groups as $bg) {
                                            $selected = $employee['blood_group'] == $bg ? 'selected' : '';
                                            echo "<option value='$bg' $selected>$bg</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Marital Status</label>
                                    <select name="marital_status" class="form-control">
                                        <option value="">Select Status</option>
                                        <option value="Single" <?php echo $employee['marital_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo $employee['marital_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                        <option value="Divorced" <?php echo $employee['marital_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="Widowed" <?php echo $employee['marital_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Nationality</label>
                                    <input type="text" name="nationality" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['nationality']); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Profile Photo</label>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <img src="../../uploads/profile_photos/<?php echo $employee['profile_photo'] ?: 'default_user.png'; ?>" 
                                                 class="rounded-circle" width="60" height="60" 
                                                 alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                        </div>
                                        <div class="custom-file">
                                            <input type="file" name="profile_photo" class="custom-file-input" 
                                                   accept="image/*">
                                            <label class="custom-file-label">Change photo</label>
                                        </div>
                                    </div>
                                    <?php if($employee['profile_photo']): ?>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" name="remove_photo" value="1">
                                            <label class="form-check-label">Remove current photo</label>
                                        </div>
                                    <?php endif; ?>
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
                                    <textarea name="present_address" class="form-control" rows="3" required><?php echo htmlspecialchars($employee['present_address']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Permanent Address</label>
                                    <textarea name="permanent_address" class="form-control" rows="3"><?php echo htmlspecialchars($employee['permanent_address']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mobile Number *</label>
                                    <input type="text" name="mobile_number" class="form-control" 
                                           value="<?php echo $employee['mobile_number']; ?>" required 
                                           pattern="[0-9]{10}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Personal Email *</label>
                                    <input type="email" name="personal_email" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['personal_email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['emergency_contact_name']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Emergency Contact Number</label>
                                    <input type="text" name="emergency_contact_number" class="form-control" 
                                           value="<?php echo $employee['emergency_contact_number']; ?>">
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
                                           value="<?php echo $employee['aadhaar_number']; ?>" 
                                           pattern="[0-9]{12}" required>
                                    <small class="form-text text-muted">12-digit Aadhaar number</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PAN Number *</label>
                                    <input type="text" name="pan_number" class="form-control uppercase"
                                           value="<?php echo $employee['pan_number']; ?>"
                                           pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" required>
                                    <small class="form-text text-muted">Format: ABCDE1234F</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Passport Number</label>
                                    <input type="text" name="passport_number" class="form-control" 
                                           value="<?php echo $employee['passport_number']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Passport Valid From</label>
                                    <input type="date" name="passport_valid_from" class="form-control" 
                                           value="<?php echo $employee['passport_valid_from']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Passport Valid To</label>
                                    <input type="date" name="passport_valid_to" class="form-control" 
                                           value="<?php echo $employee['passport_valid_to']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Driving License</label>
                                    <input type="text" name="driving_license" class="form-control" 
                                           value="<?php echo $employee['driving_license']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>DL Valid To</label>
                                    <input type="date" name="dl_valid_to" class="form-control" 
                                           value="<?php echo $employee['dl_valid_to']; ?>">
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
                                            $selected = $employee['org_id'] == $org['id'] ? 'selected' : '';
                                            echo "<option value='{$org['id']}' $selected>{$org['company_name']}</option>";
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
                                            $selected = $employee['branch_id'] == $branch['id'] ? 'selected' : '';
                                            echo "<option value='{$branch['id']}' $selected>{$branch['branch_name']}</option>";
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
                                            $selected = $employee['department_id'] == $dept['id'] ? 'selected' : '';
                                            echo "<option value='{$dept['id']}' $selected>{$dept['department_name']}</option>";
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
                                            $selected = $employee['designation_id'] == $desg['id'] ? 'selected' : '';
                                            echo "<option value='{$desg['id']}' $selected>{$desg['designation_name']}</option>";
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
                                    <input type="date" name="date_of_joining" class="form-control" 
                                           value="<?php echo $employee['date_of_joining']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Employment Type *</label>
                                    <select name="employment_type" class="form-control" required>
                                        <?php
                                        $emp_types = ['Permanent', 'Contract', 'Consultant', 'Fixed', 'Project', 'Govt'];
                                        foreach($emp_types as $type) {
                                            $selected = $employee['employment_type'] == $type ? 'selected' : '';
                                            echo "<option value='$type' $selected>$type</option>";
                                        }
                                        ?>
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
                                            $selected = $employee['shift_id'] == $shift['id'] ? 'selected' : '';
                                            echo "<option value='{$shift['id']}' $selected>{$shift['shift_name']}</option>";
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
                                    <input type="text" name="grade" class="form-control" 
                                           value="<?php echo $employee['grade']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Reporting Manager</label>
                                    <select name="reporting_manager_id" class="form-control select2">
                                        <option value="">Select Manager</option>
                                        <?php
                                        $manager_query = "SELECT id, employee_code, full_name FROM employees 
                                                         WHERE status = 'Active' AND id != :id ORDER BY full_name";
                                        $manager_stmt = $conn->prepare($manager_query);
                                        $manager_stmt->bindParam(':id', $employee_id);
                                        $manager_stmt->execute();
                                        while($manager = $manager_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = $employee['reporting_manager_id'] == $manager['id'] ? 'selected' : '';
                                            echo "<option value='{$manager['id']}' $selected>{$manager['full_name']} ({$manager['employee_code']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Employee Code *</label>
                                    <input type="text" name="employee_code" class="form-control" 
                                           value="<?php echo $employee['employee_code']; ?>" required readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Training Period (Months)</label>
                                    <input type="number" name="training_period" class="form-control" min="0"
                                           value="<?php echo $employee['training_period']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Probation Period (Months)</label>
                                    <input type="number" name="probation_period" class="form-control" min="0"
                                           value="<?php echo $employee['probation_period']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Confirmation Date</label>
                                    <input type="date" name="confirmation_date" class="form-control"
                                           value="<?php echo $employee['confirmation_date']; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" class="form-control" required>
                                        <option value="Active" <?php echo $employee['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $employee['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                                    <input type="text" name="uan_number" class="form-control"
                                           value="<?php echo $employee['uan_number']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PF Number</label>
                                    <input type="text" name="pf_number" class="form-control"
                                           value="<?php echo $employee['pf_number']; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>ESIC Number</label>
                                    <input type="text" name="esic_number" class="form-control"
                                           value="<?php echo $employee['esic_number']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Commitment From Date</label>
                                    <input type="date" name="commitment_from" class="form-control"
                                           value="<?php echo $employee['commitment_from']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Commitment To Date</label>
                                    <input type="date" name="commitment_to" class="form-control"
                                           value="<?php echo $employee['commitment_to']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg mr-3">
                            <i class="fas fa-save mr-2"></i>Update Employee
                        </button>
                        <a href="employee_view.php?id=<?php echo $employee_id; ?>" class="btn btn-info btn-lg mr-3">
                            <i class="fas fa-eye mr-2"></i>View
                        </a>
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
    // File input label
    $('.custom-file-input').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    // Auto-uppercase for PAN
    $('.uppercase').on('input', function() {
        this.value = this.value.toUpperCase();
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
});
</script>

<?php require_once '../../includes/footer.php'; ?>