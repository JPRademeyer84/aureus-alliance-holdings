<?php
/**
 * Admin Debug Configuration API
 * Allows admins to control debugging features and settings
 */

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // For debugging purposes, let's check if tables exist first
    if ($action === 'test') {
        handleTestDebugSystem($db);
        return;
    }

    // Admin authentication check
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    $adminId = $_SESSION['admin_id'];
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    handleGetDebugConfig($db);
                    break;
                case 'active':
                    handleGetActiveDebugFeatures($db);
                    break;
                case 'sessions':
                    handleGetDebugSessions($db);
                    break;
                case 'test':
                    handleTestDebugSystem($db);
                    break;
                default:
                    sendErrorResponse('Invalid action', 400);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'update':
                    handleUpdateDebugConfig($db, $adminId);
                    break;
                case 'toggle':
                    handleToggleDebugFeature($db, $adminId);
                    break;
                case 'log_session':
                    handleLogDebugSession($db);
                    break;
                default:
                    sendErrorResponse('Invalid action', 400);
            }
            break;
            
        default:
            sendErrorResponse('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    sendErrorResponse('Server error: ' . $e->getMessage(), 500);
}

function handleGetDebugConfig($db) {
    try {
        // Check if debug_config table exists, create if not
        $checkTable = "SHOW TABLES LIKE 'debug_config'";
        $stmt = $db->prepare($checkTable);
        $stmt->execute();
        $tableExists = $stmt->fetch() !== false;

        if (!$tableExists) {
            // Create debug_config table
            $createTable = "
                CREATE TABLE debug_config (
                    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                    feature_key VARCHAR(100) UNIQUE NOT NULL,
                    feature_name VARCHAR(255) NOT NULL,
                    feature_description TEXT,
                    is_enabled BOOLEAN DEFAULT FALSE,
                    is_visible BOOLEAN DEFAULT TRUE,
                    access_level ENUM('admin', 'developer', 'support') DEFAULT 'admin',
                    config_data JSON,
                    allowed_environments JSON,
                    created_by VARCHAR(36),
                    updated_by VARCHAR(36),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_feature_key (feature_key),
                    INDEX idx_enabled (is_enabled),
                    INDEX idx_visible (is_visible)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->exec($createTable);

            // Insert default debug configurations
            $defaultConfigs = [
                [
                    'feature_key' => 'console_logs',
                    'feature_name' => 'Console Logs',
                    'feature_description' => 'Monitor and capture console log messages',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'network_monitor',
                    'feature_name' => 'Network Monitor',
                    'feature_description' => 'Track API requests and responses',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'system_info',
                    'feature_name' => 'System Information',
                    'feature_description' => 'Display system and environment information',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'database_queries',
                    'feature_name' => 'Database Queries',
                    'feature_description' => 'Monitor and log database queries',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'api_testing',
                    'feature_name' => 'API Testing',
                    'feature_description' => 'Enable API testing buttons and tools',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'cache_management',
                    'feature_name' => 'Cache Management',
                    'feature_description' => 'View and clear application caches',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'error_tracking',
                    'feature_name' => 'Error Tracking',
                    'feature_description' => 'Track and log application errors',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ],
                [
                    'feature_key' => 'performance_metrics',
                    'feature_name' => 'Performance Metrics',
                    'feature_description' => 'Monitor application performance metrics',
                    'is_enabled' => false,
                    'access_level' => 'admin'
                ]
            ];

            $insertQuery = "
                INSERT INTO debug_config (
                    feature_key, feature_name, feature_description,
                    is_enabled, access_level, allowed_environments
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";

            $insertStmt = $db->prepare($insertQuery);
            foreach ($defaultConfigs as $config) {
                $insertStmt->execute([
                    $config['feature_key'],
                    $config['feature_name'],
                    $config['feature_description'],
                    $config['is_enabled'],
                    $config['access_level'],
                    json_encode(['development', 'staging'])
                ]);
            }
        }

        $query = "
            SELECT
                dc.*,
                au.username as created_by_username,
                au2.username as updated_by_username
            FROM debug_config dc
            LEFT JOIN admin_users au ON dc.created_by = au.id
            LEFT JOIN admin_users au2 ON dc.updated_by = au2.id
            ORDER BY dc.feature_name ASC
        ";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON fields
        foreach ($configs as &$config) {
            $config['config_data'] = $config['config_data'] ? json_decode($config['config_data'], true) : null;
            $config['allowed_environments'] = $config['allowed_environments'] ? json_decode($config['allowed_environments'], true) : [];
        }
        
        sendSuccessResponse($configs, 'Debug configurations retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve debug configurations: ' . $e->getMessage(), 500);
    }
}

function handleGetActiveDebugFeatures($db) {
    try {
        // Get current environment
        $environment = $_ENV['APP_ENV'] ?? 'development';
        
        $query = "
            SELECT 
                feature_key,
                feature_name,
                feature_description,
                config_data,
                access_level
            FROM debug_config 
            WHERE is_enabled = TRUE 
            AND is_visible = TRUE
            AND (
                allowed_environments IS NULL 
                OR JSON_CONTAINS(allowed_environments, ?)
            )
            ORDER BY feature_name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([json_encode($environment)]);
        $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON config data
        foreach ($features as &$feature) {
            $feature['config_data'] = $feature['config_data'] ? json_decode($feature['config_data'], true) : null;
        }
        
        sendSuccessResponse([
            'features' => $features,
            'environment' => $environment,
            'debug_enabled' => count($features) > 0
        ], 'Active debug features retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve active debug features: ' . $e->getMessage(), 500);
    }
}

function handleGetDebugSessions($db) {
    try {
        // Check if debug_sessions table exists, create if not
        $checkTable = "SHOW TABLES LIKE 'debug_sessions'";
        $stmt = $db->prepare($checkTable);
        $stmt->execute();
        $tableExists = $stmt->fetch() !== false;

        if (!$tableExists) {
            // Create debug_sessions table
            $createTable = "
                CREATE TABLE debug_sessions (
                    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                    session_id VARCHAR(255),
                    user_id VARCHAR(36),
                    admin_id VARCHAR(36),
                    feature_key VARCHAR(100) NOT NULL,
                    action_type VARCHAR(100) NOT NULL,
                    action_data JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    environment VARCHAR(50) DEFAULT 'development',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_feature_key (feature_key),
                    INDEX idx_created_at (created_at),
                    INDEX idx_user_id (user_id),
                    INDEX idx_admin_id (admin_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $db->exec($createTable);
        }

        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);

        $query = "
            SELECT
                ds.*,
                u.username as user_username,
                au.username as admin_username,
                dc.feature_name
            FROM debug_sessions ds
            LEFT JOIN users u ON ds.user_id = u.id
            LEFT JOIN admin_users au ON ds.admin_id = au.id
            LEFT JOIN debug_config dc ON ds.feature_key = dc.feature_key
            ORDER BY ds.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON action data
        foreach ($sessions as &$session) {
            $session['action_data'] = $session['action_data'] ? json_decode($session['action_data'], true) : null;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM debug_sessions";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        sendSuccessResponse([
            'sessions' => $sessions,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset
        ], 'Debug sessions retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve debug sessions: ' . $e->getMessage(), 500);
    }
}

function handleUpdateDebugConfig($db, $adminId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['feature_key'])) {
            sendErrorResponse('Invalid input data', 400);
        }
        
        $featureKey = $input['feature_key'];
        $updates = [];
        $params = [];
        
        // Build dynamic update query
        $allowedFields = ['feature_name', 'feature_description', 'is_enabled', 'is_visible', 'access_level', 'config_data', 'allowed_environments'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                
                if (in_array($field, ['config_data', 'allowed_environments'])) {
                    $params[] = json_encode($input[$field]);
                } else {
                    $params[] = $input[$field];
                }
            }
        }
        
        if (empty($updates)) {
            sendErrorResponse('No valid fields to update', 400);
        }
        
        $updates[] = "updated_by = ?";
        $updates[] = "updated_at = NOW()";
        $params[] = $adminId;
        $params[] = $featureKey;
        
        $query = "UPDATE debug_config SET " . implode(', ', $updates) . " WHERE feature_key = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            sendErrorResponse('Debug feature not found', 404);
        }
        
        sendSuccessResponse(null, 'Debug configuration updated successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to update debug configuration: ' . $e->getMessage(), 500);
    }
}

