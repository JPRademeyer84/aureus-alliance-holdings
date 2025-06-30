<?php
/**
 * SECURE DATABASE WRAPPER
 * Automatically handles encryption/decryption for sensitive fields
 */

require_once 'database.php';
require_once 'data-encryption.php';
require_once 'security-logger.php';

class SecureDatabase {
    private $db;
    private $encryption;
    private static $instance = null;
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->encryption = DataEncryption::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the underlying PDO connection
     */
    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Secure insert with automatic encryption
     */
    public function secureInsert($tableName, $data) {
        try {
            // Encrypt sensitive fields
            $encryptedData = $this->encryption->encryptFields($tableName, $data);
            
            // Add search hashes for encrypted fields
            $encryptedData = $this->addSearchHashes($tableName, $data, $encryptedData);
            
            // Build insert query
            $fields = array_keys($encryptedData);
            $placeholders = array_fill(0, count($fields), '?');
            
            $query = "INSERT INTO `$tableName` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute(array_values($encryptedData));
            
            if ($result) {
                $insertId = $this->db->lastInsertId();
                
                // Log secure insert
                logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'secure_insert', SecurityLogger::LEVEL_INFO,
                    "Secure insert completed", ['table' => $tableName, 'id' => $insertId]);
                
                return $insertId;
            }
            
            return false;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'secure_insert_failed', SecurityLogger::LEVEL_CRITICAL,
                "Secure insert failed", ['table' => $tableName, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Secure select with automatic decryption
     */
    public function secureSelect($tableName, $conditions = [], $fields = '*', $limit = null) {
        try {
            // Build select query
            $query = "SELECT $fields FROM `$tableName`";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    // Check if this is an encrypted field that needs hash search
                    if ($this->encryption->shouldEncryptField($tableName, $field)) {
                        $hashField = $field . '_hash';
                        $hash = $this->encryption->createSearchHash($tableName, $field, $value);
                        $whereClause[] = "`$hashField` = ?";
                        $params[] = $hash;
                    } else {
                        $whereClause[] = "`$field` = ?";
                        $params[] = $value;
                    }
                }
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            if ($limit) {
                $query .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            // Decrypt sensitive fields in results
            $decryptedResults = [];
            foreach ($results as $row) {
                $decryptedResults[] = $this->encryption->decryptFields($tableName, $row);
            }
            
            return $decryptedResults;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'secure_select_failed', SecurityLogger::LEVEL_WARNING,
                "Secure select failed", ['table' => $tableName, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Secure update with automatic encryption
     */
    public function secureUpdate($tableName, $data, $conditions) {
        try {
            // Encrypt sensitive fields
            $encryptedData = $this->encryption->encryptFields($tableName, $data);
            
            // Add search hashes for encrypted fields
            $encryptedData = $this->addSearchHashes($tableName, $data, $encryptedData);
            
            // Build update query
            $setClause = [];
            $params = [];
            
            foreach ($encryptedData as $field => $value) {
                $setClause[] = "`$field` = ?";
                $params[] = $value;
            }
            
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "`$field` = ?";
                $params[] = $value;
            }
            
            $query = "UPDATE `$tableName` SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                $affectedRows = $stmt->rowCount();
                
                // Log secure update
                logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'secure_update', SecurityLogger::LEVEL_INFO,
                    "Secure update completed", ['table' => $tableName, 'affected_rows' => $affectedRows]);
                
                return $affectedRows;
            }
            
            return false;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'secure_update_failed', SecurityLogger::LEVEL_CRITICAL,
                "Secure update failed", ['table' => $tableName, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Secure search by encrypted field
     */
    public function secureSearchByEncryptedField($tableName, $fieldName, $searchValue, $limit = 10) {
        if (!$this->encryption->shouldEncryptField($tableName, $fieldName)) {
            throw new Exception("Field $fieldName is not configured for encryption");
        }
        
        try {
            $hashField = $fieldName . '_hash';
            $hash = $this->encryption->createSearchHash($tableName, $fieldName, $searchValue);
            
            $query = "SELECT * FROM `$tableName` WHERE `$hashField` = ? LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$hash, $limit]);
            $results = $stmt->fetchAll();
            
            // Decrypt results
            $decryptedResults = [];
            foreach ($results as $row) {
                $decryptedResults[] = $this->encryption->decryptFields($tableName, $row);
            }
            
            return $decryptedResults;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'secure_search_failed', SecurityLogger::LEVEL_WARNING,
                "Secure search failed", ['table' => $tableName, 'field' => $fieldName, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Add search hashes for encrypted fields
     */
    private function addSearchHashes($tableName, $originalData, $encryptedData) {
        $encryptedFields = $this->encryption->getEncryptedFields($tableName);
        
        foreach ($encryptedFields as $fieldName) {
            if (isset($originalData[$fieldName]) && !empty($originalData[$fieldName])) {
                $hashField = $fieldName . '_hash';
                $hash = $this->encryption->createSearchHash($tableName, $fieldName, $originalData[$fieldName]);
                if ($hash) {
                    $encryptedData[$hashField] = $hash;
                }
            }
        }
        
        return $encryptedData;
    }
    
    /**
     * Execute raw query with logging
     */
    public function secureQuery($query, $params = []) {
        try {
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute($params);
            
            // Log query execution (without sensitive data)
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'query_executed', SecurityLogger::LEVEL_INFO,
                "Database query executed", ['query_hash' => hash('sha256', $query)]);
            
            return $stmt;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'query_failed', SecurityLogger::LEVEL_WARNING,
                "Database query failed", ['error' => $e->getMessage(), 'query_hash' => hash('sha256', $query)]);
            throw $e;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $result = $this->db->commit();
        
        if ($result) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'transaction_committed', SecurityLogger::LEVEL_INFO,
                "Database transaction committed");
        }
        
        return $result;
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $result = $this->db->rollback();
        
        logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'transaction_rollback', SecurityLogger::LEVEL_WARNING,
            "Database transaction rolled back");
        
        return $result;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
    
    /**
     * Prepare statement
     */
    public function prepare($query) {
        return $this->db->prepare($query);
    }
    
    /**
     * Execute statement
     */
    public function exec($query) {
        try {
            $result = $this->db->exec($query);
            
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'exec_query', SecurityLogger::LEVEL_INFO,
                "Database exec query", ['query_hash' => hash('sha256', $query)]);
            
            return $result;
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_DATABASE, 'exec_failed', SecurityLogger::LEVEL_WARNING,
                "Database exec failed", ['error' => $e->getMessage(), 'query_hash' => hash('sha256', $query)]);
            throw $e;
        }
    }
}

// Convenience functions
function getSecureDatabase() {
    return SecureDatabase::getInstance();
}

function secureInsert($tableName, $data) {
    $db = SecureDatabase::getInstance();
    return $db->secureInsert($tableName, $data);
}

function secureSelect($tableName, $conditions = [], $fields = '*', $limit = null) {
    $db = SecureDatabase::getInstance();
    return $db->secureSelect($tableName, $conditions, $fields, $limit);
}

function secureUpdate($tableName, $data, $conditions) {
    $db = SecureDatabase::getInstance();
    return $db->secureUpdate($tableName, $data, $conditions);
}

function secureSearchByEncryptedField($tableName, $fieldName, $searchValue, $limit = 10) {
    $db = SecureDatabase::getInstance();
    return $db->secureSearchByEncryptedField($tableName, $fieldName, $searchValue, $limit);
}
?>
