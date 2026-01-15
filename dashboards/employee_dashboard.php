<?php
// Include required files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/session.php';

// Initialize session
Session::init();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has employee role
if (Session::getUserRole() !== 'employee') {
    // Redirect to appropriate dashboard based on role
    $role = Session::getUserRole();
    $dashboard_map = [
        'super_admin' => 'super_admin_dashboard.php',
        'admin' => 'admin_dashboard.php',
        'hr' => 'hr_dashboard.php',
        'manager' => 'manager_dashboard.php'
    ];
    
    if (isset($dashboard_map[$role])) {
        header('Location: ' . $dashboard_map[$role]);
        exit();
    }
    
    // If role not found, logout
    header('Location: ../auth/logout.php');
    exit();
}

// Get user details from session
$user_id = Session::getUserId();
$employee_id = Session::getEmployeeId();
$full_name = Session::get('full_name', 'Employee');
$username = Session::get('username', 'employee@ssspl.com');
$email = Session::get('email', '');
$role = Session::getUserRole();
$role_name = Session::get('role_name', 'Employee');

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get current date info
$current_date = date('l, d F Y');
$current_month = date('F Y');
$current_year = date('Y');
$current_month_num = date('m');

// Initialize stats with default values
$total_present = 0;
$leave_balance = 12;
$leave_taken = 0;
$tasks_completed = 5;
$avg_work_hours = '8.5h';
$attendance_percentage = 0;

