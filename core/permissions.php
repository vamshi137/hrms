<?php
class Permissions {
    private static $permissions = [
        ROLE_SUPER_ADMIN => [
            'view_dashboard',
            'manage_organizations',
            'manage_branches',
            'create_admin_users',
            'view_all_reports',
            'system_settings',
            'backup_restore',
            'audit_logs'
        ],
        ROLE_ADMIN => [
            'view_dashboard',
            'manage_departments',
            'manage_designations',
            'manage_shifts',
            'manage_holidays',
            'manage_employees',
            'manage_salary',
            'process_payroll',
            'generate_payslips',
            'create_hr_users',
            'create_manager_users',
            'create_employee_users',
            'view_reports'
        ],
        ROLE_HR => [
            'view_dashboard',
            'manage_employee_profiles',
            'upload_documents',
            'monitor_attendance',
            'approve_leaves',
            'manage_recruitment',
            'manage_onboarding',
            'manage_training',
            'manage_assets',
            'manage_separation',
            'view_hr_reports'
        ],
        ROLE_MANAGER => [
            'view_dashboard',
            'view_team_attendance',
            'approve_team_leaves',
            'approve_attendance_corrections',
            'performance_reviews',
            'recommend_promotions',
            'view_team_reports'
        ],
        ROLE_EMPLOYEE => [
            'view_dashboard',
            'view_profile',
            'update_personal_details',
            'apply_leave',
            'view_attendance',
            'download_payslips',
            'submit_expenses',
            'resignation_request'
        ]
    ];
    
    public static function hasPermission($role, $permission) {
        return isset(self::$permissions[$role]) && 
               in_array($permission, self::$permissions[$role]);
    }
    
    public static function getAllPermissions($role) {
        return self::$permissions[$role] ?? [];
    }
}
?>