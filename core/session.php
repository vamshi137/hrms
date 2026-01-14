<?php
session_start();

class Session {
    
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    public static function destroy() {
        session_destroy();
        $_SESSION = array();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function getUserRole() {
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }
    
    public static function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    public static function checkRole($requiredRole) {
        $userRole = self::getUserRole();
        return $userRole === $requiredRole;
    }
    
    public static function hasPermission($requiredRole) {
        $userRole = self::getUserRole();
        $hierarchy = [
            'super_admin' => 5,
            'admin' => 4,
            'hr' => 3,
            'manager' => 2,
            'employee' => 1,
            'accounts' => 2
        ];
        
        if (!isset($hierarchy[$userRole]) || !isset($hierarchy[$requiredRole])) {
            return false;
        }
        
        return $hierarchy[$userRole] >= $hierarchy[$requiredRole];
    }
}
?>