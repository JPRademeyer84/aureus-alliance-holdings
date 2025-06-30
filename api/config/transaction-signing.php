<?php
/**
 * TRANSACTION SIGNING AND VERIFICATION SYSTEM
 * Cryptographic transaction signing with multi-signature support
 */

require_once 'security-logger.php';
require_once 'data-encryption.php';

class TransactionSigning {
    private static $instance = null;
    private $db;
    private $encryption;
    
    // Signature algorithms
    const ALGO_RSA_SHA256 = 'RSA-SHA256';
    const ALGO_ECDSA_SHA256 = 'ECDSA-SHA256';
    const ALGO_ED25519 = 'ED25519';
    
    // Signature types
    const TYPE_SINGLE = 'single';
    const TYPE_MULTI = 'multi';
    const TYPE_THRESHOLD = 'threshold';
    
    // Signature status
    const STATUS_PENDING = 'pending';
    const STATUS_SIGNED = 'signed';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->encryption = DataEncryption::getInstance();
        $this->initializeSigningTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize transaction signing tables
     */
    private function initializeSigningTables() {
        $tables = [
            // Transaction signatures
            "CREATE TABLE IF NOT EXISTS transaction_signatures (
                id VARCHAR(36) PRIMARY KEY,
                transaction_id VARCHAR(36) NOT NULL,
                transaction_hash VARCHAR(128) NOT NULL,
                signature_type ENUM('single', 'multi', 'threshold') NOT NULL,
                required_signatures INT DEFAULT 1,
                current_signatures INT DEFAULT 0,
                signature_algorithm VARCHAR(50) NOT NULL,
                signature_data JSON,
                signature_status ENUM('pending', 'signed', 'verified', 'rejected', 'expired') DEFAULT 'pending',
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_transaction_hash (transaction_hash),
                INDEX idx_signature_status (signature_status),
                INDEX idx_expires_at (expires_at)
            )",
            
            // Individual signatures
            "CREATE TABLE IF NOT EXISTS signature_records (
                id VARCHAR(36) PRIMARY KEY,
                transaction_signature_id VARCHAR(36) NOT NULL,
                signer_id VARCHAR(36) NOT NULL,
                signer_type ENUM('user', 'admin', 'system') NOT NULL,
                signature_value TEXT NOT NULL,
                signature_algorithm VARCHAR(50) NOT NULL,
                public_key_hash VARCHAR(128),
                signature_metadata JSON,
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                verified BOOLEAN DEFAULT FALSE,
                verified_at TIMESTAMP NULL,
                FOREIGN KEY (transaction_signature_id) REFERENCES transaction_signatures(id),
                INDEX idx_transaction_signature_id (transaction_signature_id),
                INDEX idx_signer_id (signer_id),
                INDEX idx_verified (verified)
            )",
            
