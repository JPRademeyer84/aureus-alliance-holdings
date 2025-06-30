<?php
/**
 * DIRECT CLEANUP DUPLICATES
 * Emergency cleanup script for duplicate plans
 * Run this directly to clean up the database
 */

header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 DUPLICATE PLANS CLEANUP STARTED\n";
    echo "=====================================\n\n";
    
    // STEP 1: Analyze current state
    echo "STEP 1: Analyzing current state...\n";
    
    $totalQuery = "SELECT COUNT(*) as total FROM investment_packages";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totalBefore = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total plans before cleanup: $totalBefore\n";
    
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
    
    echo "Duplicate groups found: " . count($duplicates) . "\n\n";
    
    if (count($duplicates) === 0) {
        echo "✅ No duplicates found! Database is clean.\n";
        exit;
    }
    
    // STEP 2: Show what will be cleaned
    echo "STEP 2: Duplicate plans to be cleaned:\n";
    
    $totalToRemove = 0;
    foreach ($duplicates as $duplicate) {
        $duplicateIds = explode(',', $duplicate['duplicate_ids']);
        $toRemove = count($duplicateIds) - 1; // Keep first, remove rest
        $totalToRemove += $toRemove;
        
        echo "- {$duplicate['name']} (\${$duplicate['price']}): {$duplicate['duplicate_count']} copies, removing $toRemove\n";
    }
    
    echo "\nTotal plans to remove: $totalToRemove\n";
    echo "Plans to keep: " . (count($duplicates)) . "\n\n";
    
    // STEP 3: Perform cleanup
    echo "STEP 3: Performing cleanup...\n";
    
    $db->beginTransaction();
    
    $actualRemoved = 0;
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
        
        echo "Keeping: {$duplicate['name']} (ID: $planToKeep)\n";
        
        // Remove all other duplicates
        for ($i = 1; $i < count($duplicateIds); $i++) {
            $planToRemove = $duplicateIds[$i];
            
            // Delete the duplicate plan
            $deleteQuery = "DELETE FROM investment_packages WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$planToRemove]);
            
            if ($deleteStmt->rowCount() > 0) {
                $actualRemoved++;
                $plansRemoved[] = [
                    'id' => $planToRemove,
                    'name' => $duplicate['name'],
                    'price' => $duplicate['price']
                ];
                echo "Removed: {$duplicate['name']} (ID: $planToRemove)\n";
            }
        }
    }
    
    $db->commit();
    
    echo "\nCleanup completed!\n";
    echo "Plans removed: $actualRemoved\n";
    echo "Plans kept: " . count($plansKept) . "\n\n";
    
    // STEP 4: Verify results
    echo "STEP 4: Verifying cleanup results...\n";
    
    $totalAfterStmt = $db->prepare($totalQuery);
    $totalAfterStmt->execute();
    $totalAfter = $totalAfterStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total plans after cleanup: $totalAfter\n";
    
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
    
    if (count($remainingDuplicates) === 0) {
        echo "✅ No remaining duplicates found!\n";
    } else {
        echo "⚠️ Warning: " . count($remainingDuplicates) . " duplicate groups still exist:\n";
        foreach ($remainingDuplicates as $remaining) {
            echo "- {$remaining['name']}: {$remaining['count']} copies\n";
        }
    }
    
    // STEP 5: Show final unique plans
    echo "\nSTEP 5: Final unique plans in database:\n";
    
    $finalPlansQuery = "
        SELECT id, name, price, shares, roi, annual_dividends, created_at
        FROM investment_packages 
        ORDER BY name, price
    ";
    
    $finalPlansStmt = $db->prepare($finalPlansQuery);
    $finalPlansStmt->execute();
    $finalPlans = $finalPlansStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalPlans as $plan) {
        echo "- {$plan['name']}: \${$plan['price']} ({$plan['shares']} shares, ROI: \${$plan['roi']})\n";
    }
    
    echo "\n=====================================\n";
    echo "🎉 CLEANUP COMPLETED SUCCESSFULLY!\n";
    echo "=====================================\n";
    echo "Summary:\n";
    echo "- Plans before: $totalBefore\n";
    echo "- Plans removed: $actualRemoved\n";
    echo "- Plans after: $totalAfter\n";
    echo "- Unique plans: " . count($finalPlans) . "\n";
    echo "- Duplicates remaining: " . count($remainingDuplicates) . "\n";
    echo "- Database integrity: " . (count($remainingDuplicates) === 0 ? "✅ PERFECT" : "⚠️ NEEDS ATTENTION") . "\n";
    
    // Return JSON for API calls
    echo "\n" . json_encode([
        'success' => true,
        'cleanup_completed' => true,
        'plans_before' => (int)$totalBefore,
        'plans_removed' => $actualRemoved,
        'plans_after' => (int)$totalAfter,
        'unique_plans' => count($finalPlans),
        'remaining_duplicates' => count($remainingDuplicates),
        'database_clean' => count($remainingDuplicates) === 0,
        'final_plans' => $finalPlans
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "❌ CLEANUP FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    echo "\n" . json_encode([
        'success' => false,
        'error' => 'Cleanup failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
