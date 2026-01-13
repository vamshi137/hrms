<?php
require_once '../core/session.php';
require_once '../core/auth.php';
require_once '../core/csrf.php';

// Check if form is submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// Validate CSRF token
if(!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
    header('Location: login.php?error=Invalid security token');
    exit();
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if(empty($username) || empty($password)) {
    header('Location: login.php?error=Please fill all fields');
    exit();
}

// Sanitize input
$username = htmlspecialchars($username);

// Attempt login
$auth = new Auth();
$result = $auth->login($username, $password);

if($result['success']) {
    $user_role = $result['user']['role_name'];
    
    // Set redirect URL based on role
    $redirect_pages = [
        'Super Admin' => '../dashboards/super_admin_dashboard.php',
        'Admin' => '../dashboards/admin_dashboard.php',
        'HR' => '../dashboards/hr_dashboard.php',
        'Manager' => '../dashboards/manager_dashboard.php',
        'Employee' => '../dashboards/employee_dashboard.php'
    ];
    
    $redirect_url = $redirect_pages[$user_role] ?? '../dashboards/employee_dashboard.php';
    
    // Set remember me cookie if checked
    if(isset($_POST['remember']) && $_POST['remember'] == 'on') {
        setcookie('remember_token', bin2hex(random_bytes(32)), time() + (86400 * 30), "/");
    }
    
    // Redirect to dashboard
    header("Location: $redirect_url");
    exit();
} else {
    header('Location: login.php?error=' . urlencode($result['message']));
    exit();
}
?>