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

    // Get user ID from session or URL parameter (for testing)
    $userId = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;

    // Calculate commission balance using new 20% direct commission model
    try {
        // Get all commission records for this user
        $commissionQuery = "
            SELECT
                cr.id,
                cr.commission_amount,
                cr.commission_percentage,
                cr.commission_type,
                cr.status,
                cr.created_at,
                cr.phase_id,
                ai.amount as investment_amount,
                ai.package_name,
                u.username as referral_username
            FROM commission_records cr
            LEFT JOIN aureus_investments ai ON cr.investment_id = ai.id
            LEFT JOIN users u ON cr.referral_user_id = u.id
            WHERE cr.user_id = ?
            ORDER BY cr.created_at DESC
        ";

        $stmt = $db->prepare($commissionQuery);
        $stmt->execute([$userId]);
        $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        $totalEarned = 0;
        $availableBalance = 0;
        $pendingCommissions = 0;
        $paidCommissions = 0;

        foreach ($commissions as $commission) {
            $amount = floatval($commission['commission_amount']);
            $totalEarned += $amount;

            if ($commission['status'] === 'paid') {
                $paidCommissions += $amount;
            } elseif ($commission['status'] === 'pending') {
                $pendingCommissions += $amount;
                $availableBalance += $amount; // Available for withdrawal
            }
        }

        $balance = [
            'total_earned' => $totalEarned,
            'available_balance' => $availableBalance,
            'pending_commissions' => $pendingCommissions,
            'paid_commissions' => $paidCommissions,
            'commission_records' => $commissions
        ];

    } catch (Exception $e) {
        // Return error with commission calculation failure
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Commission calculation failed',
            'message' => $e->getMessage()
        ]);
        exit;
    }

    // Get referral statistics (how many people this user has referred)
    $referralStatsQuery = "
        SELECT
            COUNT(DISTINCT cr.referral_user_id) as total_referrals,
            COUNT(cr.id) as total_commissions,
            SUM(CASE WHEN cr.status = 'pending' THEN 1 ELSE 0 END) as pending_commissions,
            SUM(CASE WHEN cr.status = 'paid' THEN 1 ELSE 0 END) as paid_commissions,
            AVG(cr.commission_amount) as avg_commission
        FROM commission_records cr
        WHERE cr.user_id = ?
    ";

    $referralStatsStmt = $db->prepare($referralStatsQuery);
    $referralStatsStmt->execute([$userId]);
    $referralStats = $referralStatsStmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly commission breakdown
    $monthlyBreakdownQuery = "
        SELECT
            DATE_FORMAT(cr.created_at, '%Y-%m') as month,
            COUNT(*) as commission_count,
            SUM(cr.commission_amount) as total_amount,
            SUM(CASE WHEN cr.status = 'pending' THEN cr.commission_amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN cr.status = 'paid' THEN cr.commission_amount ELSE 0 END) as paid_amount
        FROM commission_records cr
        WHERE cr.user_id = ?
        AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(cr.created_at, '%Y-%m')
        ORDER BY month DESC
    ";

    $monthlyStmt = $db->prepare($monthlyBreakdownQuery);
    $monthlyStmt->execute([$userId]);
    $monthlyBreakdown = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent commission activity (last 10 commissions)
    $recentActivityQuery = "
        SELECT
            cr.commission_amount,
            cr.commission_percentage,
            cr.commission_type,
            cr.status,
            cr.created_at,
            ai.amount as investment_amount,
            ai.package_name,
            u.username as referral_username
        FROM commission_records cr
        LEFT JOIN aureus_investments ai ON cr.investment_id = ai.id
        LEFT JOIN users u ON cr.referral_user_id = u.id
        WHERE cr.user_id = ?
        ORDER BY cr.created_at DESC
        LIMIT 10
    ";

    $activityStmt = $db->prepare($recentActivityQuery);
    $activityStmt->execute([$userId]);
    $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response for new commission model
    $response = [
        'success' => true,
        'commission_model' => '20% Direct Sales Commission',
        'balance' => [
            'total_earned' => $balance['total_earned'],
            'available_balance' => $balance['available_balance'],
            'pending_commissions' => $balance['pending_commissions'],
            'paid_commissions' => $balance['paid_commissions']
        ],
        'statistics' => [
            'total_referrals' => (int)($referralStats['total_referrals'] ?? 0),
            'total_commissions' => (int)($referralStats['total_commissions'] ?? 0),
            'pending_commissions' => (int)($referralStats['pending_commissions'] ?? 0),
            'paid_commissions' => (int)($referralStats['paid_commissions'] ?? 0),
            'average_commission' => (float)($referralStats['avg_commission'] ?? 0)
        ],
        'monthly_breakdown' => array_map(function($month) {
            return [
                'month' => $month['month'],
                'commission_count' => (int)$month['commission_count'],
                'total_amount' => (float)$month['total_amount'],
                'pending_amount' => (float)$month['pending_amount'],
                'paid_amount' => (float)$month['paid_amount']
            ];
        }, $monthlyBreakdown),
        'recent_activity' => array_map(function($activity) {
            return [
                'commission_amount' => (float)$activity['commission_amount'],
                'commission_percentage' => (float)$activity['commission_percentage'],
                'commission_type' => $activity['commission_type'],
                'status' => $activity['status'],
                'created_at' => $activity['created_at'],
                'investment_amount' => (float)($activity['investment_amount'] ?? 0),
                'package_name' => $activity['package_name'] ?? 'Unknown',
                'referral_username' => $activity['referral_username'] ?? 'Unknown'
            ];
        }, $recentActivity),
        'commission_structure' => [
            'type' => 'direct_sales',
            'percentage' => 20,
            'description' => '20% commission on direct referral sales',
            'minimum_withdrawal' => 10.00,
            'payment_frequency' => 'monthly'
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Commission balance error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
