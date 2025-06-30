<?php
// SIMPLE USER CREDITS API - NO AUTH REQUIRED FOR TESTING
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

    // Mock user credits data for testing
    $creditsData = [
        'success' => true,
        'credits' => 2500.00, // User has $2500 in credits
        'user_id' => $userId,
        'currency' => 'USDT',
        'last_updated' => date('c'),
        'credit_history' => [
            [
                'id' => 'credit_001',
                'type' => 'deposit',
                'amount' => 1000.00,
                'description' => 'Initial deposit',
                'created_at' => '2025-06-20 10:00:00'
            ],
            [
                'id' => 'credit_002',
                'type' => 'bonus',
                'amount' => 500.00,
                'description' => 'Welcome bonus',
                'created_at' => '2025-06-21 14:30:00'
            ],
            [
                'id' => 'credit_003',
                'type' => 'referral_bonus',
                'amount' => 1000.00,
                'description' => 'Referral commission credit',
                'created_at' => '2025-06-22 09:15:00'
            ]
        ],
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'DIRECT_CONNECTION',
            'database_connected' => true,
            'port_used' => 3506
        ]
    ];

    echo json_encode($creditsData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Credits fetch failed: ' . $e->getMessage(),
        'credits' => 0,
        'debug' => [
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>
