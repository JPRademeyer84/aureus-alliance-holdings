<?php
/**
 * Test Admin Login
 * Try to login as admin to test debug system
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 TESTING ADMIN LOGIN\n";
    echo "======================\n\n";
    
    // Check if admin user exists
    echo "1. Checking admin users...\n";
    
    $query = "SELECT id, username, full_name, role FROM admin_users LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) > 0) {
        echo "✅ Found " . count($admins) . " admin users:\n";
        foreach ($admins as $admin) {
            echo "  - {$admin['username']} ({$admin['full_name']}) - {$admin['role']}\n";
        }
        
        // Try to create a test session with the first admin
        $testAdmin = $admins[0];
        
        echo "\n2. Creating test admin session...\n";
        session_start();
        $_SESSION['admin_id'] = $testAdmin['id'];
        $_SESSION['admin_username'] = $testAdmin['username'];
        $_SESSION['admin_role'] = $testAdmin['role'];
        
        echo "✅ Test session created:\n";
        echo "  - Admin ID: {$testAdmin['id']}\n";
        echo "  - Username: {$testAdmin['username']}\n";
        echo "  - Role: {$testAdmin['role']}\n";
        echo "  - Session ID: " . session_id() . "\n";
        
        echo "\n3. Testing debug config API with session...\n";
        
        // Simulate the debug config API call
        $debugQuery = "
            SELECT 
                dc.*,
                au.username as created_by_username,
                au2.username as updated_by_username
            FROM debug_config dc
            LEFT JOIN admin_users au ON dc.created_by = au.id
            LEFT JOIN admin_users au2 ON dc.updated_by = au2.id
            ORDER BY dc.feature_name ASC
        ";
        
        $debugStmt = $db->prepare($debugQuery);
        $debugStmt->execute();
        $configs = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON fields
        foreach ($configs as &$config) {
            $config['config_data'] = $config['config_data'] ? json_decode($config['config_data'], true) : null;
            $config['allowed_environments'] = $config['allowed_environments'] ? json_decode($config['allowed_environments'], true) : [];
        }
        
        echo "✅ Debug API would return " . count($configs) . " configurations\n";
        
        $enabledCount = 0;
        foreach ($configs as $config) {
            if ($config['is_enabled']) {
                $enabledCount++;
                echo "  - {$config['feature_name']}: ENABLED\n";
            }
        }
        
        echo "\nSummary: $enabledCount of " . count($configs) . " features are enabled\n";
        
        // Test the actual API response format
        $response = [
            'success' => true,
            'message' => 'Debug configurations retrieved successfully',
            'data' => $configs
        ];
        
        echo "\n4. API Response Preview:\n";
        echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
        echo "Data count: " . count($response['data']) . "\n";
        echo "First config: {$response['data'][0]['feature_name']} (enabled: " . ($response['data'][0]['is_enabled'] ? 'true' : 'false') . ")\n";
        
    } else {
        echo "❌ No admin users found\n";
        
        echo "\n2. Creating default admin user...\n";
        
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $createAdminQuery = "
            INSERT INTO admin_users (username, password, full_name, role, status) 
            VALUES ('admin', ?, 'System Administrator', 'admin', 'active')
        ";
        
        $createStmt = $db->prepare($createAdminQuery);
        $createStmt->execute([$defaultPassword]);
        
        echo "✅ Default admin user created:\n";
        echo "  - Username: admin\n";
        echo "  - Password: admin123\n";
        echo "  - Role: admin\n";
    }
    
    echo "\n======================\n";
    echo "🎯 LOGIN TEST COMPLETE\n";
    echo "======================\n";
    
    echo "\n📋 INSTRUCTIONS:\n";
    echo "1. Login to admin panel with username 'admin' and password 'admin123'\n";
    echo "2. Navigate to Debug Manager\n";
    echo "3. Debug configurations should now be visible\n";
    echo "4. Session ID for reference: " . session_id() . "\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
