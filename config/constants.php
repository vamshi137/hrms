<?php
// User Roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_HR', 'hr');
define('ROLE_MANAGER', 'manager');
define('ROLE_EMPLOYEE', 'employee');
define('ROLE_ACCOUNTS', 'accounts');

// Employee Status
define('STATUS_ACTIVE', 'Active');
define('STATUS_INACTIVE', 'Inactive');
define('STATUS_TERMINATED', 'Terminated');
define('STATUS_RESIGNED', 'Resigned');

// Employment Types
define('EMP_PERMANENT', 'Permanent');
define('EMP_CONTRACT', 'Contract');
define('EMP_CONSULTANT', 'Consultant');
define('EMP_FIXED', 'Fixed');
define('EMP_PROJECT', 'Project');
define('EMP_GOVT', 'Govt');

// Gender
define('GENDER_MALE', 'Male');
define('GENDER_FEMALE', 'Female');
define('GENDER_OTHER', 'Other');

// Marital Status
define('MARITAL_SINGLE', 'Single');
define('MARITAL_MARRIED', 'Married');
define('MARITAL_DIVORCED', 'Divorced');
define('MARITAL_WIDOWED', 'Widowed');

// KYC Status
define('KYC_COMPLETE', 'Complete');
define('KYC_PENDING', 'Pending');
define('KYC_EXPIRED', 'Expired');

// Leave Types
define('LEAVE_CL', 'Casual Leave');
define('LEAVE_SL', 'Sick Leave');
define('LEAVE_EL', 'Earned Leave');
define('LEAVE_ML', 'Maternity Leave');
define('LEAVE_PL', 'Paternity Leave');

// Asset Types
define('ASSET_LAPTOP', 'Laptop');
define('ASSET_PC', 'PC');
define('ASSET_MOBILE', 'Mobile');
define('ASSET_SIM', 'SIM Card');
define('ASSET_ID', 'ID Card');
define('ASSET_TOOLS', 'Tools');

// Response Codes
define('RESPONSE_SUCCESS', 200);
define('RESPONSE_CREATED', 201);
define('RESPONSE_BAD_REQUEST', 400);
define('RESPONSE_UNAUTHORIZED', 401);
define('RESPONSE_FORBIDDEN', 403);
define('RESPONSE_NOT_FOUND', 404);
define('RESPONSE_SERVER_ERROR', 500);
?>