function handleToggleDebugFeature($db, $adminId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['feature_key'])) {
            sendErrorResponse('Feature key is required', 400);
        }
        
        $featureKey = $input['feature_key'];
        $enabled = $input['enabled'] ?? null;
        
        if ($enabled === null) {
            // Toggle current state
            $query = "
                UPDATE debug_config 
                SET is_enabled = NOT is_enabled, 
                    updated_by = ?, 
                    updated_at = NOW() 
                WHERE feature_key = ?
            ";
            $params = [$adminId, $featureKey];
        } else {
            // Set specific state
            $query = "
                UPDATE debug_config 
                SET is_enabled = ?, 
                    updated_by = ?, 
                    updated_at = NOW() 
                WHERE feature_key = ?
            ";
            $params = [(bool)$enabled, $adminId, $featureKey];
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            sendErrorResponse('Debug feature not found', 404);
        }
        
        // Get updated state
        $getQuery = "SELECT is_enabled FROM debug_config WHERE feature_key = ?";
        $getStmt = $db->prepare($getQuery);
        $getStmt->execute([$featureKey]);
        $result = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        sendSuccessResponse([
            'feature_key' => $featureKey,
            'enabled' => (bool)$result['is_enabled']
        ], 'Debug feature toggled successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to toggle debug feature: ' . $e->getMessage(), 500);
    }
}

