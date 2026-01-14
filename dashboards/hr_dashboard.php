<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has HR role
if ($_SESSION['role'] !== 'hr') {
    // Redirect to appropriate dashboard based on role
    header('Location: ' . $_SESSION['role'] . '_dashboard.php');
    exit();
}

$userName = $_SESSION['full_name'] ?? 'HR Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SSMS HRMS</title>
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #7f8c8d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .welcome-section h1 span {
            color: var(--primary);
        }
        
        .welcome-section p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card:nth-child(1)::before { background: var(--primary); }
        .stat-card:nth-child(2)::before { background: var(--success); }
        .stat-card:nth-child(3)::before { background: var(--warning); }
        .stat-card:nth-child(4)::before { background: var(--danger); }
        
        .stat-title {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin: 10px 0;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .trend-up {
            color: var(--success);
        }
        
        .trend-down {
            color: var(--danger);
        }
        
        .trend-neutral {
            color: var(--gray);
        }
        
        /* Charts Section */
        .charts-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }
        
        .section-title {
            font-size: 1.3rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        .view-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-link:hover {
            text-decoration: underline;
        }
        
        /* Chart Placeholder */
        .chart-placeholder {
            height: 200px;
            background: var(--light);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            margin-bottom: 30px;
        }
        
        .chart-placeholder .y-axis {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            gap: 40px;
        }
        
        .chart-placeholder .x-axis {
            display: flex;
            justify-content: space-around;
            width: 100%;
            position: absolute;
            bottom: 30px;
        }
        
        /* Departments Section */
        .departments-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .departments-list {
            list-style: none;
            margin-top: 20px;
        }
        
        .department-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--light);
        }
        
        .department-item:last-child {
            border-bottom: none;
        }
        
        .department-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .department-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
        }
        
        .department-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        .department-count {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Footer */
        .dashboard-footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            padding: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px;
            }
            
            .welcome-section h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome back, <span><?php echo htmlspecialchars($userName); ?>!</span></h1>
                <p>Here's what's happening in your organization today.</p>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Employees</div>
                <div class="stat-value">248</div>
                <div class="stat-trend trend-up">↑ +12</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Present Today</div>
                <div class="stat-value">231</div>
                <div class="stat-trend trend-up">↑ 93.1%</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">On Leave</div>
                <div class="stat-value">14</div>
                <div class="stat-trend trend-neutral">5.6%</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Absent Today</div>
                <div class="stat-value">3</div>
                <div class="stat-trend trend-down">↓ 1.2%</div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
            <div class="section-header">
                <h2 class="section-title">Attendance Trends</h2>
                <a href="#" class="view-link">
                    View Details →
                </a>
            </div>
            
            <div class="chart-placeholder">
                <div class="y-axis">
                    <span>100</span>
                    <span>75</span>
                    <span>50</span>
                    <span>25</span>
                </div>
                <div class="x-axis">
                    <span>Week 1</span>
                    <span>Week 2</span>
                    <span>Week 3</span>
                    <span>Week 4</span>
                </div>
                <div style="margin-top: 20px; color: var(--gray);">
                    Monthly attendance overview
                </div>
            </div>
        </div>
        
        <!-- Departments Section -->
        <div class="departments-section">
            <div class="section-header">
                <h2 class="section-title">Departments</h2>
                <span style="color: var(--gray);">Employee distribution</span>
            </div>
            
            <ul class="departments-list">
                <li class="department-item">
                    <div class="department-info">
                        <div class="department-dot" style="background: #667eea;"></div>
                        <span class="department-name">Sales</span>
                    </div>
                    <span class="department-count">68</span>
                </li>
                <li class="department-item">
                    <div class="department-info">
                        <div class="department-dot" style="background: #27ae60;"></div>
                        <span class="department-name">Marketing</span>
                    </div>
                    <span class="department-count">42</span>
                </li>
                <li class="department-item">
                    <div class="department-info">
                        <div class="department-dot" style="background: #f39c12;"></div>
                        <span class="department-name">Human Resources</span>
                    </div>
                    <span class="department-count">28</span>
                </li>
                <li class="department-item">
                    <div class="department-info">
                        <div class="department-dot" style="background: #e74c3c;"></div>
                        <span class="department-name">IT</span>
                    </div>
                    <span class="department-count">35</span>
                </li>
                <li class="department-item">
                    <div class="department-info">
                        <div class="department-dot" style="background: #9b59b6;"></div>
                        <span class="department-name">Finance</span>
                    </div>
                    <span class="department-count">24</span>
                </li>
                <li class="department-item">
                    <div class="department-info">
                        <div class="department-dot" style="background: #3498db;"></div>
                        <span class="department-name">Operations</span>
                    </div>
                    <span class="department-count">51</span>
                </li>
            </ul>
        </div>
        
        <!-- Footer -->
        <div class="dashboard-footer">
            Welcome back!<br>
            You have successfully logged in.
        </div>
    </div>
    
    <script>
        // Add animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            // Update current time
            function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                const dateString = now.toLocaleDateString([], {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
                
                // Update any time/date elements if we add them
            }
            
            updateTime();
            setInterval(updateTime, 60000);
        });
        
        // Simple logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
        
        // Add keyboard shortcut for logout (Ctrl + L)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                logout();
            }
        });
    </script>
</body>
</html>