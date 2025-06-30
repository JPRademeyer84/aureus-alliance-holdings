<?php
/**
 * Fix Investment Packages - Restore Original Package Names with Phase System
 */

try {
    $pdo = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔧 Fixing Investment Packages...\n\n";

    // Clear all existing packages
    echo "🗑️ Clearing existing packages...\n";
    $pdo->exec("DELETE FROM investment_packages");

    // Get phase IDs
    $stmt = $pdo->prepare("SELECT id, phase_number FROM phases ORDER BY phase_number");
    $stmt->execute();
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Original package structure (your actual packages)
    $originalPackages = [
        'Shovel' => ['base_price' => 25.00, 'shares' => 5, 'icon' => 'star', 'color' => 'bg-green-500'],
        'Pick' => ['base_price' => 50.00, 'shares' => 10, 'icon' => 'square', 'color' => 'bg-amber-700'],
        'Miner' => ['base_price' => 75.00, 'shares' => 15, 'icon' => 'circle', 'color' => 'bg-gray-300'],
        'Loader' => ['base_price' => 100.00, 'shares' => 20, 'icon' => 'diamond', 'color' => 'bg-yellow-500'],
        'Excavator' => ['base_price' => 250.00, 'shares' => 50, 'icon' => 'crown', 'color' => 'bg-purple-500'],
        'Crusher' => ['base_price' => 500.00, 'shares' => 100, 'icon' => 'gem', 'color' => 'bg-blue-500'],
        'Refinery' => ['base_price' => 750.00, 'shares' => 150, 'icon' => 'square', 'color' => 'bg-red-500'],
        'Aureus' => ['base_price' => 1000.00, 'shares' => 200, 'icon' => 'gem', 'color' => 'bg-gold-500']
    ];
    
    // Phase pricing multipliers (Phase 1 = $5/share, Phase 2 = $10/share, etc.)
    $phaseMultipliers = [
        1 => 5.00,   // $5 per share
        2 => 10.00,  // $10 per share
        3 => 15.00,  // $15 per share
        4 => 20.00,  // $20 per share
        5 => 25.00,  // $25 per share
        6 => 30.00,  // $30 per share
        7 => 35.00,  // $35 per share
        8 => 40.00,  // $40 per share
        9 => 45.00,  // $45 per share
        10 => 50.00, // $50 per share
        11 => 55.00, // $55 per share
        12 => 60.00, // $60 per share
        13 => 65.00, // $65 per share
        14 => 70.00, // $70 per share
        15 => 75.00, // $75 per share
        16 => 80.00, // $80 per share
        17 => 85.00, // $85 per share
        18 => 90.00, // $90 per share
        19 => 95.00, // $95 per share
        20 => 100.00 // $100 per share
    ];
    
    echo "📦 Creating packages for each phase...\n";
    
    foreach ($phases as $phase) {
        $phaseNumber = $phase['phase_number'];
        $phaseId = $phase['id'];
        $pricePerShare = $phaseMultipliers[$phaseNumber] ?? 100.00;
        
        // Only activate Phase 1 packages initially
        $isActive = ($phaseNumber == 1) ? 1 : 0;
        
        foreach ($originalPackages as $packageName => $packageData) {
            $shares = $packageData['shares'];
            $totalPrice = $shares * $pricePerShare;
            
            // Create bonuses based on package tier
            $bonuses = [];
            switch ($packageName) {
                case 'Shovel':
                    $bonuses = ["Phase {$phaseNumber} NFT Share Certificate", "Quarterly Dividend Payments"];
                    break;
                case 'Pick':
                    $bonuses = ["All Shovel Bonuses", "Mining Production Reports"];
                    break;
                case 'Miner':
                    $bonuses = ["All Pick Bonuses", "Priority Support"];
                    break;
                case 'Loader':
                    $bonuses = ["All Miner Bonuses", "Exclusive Phase {$phaseNumber} Benefits"];
                    break;
                case 'Excavator':
                    $bonuses = ["All Loader Bonuses", "VIP Access", "Monthly Updates"];
                    break;
                case 'Crusher':
                    $bonuses = ["All Excavator Bonuses", "Quarterly Briefings", "Beta Access"];
                    break;
                case 'Refinery':
                    $bonuses = ["All Crusher Bonuses", "Executive Reports", "Priority Features"];
                    break;
                case 'Aureus':
                    $bonuses = ["All Refinery Bonuses", "Direct Access", "VIP Events", "Personal Consultation"];
                    break;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO investment_packages (
                    name, price, shares, roi, icon, icon_color, bonuses,
                    phase_id, is_active, commission_percentage, competition_allocation,
                    npo_allocation, platform_allocation, mine_allocation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $packageName,
                $totalPrice,
                $shares,
                0.00, // ROI removed as per new business model
                $packageData['icon'],
                $packageData['color'],
                json_encode($bonuses),
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
    
    echo "\n🎉 Package restoration complete!\n";
    echo "📊 Your original packages (Shovel, Pick, Miner, Loader, Excavator, Crusher, Refinery, Aureus) are now integrated with the phase system.\n";
    echo "🚀 Phase 1 packages are active and ready for investment!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
