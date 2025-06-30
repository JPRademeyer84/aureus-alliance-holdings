<?php
/**
 * MULTI-FACTOR AUTHENTICATION SYSTEM
 * Bank-level MFA with TOTP, SMS, and backup codes
 */

require_once 'database.php';
require_once 'security-logger.php';
require_once 'data-encryption.php';

class MFASystem {
    private $db;
    private $encryption;
    private static $instance = null;
    
    // MFA configuration
    private $config = [
        'totp_window' => 30, // 30 seconds
        'totp_digits' => 6,
        'backup_codes_count' => 10,
        'sms_code_length' => 6,
        'sms_expiry' => 300, // 5 minutes
        'max_attempts' => 3,
        'lockout_duration' => 900 // 15 minutes
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
     * Initialize MFA tables
     */
    private function initializeTables() {
        if (!$this->db) return;
        
        try {
            // MFA settings table
            $this->db->exec("CREATE TABLE IF NOT EXISTS mfa_settings (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                user_type ENUM('admin', 'user') NOT NULL,
                totp_secret VARCHAR(255) NULL,
                totp_enabled BOOLEAN DEFAULT FALSE,
                sms_enabled BOOLEAN DEFAULT FALSE,
                phone_number VARCHAR(20) NULL,
                backup_codes JSON NULL,
                recovery_email VARCHAR(255) NULL,
                is_required BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                UNIQUE KEY unique_user (user_id, user_type),
                INDEX idx_user_id (user_id),
                INDEX idx_user_type (user_type),
                INDEX idx_totp_enabled (totp_enabled),
                INDEX idx_sms_enabled (sms_enabled)
            )");
            
            // MFA attempts table
            $this->db->exec("CREATE TABLE IF NOT EXISTS mfa_attempts (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                user_type ENUM('admin', 'user') NOT NULL,
                method ENUM('totp', 'sms', 'backup_code') NOT NULL,
                code_provided VARCHAR(20) NOT NULL,
                success BOOLEAN NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_user_id (user_id),
                INDEX idx_user_type (user_type),
                INDEX idx_method (method),
                INDEX idx_success (success),
                INDEX idx_created_at (created_at)
            )");
            
            // SMS codes table
            $this->db->exec("CREATE TABLE IF NOT EXISTS mfa_sms_codes (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                user_type ENUM('admin', 'user') NOT NULL,
                code VARCHAR(10) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                used BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_user_id (user_id),
                INDEX idx_code (code),
                INDEX idx_expires_at (expires_at),
                INDEX idx_used (used)
            )");
            
            // Device registration table
            $this->db->exec("CREATE TABLE IF NOT EXISTS mfa_trusted_devices (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                user_type ENUM('admin', 'user') NOT NULL,
                device_fingerprint VARCHAR(255) NOT NULL,
                device_name VARCHAR(100) NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_user_id (user_id),
                INDEX idx_device_fingerprint (device_fingerprint),
                INDEX idx_expires_at (expires_at),
                INDEX idx_is_active (is_active)
            )");
            
        } catch (PDOException $e) {
            error_log("MFA SYSTEM: Database initialization failed - " . $e->getMessage());
        }
    }
    
    /**
     * Setup TOTP for user
     */
    public function setupTOTP($userId, $userType = 'admin') {
        try {
            // Generate secret
            $secret = $this->generateTOTPSecret();
            
            // Encrypt secret before storing
            $encryptedSecret = $this->encryption->encrypt($secret);
            
            // Store in database
            $query = "INSERT INTO mfa_settings (user_id, user_type, totp_secret) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE totp_secret = VALUES(totp_secret)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType, $encryptedSecret]);
            
            // Generate QR code data
            $qrData = $this->generateQRCodeData($secret, $userId, $userType);
            
            // Log setup
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_totp_setup', SecurityLogger::LEVEL_INFO,
                'TOTP MFA setup initiated', ['user_type' => $userType], $userId);
            
            return [
                'secret' => $secret,
                'qr_code_url' => $qrData,
                'backup_codes' => $this->generateBackupCodes($userId, $userType)
            ];
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_setup_failed', SecurityLogger::LEVEL_CRITICAL,
                'MFA setup failed', ['error' => $e->getMessage(), 'user_type' => $userType], $userId);
            throw $e;
        }
    }
    
    /**
     * Enable TOTP after verification
     */
    public function enableTOTP($userId, $userType, $verificationCode) {
        try {
            // Get user's secret
            $secret = $this->getTOTPSecret($userId, $userType);
            if (!$secret) {
                throw new Exception('TOTP not set up for this user');
            }
            
            // Verify the code
            if (!$this->verifyTOTPCode($secret, $verificationCode)) {
                $this->recordMFAAttempt($userId, $userType, 'totp', $verificationCode, false);
                throw new Exception('Invalid verification code');
            }
            
            // Enable TOTP
            $query = "UPDATE mfa_settings SET totp_enabled = TRUE WHERE user_id = ? AND user_type = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType]);
            
            // Record successful attempt
            $this->recordMFAAttempt($userId, $userType, 'totp', $verificationCode, true);
            
            // Log enablement
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_totp_enabled', SecurityLogger::LEVEL_INFO,
                'TOTP MFA enabled', ['user_type' => $userType], $userId);
            
            return true;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_enable_failed', SecurityLogger::LEVEL_WARNING,
                'MFA enable failed', ['error' => $e->getMessage(), 'user_type' => $userType], $userId);
            throw $e;
        }
    }
    
    /**
     * Setup SMS MFA
     */
    public function setupSMS($userId, $userType, $phoneNumber) {
        try {
            // Validate phone number
            if (!$this->validatePhoneNumber($phoneNumber)) {
                throw new Exception('Invalid phone number format');
            }
            
            // Encrypt phone number
            $encryptedPhone = $this->encryption->encrypt($phoneNumber);
            
            // Store in database
            $query = "INSERT INTO mfa_settings (user_id, user_type, phone_number) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE phone_number = VALUES(phone_number)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType, $encryptedPhone]);
            
            // Send verification SMS
            $verificationCode = $this->generateSMSCode();
            $this->sendSMSCode($userId, $userType, $phoneNumber, $verificationCode);
            
            // Log setup
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_sms_setup', SecurityLogger::LEVEL_INFO,
                'SMS MFA setup initiated', ['user_type' => $userType], $userId);
            
            return [
                'message' => 'Verification code sent to your phone',
                'phone_masked' => $this->maskPhoneNumber($phoneNumber)
            ];
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_sms_setup_failed', SecurityLogger::LEVEL_WARNING,
                'SMS MFA setup failed', ['error' => $e->getMessage(), 'user_type' => $userType], $userId);
            throw $e;
        }
    }
    
    /**
     * Verify MFA code
     */
    public function verifyMFA($userId, $userType, $code, $method = 'auto') {
        try {
            // Check if user is locked out
            if ($this->isUserLockedOut($userId, $userType)) {
                throw new Exception('Account temporarily locked due to too many failed attempts');
            }
            
            $verified = false;
            $usedMethod = '';
            
            // Auto-detect method or use specific method
            if ($method === 'auto' || $method === 'totp') {
                if ($this->isTOTPEnabled($userId, $userType)) {
                    $secret = $this->getTOTPSecret($userId, $userType);
                    if ($this->verifyTOTPCode($secret, $code)) {
                        $verified = true;
                        $usedMethod = 'totp';
                    }
                }
            }
            
            if (!$verified && ($method === 'auto' || $method === 'sms')) {
                if ($this->verifySMSCode($userId, $userType, $code)) {
                    $verified = true;
                    $usedMethod = 'sms';
                }
            }
            
            if (!$verified && ($method === 'auto' || $method === 'backup_code')) {
                if ($this->verifyBackupCode($userId, $userType, $code)) {
                    $verified = true;
                    $usedMethod = 'backup_code';
                }
            }
            
            // Record attempt
            $this->recordMFAAttempt($userId, $userType, $usedMethod ?: 'unknown', $code, $verified);
            
            if ($verified) {
                // Clear failed attempts
                $this->clearFailedAttempts($userId, $userType);
                
                // Log successful verification
                logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_verified', SecurityLogger::LEVEL_INFO,
                    'MFA verification successful', ['method' => $usedMethod, 'user_type' => $userType], $userId);
                
                return [
                    'verified' => true,
                    'method' => $usedMethod
                ];
            } else {
                // Log failed verification
                logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_verification_failed', SecurityLogger::LEVEL_WARNING,
                    'MFA verification failed', ['method' => $method, 'user_type' => $userType], $userId);
                
                throw new Exception('Invalid verification code');
            }
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_verify_error', SecurityLogger::LEVEL_CRITICAL,
                'MFA verification error', ['error' => $e->getMessage(), 'user_type' => $userType], $userId);
            throw $e;
        }
    }
    
    /**
     * Check if MFA is required for user
     */
    public function isMFARequired($userId, $userType) {
        try {
            $query = "SELECT is_required, totp_enabled, sms_enabled FROM mfa_settings 
                     WHERE user_id = ? AND user_type = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                // For admin users, MFA is required by default
                return $userType === 'admin';
            }
            
            return $settings['is_required'] || $settings['totp_enabled'] || $settings['sms_enabled'];
            
        } catch (Exception $e) {
            // Default to requiring MFA for admins
            return $userType === 'admin';
        }
    }
    
    /**
     * Check if MFA is enabled for user
     */
    public function isMFAEnabled($userId, $userType) {
        try {
            $query = "SELECT totp_enabled, sms_enabled FROM mfa_settings 
                     WHERE user_id = ? AND user_type = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType]);
            $settings = $stmt->fetch();
            
            return $settings && ($settings['totp_enabled'] || $settings['sms_enabled']);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get MFA status for user
     */
    public function getMFAStatus($userId, $userType) {
        try {
            $query = "SELECT * FROM mfa_settings WHERE user_id = ? AND user_type = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                return [
                    'enabled' => false,
                    'required' => $userType === 'admin',
                    'methods' => [],
                    'backup_codes_remaining' => 0
                ];
            }
            
            $methods = [];
            if ($settings['totp_enabled']) $methods[] = 'totp';
            if ($settings['sms_enabled']) $methods[] = 'sms';
            
            $backupCodes = json_decode($settings['backup_codes'], true) ?: [];
            $backupCodesRemaining = count(array_filter($backupCodes, function($code) {
                return !$code['used'];
            }));
            
            return [
                'enabled' => !empty($methods),
                'required' => $settings['is_required'] || $userType === 'admin',
                'methods' => $methods,
                'phone_masked' => $settings['phone_number'] ? 
                    $this->maskPhoneNumber($this->encryption->decrypt($settings['phone_number'])) : null,
                'backup_codes_remaining' => $backupCodesRemaining
            ];
            
        } catch (Exception $e) {
            return [
                'enabled' => false,
                'required' => $userType === 'admin',
                'methods' => [],
                'backup_codes_remaining' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate TOTP secret
     */
    private function generateTOTPSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate QR code data for TOTP
     */
    private function generateQRCodeData($secret, $userId, $userType) {
        $issuer = 'Aureus Angel Alliance';
        $accountName = $userType === 'admin' ? "Admin:$userId" : "User:$userId";
        
        return "otpauth://totp/$issuer:$accountName?secret=$secret&issuer=$issuer&digits=6&period=30";
    }
    
    /**
     * Verify TOTP code
     */
    private function verifyTOTPCode($secret, $code) {
        $timeSlice = floor(time() / 30);
        
        // Check current time slice and adjacent ones for clock drift
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->calculateTOTP($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate TOTP code
     */
    private function calculateTOTP($secret, $timeSlice) {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, 6);
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode for TOTP
     */
    private function base32Decode($secret) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $decoded = '';
        
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $chunk = substr($secret, $i, 8);
            $chunk = str_pad($chunk, 8, '=');
            
            $binary = '';
            for ($j = 0; $j < 8; $j++) {
                if ($chunk[$j] !== '=') {
                    $binary .= str_pad(decbin(strpos($chars, $chunk[$j])), 5, '0', STR_PAD_LEFT);
                }
            }
            
            for ($j = 0; $j < strlen($binary); $j += 8) {
                if (strlen($binary) - $j >= 8) {
                    $decoded .= chr(bindec(substr($binary, $j, 8)));
                }
            }
        }
        
        return $decoded;
    }
    
    /**
     * Generate backup codes
     */
    private function generateBackupCodes($userId, $userType) {
        $codes = [];
        
        for ($i = 0; $i < $this->config['backup_codes_count']; $i++) {
            $codes[] = [
                'code' => $this->generateRandomCode(8),
                'used' => false,
                'used_at' => null
            ];
        }
        
        // Store encrypted backup codes
        $encryptedCodes = $this->encryption->encrypt(json_encode($codes));
        
        $query = "UPDATE mfa_settings SET backup_codes = ? WHERE user_id = ? AND user_type = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$encryptedCodes, $userId, $userType]);
        
        // Return only the codes (not the metadata)
        return array_column($codes, 'code');
    }
    
    /**
     * Generate random code
     */
    private function generateRandomCode($length) {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * Helper methods for SMS, backup codes, etc.
     */
    
    private function getTOTPSecret($userId, $userType) {
        $query = "SELECT totp_secret FROM mfa_settings WHERE user_id = ? AND user_type = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType]);
        $result = $stmt->fetch();
        
        return $result ? $this->encryption->decrypt($result['totp_secret']) : null;
    }
    
    private function isTOTPEnabled($userId, $userType) {
        $query = "SELECT totp_enabled FROM mfa_settings WHERE user_id = ? AND user_type = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType]);
        $result = $stmt->fetch();
        
        return $result && $result['totp_enabled'];
    }
    
    private function generateSMSCode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    private function sendSMSCode($userId, $userType, $phoneNumber, $code) {
        // Store SMS code in database
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['sms_expiry']);
        
        $query = "INSERT INTO mfa_sms_codes (user_id, user_type, code, phone_number, expires_at) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType, $code, $phoneNumber, $expiresAt]);
        
        // In production, integrate with SMS service (Twilio, AWS SNS, etc.)
        // For now, log the code (remove in production)
        error_log("SMS MFA Code for $phoneNumber: $code");
        
        return true;
    }
    
    private function verifySMSCode($userId, $userType, $code) {
        $query = "SELECT id FROM mfa_sms_codes 
                 WHERE user_id = ? AND user_type = ? AND code = ? 
                 AND expires_at > NOW() AND used = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType, $code]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark code as used
            $updateQuery = "UPDATE mfa_sms_codes SET used = TRUE WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$result['id']]);
            
            return true;
        }
        
        return false;
    }
    
    private function verifyBackupCode($userId, $userType, $code) {
        $query = "SELECT backup_codes FROM mfa_settings WHERE user_id = ? AND user_type = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['backup_codes']) {
            return false;
        }
        
        $codes = json_decode($this->encryption->decrypt($result['backup_codes']), true);
        
        foreach ($codes as &$backupCode) {
            if ($backupCode['code'] === $code && !$backupCode['used']) {
                $backupCode['used'] = true;
                $backupCode['used_at'] = date('Y-m-d H:i:s');
                
                // Update backup codes
                $encryptedCodes = $this->encryption->encrypt(json_encode($codes));
                $updateQuery = "UPDATE mfa_settings SET backup_codes = ? WHERE user_id = ? AND user_type = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute([$encryptedCodes, $userId, $userType]);
                
                return true;
            }
        }
        
        return false;
    }
    
    private function recordMFAAttempt($userId, $userType, $method, $code, $success) {
        $query = "INSERT INTO mfa_attempts (user_id, user_type, method, code_provided, success, ip_address, user_agent) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $userId, $userType, $method, $code, $success,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    private function isUserLockedOut($userId, $userType) {
        $query = "SELECT COUNT(*) as failed_count FROM mfa_attempts 
                 WHERE user_id = ? AND user_type = ? AND success = FALSE 
                 AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType, $this->config['lockout_duration']]);
        $result = $stmt->fetch();
        
        return $result['failed_count'] >= $this->config['max_attempts'];
    }
    
    private function clearFailedAttempts($userId, $userType) {
        $query = "DELETE FROM mfa_attempts 
                 WHERE user_id = ? AND user_type = ? AND success = FALSE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType]);
    }
    
    private function validatePhoneNumber($phoneNumber) {
        // Basic international phone number validation
        return preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber);
    }
    
    private function maskPhoneNumber($phoneNumber) {
        if (strlen($phoneNumber) < 4) {
            return '***';
        }
        
        return substr($phoneNumber, 0, -4) . '****';
    }
}

