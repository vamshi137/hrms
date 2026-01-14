<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../core/session.php';
require_once '../../middleware/login_required.php';

$db = getDB();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: employees_list.php');
    exit();
}

$id = $_GET['id'];

try {
    // Fetch employee details
    $query = "SELECT e.*, d.department_name, ds.designation_name, b.branch_name, 
                     s.shift_name, rm.full_name as reporting_manager_name,
                     rm.employee_id as reporting_manager_emp_id,
                     kc.*
              FROM employees e 
              LEFT JOIN departments d ON e.department_id = d.id 
              LEFT JOIN designations ds ON e.designation_id = ds.id 
              LEFT JOIN branches b ON e.work_location_id = b.id 
              LEFT JOIN shifts s ON e.shift_id = s.id 
              LEFT JOIN employees rm ON e.reporting_manager_id = rm.id 
              LEFT JOIN kyc_compliance kc ON e.id = kc.employee_id 
              WHERE e.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        header('Location: employees_list.php');
        exit();
    }
    
    // Fetch bank details
    $bankStmt = $db->prepare("SELECT * FROM employee_bank_details WHERE employee_id = :id ORDER BY is_primary DESC");
    $bankStmt->bindParam(':id', $id);
    $bankStmt->execute();
    $bankDetails = $bankStmt->fetchAll();
    
    // Fetch documents
    $docStmt = $db->prepare("SELECT * FROM employee_documents WHERE employee_id = :id ORDER BY document_type");
    $docStmt->bindParam(':id', $id);
    $docStmt->execute();
    $documents = $docStmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>View Employee - <?php echo APP_NAME; ?></title>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <?php 
    $userRole = Session::getUserRole();
    switch($userRole) {
        case 'super_admin':
        case 'admin':
            include '../../includes/sidebar_admin.php';
            break;
        case 'hr':
            include '../../includes/sidebar_hr.php';
            break;
        case 'manager':
            include '../../includes/sidebar_manager.php';
            break;
        case 'employee':
            include '../../includes/sidebar_employee.php';
            break;
    }
    ?>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-user"></i> Employee Details</h1>
            <div class="actions">
                <a href="employees_list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <?php if (Session::hasPermission('hr')): ?>
                <a href="employee_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
                <a href="employee_export.php?id=<?php echo $id; ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-print"></i> Print
                </a>
            </div>
        </div>
        
        <div class="employee-profile">
            <div class="row">
                <div class="col-md-3">
                    <div class="card profile-card">
                        <div class="card-body text-center">
                            <div class="profile-photo">
                                <?php if ($employee['profile_photo']): ?>
                                <img src="../../uploads/profile_photos/<?php echo htmlspecialchars($employee['profile_photo']); ?>" 
                                     alt="Profile Photo" class="img-thumbnail">
                                <?php else: ?>
                                <img src="../../assets/images/default_user.png" 
                                     alt="Profile Photo" class="img-thumbnail">
                                <?php endif; ?>
                            </div>
                            <h4 class="mt-3"><?php echo htmlspecialchars($employee['full_name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($employee['employee_id']); ?></p>
                            
                            <div class="employee-status">
                                <?php 
                                $statusClass = '';
                                switch($employee['status']) {
                                    case 'Active': $statusClass = 'badge-success'; break;
                                    case 'Inactive': $statusClass = 'badge-secondary'; break;
                                    case 'Terminated': $statusClass = 'badge-danger'; break;
                                    case 'Resigned': $statusClass = 'badge-warning'; break;
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?> badge-lg">
                                    <?php echo $employee['status']; ?>
                                </span>
                            </div>
                            
                            <hr>
                            
                            <div class="employee-info">
                                <p><strong>Department:</strong><br>
                                   <?php echo htmlspecialchars($employee['department_name']); ?>
                                </p>
                                <p><strong>Designation:</strong><br>
                                   <?php echo htmlspecialchars($employee['designation_name']); ?>
                                </p>
                                <p><strong>Location:</strong><br>
                                   <?php echo htmlspecialchars($employee['branch_name']); ?>
                                </p>
                                <p><strong>Date of Joining:</strong><br>
                                   <?php echo date('d-M-Y', strtotime($employee['date_of_joining'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6>Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="employee_docs.php?id=<?php echo $id; ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-file-alt"></i> Documents
                                </a>
                                <a href="employee_bank.php?id=<?php echo $id; ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-university"></i> Bank Details
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" onclick="generateKYCReport(<?php echo $id; ?>)">
                                    <i class="fas fa-file-contract"></i> KYC Report
                                </a>
                                <?php if (Session::hasPermission('hr')): ?>
                                <a href="#" class="list-group-item list-group-item-action text-danger" 
                                   onclick="changeStatus(<?php echo $id; ?>, '<?php echo $employee['status']; ?>')">
                                    <i class="fas fa-exchange-alt"></i> Change Status
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="employeeTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal">
                                <i class="fas fa-user-circle"></i> Personal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="employment-tab" data-toggle="tab" href="#employment">
                                <i class="fas fa-briefcase"></i> Employment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="kyc-tab" data-toggle="tab" href="#kyc">
                                <i class="fas fa-id-card"></i> KYC
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="bank-tab" data-toggle="tab" href="#bank">
                                <i class="fas fa-university"></i> Bank
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents">
                                <i class="fas fa-file-alt"></i> Documents
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="employeeTabContent">
                        <!-- Personal Details Tab -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h5 class="card-title">Personal Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Full Name:</th>
                                                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Employee ID:</th>
                                                    <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Gender:</th>
                                                    <td><?php echo htmlspecialchars($employee['gender']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Date of Birth:</th>
                                                    <td><?php echo date('d-M-Y', strtotime($employee['date_of_birth'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Age:</th>
                                                    <td>
                                                        <?php 
                                                        $birthDate = new DateTime($employee['date_of_birth']);
                                                        $today = new DateTime();
                                                        $age = $birthDate->diff($today)->y;
                                                        echo $age . ' years';
                                                        ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Blood Group:</th>
                                                    <td><?php echo htmlspecialchars($employee['blood_group']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Marital Status:</th>
                                                    <td><?php echo htmlspecialchars($employee['marital_status']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Nationality:</th>
                                                    <td><?php echo htmlspecialchars($employee['nationality']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Mobile Number:</th>
                                                    <td><?php echo htmlspecialchars($employee['mobile_number']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Personal Email:</th>
                                                    <td><?php echo htmlspecialchars($employee['personal_email']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mt-4">Address Details</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="address-box">
                                                <h6>Present Address:</h6>
                                                <p><?php echo nl2br(htmlspecialchars($employee['present_address'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="address-box">
                                                <h6>Permanent Address:</h6>
                                                <p><?php echo nl2br(htmlspecialchars($employee['permanent_address'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Emergency Contact:</th>
                                                    <td><?php echo htmlspecialchars($employee['emergency_contact_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Emergency Phone:</th>
                                                    <td><?php echo htmlspecialchars($employee['emergency_contact_number']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Employment Details Tab -->
                        <div class="tab-pane fade" id="employment" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h5 class="card-title">Employment Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Date of Joining:</th>
                                                    <td><?php echo date('d-M-Y', strtotime($employee['date_of_joining'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Employment Type:</th>
                                                    <td><?php echo htmlspecialchars($employee['employment_type']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Department:</th>
                                                    <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Designation:</th>
                                                    <td><?php echo htmlspecialchars($employee['designation_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Grade:</th>
                                                    <td><?php echo htmlspecialchars($employee['grade']); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Reporting Manager:</th>
                                                    <td>
                                                        <?php if ($employee['reporting_manager_name']): ?>
                                                        <?php echo htmlspecialchars($employee['reporting_manager_name']); ?>
                                                        (<?php echo htmlspecialchars($employee['reporting_manager_emp_id']); ?>)
                                                        <?php else: ?>
                                                        <span class="text-muted">Not Assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Work Location:</th>
                                                    <td><?php echo htmlspecialchars($employee['branch_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Shift:</th>
                                                    <td><?php echo htmlspecialchars($employee['shift_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Training Period:</th>
                                                    <td><?php echo $employee['training_period_days']; ?> days</td>
                                                </tr>
                                                <tr>
                                                    <th>Probation Period:</th>
                                                    <td><?php echo $employee['probation_period_days']; ?> days</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Confirmation Date:</th>
                                                    <td>
                                                        <?php if ($employee['confirmation_date']): ?>
                                                        <?php echo date('d-M-Y', strtotime($employee['confirmation_date'])); ?>
                                                        <?php else: ?>
                                                        <span class="text-muted">Not Confirmed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Commitment Period:</th>
                                                    <td>
                                                        <?php if ($employee['commitment_from']): ?>
                                                        <?php echo date('d-M-Y', strtotime($employee['commitment_from'])); ?>
                                                        to
                                                        <?php echo date('d-M-Y', strtotime($employee['commitment_to'])); ?>
                                                        <?php else: ?>
                                                        <span class="text-muted">Not Specified</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Years of Service:</th>
                                                    <td>
                                                        <?php 
                                                        $doj = new DateTime($employee['date_of_joining']);
                                                        $today = new DateTime();
                                                        $service = $doj->diff($today);
                                                        echo $service->y . ' years, ' . $service->m . ' months, ' . $service->d . ' days';
                                                        ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- KYC Details Tab -->
                        <div class="tab-pane fade" id="kyc" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">KYC & Identity Details</h5>
                                        <span class="badge badge-<?php echo $employee['overall_status'] == 'Complete' ? 'success' : 'warning'; ?>">
                                            KYC Status: <?php echo $employee['overall_status']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="kyc-item">
                                                <h6>Aadhaar Details</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th width="40%">Aadhaar Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['aadhaar_number']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Verified:</th>
                                                        <td>
                                                            <?php if ($employee['aadhaar_verified']): ?>
                                                            <span class="badge badge-success">Verified</span>
                                                            <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            
                                            <div class="kyc-item mt-3">
                                                <h6>PAN Details</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th width="40%">PAN Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['pan_number']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Verified:</th>
                                                        <td>
                                                            <?php if ($employee['pan_verified']): ?>
                                                            <span class="badge badge-success">Verified</span>
                                                            <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            
                                            <div class="kyc-item mt-3">
                                                <h6>Passport Details</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th width="40%">Passport Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['passport_number']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Validity:</th>
                                                        <td>
                                                            <?php if ($employee['passport_number']): ?>
                                                            <?php echo date('d-M-Y', strtotime($employee['passport_valid_from'])); ?>
                                                            to
                                                            <?php echo date('d-M-Y', strtotime($employee['passport_valid_to'])); ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Verified:</th>
                                                        <td>
                                                            <?php if ($employee['passport_verified']): ?>
                                                            <span class="badge badge-success">Verified</span>
                                                            <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="kyc-item">
                                                <h6>Driving License</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th width="40%">DL Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['driving_license']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Validity:</th>
                                                        <td>
                                                            <?php if ($employee['driving_license']): ?>
                                                            <?php echo date('d-M-Y', strtotime($employee['dl_valid_from'])); ?>
                                                            to
                                                            <?php echo date('d-M-Y', strtotime($employee['dl_valid_to'])); ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Verified:</th>
                                                        <td>
                                                            <?php if ($employee['dl_verified']): ?>
                                                            <span class="badge badge-success">Verified</span>
                                                            <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            
                                            <div class="kyc-item mt-3">
                                                <h6>Statutory Details</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th width="40%">UAN Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['uan_number']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>PF Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['pf_number']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>ESIC Number:</th>
                                                        <td><?php echo htmlspecialchars($employee['esic_number']); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            
                                            <div class="kyc-item mt-3">
                                                <h6>Verification History</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th width="40%">Last Verified:</th>
                                                        <td>
                                                            <?php if ($employee['last_verified_date']): ?>
                                                            <?php echo date('d-M-Y', strtotime($employee['last_verified_date'])); ?>
                                                            <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Next Verification:</th>
                                                        <td>
                                                            <?php if ($employee['next_verification_date']): ?>
                                                            <?php echo date('d-M-Y', strtotime($employee['next_verification_date'])); ?>
                                                            <?php else: ?>
                                                            <span class="text-muted">Not Scheduled</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bank Details Tab -->
                        <div class="tab-pane fade" id="bank" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">Bank Account Details</h5>
                                        <a href="employee_bank.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> Add/Edit
                                        </a>
                                    </div>
                                    
                                    <?php if (empty($bankDetails)): ?>
                                    <div class="alert alert-info">
                                        No bank details added yet.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Primary</th>
                                                    <th>Bank Name</th>
                                                    <th>Account Number</th>
                                                    <th>IFSC Code</th>
                                                    <th>Branch</th>
                                                    <th>Account Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bankDetails as $bank): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($bank['is_primary']): ?>
                                                        <span class="badge badge-success">Primary</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($bank['bank_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($bank['account_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($bank['ifsc_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($bank['branch_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($bank['account_type']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">Employee Documents</h5>
                                        <a href="employee_docs.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> Add Document
                                        </a>
                                    </div>
                                    
                                    <?php if (empty($documents)): ?>
                                    <div class="alert alert-info">
                                        No documents uploaded yet.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Document Type</th>
                                                    <th>Document Number</th>
                                                    <th>Validity</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($documents as $doc): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($doc['document_number']); ?></td>
                                                    <td>
                                                        <?php if ($doc['valid_from']): ?>
                                                        <?php echo date('d-M-Y', strtotime($doc['valid_from'])); ?>
                                                        to
                                                        <?php echo date('d-M-Y', strtotime($doc['valid_to'])); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($doc['verified']): ?>
                                                        <span class="badge badge-success">Verified</span>
                                                        <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($doc['document_path']): ?>
                                                        <a href="../../uploads/employee_docs/<?php echo htmlspecialchars($doc['document_path']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-info">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    function generateKYCReport(employeeId) {
        window.open('employee_export.php?id=' + employeeId + '&type=kyc', '_blank');
    }
    
    function changeStatus(employeeId, currentStatus) {
        var newStatus = prompt('Enter new status (Active, Inactive, Terminated, Resigned):', currentStatus);
        if (newStatus && newStatus !== currentStatus) {
            if (confirm('Are you sure you want to change status to ' + newStatus + '?')) {
                $.ajax({
                    url: '../../actions/employee_actions.php',
                    method: 'POST',
                    data: {
                        action: 'change_status',
                        employee_id: employeeId,
                        new_status: newStatus
                    },
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.success) {
                            alert('Status changed successfully');
                            location.reload();
                        } else {
                            alert('Error: ' + result.message);
                        }
                    }
                });
            }
        }
    }
    </script>
</body>
</html>