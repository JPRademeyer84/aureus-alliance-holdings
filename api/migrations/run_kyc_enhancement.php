<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Running KYC Enhancement Migration...\n";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/../../database/migrations/enhance_user_profiles_kyc.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Remove comments and split SQL into individual statements
    $lines = explode("\n", $sql);
    $cleanedLines = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && !str_starts_with($line, '--')) {
            $cleanedLines[] = $line;
        }
    }

    $cleanedSql = implode("\n", $cleanedLines);
    $statements = array_filter(array_map('trim', explode(';', $cleanedSql)));

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }

        try {
            $db->exec($statement);
            $successCount++;
            echo "✓ Executed statement successfully\n";
            echo "Statement: " . substr(str_replace(["\n", "\r"], ' ', $statement), 0, 80) . "...\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr(str_replace(["\n", "\r"], ' ', $statement), 0, 100) . "...\n";
        }
    }
    
    echo "\nMigration completed!\n";
    echo "Successful statements: $successCount\n";
    echo "Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "✅ All migrations executed successfully!\n";
    } else {
        echo "⚠️ Some migrations failed. Please check the errors above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
