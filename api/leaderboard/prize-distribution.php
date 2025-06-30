<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetPrizeDistribution($db);
            break;
        case 'POST':
            handleCalculateWinners($db);
            break;
        case 'PUT':
            handleDistributePrizes($db);
            break;
        default:
            throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetPrizeDistribution($db) {
    try {
        $action = $_GET['action'] ?? 'status';
        
        if ($action === 'status') {
            // Get current prize distribution status
            $query = "SELECT * FROM gold_diggers_prizes ORDER BY rank ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $prizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get presale status
            $presaleStatus = getPresaleStatus($db);
            
            echo json_encode([
                'success' => true,
                'prizes' => $prizes,
                'presale_status' => $presaleStatus,
                'total_prize_pool' => 250000,
                'distribution_complete' => !empty($prizes) && count(array_filter($prizes, fn($p) => $p['status'] === 'distributed')) > 0
            ]);
            
        } elseif ($action === 'winners') {
            // Get current winners
            $winners = calculateCurrentWinners($db);
            
            echo json_encode([
                'success' => true,
                'winners' => $winners,
                'calculation_date' => date('Y-m-d H:i:s')
            ]);
        }

    } catch (Exception $e) {
        throw new Exception("Failed to get prize distribution: " . $e->getMessage());
    }
}

function handleCalculateWinners($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $adminId = $input['admin_id'] ?? 'admin';
        
        // Calculate final winners
        $winners = calculateCurrentWinners($db);
        
        if (empty($winners)) {
            throw new Exception("No qualified participants found");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Clear existing prize records
            $db->exec("DELETE FROM gold_diggers_prizes");
            
            // Insert winner records
            foreach ($winners as $winner) {
                if ($winner['rank'] <= 10 && $winner['qualified']) {
                    $query = "INSERT INTO gold_diggers_prizes (
                        rank, user_id, username, direct_sales_volume, 
                        direct_referrals_count, prize_amount, status,
                        calculated_by, calculated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'calculated', ?, NOW())";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $winner['rank'],
                        $winner['user_id'],
                        $winner['username'],
                        $winner['direct_sales_volume'],
                        $winner['direct_referrals_count'],
                        $winner['bonus_amount'],
                        $adminId
                    ]);
                }
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Winners calculated successfully',
                'winners_count' => count($winners),
                'total_prize_amount' => array_sum(array_column($winners, 'bonus_amount'))
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        throw new Exception("Failed to calculate winners: " . $e->getMessage());
    }
}

function handleDistributePrizes($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $adminId = $input['admin_id'] ?? 'admin';
        $prizeIds = $input['prize_ids'] ?? [];
        
        if (empty($prizeIds)) {
            throw new Exception("No prizes selected for distribution");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            $distributedCount = 0;
            
            foreach ($prizeIds as $prizeId) {
                // Get prize details
                $query = "SELECT * FROM gold_diggers_prizes WHERE id = ? AND status = 'calculated'";
                $stmt = $db->prepare($query);
                $stmt->execute([$prizeId]);
                $prize = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$prize) {
                    continue; // Skip if prize not found or already distributed
                }
                
                // Create commission transaction for the prize
                $commissionQuery = "INSERT INTO commission_transactions (
                    plan_id, referrer_user_id, referred_user_id, investment_id,
                    level, commission_percentage, investment_amount, commission_usdt,
                    commission_nft, status, transaction_type, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'gold_diggers_prize', ?, NOW())";
                
                $stmt = $db->prepare($commissionQuery);
                $stmt->execute([
                    1, // Default plan ID
                    $prize['user_id'],
                    $prize['user_id'], // Self-referral for prize
                    null, // No specific investment
                    0, // Special level for prizes
                    0, // No percentage for prizes
                    0, // No investment amount
                    $prize['prize_amount'],
                    0, // No NFT for prizes
                    "Gold Diggers Club Prize - Rank #{$prize['rank']}"
                ]);
                
                // Update prize status
                $updateQuery = "UPDATE gold_diggers_prizes SET 
                    status = 'distributed',
                    distributed_by = ?,
                    distributed_at = NOW()
                    WHERE id = ?";
                
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$adminId, $prizeId]);
                
                $distributedCount++;
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Successfully distributed $distributedCount prizes",
                'distributed_count' => $distributedCount
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        throw new Exception("Failed to distribute prizes: " . $e->getMessage());
    }
}

