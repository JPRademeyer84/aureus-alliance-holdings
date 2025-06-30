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
            handleGetPendingPayments($db);
            break;
        case 'POST':
            handleCreateManualPayment($db);
            break;
        case 'PUT':
            handleVerifyPayment($db);
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

function handleGetPendingPayments($db) {
    try {
        $query = "SELECT 
            ai.*,
            u.username,
            u.email,
            pp.name as package_name,
            pp.shares,
            mvp.verification_status,
            mvp.admin_notes,
            mvp.verified_by,
            mvp.verified_at,
            mvp.payment_proof_url
        FROM aureus_investments ai
        LEFT JOIN users u ON ai.user_id = u.id
        LEFT JOIN participation_packages pp ON ai.package_id = pp.id
        LEFT JOIN manual_verification_payments mvp ON ai.id = mvp.investment_id
        WHERE ai.status IN ('pending', 'pending_verification')
        ORDER BY ai.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'payments' => $payments,
            'count' => count($payments)
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to fetch pending payments: " . $e->getMessage());
    }
}

function handleCreateManualPayment($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['investment_id']) || empty($input['user_id'])) {
            throw new Exception("Investment ID and user ID are required");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Create manual verification record
            $query = "INSERT INTO manual_verification_payments (
                investment_id, user_id, payment_method, amount_usd,
                wallet_address, transaction_hash, payment_proof_url,
                verification_status, admin_notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                $input['investment_id'],
                $input['user_id'],
                $input['payment_method'] ?? 'crypto',
                $input['amount_usd'],
                $input['wallet_address'] ?? null,
                $input['transaction_hash'] ?? null,
                $input['payment_proof_url'] ?? null,
                $input['admin_notes'] ?? 'Manual payment verification required'
            ]);
            
            // Update investment status
            $updateQuery = "UPDATE aureus_investments SET 
                status = 'pending_verification', 
                updated_at = NOW() 
                WHERE id = ?";
            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$input['investment_id']]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Manual payment verification created successfully'
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        throw new Exception("Failed to create manual payment: " . $e->getMessage());
    }
}

function handleVerifyPayment($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['investment_id']) || empty($input['action'])) {
            throw new Exception("Investment ID and action are required");
        }
        
        $investmentId = $input['investment_id'];
        $action = $input['action']; // 'approve' or 'reject'
        $adminId = $input['admin_id'] ?? 'admin';
        $adminNotes = $input['admin_notes'] ?? '';
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            if ($action === 'approve') {
                // Update manual verification record
                $query = "UPDATE manual_verification_payments SET 
                    verification_status = 'approved',
                    verified_by = ?,
                    verified_at = NOW(),
                    admin_notes = ?
                    WHERE investment_id = ?";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$adminId, $adminNotes, $investmentId]);
                
                // Update investment status to confirmed
                $updateQuery = "UPDATE aureus_investments SET 
                    status = 'confirmed', 
                    confirmed_at = NOW(),
                    updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$investmentId]);
                
                // Process commissions
                processInvestmentCommissions($db, $investmentId);
                
                $message = 'Payment approved and investment confirmed';
                
            } else if ($action === 'reject') {
                // Update manual verification record
                $query = "UPDATE manual_verification_payments SET 
                    verification_status = 'rejected',
                    verified_by = ?,
                    verified_at = NOW(),
                    admin_notes = ?
                    WHERE investment_id = ?";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$adminId, $adminNotes, $investmentId]);
                
                // Update investment status to cancelled
                $updateQuery = "UPDATE aureus_investments SET 
                    status = 'cancelled', 
                    updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$investmentId]);
                
                $message = 'Payment rejected and investment cancelled';
            } else {
                throw new Exception("Invalid action: $action");
            }
            
            // Log the verification action
            logPaymentVerification($db, $investmentId, $action, $adminId, $adminNotes);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        throw new Exception("Failed to verify payment: " . $e->getMessage());
    }
}

function processInvestmentCommissions($db, $investmentId) {
    try {
        // Get investment details
        $query = "SELECT * FROM aureus_investments WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$investmentId]);
        $investment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$investment) {
            throw new Exception("Investment not found");
        }
        
        // Get user's referral chain
        $referralChain = getReferralChain($db, $investment['user_id']);
        
        if (empty($referralChain)) {
            return; // No referrals to process
        }
        
        $commissionRates = [1 => 12.0, 2 => 5.0, 3 => 3.0];
        $investmentAmount = (float)$investment['amount'];
        
        foreach ($referralChain as $level => $referrerId) {
            if ($level > 3) break;
            
            $commissionRate = $commissionRates[$level];
            $commissionAmount = $investmentAmount * ($commissionRate / 100);
            
            // Create commission record
            $query = "INSERT INTO commission_transactions (
                plan_id, referrer_user_id, referred_user_id, investment_id,
                level, commission_percentage, investment_amount, commission_usdt,
                commission_nft, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $nftCommission = floor($commissionAmount / 5); // $5 per NFT pack
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                1, // Default plan ID
                $referrerId,
                $investment['user_id'],
                $investmentId,
                $level,
                $commissionRate,
                $investmentAmount,
                $commissionAmount,
                $nftCommission
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Failed to process commissions for investment $investmentId: " . $e->getMessage());
        // Don't fail the main operation if commission processing fails
    }
}

function getReferralChain($db, $userId) {
    $chain = [];
    $currentUserId = $userId;
    $level = 1;
    
    while ($level <= 3) {
        $query = "SELECT referred_by FROM users WHERE id = ? AND referred_by IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute([$currentUserId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['referred_by']) {
            break;
        }
        
        $chain[$level] = $result['referred_by'];
        $currentUserId = $result['referred_by'];
        $level++;
    }
    
    return $chain;
}

function logPaymentVerification($db, $investmentId, $action, $adminId, $notes) {
    $query = "INSERT INTO payment_verification_log (
        investment_id, action, admin_id, admin_notes, created_at
    ) VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$investmentId, $action, $adminId, $notes]);
}

// Create manual verification payments table if it doesn't exist
function createManualVerificationTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS manual_verification_payments (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        investment_id VARCHAR(36) NOT NULL,
        user_id VARCHAR(255) NOT NULL,
        payment_method ENUM('crypto', 'bank', 'manual') DEFAULT 'crypto',
        amount_usd DECIMAL(15,6) NOT NULL,
        wallet_address VARCHAR(255) NULL,
        transaction_hash VARCHAR(255) NULL,
        payment_proof_url VARCHAR(500) NULL,
        verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        verified_by VARCHAR(36) NULL,
        verified_at TIMESTAMP NULL,
        admin_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_investment_id (investment_id),
        INDEX idx_user_id (user_id),
        INDEX idx_verification_status (verification_status),
        INDEX idx_verified_by (verified_by),
        
        FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE
    )";
    
    try {
        $db->exec($query);
    } catch (PDOException $e) {
        error_log("Manual verification table creation: " . $e->getMessage());
    }
}

// Create payment verification log table if it doesn't exist
function createPaymentVerificationLogTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS payment_verification_log (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        investment_id VARCHAR(36) NOT NULL,
        action VARCHAR(50) NOT NULL,
        admin_id VARCHAR(36) NOT NULL,
        admin_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_investment_id (investment_id),
        INDEX idx_admin_id (admin_id),
        INDEX idx_created_at (created_at),
        
        FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE
    )";
    
    try {
        $db->exec($query);
    } catch (PDOException $e) {
        error_log("Payment verification log table creation: " . $e->getMessage());
    }
}

// Initialize tables
createManualVerificationTable($db);
createPaymentVerificationLogTable($db);
?>
