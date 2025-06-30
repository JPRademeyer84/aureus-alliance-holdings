<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Admin Users Status Debug</h2>";
    
    // Get all admin users with their status
    $query = "SELECT id, username, email, role, is_active, chat_status, last_activity, created_at FROM admin_users ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Admin Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Role</th><th>Is Active</th><th>Chat Status</th><th>Last Activity</th>";
    echo "</tr>";
    
    foreach ($admins as $admin) {
        $bgColor = $admin['is_active'] ? '#e8f5e8' : '#f5e8e8';
        echo "<tr style='background-color: $bgColor;'>";
        echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
        echo "<td>" . ($admin['is_active'] ? 'YES' : 'NO') . "</td>";
        echo "<td><strong>" . htmlspecialchars($admin['chat_status'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . htmlspecialchars($admin['last_activity'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get online count
    $onlineQuery = "SELECT COUNT(*) as online_count FROM admin_users WHERE chat_status = 'online' AND is_active = TRUE";
    $onlineStmt = $db->prepare($onlineQuery);
    $onlineStmt->execute();
    $onlineCount = $onlineStmt->fetch(PDO::FETCH_ASSOC)['online_count'];
    
    // Get busy count
    $busyQuery = "SELECT COUNT(*) as busy_count FROM admin_users WHERE chat_status = 'busy' AND is_active = TRUE";
    $busyStmt = $db->prepare($busyQuery);
    $busyStmt->execute();
    $busyCount = $busyStmt->fetch(PDO::FETCH_ASSOC)['busy_count'];
    
    echo "<h3>Status Counts:</h3>";
    echo "<p><strong>Online Agents:</strong> $onlineCount</p>";
    echo "<p><strong>Busy Agents:</strong> $busyCount</p>";
    echo "<p><strong>Available for Chat:</strong> " . ($onlineCount > 0 ? 'YES' : 'NO') . "</p>";
    
    // Test the agent status API response
    echo "<h3>Agent Status API Response:</h3>";
    echo "<pre>";
    
    $result = [
        'online_count' => intval($onlineCount),
        'busy_count' => intval($busyCount),
        'available' => intval($onlineCount) > 0,
        'status' => intval($onlineCount) > 0 ? 'online' : 'offline',
        'message' => intval($onlineCount) > 0 
            ? ($onlineCount == 1 ? '1 agent available' : $onlineCount . ' agents available')
            : 'No agents currently available'
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Check specific status values
    echo "<h3>Status Value Analysis:</h3>";
    $statusQuery = "SELECT chat_status, COUNT(*) as count FROM admin_users WHERE is_active = TRUE GROUP BY chat_status";
    $statusStmt = $db->prepare($statusQuery);
    $statusStmt->execute();
    $statusCounts = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Status Value</th><th>Count</th></tr>";
    foreach ($statusCounts as $status) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status['chat_status'] ?? 'NULL') . "</td>";
        echo "<td>" . $status['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
