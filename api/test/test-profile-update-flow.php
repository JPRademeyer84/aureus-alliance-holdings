<?php
// Test script to verify the complete profile update flow
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Step 1: Get a test user from the database
    $getUserQuery = "SELECT id, username, email FROM users LIMIT 1";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute();
    $testUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    if (!$testUser) {
        sendErrorResponse('No test user found in database', 404);
    }

    // Step 2: Simulate login by setting session variables
    $_SESSION['user_id'] = $testUser['id'];
    $_SESSION['user_email'] = $testUser['email'];
    $_SESSION['user_username'] = $testUser['username'];

    // Step 3: Test profile update with the logged-in session
    $profileData = [
        'username' => $testUser['username'],
        'email' => $testUser['email'],
        'fullName' => 'Test User Updated',
        'whatsappNumber' => '+1234567890',
        'telegramUsername' => '@testuser',
        'twitterHandle' => '@testuser',
        'instagramHandle' => '@testuser',
        'linkedinProfile' => 'https://linkedin.com/in/testuser'
    ];

    // Simulate the profile update API call internally
    $userId = $_SESSION['user_id'];
    $username = trim($profileData['username']);
    $email = trim($profileData['email']);
    $fullName = trim($profileData['fullName']);
    $whatsappNumber = trim($profileData['whatsappNumber']);
    $telegramUsername = trim($profileData['telegramUsername']);
    $twitterHandle = trim($profileData['twitterHandle']);
    $instagramHandle = trim($profileData['instagramHandle']);
    $linkedinProfile = trim($profileData['linkedinProfile']);

    // Validate required fields
    if (empty($username) || empty($email)) {
        sendErrorResponse('Username and email are required', 400);
    }

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

    sendSuccessResponse([
        'test_result' => 'SUCCESS',
        'original_user' => $testUser,
        'updated_user' => $updatedUser,
        'session_data' => [
            'user_id' => $_SESSION['user_id'],
            'user_email' => $_SESSION['user_email'],
            'user_username' => $_SESSION['user_username']
        ]
    ], 'Profile update test completed successfully');

} catch (Exception $e) {
    error_log('Profile update test error: ' . $e->getMessage());
    sendErrorResponse('Test failed: ' . $e->getMessage(), 500);
}
?>
