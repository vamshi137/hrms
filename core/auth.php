<?php
require_once '../config/db.php';
require_once '../config/constants.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT u.*, r.role_name 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.id 
                     WHERE (u.username = :username OR u.email = :username) 
                     AND u.status = 'Active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(password_verify($password, $user['password_hash'])) {
                    // Update last login
                    $this->updateLastLogin($user['id']);
                    
                    // Set session variables
                    Session::set('user_id', $user['id']);
                    Session::set('role_name', $user['role_name']);
                    Session::set('org_id', $user['org_id']);
                    Session::set('branch_id', $user['branch_id']);
                    Session::set('employee_id', $user['employee_id']);
                    Session::set('full_name', $user['full_name']);
                    Session::set('username', $user['username']);
                    Session::set('role_id', $user['role_id']);
                    
                    return ['success' => true, 'user' => $user];
                }
            }
            return ['success' => false, 'message' => 'Invalid credentials'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    private function updateLastLogin($user_id) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
    }
    
    public function logout() {
        Session::destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return Session::has('user_id');
    }
    
    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            return [
                'id' => Session::get('user_id'),
                'role_name' => Session::get('role_name'),
                'org_id' => Session::get('org_id'),
                'branch_id' => Session::get('branch_id'),
                'employee_id' => Session::get('employee_id'),
                'full_name' => Session::get('full_name'),
                'username' => Session::get('username'),
                'role_id' => Session::get('role_id')
            ];
        }
        return null;
    }
    
    public function checkPermission($required_role) {
        $user_role = Session::get('role_name');
        $role_hierarchy = [
            ROLE_SUPER_ADMIN => 5,
            ROLE_ADMIN => 4,
            ROLE_HR => 3,
            ROLE_MANAGER => 2,
            ROLE_EMPLOYEE => 1
        ];
        
        return isset($role_hierarchy[$user_role]) && 
               $role_hierarchy[$user_role] >= $role_hierarchy[$required_role];
    }
}
?>