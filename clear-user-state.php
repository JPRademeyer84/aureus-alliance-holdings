<?php
// Database configuration
$host = 'localhost';
$port = '3506';
$dbname = 'aureus_angels';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear all user states
    $stmt = $pdo->prepare("UPDATE telegram_users SET 
        awaiting_tx_hash = 0,
        awaiting_screenshot = 0,
        awaiting_sender_wallet = 0,
        payment_network = NULL,
        payment_package_id = NULL,
        payment_step = NULL,
        sender_wallet_address = NULL,
        screenshot_path = NULL,
        payment_is_custom = 0,
        terms_custom_investment = NULL
        WHERE telegram_id = 7648596384");
    
    $stmt->execute();
    
    echo "✅ User state cleared successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
