<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
session_start();

try {
    // Check if admin is authenticated
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Admin authentication required'
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    // Create commission_withdrawals table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS commission_withdrawals (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id INT NOT NULL,
        withdrawal_type ENUM('usdt', 'nft', 'reinvest') NOT NULL,
        amount DECIMAL(15, 6) NOT NULL,
        nft_quantity INT DEFAULT 0,
        wallet_address VARCHAR(255),
        transaction_hash VARCHAR(255),
        status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        admin_notes TEXT,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        processed_by INT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_type (withdrawal_type)
    )");

    // Get all withdrawal requests with user details
    $requestsQuery = "
        SELECT 
            cw.*,
            u.username as user_username,
            u.email as user_email,
            au.username as processed_by_username
        FROM commission_withdrawals cw
        LEFT JOIN users u ON cw.user_id = u.id
        LEFT JOIN admin_users au ON cw.processed_by = au.id
        ORDER BY cw.requested_at DESC
        LIMIT 100
    ";
    
    $requestsStmt = $db->prepare($requestsQuery);
    $requestsStmt->execute();
    $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format requests for frontend
    $formattedRequests = [];
    foreach ($requests as $request) {
        $formattedRequests[] = [
            'id' => $request['id'],
            'user_username' => $request['user_username'] ?: 'Unknown',
            'user_email' => $request['user_email'],
            'withdrawal_type' => $request['withdrawal_type'],
            'amount' => (float)$request['amount'],
            'nft_quantity' => (int)$request['nft_quantity'],
            'wallet_address' => $request['wallet_address'],
            'transaction_hash' => $request['transaction_hash'],
            'status' => $request['status'],
            'admin_notes' => $request['admin_notes'],
            'requested_at' => $request['requested_at'],
            'processed_at' => $request['processed_at'],
            'processed_by_username' => $request['processed_by_username']
        ];
    }
    
    // Get withdrawal statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status = 'completed' AND withdrawal_type = 'usdt' THEN amount ELSE 0 END) as total_usdt_paid,
            SUM(CASE WHEN status = 'completed' AND withdrawal_type = 'nft' THEN nft_quantity ELSE 0 END) as total_nft_redeemed,
            SUM(CASE WHEN status = 'pending' AND withdrawal_type = 'usdt' THEN amount ELSE 0 END) as pending_usdt,
            SUM(CASE WHEN status = 'pending' AND withdrawal_type = 'nft' THEN nft_quantity ELSE 0 END) as pending_nft
        FROM commission_withdrawals
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $formattedStats = [
        'total_requests' => (int)$stats['total_requests'],
        'pending_count' => (int)$stats['pending_count'],
        'completed_count' => (int)$stats['completed_count'],
        'failed_count' => (int)$stats['failed_count'],
        'cancelled_count' => (int)$stats['cancelled_count'],
        'total_usdt_paid' => (float)$stats['total_usdt_paid'],
        'total_nft_redeemed' => (int)$stats['total_nft_redeemed'],
        'pending_usdt' => (float)$stats['pending_usdt'],
        'pending_nft' => (int)$stats['pending_nft']
    ];
    
    // Get recent activity
    $recentActivityQuery = "
        SELECT 
            cw.withdrawal_type,
            cw.amount,
            cw.nft_quantity,
            cw.status,
            cw.processed_at,
            u.username as user_username
        FROM commission_withdrawals cw
        LEFT JOIN users u ON cw.user_id = u.id
        WHERE cw.status IN ('completed', 'failed', 'cancelled')
        ORDER BY cw.processed_at DESC
        LIMIT 10
    ";
    
    $activityStmt = $db->prepare($recentActivityQuery);
    $activityStmt->execute();
    $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedActivity = [];
    foreach ($recentActivity as $activity) {
        $formattedActivity[] = [
            'user_username' => $activity['user_username'],
            'withdrawal_type' => $activity['withdrawal_type'],
            'amount' => (float)$activity['amount'],
            'nft_quantity' => (int)$activity['nft_quantity'],
            'status' => $activity['status'],
            'processed_at' => $activity['processed_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'requests' => $formattedRequests,
        'stats' => $formattedStats,
        'recent_activity' => $formattedActivity,
        'admin_id' => $_SESSION['admin_id'],
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("Admin withdrawal requests error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
