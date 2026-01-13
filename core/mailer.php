<?php
require_once '../config/config.php';

class Mailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_user = SMTP_USER;
        $this->smtp_pass = SMTP_PASS;
        $this->from_email = SMTP_FROM;
        $this->from_name = SMTP_FROM_NAME;
    }
    
    public function send($to, $subject, $body, $is_html = true) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: " . $this->from_name . " <" . $this->from_email . ">\r\n";
        
        if($is_html) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        // For production, use PHPMailer or similar library
        // This is a simple implementation
        return mail($to, $subject, $body, $headers);
    }
    
    public function sendWelcomeEmail($to, $name, $username, $password) {
        $subject = "Welcome to HRMS System";
        $body = "
        <html>
        <body>
            <h2>Welcome to HRMS System</h2>
            <p>Dear $name,</p>
            <p>Your account has been created successfully.</p>
            <p><strong>Login Details:</strong></p>
            <ul>
                <li>Username: $username</li>
                <li>Password: $password</li>
            </ul>
            <p>Please login at: " . APP_URL . "auth/login.php</p>
            <p>Kindly change your password after first login.</p>
            <br>
            <p>Best Regards,<br>HR Team</p>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $body);
    }
    
    public function sendPasswordReset($to, $token) {
        $subject = "Password Reset Request";
        $reset_link = APP_URL . "auth/reset_password.php?token=" . $token;
        
        $body = "
        <html>
        <body>
            <h2>Password Reset Request</h2>
            <p>You have requested to reset your password.</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='$reset_link'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <br>
            <p>Best Regards,<br>HRMS Team</p>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $body);
    }
}
?>