<?php
require_once '../config/database.php';

// Response utility functions
function sendSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Simple CORS headers with credentials support
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    // Tables should already exist - no automatic creation

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'gold_diggers_club';

    switch ($action) {
        case 'gold_diggers_club':
            handleGoldDiggersClub($db);
            break;
            
        case 'user_stats':
            handleUserStats($db);
            break;
            
        case 'presale_stats':
            handlePresaleStats($db);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Gold Diggers Leaderboard API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGoldDiggersClub($db) {
    try {
        // Simplified approach: Since referral system is not fully populated yet,
        // return empty leaderboard with proper structure
        $leaderboard = [];

        // Check if there are any referral relationships
        $referralCountQuery = "SELECT COUNT(*) as count FROM referral_relationships WHERE status = 'active'";
        $stmt = $db->prepare($referralCountQuery);
        $stmt->execute();
        $referralCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // If there are referrals, try to build leaderboard
        if ($referralCount > 0) {
            $leaderboardQuery = "
                SELECT
                    u.id as user_id,
                    u.username,
                    u.full_name,
                    COALESCE(rr.total_investments, 0) as direct_sales_volume,
                    1 as direct_referrals_count,
                    CASE
                        WHEN COALESCE(rr.total_investments, 0) >= 2500 THEN 1
                        ELSE 0
                    END as qualified
                FROM users u
                INNER JOIN referral_relationships rr ON u.id = rr.referrer_user_id
                WHERE rr.status = 'active' AND COALESCE(rr.total_investments, 0) > 0
                ORDER BY rr.total_investments DESC
            ";

            $stmt = $db->prepare($leaderboardQuery);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process results
            foreach ($results as $index => $entry) {
                $rank = $index + 1;
                $bonusAmount = calculateBonusAmount($rank, $entry['qualified']);

                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $entry['user_id'],
                    'username' => $entry['username'],
                    'full_name' => $entry['full_name'] ?? $entry['username'],
                    'profile_image' => null,
                    'referrals' => (int)$entry['direct_referrals_count'],
                    'volume' => (float)$entry['direct_sales_volume'],
                    'prize' => $bonusAmount,
                    'country' => 'Unknown',
                    'flag' => '🌍',
                    'isQualified' => (bool)$entry['qualified'],
                    'direct_sales_volume' => (float)$entry['direct_sales_volume'],
                    'direct_referrals_count' => (int)$entry['direct_referrals_count'],
                    'bonus_amount' => $bonusAmount,
                    'qualified' => (bool)$entry['qualified']
                ];
            }
        }

        // Get presale statistics
        $presaleStats = getPresaleStats($db);

        // Calculate summary statistics
        $totalParticipants = count($leaderboard);
        $leadingVolume = $totalParticipants > 0 ? $leaderboard[0]['volume'] : 0;

        sendSuccessResponse([
            'leaderboard' => $leaderboard,
            'presale_stats' => $presaleStats,
            'total_bonus_pool' => 250000,
            'minimum_qualification' => 2500,
            'total_participants' => $totalParticipants,
            'leading_volume' => $leadingVolume,
            'last_updated' => date('Y-m-d H:i:s')
        ], 'Gold Diggers Club leaderboard retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve leaderboard: ' . $e->getMessage(), 500);
    }
}

function handleUserStats($db) {
    try {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID is required', 400);
        }

        // Get user's basic info
        $userQuery = "SELECT id, username, full_name FROM users WHERE id = ?";
        $stmt = $db->prepare($userQuery);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            sendErrorResponse('User not found', 404);
        }

        // Get user's referral stats if they exist
        $referralQuery = "SELECT total_investments, total_commissions_generated
                         FROM referral_relationships
                         WHERE referrer_user_id = ? AND status = 'active'";
        $stmt = $db->prepare($referralQuery);
        $stmt->execute([$userId]);
        $referralStats = $stmt->fetch(PDO::FETCH_ASSOC);

        $directSalesVolume = $referralStats ? (float)$referralStats['total_investments'] : 0;
        $qualified = $directSalesVolume >= 2500;
        $rank = 1; // Default rank
        $bonusAmount = calculateBonusAmount($rank, $qualified);

        $userStats = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'profile_image' => null,
            'direct_sales_volume' => $directSalesVolume,
            'direct_referrals_count' => $referralStats ? 1 : 0,
            'qualified' => $qualified,
            'rank' => $rank,
            'bonus_amount' => $bonusAmount
        ];

        sendSuccessResponse($userStats, 'User stats retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve user stats: ' . $e->getMessage(), 500);
    }
}

