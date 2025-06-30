<?php
/**
 * RBAC TEST SUITE
 * Comprehensive testing of Role-Based Access Control system
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-rbac.php';
require_once '../config/permission-middleware.php';

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
    
    // Test 1: RBAC system initialization
    if ($testType === 'all' || $testType === 'initialization') {
        $results['initialization'] = testRBACInitialization();
    }
    
    // Test 2: Role management
    if ($testType === 'all' || $testType === 'role_management') {
        $results['role_management'] = testRoleManagement();
    }
    
    // Test 3: Permission management
    if ($testType === 'all' || $testType === 'permission_management') {
        $results['permission_management'] = testPermissionManagement();
    }
    
    // Test 4: User role assignments
    if ($testType === 'all' || $testType === 'user_assignments') {
        $results['user_assignments'] = testUserAssignments();
    }
    
    // Test 5: Permission checking
    if ($testType === 'all' || $testType === 'permission_checking') {
        $results['permission_checking'] = testPermissionChecking();
    }
    
    // Test 6: Resource ownership
    if ($testType === 'all' || $testType === 'resource_ownership') {
        $results['resource_ownership'] = testResourceOwnership();
    }
    
    // Test 7: Audit trail
    if ($testType === 'all' || $testType === 'audit_trail') {
        $results['audit_trail'] = testAuditTrail();
    }
    
    // Test 8: Middleware integration
    if ($testType === 'all' || $testType === 'middleware') {
        $results['middleware'] = testMiddlewareIntegration();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'rbac_test', SecurityLogger::LEVEL_INFO,
        'RBAC test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'RBAC test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("RBAC test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test RBAC system initialization
 */
