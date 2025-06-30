<?php
// DIRECT INVESTMENT DATA - NO BULLSHIT
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Direct MySQL connection
    $pdo = new PDO('mysql:host=localhost;dbname=aureus_angels;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get ALL investments
    $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
    $investments = $stmt->fetchAll();
    
    // Format for frontend
    $formatted = array_map(function($inv) {
        return [
            'id' => $inv['id'],
            'packageName' => $inv['package_name'] ?? 'Unknown',
            'amount' => (float)($inv['amount'] ?? 0),
            'shares' => (int)($inv['shares'] ?? 0),
            'reward' => (float)($inv['roi'] ?? 0),
            'txHash' => $inv['tx_hash'] ?? '',
            'chainId' => $inv['chain'] ?? 'polygon',
            'walletAddress' => $inv['wallet_address'] ?? '',
            'status' => $inv['status'] ?? 'pending',
            'createdAt' => $inv['created_at'] ?? '',
            'updatedAt' => $inv['updated_at'] ?? ''
        ];
    }, $investments);
    
    echo json_encode([
        'success' => true,
        'investments' => $formatted,
        'total' => count($investments),
        'message' => 'Direct database access successful'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'investments' => [],
        'total' => 0
    ]);
}
?>
