<?php
// NUCLEAR OPTION - STANDALONE INVESTMENT GETTER
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
    
    // Get ALL investments from database
    $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
    $rawData = $stmt->fetchAll();
    
    // Format exactly as frontend expects
    $investments = array_map(function($row) {
        return [
            'id' => $row['id'],
            'packageName' => $row['package_name'] ?? 'Unknown Package',
            'amount' => (float)($row['amount'] ?? 0),
            'shares' => (int)($row['shares'] ?? 0),
            'reward' => (float)($row['roi'] ?? 0),
            'txHash' => $row['tx_hash'] ?? '',
            'chainId' => $row['chain'] ?? 'polygon',
            'walletAddress' => $row['wallet_address'] ?? '',
            'status' => $row['status'] ?? 'pending',
            'createdAt' => $row['created_at'] ?? '',
            'updatedAt' => $row['updated_at'] ?? $row['created_at'] ?? ''
        ];
    }, $rawData);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'investments' => $investments,
        'total_found' => count($rawData),
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'NUCLEAR_STANDALONE',
            'database_connected' => true,
            'raw_count' => count($rawData)
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'investments' => [],
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'NUCLEAR_STANDALONE',
            'database_connected' => false
        ]
    ]);
}
?>
