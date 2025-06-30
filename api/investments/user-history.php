<?php
// SIMPLE WORKING VERSION
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Test multiple database configurations
$configs = [
    ['host' => 'localhost', 'port' => '3306', 'db' => 'aureus_angels'],
    ['host' => 'localhost', 'port' => '3506', 'db' => 'aureus_angels'],
    ['host' => '127.0.0.1', 'port' => '3306', 'db' => 'aureus_angels']
];

$pdo = null;
foreach ($configs as $config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']};charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (Exception $e) {
        continue;
    }
}

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed', 'investments' => []]);
    exit;
}

try {

    // GET ALL INVESTMENTS
    $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        'total' => count($investments)
    ]);

} catch (Exception $e) {
    error_log("User investment history error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
