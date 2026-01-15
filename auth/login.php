<?php
// Start session at the very top
require_once __DIR__ . '/../core/session.php';
Session::init();

// Check if already logged in - redirect to appropriate dashboard
if (Session::isLoggedIn()) {
    $role = Session::getUserRole();
    $dashboard_map = [
        'super_admin' => '../dashboards/super_admin_dashboard.php',
        'admin' => '../dashboards/admin_dashboard.php',
        'hr' => '../dashboards/hr_dashboard.php',
        'manager' => '../dashboards/manager_dashboard.php',
        'employee' => '../dashboards/employee_dashboard.php'
    ];
    $redirect = $dashboard_map[$role] ?? '../dashboards/employee_dashboard.php';
    header("Location: $redirect");
    exit();
}

// Get error or success messages from URL
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SSMS HRMS</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background circles */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -250px;
            right: -250px;
            animation: float 6s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(20px) scale(1.05); }
        }
        
        .login-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1100px;
            width: 100%;
            display: flex;
            min-height: 650px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 50px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            left: -100px;
        }
        
        .login-left::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -75px;
            right: -75px;
        }
        
        .logo-section {
            position: relative;
            z-index: 1;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .logo i {
            font-size: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .login-left h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .login-left .subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 30px;
            font-weight: 300;
        }
        
        .features {
            margin-top: 40px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .feature-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .feature-text {
            font-size: 1rem;
        }
        
        .login-right {
            flex: 1;
            padding: 60px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .login-header h2 {
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #718096;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            font-size: 1.1rem;
            color: #a0aec0;
            z-index: 1;
        }
        
        .input-field {
            width: 100%;
            padding: 16px 18px 16px 52px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
            background: #f7fafc;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .toggle-password {
            position: absolute;
            right: 18px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 8px;
            color: #a0aec0;
            z-index: 10;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: #667eea;
        }
        
        .password-label-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: #5568d3;
        }
        
        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .alert-error {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #a0aec0;
            font-size: 0.9rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .demo-accounts {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .demo-accounts h4 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }
        
        .demo-accounts h4 i {
            margin-right: 8px;
            color: #667eea;
        }
        
        .demo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        
        .demo-item:last-child {
            border-bottom: none;
        }
        
        .demo-label {
            color: #4a5568;
            font-weight: 600;
        }
        
        .demo-value {
            color: #718096;
            font-family: 'Courier New', monospace;
        }
        
        @media (max-width: 968px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
            
            .login-left h1 {
                font-size: 2.2rem;
            }
            
            .features {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .login-header h2 {
                font-size: 1.6rem;
            }
            
            .demo-accounts {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h1>SSMS HRMS</h1>
                <p class="subtitle">Human Resource Management System</p>
            </div>
            
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">Secure & Encrypted Login</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="feature-text">Real-time Attendance Tracking</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-text">Advanced Analytics & Reports</div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Please enter your credentials to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login_action.php">
                <div class="form-group">
                    <label class="form-label">Email or Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               name="username" 
                               class="input-field" 
                               placeholder="Enter your email or username" 
                               required 
                               autocomplete="username"
                               value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="password-label-wrapper">
                        <label class="form-label">Password</label>
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    <div class="password-wrapper">
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="input-field" 
                                   placeholder="Enter your password" 
                                   required 
                                   autocomplete="current-password">
                        </div>
                        <button type="button" class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="divider">
                <span>Demo Accounts</span>
            </div>
            
            <div class="demo-accounts">
                <h4><i class="fas fa-info-circle"></i> Test Credentials</h4>
                <div class="demo-item">
                    <span class="demo-label">Employee:</span>
                    <span class="demo-value">employee@ssspl.com</span>
                </div>
                <div class="demo-item">
                    <span class="demo-label">Manager:</span>
                    <span class="demo-value">manager@ssspl.com</span>
                </div>
                <div class="demo-item">
                    <span class="demo-label">Password:</span>
                    <span class="demo-value">demo@123</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-hide alerts after 7 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s, transform 0.5s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 7000);
    </script>
</body>
</html>
