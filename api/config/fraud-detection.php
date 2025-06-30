<?php
/**
 * FRAUD DETECTION ALGORITHMS
 * Advanced machine learning-based fraud detection system
 */

require_once 'security-logger.php';
require_once 'financial-monitoring.php';

class FraudDetection {
    private static $instance = null;
    private $db;
    
    // Fraud types
    const FRAUD_ACCOUNT_TAKEOVER = 'account_takeover';
    const FRAUD_IDENTITY_THEFT = 'identity_theft';
    const FRAUD_PAYMENT_FRAUD = 'payment_fraud';
    const FRAUD_MONEY_LAUNDERING = 'money_laundering';
    const FRAUD_STRUCTURING = 'structuring';
    const FRAUD_VELOCITY_ABUSE = 'velocity_abuse';
    const FRAUD_BEHAVIORAL_ANOMALY = 'behavioral_anomaly';
    
    // Detection algorithms
    const ALGO_RULE_BASED = 'rule_based';
    const ALGO_STATISTICAL = 'statistical';
    const ALGO_MACHINE_LEARNING = 'machine_learning';
    const ALGO_BEHAVIORAL = 'behavioral';
    const ALGO_NETWORK_ANALYSIS = 'network_analysis';
    
    // Risk thresholds
    const RISK_THRESHOLD_LOW = 30;
    const RISK_THRESHOLD_MEDIUM = 60;
    const RISK_THRESHOLD_HIGH = 80;
    const RISK_THRESHOLD_CRITICAL = 95;
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeFraudTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize fraud detection tables
     */
    private function initializeFraudTables() {
        $tables = [
            // Fraud detection results
            "CREATE TABLE IF NOT EXISTS fraud_detection_results (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                transaction_id VARCHAR(36),
                session_id VARCHAR(100),
                fraud_type VARCHAR(50) NOT NULL,
                detection_algorithm VARCHAR(50) NOT NULL,
                fraud_score DECIMAL(5,2) NOT NULL,
                risk_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                fraud_indicators JSON NOT NULL,
                model_version VARCHAR(20),
                confidence_score DECIMAL(5,2),
                false_positive_probability DECIMAL(5,2),
                action_taken ENUM('none', 'flag', 'block', 'review') DEFAULT 'none',
                investigation_status ENUM('pending', 'investigating', 'confirmed', 'false_positive') DEFAULT 'pending',
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_fraud_type (fraud_type),
                INDEX idx_fraud_score (fraud_score),
                INDEX idx_risk_level (risk_level),
                INDEX idx_detected_at (detected_at)
            )",
            
            // User behavior profiles
            "CREATE TABLE IF NOT EXISTS user_behavior_profiles (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL UNIQUE,
                profile_data JSON NOT NULL,
                transaction_patterns JSON,
                login_patterns JSON,
                device_patterns JSON,
                location_patterns JSON,
                risk_factors JSON,
                baseline_established BOOLEAN DEFAULT FALSE,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_baseline_established (baseline_established),
                INDEX idx_last_updated (last_updated)
            )",
            
