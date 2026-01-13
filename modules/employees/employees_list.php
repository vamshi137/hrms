<?php
require_once '../../middleware/hr_only.php';
require_once '../../config/db.php';
$page_title = 'Employee List';
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
                    <h1 class="h3 mb-0">Employee Management</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../dashboards/hr_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Employee List</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-right">
                    <a href="employee_add.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Add Employee
                    </a>
                    <a href="employee_export.php" class="btn btn-success">
                        <i class="fas fa-file-excel mr-2"></i>Export
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php require_once '../../includes/alerts.php'; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department" class="form-control select2">
                                    <option value="">All Departments</option>
                                    <?php
                                    $dept_query = "SELECT * FROM departments WHERE status = 'Active'";
                                    $dept_stmt = $conn->prepare($dept_query);
                                    $dept_stmt->execute();
                                    while($dept = $dept_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = ($_GET['department'] ?? '') == $dept['id'] ? 'selected' : '';
                                        echo "<option value='{$dept['id']}' $selected>{$dept['department_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Designation</label>
                                <select name="designation" class="form-control select2">
                                    <option value="">All Designations</option>
                                    <?php
                                    $desg_query = "SELECT * FROM designations WHERE status = 'Active'";
                                    $desg_stmt = $conn->prepare($desg_query);
                                    $desg_stmt->execute();
                                    while($desg = $desg_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = ($_GET['designation'] ?? '') == $desg['id'] ? 'selected' : '';
                                        echo "<option value='{$desg['id']}' $selected>{$desg['designation_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Active" <?php echo ($_GET['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($_GET['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Name or Employee Code" 
                                           value="<?php echo $_GET['search'] ?? ''; ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employee List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Employee Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee Code</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Mobile</th>
                                <th>DOJ</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query with filters
                            $query = "SELECT e.*, d.department_name, ds.designation_name 
                                      FROM employees e
                                      LEFT JOIN departments d ON e.department_id = d.id
                                      LEFT JOIN designations ds ON e.designation_id = ds.id
                                      WHERE 1=1";
                            
                            $params = [];
                            
                            if(!empty($_GET['department'])) {
                                $query .= " AND e.department_id = :department";
                                $params[':department'] = $_GET['department'];
                            }
                            
                            if(!empty($_GET['designation'])) {
                                $query .= " AND e.designation_id = :designation";
                                $params[':designation'] = $_GET['designation'];
                            }
                            
                            if(!empty($_GET['status'])) {
                                $query .= " AND e.status = :status";
                                $params[':status'] = $_GET['status'];
                            }
                            
                            if(!empty($_GET['search'])) {
                                $query .= " AND (e.full_name LIKE :search OR e.employee_code LIKE :search)";
                                $params[':search'] = '%' . $_GET['search'] . '%';
                            }
                            
                            $query .= " ORDER BY e.id DESC";
                            
                            $stmt = $conn->prepare($query);
                            $stmt->execute($params);
                            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            $count = 1;
                            foreach($employees as $employee):
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($employee['employee_code']); ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../../uploads/profile_photos/<?php echo $employee['profile_photo'] ?: 'default_user.png'; ?>" 
                                             class="rounded-circle mr-3" width="40" height="40" 
                                             alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($employee['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo $employee['personal_email'] ?? 'N/A'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['designation_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($employee['mobile_number'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($employee['date_of_joining'])); ?></td>
                                <td>
                                    <?php
                                    $status_badge = $employee['status'] == 'Active' ? 'success' : 'danger';
                                    echo "<span class='badge badge-$status_badge'>{$employee['status']}</span>";
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="employee_view.php?id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="employee_edit.php?id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-employee" 
                                                data-id="<?php echo $employee['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($employee['full_name']); ?>"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete employee: <strong id="deleteEmpName"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
function resetFilters() {
    window.location.href = 'employees_list.php';
}

$(document).ready(function() {
    // Delete employee confirmation
    $('.delete-employee').click(function() {
        const empId = $(this).data('id');
        const empName = $(this).data('name');
        
        $('#deleteEmpName').text(empName);
        $('#deleteModal').modal('show');
        
        $('#confirmDelete').off('click').on('click', function() {
            $.ajax({
                url: '../../actions/employee_actions.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: empId
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