// Convenience functions
function getMFASystem() {
    return MFASystem::getInstance();
}

function isMFARequired($userId, $userType = 'admin') {
    $mfa = MFASystem::getInstance();
    return $mfa->isMFARequired($userId, $userType);
}

function verifyMFA($userId, $userType, $code, $method = 'auto') {
    $mfa = MFASystem::getInstance();
    return $mfa->verifyMFA($userId, $userType, $code, $method);
}

/**
 * MFA MIDDLEWARE
 * Protects sensitive endpoints with MFA requirements
 */
class MFAMiddleware {
    private $mfa;

    public function __construct() {
        $this->mfa = MFASystem::getInstance();
    }

    /**
     * Require MFA for current session
     */
    public function requireMFA($userType = 'admin') {
        $userIdKey = $userType === 'admin' ? 'admin_id' : 'user_id';

        if (!isset($_SESSION[$userIdKey])) {
            $this->sendMFAError('Authentication required', 401);
        }

        $userId = $_SESSION[$userIdKey];

        // Check if MFA is required for this user
        if (!$this->mfa->isMFARequired($userId, $userType)) {
            return; // MFA not required
        }

        // Check if MFA is enabled
        if (!$this->mfa->isMFAEnabled($userId, $userType)) {
            $this->sendMFAError('MFA setup required', 403, [
                'mfa_setup_required' => true,
                'setup_url' => $userType === 'admin' ? '/admin/mfa-setup' : '/user/mfa-setup'
            ]);
        }

        // Check if MFA is verified in current session
        if (!isset($_SESSION['mfa_verified']) || !$_SESSION['mfa_verified']) {
            $this->sendMFAError('MFA verification required', 403, [
                'mfa_verification_required' => true,
                'verification_url' => $userType === 'admin' ? '/admin/mfa-verify' : '/user/mfa-verify'
            ]);
        }

        // Check if MFA verification is still valid (30 minutes)
        $mfaAge = time() - ($_SESSION['mfa_verified_at'] ?? 0);
        if ($mfaAge > 1800) { // 30 minutes
            $_SESSION['mfa_verified'] = false;
            $this->sendMFAError('MFA verification expired', 403, [
                'mfa_verification_required' => true,
                'reason' => 'expired'
            ]);
        }
    }

