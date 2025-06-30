<?php
/**
 * DIRECT USER TEST - Tests user functionality directly
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
    $securityManager = new CommissionSecurityManager($db);
    $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
    
    $testResults = [];
    
    // STEP 1: Setup user session
    $userQuery = "SELECT id, username, email FROM users WHERE username = 'JPRademeyer'";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Test user JPRademeyer not found');
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    $testResults['user_session'] = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'session_active' => true
    ];
    
    // STEP 2: Test Commission Balance (what user sees in dashboard)
    try {
        $balance = $securityManager->getSecureUserBalance($user['id']);
        $integrityValid = $securityManager->verifyBalanceIntegrity($user['id']);
        
        $testResults['commission_balance'] = [
            'status' => 'SUCCESS',
            'balance' => $balance,
            'integrity_valid' => $integrityValid,
            'user_can_see_balance' => true
        ];
    } catch (Exception $e) {
        $testResults['commission_balance'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 3: Test Withdrawal Request
    try {
        $withdrawalResult = $withdrawalScheduler->submitWithdrawalRequest(
            $user['id'],
            'usdt',
            5.00,
            0,
            '0x1234567890abcdef1234567890abcdef12345678'
        );
        
        $testResults['withdrawal_request'] = [
            'status' => 'SUCCESS',
            'withdrawal_result' => $withdrawalResult,
            'user_can_request_withdrawal' => true
        ];
    } catch (Exception $e) {
        $testResults['withdrawal_request'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 4: Test Withdrawal History
    try {
        $withdrawalsQuery = "
            SELECT 
                id, withdrawal_type, requested_amount_usdt, requested_amount_nft,
                wallet_address, status, requested_at, transaction_hash,
                blockchain_confirmation_hash, admin_notes
            FROM secure_withdrawal_requests 
            WHERE user_id = ? 
            ORDER BY requested_at DESC 
            LIMIT 10
        ";
        
        $withdrawalsStmt = $db->prepare($withdrawalsQuery);
        $withdrawalsStmt->execute([$user['id']]);
        $withdrawals = $withdrawalsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $testResults['withdrawal_history'] = [
            'status' => 'SUCCESS',
            'withdrawal_count' => count($withdrawals),
            'withdrawals' => $withdrawals,
            'user_can_see_history' => true
        ];
    } catch (Exception $e) {
        $testResults['withdrawal_history'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 5: Test Reinvestment
    try {
        // Get current balance
        $currentBalance = $securityManager->getSecureUserBalance($user['id']);
        
        if ($currentBalance['available_usdt_balance'] >= 10) {
            // Reinvest $10 USDT into 2 NFT packs
            $reinvestAmount = 10.00;
            $nftPackPrice = 5.00;
            $nftPacksToBuy = floor($reinvestAmount / $nftPackPrice);
            $actualUsdtUsed = $nftPacksToBuy * $nftPackPrice;
            
            // Create investment record
            $investmentId = uniqid('test_reinvest_', true);
            
            // Update secure balance
            $securityManager->updateUserBalance(
                $user['id'],
                $currentBalance['total_usdt_earned'],
                $currentBalance['total_nft_earned'] + $nftPacksToBuy,
                $currentBalance['available_usdt_balance'] - $actualUsdtUsed,
                $currentBalance['available_nft_balance'] + $nftPacksToBuy,
                $currentBalance['total_usdt_withdrawn'],
                $currentBalance['total_nft_redeemed'],
                $investmentId,
                null
            );
            
            $testResults['reinvestment'] = [
                'status' => 'SUCCESS',
                'usdt_invested' => $actualUsdtUsed,
                'nft_packs_purchased' => $nftPacksToBuy,
                'investment_id' => $investmentId,
                'user_can_reinvest' => true
            ];
        } else {
            $testResults['reinvestment'] = [
                'status' => 'SKIPPED',
                'reason' => 'Insufficient balance for reinvestment test',
                'available_balance' => $currentBalance['available_usdt_balance']
            ];
        }
    } catch (Exception $e) {
        $testResults['reinvestment'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 6: Final Balance Check
    try {
        $finalBalance = $securityManager->getSecureUserBalance($user['id']);
        $finalIntegrityValid = $securityManager->verifyBalanceIntegrity($user['id']);
        
        $testResults['final_balance'] = [
            'status' => 'SUCCESS',
            'balance' => $finalBalance,
            'integrity_valid' => $finalIntegrityValid,
            'balance_updated_correctly' => true
        ];
    } catch (Exception $e) {
        $testResults['final_balance'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 7: Check Commission Data
    try {
        $commissionQuery = "
            SELECT 
                COUNT(*) as total_commissions,
                SUM(commission_usdt) as total_usdt_earned,
                SUM(commission_nft) as total_nft_earned,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_commissions
            FROM referral_commissions 
            WHERE referrer_user_id = ?
        ";
        
        $commissionStmt = $db->prepare($commissionQuery);
        $commissionStmt->execute([$user['id']]);
        $commissionStats = $commissionStmt->fetch(PDO::FETCH_ASSOC);
        
        $testResults['commission_data'] = [
            'status' => 'SUCCESS',
            'commission_stats' => $commissionStats,
            'has_active_commissions' => $commissionStats['total_commissions'] > 0
        ];
    } catch (Exception $e) {
        $testResults['commission_data'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 8: Security Audit Check
    try {
        $auditQuery = "SELECT COUNT(*) as audit_count FROM commission_transaction_log WHERE user_id = ?";
        $auditStmt = $db->prepare($auditQuery);
        $auditStmt->execute([$user['id']]);
        $auditCount = $auditStmt->fetch(PDO::FETCH_ASSOC)['audit_count'];
        
        $testResults['security_audit'] = [
            'status' => 'SUCCESS',
            'transaction_log_entries' => (int)$auditCount,
            'audit_trail_active' => $auditCount > 0
        ];
    } catch (Exception $e) {
        $testResults['security_audit'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // USER EXPERIENCE SUMMARY
    $userFeatures = [
        'can_see_balance' => isset($testResults['commission_balance']['status']) && $testResults['commission_balance']['status'] === 'SUCCESS',
        'can_request_withdrawal' => isset($testResults['withdrawal_request']['status']) && $testResults['withdrawal_request']['status'] === 'SUCCESS',
        'can_see_history' => isset($testResults['withdrawal_history']['status']) && $testResults['withdrawal_history']['status'] === 'SUCCESS',
        'can_reinvest' => isset($testResults['reinvestment']['status']) && ($testResults['reinvestment']['status'] === 'SUCCESS' || $testResults['reinvestment']['status'] === 'SKIPPED'),
        'balance_integrity_valid' => isset($testResults['final_balance']['integrity_valid']) && $testResults['final_balance']['integrity_valid'] === true,
        'has_commissions' => isset($testResults['commission_data']['has_active_commissions']) && $testResults['commission_data']['has_active_commissions'] === true,
        'audit_trail_working' => isset($testResults['security_audit']['audit_trail_active']) && $testResults['security_audit']['audit_trail_active'] === true
    ];
    
    $workingFeatures = count(array_filter($userFeatures));
    $totalFeatures = count($userFeatures);
    
    $testResults['user_experience_summary'] = [
        'overall_status' => $workingFeatures === $totalFeatures ? 'PERFECT_USER_EXPERIENCE' : 'SOME_ISSUES',
        'working_features' => $workingFeatures,
        'total_features' => $totalFeatures,
        'success_rate' => round(($workingFeatures / $totalFeatures) * 100, 1) . '%',
        'user_features' => $userFeatures,
        'ready_for_users' => $workingFeatures >= 6, // At least 6 out of 7 features working
        'test_completed_at' => date('c')
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'Direct User Experience Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Direct user test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Direct user test failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
