<?php
/**
 * CREATE NFT COUPONS TABLES
 * Manually creates the NFT coupons system tables
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 CREATING NFT COUPONS TABLES\n";
    echo "==============================\n\n";
    
    // Read the migration file
    $migrationPath = __DIR__ . '/../../database/migrations/create_nft_coupons_table.sql';
    
    if (!file_exists($migrationPath)) {
        throw new Exception("Migration file not found: $migrationPath");
    }
    
    $migrationSql = file_get_contents($migrationPath);
    
    if (!$migrationSql) {
        throw new Exception("Failed to read migration file");
    }
    
    echo "Executing migration...\n";
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        try {
            $db->exec($statement);
            $successCount++;
            
            // Extract table name for logging
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT.*?INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ Inserted data into: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✅ Altered table: {$matches[1]}\n";
            } else {
                echo "✅ Executed statement\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n==============================\n";
    echo "🎉 MIGRATION COMPLETED\n";
    echo "==============================\n";
    echo "Successful statements: $successCount\n";
    echo "Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "✅ All tables created successfully!\n";
    } else {
        echo "⚠️ Some statements failed. Check errors above.\n";
    }
    
    // Verify tables were created
    echo "\nVerifying tables...\n";
    
    $tables = ['nft_coupons', 'user_credits', 'credit_transactions'];
    
    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✅ $table: $count records\n";
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nMigration completed at: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "❌ MIGRATION FAILED: " . $e->getMessage() . "\n";
}
?>
