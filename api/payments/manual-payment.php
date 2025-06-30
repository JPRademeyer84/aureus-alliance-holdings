<?php
require_once '../config/database.php';
require_once '../utils/response.php';
require_once '../utils/validation.php';
require_once '../utils/file-upload.php';
require_once '../utils/manual-payment-notifications.php';
require_once '../utils/manual-payment-security.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'POST':
            handleCreateManualPayment($db);
            break;
        case 'GET':
            handleGetManualPayment($db);
            break;
        case 'PUT':
            handleUpdateManualPayment($db);
            break;
        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleCreateManualPayment($db) {
    // Start session to get user info
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('Authentication required', 401);
    }

    $userId = $_SESSION['user_id'];
    
    // Validate required fields
    $requiredFields = ['amount_usd', 'chain', 'company_wallet', 'sender_name'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            sendErrorResponse("Field '$field' is required", 400);
        }
    }

    $amountUSD = (float)$_POST['amount_usd'];
    $chain = trim($_POST['chain']);
    $companyWallet = trim($_POST['company_wallet']);
    $senderName = trim($_POST['sender_name']);
    $senderWallet = trim($_POST['sender_wallet'] ?? '');
    $transactionHash = trim($_POST['transaction_hash'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validate amount
    if ($amountUSD <= 0 || $amountUSD > 1000000) {
        sendErrorResponse('Invalid payment amount', 400);
    }

    // Validate chain
    $allowedChains = ['ethereum', 'bsc', 'polygon', 'tron'];
    if (!in_array($chain, $allowedChains)) {
        sendErrorResponse('Invalid blockchain network', 400);
    }

    // Security validation
    $securityCheck = validateManualPaymentSecurity($userId, $amountUSD, $senderName, $senderWallet);
    if (!$securityCheck['valid']) {
        // Log security violation
        $security = new ManualPaymentSecurity();
        $security->logSecurityEvent($userId, 'security_violation', [
            'violations' => $securityCheck['violations'],
            'risk_level' => $securityCheck['risk_level'],
            'amount' => $amountUSD
        ], $securityCheck['risk_level']);

        sendErrorResponse('Payment submission blocked: ' . implode(', ', $securityCheck['violations']), 429);
    }

    // Handle file upload
    $paymentProofPath = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['payment_proof'], 'payment_proofs');
        if ($uploadResult['success']) {
            $paymentProofPath = $uploadResult['file_path'];
        } else {
            sendErrorResponse('File upload failed: ' . $uploadResult['error'], 400);
        }
    } else {
        sendErrorResponse('Payment proof file is required', 400);
    }

    // Generate unique payment ID
    $paymentId = 'MP_' . strtoupper(uniqid());
    
    // Set expiration date (7 days from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

    try {
        $db->beginTransaction();

        // Insert manual payment record
        $query = "INSERT INTO manual_payment_transactions (
            payment_id, user_id, amount_usd, chain, company_wallet_address,
            sender_name, sender_wallet_address, transaction_hash, notes,
            payment_proof_path, payment_status, verification_status,
            expires_at, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())";
        
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            $paymentId,
            $userId,
            $amountUSD,
            $chain,
            $companyWallet,
            $senderName,
            $senderWallet,
            $transactionHash,
            $notes,
            $paymentProofPath,
            $expiresAt
        ]);

        if (!$success) {
            throw new Exception('Failed to create manual payment record');
        }

        // Log the manual payment creation
        $auditQuery = "INSERT INTO security_audit_log (
            event_type, user_id, event_details, security_level,
            ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $auditStmt = $db->prepare($auditQuery);
        $auditStmt->execute([
            'manual_payment_created',
            $userId,
            json_encode([
                'payment_id' => $paymentId,
                'amount_usd' => $amountUSD,
                'chain' => $chain,
                'sender_name' => $senderName
            ]),
            'medium',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        $db->commit();

        // Send notification email
        sendManualPaymentNotification($paymentId, 'submitted');

        // Attempt REAL blockchain verification
        $autoVerificationResult = attemptRealBlockchainVerification($paymentId, $db, [
            'payment_id' => $paymentId,
            'transaction_hash' => $transactionHash,
            'sender_wallet_address' => $senderWallet,
            'company_wallet_address' => $companyWallet,
            'amount_usd' => $amountUSD,
            'chain' => $chain
        ]);

        // Send success response
        sendSuccessResponse([
            'payment_id' => $paymentId,
            'status' => $autoVerificationResult['auto_approved'] ? 'approved' : 'pending',
            'expires_at' => $expiresAt,
            'auto_verified' => $autoVerificationResult['auto_approved'],
            'verification_confidence' => $autoVerificationResult['confidence'],
            'message' => $autoVerificationResult['auto_approved'] ?
                        'Payment automatically verified and approved!' :
                        'Manual payment submitted for review'
        ], 'Manual payment created successfully');

    } catch (Exception $e) {
        $db->rollback();
        
        // Clean up uploaded file if database operation failed
        if ($paymentProofPath && file_exists($paymentProofPath)) {
            unlink($paymentProofPath);
        }
        
        throw $e;
    }
}

