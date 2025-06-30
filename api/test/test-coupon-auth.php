<?php
/**
 * Test script for coupon API admin authentication
 */

header('Content-Type: text/plain');

echo "🔍 TESTING COUPON API ADMIN AUTHENTICATION\n";
echo "==========================================\n\n";

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test 1: Check if admin user exists
    echo "1. Checking admin user...\n";
    $query = "SELECT id, username, role FROM admin_users WHERE username = 'admin' AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user found: {$admin['username']} (Role: {$admin['role']})\n";
        
        // Test 2: Create admin session
        echo "\n2. Creating admin session...\n";
        session_start();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        
        echo "✅ Session created:\n";
        echo "  - Session ID: " . session_id() . "\n";
        echo "  - Admin ID: {$admin['id']}\n";
        echo "  - Username: {$admin['username']}\n";
        echo "  - Role: {$admin['role']}\n";
        
        // Test 3: Test admin authentication function
        echo "\n3. Testing validateAdminAuth function...\n";
        
        // Include the function from coupons API
        function validateAdminAuth($db) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['admin_id'])) {
                throw new Exception('Admin authentication required');
            }

            $query = "SELECT id, username, role, full_name FROM admin_users WHERE id = ? AND is_active = TRUE";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                throw new Exception('Invalid admin session');
            }

            if (!in_array($admin['role'], ['super_admin', 'admin'])) {
                throw new Exception('Insufficient permissions for coupon management');
            }

            return $admin;
        }
        
        try {
            $validatedAdmin = validateAdminAuth($db);
            echo "✅ Admin authentication successful:\n";
            echo "  - ID: {$validatedAdmin['id']}\n";
            echo "  - Username: {$validatedAdmin['username']}\n";
            echo "  - Role: {$validatedAdmin['role']}\n";
            echo "  - Full Name: " . ($validatedAdmin['full_name'] ?: 'Not set') . "\n";
        } catch (Exception $e) {
            echo "❌ Admin authentication failed: " . $e->getMessage() . "\n";
        }
        
        // Test 4: Test without session
        echo "\n4. Testing without admin session...\n";
        session_destroy();
        session_start(); // Start fresh session without admin data
        
        try {
            $validatedAdmin = validateAdminAuth($db);
            echo "❌ Authentication should have failed but didn't\n";
        } catch (Exception $e) {
            echo "✅ Correctly rejected unauthenticated request: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ No admin user found. Please ensure admin user exists.\n";
    }
    
    echo "\n✅ COUPON API AUTHENTICATION TEST COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>
