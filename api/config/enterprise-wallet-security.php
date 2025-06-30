<?php
/**
 * ENTERPRISE WALLET SECURITY SYSTEM
 * Bank-level security for cryptocurrency wallet management
 */

require_once 'security-logger.php';
require_once 'data-encryption.php';
require_once 'mfa-system.php';

class EnterpriseWalletSecurity {
    private static $instance = null;
    private $db;
    private $encryption;
    private $mfa;
    
    // Security levels
    const SECURITY_LEVEL_STANDARD = 1;
    const SECURITY_LEVEL_HIGH = 2;
    const SECURITY_LEVEL_CRITICAL = 3;
    
    // Wallet types
    const WALLET_TYPE_HOT = 'hot';
    const WALLET_TYPE_WARM = 'warm';
    const WALLET_TYPE_COLD = 'cold';
    
    // Transaction approval levels
    const APPROVAL_LEVEL_SINGLE = 1;
    const APPROVAL_LEVEL_DUAL = 2;
    const APPROVAL_LEVEL_MULTI = 3;
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->encryption = DataEncryption::getInstance();
        $this->mfa = MFASystem::getInstance();
        $this->initializeSecurityTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize security tables
     */
    private function initializeSecurityTables() {
        $tables = [
            // Enhanced wallet storage with security levels
            "CREATE TABLE IF NOT EXISTS secure_wallets (
                id VARCHAR(36) PRIMARY KEY,
                wallet_name VARCHAR(100) NOT NULL,
                chain VARCHAR(50) NOT NULL,
                wallet_type ENUM('hot', 'warm', 'cold') NOT NULL DEFAULT 'hot',
                security_level TINYINT NOT NULL DEFAULT 1,
                encrypted_address TEXT NOT NULL,
                address_hash VARCHAR(64) NOT NULL,
                key_derivation_path VARCHAR(200),
                hsm_key_id VARCHAR(100),
                multi_sig_config JSON,
                approval_threshold TINYINT NOT NULL DEFAULT 1,
                daily_limit_usdt DECIMAL(15,2) DEFAULT 10000.00,
                monthly_limit_usdt DECIMAL(15,2) DEFAULT 100000.00,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_accessed TIMESTAMP NULL,
                access_count INT DEFAULT 0,
                INDEX idx_chain (chain),
                INDEX idx_wallet_type (wallet_type),
                INDEX idx_security_level (security_level),
                INDEX idx_address_hash (address_hash)
            )",
            
            // Transaction approval workflow
            "CREATE TABLE IF NOT EXISTS wallet_transaction_approvals (
                id VARCHAR(36) PRIMARY KEY,
                wallet_id VARCHAR(36) NOT NULL,
                transaction_type ENUM('withdrawal', 'transfer', 'emergency') NOT NULL,
                amount_usdt DECIMAL(15,2) NOT NULL,
                destination_address VARCHAR(100) NOT NULL,
                transaction_data JSON NOT NULL,
                required_approvals TINYINT NOT NULL,
                current_approvals TINYINT DEFAULT 0,
                status ENUM('pending', 'approved', 'rejected', 'expired', 'executed') DEFAULT 'pending',
                initiated_by VARCHAR(36) NOT NULL,
                initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                executed_at TIMESTAMP NULL,
                execution_tx_hash VARCHAR(100) NULL,
                risk_score DECIMAL(3,2) DEFAULT 0.00,
                FOREIGN KEY (wallet_id) REFERENCES secure_wallets(id),
                INDEX idx_status (status),
                INDEX idx_expires_at (expires_at),
                INDEX idx_initiated_by (initiated_by)
            )",
            
