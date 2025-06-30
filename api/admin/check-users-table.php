<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if users table exists
    $tableQuery = "SHOW TABLES LIKE 'users'";
    $tableStmt = $db->prepare($tableQuery);
    $tableStmt->execute();
    $tableExists = $tableStmt->fetch() !== false;
    
    $result = [
        'users_table_exists' => $tableExists,
        'tables' => []
    ];
    
    // Get all tables
    $allTablesQuery = "SHOW TABLES";
    $allTablesStmt = $db->prepare($allTablesQuery);
    $allTablesStmt->execute();
    $tables = $allTablesStmt->fetchAll(PDO::FETCH_COLUMN);
    $result['tables'] = $tables;
    
    if ($tableExists) {
        // Get table structure
        $structureQuery = "DESCRIBE users";
        $structureStmt = $db->prepare($structureQuery);
        $structureStmt->execute();
        $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
        $result['users_table_structure'] = $structure;
        
        // Get sample data
        $sampleQuery = "SELECT * FROM users LIMIT 3";
        $sampleStmt = $db->prepare($sampleQuery);
        $sampleStmt->execute();
        $sample = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        $result['sample_users'] = $sample;
        
        // Get count
        $countQuery = "SELECT COUNT(*) as count FROM users";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute();
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        $result['user_count'] = $count['count'];
    } else {
        // Try to create the table
        $database->createTables();
        
        // Check again
        $tableStmt->execute();
        $tableExists = $tableStmt->fetch() !== false;
        $result['users_table_created'] = $tableExists;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
