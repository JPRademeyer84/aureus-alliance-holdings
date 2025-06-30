<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding additional_info_status column to user_profiles...\n";
    
    // Check if column already exists
    $checkQuery = "SHOW COLUMNS FROM user_profiles LIKE 'additional_info_status'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Add the column
        $addColumnQuery = "
            ALTER TABLE user_profiles 
            ADD COLUMN additional_info_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' 
            AFTER address_info_status
        ";
        $db->exec($addColumnQuery);
        echo "✓ Added additional_info_status column\n";
    } else {
        echo "✓ additional_info_status column already exists\n";
    }
    
    echo "✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
