<?php
// =========================================
// SSMS HRMS - ROOT INDEX ROUTER (FINAL FIXED)
// ✅ Loads login page by default
// ✅ Redirects logged-in users to dashboard
// ✅ InfinityFree compatible (NO return in main script)
// =========================================

session_start();

// Always use absolute folder paths for safety
$LOGIN_PAGE = __DIR__ . "/auth/login.php";

// Role -> Dashboard mapping
$dashboards = [
    "super_admin" => "dashboards/super_admin_dashboard.php",
    "admin"       => "dashboards/admin_dashboard.php",
    "hr"          => "dashboards/hr_dashboard.php",
    "manager"     => "dashboards/manager_dashboard.php",
    "employee"    => "dashboards/employee_dashboard.php",
];

// Detect request page
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$page = basename($path);

// Check if logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// ✅ Logout support (index.php?logout=1)
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_destroy();
    header("Location: auth/login.php");
    exit();
}

// ✅ If user is logged in
if ($logged_in) {

    $role = $_SESSION['role'] ?? "employee";
    $dashboard = $dashboards[$role] ?? $dashboards["employee"];

    // If user opens / or /index.php -> go dashboard
    if ($page == "" || $page == "index.php") {
        header("Location: $dashboard");
        exit();
    }

    // If user tries to open login again -> go dashboard
    if (strpos($path, "auth/login.php") !== false || $page == "login.php") {
        header("Location: $dashboard");
        exit();
    }

    // ✅ If logged in and accessing other pages -> allow it
    // Do nothing here (but DO NOT use return in main script)
    // So just stop routing:
    exit();
}

// ✅ If NOT logged in -> open login page as homepage
include $LOGIN_PAGE;
exit();
