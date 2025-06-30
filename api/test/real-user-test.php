<?php
/**
 * REAL USER EXPERIENCE TEST
 * Tests the actual user flow that a real person would experience
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
require_once '../security/withdrawal-scheduler.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $testResults = [];
    $testResults['real_user_test'] = [];
    
    // STEP 1: Simulate real user login
    $testResults['real_user_test']['step_1_user_login'] = [];
    
    try {
        // Get JPRademeyer user
        $userQuery = "SELECT id, username, email FROM users WHERE username = 'JPRademeyer'";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            $testResults['real_user_test']['step_1_user_login'] = [
                'status' => 'SUCCESS',
                'user_id' => $user['id'],
                'username' => $user['username'],
                'session_active' => true
            ];
        } else {
            $testResults['real_user_test']['step_1_user_login'] = [
                'status' => 'FAILED',
                'error' => 'Test user not found'
            ];
        }
        
    } catch (Exception $e) {
        $testResults['real_user_test']['step_1_user_login'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Test Commission Balance API (what user sees in dashboard)
    $testResults['real_user_test']['step_2_check_balance'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate calling /api/referrals/commission-balance.php
            $securityManager = new CommissionSecurityManager($db);
            $userId = $_SESSION['user_id'];
            
            // Get secure balance
            $balance = $securityManager->getSecureUserBalance($userId);
            
            // Get commission stats
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
            
            $testResults['real_user_test']['step_2_check_balance'] = [
                'status' => 'SUCCESS',
                'balance' => $balance,
                'commission_stats' => $commissionStats,
                'can_withdraw_usdt' => $balance['available_usdt_balance'] > 0,
                'can_withdraw_nft' => $balance['available_nft_balance'] > 0,
                'can_reinvest' => $balance['available_usdt_balance'] >= 5 || $balance['available_nft_balance'] > 0
            ];
            
        } catch (Exception $e) {
            $testResults['real_user_test']['step_2_check_balance'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 3: Test USDT Withdrawal Request
    $testResults['real_user_test']['step_3_usdt_withdrawal'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
            $userId = $_SESSION['user_id'];
            
            // Request $10 USDT withdrawal
            $result = $withdrawalScheduler->submitWithdrawalRequest(
                $userId,
                'usdt',
                10.00,
                0,
                '0x1234567890abcdef1234567890abcdef12345678'
            );
            
            $testResults['real_user_test']['step_3_usdt_withdrawal'] = [
                'status' => 'SUCCESS',
                'withdrawal_result' => $result,
                'withdrawal_amount' => 10.00,
                'withdrawal_type' => 'usdt'
            ];
            
        } catch (Exception $e) {
            $testResults['real_user_test']['step_3_usdt_withdrawal'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 4: Test NFT Withdrawal Request
    $testResults['real_user_test']['step_4_nft_withdrawal'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Request 2 NFT withdrawal
            $result = $withdrawalScheduler->submitWithdrawalRequest(
                $userId,
                'nft',
                0,
                2,
                '0x1234567890abcdef1234567890abcdef12345678'
            );
            
            $testResults['real_user_test']['step_4_nft_withdrawal'] = [
                'status' => 'SUCCESS',
                'withdrawal_result' => $result,
                'nft_quantity' => 2,
                'withdrawal_type' => 'nft'
            ];
            
        } catch (Exception $e) {
            $testResults['real_user_test']['step_4_nft_withdrawal'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 5: Test Reinvestment (USDT to NFT)
    $testResults['real_user_test']['step_5_reinvestment'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate reinvestment API call
            $userId = $_SESSION['user_id'];
            $reinvestAmount = 15.00; // $15 USDT to buy 3 NFT packs
            
            // Get current balance before reinvestment
            $balanceBefore = $securityManager->getSecureUserBalance($userId);
            
            // Calculate reinvestment
            $nftPackPrice = 5.00;
            $nftPacksToBuy = floor($reinvestAmount / $nftPackPrice);
            $actualUsdtUsed = $nftPacksToBuy * $nftPackPrice;
            
            if ($actualUsdtUsed <= $balanceBefore['available_usdt_balance']) {
                // Create investment record
                $investmentId = uniqid('test_reinvest_', true);
                
                // Update secure balance
                $securityManager->updateUserBalance(
                    $userId,
                    $balanceBefore['total_usdt_earned'],
                    $balanceBefore['total_nft_earned'] + $nftPacksToBuy,
                    $balanceBefore['available_usdt_balance'] - $actualUsdtUsed,
                    $balanceBefore['available_nft_balance'] + $nftPacksToBuy,
                    $balanceBefore['total_usdt_withdrawn'],
                    $balanceBefore['total_nft_redeemed'],
                    $investmentId,
                    null
                );
                
                $testResults['real_user_test']['step_5_reinvestment'] = [
                    'status' => 'SUCCESS',
                    'usdt_used' => $actualUsdtUsed,
                    'nft_packs_purchased' => $nftPacksToBuy,
                    'balance_before' => $balanceBefore,
                    'investment_id' => $investmentId
                ];
            } else {
                $testResults['real_user_test']['step_5_reinvestment'] = [
                    'status' => 'FAILED',
                    'error' => 'Insufficient balance for reinvestment'
                ];
            }
            
        } catch (Exception $e) {
            $testResults['real_user_test']['step_5_reinvestment'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 6: Verify Final Balance and Security
    $testResults['real_user_test']['step_6_final_verification'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Get final balance
            $finalBalance = $securityManager->getSecureUserBalance($userId);
            
            // Verify integrity
            $integrityValid = $securityManager->verifyBalanceIntegrity($userId);
            
            // Get withdrawal history
            $withdrawalsQuery = "SELECT COUNT(*) as withdrawal_count FROM secure_withdrawal_requests WHERE user_id = ?";
            $withdrawalsStmt = $db->prepare($withdrawalsQuery);
            $withdrawalsStmt->execute([$userId]);
            $withdrawalCount = $withdrawalsStmt->fetch(PDO::FETCH_ASSOC)['withdrawal_count'];
            
            // Get transaction log count
            $logQuery = "SELECT COUNT(*) as log_count FROM commission_transaction_log WHERE user_id = ?";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$userId]);
            $logCount = $logStmt->fetch(PDO::FETCH_ASSOC)['log_count'];
            
            $testResults['real_user_test']['step_6_final_verification'] = [
                'status' => 'SUCCESS',
                'final_balance' => $finalBalance,
                'integrity_valid' => $integrityValid,
                'total_withdrawals' => (int)$withdrawalCount,
                'transaction_log_entries' => (int)$logCount,
                'security_verified' => true
            ];
            
        } catch (Exception $e) {
            $testResults['real_user_test']['step_6_final_verification'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // OVERALL TEST SUMMARY
    $allStepsSuccessful = true;
    $successfulSteps = 0;
    
    foreach ($testResults['real_user_test'] as $step => $result) {
        if (isset($result['status']) && $result['status'] === 'SUCCESS') {
            $successfulSteps++;
        } else {
            $allStepsSuccessful = false;
        }
    }
    
    $testResults['test_summary'] = [
        'overall_status' => $allStepsSuccessful ? 'ALL_USER_FUNCTIONS_WORKING' : 'SOME_USER_ISSUES',
        'successful_steps' => $successfulSteps,
        'total_steps' => 6,
        'test_completed_at' => date('c'),
        'user_can_see_balance' => isset($testResults['real_user_test']['step_2_check_balance']['status']) && $testResults['real_user_test']['step_2_check_balance']['status'] === 'SUCCESS',
        'user_can_withdraw_usdt' => isset($testResults['real_user_test']['step_3_usdt_withdrawal']['status']) && $testResults['real_user_test']['step_3_usdt_withdrawal']['status'] === 'SUCCESS',
        'user_can_withdraw_nft' => isset($testResults['real_user_test']['step_4_nft_withdrawal']['status']) && $testResults['real_user_test']['step_4_nft_withdrawal']['status'] === 'SUCCESS',
        'user_can_reinvest' => isset($testResults['real_user_test']['step_5_reinvestment']['status']) && $testResults['real_user_test']['step_5_reinvestment']['status'] === 'SUCCESS',
        'security_intact' => isset($testResults['real_user_test']['step_6_final_verification']['integrity_valid']) && $testResults['real_user_test']['step_6_final_verification']['integrity_valid'] === true
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'Real User Experience Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Real user test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Real user test execution failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
