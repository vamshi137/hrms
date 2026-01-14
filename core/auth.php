<?php
require_once __DIR__ . '/session.php';

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT u.*, e.employee_id, e.full_name, e.profile_photo 
                     FROM users u 
                     LEFT JOIN employees e ON u.employee_id = e.id 
                     WHERE (u.username = :username OR u.email = :username) 
                     AND u.is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Set session variables
                Session::set('user_id', $user['id']);
                Session::set('employee_id', $user['employee_id']);
                Session::set('username', $user['username']);
                Session::set('email', $user['email']);
                Session::set('role', $user['role']);
                Session::set('full_name', $user['full_name']);
                Session::set('profile_photo', $user['profile_photo']);
                Session::set('logged_in', true);
                
                return [
                    'success' => true,
                    'role' => $user['role'],
                    'message' => 'Login successful'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
            
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    public function logout() {
        Session::destroy();
        return true;
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $query = "SELECT password_hash FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Update to new password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':hash', $newHash);
            $updateStmt->bindParam(':id', $userId);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
            
        } catch(PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
}
?>