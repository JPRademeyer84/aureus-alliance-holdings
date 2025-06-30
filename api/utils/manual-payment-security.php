<?php
require_once '../config/database.php';

/**
 * Manual Payment Security and Fraud Prevention System
 */

class ManualPaymentSecurity {
    private $db;
    
    // Security thresholds
    const MAX_DAILY_SUBMISSIONS = 5;
    const MAX_PENDING_PAYMENTS = 3;
    const MIN_TIME_BETWEEN_SUBMISSIONS = 300; // 5 minutes
    const MAX_AMOUNT_PER_DAY = 50000; // $50,000 USD
    const SUSPICIOUS_AMOUNT_THRESHOLD = 10000; // $10,000 USD
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Validate payment submission for security issues
     */
    public function validatePaymentSubmission($userId, $amount, $senderName, $walletAddress = null) {
        $violations = [];
        
        // Check daily submission limit
        if (!$this->checkDailySubmissionLimit($userId)) {
            $violations[] = 'Daily submission limit exceeded';
        }
        
        // Check pending payments limit
        if (!$this->checkPendingPaymentsLimit($userId)) {
            $violations[] = 'Too many pending payments';
        }
        
        // Check time between submissions
        if (!$this->checkTimeBetweenSubmissions($userId)) {
            $violations[] = 'Submissions too frequent';
        }
        
        // Check daily amount limit
        if (!$this->checkDailyAmountLimit($userId, $amount)) {
            $violations[] = 'Daily amount limit exceeded';
        }
        
        // Check for duplicate payments
        if ($this->checkDuplicatePayment($userId, $amount, $senderName)) {
            $violations[] = 'Potential duplicate payment detected';
        }
        
        // Check for suspicious patterns
        $suspiciousFlags = $this->checkSuspiciousPatterns($userId, $amount, $senderName, $walletAddress);
        if (!empty($suspiciousFlags)) {
            $violations = array_merge($violations, $suspiciousFlags);
        }
        
        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'risk_level' => $this->calculateRiskLevel($violations, $amount)
        ];
    }
    
    /**
     * Check daily submission limit
     */
    private function checkDailySubmissionLimit($userId) {
        $query = "SELECT COUNT(*) as count FROM manual_payment_transactions 
                  WHERE user_id = ? AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] < self::MAX_DAILY_SUBMISSIONS;
    }
    
    /**
     * Check pending payments limit
     */
    private function checkPendingPaymentsLimit($userId) {
        $query = "SELECT COUNT(*) as count FROM manual_payment_transactions 
                  WHERE user_id = ? AND verification_status = 'pending'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] < self::MAX_PENDING_PAYMENTS;
    }
    
    /**
     * Check time between submissions
     */
    private function checkTimeBetweenSubmissions($userId) {
        $query = "SELECT MAX(created_at) as last_submission FROM manual_payment_transactions 
                  WHERE user_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result['last_submission']) {
            return true; // First submission
        }
        
        $lastSubmission = strtotime($result['last_submission']);
        $timeDiff = time() - $lastSubmission;
        
        return $timeDiff >= self::MIN_TIME_BETWEEN_SUBMISSIONS;
    }
    
    /**
     * Check daily amount limit
     */
    private function checkDailyAmountLimit($userId, $amount) {
        $query = "SELECT COALESCE(SUM(amount_usd), 0) as total_today 
                  FROM manual_payment_transactions 
                  WHERE user_id = ? AND DATE(created_at) = CURDATE()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['total_today'] + $amount) <= self::MAX_AMOUNT_PER_DAY;
    }
    
    /**
     * Check for duplicate payments
     */
    private function checkDuplicatePayment($userId, $amount, $senderName) {
        $query = "SELECT COUNT(*) as count FROM manual_payment_transactions 
                  WHERE user_id = ? AND amount_usd = ? AND sender_name = ? 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND verification_status IN ('pending', 'approved')";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $amount, $senderName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Check for suspicious patterns
     */
    private function checkSuspiciousPatterns($userId, $amount, $senderName, $walletAddress) {
        $flags = [];
        
        // Large amount flag
        if ($amount >= self::SUSPICIOUS_AMOUNT_THRESHOLD) {
            $flags[] = 'Large amount transaction';
        }
        
        // Check for unusual sender name patterns
        if ($this->isSuspiciousSenderName($senderName)) {
            $flags[] = 'Suspicious sender name pattern';
        }
        
        // Check for wallet address reuse across different users
        if ($walletAddress && $this->isWalletAddressReused($walletAddress, $userId)) {
            $flags[] = 'Wallet address used by multiple users';
        }
        
        // Check user's historical behavior
        if ($this->hasUnusualBehaviorPattern($userId, $amount)) {
            $flags[] = 'Unusual behavior pattern';
        }
        
        return $flags;
    }
    
    /**
     * Check if sender name looks suspicious
     */
    private function isSuspiciousSenderName($senderName) {
        // Check for common suspicious patterns
        $suspiciousPatterns = [
            '/^test/i',
            '/^fake/i',
            '/^dummy/i',
            '/^\d+$/', // Only numbers
            '/^[a-z]{1,3}$/i', // Very short names
            '/(.)\1{4,}/', // Repeated characters
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $senderName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if wallet address is reused across different users
     */
    private function isWalletAddressReused($walletAddress, $currentUserId) {
        if (empty($walletAddress)) return false;
        
        $query = "SELECT COUNT(DISTINCT user_id) as user_count 
                  FROM manual_payment_transactions 
                  WHERE sender_wallet_address = ? AND user_id != ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$walletAddress, $currentUserId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['user_count'] > 0;
    }
    
    /**
     * Check for unusual behavior patterns
     */
    private function hasUnusualBehaviorPattern($userId, $amount) {
        // Get user's average payment amount
        $query = "SELECT AVG(amount_usd) as avg_amount, COUNT(*) as payment_count 
                  FROM manual_payment_transactions 
                  WHERE user_id = ? AND verification_status = 'approved'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['payment_count'] < 2) {
            return false; // Not enough history
        }
        
        $avgAmount = $result['avg_amount'];
        
        // Flag if current amount is more than 5x the average
        return $amount > ($avgAmount * 5);
    }
    
    /**
     * Calculate risk level based on violations and amount
     */
    private function calculateRiskLevel($violations, $amount) {
        $riskScore = 0;
        
        // Base risk from violations
        $riskScore += count($violations) * 10;
        
        // Amount-based risk
        if ($amount >= 50000) $riskScore += 30;
        elseif ($amount >= 20000) $riskScore += 20;
        elseif ($amount >= 10000) $riskScore += 10;
        
        // Determine risk level
        if ($riskScore >= 50) return 'high';
        elseif ($riskScore >= 25) return 'medium';
        else return 'low';
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($userId, $eventType, $details, $riskLevel = 'low') {
        try {
            $query = "INSERT INTO security_audit_log (
                event_type, user_id, event_details, security_level,
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'manual_payment_' . $eventType,
                $userId,
                json_encode($details),
                $riskLevel,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log('Failed to log security event: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up expired payments
     */
    public function cleanupExpiredPayments() {
        try {
            $query = "UPDATE manual_payment_transactions 
                      SET payment_status = 'expired', verification_status = 'expired'
                      WHERE expires_at < NOW() AND payment_status = 'pending'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Failed to cleanup expired payments: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStatistics() {
        try {
            $stats = [];
            
            // Total payments by status
            $query = "SELECT payment_status, COUNT(*) as count 
                      FROM manual_payment_transactions 
                      GROUP BY payment_status";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['status_counts'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // High-risk payments in last 24 hours
            $query = "SELECT COUNT(*) as count FROM security_audit_log 
                      WHERE event_type LIKE 'manual_payment_%' 
                      AND security_level = 'high' 
                      AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['high_risk_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log('Failed to get security statistics: ' . $e->getMessage());
            return [];
        }
    }
}

// Convenience function
function validateManualPaymentSecurity($userId, $amount, $senderName, $walletAddress = null) {
    $security = new ManualPaymentSecurity();
    return $security->validatePaymentSubmission($userId, $amount, $senderName, $walletAddress);
}

?>
