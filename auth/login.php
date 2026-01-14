<?php
// Check if already logged in - but no redirect loops
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Let JavaScript handle the redirect
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Demo login credentials (no database needed initially)
    $valid_credentials = [
        'admin@ssspl.com' => ['password' => 'demo@123', 'role' => 'admin', 'name' => 'Admin User'],
        'hr@ssspl.com' => ['password' => 'demo@123', 'role' => 'hr', 'name' => 'HR Manager'],
        'manager@ssspl.com' => ['password' => 'demo@123', 'role' => 'manager', 'name' => 'Department Manager'],
        'employee@ssspl.com' => ['password' => 'demo@123', 'role' => 'employee', 'name' => 'John Doe'],
        'accounts@ssspl.com' => ['password' => 'demo@123', 'role' => 'accounts', 'name' => 'Accounts Officer']
    ];
    
    if (isset($valid_credentials[$username]) && $valid_credentials[$username]['password'] === $password) {
        // Set session
        $_SESSION['logged_in'] = true;
        $_SESSION['role'] = $valid_credentials[$username]['role'];
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $valid_credentials[$username]['name'];
        $_SESSION['user_id'] = 1;
        
        // Redirect using JavaScript to prevent loop
        echo '<script>window.location.href = "dashboards/' . $valid_credentials[$username]['role'] . '_dashboard.php";</script>';
        exit();
    } else {
        $error = "Invalid credentials. Use demo accounts.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SSMS HRMS</title>
    <style>
        /* Reset and Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Main Container */
        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Left Panel - Branding */
        .branding-panel {
            background: linear-gradient(135deg, #8b2fc9 0%, #6b1b9a 100%);
            color: white;
            padding: 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .tagline {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .company-name {
            font-size: 36px;
            font-weight: 300;
            margin: 60px 0 20px 0;
            line-height: 1.3;
        }
        
        .company-description {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
            font-weight: 300;
        }
        
        .features {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 0;
            padding: 0;
        }
        
        .features li {
            display: flex;
            align-items: center;
            font-size: 15px;
            font-weight: 300;
        }
        
        .features li:before {
            content: "●";
            margin-right: 12px;
            font-size: 20px;
            color: #e91e63;
        }
        
        /* Right Panel - Login Form */
        .login-panel {
            background: #fafafa;
            padding: 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
        }
        
        .login-form-wrapper {
            width: 100%;
            max-width: 500px;
        }
        
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-title {
            font-size: 32px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 15px;
            font-weight: 400;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 15px;
        }
        
        .password-label-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .forgot-password {
            color: #e91e63;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            z-index: 2;
        }
        
        .input-field {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            background: #f8f9fa;
            color: #333;
        }
        
        .input-field::placeholder {
            color: #999;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #8b2fc9;
            box-shadow: 0 0 0 3px rgba(139, 47, 201, 0.1);
            background: white;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: #e91e63;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        
        .login-button:hover {
            background: #d81b60;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
        }
        
        /* Demo Accounts Section */
        .demo-section {
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid #e0e0e0;
        }
        
        .demo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .demo-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-badge {
            background: #8b2fc9;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .demo-accounts {
            display: grid;
            gap: 0;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        
        .demo-account {
            padding: 16px 20px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .demo-account:last-child {
            border-bottom: none;
        }
        
        .demo-account:hover {
            background: #f8f9fa;
            padding-left: 24px;
        }
        
        .account-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .account-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .account-color.red { background: #e74c3c; }
        .account-color.purple { background: #9b59b6; }
        .account-color.blue { background: #3498db; }
        .account-color.green { background: #2ecc71; }
        .account-color.orange { background: #f39c12; }
        
        .account-role {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 15px;
        }
        
        .account-email {
            color: #999;
            font-size: 14px;
            font-weight: 400;
        }
        
        .demo-note {
            text-align: center;
            color: #999;
            font-size: 13px;
            margin-top: 16px;
            line-height: 1.5;
        }
        
        .contact-section {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }
        
        .contact-link {
            color: #e91e63;
            text-decoration: none;
            font-weight: 500;
        }
        
        .contact-link:hover {
            text-decoration: underline;
        }
        
        /* Error Message */
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            border-left: 3px solid #c62828;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .branding-panel, .login-panel {
                padding: 50px 60px;
            }
            
            .login-form-wrapper {
                padding: 40px 35px;
            }
            
            .login-card {
                padding: 35px 30px;
            }
            
            .login-header {
                margin-bottom: 25px;
            }
        }
        
        @media (max-width: 1024px) {
            .login-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .branding-panel {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .company-name {
                font-size: 28px;
                margin: 40px 0 15px 0;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            .login-panel {
                padding: 40px 30px;
            }
            
            .login-form-wrapper {
                max-width: 100%;
            }
            
            .login-card {
                padding: 30px 25px;
            }
            
            .login-header {
                margin-bottom: 25px;
            }
        }
        
        @media (max-width: 768px) {
            .branding-panel, .login-panel {
                padding: 30px 20px;
            }
            
            .login-form-wrapper {
                padding: 30px 25px;
            }
            
            .login-card {
                padding: 25px 20px;
            }
            
            .login-header {
                margin-bottom: 20px;
            }
            
            .login-title {
                font-size: 28px;
            }
            
            .company-name {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .logo {
                font-size: 24px;
            }
            
            .login-title {
                font-size: 24px;
            }
            
            .features {
                display: none;
            }
            
            .login-form-wrapper {
                padding: 25px 20px;
            }
            
            .login-card {
                padding: 20px 15px;
            }
            
            .login-header {
                margin-bottom: 20px;
            }
            
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php session_start(); ?>
    <div class="login-container">
        <!-- Left Panel: Branding & Info -->
        <div class="branding-panel">
            <div class="logo-section">
                <div class="logo-icon">📋</div>
                <div class="logo-text">
                    <div class="logo">SSSMS</div>
                    <div class="tagline">Sales & Services Management</div>
                </div>
            </div>
            
            <h1 class="company-name">Srinivasa Sales and Service Private Limited</h1>
            <p class="company-description">
                Complete HR management solution for employee data, payroll, attendance, leave management, and more.
            </p>
            
            <ul class="features">
                <li>Employee Management</li>
                <li>Payroll Processing</li>
                <li>Leave Tracking</li>
                <li>Performance Reviews</li>
            </ul>
        </div>
        
        <!-- Right Panel: Login Form -->
        <div class="login-panel">
            <div class="login-form-wrapper">
                <div class="login-card">
                    <div class="login-header">
                        <h2 class="login-title">Welcome back</h2>
                        <p class="login-subtitle">Enter your credentials to access your account</p>
                    </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">✉</span>
                            <input type="email" name="username" class="input-field" 
                                   placeholder="name@company.com" required autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="password-label-wrapper">
                            <label class="form-label">Password</label>
                            <a href="#" class="forgot-password">Forgot password?</a>
                        </div>
                        <div class="password-wrapper">
                            <div class="input-wrapper">
                                <span class="input-icon">🔒</span>
                                <input type="password" name="password" id="password" class="input-field" 
                                       placeholder="Enter your password" required autocomplete="current-password">
                            </div>
                            <button type="button" class="toggle-password" onclick="togglePassword()">👁</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-button">Sign in</button>
                </form>
                
                <div class="demo-section">
                    <div class="demo-header">
                        <div class="demo-title">
                            <span>👥</span>
                            Demo Accounts
                        </div>
                        <span class="demo-badge">Dev Mode</span>
                    </div>
                    
                    <div class="demo-accounts">
                        <div class="demo-account" onclick="fillCredentials('admin@ssspl.com', 'demo@123')">
                            <div class="account-left">
                                <div class="account-color red"></div>
                                <div class="account-role">Admin</div>
                            </div>
                            <div class="account-email">admin@ssspl.com</div>
                        </div>
                        
                        <div class="demo-account" onclick="fillCredentials('hr@ssspl.com', 'demo@123')">
                            <div class="account-left">
                                <div class="account-color purple"></div>
                                <div class="account-role">HR</div>
                            </div>
                            <div class="account-email">hr@ssspl.com</div>
                        </div>
                        
                        <div class="demo-account" onclick="fillCredentials('manager@ssspl.com', 'demo@123')">
                            <div class="account-left">
                                <div class="account-color blue"></div>
                                <div class="account-role">Manager</div>
                            </div>
                            <div class="account-email">manager@ssspl.com</div>
                        </div>
                        
                        <div class="demo-account" onclick="fillCredentials('employee@ssspl.com', 'demo@123')">
                            <div class="account-left">
                                <div class="account-color green"></div>
                                <div class="account-role">Employee</div>
                            </div>
                            <div class="account-email">employee@ssspl.com</div>
                        </div>
                        
                        <div class="demo-account" onclick="fillCredentials('accounts@ssspl.com', 'demo@123')">
                            <div class="account-left">
                                <div class="account-color orange"></div>
                                <div class="account-role">Accounts</div>
                            </div>
                            <div class="account-email">accounts@ssspl.com</div>
                        </div>
                    </div>
                    
                    <p class="demo-note">
                        Click any account to auto-fill credentials
                    </p>
                </div>
                
                <div class="contact-section">
                    Don't have an account? <a href="#" class="contact-link">Contact HR</a>
                </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '👁️';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁';
            }
        }
        
        // Fill demo credentials
        function fillCredentials(email, password) {
            document.querySelector('input[name="username"]').value = email;
            document.querySelector('input[name="password"]').value = password;
            document.querySelector('form').submit();
        }
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form elements
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    group.style.transition = 'all 0.4s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animate demo accounts
            const demoAccounts = document.querySelectorAll('.demo-account');
            demoAccounts.forEach((account, index) => {
                account.style.opacity = '0';
                account.style.transform = 'translateX(-10px)';
                
                setTimeout(() => {
                    account.style.transition = 'all 0.3s ease';
                    account.style.opacity = '1';
                    account.style.transform = 'translateX(0)';
                }, 400 + (index * 60));
            });
        });
    </script>
</body>
</html>