<?php
/**
 * DATA ENCRYPTION TEST ENDPOINT
 * Tests the data encryption system functionality
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/data-encryption.php';
require_once '../config/secure-database.php';

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

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $testType = $input['test_type'] ?? 'all';
    
    $results = [];
    
    // Test 1: Basic encryption/decryption
    if ($testType === 'all' || $testType === 'basic') {
        $results['basic_encryption'] = testBasicEncryption();
    }
    
    // Test 2: Database field encryption
    if ($testType === 'all' || $testType === 'database') {
        $results['database_encryption'] = testDatabaseEncryption();
    }
    
    // Test 3: Secure database operations
    if ($testType === 'all' || $testType === 'operations') {
        $results['database_operations'] = testSecureDatabaseOperations();
    }
    
    // Test 4: Search functionality
    if ($testType === 'all' || $testType === 'search') {
        $results['search_functionality'] = testSearchFunctionality();
    }
    
    // Test 5: Performance test
    if ($testType === 'all' || $testType === 'performance') {
        $results['performance'] = testEncryptionPerformance();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'encryption_test_suite', SecurityLogger::LEVEL_INFO,
        'Data encryption test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Encryption test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Encryption test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test basic encryption/decryption functionality
 */
function testBasicEncryption() {
    $encryption = DataEncryption::getInstance();
    
    $testData = [
        'email' => 'test.user@example.com',
        'phone' => '+1-555-123-4567',
        'name' => 'John Doe',
        'ssn' => '123-45-6789',
        'wallet' => '0x1234567890abcdef1234567890abcdef12345678'
    ];
    
    $results = [];
    
    foreach ($testData as $field => $value) {
        $startTime = microtime(true);
        
        // Test encryption
        $encrypted = $encryption->encrypt($value);
        $encryptTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        
        // Test decryption
        $decrypted = $encryption->decrypt($encrypted);
        $decryptTime = microtime(true) - $startTime;
        
        $results[$field] = [
            'original' => $value,
            'encrypted_length' => strlen($encrypted),
            'decrypted' => $decrypted,
            'match' => $value === $decrypted,
            'encrypt_time_ms' => round($encryptTime * 1000, 3),
            'decrypt_time_ms' => round($decryptTime * 1000, 3),
            'integrity_verified' => $encryption->verifyIntegrity($encrypted, $value)
        ];
    }
    
    return [
        'status' => 'completed',
        'tests' => $results,
        'all_passed' => !in_array(false, array_column($results, 'match'))
    ];
}

/**
 * Test database field encryption
 */
function testDatabaseEncryption() {
    $encryption = DataEncryption::getInstance();
    
    $testTables = [
        'users' => [
            'email' => 'test@example.com',
            'full_name' => 'Test User'
        ],
        'user_profiles' => [
            'phone' => '+1234567890',
            'telegram_username' => '@testuser'
        ],
        'kyc_documents' => [
            'original_name' => 'passport.pdf',
            'file_path' => '/secure/uploads/user123/passport.pdf'
        ]
    ];
    
    $results = [];
    
    foreach ($testTables as $tableName => $fields) {
        $tableResults = [];
        
        foreach ($fields as $fieldName => $value) {
            // Test database-specific encryption
            $encrypted = $encryption->encryptForDatabase($tableName, $fieldName, $value);
            $decrypted = $encryption->decryptFromDatabase($tableName, $fieldName, $encrypted);
            
            // Test search hash
            $hash = $encryption->createSearchHash($tableName, $fieldName, $value);
            
            $tableResults[$fieldName] = [
                'original' => $value,
                'encrypted' => substr($encrypted, 0, 50) . '...', // Truncate for display
                'decrypted' => $decrypted,
                'match' => $value === $decrypted,
                'search_hash' => substr($hash, 0, 16) . '...',
                'should_encrypt' => $encryption->shouldEncryptField($tableName, $fieldName)
            ];
        }
        
        $results[$tableName] = $tableResults;
    }
    
    return [
        'status' => 'completed',
        'tables_tested' => count($testTables),
        'results' => $results
    ];
}

/**
 * Test secure database operations
 */
