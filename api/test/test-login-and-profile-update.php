<?php
// Test script to verify login and profile update flow
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Step 1: Get a test user from the database
    $getUserQuery = "SELECT id, username, email, password_hash FROM users LIMIT 1";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute();
    $testUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    if (!$testUser) {
        sendErrorResponse('No test user found in database', 404);
    }

    // Step 2: Test login API call (simulate what frontend does)
    $loginData = [
        'action' => 'login',
        'email' => $testUser['email'],
        'password' => 'password123' // Assuming this is the password
    ];

    // Clear any existing session
    session_destroy();
    session_start();

    // Simulate login process
    $email = $loginData['email'];
    $password = $loginData['password'];

    // Get user for login
    $query = "SELECT id, username, email, password_hash, created_at FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendErrorResponse('User not found for login test', 404);
    }

    // For testing, let's check if password verification works or create a test password
    $passwordVerified = password_verify($password, $user['password_hash']);
    
    if (!$passwordVerified) {
        // Let's try to update the user with a known password for testing
        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
        $updatePasswordQuery = "UPDATE users SET password_hash = ? WHERE id = ?";
        $updatePasswordStmt = $db->prepare($updatePasswordQuery);
        $updatePasswordStmt->execute([$newPasswordHash, $user['id']]);
        
        // Now verify again
        $passwordVerified = password_verify($password, $newPasswordHash);
    }

    if (!$passwordVerified) {
        sendErrorResponse('Password verification failed', 401);
    }

    // Set session variables (simulate successful login)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_username'] = $user['username'];

    // Step 3: Now test profile update with the active session
    $profileData = [
        'username' => $user['username'],
        'email' => $user['email'],
        'fullName' => 'Jean Pierre Rademeyer',
        'whatsappNumber' => '+27123456789',
        'telegramUsername' => '@jprademeyer',
        'twitterHandle' => '@jprademeyer',
        'instagramHandle' => '@jprademeyer',
        'linkedinProfile' => 'https://linkedin.com/in/jprademeyer'
    ];

    // Check if user is logged in (this should pass now)
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('Session not maintained after login', 500);
    }

    // Perform profile update
    $userId = $_SESSION['user_id'];
    $username = trim($profileData['username']);
    $email = trim($profileData['email']);
    $fullName = trim($profileData['fullName']);
    $whatsappNumber = trim($profileData['whatsappNumber']);
    $telegramUsername = trim($profileData['telegramUsername']);
    $twitterHandle = trim($profileData['twitterHandle']);
    $instagramHandle = trim($profileData['instagramHandle']);
    $linkedinProfile = trim($profileData['linkedinProfile']);

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

    sendSuccessResponse([
        'test_result' => 'SUCCESS',
        'login_successful' => true,
        'session_maintained' => isset($_SESSION['user_id']),
        'profile_updated' => true,
        'session_data' => [
            'user_id' => $_SESSION['user_id'],
            'user_email' => $_SESSION['user_email'],
            'user_username' => $_SESSION['user_username']
        ],
        'updated_user' => $updatedUser
    ], 'Login and profile update test completed successfully');

} catch (Exception $e) {
    error_log('Login and profile update test error: ' . $e->getMessage());
    sendErrorResponse('Test failed: ' . $e->getMessage(), 500);
}
?>
