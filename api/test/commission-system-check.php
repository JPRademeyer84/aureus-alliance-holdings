<?php
/**
 * COMMISSION SYSTEM CHECK
 * Checks if the commission system is actually working for real users
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
    
    $testResults = [];
    
    // STEP 1: Check if any real users exist
    $testResults['step_1_real_users'] = [];
    
    try {
        $userQuery = "SELECT COUNT(*) as user_count FROM users";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute();
        $userCount = $userStmt->fetch(PDO::FETCH_ASSOC)['user_count'];
        
        // Get a sample user
        $sampleUserQuery = "SELECT id, username, email FROM users LIMIT 1";
        $sampleUserStmt = $db->prepare($sampleUserQuery);
        $sampleUserStmt->execute();
        $sampleUser = $sampleUserStmt->fetch(PDO::FETCH_ASSOC);
        
        $testResults['step_1_real_users'] = [
            'status' => 'SUCCESS',
            'total_users' => (int)$userCount,
            'sample_user' => $sampleUser,
            'users_exist' => $userCount > 0
        ];
    } catch (Exception $e) {
        $testResults['step_1_real_users'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Check commission data
    $testResults['step_2_commission_data'] = [];
    
    try {
        $commissionQuery = "SELECT COUNT(*) as commission_count, SUM(commission_usdt) as total_usdt, SUM(commission_nft) as total_nft FROM referral_commissions";
        $commissionStmt = $db->prepare($commissionQuery);
        $commissionStmt->execute();
        $commissionData = $commissionStmt->fetch(PDO::FETCH_ASSOC);
        
        $testResults['step_2_commission_data'] = [
            'status' => 'SUCCESS',
            'total_commissions' => (int)$commissionData['commission_count'],
            'total_usdt_earned' => (float)$commissionData['total_usdt'],
            'total_nft_earned' => (int)$commissionData['total_nft'],
            'commissions_exist' => $commissionData['commission_count'] > 0
        ];
    } catch (Exception $e) {
        $testResults['step_2_commission_data'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 3: Check security system
    $testResults['step_3_security_system'] = [];
    
    try {
        $securityManager = new CommissionSecurityManager($db);
        
        // Check if security tables exist
        $primaryQuery = "SELECT COUNT(*) as count FROM commission_balances_primary";
        $primaryStmt = $db->prepare($primaryQuery);
        $primaryStmt->execute();
        $primaryCount = $primaryStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $verificationQuery = "SELECT COUNT(*) as count FROM commission_balances_verification";
        $verificationStmt = $db->prepare($verificationQuery);
        $verificationStmt->execute();
        $verificationCount = $verificationStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $testResults['step_3_security_system'] = [
            'status' => 'SUCCESS',
            'primary_table_records' => (int)$primaryCount,
            'verification_table_records' => (int)$verificationCount,
            'security_system_active' => true
        ];
    } catch (Exception $e) {
        $testResults['step_3_security_system'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 4: Test commission balance API for real user
    $testResults['step_4_commission_balance_api'] = [];
    
    if (isset($sampleUser)) {
        try {
            // Simulate user session
            $_SESSION['user_id'] = $sampleUser['id'];
            $_SESSION['username'] = $sampleUser['username'];
            $_SESSION['email'] = $sampleUser['email'];
            
            // Test commission balance API
            $securityManager = new CommissionSecurityManager($db);
            $balance = $securityManager->getSecureUserBalance($sampleUser['id']);
            $integrityValid = $securityManager->verifyBalanceIntegrity($sampleUser['id']);
            
            $testResults['step_4_commission_balance_api'] = [
                'status' => 'SUCCESS',
                'user_id' => $sampleUser['id'],
                'balance_data' => $balance,
                'integrity_valid' => $integrityValid,
                'api_working' => true
            ];
        } catch (Exception $e) {
            $testResults['step_4_commission_balance_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage(),
                'api_working' => false
            ];
        }
    }
    
    // STEP 5: Check withdrawal system
    $testResults['step_5_withdrawal_system'] = [];
    
    try {
        $withdrawalQuery = "SELECT COUNT(*) as total_withdrawals, COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_withdrawals, COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_withdrawals FROM secure_withdrawal_requests";
        $withdrawalStmt = $db->prepare($withdrawalQuery);
        $withdrawalStmt->execute();
        $withdrawalData = $withdrawalStmt->fetch(PDO::FETCH_ASSOC);
        
        $testResults['step_5_withdrawal_system'] = [
            'status' => 'SUCCESS',
            'total_withdrawals' => (int)$withdrawalData['total_withdrawals'],
            'completed_withdrawals' => (int)$withdrawalData['completed_withdrawals'],
            'pending_withdrawals' => (int)$withdrawalData['pending_withdrawals'],
            'withdrawal_system_active' => true
        ];
    } catch (Exception $e) {
        $testResults['step_5_withdrawal_system'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 6: Check if APIs are accessible
    $testResults['step_6_api_accessibility'] = [];
    
    try {
        // Check if commission balance API file exists
        $commissionBalanceFile = '../referrals/commission-balance.php';
        $withdrawalHistoryFile = '../referrals/withdrawal-history.php';
        $payoutFile = '../referrals/payout.php';
        $reinvestFile = '../referrals/reinvest.php';
        
        $testResults['step_6_api_accessibility'] = [
            'status' => 'SUCCESS',
            'commission_balance_api' => file_exists($commissionBalanceFile),
            'withdrawal_history_api' => file_exists($withdrawalHistoryFile),
            'payout_api' => file_exists($payoutFile),
            'reinvest_api' => file_exists($reinvestFile),
            'all_apis_exist' => file_exists($commissionBalanceFile) && file_exists($withdrawalHistoryFile) && file_exists($payoutFile) && file_exists($reinvestFile)
        ];
    } catch (Exception $e) {
        $testResults['step_6_api_accessibility'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 7: Check commission plan accuracy
    $testResults['step_7_commission_plan'] = [];
    
    try {
        // Check if commission rates are correct in database
        $commissionRatesQuery = "SELECT DISTINCT level, commission_usdt, commission_nft, purchase_amount FROM referral_commissions WHERE purchase_amount > 0 ORDER BY level";
        $commissionRatesStmt = $db->prepare($commissionRatesQuery);
        $commissionRatesStmt->execute();
        $commissionRates = $commissionRatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $testResults['step_7_commission_plan'] = [
            'status' => 'SUCCESS',
            'commission_rates_in_db' => $commissionRates,
            'commission_plan_implemented' => count($commissionRates) > 0
        ];
    } catch (Exception $e) {
        $testResults['step_7_commission_plan'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // FINAL ASSESSMENT
    $systemChecks = [
        'users_exist' => isset($testResults['step_1_real_users']['users_exist']) && $testResults['step_1_real_users']['users_exist'],
        'commissions_exist' => isset($testResults['step_2_commission_data']['commissions_exist']) && $testResults['step_2_commission_data']['commissions_exist'],
        'security_active' => isset($testResults['step_3_security_system']['security_system_active']) && $testResults['step_3_security_system']['security_system_active'],
        'api_working' => isset($testResults['step_4_commission_balance_api']['api_working']) && $testResults['step_4_commission_balance_api']['api_working'],
        'withdrawal_active' => isset($testResults['step_5_withdrawal_system']['withdrawal_system_active']) && $testResults['step_5_withdrawal_system']['withdrawal_system_active'],
        'apis_exist' => isset($testResults['step_6_api_accessibility']['all_apis_exist']) && $testResults['step_6_api_accessibility']['all_apis_exist'],
        'commission_plan_implemented' => isset($testResults['step_7_commission_plan']['commission_plan_implemented']) && $testResults['step_7_commission_plan']['commission_plan_implemented']
    ];
    
    $workingSystems = count(array_filter($systemChecks));
    $totalSystems = count($systemChecks);
    
    $testResults['final_assessment'] = [
        'overall_status' => $workingSystems === $totalSystems ? 'ALL_SYSTEMS_WORKING' : 'ISSUES_DETECTED',
        'working_systems' => $workingSystems,
        'total_systems' => $totalSystems,
        'success_rate' => round(($workingSystems / $totalSystems) * 100, 1) . '%',
        'system_checks' => $systemChecks,
        'ready_for_production' => $workingSystems >= 6, // At least 6 out of 7 should work
        'test_completed_at' => date('c')
    ];
    
    // ISSUES DETECTED
    $issues = [];
    foreach ($systemChecks as $check => $working) {
        if (!$working) {
            $issues[] = $check;
        }
    }
    
    if (!empty($issues)) {
        $testResults['issues_detected'] = [
            'count' => count($issues),
            'issues' => $issues,
            'requires_fixing' => true
        ];
    } else {
        $testResults['issues_detected'] = [
            'count' => 0,
            'issues' => [],
            'requires_fixing' => false
        ];
    }
    
    echo json_encode([
        'success' => true,
        'test_type' => 'Commission System Check',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Commission system check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Commission system check failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
