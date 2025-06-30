<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once 'config/database.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not authenticated', 'session' => $_SESSION]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $userId = $_SESSION['user_id'];

    // Check user in database
    $userQuery = "SELECT id, username, email, full_name FROM users WHERE id = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // Check if profile exists
    $profileQuery = "SELECT * FROM user_profiles WHERE user_id = ?";
    $profileStmt = $db->prepare($profileQuery);
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

    // Test database connection
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'user_id' => $userId,
        'user_id_type' => gettype($userId),
        'user_id_length' => strlen($userId),
        'user_data' => $user,
        'profile_exists' => $profile ? true : false,
        'profile_data' => $profile,
        'session_data' => $_SESSION,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'session_data' => $_SESSION
    ]);
}
?>
