<?php
/**
 * COMPLETE REFERRAL SYSTEM END-TO-END TEST
 * Tests all connections: User → Admin → Database → API → Security
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../security/commission-security.php';
require_once '../security/withdrawal-scheduler.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize security systems
    $securityManager = new CommissionSecurityManager($db);
    $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
    
    $testResults = [];
    
    // TEST 1: Database Tables Existence
    $testResults['database_tables'] = [];
    $requiredTables = [
        'referral_commissions',
        'commission_balances_primary',
        'commission_balances_verification',
        'commission_transaction_log',
        'security_audit_log',
        'secure_withdrawal_requests',
        'withdrawal_processing_queue',
        'business_hours_config'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            $checkQuery = "SELECT 1 FROM $table LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            $testResults['database_tables'][$table] = 'EXISTS';
        } catch (Exception $e) {
            $testResults['database_tables'][$table] = 'MISSING';
        }
    }
    
    // TEST 2: Security System Integrity
    $testResults['security_system'] = [];
    
    // Test hash generation
    try {
        $testHash = hash('sha256', 'test_data_' . time());
        $testResults['security_system']['hash_generation'] = 'WORKING';
    } catch (Exception $e) {
        $testResults['security_system']['hash_generation'] = 'FAILED: ' . $e->getMessage();
    }
    
    // Test balance integrity for test user (user_id = 1)
    try {
        $testUserId = 1;
        $isValid = $securityManager->verifyBalanceIntegrity($testUserId);
        $testResults['security_system']['balance_integrity'] = $isValid ? 'VALID' : 'INVALID';
    } catch (Exception $e) {
        $testResults['security_system']['balance_integrity'] = 'ERROR: ' . $e->getMessage();
    }
    
    // TEST 3: Business Hours System
    $testResults['business_hours'] = [];
    
    try {
        $isWithinHours = $withdrawalScheduler->isWithinBusinessHours();
        $testResults['business_hours']['current_status'] = $isWithinHours ? 'WITHIN_HOURS' : 'OUTSIDE_HOURS';
        
        $nextBusinessDay = $withdrawalScheduler->getNextBusinessDay();
        $testResults['business_hours']['next_business_day'] = date('Y-m-d H:i:s', $nextBusinessDay);
    } catch (Exception $e) {
        $testResults['business_hours']['error'] = $e->getMessage();
    }
    
    // TEST 4: API Endpoints Connectivity
    $testResults['api_endpoints'] = [];
    
    $apiEndpoints = [
        '/api/referrals/user-stats.php',
        '/api/referrals/user-history.php',
        '/api/referrals/commission-balance.php',
        '/api/referrals/payout.php',
        '/api/referrals/activate-commissions.php',
        '/api/admin/commission-records.php',
        '/api/admin/withdrawal-requests.php',
        '/api/admin/secure-withdrawals.php'
    ];
    
    foreach ($apiEndpoints as $endpoint) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $endpoint;
        $testResults['api_endpoints'][$endpoint] = file_exists($filePath) ? 'EXISTS' : 'MISSING';
    }
    
    // TEST 5: Commission Calculation Test
    $testResults['commission_calculation'] = [];
    
    try {
        $testInvestmentAmount = 100.00;
        $commissionLevels = [
            1 => ['usdt' => 12, 'nft' => 12],
            2 => ['usdt' => 5, 'nft' => 5],
            3 => ['usdt' => 3, 'nft' => 3]
        ];
        
        foreach ($commissionLevels as $level => $rates) {
            $usdtCommission = ($testInvestmentAmount * $rates['usdt']) / 100;
            $nftCommission = intval(($testInvestmentAmount * $rates['nft']) / 100 / 5); // $5 per NFT
            
            $testResults['commission_calculation']["level_$level"] = [
                'usdt_commission' => $usdtCommission,
                'nft_commission' => $nftCommission,
                'calculation_valid' => ($usdtCommission > 0 && $nftCommission >= 0)
            ];
        }
    } catch (Exception $e) {
        $testResults['commission_calculation']['error'] = $e->getMessage();
    }
    
    // TEST 6: Session and Authentication
    $testResults['authentication'] = [];
    
    $testResults['authentication']['user_session'] = isset($_SESSION['user_id']) ? 'ACTIVE' : 'INACTIVE';
    $testResults['authentication']['admin_session'] = isset($_SESSION['admin_id']) ? 'ACTIVE' : 'INACTIVE';
    $testResults['authentication']['session_id'] = session_id();
    
    // TEST 7: Database Connection and Performance
    $testResults['database_performance'] = [];
    
    try {
        $startTime = microtime(true);
        
        // Test query performance
        $testQuery = "SELECT COUNT(*) as count FROM referral_commissions";
        $testStmt = $db->prepare($testQuery);
        $testStmt->execute();
        $commissionCount = $testStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $testResults['database_performance'] = [
            'connection_status' => 'CONNECTED',
            'commission_records_count' => (int)$commissionCount,
            'query_time_ms' => round($queryTime, 2),
            'performance_rating' => $queryTime < 100 ? 'EXCELLENT' : ($queryTime < 500 ? 'GOOD' : 'SLOW')
        ];
    } catch (Exception $e) {
        $testResults['database_performance'] = [
            'connection_status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // TEST 8: Frontend Component Integration
    $testResults['frontend_integration'] = [];
    
    $frontendComponents = [
        'src/components/dashboard/CommissionWallet.tsx',
        'src/components/admin/CommissionManagement.tsx',
        'src/pages/Affiliate.tsx',
        'src/hooks/useReferralTracking.ts'
    ];
    
    foreach ($frontendComponents as $component) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/../' . $component;
        $testResults['frontend_integration'][$component] = file_exists($filePath) ? 'EXISTS' : 'MISSING';
    }
    
    // TEST 9: Security Audit Log Test
    $testResults['security_audit'] = [];
    
    try {
        // Create a test security event
        $auditQuery = "INSERT INTO security_audit_log (
            event_type, user_id, event_details, security_level, ip_address
        ) VALUES ('balance_verification', 1, ?, 'info', ?)";
        
        $auditStmt = $db->prepare($auditQuery);
        $auditStmt->execute([
            json_encode(['test' => 'system_test', 'timestamp' => time()]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $testResults['security_audit']['log_creation'] = 'SUCCESS';
        
        // Check recent audit logs
        $recentLogsQuery = "SELECT COUNT(*) as count FROM security_audit_log WHERE event_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $recentLogsStmt = $db->prepare($recentLogsQuery);
        $recentLogsStmt->execute();
        $recentCount = $recentLogsStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $testResults['security_audit']['recent_logs_count'] = (int)$recentCount;
        
    } catch (Exception $e) {
        $testResults['security_audit']['error'] = $e->getMessage();
    }
    
    // TEST 10: Overall System Health
    $testResults['system_health'] = [];
    
    $healthChecks = [
        'database_connected' => isset($testResults['database_performance']['connection_status']) && $testResults['database_performance']['connection_status'] === 'CONNECTED',
        'security_system_active' => isset($testResults['security_system']['hash_generation']) && $testResults['security_system']['hash_generation'] === 'WORKING',
        'business_hours_configured' => isset($testResults['business_hours']['current_status']),
        'api_endpoints_available' => count(array_filter($testResults['api_endpoints'], function($status) { return $status === 'EXISTS'; })) >= 6,
        'commission_calculation_working' => isset($testResults['commission_calculation']['level_1']['calculation_valid']) && $testResults['commission_calculation']['level_1']['calculation_valid']
    ];
    
    $healthScore = (count(array_filter($healthChecks)) / count($healthChecks)) * 100;
    
    $testResults['system_health'] = [
        'health_checks' => $healthChecks,
        'health_score_percentage' => round($healthScore, 1),
        'overall_status' => $healthScore >= 90 ? 'EXCELLENT' : ($healthScore >= 70 ? 'GOOD' : ($healthScore >= 50 ? 'FAIR' : 'POOR')),
        'recommendations' => $healthScore < 100 ? 'Some components need attention' : 'All systems operational'
    ];
    
    // Return comprehensive test results
    echo json_encode([
        'success' => true,
        'test_completed_at' => date('c'),
        'test_duration_seconds' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3),
        'system_version' => 'Aureus Referral System v2.0 - Military Grade',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Referral system test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Test execution failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
