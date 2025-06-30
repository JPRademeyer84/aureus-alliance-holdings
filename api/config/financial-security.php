<?php
/**
 * FINANCIAL TRANSACTION SECURITY SYSTEM
 * Bank-level validation and fraud detection for financial operations
 */

require_once 'database.php';
require_once 'security-logger.php';
require_once 'data-encryption.php';

class FinancialSecurity {
    private $db;
    private $encryption;
    private static $instance = null;
    
    // Transaction limits by KYC level
    private $transactionLimits = [
        'level_1' => [
            'daily_investment_limit' => 1000,
            'daily_withdrawal_limit' => 500,
            'monthly_investment_limit' => 10000,
            'monthly_withdrawal_limit' => 5000,
            'max_single_transaction' => 500
        ],
        'level_2' => [
            'daily_investment_limit' => 10000,
            'daily_withdrawal_limit' => 5000,
            'monthly_investment_limit' => 100000,
            'monthly_withdrawal_limit' => 50000,
            'max_single_transaction' => 5000
        ],
        'level_3' => [
            'daily_investment_limit' => 100000,
            'daily_withdrawal_limit' => 50000,
            'monthly_investment_limit' => 1000000,
            'monthly_withdrawal_limit' => 500000,
            'max_single_transaction' => 50000
        ]
    ];
    
    // Fraud detection thresholds
    private $fraudThresholds = [
        'velocity_transactions_per_hour' => 10,
        'velocity_amount_per_hour' => 10000,
        'unusual_amount_multiplier' => 5, // 5x user's average
        'suspicious_pattern_score' => 75,
        'geolocation_change_threshold' => 500 // km
    ];
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->encryption = DataEncryption::getInstance();
        $this->initializeTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize financial security tables
     */
    private function initializeTables() {
        if (!$this->db) return;
        
        try {
            // Transaction validation table
            $this->db->exec("CREATE TABLE IF NOT EXISTS transaction_validations (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                transaction_id VARCHAR(36) NOT NULL,
                transaction_type ENUM('investment', 'withdrawal', 'commission', 'transfer') NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                amount DECIMAL(15,8) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                validation_status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
                risk_score INT DEFAULT 0,
                validation_rules JSON,
                fraud_indicators JSON,
                approved_by VARCHAR(36) NULL,
                approved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_user_id (user_id),
                INDEX idx_validation_status (validation_status),
                INDEX idx_risk_score (risk_score),
                INDEX idx_created_at (created_at)
            )");
            
            // Transaction limits tracking
            $this->db->exec("CREATE TABLE IF NOT EXISTS transaction_limits_tracking (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                period_type ENUM('daily', 'monthly', 'yearly') NOT NULL,
                period_start DATE NOT NULL,
                total_amount DECIMAL(15,8) DEFAULT 0,
                transaction_count INT DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                UNIQUE KEY unique_user_period (user_id, transaction_type, period_type, period_start),
                INDEX idx_user_id (user_id),
                INDEX idx_period_start (period_start)
            )");
            
            // Fraud detection patterns
            $this->db->exec("CREATE TABLE IF NOT EXISTS fraud_patterns (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                pattern_type VARCHAR(100) NOT NULL,
                pattern_data JSON NOT NULL,
                risk_score INT NOT NULL,
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                resolved BOOLEAN DEFAULT FALSE,
                resolved_by VARCHAR(36) NULL,
                resolved_at TIMESTAMP NULL,
                
                INDEX idx_user_id (user_id),
                INDEX idx_pattern_type (pattern_type),
                INDEX idx_risk_score (risk_score),
                INDEX idx_detected_at (detected_at),
                INDEX idx_resolved (resolved)
            )");
            
            // Multi-level approval workflows
            $this->db->exec("CREATE TABLE IF NOT EXISTS approval_workflows (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                transaction_id VARCHAR(36) NOT NULL,
                workflow_type VARCHAR(100) NOT NULL,
                required_approvals INT NOT NULL,
                current_approvals INT DEFAULT 0,
                approval_data JSON,
                status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_workflow_type (workflow_type),
                INDEX idx_status (status),
                INDEX idx_expires_at (expires_at)
            )");
            
        } catch (PDOException $e) {
            error_log("FINANCIAL SECURITY: Database initialization failed - " . $e->getMessage());
        }
    }
    
