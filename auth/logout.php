<?php
// auth/logout.php
session_start();
session_destroy();

// Redirect to login page
$login_url = defined('APP_URL') ? APP_URL . 'auth/login.php' : '/auth/login.php';
header('Location: ' . $login_url);
exit;
?>