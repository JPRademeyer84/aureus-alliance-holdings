<?php
/**
 * ENCRYPTION MANAGEMENT API
 * Admin interface for managing data encryption
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/data-encryption.php';
require_once '../config/database.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    switch ($action) {
        case 'status':
            getEncryptionStatus($db);
            break;
            
        case 'setup':
            setupEncryption($db);
            break;
            
        case 'migrate':
            migrateTableData($db);
            break;
            
        case 'verify':
            verifyEncryption($db);
            break;
            
        case 'generate_key':
            generateNewKey();
            break;
            
        case 'test':
            testEncryption();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Encryption management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get encryption system status
 */
function getEncryptionStatus($db) {
    $encryption = DataEncryption::getInstance();
    
    // Check if encryption tables exist
    $tablesExist = [];
    $tables = ['users', 'user_profiles', 'kyc_documents', 'aureus_investments', 'commission_transactions'];
    
    foreach ($tables as $table) {
        try {
            $query = "SHOW TABLES LIKE '$table'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $tablesExist[$table] = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $tablesExist[$table] = false;
        }
    }
    
    // Check encryption metadata table
    $metadataExists = false;
    try {
        $query = "SHOW TABLES LIKE 'encryption_metadata'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $metadataExists = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $metadataExists = false;
    }
    
    // Get migration status if metadata exists
    $migrationStatus = [];
    if ($metadataExists) {
        try {
            $query = "SELECT table_name, field_name, migration_status, updated_at 
                     FROM encryption_metadata ORDER BY table_name, field_name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $migrationStatus = $stmt->fetchAll();
        } catch (PDOException $e) {
            $migrationStatus = [];
        }
    }
    
    // Get encrypted field configuration
    $encryptedFields = [];
    foreach ($tables as $table) {
        $fields = $encryption->getEncryptedFields($table);
        if (!empty($fields)) {
            $encryptedFields[$table] = $fields;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'encryption_enabled' => true,
            'tables_exist' => $tablesExist,
            'metadata_table_exists' => $metadataExists,
            'encrypted_fields' => $encryptedFields,
            'migration_status' => $migrationStatus,
            'total_encrypted_tables' => count($encryptedFields),
            'last_updated' => date('c')
        ]
    ]);
}

/**
 * Setup encryption system
 */
function setupEncryption($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    try {
        $migration = new EncryptionMigration($db);
        $migration->addEncryptionSupport();
        
        // Log setup completion
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_setup', SecurityLogger::LEVEL_INFO,
            'Encryption system setup completed', [], null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Encryption system setup completed successfully'
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_setup_failed', SecurityLogger::LEVEL_CRITICAL,
            'Encryption system setup failed', ['error' => $e->getMessage()], null, $_SESSION['admin_id']);
        
        throw $e;
    }
}

/**
 * Migrate table data to encrypted format
 */
function migrateTableData($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tableName = $input['table_name'] ?? '';
    $batchSize = min((int)($input['batch_size'] ?? 100), 1000);
    
    if (empty($tableName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Table name required']);
        return;
    }
    
    try {
        $migration = new EncryptionMigration($db);
        $result = $migration->migrateExistingData($tableName, $batchSize);
        
        // Log migration
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'data_migration', SecurityLogger::LEVEL_INFO,
            "Data migration for table $tableName", $result, null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'data_migration_failed', SecurityLogger::LEVEL_CRITICAL,
            "Data migration failed for table $tableName", ['error' => $e->getMessage()], null, $_SESSION['admin_id']);
        
        throw $e;
    }
}

/**
 * Verify encryption integrity
 */
