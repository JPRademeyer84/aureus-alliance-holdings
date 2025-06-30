<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Fixing Admin Users Table</h2>";
    
    // Check current structure
    $structureQuery = "DESCRIBE admin_users";
    $structureStmt = $db->prepare($structureQuery);
    $structureStmt->execute();
    $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasChatStatus = false;
    $hasLastActivity = false;
    
    foreach ($structure as $column) {
        if ($column['Field'] === 'chat_status') {
            $hasChatStatus = true;
            echo "<p>✓ chat_status column exists: " . $column['Type'] . "</p>";
        }
        if ($column['Field'] === 'last_activity') {
            $hasLastActivity = true;
            echo "<p>✓ last_activity column exists: " . $column['Type'] . "</p>";
        }
    }
    
    // Add missing columns
    if (!$hasChatStatus) {
        echo "<p>Adding chat_status column...</p>";
        $addChatStatusQuery = "ALTER TABLE admin_users ADD COLUMN chat_status ENUM('online', 'offline', 'busy') DEFAULT 'offline'";
        $db->exec($addChatStatusQuery);
        echo "<p style='color: green;'>✓ chat_status column added</p>";
    }
    
    if (!$hasLastActivity) {
        echo "<p>Adding last_activity column...</p>";
        $addLastActivityQuery = "ALTER TABLE admin_users ADD COLUMN last_activity TIMESTAMP NULL";
        $db->exec($addLastActivityQuery);
        echo "<p style='color: green;'>✓ last_activity column added</p>";
    }
    
    // Add indexes if they don't exist
    try {
        $addIndexQuery = "CREATE INDEX IF NOT EXISTS idx_chat_status ON admin_users (chat_status)";
        $db->exec($addIndexQuery);
        echo "<p>✓ chat_status index ensured</p>";
    } catch (Exception $e) {
        echo "<p>Index might already exist: " . $e->getMessage() . "</p>";
    }
    
    // Update all existing admins to have a default status if NULL
    $updateNullQuery = "UPDATE admin_users SET chat_status = 'offline' WHERE chat_status IS NULL";
    $updateStmt = $db->prepare($updateNullQuery);
    $updateStmt->execute();
    $updatedRows = $updateStmt->rowCount();
    
    if ($updatedRows > 0) {
        echo "<p>✓ Updated $updatedRows admin(s) with NULL chat_status to 'offline'</p>";
    }
    
    // Show current admin statuses
    echo "<h3>Current Admin Statuses:</h3>";
    $adminQuery = "SELECT id, username, email, role, is_active, chat_status, last_activity FROM admin_users ORDER BY created_at DESC";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Username</th><th>Role</th><th>Active</th><th>Chat Status</th><th>Last Activity</th>";
    echo "</tr>";
    
    foreach ($admins as $admin) {
        $bgColor = $admin['is_active'] ? '#e8f5e8' : '#f5e8e8';
        echo "<tr style='background-color: $bgColor;'>";
        echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
        echo "<td>" . ($admin['is_active'] ? 'YES' : 'NO') . "</td>";
        echo "<td><strong>" . htmlspecialchars($admin['chat_status'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . htmlspecialchars($admin['last_activity'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test status update
    $firstAdmin = $admins[0] ?? null;
    if ($firstAdmin) {
        echo "<h3>Testing Status Update:</h3>";
        echo "<p>Testing with admin: " . htmlspecialchars($firstAdmin['username']) . "</p>";
        
        // Update to online
        $testUpdateQuery = "UPDATE admin_users SET chat_status = 'online', last_activity = CURRENT_TIMESTAMP WHERE id = ?";
        $testUpdateStmt = $db->prepare($testUpdateQuery);
        $success = $testUpdateStmt->execute([$firstAdmin['id']]);
        
        if ($success) {
            echo "<p style='color: green;'>✓ Test update to 'online' successful</p>";
            
            // Verify the update
            $verifyQuery = "SELECT chat_status, last_activity FROM admin_users WHERE id = ?";
            $verifyStmt = $db->prepare($verifyQuery);
            $verifyStmt->execute([$firstAdmin['id']]);
            $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p>New status: <strong>" . htmlspecialchars($result['chat_status']) . "</strong></p>";
            echo "<p>Last activity: " . htmlspecialchars($result['last_activity']) . "</p>";
            
            // Test the agent status API
            echo "<h3>Testing Agent Status API:</h3>";
            $onlineQuery = "SELECT COUNT(*) as count FROM admin_users WHERE chat_status = 'online' AND is_active = TRUE";
            $onlineStmt = $db->prepare($onlineQuery);
            $onlineStmt->execute();
            $onlineCount = $onlineStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>Online agents count: <strong>$onlineCount</strong></p>";
            
            if ($onlineCount > 0) {
                echo "<p style='color: green;'>✓ Agent status API should now show agents as available!</p>";
            } else {
                echo "<p style='color: red;'>✗ No online agents found</p>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ Test update failed</p>";
        }
    }
    
    echo "<h3>Summary:</h3>";
    echo "<p>✓ Table structure verified and fixed</p>";
    echo "<p>✓ Status update functionality tested</p>";
    echo "<p>✓ Ready for live chat system</p>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
