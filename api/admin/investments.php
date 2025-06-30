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
            handleGetInvestments($db);
            break;
        case 'PUT':
            handleUpdateInvestment($db);
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

function handleGetInvestments($db) {
    try {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $query = "SELECT 
                ai.*,
                u.username,
                u.email,
                pp.name as package_name,
                pp.shares,
                bpt.reference_number as bank_reference,
                bpt.payment_status as bank_payment_status
            FROM aureus_investments ai
            LEFT JOIN users u ON ai.user_id = u.id
            LEFT JOIN participation_packages pp ON ai.package_id = pp.id
            LEFT JOIN bank_payment_transactions bpt ON ai.id = bpt.investment_id
            ORDER BY ai.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process investments to add calculated fields
            foreach ($investments as &$investment) {
                // Calculate ROI amount (this would be based on package terms)
                $investment['roi_amount'] = $investment['amount'] * 1.5; // Example: 50% ROI
                
                // Set delivery dates
                $investment['nft_delivery_date'] = date('Y-m-d', strtotime($investment['created_at'] . ' +180 days'));
                $investment['roi_delivery_date'] = date('Y-m-d', strtotime($investment['created_at'] . ' +180 days'));
                
                // Determine payment method
                if ($investment['bank_reference']) {
                    $investment['payment_method'] = 'bank';
                } elseif ($investment['transaction_hash']) {
                    $investment['payment_method'] = 'crypto';
                } else {
                    $investment['payment_method'] = 'manual';
                }
            }
            
            echo json_encode([
                'success' => true,
                'investments' => $investments,
                'count' => count($investments)
            ]);
        }
        
    } catch (Exception $e) {
        throw new Exception("Failed to fetch investments: " . $e->getMessage());
    }
}

function handleUpdateInvestment($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['action']) || empty($input['investment_id'])) {
            throw new Exception("Action and investment ID are required");
        }
        
        $action = $input['action'];
        $investmentId = $input['investment_id'];
        $adminId = $input['admin_id'] ?? 'admin';
        $adminNotes = $input['admin_notes'] ?? '';
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            switch ($action) {
                case 'update_status':
                    $newStatus = $input['status'];
                    updateInvestmentStatus($db, $investmentId, $newStatus, $adminId, $adminNotes);
                    break;
                    
                default:
                    throw new Exception("Unknown action: $action");
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Investment $action completed successfully"
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        throw new Exception("Failed to update investment: " . $e->getMessage());
    }
}

function updateInvestmentStatus($db, $investmentId, $newStatus, $adminId, $adminNotes) {
    // Get current investment details
    $query = "SELECT * FROM aureus_investments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$investmentId]);
    $investment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$investment) {
        throw new Exception("Investment not found");
    }
    
    // Update investment status
    $updateQuery = "UPDATE aureus_investments SET 
        status = ?, 
        updated_at = NOW()";
    
    $params = [$newStatus];
    
    // Add confirmation timestamp for confirmed status
    if ($newStatus === 'confirmed') {
        $updateQuery .= ", confirmed_at = NOW()";
    }
    
    $updateQuery .= " WHERE id = ?";
    $params[] = $investmentId;
    
    $stmt = $db->prepare($updateQuery);
    $stmt->execute($params);
    
    // Log the status change
    logInvestmentStatusChange($db, $investmentId, $investment['status'], $newStatus, $adminId, $adminNotes);
    
    // Process commissions if confirming investment
    if ($newStatus === 'confirmed' && $investment['status'] === 'pending') {
        processInvestmentCommissions($db, $investmentId, $investment);

        // Send investment confirmation email
        sendInvestmentConfirmationEmail($db, $investmentId, $investment);
    }

    // Generate certificate if activating investment
    if ($newStatus === 'active' && $investment['status'] === 'confirmed') {
        triggerCertificateGeneration($db, $investmentId);
    }
}

function logInvestmentStatusChange($db, $investmentId, $oldStatus, $newStatus, $adminId, $notes) {
    $query = "INSERT INTO investment_status_log (
        investment_id, old_status, new_status, changed_by, admin_notes, created_at
    ) VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$investmentId, $oldStatus, $newStatus, $adminId, $notes]);
}