            // Signing keys
            "CREATE TABLE IF NOT EXISTS signing_keys (
                id VARCHAR(36) PRIMARY KEY,
                key_id VARCHAR(100) NOT NULL UNIQUE,
                owner_id VARCHAR(36) NOT NULL,
                owner_type ENUM('user', 'admin', 'system') NOT NULL,
                key_type ENUM('signing', 'verification') NOT NULL,
                algorithm VARCHAR(50) NOT NULL,
                public_key TEXT NOT NULL,
                private_key_encrypted TEXT,
                key_fingerprint VARCHAR(128) NOT NULL,
                key_usage JSON,
                is_active BOOLEAN DEFAULT TRUE,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                revoked_at TIMESTAMP NULL,
                INDEX idx_key_id (key_id),
                INDEX idx_owner_id (owner_id),
                INDEX idx_key_fingerprint (key_fingerprint),
                INDEX idx_is_active (is_active)
            )",
            
            // Multi-signature policies
            "CREATE TABLE IF NOT EXISTS multisig_policies (
                id VARCHAR(36) PRIMARY KEY,
                policy_name VARCHAR(100) NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                amount_threshold DECIMAL(15,8),
                required_signatures INT NOT NULL,
                authorized_signers JSON NOT NULL,
                approval_timeout_hours INT DEFAULT 24,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_transaction_type (transaction_type),
                INDEX idx_amount_threshold (amount_threshold),
                INDEX idx_is_active (is_active)
            )",
            
            // Signature verification log
            "CREATE TABLE IF NOT EXISTS signature_verification_log (
                id VARCHAR(36) PRIMARY KEY,
                transaction_signature_id VARCHAR(36) NOT NULL,
                verification_result BOOLEAN NOT NULL,
                verification_details JSON,
                verified_by VARCHAR(36),
                verification_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (transaction_signature_id) REFERENCES transaction_signatures(id),
                INDEX idx_transaction_signature_id (transaction_signature_id),
                INDEX idx_verification_result (verification_result),
                INDEX idx_verification_timestamp (verification_timestamp)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create transaction signing table: " . $e->getMessage());
            }
        }
        
        $this->initializeDefaultPolicies();
    }
    
    /**
     * Initialize default multi-signature policies
     */
    private function initializeDefaultPolicies() {
        // Check if policies already exist
        $query = "SELECT COUNT(*) FROM multisig_policies";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Policies already initialized
        }
        
        $defaultPolicies = [
            [
                'policy_name' => 'Large Withdrawal Policy',
                'transaction_type' => 'withdrawal',
                'amount_threshold' => 10000,
                'required_signatures' => 2,
                'authorized_signers' => ['admin', 'financial_officer'],
                'approval_timeout_hours' => 24
            ],
            [
                'policy_name' => 'Investment Approval Policy',
                'transaction_type' => 'investment',
                'amount_threshold' => 25000,
                'required_signatures' => 2,
                'authorized_signers' => ['admin', 'investment_manager'],
                'approval_timeout_hours' => 48
            ],
            [
                'policy_name' => 'System Transfer Policy',
                'transaction_type' => 'system_transfer',
                'amount_threshold' => 5000,
                'required_signatures' => 3,
                'authorized_signers' => ['admin', 'financial_officer', 'security_officer'],
                'approval_timeout_hours' => 12
            ]
        ];
        
        foreach ($defaultPolicies as $policy) {
            $this->createMultiSigPolicy(
                $policy['policy_name'],
                $policy['transaction_type'],
                $policy['amount_threshold'],
                $policy['required_signatures'],
                $policy['authorized_signers'],
                $policy['approval_timeout_hours']
            );
        }
    }
    
    /**
     * Generate signing key pair
     */
    public function generateSigningKeys($ownerId, $ownerType, $algorithm = self::ALGO_RSA_SHA256) {
        $keyId = 'key_' . bin2hex(random_bytes(16));
        
        // Generate key pair based on algorithm
        switch ($algorithm) {
            case self::ALGO_RSA_SHA256:
                $keyPair = $this->generateRSAKeyPair();
                break;
            case self::ALGO_ECDSA_SHA256:
                $keyPair = $this->generateECDSAKeyPair();
                break;
            case self::ALGO_ED25519:
                $keyPair = $this->generateED25519KeyPair();
                break;
            default:
                throw new Exception("Unsupported signature algorithm: $algorithm");
        }
        
        // Encrypt private key
        $encryptedPrivateKey = $this->encryption->encrypt($keyPair['private_key']);
        
        // Generate fingerprint
        $fingerprint = hash('sha256', $keyPair['public_key']);
        
        // Store keys
        $signingKeyId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO signing_keys (
            id, key_id, owner_id, owner_type, key_type, algorithm,
            public_key, private_key_encrypted, key_fingerprint, key_usage
        ) VALUES (?, ?, ?, ?, 'signing', ?, ?, ?, ?, ?)";
        
        $keyUsage = ['transaction_signing', 'document_signing'];
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $signingKeyId, $keyId, $ownerId, $ownerType, $algorithm,
            $keyPair['public_key'], $encryptedPrivateKey, $fingerprint, json_encode($keyUsage)
        ]);
        
        // Log key generation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'signing_key_generated', SecurityLogger::LEVEL_INFO,
            'Signing key pair generated', [
                'key_id' => $keyId,
                'owner_id' => $ownerId,
                'owner_type' => $ownerType,
                'algorithm' => $algorithm,
                'fingerprint' => $fingerprint
            ]);
        
        return [
            'key_id' => $keyId,
            'public_key' => $keyPair['public_key'],
            'fingerprint' => $fingerprint,
            'algorithm' => $algorithm
        ];
    }
    
    /**
     * Sign transaction
     */
    public function signTransaction($transactionId, $transactionData, $signerId, $signerType, $keyId = null) {
        // Generate transaction hash
        $transactionHash = $this->generateTransactionHash($transactionData);
        
        // Get or create transaction signature record
        $transactionSignature = $this->getOrCreateTransactionSignature($transactionId, $transactionHash);
        
        // Get signer's key
        if (!$keyId) {
            $keyId = $this->getDefaultSigningKey($signerId, $signerType);
        }
        
        $signingKey = $this->getSigningKey($keyId);
        if (!$signingKey) {
            throw new Exception("Signing key not found: $keyId");
        }
        
        // Decrypt private key
        $privateKey = $this->encryption->decrypt($signingKey['private_key_encrypted']);
        
        // Generate signature
        $signature = $this->generateSignature($transactionHash, $privateKey, $signingKey['algorithm']);
        
        // Store signature record
        $signatureRecordId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO signature_records (
            id, transaction_signature_id, signer_id, signer_type,
            signature_value, signature_algorithm, public_key_hash, signature_metadata
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $metadata = [
            'key_id' => $keyId,
            'signing_timestamp' => time(),
            'client_info' => [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        ];
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $signatureRecordId, $transactionSignature['id'], $signerId, $signerType,
            $signature, $signingKey['algorithm'], $signingKey['key_fingerprint'], json_encode($metadata)
        ]);
        
        // Update signature count
        $this->updateSignatureCount($transactionSignature['id']);
        
        // Check if all required signatures are collected
        $this->checkSignatureCompletion($transactionSignature['id']);
        
        // Log signature
        logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, 'transaction_signed', SecurityLogger::LEVEL_INFO,
            'Transaction signed', [
                'transaction_id' => $transactionId,
                'signer_id' => $signerId,
                'signer_type' => $signerType,
                'key_id' => $keyId,
                'signature_algorithm' => $signingKey['algorithm']
            ]);
        
        return [
            'signature_record_id' => $signatureRecordId,
            'transaction_signature_id' => $transactionSignature['id'],
            'signature' => $signature,
            'status' => 'signed'
        ];
    }
    
    /**
     * Verify transaction signature
     */
    public function verifyTransactionSignature($transactionSignatureId) {
        // Get transaction signature
        $query = "SELECT * FROM transaction_signatures WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionSignatureId]);
        $transactionSignature = $stmt->fetch();
        
        if (!$transactionSignature) {
            throw new Exception("Transaction signature not found: $transactionSignatureId");
        }
        
        // Get all signature records
        $query = "SELECT sr.*, sk.public_key, sk.algorithm 
                  FROM signature_records sr
                  JOIN signing_keys sk ON sr.public_key_hash = sk.key_fingerprint
                  WHERE sr.transaction_signature_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionSignatureId]);
        $signatures = $stmt->fetchAll();
        
        $verificationResults = [];
        $validSignatures = 0;
        
        foreach ($signatures as $signature) {
            $isValid = $this->verifySignature(
                $transactionSignature['transaction_hash'],
                $signature['signature_value'],
                $signature['public_key'],
                $signature['algorithm']
            );
            
            $verificationResults[] = [
                'signature_record_id' => $signature['id'],
                'signer_id' => $signature['signer_id'],
                'valid' => $isValid
            ];
            
            if ($isValid) {
                $validSignatures++;
                
                // Mark signature as verified
                $updateQuery = "UPDATE signature_records SET verified = TRUE, verified_at = NOW() WHERE id = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute([$signature['id']]);
            }
            
            // Log verification
            $this->logSignatureVerification($transactionSignatureId, $isValid, [
                'signature_record_id' => $signature['id'],
                'signer_id' => $signature['signer_id'],
                'algorithm' => $signature['algorithm']
            ]);
        }
        
        // Update transaction signature status
        $newStatus = ($validSignatures >= $transactionSignature['required_signatures']) ? 
                     self::STATUS_VERIFIED : self::STATUS_SIGNED;
        
        $updateQuery = "UPDATE transaction_signatures SET signature_status = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute([$newStatus, $transactionSignatureId]);
        
        return [
            'transaction_signature_id' => $transactionSignatureId,
            'verification_results' => $verificationResults,
            'valid_signatures' => $validSignatures,
            'required_signatures' => $transactionSignature['required_signatures'],
            'status' => $newStatus,
            'fully_verified' => ($validSignatures >= $transactionSignature['required_signatures'])
        ];
    }
    
    /**
     * Check if transaction requires multi-signature approval
     */
    public function requiresMultiSignature($transactionType, $amount) {
        $query = "SELECT * FROM multisig_policies 
                  WHERE transaction_type = ? AND amount_threshold <= ? AND is_active = TRUE
                  ORDER BY amount_threshold DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionType, $amount]);
        $policy = $stmt->fetch();
        
        return $policy ? $policy : false;
    }
    
    /**
     * Helper methods
     */
    
    private function generateRSAKeyPair() {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        $resource = openssl_pkey_new($config);
        openssl_pkey_export($resource, $privateKey);
        $publicKey = openssl_pkey_get_details($resource)['key'];
        
        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }
    
    private function generateECDSAKeyPair() {
        // Placeholder for ECDSA key generation
        // In production, use proper ECDSA library
        return $this->generateRSAKeyPair(); // Fallback to RSA for now
    }
    
    private function generateED25519KeyPair() {
        // Placeholder for ED25519 key generation
        // In production, use sodium_crypto_sign_keypair()
        return $this->generateRSAKeyPair(); // Fallback to RSA for now
    }
    
    private function generateTransactionHash($transactionData) {
        $canonicalData = json_encode($transactionData, JSON_UNESCAPED_SLASHES | JSON_SORT_KEYS);
        return hash('sha256', $canonicalData);
    }
    
    private function generateSignature($data, $privateKey, $algorithm) {
        switch ($algorithm) {
            case self::ALGO_RSA_SHA256:
                openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
                return base64_encode($signature);
                
            case self::ALGO_ECDSA_SHA256:
            case self::ALGO_ED25519:
                // Placeholder for other algorithms
                openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
                return base64_encode($signature);
                
            default:
                throw new Exception("Unsupported signature algorithm: $algorithm");
        }
    }
    
    private function verifySignature($data, $signature, $publicKey, $algorithm) {
        $binarySignature = base64_decode($signature);
        
        switch ($algorithm) {
            case self::ALGO_RSA_SHA256:
                return openssl_verify($data, $binarySignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
                
            case self::ALGO_ECDSA_SHA256:
            case self::ALGO_ED25519:
                // Placeholder for other algorithms
                return openssl_verify($data, $binarySignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
                
            default:
                throw new Exception("Unsupported signature algorithm: $algorithm");
        }
    }
    
    private function getOrCreateTransactionSignature($transactionId, $transactionHash) {
        // Check if signature record exists
        $query = "SELECT * FROM transaction_signatures WHERE transaction_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return $existing;
        }
        
        // Create new signature record
        $signatureId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO transaction_signatures (
            id, transaction_id, transaction_hash, signature_type,
            required_signatures, signature_algorithm
        ) VALUES (?, ?, ?, 'single', 1, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$signatureId, $transactionId, $transactionHash, self::ALGO_RSA_SHA256]);
        
        return [
            'id' => $signatureId,
            'transaction_id' => $transactionId,
            'transaction_hash' => $transactionHash,
            'signature_type' => 'single',
            'required_signatures' => 1,
            'current_signatures' => 0
        ];
    }
    
    private function getSigningKey($keyId) {
        $query = "SELECT * FROM signing_keys WHERE key_id = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$keyId]);
        return $stmt->fetch();
    }
    
    private function getDefaultSigningKey($ownerId, $ownerType) {
        $query = "SELECT key_id FROM signing_keys 
                  WHERE owner_id = ? AND owner_type = ? AND is_active = TRUE
                  ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ownerId, $ownerType]);
        $result = $stmt->fetch();
        
        if (!$result) {
            // Generate new key if none exists
            $keyPair = $this->generateSigningKeys($ownerId, $ownerType);
            return $keyPair['key_id'];
        }
        
        return $result['key_id'];
    }
    
    private function updateSignatureCount($transactionSignatureId) {
        $query = "UPDATE transaction_signatures 
                  SET current_signatures = (
                      SELECT COUNT(*) FROM signature_records 
                      WHERE transaction_signature_id = ?
                  )
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionSignatureId, $transactionSignatureId]);
    }
    
    private function checkSignatureCompletion($transactionSignatureId) {
        $query = "SELECT * FROM transaction_signatures WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$transactionSignatureId]);
        $signature = $stmt->fetch();
        
        if ($signature && $signature['current_signatures'] >= $signature['required_signatures']) {
            $updateQuery = "UPDATE transaction_signatures SET signature_status = 'signed' WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$transactionSignatureId]);
        }
    }
    
    private function createMultiSigPolicy($policyName, $transactionType, $amountThreshold, $requiredSignatures, $authorizedSigners, $timeoutHours) {
        $policyId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO multisig_policies (
            id, policy_name, transaction_type, amount_threshold,
            required_signatures, authorized_signers, approval_timeout_hours, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'system')";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $policyId, $policyName, $transactionType, $amountThreshold,
            $requiredSignatures, json_encode($authorizedSigners), $timeoutHours
        ]);
        
        return $policyId;
    }
    
    private function logSignatureVerification($transactionSignatureId, $result, $details) {
        $logId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO signature_verification_log (
            id, transaction_signature_id, verification_result,
            verification_details, verified_by
        ) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $logId, $transactionSignatureId, $result,
            json_encode($details), $_SESSION['admin_id'] ?? 'system'
        ]);
    }
}

// Convenience functions
function generateSigningKeys($ownerId, $ownerType, $algorithm = TransactionSigning::ALGO_RSA_SHA256) {
    $signing = TransactionSigning::getInstance();
    return $signing->generateSigningKeys($ownerId, $ownerType, $algorithm);
}

function signTransaction($transactionId, $transactionData, $signerId, $signerType, $keyId = null) {
    $signing = TransactionSigning::getInstance();
    return $signing->signTransaction($transactionId, $transactionData, $signerId, $signerType, $keyId);
}

function verifyTransactionSignature($transactionSignatureId) {
    $signing = TransactionSigning::getInstance();
    return $signing->verifyTransactionSignature($transactionSignatureId);
}

function requiresMultiSignature($transactionType, $amount) {
    $signing = TransactionSigning::getInstance();
    return $signing->requiresMultiSignature($transactionType, $amount);
}
?>
