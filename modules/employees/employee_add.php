<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../core/session.php';
require_once '../../middleware/login_required.php';
require_once '../../middleware/hr_only.php';

$db = getDB();
$message = '';

// Fetch data for dropdowns
$departments = [];
$designations = [];
$branches = [];
$shifts = [];
$managers = [];

try {
    // Fetch departments
    $stmt = $db->query("SELECT id, department_name FROM departments WHERE 1 ORDER BY department_name");
    $departments = $stmt->fetchAll();
    
    // Fetch designations
    $stmt = $db->query("SELECT id, designation_name FROM designations WHERE 1 ORDER BY designation_name");
    $designations = $stmt->fetchAll();
    
    // Fetch branches
    $stmt = $db->query("SELECT id, branch_name FROM branches WHERE 1 ORDER BY branch_name");
    $branches = $stmt->fetchAll();
    
    // Fetch shifts
    $stmt = $db->query("SELECT id, shift_name FROM shifts WHERE 1 ORDER BY shift_name");
    $shifts = $stmt->fetchAll();
    
    // Fetch managers (active employees)
    $stmt = $db->query("SELECT id, employee_id, full_name FROM employees WHERE status = 'Active' ORDER BY full_name");
    $managers = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $message = "Error loading data: " . $e->getMessage();
}

