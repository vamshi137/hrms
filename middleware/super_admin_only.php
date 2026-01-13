<?php
require_once 'login_required.php';

if($current_user['role_name'] !== 'Super Admin') {
    header('Location: ../dashboards/employee_dashboard.php?error=Access denied');
    exit();
}
?>