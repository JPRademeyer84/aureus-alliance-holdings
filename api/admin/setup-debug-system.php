<?php
/**
 * Setup Debug System
 * Creates debug configuration tables and default settings
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 SETTING UP DEBUG SYSTEM\n";
    echo "==========================\n\n";
    
    // Read and execute the migration file
    $migrationPath = __DIR__ . '/../../database/migrations/create_debug_config_table.sql';
    
    if (!file_exists($migrationPath)) {
        throw new Exception("Migration file not found: $migrationPath");
    }
    
    $migrationSql = file_get_contents($migrationPath);
    
    if (!$migrationSql) {
        throw new Exception("Failed to read migration file");
    }
    
    echo "Executing debug system migration...\n";
    
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
            
            // Extract operation type for logging
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
            
            // Check if it's a "already exists" error (which is OK)
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "⚠️ Already exists: " . substr($statement, 0, 50) . "...\n";
                $errorCount--; // Don't count as error
                $successCount++;
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                echo "   Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n==============================\n";
    echo "🎉 DEBUG SYSTEM SETUP COMPLETED\n";
    echo "==============================\n";
    echo "Successful operations: $successCount\n";
    echo "Failed operations: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "✅ Debug system setup successfully!\n";
    } else {
        echo "⚠️ Some operations failed. Check errors above.\n";
    }
    
    // Verify tables were created
    echo "\nVerifying debug system tables...\n";
    
    $tables = ['debug_config', 'debug_sessions'];
    
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
    
    // Show available debug features
    echo "\nAvailable debug features:\n";
    
    try {
        $featuresQuery = "
            SELECT feature_key, feature_name, is_enabled, is_visible, access_level
            FROM debug_config 
            ORDER BY feature_name
        ";
        $featuresStmt = $db->prepare($featuresQuery);
        $featuresStmt->execute();
        $features = $featuresStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($features as $feature) {
            $status = $feature['is_enabled'] ? '🟢 ENABLED' : '🔴 DISABLED';
            $visibility = $feature['is_visible'] ? 'VISIBLE' : 'HIDDEN';
            echo "  - {$feature['feature_name']} ({$feature['feature_key']}): $status, $visibility, {$feature['access_level']}\n";
        }
        
        $enabledCount = count(array_filter($features, fn($f) => $f['is_enabled']));
        echo "\nSummary: $enabledCount of " . count($features) . " debug features are enabled\n";
        
    } catch (Exception $e) {
        echo "❌ Error fetching debug features: " . $e->getMessage() . "\n";
    }
    
    echo "\n==============================\n";
    echo "🎯 NEXT STEPS\n";
    echo "==============================\n";
    echo "1. Access admin panel → Debug Manager\n";
    echo "2. Configure debug features as needed\n";
    echo "3. Use Ctrl+Shift+D to open debug panel\n";
    echo "4. Monitor debug activity in sessions tab\n";
    echo "\nDebug system is ready for use!\n";
    echo "\nSetup completed at: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "❌ DEBUG SYSTEM SETUP FAILED: " . $e->getMessage() . "\n";
}
?>