            // Fraud rules
            "CREATE TABLE IF NOT EXISTS fraud_detection_rules (
                id VARCHAR(36) PRIMARY KEY,
                rule_name VARCHAR(100) NOT NULL,
                fraud_type VARCHAR(50) NOT NULL,
                rule_conditions JSON NOT NULL,
                rule_weights JSON,
                threshold_score DECIMAL(5,2) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                rule_priority INT DEFAULT 1,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_fraud_type (fraud_type),
                INDEX idx_is_active (is_active),
                INDEX idx_rule_priority (rule_priority)
            )",
            
            // Device fingerprints
            "CREATE TABLE IF NOT EXISTS device_fingerprints (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                device_hash VARCHAR(128) NOT NULL,
                device_info JSON NOT NULL,
                first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                trust_score DECIMAL(5,2) DEFAULT 50.00,
                is_trusted BOOLEAN DEFAULT FALSE,
                is_blocked BOOLEAN DEFAULT FALSE,
                usage_count INT DEFAULT 1,
                INDEX idx_user_id (user_id),
                INDEX idx_device_hash (device_hash),
                INDEX idx_trust_score (trust_score),
                INDEX idx_is_trusted (is_trusted)
            )",
            
            // Fraud investigation cases
            "CREATE TABLE IF NOT EXISTS fraud_investigation_cases (
                id VARCHAR(36) PRIMARY KEY,
                case_number VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(36) NOT NULL,
                fraud_detection_ids JSON NOT NULL,
                case_priority ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                case_status ENUM('open', 'investigating', 'closed', 'escalated') DEFAULT 'open',
                assigned_investigator VARCHAR(36),
                investigation_notes TEXT,
                evidence_collected JSON,
                case_resolution ENUM('confirmed_fraud', 'false_positive', 'inconclusive') NULL,
                financial_impact DECIMAL(15,8),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                closed_at TIMESTAMP NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_case_priority (case_priority),
                INDEX idx_case_status (case_status),
                INDEX idx_assigned_investigator (assigned_investigator)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create fraud detection table: " . $e->getMessage());
            }
        }
        
        $this->initializeFraudRules();
    }
    
    /**
     * Initialize default fraud detection rules
     */
    private function initializeFraudRules() {
        // Check if rules already exist
        $query = "SELECT COUNT(*) FROM fraud_detection_rules";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Rules already initialized
        }
        
        $defaultRules = [
            [
                'rule_name' => 'Velocity Fraud Detection',
                'fraud_type' => self::FRAUD_VELOCITY_ABUSE,
                'conditions' => [
                    'transaction_count_1h' => 10,
                    'transaction_amount_1h' => 50000,
                    'unique_recipients_1h' => 5
                ],
                'weights' => ['count' => 0.4, 'amount' => 0.4, 'recipients' => 0.2],
                'threshold' => 70
            ],
            [
                'rule_name' => 'Account Takeover Detection',
                'fraud_type' => self::FRAUD_ACCOUNT_TAKEOVER,
                'conditions' => [
                    'new_device' => true,
                    'location_change' => true,
                    'password_change' => true,
                    'large_transaction' => true
                ],
                'weights' => ['device' => 0.3, 'location' => 0.2, 'password' => 0.3, 'transaction' => 0.2],
                'threshold' => 75
            ],
            [
                'rule_name' => 'Structuring Detection',
                'fraud_type' => self::FRAUD_STRUCTURING,
                'conditions' => [
                    'amount_just_below_threshold' => 9500,
                    'multiple_transactions_daily' => 3,
                    'round_amounts' => true
                ],
                'weights' => ['amount' => 0.5, 'frequency' => 0.3, 'pattern' => 0.2],
                'threshold' => 80
            ],
            [
                'rule_name' => 'Behavioral Anomaly Detection',
                'fraud_type' => self::FRAUD_BEHAVIORAL_ANOMALY,
                'conditions' => [
                    'deviation_from_baseline' => 3.0, // 3 standard deviations
                    'unusual_time' => true,
                    'unusual_amount' => true
                ],
                'weights' => ['deviation' => 0.6, 'time' => 0.2, 'amount' => 0.2],
                'threshold' => 65
            ]
        ];
        
        foreach ($defaultRules as $rule) {
            $this->createFraudRule($rule['rule_name'], $rule['fraud_type'], $rule['conditions'], $rule['weights'], $rule['threshold']);
        }
    }
    
    /**
     * Analyze transaction for fraud
     */
    public function analyzeTransaction($transactionId, $userId, $transactionData) {
        $fraudResults = [];
        
        // Get user behavior profile
        $userProfile = $this->getUserBehaviorProfile($userId);
        
        // Run all active fraud detection rules
        $query = "SELECT * FROM fraud_detection_rules WHERE is_active = TRUE ORDER BY rule_priority DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $rules = $stmt->fetchAll();
        
        foreach ($rules as $rule) {
            $result = $this->applyFraudRule($rule, $userId, $transactionData, $userProfile);
            if ($result && $result['fraud_score'] > 0) {
                $fraudResults[] = $result;
            }
        }
        
        // Apply machine learning models
        $mlResult = $this->applyMachineLearningModels($userId, $transactionData, $userProfile);
        if ($mlResult) {
            $fraudResults[] = $mlResult;
        }
        
        // Behavioral analysis
        $behavioralResult = $this->analyzeBehavioralPatterns($userId, $transactionData, $userProfile);
        if ($behavioralResult) {
            $fraudResults[] = $behavioralResult;
        }
        
        // Device and location analysis
        $deviceResult = $this->analyzeDeviceAndLocation($userId, $transactionData);
        if ($deviceResult) {
            $fraudResults[] = $deviceResult;
        }
        
        // Calculate overall fraud score
        $overallScore = $this->calculateOverallFraudScore($fraudResults);
        $riskLevel = $this->determineRiskLevel($overallScore);
        
        // Store results and take action
        foreach ($fraudResults as $result) {
            $this->storeFraudDetectionResult($result, $transactionId, $userId);
        }
        
        // Take automated action based on risk level
        $actionTaken = $this->takeAutomatedAction($overallScore, $riskLevel, $transactionId, $userId);
        
        // Update user behavior profile
        $this->updateUserBehaviorProfile($userId, $transactionData);
        
        return [
            'fraud_detected' => $overallScore > self::RISK_THRESHOLD_LOW,
            'fraud_score' => $overallScore,
            'risk_level' => $riskLevel,
            'fraud_types' => array_unique(array_column($fraudResults, 'fraud_type')),
            'detection_results' => $fraudResults,
            'action_taken' => $actionTaken,
            'requires_investigation' => $overallScore > self::RISK_THRESHOLD_HIGH
        ];
    }
    
    /**
     * Apply fraud detection rule
     */
    private function applyFraudRule($rule, $userId, $transactionData, $userProfile) {
        $conditions = json_decode($rule['rule_conditions'], true);
        $weights = json_decode($rule['rule_weights'], true);
        $fraudIndicators = [];
        $score = 0;
        
        switch ($rule['fraud_type']) {
            case self::FRAUD_VELOCITY_ABUSE:
                $score = $this->checkVelocityFraud($userId, $transactionData, $conditions, $weights, $fraudIndicators);
                break;
                
            case self::FRAUD_ACCOUNT_TAKEOVER:
                $score = $this->checkAccountTakeover($userId, $transactionData, $conditions, $weights, $fraudIndicators);
                break;
                
            case self::FRAUD_STRUCTURING:
                $score = $this->checkStructuring($userId, $transactionData, $conditions, $weights, $fraudIndicators);
                break;
                
            case self::FRAUD_BEHAVIORAL_ANOMALY:
                $score = $this->checkBehavioralAnomaly($userId, $transactionData, $userProfile, $conditions, $weights, $fraudIndicators);
                break;
        }
        
        if ($score >= $rule['threshold_score']) {
            return [
                'fraud_type' => $rule['fraud_type'],
                'detection_algorithm' => self::ALGO_RULE_BASED,
                'fraud_score' => $score,
                'fraud_indicators' => $fraudIndicators,
                'rule_name' => $rule['rule_name'],
                'confidence_score' => min(100, $score * 1.2)
            ];
        }
        
        return null;
    }
    
    /**
     * Check velocity fraud
     */
    private function checkVelocityFraud($userId, $transactionData, $conditions, $weights, &$fraudIndicators) {
        $score = 0;
        
        // Check transaction count in last hour
        $query = "SELECT COUNT(*) FROM transaction_validations 
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $transactionCount = $stmt->fetchColumn();
        
        if ($transactionCount >= $conditions['transaction_count_1h']) {
            $fraudIndicators[] = "High transaction velocity: $transactionCount transactions in 1 hour";
            $score += 40 * ($weights['count'] ?? 0.4);
        }
        
        // Check transaction amount in last hour
        $query = "SELECT COALESCE(SUM(amount), 0) FROM transaction_validations 
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $totalAmount = $stmt->fetchColumn();
        
        if ($totalAmount >= $conditions['transaction_amount_1h']) {
            $fraudIndicators[] = "High transaction amount: $$totalAmount in 1 hour";
            $score += 35 * ($weights['amount'] ?? 0.4);
        }
        
        // Check unique recipients
        $query = "SELECT COUNT(DISTINCT recipient_address) FROM transaction_validations 
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $uniqueRecipients = $stmt->fetchColumn();
        
        if ($uniqueRecipients >= $conditions['unique_recipients_1h']) {
            $fraudIndicators[] = "Multiple recipients: $uniqueRecipients unique recipients";
            $score += 25 * ($weights['recipients'] ?? 0.2);
        }
        
        return $score;
    }
    
    /**
     * Check account takeover
     */
    private function checkAccountTakeover($userId, $transactionData, $conditions, $weights, &$fraudIndicators) {
        $score = 0;
        
        // Check for new device
        $deviceHash = $this->generateDeviceHash();
        $query = "SELECT COUNT(*) FROM device_fingerprints WHERE user_id = ? AND device_hash = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $deviceHash]);
        $deviceKnown = $stmt->fetchColumn() > 0;
        
        if (!$deviceKnown && $conditions['new_device']) {
            $fraudIndicators[] = "New device detected";
            $score += 30 * ($weights['device'] ?? 0.3);
        }
        
        // Check for location change
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $query = "SELECT ip_address FROM user_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $recentIPs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array($currentIP, $recentIPs) && $conditions['location_change']) {
            $fraudIndicators[] = "New location/IP address";
            $score += 20 * ($weights['location'] ?? 0.2);
        }
        
        // Check for recent password change
        $query = "SELECT COUNT(*) FROM security_events 
                  WHERE user_id = ? AND event_type = 'password_changed' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $passwordChanged = $stmt->fetchColumn() > 0;
        
        if ($passwordChanged && $conditions['password_change']) {
            $fraudIndicators[] = "Recent password change";
            $score += 30 * ($weights['password'] ?? 0.3);
        }
        
        // Check for large transaction
        $amount = $transactionData['amount'] ?? 0;
        if ($amount > 10000 && $conditions['large_transaction']) {
            $fraudIndicators[] = "Large transaction amount: $$amount";
            $score += 20 * ($weights['transaction'] ?? 0.2);
        }
        
        return $score;
    }
    
    /**
     * Check structuring patterns
     */
    private function checkStructuring($userId, $transactionData, $conditions, $weights, &$fraudIndicators) {
        $score = 0;
        $amount = $transactionData['amount'] ?? 0;
        
        // Check if amount is just below reporting threshold
        if ($amount >= $conditions['amount_just_below_threshold'] && $amount < 10000) {
            $fraudIndicators[] = "Amount just below reporting threshold: $$amount";
            $score += 50 * ($weights['amount'] ?? 0.5);
        }
        
        // Check for multiple transactions today
        $query = "SELECT COUNT(*) FROM transaction_validations 
                  WHERE user_id = ? AND DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $dailyTransactions = $stmt->fetchColumn();
        
        if ($dailyTransactions >= $conditions['multiple_transactions_daily']) {
            $fraudIndicators[] = "Multiple daily transactions: $dailyTransactions";
            $score += 30 * ($weights['frequency'] ?? 0.3);
        }
        
        // Check for round amounts
        if ($amount % 1000 == 0 && $conditions['round_amounts']) {
            $fraudIndicators[] = "Round amount pattern: $$amount";
            $score += 20 * ($weights['pattern'] ?? 0.2);
        }
        
        return $score;
    }
    
    /**
     * Check behavioral anomalies
     */
    private function checkBehavioralAnomaly($userId, $transactionData, $userProfile, $conditions, $weights, &$fraudIndicators) {
        $score = 0;
        
        if (!$userProfile || !$userProfile['baseline_established']) {
            return 0; // Can't detect anomalies without baseline
        }
        
        $profileData = json_decode($userProfile['profile_data'], true);
        $transactionPatterns = json_decode($userProfile['transaction_patterns'], true);
        
        // Check deviation from baseline transaction amount
        $amount = $transactionData['amount'] ?? 0;
        $avgAmount = $transactionPatterns['avg_amount'] ?? 0;
        $stdDev = $transactionPatterns['amount_std_dev'] ?? 1;
        
        if ($stdDev > 0) {
            $zScore = abs(($amount - $avgAmount) / $stdDev);
            if ($zScore >= $conditions['deviation_from_baseline']) {
                $fraudIndicators[] = "Amount deviation: {$zScore} standard deviations from baseline";
                $score += 60 * ($weights['deviation'] ?? 0.6);
            }
        }
        
        // Check unusual time
        $hour = date('H');
        $usualHours = $transactionPatterns['usual_hours'] ?? [];
        if (!in_array($hour, $usualHours) && $conditions['unusual_time']) {
            $fraudIndicators[] = "Unusual transaction time: {$hour}:00";
            $score += 20 * ($weights['time'] ?? 0.2);
        }
        
        return $score;
    }
    
    /**
     * Apply machine learning models (placeholder)
     */
    private function applyMachineLearningModels($userId, $transactionData, $userProfile) {
        // Placeholder for ML model integration
        // In production, integrate with TensorFlow, scikit-learn, or cloud ML services
        
        $features = $this->extractFeatures($userId, $transactionData, $userProfile);
        $mlScore = $this->calculateMLScore($features);
        
        if ($mlScore > 60) {
            return [
                'fraud_type' => 'ml_detected_anomaly',
                'detection_algorithm' => self::ALGO_MACHINE_LEARNING,
                'fraud_score' => $mlScore,
                'fraud_indicators' => ['Machine learning model detected anomaly'],
                'confidence_score' => $mlScore * 0.9,
                'model_version' => '1.0'
            ];
        }
        
        return null;
    }
    
    /**
     * Helper methods
     */
    
    private function getUserBehaviorProfile($userId) {
        $query = "SELECT * FROM user_behavior_profiles WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    private function generateDeviceHash() {
        $deviceInfo = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return hash('sha256', json_encode($deviceInfo));
    }
    
    private function calculateOverallFraudScore($fraudResults) {
        if (empty($fraudResults)) return 0;
        
        $scores = array_column($fraudResults, 'fraud_score');
        return min(100, max($scores) + (array_sum($scores) - max($scores)) * 0.3);
    }
    
    private function determineRiskLevel($score) {
        if ($score >= self::RISK_THRESHOLD_CRITICAL) return 'critical';
        if ($score >= self::RISK_THRESHOLD_HIGH) return 'high';
        if ($score >= self::RISK_THRESHOLD_MEDIUM) return 'medium';
        return 'low';
    }
    
    private function storeFraudDetectionResult($result, $transactionId, $userId) {
        $resultId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO fraud_detection_results (
            id, user_id, transaction_id, fraud_type, detection_algorithm,
            fraud_score, risk_level, fraud_indicators, confidence_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $resultId, $userId, $transactionId, $result['fraud_type'],
            $result['detection_algorithm'], $result['fraud_score'],
            $this->determineRiskLevel($result['fraud_score']),
            json_encode($result['fraud_indicators']), $result['confidence_score'] ?? null
        ]);
        
        return $resultId;
    }
    
    private function takeAutomatedAction($fraudScore, $riskLevel, $transactionId, $userId) {
        if ($fraudScore >= self::RISK_THRESHOLD_CRITICAL) {
            // Block transaction and user account
            $this->blockTransaction($transactionId);
            $this->flagUserAccount($userId, 'critical_fraud_detected');
            return 'block';
        } elseif ($fraudScore >= self::RISK_THRESHOLD_HIGH) {
            // Flag for manual review
            $this->flagTransaction($transactionId);
            return 'flag';
        } elseif ($fraudScore >= self::RISK_THRESHOLD_MEDIUM) {
            // Log for monitoring
            $this->logFraudAlert($transactionId, $userId, $fraudScore);
            return 'monitor';
        }
        
        return 'none';
    }
    
    private function createFraudRule($ruleName, $fraudType, $conditions, $weights, $threshold) {
        $ruleId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO fraud_detection_rules (
            id, rule_name, fraud_type, rule_conditions, rule_weights, threshold_score
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $ruleId, $ruleName, $fraudType, json_encode($conditions),
            json_encode($weights), $threshold
        ]);
        
        return $ruleId;
    }
    
    private function extractFeatures($userId, $transactionData, $userProfile) {
        // Extract features for ML model
        return [
            'amount' => $transactionData['amount'] ?? 0,
            'hour' => date('H'),
            'day_of_week' => date('w'),
            'user_age_days' => $this->getUserAgeDays($userId),
            'transaction_count_today' => $this->getTransactionCountToday($userId),
            'avg_transaction_amount' => $this->getAvgTransactionAmount($userId)
        ];
    }
    
    private function calculateMLScore($features) {
        // Simplified ML scoring (placeholder)
        $score = 0;
        
        if ($features['amount'] > 10000) $score += 30;
        if ($features['hour'] < 6 || $features['hour'] > 22) $score += 20;
        if ($features['transaction_count_today'] > 5) $score += 25;
        
        return min(100, $score);
    }
    
    private function getUserAgeDays($userId) {
        $query = "SELECT DATEDIFF(NOW(), created_at) FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function getTransactionCountToday($userId) {
        $query = "SELECT COUNT(*) FROM transaction_validations 
                  WHERE user_id = ? AND DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getAvgTransactionAmount($userId) {
        $query = "SELECT AVG(amount) FROM transaction_validations WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function blockTransaction($transactionId) {
        // Implementation to block transaction
        logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, 'transaction_blocked', SecurityLogger::LEVEL_CRITICAL,
            'Transaction blocked due to fraud detection', ['transaction_id' => $transactionId]);
    }
    
    private function flagUserAccount($userId, $reason) {
        // Implementation to flag user account
        logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, 'user_account_flagged', SecurityLogger::LEVEL_WARNING,
            'User account flagged for fraud', ['user_id' => $userId, 'reason' => $reason]);
    }
    
    private function flagTransaction($transactionId) {
        // Implementation to flag transaction for review
        logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, 'transaction_flagged', SecurityLogger::LEVEL_WARNING,
            'Transaction flagged for manual review', ['transaction_id' => $transactionId]);
    }
    
    private function logFraudAlert($transactionId, $userId, $fraudScore) {
        logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, 'fraud_alert', SecurityLogger::LEVEL_INFO,
            'Fraud alert generated', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
                'fraud_score' => $fraudScore
            ]);
    }
    
    private function updateUserBehaviorProfile($userId, $transactionData) {
        // Update user behavior profile with new transaction data
        // This would involve statistical calculations and pattern updates
        // Placeholder implementation
    }
    
    private function analyzeBehavioralPatterns($userId, $transactionData, $userProfile) {
        // Placeholder for behavioral pattern analysis
        return null;
    }
    
    private function analyzeDeviceAndLocation($userId, $transactionData) {
        // Placeholder for device and location analysis
        return null;
    }
}

// Convenience functions
function analyzeTransactionFraud($transactionId, $userId, $transactionData) {
    $fraudDetection = FraudDetection::getInstance();
    return $fraudDetection->analyzeTransaction($transactionId, $userId, $transactionData);
}
?>
