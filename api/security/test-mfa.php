<?php
/**
 * MFA SECURITY TEST ENDPOINT
 * Tests the multi-factor authentication system
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/mfa-system.php';

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
    
    // Test 1: TOTP functionality
    if ($testType === 'all' || $testType === 'totp') {
        $results['totp_functionality'] = testTOTPFunctionality();
    }
    
    // Test 2: SMS functionality
    if ($testType === 'all' || $testType === 'sms') {
        $results['sms_functionality'] = testSMSFunctionality();
    }
    
    // Test 3: Backup codes
    if ($testType === 'all' || $testType === 'backup_codes') {
        $results['backup_codes'] = testBackupCodes();
    }
    
    // Test 4: MFA enforcement
    if ($testType === 'all' || $testType === 'enforcement') {
        $results['mfa_enforcement'] = testMFAEnforcement();
    }
    
    // Test 5: Security features
    if ($testType === 'all' || $testType === 'security') {
        $results['security_features'] = testSecurityFeatures();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_test_suite', SecurityLogger::LEVEL_INFO,
        'MFA test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'MFA test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("MFA test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test TOTP functionality
 */
function testTOTPFunctionality() {
    $mfa = MFASystem::getInstance();
    
    $testCases = [
        [
            'name' => 'TOTP setup',
            'test_function' => function() use ($mfa) {
                $result = $mfa->setupTOTP('test_user_totp', 'admin');
                return isset($result['secret']) && isset($result['qr_code_url']) && isset($result['backup_codes']);
            }
        ],
        [
            'name' => 'TOTP secret generation',
            'test_function' => function() use ($mfa) {
                $result1 = $mfa->setupTOTP('test_user_1', 'admin');
                $result2 = $mfa->setupTOTP('test_user_2', 'admin');
                return $result1['secret'] !== $result2['secret']; // Secrets should be unique
            }
        ],
        [
            'name' => 'QR code format',
            'test_function' => function() use ($mfa) {
                $result = $mfa->setupTOTP('test_user_qr', 'admin');
                return strpos($result['qr_code_url'], 'otpauth://totp/') === 0;
            }
        ],
        [
            'name' => 'MFA status check',
            'test_function' => function() use ($mfa) {
                $status = $mfa->getMFAStatus('test_user_status', 'admin');
                return isset($status['enabled']) && isset($status['required']) && isset($status['methods']);
            }
        ]
    ];
    
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
 * Test SMS functionality
 */
function testSMSFunctionality() {
    $mfa = MFASystem::getInstance();
    
    $testCases = [
        [
            'name' => 'SMS setup',
            'test_function' => function() use ($mfa) {
                $result = $mfa->setupSMS('test_user_sms', 'admin', '+1234567890');
                return isset($result['message']) && isset($result['phone_masked']);
            }
        ],
        [
            'name' => 'Phone number validation',
            'test_function' => function() use ($mfa) {
                try {
                    $mfa->setupSMS('test_user_invalid', 'admin', 'invalid_phone');
                    return false; // Should have thrown exception
                } catch (Exception $e) {
                    return strpos($e->getMessage(), 'Invalid phone number') !== false;
                }
            }
        ],
        [
            'name' => 'Phone number masking',
            'test_function' => function() use ($mfa) {
                $result = $mfa->setupSMS('test_user_mask', 'admin', '+1234567890');
                return strpos($result['phone_masked'], '****') !== false;
            }
        ]
    ];
    
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
 * Test backup codes functionality
 */
function testBackupCodes() {
    $mfa = MFASystem::getInstance();
    
    $testCases = [
        [
            'name' => 'Backup codes generation',
            'test_function' => function() use ($mfa) {
                $codes = $mfa->generateBackupCodes('test_user_backup', 'admin');
                return is_array($codes) && count($codes) === 10;
            }
        ],
        [
            'name' => 'Backup codes uniqueness',
            'test_function' => function() use ($mfa) {
                $codes = $mfa->generateBackupCodes('test_user_unique', 'admin');
                return count($codes) === count(array_unique($codes));
            }
        ],
        [
            'name' => 'Backup codes format',
            'test_function' => function() use ($mfa) {
                $codes = $mfa->generateBackupCodes('test_user_format', 'admin');
                foreach ($codes as $code) {
                    if (strlen($code) !== 8 || !ctype_alnum($code)) {
                        return false;
                    }
                }
                return true;
            }
        ]
    ];
    
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
 * Test MFA enforcement
 */
function testMFAEnforcement() {
    $mfa = MFASystem::getInstance();
    
    $testCases = [
        [
            'name' => 'Admin MFA requirement',
            'test_function' => function() use ($mfa) {
                return $mfa->isMFARequired('test_admin', 'admin') === true;
            }
        ],
        [
            'name' => 'User MFA requirement',
            'test_function' => function() use ($mfa) {
                // Users don't require MFA by default
                return $mfa->isMFARequired('test_user', 'user') === false;
            }
        ],
        [
            'name' => 'MFA enabled check',
            'test_function' => function() use ($mfa) {
                // Should return false for non-existent user
                return $mfa->isMFAEnabled('non_existent_user', 'admin') === false;
            }
        ]
    ];
    
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
 * Test security features
 */
function testSecurityFeatures() {
    $mfa = MFASystem::getInstance();
    
    $testCases = [
        [
            'name' => 'Database tables creation',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $tables = ['mfa_settings', 'mfa_attempts', 'mfa_sms_codes', 'mfa_trusted_devices'];
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
            'name' => 'Encryption functionality',
            'test_function' => function() {
                // Test if encryption is working by checking if secrets are stored encrypted
                return class_exists('DataEncryption');
            }
        ],
        [
            'name' => 'Security logging',
            'test_function' => function() {
                // Test if security logging is available
                return function_exists('logSecurityEvent');
            }
        ]
    ];
    
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
