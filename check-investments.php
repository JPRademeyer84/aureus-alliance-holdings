<?php
require_once 'api/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "🔍 CHECKING CURRENT INVESTMENTS\n";
    echo "================================\n\n";
    
    // Get current active phase
    $stmt = $pdo->prepare("SELECT * FROM phases WHERE is_active = TRUE LIMIT 1");
    $stmt->execute();
    $currentPhase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentPhase) {
        echo "📊 CURRENT ACTIVE PHASE: {$currentPhase['phase_number']} - {$currentPhase['name']}\n";
        echo "📅 Started: {$currentPhase['start_date']}\n";
        echo "🎯 Total Shares Available: " . number_format($currentPhase['total_packages_available']) . "\n\n";
        
        // Get all investments for current phase
        $stmt = $pdo->prepare("
            SELECT
                ai.*,
                u.email
            FROM aureus_investments ai
            LEFT JOIN users u ON ai.user_id = u.id
            WHERE ai.created_at >= ?
            ORDER BY ai.created_at DESC
        ");
        $stmt->execute([$currentPhase['start_date']]);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "💰 INVESTMENTS SINCE PHASE START:\n";
        echo "ID\tUser\t\t\tAmount\tShares\tStatus\tCreated\n";
        echo "------------------------------------------------------------------------\n";

        $totalCompleted = 0;
        $totalPending = 0;

        foreach ($investments as $inv) {
            $userName = $inv['email'] ?? 'Unknown';
            if (strlen($userName) > 20) $userName = substr($userName, 0, 17) . '...';

            echo sprintf("%d\t%-20s\t$%d\t%d\t%s\t%s\n",
                $inv['id'],
                $userName,
                $inv['amount'],
                $inv['shares'],
                $inv['status'],
                substr($inv['created_at'], 0, 16)
            );

            if ($inv['status'] === 'completed') {
                $totalCompleted += $inv['shares'];
            } elseif ($inv['status'] === 'pending') {
                $totalPending += $inv['shares'];
            }
        }
        
        echo "\n📊 SUMMARY:\n";
        echo "✅ Completed Shares: " . number_format($totalCompleted) . "\n";
        echo "⏳ Pending Shares: " . number_format($totalPending) . "\n";
        echo "🔄 Total Committed: " . number_format($totalCompleted + $totalPending) . "\n";
        echo "📈 Available Shares: " . number_format($currentPhase['total_packages_available'] - $totalCompleted - $totalPending) . "\n\n";

        // Check for any unusual data
        echo "🔍 DATA INTEGRITY CHECK:\n";
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, MAX(shares) as max_shares, MIN(shares) as min_shares
            FROM aureus_investments
            WHERE created_at >= ?
        ");
        $stmt->execute([$currentPhase['start_date']]);
        $integrity = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "Total Investment Records: " . $integrity['count'] . "\n";
        echo "Max Shares in Single Investment: " . number_format($integrity['max_shares']) . "\n";
        echo "Min Shares in Single Investment: " . number_format($integrity['min_shares']) . "\n";

        // Check for any investments with extremely large share counts
        $stmt = $pdo->prepare("
            SELECT id, shares, amount, status
            FROM aureus_investments
            WHERE created_at >= ? AND shares > 100000
        ");
        $stmt->execute([$currentPhase['start_date']]);
        $largeInvestments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($largeInvestments)) {
            echo "\n⚠️ LARGE INVESTMENTS FOUND:\n";
            foreach ($largeInvestments as $inv) {
                echo "ID: {$inv['id']}, Shares: " . number_format($inv['shares']) . ", Amount: $" . number_format($inv['amount']) . ", Status: {$inv['status']}\n";
            }
        }
        
    } else {
        echo "❌ No active phase found!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>
