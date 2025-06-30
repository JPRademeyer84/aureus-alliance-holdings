<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    echo "Step 1: Starting test...\n";
    
    $database = new Database();
    echo "Step 2: Database object created\n";
    
    $db = $database->getConnection();
    echo "Step 3: Database connection established\n";
    
    // Test if admin exists
    $adminId = $_GET['admin_id'] ?? '';
    echo "Step 4: Admin ID: $adminId\n";
    
    if (!$adminId) {
        throw new Exception('No admin ID provided');
    }
    
    $adminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
    $adminStmt = $db->prepare($adminQuery);
    echo "Step 5: Admin query prepared\n";
    
    $adminStmt->execute([$adminId]);
    echo "Step 6: Admin query executed\n";
    
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    echo "Step 7: Admin fetched: " . json_encode($admin) . "\n";
    
    if (!$admin) {
        throw new Exception('Admin not found');
    }
    
    // Test if users table exists
    $tableQuery = "SHOW TABLES LIKE 'users'";
    $tableStmt = $db->prepare($tableQuery);
    $tableStmt->execute();
    $tableExists = $tableStmt->fetch() !== false;
    echo "Step 8: Users table exists: " . ($tableExists ? 'YES' : 'NO') . "\n";
    
    if (!$tableExists) {
        throw new Exception('Users table does not exist');
    }
    
    // Test simple query
    $countQuery = "SELECT COUNT(*) as count FROM users";
    $countStmt = $db->prepare($countQuery);
    echo "Step 9: Count query prepared\n";
    
    $countStmt->execute();
    echo "Step 10: Count query executed\n";
    
    $count = $countStmt->fetch(PDO::FETCH_ASSOC);
    echo "Step 11: Count result: " . json_encode($count) . "\n";
    
    // Test full query
    $query = "SELECT id, username, email, is_active, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    echo "Step 12: Full query prepared\n";
    
    $stmt->execute();
    echo "Step 13: Full query executed\n";
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Step 14: Users fetched: " . count($users) . " records\n";
    
    // Test stats query
    $statsQuery = "SELECT 
                  COUNT(*) as total_users,
                  SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
                  SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_users
                  FROM users";
    $statsStmt = $db->prepare($statsQuery);
    echo "Step 15: Stats query prepared\n";
    
    $statsStmt->execute();
    echo "Step 16: Stats query executed\n";
    
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    echo "Step 17: Stats fetched: " . json_encode($stats) . "\n";
    
    echo "SUCCESS: All tests passed!\n";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'All tests passed',
        'data' => [
            'admin' => $admin,
            'table_exists' => $tableExists,
            'user_count' => $count,
            'users_sample' => $users,
            'statistics' => $stats
        ]
    ]);
    
} catch (Exception $e) {
    echo "ERROR at step: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
