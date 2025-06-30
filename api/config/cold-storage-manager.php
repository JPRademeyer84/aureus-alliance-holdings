<?php
/**
 * COLD STORAGE MANAGEMENT SYSTEM
 * Manages offline cryptocurrency storage for maximum security
 */

require_once 'enterprise-wallet-security.php';

class ColdStorageManager {
    private static $instance = null;
    private $db;
    private $walletSecurity;
    
    // Storage types
    const STORAGE_TYPE_HARDWARE = 'hardware';
    const STORAGE_TYPE_PAPER = 'paper';
    const STORAGE_TYPE_AIR_GAPPED = 'air_gapped';
    
    // Security protocols
    const PROTOCOL_BANK_VAULT = 'bank_vault';
    const PROTOCOL_SAFE_DEPOSIT = 'safe_deposit_box';
    const PROTOCOL_SECURE_FACILITY = 'secure_facility';
    const PROTOCOL_MULTI_LOCATION = 'multi_location';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->walletSecurity = EnterpriseWalletSecurity::getInstance();
        $this->initializeColdStorageTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize cold storage tables
     */
    private function initializeColdStorageTables() {
        $tables = [
            // Cold storage vault details
            "CREATE TABLE IF NOT EXISTS cold_storage_vaults (
                id VARCHAR(36) PRIMARY KEY,
                vault_name VARCHAR(100) NOT NULL,
                vault_type ENUM('hardware', 'paper', 'air_gapped') NOT NULL,
                security_level TINYINT NOT NULL DEFAULT 3,
                storage_protocol ENUM('bank_vault', 'safe_deposit_box', 'secure_facility', 'multi_location') NOT NULL,
                total_balance_usdt DECIMAL(15,2) DEFAULT 0.00,
                last_balance_check TIMESTAMP NULL,
                physical_location VARCHAR(200),
                access_protocol TEXT,
                emergency_recovery_info TEXT,
                insurance_policy VARCHAR(100),
                insurance_amount DECIMAL(15,2),
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_accessed TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_vault_type (vault_type),
                INDEX idx_security_level (security_level),
                INDEX idx_storage_protocol (storage_protocol)
            )",
            
            // Cold storage wallet assignments
            "CREATE TABLE IF NOT EXISTS cold_storage_wallets (
                id VARCHAR(36) PRIMARY KEY,
                vault_id VARCHAR(36) NOT NULL,
                chain VARCHAR(50) NOT NULL,
                wallet_address_hash VARCHAR(64) NOT NULL,
                encrypted_private_key TEXT,
                key_derivation_path VARCHAR(200),
                balance_usdt DECIMAL(15,2) DEFAULT 0.00,
                last_transaction_hash VARCHAR(100),
                last_transaction_date TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (vault_id) REFERENCES cold_storage_vaults(id),
                INDEX idx_vault_chain (vault_id, chain),
                INDEX idx_address_hash (wallet_address_hash)
            )",
            
