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
require_once '../security/commission-security.php';
session_start();

try {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'User authentication required'
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Initialize security manager
    $securityManager = new CommissionSecurityManager($db);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    
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

    // Get user's secure withdrawal history
    $withdrawalsQuery = "
        SELECT
            swr.*,
            wpq.queue_position,
            wpq.scheduled_for_date,
            wpq.queue_status,
            au.username as processed_by_username
        FROM secure_withdrawal_requests swr
        LEFT JOIN withdrawal_processing_queue wpq ON swr.id = wpq.withdrawal_request_id
        LEFT JOIN admin_users au ON swr.admin_id = au.id
        WHERE swr.user_id = ?
        ORDER BY swr.requested_at DESC
        LIMIT 50
    ";

    $withdrawalsStmt = $db->prepare($withdrawalsQuery);
    $withdrawalsStmt->execute([$userId]);
    $withdrawals = $withdrawalsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format withdrawals for frontend
    $formattedWithdrawals = [];
    foreach ($withdrawals as $withdrawal) {
        $formattedWithdrawals[] = [
            'id' => $withdrawal['id'],
            'withdrawal_type' => $withdrawal['withdrawal_type'],
            'amount_usdt' => (float)$withdrawal['requested_amount_usdt'],
            'amount_nft' => (int)$withdrawal['requested_amount_nft'],
            'wallet_address' => $withdrawal['wallet_address'],
            'transaction_hash' => $withdrawal['transaction_hash'],
            'blockchain_hash' => $withdrawal['blockchain_confirmation_hash'],
            'status' => $withdrawal['status'],
            'admin_notes' => $withdrawal['admin_notes'],
            'requested_at' => $withdrawal['requested_at'],
            'queued_at' => $withdrawal['queued_at'],
            'processing_started_at' => $withdrawal['processing_started_at'],
            'completed_at' => $withdrawal['completed_at'],
            'next_business_day' => $withdrawal['next_business_day'],
            'queue_position' => $withdrawal['queue_position'],
            'queue_status' => $withdrawal['queue_status'],
            'processed_by_username' => $withdrawal['processed_by_username']
        ];
    }
    
    // Get secure withdrawal statistics
    $statsQuery = "
        SELECT
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'queued_for_processing' THEN 1 ELSE 0 END) as queued_requests,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_requests,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_requests,
            SUM(CASE WHEN status = 'outside_business_hours' THEN 1 ELSE 0 END) as outside_hours_requests,
            SUM(CASE WHEN status = 'completed' THEN requested_amount_usdt ELSE 0 END) as total_usdt_withdrawn,
            SUM(CASE WHEN status = 'completed' THEN requested_amount_nft ELSE 0 END) as total_nft_redeemed,
            SUM(CASE WHEN status IN ('pending', 'queued_for_processing', 'processing') THEN requested_amount_usdt ELSE 0 END) as pending_usdt_amount,
            SUM(CASE WHEN status IN ('pending', 'queued_for_processing', 'processing') THEN requested_amount_nft ELSE 0 END) as pending_nft_quantity
        FROM secure_withdrawal_requests
        WHERE user_id = ?
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $formattedStats = [
        'total_requests' => (int)$stats['total_requests'],
        'pending_requests' => (int)$stats['pending_requests'],
        'queued_requests' => (int)$stats['queued_requests'],
        'processing_requests' => (int)$stats['processing_requests'],
        'completed_requests' => (int)$stats['completed_requests'],
        'failed_requests' => (int)$stats['failed_requests'],
        'outside_hours_requests' => (int)$stats['outside_hours_requests'],
        'total_usdt_withdrawn' => (float)$stats['total_usdt_withdrawn'],
        'total_nft_redeemed' => (int)$stats['total_nft_redeemed'],
        'pending_usdt_amount' => (float)$stats['pending_usdt_amount'],
        'pending_nft_quantity' => (int)$stats['pending_nft_quantity']
    ];

    echo json_encode([
        'success' => true,
        'withdrawals' => $formattedWithdrawals,
        'stats' => $formattedStats,
        'user_id' => $userId,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("Withdrawal history error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
