<?php
// User Profile Update API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cors.php';

session_start();
setCorsHeaders();

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendErrorResponse('Invalid JSON input', 400);
    }

    $userId = $_SESSION['user_id'];
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $fullName = trim($input['fullName'] ?? '');
    $whatsappNumber = trim($input['whatsappNumber'] ?? '');
    $telegramUsername = trim($input['telegramUsername'] ?? '');
    $twitterHandle = trim($input['twitterHandle'] ?? '');
    $instagramHandle = trim($input['instagramHandle'] ?? '');
    $linkedinProfile = trim($input['linkedinProfile'] ?? '');

    // Validate required fields
    if (empty($username)) {
        sendErrorResponse('Username is required', 400);
    }

    if (empty($email)) {
        sendErrorResponse('Email is required', 400);
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if username is already taken by another user
    $checkUsernameQuery = "SELECT id FROM users WHERE username = ? AND id != ?";
    $checkUsernameStmt = $db->prepare($checkUsernameQuery);
    $checkUsernameStmt->execute([$username, $userId]);

    if ($checkUsernameStmt->fetch()) {
        sendErrorResponse('Username is already taken', 400);
    }

    // Check if email is already taken by another user
    $checkEmailQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $checkEmailStmt = $db->prepare($checkEmailQuery);
    $checkEmailStmt->execute([$email, $userId]);

    if ($checkEmailStmt->fetch()) {
        sendErrorResponse('Email is already taken', 400);
    }

    // Update user profile
    $updateQuery = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    full_name = ?,
                    whatsapp_number = ?,
                    telegram_username = ?,
                    twitter_handle = ?,
                    instagram_handle = ?,
                    linkedin_profile = ?,
                    updated_at = NOW()
                    WHERE id = ?";
    
    $updateStmt = $db->prepare($updateQuery);
    $success = $updateStmt->execute([
        $username,
        $email,
        $fullName,
        $whatsappNumber,
        $telegramUsername,
        $twitterHandle,
        $instagramHandle,
        $linkedinProfile,
        $userId
    ]);

    if (!$success) {
        throw new Exception('Failed to update profile in database');
    }

    // Get updated user data
    $getUserQuery = "SELECT id, username, email, full_name, whatsapp_number, telegram_username, 
                     twitter_handle, instagram_handle, linkedin_profile, created_at, updated_at 
                     FROM users WHERE id = ?";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute([$userId]);
    $updatedUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    if (!$updatedUser) {
        throw new Exception('Failed to retrieve updated user data');
    }

    // Update session data
    $_SESSION['user_username'] = $updatedUser['username'];
    $_SESSION['user_email'] = $updatedUser['email'];
    $_SESSION['user_full_name'] = $updatedUser['full_name'];

    sendSuccessResponse([
        'user' => $updatedUser
    ], 'Profile updated successfully');

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    sendErrorResponse('Failed to update profile: ' . $e->getMessage(), 500);
}
?>
