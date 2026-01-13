<?php
require_once '../config/db.php';
require_once '../core/validator.php';
require_once '../core/uploader.php';
require_once '../core/auth.php';

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
    case 'add':
        addEmployee($conn, $auth);
        break;
        
    case 'update':
        updateEmployee($conn, $auth);
        break;
        
    case 'delete':
        deleteEmployee($conn, $auth);
        break;
        
    case 'add_bank':
        addBankDetails($conn, $auth);
        break;
        
    case 'add_document':
        addDocument($conn, $auth);
        break;
        
    case 'delete_document':
        deleteDocument($conn, $auth);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addEmployee($conn, $auth) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get form data
        $data = $_POST;
        
        // Validate required fields
        $required_fields = ['full_name', 'gender', 'dob', 'mobile_number', 'personal_email', 
                          'aadhaar_number', 'pan_number', 'employee_code', 'date_of_joining',
                          'employment_type', 'org_id', 'branch_id', 'department_id', 'designation_id'];
        
        foreach($required_fields as $field) {
            if(empty($data[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Validate Aadhaar
        if(!Validator::validateAadhaar($data['aadhaar_number'])) {
            throw new Exception("Invalid Aadhaar number");
        }
        
        // Validate PAN
        if(!Validator::validatePAN($data['pan_number'])) {
            throw new Exception("Invalid PAN number");
        }
        
        // Validate mobile
        if(!Validator::validatePhone($data['mobile_number'])) {
            throw new Exception("Invalid mobile number");
        }
        
        // Validate email
        if(!Validator::validateEmail($data['personal_email'])) {
            throw new Exception("Invalid email address");
        }
        
        // Check if employee code already exists
        $check_query = "SELECT id FROM employees WHERE employee_code = :employee_code";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':employee_code', $data['employee_code']);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            throw new Exception("Employee code already exists");
        }
        
        // Handle file upload
        $profile_photo = null;
        if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $uploader = new Uploader('../uploads/profile_photos/', ['jpg', 'jpeg', 'png', 'gif']);
            $result = $uploader->upload($_FILES['profile_photo'], 'profile');
            
            if($result['success']) {
                $profile_photo = $result['file_path'];
            } else {
                throw new Exception("Failed to upload profile photo: " . implode(', ', $result['errors']));
            }
        }
        
        // Insert employee
        $query = "INSERT INTO employees (
            org_id, branch_id, department_id, designation_id, shift_id,
            employee_code, full_name, gender, dob, blood_group, marital_status,
            nationality, present_address, permanent_address, mobile_number,
            emergency_contact_name, emergency_contact_number, personal_email,
            aadhaar_number, pan_number, passport_number, passport_valid_from,
            passport_valid_to, driving_license, dl_valid_from, dl_valid_to,
            uan_number, pf_number, esic_number, date_of_joining, employment_type,
            grade, reporting_manager_id, training_period, probation_period,
            confirmation_date, commitment_from, commitment_to, profile_photo,
            status
        ) VALUES (
            :org_id, :branch_id, :department_id, :designation_id, :shift_id,
            :employee_code, :full_name, :gender, :dob, :blood_group, :marital_status,
            :nationality, :present_address, :permanent_address, :mobile_number,
            :emergency_contact_name, :emergency_contact_number, :personal_email,
            :aadhaar_number, :pan_number, :passport_number, :passport_valid_from,
            :passport_valid_to, :driving_license, :dl_valid_from, :dl_valid_to,
            :uan_number, :pf_number, :esic_number, :date_of_joining, :employment_type,
            :grade, :reporting_manager_id, :training_period, :probation_period,
            :confirmation_date, :commitment_from, :commitment_to, :profile_photo,
            :status
        )";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':org_id', $data['org_id']);
        $stmt->bindParam(':branch_id', $data['branch_id']);
        $stmt->bindParam(':department_id', $data['department_id']);
        $stmt->bindParam(':designation_id', $data['designation_id']);
        $stmt->bindParam(':shift_id', $data['shift_id']);
        $stmt->bindParam(':employee_code', $data['employee_code']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':dob', $data['dob']);
        $stmt->bindParam(':blood_group', $data['blood_group']);
        $stmt->bindParam(':marital_status', $data['marital_status']);
        $stmt->bindParam(':nationality', $data['nationality']);
        $stmt->bindParam(':present_address', $data['present_address']);
        $stmt->bindParam(':permanent_address', $data['permanent_address']);
        $stmt->bindParam(':mobile_number', $data['mobile_number']);
        $stmt->bindParam(':emergency_contact_name', $data['emergency_contact_name']);
        $stmt->bindParam(':emergency_contact_number', $data['emergency_contact_number']);
        $stmt->bindParam(':personal_email', $data['personal_email']);
        $stmt->bindParam(':aadhaar_number', $data['aadhaar_number']);
        $stmt->bindParam(':pan_number', $data['pan_number']);
        $stmt->bindParam(':passport_number', $data['passport_number']);
        $stmt->bindParam(':passport_valid_from', $data['passport_valid_from']);
        $stmt->bindParam(':passport_valid_to', $data['passport_valid_to']);
        $stmt->bindParam(':driving_license', $data['driving_license']);
        $stmt->bindParam(':dl_valid_from', $data['dl_valid_from']);
        $stmt->bindParam(':dl_valid_to', $data['dl_valid_to']);
        $stmt->bindParam(':uan_number', $data['uan_number']);
        $stmt->bindParam(':pf_number', $data['pf_number']);
        $stmt->bindParam(':esic_number', $data['esic_number']);
        $stmt->bindParam(':date_of_joining', $data['date_of_joining']);
        $stmt->bindParam(':employment_type', $data['employment_type']);
        $stmt->bindParam(':grade', $data['grade']);
        $stmt->bindParam(':reporting_manager_id', $data['reporting_manager_id']);
        $stmt->bindParam(':training_period', $data['training_period']);
        $stmt->bindParam(':probation_period', $data['probation_period']);
        $stmt->bindParam(':confirmation_date', $data['confirmation_date']);
        $stmt->bindParam(':commitment_from', $data['commitment_from']);
        $stmt->bindParam(':commitment_to', $data['commitment_to']);
        $stmt->bindParam(':profile_photo', $profile_photo);
        $stmt->bindParam(':status', $data['status']);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to save employee: " . implode(', ', $stmt->errorInfo()));
        }
        
        $employee_id = $conn->lastInsertId();
        
        // Create user account for employee
        createEmployeeUserAccount($conn, $employee_id, $data);
        
        // Commit transaction
        $conn->commit();
        
        // Log activity
        logActivity($auth->getCurrentUser()['id'], 'Add', 'Employee', "Added employee: {$data['full_name']} ({$data['employee_code']})");
        
        header('Location: ../modules/employees/employee_view.php?id=' . $employee_id . '&success=Employee added successfully');
        exit();
        
    } catch(Exception $e) {
        $conn->rollBack();
        header('Location: ../modules/employees/employee_add.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

function updateEmployee($conn, $auth) {
    try {
        if(empty($_POST['id'])) {
            throw new Exception("Employee ID is required");
        }
        
        $employee_id = $_POST['id'];
        $data = $_POST;
        
        // Get current employee data
        $current_query = "SELECT profile_photo FROM employees WHERE id = :id";
        $current_stmt = $conn->prepare($current_query);
        $current_stmt->bindParam(':id', $employee_id);
        $current_stmt->execute();
        $current_employee = $current_stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$current_employee) {
            throw new Exception("Employee not found");
        }
        
        // Handle profile photo
        $profile_photo = $current_employee['profile_photo'];
        
        // Check if remove photo is requested
        if(isset($data['remove_photo']) && $data['remove_photo'] == '1') {
            if($profile_photo && file_exists('../uploads/profile_photos/' . $profile_photo)) {
                unlink('../uploads/profile_photos/' . $profile_photo);
            }
            $profile_photo = null;
        }
        
        // Upload new photo if provided
        if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            // Remove old photo if exists
            if($profile_photo && file_exists('../uploads/profile_photos/' . $profile_photo)) {
                unlink('../uploads/profile_photos/' . $profile_photo);
            }
            
            $uploader = new Uploader('../uploads/profile_photos/', ['jpg', 'jpeg', 'png', 'gif']);
            $result = $uploader->upload($_FILES['profile_photo'], 'profile');
            
            if($result['success']) {
                $profile_photo = $result['file_path'];
            } else {
                throw new Exception("Failed to upload profile photo: " . implode(', ', $result['errors']));
            }
        }
        
        // Update employee
        $query = "UPDATE employees SET
            org_id = :org_id,
            branch_id = :branch_id,
            department_id = :department_id,
            designation_id = :designation_id,
            shift_id = :shift_id,
            full_name = :full_name,
            gender = :gender,
            dob = :dob,
            blood_group = :blood_group,
            marital_status = :marital_status,
            nationality = :nationality,
            present_address = :present_address,
            permanent_address = :permanent_address,
            mobile_number = :mobile_number,
            emergency_contact_name = :emergency_contact_name,
            emergency_contact_number = :emergency_contact_number,
            personal_email = :personal_email,
            aadhaar_number = :aadhaar_number,
            pan_number = :pan_number,
            passport_number = :passport_number,
            passport_valid_from = :passport_valid_from,
            passport_valid_to = :passport_valid_to,
            driving_license = :driving_license,
            dl_valid_from = :dl_valid_from,
            dl_valid_to = :dl_valid_to,
            uan_number = :uan_number,
            pf_number = :pf_number,
            esic_number = :esic_number,
            date_of_joining = :date_of_joining,
            employment_type = :employment_type,
            grade = :grade,
            reporting_manager_id = :reporting_manager_id,
            training_period = :training_period,
            probation_period = :probation_period,
            confirmation_date = :confirmation_date,
            commitment_from = :commitment_from,
            commitment_to = :commitment_to,
            profile_photo = :profile_photo,
            status = :status
            WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $employee_id);
        $stmt->bindParam(':org_id', $data['org_id']);
        $stmt->bindParam(':branch_id', $data['branch_id']);
        $stmt->bindParam(':department_id', $data['department_id']);
        $stmt->bindParam(':designation_id', $data['designation_id']);
        $stmt->bindParam(':shift_id', $data['shift_id']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':dob', $data['dob']);
        $stmt->bindParam(':blood_group', $data['blood_group']);
        $stmt->bindParam(':marital_status', $data['marital_status']);
        $stmt->bindParam(':nationality', $data['nationality']);
        $stmt->bindParam(':present_address', $data['present_address']);
        $stmt->bindParam(':permanent_address', $data['permanent_address']);
        $stmt->bindParam(':mobile_number', $data['mobile_number']);
        $stmt->bindParam(':emergency_contact_name', $data['emergency_contact_name']);
        $stmt->bindParam(':emergency_contact_number', $data['emergency_contact_number']);
        $stmt->bindParam(':personal_email', $data['personal_email']);
        $stmt->bindParam(':aadhaar_number', $data['aadhaar_number']);
        $stmt->bindParam(':pan_number', $data['pan_number']);
        $stmt->bindParam(':passport_number', $data['passport_number']);
        $stmt->bindParam(':passport_valid_from', $data['passport_valid_from']);
        $stmt->bindParam(':passport_valid_to', $data['passport_valid_to']);
        $stmt->bindParam(':driving_license', $data['driving_license']);
        $stmt->bindParam(':dl_valid_from', $data['dl_valid_from']);
        $stmt->bindParam(':dl_valid_to', $data['dl_valid_to']);
        $stmt->bindParam(':uan_number', $data['uan_number']);
        $stmt->bindParam(':pf_number', $data['pf_number']);
        $stmt->bindParam(':esic_number', $data['esic_number']);
        $stmt->bindParam(':date_of_joining', $data['date_of_joining']);
        $stmt->bindParam(':employment_type', $data['employment_type']);
        $stmt->bindParam(':grade', $data['grade']);
        $stmt->bindParam(':reporting_manager_id', $data['reporting_manager_id']);
        $stmt->bindParam(':training_period', $data['training_period']);
        $stmt->bindParam(':probation_period', $data['probation_period']);
        $stmt->bindParam(':confirmation_date', $data['confirmation_date']);
        $stmt->bindParam(':commitment_from', $data['commitment_from']);
        $stmt->bindParam(':commitment_to', $data['commitment_to']);
        $stmt->bindParam(':profile_photo', $profile_photo);
        $stmt->bindParam(':status', $data['status']);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to update employee: " . implode(', ', $stmt->errorInfo()));
        }
        
        // Update user account if exists
        updateEmployeeUserAccount($conn, $employee_id, $data);
        
        // Log activity
        logActivity($auth->getCurrentUser()['id'], 'Update', 'Employee', "Updated employee: {$data['full_name']}");
        
        header('Location: ../modules/employees/employee_view.php?id=' . $employee_id . '&success=Employee updated successfully');
        exit();
        
    } catch(Exception $e) {
        header('Location: ../modules/employees/employee_edit.php?id=' . $employee_id . '&error=' . urlencode($e->getMessage()));
        exit();
    }
}

