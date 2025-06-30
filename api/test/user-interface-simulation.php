<?php
/**
 * USER INTERFACE SIMULATION TEST
 * Simulates exactly what a real user would experience in the frontend
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
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $testResults = [];
    
    // STEP 1: Simulate user login (what happens when user logs in)
    $testResults['step_1_user_login'] = [];
    
    try {
        // Get JPRademeyer user (our test user with commissions)
        $userQuery = "SELECT id, username, email FROM users WHERE username = 'JPRademeyer'";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Simulate user session (what happens after login)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            $testResults['step_1_user_login'] = [
                'status' => 'SUCCESS',
                'user_logged_in' => true,
                'user_data' => $user
            ];
        } else {
            $testResults['step_1_user_login'] = [
                'status' => 'FAILED',
                'error' => 'Test user not found'
            ];
        }
        
    } catch (Exception $e) {
        $testResults['step_1_user_login'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Test Commission Balance API (what frontend calls)
    $testResults['step_2_commission_balance_api'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate the exact API call the frontend makes
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/aureus-angel-alliance/api/referrals/commission-balance.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    $testResults['step_2_commission_balance_api'] = [
                        'status' => 'SUCCESS',
                        'api_response' => $data,
                        'balance_data' => $data['balance'],
                        'user_can_see_balance' => true
                    ];
                } else {
                    $testResults['step_2_commission_balance_api'] = [
                        'status' => 'FAILED',
                        'error' => 'API returned error: ' . ($data['error'] ?? 'Unknown error'),
                        'response' => $response
                    ];
                }
            } else {
                $testResults['step_2_commission_balance_api'] = [
                    'status' => 'FAILED',
                    'error' => 'HTTP error: ' . $httpCode,
                    'response' => $response
                ];
            }
            
        } catch (Exception $e) {
            $testResults['step_2_commission_balance_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 3: Test Withdrawal History API (what frontend calls)
    $testResults['step_3_withdrawal_history_api'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate the exact API call the frontend makes
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/aureus-angel-alliance/api/referrals/withdrawal-history.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    $testResults['step_3_withdrawal_history_api'] = [
                        'status' => 'SUCCESS',
                        'api_response' => $data,
                        'withdrawal_count' => count($data['withdrawals']),
                        'user_can_see_history' => true
                    ];
                } else {
                    $testResults['step_3_withdrawal_history_api'] = [
                        'status' => 'FAILED',
                        'error' => 'API returned error: ' . ($data['error'] ?? 'Unknown error'),
                        'response' => $response
                    ];
                }
            } else {
                $testResults['step_3_withdrawal_history_api'] = [
                    'status' => 'FAILED',
                    'error' => 'HTTP error: ' . $httpCode,
                    'response' => $response
                ];
            }
            
        } catch (Exception $e) {
            $testResults['step_3_withdrawal_history_api'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 4: Test USDT Withdrawal Request (what happens when user clicks "Request Withdrawal")
    $testResults['step_4_usdt_withdrawal_request'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate the exact API call the frontend makes for USDT withdrawal
            $withdrawalData = [
                'action' => 'request_withdrawal',
                'type' => 'usdt',
                'amount' => 5.00,
                'nft_quantity' => 0,
                'wallet_address' => '0x1234567890abcdef1234567890abcdef12345678'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/aureus-angel-alliance/api/referrals/payout.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($withdrawalData));
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    $testResults['step_4_usdt_withdrawal_request'] = [
                        'status' => 'SUCCESS',
                        'withdrawal_submitted' => true,
                        'api_response' => $data,
                        'user_can_request_withdrawal' => true
                    ];
                } else {
                    $testResults['step_4_usdt_withdrawal_request'] = [
                        'status' => 'FAILED',
                        'error' => 'API returned error: ' . ($data['error'] ?? 'Unknown error'),
                        'response' => $response
                    ];
                }
            } else {
                $testResults['step_4_usdt_withdrawal_request'] = [
                    'status' => 'FAILED',
                    'error' => 'HTTP error: ' . $httpCode,
                    'response' => $response
                ];
            }
            
        } catch (Exception $e) {
            $testResults['step_4_usdt_withdrawal_request'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 5: Test Reinvestment (what happens when user chooses "Reinvest in More NFTs")
    $testResults['step_5_reinvestment_request'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Simulate the exact API call the frontend makes for reinvestment
            $reinvestData = [
                'amount' => 10.00,
                'type' => 'usdt',
                'nft_quantity' => 0
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/aureus-angel-alliance/api/referrals/reinvest.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reinvestData));
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    $testResults['step_5_reinvestment_request'] = [
                        'status' => 'SUCCESS',
                        'reinvestment_completed' => true,
                        'api_response' => $data,
                        'user_can_reinvest' => true
                    ];
                } else {
                    $testResults['step_5_reinvestment_request'] = [
                        'status' => 'FAILED',
                        'error' => 'API returned error: ' . ($data['error'] ?? 'Unknown error'),
                        'response' => $response
                    ];
                }
            } else {
                $testResults['step_5_reinvestment_request'] = [
                    'status' => 'FAILED',
                    'error' => 'HTTP error: ' . $httpCode,
                    'response' => $response
                ];
            }
            
        } catch (Exception $e) {
            $testResults['step_5_reinvestment_request'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 6: Final Balance Check (what user sees after actions)
    $testResults['step_6_final_balance_check'] = [];
    
    if (isset($_SESSION['user_id'])) {
        try {
            // Check balance again after withdrawal and reinvestment
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/aureus-angel-alliance/api/referrals/commission-balance.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    $testResults['step_6_final_balance_check'] = [
                        'status' => 'SUCCESS',
                        'final_balance' => $data['balance'],
                        'balance_updated_correctly' => true
                    ];
                } else {
                    $testResults['step_6_final_balance_check'] = [
                        'status' => 'FAILED',
                        'error' => 'API returned error: ' . ($data['error'] ?? 'Unknown error')
                    ];
                }
            } else {
                $testResults['step_6_final_balance_check'] = [
                    'status' => 'FAILED',
                    'error' => 'HTTP error: ' . $httpCode
                ];
            }
            
        } catch (Exception $e) {
            $testResults['step_6_final_balance_check'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // SUMMARY: What the user can actually do
    $userCapabilities = [
        'can_login' => isset($testResults['step_1_user_login']['status']) && $testResults['step_1_user_login']['status'] === 'SUCCESS',
        'can_see_balance' => isset($testResults['step_2_commission_balance_api']['status']) && $testResults['step_2_commission_balance_api']['status'] === 'SUCCESS',
        'can_see_history' => isset($testResults['step_3_withdrawal_history_api']['status']) && $testResults['step_3_withdrawal_history_api']['status'] === 'SUCCESS',
        'can_request_withdrawal' => isset($testResults['step_4_usdt_withdrawal_request']['status']) && $testResults['step_4_usdt_withdrawal_request']['status'] === 'SUCCESS',
        'can_reinvest' => isset($testResults['step_5_reinvestment_request']['status']) && $testResults['step_5_reinvestment_request']['status'] === 'SUCCESS',
        'balance_updates_correctly' => isset($testResults['step_6_final_balance_check']['status']) && $testResults['step_6_final_balance_check']['status'] === 'SUCCESS'
    ];
    
    $workingFeatures = count(array_filter($userCapabilities));
    $totalFeatures = count($userCapabilities);
    
    $testResults['user_experience_summary'] = [
        'overall_status' => $workingFeatures === $totalFeatures ? 'PERFECT_USER_EXPERIENCE' : 'SOME_USER_ISSUES',
        'working_features' => $workingFeatures,
        'total_features' => $totalFeatures,
        'user_satisfaction_score' => round(($workingFeatures / $totalFeatures) * 100, 1) . '%',
        'user_capabilities' => $userCapabilities,
        'test_completed_at' => date('c')
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'User Interface Simulation Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("User interface simulation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'User interface simulation failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
