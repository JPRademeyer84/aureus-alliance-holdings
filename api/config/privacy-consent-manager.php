<?php
/**
 * PRIVACY CONSENT MANAGER
 * Advanced consent management with granular privacy controls
 */

require_once 'gdpr-compliance.php';
require_once 'security-logger.php';

class PrivacyConsentManager {
    private static $instance = null;
    private $db;
    private $gdpr;
    
    // Consent categories
    const CATEGORY_ESSENTIAL = 'essential';
    const CATEGORY_FUNCTIONAL = 'functional';
    const CATEGORY_ANALYTICS = 'analytics';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_PERSONALIZATION = 'personalization';
    
    // Privacy settings
    const PRIVACY_PUBLIC = 'public';
    const PRIVACY_PRIVATE = 'private';
    const PRIVACY_FRIENDS = 'friends';
    const PRIVACY_CUSTOM = 'custom';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->gdpr = GDPRCompliance::getInstance();
        $this->initializeConsentTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize consent management tables
     */
    private function initializeConsentTables() {
        $tables = [
            // Privacy settings
            "CREATE TABLE IF NOT EXISTS user_privacy_settings (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL UNIQUE,
                profile_visibility ENUM('public', 'private', 'friends', 'custom') DEFAULT 'private',
                email_visibility ENUM('public', 'private', 'friends', 'custom') DEFAULT 'private',
                phone_visibility ENUM('public', 'private', 'friends', 'custom') DEFAULT 'private',
                transaction_history_visibility ENUM('public', 'private', 'friends', 'custom') DEFAULT 'private',
                investment_visibility ENUM('public', 'private', 'friends', 'custom') DEFAULT 'private',
                allow_marketing_emails BOOLEAN DEFAULT FALSE,
                allow_promotional_sms BOOLEAN DEFAULT FALSE,
                allow_push_notifications BOOLEAN DEFAULT TRUE,
                allow_data_analytics BOOLEAN DEFAULT FALSE,
                allow_personalization BOOLEAN DEFAULT TRUE,
                allow_third_party_sharing BOOLEAN DEFAULT FALSE,
                data_retention_preference ENUM('minimum', 'standard', 'extended') DEFAULT 'standard',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id)
            )",
            
