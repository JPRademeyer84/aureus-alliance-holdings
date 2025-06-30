<?php
// Test profile save functionality
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get your user before update
    $email = 'jp.rademeyer84@gmail.com';
    $getUserQuery = "SELECT * FROM users WHERE email = ?";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute([$email]);
    $userBefore = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userBefore) {
        sendErrorResponse('User not found', 404);
    }

    // Simulate login
    $_SESSION['user_id'] = $userBefore['id'];
    $_SESSION['user_email'] = $userBefore['email'];
    $_SESSION['user_username'] = $userBefore['username'];

    // Test profile update
    $testData = [
        'username' => $userBefore['username'],
        'email' => $userBefore['email'],
        'fullName' => 'Jean Pierre Rademeyer UPDATED',
        'whatsappNumber' => '+27123456789',
        'telegramUsername' => '@jprademeyer',
        'twitterHandle' => '@jprademeyer',
        'instagramHandle' => '@jprademeyer',
        'linkedinProfile' => 'https://linkedin.com/in/jprademeyer'
    ];

    $userId = $_SESSION['user_id'];
    $username = trim($testData['username']);
    $email = trim($testData['email']);
    $fullName = trim($testData['fullName']);
    $whatsappNumber = trim($testData['whatsappNumber']);
    $telegramUsername = trim($testData['telegramUsername']);
    $twitterHandle = trim($testData['twitterHandle']);
    $instagramHandle = trim($testData['instagramHandle']);
    $linkedinProfile = trim($testData['linkedinProfile']);

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
        $errorInfo = $updateStmt->errorInfo();
        sendErrorResponse('Database update failed: ' . json_encode($errorInfo), 500);
    }

    // Get updated user data
    $getUserQuery = "SELECT * FROM users WHERE id = ?";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute([$userId]);
    $userAfter = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    sendSuccessResponse([
        'test_result' => 'SUCCESS',
        'update_success' => $success,
        'rows_affected' => $updateStmt->rowCount(),
        'user_before' => $userBefore,
        'user_after' => $userAfter,
        'data_sent' => $testData
    ], 'Profile update test completed');

} catch (Exception $e) {
    error_log('Profile update test error: ' . $e->getMessage());
    sendErrorResponse('Test failed: ' . $e->getMessage(), 500);
}
?>
