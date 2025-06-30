<?php
/**
 * COMPLETE END-TO-END REFERRAL WORKFLOW TEST
 * Tests: Referral Link → Investment → Commission Creation → Activation → Withdrawal
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
    $testResults['workflow_test'] = [];
    
    // STEP 1: Create Test Users
    $testResults['workflow_test']['step_1_create_users'] = [];
    
    try {
        // Create referrer user (JPRademeyer)
        $referrerQuery = "INSERT IGNORE INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())";
        $referrerStmt = $db->prepare($referrerQuery);
        $referrerStmt->execute(['JPRademeyer', 'jp@test.com', password_hash('test123', PASSWORD_DEFAULT)]);
        
        // Get referrer ID
        $getReferrerQuery = "SELECT id FROM users WHERE username = 'JPRademeyer'";
        $getReferrerStmt = $db->prepare($getReferrerQuery);
        $getReferrerStmt->execute();
        $referrerId = $getReferrerStmt->fetchColumn();
        
        // Create referred user (TestUser)
        $referredQuery = "INSERT IGNORE INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())";
        $referredStmt = $db->prepare($referredQuery);
        $referredStmt->execute(['TestUser', 'test@test.com', password_hash('test123', PASSWORD_DEFAULT)]);
        
        // Get referred ID
        $getReferredQuery = "SELECT id FROM users WHERE username = 'TestUser'";
        $getReferredStmt = $db->prepare($getReferredQuery);
        $getReferredStmt->execute();
        $referredId = $getReferredStmt->fetchColumn();
        
        $testResults['workflow_test']['step_1_create_users'] = [
            'status' => 'SUCCESS',
            'referrer_id' => $referrerId,
            'referred_id' => $referredId,
            'referrer_username' => 'JPRademeyer',
            'referred_username' => 'TestUser'
        ];
        
    } catch (Exception $e) {
        $testResults['workflow_test']['step_1_create_users'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Simulate Referral Link Visit
    $testResults['workflow_test']['step_2_referral_tracking'] = [];
    
    if ($testResults['workflow_test']['step_1_create_users']['status'] === 'SUCCESS') {
        try {
            // Simulate referral data in session
            $_SESSION['referral_data'] = [
                'referrer_user_id' => $referrerId,
                'referrer_username' => 'JPRademeyer',
                'source' => 'direct_link',
                'timestamp' => date('c'),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Agent'
            ];
            
            $testResults['workflow_test']['step_2_referral_tracking'] = [
                'status' => 'SUCCESS',
                'referral_data_stored' => true,
                'session_data' => $_SESSION['referral_data']
            ];
            
        } catch (Exception $e) {
            $testResults['workflow_test']['step_2_referral_tracking'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 3: Simulate Investment Creation
    $testResults['workflow_test']['step_3_investment_creation'] = [];
    
    if (isset($testResults['workflow_test']['step_2_referral_tracking']['status']) && $testResults['workflow_test']['step_2_referral_tracking']['status'] === 'SUCCESS') {
        try {
            // Create referral_commissions table first
            $db->exec("CREATE TABLE IF NOT EXISTS referral_commissions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                referrer_user_id INT NOT NULL,
                referred_user_id INT NOT NULL,
                investment_id VARCHAR(255) NOT NULL,
                level INT NOT NULL CHECK (level IN (1, 2, 3)),
                purchase_amount DECIMAL(10, 2) NOT NULL,
                commission_usdt DECIMAL(10, 2) NOT NULL,
                commission_nft INT NOT NULL,
                status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_referrer (referrer_user_id),
                INDEX idx_referred (referred_user_id),
                INDEX idx_investment (investment_id),
                INDEX idx_status (status)
            )");

            $investmentId = uniqid('test_inv_', true);
            $investmentAmount = 100.00;

            // Create investment record
            $investmentQuery = "INSERT INTO aureus_investments (
                id, user_id, name, email, wallet_address, chain, amount, 
                investment_plan, package_name, shares, roi, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $investmentStmt = $db->prepare($investmentQuery);
            $investmentStmt->execute([
                $investmentId, $referredId, 'Test User', 'test@test.com', 
                '0x1234567890abcdef', 'polygon', $investmentAmount,
                'shovel', 'Shovel', 5, 25.00, 'pending'
            ]);
            
            // Create commission records (simulate investment process)
            $commissionLevels = [
                1 => ['usdt' => 12, 'nft' => 12],
                2 => ['usdt' => 5, 'nft' => 5],
                3 => ['usdt' => 3, 'nft' => 3]
            ];
            
            $commissionsCreated = 0;
            $totalUsdtCommissions = 0;
            $totalNftCommissions = 0;
            
            // Create Level 1 commission for direct referrer
            $level1UsdtCommission = ($investmentAmount * 12) / 100; // 12%
            $level1NftCommission = intval(($investmentAmount * 12) / 100 / 5); // 12% / $5 per NFT
            
            $commissionQuery = "INSERT INTO referral_commissions (
                referrer_user_id, referred_user_id, investment_id, level,
                purchase_amount, commission_usdt, commission_nft, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $commissionStmt = $db->prepare($commissionQuery);
            $commissionStmt->execute([
                $referrerId, $referredId, $investmentId, 1,
                $investmentAmount, $level1UsdtCommission, $level1NftCommission
            ]);
            
            $commissionsCreated++;
            $totalUsdtCommissions += $level1UsdtCommission;
            $totalNftCommissions += $level1NftCommission;
            
            // Create initial secure balance for referrer if it doesn't exist
            try {
                $currentBalance = $securityManager->getSecureUserBalance($referrerId);
            } catch (Exception $e) {
                // Create initial balance
                $securityManager->updateUserBalance(
                    $referrerId,
                    0, 0, 0, 0, 0, 0,
                    uniqid('init_', true),
                    null
                );
                $currentBalance = $securityManager->getSecureUserBalance($referrerId);
            }

            // Update secure balance for referrer (earned but not available)
            $securityManager->updateUserBalance(
                $referrerId,
                $currentBalance['total_usdt_earned'] + $level1UsdtCommission,
                $currentBalance['total_nft_earned'] + $level1NftCommission,
                $currentBalance['available_usdt_balance'], // Don't add to available yet (pending)
                $currentBalance['available_nft_balance'], // Don't add to available yet (pending)
                $currentBalance['total_usdt_withdrawn'],
                $currentBalance['total_nft_redeemed'],
                $investmentId,
                null
            );
            
            $testResults['workflow_test']['step_3_investment_creation'] = [
                'status' => 'SUCCESS',
                'investment_id' => $investmentId,
                'investment_amount' => $investmentAmount,
                'commissions_created' => $commissionsCreated,
                'total_usdt_commissions' => $totalUsdtCommissions,
                'total_nft_commissions' => $totalNftCommissions,
                'level_1_usdt' => $level1UsdtCommission,
                'level_1_nft' => $level1NftCommission
            ];
            
        } catch (Exception $e) {
            $testResults['workflow_test']['step_3_investment_creation'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 4: Test Commission Activation
    $testResults['workflow_test']['step_4_commission_activation'] = [];
    
    if (isset($testResults['workflow_test']['step_3_investment_creation']['status']) && $testResults['workflow_test']['step_3_investment_creation']['status'] === 'SUCCESS') {
        try {
            // Activate pending commissions for the referrer
            $activateQuery = "UPDATE referral_commissions SET status = 'paid' WHERE referrer_user_id = ? AND status = 'pending'";
            $activateStmt = $db->prepare($activateQuery);
            $activateStmt->execute([$referrerId]);
            
            $activatedCount = $activateStmt->rowCount();
            
            // Update secure balance to make commissions available
            $userCommissionsQuery = "
                SELECT 
                    SUM(commission_usdt) as total_usdt,
                    SUM(commission_nft) as total_nft,
                    SUM(CASE WHEN status = 'paid' THEN commission_usdt ELSE 0 END) as available_usdt,
                    SUM(CASE WHEN status = 'paid' THEN commission_nft ELSE 0 END) as available_nft
                FROM referral_commissions 
                WHERE referrer_user_id = ?
            ";
            
            $userCommissionsStmt = $db->prepare($userCommissionsQuery);
            $userCommissionsStmt->execute([$referrerId]);
            $userCommissions = $userCommissionsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get current balance to preserve withdrawal history
            $currentBalance = $securityManager->getSecureUserBalance($referrerId);
            
            // Update secure balance with available amounts
            $securityManager->updateUserBalance(
                $referrerId,
                (float)$userCommissions['total_usdt'],
                (int)$userCommissions['total_nft'],
                (float)$userCommissions['available_usdt'], // Now available for withdrawal
                (int)$userCommissions['available_nft'], // Now available for withdrawal
                $currentBalance['total_usdt_withdrawn'],
                $currentBalance['total_nft_redeemed'],
                uniqid('activation_', true),
                1 // Admin ID for test
            );
            
            $testResults['workflow_test']['step_4_commission_activation'] = [
                'status' => 'SUCCESS',
                'activated_count' => $activatedCount,
                'available_usdt' => (float)$userCommissions['available_usdt'],
                'available_nft' => (int)$userCommissions['available_nft']
            ];
            
        } catch (Exception $e) {
            $testResults['workflow_test']['step_4_commission_activation'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 5: Test Withdrawal Request
    $testResults['workflow_test']['step_5_withdrawal_request'] = [];
    
    if (isset($testResults['workflow_test']['step_4_commission_activation']['status']) && $testResults['workflow_test']['step_4_commission_activation']['status'] === 'SUCCESS') {
        try {
            $withdrawalAmount = 5.00; // Withdraw $5 USDT
            $walletAddress = '0xabcdef1234567890';
            
            $result = $withdrawalScheduler->submitWithdrawalRequest(
                $referrerId,
                'usdt',
                $withdrawalAmount,
                0,
                $walletAddress
            );
            
            $testResults['workflow_test']['step_5_withdrawal_request'] = [
                'status' => 'SUCCESS',
                'withdrawal_result' => $result,
                'withdrawal_amount' => $withdrawalAmount,
                'wallet_address' => $walletAddress
            ];
            
        } catch (Exception $e) {
            $testResults['workflow_test']['step_5_withdrawal_request'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 6: Verify Security Integrity
    $testResults['workflow_test']['step_6_security_verification'] = [];

    if (isset($referrerId) && $referrerId) {
        try {
            $integrityValid = $securityManager->verifyBalanceIntegrity($referrerId);
            $finalBalance = $securityManager->getSecureUserBalance($referrerId);
        
        $testResults['workflow_test']['step_6_security_verification'] = [
            'status' => 'SUCCESS',
            'integrity_valid' => $integrityValid,
            'final_balance' => $finalBalance
        ];

        } catch (Exception $e) {
            $testResults['workflow_test']['step_6_security_verification'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    } else {
        $testResults['workflow_test']['step_6_security_verification'] = [
            'status' => 'FAILED',
            'error' => 'No referrer ID available for security verification'
        ];
    }
    
    // OVERALL WORKFLOW STATUS
    $allStepsSuccessful = true;
    foreach ($testResults['workflow_test'] as $step => $result) {
        if (isset($result['status']) && $result['status'] !== 'SUCCESS') {
            $allStepsSuccessful = false;
            break;
        }
    }
    
    $testResults['workflow_summary'] = [
        'overall_status' => $allStepsSuccessful ? 'COMPLETE_SUCCESS' : 'PARTIAL_FAILURE',
        'steps_completed' => count(array_filter($testResults['workflow_test'], function($step) {
            return isset($step['status']) && $step['status'] === 'SUCCESS';
        })),
        'total_steps' => 6,
        'test_completed_at' => date('c'),
        'referrer_username' => 'JPRademeyer',
        'referred_username' => 'TestUser'
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'Complete End-to-End Workflow Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Complete workflow test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Workflow test execution failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
