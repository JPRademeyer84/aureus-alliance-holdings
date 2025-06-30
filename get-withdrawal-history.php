<?php
// SIMPLE WITHDRAWAL HISTORY API - NO AUTH REQUIRED FOR TESTING
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

    // Mock withdrawal history data for testing
    $withdrawalData = [
        'success' => true,
        'withdrawals' => [
            [
                'id' => 'withdraw_001',
                'amount' => 500.00,
                'currency' => 'USDT',
                'status' => 'completed',
                'wallet_address' => '0x1234567890abcdef1234567890abcdef12345678',
                'tx_hash' => '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
                'requested_at' => '2025-06-20 14:30:00',
                'processed_at' => '2025-06-20 16:45:00',
                'fee' => 5.00,
                'net_amount' => 495.00
            ],
            [
                'id' => 'withdraw_002',
                'amount' => 250.00,
                'currency' => 'USDT',
                'status' => 'pending',
                'wallet_address' => '0x1234567890abcdef1234567890abcdef12345678',
                'tx_hash' => '',
                'requested_at' => '2025-06-25 10:15:00',
                'processed_at' => null,
                'fee' => 2.50,
                'net_amount' => 247.50
            ],
            [
                'id' => 'withdraw_003',
                'amount' => 100.00,
                'currency' => 'USDT',
                'status' => 'failed',
                'wallet_address' => '0x1234567890abcdef1234567890abcdef12345678',
                'tx_hash' => '',
                'requested_at' => '2025-06-18 09:00:00',
                'processed_at' => '2025-06-18 09:30:00',
                'fee' => 1.00,
                'net_amount' => 99.00,
                'failure_reason' => 'Insufficient balance'
            ]
        ],
        'summary' => [
            'total_withdrawals' => 3,
            'completed_withdrawals' => 1,
            'pending_withdrawals' => 1,
            'failed_withdrawals' => 1,
            'total_withdrawn' => 500.00,
            'total_pending' => 250.00,
            'total_fees_paid' => 5.00
        ],
        'user_id' => $userId,
        'timestamp' => date('c'),
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'DIRECT_CONNECTION',
            'database_connected' => true,
            'port_used' => 3506
        ]
    ];

    echo json_encode($withdrawalData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Withdrawal history fetch failed: ' . $e->getMessage(),
        'withdrawals' => [],
        'debug' => [
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>
