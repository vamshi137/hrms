<?php
require_once '../../config/db.php';
require_once '../../core/session.php';
require_once '../../middleware/login_required.php';

// Check if the user has HR or Admin role
if ($_SESSION['role'] != 'hr' && $_SESSION['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

$id = $_GET['id'] ?? 0;

$db = getDB();
$stmt = $db->prepare("
    SELECT e.*, k.*
    FROM employees e
    LEFT JOIN employee_kyc k ON e.id = k.employee_id
    WHERE e.id = :id
");
$stmt->execute([':id' => $id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: employees_list.php');
    exit;
}

// Fetch departments, designations, branches, and employees (for reporting manager) for dropdowns
$stmt = $db->query("SELECT id, department_name FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT id, designation_name FROM designations ORDER BY designation_name");
$designations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT id, branch_name FROM branches ORDER BY branch_name");
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT id, employee_id, full_name FROM employees WHERE id != :id ORDER BY full_name");
$stmt->execute([':id' => $id]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>Edit Employee - HRMS</title>
    <style>
        .section-title {
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar_hr.php'; ?>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Employee</h1>
                </div>

                <form action="../../actions/employee_actions.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">

                    <div class="form-group row">
                        <label for="employee_id" class="col-sm-2 col-form-label">Employee ID</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="employee_id" value="<?php echo $employee['employee_id']; ?>" readonly>
                        </div>
                    </div>

                    <h4 class="section-title">Personal Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Full Name (as per Aadhaar/PAN/10th)</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $employee['full_name']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo $employee['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $employee['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $employee['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo $employee['date_of_birth']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="blood_group">Blood Group</label>
                                <input type="text" class="form-control" id="blood_group" name="blood_group" value="<?php echo $employee['blood_group']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="marital_status">Marital Status</label>
                                <select class="form-control" id="marital_status" name="marital_status" required>
                                    <option value="">Select</option>
                                    <option value="Single" <?php echo $employee['marital_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo $employee['marital_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nationality">Nationality</label>
                                <input type="text" class="form-control" id="nationality" name="nationality" value="<?php echo $employee['nationality']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <h4 class="section-title">Address & Contact Details</h4>
                    <div class="form-group">
                        <label for="present_address">Present Address</label>
                        <textarea class="form-control" id="present_address" name="present_address" rows="3" required><?php echo $employee['present_address']; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mobile_number">Mobile Number</label>
                                <input type="text" class="form-control" id="mobile_number" name="mobile_number" value="<?php echo $employee['mobile_number']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="personal_email">Personal Email ID</label>
                                <input type="email" class="form-control" id="personal_email" name="personal_email" value="<?php echo $employee['personal_email']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact_name">Emergency Contact Name</label>
                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo $employee['emergency_contact_name']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emergency_contact_number">Emergency Contact Number</label>
                                <input type="text" class="form-control" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo $employee['emergency_contact_number']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="permanent_address">Permanent Address</label>
                        <textarea class="form-control" id="permanent_address" name="permanent_address" rows="3" required><?php echo $employee['permanent_address']; ?></textarea>
                    </div>

                    <h4 class="section-title">Identity & KYC Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aadhaar_number">Aadhaar Number</label>
                                <input type="text" class="form-control" id="aadhaar_number" name="aadhaar_number" value="<?php echo $employee['aadhaar_number']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pan_number">PAN Number</label>
                                <input type="text" class="form-control" id="pan_number" name="pan_number" value="<?php echo $employee['pan_number']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="passport_number">Passport Number (if applicable)</label>
                                <input type="text" class="form-control" id="passport_number" name="passport_number" value="<?php echo $employee['passport_number']; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="passport_valid_from">Passport Valid From</label>
                                <input type="date" class="form-control" id="passport_valid_from" name="passport_valid_from" value="<?php echo $employee['passport_valid_from']; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="passport_valid_to">Passport Valid To</label>
                                <input type="date" class="form-control" id="passport_valid_to" name="passport_valid_to" value="<?php echo $employee['passport_valid_to']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="driving_license_number">Driving License Number</label>
                                <input type="text" class="form-control" id="driving_license_number" name="driving_license_number" value="<?php echo $employee['driving_license_number']; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dl_valid_from">DL Valid From</label>
                                <input type="date" class="form-control" id="dl_valid_from" name="dl_valid_from" value="<?php echo $employee['dl_valid_from']; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dl_valid_to">DL Valid To</label>
                                <input type="date" class="form-control" id="dl_valid_to" name="dl_valid_to" value="<?php echo $employee['dl_valid_to']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="uan_number">UAN Number</label>
                                <input type="text" class="form-control" id="uan_number" name="uan_number" value="<?php echo $employee['uan_number']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pf_number">PF Number</label>
                                <input type="text" class="form-control" id="pf_number" name="pf_number" value="<?php echo $employee['pf_number']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="esic_number">ESIC Number</label>
                                <input type="text" class="form-control" id="esic_number" name="esic_number" value="<?php echo $employee['esic_number']; ?>">
                            </div>
                        </div>
                    </div>

                    <h4 class="section-title">Employment Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_of_joining">Date of Joining</label>
                                <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo $employee['date_of_joining']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="employment_type">Employment Type</label>
                                <select class="form-control" id="employment_type" name="employment_type" required>
                                    <option value="">Select</option>
                                    <option value="Permanent" <?php echo $employee['employment_type'] == 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="Contract" <?php echo $employee['employment_type'] == 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="Consultant" <?php echo $employee['employment_type'] == 'Consultant' ? 'selected' : ''; ?>>Consultant</option>
                                    <option value="Project" <?php echo $employee['employment_type'] == 'Project' ? 'selected' : ''; ?>>Project</option>
                                    <option value="Govt" <?php echo $employee['employment_type'] == 'Govt' ? 'selected' : ''; ?>>Govt</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department_id">Department</label>
                                <select class="form-control" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $employee['department_id'] ? 'selected' : ''; ?>>
                                            <?php echo $dept['department_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="designation_id">Designation</label>
                                <select class="form-control" id="designation_id" name="designation_id" required>
                                    <option value="">Select Designation</option>
                                    <?php foreach ($designations as $desig): ?>
                                        <option value="<?php echo $desig['id']; ?>" <?php echo $desig['id'] == $employee['designation_id'] ? 'selected' : ''; ?>>
                                            <?php echo $desig['designation_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="grade">Grade</label>
                                <input type="text" class="form-control" id="grade" name="grade" value="<?php echo $employee['grade']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reporting_manager_id">Reporting Manager</label>
                                <select class="form-control" id="reporting_manager_id" name="reporting_manager_id">
                                    <option value="">Select Reporting Manager</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php echo $emp['id'] == $employee['reporting_manager_id'] ? 'selected' : ''; ?>>
                                            <?php echo $emp['full_name'] . ' (' . $emp['employee_id'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="work_location">Work Location / Branch</label>
                                <select class="form-control" id="work_location" name="work_location" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>" <?php echo $branch['id'] == $employee['work_location'] ? 'selected' : ''; ?>>
                                            <?php echo $branch['branch_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="shift_type">Shift Type</label>
                                <input type="text" class="form-control" id="shift_type" name="shift_type" value="<?php echo $employee['shift_type']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="training_period">Training Period (in days)</label>
                                <input type="number" class="form-control" id="training_period" name="training_period" value="<?php echo $employee['training_period']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="probation_period">Probation Period (in days)</label>
                                <input type="number" class="form-control" id="probation_period" name="probation_period" value="<?php echo $employee['probation_period']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="confirmation_date">Confirmation Date</label>
                                <input type="date" class="form-control" id="confirmation_date" name="confirmation_date" value="<?php echo $employee['confirmation_date']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="commitment_from">Commitment From</label>
                                <input type="date" class="form-control" id="commitment_from" name="commitment_from" value="<?php echo $employee['commitment_from']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="commitment_to">Commitment To</label>
                                <input type="date" class="form-control" id="commitment_to" name="commitment_to" value="<?php echo $employee['commitment_to']; ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Employee</button>
                    <a href="employees_list.php" class="btn btn-secondary">Cancel</a>
                </form>

            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script>
        // Initialize select2 for dropdowns
        $(document).ready(function() {
            $('#department_id, #designation_id, #reporting_manager_id, #work_location').select2();
        });
    </script>
</body>
</html>