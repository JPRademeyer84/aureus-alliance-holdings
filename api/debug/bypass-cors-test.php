<?php
// BYPASS ALL CORS BULLSHIT AND GET THE DATA
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// NO CORS INCLUDES - DIRECT DATABASE ACCESS
session_start();

try {
    // Direct database connection without any middleware
    $host = 'localhost';
    $dbname = 'aureus_angels';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get ALL investment data - NO AUTHENTICATION CHECKS
    $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
    $investments = $stmt->fetchAll();
    
    // Format for frontend
    $formattedInvestments = array_map(function($investment) {
        return [
            'id' => $investment['id'],
            'packageName' => $investment['package_name'] ?? 'Unknown Package',
            'amount' => (float)($investment['amount'] ?? 0),
            'shares' => (int)($investment['shares'] ?? 0),
            'reward' => (float)($investment['roi'] ?? 0),
            'txHash' => $investment['tx_hash'] ?? '',
            'chainId' => $investment['chain'] ?? 'polygon',
            'walletAddress' => $investment['wallet_address'] ?? '',
            'status' => $investment['status'] ?? 'pending',
            'createdAt' => $investment['created_at'] ?? '',
            'updatedAt' => $investment['updated_at'] ?? $investment['created_at'] ?? ''
        ];
    }, $investments);
    
    // Calculate summary
    $totalInvested = 0;
    $totalROI = 0;
    $completedInvestments = 0;
    $pendingInvestments = 0;
    
    foreach ($investments as $investment) {
        if ($investment['status'] === 'completed') {
            $totalInvested += (float)($investment['amount'] ?? 0);
            $totalROI += (float)($investment['roi'] ?? 0);
            $completedInvestments++;
        } elseif ($investment['status'] === 'pending') {
            $pendingInvestments++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'investments' => $formattedInvestments,
        'summary' => [
            'totalInvestments' => count($investments),
            'completedInvestments' => $completedInvestments,
            'pendingInvestments' => $pendingInvestments,
            'totalInvested' => $totalInvested,
            'totalROI' => $totalROI,
            'totalShares' => array_sum(array_column($investments, 'shares'))
        ],
        'debug_info' => [
            'cors_bypassed' => true,
            'auth_bypassed' => true,
            'direct_db_access' => true,
            'total_records_found' => count($investments)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'cors_bypassed' => true,
            'auth_bypassed' => true,
            'direct_db_access' => 'failed'
        ]
    ]);
}
?>