            // Individual approvals
            "CREATE TABLE IF NOT EXISTS wallet_approval_signatures (
                id VARCHAR(36) PRIMARY KEY,
                approval_id VARCHAR(36) NOT NULL,
                approver_id VARCHAR(36) NOT NULL,
                approval_type ENUM('admin', 'security_officer', 'compliance') NOT NULL,
                mfa_verified BOOLEAN DEFAULT FALSE,
                signature_hash VARCHAR(128) NOT NULL,
                approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent TEXT,
                FOREIGN KEY (approval_id) REFERENCES wallet_transaction_approvals(id),
                UNIQUE KEY unique_approval_per_user (approval_id, approver_id),
                INDEX idx_approver (approver_id),
                INDEX idx_approval_type (approval_type)
            )",
            
            // Real-time transaction monitoring
            "CREATE TABLE IF NOT EXISTS wallet_transaction_monitoring (
                id VARCHAR(36) PRIMARY KEY,
                wallet_id VARCHAR(36) NOT NULL,
                transaction_hash VARCHAR(100) NOT NULL,
                chain VARCHAR(50) NOT NULL,
                transaction_type ENUM('incoming', 'outgoing') NOT NULL,
                amount_usdt DECIMAL(15,2) NOT NULL,
                from_address VARCHAR(100) NOT NULL,
                to_address VARCHAR(100) NOT NULL,
                block_number BIGINT,
                confirmations INT DEFAULT 0,
                status ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
                risk_flags JSON,
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                confirmed_at TIMESTAMP NULL,
                FOREIGN KEY (wallet_id) REFERENCES secure_wallets(id),
                UNIQUE KEY unique_tx_hash (transaction_hash, chain),
                INDEX idx_wallet_status (wallet_id, status),
                INDEX idx_chain_block (chain, block_number)
            )",
            
            // Cold storage management
            "CREATE TABLE IF NOT EXISTS cold_storage_vaults (
                id VARCHAR(36) PRIMARY KEY,
                vault_name VARCHAR(100) NOT NULL,
                vault_type ENUM('hardware', 'paper', 'air_gapped') NOT NULL,
                security_level TINYINT NOT NULL DEFAULT 3,
                total_balance_usdt DECIMAL(15,2) DEFAULT 0.00,
                last_balance_check TIMESTAMP NULL,
                physical_location VARCHAR(200),
                access_protocol TEXT,
                emergency_recovery_info TEXT,
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_accessed TIMESTAMP NULL,
                INDEX idx_vault_type (vault_type),
                INDEX idx_security_level (security_level)
            )",
            
            // HSM key management
            "CREATE TABLE IF NOT EXISTS hsm_key_management (
                id VARCHAR(36) PRIMARY KEY,
                key_id VARCHAR(100) NOT NULL UNIQUE,
                key_type ENUM('master', 'derived', 'backup') NOT NULL,
                algorithm VARCHAR(50) NOT NULL,
                key_usage ENUM('encryption', 'signing', 'derivation') NOT NULL,
                associated_wallet_id VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                rotation_schedule VARCHAR(50),
                last_rotated TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (associated_wallet_id) REFERENCES secure_wallets(id),
                INDEX idx_key_type (key_type),
                INDEX idx_key_usage (key_usage),
                INDEX idx_expires_at (expires_at)
            )",
            
            // Security audit trail
            "CREATE TABLE IF NOT EXISTS wallet_security_audit (
                id VARCHAR(36) PRIMARY KEY,
                wallet_id VARCHAR(36),
                operation_type VARCHAR(100) NOT NULL,
                operation_details JSON NOT NULL,
                security_level_required TINYINT NOT NULL,
                mfa_verified BOOLEAN DEFAULT FALSE,
                admin_id VARCHAR(36),
                ip_address VARCHAR(45),
                user_agent TEXT,
                risk_assessment JSON,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (wallet_id) REFERENCES secure_wallets(id),
                INDEX idx_wallet_operation (wallet_id, operation_type),
                INDEX idx_timestamp (timestamp),
                INDEX idx_admin_id (admin_id)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create wallet security table: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create secure wallet with enhanced security
     */
    public function createSecureWallet($walletData, $adminId) {
        $walletId = $this->generateSecureId();
        
        // Validate input
        $this->validateWalletCreationData($walletData);
        
        // Determine security level based on wallet type and amount limits
        $securityLevel = $this->calculateSecurityLevel($walletData);
        
        // Encrypt wallet address with HSM if available
        $encryptedAddress = $this->encryptWithHSM($walletData['address'], $securityLevel);
        $addressHash = hash('sha256', $walletData['address'] . $adminId . time());
        
        // Generate multi-signature configuration if required
        $multiSigConfig = null;
        if ($securityLevel >= self::SECURITY_LEVEL_HIGH) {
            $multiSigConfig = $this->generateMultiSigConfig($walletData);
        }
        
        // Insert wallet record
        $query = "INSERT INTO secure_wallets (
            id, wallet_name, chain, wallet_type, security_level, encrypted_address, 
            address_hash, hsm_key_id, multi_sig_config, approval_threshold, 
            daily_limit_usdt, monthly_limit_usdt, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $walletId,
            $walletData['name'],
            $walletData['chain'],
            $walletData['type'] ?? self::WALLET_TYPE_HOT,
            $securityLevel,
            $encryptedAddress['encrypted_data'],
            $addressHash,
            $encryptedAddress['hsm_key_id'] ?? null,
            json_encode($multiSigConfig),
            $this->getApprovalThreshold($securityLevel),
            $walletData['daily_limit'] ?? 10000.00,
            $walletData['monthly_limit'] ?? 100000.00,
            $adminId
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create secure wallet');
        }
        
        // Log wallet creation
        $this->logWalletOperation($walletId, 'wallet_created', [
            'wallet_name' => $walletData['name'],
            'chain' => $walletData['chain'],
            'security_level' => $securityLevel,
            'wallet_type' => $walletData['type'] ?? self::WALLET_TYPE_HOT
        ], $securityLevel, $adminId);
        
        return [
            'wallet_id' => $walletId,
            'security_level' => $securityLevel,
            'approval_threshold' => $this->getApprovalThreshold($securityLevel),
            'multi_sig_required' => $securityLevel >= self::SECURITY_LEVEL_HIGH
        ];
    }
    
    /**
     * Initiate transaction with approval workflow
     */
    public function initiateTransaction($walletId, $transactionData, $adminId) {
        // Validate wallet exists and is active
        $wallet = $this->getSecureWallet($walletId);
        if (!$wallet || !$wallet['is_active']) {
            throw new Exception('Wallet not found or inactive');
        }
        
        // Calculate risk score
        $riskScore = $this->calculateTransactionRisk($wallet, $transactionData);
        
        // Determine required approvals based on amount and risk
        $requiredApprovals = $this->calculateRequiredApprovals($wallet, $transactionData, $riskScore);
        
        // Create approval request
        $approvalId = $this->generateSecureId();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $query = "INSERT INTO wallet_transaction_approvals (
            id, wallet_id, transaction_type, amount_usdt, destination_address,
            transaction_data, required_approvals, initiated_by, expires_at, risk_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $approvalId,
            $walletId,
            $transactionData['type'],
            $transactionData['amount'],
            $transactionData['destination'],
            json_encode($transactionData),
            $requiredApprovals,
            $adminId,
            $expiresAt,
            $riskScore
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create transaction approval request');
        }
        
        // Log transaction initiation
        $this->logWalletOperation($walletId, 'transaction_initiated', [
            'approval_id' => $approvalId,
            'amount' => $transactionData['amount'],
            'destination' => $this->maskAddress($transactionData['destination']),
            'risk_score' => $riskScore,
            'required_approvals' => $requiredApprovals
        ], $wallet['security_level'], $adminId);
        
        return [
            'approval_id' => $approvalId,
            'required_approvals' => $requiredApprovals,
            'current_approvals' => 0,
            'risk_score' => $riskScore,
            'expires_at' => $expiresAt
        ];
    }
    
    /**
     * Generate secure ID
     */
    private function generateSecureId() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Validate wallet creation data
     */
    private function validateWalletCreationData($data) {
        $required = ['name', 'chain', 'address'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate address format
        if (!$this->validateAddressFormat($data['address'], $data['chain'])) {
            throw new Exception('Invalid wallet address format');
        }
    }
    
    /**
     * Calculate security level
     */
    private function calculateSecurityLevel($walletData) {
        $dailyLimit = $walletData['daily_limit'] ?? 10000;
        $monthlyLimit = $walletData['monthly_limit'] ?? 100000;
        $walletType = $walletData['type'] ?? self::WALLET_TYPE_HOT;
        
        if ($walletType === self::WALLET_TYPE_COLD || $monthlyLimit > 500000) {
            return self::SECURITY_LEVEL_CRITICAL;
        } elseif ($walletType === self::WALLET_TYPE_WARM || $dailyLimit > 50000) {
            return self::SECURITY_LEVEL_HIGH;
        } else {
            return self::SECURITY_LEVEL_STANDARD;
        }
    }
    
    /**
     * Get approval threshold based on security level
     */
    private function getApprovalThreshold($securityLevel) {
        switch ($securityLevel) {
            case self::SECURITY_LEVEL_CRITICAL:
                return self::APPROVAL_LEVEL_MULTI; // 3+ approvals
            case self::SECURITY_LEVEL_HIGH:
                return self::APPROVAL_LEVEL_DUAL; // 2 approvals
            default:
                return self::APPROVAL_LEVEL_SINGLE; // 1 approval
        }
    }
    
    /**
     * Log wallet operation
     */
    private function logWalletOperation($walletId, $operation, $details, $securityLevel, $adminId) {
        $query = "INSERT INTO wallet_security_audit (
            id, wallet_id, operation_type, operation_details, security_level_required,
            admin_id, ip_address, user_agent, risk_assessment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $this->generateSecureId(),
            $walletId,
            $operation,
            json_encode($details),
            $securityLevel,
            $adminId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode(['timestamp' => time(), 'security_level' => $securityLevel])
        ]);
        
        // Also log to security system
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_operation', SecurityLogger::LEVEL_INFO,
            "Wallet operation: $operation", array_merge($details, ['wallet_id' => $walletId]), null, $adminId);
    }

    /**
     * Encrypt with HSM if available
     */
    private function encryptWithHSM($data, $securityLevel) {
        if ($securityLevel >= self::SECURITY_LEVEL_HIGH && $this->isHSMAvailable()) {
            return $this->encryptWithHardwareModule($data);
        } else {
            return [
                'encrypted_data' => $this->encryption->encrypt($data),
                'hsm_key_id' => null
            ];
        }
    }

    /**
     * Check if HSM is available
     */
    private function isHSMAvailable() {
        // Check for HSM environment variables or configuration
        return !empty($_ENV['HSM_ENDPOINT']) && !empty($_ENV['HSM_API_KEY']);
    }

    /**
     * Encrypt with hardware security module
     */
    private function encryptWithHardwareModule($data) {
        // This would integrate with actual HSM like AWS CloudHSM, Azure Dedicated HSM, etc.
        // For now, we'll simulate HSM encryption with enhanced security

        $hsmKeyId = 'hsm_key_' . bin2hex(random_bytes(8));

        // Enhanced encryption with multiple layers
        $salt = bin2hex(random_bytes(32));
        $key = hash_pbkdf2('sha256', $_ENV['HSM_MASTER_KEY'] ?? 'default_hsm_key', $salt, 10000, 32, true);
        $iv = random_bytes(16);

        $encrypted = openssl_encrypt($data, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);

        $encryptedData = base64_encode($salt . $iv . $tag . $encrypted);

        // Store HSM key reference
        $this->storeHSMKeyReference($hsmKeyId, $salt);

        return [
            'encrypted_data' => $encryptedData,
            'hsm_key_id' => $hsmKeyId
        ];
    }

    /**
     * Store HSM key reference
     */
    private function storeHSMKeyReference($keyId, $salt) {
        $query = "INSERT INTO hsm_key_management (
            id, key_id, key_type, algorithm, key_usage, created_at
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $this->generateSecureId(),
            $keyId,
            'derived',
            'AES-256-GCM',
            'encryption',
            date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Generate multi-signature configuration
     */
    private function generateMultiSigConfig($walletData) {
        $securityLevel = $this->calculateSecurityLevel($walletData);

        if ($securityLevel < self::SECURITY_LEVEL_HIGH) {
            return null;
        }

        // Generate multi-sig configuration based on security level
        $config = [
            'type' => 'multi_signature',
            'required_signatures' => $securityLevel === self::SECURITY_LEVEL_CRITICAL ? 3 : 2,
            'total_signers' => $securityLevel === self::SECURITY_LEVEL_CRITICAL ? 5 : 3,
            'signer_roles' => [
                'primary_admin',
                'security_officer',
                'compliance_officer'
            ],
            'emergency_recovery' => [
                'enabled' => true,
                'required_signatures' => $securityLevel === self::SECURITY_LEVEL_CRITICAL ? 4 : 3,
                'timeout_hours' => 72
            ],
            'created_at' => date('c')
        ];

        if ($securityLevel === self::SECURITY_LEVEL_CRITICAL) {
            $config['signer_roles'][] = 'ceo_approval';
            $config['signer_roles'][] = 'external_auditor';
        }

        return $config;
    }

    /**
     * Calculate transaction risk score
     */
    private function calculateTransactionRisk($wallet, $transactionData) {
        $riskScore = 0.0;

        // Amount-based risk
        $amount = $transactionData['amount'];
        if ($amount > $wallet['daily_limit_usdt'] * 0.8) {
            $riskScore += 0.3;
        }
        if ($amount > $wallet['monthly_limit_usdt'] * 0.5) {
            $riskScore += 0.2;
        }

        // Destination address risk
        if (!$this->isKnownAddress($transactionData['destination'])) {
            $riskScore += 0.2;
        }

        // Time-based risk (outside business hours)
        $hour = (int)date('H');
        if ($hour < 9 || $hour > 17) {
            $riskScore += 0.1;
        }

        // Frequency risk (multiple transactions in short time)
        if ($this->hasRecentTransactions($wallet['id'], 3600)) { // 1 hour
            $riskScore += 0.15;
        }

        // Geographic risk (unusual IP location)
        if ($this->isUnusualLocation($_SERVER['REMOTE_ADDR'] ?? '')) {
            $riskScore += 0.1;
        }

        return min(1.0, $riskScore); // Cap at 1.0
    }

    /**
     * Calculate required approvals
     */
    private function calculateRequiredApprovals($wallet, $transactionData, $riskScore) {
        $baseApprovals = $wallet['approval_threshold'];

        // Increase approvals based on risk score
        if ($riskScore > 0.7) {
            $baseApprovals += 2;
        } elseif ($riskScore > 0.4) {
            $baseApprovals += 1;
        }

        // Increase approvals for large amounts
        if ($transactionData['amount'] > $wallet['daily_limit_usdt']) {
            $baseApprovals += 1;
        }

        return min(5, $baseApprovals); // Cap at 5 approvals
    }

    /**
     * Get secure wallet
     */
    private function getSecureWallet($walletId) {
        $query = "SELECT * FROM secure_wallets WHERE id = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$walletId]);
        return $stmt->fetch();
    }

    /**
     * Validate address format
     */
    private function validateAddressFormat($address, $chain) {
        switch (strtolower($chain)) {
            case 'ethereum':
            case 'bsc':
            case 'polygon':
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
            case 'tron':
                return preg_match('/^T[a-zA-Z0-9]{33}$/', $address) === 1;
            case 'bitcoin':
                return preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address) === 1;
            default:
                return false;
        }
    }

    /**
     * Check if address is known/whitelisted
     */
    private function isKnownAddress($address) {
        $query = "SELECT COUNT(*) FROM secure_wallets WHERE address_hash = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([hash('sha256', $address)]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check for recent transactions
     */
    private function hasRecentTransactions($walletId, $timeWindow) {
        $query = "SELECT COUNT(*) FROM wallet_transaction_approvals
                 WHERE wallet_id = ? AND initiated_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$walletId, $timeWindow]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check for unusual location
     */
    private function isUnusualLocation($ipAddress) {
        // This would integrate with IP geolocation service
        // For now, return false (no unusual location detected)
        return false;
    }

    /**
     * Mask address for logging
     */
    private function maskAddress($address) {
        if (strlen($address) <= 10) {
            return $address;
        }
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }
}

// Convenience functions
function createSecureWallet($walletData, $adminId) {
    $security = EnterpriseWalletSecurity::getInstance();
    return $security->createSecureWallet($walletData, $adminId);
}

function initiateSecureTransaction($walletId, $transactionData, $adminId) {
    $security = EnterpriseWalletSecurity::getInstance();
    return $security->initiateTransaction($walletId, $transactionData, $adminId);
}
?>
