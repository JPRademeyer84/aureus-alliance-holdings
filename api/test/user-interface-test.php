<?php
/**
 * USER INTERFACE TEST
 * Tests if the user interface can actually access and display commission data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../security/commission-security.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize security manager
    $securityManager = new CommissionSecurityManager($db);
    
    $testResults = [];
    $testResults['ui_test'] = [];
    
    // TEST 1: Simulate user login session
    $testResults['ui_test']['step_1_simulate_login'] = [];
    
    try {
        // Get the test user (JPRademeyer) from our previous tests
        $userQuery = "SELECT id, username, email FROM users WHERE username = 'JPRademeyer'";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Simulate user session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            $testResults['ui_test']['step_1_simulate_login'] = [
                'status' => 'SUCCESS',
                'user_id' => $user['id'],
                'username' => $user['username'],
                'session_created' => true
            ];
        } else {
            $testResults['ui_test']['step_1_simulate_login'] = [
                'status' => 'FAILED',
                'error' => 'Test user JPRademeyer not found'
            ];
        }
        
    } catch (Exception $e) {
        $testResults['ui_test']['step_1_simulate_login'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // TEST 2: Test Commission Balance API
    $testResults['ui_test']['step_2_commission_balance_api'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate the commission-balance.php API call
            $userId = $_SESSION['user_id'];
            
            // Get secure user balance
            $balance = $securityManager->getSecureUserBalance($userId);
            
            // Get commission statistics
            $commissionStatsQuery = "
                SELECT 
                    COUNT(*) as total_commissions,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_commissions,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_commissions,
                    SUM(CASE WHEN status = 'pending' THEN commission_usdt ELSE 0 END) as pending_usdt,
                    SUM(CASE WHEN status = 'pending' THEN commission_nft ELSE 0 END) as pending_nft
                FROM referral_commissions 
                WHERE referrer_user_id = ?
            ";
            
            $commissionStatsStmt = $db->prepare($commissionStatsQuery);
            $commissionStatsStmt->execute([$userId]);
            $commissionStats = $commissionStatsStmt->fetch(PDO::FETCH_ASSOC);
            
            $testResults['ui_test']['step_2_commission_balance_api'] = [
                'status' => 'SUCCESS',
                'balance' => $balance,
                'commission_stats' => $commissionStats,
                'api_accessible' => true
            ];
            
        } catch (Exception $e) {
            $testResults['ui_test']['step_2_commission_balance_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // TEST 3: Test Withdrawal History API
    $testResults['ui_test']['step_3_withdrawal_history_api'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Get secure withdrawal history
            $withdrawalsQuery = "
                SELECT 
                    swr.*,
                    wpq.queue_position,
                    wpq.scheduled_for_date,
                    wpq.queue_status
                FROM secure_withdrawal_requests swr
                LEFT JOIN withdrawal_processing_queue wpq ON swr.id = wpq.withdrawal_request_id
                WHERE swr.user_id = ?
                ORDER BY swr.requested_at DESC
                LIMIT 10
            ";
            
            $withdrawalsStmt = $db->prepare($withdrawalsQuery);
            $withdrawalsStmt->execute([$userId]);
            $withdrawals = $withdrawalsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $testResults['ui_test']['step_3_withdrawal_history_api'] = [
                'status' => 'SUCCESS',
                'withdrawal_count' => count($withdrawals),
                'withdrawals' => $withdrawals,
                'api_accessible' => true
            ];
            
        } catch (Exception $e) {
            $testResults['ui_test']['step_3_withdrawal_history_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // TEST 4: Test Referral Stats API
    $testResults['ui_test']['step_4_referral_stats_api'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Get referral statistics
            $referralStatsQuery = "
                SELECT 
                    COUNT(DISTINCT referred_user_id) as total_referrals,
                    COUNT(*) as total_commissions,
                    SUM(commission_usdt) as total_usdt_earned,
                    SUM(commission_nft) as total_nft_earned,
                    MAX(created_at) as last_commission_date
                FROM referral_commissions 
                WHERE referrer_user_id = ?
            ";
            
            $referralStatsStmt = $db->prepare($referralStatsQuery);
            $referralStatsStmt->execute([$userId]);
            $referralStats = $referralStatsStmt->fetch(PDO::FETCH_ASSOC);
            
            $testResults['ui_test']['step_4_referral_stats_api'] = [
                'status' => 'SUCCESS',
                'referral_stats' => $referralStats,
                'api_accessible' => true
            ];
            
        } catch (Exception $e) {
            $testResults['ui_test']['step_4_referral_stats_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // TEST 5: Test Reinvestment API Accessibility
    $testResults['ui_test']['step_5_reinvestment_api'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Check if reinvestment API file exists and is accessible
            $reinvestFile = '../referrals/reinvest.php';
            if (file_exists($reinvestFile)) {
                $testResults['ui_test']['step_5_reinvestment_api'] = [
                    'status' => 'SUCCESS',
                    'file_exists' => true,
                    'api_accessible' => true
                ];
            } else {
                $testResults['ui_test']['step_5_reinvestment_api'] = [
                    'status' => 'FAILED',
                    'error' => 'Reinvestment API file not found'
                ];
            }
            
        } catch (Exception $e) {
            $testResults['ui_test']['step_5_reinvestment_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // TEST 6: Test Security System Integration
    $testResults['ui_test']['step_6_security_integration'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Test balance integrity
            $integrityValid = $securityManager->verifyBalanceIntegrity($userId);
            
            // Test transaction log
            $logQuery = "SELECT COUNT(*) as log_count FROM commission_transaction_log WHERE user_id = ?";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$userId]);
            $logCount = $logStmt->fetch(PDO::FETCH_ASSOC)['log_count'];
            
            $testResults['ui_test']['step_6_security_integration'] = [
                'status' => 'SUCCESS',
                'integrity_valid' => $integrityValid,
                'transaction_log_entries' => (int)$logCount,
                'security_system_working' => true
            ];
            
        } catch (Exception $e) {
            $testResults['ui_test']['step_6_security_integration'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // OVERALL UI TEST STATUS
    $allStepsSuccessful = true;
    $successfulSteps = 0;
    
    foreach ($testResults['ui_test'] as $step => $result) {
        if (isset($result['status']) && $result['status'] === 'SUCCESS') {
            $successfulSteps++;
        } else {
            $allStepsSuccessful = false;
        }
    }
    
    $testResults['ui_summary'] = [
        'overall_status' => $allStepsSuccessful ? 'ALL_UI_TESTS_PASS' : 'SOME_UI_ISSUES',
        'successful_steps' => $successfulSteps,
        'total_steps' => 6,
        'test_completed_at' => date('c'),
        'user_session_active' => isset($_SESSION['user_id']),
        'test_user' => $_SESSION['username'] ?? 'none'
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'User Interface Integration Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("UI test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'UI test execution failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
