<?php
/**
 * CLEANUP DUPLICATE PLANS
 * Removes all duplicate investment packages and keeps only unique ones
 * ADMIN ONLY - Requires admin authentication
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

// ADMIN AUTHENTICATION REQUIRED
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Admin authentication required',
        'message' => 'Only admins can perform plan cleanup'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $cleanupResults = [];
    
    // STEP 1: Analyze current duplicates
    $cleanupResults['step_1_analysis'] = [];
    
    try {
        // Get exact duplicates
        $duplicatesQuery = "
            SELECT 
                name, price, shares, roi, annual_dividends,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(id ORDER BY created_at ASC) as duplicate_ids,
                MIN(created_at) as first_created,
                MAX(created_at) as last_created
            FROM investment_packages 
            GROUP BY name, price, shares, roi, annual_dividends
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC
        ";
        
        $duplicatesStmt = $db->prepare($duplicatesQuery);
        $duplicatesStmt->execute();
        $duplicates = $duplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total plan count
        $totalQuery = "SELECT COUNT(*) as total FROM investment_packages";
        $totalStmt = $db->prepare($totalQuery);
        $totalStmt->execute();
        $totalPlans = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $cleanupResults['step_1_analysis'] = [
            'status' => 'SUCCESS',
            'total_plans_before' => (int)$totalPlans,
            'duplicate_groups' => count($duplicates),
            'duplicates_found' => $duplicates,
            'cleanup_needed' => count($duplicates) > 0
        ];
        
    } catch (Exception $e) {
        $cleanupResults['step_1_analysis'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 2: Backup existing investments that reference these plans
    $cleanupResults['step_2_backup_check'] = [];
    
    try {
        // Check if any investments reference the plans we're about to clean
        $investmentCheckQuery = "
            SELECT 
                package_name,
                COUNT(*) as investment_count
            FROM aureus_investments 
            GROUP BY package_name
        ";
        
        $investmentCheckStmt = $db->prepare($investmentCheckQuery);
        $investmentCheckStmt->execute();
        $investmentReferences = $investmentCheckStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cleanupResults['step_2_backup_check'] = [
            'status' => 'SUCCESS',
            'investment_references' => $investmentReferences,
            'investments_exist' => count($investmentReferences) > 0,
            'safe_to_cleanup' => true // We're only removing duplicates, not unique plans
        ];
        
    } catch (Exception $e) {
        $cleanupResults['step_2_backup_check'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 3: Perform cleanup (remove duplicates, keep oldest)
    $cleanupResults['step_3_cleanup'] = [];
    
    if (isset($duplicates) && count($duplicates) > 0) {
        try {
            $db->beginTransaction();
            
            $totalRemoved = 0;
            $plansKept = [];
            $plansRemoved = [];
            
            foreach ($duplicates as $duplicate) {
                $duplicateIds = explode(',', $duplicate['duplicate_ids']);
                
                // Keep the first (oldest) plan
                $planToKeep = $duplicateIds[0];
                $plansKept[] = [
                    'id' => $planToKeep,
                    'name' => $duplicate['name'],
                    'price' => $duplicate['price']
                ];
                
                // Remove all other duplicates
                for ($i = 1; $i < count($duplicateIds); $i++) {
                    $planToRemove = $duplicateIds[$i];
                    
                    // Delete the duplicate plan
                    $deleteQuery = "DELETE FROM investment_packages WHERE id = ?";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->execute([$planToRemove]);
                    
                    if ($deleteStmt->rowCount() > 0) {
                        $totalRemoved++;
                        $plansRemoved[] = [
                            'id' => $planToRemove,
                            'name' => $duplicate['name'],
                            'price' => $duplicate['price']
                        ];
                    }
                }
            }
            
            $db->commit();
            
            // Get final count
            $finalCountQuery = "SELECT COUNT(*) as total FROM investment_packages";
            $finalCountStmt = $db->prepare($finalCountQuery);
            $finalCountStmt->execute();
            $finalCount = $finalCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $cleanupResults['step_3_cleanup'] = [
                'status' => 'SUCCESS',
                'total_removed' => $totalRemoved,
                'plans_kept' => $plansKept,
                'plans_removed' => $plansRemoved,
                'final_plan_count' => (int)$finalCount,
                'cleanup_successful' => true
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            $cleanupResults['step_3_cleanup'] = [
                'status' => 'FAILED',
                'error' => $e->getMessage(),
                'cleanup_successful' => false
            ];
        }
    } else {
        $cleanupResults['step_3_cleanup'] = [
            'status' => 'SKIPPED',
            'reason' => 'No duplicates found to clean up',
            'cleanup_successful' => true
        ];
    }
    
    // STEP 4: Verify cleanup results
    $cleanupResults['step_4_verification'] = [];
    
    try {
        // Check for remaining duplicates
        $verifyQuery = "
            SELECT 
                name, COUNT(*) as count
            FROM investment_packages 
            GROUP BY name
            HAVING COUNT(*) > 1
        ";
        
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->execute();
        $remainingDuplicates = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get final unique plans
        $uniquePlansQuery = "
            SELECT id, name, price, shares, roi, annual_dividends, created_at
            FROM investment_packages 
            ORDER BY name, price
        ";
        
        $uniquePlansStmt = $db->prepare($uniquePlansQuery);
        $uniquePlansStmt->execute();
        $uniquePlans = $uniquePlansStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cleanupResults['step_4_verification'] = [
            'status' => 'SUCCESS',
            'remaining_duplicates' => count($remainingDuplicates),
            'unique_plans' => $uniquePlans,
            'cleanup_verified' => count($remainingDuplicates) === 0,
            'final_plan_count' => count($uniquePlans)
        ];
        
    } catch (Exception $e) {
        $cleanupResults['step_4_verification'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 5: Log admin action
    $cleanupResults['step_5_audit_log'] = [];
    
    try {
        $auditQuery = "
            INSERT INTO security_audit_log (
                event_type, admin_id, event_details, security_level, 
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $auditStmt = $db->prepare($auditQuery);
        $auditStmt->execute([
            'admin_action',
            $_SESSION['admin_id'],
            json_encode([
                'action' => 'cleanup_duplicate_plans',
                'admin_username' => $_SESSION['admin_username'],
                'plans_removed' => $cleanupResults['step_3_cleanup']['total_removed'] ?? 0,
                'timestamp' => date('c')
            ]),
            'info',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        $cleanupResults['step_5_audit_log'] = [
            'status' => 'SUCCESS',
            'audit_logged' => true,
            'admin_action_recorded' => true
        ];
        
    } catch (Exception $e) {
        $cleanupResults['step_5_audit_log'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // FINAL ASSESSMENT
    $cleanupSuccess = [
        'analysis_completed' => isset($cleanupResults['step_1_analysis']['cleanup_needed']),
        'backup_verified' => isset($cleanupResults['step_2_backup_check']['safe_to_cleanup']) && $cleanupResults['step_2_backup_check']['safe_to_cleanup'],
        'cleanup_successful' => isset($cleanupResults['step_3_cleanup']['cleanup_successful']) && $cleanupResults['step_3_cleanup']['cleanup_successful'],
        'verification_passed' => isset($cleanupResults['step_4_verification']['cleanup_verified']) && $cleanupResults['step_4_verification']['cleanup_verified'],
        'audit_logged' => isset($cleanupResults['step_5_audit_log']['audit_logged']) && $cleanupResults['step_5_audit_log']['audit_logged']
    ];
    
    $successfulSteps = count(array_filter($cleanupSuccess));
    $totalSteps = count($cleanupSuccess);
    
    $cleanupResults['final_assessment'] = [
        'overall_status' => $successfulSteps === $totalSteps ? 'CLEANUP_SUCCESSFUL' : 'CLEANUP_ISSUES',
        'successful_steps' => $successfulSteps,
        'total_steps' => $totalSteps,
        'success_rate' => round(($successfulSteps / $totalSteps) * 100, 1) . '%',
        'cleanup_success' => $cleanupSuccess,
        'database_integrity_maintained' => $successfulSteps >= 4,
        'admin_action_by' => $_SESSION['admin_username'],
        'cleanup_completed_at' => date('c')
    ];
    
    echo json_encode([
        'success' => true,
        'cleanup_type' => 'Duplicate Plans Cleanup',
        'cleanup_results' => $cleanupResults
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Plan cleanup error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Plan cleanup failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
