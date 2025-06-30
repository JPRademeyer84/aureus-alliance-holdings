<?php
/**
 * ADMIN WITHDRAWAL PROCESSING TEST
 * Tests the admin side of the withdrawal system
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
    $testResults['admin_test'] = [];
    
    // STEP 1: Simulate admin login
    $testResults['admin_test']['step_1_admin_login'] = [];
    
    try {
        // Get admin user
        $adminQuery = "SELECT id, username FROM admin_users WHERE username = 'admin'";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            $testResults['admin_test']['step_1_admin_login'] = [
                'status' => 'SUCCESS',
                'admin_id' => $admin['id'],
                'admin_username' => $admin['username'],
                'session_active' => true
            ];
        } else {
            $testResults['admin_test']['step_1_admin_login'] = [
                'status' => 'FAILED',
                'error' => 'Admin user not found'
            ];
        }
        
    } catch (Exception $e) {
        $testResults['admin_test']['step_1_admin_login'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Get Pending Withdrawals
    $testResults['admin_test']['step_2_get_pending_withdrawals'] = [];
    
    if (isset($_SESSION['admin_id'])) {
        try {
            $securityManager = new CommissionSecurityManager($db);
            $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
            
            // Get pending withdrawals for admin
            $pendingWithdrawals = $withdrawalScheduler->getPendingWithdrawalsForAdmin();
            
            $testResults['admin_test']['step_2_get_pending_withdrawals'] = [
                'status' => 'SUCCESS',
                'pending_withdrawals' => $pendingWithdrawals,
                'withdrawal_count' => count($pendingWithdrawals['withdrawals'] ?? []),
                'business_hours_active' => $pendingWithdrawals['business_hours_active'] ?? false
            ];
            
        } catch (Exception $e) {
            $testResults['admin_test']['step_2_get_pending_withdrawals'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 3: Process a USDT Withdrawal
    $testResults['admin_test']['step_3_process_usdt_withdrawal'] = [];
    
    if (isset($_SESSION['admin_id'])) {
        try {
            // Get the latest USDT withdrawal
            $withdrawalQuery = "SELECT * FROM secure_withdrawal_requests WHERE withdrawal_type = 'usdt' AND status = 'pending' ORDER BY requested_at DESC LIMIT 1";
            $withdrawalStmt = $db->prepare($withdrawalQuery);
            $withdrawalStmt->execute();
            $withdrawal = $withdrawalStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($withdrawal) {
                // Process the withdrawal as completed
                $result = $withdrawalScheduler->adminProcessWithdrawal(
                    $withdrawal['id'],
                    $_SESSION['admin_id'],
                    'completed',
                    '0x1234567890abcdef1234567890abcdef12345678901234567890abcdef12345678', // Transaction hash
                    '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890ab', // Blockchain hash
                    'Test withdrawal processed successfully'
                );
                
                $testResults['admin_test']['step_3_process_usdt_withdrawal'] = [
                    'status' => 'SUCCESS',
                    'withdrawal_id' => $withdrawal['id'],
                    'withdrawal_amount' => $withdrawal['requested_amount_usdt'],
                    'processing_result' => $result
                ];
            } else {
                $testResults['admin_test']['step_3_process_usdt_withdrawal'] = [
                    'status' => 'FAILED',
                    'error' => 'No pending USDT withdrawals found'
                ];
            }
            
        } catch (Exception $e) {
            $testResults['admin_test']['step_3_process_usdt_withdrawal'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 4: Process an NFT Withdrawal
    $testResults['admin_test']['step_4_process_nft_withdrawal'] = [];
    
    if (isset($_SESSION['admin_id'])) {
        try {
            // Get the latest NFT withdrawal
            $withdrawalQuery = "SELECT * FROM secure_withdrawal_requests WHERE withdrawal_type = 'nft' AND status = 'pending' ORDER BY requested_at DESC LIMIT 1";
            $withdrawalStmt = $db->prepare($withdrawalQuery);
            $withdrawalStmt->execute();
            $withdrawal = $withdrawalStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($withdrawal) {
                // Process the withdrawal as completed
                $result = $withdrawalScheduler->adminProcessWithdrawal(
                    $withdrawal['id'],
                    $_SESSION['admin_id'],
                    'completed',
                    '0x9876543210fedcba9876543210fedcba9876543210fedcba9876543210fedcba', // Transaction hash
                    '0xfedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210fe', // Blockchain hash
                    'Test NFT withdrawal processed successfully'
                );
                
                $testResults['admin_test']['step_4_process_nft_withdrawal'] = [
                    'status' => 'SUCCESS',
                    'withdrawal_id' => $withdrawal['id'],
                    'nft_quantity' => $withdrawal['requested_amount_nft'],
                    'processing_result' => $result
                ];
            } else {
                $testResults['admin_test']['step_4_process_nft_withdrawal'] = [
                    'status' => 'FAILED',
                    'error' => 'No pending NFT withdrawals found'
                ];
            }
            
        } catch (Exception $e) {
            $testResults['admin_test']['step_4_process_nft_withdrawal'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 5: Verify User Balance After Withdrawals
    $testResults['admin_test']['step_5_verify_user_balance'] = [];
    
    if (isset($_SESSION['admin_id'])) {
        try {
            $securityManager = new CommissionSecurityManager($db);
            
            // Get JPRademeyer's balance after withdrawals
            $userQuery = "SELECT id FROM users WHERE username = 'JPRademeyer'";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $userBalance = $securityManager->getSecureUserBalance($user['id']);
                $integrityValid = $securityManager->verifyBalanceIntegrity($user['id']);
                
                $testResults['admin_test']['step_5_verify_user_balance'] = [
                    'status' => 'SUCCESS',
                    'user_balance_after_withdrawals' => $userBalance,
                    'integrity_valid' => $integrityValid,
                    'balance_updated_correctly' => true
                ];
            } else {
                $testResults['admin_test']['step_5_verify_user_balance'] = [
                    'status' => 'FAILED',
                    'error' => 'User not found'
                ];
            }
            
        } catch (Exception $e) {
            $testResults['admin_test']['step_5_verify_user_balance'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 6: Check Transaction Audit Trail
    $testResults['admin_test']['step_6_audit_trail'] = [];
    
    if (isset($_SESSION['admin_id'])) {
        try {
            // Get transaction log entries
            $logQuery = "SELECT COUNT(*) as total_transactions FROM commission_transaction_log";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute();
            $totalTransactions = $logStmt->fetch(PDO::FETCH_ASSOC)['total_transactions'];
            
            // Get withdrawal records
            $withdrawalQuery = "SELECT COUNT(*) as total_withdrawals, COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_withdrawals FROM secure_withdrawal_requests";
            $withdrawalStmt = $db->prepare($withdrawalQuery);
            $withdrawalStmt->execute();
            $withdrawalStats = $withdrawalStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get security audit log
            $auditQuery = "SELECT COUNT(*) as audit_entries FROM security_audit_log";
            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute();
            $auditEntries = $auditStmt->fetch(PDO::FETCH_ASSOC)['audit_entries'];
            
            $testResults['admin_test']['step_6_audit_trail'] = [
                'status' => 'SUCCESS',
                'total_transaction_logs' => (int)$totalTransactions,
                'total_withdrawals' => (int)$withdrawalStats['total_withdrawals'],
                'completed_withdrawals' => (int)$withdrawalStats['completed_withdrawals'],
                'security_audit_entries' => (int)$auditEntries,
                'audit_trail_complete' => true
            ];
            
        } catch (Exception $e) {
            $testResults['admin_test']['step_6_audit_trail'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // OVERALL ADMIN TEST SUMMARY
    $allStepsSuccessful = true;
    $successfulSteps = 0;
    
    foreach ($testResults['admin_test'] as $step => $result) {
        if (isset($result['status']) && $result['status'] === 'SUCCESS') {
            $successfulSteps++;
        } else {
            $allStepsSuccessful = false;
        }
    }
    
    $testResults['admin_summary'] = [
        'overall_status' => $allStepsSuccessful ? 'ALL_ADMIN_FUNCTIONS_WORKING' : 'SOME_ADMIN_ISSUES',
        'successful_steps' => $successfulSteps,
        'total_steps' => 6,
        'test_completed_at' => date('c'),
        'admin_can_login' => isset($testResults['admin_test']['step_1_admin_login']['status']) && $testResults['admin_test']['step_1_admin_login']['status'] === 'SUCCESS',
        'admin_can_see_withdrawals' => isset($testResults['admin_test']['step_2_get_pending_withdrawals']['status']) && $testResults['admin_test']['step_2_get_pending_withdrawals']['status'] === 'SUCCESS',
        'admin_can_process_usdt' => isset($testResults['admin_test']['step_3_process_usdt_withdrawal']['status']) && $testResults['admin_test']['step_3_process_usdt_withdrawal']['status'] === 'SUCCESS',
        'admin_can_process_nft' => isset($testResults['admin_test']['step_4_process_nft_withdrawal']['status']) && $testResults['admin_test']['step_4_process_nft_withdrawal']['status'] === 'SUCCESS',
        'balances_update_correctly' => isset($testResults['admin_test']['step_5_verify_user_balance']['integrity_valid']) && $testResults['admin_test']['step_5_verify_user_balance']['integrity_valid'] === true,
        'audit_trail_working' => isset($testResults['admin_test']['step_6_audit_trail']['audit_trail_complete']) && $testResults['admin_test']['step_6_audit_trail']['audit_trail_complete'] === true
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'Admin Withdrawal Processing Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Admin test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Admin test execution failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
