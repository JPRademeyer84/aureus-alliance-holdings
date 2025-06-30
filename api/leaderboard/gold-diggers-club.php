<?php
/**
 * Gold Diggers Club Leaderboard API
 * Provides leaderboard data for the Gold Diggers Club competition
 * This is a proxy/wrapper for the existing referrals API to match frontend expectations
 */

require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetLeaderboard($db);
            break;
            
        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Gold Diggers Club Leaderboard API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetLeaderboard($db) {
    try {
        // Simplified approach: just get data from referral_relationships table
        // Since the actual database might be empty, we'll return a proper empty response
        $leaderboardQuery = "
            SELECT
                u.id as user_id,
                u.username,
                u.full_name,
                up.profile_image,
                COALESCE(rr_stats.total_volume, 0) as direct_sales_volume,
                COALESCE(rr_stats.referral_count, 0) as direct_referrals_count,
                CASE
                    WHEN COALESCE(rr_stats.total_volume, 0) >= 2500 THEN 1
                    ELSE 0
                END as qualified
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (
                SELECT
                    referrer_user_id,
                    COALESCE(SUM(total_investments), 0) as total_volume,
                    COUNT(DISTINCT referred_user_id) as referral_count
                FROM referral_relationships
                WHERE status = 'active'
                GROUP BY referrer_user_id
            ) rr_stats ON u.id = rr_stats.referrer_user_id
            WHERE COALESCE(rr_stats.total_volume, 0) > 0
            ORDER BY rr_stats.total_volume DESC, rr_stats.referral_count DESC
            LIMIT 50
        ";

        $stmt = $db->prepare($leaderboardQuery);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process leaderboard data and add rank and bonus amount
        $leaderboard = [];
        
        if (!empty($results)) {
            foreach ($results as $index => $entry) {
                $rank = $index + 1;
                $bonusAmount = calculateBonusAmount($rank, $entry['qualified']);

                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $entry['user_id'],
                    'username' => $entry['username'],
                    'full_name' => $entry['full_name'],
                    'profile_image' => $entry['profile_image'],
                    'referrals' => (int)$entry['direct_referrals_count'], // Match frontend interface
                    'volume' => (float)$entry['direct_sales_volume'], // Match frontend interface
                    'prize' => $bonusAmount, // Match frontend interface
                    'country' => 'Unknown', // Default value for frontend compatibility
                    'flag' => '🌍', // Default flag for frontend compatibility
                    'isQualified' => (bool)$entry['qualified'], // Match frontend interface
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

function calculateBonusAmount($rank, $qualified) {
    if (!$qualified) {
        return 0;
    }
    
    switch ($rank) {
        case 1:
            return 100000;
        case 2:
            return 50000;
        case 3:
            return 30000;
        case 4:
        case 5:
        case 6:
        case 7:
        case 8:
        case 9:
        case 10:
            return 10000;
        default:
            return 0;
    }
}

function getPresaleStats($db) {
    try {
        // Get presale statistics
        $statsQuery = "
            SELECT 
                COUNT(*) as total_packs_sold,
                SUM(amount) as total_raised,
                MAX(created_at) as last_sale_date
            FROM aureus_investments 
            WHERE status = 'completed'
        ";
        
        $stmt = $db->prepare($statsQuery);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalPacksAvailable = 200000; // Total NFT supply
        $totalPacksSold = (int)($stats['total_packs_sold'] ?? 0);
        $totalRaised = (float)($stats['total_raised'] ?? 0);
        $presaleProgress = $totalPacksAvailable > 0 ? ($totalPacksSold / $totalPacksAvailable) * 100 : 0;
        
        // Estimate end date based on current progress (simplified calculation)
        $estimatedEndDate = date('Y-m-d', strtotime('+30 days'));
        $isPresaleActive = $totalPacksSold < $totalPacksAvailable;
        
        return [
            'total_packs_sold' => $totalPacksSold,
            'total_packs_available' => $totalPacksAvailable,
            'total_raised' => $totalRaised,
            'presale_progress' => round($presaleProgress, 2),
            'estimated_end_date' => $estimatedEndDate,
            'is_presale_active' => $isPresaleActive
        ];
        
    } catch (Exception $e) {
        error_log("Error getting presale stats: " . $e->getMessage());
        return [
            'total_packs_sold' => 0,
            'total_packs_available' => 200000,
            'total_raised' => 0,
            'presale_progress' => 0,
            'estimated_end_date' => date('Y-m-d', strtotime('+30 days')),
            'is_presale_active' => true
        ];
    }
}

// Create necessary tables if they don't exist
function createTablesIfNeeded($db) {
    // Create referral_relationships table if it doesn't exist
    $createReferralTable = "CREATE TABLE IF NOT EXISTS referral_relationships (
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
        INDEX idx_status (status)
    )";
    
    // Create aureus_investments table if it doesn't exist
    $createInvestmentsTable = "CREATE TABLE IF NOT EXISTS aureus_investments (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        investor_wallet VARCHAR(255) NOT NULL,
        amount DECIMAL(15,6) NOT NULL,
        package_tier VARCHAR(50),
        tx_hash VARCHAR(255),
        chain_id VARCHAR(50),
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_investor (investor_wallet),
        INDEX idx_status (status),
        INDEX idx_tier (package_tier)
    )";
    
    try {
        $db->exec($createReferralTable);
        $db->exec($createInvestmentsTable);
    } catch (Exception $e) {
        error_log("Error creating tables: " . $e->getMessage());
    }
}

// Create tables if needed
createTablesIfNeeded($db);
?>