function deleteEmployee($conn, $auth) {
    try {
        if(empty($_POST['id'])) {
            throw new Exception("Employee ID is required");
        }
        
        $employee_id = $_POST['id'];
        
        // Get employee details for logging
        $query = "SELECT employee_code, full_name FROM employees WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $employee_id);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$employee) {
            throw new Exception("Employee not found");
        }
        
        // Check if employee can be deleted (no dependent records)
        // You can add additional checks here
        
        // Delete employee (cascade will handle related records)
        $delete_query = "DELETE FROM employees WHERE id = :id";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bindParam(':id', $employee_id);
        
        if(!$delete_stmt->execute()) {
            throw new Exception("Failed to delete employee");
        }
        
        // Log activity
        logActivity($auth->getCurrentUser()['id'], 'Delete', 'Employee', "Deleted employee: {$employee['full_name']} ({$employee['employee_code']})");
        
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createEmployeeUserAccount($conn, $employee_id, $employee_data) {
    // Get employee role ID
    $role_query = "SELECT id FROM roles WHERE role_name = 'Employee' LIMIT 1";
    $role_stmt = $conn->prepare($role_query);
    $role_stmt->execute();
    $role = $role_stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$role) {
        throw new Exception("Employee role not found");
    }
    
    // Generate username (employee code)
    $username = $employee_data['employee_code'];
    
    // Generate initial password (first 4 chars of Aadhaar + last 4 of mobile)
    $password = substr($employee_data['aadhaar_number'], 0, 4) . substr($employee_data['mobile_number'], -4);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user account
    $user_query = "INSERT INTO users (
        org_id, branch_id, employee_id, role_id,
        full_name, username, email, phone, password_hash, status
    ) VALUES (
        :org_id, :branch_id, :employee_id, :role_id,
        :full_name, :username, :email, :phone, :password_hash, 'Active'
    )";
    
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bindParam(':org_id', $employee_data['org_id']);
    $user_stmt->bindParam(':branch_id', $employee_data['branch_id']);
    $user_stmt->bindParam(':employee_id', $employee_id);
    $user_stmt->bindParam(':role_id', $role['id']);
    $user_stmt->bindParam(':full_name', $employee_data['full_name']);
    $user_stmt->bindParam(':username', $username);
    $user_stmt->bindParam(':email', $employee_data['personal_email']);
    $user_stmt->bindParam(':phone', $employee_data['mobile_number']);
    $user_stmt->bindParam(':password_hash', $password_hash);
    
    return $user_stmt->execute();
}

function updateEmployeeUserAccount($conn, $employee_id, $employee_data) {
    // Check if user account exists
    $check_query = "SELECT id FROM users WHERE employee_id = :employee_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':employee_id', $employee_id);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        // Update existing user account
        $update_query = "UPDATE users SET
            full_name = :full_name,
            email = :email,
            phone = :phone,
            status = :status
            WHERE employee_id = :employee_id";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':full_name', $employee_data['full_name']);
        $update_stmt->bindParam(':email', $employee_data['personal_email']);
        $update_stmt->bindParam(':phone', $employee_data['mobile_number']);
        $update_stmt->bindParam(':status', $employee_data['status']);
        $update_stmt->bindParam(':employee_id', $employee_id);
        
        return $update_stmt->execute();
    }
    
    return true;
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