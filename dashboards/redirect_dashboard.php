<?php
/**
 * Dashboard Redirector
 * Redirects users to their respective dashboards based on role
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (defined('APP_URL') ? APP_URL . 'auth/login.php' : '/auth/login.php'));
    exit();
}

// Get user role
$user_role = $_SESSION['user_role'] ?? ROLE_EMPLOYEE;

// Define dashboard mapping
$dashboard_files = [
    ROLE_SUPER_ADMIN => 'super_admin_dashboard.php',
    ROLE_ADMIN => 'admin_dashboard.php',
    ROLE_HR => 'hr_dashboard.php',
    ROLE_MANAGER => 'manager_dashboard.php',
    ROLE_EMPLOYEE => 'employee_dashboard.php'
];

// Get dashboard file
$dashboard_file = $dashboard_files[$user_role] ?? 'employee_dashboard.php';
$dashboard_path = __DIR__ . '/' . $dashboard_file;

// Include the dashboard
if (file_exists($dashboard_path)) {
    require_once $dashboard_path;
} else {
    // Fallback to default dashboard
    echo "<h1>Welcome to HRMS</h1>";
    echo "<p>Your dashboard is being prepared. Please contact administrator.</p>";
    echo "<a href='" . (defined('APP_URL') ? APP_URL : '/') . "'>Go to Home</a>";
}
?>