<?php
require_once '../config/db.php';
require_once '../core/mailer.php';
require_once '../core/csrf.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HRMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        .reset-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-card text-center">
        <div class="reset-icon">
            <i class="fas fa-key"></i>
        </div>
        <h3>Reset Password</h3>
        <p class="text-muted mb-4">Enter your email to receive reset instructions</p>
        
        <?php
        if(isset($_GET['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        if(isset($_GET['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
        }
        ?>
        
        <form action="reset_password_process.php" method="POST">
            <?php echo CSRF::getTokenField(); ?>
            <div class="form-group">
                <input type="email" class="form-control" name="email" 
                       placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
            </button>
        </form>
        
        <div class="mt-4">
            <a href="login.php" class="text-decoration-none">
                <i class="fas fa-arrow-left mr-2"></i>Back to Login
            </a>
        </div>
    </div>
</body>
</html>