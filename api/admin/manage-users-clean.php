<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

function sendResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    sendResponse(null, $message, false, $code);
}

function hasPermission($adminRole, $requiredRole) {
    $roleHierarchy = [
        'super_admin' => 3,
        'admin' => 2,
        'chat_support' => 1
    ];
    
    return ($roleHierarchy[$adminRole] ?? 0) >= ($roleHierarchy[$requiredRole] ?? 0);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $adminId = $_GET['admin_id'] ?? '';
        
        if (!$adminId) {
            sendErrorResponse('Admin ID is required');
        }
        
        // Verify admin permissions
        $adminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute([$adminId]);
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin || !hasPermission($admin['role'], 'admin')) {
            sendErrorResponse('Insufficient permissions', 403);
        }
        
        // Get users
        $query = "SELECT id, username, email, is_active, created_at, updated_at FROM users ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $statsQuery = "SELECT 
                      COUNT(*) as total_users,
                      SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
                      SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_users
                      FROM users";
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse([
            'users' => $users,
            'statistics' => [
                'total' => intval($stats['total_users']),
                'active' => intval($stats['active_users']),
                'inactive' => intval($stats['inactive_users'])
            ]
        ], 'Users retrieved successfully');
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendErrorResponse('Invalid JSON input');
        }
        
        $action = $input['action'] ?? '';
        $adminId = $input['admin_id'] ?? '';
        
        // Verify admin permissions
        if (!$adminId) {
            sendErrorResponse('Admin ID is required');
        }
        
        $adminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute([$adminId]);
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin || !hasPermission($admin['role'], 'admin')) {
            sendErrorResponse('Insufficient permissions', 403);
        }
        
        if ($action === 'create') {
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                sendErrorResponse('Username, email, and password are required');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendErrorResponse('Invalid email address');
            }
            
            // Check if username already exists
            $checkQuery = "SELECT id FROM users WHERE username = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$username]);
            
            if ($checkStmt->fetch()) {
                sendErrorResponse('Username already exists');
            }
            
            // Check if email already exists
            $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
            $checkEmailStmt = $db->prepare($checkEmailQuery);
            $checkEmailStmt->execute([$email]);
            
            if ($checkEmailStmt->fetch()) {
                sendErrorResponse('Email already exists');
            }
            
            // Create new user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO users (username, email, password_hash, is_active) VALUES (?, ?, ?, TRUE)";
            $insertStmt = $db->prepare($insertQuery);
            
            if ($insertStmt->execute([$username, $email, $passwordHash])) {
                sendResponse([
                    'user_created' => true,
                    'username' => $username,
                    'email' => $email
                ], 'User created successfully');
            } else {
                sendErrorResponse('Failed to create user');
            }
        } else {
            sendErrorResponse('Invalid action specified');
        }
        
    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("User management error: " . $e->getMessage());
    sendErrorResponse('Internal server error', 500);
}
?>
