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
            handleGetBankPayments($db);
            break;
        case 'POST':
            handleCreateBankPayment($db);
            break;
        case 'PUT':
            handleUpdateBankPayment($db);
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

function handleGetBankPayments($db) {
    try {
        $userId = $_GET['user_id'] ?? null;
        $investmentId = $_GET['investment_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $isAdmin = isset($_GET['admin']) && $_GET['admin'] === 'true';
        
        $query = "SELECT 
            bpt.*,
            ai.package_name,
            ai.amount as investment_amount,
            u.username,
            u.email,
            cba.account_name,
            cba.bank_name,
            cba.account_number,
            au.username as verified_by_username
        FROM bank_payment_transactions bpt
        LEFT JOIN aureus_investments ai ON bpt.investment_id = ai.id
        LEFT JOIN users u ON bpt.user_id = u.id
        LEFT JOIN company_bank_accounts cba ON bpt.bank_account_id = cba.id
        LEFT JOIN admin_users au ON bpt.verified_by = au.id
        WHERE 1=1";
        
        $params = [];
        
        if ($userId && !$isAdmin) {
            $query .= " AND bpt.user_id = ?";
            $params[] = $userId;
        }
        
        if ($investmentId) {
            $query .= " AND bpt.investment_id = ?";
            $params[] = $investmentId;
        }
        
        if ($status) {
            $query .= " AND bpt.payment_status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY bpt.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'payments' => $payments,
            'count' => count($payments)
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to fetch bank payments: " . $e->getMessage());
    }
}

function handleCreateBankPayment($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($input['investment_id']) || empty($input['user_id']) || empty($input['amount_usd'])) {
            throw new Exception("Investment ID, user ID, and amount are required");
        }
        
        // Get investment details
        $investment = getInvestmentDetails($db, $input['investment_id'], $input['user_id']);
        if (!$investment) {
            throw new Exception("Investment not found or access denied");
        }
        
        // Get user's country and bank account
        $countryCode = $input['country_code'] ?? 'USA';
        $currencyCode = $input['currency_code'] ?? 'USD';
        
        $bankAccount = getBankAccountForPayment($db, $countryCode, $currencyCode);
        if (!$bankAccount) {
            throw new Exception("No bank account available for this country/currency");
        }
        
        // Generate unique reference number
        $referenceNumber = generateReferenceNumber($db);
        
        // Calculate amounts and exchange rate
        $amountUSD = (float)$input['amount_usd'];
        $exchangeRate = (float)($input['exchange_rate'] ?? 1.0);
        $amountLocal = $amountUSD * $exchangeRate;
        
        // Set expiration date (7 days from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        // Create bank payment transaction
        $query = "INSERT INTO bank_payment_transactions (
            investment_id, user_id, bank_account_id, reference_number,
            amount_usd, amount_local, local_currency, exchange_rate,
            payment_status, verification_status, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $input['investment_id'],
            $input['user_id'],
            $bankAccount['id'],
            $referenceNumber,
            $amountUSD,
            $amountLocal,
            $currencyCode,
            $exchangeRate,
            'pending',
            'pending',
            $expiresAt
        ]);
        
        $paymentId = $db->lastInsertId();
        
        // Update investment status
        updateInvestmentStatus($db, $input['investment_id'], 'pending_bank_payment');
        
        echo json_encode([
            'success' => true,
            'message' => 'Bank payment created successfully',
            'payment_id' => $paymentId,
            'reference_number' => $referenceNumber,
            'bank_account' => $bankAccount,
            'payment_details' => [
                'amount_usd' => $amountUSD,
                'amount_local' => $amountLocal,
                'currency' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'expires_at' => $expiresAt
            ]
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to create bank payment: " . $e->getMessage());
    }
}

function handleUpdateBankPayment($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['payment_id'])) {
            throw new Exception("Payment ID is required");
        }
        
        $paymentId = $input['payment_id'];
        $action = $input['action'] ?? 'update';
        
        switch ($action) {
            case 'submit_proof':
                handleSubmitPaymentProof($db, $paymentId, $input);
                break;
            case 'verify_payment':
                handleVerifyPayment($db, $paymentId, $input);
                break;
            case 'reject_payment':
                handleRejectPayment($db, $paymentId, $input);
                break;
            default:
                handleGeneralUpdate($db, $paymentId, $input);
        }

    } catch (Exception $e) {
        throw new Exception("Failed to update bank payment: " . $e->getMessage());
    }
}

