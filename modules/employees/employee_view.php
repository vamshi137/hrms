<?php
require_once '../../middleware/hr_only.php';
require_once '../../config/db.php';

// Check if employee ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: employees_list.php?error=Employee ID required');
    exit();
}

$employee_id = $_GET['id'];

// Fetch employee data with all relationships
$database = new Database();
$conn = $database->getConnection();

$query = "SELECT e.*, 
          o.company_name, o.company_code,
          b.branch_name, b.branch_code,
          d.department_name, d.department_code,
          ds.designation_name, ds.grade_level,
          s.shift_name, s.shift_start, s.shift_end,
          rm.full_name as reporting_manager_name,
          rm.employee_code as reporting_manager_code
          FROM employees e
          LEFT JOIN organizations o ON e.org_id = o.id
          LEFT JOIN branches b ON e.branch_id = b.id
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN designations ds ON e.designation_id = ds.id
          LEFT JOIN shifts s ON e.shift_id = s.id
          LEFT JOIN employees rm ON e.reporting_manager_id = rm.id
          WHERE e.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $employee_id);
$stmt->execute();

if($stmt->rowCount() === 0) {
    header('Location: employees_list.php?error=Employee not found');
    exit();
}

$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch bank details
$bank_query = "SELECT * FROM employee_bank_details WHERE employee_id = :id";
$bank_stmt = $conn->prepare($bank_query);
$bank_stmt->bindParam(':id', $employee_id);
$bank_stmt->execute();
$bank_details = $bank_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch documents
$doc_query = "SELECT * FROM employee_documents WHERE employee_id = :id ORDER BY uploaded_at DESC";
$doc_stmt = $conn->prepare($doc_query);
$doc_stmt->bindParam(':id', $employee_id);
$doc_stmt->execute();
$documents = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Employee: ' . $employee['full_name'];
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
                    <h1 class="h3 mb-0">Employee Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/hr_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="employees_list.php">Employees</a></li>
                            <li class="breadcrumb-item active">View</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group">
                        <a href="employee_edit.php?id=<?php echo $employee_id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit mr-2"></i>Edit
                        </a>
                        <a href="employees_list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to List
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Employee Profile Card -->
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="profile-photo-container mb-4">
                            <img src="../../uploads/profile_photos/<?php echo $employee['profile_photo'] ?: 'default_user.png'; ?>" 
                                 class="rounded-circle border shadow" width="150" height="150"
                                 alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                        </div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($employee['full_name']); ?></h4>
                        <p class="text-muted mb-2"><?php echo $employee['designation_name']; ?></p>
                        <p class="mb-3">
                            <span class="badge badge-<?php echo $employee['status'] == 'Active' ? 'success' : 'danger'; ?>">
                                <?php echo $employee['status']; ?>
                            </span>
                        </p>
                        <div class="d-flex justify-content-center">
                            <div class="px-3">
                                <h5 class="mb-0">EMP ID</h5>
                                <small class="text-muted"><?php echo $employee['employee_code']; ?></small>
                            </div>
                            <div class="px-3">
                                <h5 class="mb-0">Grade</h5>
                                <small class="text-muted"><?php echo $employee['grade'] ?: 'N/A'; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-6 text-center">
                                <a href="mailto:<?php echo $employee['personal_email']; ?>" class="text-primary">
                                    <i class="fas fa-envelope fa-lg"></i>
                                </a>
                            </div>
                            <div class="col-6 text-center">
                                <a href="tel:<?php echo $employee['mobile_number']; ?>" class="text-success">
                                    <i class="fas fa-phone fa-lg"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Employment Info</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Organization</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['company_name']; ?></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Branch</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['branch_name']; ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Department</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['department_name']; ?></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Designation</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['designation_name']; ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Date of Joining</small>
                                <p class="mb-0 font-weight-bold"><?php echo date('d-m-Y', strtotime($employee['date_of_joining'])); ?></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Employment Type</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['employment_type']; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Reporting Manager</small>
                                <p class="mb-0 font-weight-bold">
                                    <?php echo $employee['reporting_manager_name'] ?: 'N/A'; ?>
                                </p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Shift</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['shift_name'] ?: 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Personal Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Personal Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Full Name</small>
                                <p class="mb-0 font-weight-bold"><?php echo htmlspecialchars($employee['full_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Gender</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['gender']; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Date of Birth</small>
                                <p class="mb-0 font-weight-bold"><?php echo date('d-m-Y', strtotime($employee['dob'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Age</small>
                                <p class="mb-0 font-weight-bold"><?php echo calculateAge($employee['dob']); ?> years</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Blood Group</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['blood_group'] ?: 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Marital Status</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['marital_status'] ?: 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Nationality</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['nationality'] ?: 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Mobile Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['mobile_number']; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Personal Email</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['personal_email']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Emergency Contact</small>
                                <p class="mb-0 font-weight-bold">
                                    <?php echo $employee['emergency_contact_name'] ?: 'N/A'; ?>
                                    <?php if($employee['emergency_contact_number']): ?>
                                        <br><small><?php echo $employee['emergency_contact_number']; ?></small>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Address Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Present Address</small>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($employee['present_address'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted">Permanent Address</small>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($employee['permanent_address'] ?: $employee['present_address'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Identity Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Identity Documents</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Aadhaar Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['aadhaar_number']; ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted">PAN Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['pan_number']; ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted">Passport Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['passport_number'] ?: 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted">UAN Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['uan_number'] ?: 'N/A'; ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted">PF Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['pf_number'] ?: 'N/A'; ?></p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <small class="text-muted">ESIC Number</small>
                                <p class="mb-0 font-weight-bold"><?php echo $employee['esic_number'] ?: 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Bank Account Details</h6>
                        <?php if($bank_details): ?>
                            <a href="employee_bank.php?id=<?php echo $employee_id; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                        <?php else: ?>
                            <a href="employee_bank.php?id=<?php echo $employee_id; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus mr-1"></i>Add Bank
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if($bank_details): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Bank Name</small>
                                    <p class="mb-0 font-weight-bold"><?php echo $bank_details['bank_name']; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Account Number</small>
                                    <p class="mb-0 font-weight-bold"><?php echo $bank_details['bank_account_number']; ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">IFSC Code</small>
                                    <p class="mb-0 font-weight-bold"><?php echo $bank_details['ifsc_code']; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Branch Name</small>
                                    <p class="mb-0 font-weight-bold"><?php echo $bank_details['branch_name']; ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted">Payment Mode</small>
                                    <p class="mb-0 font-weight-bold"><?php echo $bank_details['payment_mode']; ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-university fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No bank details added yet</p>
                                <a href="employee_bank.php?id=<?php echo $employee_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-plus mr-2"></i>Add Bank Details
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Employee Documents</h6>
                        <a href="employee_docs.php?id=<?php echo $employee_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus mr-1"></i>Add Document
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if(count($documents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Document Type</th>
                                            <th>File Name</th>
                                            <th>Uploaded At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($documents as $index => $doc): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                            <td>
                                                <?php 
                                                $filename = basename($doc['file_path']);
                                                echo htmlspecialchars($filename);
                                                ?>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="../../uploads/employee_docs/<?php echo $doc['file_path']; ?>" 
                                                   target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../../uploads/employee_docs/<?php echo $doc['file_path']; ?>" 
                                                   download class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger delete-doc" 
                                                        data-id="<?php echo $doc['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No documents uploaded yet</p>
                                <a href="employee_docs.php?id=<?php echo $employee_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-upload mr-2"></i>Upload Documents
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Document Modal -->
<div class="modal fade" id="deleteDocModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Document</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this document?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDocDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete document
    $('.delete-doc').click(function() {
        const docId = $(this).data('id');
        $('#deleteDocModal').modal('show');
        
        $('#confirmDocDelete').off('click').on('click', function() {
            $.ajax({
                url: '../../actions/employee_actions.php',
                type: 'POST',
                data: {
                    action: 'delete_document',
                    id: docId
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
});
</script>

<?php require_once '../../includes/footer.php'; ?>