    /**
     * Validate financial transaction
     */
    public function validateTransaction($transactionId, $transactionType, $userId, $amount, $currency = 'USDT', $additionalData = []) {
        try {
            $validationId = $this->generateValidationId();
            $riskScore = 0;
            $validationRules = [];
            $fraudIndicators = [];
            
            // 1. Amount validation
            $amountValidation = $this->validateAmount($userId, $transactionType, $amount);
            $riskScore += $amountValidation['risk_score'];
            $validationRules['amount_validation'] = $amountValidation;
            
            // 2. Limit validation
            $limitValidation = $this->validateLimits($userId, $transactionType, $amount);
            $riskScore += $limitValidation['risk_score'];
            $validationRules['limit_validation'] = $limitValidation;
            
            // 3. Velocity validation
            $velocityValidation = $this->validateVelocity($userId, $transactionType, $amount);
            $riskScore += $velocityValidation['risk_score'];
            $validationRules['velocity_validation'] = $velocityValidation;
            
            // 4. Pattern analysis
            $patternAnalysis = $this->analyzePatterns($userId, $transactionType, $amount, $additionalData);
            $riskScore += $patternAnalysis['risk_score'];
            $validationRules['pattern_analysis'] = $patternAnalysis;
            
            // 5. Fraud detection
            $fraudDetection = $this->detectFraud($userId, $transactionType, $amount, $additionalData);
            $riskScore += $fraudDetection['risk_score'];
            $fraudIndicators = $fraudDetection['indicators'];
            
            // Determine validation status
            $validationStatus = $this->determineValidationStatus($riskScore, $fraudIndicators);
            
            // Store validation record
            $this->storeValidation($validationId, $transactionId, $transactionType, $userId, 
                                 $amount, $currency, $validationStatus, $riskScore, 
                                 $validationRules, $fraudIndicators);
            
            // Check if approval workflow is needed
            $approvalNeeded = $this->checkApprovalRequired($transactionType, $amount, $riskScore);
            if ($approvalNeeded) {
                $this->initiateApprovalWorkflow($transactionId, $transactionType, $amount, $riskScore);
            }
            
            // Log validation
            logFinancialEvent('transaction_validated', SecurityLogger::LEVEL_INFO,
                "Transaction validation completed", [
                    'validation_id' => $validationId,
                    'transaction_id' => $transactionId,
                    'risk_score' => $riskScore,
                    'status' => $validationStatus
                ], $userId);
            
            return [
                'validation_id' => $validationId,
                'status' => $validationStatus,
                'risk_score' => $riskScore,
                'approval_required' => $approvalNeeded,
                'validation_rules' => $validationRules,
                'fraud_indicators' => $fraudIndicators
            ];
            
        } catch (Exception $e) {
            logFinancialEvent('validation_failed', SecurityLogger::LEVEL_CRITICAL,
                "Transaction validation failed", ['error' => $e->getMessage()], $userId);
            throw $e;
        }
    }
    
    /**
     * Validate transaction amount
     */
    private function validateAmount($userId, $transactionType, $amount) {
        $riskScore = 0;
        $issues = [];
        
        // Check minimum amounts
        $minimums = [
            'investment' => 25,
            'withdrawal' => 10,
            'commission' => 1
        ];
        
        if (isset($minimums[$transactionType]) && $amount < $minimums[$transactionType]) {
            $riskScore += 20;
            $issues[] = "Amount below minimum for $transactionType";
        }
        
        // Check for unusual amounts (round numbers, specific patterns)
        if ($amount > 1000 && $amount % 1000 === 0) {
            $riskScore += 5;
            $issues[] = "Round number amount detected";
        }
        
        // Check user's historical average
        $userAverage = $this->getUserAverageTransaction($userId, $transactionType);
        if ($userAverage > 0 && $amount > ($userAverage * $this->fraudThresholds['unusual_amount_multiplier'])) {
            $riskScore += 30;
            $issues[] = "Amount significantly higher than user average";
        }
        
        return [
            'valid' => $riskScore < 50,
            'risk_score' => $riskScore,
            'issues' => $issues,
            'user_average' => $userAverage
        ];
    }
    
