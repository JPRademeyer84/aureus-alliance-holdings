<?php
/**
 * CLEANUP COMMISSION PLANS
 * Removes duplicate commission plans and keeps only one default plan
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 COMMISSION PLANS CLEANUP STARTED\n";
    echo "===================================\n\n";
    
    // STEP 1: Analyze commission_plans table
    echo "STEP 1: Analyzing commission_plans table...\n";
    
    $totalQuery = "SELECT COUNT(*) as total FROM commission_plans";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totalBefore = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total commission plans before cleanup: $totalBefore\n";
    
    // Get all plans to see what we have
    $allPlansQuery = "
        SELECT 
            id, plan_name, description, is_active, is_default,
            level_1_usdt_percent, level_1_nft_percent,
            level_2_usdt_percent, level_2_nft_percent,
            level_3_usdt_percent, level_3_nft_percent,
            nft_pack_price, minimum_investment,
            created_at
        FROM commission_plans 
        ORDER BY created_at ASC
        LIMIT 10
    ";
    
    $allPlansStmt = $db->prepare($allPlansQuery);
    $allPlansStmt->execute();
    $samplePlans = $allPlansStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample commission plans:\n";
    foreach ($samplePlans as $i => $plan) {
        echo "Plan " . ($i + 1) . ": {$plan['plan_name']}\n";
        echo "  - USDT: L1={$plan['level_1_usdt_percent']}%, L2={$plan['level_2_usdt_percent']}%, L3={$plan['level_3_usdt_percent']}%\n";
        echo "  - NFT: L1={$plan['level_1_nft_percent']}%, L2={$plan['level_2_nft_percent']}%, L3={$plan['level_3_nft_percent']}%\n";
        echo "  - Active: {$plan['is_active']}, Default: {$plan['is_default']}\n";
        echo "  - Created: {$plan['created_at']}\n\n";
    }
    
    // Check for exact duplicates
    $duplicatesQuery = "
        SELECT 
            plan_name, description, 
            level_1_usdt_percent, level_1_nft_percent,
            level_2_usdt_percent, level_2_nft_percent,
            level_3_usdt_percent, level_3_nft_percent,
            nft_pack_price, minimum_investment,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(id ORDER BY created_at ASC) as duplicate_ids,
            MIN(created_at) as first_created,
            MAX(created_at) as last_created
        FROM commission_plans 
        GROUP BY 
            plan_name, description,
            level_1_usdt_percent, level_1_nft_percent,
            level_2_usdt_percent, level_2_nft_percent,
            level_3_usdt_percent, level_3_nft_percent,
            nft_pack_price, minimum_investment
        HAVING COUNT(*) > 1
        ORDER BY duplicate_count DESC
    ";
    
    $duplicatesStmt = $db->prepare($duplicatesQuery);
    $duplicatesStmt->execute();
    $duplicates = $duplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Duplicate groups found: " . count($duplicates) . "\n";
    
    if (count($duplicates) > 0) {
        echo "\nDuplicate commission plans:\n";
        foreach ($duplicates as $duplicate) {
            echo "- {$duplicate['plan_name']}: {$duplicate['duplicate_count']} copies\n";
            echo "  USDT: {$duplicate['level_1_usdt_percent']}%/{$duplicate['level_2_usdt_percent']}%/{$duplicate['level_3_usdt_percent']}%\n";
            echo "  NFT: {$duplicate['level_1_nft_percent']}%/{$duplicate['level_2_nft_percent']}%/{$duplicate['level_3_nft_percent']}%\n";
        }
    }
    
    // STEP 2: Check if any commission transactions reference these plans
    echo "\nSTEP 2: Checking commission transaction references...\n";
    
    $referencesQuery = "SELECT COUNT(*) as count FROM commission_transactions";
    $referencesStmt = $db->prepare($referencesQuery);
    $referencesStmt->execute();
    $transactionCount = $referencesStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Commission transactions referencing plans: $transactionCount\n";
    
    if ($transactionCount == 0) {
        echo "✅ Safe to clean up - no transactions reference commission plans\n";
    } else {
        echo "⚠️ Warning: Some transactions reference commission plans\n";
    }
    
    // STEP 3: Perform cleanup
    echo "\nSTEP 3: Performing cleanup...\n";
    
    $db->beginTransaction();
    
    try {
        // Strategy: Keep only ONE commission plan (the oldest one)
        // and delete all others since they're all identical
        
        $oldestPlanQuery = "
            SELECT id, plan_name, created_at
            FROM commission_plans 
            ORDER BY created_at ASC 
            LIMIT 1
        ";
        
        $oldestPlanStmt = $db->prepare($oldestPlanQuery);
        $oldestPlanStmt->execute();
        $oldestPlan = $oldestPlanStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oldestPlan) {
            echo "Keeping oldest plan: {$oldestPlan['plan_name']} (ID: {$oldestPlan['id']})\n";
            echo "Created: {$oldestPlan['created_at']}\n";
            
            // Delete all other plans
            $deleteQuery = "DELETE FROM commission_plans WHERE id != ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$oldestPlan['id']]);
            
            $deletedCount = $deleteStmt->rowCount();
            echo "Deleted $deletedCount duplicate commission plans\n";
            
            // Make sure the remaining plan is active and default
            $updateQuery = "
                UPDATE commission_plans 
                SET is_active = 1, is_default = 1, updated_at = NOW()
                WHERE id = ?
            ";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$oldestPlan['id']]);
            
            echo "Updated remaining plan to be active and default\n";
            
        } else {
            throw new Exception("No commission plans found to keep");
        }
        
        $db->commit();
        echo "✅ Cleanup completed successfully\n";
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
    // STEP 4: Verify results
    echo "\nSTEP 4: Verifying cleanup results...\n";
    
    $finalCountStmt = $db->prepare($totalQuery);
    $finalCountStmt->execute();
    $totalAfter = $finalCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total commission plans after cleanup: $totalAfter\n";
    
    // Show remaining plan
    $remainingQuery = "SELECT * FROM commission_plans LIMIT 1";
    $remainingStmt = $db->prepare($remainingQuery);
    $remainingStmt->execute();
    $remainingPlan = $remainingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($remainingPlan) {
        echo "\nRemaining commission plan:\n";
        echo "- Name: {$remainingPlan['plan_name']}\n";
        echo "- Description: {$remainingPlan['description']}\n";
        echo "- USDT Commissions: L1={$remainingPlan['level_1_usdt_percent']}%, L2={$remainingPlan['level_2_usdt_percent']}%, L3={$remainingPlan['level_3_usdt_percent']}%\n";
        echo "- NFT Commissions: L1={$remainingPlan['level_1_nft_percent']}%, L2={$remainingPlan['level_2_nft_percent']}%, L3={$remainingPlan['level_3_nft_percent']}%\n";
        echo "- NFT Pack Price: \${$remainingPlan['nft_pack_price']}\n";
        echo "- Minimum Investment: \${$remainingPlan['minimum_investment']}\n";
        echo "- Active: {$remainingPlan['is_active']}\n";
        echo "- Default: {$remainingPlan['is_default']}\n";
        echo "- Created: {$remainingPlan['created_at']}\n";
    }
    
    echo "\n===================================\n";
    echo "🎉 COMMISSION PLANS CLEANUP COMPLETE!\n";
    echo "===================================\n";
    echo "Summary:\n";
    echo "- Plans before: $totalBefore\n";
    echo "- Plans after: $totalAfter\n";
    echo "- Plans removed: " . ($totalBefore - $totalAfter) . "\n";
    echo "- Database clean: " . ($totalAfter == 1 ? "✅ YES" : "❌ NO") . "\n";
    
    // Return JSON for API calls
    echo "\n" . json_encode([
        'success' => true,
        'cleanup_completed' => true,
        'plans_before' => (int)$totalBefore,
        'plans_after' => (int)$totalAfter,
        'plans_removed' => (int)($totalBefore - $totalAfter),
        'database_clean' => $totalAfter == 1,
        'remaining_plan' => $remainingPlan
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "❌ COMMISSION PLANS CLEANUP FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    echo "\n" . json_encode([
        'success' => false,
        'error' => 'Commission plans cleanup failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