    /**
     * Require fresh MFA verification (for sensitive operations)
     */
    public function requireFreshMFA($userType = 'admin', $maxAge = 300) {
        $this->requireMFA($userType);

        $mfaAge = time() - ($_SESSION['mfa_verified_at'] ?? 0);
        if ($mfaAge > $maxAge) {
            $this->sendMFAError('Fresh MFA verification required', 403, [
                'fresh_mfa_required' => true,
                'max_age_seconds' => $maxAge,
                'current_age_seconds' => $mfaAge
            ]);
        }
    }

    /**
     * Check if device is trusted
     */
    public function checkTrustedDevice($userId, $userType) {
        $deviceFingerprint = $this->generateDeviceFingerprint();

        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT id FROM mfa_trusted_devices
                 WHERE user_id = ? AND user_type = ? AND device_fingerprint = ?
                 AND expires_at > NOW() AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId, $userType, $deviceFingerprint]);

        return $stmt->fetch() !== false;
    }

    /**
     * Register trusted device
     */
    public function registerTrustedDevice($userId, $userType, $deviceName = null) {
        $deviceFingerprint = $this->generateDeviceFingerprint();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO mfa_trusted_devices
                 (user_id, user_type, device_fingerprint, device_name, ip_address, user_agent, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $userId, $userType, $deviceFingerprint, $deviceName,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);

        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'trusted_device_registered', SecurityLogger::LEVEL_INFO,
            'Trusted device registered', ['device_name' => $deviceName, 'user_type' => $userType], $userId);
    }

    /**
     * Generate device fingerprint
     */
    private function generateDeviceFingerprint() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Send MFA error response
     */
    private function sendMFAError($message, $code = 403, $data = []) {
        http_response_code($code);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => $message,
            'mfa_required' => true,
            'timestamp' => date('c')
        ];

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        echo json_encode($response);
        exit;
    }
}

