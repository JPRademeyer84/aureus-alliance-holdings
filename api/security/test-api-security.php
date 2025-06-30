<?php
/**
 * API SECURITY TEST SUITE
 * Comprehensive testing of API security, rate limiting, and abuse detection
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-api-security.php';
require_once '../config/api-security-middleware.php';

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
    
    // Test 1: System initialization
    if ($testType === 'all' || $testType === 'initialization') {
        $results['initialization'] = testSystemInitialization();
    }
    
    // Test 2: API key management
    if ($testType === 'all' || $testType === 'api_keys') {
        $results['api_keys'] = testAPIKeyManagement();
    }
    
    // Test 3: Rate limiting
    if ($testType === 'all' || $testType === 'rate_limiting') {
        $results['rate_limiting'] = testRateLimiting();
    }
    
    // Test 4: Authentication
    if ($testType === 'all' || $testType === 'authentication') {
        $results['authentication'] = testAuthentication();
    }
    
    // Test 5: Abuse detection
    if ($testType === 'all' || $testType === 'abuse_detection') {
        $results['abuse_detection'] = testAbuseDetection();
    }
    
    // Test 6: Endpoint configuration
    if ($testType === 'all' || $testType === 'endpoint_config') {
        $results['endpoint_config'] = testEndpointConfiguration();
    }
    
    // Test 7: Security middleware
    if ($testType === 'all' || $testType === 'middleware') {
        $results['middleware'] = testSecurityMiddleware();
    }
    
    // Test 8: Usage analytics
    if ($testType === 'all' || $testType === 'analytics') {
        $results['analytics'] = testUsageAnalytics();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'api_security_test', SecurityLogger::LEVEL_INFO,
        'API security test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'API security test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("API security test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test system initialization
 */
