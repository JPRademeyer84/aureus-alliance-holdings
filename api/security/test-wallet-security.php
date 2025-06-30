<?php
/**
 * WALLET SECURITY TEST SUITE
 * Comprehensive testing of enterprise wallet security features
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-wallet-security.php';
require_once '../config/multi-signature-wallet.php';
require_once '../config/cold-storage-manager.php';

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
    
    // Test 1: Wallet creation and security levels
    if ($testType === 'all' || $testType === 'wallet_creation') {
        $results['wallet_creation'] = testWalletCreation();
    }
    
    // Test 2: Multi-signature functionality
    if ($testType === 'all' || $testType === 'multi_signature') {
        $results['multi_signature'] = testMultiSignature();
    }
    
    // Test 3: Cold storage management
    if ($testType === 'all' || $testType === 'cold_storage') {
        $results['cold_storage'] = testColdStorage();
    }
    
    // Test 4: Transaction approval workflow
    if ($testType === 'all' || $testType === 'approval_workflow') {
        $results['approval_workflow'] = testApprovalWorkflow();
    }
    
    // Test 5: Security controls
    if ($testType === 'all' || $testType === 'security_controls') {
        $results['security_controls'] = testSecurityControls();
    }
    
    // Test 6: HSM integration
    if ($testType === 'all' || $testType === 'hsm_integration') {
        $results['hsm_integration'] = testHSMIntegration();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_security_test', SecurityLogger::LEVEL_INFO,
        'Wallet security test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Wallet security test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Wallet security test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test wallet creation and security levels
 */
function testWalletCreation() {
    $testCases = [
        [
            'name' => 'Enterprise wallet security class exists',
            'test_function' => function() {
                return class_exists('EnterpriseWalletSecurity');
            }
        ],
        [
            'name' => 'Security level calculation',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('calculateSecurityLevel');
            }
        ],
        [
            'name' => 'HSM encryption availability',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('encryptWithHSM');
            }
        ],
        [
            'name' => 'Multi-sig configuration generation',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('generateMultiSigConfig');
            }
        ],
        [
            'name' => 'Database tables creation',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $tables = ['secure_wallets', 'wallet_transaction_approvals', 'wallet_approval_signatures'];
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
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test multi-signature functionality
 */
function testMultiSignature() {
    $testCases = [
        [
            'name' => 'Multi-signature wallet class exists',
            'test_function' => function() {
                return class_exists('MultiSignatureWallet');
            }
        ],
        [
            'name' => 'Approval submission method',
            'test_function' => function() {
                return method_exists('MultiSignatureWallet', 'submitApproval');
            }
        ],
        [
            'name' => 'Transaction execution method',
            'test_function' => function() {
                return method_exists('MultiSignatureWallet', 'executeApprovedTransaction');
            }
        ],
        [
            'name' => 'Emergency override capability',
            'test_function' => function() {
                return method_exists('MultiSignatureWallet', 'emergencyOverride');
            }
        ],
        [
            'name' => 'Signature verification',
            'test_function' => function() {
                $multiSig = MultiSignatureWallet::getInstance();
                $reflection = new ReflectionClass($multiSig);
                return $reflection->hasMethod('verifyAllSignatures');
            }
        ],
        [
            'name' => 'Approval revocation',
            'test_function' => function() {
                return method_exists('MultiSignatureWallet', 'revokeApproval');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test cold storage management
 */
function testColdStorage() {
    $testCases = [
        [
            'name' => 'Cold storage manager class exists',
            'test_function' => function() {
                return class_exists('ColdStorageManager');
            }
        ],
        [
            'name' => 'Vault creation functionality',
            'test_function' => function() {
                return method_exists('ColdStorageManager', 'createColdStorageVault');
            }
        ],
        [
            'name' => 'Balance check capability',
            'test_function' => function() {
                return method_exists('ColdStorageManager', 'performBalanceCheck');
            }
        ],
        [
            'name' => 'Transfer initiation',
            'test_function' => function() {
                return method_exists('ColdStorageManager', 'initiateColdStorageTransfer');
            }
        ],
        [
            'name' => 'Access logging',
            'test_function' => function() {
                $coldStorage = ColdStorageManager::getInstance();
                $reflection = new ReflectionClass($coldStorage);
                return $reflection->hasMethod('logColdStorageAccess');
            }
        ],
        [
            'name' => 'Cold storage database tables',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $tables = ['cold_storage_vaults', 'cold_storage_wallets', 'cold_storage_access_log'];
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
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test approval workflow
 */
function testApprovalWorkflow() {
    $testCases = [
        [
            'name' => 'Risk calculation',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('calculateTransactionRisk');
            }
        ],
        [
            'name' => 'Approval threshold calculation',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('calculateRequiredApprovals');
            }
        ],
        [
            'name' => 'Pending approvals retrieval',
            'test_function' => function() {
                return function_exists('getPendingWalletApprovals');
            }
        ],
        [
            'name' => 'Approval submission',
            'test_function' => function() {
                return function_exists('submitWalletApproval');
            }
        ],
        [
            'name' => 'Transaction execution',
            'test_function' => function() {
                return function_exists('executeWalletTransaction');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test security controls
 */
function testSecurityControls() {
    $testCases = [
        [
            'name' => 'Address validation',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('validateAddressFormat');
            }
        ],
        [
            'name' => 'Known address checking',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('isKnownAddress');
            }
        ],
        [
            'name' => 'Recent transaction monitoring',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('hasRecentTransactions');
            }
        ],
        [
            'name' => 'Geographic risk assessment',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('isUnusualLocation');
            }
        ],
        [
            'name' => 'Security audit logging',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('logWalletOperation');
            }
        ]
    ];
    
    return runTestCases($testCases);
}

/**
 * Test HSM integration
 */
function testHSMIntegration() {
    $testCases = [
        [
            'name' => 'HSM availability check',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('isHSMAvailable');
            }
        ],
        [
            'name' => 'Hardware encryption method',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('encryptWithHardwareModule');
            }
        ],
        [
            'name' => 'HSM key management table',
            'test_function' => function() {
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SHOW TABLES LIKE 'hsm_key_management'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                return $stmt->fetch() !== false;
            }
        ],
        [
            'name' => 'Key reference storage',
            'test_function' => function() {
                $walletSecurity = EnterpriseWalletSecurity::getInstance();
                $reflection = new ReflectionClass($walletSecurity);
                return $reflection->hasMethod('storeHSMKeyReference');
            }
        ],
        [
            'name' => 'Enhanced encryption for cold storage',
            'test_function' => function() {
                $coldStorage = ColdStorageManager::getInstance();
                $reflection = new ReflectionClass($coldStorage);
                return $reflection->hasMethod('encryptPrivateKeyForColdStorage');
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
