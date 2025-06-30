<?php
// Session Status Check API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    $sessionInfo = [
        'session_id' => session_id(),
        'is_authenticated' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_username' => $_SESSION['user_username'] ?? null,
        'session_data_count' => count($_SESSION),
        'cookies_received' => !empty($_COOKIE),
        'cookie_count' => count($_COOKIE),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];

    if (isset($_SESSION['user_id'])) {
        sendSuccessResponse($sessionInfo, 'User is authenticated');
    } else {
        sendSuccessResponse($sessionInfo, 'User is not authenticated');
    }

} catch (Exception $e) {
    error_log("Session status error: " . $e->getMessage());
    sendErrorResponse('Session error: ' . $e->getMessage(), 500);
}
?>
