<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Session {
    
    /**
     * Initialize session
     */
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Set session variable
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
    }
    
    /**
     * Get user role slug
     */
    public static function getUserRole() {
        return self::get('role', null);
    }
    
    /**
     * Get user ID
     */
    public static function getUserId() {
        return self::get('user_id', null);
    }
    
    /**
     * Get employee ID
     */
    public static function getEmployeeId() {
        return self::get('employee_id', null);
    }
    
    /**
     * Check if user has specific role
     */
    public static function checkRole($requiredRole) {
        $userRole = self::getUserRole();
        return $userRole === $requiredRole;
    }
    
    /**
     * Check if user has permission (role hierarchy)
     */
    public static function hasPermission($requiredRole) {
        $userRole = self::getUserRole();
        $hierarchy = [
            'super_admin' => 5,
            'admin' => 4,
            'hr' => 3,
            'manager' => 2,
            'employee' => 1
        ];
        
        if (!isset($hierarchy[$userRole]) || !isset($hierarchy[$requiredRole])) {
            return false;
        }
        
        return $hierarchy[$userRole] >= $hierarchy[$requiredRole];
    }
    
    /**
     * Destroy session and logout
     */
    public static function destroy() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
?>