    /**
     * Validate transaction limits
     */
    private function validateLimits($userId, $transactionType, $amount) {
        $riskScore = 0;
        $issues = [];
        
        // Get user's KYC level
        $kycLevel = $this->getUserKYCLevel($userId);
        $limits = $this->transactionLimits[$kycLevel] ?? $this->transactionLimits['level_1'];
        
        // Check single transaction limit
        $maxSingle = $limits['max_single_transaction'];
        if ($amount > $maxSingle) {
            $riskScore += 50;
            $issues[] = "Amount exceeds single transaction limit ($maxSingle)";
        }
        
        // Check daily limits
        $dailyUsed = $this->getDailyUsage($userId, $transactionType);
        $dailyLimit = $limits["daily_{$transactionType}_limit"] ?? $limits['daily_investment_limit'];
        
        if (($dailyUsed + $amount) > $dailyLimit) {
            $riskScore += 40;
            $issues[] = "Transaction would exceed daily limit";
        }
        
        // Check monthly limits
        $monthlyUsed = $this->getMonthlyUsage($userId, $transactionType);
        $monthlyLimit = $limits["monthly_{$transactionType}_limit"] ?? $limits['monthly_investment_limit'];
        
        if (($monthlyUsed + $amount) > $monthlyLimit) {
            $riskScore += 30;
            $issues[] = "Transaction would exceed monthly limit";
        }
        
        return [
            'valid' => $riskScore < 50,
            'risk_score' => $riskScore,
            'issues' => $issues,
            'kyc_level' => $kycLevel,
            'limits' => $limits,
            'daily_used' => $dailyUsed,
            'monthly_used' => $monthlyUsed
        ];
    }
    
    /**
     * Validate transaction velocity
     */
    private function validateVelocity($userId, $transactionType, $amount) {
        $riskScore = 0;
        $issues = [];
        
        // Check transactions in last hour
        $hourlyTransactions = $this->getHourlyTransactionCount($userId);
        $hourlyAmount = $this->getHourlyTransactionAmount($userId);
        
        if ($hourlyTransactions >= $this->fraudThresholds['velocity_transactions_per_hour']) {
            $riskScore += 40;
            $issues[] = "High transaction velocity detected";
        }
        
        if (($hourlyAmount + $amount) > $this->fraudThresholds['velocity_amount_per_hour']) {
            $riskScore += 35;
            $issues[] = "High amount velocity detected";
        }
        
        // Check for rapid successive transactions
        $lastTransaction = $this->getLastTransactionTime($userId);
        if ($lastTransaction && (time() - strtotime($lastTransaction)) < 60) {
            $riskScore += 25;
            $issues[] = "Rapid successive transactions detected";
        }
        
        return [
            'valid' => $riskScore < 50,
            'risk_score' => $riskScore,
            'issues' => $issues,
            'hourly_transactions' => $hourlyTransactions,
            'hourly_amount' => $hourlyAmount
        ];
    }
    
    /**
     * Analyze transaction patterns
     */
    private function analyzePatterns($userId, $transactionType, $amount, $additionalData) {
        $riskScore = 0;
        $patterns = [];
        
        // Time-based patterns
        $hour = date('H');
        if ($hour >= 2 && $hour <= 6) { // Late night transactions
            $riskScore += 10;
            $patterns[] = "Late night transaction";
        }
        
        // IP/Location patterns
        if (isset($additionalData['ip_address'])) {
            $locationChange = $this->checkLocationChange($userId, $additionalData['ip_address']);
            if ($locationChange['suspicious']) {
                $riskScore += 20;
                $patterns[] = "Suspicious location change";
            }
        }
        
        // Device patterns
        if (isset($additionalData['user_agent'])) {
            $deviceChange = $this->checkDeviceChange($userId, $additionalData['user_agent']);
            if ($deviceChange['suspicious']) {
                $riskScore += 15;
                $patterns[] = "New device detected";
            }
        }
        
        // Amount patterns
        $amountPattern = $this->analyzeAmountPattern($userId, $amount);
        $riskScore += $amountPattern['risk_score'];
        $patterns = array_merge($patterns, $amountPattern['patterns']);
        
        return [
            'risk_score' => $riskScore,
            'patterns' => $patterns
        ];
    }
    
