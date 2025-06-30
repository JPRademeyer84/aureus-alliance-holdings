<?php
// Simple users API without complex paths
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple response
echo json_encode([
    'success' => true,
    'message' => 'Users API working',
    'data' => [
        'users' => [
            [
                'id' => 1,
                'username' => 'john_doe',
                'email' => 'john@example.com',
                'is_active' => true,
                'created_at' => '2024-01-01 12:00:00',
                'updated_at' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'username' => 'jane_smith',
                'email' => 'jane@example.com',
                'is_active' => true,
                'created_at' => '2024-01-02 12:00:00',
                'updated_at' => '2024-01-02 12:00:00'
            ]
        ],
        'statistics' => [
            'total' => 2,
            'active' => 2,
            'inactive' => 0
        ]
    ]
], JSON_PRETTY_PRINT);
?>
