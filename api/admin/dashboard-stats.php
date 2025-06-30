<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

function sendStatsResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendStatsErrorResponse($message, $code = 400) {
    sendStatsResponse(null, $message, false, $code);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'GET') {
        sendStatsErrorResponse('Only GET method allowed', 405);
    }

    $adminId = $_GET['admin_id'] ?? '';
    
    if (!$adminId) {
        sendStatsErrorResponse('Admin ID is required');
    }
    
    // Verify admin exists and is active
    $adminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute([$adminId]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        sendStatsErrorResponse('Invalid admin credentials', 401);
    }

    // Initialize stats array
    $stats = [
        'users' => [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'new_today' => 0
        ],
        'admins' => [
            'total' => 0,
            'online' => 0,
            'super_admins' => 0,
            'regular_admins' => 0,
            'chat_support' => 0
        ],
        'messages' => [
            'contact_messages' => 0,
            'unread_contact' => 0,
            'chat_sessions' => 0,
            'active_chats' => 0,
            'offline_messages' => 0
        ],
        'system' => [
            'wallets_configured' => 0,
            'packages_available' => 0,
            'recent_activity' => 0
        ]
    ];

    // Get user statistics
    try {
        $userStatsQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today
            FROM users";
        $userStatsStmt = $db->prepare($userStatsQuery);
        $userStatsStmt->execute();
        $userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userStats) {
            $stats['users'] = [
                'total' => intval($userStats['total']),
                'active' => intval($userStats['active']),
                'inactive' => intval($userStats['inactive']),
                'new_today' => intval($userStats['new_today'])
            ];
        }
    } catch (Exception $e) {
        // Users table might not exist yet, keep default values
        error_log("Users stats error: " . $e->getMessage());
    }

    // Get admin statistics
    try {
        $adminStatsQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END) as online,
            SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admins,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as regular_admins,
            SUM(CASE WHEN role = 'chat_support' THEN 1 ELSE 0 END) as chat_support
            FROM admin_users WHERE is_active = TRUE";
        $adminStatsStmt = $db->prepare($adminStatsQuery);
        $adminStatsStmt->execute();
        $adminStats = $adminStatsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($adminStats) {
            $stats['admins'] = [
                'total' => intval($adminStats['total']),
                'online' => intval($adminStats['online']),
                'super_admins' => intval($adminStats['super_admins']),
                'regular_admins' => intval($adminStats['regular_admins']),
                'chat_support' => intval($adminStats['chat_support'])
            ];
        }
    } catch (Exception $e) {
        error_log("Admin stats error: " . $e->getMessage());
    }

    // Get message statistics
    try {
        // Contact messages
        $contactQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread
            FROM contact_messages";
        $contactStmt = $db->prepare($contactQuery);
        $contactStmt->execute();
        $contactStats = $contactStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($contactStats) {
            $stats['messages']['contact_messages'] = intval($contactStats['total']);
            $stats['messages']['unread_contact'] = intval($contactStats['unread']);
        }
    } catch (Exception $e) {
        error_log("Contact messages stats error: " . $e->getMessage());
    }

    try {
        // Chat sessions
        $chatQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
            FROM chat_sessions";
        $chatStmt = $db->prepare($chatQuery);
        $chatStmt->execute();
        $chatStats = $chatStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($chatStats) {
            $stats['messages']['chat_sessions'] = intval($chatStats['total']);
            $stats['messages']['active_chats'] = intval($chatStats['active']);
        }
    } catch (Exception $e) {
        error_log("Chat sessions stats error: " . $e->getMessage());
    }

    try {
        // Offline messages
        $offlineQuery = "SELECT COUNT(*) as total FROM offline_messages WHERE is_read = FALSE";
        $offlineStmt = $db->prepare($offlineQuery);
        $offlineStmt->execute();
        $offlineStats = $offlineStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($offlineStats) {
            $stats['messages']['offline_messages'] = intval($offlineStats['total']);
        }
    } catch (Exception $e) {
        error_log("Offline messages stats error: " . $e->getMessage());
    }

    // Get system statistics (only for admin+ roles)
    if (in_array($admin['role'], ['super_admin', 'admin'])) {
        try {
            // Wallet count
            $walletQuery = "SELECT COUNT(*) as total FROM wallets WHERE is_active = TRUE";
            $walletStmt = $db->prepare($walletQuery);
            $walletStmt->execute();
            $walletStats = $walletStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($walletStats) {
                $stats['system']['wallets_configured'] = intval($walletStats['total']);
            }
        } catch (Exception $e) {
            error_log("Wallet stats error: " . $e->getMessage());
        }

        try {
            // Package count
            $packageQuery = "SELECT COUNT(*) as total FROM investment_packages WHERE is_active = TRUE";
            $packageStmt = $db->prepare($packageQuery);
            $packageStmt->execute();
            $packageStats = $packageStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($packageStats) {
                $stats['system']['packages_available'] = intval($packageStats['total']);
            }
        } catch (Exception $e) {
            error_log("Package stats error: " . $e->getMessage());
        }

        try {
            // Recent activity (last 24 hours)
            $activityQuery = "SELECT 
                (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) +
                (SELECT COUNT(*) FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) +
                (SELECT COUNT(*) FROM chat_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
                as total";
            $activityStmt = $db->prepare($activityQuery);
            $activityStmt->execute();
            $activityStats = $activityStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activityStats) {
                $stats['system']['recent_activity'] = intval($activityStats['total']);
            }
        } catch (Exception $e) {
            error_log("Activity stats error: " . $e->getMessage());
        }
    }

    // Add timestamp
    $stats['last_updated'] = date('Y-m-d H:i:s');
    $stats['admin_role'] = $admin['role'];

    sendStatsResponse($stats, 'Dashboard statistics retrieved successfully');

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    sendStatsErrorResponse('Internal server error', 500);
}
?>
