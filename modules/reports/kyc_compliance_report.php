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
    SELECT e.employee_id, e.full_name, k.aadhaar_number, k.pan_number, k.pf_number, k.esic_number,
           CASE 
                WHEN k.aadhaar_number IS NOT NULL AND k.pan_number IS NOT NULL AND k.pf_number IS NOT NULL AND k.esic_number IS NOT NULL THEN 'Complete'
                ELSE 'Incomplete'
           END as kyc_status
    FROM employees e
    LEFT JOIN employee_kyc k ON e.id = k.employee_id
    ORDER BY e.id
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../includes/head.php'; ?>
    <title>KYC Compliance Report - HRMS</title>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar_hr.php'; ?>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">KYC Compliance Report</h1>
                </div>

                <div class="table-responsive">
                    <table id="kycTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Aadhaar</th>
                                <th>PAN</th>
                                <th>PF</th>
                                <th>ESIC</th>
                                <th>KYC Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo $emp['employee_id']; ?></td>
                                <td><?php echo $emp['full_name']; ?></td>
                                <td><?php echo $emp['aadhaar_number'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $emp['pan_number'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $emp['pf_number'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $emp['esic_number'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <?php if ($emp['kyc_status'] == 'Complete'): ?>
                                        <span class="badge badge-success">Complete</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Incomplete</span>
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
            $('#kycTable').DataTable();
        });
    </script>
</body>
</html>