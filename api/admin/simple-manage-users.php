<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

function sendResponse($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_PRETTY_PRINT);
    exit;
}

function sendError($message, $code = 400) {
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            // Get all users
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
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Invalid JSON input');
        }

        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $username = $input['username'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';

            if (empty($username) || empty($email) || empty($password)) {
                sendError('Username, email, and password are required');
            }

            // Check if username already exists
            $checkUsernameQuery = "SELECT id FROM users WHERE username = ?";
            $checkUsernameStmt = $db->prepare($checkUsernameQuery);
            $checkUsernameStmt->execute([$username]);

            if ($checkUsernameStmt->fetch()) {
                sendError('Username already exists');
            }

            // Check if email already exists
            $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
            $checkEmailStmt = $db->prepare($checkEmailQuery);
            $checkEmailStmt->execute([$email]);

            if ($checkEmailStmt->fetch()) {
                sendError('Email already exists');
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
                sendError('Failed to create user');
            }

        } elseif ($action === 'update') {
            $userId = $input['user_id'] ?? '';
            $updates = $input['updates'] ?? [];

            if (empty($userId)) {
                sendError('User ID is required');
            }

            $allowedFields = ['username', 'email', 'is_active'];
            $updateFields = [];
            $updateValues = [];

            foreach ($updates as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $value;
                }
            }

            if (empty($updateFields)) {
                sendError('No valid fields to update');
            }

            $updateValues[] = $userId;
            $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);

            if ($updateStmt->execute($updateValues)) {
                sendResponse(['updated' => true], 'User updated successfully');
            } else {
                sendError('Failed to update user');
            }

        } elseif ($action === 'delete') {
            $userId = $input['user_id'] ?? '';

            if (empty($userId)) {
                sendError('User ID is required');
            }

            $deleteQuery = "UPDATE users SET is_active = FALSE WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);

            if ($deleteStmt->execute([$userId])) {
                sendResponse(['deleted' => true], 'User deactivated successfully');
            } else {
                sendError('Failed to deactivate user');
            }

        } else {
            sendError('Invalid action');
        }
        
    } else {
        sendError('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Simple manage users error: " . $e->getMessage());
    sendError('Internal server error: ' . $e->getMessage(), 500);
}
?>
