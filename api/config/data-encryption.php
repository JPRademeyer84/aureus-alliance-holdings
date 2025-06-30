<?php
/**
 * DATA ENCRYPTION SYSTEM
 * Bank-level encryption for sensitive data at rest
 */

require_once 'env-loader.php';
require_once 'security-logger.php';

class DataEncryption {
    private $encryptionKey;
    private $cipher = 'AES-256-GCM';
    private static $instance = null;
    
    // Sensitive fields that require encryption
    private $encryptedFields = [
        'users' => ['email', 'full_name'],
        'user_profiles' => ['phone', 'date_of_birth', 'telegram_username', 'whatsapp_number'],
        'kyc_documents' => ['original_name', 'file_path'],
        'aureus_investments' => ['name', 'email', 'wallet_address'],
        'commission_transactions' => ['referrer_user_id', 'referred_user_id'],
        'commission_balances_primary' => ['balance_hash'],
        'security_events' => ['event_data'],
        'chat_messages' => ['message_content']
    ];
    
    private function __construct() {
        $this->initializeEncryption();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize encryption system
     */
    private function initializeEncryption() {
        // Get encryption key from environment
        $envKey = EnvLoader::get('DATA_ENCRYPTION_KEY');
        
        if (!$envKey) {
            // Generate a new key if none exists (development only)
            if (!Environment::isProduction()) {
                $envKey = base64_encode(random_bytes(32));
                error_log("WARNING: Generated temporary encryption key. Set DATA_ENCRYPTION_KEY in production!");
            } else {
                throw new Exception('DATA_ENCRYPTION_KEY must be set in production environment');
            }
        }
        
        $this->encryptionKey = base64_decode($envKey);
        
        if (strlen($this->encryptionKey) !== 32) {
            throw new Exception('Encryption key must be 32 bytes (256 bits)');
        }
        
        // Log encryption system initialization
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_init', SecurityLogger::LEVEL_INFO,
            'Data encryption system initialized');
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data, $associatedData = '') {
        if (empty($data)) {
            return $data;
        }
        
        try {
            // Generate random IV
            $iv = random_bytes(12); // 96-bit IV for GCM
            
            // Encrypt data
            $encrypted = openssl_encrypt(
                $data,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $associatedData
            );
            
            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }
            
            // Combine IV, tag, and encrypted data
            $result = base64_encode($iv . $tag . $encrypted);
            
            return $result;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_failed', SecurityLogger::LEVEL_CRITICAL,
                'Data encryption failed', ['error' => $e->getMessage()]);
            throw new Exception('Encryption failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encryptedData, $associatedData = '') {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        try {
            // Decode base64
            $data = base64_decode($encryptedData);
            
            if ($data === false || strlen($data) < 28) { // 12 (IV) + 16 (tag) minimum
                throw new Exception('Invalid encrypted data format');
            }
            
            // Extract components
            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $encrypted = substr($data, 28);
            
            // Decrypt data
            $decrypted = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $associatedData
            );
            
            if ($decrypted === false) {
                throw new Exception('Decryption failed - data may be corrupted or tampered with');
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'decryption_failed', SecurityLogger::LEVEL_CRITICAL,
                'Data decryption failed', ['error' => $e->getMessage()]);
            throw new Exception('Decryption failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Encrypt data for database storage
     */
    public function encryptForDatabase($tableName, $fieldName, $value) {
        if (!$this->shouldEncryptField($tableName, $fieldName)) {
            return $value;
        }
        
        if (empty($value)) {
            return $value;
        }
        
        // Use table.field as associated data for additional security
        $associatedData = $tableName . '.' . $fieldName;
        
        return $this->encrypt($value, $associatedData);
    }
    
    /**
     * Decrypt data from database
     */
    public function decryptFromDatabase($tableName, $fieldName, $encryptedValue) {
        if (!$this->shouldEncryptField($tableName, $fieldName)) {
            return $encryptedValue;
        }
        
        if (empty($encryptedValue)) {
            return $encryptedValue;
        }
        
        // Use table.field as associated data
        $associatedData = $tableName . '.' . $fieldName;
        
        return $this->decrypt($encryptedValue, $associatedData);
    }
    
    /**
     * Check if field should be encrypted
     */
    public function shouldEncryptField($tableName, $fieldName) {
        return isset($this->encryptedFields[$tableName]) && 
               in_array($fieldName, $this->encryptedFields[$tableName]);
    }
    
    /**
     * Encrypt multiple fields in a data array
     */
    public function encryptFields($tableName, $data) {
        if (!isset($this->encryptedFields[$tableName])) {
            return $data;
        }
        
        $encryptedData = $data;
        
        foreach ($this->encryptedFields[$tableName] as $fieldName) {
            if (isset($encryptedData[$fieldName])) {
                $encryptedData[$fieldName] = $this->encryptForDatabase($tableName, $fieldName, $encryptedData[$fieldName]);
            }
        }
        
        return $encryptedData;
    }
    
    /**
     * Decrypt multiple fields in a data array
     */
    public function decryptFields($tableName, $data) {
        if (!isset($this->encryptedFields[$tableName])) {
            return $data;
        }
        
        $decryptedData = $data;
        
        foreach ($this->encryptedFields[$tableName] as $fieldName) {
            if (isset($decryptedData[$fieldName])) {
                try {
                    $decryptedData[$fieldName] = $this->decryptFromDatabase($tableName, $fieldName, $decryptedData[$fieldName]);
                } catch (Exception $e) {
                    // Log decryption failure but don't break the entire operation
                    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'field_decryption_failed', SecurityLogger::LEVEL_WARNING,
                        'Failed to decrypt field', ['table' => $tableName, 'field' => $fieldName, 'error' => $e->getMessage()]);
                    
                    // Keep encrypted value if decryption fails
                    $decryptedData[$fieldName] = '[ENCRYPTED]';
                }
            }
        }
        
        return $decryptedData;
    }
    
