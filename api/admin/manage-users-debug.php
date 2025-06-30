<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

header('Content-Type: application/json');

function sendResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'query_params' => $_GET,
            'php_version' => phpversion()
        ]
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400, $debug = null) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => null,
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'query_params' => $_GET,
            'debug_details' => $debug,
            'php_version' => phpversion()
        ]
    ]);
    exit();
}

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendErrorResponse('Database connection failed', 500);
    }
    
    // Test table creation
    $database->createTables();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $adminId = $_GET['admin_id'] ?? '';
        
        if (!$adminId) {
            sendErrorResponse('Admin ID is required', 400);
        }
        
        // Verify admin exists and has permissions
        $adminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute([$adminId]);
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            sendErrorResponse('Admin not found or inactive', 403, ['admin_id' => $adminId]);
        }
        
        // Check if admin has permission (admin or super_admin)
        if (!in_array($admin['role'], ['admin', 'super_admin'])) {
            sendErrorResponse('Insufficient permissions', 403, ['admin_role' => $admin['role']]);
        }
        
        // Test if users table exists
        $tableCheckQuery = "SHOW TABLES LIKE 'users'";
        $tableCheckStmt = $db->prepare($tableCheckQuery);
        $tableCheckStmt->execute();
        $tableExists = $tableCheckStmt->fetch() !== false;
        
        if (!$tableExists) {
            sendErrorResponse('Users table does not exist', 500);
        }
        
        // Get users with error handling
        try {
            $query = "SELECT id, username, email, is_active, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT 50";
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
                ],
                'table_exists' => $tableExists,
                'admin_role' => $admin['role']
            ], 'Users retrieved successfully');
            
        } catch (PDOException $e) {
            sendErrorResponse('Database query error: ' . $e->getMessage(), 500, [
                'sql_error' => $e->getMessage(),
                'sql_code' => $e->getCode()
            ]);
        }
        
    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    sendErrorResponse('Unexpected error: ' . $e->getMessage(), 500, [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'error_trace' => $e->getTraceAsString()
    ]);
}
?>