function handleGetManualPayment($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('Authentication required', 401);
    }

    $userId = $_SESSION['user_id'];
    $paymentId = $_GET['payment_id'] ?? null;

    if ($paymentId) {
        // Get specific payment
        $query = "SELECT * FROM manual_payment_transactions 
                  WHERE payment_id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$paymentId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            sendErrorResponse('Payment not found', 404);
        }

        // Remove sensitive information
        unset($payment['payment_proof_path']);
        
        sendSuccessResponse($payment, 'Payment details retrieved successfully');
    } else {
        // Get all payments for user
        $query = "SELECT payment_id, amount_usd, chain, payment_status, 
                         verification_status, created_at, expires_at
                  FROM manual_payment_transactions 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse($payments, 'Payment history retrieved successfully');
    }
}

function handleUpdateManualPayment($db) {
    // This endpoint is for admin use only
    session_start();
    
    if (!isset($_SESSION['admin_id'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['payment_id']) || !isset($input['action'])) {
        sendErrorResponse('Payment ID and action are required', 400);
    }

    $paymentId = $input['payment_id'];
    $action = $input['action']; // 'approve' or 'reject'
    $adminId = $_SESSION['admin_id'];
    $notes = $input['notes'] ?? '';

    if (!in_array($action, ['approve', 'reject'])) {
        sendErrorResponse('Invalid action. Must be approve or reject', 400);
    }

    try {
        $db->beginTransaction();

        // Get payment details
        $query = "SELECT * FROM manual_payment_transactions WHERE payment_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        if ($payment['verification_status'] !== 'pending') {
            throw new Exception('Payment has already been processed');
        }

        // Update payment status
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $paymentStatus = $action === 'approve' ? 'confirmed' : 'failed';

        $updateQuery = "UPDATE manual_payment_transactions SET 
                        verification_status = ?, payment_status = ?,
                        verified_by = ?, verified_at = NOW(),
                        verification_notes = ?
                        WHERE payment_id = ?";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([
            $newStatus,
            $paymentStatus,
            $adminId,
            $notes,
            $paymentId
        ]);

        // If approved, create investment records
        if ($action === 'approve') {
            // This would integrate with the investment creation system
            // For now, we'll just log the approval
        }

        // Log admin action
        $auditQuery = "INSERT INTO security_audit_log (
            event_type, admin_id, event_details, security_level,
            ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $auditStmt = $db->prepare($auditQuery);
        $auditStmt->execute([
            'manual_payment_' . $action,
            $adminId,
            json_encode([
                'payment_id' => $paymentId,
                'user_id' => $payment['user_id'],
                'amount_usd' => $payment['amount_usd'],
                'notes' => $notes
            ]),
            'high',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        $db->commit();

        // Send notification email
        sendManualPaymentNotification($paymentId, $action, $notes);

        sendSuccessResponse([
            'payment_id' => $paymentId,
            'status' => $newStatus,
            'action' => $action
        ], "Payment {$action}d successfully");

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Attempt automatic verification of payment
 */
function attemptAutoVerification($paymentId, $db) {
    try {
        // Get payment details
        $query = "SELECT * FROM manual_payment_transactions WHERE payment_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            return ['auto_approved' => false, 'confidence' => 0, 'reason' => 'Payment not found'];
        }

        $confidence = 0;
        $checks = [];

        // Basic validation checks
        if (!empty($payment['transaction_hash'])) {
            $confidence += 30;
            $checks[] = 'Transaction hash provided';
        }

        if (!empty($payment['sender_wallet_address'])) {
            $confidence += 20;
            $checks[] = 'Sender wallet provided';
        }

        // Validate wallet address format
        if (function_exists('WalletSecurity::validateWalletAddress')) {
            $walletValid = WalletSecurity::validateWalletAddress(
                $payment['company_wallet_address'],
                $payment['chain']
            );
            if ($walletValid) {
                $confidence += 25;
                $checks[] = 'Company wallet format valid';
            }
        }

        // Amount validation
        if ($payment['amount_usd'] > 0 && $payment['amount_usd'] <= 50000) {
            $confidence += 25;
            $checks[] = 'Amount within acceptable range';
        }

        // Auto-approve if confidence is high enough
        $autoApprove = $confidence >= 80;

        if ($autoApprove) {
            // Update payment status to approved
            $updateQuery = "UPDATE manual_payment_transactions SET
                              payment_status = 'approved',
                              verification_status = 'auto_approved',
                              auto_verified_at = NOW(),
                              verification_confidence = ?,
                              verification_reason = ?
                            WHERE payment_id = ?";

            $stmt = $db->prepare($updateQuery);
            $stmt->execute([
                $confidence,
                'Automatically approved: ' . implode(', ', $checks),
                $paymentId
            ]);
        }

        return [
            'auto_approved' => $autoApprove,
            'confidence' => $confidence,
            'checks' => $checks,
            'reason' => $autoApprove ? 'Auto-approved' : 'Requires manual review'
        ];

    } catch (Exception $e) {
        error_log("Auto verification failed for payment $paymentId: " . $e->getMessage());
        return [
            'auto_approved' => false,
            'confidence' => 0,
            'reason' => 'Auto verification error: ' . $e->getMessage()
        ];
    }
}

/**
 * Attempt REAL blockchain verification
 */
function attemptRealBlockchainVerification($paymentId, $db, $paymentData) {
    try {
        // Include the blockchain verification class
        require_once 'blockchain-verification.php';

        $verifier = new BlockchainVerification($db);
        $blockchainResult = $verifier->verifyTransaction($paymentData);

        if ($blockchainResult['verified']) {
            // FULL VERIFICATION PASSED - Auto approve
            $updateQuery = "UPDATE manual_payment_transactions SET
                              payment_status = 'approved',
                              verification_status = 'blockchain_verified',
                              auto_verified_at = NOW(),
                              verification_confidence = 100,
                              verification_reason = 'Blockchain verification successful'
                            WHERE payment_id = ?";

            $stmt = $db->prepare($updateQuery);
            $stmt->execute([$paymentId]);

            return [
                'auto_approved' => true,
                'confidence' => 100,
                'method' => 'blockchain_verified',
                'checks' => $blockchainResult['checks'],
                'reason' => 'Transaction verified on blockchain'
            ];
        } else {
            // Blockchain verification failed - Manual review required
            $updateQuery = "UPDATE manual_payment_transactions SET
                              verification_status = 'manual_review_required',
                              verification_confidence = 0,
                              verification_reason = ?
                            WHERE payment_id = ?";

            $stmt = $db->prepare($updateQuery);
            $stmt->execute([
                'Blockchain verification failed: ' . implode(', ', $blockchainResult['errors']),
                $paymentId
            ]);

            return [
                'auto_approved' => false,
                'confidence' => 0,
                'method' => 'blockchain_failed',
                'errors' => $blockchainResult['errors'],
                'reason' => 'Blockchain verification failed - requires manual review'
            ];
        }

    } catch (Exception $e) {
        // If blockchain verification fails, fall back to basic verification
        error_log("Blockchain verification error for payment $paymentId: " . $e->getMessage());

        return attemptAutoVerification($paymentId, $db);
    }
}

/**
 * Simple CORS enabler
 */
function enableCORS() {
    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");
}
?>
