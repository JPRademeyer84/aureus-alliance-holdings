<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

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

class CommissionProcessor {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Process commissions for a new investment
     */
    public function processInvestmentCommissions($investmentId, $investorUserId, $investmentAmount, $packageName) {
        try {
            // Get the default commission plan
            $planQuery = "SELECT * FROM commission_plans WHERE is_default = TRUE AND is_active = TRUE LIMIT 1";
            $planStmt = $this->db->prepare($planQuery);
            $planStmt->execute();
            $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plan) {
                throw new Exception('No active commission plan found');
            }
            
            // Get referral relationships for this user (up to max levels)
            $referrers = $this->getReferralChain($investorUserId, $plan['max_levels']);
            
            if (empty($referrers)) {
                return [
                    'processed' => true,
                    'commissions_created' => 0,
                    'message' => 'No referrers found for this investment'
                ];
            }
            
            $commissionsCreated = 0;
            $this->db->beginTransaction();
            
            foreach ($referrers as $level => $referrer) {
                $levelNum = $level + 1; // Convert 0-based to 1-based
                
                // Get commission percentages for this level
                $usdtPercent = $plan["level_{$levelNum}_usdt_percent"];
                $nftPercent = $plan["level_{$levelNum}_nft_percent"];
                
                if ($usdtPercent <= 0 && $nftPercent <= 0) {
                    continue; // Skip if no commission for this level
                }
                
                // Calculate commission amounts
                $usdtCommission = ($investmentAmount * $usdtPercent) / 100;
                $nftCommission = intval(($investmentAmount * $nftPercent) / 100 / $plan['nft_pack_price']);
                
                // Create commission transaction record
                $this->createCommissionTransaction(
                    $plan['id'],
                    $referrer['user_id'],
                    $referrer['username'],
                    $investorUserId,
                    $this->getUsernameById($investorUserId),
                    $investmentId,
                    $investmentAmount,
                    $packageName,
                    $levelNum,
                    $usdtPercent,
                    $nftPercent,
                    $usdtCommission,
                    $nftCommission
                );
                
                $commissionsCreated++;
            }
            
            $this->db->commit();
            
            return [
                'processed' => true,
                'commissions_created' => $commissionsCreated,
                'message' => "Successfully created $commissionsCreated commission transactions"
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get the referral chain for a user up to specified levels
     */
    private function getReferralChain($userId, $maxLevels) {
        $referrers = [];
        $currentUserId = $userId;
        
        for ($level = 0; $level < $maxLevels; $level++) {
            $query = "SELECT referrer_user_id, referrer_username 
                     FROM referral_relationships 
                     WHERE referred_user_id = ? AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$currentUserId]);
            $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$referrer) {
                break; // No more referrers in the chain
            }
            
            $referrers[] = [
                'user_id' => $referrer['referrer_user_id'],
                'username' => $referrer['referrer_username'],
                'level' => $level + 1
            ];
            
            $currentUserId = $referrer['referrer_user_id'];
        }
        
        return $referrers;
    }
    
    /**
     * Create a commission transaction record
     */
    private function createCommissionTransaction($planId, $referrerUserId, $referrerUsername, 
                                               $referredUserId, $referredUsername, $investmentId, 
                                               $investmentAmount, $packageName, $level, 
                                               $usdtPercent, $nftPercent, $usdtAmount, $nftAmount) {
        
        $query = "INSERT INTO commission_transactions (
            commission_plan_id, referrer_user_id, referred_user_id,
            referrer_username, referred_username, investment_id,
            investment_amount, investment_package, commission_level,
            usdt_commission_percent, nft_commission_percent,
            usdt_commission_amount, nft_commission_amount,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $planId, $referrerUserId, $referredUserId,
            $referrerUsername, $referredUsername, $investmentId,
            $investmentAmount, $packageName, $level,
            $usdtPercent, $nftPercent, $usdtAmount, $nftAmount
        ]);
    }
    
    /**
     * Get username by user ID (could be wallet address)
     */
    private function getUsernameById($userId) {
        // First try users table
        $query = "SELECT username FROM users WHERE id = ? OR email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return $user['username'];
        }
        
        // If not found, it might be a wallet address - return truncated version
        if (strlen($userId) > 20) {
            return substr($userId, 0, 6) . '...' . substr($userId, -4);
        }
        
        return $userId;
    }
    
    /**
     * Track a new referral relationship
     */
    public function trackReferral($referrerUserId, $referredUserId, $referralCode = null, $source = 'direct_link') {
        try {
            // Check if relationship already exists
            $checkQuery = "SELECT id FROM referral_relationships 
                          WHERE referrer_user_id = ? AND referred_user_id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$referrerUserId, $referredUserId]);
            
            if ($checkStmt->fetch()) {
                return ['exists' => true, 'message' => 'Referral relationship already exists'];
            }
            
            // Get usernames
            $referrerUsername = $this->getUsernameById($referrerUserId);
            $referredUsername = $this->getUsernameById($referredUserId);
            
            // Create referral relationship
            $insertQuery = "INSERT INTO referral_relationships (
                referrer_user_id, referred_user_id, referrer_username, 
                referred_username, referral_code, referral_source,
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($insertQuery);
            $success = $stmt->execute([
                $referrerUserId, $referredUserId, $referrerUsername,
                $referredUsername, $referralCode, $source,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            if ($success) {
                return [
                    'created' => true,
                    'relationship_id' => $this->db->lastInsertId(),
                    'message' => 'Referral relationship created successfully'
                ];
            } else {
                throw new Exception('Failed to create referral relationship');
            }
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    // Tables should already exist - no automatic creation
    
    // Run commission tables migration if needed
    $migrationSql = file_get_contents('../../database/migrations/create_commission_tables.sql');
    if ($migrationSql) {
        $db->exec($migrationSql);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'process_investment';

    $processor = new CommissionProcessor($db);

    switch ($action) {
        case 'process_investment':
            // Process commissions for a new investment
            $required = ['investment_id', 'investor_user_id', 'investment_amount', 'package_name'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    sendErrorResponse("Field '$field' is required", 400);
                }
            }
            
            $result = $processor->processInvestmentCommissions(
                $input['investment_id'],
                $input['investor_user_id'],
                floatval($input['investment_amount']),
                $input['package_name']
            );
            
            sendResponse($result, $result['message']);
            break;
            
        case 'track_referral':
            // Track a new referral relationship
            $required = ['referrer_user_id', 'referred_user_id'];
            foreach ($required as $field) {
                if (!isset($input[$field])) {
                    sendErrorResponse("Field '$field' is required", 400);
                }
            }
            
            $result = $processor->trackReferral(
                $input['referrer_user_id'],
                $input['referred_user_id'],
                $input['referral_code'] ?? null,
                $input['source'] ?? 'direct_link'
            );
            
            sendResponse($result, $result['message']);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Commission Processing Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