function handlePresaleStats($db) {
    try {
        $stats = getPresaleStats($db);
        sendSuccessResponse($stats, 'Presale stats retrieved successfully');
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve presale stats: ' . $e->getMessage(), 500);
    }
}

function getPresaleStats($db) {
    // Get presale statistics from actual investments
    $presaleQuery = "
        SELECT
            COUNT(*) as total_investments,
            SUM(shares) as total_packs_sold,
            SUM(amount) as total_raised,
            MAX(created_at) as last_investment
        FROM aureus_investments
        WHERE status IN ('completed', 'pending')
    ";

    $stmt = $db->prepare($presaleQuery);
    $stmt->execute();
    $presaleData = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalPacksAvailable = 200000; // Total NFT supply
    $totalPacksSold = (int)($presaleData['total_packs_sold'] ?? 0);
    $totalRaised = (float)($presaleData['total_raised'] ?? 0);
    $presaleProgress = $totalPacksAvailable > 0 ? ($totalPacksSold / $totalPacksAvailable) * 100 : 0;

    // Estimate end date based on current progress
    $estimatedEndDate = date('Y-m-d', strtotime('+30 days'));
    $isPresaleActive = $totalPacksSold < $totalPacksAvailable;

    return [
        'total_packs_sold' => $totalPacksSold,
        'total_packs_available' => $totalPacksAvailable,
        'total_raised' => $totalRaised,
        'presale_progress' => round($presaleProgress, 2),
        'estimated_end_date' => $estimatedEndDate,
        'is_presale_active' => $isPresaleActive,
        'last_investment' => $presaleData['last_investment']
    ];
}

function calculateBonusAmount($rank, $qualified) {
    if (!$qualified) {
        return 0;
    }

    switch ($rank) {
        case 1:
            return 100000; // $100,000 for 1st place
        case 2:
            return 50000;  // $50,000 for 2nd place
        case 3:
            return 30000;  // $30,000 for 3rd place
        case 4:
        case 5:
        case 6:
        case 7:
        case 8:
        case 9:
        case 10:
            return 10000;  // $10,000 each for 4th-10th place
        default:
            return 0;      // No bonus for ranks below 10
    }
}

// Create referral_relationships table if it doesn't exist
function createReferralRelationshipsTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS referral_relationships (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        referrer_user_id VARCHAR(255) NOT NULL,
        referred_user_id VARCHAR(255) NOT NULL,
        level INT NOT NULL DEFAULT 1,
        investment_amount DECIMAL(15,6) DEFAULT 0,
        commission_earned DECIMAL(15,6) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_referrer (referrer_user_id),
        INDEX idx_referred (referred_user_id),
        INDEX idx_level (level),
        INDEX idx_status (status),
        UNIQUE KEY unique_referral (referrer_user_id, referred_user_id, level)
    )";
    
    $db->exec($query);
}

// Create aureus_investments table if it doesn't exist
function createAureusInvestmentsTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS aureus_investments (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        investor_wallet VARCHAR(255) NOT NULL,
        package_tier ENUM('Shovel', 'Pick', 'Miner', 'Loader', 'Excavator', 'Crusher', 'Refinery', 'Aureus') NOT NULL,
        package_count INT NOT NULL DEFAULT 1,
        amount DECIMAL(15,6) NOT NULL,
        investment_amount DECIMAL(15,6) NOT NULL,
        referrer_wallet VARCHAR(255),
        transaction_hash VARCHAR(255),
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_investor (investor_wallet),
        INDEX idx_referrer (referrer_wallet),
        INDEX idx_status (status),
        INDEX idx_tier (package_tier)
    )";
    
    $db->exec($query);
}

// Create tables
createReferralRelationshipsTable($db);
createAureusInvestmentsTable($db);
?>
