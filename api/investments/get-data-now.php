<?php
// FRESH FILE - NO CACHE BULLSHIT
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Try different database connections
    $pdo = null;
    $configs = [
        'mysql:host=localhost;dbname=aureus_angels;charset=utf8mb4',
        'mysql:host=localhost;port=3306;dbname=aureus_angels;charset=utf8mb4',
        'mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4',
        'mysql:host=127.0.0.1;dbname=aureus_angels;charset=utf8mb4'
    ];
    
    foreach ($configs as $dsn) {
        try {
            $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            break;
        } catch (Exception $e) {
            continue;
        }
    }
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'No database connection', 'investments' => []]);
        exit;
    }
    
    // Get ALL data from investments table
    $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for frontend
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
            'updatedAt' => $row['updated_at'] ?? ''
        ];
    }, $data);
    
    echo json_encode([
        'success' => true,
        'investments' => $investments,
        'total_found' => count($data),
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Fresh data retrieved successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'investments' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
