<?php
/**
 * INPUT VALIDATION TEST ENDPOINT
 * Tests the centralized input validation system
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/input-validator.php';

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
    
    // Test 1: Basic validation
    if ($testType === 'all' || $testType === 'basic') {
        $results['basic_validation'] = testBasicValidation();
    }
    
    // Test 2: Security pattern detection
    if ($testType === 'all' || $testType === 'security') {
        $results['security_patterns'] = testSecurityPatterns();
    }
    
    // Test 3: Data type validation
    if ($testType === 'all' || $testType === 'types') {
        $results['data_types'] = testDataTypes();
    }
    
    // Test 4: Sanitization
    if ($testType === 'all' || $testType === 'sanitization') {
        $results['sanitization'] = testSanitization();
    }
    
    // Test 5: Rule sets
    if ($testType === 'all' || $testType === 'rulesets') {
        $results['rule_sets'] = testRuleSets();
    }
    
    // Test 6: File validation
    if ($testType === 'all' || $testType === 'files') {
        $results['file_validation'] = testFileValidation();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'validation_test_suite', SecurityLogger::LEVEL_INFO,
        'Input validation test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Input validation test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Validation test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test basic validation functionality
 */
function testBasicValidation() {
    $validator = InputValidator::getInstance();
    
    $testCases = [
        [
            'name' => 'Valid email',
            'data' => ['email' => 'test@example.com'],
            'rules' => ['email' => ['type' => 'email', 'required' => true]],
            'expected_valid' => true
        ],
        [
            'name' => 'Invalid email',
            'data' => ['email' => 'invalid-email'],
            'rules' => ['email' => ['type' => 'email', 'required' => true]],
            'expected_valid' => false
        ],
        [
            'name' => 'Required field missing',
            'data' => [],
            'rules' => ['name' => ['type' => 'string', 'required' => true]],
            'expected_valid' => false
        ],
        [
            'name' => 'Length validation',
            'data' => ['password' => '123'],
            'rules' => ['password' => ['type' => 'string', 'min_length' => 8]],
            'expected_valid' => false
        ],
        [
            'name' => 'Numeric range validation',
            'data' => ['amount' => 150],
            'rules' => ['amount' => ['type' => 'float', 'min_value' => 25, 'max_value' => 1000]],
            'expected_valid' => true
        ]
    ];
    
    $results = [];
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        try {
            $result = $validator->validate($testCase['data'], $testCase['rules'], 'test');
            $testPassed = $result['valid'] === $testCase['expected_valid'];
            
            if ($testPassed) $passed++;
            
            $results[] = [
                'test_case' => $testCase['name'],
                'expected_valid' => $testCase['expected_valid'],
                'actual_valid' => $result['valid'],
                'errors' => $result['errors'],
                'passed' => $testPassed
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test_case' => $testCase['name'],
                'error' => $e->getMessage(),
                'passed' => false
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
 * Test security pattern detection
 */
function testSecurityPatterns() {
    $validator = InputValidator::getInstance();
    
    $testCases = [
        [
            'name' => 'SQL injection attempt',
            'data' => ['input' => "'; DROP TABLE users; --"],
            'rules' => ['input' => ['type' => 'string', 'required' => true]],
            'should_detect_threat' => true
        ],
        [
            'name' => 'XSS script tag',
            'data' => ['input' => '<script>alert("xss")</script>'],
            'rules' => ['input' => ['type' => 'string', 'required' => true]],
            'should_detect_threat' => true
        ],
        [
            'name' => 'JavaScript protocol',
            'data' => ['input' => 'javascript:alert(1)'],
            'rules' => ['input' => ['type' => 'string', 'required' => true]],
            'should_detect_threat' => true
        ],
        [
            'name' => 'Path traversal',
            'data' => ['input' => '../../../etc/passwd'],
            'rules' => ['input' => ['type' => 'string', 'required' => true]],
            'should_detect_threat' => true
        ],
        [
            'name' => 'Safe input',
            'data' => ['input' => 'This is a normal string'],
            'rules' => ['input' => ['type' => 'string', 'required' => true]],
            'should_detect_threat' => false
        ]
    ];
    
    $results = [];
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        try {
            $result = $validator->validate($testCase['data'], $testCase['rules'], 'security_test');
            
            // If validation should detect threat, it should fail
            $threatDetected = !$result['valid'];
            $testPassed = $threatDetected === $testCase['should_detect_threat'];
            
            if ($testPassed) $passed++;
            
            $results[] = [
                'test_case' => $testCase['name'],
                'should_detect_threat' => $testCase['should_detect_threat'],
                'threat_detected' => $threatDetected,
                'validation_errors' => $result['errors'],
                'passed' => $testPassed
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test_case' => $testCase['name'],
                'error' => $e->getMessage(),
                'passed' => false
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
 * Test data type validation
 */
function testDataTypes() {
    $validator = InputValidator::getInstance();
    
    $testCases = [
        [
            'name' => 'Integer validation - valid',
            'data' => ['number' => 42],
            'rules' => ['number' => ['type' => 'integer', 'required' => true]],
            'expected_valid' => true
        ],
        [
            'name' => 'Integer validation - invalid',
            'data' => ['number' => 'not a number'],
            'rules' => ['number' => ['type' => 'integer', 'required' => true]],
            'expected_valid' => false
        ],
        [
            'name' => 'Float validation - valid',
            'data' => ['amount' => 123.45],
            'rules' => ['amount' => ['type' => 'float', 'required' => true]],
            'expected_valid' => true
        ],
        [
            'name' => 'Boolean validation - valid',
            'data' => ['flag' => true],
            'rules' => ['flag' => ['type' => 'boolean', 'required' => true]],
            'expected_valid' => true
        ],
        [
            'name' => 'Array validation - valid',
            'data' => ['items' => ['a', 'b', 'c']],
            'rules' => ['items' => ['type' => 'array', 'required' => true]],
            'expected_valid' => true
        ]
    ];
    
    $results = [];
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        try {
            $result = $validator->validate($testCase['data'], $testCase['rules'], 'type_test');
            $testPassed = $result['valid'] === $testCase['expected_valid'];
            
            if ($testPassed) $passed++;
            
            $results[] = [
                'test_case' => $testCase['name'],
                'expected_valid' => $testCase['expected_valid'],
                'actual_valid' => $result['valid'],
                'passed' => $testPassed
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test_case' => $testCase['name'],
                'error' => $e->getMessage(),
                'passed' => false
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
 * Test sanitization functionality
 */
function testSanitization() {
    $validator = InputValidator::getInstance();
    
    $testCases = [
        [
            'name' => 'Trim whitespace',
            'input' => '  test  ',
            'context' => 'html',
            'expected_contains' => 'test'
        ],
        [
            'name' => 'HTML encoding',
            'input' => '<script>alert("test")</script>',
            'context' => 'html',
            'expected_contains' => '&lt;script&gt;'
        ],
        [
            'name' => 'URL encoding',
            'input' => 'hello world',
            'context' => 'url',
            'expected_contains' => 'hello+world'
        ],
        [
            'name' => 'JSON encoding',
            'input' => 'test "quote"',
            'context' => 'js',
            'expected_contains' => '\\"'
        ]
    ];
    
    $results = [];
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        try {
            $sanitized = $validator->sanitizeOutput($testCase['input'], $testCase['context']);
            $testPassed = strpos($sanitized, $testCase['expected_contains']) !== false;
            
            if ($testPassed) $passed++;
            
            $results[] = [
                'test_case' => $testCase['name'],
                'input' => $testCase['input'],
                'sanitized' => $sanitized,
                'expected_contains' => $testCase['expected_contains'],
                'passed' => $testPassed
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test_case' => $testCase['name'],
                'error' => $e->getMessage(),
                'passed' => false
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
 * Test predefined rule sets
 */
function testRuleSets() {
    $validator = InputValidator::getInstance();
    
    $testCases = [
        [
            'name' => 'User registration rules',
            'data' => [
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'full_name' => 'Test User'
            ],
            'rules' => ValidationRules::userRegistration(),
            'expected_valid' => true
        ],
        [
            'name' => 'Investment rules',
            'data' => [
                'amount' => 100.50,
                'wallet_address' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7',
                'chain' => 'ethereum',
                'package_name' => 'Gold Package'
            ],
            'rules' => ValidationRules::investment(),
            'expected_valid' => true
        ],
        [
            'name' => 'Admin login rules',
            'data' => [
                'username' => 'admin',
                'password' => 'adminpass'
            ],
            'rules' => ValidationRules::adminLogin(),
            'expected_valid' => true
        ]
    ];
    
    $results = [];
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        try {
            $result = $validator->validate($testCase['data'], $testCase['rules'], 'ruleset_test');
            $testPassed = $result['valid'] === $testCase['expected_valid'];
            
            if ($testPassed) $passed++;
            
            $results[] = [
                'test_case' => $testCase['name'],
                'expected_valid' => $testCase['expected_valid'],
                'actual_valid' => $result['valid'],
                'errors' => $result['errors'],
                'passed' => $testPassed
            ];
            
        } catch (Exception $e) {
            $results[] = [
                'test_case' => $testCase['name'],
                'error' => $e->getMessage(),
                'passed' => false
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
 * Test file validation (simulated)
 */
function testFileValidation() {
    $results = [];
    
    // Since we can't easily simulate file uploads in a test, 
    // we'll test the validation logic components
    
    $testCases = [
        [
            'name' => 'File validation rules',
            'rules' => ValidationRules::fileUpload(),
            'expected_has_document' => true
        ],
        [
            'name' => 'KYC file validation rules',
            'rules' => ValidationRules::kycFileUpload(),
            'expected_has_document' => true
        ]
    ];
    
    $passed = 0;
    
    foreach ($testCases as $testCase) {
        $hasDocument = isset($testCase['rules']['document']);
        $testPassed = $hasDocument === $testCase['expected_has_document'];
        
        if ($testPassed) $passed++;
        
        $results[] = [
            'test_case' => $testCase['name'],
            'has_document_rule' => $hasDocument,
            'rules' => $testCase['rules'],
            'passed' => $testPassed
        ];
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
 * Helper functions
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