function handleLogDebugSession($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['feature_key']) || !isset($input['action_type'])) {
            sendErrorResponse('Feature key and action type are required', 400);
        }
        
        session_start();
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
        $adminId = $_SESSION['admin_id'] ?? null;
        
        $query = "
            INSERT INTO debug_sessions (
                session_id, user_id, admin_id, feature_key, action_type, 
                action_data, ip_address, user_agent, environment
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $sessionId,
            $userId,
            $adminId,
            $input['feature_key'],
            $input['action_type'],
            isset($input['action_data']) ? json_encode($input['action_data']) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_ENV['APP_ENV'] ?? 'development'
        ]);
        
        sendSuccessResponse(null, 'Debug session logged successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to log debug session: ' . $e->getMessage(), 500);
    }
}

function handleTestDebugSystem($db) {
    try {
        $results = [];

        // Check if debug_config table exists
        $checkTable = "SHOW TABLES LIKE 'debug_config'";
        $stmt = $db->prepare($checkTable);
        $stmt->execute();
        $debugConfigExists = $stmt->fetch() !== false;
        $results['debug_config_table'] = $debugConfigExists;

        // Check if debug_sessions table exists
        $checkTable = "SHOW TABLES LIKE 'debug_sessions'";
        $stmt = $db->prepare($checkTable);
        $stmt->execute();
        $debugSessionsExists = $stmt->fetch() !== false;
        $results['debug_sessions_table'] = $debugSessionsExists;

        // Check admin session
        $results['session_started'] = session_status() === PHP_SESSION_ACTIVE;
        $results['admin_id_in_session'] = isset($_SESSION['admin_id']);
        $results['session_data'] = $_SESSION ?? [];

        // Check if admin_users table exists
        $checkTable = "SHOW TABLES LIKE 'admin_users'";
        $stmt = $db->prepare($checkTable);
        $stmt->execute();
        $adminUsersExists = $stmt->fetch() !== false;
        $results['admin_users_table'] = $adminUsersExists;

        if ($debugConfigExists) {
            // Get count of debug configs
            $countQuery = "SELECT COUNT(*) as count FROM debug_config";
            $stmt = $db->prepare($countQuery);
            $stmt->execute();
            $results['debug_config_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }

        sendSuccessResponse($results, 'Debug system test completed');

    } catch (Exception $e) {
        sendErrorResponse('Debug system test failed: ' . $e->getMessage(), 500);
    }
}

?>
