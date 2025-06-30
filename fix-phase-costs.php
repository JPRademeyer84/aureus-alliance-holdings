<?php
/**
 * Fix Phase Costs - Update to correct pricing structure
 */

try {
    $pdo = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "💰 Fixing Phase Costs to Correct Pricing...\n\n";
    
    // Correct phase pricing structure
    $correctPhaseCosts = [
        0 => 5.00,    // Pre Sale
        1 => 10.00,   // Phase 1
        2 => 15.00,   // Phase 2
        3 => 20.00,   // Phase 3
        4 => 25.00,   // Phase 4
        5 => 30.00,   // Phase 5
        6 => 35.00,   // Phase 6
        7 => 40.00,   // Phase 7
        8 => 45.00,   // Phase 8
        9 => 50.00,   // Phase 9
        10 => 100.00, // Phase 10
        11 => 200.00, // Phase 11
        12 => 300.00, // Phase 12
        13 => 400.00, // Phase 13
        14 => 500.00, // Phase 14
        15 => 600.00, // Phase 15
        16 => 700.00, // Phase 16
        17 => 800.00, // Phase 17
        18 => 900.00, // Phase 18
        19 => 1000.00 // Phase 19
    ];
    
    // Update phase names and costs
    foreach ($correctPhaseCosts as $phaseNumber => $costPerShare) {
        if ($phaseNumber == 0) {
            $phaseName = "Pre Sale";
            $actualPhaseNumber = 1; // Pre Sale is stored as phase 1 in DB
        } else {
            $phaseName = "Phase {$phaseNumber}";
            $actualPhaseNumber = $phaseNumber + 1; // Shift by 1 since Pre Sale is phase 1
        }
        
        $stmt = $pdo->prepare("
            UPDATE phases SET 
                name = ?,
                cost_per_share = ?,
                updated_at = NOW()
            WHERE phase_number = ?
        ");
        
        $stmt->execute([$phaseName, $costPerShare, $actualPhaseNumber]);
        
        if ($phaseNumber == 0) {
            echo "✅ Pre Sale (Phase 1): \${$costPerShare}/share\n";
        } else {
            echo "✅ Phase {$phaseNumber} (DB Phase " . ($phaseNumber + 1) . "): \${$costPerShare}/share\n";
        }
    }
    
    // Update any remaining phases to inactive if they exist
    $stmt = $pdo->prepare("
        UPDATE phases SET 
            is_active = FALSE,
            name = CONCAT('Phase ', phase_number - 1),
            cost_per_share = 1000.00
        WHERE phase_number > 20
    ");
    $stmt->execute();
    
    echo "\n🎉 Phase costs updated successfully!\n";
    echo "📊 Price progression:\n";
    echo "   Pre Sale: \$5/share → Shovel = \$25, Aureus = \$1,000\n";
    echo "   Phase 1: \$10/share → Shovel = \$50, Aureus = \$2,000\n";
    echo "   Phase 9: \$50/share → Shovel = \$250, Aureus = \$10,000\n";
    echo "   Phase 10: \$100/share → Shovel = \$500, Aureus = \$20,000\n";
    echo "   Phase 19: \$1,000/share → Shovel = \$5,000, Aureus = \$200,000\n";
    
    // Show current pricing
    echo "\n💰 Current Pricing (Pre Sale - \$5.00/share):\n";
    $stmt = $pdo->query("
        SELECT ip.name, ip.shares, (ip.shares * p.cost_per_share) as price
        FROM investment_packages ip
        CROSS JOIN phases p
        WHERE p.is_active = TRUE
        ORDER BY price
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['name']}: {$row['shares']} shares × \$5.00 = \${$row['price']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
