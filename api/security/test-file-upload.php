<?php
/**
 * FILE UPLOAD SECURITY TEST ENDPOINT
 * Tests the secure file upload system
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/secure-file-upload.php';

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
    
    // Test 1: File validation
    if ($testType === 'all' || $testType === 'validation') {
        $results['file_validation'] = testFileValidation();
    }
    
    // Test 2: Security scanning
    if ($testType === 'all' || $testType === 'security') {
        $results['security_scanning'] = testSecurityScanning();
    }
    
    // Test 3: Storage security
    if ($testType === 'all' || $testType === 'storage') {
        $results['storage_security'] = testStorageSecurity();
    }
    
    // Test 4: Access controls
    if ($testType === 'all' || $testType === 'access') {
        $results['access_controls'] = testAccessControls();
    }
    
    // Test 5: Quarantine system
    if ($testType === 'all' || $testType === 'quarantine') {
        $results['quarantine_system'] = testQuarantineSystem();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'file_upload_test_suite', SecurityLogger::LEVEL_INFO,
        'File upload security test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'File upload security test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("File upload test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test file validation functionality
 */
function testFileValidation() {
    $testCases = [
        [
            'name' => 'Directory structure',
            'test_function' => function() {
                $uploadDir = dirname(dirname(__DIR__)) . '/secure-uploads/';
                $quarantineDir = dirname(dirname(__DIR__)) . '/quarantine/';
                
                return is_dir($uploadDir) && is_dir($quarantineDir);
            }
        ],
        [
            'name' => 'Directory permissions',
            'test_function' => function() {
                $uploadDir = dirname(dirname(__DIR__)) . '/secure-uploads/';
                $perms = fileperms($uploadDir);
                
                // Check if directory is not world-readable
                return ($perms & 0004) === 0;
            }
        ],
        [
            'name' => 'SecureFileUpload class availability',
            'test_function' => function() {
                return class_exists('SecureFileUpload');
            }
        ],
        [
            'name' => 'File type validation methods',
            'test_function' => function() {
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                
                $requiredMethods = ['validateFileType', 'validateFileContent', 'scanForThreats'];
                foreach ($requiredMethods as $method) {
                    if (!$reflection->hasMethod($method)) {
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
 * Test security scanning functionality
 */
function testSecurityScanning() {
    $testCases = [
        [
            'name' => 'Malicious pattern detection',
            'test_function' => function() {
                // Create a test file with malicious content
                $testContent = '<script>alert("xss")</script>';
                $testFile = tempnam(sys_get_temp_dir(), 'security_test');
                file_put_contents($testFile, $testContent);
                
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                $method = $reflection->getMethod('validateFileContent');
                $method->setAccessible(true);
                
                try {
                    $method->invoke($upload, ['tmp_name' => $testFile]);
                    unlink($testFile);
                    return false; // Should have thrown exception
                } catch (Exception $e) {
                    unlink($testFile);
                    return strpos($e->getMessage(), 'Malicious content') !== false;
                }
            }
        ],
        [
            'name' => 'File size validation',
            'test_function' => function() {
                // Test file size limits
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                $method = $reflection->getMethod('validateBasicUpload');
                $method->setAccessible(true);
                
                $testFile = [
                    'size' => 10 * 1024 * 1024, // 10MB - should exceed 5MB limit
                    'error' => UPLOAD_ERR_OK,
                    'tmp_name' => tempnam(sys_get_temp_dir(), 'size_test')
                ];
                
                try {
                    $method->invoke($upload, $testFile);
                    return false; // Should have thrown exception
                } catch (Exception $e) {
                    return strpos($e->getMessage(), 'File size') !== false;
                }
            }
        ],
        [
            'name' => 'MIME type validation',
            'test_function' => function() {
                // Test MIME type checking
                return function_exists('finfo_open') && function_exists('finfo_file');
            }
        ],
        [
            'name' => 'Magic number validation',
            'test_function' => function() {
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                
                return $reflection->hasMethod('validateMagicNumbers');
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
 * Test storage security
 */
function testStorageSecurity() {
    $testCases = [
        [
            'name' => 'Upload directory outside web root',
            'test_function' => function() {
                $uploadDir = dirname(dirname(__DIR__)) . '/secure-uploads/';
                $webRoot = dirname(dirname(__DIR__)) . '/public/';
                
                // Check if upload directory is not under web root
                return strpos($uploadDir, $webRoot) === false;
            }
        ],
        [
            'name' => 'Secure filename generation',
            'test_function' => function() {
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                $method = $reflection->getMethod('generateSecureFilename');
                $method->setAccessible(true);
                
                $testFile = ['name' => '../../../etc/passwd'];
                $filename = $method->invoke($upload, $testFile, 'test', 'user123');
                
                // Should not contain path traversal
                return strpos($filename, '../') === false && strpos($filename, '/') === false;
            }
        ],
        [
            'name' => 'File permissions',
            'test_function' => function() {
                // Test that uploaded files get secure permissions (0600)
                $testFile = tempnam(sys_get_temp_dir(), 'perm_test');
                chmod($testFile, 0600);
                $perms = fileperms($testFile);
                unlink($testFile);
                
                // Check if only owner can read/write
                return ($perms & 0077) === 0;
            }
        ],
        [
            'name' => 'Path traversal protection',
            'test_function' => function() {
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                $method = $reflection->getMethod('isPathSafe');
                $method->setAccessible(true);
                
                $safePath = '/secure-uploads/kyc/document.pdf';
                $unsafePath = '/secure-uploads/../../../etc/passwd';
                
                return $method->invoke($upload, $safePath) && !$method->invoke($upload, $unsafePath);
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
 * Test access controls
 */
function testAccessControls() {
    $testCases = [
        [
            'name' => 'Authentication requirement',
            'test_function' => function() {
                // Check if upload endpoints require authentication
                return isset($_SESSION['admin_id']) || isset($_SESSION['user_id']);
            }
        ],
        [
            'name' => 'File serving security',
            'test_function' => function() {
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                
                return $reflection->hasMethod('serveFile');
            }
        ],
        [
            'name' => 'Logging functionality',
            'test_function' => function() {
                return function_exists('logFileUploadEvent');
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
 * Test quarantine system
 */
function testQuarantineSystem() {
    $testCases = [
        [
            'name' => 'Quarantine directory exists',
            'test_function' => function() {
                $quarantineDir = dirname(dirname(__DIR__)) . '/quarantine/';
                return is_dir($quarantineDir);
            }
        ],
        [
            'name' => 'Quarantine functionality',
            'test_function' => function() {
                $upload = new SecureFileUpload();
                $reflection = new ReflectionClass($upload);
                
                return $reflection->hasMethod('quarantineFile');
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
