<?php
// Set default timezone for India
if(!date_default_timezone_get()) {
    date_default_timezone_set('Asia/Kolkata');
}

function formatDate($date, $format = 'd-m-Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd-m-Y H:i:s') {
    return date($format, strtotime($datetime));
}

function getCurrentDate($format = 'Y-m-d') {
    return date($format);
}

function getCurrentDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}
?>