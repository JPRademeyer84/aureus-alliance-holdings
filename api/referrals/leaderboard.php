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

function sendResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    sendResponse(null, $message, false, $code);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    // Tables should already exist - no automatic creation

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'general';

    switch ($action) {
        case 'gold_diggers_club':
            // Redirect to the Gold Diggers specific endpoint
            include 'gold-diggers-leaderboard.php';
            break;
            
        case 'general':
            handleGeneralLeaderboard($db);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Leaderboard API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGeneralLeaderboard($db) {
    try {
        // Get general leaderboard data
        $leaderboardQuery = "
            SELECT 
                u.id as user_id,
                u.username,
                u.full_name,
                up.profile_image,
                COALESCE(stats.total_invested, 0) as total_invested,
                COALESCE(stats.total_commissions, 0) as total_commissions,
                COALESCE(stats.referral_count, 0) as referral_count
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (
                SELECT 
                    investor_wallet as user_id,
                    SUM(amount) as total_invested,
                    0 as total_commissions,
                    0 as referral_count
                FROM aureus_investments 
                WHERE status = 'completed'
                GROUP BY investor_wallet
                
                UNION ALL
                
                SELECT 
                    referrer_user_id as user_id,
                    0 as total_invested,
                    SUM(usdt_commission_amount) as total_commissions,
                    COUNT(DISTINCT referred_user_id) as referral_count
                FROM commission_transactions ct
                INNER JOIN referral_relationships rr ON ct.referrer_user_id = rr.referrer_user_id
                WHERE ct.status = 'paid'
                GROUP BY referrer_user_id
            ) stats ON u.id = stats.user_id
            WHERE COALESCE(stats.total_invested, 0) > 0 
               OR COALESCE(stats.total_commissions, 0) > 0
            ORDER BY 
                COALESCE(stats.total_invested, 0) + COALESCE(stats.total_commissions, 0) DESC,
                stats.referral_count DESC
            LIMIT 50
        ";

        $stmt = $db->prepare($leaderboardQuery);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add rank
        $leaderboard = [];
        foreach ($results as $index => $entry) {
            $leaderboard[] = [
                'rank' => $index + 1,
                'user_id' => $entry['user_id'],
                'username' => $entry['username'],
                'full_name' => $entry['full_name'],
                'profile_image' => $entry['profile_image'],
                'total_invested' => (float)$entry['total_invested'],
                'total_commissions' => (float)$entry['total_commissions'],
                'referral_count' => (int)$entry['referral_count'],
                'total_value' => (float)$entry['total_invested'] + (float)$entry['total_commissions']
            ];
        }

        sendResponse([
            'leaderboard' => $leaderboard,
            'total_users' => count($leaderboard),
            'last_updated' => date('Y-m-d H:i:s')
        ], 'General leaderboard retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve leaderboard: ' . $e->getMessage(), 500);
    }
}

// Create tables if they don't exist
function createTablesIfNotExist($db) {
    // Create aureus_investments table
    $investmentsTable = "CREATE TABLE IF NOT EXISTS aureus_investments (
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
        INDEX idx_status (status)
    )";
    
    // Create referral_relationships table
    $referralTable = "CREATE TABLE IF NOT EXISTS referral_relationships (
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
        UNIQUE KEY unique_referral (referrer_user_id, referred_user_id, level)
    )";
    
    // Create commission_transactions table
    $commissionTable = "CREATE TABLE IF NOT EXISTS commission_transactions (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        referrer_user_id VARCHAR(255) NOT NULL,
        referred_user_id VARCHAR(255) NOT NULL,
        investment_id VARCHAR(36) NOT NULL,
        level INT NOT NULL,
        usdt_commission_amount DECIMAL(15,6) NOT NULL,
        nft_commission_amount DECIMAL(15,6) DEFAULT 0,
        status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        transaction_hash VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_referrer (referrer_user_id),
        INDEX idx_referred (referred_user_id),
        INDEX idx_investment (investment_id),
        INDEX idx_status (status)
    )";
    
    $db->exec($investmentsTable);
    $db->exec($referralTable);
    $db->exec($commissionTable);
}

createTablesIfNotExist($db);
?>