    /**
     * Detect fraud indicators
     */
    private function detectFraud($userId, $transactionType, $amount, $additionalData) {
        $riskScore = 0;
        $indicators = [];
        
        // Check for known fraud patterns
        $knownPatterns = $this->checkKnownFraudPatterns($userId, $additionalData);
        $riskScore += $knownPatterns['risk_score'];
        $indicators = array_merge($indicators, $knownPatterns['indicators']);
        
        // Behavioral analysis
        $behaviorAnalysis = $this->analyzeBehavior($userId, $transactionType, $amount);
        $riskScore += $behaviorAnalysis['risk_score'];
        $indicators = array_merge($indicators, $behaviorAnalysis['indicators']);
        
        // Machine learning fraud detection (placeholder for ML integration)
        $mlScore = $this->mlFraudDetection($userId, $transactionType, $amount, $additionalData);
        $riskScore += $mlScore;
        
        if ($mlScore > 30) {
            $indicators[] = "ML fraud detection triggered";
        }
        
        return [
            'risk_score' => $riskScore,
            'indicators' => $indicators
        ];
    }
    
    /**
     * Helper methods for data retrieval and analysis
     */
    
    private function getUserKYCLevel($userId) {
        try {
            $query = "SELECT kyc_status FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            // Map KYC status to levels
            switch ($result['kyc_status'] ?? 'not_verified') {
                case 'verified': return 'level_3';
                case 'pending': return 'level_2';
                default: return 'level_1';
            }
        } catch (Exception $e) {
            return 'level_1'; // Default to most restrictive
        }
    }
    
