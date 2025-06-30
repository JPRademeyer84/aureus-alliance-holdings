<?php
/**
 * FINANCIAL MONITORING SYSTEM
 * Real-time balance monitoring, alerts, and anomaly detection
 */

require_once 'security-logger.php';
require_once 'financial-security.php';

class FinancialMonitoring {
    private static $instance = null;
    private $db;
    
    // Alert thresholds
    const ALERT_LEVEL_LOW = 1;
    const ALERT_LEVEL_MEDIUM = 2;
    const ALERT_LEVEL_HIGH = 3;
    const ALERT_LEVEL_CRITICAL = 4;
    
    // Monitoring types
    const MONITOR_BALANCE_CHANGE = 'balance_change';
    const MONITOR_LARGE_TRANSACTION = 'large_transaction';
    const MONITOR_VELOCITY = 'velocity';
    const MONITOR_PATTERN_ANOMALY = 'pattern_anomaly';
    const MONITOR_FRAUD_INDICATOR = 'fraud_indicator';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeMonitoringTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize monitoring tables
     */
    private function initializeMonitoringTables() {
        $tables = [
            // Real-time balance monitoring
            "CREATE TABLE IF NOT EXISTS balance_monitoring (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                balance_type ENUM('usdt', 'nft', 'total') NOT NULL,
                previous_balance DECIMAL(15,8) NOT NULL,
                current_balance DECIMAL(15,8) NOT NULL,
                change_amount DECIMAL(15,8) NOT NULL,
                change_percentage DECIMAL(8,4) NOT NULL,
                change_reason VARCHAR(100),
                transaction_id VARCHAR(36),
                alert_triggered BOOLEAN DEFAULT FALSE,
                alert_level TINYINT DEFAULT 0,
                monitored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_alert_triggered (alert_triggered),
                INDEX idx_monitored_at (monitored_at)
            )",
            
            // Financial alerts
            "CREATE TABLE IF NOT EXISTS financial_alerts (
                id VARCHAR(36) PRIMARY KEY,
                alert_type VARCHAR(50) NOT NULL,
                alert_level TINYINT NOT NULL,
                user_id VARCHAR(36),
                transaction_id VARCHAR(36),
                alert_title VARCHAR(200) NOT NULL,
                alert_message TEXT NOT NULL,
                alert_data JSON,
                triggered_by VARCHAR(100),
                acknowledged BOOLEAN DEFAULT FALSE,
                acknowledged_by VARCHAR(36),
                acknowledged_at TIMESTAMP NULL,
                resolved BOOLEAN DEFAULT FALSE,
                resolved_by VARCHAR(36),
                resolved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_alert_type (alert_type),
                INDEX idx_alert_level (alert_level),
                INDEX idx_user_id (user_id),
                INDEX idx_acknowledged (acknowledged),
                INDEX idx_resolved (resolved),
                INDEX idx_created_at (created_at)
            )",
            
