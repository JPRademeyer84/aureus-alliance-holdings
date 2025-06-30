<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Step 1: Checking current users table structure...\n";
    
    // Check current table structure
    $structureQuery = "DESCRIBE users";
    $structureStmt = $db->prepare($structureQuery);
    $structureStmt->execute();
    $currentStructure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current table structure:\n";
    foreach ($currentStructure as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if is_active column exists
    $hasIsActive = false;
    $hasUpdatedAt = false;
    foreach ($currentStructure as $column) {
        if ($column['Field'] === 'is_active') {
            $hasIsActive = true;
        }
        if ($column['Field'] === 'updated_at') {
            $hasUpdatedAt = true;
        }
    }
    
    echo "\nStep 2: Checking missing columns...\n";
    echo "is_active column exists: " . ($hasIsActive ? "YES" : "NO") . "\n";
    echo "updated_at column exists: " . ($hasUpdatedAt ? "YES" : "NO") . "\n";
    
    // Add missing columns
    if (!$hasIsActive) {
        echo "\nStep 3: Adding is_active column...\n";
        $addIsActiveQuery = "ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE";
        $db->exec($addIsActiveQuery);
        echo "is_active column added successfully\n";
    }
    
    if (!$hasUpdatedAt) {
        echo "\nStep 4: Adding updated_at column...\n";
        $addUpdatedAtQuery = "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $db->exec($addUpdatedAtQuery);
        echo "updated_at column added successfully\n";
    }
    
    // Add index if needed
    echo "\nStep 5: Adding indexes...\n";
    try {
        $addIndexQuery = "CREATE INDEX IF NOT EXISTS idx_is_active ON users (is_active)";
        $db->exec($addIndexQuery);
        echo "Index on is_active added\n";
    } catch (Exception $e) {
        echo "Index might already exist: " . $e->getMessage() . "\n";
    }
    
    // Check final structure
    echo "\nStep 6: Final table structure:\n";
    $structureStmt->execute();
    $finalStructure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalStructure as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ") " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
             ($column['Default'] ? ' DEFAULT ' . $column['Default'] : '') . "\n";
    }
    
    // Test a simple query
    echo "\nStep 7: Testing query...\n";
    $testQuery = "SELECT COUNT(*) as count FROM users";
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute();
    $count = $testStmt->fetch(PDO::FETCH_ASSOC);
    echo "Current user count: " . $count['count'] . "\n";
    
    // Test the problematic query
    echo "\nStep 8: Testing full query...\n";
    $fullQuery = "SELECT id, username, email, is_active, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT 5";
    $fullStmt = $db->prepare($fullQuery);
    $fullStmt->execute();
    $users = $fullStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query successful! Found " . count($users) . " users\n";
    
    if (count($users) > 0) {
        echo "Sample user data:\n";
        foreach ($users as $user) {
            echo "- " . $user['username'] . " (" . $user['email'] . ") - Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Users table structure fixed successfully',
        'data' => [
            'final_structure' => $finalStructure,
            'user_count' => $count['count'],
            'sample_users' => $users
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
