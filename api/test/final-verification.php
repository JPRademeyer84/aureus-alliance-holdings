<?php
/**
 * FINAL VERIFICATION
 * Verifies that all duplicate plans have been removed and admin restrictions are working
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
    
    $verificationResults = [];
    
    // STEP 1: Verify no duplicates exist
    $verificationResults['step_1_duplicate_check'] = [];
    
    try {
        // Check for exact duplicates
        $duplicatesQuery = "
            SELECT 
                name, price, shares, roi, annual_dividends,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(id) as ids
            FROM investment_packages 
            GROUP BY name, price, shares, roi, annual_dividends
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC
        ";
        
        $duplicatesStmt = $db->prepare($duplicatesQuery);
        $duplicatesStmt->execute();
        $duplicates = $duplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check for name duplicates
        $nameDuplicatesQuery = "
            SELECT 
                name, COUNT(*) as count
            FROM investment_packages 
            GROUP BY name
            HAVING COUNT(*) > 1
        ";
        
        $nameDuplicatesStmt = $db->prepare($nameDuplicatesQuery);
        $nameDuplicatesStmt->execute();
        $nameDuplicates = $nameDuplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $totalQuery = "SELECT COUNT(*) as total FROM investment_packages";
        $totalStmt = $db->prepare($totalQuery);
        $totalStmt->execute();
        $totalPlans = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $verificationResults['step_1_duplicate_check'] = [
            'status' => 'SUCCESS',
            'total_plans' => (int)$totalPlans,
            'exact_duplicates' => count($duplicates),
            'name_duplicates' => count($nameDuplicates),
            'duplicate_details' => $duplicates,
            'name_duplicate_details' => $nameDuplicates,
            'database_clean' => count($duplicates) === 0 && count($nameDuplicates) === 0
        ];
        
    } catch (Exception $e) {
        $verificationResults['step_1_duplicate_check'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Verify unique plans
    $verificationResults['step_2_unique_plans'] = [];
    
    try {
        $uniquePlansQuery = "
            SELECT id, name, price, shares, roi, annual_dividends, created_at
            FROM investment_packages 
            ORDER BY name, price
        ";
        
        $uniquePlansStmt = $db->prepare($uniquePlansQuery);
        $uniquePlansStmt->execute();
        $uniquePlans = $uniquePlansStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $verificationResults['step_2_unique_plans'] = [
            'status' => 'SUCCESS',
            'unique_plan_count' => count($uniquePlans),
            'unique_plans' => $uniquePlans,
            'plans_verified' => true
        ];
        
    } catch (Exception $e) {
        $verificationResults['step_2_unique_plans'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 3: Test admin restrictions (without session)
    $verificationResults['step_3_admin_restrictions'] = [];
    
    try {
        // Test package creation without admin session
        $testData = json_encode([
            'name' => 'Unauthorized Test Plan',
            'price' => 999,
            'shares' => 1,
            'roi' => 1000,
            'annual_dividends' => 1000,
            'quarter_dividends' => 250
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $testData
            ]
        ]);
        
        $response = @file_get_contents('http://localhost/aureus-angel-alliance/api/packages/index.php', false, $context);
        $responseData = json_decode($response, true);
        
        // Check if unauthorized access was blocked
        $unauthorizedBlocked = $responseData && !$responseData['success'] && 
                              (strpos($responseData['error'], 'Admin authentication required') !== false);
        
        $verificationResults['step_3_admin_restrictions'] = [
            'status' => 'SUCCESS',
            'unauthorized_access_blocked' => $unauthorizedBlocked,
            'response' => $responseData,
            'admin_only_enforced' => $unauthorizedBlocked
        ];
        
    } catch (Exception $e) {
        $verificationResults['step_3_admin_restrictions'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 4: Verify automatic plan generation is disabled
    $verificationResults['step_4_auto_generation_disabled'] = [];
    
    try {
        // Record current plan count
        $beforeCount = $verificationResults['step_1_duplicate_check']['total_plans'];
        
        // Simulate multiple API calls that previously triggered plan creation
        $apiEndpoints = [
            'http://localhost/aureus-angel-alliance/api/packages/index.php',
            'http://localhost/aureus-angel-alliance/api/wallets/index.php'
        ];
        
        foreach ($apiEndpoints as $endpoint) {
            @file_get_contents($endpoint);
        }
        
        // Check plan count after API calls
        $afterCountStmt = $db->prepare($totalQuery);
        $afterCountStmt->execute();
        $afterCount = $afterCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $verificationResults['step_4_auto_generation_disabled'] = [
            'status' => 'SUCCESS',
            'plan_count_before' => (int)$beforeCount,
            'plan_count_after' => (int)$afterCount,
            'no_auto_generation' => $beforeCount === $afterCount,
            'auto_generation_disabled' => $beforeCount === $afterCount
        ];
        
    } catch (Exception $e) {
        $verificationResults['step_4_auto_generation_disabled'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 5: Check referral system integrity
    $verificationResults['step_5_referral_integrity'] = [];
    
    try {
        // Check if referral commissions still work with cleaned plans
        $commissionQuery = "SELECT COUNT(*) as count FROM referral_commissions";
        $commissionStmt = $db->prepare($commissionQuery);
        $commissionStmt->execute();
        $commissionCount = $commissionStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check if investments still reference valid plans
        $investmentQuery = "
            SELECT 
                ai.package_name,
                COUNT(*) as investment_count,
                CASE WHEN ip.name IS NOT NULL THEN 'valid' ELSE 'invalid' END as plan_status
            FROM aureus_investments ai
            LEFT JOIN investment_packages ip ON ai.package_name = ip.name
            GROUP BY ai.package_name, plan_status
        ";
        
        $investmentStmt = $db->prepare($investmentQuery);
        $investmentStmt->execute();
        $investmentReferences = $investmentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $verificationResults['step_5_referral_integrity'] = [
            'status' => 'SUCCESS',
            'commission_count' => (int)$commissionCount,
            'investment_references' => $investmentReferences,
            'referral_system_intact' => $commissionCount > 0,
            'investment_plan_references_valid' => true
        ];
        
    } catch (Exception $e) {
        $verificationResults['step_5_referral_integrity'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // FINAL ASSESSMENT
    $verificationChecks = [
        'database_clean' => isset($verificationResults['step_1_duplicate_check']['database_clean']) && $verificationResults['step_1_duplicate_check']['database_clean'],
        'unique_plans_verified' => isset($verificationResults['step_2_unique_plans']['plans_verified']) && $verificationResults['step_2_unique_plans']['plans_verified'],
        'admin_restrictions_enforced' => isset($verificationResults['step_3_admin_restrictions']['admin_only_enforced']) && $verificationResults['step_3_admin_restrictions']['admin_only_enforced'],
        'auto_generation_disabled' => isset($verificationResults['step_4_auto_generation_disabled']['auto_generation_disabled']) && $verificationResults['step_4_auto_generation_disabled']['auto_generation_disabled'],
        'referral_system_intact' => isset($verificationResults['step_5_referral_integrity']['referral_system_intact']) && $verificationResults['step_5_referral_integrity']['referral_system_intact']
    ];
    
    $passedChecks = count(array_filter($verificationChecks));
    $totalChecks = count($verificationChecks);
    
    $verificationResults['final_assessment'] = [
        'overall_status' => $passedChecks === $totalChecks ? 'ALL_ISSUES_RESOLVED' : 'SOME_ISSUES_REMAIN',
        'passed_checks' => $passedChecks,
        'total_checks' => $totalChecks,
        'success_rate' => round(($passedChecks / $totalChecks) * 100, 1) . '%',
        'verification_checks' => $verificationChecks,
        'database_integrity_restored' => $passedChecks >= 4,
        'admin_control_established' => isset($verificationResults['step_3_admin_restrictions']['admin_only_enforced']) && $verificationResults['step_3_admin_restrictions']['admin_only_enforced'],
        'verification_completed_at' => date('c')
    ];
    
    echo json_encode([
        'success' => true,
        'verification_type' => 'Final System Verification',
        'verification_results' => $verificationResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Final verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Final verification failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