            // Anomaly detection
            "CREATE TABLE IF NOT EXISTS financial_anomalies (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                anomaly_type VARCHAR(50) NOT NULL,
                anomaly_score DECIMAL(5,2) NOT NULL,
                baseline_value DECIMAL(15,8),
                current_value DECIMAL(15,8),
                deviation_percentage DECIMAL(8,4),
                detection_algorithm VARCHAR(50),
                anomaly_details JSON,
                investigation_status ENUM('pending', 'investigating', 'resolved', 'false_positive') DEFAULT 'pending',
                investigated_by VARCHAR(36),
                investigation_notes TEXT,
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_anomaly_type (anomaly_type),
                INDEX idx_anomaly_score (anomaly_score),
                INDEX idx_investigation_status (investigation_status),
                INDEX idx_detected_at (detected_at)
            )",
            
            // Real-time monitoring rules
            "CREATE TABLE IF NOT EXISTS monitoring_rules (
                id VARCHAR(36) PRIMARY KEY,
                rule_name VARCHAR(100) NOT NULL,
                rule_type VARCHAR(50) NOT NULL,
                rule_conditions JSON NOT NULL,
                alert_level TINYINT NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_rule_type (rule_type),
                INDEX idx_is_active (is_active)
            )",
            
            // Balance snapshots for trend analysis
            "CREATE TABLE IF NOT EXISTS balance_snapshots (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                snapshot_type ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL,
                usdt_balance DECIMAL(15,8) NOT NULL,
                nft_balance INT NOT NULL,
                total_value_usd DECIMAL(15,8) NOT NULL,
                snapshot_date TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_snapshot (user_id, snapshot_type, snapshot_date),
                INDEX idx_user_id (user_id),
                INDEX idx_snapshot_type (snapshot_type),
                INDEX idx_snapshot_date (snapshot_date)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create financial monitoring table: " . $e->getMessage());
            }
        }
        
        $this->initializeDefaultRules();
    }
    
    /**
     * Initialize default monitoring rules
     */
    private function initializeDefaultRules() {
        // Check if rules already exist
        $query = "SELECT COUNT(*) FROM monitoring_rules";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Rules already initialized
        }
        
        $defaultRules = [
            [
                'rule_name' => 'Large Balance Change',
                'rule_type' => self::MONITOR_BALANCE_CHANGE,
                'rule_conditions' => [
                    'change_threshold_percentage' => 50,
                    'change_threshold_amount' => 10000,
                    'time_window_minutes' => 60
                ],
                'alert_level' => self::ALERT_LEVEL_HIGH
            ],
            [
                'rule_name' => 'High Velocity Transactions',
                'rule_type' => self::MONITOR_VELOCITY,
                'rule_conditions' => [
                    'transaction_count_threshold' => 10,
                    'amount_threshold' => 50000,
                    'time_window_minutes' => 60
                ],
                'alert_level' => self::ALERT_LEVEL_MEDIUM
            ],
            [
                'rule_name' => 'Large Single Transaction',
                'rule_type' => self::MONITOR_LARGE_TRANSACTION,
                'rule_conditions' => [
                    'amount_threshold' => 25000,
                    'require_approval' => true
                ],
                'alert_level' => self::ALERT_LEVEL_HIGH
            ],
            [
                'rule_name' => 'Unusual Pattern Detection',
                'rule_type' => self::MONITOR_PATTERN_ANOMALY,
                'rule_conditions' => [
                    'deviation_threshold' => 3.0, // 3 standard deviations
                    'minimum_history_days' => 7
                ],
                'alert_level' => self::ALERT_LEVEL_MEDIUM
            ],
            [
                'rule_name' => 'Fraud Indicator Alert',
                'rule_type' => self::MONITOR_FRAUD_INDICATOR,
                'rule_conditions' => [
                    'fraud_score_threshold' => 70,
                    'immediate_block' => false
                ],
                'alert_level' => self::ALERT_LEVEL_CRITICAL
            ]
        ];
        
        foreach ($defaultRules as $rule) {
            $this->createMonitoringRule($rule['rule_name'], $rule['rule_type'], $rule['rule_conditions'], $rule['alert_level']);
        }
    }
    
    /**
     * Monitor balance changes in real-time
     */
    public function monitorBalanceChange($userId, $balanceType, $previousBalance, $currentBalance, $changeReason = null, $transactionId = null) {
        $changeAmount = $currentBalance - $previousBalance;
        $changePercentage = $previousBalance > 0 ? ($changeAmount / $previousBalance) * 100 : 0;
        
        // Store monitoring record
        $monitoringId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO balance_monitoring (
            id, user_id, balance_type, previous_balance, current_balance,
            change_amount, change_percentage, change_reason, transaction_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $monitoringId, $userId, $balanceType, $previousBalance, $currentBalance,
            $changeAmount, $changePercentage, $changeReason, $transactionId
        ]);
        
        // Check monitoring rules
        $this->checkMonitoringRules($userId, self::MONITOR_BALANCE_CHANGE, [
            'balance_type' => $balanceType,
            'change_amount' => $changeAmount,
            'change_percentage' => abs($changePercentage),
            'transaction_id' => $transactionId
        ]);
        
        // Update balance snapshots
        $this->updateBalanceSnapshots($userId);
        
        return $monitoringId;
    }
    
    /**
     * Monitor transaction velocity
     */
    public function monitorTransactionVelocity($userId, $transactionType, $amount, $transactionId) {
        // Get recent transactions in the last hour
        $query = "SELECT COUNT(*) as transaction_count, SUM(amount) as total_amount
                  FROM transaction_validations 
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $velocity = $stmt->fetch();
        
        // Check velocity rules
        $this->checkMonitoringRules($userId, self::MONITOR_VELOCITY, [
            'transaction_count' => $velocity['transaction_count'],
            'total_amount' => $velocity['total_amount'],
            'current_transaction_amount' => $amount,
            'transaction_id' => $transactionId
        ]);
    }
    
    /**
     * Detect financial anomalies using statistical analysis
     */
    public function detectAnomalies($userId) {
        // Get user's transaction history for baseline
        $query = "SELECT amount, created_at
                  FROM transaction_validations 
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  ORDER BY created_at";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll();
        
        if (count($transactions) < 10) {
            return; // Not enough data for anomaly detection
        }
        
        // Calculate statistical baseline
        $amounts = array_column($transactions, 'amount');
        $mean = array_sum($amounts) / count($amounts);
        $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $amounts)) / count($amounts);
        $stdDev = sqrt($variance);
        
        // Check recent transactions for anomalies
        $recentQuery = "SELECT * FROM transaction_validations 
                       WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $recentStmt = $this->db->prepare($recentQuery);
        $recentStmt->execute([$userId]);
        $recentTransactions = $recentStmt->fetchAll();
        
        foreach ($recentTransactions as $transaction) {
            $zScore = $stdDev > 0 ? abs(($transaction['amount'] - $mean) / $stdDev) : 0;
            
            if ($zScore > 3.0) { // 3 standard deviations
                $this->recordAnomaly($userId, 'statistical_outlier', $zScore * 10, $mean, $transaction['amount'], [
                    'z_score' => $zScore,
                    'mean' => $mean,
                    'std_dev' => $stdDev,
                    'transaction_id' => $transaction['transaction_id']
                ]);
            }
        }
    }
    
    /**
     * Create financial alert
     */
    public function createAlert($alertType, $alertLevel, $userId, $title, $message, $alertData = [], $transactionId = null) {
        $alertId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO financial_alerts (
            id, alert_type, alert_level, user_id, transaction_id,
            alert_title, alert_message, alert_data, triggered_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $alertId, $alertType, $alertLevel, $userId, $transactionId,
            $title, $message, json_encode($alertData), 'system'
        ]);
        
        // Send real-time notifications for high/critical alerts
        if ($alertLevel >= self::ALERT_LEVEL_HIGH) {
            $this->sendRealTimeNotification($alertId, $alertType, $alertLevel, $title, $message);
        }
        
        // Log security event
        logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, 'financial_alert_created', 
            $alertLevel >= self::ALERT_LEVEL_HIGH ? SecurityLogger::LEVEL_WARNING : SecurityLogger::LEVEL_INFO,
            "Financial alert created: $title", array_merge($alertData, [
                'alert_id' => $alertId,
                'alert_type' => $alertType,
                'alert_level' => $alertLevel,
                'user_id' => $userId
            ]));
        
        return $alertId;
    }
    
    /**
     * Check monitoring rules against current data
     */
    private function checkMonitoringRules($userId, $ruleType, $data) {
        $query = "SELECT * FROM monitoring_rules WHERE rule_type = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ruleType]);
        $rules = $stmt->fetchAll();
        
        foreach ($rules as $rule) {
            $conditions = json_decode($rule['rule_conditions'], true);
            $triggered = false;
            $alertMessage = '';
            
            switch ($ruleType) {
                case self::MONITOR_BALANCE_CHANGE:
                    if (isset($conditions['change_threshold_percentage']) && 
                        $data['change_percentage'] >= $conditions['change_threshold_percentage']) {
                        $triggered = true;
                        $alertMessage = "Balance changed by {$data['change_percentage']}% (${$data['change_amount']})";
                    }
                    break;
                    
                case self::MONITOR_VELOCITY:
                    if (isset($conditions['transaction_count_threshold']) && 
                        $data['transaction_count'] >= $conditions['transaction_count_threshold']) {
                        $triggered = true;
                        $alertMessage = "High transaction velocity: {$data['transaction_count']} transactions totaling ${$data['total_amount']}";
                    }
                    break;
                    
                case self::MONITOR_LARGE_TRANSACTION:
                    if (isset($conditions['amount_threshold']) && 
                        $data['current_transaction_amount'] >= $conditions['amount_threshold']) {
                        $triggered = true;
                        $alertMessage = "Large transaction: ${$data['current_transaction_amount']}";
                    }
                    break;
            }
            
            if ($triggered) {
                $this->createAlert($ruleType, $rule['alert_level'], $userId, $rule['rule_name'], $alertMessage, $data, $data['transaction_id'] ?? null);
            }
        }
    }
    
    /**
     * Record financial anomaly
     */
    private function recordAnomaly($userId, $anomalyType, $anomalyScore, $baselineValue, $currentValue, $details) {
        $anomalyId = bin2hex(random_bytes(16));
        $deviationPercentage = $baselineValue > 0 ? (($currentValue - $baselineValue) / $baselineValue) * 100 : 0;
        
        $query = "INSERT INTO financial_anomalies (
            id, user_id, anomaly_type, anomaly_score, baseline_value,
            current_value, deviation_percentage, detection_algorithm, anomaly_details
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $anomalyId, $userId, $anomalyType, $anomalyScore, $baselineValue,
            $currentValue, $deviationPercentage, 'statistical_analysis', json_encode($details)
        ]);
        
        // Create alert for high-score anomalies
        if ($anomalyScore >= 70) {
            $this->createAlert(self::MONITOR_PATTERN_ANOMALY, self::ALERT_LEVEL_HIGH, $userId,
                'Financial Anomaly Detected', 
                "Unusual pattern detected with score: $anomalyScore", 
                $details);
        }
        
        return $anomalyId;
    }
    
    /**
     * Create monitoring rule
     */
    private function createMonitoringRule($ruleName, $ruleType, $conditions, $alertLevel) {
        $ruleId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO monitoring_rules (
            id, rule_name, rule_type, rule_conditions, alert_level, created_by
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $ruleId, $ruleName, $ruleType, json_encode($conditions), $alertLevel, 'system'
        ]);
        
        return $ruleId;
    }
    
    /**
     * Update balance snapshots for trend analysis
     */
    private function updateBalanceSnapshots($userId) {
        // Get current balance
        $commissionSecurity = CommissionSecurity::getInstance();
        $balance = $commissionSecurity->getUserBalance($userId);
        
        if (!$balance) return;
        
        $totalValueUSD = $balance['available_usdt_balance'] + ($balance['available_nft_balance'] * 5); // Assuming $5 per NFT
        
        // Create hourly snapshot
        $snapshotId = bin2hex(random_bytes(16));
        $currentHour = date('Y-m-d H:00:00');
        
        $query = "INSERT INTO balance_snapshots (
            id, user_id, snapshot_type, usdt_balance, nft_balance, total_value_usd, snapshot_date
        ) VALUES (?, ?, 'hourly', ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            usdt_balance = VALUES(usdt_balance),
            nft_balance = VALUES(nft_balance),
            total_value_usd = VALUES(total_value_usd)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $snapshotId, $userId, $balance['available_usdt_balance'], 
            $balance['available_nft_balance'], $totalValueUSD, $currentHour
        ]);
    }
    
    /**
     * Send real-time notification
     */
    private function sendRealTimeNotification($alertId, $alertType, $alertLevel, $title, $message) {
        // In production, integrate with notification services (email, SMS, Slack, etc.)
        // For now, log the notification
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'real_time_notification', SecurityLogger::LEVEL_INFO,
            "Real-time notification sent: $title", [
                'alert_id' => $alertId,
                'alert_type' => $alertType,
                'alert_level' => $alertLevel,
                'notification_channels' => ['email', 'dashboard']
            ]);
    }
}

// Convenience functions
function monitorBalanceChange($userId, $balanceType, $previousBalance, $currentBalance, $changeReason = null, $transactionId = null) {
    $monitoring = FinancialMonitoring::getInstance();
    return $monitoring->monitorBalanceChange($userId, $balanceType, $previousBalance, $currentBalance, $changeReason, $transactionId);
}

function monitorTransactionVelocity($userId, $transactionType, $amount, $transactionId) {
    $monitoring = FinancialMonitoring::getInstance();
    return $monitoring->monitorTransactionVelocity($userId, $transactionType, $amount, $transactionId);
}

function detectFinancialAnomalies($userId) {
    $monitoring = FinancialMonitoring::getInstance();
    return $monitoring->detectAnomalies($userId);
}

function createFinancialAlert($alertType, $alertLevel, $userId, $title, $message, $alertData = [], $transactionId = null) {
    $monitoring = FinancialMonitoring::getInstance();
    return $monitoring->createAlert($alertType, $alertLevel, $userId, $title, $message, $alertData, $transactionId);
}
?>
