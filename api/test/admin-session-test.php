<?php
/**
 * Test Admin Session
 * Check if admin authentication is working
 */

header('Content-Type: text/plain');

session_start();

echo "🔍 TESTING ADMIN SESSION\n";
echo "========================\n\n";

echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";

echo "\nSession Data:\n";
if (empty($_SESSION)) {
    echo "❌ Session is empty\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "  - $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}

echo "\nAdmin Authentication Check:\n";
if (isset($_SESSION['admin_id'])) {
    echo "✅ Admin ID: {$_SESSION['admin_id']}\n";
    
    // Try to get admin details
    require_once '../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, full_name, role FROM admin_users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "✅ Admin Details:\n";
            echo "  - Username: {$admin['username']}\n";
            echo "  - Full Name: {$admin['full_name']}\n";
            echo "  - Role: {$admin['role']}\n";
        } else {
            echo "❌ Admin not found in database\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ No admin_id in session\n";
}

echo "\nCookies:\n";
if (empty($_COOKIE)) {
    echo "❌ No cookies found\n";
} else {
    foreach ($_COOKIE as $name => $value) {
        echo "  - $name: " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
    }
}

echo "\nHeaders:\n";
$headers = getallheaders();
if ($headers) {
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'cookie') {
            echo "  - $name: " . substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '') . "\n";
        }
    }
}

echo "\n========================\n";
echo "🎯 SESSION TEST COMPLETE\n";
echo "========================\n";
?>
