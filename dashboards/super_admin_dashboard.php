<?php
// dashboards/super_admin_dashboard.php
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (defined('APP_URL') ? APP_URL . 'auth/login.php' : '/auth/login.php'));
    exit;
}

$user_name = $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: #343a40;
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
        }
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #4b545c;
            margin-bottom: 20px;
        }
        .nav-link {
            color: #adb5bd;
            padding: 12px 20px;
            margin: 5px 0;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background: #495057;
        }
        .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .bg-primary { background: #667eea; }
        .bg-success { background: #28a745; }
        .bg-warning { background: #ffc107; }
        .bg-info { background: #17a2b8; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h3>HRMS</h3>
            <p class="text-muted">Super Admin Panel</p>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="/">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="/employees">
                <i class="bi bi-people"></i> Employees
            </a>
            <a class="nav-link" href="/attendance">
                <i class="bi bi-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="/leave">
                <i class="bi bi-calendar-event"></i> Leave
            </a>
            <a class="nav-link" href="/payroll">
                <i class="bi bi-cash"></i> Payroll
            </a>
            <a class="nav-link" href="/departments">
                <i class="bi bi-building"></i> Departments
            </a>
            <a class="nav-link" href="/users">
                <i class="bi bi-person-badge"></i> Users
            </a>
            <a class="nav-link" href="/reports">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
            <div class="mt-5">
                <a class="nav-link text-danger" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Dashboard</h1>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3>150</h3>
                    <p class="text-muted">Total Employees</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h3>95%</h3>
                    <p class="text-muted">Today's Attendance</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h3>12</h3>
                    <p class="text-muted">Pending Leaves</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="bi bi-cash"></i>
                    </div>
                    <h3>₹ 5,82,000</h3>
                    <p class="text-muted">This Month Payroll</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="stat-card">
                    <h4>Recent Activities</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-plus-circle text-success"></i> New employee added: John Doe
                            <span class="float-end text-muted">2 hours ago</span>
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-calendar-check text-primary"></i> Attendance marked for 145 employees
                            <span class="float-end text-muted">4 hours ago</span>
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-cash text-warning"></i> Payroll processed for March 2024
                            <span class="float-end text-muted">1 day ago</span>
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-calendar-event text-info"></i> Leave approved for Sarah Smith
                            <span class="float-end text-muted">2 days ago</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h4>Quick Actions</h4>
                    <div class="d-grid gap-2">
                        <a href="/employee-add" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Add Employee
                        </a>
                        <a href="/attendance-mark" class="btn btn-success">
                            <i class="bi bi-calendar-check"></i> Mark Attendance
                        </a>
                        <a href="/leave-approvals" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Approve Leaves
                        </a>
                        <a href="/payroll" class="btn btn-info">
                            <i class="bi bi-calculator"></i> Process Payroll
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>