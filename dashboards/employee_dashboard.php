<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has employee role
if ($_SESSION['role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user details
$username = $_SESSION['username'] ?? 'Employee';
$full_name = $_SESSION['full_name'] ?? 'Employee User';
$role = $_SESSION['role'] ?? 'employee';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - HRMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 30px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #8b2fc9;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            color: #666;
            text-transform: capitalize;
        }
        
        .logout-btn {
            padding: 8px 20px;
            background: #e91e63;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #d81b60;
        }
        
        /* Main Container */
        .main-container {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #e0e0e0;
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            background: #f8f9fa;
            color: #8b2fc9;
            border-left-color: #8b2fc9;
        }
        
        .menu-item.active {
            background: #f3e5f5;
            color: #8b2fc9;
            border-left-color: #8b2fc9;
            font-weight: 600;
        }
        
        .menu-icon {
            font-size: 20px;
        }
        
        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }
        
        .stat-card.purple { border-left-color: #8b2fc9; }
        .stat-card.blue { border-left-color: #3498db; }
        .stat-card.green { border-left-color: #2ecc71; }
        .stat-card.orange { border-left-color: #f39c12; }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Quick Actions */
        .section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            padding: 16px 20px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: #333;
        }
        
        .action-btn:hover {
            background: #8b2fc9;
            color: white;
            border-color: #8b2fc9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 47, 201, 0.2);
        }
        
        .action-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .action-label {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Recent Activity */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f3e5f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #8b2fc9;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 2px;
        }
        
        .activity-time {
            font-size: 12px;
            color: #999;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            
            .content {
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 0 15px;
            }
            
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <div class="logo">SSSMS</div>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($full_name); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($role); ?></div>
            </div>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="#" class="menu-item active">
                <span class="menu-icon">🏠</span>
                <span>Dashboard</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">👤</span>
                <span>My Profile</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">📅</span>
                <span>Attendance</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">🏖️</span>
                <span>Leave</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">💰</span>
                <span>Payslips</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">💸</span>
                <span>Expenses</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">📊</span>
                <span>Performance</span>
            </a>
            <a href="#" class="menu-item">
                <span class="menu-icon">📚</span>
                <span>Training</span>
            </a>
        </div>
        
        <!-- Content Area -->
        <div class="content">
            <div class="page-header">
                <h1 class="page-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?>!</h1>
                <p class="page-subtitle">Here's what's happening with your work today</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value">22</div>
                    <div class="stat-label">Days Present</div>
                </div>
                
                <div class="stat-card blue">
                    <div class="stat-icon">🏖️</div>
                    <div class="stat-value">12</div>
                    <div class="stat-label">Leave Balance</div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value">5</div>
                    <div class="stat-label">Tasks Completed</div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-value">8.5h</div>
                    <div class="stat-label">Avg Work Hours</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="section">
                <h2 class="section-title">Quick Actions</h2>
                <div class="quick-actions">
                    <a href="#" class="action-btn">
                        <div class="action-icon">✓</div>
                        <div class="action-label">Mark Attendance</div>
                    </a>
                    
                    <a href="#" class="action-btn">
                        <div class="action-icon">🏖️</div>
                        <div class="action-label">Apply Leave</div>
                    </a>
                    
                    <a href="#" class="action-btn">
                        <div class="action-icon">💸</div>
                        <div class="action-label">Submit Expense</div>
                    </a>
                    
                    <a href="#" class="action-btn">
                        <div class="action-icon">📄</div>
                        <div class="action-label">View Payslip</div>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="section">
                <h2 class="section-title">Recent Activity</h2>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon">✓</div>
                        <div class="activity-content">
                            <div class="activity-title">Attendance marked for today</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon">🏖️</div>
                        <div class="activity-content">
                            <div class="activity-title">Leave request approved by manager</div>
                            <div class="activity-time">Yesterday</div>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon">💰</div>
                        <div class="activity-content">
                            <div class="activity-title">Salary credited to your account</div>
                            <div class="activity-time">3 days ago</div>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon">📚</div>
                        <div class="activity-content">
                            <div class="activity-title">Training session completed</div>
                            <div class="activity-time">1 week ago</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>