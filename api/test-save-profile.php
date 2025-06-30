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
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    // Create tables silently
    ob_start();
    $database->createTables();
    ob_end_clean();
    
    $userId = $_SESSION['user_id'];
    
    // Test data to save
    $testData = [
        'full_name' => 'Test User',
        'phone' => '+1234567890',
        'country' => 'Test Country',
        'city' => 'Test City',
        'bio' => 'This is a test bio'
    ];
    
    // Check if profile exists
    $checkQuery = "SELECT id FROM user_profiles WHERE user_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$userId]);
    $exists = $checkStmt->fetch();
    
    $profileData = [
        'phone' => $testData['phone'],
        'country' => $testData['country'],
        'city' => $testData['city'],
        'bio' => $testData['bio'],
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($exists) {
        // Update existing profile
        $updateFields = [];
        $updateValues = [];
        
        foreach ($profileData as $field => $value) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $value;
        }
        
        $updateValues[] = $userId;
        
        $updateQuery = "UPDATE user_profiles SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute($updateValues);
        
        $action = 'updated';
    } else {
        // Create new profile
        $profileData['user_id'] = $userId;
        $profileData['created_at'] = date('Y-m-d H:i:s');
        
        $fields = implode(', ', array_keys($profileData));
        $placeholders = implode(', ', array_fill(0, count($profileData), '?'));
        
        $insertQuery = "INSERT INTO user_profiles ($fields) VALUES ($placeholders)";
        $stmt = $db->prepare($insertQuery);
        $success = $stmt->execute(array_values($profileData));
        
        $action = 'created';
    }
    
    // Also update user's full_name
    $userUpdateQuery = "UPDATE users SET full_name = ? WHERE id = ?";
    $userStmt = $db->prepare($userUpdateQuery);
    $userUpdateSuccess = $userStmt->execute([$testData['full_name'], $userId]);
    
    echo json_encode([
        'success' => $success,
        'action' => $action,
        'profile_exists_before' => $exists ? true : false,
        'user_update_success' => $userUpdateSuccess,
        'user_id' => $userId,
        'test_data' => $testData,
        'profile_data' => $profileData,
        'query' => $exists ? $updateQuery : $insertQuery,
        'values' => $exists ? $updateValues : array_values($profileData),
        'error' => $success ? null : $stmt->errorInfo(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage(),
        'user_id' => $_SESSION['user_id'] ?? 'not set'
    ]);
}
?>