function verifyEncryption($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $tableName = $input['table_name'] ?? '';
    $sampleSize = min((int)($input['sample_size'] ?? 10), 100);
    
    if (empty($tableName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Table name required']);
        return;
    }
    
    try {
        $encryption = DataEncryption::getInstance();
        $encryptedFields = $encryption->getEncryptedFields($tableName);
        
        if (empty($encryptedFields)) {
            echo json_encode([
                'success' => true,
                'message' => 'No encrypted fields in this table',
                'verified' => 0,
                'failed' => 0
            ]);
            return;
        }
        
        // Get sample records
        $query = "SELECT * FROM `$tableName` ORDER BY RAND() LIMIT $sampleSize";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        $verified = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($records as $record) {
            foreach ($encryptedFields as $fieldName) {
                if (isset($record[$fieldName]) && !empty($record[$fieldName])) {
                    try {
                        // Try to decrypt the field
                        $decrypted = $encryption->decryptFromDatabase($tableName, $fieldName, $record[$fieldName]);
                        
                        // Try to re-encrypt and verify
                        $reencrypted = $encryption->encryptForDatabase($tableName, $fieldName, $decrypted);
                        
                        if ($encryption->verifyIntegrity($reencrypted, $decrypted)) {
                            $verified++;
                        } else {
                            $failed++;
                            $errors[] = "Integrity check failed for $tableName.$fieldName (ID: {$record['id']})";
                        }
                        
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = "Decryption failed for $tableName.$fieldName (ID: {$record['id']}): " . $e->getMessage();
                    }
                }
            }
        }
        
        // Log verification results
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_verification', SecurityLogger::LEVEL_INFO,
            "Encryption verification completed for $tableName", 
            ['verified' => $verified, 'failed' => $failed, 'sample_size' => $sampleSize], 
            null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'table_name' => $tableName,
                'sample_size' => $sampleSize,
                'verified' => $verified,
                'failed' => $failed,
                'errors' => $errors,
                'integrity_percentage' => $verified + $failed > 0 ? round(($verified / ($verified + $failed)) * 100, 2) : 0
            ]
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_verification_failed', SecurityLogger::LEVEL_CRITICAL,
            "Encryption verification failed for $tableName", ['error' => $e->getMessage()], null, $_SESSION['admin_id']);
        
        throw $e;
    }
}

/**
 * Generate new encryption key
 */
function generateNewKey() {
    try {
        $newKey = DataEncryption::generateKey();
        
        // Log key generation (but not the key itself!)
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_key_generated', SecurityLogger::LEVEL_CRITICAL,
            'New encryption key generated', [], null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'New encryption key generated',
            'key' => $newKey,
            'warning' => 'Store this key securely and update your environment configuration. This key will not be shown again.'
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'key_generation_failed', SecurityLogger::LEVEL_CRITICAL,
            'Encryption key generation failed', ['error' => $e->getMessage()], null, $_SESSION['admin_id']);
        
        throw $e;
    }
}

/**
 * Test encryption system
 */
function testEncryption() {
    try {
        $encryption = DataEncryption::getInstance();
        
        // Test data
        $testData = [
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'name' => 'Test User',
            'sensitive_info' => 'This is sensitive information that should be encrypted'
        ];
        
        $results = [];
        
        foreach ($testData as $field => $value) {
            // Test encryption
            $encrypted = $encryption->encrypt($value);
            $decrypted = $encryption->decrypt($encrypted);
            
            $results[$field] = [
                'original' => $value,
                'encrypted_length' => strlen($encrypted),
                'decrypted' => $decrypted,
                'match' => $value === $decrypted,
                'integrity_verified' => $encryption->verifyIntegrity($encrypted, $value)
            ];
        }
        
        // Test database-specific encryption
        $dbTest = $encryption->encryptForDatabase('users', 'email', 'test@example.com');
        $dbDecrypted = $encryption->decryptFromDatabase('users', 'email', $dbTest);
        
        $results['database_test'] = [
            'original' => 'test@example.com',
            'encrypted_length' => strlen($dbTest),
            'decrypted' => $dbDecrypted,
            'match' => 'test@example.com' === $dbDecrypted
        ];
        
        // Log test completion
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_test', SecurityLogger::LEVEL_INFO,
            'Encryption system test completed', ['tests_passed' => count(array_filter($results, function($r) { return $r['match']; }))], 
            null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Encryption test completed',
            'results' => $results,
            'all_tests_passed' => !in_array(false, array_column($results, 'match'))
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_test_failed', SecurityLogger::LEVEL_CRITICAL,
            'Encryption test failed', ['error' => $e->getMessage()], null, $_SESSION['admin_id']);
        
        throw $e;
    }
}
?>
