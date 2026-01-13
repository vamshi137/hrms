<?php
/**
 * HRMS System - Professional Main Entry Point
 * Compatible with your current Database class
 */

// ============================================
// ERROR HANDLING & SECURITY
// ============================================

// Enable error reporting in development
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start output buffering for clean output
ob_start();

// Prevent XSS attacks
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ============================================
// LOAD CONFIGURATION
// ============================================

// Define base path
define('BASE_PATH', __DIR__);

// Load configuration files
try {
    // Load config.php
    $config_file = BASE_PATH . '/config/config.php';
    if (!file_exists($config_file)) {
        throw new Exception('Configuration file not found: config.php');
    }
    require_once $config_file;
    
    // Load database class
    $db_file = BASE_PATH . '/config/db.php';
    if (!file_exists($db_file)) {
        throw new Exception('Database configuration file not found: db.php');
    }
    require_once $db_file;
    
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}

// ============================================
// SESSION MANAGEMENT
// ============================================

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    // Session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session name
    session_name('HRMS_SESSION');
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// ============================================
// DATABASE CONNECTION
// ============================================

// Global database connection
$GLOBALS['db_connection'] = null;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Failed to establish database connection');
    }
    
    $GLOBALS['db_connection'] = $conn;
    define('DB_CONNECTED', true);
    
} catch (Exception $e) {
    if (APP_DEBUG) {
        die("Database Connection Error: " . $e->getMessage());
    } else {
        // Log error and show user-friendly message
        error_log("Database Error: " . $e->getMessage());
        die("System temporarily unavailable. Please try again later.");
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get database connection
 */
function getDB() {
    return $GLOBALS['db_connection'];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Redirect to URL
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if route is accessible without login
 */
function isPublicRoute($route) {
    $publicRoutes = [
        'auth/login',
        'auth/login_action',
        'auth/forgot_password',
        'auth/reset_password',
        'api/public'
    ];
    
    foreach ($publicRoutes as $publicRoute) {
        if (strpos($route, $publicRoute) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Get user role name
 */
function getRoleName($roleId) {
    $roles = [
        1 => 'Super Admin',
        2 => 'Admin',
        3 => 'HR Manager',
        4 => 'Manager',
        5 => 'Employee'
    ];
    return $roles[$roleId] ?? 'Unknown';
}

// ============================================
// URL ROUTING SYSTEM
// ============================================

// Get requested URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Remove query string
if (($pos = strpos($requestUri, '?')) !== false) {
    $requestUri = substr($requestUri, 0, $pos);
}

// Remove script name from URI
if (strpos($requestUri, $scriptName) === 0) {
    $requestUri = substr($requestUri, strlen($scriptName));
}

// Remove leading/trailing slashes
$requestUri = trim($requestUri, '/');

// Default route
if (empty($requestUri)) {
    $requestUri = 'home';
}

// Parse route parts
$routeParts = explode('/', $requestUri);
$mainRoute = $routeParts[0];

// ============================================
// PUBLIC ROUTES (No authentication required)
// ============================================

$publicRoutes = [
    'login' => 'auth/login.php',
    'login_action' => 'auth/login_action.php',
    'logout' => 'auth/logout.php',
    'forgot-password' => 'auth/forgot_password.php',
    'reset-password' => 'auth/reset_password.php'
];

// Check if current route is public
if (isset($publicRoutes[$mainRoute])) {
    $filePath = BASE_PATH . '/' . $publicRoutes[$mainRoute];
    if (file_exists($filePath)) {
        require_once $filePath;
        exit();
    }
}

// ============================================
// AUTHENTICATION CHECK
// ============================================

if (!isLoggedIn()) {
    // Store requested URL for redirect after login
    $_SESSION['redirect_url'] = currentUrl();
    
    // Redirect to login
    $loginUrl = defined('APP_URL') ? APP_URL . 'auth/login.php' : '/auth/login.php';
    redirect($loginUrl);
}

// ============================================
// PRIVATE ROUTES (Authentication required)
// ============================================

// Route mapping for authenticated users
$routes = [
    // Dashboard routes
    'home' => 'dashboard.php',
    'dashboard' => 'dashboard.php',
    
    // Employee management
    'employees' => 'modules/employees/employees_list.php',
    'employee-add' => 'modules/employees/employee_add.php',
    'employee-edit' => 'modules/employees/employee_edit.php',
    'employee-view' => 'modules/employees/employee_view.php',
    
    // Attendance
    'attendance' => 'modules/attendance/attendance_list.php',
    'attendance-mark' => 'modules/attendance/attendance_mark.php',
    'attendance-report' => 'modules/attendance/attendance_report.php',
    
    // Leave
    'leave' => 'modules/leave/leave_apply.php',
    'my-leaves' => 'modules/leave/leave_my_requests.php',
    'leave-approvals' => 'modules/leave/leave_approvals.php',
    
    // Payroll
    'payroll' => 'modules/payroll/payroll_list.php',
    'payslip' => 'modules/payroll/payslip_download.php',
    
    // Masters
    'departments' => 'modules/masters/department.php',
    'designations' => 'modules/masters/designation.php',
    'branches' => 'modules/masters/branch.php',
    'shifts' => 'modules/masters/shift.php',
    'holidays' => 'modules/masters/holiday.php',
    
    // User management
    'users' => 'modules/users/users_list.php',
    'user-add' => 'modules/users/user_add.php',
    'user-edit' => 'modules/users/user_edit.php',
    
    // Reports
    'reports' => 'modules/reports/employee_report.php',
    'attendance-report' => 'modules/reports/attendance_report.php',
    'payroll-report' => 'modules/reports/payroll_report.php',
    
    // API endpoints
    'api' => 'api/router.php',
];

// Check if route exists
if (isset($routes[$mainRoute])) {
    $filePath = BASE_PATH . '/' . $routes[$mainRoute];
    if (file_exists($filePath)) {
        require_once $filePath;
        exit();
    }
}

// ============================================
// DYNAMIC ROUTE HANDLING
// ============================================

// Try direct file access for backward compatibility
$directPath = BASE_PATH . '/' . $requestUri . '.php';
if (file_exists($directPath)) {
    require_once $directPath;
    exit();
}

// Try module path
$modulePath = BASE_PATH . '/modules/' . $requestUri . '.php';
if (file_exists($modulePath)) {
    require_once $modulePath;
    exit();
}

// Try directory index
$dirPath = BASE_PATH . '/' . $requestUri . '/index.php';
if (file_exists($dirPath)) {
    require_once $dirPath;
    exit();
}

// ============================================
// 404 PAGE NOT FOUND
// ============================================

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            border-radius: 15px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 90%;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            color: #333;
            margin-bottom: 20px;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            The page you are looking for might have been removed, had its name changed, 
            or is temporarily unavailable.
        </p>
        <a href="<?php echo defined('APP_URL') ? APP_URL : '/'; ?>" class="btn-home">
            <i class="fas fa-home me-2"></i>Go to Homepage
        </a>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
<?php
// End output buffering
ob_end_flush();
?>