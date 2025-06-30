<?php
// Simple dashboard stats endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Database connection
    $pdo = new PDO(
        'mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get basic stats
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
        ],
        'certificates' => [
            'total_certificates' => 0,
            'pending_generation' => 0,
            'completed_certificates' => 0,
            'failed_generation' => 0,
            'valid_certificates' => 0,
            'converted_to_nft' => 0
        ]
    ];

    // Try to get real data, but use defaults if tables don't exist
    try {
        // Admin users count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM admin_users");
        $stats['admins']['total'] = (int)$stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE role = 'super_admin'");
        $stats['admins']['super_admins'] = (int)$stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE role = 'admin'");
        $stats['admins']['regular_admins'] = (int)$stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE role = 'chat_support'");
        $stats['admins']['chat_support'] = (int)$stmt->fetch()['count'];
    } catch (Exception $e) {
        // Table might not exist, use defaults
    }

    try {
        // Investment packages count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM investment_packages");
        $stats['system']['packages_available'] = (int)$stmt->fetch()['count'];
    } catch (Exception $e) {
        // Table might not exist, use defaults
    }

    try {
        // Investment wallets count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM investment_wallets WHERE is_active = 1");
        $stats['system']['wallets_configured'] = (int)$stmt->fetch()['count'];
    } catch (Exception $e) {
        // Table might not exist, use defaults
    }

    try {
        // Users count (if users table exists)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['users']['total'] = (int)$stmt->fetch()['count'];
    } catch (Exception $e) {
        // Table might not exist, use defaults
    }

    echo json_encode([
        'success' => true,
        'message' => 'Dashboard stats retrieved successfully',
        'data' => $stats
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'data' => [
            'users' => [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'new_today' => 0
            ],
            'admins' => [
                'total' => 1,
                'online' => 1,
                'super_admins' => 1,
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
                'wallets_configured' => 3,
                'packages_available' => 7,
                'recent_activity' => 1
            ],
            'certificates' => [
                'total_certificates' => 0,
                'pending_generation' => 0,
                'completed_certificates' => 0,
                'failed_generation' => 0,
                'valid_certificates' => 0,
                'converted_to_nft' => 0
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?>