// Fetch real attendance data for current month
try {
    // Use employee_id from session if available, otherwise use user_id
    $emp_id = $employee_id ?? $user_id;
    
    $stmt = $db->prepare("SELECT COUNT(*) as present_days FROM attendance 
                         WHERE employee_id = :emp_id 
                         AND MONTH(attendance_date) = :month 
                         AND YEAR(attendance_date) = :year 
                         AND status = 'Present'");
    $stmt->bindParam(':emp_id', $emp_id);
    $stmt->bindParam(':month', $current_month_num);
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_present = $result['present_days'] ?? 0;
    
    // Calculate attendance percentage
    $total_working_days = date('j'); // Days passed in current month
    $attendance_percentage = $total_working_days > 0 ? round(($total_present / $total_working_days) * 100, 1) : 0;
} catch(PDOException $e) {
    // Keep default values if query fails
    error_log("Attendance query error: " . $e->getMessage());
}

// Fetch leave balance
try {
    $emp_id = $employee_id ?? $user_id;
    $stmt = $db->prepare("SELECT * FROM leave_balance WHERE employee_id = :emp_id LIMIT 1");
    $stmt->bindParam(':emp_id', $emp_id);
    $stmt->execute();
    $leave_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($leave_data) {
        $leave_balance = $leave_data['available'] ?? 12;
    }
} catch(PDOException $e) {
    // Keep default value
    error_log("Leave balance query error: " . $e->getMessage());
}

// Count approved leaves this year
try {
    $stmt = $db->prepare("SELECT COUNT(*) as leave_count FROM leave_applications 
                         WHERE employee_id = :user_id 
                         AND YEAR(from_date) = :year 
                         AND status = 'approved'");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $leave_taken = $result['leave_count'] ?? 0;
} catch(PDOException $e) {
    // Keep default value
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - SSSMS HRMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        /* Header */
        .header {
            background: #2d2d2d;
            color: white;
            padding: 0 30px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #d946a6 0%, #b91c8c 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }
        
        .logo-text h1 {
            font-size: 18px;
            font-weight: 600;
        }
        
        .logo-text p {
            font-size: 11px;
            opacity: 0.8;
            margin-top: -2px;
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 8px;
            min-width: 300px;
        }
        
        .search-bar input {
            background: none;
            border: none;
            color: white;
            outline: none;
            width: 100%;
            font-size: 14px;
        }
        
        .search-bar input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-icon {
            position: relative;
            cursor: pointer;
            font-size: 20px;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #d946a6;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-info {
            text-align: left;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
        }
        
        .user-role {
            font-size: 11px;
            opacity: 0.7;
            text-transform: capitalize;
        }
        
        /* Main Layout */
        .main-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            padding: 20px 0;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }
        
        .menu-section {
            margin-bottom: 10px;
        }
        
        .menu-item {
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .menu-item:hover {
            background: #f9f9f9;
            color: #d946a6;
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(217,70,166,0.1) 0%, rgba(255,255,255,0) 100%);
            color: #d946a6;
            border-left: 3px solid #d946a6;
            font-weight: 600;
        }
        
        .menu-icon {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .menu-badge {
            margin-left: auto;
            background: #d946a6;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title-section h1 {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        
        .page-title-section h1 span {
            background: linear-gradient(135deg, #d946a6 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .date-section {
            text-align: right;
        }
        
        .current-date {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .calendar-icon-btn {
            background: linear-gradient(135deg, #d946a6 0%, #b91c8c 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .stat-trend {
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-trend.up {
            color: #10b981;
        }
        
        .stat-trend.down {
            color: #ef4444;
        }
        
        .stat-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .stat-card:nth-child(1) .stat-icon-wrapper {
            background: linear-gradient(135deg, rgba(217,70,166,0.1) 0%, rgba(217,70,166,0.2) 100%);
        }
        
        .stat-card:nth-child(2) .stat-icon-wrapper {
            background: linear-gradient(135deg, rgba(124,58,237,0.1) 0%, rgba(124,58,237,0.2) 100%);
        }
        
        .stat-card:nth-child(3) .stat-icon-wrapper {
            background: linear-gradient(135deg, rgba(236,72,153,0.1) 0%, rgba(236,72,153,0.2) 100%);
        }
        
        .stat-card:nth-child(4) .stat-icon-wrapper {
            background: linear-gradient(135deg, rgba(139,92,246,0.1) 0%, rgba(139,92,246,0.2) 100%);
        }
        
        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .chart-subtitle {
            font-size: 13px;
            color: #666;
            margin-top: 2px;
        }
        
        .view-details-btn {
            background: none;
            border: none;
            color: #d946a6;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .chart-placeholder {
            height: 300px;
            background: linear-gradient(180deg, rgba(217,70,166,0.05) 0%, rgba(124,58,237,0.05) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        .donut-chart {
            height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .donut-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(
                #d946a6 0deg 120deg,
                #ec4899 120deg 210deg,
                #7c3aed 210deg 300deg,
                #a855f7 300deg 360deg
            );
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .donut-center {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #d946a6 0%, #7c3aed 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-top: 15px;
        }
        
        .welcome-message h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .welcome-message p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* User Info Bottom */
        .user-info-bottom {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 20px;
        }
        
        .user-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-card .user-avatar {
            width: 44px;
            height: 44px;
            font-size: 16px;
        }
        
        .user-card .user-info .user-name {
            font-size: 14px;
            color: #1a1a1a;
        }
        
        .user-card .user-info .user-role {
            font-size: 12px;
            color: #666;
        }
        
        /* Responsive */
        @media (max-width: 1400px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .search-bar {
                min-width: 200px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .header-left {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .search-bar {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo-section">
                <div class="logo-icon">S</div>
                <div class="logo-text">
                    <h1>SSSMS</h1>
                    <p>HR Management</p>
                </div>
            </div>
            
            <div class="search-bar">
                <span>🔍</span>
                <input type="text" placeholder="Search employees, reports...">
            </div>
        </div>
        
        <div class="header-right">
            <div class="notification-icon">
                🔔
                <span class="notification-badge">3</span>
            </div>
            
            <span>❓</span>
            
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($full_name); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($role_name); ?></div>
                </div>
            </div>
            
            <a href="../auth/logout.php" style="margin-left: 15px; padding: 8px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- User Profile Section in Sidebar -->
            <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold;">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #111827; font-size: 14px;"><?php echo htmlspecialchars($full_name); ?></div>
                        <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($role_name); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="menu-section">
                <a href="employee_dashboard.php" class="menu-item active">
                    <span class="menu-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                
                <a href="../modules/employees/employee_view.php?id=<?php echo $employee_id; ?>" class="menu-item">
                    <span class="menu-icon">👤</span>
                    <span>My Profile</span>
                </a>
                
                <a href="../modules/attendance/attendance_list.php" class="menu-item">
                    <span class="menu-icon">⏰</span>
                    <span>Attendance</span>
                </a>
                
                <a href="../modules/leave/leave_my_requests.php" class="menu-item">
                    <span class="menu-icon">📅</span>
                    <span>Leave</span>
                    <span class="menu-badge">3</span>
                </a>
                
                <a href="../modules/performance/self_appraisal.php" class="menu-item">
                    <span class="menu-icon">🎯</span>
                    <span>Performance</span>
                </a>
                
                <a href="../modules/training/training_list.php" class="menu-item">
                    <span class="menu-icon">🎓</span>
                    <span>Training</span>
                </a>
                
                <a href="../modules/payroll/payslip_download.php" class="menu-item">
                    <span class="menu-icon">💼</span>
                    <span>Payslips</span>
                </a>
                
                <a href="../modules/expenses/expense_apply.php" class="menu-item">
                    <span class="menu-icon">✈️</span>
                    <span>Expenses</span>
                </a>
                
                <a href="../modules/grievance/grievance_register.php" class="menu-item">
                    <span class="menu-icon">💬</span>
                    <span>Grievance</span>
                </a>
                
                <div style="margin: 20px 0; height: 1px; background: #e5e7eb;"></div>
                
                <a href="../auth/logout.php" class="menu-item" style="color: #dc2626;">
                    <span class="menu-icon">🚪</span>
                    <span><strong>Logout</strong></span>
                </a>
            </div>
            
            <div style="padding: 20px; margin-top: 20px;">
                <div class="user-card">
                    <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($role); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content">
            <div class="page-header">
                <div class="page-title-section">
                    <h1>Welcome back, <span><?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?></span>!</h1>
                    <p class="page-subtitle">Here's what's happening in your organization today.</p>
                </div>
                
                <div class="date-section">
                    <div class="current-date"><?php echo $current_date; ?></div>
                    <button class="calendar-icon-btn">
                        📅 Calendar
                    </button>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Present Today</div>
                        <div class="stat-value"><?php echo $total_present; ?></div>
                        <div class="stat-trend up">
                            ↑ <?php echo $attendance_percentage; ?>%
                        </div>
                    </div>
                    <div class="stat-icon-wrapper">
                        👥
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">On Leave</div>
                        <div class="stat-value"><?php echo $leave_taken; ?></div>
                        <div class="stat-trend">
                            5.6%
                        </div>
                    </div>
                    <div class="stat-icon-wrapper">
                        🏖️
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Leave Balance</div>
                        <div class="stat-value"><?php echo $leave_balance; ?></div>
                        <div class="stat-trend down">
                            ↓ 1.2%
                        </div>
                    </div>
                    <div class="stat-icon-wrapper">
                        ⏰
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Avg Work Hours</div>
                        <div class="stat-value"><?php echo $avg_work_hours; ?></div>
                        <div class="stat-trend up">
                            ↑ 2.1%
                        </div>
                    </div>
                    <div class="stat-icon-wrapper">
                        📊
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Attendance Trends</div>
                            <div class="chart-subtitle">Monthly attendance overview</div>
                        </div>
                        <button class="view-details-btn">
                            View Details →
                        </button>
                    </div>
                    <div class="chart-placeholder">
                        📈 Attendance chart will be displayed here
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Departments</div>
                            <div class="chart-subtitle">Employee distribution</div>
                        </div>
                    </div>
                    <div class="donut-chart">
                        <div class="donut-placeholder">
                            <div class="donut-center"></div>
                        </div>
                    </div>
                    <div class="welcome-message">
                        <h3>Welcome back!</h3>
                        <p>You have successfully logged in.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add any interactive JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded successfully');
        });
    </script>
</body>
</html>