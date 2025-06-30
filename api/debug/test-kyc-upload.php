<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user session exists
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No user session found. Run test-user-session.php first.',
        'session_data' => $_SESSION
    ]);
    exit;
}

// Test the KYC upload endpoint
echo json_encode([
    'success' => true,
    'message' => 'User session is active',
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'],
    'user_username' => $_SESSION['user_username'] ?? 'Unknown',
    'user_email' => $_SESSION['user_email'] ?? 'Unknown',
    'session_data' => $_SESSION,
    'next_step' => 'Now try uploading a file through the KYC page'
]);
?>
