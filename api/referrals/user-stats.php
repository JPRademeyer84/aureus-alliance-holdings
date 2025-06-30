<?php
require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    
    // Initialize stats with zeros (no mock data)
    $stats = [
        'totalReferrals' => 0,
        'totalCommissions' => 0.0,
        'totalNFTBonuses' => 0,
        'level1Referrals' => 0,
        'level2Referrals' => 0,
        'level3Referrals' => 0,
        'pendingCommissions' => 0.0,
        'paidCommissions' => 0.0
    ];

    try {
        // Check if referral_commissions table exists, if not create it
        $db->exec("CREATE TABLE IF NOT EXISTS referral_commissions (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            referrer_user_id INT NOT NULL,
            referred_user_id INT NOT NULL,
            investment_id VARCHAR(255) NOT NULL,
            level INT NOT NULL CHECK (level IN (1, 2, 3)),
            purchase_amount DECIMAL(10, 2) NOT NULL,
            commission_usdt DECIMAL(10, 2) NOT NULL,
            commission_nft INT NOT NULL,
            status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_referrer (referrer_user_id),
            INDEX idx_referred (referred_user_id),
            INDEX idx_investment (investment_id),
            INDEX idx_status (status)
        )");

        // Get referral statistics for this user
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_referrals,
                SUM(commission_usdt) as total_commissions,
                SUM(commission_nft) as total_nft_bonuses,
                SUM(CASE WHEN level = 1 THEN 1 ELSE 0 END) as level1_referrals,
                SUM(CASE WHEN level = 2 THEN 1 ELSE 0 END) as level2_referrals,
                SUM(CASE WHEN level = 3 THEN 1 ELSE 0 END) as level3_referrals,
                SUM(CASE WHEN status = 'pending' THEN commission_usdt ELSE 0 END) as pending_commissions,
                SUM(CASE WHEN status = 'paid' THEN commission_usdt ELSE 0 END) as paid_commissions
            FROM referral_commissions 
            WHERE referrer_user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total_referrals'] > 0) {
            $stats = [
                'totalReferrals' => (int)$result['total_referrals'],
                'totalCommissions' => (float)$result['total_commissions'],
                'totalNFTBonuses' => (int)$result['total_nft_bonuses'],
                'level1Referrals' => (int)$result['level1_referrals'],
                'level2Referrals' => (int)$result['level2_referrals'],
                'level3Referrals' => (int)$result['level3_referrals'],
                'pendingCommissions' => (float)$result['pending_commissions'],
                'paidCommissions' => (float)$result['paid_commissions']
            ];
        }
        
    } catch (Exception $e) {
        error_log("Referral stats error: " . $e->getMessage());
        // Keep default zero stats if database error
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'user_id' => $userId,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("Referral user stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
