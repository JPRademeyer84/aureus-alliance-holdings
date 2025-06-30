<?php
/**
 * RUN PLAN CLEANUP TEST
 * Simulates admin login and runs the cleanup script
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
    
    // STEP 1: Simulate admin login
    $testResults['step_1_admin_login'] = [];
    
    try {
        // Get admin user
        $adminQuery = "SELECT id, username FROM admin_users WHERE username = 'admin' LIMIT 1";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Set admin session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            $testResults['step_1_admin_login'] = [
                'status' => 'SUCCESS',
                'admin_logged_in' => true,
                'admin_id' => $admin['id'],
                'admin_username' => $admin['username']
            ];
        } else {
            $testResults['step_1_admin_login'] = [
                'status' => 'FAILED',
                'error' => 'Admin user not found'
            ];
        }
        
    } catch (Exception $e) {
        $testResults['step_1_admin_login'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Check plans before cleanup
    $testResults['step_2_before_cleanup'] = [];
    
    try {
        $beforeQuery = "SELECT COUNT(*) as total FROM investment_packages";
        $beforeStmt = $db->prepare($beforeQuery);
        $beforeStmt->execute();
        $beforeCount = $beforeStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $duplicatesQuery = "
            SELECT 
                name, COUNT(*) as count
            FROM investment_packages 
            GROUP BY name
            HAVING COUNT(*) > 1
        ";
        
        $duplicatesStmt = $db->prepare($duplicatesQuery);
        $duplicatesStmt->execute();
        $duplicateGroups = $duplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $testResults['step_2_before_cleanup'] = [
            'status' => 'SUCCESS',
            'total_plans_before' => (int)$beforeCount,
            'duplicate_groups' => count($duplicateGroups),
            'duplicates_found' => $duplicateGroups
        ];
        
    } catch (Exception $e) {
        $testResults['step_2_before_cleanup'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 3: Run cleanup script
    $testResults['step_3_cleanup_execution'] = [];
    
    if (isset($_SESSION['admin_id'])) {
        try {
            // Include and execute the cleanup script
            ob_start();
            include '../admin/cleanup-duplicate-plans.php';
            $cleanupOutput = ob_get_clean();
            
            // Parse the cleanup output
            $cleanupResult = json_decode($cleanupOutput, true);
            
            if ($cleanupResult && $cleanupResult['success']) {
                $testResults['step_3_cleanup_execution'] = [
                    'status' => 'SUCCESS',
                    'cleanup_executed' => true,
                    'cleanup_result' => $cleanupResult['cleanup_results']
                ];
            } else {
                $testResults['step_3_cleanup_execution'] = [
                    'status' => 'FAILED',
                    'error' => 'Cleanup script failed',
                    'cleanup_output' => $cleanupOutput
                ];
            }
            
        } catch (Exception $e) {
            $testResults['step_3_cleanup_execution'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // STEP 4: Verify cleanup results
    $testResults['step_4_after_cleanup'] = [];
    
    try {
        $afterQuery = "SELECT COUNT(*) as total FROM investment_packages";
        $afterStmt = $db->prepare($afterQuery);
        $afterStmt->execute();
        $afterCount = $afterStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $remainingDuplicatesQuery = "
            SELECT 
                name, COUNT(*) as count
            FROM investment_packages 
            GROUP BY name
            HAVING COUNT(*) > 1
        ";
        
        $remainingDuplicatesStmt = $db->prepare($remainingDuplicatesQuery);
        $remainingDuplicatesStmt->execute();
        $remainingDuplicates = $remainingDuplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get final unique plans
        $finalPlansQuery = "
            SELECT id, name, price, shares, roi, annual_dividends, created_at
            FROM investment_packages 
            ORDER BY name, price
        ";
        
        $finalPlansStmt = $db->prepare($finalPlansQuery);
        $finalPlansStmt->execute();
        $finalPlans = $finalPlansStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $testResults['step_4_after_cleanup'] = [
            'status' => 'SUCCESS',
            'total_plans_after' => (int)$afterCount,
            'remaining_duplicates' => count($remainingDuplicates),
            'final_plans' => $finalPlans,
            'cleanup_successful' => count($remainingDuplicates) === 0
        ];
        
    } catch (Exception $e) {
        $testResults['step_4_after_cleanup'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 5: Test admin restrictions
    $testResults['step_5_admin_restrictions'] = [];
    
    try {
        // Clear admin session to test restrictions
        $originalAdminId = $_SESSION['admin_id'];
        $originalAdminUsername = $_SESSION['admin_username'];
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        
        // Try to create a plan without admin session (should fail)
        $testPlanData = [
            'name' => 'Test Plan',
            'price' => 100,
            'shares' => 10,
            'roi' => 800,
            'annual_dividends' => 800,
            'quarter_dividends' => 200
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/aureus-angel-alliance/api/packages/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPlanData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Restore admin session
        $_SESSION['admin_id'] = $originalAdminId;
        $_SESSION['admin_username'] = $originalAdminUsername;
        
        $responseData = json_decode($response, true);
        
        $testResults['step_5_admin_restrictions'] = [
            'status' => 'SUCCESS',
            'http_code' => $httpCode,
            'response' => $responseData,
            'restrictions_working' => $httpCode === 401,
            'unauthorized_access_blocked' => $httpCode === 401
        ];
        
    } catch (Exception $e) {
        $testResults['step_5_admin_restrictions'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // FINAL ASSESSMENT
    $cleanupSuccess = [
        'admin_login_successful' => isset($testResults['step_1_admin_login']['admin_logged_in']) && $testResults['step_1_admin_login']['admin_logged_in'],
        'duplicates_detected' => isset($testResults['step_2_before_cleanup']['duplicate_groups']) && $testResults['step_2_before_cleanup']['duplicate_groups'] > 0,
        'cleanup_executed' => isset($testResults['step_3_cleanup_execution']['cleanup_executed']) && $testResults['step_3_cleanup_execution']['cleanup_executed'],
        'cleanup_successful' => isset($testResults['step_4_after_cleanup']['cleanup_successful']) && $testResults['step_4_after_cleanup']['cleanup_successful'],
        'admin_restrictions_working' => isset($testResults['step_5_admin_restrictions']['restrictions_working']) && $testResults['step_5_admin_restrictions']['restrictions_working']
    ];
    
    $successfulSteps = count(array_filter($cleanupSuccess));
    $totalSteps = count($cleanupSuccess);
    
    $testResults['final_assessment'] = [
        'overall_status' => $successfulSteps === $totalSteps ? 'CLEANUP_AND_RESTRICTIONS_SUCCESSFUL' : 'SOME_ISSUES_REMAIN',
        'successful_steps' => $successfulSteps,
        'total_steps' => $totalSteps,
        'success_rate' => round(($successfulSteps / $totalSteps) * 100, 1) . '%',
        'cleanup_success' => $cleanupSuccess,
        'database_cleaned' => $successfulSteps >= 4,
        'admin_only_access_enforced' => isset($testResults['step_5_admin_restrictions']['restrictions_working']) && $testResults['step_5_admin_restrictions']['restrictions_working'],
        'test_completed_at' => date('c')
    ];
    
    echo json_encode([
        'success' => true,
        'test_type' => 'Plan Cleanup and Admin Restrictions Test',
        'test_results' => $testResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Plan cleanup test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Plan cleanup test failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