function testSecureDatabaseOperations() {
    try {
        $secureDb = SecureDatabase::getInstance();
        
        // Create a test table for this test
        $secureDb->exec("CREATE TEMPORARY TABLE test_encryption (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            email VARCHAR(255),
            email_hash VARCHAR(64),
            full_name VARCHAR(255),
            full_name_hash VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add test table to encryption configuration temporarily
        $encryption = DataEncryption::getInstance();
        $encryption->addEncryptedField('test_encryption', 'email');
        $encryption->addEncryptedField('test_encryption', 'full_name');
        
        $testData = [
            'email' => 'secure.test@example.com',
            'full_name' => 'Secure Test User'
        ];
        
        // Test secure insert
        $insertId = $secureDb->secureInsert('test_encryption', $testData);
        
        // Test secure select
        $selectResults = $secureDb->secureSelect('test_encryption', ['email' => 'secure.test@example.com']);
        
        // Test secure search by encrypted field
        $searchResults = $secureDb->secureSearchByEncryptedField('test_encryption', 'email', 'secure.test@example.com');
        
        // Test secure update
        $updateData = ['full_name' => 'Updated Secure User'];
        $updateResult = $secureDb->secureUpdate('test_encryption', $updateData, ['id' => $insertId]);
        
        return [
            'status' => 'completed',
            'insert_success' => !empty($insertId),
            'select_count' => count($selectResults),
            'search_count' => count($searchResults),
            'update_affected' => $updateResult,
            'data_integrity' => !empty($selectResults) && $selectResults[0]['email'] === 'secure.test@example.com'
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Test search functionality
 */
function testSearchFunctionality() {
    $encryption = DataEncryption::getInstance();
    
    $testData = [
        'email1' => 'user1@example.com',
        'email2' => 'user2@example.com',
        'phone1' => '+1234567890',
        'phone2' => '+0987654321'
    ];
    
    $results = [];
    
    // Test hash consistency
    foreach ($testData as $key => $value) {
        $hash1 = $encryption->createSearchHash('users', 'email', $value);
        $hash2 = $encryption->createSearchHash('users', 'email', $value);
        
        $results[$key] = [
            'value' => $value,
            'hash1' => substr($hash1, 0, 16) . '...',
            'hash2' => substr($hash2, 0, 16) . '...',
            'hashes_match' => $hash1 === $hash2,
            'hash_length' => strlen($hash1)
        ];
    }
    
    // Test different values produce different hashes
    $hash_email1 = $encryption->createSearchHash('users', 'email', $testData['email1']);
    $hash_email2 = $encryption->createSearchHash('users', 'email', $testData['email2']);
    
    return [
        'status' => 'completed',
        'hash_tests' => $results,
        'different_values_different_hashes' => $hash_email1 !== $hash_email2,
        'all_hashes_consistent' => !in_array(false, array_column($results, 'hashes_match'))
    ];
}

/**
 * Test encryption performance
 */
function testEncryptionPerformance() {
    $encryption = DataEncryption::getInstance();
    
    $testSizes = [
        'small' => str_repeat('A', 100),      // 100 bytes
        'medium' => str_repeat('B', 1000),    // 1KB
        'large' => str_repeat('C', 10000),    // 10KB
    ];
    
    $iterations = 100;
    $results = [];
    
    foreach ($testSizes as $size => $data) {
        $encryptTimes = [];
        $decryptTimes = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            // Measure encryption time
            $startTime = microtime(true);
            $encrypted = $encryption->encrypt($data);
            $encryptTimes[] = microtime(true) - $startTime;
            
            // Measure decryption time
            $startTime = microtime(true);
            $decrypted = $encryption->decrypt($encrypted);
            $decryptTimes[] = microtime(true) - $startTime;
        }
        
        $results[$size] = [
            'data_size_bytes' => strlen($data),
            'iterations' => $iterations,
            'avg_encrypt_time_ms' => round(array_sum($encryptTimes) / count($encryptTimes) * 1000, 3),
            'avg_decrypt_time_ms' => round(array_sum($decryptTimes) / count($decryptTimes) * 1000, 3),
            'max_encrypt_time_ms' => round(max($encryptTimes) * 1000, 3),
            'max_decrypt_time_ms' => round(max($decryptTimes) * 1000, 3),
            'throughput_mb_per_sec' => round((strlen($data) * $iterations) / (array_sum($encryptTimes) + array_sum($decryptTimes)) / 1024 / 1024, 2)
        ];
    }
    
    return [
        'status' => 'completed',
        'performance_results' => $results,
        'total_operations' => $iterations * count($testSizes) * 2 // encrypt + decrypt
    ];
}
?>
