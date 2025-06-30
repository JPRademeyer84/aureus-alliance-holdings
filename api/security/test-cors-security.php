<?php
/**
 * CORS SECURITY TEST ENDPOINT
 * Tests the CORS security implementation
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';

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
    
    // Test 1: Origin validation
    if ($testType === 'all' || $testType === 'origin_validation') {
        $results['origin_validation'] = testOriginValidation();
    }
    
    // Test 2: Attack detection
    if ($testType === 'all' || $testType === 'attack_detection') {
        $results['attack_detection'] = testAttackDetection();
    }
    
    // Test 3: Rate limiting
    if ($testType === 'all' || $testType === 'rate_limiting') {
        $results['rate_limiting'] = testRateLimiting();
    }
    
    // Test 4: Security headers
    if ($testType === 'all' || $testType === 'security_headers') {
        $results['security_headers'] = testSecurityHeaders();
    }
    
    // Test 5: Configuration security
    if ($testType === 'all' || $testType === 'configuration') {
        $results['configuration'] = testConfigurationSecurity();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cors_security_test', SecurityLogger::LEVEL_INFO,
        'CORS security test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'CORS security test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("CORS security test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test origin validation functionality
 */
function testOriginValidation() {
    $testCases = [
        [
            'name' => 'Valid origin acceptance',
            'test_function' => function() {
                $allowedOrigins = SecureCORS::getAllowedOrigins();
                if (empty($allowedOrigins)) {
                    return false; // No origins configured
                }
                
                $testOrigin = $allowedOrigins[0];
                return SecureCORS::validateOrigin($testOrigin);
            }
        ],
        [
            'name' => 'Invalid origin rejection',
            'test_function' => function() {
                $invalidOrigin = 'https://malicious-site.com';
                return !SecureCORS::validateOrigin($invalidOrigin);
            }
        ],
        [
            'name' => 'Empty origin handling',
            'test_function' => function() {
                return !SecureCORS::validateOrigin('');
            }
        ],
        [
            'name' => 'Null origin handling',
            'test_function' => function() {
                return !SecureCORS::validateOrigin(null);
            }
        ],
        [
            'name' => 'Case sensitivity',
            'test_function' => function() {
                $allowedOrigins = SecureCORS::getAllowedOrigins();
                if (empty($allowedOrigins)) {
                    return true; // Skip if no origins
                }
                
                $testOrigin = strtoupper($allowedOrigins[0]);
                return !SecureCORS::validateOrigin($testOrigin);
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test attack detection functionality
 */
function testAttackDetection() {
    $testCases = [
        [
            'name' => 'IP address origin detection',
            'test_function' => function() {
                $ipOrigin = 'http://192.168.1.1';
                return !SecureCORS::validateOriginWithSecurity($ipOrigin);
            }
        ],
        [
            'name' => 'Suspicious TLD detection',
            'test_function' => function() {
                $suspiciousTLD = 'https://malicious.tk';
                return !SecureCORS::validateOriginWithSecurity($suspiciousTLD);
            }
        ],
        [
            'name' => 'URL shortener detection',
            'test_function' => function() {
                $shortener = 'https://bit.ly/malicious';
                return !SecureCORS::validateOriginWithSecurity($shortener);
            }
        ],
        [
            'name' => 'Localhost detection',
            'test_function' => function() {
                $localhost = 'http://localhost:8080';
                return !SecureCORS::validateOriginWithSecurity($localhost);
            }
        ],
        [
            'name' => 'Data URI detection',
            'test_function' => function() {
                $dataUri = 'data:text/html,<script>alert("xss")</script>';
                return !SecureCORS::validateOriginWithSecurity($dataUri);
            }
        ],
        [
            'name' => 'File protocol detection',
            'test_function' => function() {
                $fileProtocol = 'file:///etc/passwd';
                return !SecureCORS::validateOriginWithSecurity($fileProtocol);
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test rate limiting functionality
 */
function testRateLimiting() {
    $testCases = [
        [
            'name' => 'Rate limiting mechanism exists',
            'test_function' => function() {
                $reflection = new ReflectionClass('SecureCORS');
                return $reflection->hasMethod('isRateLimited');
            }
        ],
        [
            'name' => 'Session rate limit storage',
            'test_function' => function() {
                // Test that rate limiting uses session storage
                $origin = 'https://test-rate-limit.com';
                SecureCORS::validateOriginWithSecurity($origin);
                return isset($_SESSION['cors_rate_limit']);
            }
        ],
        [
            'name' => 'Rate limit cleanup',
            'test_function' => function() {
                // Test that old entries are cleaned up
                if (!isset($_SESSION['cors_rate_limit'])) {
                    $_SESSION['cors_rate_limit'] = [];
                }
                
                // Add old entry
                $oldKey = 'cors_test_' . (time() - 3600); // 1 hour ago
                $_SESSION['cors_rate_limit'][$oldKey] = time() - 3600;
                
                $origin = 'https://test-cleanup.com';
                SecureCORS::validateOriginWithSecurity($origin);
                
                // Check if old entry was cleaned up
                return !isset($_SESSION['cors_rate_limit'][$oldKey]);
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test security headers
 */
function testSecurityHeaders() {
    $testCases = [
        [
            'name' => 'CORS headers function exists',
            'test_function' => function() {
                return function_exists('setCorsHeaders');
            }
        ],
        [
            'name' => 'Preflight handler exists',
            'test_function' => function() {
                return function_exists('handlePreflight');
            }
        ],
        [
            'name' => 'SecureCORS class exists',
            'test_function' => function() {
                return class_exists('SecureCORS');
            }
        ],
        [
            'name' => 'Origin validation method exists',
            'test_function' => function() {
                return method_exists('SecureCORS', 'validateOrigin');
            }
        ],
        [
            'name' => 'Enhanced validation method exists',
            'test_function' => function() {
                return method_exists('SecureCORS', 'validateOriginWithSecurity');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test configuration security
 */
function testConfigurationSecurity() {
    $testCases = [
        [
            'name' => 'No wildcard origins configured',
            'test_function' => function() {
                $allowedOrigins = SecureCORS::getAllowedOrigins();
                return !in_array('*', $allowedOrigins, true);
            }
        ],
        [
            'name' => 'Origins are HTTPS',
            'test_function' => function() {
                $allowedOrigins = SecureCORS::getAllowedOrigins();
                foreach ($allowedOrigins as $origin) {
                    if (strpos($origin, 'https://') !== 0 && strpos($origin, 'http://localhost') !== 0) {
                        return false;
                    }
                }
                return true;
            }
        ],
        [
            'name' => 'Security stats available',
            'test_function' => function() {
                $stats = SecureCORS::getSecurityStats();
                return isset($stats['allowed_origins']) && isset($stats['attack_detection_enabled']);
            }
        ],
        [
            'name' => 'Attack detection enabled',
            'test_function' => function() {
                $stats = SecureCORS::getSecurityStats();
                return $stats['attack_detection_enabled'] === true;
            }
        ],
        [
            'name' => 'Logging integration',
            'test_function' => function() {
                return function_exists('logCorsEvent');
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
