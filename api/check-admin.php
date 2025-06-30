<?php
header('Content-Type: text/html');

echo "<h1>Admin Database Check</h1>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'aureus_angels';
    $username = 'root';
    $password = '';
    $port = 3506; // Custom XAMPP port

    echo "<p><strong>Connecting to database:</strong> $dbname on port $port</p>";

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo "<p style='color: red;'>❌ admin_users table does not exist</p>";
        echo "<p>You need to run the database setup first.</p>";
        exit;
    }

    echo "<p style='color: green;'>✅ admin_users table exists</p>";

    // Check admin users
    $stmt = $pdo->query("SELECT id, username, role, is_active, created_at FROM admin_users");
    $users = $stmt->fetchAll();

    echo "<h2>Admin Users Found (" . count($users) . "):</h2>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th><th>Created</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test password verification for admin user
        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            $testPassword = 'Underdog8406155100085@123!@#';
            $passwordValid = password_verify($testPassword, $admin['password_hash']);
            
            echo "<h2>Password Test:</h2>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Test Password:</strong> " . htmlspecialchars($testPassword) . "</p>";
            echo "<p><strong>Password Valid:</strong> " . ($passwordValid ? '<span style="color: green;">✅ YES</span>' : '<span style="color: red;">❌ NO</span>') . "</p>";
            
            if (!$passwordValid) {
                echo "<p style='color: orange;'>⚠️ The password might need to be reset. The admin user exists but the password doesn't match.</p>";
                
                // Show the hash for debugging
                echo "<p><strong>Stored Hash:</strong> " . htmlspecialchars(substr($admin['password_hash'], 0, 50)) . "...</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Admin user 'admin' not found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ No admin users found in database</p>";
        echo "<p>You need to create an admin user first.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    
    if ($e->getCode() == 1049) {
        echo "<p>The database 'aureus_angels' doesn't exist. Please create it first.</p>";
    } elseif ($e->getCode() == 2002) {
        echo "<p>Cannot connect to MySQL server. Make sure XAMPP MySQL is running on port 3506.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ General error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
