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
    
    // Initialize empty records array (no mock data)
    $records = [];

    try {
        // Get referral commission history for this user
        $stmt = $db->prepare("
            SELECT 
                rc.id,
                rc.level,
                rc.purchase_amount,
                rc.commission_usdt,
                rc.commission_nft,
                rc.status,
                rc.created_at,
                u.email as referred_user_email,
                u.username as referred_user_username
            FROM referral_commissions rc
            LEFT JOIN users u ON rc.referred_user_id = u.id
            WHERE rc.referrer_user_id = ?
            ORDER BY rc.created_at DESC
            LIMIT 50
        ");
        
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format records for frontend
        foreach ($results as $result) {
            $records[] = [
                'id' => $result['id'],
                'referredUser' => $result['referred_user_email'] ?: $result['referred_user_username'] ?: 'Unknown User',
                'level' => (int)$result['level'],
                'purchaseAmount' => (float)$result['purchase_amount'],
                'commissionUSDT' => (float)$result['commission_usdt'],
                'commissionNFT' => (int)$result['commission_nft'],
                'status' => $result['status'],
                'date' => $result['created_at']
            ];
        }
        
    } catch (Exception $e) {
        error_log("Referral history error: " . $e->getMessage());
        // Keep empty records array if database error
    }

    echo json_encode([
        'success' => true,
        'records' => $records,
        'total_records' => count($records),
        'user_id' => $userId,
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    error_log("Referral user history error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
