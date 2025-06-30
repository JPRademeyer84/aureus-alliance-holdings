<?php
$host = 'localhost';
$port = 3506;
$user = 'root';
$password = '';
$database = 'aureus_angels';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FIXING CRYPTO PAYMENT TRANSACTIONS ENUM VALUES ===\n";
    
    // Check current ENUM values
    $stmt = $pdo->query("SHOW COLUMNS FROM crypto_payment_transactions LIKE 'verification_status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current verification_status ENUM: {$column['Type']}\n";
    
    // Add missing ENUM values
    $sql = "ALTER TABLE crypto_payment_transactions 
            MODIFY COLUMN verification_status ENUM('pending', 'approved', 'rejected', 'reviewing', 'manual_review_required', 'verification_failed') DEFAULT 'pending'";
    
    $pdo->exec($sql);
    echo "✅ Updated verification_status ENUM values\n";
    
    // Verify the change
    $stmt = $pdo->query("SHOW COLUMNS FROM crypto_payment_transactions LIKE 'verification_status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Updated verification_status ENUM: {$column['Type']}\n";
    
    echo "\n✅ ENUM values fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
