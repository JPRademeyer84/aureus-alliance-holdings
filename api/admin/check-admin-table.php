<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Admin Users Table Structure Check</h2>";
    
    // Check if table exists
    $tableQuery = "SHOW TABLES LIKE 'admin_users'";
    $tableStmt = $db->prepare($tableQuery);
    $tableStmt->execute();
    $tableExists = $tableStmt->fetch() !== false;
    
    if (!$tableExists) {
        echo "<p style='color: red;'>ERROR: admin_users table does not exist!</p>";
        echo "<p>Creating tables...</p>";
        $database->createTables();
        echo "<p>Tables created. Checking again...</p>";
        
        $tableStmt->execute();
        $tableExists = $tableStmt->fetch() !== false;
    }
    
    if ($tableExists) {
        echo "<p style='color: green;'>✓ admin_users table exists</p>";
        
        // Get table structure
        $structureQuery = "DESCRIBE admin_users";
        $structureStmt = $db->prepare($structureQuery);
        $structureStmt->execute();
        $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
        echo "</tr>";
        
        $hasChatStatus = false;
        foreach ($structure as $column) {
            if ($column['Field'] === 'chat_status') {
                $hasChatStatus = true;
            }
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!$hasChatStatus) {
            echo "<p style='color: red;'>ERROR: chat_status column is missing!</p>";
            echo "<p>Adding chat_status column...</p>";
            
            $addColumnQuery = "ALTER TABLE admin_users ADD COLUMN chat_status ENUM('online', 'offline', 'busy') DEFAULT 'offline'";
            $db->exec($addColumnQuery);
            
            echo "<p style='color: green;'>✓ chat_status column added</p>";
        } else {
            echo "<p style='color: green;'>✓ chat_status column exists</p>";
        }
        
        // Test update query
        echo "<h3>Testing Update Query:</h3>";
        $testAdminId = 'test-admin-id';
        $testStatus = 'online';
        
        $updateQuery = "UPDATE admin_users SET chat_status = ?, last_activity = CURRENT_TIMESTAMP WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        echo "<p>Query: " . htmlspecialchars($updateQuery) . "</p>";
        echo "<p>Parameters: ['" . htmlspecialchars($testStatus) . "', '" . htmlspecialchars($testAdminId) . "']</p>";
        
        // Get current admin for real test
        $adminQuery = "SELECT id, username, chat_status FROM admin_users WHERE is_active = TRUE LIMIT 1";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<h3>Real Admin Test:</h3>";
            echo "<p>Testing with admin: " . htmlspecialchars($admin['username']) . " (ID: " . htmlspecialchars($admin['id']) . ")</p>";
            echo "<p>Current status: " . htmlspecialchars($admin['chat_status'] ?? 'NULL') . "</p>";
            
            // Try to update to online
            $realUpdateStmt = $db->prepare($updateQuery);
            $success = $realUpdateStmt->execute(['online', $admin['id']]);
            
            if ($success) {
                echo "<p style='color: green;'>✓ Update successful</p>";
                
                // Check if it was actually updated
                $checkStmt = $db->prepare("SELECT chat_status FROM admin_users WHERE id = ?");
                $checkStmt->execute([$admin['id']]);
                $newStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<p>New status: " . htmlspecialchars($newStatus['chat_status'] ?? 'NULL') . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Update failed</p>";
                $errorInfo = $realUpdateStmt->errorInfo();
                echo "<p>Error: " . htmlspecialchars(print_r($errorInfo, true)) . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>No active admin found for testing</p>";
        }
        
    } else {
        echo "<p style='color: red;'>ERROR: Could not create admin_users table!</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
