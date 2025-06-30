<?php
/**
 * GDPR COMPLIANCE SYSTEM
 * Comprehensive GDPR compliance with data subject rights, consent management, and privacy by design
 */

require_once 'security-logger.php';
require_once 'data-encryption.php';

class GDPRCompliance {
    private static $instance = null;
    private $db;
    private $encryption;

    // Data subject rights
    const RIGHT_ACCESS = 'access';
    const RIGHT_RECTIFICATION = 'rectification';
    const RIGHT_ERASURE = 'erasure';
    const RIGHT_PORTABILITY = 'portability';
    const RIGHT_RESTRICT = 'restrict_processing';
    const RIGHT_OBJECT = 'object_processing';
    const RIGHT_WITHDRAW_CONSENT = 'withdraw_consent';

    // Consent types
    const CONSENT_MARKETING = 'marketing';
    const CONSENT_ANALYTICS = 'analytics';
    const CONSENT_FUNCTIONAL = 'functional';
    const CONSENT_NECESSARY = 'necessary';
    const CONSENT_THIRD_PARTY = 'third_party';

    // Processing lawful bases
    const BASIS_CONSENT = 'consent';
    const BASIS_CONTRACT = 'contract';
    const BASIS_LEGAL_OBLIGATION = 'legal_obligation';
    const BASIS_VITAL_INTERESTS = 'vital_interests';
    const BASIS_PUBLIC_TASK = 'public_task';
    const BASIS_LEGITIMATE_INTERESTS = 'legitimate_interests';

    // Data categories
    const CATEGORY_PERSONAL = 'personal_data';
    const CATEGORY_SENSITIVE = 'sensitive_data';
    const CATEGORY_FINANCIAL = 'financial_data';
    const CATEGORY_BIOMETRIC = 'biometric_data';
    const CATEGORY_BEHAVIORAL = 'behavioral_data';

    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->encryption = DataEncryption::getInstance();
        $this->initializeGDPRTables();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize GDPR compliance tables
     */
    private function initializeGDPRTables() {
        $tables = [
            // Consent management
            "CREATE TABLE IF NOT EXISTS gdpr_consent_records (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                consent_type VARCHAR(50) NOT NULL,
                consent_given BOOLEAN NOT NULL,
                consent_version VARCHAR(20) NOT NULL,
                consent_text TEXT NOT NULL,
                consent_method ENUM('explicit', 'implicit', 'opt_in', 'pre_checked') NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                consent_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                withdrawn_at TIMESTAMP NULL,
                withdrawal_reason TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_user_id (user_id),
                INDEX idx_consent_type (consent_type),
                INDEX idx_consent_given (consent_given),
                INDEX idx_is_active (is_active),
                INDEX idx_consent_timestamp (consent_timestamp)
            )",

            // Data subject requests
            "CREATE TABLE IF NOT EXISTS gdpr_data_requests (
                id VARCHAR(36) PRIMARY KEY,
                request_number VARCHAR(50) NOT NULL UNIQUE,
                user_id VARCHAR(36) NOT NULL,
                request_type VARCHAR(50) NOT NULL,
                request_status ENUM('pending', 'in_progress', 'completed', 'rejected', 'expired') DEFAULT 'pending',
                request_details JSON,
                identity_verified BOOLEAN DEFAULT FALSE,
                verification_method VARCHAR(50),
                verification_data JSON,
                assigned_to VARCHAR(36),
                processing_notes TEXT,
                completion_data JSON,
                requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                due_date TIMESTAMP NOT NULL,
                completed_at TIMESTAMP NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_request_type (request_type),
                INDEX idx_request_status (request_status),
                INDEX idx_due_date (due_date),
                INDEX idx_requested_at (requested_at)
            )",

