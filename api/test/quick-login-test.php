<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Quick Login Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if we have any users
    $query = "SELECT id, username, email FROM users LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Available Users:</h3>";
    if ($users) {
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}</li>";
        }
        echo "</ul>";
        
        // Auto-login as first user for testing
        if (isset($_GET['auto_login']) && $_GET['auto_login'] === '1') {
            $firstUser = $users[0];
            $_SESSION['user_id'] = $firstUser['id'];
            $_SESSION['username'] = $firstUser['username'];
            $_SESSION['email'] = $firstUser['email'];
            
            echo "<p>✅ Auto-logged in as: {$firstUser['username']}</p>";
            echo "<p><a href='test-enhanced-profile.php'>Test Enhanced Profile API</a></p>";
            echo "<p><a href='../../'>Go to Frontend</a></p>";
        } else {
            echo "<p><a href='?auto_login=1'>Auto-login as first user</a></p>";
        }
    } else {
        echo "<p>No users found in database</p>";
    }
    
    // Show current session
    echo "<h3>Current Session:</h3>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ Logged in as User ID: {$_SESSION['user_id']}</p>";
        echo "<p>Username: " . ($_SESSION['username'] ?? 'N/A') . "</p>";
        echo "<p>Email: " . ($_SESSION['email'] ?? 'N/A') . "</p>";
    } else {
        echo "<p>❌ No active session</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
