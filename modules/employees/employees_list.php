<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../core/session.php';
require_once '../../middleware/login_required.php';
require_once '../../middleware/hr_only.php';

$db = getDB();
$message = '';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Don't actually delete, just mark as inactive
        $query = "UPDATE employees SET status = 'Inactive', updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $message = "Employee deactivated successfully";
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch employees with filters
$where = "1=1";
$params = [];

if (isset($_GET['status']) && $_GET['status'] != '') {
    $where .= " AND e.status = :status";
    $params[':status'] = $_GET['status'];
}

if (isset($_GET['department']) && is_numeric($_GET['department'])) {
    $where .= " AND e.department_id = :dept";
    $params[':dept'] = $_GET['department'];
}

if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = "%" . $_GET['search'] . "%";
    $where .= " AND (e.employee_id LIKE :search OR e.full_name LIKE :search OR e.mobile_number LIKE :search)";
    $params[':search'] = $search;
}

// Fetch departments for filter
$deptStmt = $db->query("SELECT id, department_name FROM departments ORDER BY department_name");
$departments = $deptStmt->fetchAll();

// Fetch employees
$query = "SELECT e.*, d.department_name, ds.designation_name, b.branch_name 
          FROM employees e 
          LEFT JOIN departments d ON e.department_id = d.id 
          LEFT JOIN designations ds ON e.designation_id = ds.id 
          LEFT JOIN branches b ON e.work_location_id = b.id 
          WHERE $where 
          ORDER BY e.date_of_joining DESC, e.id DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$employees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>Employee List - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/datatables.min.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/sidebar_hr.php'; ?>
    
    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Employee Master</h1>
            <div class="actions">
                <a href="employee_add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Employee
                </a>
                <a href="employee_export.php" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
        <div class="alert alert-success">
            Employee added successfully! 
            <a href="employee_view.php?id=<?php echo $_GET['id']; ?>">View Employee</a>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-success">Employee updated successfully!</div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Filter Employees</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="filter-form">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Active" <?php echo isset($_GET['status']) && $_GET['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo isset($_GET['status']) && $_GET['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="Terminated" <?php echo isset($_GET['status']) && $_GET['status'] == 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                                    <option value="Resigned" <?php echo isset($_GET['status']) && $_GET['status'] == 'Resigned' ? 'selected' : ''; ?>>Resigned</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" name="department" class="form-control">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo isset($_GET['department']) && $_GET['department'] == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">Search (ID, Name, Mobile)</label>
                                <input type="text" id="search" name="search" 
                                       class="form-control" 
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                       placeholder="Search...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group" style="margin-top: 28px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="employees_list.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Employee List (<?php echo count($employees); ?> records)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Location</th>
                                <th>DOJ</th>
                                <th>Mobile</th>
                                <th>Status</th>
                                <th>KYC</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($emp['full_name']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($emp['personal_email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($emp['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['designation_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['branch_name']); ?></td>
                                <td><?php echo date('d-M-Y', strtotime($emp['date_of_joining'])); ?></td>
                                <td><?php echo htmlspecialchars($emp['mobile_number']); ?></td>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    switch($emp['status']) {
                                        case 'Active': $statusClass = 'badge-success'; break;
                                        case 'Inactive': $statusClass = 'badge-secondary'; break;
                                        case 'Terminated': $statusClass = 'badge-danger'; break;
                                        case 'Resigned': $statusClass = 'badge-warning'; break;
                                        default: $statusClass = 'badge-light';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $emp['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Check KYC completeness
                                    $kycComplete = ($emp['aadhaar_number'] && $emp['pan_number'] && $emp['pf_number']);
                                    ?>
                                    <span class="badge <?php echo $kycComplete ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $kycComplete ? 'Complete' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="employee_view.php?id=<?php echo $emp['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="employee_edit.php?id=<?php echo $emp['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="employee_docs.php?id=<?php echo $emp['id']; ?>" 
                                           class="btn btn-sm btn-secondary" title="Documents">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        <a href="employees_list.php?delete=<?php echo $emp['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to deactivate this employee?')" 
                                           title="Deactivate">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    $(document).ready(function() {
        $('#employeeTable').DataTable({
            "pageLength": 25,
            "order": [[0, "desc"]],
            "dom": '<"top"fl<"clear">>rt<"bottom"ip<"clear">>',
            "language": {
                "search": "Search in table:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });
    });
    </script>
</body>
</html>