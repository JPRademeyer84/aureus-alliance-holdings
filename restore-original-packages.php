<?php
/**
 * Restore ONLY the original 8 packages - no phase multiplication
 */

try {
    $pdo = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Restoring ONLY your original 8 packages...\n\n";
    
    // Clear all packages
    $pdo->exec("DELETE FROM investment_packages");
    
    // Get Phase 1 ID
    $stmt = $pdo->prepare("SELECT id FROM phases WHERE phase_number = 1");
    $stmt->execute();
    $phase1Id = $stmt->fetchColumn();
    
    // Your original 8 packages ONLY
    $packages = [
        ['name' => 'Shovel', 'price' => 25.00, 'shares' => 5, 'icon' => 'star', 'color' => 'bg-green-500'],
        ['name' => 'Pick', 'price' => 50.00, 'shares' => 10, 'icon' => 'square', 'color' => 'bg-amber-700'],
        ['name' => 'Miner', 'price' => 75.00, 'shares' => 15, 'icon' => 'circle', 'color' => 'bg-gray-300'],
        ['name' => 'Loader', 'price' => 100.00, 'shares' => 20, 'icon' => 'diamond', 'color' => 'bg-yellow-500'],
        ['name' => 'Excavator', 'price' => 250.00, 'shares' => 50, 'icon' => 'crown', 'color' => 'bg-purple-500'],
        ['name' => 'Crusher', 'price' => 500.00, 'shares' => 100, 'icon' => 'gem', 'color' => 'bg-blue-500'],
        ['name' => 'Refinery', 'price' => 750.00, 'shares' => 150, 'icon' => 'square', 'color' => 'bg-red-500'],
        ['name' => 'Aureus', 'price' => 1000.00, 'shares' => 200, 'icon' => 'gem', 'color' => 'bg-gold-500']
    ];
    
    foreach ($packages as $pkg) {
        $bonuses = json_encode([
            "NFT Share Certificate",
            "Quarterly Dividend Payments",
            "Mining Production Reports"
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO investment_packages (
                name, price, shares, roi, icon, icon_color, bonuses,
                phase_id, is_active, commission_percentage, competition_allocation,
                npo_allocation, platform_allocation, mine_allocation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $pkg['name'],
            $pkg['price'],
            $pkg['shares'],
            0.00,
            $pkg['icon'],
            $pkg['color'],
            $bonuses,
            $phase1Id,
            1, // All active
            15.00, 15.00, 10.00, 25.00, 35.00
        ]);
        
        echo "✅ Restored: {$pkg['name']} - \${$pkg['price']} ({$pkg['shares']} shares)\n";
    }
    
    echo "\n🎉 Done! Restored your original 8 packages only.\n";
    echo "📊 The phase system will track these packages and advance when limits are reached.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
