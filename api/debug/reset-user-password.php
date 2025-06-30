<?php
// Emergency password reset for user access
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get your user account
    $email = 'jp.rademeyer84@gmail.com'; // Your email
    $newPassword = 'password123'; // Temporary password
    
    // Hash the new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update your password
    $updateQuery = "UPDATE users SET password_hash = ? WHERE email = ?";
    $updateStmt = $db->prepare($updateQuery);
    $success = $updateStmt->execute([$passwordHash, $email]);
    
    if ($success) {
        // Get user info
        $getUserQuery = "SELECT id, username, email, created_at FROM users WHERE email = ?";
        $getUserStmt = $db->prepare($getUserQuery);
        $getUserStmt->execute([$email]);
        $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        
        sendSuccessResponse([
            'password_reset' => true,
            'email' => $email,
            'new_password' => $newPassword,
            'user_info' => $user,
            'instructions' => 'Use email: ' . $email . ' and password: ' . $newPassword . ' to login'
        ], 'Password reset successfully');
    } else {
        sendErrorResponse('Failed to reset password', 500);
    }

} catch (Exception $e) {
    error_log('Password reset error: ' . $e->getMessage());
    sendErrorResponse('Reset failed: ' . $e->getMessage(), 500);
}
?>
