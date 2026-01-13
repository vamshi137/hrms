<?php
require_once 'login_required.php';

$allowed_roles = ['Super Admin', 'Admin', 'HR', 'Manager'];
if(!in_array($current_user['role_name'], $allowed_roles)) {
    header('Location: ../dashboards/employee_dashboard.php?error=Access denied');
    exit();
}
?>