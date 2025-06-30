<?php
// Simple test to check what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Basic PHP test working',
        'data' => [
            'php_version' => phpversion(),
            'timestamp' => date('Y-m-d H:i:s'),
            'get_params' => $_GET,
            'server_info' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI']
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