            // Consent preferences
            "CREATE TABLE IF NOT EXISTS consent_preferences (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                consent_category VARCHAR(50) NOT NULL,
                preference_key VARCHAR(100) NOT NULL,
                preference_value JSON NOT NULL,
                consent_required BOOLEAN DEFAULT TRUE,
                consent_given BOOLEAN DEFAULT FALSE,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_preference (user_id, consent_category, preference_key),
                INDEX idx_user_id (user_id),
                INDEX idx_consent_category (consent_category)
            )",
            
            // Cookie consent
            "CREATE TABLE IF NOT EXISTS cookie_consent (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36),
                session_id VARCHAR(100),
                cookie_category VARCHAR(50) NOT NULL,
                consent_given BOOLEAN NOT NULL,
                consent_version VARCHAR(20) NOT NULL,
                expires_at TIMESTAMP NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_session_id (session_id),
                INDEX idx_cookie_category (cookie_category)
            )",
            
            // Privacy notices
            "CREATE TABLE IF NOT EXISTS privacy_notices (
                id VARCHAR(36) PRIMARY KEY,
                notice_type VARCHAR(50) NOT NULL,
                notice_title VARCHAR(200) NOT NULL,
                notice_content TEXT NOT NULL,
                notice_version VARCHAR(20) NOT NULL,
                effective_date TIMESTAMP NOT NULL,
                expiry_date TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                requires_consent BOOLEAN DEFAULT FALSE,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notice_type (notice_type),
                INDEX idx_effective_date (effective_date),
                INDEX idx_is_active (is_active)
            )",
            
            // Data processing purposes
            "CREATE TABLE IF NOT EXISTS data_processing_purposes (
                id VARCHAR(36) PRIMARY KEY,
                purpose_name VARCHAR(100) NOT NULL,
                purpose_description TEXT NOT NULL,
                data_categories JSON NOT NULL,
                legal_basis VARCHAR(50) NOT NULL,
                retention_period VARCHAR(100),
                third_party_sharing BOOLEAN DEFAULT FALSE,
                requires_consent BOOLEAN DEFAULT TRUE,
                is_essential BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_legal_basis (legal_basis),
                INDEX idx_requires_consent (requires_consent),
                INDEX idx_is_essential (is_essential)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create consent table: " . $e->getMessage());
            }
        }
        
        $this->initializeDefaultSettings();
    }
    
    /**
     * Initialize default privacy settings and notices
     */
    private function initializeDefaultSettings() {
        // Check if notices already exist
        $query = "SELECT COUNT(*) FROM privacy_notices";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Already initialized
        }
        
        // Default privacy notices
        $defaultNotices = [
            [
                'type' => 'privacy_policy',
                'title' => 'Privacy Policy',
                'content' => $this->getDefaultPrivacyPolicy(),
                'version' => '1.0',
                'requires_consent' => false
            ],
            [
                'type' => 'cookie_policy',
                'title' => 'Cookie Policy',
                'content' => $this->getDefaultCookiePolicy(),
                'version' => '1.0',
                'requires_consent' => true
            ],
            [
                'type' => 'data_processing',
                'title' => 'Data Processing Notice',
                'content' => $this->getDefaultDataProcessingNotice(),
                'version' => '1.0',
                'requires_consent' => true
            ]
        ];
        
        foreach ($defaultNotices as $notice) {
            $this->createPrivacyNotice(
                $notice['type'],
                $notice['title'],
                $notice['content'],
                $notice['version'],
                $notice['requires_consent']
            );
        }
        
        // Default processing purposes
        $defaultPurposes = [
            [
                'name' => 'Account Management',
                'description' => 'Managing user accounts, authentication, and basic platform functionality',
                'data_categories' => ['personal_data'],
                'legal_basis' => 'contract',
                'retention_period' => '7 years after account closure',
                'essential' => true
            ],
            [
                'name' => 'Marketing Communications',
                'description' => 'Sending promotional emails, newsletters, and marketing content',
                'data_categories' => ['personal_data', 'behavioral_data'],
                'legal_basis' => 'consent',
                'retention_period' => 'Until consent withdrawn',
                'essential' => false
            ],
            [
                'name' => 'Analytics and Insights',
                'description' => 'Analyzing user behavior to improve platform performance and user experience',
                'data_categories' => ['behavioral_data', 'technical_data'],
                'legal_basis' => 'legitimate_interests',
                'retention_period' => '2 years',
                'essential' => false
            ]
        ];
        
        foreach ($defaultPurposes as $purpose) {
            $this->createProcessingPurpose(
                $purpose['name'],
                $purpose['description'],
                $purpose['data_categories'],
                $purpose['legal_basis'],
                $purpose['retention_period'],
                false,
                $purpose['essential']
            );
        }
    }
    
    /**
     * Get user privacy settings
     */
    public function getUserPrivacySettings($userId) {
        $query = "SELECT * FROM user_privacy_settings WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Create default settings
            $settings = $this->createDefaultPrivacySettings($userId);
        }
        
        return $settings;
    }
    
    /**
     * Update user privacy settings
     */
    public function updatePrivacySettings($userId, $settings) {
        $allowedFields = [
            'profile_visibility', 'email_visibility', 'phone_visibility',
            'transaction_history_visibility', 'investment_visibility',
            'allow_marketing_emails', 'allow_promotional_sms', 'allow_push_notifications',
            'allow_data_analytics', 'allow_personalization', 'allow_third_party_sharing',
            'data_retention_preference'
        ];
        
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($settings[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $settings[$field];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $params[] = $userId;
        
        $query = "UPDATE user_privacy_settings 
                  SET " . implode(', ', $updateFields) . ", updated_at = NOW()
                  WHERE user_id = ?";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($params);
        
        if ($success) {
            // Log privacy settings update
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'privacy_settings_updated', SecurityLogger::LEVEL_INFO,
                'User privacy settings updated', [
                    'user_id' => $userId,
                    'updated_fields' => array_keys($settings)
                ]);
        }
        
        return $success;
    }
    
    /**
     * Record cookie consent
     */
    public function recordCookieConsent($userId, $sessionId, $cookieCategory, $consentGiven, $consentVersion) {
        $consentId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO cookie_consent (
            id, user_id, session_id, cookie_category, consent_given,
            consent_version, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $consentId, $userId, $sessionId, $cookieCategory, $consentGiven,
            $consentVersion, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Also record in GDPR system
        $this->gdpr->recordConsent($userId, "cookie_$cookieCategory", $consentGiven, $consentVersion, 
            "Cookie consent for $cookieCategory", 'explicit');
        
        return $consentId;
    }
    
    /**
     * Check cookie consent
     */
    public function hasCookieConsent($userId, $sessionId, $cookieCategory) {
        $query = "SELECT consent_given FROM cookie_consent 
                  WHERE (user_id = ? OR session_id = ?) AND cookie_category = ?
                  ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $sessionId, $cookieCategory]);
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['consent_given'] : false;
    }
    
    /**
     * Get consent preferences
     */
    public function getConsentPreferences($userId) {
        $query = "SELECT * FROM consent_preferences WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update consent preference
     */
    public function updateConsentPreference($userId, $category, $preferenceKey, $preferenceValue, $consentGiven) {
        $preferenceId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO consent_preferences (
            id, user_id, consent_category, preference_key, preference_value, consent_given
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            preference_value = VALUES(preference_value),
            consent_given = VALUES(consent_given),
            last_updated = NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $preferenceId, $userId, $category, $preferenceKey, 
            json_encode($preferenceValue), $consentGiven
        ]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get active privacy notices
     */
    public function getActivePrivacyNotices($noticeType = null) {
        $whereClause = "WHERE is_active = TRUE AND effective_date <= NOW()";
        $params = [];
        
        if ($noticeType) {
            $whereClause .= " AND notice_type = ?";
            $params[] = $noticeType;
        }
        
        $query = "SELECT * FROM privacy_notices $whereClause ORDER BY effective_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get processing purposes
     */
    public function getProcessingPurposes($requiresConsent = null) {
        $whereClause = "WHERE is_active = TRUE";
        $params = [];
        
        if ($requiresConsent !== null) {
            $whereClause .= " AND requires_consent = ?";
            $params[] = $requiresConsent;
        }
        
        $query = "SELECT * FROM data_processing_purposes $whereClause ORDER BY is_essential DESC, purpose_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Helper methods
     */
    
    private function createDefaultPrivacySettings($userId) {
        $settingsId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO user_privacy_settings (id, user_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$settingsId, $userId]);
        
        // Return the created settings
        return $this->getUserPrivacySettings($userId);
    }
    
    private function createPrivacyNotice($noticeType, $title, $content, $version, $requiresConsent) {
        $noticeId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO privacy_notices (
            id, notice_type, notice_title, notice_content, notice_version,
            effective_date, requires_consent, created_by
        ) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'system')";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$noticeId, $noticeType, $title, $content, $version, $requiresConsent]);
        
        return $noticeId;
    }
    
    private function createProcessingPurpose($name, $description, $dataCategories, $legalBasis, $retentionPeriod, $thirdPartySharing, $isEssential) {
        $purposeId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO data_processing_purposes (
            id, purpose_name, purpose_description, data_categories, legal_basis,
            retention_period, third_party_sharing, requires_consent, is_essential
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $requiresConsent = ($legalBasis === 'consent');
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $purposeId, $name, $description, json_encode($dataCategories), $legalBasis,
            $retentionPeriod, $thirdPartySharing, $requiresConsent, $isEssential
        ]);
        
        return $purposeId;
    }
    
    private function getDefaultPrivacyPolicy() {
        return "This Privacy Policy describes how Aureus Angel Alliance collects, uses, and protects your personal information. We are committed to protecting your privacy and ensuring transparency in our data processing activities. This policy explains your rights under GDPR and how you can exercise them.";
    }
    
    private function getDefaultCookiePolicy() {
        return "We use cookies to enhance your experience on our platform. This policy explains what cookies we use, why we use them, and how you can manage your cookie preferences. You have the right to accept or decline non-essential cookies.";
    }
    
    private function getDefaultDataProcessingNotice() {
        return "We process your personal data for various purposes including account management, transaction processing, and regulatory compliance. This notice explains the legal basis for each type of processing and your rights regarding your personal data.";
    }
}

// Convenience functions
function getUserPrivacySettings($userId) {
    $manager = PrivacyConsentManager::getInstance();
    return $manager->getUserPrivacySettings($userId);
}

function updateUserPrivacySettings($userId, $settings) {
    $manager = PrivacyConsentManager::getInstance();
    return $manager->updatePrivacySettings($userId, $settings);
}

function recordCookieConsent($userId, $sessionId, $cookieCategory, $consentGiven, $consentVersion = '1.0') {
    $manager = PrivacyConsentManager::getInstance();
    return $manager->recordCookieConsent($userId, $sessionId, $cookieCategory, $consentGiven, $consentVersion);
}

function hasCookieConsent($userId, $sessionId, $cookieCategory) {
    $manager = PrivacyConsentManager::getInstance();
    return $manager->hasCookieConsent($userId, $sessionId, $cookieCategory);
}

function getActivePrivacyNotices($noticeType = null) {
    $manager = PrivacyConsentManager::getInstance();
    return $manager->getActivePrivacyNotices($noticeType);
}

function getProcessingPurposes($requiresConsent = null) {
    $manager = PrivacyConsentManager::getInstance();
    return $manager->getProcessingPurposes($requiresConsent);
}
?>