            // Data processing activities
            "CREATE TABLE IF NOT EXISTS gdpr_processing_activities (
                id VARCHAR(36) PRIMARY KEY,
                activity_name VARCHAR(200) NOT NULL,
                purpose_description TEXT NOT NULL,
                lawful_basis VARCHAR(50) NOT NULL,
                data_categories JSON NOT NULL,
                data_subjects JSON NOT NULL,
                recipients JSON,
                third_country_transfers JSON,
                retention_period VARCHAR(100),
                security_measures JSON,
                data_controller VARCHAR(200),
                data_processor VARCHAR(200),
                dpo_contact VARCHAR(200),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_lawful_basis (lawful_basis),
                INDEX idx_is_active (is_active)
            )",

            // Data breach incidents
            "CREATE TABLE IF NOT EXISTS gdpr_data_breaches (
                id VARCHAR(36) PRIMARY KEY,
                breach_reference VARCHAR(50) NOT NULL UNIQUE,
                breach_type VARCHAR(50) NOT NULL,
                severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                affected_data_categories JSON NOT NULL,
                affected_individuals_count INT,
                breach_description TEXT NOT NULL,
                cause_description TEXT,
                containment_measures TEXT,
                assessment_outcome TEXT,
                notification_required BOOLEAN DEFAULT FALSE,
                authority_notified BOOLEAN DEFAULT FALSE,
                authority_notification_date TIMESTAMP NULL,
                individuals_notified BOOLEAN DEFAULT FALSE,
                individuals_notification_date TIMESTAMP NULL,
                discovered_at TIMESTAMP NOT NULL,
                reported_by VARCHAR(36),
                investigated_by VARCHAR(36),
                status ENUM('open', 'investigating', 'contained', 'resolved') DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_breach_type (breach_type),
                INDEX idx_severity (severity),
                INDEX idx_status (status),
                INDEX idx_discovered_at (discovered_at)
            )",

            // Privacy impact assessments
            "CREATE TABLE IF NOT EXISTS gdpr_privacy_assessments (
                id VARCHAR(36) PRIMARY KEY,
                assessment_name VARCHAR(200) NOT NULL,
                processing_activity_id VARCHAR(36),
                assessment_type ENUM('DPIA', 'LIA', 'TIA') DEFAULT 'DPIA',
                risk_level ENUM('low', 'medium', 'high', 'very_high') NOT NULL,
                assessment_data JSON NOT NULL,
                mitigation_measures JSON,
                residual_risks JSON,
                consultation_required BOOLEAN DEFAULT FALSE,
                authority_consulted BOOLEAN DEFAULT FALSE,
                consultation_outcome TEXT,
                assessment_status ENUM('draft', 'review', 'approved', 'rejected') DEFAULT 'draft',
                conducted_by VARCHAR(36),
                reviewed_by VARCHAR(36),
                approved_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (processing_activity_id) REFERENCES gdpr_processing_activities(id),
                INDEX idx_assessment_type (assessment_type),
                INDEX idx_risk_level (risk_level),
                INDEX idx_assessment_status (assessment_status)
            )",

            // Data retention policies
            "CREATE TABLE IF NOT EXISTS gdpr_retention_policies (
                id VARCHAR(36) PRIMARY KEY,
                policy_name VARCHAR(200) NOT NULL,
                data_category VARCHAR(50) NOT NULL,
                retention_period_months INT NOT NULL,
                retention_criteria TEXT,
                deletion_method ENUM('secure_delete', 'anonymize', 'pseudonymize') DEFAULT 'secure_delete',
                legal_basis TEXT,
                review_frequency_months INT DEFAULT 12,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_data_category (data_category),
                INDEX idx_is_active (is_active)
            )"
        ];

        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create GDPR table: " . $e->getMessage());
            }
        }

        $this->initializeDefaultPolicies();
    }

    /**
     * Initialize default GDPR policies and activities
     */
    private function initializeDefaultPolicies() {
        // Check if policies already exist
        $query = "SELECT COUNT(*) FROM gdpr_processing_activities";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            return; // Policies already initialized
        }

        // Default processing activities
        $defaultActivities = [
            [
                'name' => 'User Account Management',
                'purpose' => 'Managing user accounts, authentication, and profile information',
                'lawful_basis' => self::BASIS_CONTRACT,
                'data_categories' => [self::CATEGORY_PERSONAL],
                'data_subjects' => ['platform_users'],
                'retention_period' => '7 years after account closure'
            ],
            [
                'name' => 'Financial Transaction Processing',
                'purpose' => 'Processing financial transactions, commissions, and payments',
                'lawful_basis' => self::BASIS_CONTRACT,
                'data_categories' => [self::CATEGORY_FINANCIAL, self::CATEGORY_PERSONAL],
                'data_subjects' => ['platform_users', 'investors'],
                'retention_period' => '10 years for financial records'
            ],
            [
                'name' => 'KYC Verification',
                'purpose' => 'Know Your Customer verification for regulatory compliance',
                'lawful_basis' => self::BASIS_LEGAL_OBLIGATION,
                'data_categories' => [self::CATEGORY_PERSONAL, self::CATEGORY_SENSITIVE, self::CATEGORY_BIOMETRIC],
                'data_subjects' => ['platform_users'],
                'retention_period' => '5 years after verification'
            ],
            [
                'name' => 'Marketing Communications',
                'purpose' => 'Sending marketing emails and promotional content',
                'lawful_basis' => self::BASIS_CONSENT,
                'data_categories' => [self::CATEGORY_PERSONAL, self::CATEGORY_BEHAVIORAL],
                'data_subjects' => ['platform_users', 'prospects'],
                'retention_period' => 'Until consent withdrawn'
            ]
        ];

        foreach ($defaultActivities as $activity) {
            $this->createProcessingActivity(
                $activity['name'],
                $activity['purpose'],
                $activity['lawful_basis'],
                $activity['data_categories'],
                $activity['data_subjects'],
                [],
                [],
                $activity['retention_period']
            );
        }

        // Default retention policies
        $defaultRetentions = [
            ['User Personal Data', self::CATEGORY_PERSONAL, 84, 'Account active + 7 years'],
            ['Financial Records', self::CATEGORY_FINANCIAL, 120, 'Legal requirement'],
            ['KYC Documents', self::CATEGORY_SENSITIVE, 60, 'Regulatory compliance'],
            ['Marketing Data', self::CATEGORY_BEHAVIORAL, 36, 'Marketing effectiveness'],
            ['Security Logs', 'security_data', 24, 'Security monitoring']
        ];

        foreach ($defaultRetentions as $retention) {
            $this->createRetentionPolicy($retention[0], $retention[1], $retention[2], $retention[3]);
        }
    }

    /**
     * Record user consent
     */
    public function recordConsent($userId, $consentType, $consentGiven, $consentVersion, $consentText, $consentMethod = 'explicit') {
        $consentId = bin2hex(random_bytes(16));

        // Withdraw previous consent of same type if exists
        if ($consentGiven) {
            $this->withdrawPreviousConsent($userId, $consentType);
        }

        $query = "INSERT INTO gdpr_consent_records (
            id, user_id, consent_type, consent_given, consent_version,
            consent_text, consent_method, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $consentId, $userId, $consentType, $consentGiven, $consentVersion,
            $consentText, $consentMethod, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        // Log consent action
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'gdpr_consent_recorded', SecurityLogger::LEVEL_INFO,
            'GDPR consent recorded', [
                'consent_id' => $consentId,
                'user_id' => $userId,
                'consent_type' => $consentType,
                'consent_given' => $consentGiven
            ]);

        return $consentId;
    }

    /**
     * Check if user has given consent
     */
    public function hasConsent($userId, $consentType) {
        $query = "SELECT consent_given FROM gdpr_consent_records
                  WHERE user_id = ? AND consent_type = ? AND is_active = TRUE
                  ORDER BY consent_timestamp DESC LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $consentType]);
        $result = $stmt->fetch();

        return $result ? (bool)$result['consent_given'] : false;
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent($userId, $consentType, $reason = null) {
        $query = "UPDATE gdpr_consent_records
                  SET consent_given = FALSE, withdrawn_at = NOW(), withdrawal_reason = ?
                  WHERE user_id = ? AND consent_type = ? AND is_active = TRUE";

        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$reason, $userId, $consentType]);

        if ($success) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'gdpr_consent_withdrawn', SecurityLogger::LEVEL_INFO,
                'GDPR consent withdrawn', [
                    'user_id' => $userId,
                    'consent_type' => $consentType,
                    'reason' => $reason
                ]);
        }

        return $success;
    }

    /**
     * Create data subject request
     */
    public function createDataSubjectRequest($userId, $requestType, $requestDetails = []) {
        $requestId = bin2hex(random_bytes(16));
        $requestNumber = 'DSR-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculate due date (30 days for most requests, 72 hours for breach notifications)
        $dueDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        if ($requestType === self::RIGHT_ERASURE) {
            $dueDate = date('Y-m-d H:i:s', strtotime('+72 hours'));
        }

        $query = "INSERT INTO gdpr_data_requests (
            id, request_number, user_id, request_type, request_details, due_date
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $requestId, $requestNumber, $userId, $requestType, json_encode($requestDetails), $dueDate
        ]);

        // Log request creation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'gdpr_request_created', SecurityLogger::LEVEL_INFO,
            'GDPR data subject request created', [
                'request_id' => $requestId,
                'request_number' => $requestNumber,
                'user_id' => $userId,
                'request_type' => $requestType
            ]);

        // Send notification to DPO/Admin
        $this->notifyDataProtectionOfficer($requestId, $requestType, $userId);

        return [
            'request_id' => $requestId,
            'request_number' => $requestNumber,
            'due_date' => $dueDate
        ];
    }

    /**
     * Process data access request (Right to Access)
     */
    public function processAccessRequest($requestId) {
        $request = $this->getDataSubjectRequest($requestId);
        if (!$request || $request['request_type'] !== self::RIGHT_ACCESS) {
            throw new Exception("Invalid access request: $requestId");
        }

        $userId = $request['user_id'];
        $userData = $this->collectUserData($userId);

        // Update request status
        $this->updateRequestStatus($requestId, 'completed', [
            'completion_method' => 'data_export',
            'data_categories' => array_keys($userData),
            'export_format' => 'json'
        ]);

        return $userData;
    }

    /**
     * Process data erasure request (Right to be Forgotten)
     */
    public function processErasureRequest($requestId, $adminId) {
        $request = $this->getDataSubjectRequest($requestId);
        if (!$request || $request['request_type'] !== self::RIGHT_ERASURE) {
            throw new Exception("Invalid erasure request: $requestId");
        }

        $userId = $request['user_id'];

        // Check if erasure is legally possible
        $legalHolds = $this->checkLegalHolds($userId);
        if (!empty($legalHolds)) {
            $this->updateRequestStatus($requestId, 'rejected', [
                'rejection_reason' => 'Legal obligations prevent erasure',
                'legal_holds' => $legalHolds
            ]);
            return false;
        }

        // Perform data erasure
        $erasureResults = $this->performDataErasure($userId);

        // Update request status
        $this->updateRequestStatus($requestId, 'completed', [
            'completion_method' => 'data_erasure',
            'erased_categories' => $erasureResults['erased'],
            'retained_categories' => $erasureResults['retained'],
            'processed_by' => $adminId
        ]);

        return true;
    }

    /**
     * Process data portability request
     */
    public function processPortabilityRequest($requestId) {
        $request = $this->getDataSubjectRequest($requestId);
        if (!$request || $request['request_type'] !== self::RIGHT_PORTABILITY) {
            throw new Exception("Invalid portability request: $requestId");
        }

        $userId = $request['user_id'];
        $portableData = $this->collectPortableData($userId);

        // Create structured export
        $exportData = [
            'user_id' => $userId,
            'export_date' => date('c'),
            'data_format' => 'JSON',
            'data' => $portableData
        ];

        $this->updateRequestStatus($requestId, 'completed', [
            'completion_method' => 'data_export',
            'export_format' => 'json',
            'data_categories' => array_keys($portableData)
        ]);

        return $exportData;
    }

    /**
     * Report data breach
     */
    public function reportDataBreach($breachType, $severity, $affectedDataCategories, $affectedCount, $description, $cause = null) {
        $breachId = bin2hex(random_bytes(16));
        $breachReference = 'BREACH-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        $query = "INSERT INTO gdpr_data_breaches (
            id, breach_reference, breach_type, severity, affected_data_categories,
            affected_individuals_count, breach_description, cause_description,
            discovered_at, reported_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $breachId, $breachReference, $breachType, $severity, json_encode($affectedDataCategories),
            $affectedCount, $description, $cause, $_SESSION['admin_id'] ?? 'system'
        ]);

        // Determine if authority notification is required (72 hours for high/critical)
        $notificationRequired = in_array($severity, ['high', 'critical']);

        if ($notificationRequired) {
            $updateQuery = "UPDATE gdpr_data_breaches SET notification_required = TRUE WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$breachId]);
        }

        // Log breach
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'gdpr_data_breach',
            $severity === 'critical' ? SecurityLogger::LEVEL_CRITICAL : SecurityLogger::LEVEL_WARNING,
            'GDPR data breach reported', [
                'breach_id' => $breachId,
                'breach_reference' => $breachReference,
                'severity' => $severity,
                'affected_count' => $affectedCount
            ]);

        return [
            'breach_id' => $breachId,
            'breach_reference' => $breachReference,
            'notification_required' => $notificationRequired
        ];
    }

    /**
     * Helper methods
     */

    private function withdrawPreviousConsent($userId, $consentType) {
        $query = "UPDATE gdpr_consent_records SET is_active = FALSE
                  WHERE user_id = ? AND consent_type = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $consentType]);
    }

    private function getDataSubjectRequest($requestId) {
        $query = "SELECT * FROM gdpr_data_requests WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }

    private function updateRequestStatus($requestId, $status, $completionData = []) {
        $query = "UPDATE gdpr_data_requests
                  SET request_status = ?, completion_data = ?, completed_at = NOW()
                  WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$status, json_encode($completionData), $requestId]);
    }

    private function collectUserData($userId) {
        $userData = [];

        // Collect from various tables
        $tables = [
            'users' => ['id', 'username', 'email', 'full_name', 'created_at'],
            'user_profiles' => ['phone', 'date_of_birth', 'telegram_username', 'whatsapp_number'],
            'kyc_documents' => ['document_type', 'verification_status', 'uploaded_at'],
            'aureus_investments' => ['package_type', 'amount', 'created_at'],
            'commission_transactions' => ['transaction_type', 'amount', 'created_at']
        ];

        foreach ($tables as $table => $fields) {
            $fieldList = implode(', ', $fields);
            $query = "SELECT $fieldList FROM $table WHERE user_id = ? OR id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userId]);
            $userData[$table] = $stmt->fetchAll();
        }

        return $userData;
    }

    private function collectPortableData($userId) {
        // Only collect data that is portable under GDPR
        $portableData = [];

        // User profile data
        $query = "SELECT username, email, full_name, created_at FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $portableData['profile'] = $stmt->fetch();

        // Investment data
        $query = "SELECT package_type, amount, created_at FROM aureus_investments WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $portableData['investments'] = $stmt->fetchAll();

        // Transaction history
        $query = "SELECT transaction_type, amount, created_at FROM commission_transactions WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $portableData['transactions'] = $stmt->fetchAll();

        return $portableData;
    }

    private function checkLegalHolds($userId) {
        $holds = [];

        // Check for ongoing investigations
        $query = "SELECT COUNT(*) FROM fraud_investigation_cases WHERE user_id = ? AND case_status IN ('open', 'investigating')";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) {
            $holds[] = 'fraud_investigation';
        }

        // Check for regulatory requirements
        $query = "SELECT COUNT(*) FROM compliance_violations WHERE user_id = ? AND remediation_status != 'completed'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() > 0) {
            $holds[] = 'regulatory_compliance';
        }

        return $holds;
    }

    private function performDataErasure($userId) {
        $erased = [];
        $retained = [];

        // Tables that can be safely erased
        $erasableTables = [
            'user_profiles' => 'user_id',
            'chat_messages' => 'user_id',
            'gdpr_consent_records' => 'user_id'
        ];

        foreach ($erasableTables as $table => $userColumn) {
            $query = "DELETE FROM $table WHERE $userColumn = ?";
            $stmt = $this->db->prepare($query);
            if ($stmt->execute([$userId])) {
                $erased[] = $table;
            }
        }

        // Tables that need anonymization instead of deletion
        $anonymizeTables = [
            'commission_transactions' => ['user_id' => 'ANONYMIZED'],
            'aureus_investments' => ['email' => 'anonymized@example.com', 'name' => 'ANONYMIZED']
        ];

        foreach ($anonymizeTables as $table => $anonymizations) {
            $setParts = [];
            $values = [];
            foreach ($anonymizations as $column => $value) {
                $setParts[] = "$column = ?";
                $values[] = $value;
            }
            $values[] = $userId;

            $query = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            if ($stmt->execute($values)) {
                $erased[] = $table . ' (anonymized)';
            }
        }

        // Tables that must be retained for legal reasons
        $retainedTables = ['kyc_documents', 'security_events', 'compliance_audit_trails'];
        foreach ($retainedTables as $table) {
            $retained[] = $table;
        }

        return ['erased' => $erased, 'retained' => $retained];
    }

    private function notifyDataProtectionOfficer($requestId, $requestType, $userId) {
        // In production, send email/notification to DPO
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'gdpr_dpo_notification', SecurityLogger::LEVEL_INFO,
            'DPO notified of data subject request', [
                'request_id' => $requestId,
                'request_type' => $requestType,
                'user_id' => $userId
            ]);
    }

    private function createProcessingActivity($name, $purpose, $lawfulBasis, $dataCategories, $dataSubjects, $recipients, $thirdCountryTransfers, $retentionPeriod) {
        $activityId = bin2hex(random_bytes(16));

        $query = "INSERT INTO gdpr_processing_activities (
            id, activity_name, purpose_description, lawful_basis, data_categories,
            data_subjects, recipients, third_country_transfers, retention_period
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $activityId, $name, $purpose, $lawfulBasis, json_encode($dataCategories),
            json_encode($dataSubjects), json_encode($recipients), json_encode($thirdCountryTransfers), $retentionPeriod
        ]);

        return $activityId;
    }

    private function createRetentionPolicy($policyName, $dataCategory, $retentionMonths, $criteria) {
        $policyId = bin2hex(random_bytes(16));

        $query = "INSERT INTO gdpr_retention_policies (
            id, policy_name, data_category, retention_period_months, retention_criteria
        ) VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$policyId, $policyName, $dataCategory, $retentionMonths, $criteria]);

        return $policyId;
    }
}

// Convenience functions
function recordGDPRConsent($userId, $consentType, $consentGiven, $consentVersion, $consentText, $consentMethod = 'explicit') {
    $gdpr = GDPRCompliance::getInstance();
    return $gdpr->recordConsent($userId, $consentType, $consentGiven, $consentVersion, $consentText, $consentMethod);
}

function hasGDPRConsent($userId, $consentType) {
    $gdpr = GDPRCompliance::getInstance();
    return $gdpr->hasConsent($userId, $consentType);
}

function withdrawGDPRConsent($userId, $consentType, $reason = null) {
    $gdpr = GDPRCompliance::getInstance();
    return $gdpr->withdrawConsent($userId, $consentType, $reason);
}

function createDataSubjectRequest($userId, $requestType, $requestDetails = []) {
    $gdpr = GDPRCompliance::getInstance();
    return $gdpr->createDataSubjectRequest($userId, $requestType, $requestDetails);
}

function reportGDPRBreach($breachType, $severity, $affectedDataCategories, $affectedCount, $description, $cause = null) {
    $gdpr = GDPRCompliance::getInstance();
    return $gdpr->reportDataBreach($breachType, $severity, $affectedDataCategories, $affectedCount, $description, $cause);
}
?>