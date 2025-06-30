<?php
// Debug API to check session status
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
    $sessionData = [
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'is_authenticated' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user_username'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'cookies' => $_COOKIE
    ];

    sendSuccessResponse($sessionData, 'Session data retrieved successfully');

} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    sendErrorResponse('Session error: ' . $e->getMessage(), 500);
}
?>