/**
 * SENSITIVE ENDPOINT PROTECTION
 * Decorator for protecting sensitive operations
 */
class SensitiveEndpointProtection {

    /**
     * Protect financial operations
     */
    public static function protectFinancialOperation($userType = 'admin') {
        $mfaMiddleware = new MFAMiddleware();
        $mfaMiddleware->requireFreshMFA($userType, 300); // 5 minutes for financial ops
    }

    /**
     * Protect admin operations
     */
    public static function protectAdminOperation() {
        $mfaMiddleware = new MFAMiddleware();
        $mfaMiddleware->requireMFA('admin');
    }

    /**
     * Protect user account changes
     */
    public static function protectAccountChanges($userType = 'user') {
        $mfaMiddleware = new MFAMiddleware();
        $mfaMiddleware->requireFreshMFA($userType, 600); // 10 minutes for account changes
    }

    /**
     * Protect sensitive data access
     */
    public static function protectSensitiveData($userType = 'admin') {
        $mfaMiddleware = new MFAMiddleware();
        $mfaMiddleware->requireMFA($userType);
    }
}

// Convenience functions
function requireMFA($userType = 'admin') {
    $middleware = new MFAMiddleware();
    $middleware->requireMFA($userType);
}

function requireFreshMFA($userType = 'admin', $maxAge = 300) {
    $middleware = new MFAMiddleware();
    $middleware->requireFreshMFA($userType, $maxAge);
}

function protectFinancialOperation($userType = 'admin') {
    SensitiveEndpointProtection::protectFinancialOperation($userType);
}

function protectAdminOperation() {
    SensitiveEndpointProtection::protectAdminOperation();
}
?>
