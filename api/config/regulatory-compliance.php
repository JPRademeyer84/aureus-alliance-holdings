<?php
/**
 * REGULATORY COMPLIANCE REPORTING SYSTEM
 * Automated compliance reporting for financial regulations
 */

require_once 'security-logger.php';
require_once 'financial-security.php';

class RegulatoryCompliance {
    private static $instance = null;
    private $db;
    
    // Compliance frameworks
    const FRAMEWORK_AML = 'AML'; // Anti-Money Laundering
    const FRAMEWORK_KYC = 'KYC'; // Know Your Customer
    const FRAMEWORK_CTF = 'CTF'; // Counter-Terrorism Financing
    const FRAMEWORK_GDPR = 'GDPR'; // General Data Protection Regulation
    const FRAMEWORK_SOX = 'SOX'; // Sarbanes-Oxley Act
    const FRAMEWORK_PCI_DSS = 'PCI_DSS'; // Payment Card Industry Data Security Standard
    
    // Report types
    const REPORT_SUSPICIOUS_ACTIVITY = 'SAR'; // Suspicious Activity Report
    const REPORT_CURRENCY_TRANSACTION = 'CTR'; // Currency Transaction Report
    const REPORT_LARGE_CASH_TRANSACTION = 'LCTR'; // Large Cash Transaction Report
    const REPORT_CROSS_BORDER = 'CBR'; // Cross Border Report
    const REPORT_AUDIT_TRAIL = 'ATR'; // Audit Trail Report
    
    // Risk levels
    const RISK_LOW = 1;
    const RISK_MEDIUM = 2;
    const RISK_HIGH = 3;
    const RISK_CRITICAL = 4;
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeComplianceTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize compliance tables
     */
    private function initializeComplianceTables() {
        $tables = [
            // Compliance reports
            "CREATE TABLE IF NOT EXISTS compliance_reports (
                id VARCHAR(36) PRIMARY KEY,
                report_type VARCHAR(10) NOT NULL,
                report_framework VARCHAR(20) NOT NULL,
                report_period_start DATE NOT NULL,
                report_period_end DATE NOT NULL,
                report_data JSON NOT NULL,
                report_summary TEXT,
                risk_level TINYINT NOT NULL,
                compliance_status ENUM('compliant', 'non_compliant', 'pending_review') DEFAULT 'pending_review',
                generated_by VARCHAR(36),
                reviewed_by VARCHAR(36),
                approved_by VARCHAR(36),
                submitted_to_authority BOOLEAN DEFAULT FALSE,
                submission_date TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_report_type (report_type),
                INDEX idx_report_framework (report_framework),
                INDEX idx_report_period (report_period_start, report_period_end),
                INDEX idx_compliance_status (compliance_status),
                INDEX idx_risk_level (risk_level)
            )",
            
            // Suspicious activity tracking
            "CREATE TABLE IF NOT EXISTS suspicious_activities (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                activity_type VARCHAR(50) NOT NULL,
                transaction_ids JSON,
                suspicion_indicators JSON NOT NULL,
                risk_score DECIMAL(5,2) NOT NULL,
                investigation_status ENUM('pending', 'investigating', 'cleared', 'confirmed') DEFAULT 'pending',
                investigator_id VARCHAR(36),
                investigation_notes TEXT,
                reported_to_authority BOOLEAN DEFAULT FALSE,
                authority_reference VARCHAR(100),
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reported_at TIMESTAMP NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_activity_type (activity_type),
                INDEX idx_risk_score (risk_score),
                INDEX idx_investigation_status (investigation_status),
                INDEX idx_detected_at (detected_at)
            )",
            
