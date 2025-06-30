<?php
require_once 'config/database.php';

try {
    echo "<h2>Running Commission Tables Migration</h2>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    echo "<p>✅ Database connection established</p>";
    
    // Read migration file
    $migrationPath = __DIR__ . '/../database/migrations/create_commission_tables.sql';
    
    if (!file_exists($migrationPath)) {
        throw new Exception('Migration file not found: ' . $migrationPath);
    }
    
    $migrationSql = file_get_contents($migrationPath);
    
    if (!$migrationSql) {
        throw new Exception('Failed to read migration file');
    }
    
    echo "<p>✅ Migration file loaded</p>";
    
    // Execute migration
    $db->exec($migrationSql);
    
    echo "<p>✅ Commission tables created successfully</p>";
    
    // Check if tables were created
    $tables = ['commission_plans', 'commission_transactions', 'referral_relationships', 'commission_payouts', 'payout_transaction_items'];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo "<p>✅ Table '$table' created successfully</p>";
        } else {
            echo "<p>❌ Table '$table' not found</p>";
        }
    }
    
    // Check if default commission plan was inserted
    $query = "SELECT COUNT(*) as count FROM commission_plans WHERE is_default = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<p>✅ Default commission plan created</p>";
    } else {
        echo "<p>❌ Default commission plan not found</p>";
    }
    
    echo "<h3>Migration completed successfully!</h3>";
    echo "<p><a href='admin/commission-plans.php'>Test Commission Plans API</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
