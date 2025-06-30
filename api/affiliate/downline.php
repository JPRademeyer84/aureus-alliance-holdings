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

// For now, we'll use session or a simple user ID
// In production, this should come from JWT token or session
$userId = $_GET['user_id'] ?? 1; // Default to user 1 for testing

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $database->createTables();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if affiliate_downline table exists, if not return empty data
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'affiliate_downline'");
        if ($tableCheck->rowCount() == 0) {
            // Table doesn't exist, return empty data
            echo json_encode([
                'success' => true,
                'members' => [],
                'stats' => [
                    'totalMembers' => 0,
                    'activeMembers' => 0,
                    'totalVolume' => 0,
                    'totalCommissions' => 0,
                    'thisMonthVolume' => 0,
                    'thisMonthCommissions' => 0,
                    'level1Count' => 0,
                    'level2Count' => 0,
                    'level3Count' => 0
                ]
            ]);
            exit();
        }

        // Get downline members for the user
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.username,
                u.email,
                u.full_name as fullName,
                COALESCE(up.phone, '') as phone,
                COALESCE(up.country, '') as country,
                COALESCE(up.city, '') as city,
                COALESCE(up.telegram_username, '') as telegram_username,
                COALESCE(up.whatsapp_number, '') as whatsapp_number,
                COALESCE(ad.level, 1) as level,
                COALESCE(ad.total_invested, 0) as totalInvested,
                COALESCE(ad.commission_generated, 0) as commissionGenerated,
                COALESCE(ad.nft_bonus_generated, 0) as nftBonusGenerated,
                COALESCE(ad.last_activity, NOW()) as lastActivity,
                COALESCE(ad.status, 'active') as status,
                COALESCE(ad.created_at, u.created_at) as joinDate,
                0 as directReferrals,
                0 as totalDownline
            FROM affiliate_downline ad
            JOIN users u ON ad.referred_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE ad.referrer_id = ?
            ORDER BY ad.level ASC, ad.created_at DESC
        ");
        
        $stmt->execute([$userId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        // For now, use total stats as monthly stats (can be improved later)
        $thisMonthVolume = array_sum(array_column($members, 'totalInvested'));
        $thisMonthCommissions = array_sum(array_column($members, 'commissionGenerated'));

        $stats = [
            'totalMembers' => count($members),
            'activeMembers' => count(array_filter($members, fn($m) => $m['status'] === 'active')),
            'totalVolume' => array_sum(array_column($members, 'totalInvested')),
            'totalCommissions' => array_sum(array_column($members, 'commissionGenerated')),
            'level1Count' => count(array_filter($members, fn($m) => $m['level'] == 1)),
            'level2Count' => count(array_filter($members, fn($m) => $m['level'] == 2)),
            'level3Count' => count(array_filter($members, fn($m) => $m['level'] == 3)),
            'thisMonthVolume' => $thisMonthVolume,
            'thisMonthCommissions' => $thisMonthCommissions
        ];
        
        echo json_encode([
            'success' => true,
            'members' => $members,
            'stats' => $stats
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
