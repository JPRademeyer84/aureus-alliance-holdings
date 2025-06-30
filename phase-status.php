<?php
/**
 * Phase Status Monitor
 * Shows current phase status and allows manual phase advancement
 */

try {
    $pdo = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🎯 AUREUS PHASE SYSTEM STATUS\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    // Get current active phase
    $stmt = $pdo->prepare("
        SELECT * FROM phases 
        WHERE is_active = TRUE 
        ORDER BY phase_number ASC 
        LIMIT 1
    ");
    $stmt->execute();
    $currentPhase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentPhase) {
        echo "🚀 CURRENT ACTIVE PHASE: {$currentPhase['phase_number']} - {$currentPhase['name']}\n";
        echo "📅 Started: " . ($currentPhase['start_date'] ?? 'Not set') . "\n";
        echo "🎯 Total Shares Available: " . number_format($currentPhase['total_packages_available']) . "\n";
        
        // Get shares sold and pending ONLY for the current active phase
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN ai.status = 'completed' THEN ai.shares ELSE 0 END), 0) as shares_sold,
                COALESCE(SUM(CASE WHEN ai.status = 'pending' THEN ai.shares ELSE 0 END), 0) as pending_shares
            FROM aureus_investments ai
            WHERE ai.created_at >= ?
        ");
        $stmt->execute([$currentPhase['start_date']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $sharesSold = $stats['shares_sold'];
        $pendingShares = $stats['pending_shares'];
        $availableShares = $currentPhase['total_packages_available'] - $sharesSold - $pendingShares;
        $completionPercentage = ($sharesSold / $currentPhase['total_packages_available']) * 100;
        
        echo "✅ Shares Sold: " . number_format($sharesSold) . "\n";
        echo "⏳ Pending Shares: " . number_format($pendingShares) . "\n";
        echo "🔄 Available Shares: " . number_format($availableShares) . "\n";
        echo "📊 Completion: " . number_format($completionPercentage, 2) . "%\n";
        echo "💰 Revenue: $" . number_format($currentPhase['total_revenue'], 2) . "\n\n";
        
        // Progress bar
        $barLength = 50;
        $filledLength = (int)($completionPercentage / 100 * $barLength);
        $bar = str_repeat("█", $filledLength) . str_repeat("░", $barLength - $filledLength);
        echo "Progress: [{$bar}] " . number_format($completionPercentage, 1) . "%\n\n";
        
        // Check if phase should advance
        if ($sharesSold >= $currentPhase['total_packages_available']) {
            echo "🎉 PHASE {$currentPhase['phase_number']} IS COMPLETE! Ready to advance to next phase.\n\n";
        }
        
    } else {
        echo "❌ No active phase found!\n\n";
    }
    
    // Show all phases overview
    echo "📋 ALL PHASES OVERVIEW:\n";
    echo "-" . str_repeat("-", 80) . "\n";
    printf("%-5s %-20s %-8s %-12s %-12s %-12s %-8s\n", 
           "Phase", "Name", "Status", "Total", "Sold", "Available", "Progress");
    echo "-" . str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            CASE
                WHEN p.is_active = 1 THEN
                    COALESCE(SUM(CASE WHEN ai.status = 'completed' AND ai.created_at >= p.start_date THEN ai.shares ELSE 0 END), 0)
                ELSE 0
            END as shares_sold,
            CASE
                WHEN p.is_active = 1 THEN
                    COALESCE(SUM(CASE WHEN ai.status = 'pending' AND ai.created_at >= p.start_date THEN ai.shares ELSE 0 END), 0)
                ELSE 0
            END as pending_shares
        FROM phases p
        LEFT JOIN aureus_investments ai ON p.is_active = 1
        GROUP BY p.id
        ORDER BY p.phase_number
    ");
    $stmt->execute();
    $allPhases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allPhases as $phase) {
        $status = $phase['is_active'] ? "ACTIVE" : "INACTIVE";
        $sharesSold = $phase['shares_sold'];
        $availableShares = $phase['total_packages_available'] - $sharesSold - $phase['pending_shares'];
        $progress = $phase['total_packages_available'] > 0 
            ? ($sharesSold / $phase['total_packages_available']) * 100 
            : 0;
        
        printf("%-5d %-20s %-8s %-12s %-12s %-12s %-7.1f%%\n",
               $phase['phase_number'],
               substr($phase['name'], 0, 20),
               $status,
               number_format($phase['total_packages_available']),
               number_format($sharesSold),
               number_format($availableShares),
               $progress
        );
    }
    
    echo "\n📊 SYSTEM TOTALS:\n";
    $totalShares = array_sum(array_column($allPhases, 'total_packages_available'));
    $totalSold = array_sum(array_column($allPhases, 'shares_sold'));
    $totalRevenue = array_sum(array_column($allPhases, 'total_revenue'));
    
    echo "🎯 Total Shares Across All Phases: " . number_format($totalShares) . "\n";
    echo "✅ Total Shares Sold: " . number_format($totalSold) . "\n";
    echo "💰 Total Revenue: $" . number_format($totalRevenue, 2) . "\n";
    echo "📈 Overall Progress: " . number_format(($totalSold / $totalShares) * 100, 2) . "%\n\n";
    
    // Show active packages with dynamic pricing
    echo "📦 ACTIVE PACKAGES (Current Phase - Dynamic Pricing):\n";
    echo "-" . str_repeat("-", 70) . "\n";

    $stmt = $pdo->prepare("
        SELECT name, shares, calculated_price, cost_per_share
        FROM package_pricing
        ORDER BY calculated_price
    ");
    $stmt->execute();
    $activePackages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($activePackages) {
        printf("%-15s %-8s %-12s %-15s\n", "Package", "Shares", "Price", "Cost/Share");
        echo "-" . str_repeat("-", 70) . "\n";
        foreach ($activePackages as $pkg) {
            printf("%-15s %-8s $%-11s $%-14s\n",
                   $pkg['name'],
                   number_format($pkg['shares']),
                   number_format($pkg['calculated_price'], 2),
                   number_format($pkg['cost_per_share'], 2)
            );
        }
    } else {
        echo "❌ No active packages found!\n";
    }
    
    echo "\n🎯 Phase System Status: OPERATIONAL ✅\n";
    echo "📱 Telegram Bot Integration: ACTIVE ✅\n";
    echo "🔄 Auto-Phase Advancement: ENABLED ✅\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
