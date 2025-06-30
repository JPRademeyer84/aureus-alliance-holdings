<?php
// Check user state in database
$host = 'localhost';
$port = 3506;
$dbname = 'aureus_angels';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check telegram user state for user ID 7648596384
    $stmt = $pdo->prepare("SELECT * FROM telegram_users WHERE telegram_id = ?");
    $stmt->execute([7648596384]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User State:\n";
    echo "===========\n";
    if ($user) {
        foreach ($user as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "User not found\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
