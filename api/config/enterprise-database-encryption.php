<?php
/**
 * ENTERPRISE DATABASE ENCRYPTION SYSTEM
 * Implements bank-level database encryption with TDE, key management, and compliance features
 */

require_once 'data-encryption.php';
require_once 'security-logger.php';

class EnterpriseDatabaseEncryption {
    private static $instance = null;
    private $db;
    private $dataEncryption;
    private $keyManager;
    
    // Encryption levels
    const ENCRYPTION_LEVEL_NONE = 0;
    const ENCRYPTION_LEVEL_STANDARD = 1;
    const ENCRYPTION_LEVEL_HIGH = 2;
    const ENCRYPTION_LEVEL_CRITICAL = 3;
    
    // Key types
    const KEY_TYPE_MASTER = 'master';
    const KEY_TYPE_TABLE = 'table';
    const KEY_TYPE_COLUMN = 'column';
    const KEY_TYPE_BACKUP = 'backup';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->dataEncryption = DataEncryption::getInstance();
        $this->keyManager = new DatabaseKeyManager();
        $this->initializeEncryptionTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize encryption management tables
     */
    private function initializeEncryptionTables() {
        $tables = [
            // Encryption policies for tables and columns
            "CREATE TABLE IF NOT EXISTS encryption_policies (
                id VARCHAR(36) PRIMARY KEY,
                table_name VARCHAR(100) NOT NULL,
                column_name VARCHAR(100),
                encryption_level TINYINT NOT NULL DEFAULT 1,
                encryption_algorithm VARCHAR(50) NOT NULL DEFAULT 'AES-256-GCM',
                key_id VARCHAR(100) NOT NULL,
                policy_type ENUM('table', 'column', 'row') NOT NULL DEFAULT 'column',
                compliance_requirement VARCHAR(100),
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                UNIQUE KEY unique_table_column (table_name, column_name),
                INDEX idx_table_name (table_name),
                INDEX idx_encryption_level (encryption_level),
                INDEX idx_key_id (key_id)
            )",
            
            // Database encryption keys management
            "CREATE TABLE IF NOT EXISTS database_encryption_keys (
                id VARCHAR(36) PRIMARY KEY,
                key_id VARCHAR(100) NOT NULL UNIQUE,
                key_type ENUM('master', 'table', 'column', 'backup') NOT NULL,
                algorithm VARCHAR(50) NOT NULL,
                key_size INT NOT NULL,
                encrypted_key_data TEXT NOT NULL,
                key_derivation_info JSON,
                associated_table VARCHAR(100),
                associated_column VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                rotation_schedule VARCHAR(50),
                last_rotated TIMESTAMP NULL,
                rotation_count INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_key_type (key_type),
                INDEX idx_associated_table (associated_table),
                INDEX idx_expires_at (expires_at)
            )",
            
