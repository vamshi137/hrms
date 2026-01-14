<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../core/session.php';
require_once '../middleware/login_required.php';
require_once '../middleware/admin_only.php';

$db = getDB();

// Fetch dashboard statistics
try {
    // Total Employees
    $stmt = $db->query("SELECT COUNT(*) as total FROM employees WHERE status = 'Active'");
    $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Present Today (mock data)
    $presentToday = 231;
    $presentPercentage = number_format(($presentToday / $totalEmployees) * 100, 1);
    
    // On Leave (mock data)
    $onLeave = 14;
    $leavePercentage = number_format(($onLeave / $totalEmployees) * 100, 1);
    
    // Absent Today
    $absentToday = 3;
    $absentPercentage = number_format(($absentToday / $totalEmployees) * 100, 1);
    
    // Fetch department stats
    $stmt = $db->query("SELECT d.department_name, COUNT(e.id) as employee_count 
                       FROM departments d 
                       LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'Active' 
                       GROUP BY d.id 
                       ORDER BY employee_count DESC 
                       LIMIT 6");
    $departments = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

$userName = Session::get('full_name') ?: Session::get('username');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-wrapper">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-welcome">
                    <div class="welcome-text">
                        <h1>Admin Dashboard</h1>
                        <p>Welcome, <?php echo htmlspecialchars($userName); ?>! Here's your organization overview.</p>
                    </div>
                    <div class="welcome-user">
                        <div class="user-avatar">
                            <?php if (Session::get('profile_photo')): ?>
                            <img src="../uploads/profile_photos/<?php echo htmlspecialchars(Session::get('profile_photo')); ?>" alt="Profile">
                            <?php else: ?>
                            <img src="../assets/images/default_user.png" alt="Profile">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card total-employees">
                    <div class="stat-header">
                        <span class="stat-title">Total Employees</span>
                        <div class="stat-icon total">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo $totalEmployees; ?></h2>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>↑ +12 from last month</span>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card present-today">
                    <div class="stat-header">
                        <span class="stat-title">Present Today</span>
                        <div class="stat-icon present">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo $presentToday; ?></h2>
                        <div class="stat-trend">
                            <span class="trend-up">↑ <?php echo $presentPercentage; ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card on-leave">
                    <div class="stat-header">
                        <span class="stat-title">On Leave</span>
                        <div class="stat-icon leave">
                            <i class="fas fa-umbrella-beach"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo $onLeave; ?></h2>
                        <div class="stat-trend">
                            <span class="trend-neutral"><?php echo $leavePercentage; ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card absent-today">
                    <div class="stat-header">
                        <span class="stat-title">Absent Today</span>
                        <div class="stat-icon absent">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h2><?php echo $absentToday; ?></h2>
                        <div class="stat-trend">
                            <span class="trend-down">↓ <?php echo $absentPercentage; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts & Quick Actions -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Department Distribution</h3>
                    </div>
                    <div class="chart-body">
                        <div class="chart-container">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Admin Actions</h3>
                    </div>
                    <div class="chart-body">
                        <div class="actions-grid">
                            <a href="../modules/masters/organization.php" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <span>Organization Setup</span>
                            </a>
                            
                            <a href="../modules/users/users_list.php" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <span>User Management</span>
                            </a>
                            
                            <a href="../modules/reports/employee_report.php" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-file-export"></i>
                                </div>
                                <span>Export Reports</span>
                            </a>
                            
                            <a href="../modules/hr/compliance.php" class="action-btn">
                                <div class="action-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <span>Compliance</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
    <script>
    $(document).ready(function() {
        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentChart = new Chart(deptCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($departments, 'department_name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($departments, 'employee_count')); ?>,
                    backgroundColor: [
                        '#667eea', '#764ba2', '#27ae60', '#f39c12', '#e74c3c', '#3498db'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
    </script>
</body>
</html>