function testRBACInitialization() {
    $testCases = [
        [
            'name' => 'Enterprise RBAC class exists',
            'test_function' => function() {
                return class_exists('EnterpriseRBAC');
            }
        ],
        [
            'name' => 'Permission middleware class exists',
            'test_function' => function() {
                return class_exists('PermissionMiddleware');
            }
        ],
        [
            'name' => 'RBAC tables created',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $tables = [
                    'rbac_permissions',
                    'rbac_roles',
                    'rbac_role_permissions',
                    'rbac_user_roles',
                    'rbac_permission_audit',
                    'rbac_resource_ownership',
                    'rbac_permission_conditions'
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
            'name' => 'Default permissions created',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT COUNT(*) FROM rbac_permissions WHERE is_system_permission = TRUE";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            }
        ],
        [
            'name' => 'Default roles created',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT COUNT(*) FROM rbac_roles WHERE is_system_role = TRUE";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test role management
 */
function testRoleManagement() {
    $testCases = [
        [
            'name' => 'Role creation method',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'createRole');
            }
        ],
        [
            'name' => 'Role assignment method',
            'test_function' => function() {
                return function_exists('assignRoleToUser');
            }
        ],
        [
            'name' => 'Role revocation method',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'revokeUserRole');
            }
        ],
        [
            'name' => 'User roles retrieval',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'getUserRoles');
            }
        ],
        [
            'name' => 'Role hierarchy validation',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT role_level FROM rbac_roles WHERE role_key = 'super_admin'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $superAdminLevel = $stmt->fetchColumn();
                
                $query = "SELECT role_level FROM rbac_roles WHERE role_key = 'chat_support'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $chatSupportLevel = $stmt->fetchColumn();
                
                return $superAdminLevel > $chatSupportLevel;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test permission management
 */
function testPermissionManagement() {
    $testCases = [
        [
            'name' => 'Permission creation method',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'createPermission');
            }
        ],
        [
            'name' => 'Permission assignment to role',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'assignPermissionToRole');
            }
        ],
        [
            'name' => 'Permission checking method',
            'test_function' => function() {
                return function_exists('hasPermission');
            }
        ],
        [
            'name' => 'User permissions retrieval',
            'test_function' => function() {
                return function_exists('getUserPermissions');
            }
        ],
        [
            'name' => 'Permission categories exist',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $categories = ['user_management', 'financial', 'security', 'system'];
                foreach ($categories as $category) {
                    $query = "SELECT COUNT(*) FROM rbac_permissions WHERE category = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$category]);
                    if ($stmt->fetchColumn() == 0) {
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
 * Test user assignments
 */
function testUserAssignments() {
    $testCases = [
        [
            'name' => 'User role assignment table',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'rbac_user_roles'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Role assignment with expiration',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM rbac_user_roles LIKE 'expires_at'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'User type support',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM rbac_user_roles LIKE 'user_type'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && strpos($column['Type'], 'enum') !== false;
            }
        ],
        [
            'name' => 'Assignment audit trail',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM rbac_user_roles LIKE 'assigned_by'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Active status tracking',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM rbac_user_roles LIKE 'is_active'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test permission checking
 */
function testPermissionChecking() {
    $testCases = [
        [
            'name' => 'Permission check function exists',
            'test_function' => function() {
                return function_exists('checkPermission');
            }
        ],
        [
            'name' => 'Require permission function exists',
            'test_function' => function() {
                return function_exists('requirePermission');
            }
        ],
        [
            'name' => 'Role permission checking',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'roleHasPermission');
            }
        ],
        [
            'name' => 'Permission conditions support',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'rbac_permission_conditions'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Context-based permission checking',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                $reflection = new ReflectionClass($rbac);
                return $reflection->hasMethod('checkPermissionConditions');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test resource ownership
 */
function testResourceOwnership() {
    $testCases = [
        [
            'name' => 'Resource ownership table exists',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'rbac_resource_ownership'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Ownership granting method',
            'test_function' => function() {
                return function_exists('grantResourceOwnership');
            }
        ],
        [
            'name' => 'Resource ownership checking',
            'test_function' => function() {
                return function_exists('checkResourceOwnership');
            }
        ],
        [
            'name' => 'Ownership levels defined',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM rbac_resource_ownership LIKE 'ownership_level'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $column = $stmt->fetch();
                
                return $column && strpos($column['Type'], 'enum') !== false;
            }
        ],
        [
            'name' => 'Resource access validation',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                $reflection = new ReflectionClass($rbac);
                return $reflection->hasMethod('checkResourceAccess');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test audit trail
 */
function testAuditTrail() {
    $testCases = [
        [
            'name' => 'Audit trail table exists',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'rbac_permission_audit'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Audit trail retrieval method',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                return method_exists($rbac, 'getPermissionAuditTrail');
            }
        ],
        [
            'name' => 'Permission audit logging',
            'test_function' => function() {
                $rbac = EnterpriseRBAC::getInstance();
                $reflection = new ReflectionClass($rbac);
                return $reflection->hasMethod('logPermissionAudit');
            }
        ],
        [
            'name' => 'Audit detail tracking',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $columns = ['user_id', 'permission_key', 'permission_granted', 'denial_reason'];
                foreach ($columns as $column) {
                    $query = "SHOW COLUMNS FROM rbac_permission_audit LIKE '$column'";
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
            'name' => 'Session tracking in audit',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW COLUMNS FROM rbac_permission_audit LIKE 'session_id'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test middleware integration
 */
function testMiddlewareIntegration() {
    $testCases = [
        [
            'name' => 'Middleware permission check',
            'test_function' => function() {
                return method_exists('PermissionMiddleware', 'checkPermission');
            }
        ],
        [
            'name' => 'Legacy compatibility',
            'test_function' => function() {
                return method_exists('PermissionMiddleware', 'legacyPermissionCheck');
            }
        ],
        [
            'name' => 'User migration support',
            'test_function' => function() {
                return function_exists('migrateUserToRBAC');
            }
        ],
        [
            'name' => 'Effective permissions retrieval',
            'test_function' => function() {
                return function_exists('getUserEffectivePermissions');
            }
        ],
        [
            'name' => 'RBAC role detection',
            'test_function' => function() {
                return method_exists('PermissionMiddleware', 'userHasRBACRoles');
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
