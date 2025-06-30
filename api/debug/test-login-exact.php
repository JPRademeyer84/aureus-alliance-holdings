<?php
// Test the exact login process that frontend uses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Test the exact same login process as the auth.php
    $email = 'jp.rademeyer84@gmail.com';
    $password = 'password123';

    // Get user exactly like auth.php does
    $query = "SELECT id, username, email, password_hash, created_at FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendErrorResponse('User not found', 404);
    }

    // Test password verification
    $passwordVerified = password_verify($password, $user['password_hash']);
    
    if (!$passwordVerified) {
        sendErrorResponse('Password verification failed. Hash: ' . substr($user['password_hash'], 0, 30) . '...', 401);
    }

    // Set session variables exactly like auth.php does
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_username'] = $user['username'];

    // Remove password hash from response
    unset($user['password_hash']);

    // Test if we can immediately check session
    $sessionCheck = [
        'session_id' => session_id(),
        'user_id_set' => isset($_SESSION['user_id']),
        'session_user_id' => $_SESSION['user_id'] ?? null,
        'session_email' => $_SESSION['user_email'] ?? null,
        'session_username' => $_SESSION['user_username'] ?? null
    ];

    sendSuccessResponse([
        'login_test' => 'SUCCESS',
        'user' => $user,
        'password_verified' => $passwordVerified,
        'session_check' => $sessionCheck,
        'message' => 'Login should work with these credentials'
    ], 'Login test successful');

} catch (Exception $e) {
    error_log('Login test error: ' . $e->getMessage());
    sendErrorResponse('Login test failed: ' . $e->getMessage(), 500);
}
?>