function processInvestmentCommissions($db, $investmentId, $investment) {
    try {
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
                $nftCommission,
                'pending'
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

function triggerCertificateGeneration($db, $investmentId) {
    try {
        // Check if certificate already exists
        $query = "SELECT id FROM share_certificates WHERE investment_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$investmentId]);
        
        if ($stmt->rowCount() > 0) {
            return; // Certificate already exists
        }
        
        // Get investment details
        $query = "SELECT ai.*, u.username, u.email, pp.name as package_name, pp.shares
                  FROM aureus_investments ai
                  LEFT JOIN users u ON ai.user_id = u.id
                  LEFT JOIN participation_packages pp ON ai.package_id = pp.id
                  WHERE ai.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$investmentId]);
        $investment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$investment) {
            throw new Exception("Investment not found for certificate generation");
        }
        
        // Generate certificate number
        $certificateNumber = generateCertificateNumber($db);
        
        // Create certificate record
        $query = "INSERT INTO share_certificates (
            certificate_number, investment_id, user_id, holder_name,
            share_quantity, share_value, certificate_type, legal_status,
            issue_date, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'share_certificate', 'valid', NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $certificateNumber,
            $investmentId,
            $investment['user_id'],
            $investment['username'],
            $investment['shares'],
            $investment['amount'],
        ]);
        
        error_log("Certificate generated for investment $investmentId: $certificateNumber");
        
    } catch (Exception $e) {
        error_log("Failed to generate certificate for investment $investmentId: " . $e->getMessage());
        // Don't fail the main operation if certificate generation fails
    }
}

function generateCertificateNumber($db) {
    $prefix = 'AAH-';
    $year = date('Y');

    // Get next sequence number for this year
    $query = "SELECT COUNT(*) as count FROM share_certificates
              WHERE certificate_number LIKE ? AND YEAR(created_at) = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$prefix . $year . '%', $year]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $sequence = str_pad($count + 1, 6, '0', STR_PAD_LEFT);

    return $prefix . $year . '-' . $sequence;
}

function sendInvestmentConfirmationEmail($db, $investmentId, $investment) {
    try {
        // Get user details
        $query = "SELECT u.email, u.username, pp.name as package_name, pp.shares
                  FROM users u
                  LEFT JOIN aureus_investments ai ON u.id = ai.user_id
                  LEFT JOIN participation_packages pp ON ai.package_id = pp.id
                  WHERE ai.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$investmentId]);
        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userDetails || !$userDetails['email']) {
            error_log("Cannot send investment confirmation email: user details not found for investment $investmentId");
            return;
        }

        // Prepare email data
        $emailData = [
            'username' => $userDetails['username'],
            'package_name' => $userDetails['package_name'],
            'amount' => $investment['amount'],
            'shares' => $userDetails['shares'],
            'investment_date' => date('Y-m-d'),
            'nft_delivery_date' => date('Y-m-d', strtotime('+180 days')),
            'roi_delivery_date' => date('Y-m-d', strtotime('+180 days'))
        ];

        // Send email using the email service
        $emailServiceUrl = 'http://localhost/aureus-angel-alliance/api/notifications/email-service.php';
        $postData = json_encode([
            'type' => 'investment_confirmation',
            'recipient' => $userDetails['email'],
            'data' => $emailData,
            'priority' => 'high'
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $postData
            ]
        ]);

        $result = file_get_contents($emailServiceUrl, false, $context);

        if ($result) {
            $response = json_decode($result, true);
            if ($response && $response['success']) {
                error_log("Investment confirmation email sent successfully for investment $investmentId");
            } else {
                error_log("Failed to send investment confirmation email: " . ($response['error'] ?? 'Unknown error'));
            }
        }

    } catch (Exception $e) {
        error_log("Error sending investment confirmation email for investment $investmentId: " . $e->getMessage());
    }
}

// Create investment status log table if it doesn't exist
function createInvestmentStatusLogTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS investment_status_log (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        investment_id VARCHAR(36) NOT NULL,
        old_status VARCHAR(50) NOT NULL,
        new_status VARCHAR(50) NOT NULL,
        changed_by VARCHAR(36) NOT NULL,
        admin_notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_investment_id (investment_id),
        INDEX idx_changed_by (changed_by),
        INDEX idx_created_at (created_at),
        
        FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE
    )";
    
    try {
        $db->exec($query);
    } catch (PDOException $e) {
        // Table might already exist, continue
        error_log("Investment status log table creation: " . $e->getMessage());
    }
}

// Initialize the status log table
createInvestmentStatusLogTable($db);
?>
