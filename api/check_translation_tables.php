<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Translation Tables Structure ===\n\n";
    
    // Check translation_keys table
    echo "translation_keys table:\n";
    $result = $db->query('DESCRIBE translation_keys');
    while($row = $result->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Check translations table
    echo "translations table:\n";
    $result = $db->query('DESCRIBE translations');
    while($row = $result->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Show sample data
    echo "Sample translation_keys:\n";
    $result = $db->query('SELECT * FROM translation_keys LIMIT 3');
    while($row = $result->fetch()) {
        print_r($row);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