function calculateCurrentWinners($db) {
    try {
        // Get leaderboard data
        $leaderboardQuery = "
            SELECT
                u.id as user_id,
                u.username,
                u.full_name,
                COALESCE(rr_stats.total_volume, 0) as direct_sales_volume,
                COALESCE(rr_stats.referral_count, 0) as direct_referrals_count,
                CASE
                    WHEN COALESCE(rr_stats.total_volume, 0) >= 2500 THEN 1
                    ELSE 0
                END as qualified
            FROM users u
            LEFT JOIN (
                SELECT
                    referrer_user_id,
                    COALESCE(SUM(investment_amount), 0) as total_volume,
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
        
        $winners = [];
        
        foreach ($results as $index => $entry) {
            $rank = $index + 1;
            $bonusAmount = calculateBonusAmount($rank, $entry['qualified']);
            
            $winners[] = [
                'rank' => $rank,
                'user_id' => $entry['user_id'],
                'username' => $entry['username'],
                'full_name' => $entry['full_name'],
                'direct_sales_volume' => (float)$entry['direct_sales_volume'],
                'direct_referrals_count' => (int)$entry['direct_referrals_count'],
                'bonus_amount' => $bonusAmount,
                'qualified' => (bool)$entry['qualified']
            ];
        }
        
        return $winners;
        
    } catch (Exception $e) {
        throw new Exception("Failed to calculate winners: " . $e->getMessage());
    }
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

function getPresaleStatus($db) {
    try {
        $query = "SELECT 
            COUNT(*) as total_investments,
            SUM(amount) as total_raised,
            MAX(created_at) as last_investment_date
        FROM aureus_investments 
        WHERE status IN ('confirmed', 'active', 'completed')";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalPacksAvailable = 200000; // Total NFT supply
        $totalInvestments = (int)($stats['total_investments'] ?? 0);
        $totalRaised = (float)($stats['total_raised'] ?? 0);
        $progress = $totalPacksAvailable > 0 ? ($totalInvestments / $totalPacksAvailable) * 100 : 0;
        
        return [
            'total_investments' => $totalInvestments,
            'total_raised' => $totalRaised,
            'progress_percentage' => round($progress, 2),
            'is_active' => $totalInvestments < $totalPacksAvailable,
            'last_investment_date' => $stats['last_investment_date']
        ];
        
    } catch (Exception $e) {
        return [
            'total_investments' => 0,
            'total_raised' => 0,
            'progress_percentage' => 0,
            'is_active' => true,
            'last_investment_date' => null
        ];
    }
}

// Create gold_diggers_prizes table if it doesn't exist
function createGoldDiggersPrizesTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS gold_diggers_prizes (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        rank INT NOT NULL,
        user_id VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL,
        direct_sales_volume DECIMAL(15,6) NOT NULL,
        direct_referrals_count INT NOT NULL,
        prize_amount DECIMAL(15,6) NOT NULL,
        status ENUM('calculated', 'distributed', 'cancelled') DEFAULT 'calculated',
        calculated_by VARCHAR(36) NULL,
        calculated_at TIMESTAMP NULL,
        distributed_by VARCHAR(36) NULL,
        distributed_at TIMESTAMP NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_rank (rank),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_calculated_at (calculated_at),
        INDEX idx_distributed_at (distributed_at),
        
        UNIQUE KEY idx_rank_unique (rank)
    )";
    
    try {
        $db->exec($query);
    } catch (PDOException $e) {
        error_log("Gold Diggers prizes table creation: " . $e->getMessage());
    }
}

// Initialize the table
createGoldDiggersPrizesTable($db);
?>