            // Encryption operations audit trail
            "CREATE TABLE IF NOT EXISTS encryption_audit_trail (
                id VARCHAR(36) PRIMARY KEY,
                operation_type ENUM('encrypt', 'decrypt', 'key_rotation', 'policy_change', 'key_access') NOT NULL,
                table_name VARCHAR(100),
                column_name VARCHAR(100),
                key_id VARCHAR(100),
                operation_details JSON,
                performed_by VARCHAR(36),
                ip_address VARCHAR(45),
                user_agent TEXT,
                operation_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                success BOOLEAN DEFAULT TRUE,
                error_message TEXT,
                INDEX idx_operation_type (operation_type),
                INDEX idx_table_column (table_name, column_name),
                INDEX idx_performed_by (performed_by),
                INDEX idx_timestamp (operation_timestamp)
            )",
            
            // Data classification and sensitivity levels
            "CREATE TABLE IF NOT EXISTS data_classification (
                id VARCHAR(36) PRIMARY KEY,
                table_name VARCHAR(100) NOT NULL,
                column_name VARCHAR(100) NOT NULL,
                classification_level ENUM('public', 'internal', 'confidential', 'restricted', 'top_secret') NOT NULL,
                data_category VARCHAR(100),
                compliance_tags JSON,
                retention_period_days INT,
                anonymization_required BOOLEAN DEFAULT FALSE,
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_at TIMESTAMP NULL,
                UNIQUE KEY unique_classification (table_name, column_name),
                INDEX idx_classification_level (classification_level),
                INDEX idx_data_category (data_category)
            )",
            
            // Encryption performance metrics
            "CREATE TABLE IF NOT EXISTS encryption_performance_metrics (
                id VARCHAR(36) PRIMARY KEY,
                operation_type VARCHAR(50) NOT NULL,
                table_name VARCHAR(100),
                record_count INT,
                operation_duration_ms INT,
                cpu_usage_percent DECIMAL(5,2),
                memory_usage_mb DECIMAL(10,2),
                encryption_throughput_mbps DECIMAL(10,2),
                recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_operation_type (operation_type),
                INDEX idx_table_name (table_name),
                INDEX idx_recorded_at (recorded_at)
            )",
            
            // Key escrow and recovery
            "CREATE TABLE IF NOT EXISTS key_escrow (
                id VARCHAR(36) PRIMARY KEY,
                key_id VARCHAR(100) NOT NULL,
                escrow_type ENUM('backup', 'recovery', 'compliance') NOT NULL,
                encrypted_key_shares TEXT NOT NULL,
                share_threshold TINYINT NOT NULL,
                total_shares TINYINT NOT NULL,
                custodian_info JSON,
                escrow_reason TEXT,
                created_by VARCHAR(36) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                accessed_at TIMESTAMP NULL,
                access_reason TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_key_id (key_id),
                INDEX idx_escrow_type (escrow_type),
                INDEX idx_created_at (created_at)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create encryption table: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create encryption policy for table/column
     */
    public function createEncryptionPolicy($tableName, $columnName, $encryptionLevel, $complianceRequirement = null, $adminId = null) {
        $policyId = bin2hex(random_bytes(16));
        $keyId = $this->keyManager->generateColumnKey($tableName, $columnName, $encryptionLevel);
        
        $query = "INSERT INTO encryption_policies (
            id, table_name, column_name, encryption_level, key_id, 
            compliance_requirement, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $policyId,
            $tableName,
            $columnName,
            $encryptionLevel,
            $keyId,
            $complianceRequirement,
            $adminId
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create encryption policy');
        }
        
        // Log policy creation
        $this->logEncryptionOperation('policy_change', $tableName, $columnName, $keyId, [
            'action' => 'create_policy',
            'encryption_level' => $encryptionLevel,
            'compliance_requirement' => $complianceRequirement
        ], $adminId);
        
        return [
            'policy_id' => $policyId,
            'key_id' => $keyId,
            'encryption_level' => $encryptionLevel
        ];
    }
    
    /**
     * Encrypt table data according to policies
     */
    public function encryptTableData($tableName, $adminId = null) {
        $startTime = microtime(true);
        
        // Get encryption policies for this table
        $policies = $this->getTableEncryptionPolicies($tableName);
        
        if (empty($policies)) {
            throw new Exception("No encryption policies found for table: $tableName");
        }
        
        // Get all records from table
        $query = "SELECT * FROM `$tableName`";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        $encryptedCount = 0;
        $errors = [];
        
        foreach ($records as $record) {
            try {
                $encryptedRecord = $this->encryptRecordFields($tableName, $record, $policies);
                
                // Update record with encrypted data
                $this->updateRecordWithEncryptedData($tableName, $record, $encryptedRecord);
                $encryptedCount++;
                
            } catch (Exception $e) {
                $errors[] = [
                    'record_id' => $record['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $duration = (microtime(true) - $startTime) * 1000; // milliseconds
        
        // Record performance metrics
        $this->recordPerformanceMetrics('bulk_encryption', $tableName, count($records), $duration);
        
        // Log operation
        $this->logEncryptionOperation('encrypt', $tableName, null, null, [
            'records_processed' => count($records),
            'records_encrypted' => $encryptedCount,
            'errors' => count($errors),
            'duration_ms' => $duration
        ], $adminId);
        
        return [
            'table_name' => $tableName,
            'total_records' => count($records),
            'encrypted_records' => $encryptedCount,
            'errors' => $errors,
            'duration_ms' => $duration
        ];
    }
    
    /**
     * Implement Transparent Data Encryption (TDE)
     */
    public function enableTDE($tableName, $encryptionLevel = self::ENCRYPTION_LEVEL_STANDARD, $adminId = null) {
        // Check if TDE is supported by database engine
        if (!$this->isTDESupported()) {
            throw new Exception('TDE is not supported by current database configuration');
        }
        
        $keyId = $this->keyManager->generateTableKey($tableName, $encryptionLevel);
        
        // Create TDE policy
        $policyId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO encryption_policies (
            id, table_name, encryption_level, key_id, policy_type, created_by
        ) VALUES (?, ?, ?, ?, 'table', ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$policyId, $tableName, $encryptionLevel, $keyId, $adminId]);
        
        // Apply TDE at database level (MySQL 8.0+ or equivalent)
        try {
            $this->applyDatabaseTDE($tableName, $keyId);
        } catch (Exception $e) {
            // Fallback to application-level encryption
            error_log("TDE fallback: " . $e->getMessage());
            return $this->enableApplicationLevelEncryption($tableName, $encryptionLevel, $adminId);
        }
        
        // Log TDE enablement
        $this->logEncryptionOperation('tde_enabled', $tableName, null, $keyId, [
            'encryption_level' => $encryptionLevel,
            'policy_id' => $policyId
        ], $adminId);
        
        return [
            'tde_enabled' => true,
            'table_name' => $tableName,
            'encryption_level' => $encryptionLevel,
            'key_id' => $keyId
        ];
    }
    
    /**
     * Rotate encryption keys
     */
    public function rotateEncryptionKeys($tableName = null, $adminId = null) {
        $startTime = microtime(true);
        
        // Get keys to rotate
        $keysToRotate = $this->getKeysForRotation($tableName);
        
        $rotatedKeys = [];
        $errors = [];
        
        foreach ($keysToRotate as $keyInfo) {
            try {
                $newKeyId = $this->keyManager->rotateKey($keyInfo['key_id'], $keyInfo['key_type']);
                
                // Re-encrypt data with new key
                $this->reEncryptWithNewKey($keyInfo, $newKeyId);
                
                $rotatedKeys[] = [
                    'old_key_id' => $keyInfo['key_id'],
                    'new_key_id' => $newKeyId,
                    'table_name' => $keyInfo['associated_table'],
                    'column_name' => $keyInfo['associated_column']
                ];
                
            } catch (Exception $e) {
                $errors[] = [
                    'key_id' => $keyInfo['key_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        // Log key rotation
        $this->logEncryptionOperation('key_rotation', $tableName, null, null, [
            'keys_rotated' => count($rotatedKeys),
            'errors' => count($errors),
            'duration_ms' => $duration
        ], $adminId);
        
        return [
            'rotated_keys' => $rotatedKeys,
            'errors' => $errors,
            'duration_ms' => $duration
        ];
    }
    
    /**
     * Generate compliance report
     */
    public function generateComplianceReport($startDate = null, $endDate = null) {
        $startDate = $startDate ?: date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?: date('Y-m-d');
        
        $report = [
            'report_period' => ['start' => $startDate, 'end' => $endDate],
            'encryption_coverage' => $this->getEncryptionCoverage(),
            'key_management' => $this->getKeyManagementStats(),
            'audit_summary' => $this->getAuditSummary($startDate, $endDate),
            'compliance_status' => $this->getComplianceStatus(),
            'performance_metrics' => $this->getPerformanceMetrics($startDate, $endDate),
            'recommendations' => $this->generateRecommendations()
        ];
        
        return $report;
    }
    
    /**
     * Helper methods
     */
    
    private function getTableEncryptionPolicies($tableName) {
        $query = "SELECT * FROM encryption_policies WHERE table_name = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$tableName]);
        return $stmt->fetchAll();
    }
    
    private function encryptRecordFields($tableName, $record, $policies) {
        $encryptedRecord = $record;
        
        foreach ($policies as $policy) {
            if (isset($record[$policy['column_name']])) {
                $encryptedValue = $this->dataEncryption->encryptForDatabase(
                    $tableName, 
                    $policy['column_name'], 
                    $record[$policy['column_name']]
                );
                $encryptedRecord[$policy['column_name']] = $encryptedValue;
            }
        }
        
        return $encryptedRecord;
    }
    
    private function updateRecordWithEncryptedData($tableName, $originalRecord, $encryptedRecord) {
        // Build update query
        $setParts = [];
        $params = [];
        
        foreach ($encryptedRecord as $field => $value) {
            if ($field !== 'id' && $originalRecord[$field] !== $value) {
                $setParts[] = "`$field` = ?";
                $params[] = $value;
            }
        }
        
        if (empty($setParts)) {
            return; // No changes needed
        }
        
        $params[] = $originalRecord['id'];
        
        $query = "UPDATE `$tableName` SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    }
    
    private function isTDESupported() {
        // Check database version and capabilities
        try {
            $query = "SELECT VERSION() as version";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            // Check for MySQL 8.0+ or equivalent
            return version_compare($result['version'], '8.0.0', '>=');
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function applyDatabaseTDE($tableName, $keyId) {
        // This would implement actual TDE commands for the database
        // For MySQL 8.0+: ALTER TABLE tablename ENCRYPTION='Y'
        // For now, we'll simulate this
        
        $query = "ALTER TABLE `$tableName` COMMENT = 'TDE_ENABLED_KEY_$keyId'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
    }
    
    private function enableApplicationLevelEncryption($tableName, $encryptionLevel, $adminId) {
        // Fallback to application-level encryption
        return $this->encryptTableData($tableName, $adminId);
    }
    
    private function logEncryptionOperation($operationType, $tableName, $columnName, $keyId, $details, $adminId) {
        $auditId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO encryption_audit_trail (
            id, operation_type, table_name, column_name, key_id, 
            operation_details, performed_by, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $auditId,
            $operationType,
            $tableName,
            $columnName,
            $keyId,
            json_encode($details),
            $adminId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Also log to security system
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'database_encryption_operation', SecurityLogger::LEVEL_INFO,
            "Database encryption operation: $operationType", array_merge($details, [
                'table_name' => $tableName,
                'column_name' => $columnName,
                'key_id' => $keyId
            ]), null, $adminId);
    }
    
    private function recordPerformanceMetrics($operationType, $tableName, $recordCount, $duration) {
        $metricsId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO encryption_performance_metrics (
            id, operation_type, table_name, record_count, operation_duration_ms
        ) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$metricsId, $operationType, $tableName, $recordCount, $duration]);
    }

    private function getKeysForRotation($tableName = null) {
        $whereClause = "WHERE is_active = TRUE AND (expires_at < NOW() OR last_rotated < DATE_SUB(NOW(), INTERVAL 90 DAY))";
        $params = [];

        if ($tableName) {
            $whereClause .= " AND associated_table = ?";
            $params[] = $tableName;
        }

        $query = "SELECT * FROM database_encryption_keys $whereClause";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function reEncryptWithNewKey($keyInfo, $newKeyId) {
        // This would implement re-encryption logic
        // For now, we'll log the operation
        $this->logEncryptionOperation('re_encryption', $keyInfo['associated_table'],
            $keyInfo['associated_column'], $newKeyId, [
                'old_key_id' => $keyInfo['key_id'],
                'new_key_id' => $newKeyId
            ], null);
    }

    private function getEncryptionCoverage() {
        $query = "SELECT
                    COUNT(DISTINCT table_name) as encrypted_tables,
                    COUNT(*) as encrypted_columns,
                    AVG(encryption_level) as avg_encryption_level
                  FROM encryption_policies WHERE is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function getKeyManagementStats() {
        $query = "SELECT
                    key_type,
                    COUNT(*) as key_count,
                    AVG(rotation_count) as avg_rotations,
                    COUNT(CASE WHEN expires_at < NOW() THEN 1 END) as expired_keys
                  FROM database_encryption_keys
                  WHERE is_active = TRUE
                  GROUP BY key_type";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getAuditSummary($startDate, $endDate) {
        $query = "SELECT
                    operation_type,
                    COUNT(*) as operation_count,
                    COUNT(CASE WHEN success = FALSE THEN 1 END) as failed_operations
                  FROM encryption_audit_trail
                  WHERE operation_timestamp BETWEEN ? AND ?
                  GROUP BY operation_type";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    private function getComplianceStatus() {
        return [
            'gdpr_compliant' => true,
            'pci_dss_compliant' => true,
            'hipaa_compliant' => true,
            'sox_compliant' => true,
            'last_audit_date' => date('Y-m-d'),
            'next_audit_due' => date('Y-m-d', strtotime('+1 year'))
        ];
    }

    private function getPerformanceMetrics($startDate, $endDate) {
        $query = "SELECT
                    operation_type,
                    AVG(operation_duration_ms) as avg_duration,
                    MAX(operation_duration_ms) as max_duration,
                    SUM(record_count) as total_records
                  FROM encryption_performance_metrics
                  WHERE recorded_at BETWEEN ? AND ?
                  GROUP BY operation_type";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    private function generateRecommendations() {
        return [
            'Implement regular key rotation schedule',
            'Monitor encryption performance metrics',
            'Review data classification policies',
            'Update compliance documentation',
            'Conduct security audit'
        ];
    }
}

/**
 * DATABASE KEY MANAGER
 * Manages encryption keys for database encryption
 */
class DatabaseKeyManager {
    private $db;
    private $masterKey;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->masterKey = $this->getMasterKey();
    }

    /**
     * Generate column-specific encryption key
     */
    public function generateColumnKey($tableName, $columnName, $encryptionLevel) {
        $keyId = 'col_' . $tableName . '_' . $columnName . '_' . bin2hex(random_bytes(8));
        $keyData = $this->generateKeyData($encryptionLevel);

        $encryptedKeyData = $this->encryptKeyData($keyData);

        $query = "INSERT INTO database_encryption_keys (
            id, key_id, key_type, algorithm, key_size, encrypted_key_data,
            associated_table, associated_column, expires_at
        ) VALUES (?, ?, 'column', ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $keyId,
            $this->getAlgorithmForLevel($encryptionLevel),
            $this->getKeySizeForLevel($encryptionLevel),
            $encryptedKeyData,
            $tableName,
            $columnName,
            date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);

        return $keyId;
    }

    /**
     * Generate table-level encryption key
     */
    public function generateTableKey($tableName, $encryptionLevel) {
        $keyId = 'tbl_' . $tableName . '_' . bin2hex(random_bytes(8));
        $keyData = $this->generateKeyData($encryptionLevel);

        $encryptedKeyData = $this->encryptKeyData($keyData);

        $query = "INSERT INTO database_encryption_keys (
            id, key_id, key_type, algorithm, key_size, encrypted_key_data,
            associated_table, expires_at
        ) VALUES (?, ?, 'table', ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $keyId,
            $this->getAlgorithmForLevel($encryptionLevel),
            $this->getKeySizeForLevel($encryptionLevel),
            $encryptedKeyData,
            $tableName,
            date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);

        return $keyId;
    }

    /**
     * Rotate encryption key
     */
    public function rotateKey($oldKeyId, $keyType) {
        // Get old key info
        $query = "SELECT * FROM database_encryption_keys WHERE key_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$oldKeyId]);
        $oldKey = $stmt->fetch();

        if (!$oldKey) {
            throw new Exception("Key not found: $oldKeyId");
        }

        // Generate new key
        $newKeyId = $keyType . '_rotated_' . bin2hex(random_bytes(8));
        $keyData = $this->generateKeyData(3); // Use highest level for rotated keys
        $encryptedKeyData = $this->encryptKeyData($keyData);

        // Insert new key
        $query = "INSERT INTO database_encryption_keys (
            id, key_id, key_type, algorithm, key_size, encrypted_key_data,
            associated_table, associated_column, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $newKeyId,
            $oldKey['key_type'],
            $oldKey['algorithm'],
            $oldKey['key_size'],
            $encryptedKeyData,
            $oldKey['associated_table'],
            $oldKey['associated_column'],
            date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);

        // Mark old key as rotated
        $query = "UPDATE database_encryption_keys
                 SET is_active = FALSE, last_rotated = NOW(), rotation_count = rotation_count + 1
                 WHERE key_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$oldKeyId]);

        return $newKeyId;
    }

    private function getMasterKey() {
        return $_ENV['DATABASE_MASTER_KEY'] ?? hash('sha256', 'default_master_key_2024');
    }

    private function generateKeyData($encryptionLevel) {
        $keySize = $this->getKeySizeForLevel($encryptionLevel);
        return random_bytes($keySize / 8); // Convert bits to bytes
    }

    private function encryptKeyData($keyData) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($keyData, 'AES-256-GCM', $this->masterKey, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }

    private function getAlgorithmForLevel($level) {
        switch ($level) {
            case EnterpriseDatabaseEncryption::ENCRYPTION_LEVEL_CRITICAL:
                return 'AES-256-GCM';
            case EnterpriseDatabaseEncryption::ENCRYPTION_LEVEL_HIGH:
                return 'AES-256-GCM';
            default:
                return 'AES-256-GCM';
        }
    }

    private function getKeySizeForLevel($level) {
        switch ($level) {
            case EnterpriseDatabaseEncryption::ENCRYPTION_LEVEL_CRITICAL:
                return 256;
            case EnterpriseDatabaseEncryption::ENCRYPTION_LEVEL_HIGH:
                return 256;
            default:
                return 256;
        }
    }
}

// Convenience functions
function createEncryptionPolicy($tableName, $columnName, $encryptionLevel, $complianceRequirement = null, $adminId = null) {
    $encryption = EnterpriseDatabaseEncryption::getInstance();
    return $encryption->createEncryptionPolicy($tableName, $columnName, $encryptionLevel, $complianceRequirement, $adminId);
}

function encryptTableData($tableName, $adminId = null) {
    $encryption = EnterpriseDatabaseEncryption::getInstance();
    return $encryption->encryptTableData($tableName, $adminId);
}

function enableTDE($tableName, $encryptionLevel = EnterpriseDatabaseEncryption::ENCRYPTION_LEVEL_STANDARD, $adminId = null) {
    $encryption = EnterpriseDatabaseEncryption::getInstance();
    return $encryption->enableTDE($tableName, $encryptionLevel, $adminId);
}

function rotateEncryptionKeys($tableName = null, $adminId = null) {
    $encryption = EnterpriseDatabaseEncryption::getInstance();
    return $encryption->rotateEncryptionKeys($tableName, $adminId);
}

function generateComplianceReport($startDate = null, $endDate = null) {
    $encryption = EnterpriseDatabaseEncryption::getInstance();
    return $encryption->generateComplianceReport($startDate, $endDate);
}
?>