function handleSubmitPaymentProof($db, $paymentId, $input) {
    // Validate payment exists and belongs to user
    $payment = getBankPaymentDetails($db, $paymentId, $input['user_id'] ?? null);
    if (!$payment) {
        throw new Exception("Payment not found or access denied");
    }
    
    if ($payment['payment_status'] !== 'pending') {
        throw new Exception("Payment proof can only be submitted for pending payments");
    }
    
    // Update payment with submitted details
    $query = "UPDATE bank_payment_transactions SET 
        sender_name = ?, sender_account = ?, sender_bank = ?,
        transfer_date = ?, bank_reference = ?, payment_proof_path = ?,
        payment_status = 'submitted', submitted_at = NOW(),
        verification_status = 'reviewing'
        WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $input['sender_name'] ?? null,
        $input['sender_account'] ?? null,
        $input['sender_bank'] ?? null,
        $input['transfer_date'] ?? null,
        $input['bank_reference'] ?? null,
        $input['payment_proof_path'] ?? null,
        $paymentId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment proof submitted successfully',
        'status' => 'submitted'
    ]);
}

function handleVerifyPayment($db, $paymentId, $input) {
    // Admin verification
    if (empty($input['admin_id'])) {
        throw new Exception("Admin ID is required for verification");
    }
    
    $payment = getBankPaymentDetails($db, $paymentId);
    if (!$payment) {
        throw new Exception("Payment not found");
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Update payment status
        $query = "UPDATE bank_payment_transactions SET 
            payment_status = 'confirmed', verification_status = 'approved',
            verified_by = ?, verified_at = NOW(), confirmed_at = NOW(),
            verification_notes = ?
            WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $input['admin_id'],
            $input['verification_notes'] ?? 'Payment verified by admin',
            $paymentId
        ]);
        
        // Update investment status
        updateInvestmentStatus($db, $payment['investment_id'], 'confirmed');
        
        // Calculate and create commissions
        calculateBankPaymentCommissions($db, $paymentId, $payment);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified and confirmed successfully',
            'status' => 'confirmed'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function generateReferenceNumber($db) {
    $prefix = 'AAH-BP-';
    $timestamp = date('Ymd');
    
    // Get next sequence number for today
    $query = "SELECT COUNT(*) as count FROM bank_payment_transactions 
              WHERE reference_number LIKE ? AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute([$prefix . $timestamp . '%']);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    
    return $prefix . $timestamp . '-' . $sequence;
}

function getBankAccountForPayment($db, $countryCode, $currencyCode) {
    $query = "SELECT * FROM company_bank_accounts 
              WHERE (country_code = ? OR is_default = TRUE) 
              AND currency_code = ? AND is_active = TRUE 
              ORDER BY (country_code = ?) DESC, is_default DESC LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$countryCode, $currencyCode, $countryCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getInvestmentDetails($db, $investmentId, $userId) {
    $query = "SELECT * FROM aureus_investments WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$investmentId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getBankPaymentDetails($db, $paymentId, $userId = null) {
    $query = "SELECT * FROM bank_payment_transactions WHERE id = ?";
    $params = [$paymentId];
    
    if ($userId) {
        $query .= " AND user_id = ?";
        $params[] = $userId;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateInvestmentStatus($db, $investmentId, $status) {
    $query = "UPDATE aureus_investments SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $investmentId]);
}

function calculateBankPaymentCommissions($db, $paymentId, $payment) {
    // Get referral chain for the user
    $referralChain = getReferralChain($db, $payment['user_id']);
    
    $commissionRates = [1 => 12.0, 2 => 5.0, 3 => 3.0];
    
    foreach ($referralChain as $level => $referrerId) {
        if ($level > 3) break;
        
        $commissionRate = $commissionRates[$level];
        $commissionAmountUSD = $payment['amount_usd'] * ($commissionRate / 100);
        
        // Create commission record (always paid in USDT)
        $query = "INSERT INTO bank_payment_commissions (
            bank_payment_id, investment_id, referrer_user_id, commission_level,
            commission_percentage, investment_amount_usd, commission_amount_usd,
            commission_amount_usdt, calculation_status, calculated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'calculated', NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $paymentId,
            $payment['investment_id'],
            $referrerId,
            $level,
            $commissionRate,
            $payment['amount_usd'],
            $commissionAmountUSD,
            $commissionAmountUSD, // Same as USD for USDT
            'calculated'
        ]);
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
?>
