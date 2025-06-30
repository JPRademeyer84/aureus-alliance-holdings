<?php
/**
 * Improve Phase System Architecture
 * - Add cost_per_share to phases table
 * - Remove phase_id from investment_packages
 * - Keep only 8 packages with dynamic pricing
 */

try {
    $pdo = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Improving Phase System Architecture...\n\n";
    
    // Step 1: Add cost_per_share column to phases table
    echo "📊 Adding cost_per_share column to phases table...\n";
    try {
        $pdo->exec("ALTER TABLE phases ADD COLUMN cost_per_share DECIMAL(10,2) DEFAULT 5.00");
        echo "✅ Added cost_per_share column\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ cost_per_share column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Update phases with cost_per_share values
    echo "💰 Setting cost_per_share for each phase...\n";
    $phaseData = [
        1 => 5.00,   2 => 10.00,  3 => 15.00,  4 => 20.00,  5 => 25.00,
        6 => 30.00,  7 => 35.00,  8 => 40.00,  9 => 45.00,  10 => 50.00,
        11 => 55.00, 12 => 60.00, 13 => 65.00, 14 => 70.00, 15 => 75.00,
        16 => 80.00, 17 => 85.00, 18 => 90.00, 19 => 95.00, 20 => 100.00
    ];
    
    foreach ($phaseData as $phaseNumber => $costPerShare) {
        $stmt = $pdo->prepare("UPDATE phases SET cost_per_share = ? WHERE phase_number = ?");
        $stmt->execute([$costPerShare, $phaseNumber]);
        echo "✅ Phase {$phaseNumber}: \${$costPerShare}/share\n";
    }
    
    // Step 3: Remove phase_id from investment_packages table
    echo "\n🗑️ Removing phase_id dependency from investment_packages...\n";
    try {
        $pdo->exec("ALTER TABLE investment_packages DROP COLUMN phase_id");
        echo "✅ Removed phase_id column from investment_packages\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), "check that column/key exists") !== false) {
            echo "ℹ️ phase_id column already removed\n";
        } else {
            throw $e;
        }
    }
    
    // Step 4: Clear and recreate the 8 core packages (no phase dependency)
    echo "\n📦 Creating the 8 core packages...\n";
    $pdo->exec("DELETE FROM investment_packages");
    
    $corePackages = [
        ['name' => 'Shovel', 'shares' => 5, 'icon' => 'star', 'color' => 'bg-green-500'],
        ['name' => 'Pick', 'shares' => 10, 'icon' => 'square', 'color' => 'bg-amber-700'],
        ['name' => 'Miner', 'shares' => 15, 'icon' => 'circle', 'color' => 'bg-gray-300'],
        ['name' => 'Loader', 'shares' => 20, 'icon' => 'diamond', 'color' => 'bg-yellow-500'],
        ['name' => 'Excavator', 'shares' => 50, 'icon' => 'crown', 'color' => 'bg-purple-500'],
        ['name' => 'Crusher', 'shares' => 100, 'icon' => 'gem', 'color' => 'bg-blue-500'],
        ['name' => 'Refinery', 'shares' => 150, 'icon' => 'square', 'color' => 'bg-red-500'],
        ['name' => 'Aureus', 'shares' => 200, 'icon' => 'gem', 'color' => 'bg-gold-500']
    ];
    
    foreach ($corePackages as $pkg) {
        $bonuses = json_encode([
            "NFT Share Certificate",
            "Quarterly Dividend Payments",
            "Mining Production Reports"
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO investment_packages (
                name, price, shares, roi, icon, icon_color, bonuses,
                is_active, commission_percentage, competition_allocation,
                npo_allocation, platform_allocation, mine_allocation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Set price to 0 - will be calculated dynamically
        $stmt->execute([
            $pkg['name'],
            0.00, // Dynamic pricing
            $pkg['shares'],
            0.00,
            $pkg['icon'],
            $pkg['color'],
            $bonuses,
            1, // All active
            15.00, 15.00, 10.00, 25.00, 35.00
        ]);
        
        echo "✅ Created: {$pkg['name']} ({$pkg['shares']} shares)\n";
    }
    
    // Step 5: Create helper function for dynamic pricing
    echo "\n📝 Creating dynamic pricing helper...\n";
    $helperSQL = "
    CREATE OR REPLACE VIEW package_pricing AS
    SELECT 
        ip.*,
        p.cost_per_share,
        (ip.shares * p.cost_per_share) as calculated_price,
        p.phase_number,
        p.name as phase_name,
        p.is_active as phase_active
    FROM investment_packages ip
    CROSS JOIN phases p
    WHERE p.is_active = TRUE;
    ";
    
    $pdo->exec($helperSQL);
    echo "✅ Created package_pricing view for dynamic pricing\n";
    
    echo "\n🎉 Phase System Architecture Improved!\n";
    echo "📊 Benefits:\n";
    echo "   ✅ Only 8 core packages (no duplication)\n";
    echo "   ✅ Dynamic pricing based on active phase\n";
    echo "   ✅ Cleaner database structure\n";
    echo "   ✅ Easier to manage and maintain\n";
    
    // Show current pricing
    echo "\n💰 Current Pricing (Phase 1 - \$5.00/share):\n";
    $stmt = $pdo->query("SELECT name, shares, calculated_price FROM package_pricing ORDER BY calculated_price");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['name']}: {$row['shares']} shares × \$5.00 = \${$row['calculated_price']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
