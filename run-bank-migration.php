<?php
$host = 'localhost';
$port = 3506;
$user = 'root';
$password = '';
$database = 'aureus_angels';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== RUNNING BANK PAYMENT SYSTEM MIGRATION ===\n";
    
    // Read the SQL file
    $sql = file_get_contents('database/migrations/create_bank_payment_system.sql');
    
    if (!$sql) {
        throw new Exception("Could not read migration file");
    }
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'USE ') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Extract table name for reporting
            if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
                echo "✅ Inserted data into: {$matches[1]}\n";
            } else {
                echo "✅ Executed statement\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== MIGRATION COMPLETE ===\n";
    echo "✅ Successful statements: $successCount\n";
    echo "❌ Failed statements: $errorCount\n";
    
    // Verify tables were created
    echo "\n=== VERIFYING TABLES ===\n";
    $tables = ['country_payment_config', 'company_bank_accounts', 'bank_payment_transactions', 'bank_payment_commissions', 'payment_method_log'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Table '$table' exists with $count records\n";
        } catch (Exception $e) {
            echo "❌ Table '$table' does not exist or has issues\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
