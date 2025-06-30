<?php
// Simple direct connection test
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=aureus_angels", "root", "");
    echo "✅ Direct connection works!";
    
    // Test admin user
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<br>✅ Admin user found!";
        echo "<br>Password hash: " . substr($user['password_hash'], 0, 20) . "...";
    } else {
        echo "<br>❌ No admin user found";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
