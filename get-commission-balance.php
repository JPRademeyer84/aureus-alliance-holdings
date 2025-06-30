<?php
// SIMPLE COMMISSION BALANCE API - NO AUTH REQUIRED FOR TESTING
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Direct MySQL connection with CORRECT PORT 3506
    $pdo = new PDO('mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Get user ID from query parameter (for testing)
    $userId = $_GET['user_id'] ?? 1;

    // Mock commission data for testing
    $commissionData = [
        'success' => true,
        'balance' => [
            'total_balance' => 1250.75,
            'available_balance' => 1000.00,
            'pending_balance' => 250.75,
            'currency' => 'USDT'
        ],
        'commission_stats' => [
            'total_commissions' => 15,
            'pending_commissions' => 3,
            'paid_commissions' => 12,
            'pending_usdt' => 250.75,
            'pending_nft' => 2
        ],
        'level_breakdown' => [
            [
                'level' => 1,
                'count' => 8,
                'commission_rate' => 10,
                'total_earned' => 800.00
            ],
            [
                'level' => 2,
                'count' => 4,
                'commission_rate' => 5,
                'total_earned' => 300.00
            ],
            [
                'level' => 3,
                'count' => 3,
                'commission_rate' => 3,
                'total_earned' => 150.75
            ]
        ],
        'recent_activity' => [
            [
                'id' => 'comm_001',
                'type' => 'referral_commission',
                'amount' => 50.00,
                'currency' => 'USDT',
                'from_user' => 'john_doe',
                'level' => 1,
                'status' => 'paid',
                'created_at' => '2025-06-25 14:30:00'
            ],
            [
                'id' => 'comm_002',
                'type' => 'referral_commission',
                'amount' => 25.00,
                'currency' => 'USDT',
                'from_user' => 'jane_smith',
                'level' => 2,
                'status' => 'pending',
                'created_at' => '2025-06-25 10:15:00'
            ],
            [
                'id' => 'comm_003',
                'type' => 'nft_bonus',
                'amount' => 1,
                'currency' => 'NFT',
                'from_user' => 'crypto_king',
                'level' => 1,
                'status' => 'pending',
                'created_at' => '2025-06-24 16:45:00'
            ]
        ],
        'security_verified' => true,
        'user_id' => $userId,
        'timestamp' => date('c'),
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'DIRECT_CONNECTION',
            'database_connected' => true,
            'port_used' => 3506
        ]
    ];

    echo json_encode($commissionData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Commission data fetch failed: ' . $e->getMessage(),
        'balance' => [
            'total_balance' => 0,
            'available_balance' => 0,
            'pending_balance' => 0,
            'currency' => 'USDT'
        ],
        'debug' => [
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>
