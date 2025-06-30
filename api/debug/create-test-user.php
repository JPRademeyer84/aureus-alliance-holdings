<?php
require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create test user if not exists
    $testEmail = 'test@aureus.com';
    $testUsername = 'testuser';
    $testPassword = 'password123';
    
    // Check if test user exists
    $checkQuery = "SELECT id, username, email FROM users WHERE email = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$testEmail]);
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // User exists, log them in
        $_SESSION['user_id'] = $existingUser['id'];
        $_SESSION['user_email'] = $existingUser['email'];
        $_SESSION['user_username'] = $existingUser['username'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Test user already exists and logged in',
            'user' => $existingUser,
            'session_id' => session_id()
        ]);
    } else {
        // Create new test user
        $passwordHash = password_hash($testPassword, PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        
        if ($insertStmt->execute([$testUsername, $testEmail, $passwordHash, 'Test User'])) {
            $userId = $db->lastInsertId();
            
            // Get the created user
            $getUserQuery = "SELECT id, username, email, full_name FROM users WHERE id = ?";
            $getUserStmt = $db->prepare($getUserQuery);
            $getUserStmt->execute([$userId]);
            $newUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            
            // Log them in
            $_SESSION['user_id'] = $newUser['id'];
            $_SESSION['user_email'] = $newUser['email'];
            $_SESSION['user_username'] = $newUser['username'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Test user created and logged in',
                'user' => $newUser,
                'session_id' => session_id()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create test user'
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
