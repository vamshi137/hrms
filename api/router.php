<?php
/**
 * API Router
 */

// Set content type to JSON
header('Content-Type: application/json');

// Get requested endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove base path
$endpoint = str_replace(dirname($script_name), '', $request_uri);
$endpoint = trim($endpoint, '/');
$endpoint_parts = explode('/', $endpoint);

// Remove 'api' prefix if present
if ($endpoint_parts[0] === 'api') {
    array_shift($endpoint_parts);
}

$api_endpoint = implode('/', $endpoint_parts);
$method = $_SERVER['REQUEST_METHOD'];

// API routing
$api_response = [
    'status' => 'error',
    'message' => 'Invalid API endpoint',
    'data' => null
];

// Check if API file exists
$api_file = __DIR__ . '/' . $endpoint_parts[0] . '_api.php';
if (file_exists($api_file)) {
    require_once $api_file;
} else {
    // Send JSON response
    echo json_encode($api_response, JSON_PRETTY_PRINT);
}
?>