// Generate new employee ID
$employee_id = 'EMP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Insert employee
        $query = "INSERT INTO employees (
            employee_id, full_name, gender, date_of_birth, blood_group, marital_status, nationality,
            present_address, mobile_number, emergency_contact_name, emergency_contact_number, 
            personal_email, permanent_address, aadhaar_number, pan_number, passport_number,
            passport_valid_from, passport_valid_to, driving_license, dl_valid_from, dl_valid_to,
            uan_number, pf_number, esic_number, date_of_joining, employment_type, department_id,
            designation_id, grade, reporting_manager_id, work_location_id, shift_id,
            training_period_days, probation_period_days, confirmation_date, commitment_from,
            commitment_to, status, created_by
        ) VALUES (
            :employee_id, :full_name, :gender, :dob, :blood_group, :marital_status, :nationality,
            :present_address, :mobile, :emergency_name, :emergency_contact, :personal_email,
            :permanent_address, :aadhaar, :pan, :passport, :passport_from, :passport_to,
            :dl, :dl_from, :dl_to, :uan, :pf, :esic, :doj, :emp_type, :dept_id, :desig_id,
            :grade, :reporting_manager, :location, :shift, :training_days, :probation_days,
            :confirmation_date, :commit_from, :commit_to, :status, :created_by
        )";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':employee_id', $_POST['employee_id']);
        $stmt->bindParam(':full_name', $_POST['full_name']);
        $stmt->bindParam(':gender', $_POST['gender']);
        $stmt->bindParam(':dob', $_POST['date_of_birth']);
        $stmt->bindParam(':blood_group', $_POST['blood_group']);
        $stmt->bindParam(':marital_status', $_POST['marital_status']);
        $stmt->bindParam(':nationality', $_POST['nationality']);
        $stmt->bindParam(':present_address', $_POST['present_address']);
        $stmt->bindParam(':mobile', $_POST['mobile_number']);
        $stmt->bindParam(':emergency_name', $_POST['emergency_contact_name']);
        $stmt->bindParam(':emergency_contact', $_POST['emergency_contact_number']);
        $stmt->bindParam(':personal_email', $_POST['personal_email']);
        $stmt->bindParam(':permanent_address', $_POST['permanent_address']);
        $stmt->bindParam(':aadhaar', $_POST['aadhaar_number']);
        $stmt->bindParam(':pan', $_POST['pan_number']);
        $stmt->bindParam(':passport', $_POST['passport_number']);
        $stmt->bindParam(':passport_from', $_POST['passport_valid_from']);
        $stmt->bindParam(':passport_to', $_POST['passport_valid_to']);
        $stmt->bindParam(':dl', $_POST['driving_license']);
        $stmt->bindParam(':dl_from', $_POST['dl_valid_from']);
        $stmt->bindParam(':dl_to', $_POST['dl_valid_to']);
        $stmt->bindParam(':uan', $_POST['uan_number']);
        $stmt->bindParam(':pf', $_POST['pf_number']);
        $stmt->bindParam(':esic', $_POST['esic_number']);
        $stmt->bindParam(':doj', $_POST['date_of_joining']);
        $stmt->bindParam(':emp_type', $_POST['employment_type']);
        $stmt->bindParam(':dept_id', $_POST['department_id']);
        $stmt->bindParam(':desig_id', $_POST['designation_id']);
        $stmt->bindParam(':grade', $_POST['grade']);
        $stmt->bindParam(':reporting_manager', $_POST['reporting_manager_id']);
        $stmt->bindParam(':location', $_POST['work_location_id']);
        $stmt->bindParam(':shift', $_POST['shift_id']);
        $stmt->bindParam(':training_days', $_POST['training_period_days']);
        $stmt->bindParam(':probation_days', $_POST['probation_period_days']);
        $stmt->bindParam(':confirmation_date', $_POST['confirmation_date']);
        $stmt->bindParam(':commit_from', $_POST['commitment_from']);
        $stmt->bindParam(':commit_to', $_POST['commitment_to']);
        
        $status = 'Active';
        $created_by = Session::get('user_id');
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':created_by', $created_by);
        
        if ($stmt->execute()) {
            $employee_id = $db->lastInsertId();
            
            // Insert KYC compliance record
            $kycQuery = "INSERT INTO kyc_compliance (employee_id) VALUES (:emp_id)";
            $kycStmt = $db->prepare($kycQuery);
            $kycStmt->bindParam(':emp_id', $employee_id);
            $kycStmt->execute();
            
            $db->commit();
            
            header('Location: employees_list.php?msg=added&id=' . $employee_id);
            exit();
        } else {
            $db->rollBack();
            $message = "Failed to add employee";
        }
        
    } catch(PDOException $e) {
        $db->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>Add New Employee - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/sidebar_hr.php'; ?>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Add New Employee</h1>
            <div class="actions">
                <a href="employees_list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="employee-form">
            <div class="card">
                <div class="card-header">
                    <h3>Employee ID & Basic Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employee_id">Employee ID *</label>
                                <input type="text" id="employee_id" name="employee_id" 
                                       value="<?php echo $employee_id; ?>" required 
                                       class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="full_name">Full Name (as per Aadhaar/PAN/10th) *</label>
                                <input type="text" id="full_name" name="full_name" 
                                       required class="form-control" 
                                       placeholder="Enter full name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gender">Gender *</label>
                                <select id="gender" name="gender" required class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="blood_group">Blood Group</label>
                                <select id="blood_group" name="blood_group" class="form-control">
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="marital_status">Marital Status</label>
                                <select id="marital_status" name="marital_status" class="form-control">
                                    <option value="">Select Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="nationality">Nationality *</label>
                                <input type="text" id="nationality" name="nationality" 
                                       value="Indian" required class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Address & Contact Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="present_address">Present Address *</label>
                                <textarea id="present_address" name="present_address" 
                                          required class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="permanent_address">Permanent Address *</label>
                                <textarea id="permanent_address" name="permanent_address" 
                                          required class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mobile_number">Mobile Number *</label>
                                <input type="tel" id="mobile_number" name="mobile_number" 
                                       required class="form-control" 
                                       pattern="[0-9]{10}" 
                                       placeholder="10-digit mobile number">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="personal_email">Personal Email ID *</label>
                                <input type="email" id="personal_email" name="personal_email" 
                                       required class="form-control" 
                                       placeholder="personal@example.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact_name">Emergency Contact Name *</label>
                                <input type="text" id="emergency_contact_name" 
                                       name="emergency_contact_name" required 
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact_number">Emergency Contact Number *</label>
                                <input type="tel" id="emergency_contact_number" 
                                       name="emergency_contact_number" required 
                                       class="form-control" pattern="[0-9]{10}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Identity & KYC Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aadhaar_number">Aadhaar Number *</label>
                                <input type="text" id="aadhaar_number" name="aadhaar_number" 
                                       required class="form-control" 
                                       pattern="[0-9]{12}" 
                                       placeholder="12-digit Aadhaar number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pan_number">PAN Number *</label>
                                <input type="text" id="pan_number" name="pan_number" 
                                       required class="form-control" 
                                       pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" 
                                       placeholder="AAAAA1234A">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="passport_number">Passport Number</label>
                                <input type="text" id="passport_number" name="passport_number" 
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="passport_valid_from">Passport Valid From</label>
                                <input type="date" id="passport_valid_from" 
                                       name="passport_valid_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="passport_valid_to">Passport Valid To</label>
                                <input type="date" id="passport_valid_to" 
                                       name="passport_valid_to" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="driving_license">Driving License</label>
                                <input type="text" id="driving_license" name="driving_license" 
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="dl_valid_from">DL Valid From</label>
                                <input type="date" id="dl_valid_from" name="dl_valid_from" 
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="dl_valid_to">DL Valid To</label>
                                <input type="date" id="dl_valid_to" name="dl_valid_to" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="uan_number">UAN Number</label>
                                <input type="text" id="uan_number" name="uan_number" 
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pf_number">PF Number</label>
                                <input type="text" id="pf_number" name="pf_number" 
                                       class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="esic_number">ESIC Number</label>
                                <input type="text" id="esic_number" name="esic_number" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Employment Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_of_joining">Date of Joining *</label>
                                <input type="date" id="date_of_joining" name="date_of_joining" 
                                       required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employment_type">Employment Type *</label>
                                <select id="employment_type" name="employment_type" 
                                        required class="form-control">
                                    <option value="">Select Type</option>
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
                                <label for="grade">Grade</label>
                                <input type="text" id="grade" name="grade" 
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department_id">Department *</label>
                                <select id="department_id" name="department_id" 
                                        required class="form-control select2">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="designation_id">Designation *</label>
                                <select id="designation_id" name="designation_id" 
                                        required class="form-control select2">
                                    <option value="">Select Designation</option>
                                    <?php foreach ($designations as $desig): ?>
                                    <option value="<?php echo $desig['id']; ?>">
                                        <?php echo htmlspecialchars($desig['designation_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reporting_manager_id">Reporting Manager</label>
                                <select id="reporting_manager_id" name="reporting_manager_id" 
                                        class="form-control select2">
                                    <option value="">Select Manager</option>
                                    <?php foreach ($managers as $manager): ?>
                                    <option value="<?php echo $manager['id']; ?>">
                                        <?php echo htmlspecialchars($manager['full_name'] . ' (' . $manager['employee_id'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="work_location_id">Work Location/Branch *</label>
                                <select id="work_location_id" name="work_location_id" 
                                        required class="form-control select2">
                                    <option value="">Select Location</option>
                                    <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>">
                                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="shift_id">Shift Type</label>
                                <select id="shift_id" name="shift_id" class="form-control">
                                    <option value="">Select Shift</option>
                                    <?php foreach ($shifts as $shift): ?>
                                    <option value="<?php echo $shift['id']; ?>">
                                        <?php echo htmlspecialchars($shift['shift_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="training_period_days">Training Period (Days)</label>
                                <input type="number" id="training_period_days" 
                                       name="training_period_days" class="form-control" 
                                       min="0" max="365">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="probation_period_days">Probation Period (Days)</label>
                                <input type="number" id="probation_period_days" 
                                       name="probation_period_days" class="form-control" 
                                       value="90" min="0" max="365">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="confirmation_date">Confirmation Date</label>
                                <input type="date" id="confirmation_date" 
                                       name="confirmation_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="commitment_from">Commitment From</label>
                                <input type="date" id="commitment_from" 
                                       name="commitment_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="commitment_to">Commitment To</label>
                                <input type="date" id="commitment_to" 
                                       name="commitment_to" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Employee
                </button>
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </button>
                <a href="employees_list.php" class="btn btn-light">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2();
        
        // Calculate confirmation date based on probation period
        $('#date_of_joining, #probation_period_days').change(function() {
            var doj = $('#date_of_joining').val();
            var probationDays = $('#probation_period_days').val();
            
            if (doj && probationDays) {
                var dojDate = new Date(doj);
                var confirmationDate = new Date(dojDate);
                confirmationDate.setDate(confirmationDate.getDate() + parseInt(probationDays));
                
                // Format date as YYYY-MM-DD
                var formattedDate = confirmationDate.toISOString().split('T')[0];
                $('#confirmation_date').val(formattedDate);
            }
        });
        
        // Form validation
        $('form').submit(function(e) {
            var aadhaar = $('#aadhaar_number').val();
            var pan = $('#pan_number').val();
            
            // Validate Aadhaar
            if (aadhaar && !/^\d{12}$/.test(aadhaar)) {
                alert('Aadhaar number must be 12 digits');
                e.preventDefault();
                return false;
            }
            
            // Validate PAN
            if (pan && !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(pan)) {
                alert('PAN number must be in format: AAAAA1234A');
                e.preventDefault();
                return false;
            }
            
            // Validate mobile number
            var mobile = $('#mobile_number').val();
            if (mobile && !/^\d{10}$/.test(mobile)) {
                alert('Mobile number must be 10 digits');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>