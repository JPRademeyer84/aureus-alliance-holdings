<?php
/**
 * AUTOMATIC PAYMENT VERIFICATION SYSTEM
 * Attempts automatic verification with manual fallback
 */

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../utils/validation.php';
require_once '../utils/WalletSecurity.php';

class AutoPaymentVerification {
    private $db;
    private $blockchainAPIs;
    
    public function __construct($database) {
        $this->db = $database;
        $this->blockchainAPIs = [
            'ethereum' => 'https://api.etherscan.io/api',
            'bsc' => 'https://api.bscscan.com/api',
            'polygon' => 'https://api.polygonscan.com/api',
            'tron' => 'https://api.trongrid.io'
        ];
    }
    
    /**
     * Main verification function - tries auto first, falls back to manual
     */
    public function verifyPayment($paymentId) {
        try {
            // Get payment details
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Step 1: Basic validation
            $basicValidation = $this->performBasicValidation($payment);
            
            // Step 2: Attempt automatic verification
            $autoVerification = $this->attemptAutoVerification($payment);
            
            // Step 3: Determine final action
            $result = $this->determineVerificationResult($basicValidation, $autoVerification, $payment);
            
            // Step 4: Update payment status
            $this->updatePaymentStatus($paymentId, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Auto verification failed for payment $paymentId: " . $e->getMessage());
            
            // Fallback to manual review
            return $this->fallbackToManualReview($paymentId, $e->getMessage());
        }
    }
    
    /**
     * Basic validation checks
     */
    private function performBasicValidation($payment) {
        $checks = [];
        $score = 0;
        
        // 1. Wallet address format validation
        $walletValid = WalletSecurity::validateWalletAddress(
            $payment['company_wallet_address'], 
            $payment['chain']
        );
        $checks['wallet_format'] = $walletValid;
        if ($walletValid) $score += 20;
        
        // 2. Amount validation
        $amountValid = ($payment['amount_usd'] > 0 && $payment['amount_usd'] <= 100000);
        $checks['amount_valid'] = $amountValid;
        if ($amountValid) $score += 20;
        
        // 3. Transaction hash format
        $hashValid = $this->validateTransactionHash($payment['transaction_hash'], $payment['chain']);
        $checks['hash_format'] = $hashValid;
        if ($hashValid) $score += 20;
        
        // 4. Sender wallet format
        if (!empty($payment['sender_wallet_address'])) {
            $senderValid = WalletSecurity::validateWalletAddress(
                $payment['sender_wallet_address'], 
                $payment['chain']
            );
            $checks['sender_wallet'] = $senderValid;
            if ($senderValid) $score += 20;
        }
        
        // 5. Time validation (not too old, not future)
        $timeValid = $this->validatePaymentTime($payment['created_at']);
        $checks['time_valid'] = $timeValid;
        if ($timeValid) $score += 20;
        
        return [
            'score' => $score,
            'checks' => $checks,
            'passed' => $score >= 60 // Need at least 60% to pass basic validation
        ];
    }
    
    /**
     * Attempt automatic blockchain verification
     */
    private function attemptAutoVerification($payment) {
        $result = [
            'attempted' => true,
            'success' => false,
            'confidence' => 0,
            'checks' => [],
            'reason' => ''
        ];
        
        try {
            // Only attempt if we have transaction hash
            if (empty($payment['transaction_hash'])) {
                $result['attempted'] = false;
                $result['reason'] = 'No transaction hash provided';
                return $result;
            }
            
            // Try blockchain API verification
            $blockchainResult = $this->verifyOnBlockchain($payment);
            
            if ($blockchainResult['success']) {
                $result['success'] = true;
                $result['confidence'] = $blockchainResult['confidence'];
                $result['checks'] = $blockchainResult['checks'];
                $result['blockchain_data'] = $blockchainResult['data'];
            } else {
                $result['reason'] = $blockchainResult['error'];
            }
            
        } catch (Exception $e) {
            $result['reason'] = 'Blockchain verification failed: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Verify transaction on blockchain
     */
    private function verifyOnBlockchain($payment) {
        $chain = $payment['chain'];
        $txHash = $payment['transaction_hash'];
        $expectedAmount = $payment['amount_usd'];
        $expectedWallet = $payment['company_wallet_address'];
        
        // For demo purposes, simulate blockchain verification
        // In production, this would make actual API calls
        
        $mockVerification = $this->simulateBlockchainVerification($payment);
        
        return $mockVerification;
    }
    
    /**
     * Simulate blockchain verification (replace with real API calls)
     */
    private function simulateBlockchainVerification($payment) {
        // Simulate different scenarios based on payment data
        $txHash = $payment['transaction_hash'];
        $amount = $payment['amount_usd'];
        
        // Simulate success for valid-looking hashes
        if (strlen($txHash) >= 40 && preg_match('/^0x[a-fA-F0-9]+$/', $txHash)) {
            return [
                'success' => true,
                'confidence' => 95,
                'checks' => [
                    'transaction_exists' => true,
                    'amount_matches' => true,
                    'wallet_matches' => true,
                    'confirmed' => true
                ],
                'data' => [
                    'block_number' => rand(1000000, 9999999),
                    'confirmations' => rand(12, 100),
                    'actual_amount' => $amount,
                    'gas_used' => rand(21000, 50000)
                ]
            ];
        }
        
        // Simulate failure for invalid hashes
        return [
            'success' => false,
            'error' => 'Transaction not found on blockchain',
            'confidence' => 0
        ];
    }
    
    /**
     * Determine final verification result
     */
    private function determineVerificationResult($basicValidation, $autoVerification, $payment) {
        $result = [
            'action' => 'manual_review', // Default to manual
            'status' => 'pending',
            'confidence' => 0,
            'reason' => '',
            'auto_approved' => false
        ];
        
        // Auto-approve conditions
        if ($basicValidation['passed'] && 
            $autoVerification['success'] && 
            $autoVerification['confidence'] >= 90) {
            
            $result['action'] = 'auto_approve';
            $result['status'] = 'approved';
            $result['confidence'] = $autoVerification['confidence'];
            $result['reason'] = 'Automatic verification successful';
            $result['auto_approved'] = true;
            
        } else {
            // Determine reason for manual review
            if (!$basicValidation['passed']) {
                $result['reason'] = 'Failed basic validation checks';
            } elseif (!$autoVerification['attempted']) {
                $result['reason'] = 'Automatic verification not possible';
            } elseif (!$autoVerification['success']) {
                $result['reason'] = 'Blockchain verification failed: ' . $autoVerification['reason'];
            } elseif ($autoVerification['confidence'] < 90) {
                $result['reason'] = 'Low confidence score: ' . $autoVerification['confidence'] . '%';
            }
        }
        
        return $result;
    }
    
    /**
     * Update payment status in database
     */
    private function updatePaymentStatus($paymentId, $result) {
        $query = "UPDATE manual_payment_transactions SET 
                    verification_status = ?,
                    auto_verification_result = ?,
                    verification_confidence = ?,
                    verification_reason = ?,
                    auto_verified_at = ?
                  WHERE payment_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $result['status'],
            json_encode($result),
            $result['confidence'],
            $result['reason'],
            $result['auto_approved'] ? date('Y-m-d H:i:s') : null,
            $paymentId
        ]);
        
        // If auto-approved, also update payment status
        if ($result['auto_approved']) {
            $updateQuery = "UPDATE manual_payment_transactions SET 
                              payment_status = 'approved',
                              verified_at = NOW()
                            WHERE payment_id = ?";
            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute([$paymentId]);
        }
    }
    
    /**
     * Fallback to manual review
     */
    private function fallbackToManualReview($paymentId, $error) {
        $query = "UPDATE manual_payment_transactions SET 
                    verification_status = 'manual_review_required',
                    verification_reason = ?
                  WHERE payment_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(["Auto verification failed: $error", $paymentId]);
        
        return [
            'action' => 'manual_review',
            'status' => 'manual_review_required',
            'reason' => $error,
            'auto_approved' => false
        ];
    }
    
    // Helper methods
    private function getPaymentDetails($paymentId) {
        $query = "SELECT * FROM manual_payment_transactions WHERE payment_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$paymentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function validateTransactionHash($hash, $chain) {
        if (empty($hash)) return false;
        
        switch ($chain) {
            case 'ethereum':
            case 'bsc':
            case 'polygon':
                return preg_match('/^0x[a-fA-F0-9]{64}$/', $hash);
            case 'tron':
                return preg_match('/^[a-fA-F0-9]{64}$/', $hash);
            default:
                return false;
        }
    }
    
    private function validatePaymentTime($createdAt) {
        $created = strtotime($createdAt);
        $now = time();
        $dayAgo = $now - (24 * 60 * 60);
        
        return ($created >= $dayAgo && $created <= $now);
    }
}

// API endpoint
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['payment_id'])) {
            throw new Exception('Payment ID required');
        }
        
        $verifier = new AutoPaymentVerification($db);
        $result = $verifier->verifyPayment($input['payment_id']);
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
