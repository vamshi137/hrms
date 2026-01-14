<?php
require_once '../../config/db.php';
require_once '../../core/session.php';
require_once '../../middleware/login_required.php';

// Check if the user has HR or Admin role
if ($_SESSION['role'] != 'hr' && $_SESSION['role'] != 'admin') {
    header('Location: ../../index.php');
    exit;
}

$db = getDB();
$stmt = $db->query("
    SELECT e.employee_id, e.full_name, e.employment_type, e.grade, rm.full_name as reporting_manager, e.confirmation_date, e.status
    FROM employees e
    LEFT JOIN employees rm ON e.reporting_manager_id = rm.id
    ORDER BY e.id
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>Employment Status Report - HRMS</title>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar_hr.php'; ?>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Employment Status Report</h1>
                </div>

                <div class="table-responsive">
                    <table id="employmentTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Employment Type</th>
                                <th>Grade</th>
                                <th>Reporting Manager</th>
                                <th>Confirmation Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo $emp['employee_id']; ?></td>
                                <td><?php echo $emp['full_name']; ?></td>
                                <td><?php echo $emp['employment_type']; ?></td>
                                <td><?php echo $emp['grade']; ?></td>
                                <td><?php echo $emp['reporting_manager']; ?></td>
                                <td><?php echo $emp['confirmation_date']; ?></td>
                                <td>
                                    <?php if ($emp['status'] == 'Active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php elseif ($emp['status'] == 'Inactive'): ?>
                                        <span class="badge badge-warning">Inactive</span>
                                    <?php elseif ($emp['status'] == 'Terminated'): ?>
                                        <span class="badge badge-danger">Terminated</span>
                                    <?php elseif ($emp['status'] == 'Resigned'): ?>
                                        <span class="badge badge-secondary">Resigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script>
        $(document).ready(function() {
            $('#employmentTable').DataTable();
        });
    </script>
</body>
</html>