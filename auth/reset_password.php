<?php
require_once '../config/db.php';
require_once '../core/validator.php';

// Check if token is valid
$token = $_GET['token'] ?? '';

if(empty($token)) {
    header('Location: forgot_password.php?error=Invalid reset link');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Check if token exists and is not expired
$query = "SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()";
$stmt = $conn->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();

if($stmt->rowCount() === 0) {
    header('Location: forgot_password.php?error=Invalid or expired reset link');
    exit();
}

$reset_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - HRMS</title>
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
    </style>
</head>
<body>
    <div class="reset-card">
        <h3 class="text-center mb-4">Set New Password</h3>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <form action="reset_password_action.php" method="POST" id="resetForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="new_password" 
                           name="new_password" required minlength="8">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <small class="form-text text-muted">Minimum 8 characters with letters and numbers</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div id="passwordMatch" class="mt-1"></div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-save mr-2"></i>Reset Password
            </button>
        </form>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#toggleNewPass').click(function() {
                const input = $('#new_password');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });
            
            $('#toggleConfirmPass').click(function() {
                const input = $('#confirm_password');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });
            
            // Password match validation
            $('#confirm_password').on('keyup', function() {
                const newPass = $('#new_password').val();
                const confirmPass = $(this).val();
                const matchDiv = $('#passwordMatch');
                
                if(confirmPass === '') {
                    matchDiv.html('');
                } else if(newPass === confirmPass) {
                    matchDiv.html('<small class="text-success"><i class="fas fa-check"></i> Passwords match</small>');
                } else {
                    matchDiv.html('<small class="text-danger"><i class="fas fa-times"></i> Passwords do not match</small>');
                }
            });
            
            // Form validation
            $('#resetForm').submit(function(e) {
                const newPass = $('#new_password').val();
                const confirmPass = $('#confirm_password').val();
                
                if(newPass.length < 8) {
                    alert('Password must be at least 8 characters long');
                    e.preventDefault();
                    return false;
                }
                
                if(!/(?=.*[a-zA-Z])(?=.*\d)/.test(newPass)) {
                    alert('Password must contain both letters and numbers');
                    e.preventDefault();
                    return false;
                }
                
                if(newPass !== confirmPass) {
                    alert('Passwords do not match');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>