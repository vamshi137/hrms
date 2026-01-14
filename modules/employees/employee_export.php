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
    SELECT e.*, d.department_name, ds.designation_name, b.branch_name, 
           rm.full_name as reporting_manager_name, k.*
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN designations ds ON e.designation_id = ds.id
    LEFT JOIN branches b ON e.work_location = b.id
    LEFT JOIN employees rm ON e.reporting_manager_id = rm.id
    LEFT JOIN employee_kyc k ON e.id = k.employee_id
    ORDER BY e.id
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export to Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="employee_export_' . date('Ymd_His') . '.xls"');

echo "Employee ID\tFull Name\tGender\tDate of Birth\tBlood Group\tMarital Status\tNationality\tPresent Address\tMobile Number\tEmergency Contact Name\tEmergency Contact Number\tPersonal Email\tPermanent Address\tDate of Joining\tEmployment Type\tDepartment\tDesignation\tGrade\tReporting Manager\tWork Location\tShift Type\tTraining Period\tProbation Period\tConfirmation Date\tCommitment From\tCommitment To\tStatus\tAadhaar Number\tPAN Number\tPassport Number\tPassport Valid From\tPassport Valid To\tDriving License Number\tDL Valid From\tDL Valid To\tUAN Number\tPF Number\tESIC Number\n";

foreach ($employees as $emp) {
    echo $emp['employee_id'] . "\t";
    echo $emp['full_name'] . "\t";
    echo $emp['gender'] . "\t";
    echo $emp['date_of_birth'] . "\t";
    echo $emp['blood_group'] . "\t";
    echo $emp['marital_status'] . "\t";
    echo $emp['nationality'] . "\t";
    echo $emp['present_address'] . "\t";
    echo $emp['mobile_number'] . "\t";
    echo $emp['emergency_contact_name'] . "\t";
    echo $emp['emergency_contact_number'] . "\t";
    echo $emp['personal_email'] . "\t";
    echo $emp['permanent_address'] . "\t";
    echo $emp['date_of_joining'] . "\t";
    echo $emp['employment_type'] . "\t";
    echo $emp['department_name'] . "\t";
    echo $emp['designation_name'] . "\t";
    echo $emp['grade'] . "\t";
    echo $emp['reporting_manager_name'] . "\t";
    echo $emp['branch_name'] . "\t";
    echo $emp['shift_type'] . "\t";
    echo $emp['training_period'] . "\t";
    echo $emp['probation_period'] . "\t";
    echo $emp['confirmation_date'] . "\t";
    echo $emp['commitment_from'] . "\t";
    echo $emp['commitment_to'] . "\t";
    echo $emp['status'] . "\t";
    echo $emp['aadhaar_number'] . "\t";
    echo $emp['pan_number'] . "\t";
    echo $emp['passport_number'] . "\t";
    echo $emp['passport_valid_from'] . "\t";
    echo $emp['passport_valid_to'] . "\t";
    echo $emp['driving_license_number'] . "\t";
    echo $emp['dl_valid_from'] . "\t";
    echo $emp['dl_valid_to'] . "\t";
    echo $emp['uan_number'] . "\t";
    echo $emp['pf_number'] . "\t";
    echo $emp['esic_number'] . "\n";
}