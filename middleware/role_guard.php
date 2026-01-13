<?php
require_once __DIR__ . "/login_required.php";

function require_role(array $allowed_roles) {
    $role = $_SESSION['role_name'] ?? '';
    if (!in_array($role, $allowed_roles)) {
        redirect("/auth/login.php");
    }
}