    private function getUserAverageTransaction($userId, $transactionType) {
        try {
            $query = "SELECT AVG(amount) as avg_amount FROM aureus_investments 
                     WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return floatval($result['avg_amount'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getDailyUsage($userId, $transactionType) {
        try {
            $query = "SELECT COALESCE(total_amount, 0) as total FROM transaction_limits_tracking 
                     WHERE user_id = ? AND transaction_type = ? AND period_type = 'daily' 
                     AND period_start = CURDATE()";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $transactionType]);
            $result = $stmt->fetch();
            
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getMonthlyUsage($userId, $transactionType) {
        try {
            $query = "SELECT COALESCE(total_amount, 0) as total FROM transaction_limits_tracking 
                     WHERE user_id = ? AND transaction_type = ? AND period_type = 'monthly' 
                     AND period_start = DATE_FORMAT(NOW(), '%Y-%m-01')";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $transactionType]);
            $result = $stmt->fetch();
            
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getHourlyTransactionCount($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM aureus_investments 
                     WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return intval($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getHourlyTransactionAmount($userId) {
        try {
            $query = "SELECT COALESCE(SUM(amount), 0) as total FROM aureus_investments 
                     WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getLastTransactionTime($userId) {
        try {
            $query = "SELECT MAX(created_at) as last_transaction FROM aureus_investments WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result['last_transaction'];
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function checkLocationChange($userId, $ipAddress) {
        // Placeholder for geolocation checking
        // In production, integrate with IP geolocation service
        return ['suspicious' => false, 'distance' => 0];
    }
    
    private function checkDeviceChange($userId, $userAgent) {
        // Placeholder for device fingerprinting
        // In production, implement device fingerprinting
        return ['suspicious' => false, 'new_device' => false];
    }
    
    private function analyzeAmountPattern($userId, $amount) {
        $riskScore = 0;
        $patterns = [];
        
        // Check for specific amount patterns that might indicate fraud
        if ($amount == 999 || $amount == 9999) {
            $riskScore += 15;
            $patterns[] = "Suspicious amount pattern";
        }
        
        return ['risk_score' => $riskScore, 'patterns' => $patterns];
    }
    
    private function checkKnownFraudPatterns($userId, $additionalData) {
        // Placeholder for known fraud pattern checking
        return ['risk_score' => 0, 'indicators' => []];
    }
    
    private function analyzeBehavior($userId, $transactionType, $amount) {
        // Placeholder for behavioral analysis
        return ['risk_score' => 0, 'indicators' => []];
    }
    
    private function mlFraudDetection($userId, $transactionType, $amount, $additionalData) {
        // Placeholder for machine learning fraud detection
        // In production, integrate with ML fraud detection service
        return 0;
    }
    
    private function determineValidationStatus($riskScore, $fraudIndicators) {
        if ($riskScore >= 80 || count($fraudIndicators) >= 3) {
            return 'rejected';
        } elseif ($riskScore >= 50 || count($fraudIndicators) >= 1) {
            return 'flagged';
        } elseif ($riskScore >= 30) {
            return 'pending';
        } else {
            return 'approved';
        }
    }
    
    private function checkApprovalRequired($transactionType, $amount, $riskScore) {
        // High-value transactions require approval
        if ($amount > 10000) return true;
        
        // High-risk transactions require approval
        if ($riskScore > 50) return true;
        
        // Withdrawals over certain amounts require approval
        if ($transactionType === 'withdrawal' && $amount > 1000) return true;
        
        return false;
    }
    
    private function initiateApprovalWorkflow($transactionId, $transactionType, $amount, $riskScore) {
        // Determine required approvals based on amount and risk
        $requiredApprovals = 1;
        if ($amount > 50000 || $riskScore > 70) {
            $requiredApprovals = 2;
        }
        if ($amount > 100000 || $riskScore > 90) {
            $requiredApprovals = 3;
        }
        
        $workflowId = $this->generateWorkflowId();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $query = "INSERT INTO approval_workflows (id, transaction_id, workflow_type, required_approvals, expires_at) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$workflowId, $transactionId, $transactionType, $requiredApprovals, $expiresAt]);
        
        // Log workflow initiation
        logFinancialEvent('approval_workflow_initiated', SecurityLogger::LEVEL_INFO,
            "Approval workflow initiated", [
                'workflow_id' => $workflowId,
                'transaction_id' => $transactionId,
                'required_approvals' => $requiredApprovals
            ]);
    }
    
    private function storeValidation($validationId, $transactionId, $transactionType, $userId, 
                                   $amount, $currency, $validationStatus, $riskScore, 
                                   $validationRules, $fraudIndicators) {
        $query = "INSERT INTO transaction_validations 
                 (id, transaction_id, transaction_type, user_id, amount, currency, 
                  validation_status, risk_score, validation_rules, fraud_indicators) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $validationId, $transactionId, $transactionType, $userId, $amount, $currency,
            $validationStatus, $riskScore, json_encode($validationRules), json_encode($fraudIndicators)
        ]);
    }
    
    private function generateValidationId() {
        return 'val_' . uniqid() . '_' . time();
    }
    
    private function generateWorkflowId() {
        return 'wf_' . uniqid() . '_' . time();
    }
}

// Convenience functions
function validateFinancialTransaction($transactionId, $transactionType, $userId, $amount, $currency = 'USDT', $additionalData = []) {
    $financialSecurity = FinancialSecurity::getInstance();
    return $financialSecurity->validateTransaction($transactionId, $transactionType, $userId, $amount, $currency, $additionalData);
}

/**
 * APPROVAL WORKFLOW MANAGER
 * Handles multi-level approval processes for financial transactions
 */
class ApprovalWorkflowManager {
    private $db;
    private static $instance = null;

    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get pending approvals for admin
     */
    public function getPendingApprovals($adminId = null) {
        try {
            $query = "SELECT
                        aw.id as workflow_id,
                        aw.transaction_id,
                        aw.workflow_type,
                        aw.required_approvals,
                        aw.current_approvals,
                        aw.expires_at,
                        tv.amount,
                        tv.currency,
                        tv.risk_score,
                        tv.user_id,
                        u.username,
                        u.email
                      FROM approval_workflows aw
                      JOIN transaction_validations tv ON aw.transaction_id = tv.transaction_id
                      LEFT JOIN users u ON tv.user_id = u.id
                      WHERE aw.status = 'pending' AND aw.expires_at > NOW()
                      ORDER BY tv.risk_score DESC, aw.created_at ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (Exception $e) {
            logFinancialEvent('approval_fetch_failed', SecurityLogger::LEVEL_WARNING,
                "Failed to fetch pending approvals", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Approve transaction
     */
    public function approveTransaction($workflowId, $adminId, $comments = '') {
        try {
            $this->db->beginTransaction();

            // Get workflow details
            $workflow = $this->getWorkflowDetails($workflowId);
            if (!$workflow) {
                throw new Exception('Workflow not found');
            }

            // Check if already approved by this admin
            if ($this->hasAdminApproved($workflowId, $adminId)) {
                throw new Exception('Admin has already approved this transaction');
            }

            // Add approval
            $this->addApproval($workflowId, $adminId, 'approved', $comments);

            // Update workflow
            $newApprovalCount = $workflow['current_approvals'] + 1;
            $this->updateWorkflowApprovals($workflowId, $newApprovalCount);

            // Check if fully approved
            if ($newApprovalCount >= $workflow['required_approvals']) {
                $this->finalizeWorkflow($workflowId, 'approved');
                $this->approveTransactionValidation($workflow['transaction_id'], $adminId);
            }

            $this->db->commit();

            // Log approval
            logFinancialEvent('transaction_approved', SecurityLogger::LEVEL_INFO,
                "Transaction approved by admin", [
                    'workflow_id' => $workflowId,
                    'transaction_id' => $workflow['transaction_id'],
                    'admin_id' => $adminId,
                    'approvals' => "$newApprovalCount/{$workflow['required_approvals']}"
                ], null, $adminId);

            return [
                'success' => true,
                'message' => 'Transaction approved successfully',
                'approvals' => "$newApprovalCount/{$workflow['required_approvals']}",
                'fully_approved' => $newApprovalCount >= $workflow['required_approvals']
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            logFinancialEvent('approval_failed', SecurityLogger::LEVEL_CRITICAL,
                "Transaction approval failed", ['error' => $e->getMessage()], null, $adminId);
            throw $e;
        }
    }

    /**
     * Reject transaction
     */
    public function rejectTransaction($workflowId, $adminId, $reason = '') {
        try {
            $this->db->beginTransaction();

            // Get workflow details
            $workflow = $this->getWorkflowDetails($workflowId);
            if (!$workflow) {
                throw new Exception('Workflow not found');
            }

            // Add rejection
            $this->addApproval($workflowId, $adminId, 'rejected', $reason);

            // Finalize workflow as rejected
            $this->finalizeWorkflow($workflowId, 'rejected');
            $this->rejectTransactionValidation($workflow['transaction_id'], $adminId, $reason);

            $this->db->commit();

            // Log rejection
            logFinancialEvent('transaction_rejected', SecurityLogger::LEVEL_WARNING,
                "Transaction rejected by admin", [
                    'workflow_id' => $workflowId,
                    'transaction_id' => $workflow['transaction_id'],
                    'admin_id' => $adminId,
                    'reason' => $reason
                ], null, $adminId);

            return [
                'success' => true,
                'message' => 'Transaction rejected successfully',
                'reason' => $reason
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            logFinancialEvent('rejection_failed', SecurityLogger::LEVEL_CRITICAL,
                "Transaction rejection failed", ['error' => $e->getMessage()], null, $adminId);
            throw $e;
        }
    }

    /**
     * Helper methods
     */

    private function getWorkflowDetails($workflowId) {
        $query = "SELECT * FROM approval_workflows WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$workflowId]);
        return $stmt->fetch();
    }

    private function hasAdminApproved($workflowId, $adminId) {
        $query = "SELECT COUNT(*) as count FROM approval_workflow_approvals
                 WHERE workflow_id = ? AND admin_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$workflowId, $adminId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    private function addApproval($workflowId, $adminId, $decision, $comments) {
        // Create approvals table if it doesn't exist
        $this->db->exec("CREATE TABLE IF NOT EXISTS approval_workflow_approvals (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            workflow_id VARCHAR(36) NOT NULL,
            admin_id VARCHAR(36) NOT NULL,
            decision ENUM('approved', 'rejected') NOT NULL,
            comments TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_workflow_id (workflow_id),
            INDEX idx_admin_id (admin_id)
        )");

        $query = "INSERT INTO approval_workflow_approvals (workflow_id, admin_id, decision, comments)
                 VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$workflowId, $adminId, $decision, $comments]);
    }

    private function updateWorkflowApprovals($workflowId, $newCount) {
        $query = "UPDATE approval_workflows SET current_approvals = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$newCount, $workflowId]);
    }

    private function finalizeWorkflow($workflowId, $status) {
        $query = "UPDATE approval_workflows SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$status, $workflowId]);
    }

    private function approveTransactionValidation($transactionId, $adminId) {
        $query = "UPDATE transaction_validations
                 SET validation_status = 'approved', approved_by = ?, approved_at = NOW()
                 WHERE transaction_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$adminId, $transactionId]);
    }

    private function rejectTransactionValidation($transactionId, $adminId, $reason) {
        $query = "UPDATE transaction_validations
                 SET validation_status = 'rejected', approved_by = ?, approved_at = NOW()
                 WHERE transaction_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$adminId, $transactionId]);
    }
}

/**
 * TRANSACTION REVERSAL MANAGER
 * Handles transaction reversals and audit capabilities
 */
class TransactionReversalManager {
    private $db;
    private static $instance = null;

    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeTables();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeTables() {
        if (!$this->db) return;

        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS transaction_reversals (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                original_transaction_id VARCHAR(36) NOT NULL,
                reversal_transaction_id VARCHAR(36) NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                original_amount DECIMAL(15,8) NOT NULL,
                reversal_amount DECIMAL(15,8) NOT NULL,
                reason TEXT NOT NULL,
                initiated_by VARCHAR(36) NOT NULL,
                approved_by VARCHAR(36) NULL,
                status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,

                INDEX idx_original_transaction (original_transaction_id),
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            )");
        } catch (PDOException $e) {
            error_log("REVERSAL MANAGER: Database initialization failed - " . $e->getMessage());
        }
    }

    /**
     * Initiate transaction reversal
     */
    public function initiateReversal($originalTransactionId, $reason, $adminId, $partialAmount = null) {
        try {
            $this->db->beginTransaction();

            // Get original transaction details
            $originalTransaction = $this->getTransactionDetails($originalTransactionId);
            if (!$originalTransaction) {
                throw new Exception('Original transaction not found');
            }

            // Check if already reversed
            if ($this->isTransactionReversed($originalTransactionId)) {
                throw new Exception('Transaction has already been reversed');
            }

            // Determine reversal amount
            $reversalAmount = $partialAmount ?? $originalTransaction['amount'];
            if ($reversalAmount > $originalTransaction['amount']) {
                throw new Exception('Reversal amount cannot exceed original amount');
            }

            // Create reversal record
            $reversalId = $this->generateReversalId();
            $reversalTransactionId = $this->generateTransactionId();

            $query = "INSERT INTO transaction_reversals
                     (id, original_transaction_id, reversal_transaction_id, transaction_type,
                      user_id, original_amount, reversal_amount, reason, initiated_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $reversalId, $originalTransactionId, $reversalTransactionId,
                $originalTransaction['type'], $originalTransaction['user_id'],
                $originalTransaction['amount'], $reversalAmount, $reason, $adminId
            ]);

            $this->db->commit();

            // Log reversal initiation
            logFinancialEvent('reversal_initiated', SecurityLogger::LEVEL_WARNING,
                "Transaction reversal initiated", [
                    'reversal_id' => $reversalId,
                    'original_transaction_id' => $originalTransactionId,
                    'reversal_amount' => $reversalAmount,
                    'reason' => $reason
                ], $originalTransaction['user_id'], $adminId);

            return [
                'success' => true,
                'reversal_id' => $reversalId,
                'reversal_transaction_id' => $reversalTransactionId,
                'message' => 'Reversal initiated successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            logFinancialEvent('reversal_initiation_failed', SecurityLogger::LEVEL_CRITICAL,
                "Transaction reversal initiation failed", ['error' => $e->getMessage()], null, $adminId);
            throw $e;
        }
    }

    private function getTransactionDetails($transactionId) {
        // This would need to be adapted based on your transaction table structure
        $query = "SELECT id, user_id, amount, 'investment' as type FROM aureus_investments WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionId]);
        return $stmt->fetch();
    }

    private function isTransactionReversed($transactionId) {
        $query = "SELECT COUNT(*) as count FROM transaction_reversals
                 WHERE original_transaction_id = ? AND status IN ('approved', 'completed')";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    private function generateReversalId() {
        return 'rev_' . uniqid() . '_' . time();
    }

    private function generateTransactionId() {
        return 'txn_' . uniqid() . '_' . time();
    }
}
?>
