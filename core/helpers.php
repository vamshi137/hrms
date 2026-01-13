<?php
function redirect($url) {
    header("Location: $url");
    exit();
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
}

function generateEmployeeCode($prefix = 'EMP', $length = 5) {
    $random = strtoupper(substr(uniqid(), -$length));
    return $prefix . date('Y') . $random;
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function calculateAge($dob) {
    $birthdate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthdate->diff($today)->y;
}

function getLeaveDays($from_date, $to_date, $exclude_weekends = true, $exclude_holidays = []) {
    $start = new DateTime($from_date);
    $end = new DateTime($to_date);
    $end->modify('+1 day');
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $days = 0;
    foreach($period as $date) {
        if($exclude_weekends && in_array($date->format('N'), [6, 7])) {
            continue;
        }
        
        $date_str = $date->format('Y-m-d');
        if($exclude_holidays && in_array($date_str, $exclude_holidays)) {
            continue;
        }
        
        $days++;
    }
    
    return $days;
}

function getStatusBadge($status) {
    $badges = [
        'Active' => 'success',
        'Inactive' => 'secondary',
        'Pending' => 'warning',
        'Approved' => 'success',
        'Rejected' => 'danger',
        'Open' => 'warning',
        'Closed' => 'success',
        'Completed' => 'success'
    ];
    
    $color = $badges[$status] ?? 'secondary';
    return "<span class='badge badge-$color'>$status</span>";
}

function logActivity($user_id, $action, $module, $details = '') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, module, details, ip_address, user_agent) 
              VALUES (:user_id, :action, :module, :details, :ip, :ua)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':module', $module);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':ua', $_SERVER['HTTP_USER_AGENT']);
    
    return $stmt->execute();
}
?>