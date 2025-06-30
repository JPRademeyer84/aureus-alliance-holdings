<?php
/**
 * Setup the 20-Phase System with proper share limits and pricing
 * This script configures the phase system for the Aureus investment platform
 */

try {
    $pdo = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🚀 Setting up 20-Phase Investment System...\n\n";
    
    // Phase configuration with share limits and pricing
    $phaseConfig = [
        1 => ['name' => 'Presale Phase', 'shares' => 200000, 'price_per_share' => 5.00, 'description' => 'Initial presale with foundation setup'],
        2 => ['name' => 'Phase 2', 'shares' => 150000, 'price_per_share' => 10.00, 'description' => 'Growth phase with expanded features'],
        3 => ['name' => 'Phase 3', 'shares' => 120000, 'price_per_share' => 15.00, 'description' => 'Expansion phase with new markets'],
        4 => ['name' => 'Phase 4', 'shares' => 100000, 'price_per_share' => 20.00, 'description' => 'Development phase with advanced features'],
        5 => ['name' => 'Phase 5', 'shares' => 90000, 'price_per_share' => 25.00, 'description' => 'Innovation phase with cutting-edge technology'],
        6 => ['name' => 'Phase 6', 'shares' => 80000, 'price_per_share' => 30.00, 'description' => 'Scaling phase with market expansion'],
        7 => ['name' => 'Phase 7', 'shares' => 75000, 'price_per_share' => 35.00, 'description' => 'Optimization phase with efficiency improvements'],
        8 => ['name' => 'Phase 8', 'shares' => 70000, 'price_per_share' => 40.00, 'description' => 'Enhancement phase with feature upgrades'],
        9 => ['name' => 'Phase 9', 'shares' => 65000, 'price_per_share' => 45.00, 'description' => 'Acceleration phase with rapid growth'],
        10 => ['name' => 'Phase 10', 'shares' => 60000, 'price_per_share' => 50.00, 'description' => 'Milestone phase with major achievements'],
        11 => ['name' => 'Phase 11', 'shares' => 55000, 'price_per_share' => 55.00, 'description' => 'Advanced phase with premium features'],
        12 => ['name' => 'Phase 12', 'shares' => 50000, 'price_per_share' => 60.00, 'description' => 'Elite phase with exclusive benefits'],
        13 => ['name' => 'Phase 13', 'shares' => 45000, 'price_per_share' => 65.00, 'description' => 'Premium phase with enhanced rewards'],
        14 => ['name' => 'Phase 14', 'shares' => 40000, 'price_per_share' => 70.00, 'description' => 'Superior phase with top-tier access'],
        15 => ['name' => 'Phase 15', 'shares' => 35000, 'price_per_share' => 75.00, 'description' => 'Excellence phase with maximum benefits'],
        16 => ['name' => 'Phase 16', 'shares' => 30000, 'price_per_share' => 80.00, 'description' => 'Ultimate phase with exclusive privileges'],
        17 => ['name' => 'Phase 17', 'shares' => 25000, 'price_per_share' => 85.00, 'description' => 'Final expansion phase'],
        18 => ['name' => 'Phase 18', 'shares' => 20000, 'price_per_share' => 90.00, 'description' => 'Pre-completion phase'],
        19 => ['name' => 'Phase 19', 'shares' => 15000, 'price_per_share' => 95.00, 'description' => 'Near-completion phase'],
        20 => ['name' => 'Phase 20', 'shares' => 10000, 'price_per_share' => 100.00, 'description' => 'Final phase - completion']
    ];
    
    // Revenue distribution for all phases
    $revenueDistribution = json_encode([
        'commission' => 15,
        'competition' => 15,
        'platform' => 25,
        'npo' => 10,
        'mine' => 35
    ]);
    
    // Update each phase with proper configuration
    foreach ($phaseConfig as $phaseNumber => $config) {
        $stmt = $pdo->prepare("
            UPDATE phases SET 
                name = ?,
                description = ?,
                total_packages_available = ?,
                revenue_distribution = ?,
                updated_at = NOW()
            WHERE phase_number = ?
        ");
        
        $stmt->execute([
            $config['name'],
            $config['description'],
            $config['shares'],
            $revenueDistribution,
            $phaseNumber
        ]);
        
        echo "✅ Updated Phase {$phaseNumber}: {$config['name']} - {$config['shares']} shares at \${$config['price_per_share']}/share\n";
    }
    
    // Activate Phase 1 (Presale) by default
    $stmt = $pdo->prepare("
        UPDATE phases SET 
            is_active = TRUE,
            start_date = NOW()
        WHERE phase_number = 1
    ");
    $stmt->execute();
    
    echo "\n🎯 Phase 1 (Presale) activated!\n";
    
    // Create phase-based investment packages
    echo "\n📦 Creating phase-based investment packages...\n";
    
    // Clear existing packages first
    $pdo->exec("DELETE FROM investment_packages");
    
    // Create packages for each phase
    foreach ($phaseConfig as $phaseNumber => $config) {
        // Get phase ID
        $stmt = $pdo->prepare("SELECT id FROM phases WHERE phase_number = ?");
        $stmt->execute([$phaseNumber]);
        $phaseId = $stmt->fetchColumn();
        
        $pricePerShare = $config['price_per_share'];
        
        // Create different package sizes for each phase
        $packageSizes = [
            ['name' => 'Starter', 'shares' => 10, 'icon' => 'star', 'color' => 'bg-green-500'],
            ['name' => 'Bronze', 'shares' => 50, 'icon' => 'square', 'color' => 'bg-amber-700'],
            ['name' => 'Silver', 'shares' => 100, 'icon' => 'circle', 'color' => 'bg-gray-300'],
            ['name' => 'Gold', 'shares' => 250, 'icon' => 'diamond', 'color' => 'bg-yellow-500'],
            ['name' => 'Platinum', 'shares' => 500, 'icon' => 'crown', 'color' => 'bg-purple-600'],
            ['name' => 'Diamond', 'shares' => 1000, 'icon' => 'gem', 'color' => 'bg-blue-600']
        ];
        
        foreach ($packageSizes as $package) {
            $totalPrice = $package['shares'] * $pricePerShare;
            $packageName = "{$package['name']} - Phase {$phaseNumber}";
            
            // Only create packages for active phase (Phase 1) initially
            $isActive = ($phaseNumber == 1) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO investment_packages (
                    name, price, shares, roi, icon, icon_color, bonuses,
                    phase_id, is_active, commission_percentage, competition_allocation,
                    npo_allocation, platform_allocation, mine_allocation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $bonuses = json_encode([
                "Phase {$phaseNumber} NFT Share Certificate",
                "Quarterly Dividend Payments",
                "Mining Production Reports",
                "Exclusive Phase {$phaseNumber} Benefits"
            ]);
            
            $stmt->execute([
                $packageName,
                $totalPrice,
                $package['shares'],
                0.00, // ROI removed as per new business model
                $package['icon'],
                $package['color'],
                $bonuses,
                $phaseId,
                $isActive,
                15.00, // Commission
                15.00, // Competition
                10.00, // NPO
                25.00, // Platform
                35.00  // Mine
            ]);
        }
        
        if ($phaseNumber == 1) {
            echo "✅ Created packages for Phase {$phaseNumber} (ACTIVE)\n";
        } else {
            echo "⏳ Created packages for Phase {$phaseNumber} (INACTIVE)\n";
        }
    }
    
    echo "\n🎉 Phase system setup complete!\n";
    echo "📊 Total shares across all phases: " . array_sum(array_column($phaseConfig, 'shares')) . "\n";
    echo "💰 Price range: \$5.00 - \$100.00 per share\n";
    echo "🚀 Phase 1 (Presale) is now active with 200,000 shares available at \$5.00 each\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