    /**
     * Generate a new encryption key
     */
    public static function generateKey() {
        return base64_encode(random_bytes(32));
    }
    
    /**
     * Rotate encryption key (for key rotation procedures)
     */
    public function rotateKey($newKey) {
        $oldKey = $this->encryptionKey;
        $this->encryptionKey = base64_decode($newKey);
        
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'key_rotation', SecurityLogger::LEVEL_CRITICAL,
            'Encryption key rotation initiated');
        
        // In a real implementation, you would:
        // 1. Decrypt all data with old key
        // 2. Re-encrypt with new key
        // 3. Update database
        // This is a complex operation that should be done during maintenance windows
        
        return true;
    }
    
    /**
     * Hash sensitive data for searching (one-way)
     */
    public function hashForSearch($data, $salt = '') {
        if (empty($data)) {
            return $data;
        }
        
        // Use HMAC for secure hashing
        return hash_hmac('sha256', $data, $this->encryptionKey . $salt);
    }
    
    /**
     * Create searchable hash for encrypted fields
     */
    public function createSearchHash($tableName, $fieldName, $value) {
        if (!$this->shouldEncryptField($tableName, $fieldName)) {
            return null;
        }
        
        $salt = $tableName . '.' . $fieldName;
        return $this->hashForSearch($value, $salt);
    }
    
    /**
     * Get list of encrypted fields for a table
     */
    public function getEncryptedFields($tableName) {
        return $this->encryptedFields[$tableName] ?? [];
    }
    
    /**
     * Add field to encryption list
     */
    public function addEncryptedField($tableName, $fieldName) {
        if (!isset($this->encryptedFields[$tableName])) {
            $this->encryptedFields[$tableName] = [];
        }
        
        if (!in_array($fieldName, $this->encryptedFields[$tableName])) {
            $this->encryptedFields[$tableName][] = $fieldName;
            
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_field_added', SecurityLogger::LEVEL_INFO,
                'New field added to encryption list', ['table' => $tableName, 'field' => $fieldName]);
        }
    }
    
    /**
     * Verify encryption integrity
     */
    public function verifyIntegrity($encryptedData, $originalData) {
        try {
            $decrypted = $this->decrypt($encryptedData);
            return $decrypted === $originalData;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Convenience functions
function encryptSensitiveData($tableName, $fieldName, $value) {
    $encryption = DataEncryption::getInstance();
    return $encryption->encryptForDatabase($tableName, $fieldName, $value);
}

function decryptSensitiveData($tableName, $fieldName, $encryptedValue) {
    $encryption = DataEncryption::getInstance();
    return $encryption->decryptFromDatabase($tableName, $fieldName, $encryptedValue);
}

function encryptDataFields($tableName, $data) {
    $encryption = DataEncryption::getInstance();
    return $encryption->encryptFields($tableName, $data);
}

function decryptDataFields($tableName, $data) {
    $encryption = DataEncryption::getInstance();
    return $encryption->decryptFields($tableName, $data);
}

/**
 * DATABASE ENCRYPTION MIGRATION SYSTEM
 * Handles adding encryption support to existing tables
 */
class EncryptionMigration {
    private $db;
    private $encryption;

    public function __construct($database) {
        $this->db = $database;
        $this->encryption = DataEncryption::getInstance();
    }

    /**
     * Add encryption support columns to tables
     */
    public function addEncryptionSupport() {
        $tables = [
            'users' => ['email_hash', 'full_name_hash'],
            'user_profiles' => ['phone_hash', 'telegram_hash', 'whatsapp_hash'],
            'kyc_documents' => ['filename_hash'],
            'aureus_investments' => ['email_hash', 'wallet_hash'],
            'commission_transactions' => ['referrer_hash', 'referred_hash']
        ];

        foreach ($tables as $tableName => $hashFields) {
            $this->addHashColumns($tableName, $hashFields);
        }

        // Create encryption metadata table
        $this->createEncryptionMetadataTable();

        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_migration', SecurityLogger::LEVEL_INFO,
            'Database encryption support added');
    }

    /**
     * Add hash columns for searchable encrypted fields
     */
    private function addHashColumns($tableName, $hashFields) {
        foreach ($hashFields as $hashField) {
            try {
                $query = "ALTER TABLE `$tableName` ADD COLUMN IF NOT EXISTS `$hashField` VARCHAR(64) NULL";
                $this->db->exec($query);

                // Add index for hash field
                $indexName = "idx_{$hashField}";
                $indexQuery = "CREATE INDEX IF NOT EXISTS `$indexName` ON `$tableName` (`$hashField`)";
                $this->db->exec($indexQuery);

            } catch (PDOException $e) {
                error_log("Failed to add hash column $hashField to $tableName: " . $e->getMessage());
            }
        }
    }

    /**
     * Create encryption metadata table
     */
    private function createEncryptionMetadataTable() {
        $query = "CREATE TABLE IF NOT EXISTS encryption_metadata (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            table_name VARCHAR(100) NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            encryption_version INT DEFAULT 1,
            key_rotation_date TIMESTAMP NULL,
            migration_status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            UNIQUE KEY unique_table_field (table_name, field_name),
            INDEX idx_table_name (table_name),
            INDEX idx_migration_status (migration_status)
        )";

        $this->db->exec($query);
    }

    /**
     * Migrate existing data to encrypted format
     */
    public function migrateExistingData($tableName, $batchSize = 100) {
        $encryptedFields = $this->encryption->getEncryptedFields($tableName);

        if (empty($encryptedFields)) {
            return ['status' => 'no_fields', 'message' => 'No encrypted fields defined for table'];
        }

        try {
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM `$tableName`";
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute();
            $total = $stmt->fetch()['total'];

            $processed = 0;
            $offset = 0;

            while ($offset < $total) {
                // Get batch of records
                $selectQuery = "SELECT * FROM `$tableName` LIMIT $batchSize OFFSET $offset";
                $stmt = $this->db->prepare($selectQuery);
                $stmt->execute();
                $records = $stmt->fetchAll();

                foreach ($records as $record) {
                    $this->migrateRecord($tableName, $record, $encryptedFields);
                    $processed++;
                }

                $offset += $batchSize;

                // Log progress
                if ($processed % 1000 === 0) {
                    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_migration_progress',
                        SecurityLogger::LEVEL_INFO, "Migration progress: $processed/$total records");
                }
            }

            return [
                'status' => 'completed',
                'message' => "Successfully migrated $processed records",
                'processed' => $processed,
                'total' => $total
            ];

        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_migration_failed',
                SecurityLogger::LEVEL_CRITICAL, 'Data migration failed',
                ['table' => $tableName, 'error' => $e->getMessage()]);

            return [
                'status' => 'failed',
                'message' => 'Migration failed: ' . $e->getMessage(),
                'processed' => $processed ?? 0
            ];
        }
    }

    /**
     * Migrate a single record
     */
    private function migrateRecord($tableName, $record, $encryptedFields) {
        $updates = [];
        $hashUpdates = [];
        $params = [];

        foreach ($encryptedFields as $fieldName) {
            if (isset($record[$fieldName]) && !empty($record[$fieldName])) {
                // Check if already encrypted (basic check)
                if (!$this->isAlreadyEncrypted($record[$fieldName])) {
                    // Encrypt the field
                    $encrypted = $this->encryption->encryptForDatabase($tableName, $fieldName, $record[$fieldName]);
                    $updates[] = "`$fieldName` = ?";
                    $params[] = $encrypted;

                    // Create search hash
                    $hash = $this->encryption->createSearchHash($tableName, $fieldName, $record[$fieldName]);
                    if ($hash) {
                        $hashField = $fieldName . '_hash';
                        $hashUpdates[] = "`$hashField` = ?";
                        $params[] = $hash;
                    }
                }
            }
        }

        if (!empty($updates) || !empty($hashUpdates)) {
            $allUpdates = array_merge($updates, $hashUpdates);
            $updateQuery = "UPDATE `$tableName` SET " . implode(', ', $allUpdates) . " WHERE id = ?";
            $params[] = $record['id'];

            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute($params);
        }
    }

    /**
     * Basic check if data is already encrypted
     */
    private function isAlreadyEncrypted($data) {
        // Check if it looks like base64 encoded encrypted data
        return preg_match('/^[A-Za-z0-9+\/]+=*$/', $data) && strlen($data) > 40;
    }
}
?>
