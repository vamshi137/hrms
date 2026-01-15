<?php
// Start session at the very top
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../config/db.php';

Session::init();

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=Invalid request method');
    exit();
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    header('Location: login.php?error=Please fill all fields');
    exit();
}

// Sanitize username/email input
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        header('Location: login.php?error=Database connection failed');
        exit();
    }
    
    // Query to fetch user by username OR email and JOIN with roles table
    $query = "SELECT 
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.password_hash,
                u.employee_id,
                u.status,
                u.org_id,
                u.branch_id,
                u.role_id,
                r.role_slug,
                r.role_name
              FROM users u
              INNER JOIN roles r ON u.role_id = r.id
              WHERE (u.username = :username OR u.email = :username)
              AND r.status = 'active'
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    
    // Check if user exists
    if ($stmt->rowCount() == 0) {
        header('Location: login.php?error=Invalid username or email&username=' . urlencode($username));
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if account is active
    if ($user['status'] !== 'Active') {
        header('Location: login.php?error=Your account is inactive. Please contact administrator&username=' . urlencode($username));
        exit();
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        header('Location: login.php?error=Invalid password&username=' . urlencode($username));
        exit();
    }
    
    // Password is correct - Set session variables
    Session::set('logged_in', true);
    Session::set('user_id', $user['id']);
    Session::set('username', $user['username']);
    Session::set('email', $user['email']);
    Session::set('full_name', $user['full_name']);
    Session::set('role', $user['role_slug']); // role_slug for routing (employee, admin, hr, etc)
    Session::set('role_name', $user['role_name']); // role_name for display (Employee, Admin, HR, etc)
    Session::set('employee_id', $user['employee_id']);
    Session::set('org_id', $user['org_id']);
    Session::set('branch_id', $user['branch_id']);
    
    // Update last_login timestamp in database
    $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Redirect based on role_slug
    $role_slug = $user['role_slug'];
    
    $dashboard_map = [
        'super_admin' => '/dashboards/super_admin_dashboard.php',
        'admin' => '/dashboards/admin_dashboard.php',
        'hr' => '/dashboards/hr_dashboard.php',
        'manager' => '/dashboards/manager_dashboard.php',
        'employee' => '/dashboards/employee_dashboard.php'
    ];
    
    // Get redirect URL based on role
    $redirect_url = $dashboard_map[$role_slug] ?? '/dashboards/employee_dashboard.php';
    
    // Special handling for employee role
    if ($role_slug === 'employee') {
        header('Location: /dashboards/employee_dashboard.php');
        exit();
    }
    
    // Redirect to appropriate dashboard
    header("Location: $redirect_url");
    exit();
    
} catch (PDOException $e) {
    // Log error (in production, log to file instead of displaying)
    error_log("Login error: " . $e->getMessage());
    header('Location: login.php?error=An error occurred. Please try again later');
    exit();
}
?>
