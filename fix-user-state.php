<?php
// Fix user state in database
$host = 'localhost';
$port = 3506;
$dbname = 'aureus_angels';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fix the user state for user ID 7648596384
    $stmt = $pdo->prepare("UPDATE telegram_users SET 
        payment_network = 'polygon',
        payment_package_id = 'b59ec520-d60f-4f6d-8c4d-16578928e43c',
        payment_is_custom = 1
        WHERE telegram_id = ?");
    $stmt->execute([7648596384]);
    
    echo "User state fixed successfully!\n";
    
    // Verify the fix
    $stmt = $pdo->prepare("SELECT payment_network, payment_package_id, payment_is_custom FROM telegram_users WHERE telegram_id = ?");
    $stmt->execute([7648596384]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Updated values:\n";
    echo "payment_network: " . $user['payment_network'] . "\n";
    echo "payment_package_id: " . $user['payment_package_id'] . "\n";
    echo "payment_is_custom: " . $user['payment_is_custom'] . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
