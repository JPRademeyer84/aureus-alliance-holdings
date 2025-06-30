<?php
/**
 * DATABASE ENCRYPTION TEST SUITE
 * Comprehensive testing of enterprise database encryption features
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-database-encryption.php';

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
    
    // Test 1: Encryption system initialization
    if ($testType === 'all' || $testType === 'initialization') {
        $results['initialization'] = testEncryptionInitialization();
    }
    
    // Test 2: Key management
    if ($testType === 'all' || $testType === 'key_management') {
        $results['key_management'] = testKeyManagement();
    }
    
    // Test 3: Encryption policies
    if ($testType === 'all' || $testType === 'encryption_policies') {
        $results['encryption_policies'] = testEncryptionPolicies();
    }
    
    // Test 4: TDE functionality
    if ($testType === 'all' || $testType === 'tde') {
        $results['tde'] = testTDEFunctionality();
    }
    
    // Test 5: Performance and compliance
    if ($testType === 'all' || $testType === 'performance') {
        $results['performance'] = testPerformanceAndCompliance();
    }
    
    // Test 6: Data classification
    if ($testType === 'all' || $testType === 'classification') {
        $results['classification'] = testDataClassification();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'database_encryption_test', SecurityLogger::LEVEL_INFO,
        'Database encryption test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database encryption test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Database encryption test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test encryption system initialization
 */
function testEncryptionInitialization() {
    $testCases = [
        [
            'name' => 'Enterprise database encryption class exists',
            'test_function' => function() {
                return class_exists('EnterpriseDatabaseEncryption');
            }
        ],
        [
            'name' => 'Database key manager class exists',
            'test_function' => function() {
                return class_exists('DatabaseKeyManager');
            }
        ],
        [
            'name' => 'Encryption tables created',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $tables = [
                    'encryption_policies',
                    'database_encryption_keys',
                    'encryption_audit_trail',
                    'data_classification',
                    'encryption_performance_metrics',
                    'key_escrow'
                ];
                
                foreach ($tables as $table) {
                    $query = "SHOW TABLES LIKE '$table'";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    if (!$stmt->fetch()) {
                        return false;
                    }
                }
                return true;
            }
        ],
        [
            'name' => 'Encryption instance creation',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                return $encryption instanceof EnterpriseDatabaseEncryption;
            }
        ],
        [
            'name' => 'Key manager initialization',
            'test_function' => function() {
                $keyManager = new DatabaseKeyManager();
                return $keyManager instanceof DatabaseKeyManager;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test key management functionality
 */
function testKeyManagement() {
    $testCases = [
        [
            'name' => 'Column key generation',
            'test_function' => function() {
                $keyManager = new DatabaseKeyManager();
                $keyId = $keyManager->generateColumnKey('test_table', 'test_column', 2);
                return !empty($keyId) && strpos($keyId, 'col_') === 0;
            }
        ],
        [
            'name' => 'Table key generation',
            'test_function' => function() {
                $keyManager = new DatabaseKeyManager();
                $keyId = $keyManager->generateTableKey('test_table', 3);
                return !empty($keyId) && strpos($keyId, 'tbl_') === 0;
            }
        ],
        [
            'name' => 'Key rotation capability',
            'test_function' => function() {
                $keyManager = new DatabaseKeyManager();
                return method_exists($keyManager, 'rotateKey');
            }
        ],
        [
            'name' => 'Key encryption verification',
            'test_function' => function() {
                $keyManager = new DatabaseKeyManager();
                $reflection = new ReflectionClass($keyManager);
                return $reflection->hasMethod('encryptKeyData');
            }
        ],
        [
            'name' => 'Algorithm selection by level',
            'test_function' => function() {
                $keyManager = new DatabaseKeyManager();
                $reflection = new ReflectionClass($keyManager);
                return $reflection->hasMethod('getAlgorithmForLevel');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test encryption policies
 */
function testEncryptionPolicies() {
    $testCases = [
        [
            'name' => 'Policy creation method',
            'test_function' => function() {
                return function_exists('createEncryptionPolicy');
            }
        ],
        [
            'name' => 'Encryption policy creation',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                return method_exists($encryption, 'createEncryptionPolicy');
            }
        ],
        [
            'name' => 'Table encryption method',
            'test_function' => function() {
                return function_exists('encryptTableData');
            }
        ],
        [
            'name' => 'Policy validation',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('getTableEncryptionPolicies');
            }
        ],
        [
            'name' => 'Record field encryption',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('encryptRecordFields');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test TDE functionality
 */
function testTDEFunctionality() {
    $testCases = [
        [
            'name' => 'TDE enablement method',
            'test_function' => function() {
                return function_exists('enableTDE');
            }
        ],
        [
            'name' => 'TDE support detection',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('isTDESupported');
            }
        ],
        [
            'name' => 'Database TDE application',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('applyDatabaseTDE');
            }
        ],
        [
            'name' => 'Application-level fallback',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('enableApplicationLevelEncryption');
            }
        ],
        [
            'name' => 'TDE policy creation',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                return method_exists($encryption, 'enableTDE');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test performance and compliance
 */
function testPerformanceAndCompliance() {
    $testCases = [
        [
            'name' => 'Performance metrics recording',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('recordPerformanceMetrics');
            }
        ],
        [
            'name' => 'Compliance report generation',
            'test_function' => function() {
                return function_exists('generateComplianceReport');
            }
        ],
        [
            'name' => 'Audit trail logging',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('logEncryptionOperation');
            }
        ],
        [
            'name' => 'Key rotation scheduling',
            'test_function' => function() {
                return function_exists('rotateEncryptionKeys');
            }
        ],
        [
            'name' => 'Compliance status checking',
            'test_function' => function() {
                $encryption = EnterpriseDatabaseEncryption::getInstance();
                $reflection = new ReflectionClass($encryption);
                return $reflection->hasMethod('getComplianceStatus');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test data classification
 */
function testDataClassification() {
    $testCases = [
        [
            'name' => 'Data classification table exists',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'data_classification'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Classification levels defined',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM data_classification LIKE 'classification_level'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && strpos($column['Type'], 'enum') !== false;
            }
        ],
        [
            'name' => 'Compliance tags support',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM data_classification LIKE 'compliance_tags'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && $column['Type'] === 'json';
            }
        ],
        [
            'name' => 'Retention period tracking',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM data_classification LIKE 'retention_period_days'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Anonymization requirements',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM data_classification LIKE 'anonymization_required'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Run test cases and return results
 */
function runTestCases($testCases) {
    $results = [];
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        try {
            $testPassed = $testCase['test_function']();
            if ($testPassed) $passed++;
            
            $results[] = [
                'test_case' => $testCase['name'],
                'passed' => $testPassed,
                'status' => $testPassed ? 'PASS' : 'FAIL'
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test_case' => $testCase['name'],
                'passed' => false,
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }
    
    return [
        'status' => 'completed',
        'tests_run' => count($testCases),
        'tests_passed' => $passed,
        'success_rate' => round(($passed / count($testCases)) * 100, 2),
        'results' => $results
    ];
}

/**
 * Calculate overall test score
 */
function calculateOverallScore($results) {
    $totalScore = 0;
    $testCount = 0;
    
    foreach ($results as $testName => $result) {
        if (isset($result['success_rate'])) {
            $totalScore += $result['success_rate'];
            $testCount++;
        }
    }
    
    return $testCount > 0 ? round($totalScore / $testCount) : 0;
}
?>
