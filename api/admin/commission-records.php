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

    // Get all commission records with user details
    $commissionsQuery = "
        SELECT 
            rc.*,
            u1.username as referrer_username,
            u1.email as referrer_email,
            u2.username as referred_username,
            u2.email as referred_email
        FROM referral_commissions rc
        LEFT JOIN users u1 ON rc.referrer_user_id = u1.id
        LEFT JOIN users u2 ON rc.referred_user_id = u2.id
        ORDER BY rc.created_at DESC
        LIMIT 100
    ";
    
    $commissionsStmt = $db->prepare($commissionsQuery);
    $commissionsStmt->execute();
    $commissions = $commissionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format commissions for frontend
    $formattedCommissions = [];
    foreach ($commissions as $commission) {
        $formattedCommissions[] = [
            'id' => $commission['id'],
            'referrer_username' => $commission['referrer_username'] ?: 'Unknown',
            'referrer_email' => $commission['referrer_email'],
            'referred_username' => $commission['referred_username'] ?: 'Unknown',
            'referred_email' => $commission['referred_email'],
            'level' => (int)$commission['level'],
            'purchase_amount' => (float)$commission['purchase_amount'],
            'commission_usdt' => (float)$commission['commission_usdt'],
            'commission_nft' => (int)$commission['commission_nft'],
            'status' => $commission['status'],
            'created_at' => $commission['created_at'],
            'updated_at' => $commission['updated_at'],
            'investment_id' => $commission['investment_id']
        ];
    }
    
    // Get commission statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_commissions,
            SUM(commission_usdt) as total_usdt_commissions,
            SUM(commission_nft) as total_nft_commissions,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'pending' THEN commission_usdt ELSE 0 END) as pending_usdt,
            SUM(CASE WHEN status = 'paid' THEN commission_usdt ELSE 0 END) as paid_usdt,
            COUNT(DISTINCT referrer_user_id) as unique_referrers,
            COUNT(DISTINCT referred_user_id) as unique_referred
        FROM referral_commissions
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $formattedStats = [
        'total_commissions' => (int)$stats['total_commissions'],
        'total_usdt_commissions' => (float)$stats['total_usdt_commissions'],
        'total_nft_commissions' => (int)$stats['total_nft_commissions'],
        'pending_count' => (int)$stats['pending_count'],
        'paid_count' => (int)$stats['paid_count'],
        'pending_usdt' => (float)$stats['pending_usdt'],
        'paid_usdt' => (float)$stats['paid_usdt'],
        'unique_referrers' => (int)$stats['unique_referrers'],
        'unique_referred' => (int)$stats['unique_referred']
    ];
    
    // Get level breakdown
    $levelBreakdownQuery = "
        SELECT 
            level,
            COUNT(*) as count,
            SUM(commission_usdt) as total_usdt,
            SUM(commission_nft) as total_nft,
            AVG(commission_usdt) as avg_usdt
        FROM referral_commissions
        GROUP BY level
        ORDER BY level
    ";
    
    $levelStmt = $db->prepare($levelBreakdownQuery);
    $levelStmt->execute();
    $levelBreakdown = $levelStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedLevelBreakdown = [];
    foreach ($levelBreakdown as $level) {
        $formattedLevelBreakdown[] = [
            'level' => (int)$level['level'],
            'count' => (int)$level['count'],
            'total_usdt' => (float)$level['total_usdt'],
            'total_nft' => (int)$level['total_nft'],
            'avg_usdt' => (float)$level['avg_usdt']
        ];
    }

    echo json_encode([
        'success' => true,
        'commissions' => $formattedCommissions,
        'stats' => $formattedStats,
        'level_breakdown' => $formattedLevelBreakdown,
        'admin_id' => $_SESSION['admin_id'],
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("Admin commission records error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
