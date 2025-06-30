<?php
// Root level API test
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'success' => true,
    'message' => 'Root level API test working',
    'data' => [
        'users' => [
            ['id' => 1, 'username' => 'test1', 'email' => 'test1@example.com', 'is_active' => true, 'created_at' => '2024-01-01 12:00:00'],
            ['id' => 2, 'username' => 'test2', 'email' => 'test2@example.com', 'is_active' => true, 'created_at' => '2024-01-02 12:00:00']
        ],
        'statistics' => ['total' => 2, 'active' => 2, 'inactive' => 0]
    ]
], JSON_PRETTY_PRINT);
?>
