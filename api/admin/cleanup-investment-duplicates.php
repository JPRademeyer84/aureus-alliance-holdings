<?php
/**
 * CLEANUP INVESTMENT DUPLICATES
 * Investigates and cleans up duplicates in aureus_investments table
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 INVESTMENT DUPLICATES CLEANUP\n";
    echo "===============================\n\n";
    
    // STEP 1: Analyze aureus_investments table
    echo "STEP 1: Analyzing aureus_investments table...\n";
    
    $totalQuery = "SELECT COUNT(*) as total FROM aureus_investments";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totalBefore = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total investments before cleanup: $totalBefore\n";
    
    // Check for duplicates by package_name
    $duplicatesQuery = "
        SELECT 
            package_name, COUNT(*) as count,
            GROUP_CONCAT(id) as investment_ids
        FROM aureus_investments 
        GROUP BY package_name
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ";
    
    $duplicatesStmt = $db->prepare($duplicatesQuery);
    $duplicatesStmt->execute();
    $duplicates = $duplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Duplicate groups found: " . count($duplicates) . "\n\n";
    
    if (count($duplicates) > 0) {
        echo "Duplicate investments by package name:\n";
        foreach ($duplicates as $duplicate) {
            echo "- {$duplicate['package_name']}: {$duplicate['count']} investments\n";
            echo "  IDs: {$duplicate['investment_ids']}\n";
        }
        echo "\n";
    }
    
    // Check for exact duplicates (same user, same package, same amount)
    $exactDuplicatesQuery = "
        SELECT 
            user_id, package_name, amount, COUNT(*) as count,
            GROUP_CONCAT(id ORDER BY created_at ASC) as investment_ids,
            MIN(created_at) as first_created,
            MAX(created_at) as last_created
        FROM aureus_investments 
        GROUP BY user_id, package_name, amount
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ";
    
    $exactDuplicatesStmt = $db->prepare($exactDuplicatesQuery);
    $exactDuplicatesStmt->execute();
    $exactDuplicates = $exactDuplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Exact duplicate investments found: " . count($exactDuplicates) . "\n";
    
    if (count($exactDuplicates) > 0) {
        echo "\nExact duplicate investments:\n";
        foreach ($exactDuplicates as $duplicate) {
            echo "- User {$duplicate['user_id']}, Package: {$duplicate['package_name']}, Amount: \${$duplicate['amount']}\n";
            echo "  Count: {$duplicate['count']}, IDs: {$duplicate['investment_ids']}\n";
            echo "  First: {$duplicate['first_created']}, Last: {$duplicate['last_created']}\n\n";
        }
    }
    
    // Show all investments for analysis
    echo "All investments in database:\n";
    $allInvestmentsQuery = "
        SELECT id, user_id, name, package_name, amount, status, created_at
        FROM aureus_investments 
        ORDER BY created_at ASC
    ";
    
    $allInvestmentsStmt = $db->prepare($allInvestmentsQuery);
    $allInvestmentsStmt->execute();
    $allInvestments = $allInvestmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allInvestments as $investment) {
        echo "- ID: {$investment['id']}\n";
        echo "  User: {$investment['user_id']} ({$investment['name']})\n";
        echo "  Package: {$investment['package_name']}\n";
        echo "  Amount: \${$investment['amount']}\n";
        echo "  Status: {$investment['status']}\n";
        echo "  Created: {$investment['created_at']}\n\n";
    }
    
    // STEP 2: Check if these are real duplicates or legitimate multiple investments
    echo "STEP 2: Analyzing investment legitimacy...\n";
    
    $realDuplicates = [];
    $legitimateInvestments = [];
    
    foreach ($exactDuplicates as $duplicate) {
        $ids = explode(',', $duplicate['investment_ids']);
        $timeDiff = strtotime($duplicate['last_created']) - strtotime($duplicate['first_created']);
        
        // If investments were made within 1 minute of each other, likely duplicates
        if ($timeDiff < 60) {
            $realDuplicates[] = $duplicate;
            echo "- REAL DUPLICATE: User {$duplicate['user_id']}, {$duplicate['package_name']}, time diff: {$timeDiff}s\n";
        } else {
            $legitimateInvestments[] = $duplicate;
            echo "- LEGITIMATE: User {$duplicate['user_id']}, {$duplicate['package_name']}, time diff: " . round($timeDiff/3600, 2) . "h\n";
        }
    }
    
    echo "\nReal duplicates to clean: " . count($realDuplicates) . "\n";
    echo "Legitimate multiple investments: " . count($legitimateInvestments) . "\n\n";
    
    // STEP 3: Clean up real duplicates only
    if (count($realDuplicates) > 0) {
        echo "STEP 3: Cleaning up real duplicates...\n";
        
        $db->beginTransaction();
        
        try {
            $totalRemoved = 0;
            
            foreach ($realDuplicates as $duplicate) {
                $ids = explode(',', $duplicate['investment_ids']);
                
                // Keep the first (oldest) investment
                $keepId = $ids[0];
                echo "Keeping investment: $keepId\n";
                
                // Remove all others
                for ($i = 1; $i < count($ids); $i++) {
                    $removeId = $ids[$i];
                    
                    $deleteQuery = "DELETE FROM aureus_investments WHERE id = ?";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->execute([$removeId]);
                    
                    if ($deleteStmt->rowCount() > 0) {
                        $totalRemoved++;
                        echo "Removed duplicate investment: $removeId\n";
                    }
                }
            }
            
            $db->commit();
            echo "✅ Cleanup completed successfully\n";
            echo "Total duplicates removed: $totalRemoved\n\n";
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        echo "STEP 3: No real duplicates found to clean up\n\n";
    }
    
    // STEP 4: Verify results
    echo "STEP 4: Verifying cleanup results...\n";
    
    $finalCountStmt = $db->prepare($totalQuery);
    $finalCountStmt->execute();
    $totalAfter = $finalCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total investments after cleanup: $totalAfter\n";
    
    // Check for remaining duplicates
    $remainingDuplicatesStmt = $db->prepare($exactDuplicatesQuery);
    $remainingDuplicatesStmt->execute();
    $remainingDuplicates = $remainingDuplicatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Remaining duplicate groups: " . count($remainingDuplicates) . "\n";
    
    if (count($remainingDuplicates) > 0) {
        echo "\nRemaining duplicates (likely legitimate):\n";
        foreach ($remainingDuplicates as $remaining) {
            $timeDiff = strtotime($remaining['last_created']) - strtotime($remaining['first_created']);
            echo "- User {$remaining['user_id']}, {$remaining['package_name']}: {$remaining['count']} investments, time span: " . round($timeDiff/3600, 2) . "h\n";
        }
    }
    
    echo "\n===============================\n";
    echo "🎉 INVESTMENT CLEANUP COMPLETE!\n";
    echo "===============================\n";
    echo "Summary:\n";
    echo "- Investments before: $totalBefore\n";
    echo "- Investments after: $totalAfter\n";
    echo "- Real duplicates removed: " . (isset($totalRemoved) ? $totalRemoved : 0) . "\n";
    echo "- Legitimate investments preserved: " . count($legitimateInvestments) . "\n";
    echo "- Database status: " . (count($remainingDuplicates) <= count($legitimateInvestments) ? "✅ CLEAN" : "⚠️ NEEDS REVIEW") . "\n";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "❌ INVESTMENT CLEANUP FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>