            // Compliance thresholds
            "CREATE TABLE IF NOT EXISTS compliance_thresholds (
                id VARCHAR(36) PRIMARY KEY,
                threshold_name VARCHAR(100) NOT NULL,
                framework VARCHAR(20) NOT NULL,
                threshold_type VARCHAR(50) NOT NULL,
                threshold_value DECIMAL(15,8) NOT NULL,
                currency VARCHAR(10) DEFAULT 'USD',
                time_period VARCHAR(20), -- daily, weekly, monthly, yearly
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_framework (framework),
                INDEX idx_threshold_type (threshold_type),
                INDEX idx_is_active (is_active)
            )",
            
            // Compliance violations
            "CREATE TABLE IF NOT EXISTS compliance_violations (
                id VARCHAR(36) PRIMARY KEY,
                violation_type VARCHAR(50) NOT NULL,
                framework VARCHAR(20) NOT NULL,
                user_id VARCHAR(36),
                transaction_id VARCHAR(36),
                violation_details JSON NOT NULL,
                severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                auto_detected BOOLEAN DEFAULT TRUE,
                remediation_required BOOLEAN DEFAULT TRUE,
                remediation_status ENUM('pending', 'in_progress', 'completed', 'waived') DEFAULT 'pending',
                remediation_notes TEXT,
                remediated_by VARCHAR(36),
                remediated_at TIMESTAMP NULL,
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_violation_type (violation_type),
                INDEX idx_framework (framework),
                INDEX idx_user_id (user_id),
                INDEX idx_severity (severity),
                INDEX idx_remediation_status (remediation_status)
            )",
            
            // Audit trails
            "CREATE TABLE IF NOT EXISTS compliance_audit_trails (
                id VARCHAR(36) PRIMARY KEY,
                entity_type VARCHAR(50) NOT NULL, -- user, transaction, system
                entity_id VARCHAR(36) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                action_details JSON,
                performed_by VARCHAR(36),
                performed_by_type ENUM('user', 'admin', 'system') NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                compliance_relevant BOOLEAN DEFAULT TRUE,
                retention_period_years INT DEFAULT 7,
                archived BOOLEAN DEFAULT FALSE,
                performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_entity_type (entity_type),
                INDEX idx_entity_id (entity_id),
                INDEX idx_action_type (action_type),
                INDEX idx_performed_by (performed_by),
                INDEX idx_compliance_relevant (compliance_relevant),
                INDEX idx_performed_at (performed_at)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create compliance table: " . $e->getMessage());
            }
        }
        
        $this->initializeComplianceThresholds();
    }
    
    /**
     * Initialize default compliance thresholds
     */
    private function initializeComplianceThresholds() {
        // Check if thresholds already exist
        $query = "SELECT COUNT(*) FROM compliance_thresholds";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Thresholds already initialized
        }
        
        $defaultThresholds = [
            // AML thresholds
            ['AML Cash Transaction Reporting', self::FRAMEWORK_AML, 'cash_transaction', 10000, 'USD', 'daily'],
            ['AML Suspicious Activity', self::FRAMEWORK_AML, 'suspicious_activity', 5000, 'USD', 'single'],
            ['AML Structuring Detection', self::FRAMEWORK_AML, 'structuring', 9000, 'USD', 'daily'],
            
            // KYC thresholds
            ['KYC Enhanced Due Diligence', self::FRAMEWORK_KYC, 'enhanced_dd', 25000, 'USD', 'monthly'],
            ['KYC High Risk Customer', self::FRAMEWORK_KYC, 'high_risk', 50000, 'USD', 'yearly'],
            
            // CTF thresholds
            ['CTF Large Transaction', self::FRAMEWORK_CTF, 'large_transaction', 15000, 'USD', 'single'],
            ['CTF Cross Border', self::FRAMEWORK_CTF, 'cross_border', 3000, 'USD', 'single'],
            
            // General compliance
            ['Large Cash Transaction', 'GENERAL', 'large_cash', 10000, 'USD', 'single'],
            ['Velocity Monitoring', 'GENERAL', 'velocity', 100000, 'USD', 'daily']
        ];
        
        foreach ($defaultThresholds as $threshold) {
            $this->createComplianceThreshold($threshold[0], $threshold[1], $threshold[2], $threshold[3], $threshold[4], $threshold[5]);
        }
    }
    
    /**
     * Check transaction compliance
     */
    public function checkTransactionCompliance($transactionId, $userId, $transactionType, $amount, $currency = 'USD', $additionalData = []) {
        $violations = [];
        $reports = [];
        
        // Check against all active thresholds
        $query = "SELECT * FROM compliance_thresholds WHERE is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $thresholds = $stmt->fetchAll();
        
        foreach ($thresholds as $threshold) {
            $violation = $this->checkThresholdViolation($threshold, $userId, $transactionType, $amount, $currency, $additionalData);
            if ($violation) {
                $violations[] = $violation;
                
                // Generate compliance report if needed
                if ($violation['severity'] === 'high' || $violation['severity'] === 'critical') {
                    $reports[] = $this->generateComplianceReport($threshold['framework'], $violation);
                }
            }
        }
        
        // Check for suspicious activity patterns
        $suspiciousActivity = $this->detectSuspiciousActivity($userId, $transactionType, $amount, $additionalData);
        if ($suspiciousActivity) {
            $this->recordSuspiciousActivity($userId, $suspiciousActivity, [$transactionId]);
        }
        
        // Log compliance check
        $this->logComplianceAuditTrail('transaction', $transactionId, 'compliance_check', [
            'violations_found' => count($violations),
            'reports_generated' => count($reports),
            'suspicious_activity' => $suspiciousActivity !== null
        ], $userId, 'system');
        
        return [
            'compliant' => empty($violations),
            'violations' => $violations,
            'reports_generated' => $reports,
            'suspicious_activity' => $suspiciousActivity,
            'risk_level' => $this->calculateRiskLevel($violations, $suspiciousActivity)
        ];
    }
    
    /**
     * Generate suspicious activity report (SAR)
     */
    public function generateSuspiciousActivityReport($userId, $suspiciousActivityId, $reportPeriodDays = 30) {
        // Get suspicious activity details
        $query = "SELECT * FROM suspicious_activities WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$suspiciousActivityId]);
        $activity = $stmt->fetch();
        
        if (!$activity) {
            throw new Exception("Suspicious activity not found: $suspiciousActivityId");
        }
        
        // Get related transactions
        $transactionIds = json_decode($activity['transaction_ids'], true);
        $transactions = $this->getTransactionDetails($transactionIds);
        
        // Get user information
        $userInfo = $this->getUserComplianceInfo($userId);
        
        // Generate report data
        $reportData = [
            'report_type' => self::REPORT_SUSPICIOUS_ACTIVITY,
            'subject_user' => $userInfo,
            'suspicious_activity' => [
                'activity_type' => $activity['activity_type'],
                'suspicion_indicators' => json_decode($activity['suspicion_indicators'], true),
                'risk_score' => $activity['risk_score'],
                'detected_at' => $activity['detected_at']
            ],
            'related_transactions' => $transactions,
            'investigation_notes' => $activity['investigation_notes'],
            'reporting_institution' => $this->getInstitutionInfo()
        ];
        
        $reportId = $this->createComplianceReport(
            self::REPORT_SUSPICIOUS_ACTIVITY,
            self::FRAMEWORK_AML,
            date('Y-m-d', strtotime("-$reportPeriodDays days")),
            date('Y-m-d'),
            $reportData,
            "Suspicious Activity Report for User ID: $userId",
            self::RISK_HIGH
        );
        
        // Mark activity as reported
        $updateQuery = "UPDATE suspicious_activities SET reported_to_authority = TRUE, reported_at = NOW() WHERE id = ?";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute([$suspiciousActivityId]);
        
        return $reportId;
    }
    
    /**
     * Generate currency transaction report (CTR)
     */
    public function generateCurrencyTransactionReport($userId, $reportPeriodDays = 1) {
        $startDate = date('Y-m-d', strtotime("-$reportPeriodDays days"));
        $endDate = date('Y-m-d');
        
        // Get large transactions for the period
        $query = "SELECT * FROM transaction_validations 
                  WHERE user_id = ? AND amount >= 10000 
                  AND created_at BETWEEN ? AND ?
                  ORDER BY created_at";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $startDate, $endDate]);
        $transactions = $stmt->fetchAll();
        
        if (empty($transactions)) {
            return null; // No reportable transactions
        }
        
        $userInfo = $this->getUserComplianceInfo($userId);
        
        $reportData = [
            'report_type' => self::REPORT_CURRENCY_TRANSACTION,
            'subject_user' => $userInfo,
            'reporting_period' => ['start' => $startDate, 'end' => $endDate],
            'transactions' => $transactions,
            'total_amount' => array_sum(array_column($transactions, 'amount')),
            'transaction_count' => count($transactions),
            'reporting_institution' => $this->getInstitutionInfo()
        ];
        
        return $this->createComplianceReport(
            self::REPORT_CURRENCY_TRANSACTION,
            self::FRAMEWORK_AML,
            $startDate,
            $endDate,
            $reportData,
            "Currency Transaction Report for User ID: $userId",
            self::RISK_MEDIUM
        );
    }
    
    /**
     * Generate audit trail report
     */
    public function generateAuditTrailReport($entityType, $entityId, $reportPeriodDays = 30) {
        $startDate = date('Y-m-d', strtotime("-$reportPeriodDays days"));
        $endDate = date('Y-m-d');
        
        $query = "SELECT * FROM compliance_audit_trails 
                  WHERE entity_type = ? AND entity_id = ?
                  AND performed_at BETWEEN ? AND ?
                  ORDER BY performed_at";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$entityType, $entityId, $startDate, $endDate]);
        $auditTrail = $stmt->fetchAll();
        
        $reportData = [
            'report_type' => self::REPORT_AUDIT_TRAIL,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reporting_period' => ['start' => $startDate, 'end' => $endDate],
            'audit_entries' => $auditTrail,
            'total_entries' => count($auditTrail),
            'compliance_relevant_entries' => count(array_filter($auditTrail, function($entry) {
                return $entry['compliance_relevant'];
            }))
        ];
        
        return $this->createComplianceReport(
            self::REPORT_AUDIT_TRAIL,
            self::FRAMEWORK_SOX,
            $startDate,
            $endDate,
            $reportData,
            "Audit Trail Report for $entityType: $entityId",
            self::RISK_LOW
        );
    }
    
    /**
     * Helper methods
     */
    
    private function createComplianceThreshold($name, $framework, $type, $value, $currency, $period) {
        $thresholdId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO compliance_thresholds (
            id, threshold_name, framework, threshold_type, threshold_value,
            currency, time_period
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$thresholdId, $name, $framework, $type, $value, $currency, $period]);
        
        return $thresholdId;
    }
    
    private function checkThresholdViolation($threshold, $userId, $transactionType, $amount, $currency, $additionalData) {
        // Convert amount to threshold currency if needed
        $convertedAmount = $this->convertCurrency($amount, $currency, $threshold['currency']);
        
        if ($convertedAmount >= $threshold['threshold_value']) {
            return $this->recordComplianceViolation(
                $threshold['threshold_type'],
                $threshold['framework'],
                $userId,
                $additionalData['transaction_id'] ?? null,
                [
                    'threshold_name' => $threshold['threshold_name'],
                    'threshold_value' => $threshold['threshold_value'],
                    'actual_value' => $convertedAmount,
                    'currency' => $threshold['currency'],
                    'transaction_type' => $transactionType
                ],
                $this->determineSeverity($convertedAmount, $threshold['threshold_value'])
            );
        }
        
        return null;
    }
    
    private function detectSuspiciousActivity($userId, $transactionType, $amount, $additionalData) {
        $suspicionIndicators = [];
        $riskScore = 0;
        
        // Check for round number amounts (potential structuring)
        if ($amount % 1000 == 0 && $amount >= 5000) {
            $suspicionIndicators[] = 'Round number amount';
            $riskScore += 20;
        }
        
        // Check for unusual timing
        $hour = date('H');
        if ($hour < 6 || $hour > 22) {
            $suspicionIndicators[] = 'Unusual transaction time';
            $riskScore += 15;
        }
        
        // Check for rapid succession of transactions
        $query = "SELECT COUNT(*) FROM transaction_validations 
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $recentTransactions = $stmt->fetchColumn();
        
        if ($recentTransactions > 5) {
            $suspicionIndicators[] = 'High transaction velocity';
            $riskScore += 25;
        }
        
        // Return suspicious activity if risk score is high enough
        if ($riskScore >= 40) {
            return [
                'activity_type' => 'potential_structuring',
                'suspicion_indicators' => $suspicionIndicators,
                'risk_score' => $riskScore
            ];
        }
        
        return null;
    }
    
    private function recordSuspiciousActivity($userId, $suspiciousActivity, $transactionIds) {
        $activityId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO suspicious_activities (
            id, user_id, activity_type, transaction_ids,
            suspicion_indicators, risk_score
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $activityId, $userId, $suspiciousActivity['activity_type'],
            json_encode($transactionIds), json_encode($suspiciousActivity['suspicion_indicators']),
            $suspiciousActivity['risk_score']
        ]);
        
        return $activityId;
    }
    
    private function recordComplianceViolation($violationType, $framework, $userId, $transactionId, $details, $severity) {
        $violationId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO compliance_violations (
            id, violation_type, framework, user_id, transaction_id,
            violation_details, severity
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $violationId, $violationType, $framework, $userId, $transactionId,
            json_encode($details), $severity
        ]);
        
        return [
            'violation_id' => $violationId,
            'violation_type' => $violationType,
            'framework' => $framework,
            'severity' => $severity,
            'details' => $details
        ];
    }
    
    private function createComplianceReport($reportType, $framework, $startDate, $endDate, $reportData, $summary, $riskLevel) {
        $reportId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO compliance_reports (
            id, report_type, report_framework, report_period_start,
            report_period_end, report_data, report_summary, risk_level, generated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $reportId, $reportType, $framework, $startDate, $endDate,
            json_encode($reportData), $summary, $riskLevel, $_SESSION['admin_id'] ?? 'system'
        ]);
        
        // Log report generation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'compliance_report_generated', SecurityLogger::LEVEL_INFO,
            "Compliance report generated: $reportType", [
                'report_id' => $reportId,
                'framework' => $framework,
                'risk_level' => $riskLevel
            ]);
        
        return $reportId;
    }
    
    private function logComplianceAuditTrail($entityType, $entityId, $actionType, $actionDetails, $performedBy, $performedByType) {
        $auditId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO compliance_audit_trails (
            id, entity_type, entity_id, action_type, action_details,
            performed_by, performed_by_type, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $auditId, $entityType, $entityId, $actionType, json_encode($actionDetails),
            $performedBy, $performedByType, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return $auditId;
    }
    
    private function convertCurrency($amount, $fromCurrency, $toCurrency) {
        // Placeholder for currency conversion
        // In production, integrate with real-time exchange rate API
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        // Simple conversion rates (should be real-time in production)
        $rates = [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'USDT' => 1.0 // Assuming USDT = USD
        ];
        
        $usdAmount = $amount / ($rates[$fromCurrency] ?? 1.0);
        return $usdAmount * ($rates[$toCurrency] ?? 1.0);
    }
    
    private function determineSeverity($actualValue, $thresholdValue) {
        $ratio = $actualValue / $thresholdValue;
        
        if ($ratio >= 5.0) return 'critical';
        if ($ratio >= 2.0) return 'high';
        if ($ratio >= 1.5) return 'medium';
        return 'low';
    }
    
    private function calculateRiskLevel($violations, $suspiciousActivity) {
        $riskScore = 0;
        
        foreach ($violations as $violation) {
            switch ($violation['severity']) {
                case 'critical': $riskScore += 40; break;
                case 'high': $riskScore += 30; break;
                case 'medium': $riskScore += 20; break;
                case 'low': $riskScore += 10; break;
            }
        }
        
        if ($suspiciousActivity) {
            $riskScore += $suspiciousActivity['risk_score'];
        }
        
        if ($riskScore >= 80) return self::RISK_CRITICAL;
        if ($riskScore >= 60) return self::RISK_HIGH;
        if ($riskScore >= 30) return self::RISK_MEDIUM;
        return self::RISK_LOW;
    }
    
    private function getUserComplianceInfo($userId) {
        // Get user information for compliance reporting
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    private function getTransactionDetails($transactionIds) {
        if (empty($transactionIds)) return [];
        
        $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
        $query = "SELECT * FROM transaction_validations WHERE transaction_id IN ($placeholders)";
        $stmt = $this->db->prepare($query);
        $stmt->execute($transactionIds);
        return $stmt->fetchAll();
    }
    
    private function getInstitutionInfo() {
        return [
            'name' => 'Aureus Angel Alliance',
            'registration_number' => 'AAA-2024-001',
            'address' => 'Financial District, Compliance Office',
            'contact_person' => 'Compliance Officer',
            'reporting_date' => date('Y-m-d H:i:s')
        ];
    }
}

// Convenience functions
function checkTransactionCompliance($transactionId, $userId, $transactionType, $amount, $currency = 'USD', $additionalData = []) {
    $compliance = RegulatoryCompliance::getInstance();
    return $compliance->checkTransactionCompliance($transactionId, $userId, $transactionType, $amount, $currency, $additionalData);
}

function generateSuspiciousActivityReport($userId, $suspiciousActivityId, $reportPeriodDays = 30) {
    $compliance = RegulatoryCompliance::getInstance();
    return $compliance->generateSuspiciousActivityReport($userId, $suspiciousActivityId, $reportPeriodDays);
}

function generateCurrencyTransactionReport($userId, $reportPeriodDays = 1) {
    $compliance = RegulatoryCompliance::getInstance();
    return $compliance->generateCurrencyTransactionReport($userId, $reportPeriodDays);
}

function generateAuditTrailReport($entityType, $entityId, $reportPeriodDays = 30) {
    $compliance = RegulatoryCompliance::getInstance();
    return $compliance->generateAuditTrailReport($entityType, $entityId, $reportPeriodDays);
}
?>
