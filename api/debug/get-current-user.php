<?php
// Get current logged-in user data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get current user data
    $getUserQuery = "SELECT id, username, email, full_name, whatsapp_number, telegram_username, 
                     twitter_handle, instagram_handle, linkedin_profile, created_at, updated_at, role 
                     FROM users WHERE id = ?";
    $getUserStmt = $db->prepare($getUserQuery);
    $getUserStmt->execute([$_SESSION['user_id']]);
    $currentUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        sendErrorResponse('User not found', 404);
    }

    sendSuccessResponse([
        'user' => $currentUser
    ], 'Current user data retrieved successfully');

} catch (Exception $e) {
    error_log('Get current user error: ' . $e->getMessage());
    sendErrorResponse('Failed to get user data: ' . $e->getMessage(), 500);
}
?>
