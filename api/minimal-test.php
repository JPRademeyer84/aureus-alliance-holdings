<?php
// Minimal test API to bypass any caching issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple test response
$response = [
    'success' => true,
    'message' => 'Minimal test API working perfectly',
    'data' => [
        'users' => [
            [
                'id' => 1,
                'username' => 'test_user_1',
                'email' => 'test1@example.com',
                'is_active' => true,
                'created_at' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'username' => 'test_user_2',
                'email' => 'test2@example.com',
                'is_active' => true,
                'created_at' => '2024-01-02 12:00:00'
            ]
        ],
        'statistics' => [
            'total' => 2,
            'active' => 2,
            'inactive' => 0
        ]
    ],
    'timestamp' => date('Y-m-d H:i:s'),
    'url_called' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD']
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