function testSystemInitialization() {
    $testCases = [
        [
            'name' => 'Enterprise API security class exists',
            'test_function' => function() {
                return class_exists('EnterpriseAPISecurity');
            }
        ],
        [
            'name' => 'API security middleware exists',
            'test_function' => function() {
                return class_exists('APISecurityMiddleware');
            }
        ],
        [
            'name' => 'API security tables created',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $tables = [
                    'api_keys',
                    'api_rate_limits',
                    'api_request_log',
                    'api_abuse_detection',
                    'api_endpoint_config',
                    'api_usage_analytics'
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
            'name' => 'Default endpoint configurations created',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT COUNT(*) FROM api_endpoint_config";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            }
        ],
        [
            'name' => 'Convenience functions available',
            'test_function' => function() {
                return function_exists('generateAPIKey') && 
                       function_exists('validateAPIKey') &&
                       function_exists('checkAPIRateLimit') &&
                       function_exists('secureAPIEndpoint');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test API key management
 */
function testAPIKeyManagement() {
    $testCases = [
        [
            'name' => 'API key generation',
            'test_function' => function() {
                $result = generateAPIKey('test_user', 'user', 'Test Key', EnterpriseAPISecurity::TIER_FREE);
                return isset($result['key_id']) && isset($result['key_secret']) && isset($result['full_key']);
            }
        ],
        [
            'name' => 'API key validation',
            'test_function' => function() {
                $result = generateAPIKey('test_user', 'user', 'Test Key', EnterpriseAPISecurity::TIER_FREE);
                $validation = validateAPIKey($result['full_key']);
                return $validation !== false && $validation['user_id'] === 'test_user';
            }
        ],
        [
            'name' => 'API key expiration handling',
            'test_function' => function() {
                $expiredKey = generateAPIKey('test_user', 'user', 'Expired Key', EnterpriseAPISecurity::TIER_FREE, [], date('Y-m-d H:i:s', time() - 3600));
                $validation = validateAPIKey($expiredKey['full_key']);
                return $validation === false;
            }
        ],
        [
            'name' => 'API key tier assignment',
            'test_function' => function() {
                $result = generateAPIKey('test_user', 'user', 'Premium Key', EnterpriseAPISecurity::TIER_PREMIUM);
                $validation = validateAPIKey($result['full_key']);
                return $validation['tier'] === EnterpriseAPISecurity::TIER_PREMIUM;
            }
        ],
        [
            'name' => 'API key permissions',
            'test_function' => function() {
                $permissions = ['users.read', 'users.write'];
                $result = generateAPIKey('test_user', 'user', 'Permission Key', EnterpriseAPISecurity::TIER_BASIC, $permissions);
                $validation = validateAPIKey($result['full_key']);
                return $validation['permissions'] === $permissions;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test rate limiting
 */
function testRateLimiting() {
    $testCases = [
        [
            'name' => 'Rate limit check function exists',
            'test_function' => function() {
                return function_exists('checkAPIRateLimit');
            }
        ],
        [
            'name' => 'Rate limit allows initial requests',
            'test_function' => function() {
                $testIdentifier = 'test_' . bin2hex(random_bytes(8));
                $result = checkAPIRateLimit($testIdentifier, 'ip', '/test/endpoint', EnterpriseAPISecurity::TIER_FREE);
                return $result['allowed'] === true;
            }
        ],
        [
            'name' => 'Rate limit tracks request count',
            'test_function' => function() {
                $testIdentifier = 'test_' . bin2hex(random_bytes(8));
                $result1 = checkAPIRateLimit($testIdentifier, 'ip', '/test/endpoint', EnterpriseAPISecurity::TIER_FREE);
                $result2 = checkAPIRateLimit($testIdentifier, 'ip', '/test/endpoint', EnterpriseAPISecurity::TIER_FREE);
                return isset($result2['requests_remaining']) && $result2['requests_remaining'] < $result1['requests_remaining'];
            }
        ],
        [
            'name' => 'Rate limit tier differences',
            'test_function' => function() {
                $testIdentifier1 = 'test_free_' . bin2hex(random_bytes(8));
                $testIdentifier2 = 'test_premium_' . bin2hex(random_bytes(8));
                
                $freeResult = checkAPIRateLimit($testIdentifier1, 'ip', '/test/endpoint', EnterpriseAPISecurity::TIER_FREE);
                $premiumResult = checkAPIRateLimit($testIdentifier2, 'ip', '/test/endpoint', EnterpriseAPISecurity::TIER_PREMIUM);
                
                return $premiumResult['requests_remaining'] > $freeResult['requests_remaining'];
            }
        ],
        [
            'name' => 'Rate limit table structure',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $columns = ['identifier', 'identifier_type', 'endpoint_pattern', 'request_count', 'blocked_until'];
                foreach ($columns as $column) {
                    $query = "SHOW COLUMNS FROM api_rate_limits LIKE '$column'";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    if (!$stmt->fetch()) {
                        return false;
                    }
                }
                return true;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test authentication
 */
function testAuthentication() {
    $testCases = [
        [
            'name' => 'API key authentication method exists',
            'test_function' => function() {
                return method_exists('APISecurityMiddleware', 'authenticateRequest');
            }
        ],
        [
            'name' => 'Session authentication support',
            'test_function' => function() {
                // Check if session authentication is supported
                return isset($_SESSION); // Basic check
            }
        ],
        [
            'name' => 'Authentication type validation',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_endpoint_config LIKE 'authentication_types'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && $column['Type'] === 'json';
            }
        ],
        [
            'name' => 'Authentication requirement configuration',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_endpoint_config LIKE 'authentication_required'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && strpos($column['Type'], 'tinyint') !== false;
            }
        ],
        [
            'name' => 'Security level configuration',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_endpoint_config LIKE 'security_level'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && strpos($column['Type'], 'enum') !== false;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test abuse detection
 */
function testAbuseDetection() {
    $testCases = [
        [
            'name' => 'Abuse detection function exists',
            'test_function' => function() {
                return function_exists('detectAPIAbuse');
            }
        ],
        [
            'name' => 'Abuse detection table exists',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'api_abuse_detection'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Abuse level tracking',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_abuse_detection LIKE 'abuse_level'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Abuse pattern storage',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_abuse_detection LIKE 'abuse_patterns'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && $column['Type'] === 'json';
            }
        ],
        [
            'name' => 'Auto-blocking capability',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_abuse_detection LIKE 'auto_blocked'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test endpoint configuration
 */
function testEndpointConfiguration() {
    $testCases = [
        [
            'name' => 'Endpoint configuration table exists',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'api_endpoint_config'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Endpoint pattern matching',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT COUNT(*) FROM api_endpoint_config WHERE endpoint_pattern LIKE '%*%'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            }
        ],
        [
            'name' => 'Rate limit tier configuration',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_endpoint_config LIKE 'rate_limit_tier'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && $column['Type'] === 'json';
            }
        ],
        [
            'name' => 'Deprecation support',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $columns = ['deprecated', 'deprecation_date', 'replacement_endpoint'];
                foreach ($columns as $column) {
                    $query = "SHOW COLUMNS FROM api_endpoint_config LIKE '$column'";
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
            'name' => 'Required permissions configuration',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_endpoint_config LIKE 'required_permissions'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && $column['Type'] === 'json';
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test security middleware
 */
function testSecurityMiddleware() {
    $testCases = [
        [
            'name' => 'Security middleware class exists',
            'test_function' => function() {
                return class_exists('APISecurityMiddleware');
            }
        ],
        [
            'name' => 'Secure endpoint method exists',
            'test_function' => function() {
                return method_exists('APISecurityMiddleware', 'secureEndpoint');
            }
        ],
        [
            'name' => 'Request logging method exists',
            'test_function' => function() {
                return method_exists('APISecurityMiddleware', 'logRequestCompletion');
            }
        ],
        [
            'name' => 'Security headers method exists',
            'test_function' => function() {
                return method_exists('APISecurityMiddleware', 'setSecurityHeaders');
            }
        ],
        [
            'name' => 'HTTPS enforcement method exists',
            'test_function' => function() {
                return method_exists('APISecurityMiddleware', 'enforceHTTPS');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test usage analytics
 */
function testUsageAnalytics() {
    $testCases = [
        [
            'name' => 'Usage analytics table exists',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'api_usage_analytics'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Analytics data structure',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $columns = ['date_hour', 'endpoint_pattern', 'request_count', 'error_count', 'avg_response_time_ms'];
                foreach ($columns as $column) {
                    $query = "SHOW COLUMNS FROM api_usage_analytics LIKE '$column'";
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
            'name' => 'Performance metrics tracking',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM api_usage_analytics LIKE 'total_bytes_transferred'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Security metrics tracking',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $columns = ['rate_limited_requests', 'abuse_detected_requests'];
                foreach ($columns as $column) {
                    $query = "SHOW COLUMNS FROM api_usage_analytics LIKE '$column'";
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
            'name' => 'Unique constraint for analytics',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW INDEX FROM api_usage_analytics WHERE Key_name = 'unique_analytics'";
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
