<?php
// Add profile columns to users table if they don't exist
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check existing columns
    $columnsQuery = "SHOW COLUMNS FROM users";
    $columnsStmt = $db->prepare($columnsQuery);
    $columnsStmt->execute();
    $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [];
    
    // Define columns that should exist
    $requiredColumns = [
        'full_name' => 'VARCHAR(255) NULL',
        'whatsapp_number' => 'VARCHAR(20) NULL',
        'telegram_username' => 'VARCHAR(100) NULL',
        'twitter_handle' => 'VARCHAR(100) NULL',
        'instagram_handle' => 'VARCHAR(100) NULL',
        'linkedin_profile' => 'VARCHAR(255) NULL'
    ];
    
    // Check which columns need to be added
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $columnsToAdd[$column] = $definition;
        }
    }
    
    // Add missing columns
    $addedColumns = [];
    foreach ($columnsToAdd as $column => $definition) {
        try {
            $alterQuery = "ALTER TABLE users ADD COLUMN $column $definition";
            $db->exec($alterQuery);
            $addedColumns[] = $column;
        } catch (Exception $e) {
            // Column might already exist, continue
            error_log("Failed to add column $column: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile columns checked and updated',
        'existing_columns' => $existingColumns,
        'added_columns' => $addedColumns,
        'columns_needed' => array_keys($columnsToAdd)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update table structure: ' . $e->getMessage()
    ]);
}
?>
