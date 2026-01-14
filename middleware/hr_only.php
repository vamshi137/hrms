<?php
require_once __DIR__ . '/../core/session.php';

if (!Session::isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit();
}

$userRole = Session::getUserRole();
if (!Session::hasPermission('hr')) {
    header('Location: ../../index.php?error=access_denied');
    exit();
}
?>