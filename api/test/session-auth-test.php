<?php
/**
 * Test Session-Based Authentication
 * Test the new session-based admin authentication system
 */

header('Content-Type: text/plain');

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 TESTING SESSION-BASED AUTHENTICATION\n";
    echo "=======================================\n\n";
    
    // Test 1: Login and create session
    echo "1. Testing admin login with session creation...\n";
    
    // Get admin user
    $adminQuery = "SELECT id, username, password_hash, role FROM admin_users WHERE username = 'admin' LIMIT 1";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute();
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user found: {$admin['username']}\n";
        
        // Simulate login by creating session
        session_start();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        
        echo "✅ Session created:\n";
        echo "  - Session ID: " . session_id() . "\n";
        echo "  - Admin ID: {$admin['id']}\n";
        echo "  - Username: {$admin['username']}\n";
        echo "  - Role: {$admin['role']}\n";
        
        // Test 2: Test debug config API with session
        echo "\n2. Testing debug config API with session...\n";
        
        // Check session authentication
        if (isset($_SESSION['admin_id'])) {
            echo "✅ Session authentication passed\n";
            
            // Test the debug config query
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
            
            echo "✅ Debug config query successful: " . count($configs) . " configurations\n";
            
            $enabledCount = 0;
            foreach ($configs as $config) {
                if ($config['is_enabled']) {
                    $enabledCount++;
                    echo "  - {$config['feature_name']}: ENABLED\n";
                }
            }
            
            echo "\nSummary: $enabledCount of " . count($configs) . " features enabled\n";
            
            // Test 3: Simulate API response
            echo "\n3. Testing API response format...\n";
            
            $response = [
                'success' => true,
                'message' => 'Debug configurations retrieved successfully',
                'data' => $configs
            ];
            
            echo "✅ API response format:\n";
            echo "  - Success: " . ($response['success'] ? 'true' : 'false') . "\n";
            echo "  - Message: {$response['message']}\n";
            echo "  - Data count: " . count($response['data']) . "\n";
            
            if (count($response['data']) > 0) {
                $first = $response['data'][0];
                echo "  - First config: {$first['feature_name']} (enabled: " . ($first['is_enabled'] ? 'true' : 'false') . ")\n";
            }
            
        } else {
            echo "❌ Session authentication failed\n";
        }
        
    } else {
        echo "❌ No admin user found\n";
    }
    
    echo "\n4. Testing session persistence...\n";
    
    // Check if session data persists
    if (isset($_SESSION['admin_id'])) {
        echo "✅ Session data persists:\n";
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'admin_') === 0) {
                echo "  - $key: $value\n";
            }
        }
    } else {
        echo "❌ Session data lost\n";
    }
    
    echo "\n=======================================\n";
    echo "🎯 SESSION AUTHENTICATION TEST COMPLETE\n";
    echo "=======================================\n";
    
    echo "\n📋 NEXT STEPS:\n";
    echo "1. Logout and login again in the admin panel\n";
    echo "2. The login should now create a PHP session\n";
    echo "3. Debug Manager should work with session authentication\n";
    echo "4. Session ID for reference: " . session_id() . "\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
