<?php
require_once '../core/session.php';
require_once '../core/auth.php';

$auth = new Auth();

if(!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$current_user = $auth->getCurrentUser();
?>