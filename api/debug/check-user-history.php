<?php
// Check user account details to help restore access
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get your user account details
    $email = 'jp.rademeyer84@gmail.com';
    
    $getUserQuery = "SELECT id, username, email, password_hash, created_at, updated_at FROM users WHERE email = ?";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute([$email]);
    $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Let's try common passwords that might have been your original
        $commonPasswords = [
            'password',
            'Password123',
            'password123',
            'admin',
            'Admin123',
            '123456',
            'aureus123',
            'Aureus123'
        ];
        
        $passwordFound = false;
        $workingPassword = '';
        
        foreach ($commonPasswords as $testPassword) {
            if (password_verify($testPassword, $user['password_hash'])) {
                $passwordFound = true;
                $workingPassword = $testPassword;
                break;
            }
        }
        
        sendSuccessResponse([
            'user_found' => true,
            'user_info' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at']
            ],
            'password_found' => $passwordFound,
            'working_password' => $workingPassword,
            'current_hash' => substr($user['password_hash'], 0, 20) . '...',
            'message' => $passwordFound ? 
                "Found working password: $workingPassword" : 
                "Could not determine original password. Hash was modified."
        ], 'User account analysis complete');
    } else {
        sendErrorResponse('User not found', 404);
    }

} catch (Exception $e) {
    error_log('User check error: ' . $e->getMessage());
    sendErrorResponse('Check failed: ' . $e->getMessage(), 500);
}
?>
