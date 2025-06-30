<?php
require_once 'config/database.php';
require_once 'config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();
    
    echo "<h2>Admin Debug Information</h2>";
    
    // Get all admin users
    $query = "SELECT * FROM admin_users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Admin Users in Database:</h3>";
    foreach ($admins as $admin) {
        echo "<p>ID: {$admin['id']}, Username: {$admin['username']}, Created: {$admin['created_at']}</p>";
    }
    
    // Check if company_wallets table exists and show structure
    $query = "SHOW TABLES LIKE 'company_wallets'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<h3>company_wallets table exists</h3>";
        
        $query = "DESCRIBE company_wallets";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show existing wallets
        $query = "SELECT * FROM company_wallets";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Existing Wallets:</h3>";
        if (count($wallets) > 0) {
            foreach ($wallets as $wallet) {
                echo "<p>Chain: {$wallet['chain']}, Active: " . ($wallet['is_active'] ? 'Yes' : 'No') . ", Created: {$wallet['created_at']}</p>";
            }
        } else {
            echo "<p>No wallets found</p>";
        }
    } else {
        echo "<h3>company_wallets table does NOT exist</h3>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