            // Cold storage access log
            "CREATE TABLE IF NOT EXISTS cold_storage_access_log (
                id VARCHAR(36) PRIMARY KEY,
                vault_id VARCHAR(36) NOT NULL,
                access_type ENUM('balance_check', 'withdrawal_prep', 'emergency_access', 'maintenance') NOT NULL,
                accessed_by VARCHAR(36) NOT NULL,
                access_reason TEXT NOT NULL,
                mfa_verified BOOLEAN DEFAULT FALSE,
                physical_verification BOOLEAN DEFAULT FALSE,
                witness_required BOOLEAN DEFAULT FALSE,
                witness_id VARCHAR(36),
                access_duration_minutes INT,
                items_accessed JSON,
                access_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                FOREIGN KEY (vault_id) REFERENCES cold_storage_vaults(id),
                INDEX idx_vault_access (vault_id, access_type),
                INDEX idx_accessed_by (accessed_by),
                INDEX idx_access_timestamp (access_timestamp)
            )",
            
            // Cold storage transfer requests
            "CREATE TABLE IF NOT EXISTS cold_storage_transfers (
                id VARCHAR(36) PRIMARY KEY,
                vault_id VARCHAR(36) NOT NULL,
                transfer_type ENUM('hot_to_cold', 'cold_to_hot', 'cold_to_cold') NOT NULL,
                source_address VARCHAR(100),
                destination_address VARCHAR(100) NOT NULL,
                amount_usdt DECIMAL(15,2) NOT NULL,
                chain VARCHAR(50) NOT NULL,
                justification TEXT NOT NULL,
                risk_assessment JSON,
                required_approvals TINYINT NOT NULL DEFAULT 3,
                current_approvals TINYINT DEFAULT 0,
                status ENUM('pending', 'approved', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
                initiated_by VARCHAR(36) NOT NULL,
                initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                approved_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                transaction_hash VARCHAR(100),
                FOREIGN KEY (vault_id) REFERENCES cold_storage_vaults(id),
                INDEX idx_vault_status (vault_id, status),
                INDEX idx_transfer_type (transfer_type),
                INDEX idx_initiated_by (initiated_by)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create cold storage table: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create cold storage vault
     */
    public function createColdStorageVault($vaultData, $adminId) {
        $vaultId = bin2hex(random_bytes(16));
        
        // Validate vault data
        $this->validateVaultData($vaultData);
        
        // Determine security requirements
        $securityRequirements = $this->calculateSecurityRequirements($vaultData);
        
        $query = "INSERT INTO cold_storage_vaults (
            id, vault_name, vault_type, security_level, storage_protocol,
            physical_location, access_protocol, emergency_recovery_info,
            insurance_policy, insurance_amount, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $vaultId,
            $vaultData['name'],
            $vaultData['type'],
            $securityRequirements['security_level'],
            $vaultData['storage_protocol'],
            $vaultData['physical_location'] ?? null,
            json_encode($securityRequirements['access_protocol']),
            $vaultData['emergency_recovery'] ?? null,
            $vaultData['insurance_policy'] ?? null,
            $vaultData['insurance_amount'] ?? 0,
            $adminId
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create cold storage vault');
        }
        
        // Log vault creation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cold_storage_vault_created', SecurityLogger::LEVEL_CRITICAL,
            'Cold storage vault created', [
                'vault_id' => $vaultId,
                'vault_name' => $vaultData['name'],
                'vault_type' => $vaultData['type'],
                'security_level' => $securityRequirements['security_level']
            ], null, $adminId);
        
        return [
            'vault_id' => $vaultId,
            'security_requirements' => $securityRequirements,
            'setup_instructions' => $this->generateSetupInstructions($vaultData['type'])
        ];
    }
    
    /**
     * Add wallet to cold storage
     */
    public function addWalletToColdStorage($vaultId, $walletData, $adminId) {
        // Verify vault exists
        $vault = $this->getColdStorageVault($vaultId);
        if (!$vault) {
            throw new Exception('Cold storage vault not found');
        }
        
        // Validate wallet data
        $this->validateColdWalletData($walletData);
        
        // Encrypt private key with maximum security
        $encryptedPrivateKey = null;
        if (isset($walletData['private_key'])) {
            $encryptedPrivateKey = $this->encryptPrivateKeyForColdStorage($walletData['private_key']);
        }
        
        $walletId = bin2hex(random_bytes(16));
        $addressHash = hash('sha256', $walletData['address'] . $vaultId . time());
        
        $query = "INSERT INTO cold_storage_wallets (
            id, vault_id, chain, wallet_address_hash, encrypted_private_key,
            key_derivation_path, balance_usdt
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $walletId,
            $vaultId,
            $walletData['chain'],
            $addressHash,
            $encryptedPrivateKey,
            $walletData['derivation_path'] ?? null,
            $walletData['initial_balance'] ?? 0
        ]);
        
        if (!$success) {
            throw new Exception('Failed to add wallet to cold storage');
        }
        
        // Update vault balance
        $this->updateVaultBalance($vaultId);
        
        // Log wallet addition
        $this->logColdStorageAccess($vaultId, 'wallet_addition', $adminId, 
            'Added new wallet to cold storage', ['wallet_id' => $walletId, 'chain' => $walletData['chain']]);
        
        return [
            'wallet_id' => $walletId,
            'vault_id' => $vaultId,
            'security_instructions' => $this->generateWalletSecurityInstructions($vault['vault_type'])
        ];
    }
    
    /**
     * Initiate cold storage transfer
     */
    public function initiateColdStorageTransfer($transferData, $adminId) {
        $transferId = bin2hex(random_bytes(16));
        
        // Validate transfer data
        $this->validateTransferData($transferData);
        
        // Calculate risk assessment
        $riskAssessment = $this->calculateTransferRisk($transferData);
        
        // Determine required approvals based on amount and risk
        $requiredApprovals = $this->calculateRequiredApprovalsForTransfer($transferData, $riskAssessment);
        
        $query = "INSERT INTO cold_storage_transfers (
            id, vault_id, transfer_type, source_address, destination_address,
            amount_usdt, chain, justification, risk_assessment, required_approvals, initiated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $transferId,
            $transferData['vault_id'],
            $transferData['transfer_type'],
            $transferData['source_address'] ?? null,
            $transferData['destination_address'],
            $transferData['amount'],
            $transferData['chain'],
            $transferData['justification'],
            json_encode($riskAssessment),
            $requiredApprovals,
            $adminId
        ]);
        
        if (!$success) {
            throw new Exception('Failed to initiate cold storage transfer');
        }
        
        // Log transfer initiation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cold_storage_transfer_initiated', SecurityLogger::LEVEL_CRITICAL,
            'Cold storage transfer initiated', [
                'transfer_id' => $transferId,
                'transfer_type' => $transferData['transfer_type'],
                'amount' => $transferData['amount'],
                'risk_score' => $riskAssessment['risk_score'],
                'required_approvals' => $requiredApprovals
            ], null, $adminId);
        
        return [
            'transfer_id' => $transferId,
            'required_approvals' => $requiredApprovals,
            'risk_assessment' => $riskAssessment,
            'approval_process' => $this->getApprovalProcessInstructions($requiredApprovals)
        ];
    }
    
    /**
     * Perform balance check on cold storage
     */
    public function performBalanceCheck($vaultId, $adminId, $physicalVerification = false) {
        $vault = $this->getColdStorageVault($vaultId);
        if (!$vault) {
            throw new Exception('Cold storage vault not found');
        }
        
        // Log access
        $this->logColdStorageAccess($vaultId, 'balance_check', $adminId, 
            'Performed balance verification', [], $physicalVerification);
        
        // Get all wallets in vault
        $wallets = $this->getVaultWallets($vaultId);
        $totalBalance = 0;
        $balanceDetails = [];
        
        foreach ($wallets as $wallet) {
            // In a real implementation, this would query blockchain APIs
            $balance = $this->getWalletBalance($wallet);
            $totalBalance += $balance;
            
            $balanceDetails[] = [
                'wallet_id' => $wallet['id'],
                'chain' => $wallet['chain'],
                'balance_usdt' => $balance,
                'last_updated' => date('c')
            ];
        }
        
        // Update vault balance
        $query = "UPDATE cold_storage_vaults 
                 SET total_balance_usdt = ?, last_balance_check = NOW() 
                 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$totalBalance, $vaultId]);
        
        return [
            'vault_id' => $vaultId,
            'total_balance_usdt' => $totalBalance,
            'wallet_count' => count($wallets),
            'balance_details' => $balanceDetails,
            'last_check' => date('c'),
            'physical_verification' => $physicalVerification
        ];
    }
    
    /**
     * Helper methods
     */
    
    private function validateVaultData($data) {
        $required = ['name', 'type', 'storage_protocol'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $validTypes = [self::STORAGE_TYPE_HARDWARE, self::STORAGE_TYPE_PAPER, self::STORAGE_TYPE_AIR_GAPPED];
        if (!in_array($data['type'], $validTypes)) {
            throw new Exception('Invalid vault type');
        }
    }
    
    private function calculateSecurityRequirements($vaultData) {
        $securityLevel = 3; // Maximum for cold storage
        
        $accessProtocol = [
            'mfa_required' => true,
            'physical_verification' => true,
            'witness_required' => $vaultData['type'] !== self::STORAGE_TYPE_HARDWARE,
            'dual_control' => true,
            'access_window' => 'business_hours_only',
            'maximum_access_duration' => 120, // minutes
            'cooling_period' => 24 // hours between accesses
        ];
        
        if ($vaultData['type'] === self::STORAGE_TYPE_PAPER) {
            $accessProtocol['environmental_controls'] = true;
            $accessProtocol['tamper_evident_seals'] = true;
        }
        
        return [
            'security_level' => $securityLevel,
            'access_protocol' => $accessProtocol
        ];
    }
    
    private function generateSetupInstructions($vaultType) {
        $instructions = [
            'general' => [
                'Ensure physical security of storage location',
                'Implement dual control access procedures',
                'Set up environmental monitoring',
                'Establish regular audit schedule'
            ]
        ];
        
        switch ($vaultType) {
            case self::STORAGE_TYPE_HARDWARE:
                $instructions['specific'] = [
                    'Use certified hardware security modules',
                    'Implement tamper-evident packaging',
                    'Store in fireproof, waterproof container',
                    'Maintain backup devices in separate location'
                ];
                break;
                
            case self::STORAGE_TYPE_PAPER:
                $instructions['specific'] = [
                    'Use archival quality paper and ink',
                    'Laminate or use protective sleeves',
                    'Store in multiple secure locations',
                    'Use BIP39 mnemonic phrases',
                    'Implement Shamir secret sharing if possible'
                ];
                break;
                
            case self::STORAGE_TYPE_AIR_GAPPED:
                $instructions['specific'] = [
                    'Ensure complete network isolation',
                    'Use dedicated hardware',
                    'Implement secure boot procedures',
                    'Regular security audits of air-gapped system'
                ];
                break;
        }
        
        return $instructions;
    }
    
    private function encryptPrivateKeyForColdStorage($privateKey) {
        // Use maximum encryption for cold storage
        $salt = bin2hex(random_bytes(32));
        $key = hash_pbkdf2('sha256', $_ENV['COLD_STORAGE_MASTER_KEY'] ?? 'default_cold_key', $salt, 100000, 32, true);
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt($privateKey, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        return base64_encode($salt . $iv . $tag . $encrypted);
    }
    
    private function logColdStorageAccess($vaultId, $accessType, $adminId, $reason, $itemsAccessed = [], $physicalVerification = false) {
        $accessId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO cold_storage_access_log (
            id, vault_id, access_type, accessed_by, access_reason,
            physical_verification, items_accessed, ip_address
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $accessId,
            $vaultId,
            $accessType,
            $adminId,
            $reason,
            $physicalVerification,
            json_encode($itemsAccessed),
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cold_storage_access', SecurityLogger::LEVEL_CRITICAL,
            "Cold storage access: $accessType", [
                'vault_id' => $vaultId,
                'access_type' => $accessType,
                'physical_verification' => $physicalVerification
            ], null, $adminId);
    }
    
    private function getColdStorageVault($vaultId) {
        $query = "SELECT * FROM cold_storage_vaults WHERE id = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$vaultId]);
        return $stmt->fetch();
    }
    
    private function getVaultWallets($vaultId) {
        $query = "SELECT * FROM cold_storage_wallets WHERE vault_id = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$vaultId]);
        return $stmt->fetchAll();
    }
    
    private function getWalletBalance($wallet) {
        // Mock balance - in real implementation, query blockchain
        return rand(1000, 50000);
    }
    
    private function updateVaultBalance($vaultId) {
        $query = "UPDATE cold_storage_vaults 
                 SET total_balance_usdt = (
                     SELECT COALESCE(SUM(balance_usdt), 0) 
                     FROM cold_storage_wallets 
                     WHERE vault_id = ? AND is_active = TRUE
                 ) WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$vaultId, $vaultId]);
    }
    
    private function validateColdWalletData($data) {
        $required = ['address', 'chain'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
    }
    
    private function validateTransferData($data) {
        $required = ['vault_id', 'transfer_type', 'destination_address', 'amount', 'chain', 'justification'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
    }
    
    private function calculateTransferRisk($transferData) {
        $riskScore = 0.0;
        
        // Amount-based risk
        if ($transferData['amount'] > 100000) $riskScore += 0.4;
        elseif ($transferData['amount'] > 50000) $riskScore += 0.2;
        
        // Transfer type risk
        if ($transferData['transfer_type'] === 'cold_to_hot') $riskScore += 0.3;
        
        return [
            'risk_score' => min(1.0, $riskScore),
            'risk_factors' => ['amount_based', 'transfer_type'],
            'calculated_at' => date('c')
        ];
    }
    
    private function calculateRequiredApprovalsForTransfer($transferData, $riskAssessment) {
        $baseApprovals = 3; // Minimum for cold storage
        
        if ($riskAssessment['risk_score'] > 0.7) $baseApprovals += 2;
        elseif ($riskAssessment['risk_score'] > 0.4) $baseApprovals += 1;
        
        if ($transferData['amount'] > 500000) $baseApprovals += 1;
        
        return min(5, $baseApprovals);
    }
    
    private function getApprovalProcessInstructions($requiredApprovals) {
        return [
            'required_approvals' => $requiredApprovals,
            'approval_roles' => ['ceo', 'cto', 'security_officer', 'compliance_officer', 'external_auditor'],
            'process_steps' => [
                'Submit transfer request with justification',
                'Security review and risk assessment',
                'Multi-signature approval collection',
                'Physical verification if required',
                'Execution with dual control'
            ]
        ];
    }
    
    private function generateWalletSecurityInstructions($vaultType) {
        return [
            'backup_requirements' => 'Create multiple secure backups',
            'verification_process' => 'Verify wallet addresses before funding',
            'access_controls' => 'Implement strict access controls',
            'monitoring' => 'Set up balance monitoring alerts'
        ];
    }
}

// Convenience functions
function createColdStorageVault($vaultData, $adminId) {
    $coldStorage = ColdStorageManager::getInstance();
    return $coldStorage->createColdStorageVault($vaultData, $adminId);
}

function addWalletToColdStorage($vaultId, $walletData, $adminId) {
    $coldStorage = ColdStorageManager::getInstance();
    return $coldStorage->addWalletToColdStorage($vaultId, $walletData, $adminId);
}

function initiateColdStorageTransfer($transferData, $adminId) {
    $coldStorage = ColdStorageManager::getInstance();
    return $coldStorage->initiateColdStorageTransfer($transferData, $adminId);
}
?>
