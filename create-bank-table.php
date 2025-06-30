<?php
$host = 'localhost';
$port = 3506;
$user = 'root';
$password = '';
$database = 'aureus_angels';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CREATING BANK PAYMENT TRANSACTIONS TABLE ===\n";
    
    // Create the bank_payment_transactions table
    $sql = "
    CREATE TABLE IF NOT EXISTS bank_payment_transactions (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        
        -- Investment relationship
        investment_id VARCHAR(36) NOT NULL,
        user_id VARCHAR(255) NOT NULL,
        
        -- Payment details
        reference_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique reference for bank transfer',
        
        -- Transaction amounts
        amount_usd DECIMAL(15,6) NOT NULL,
        amount_local DECIMAL(15,6) NOT NULL,
        local_currency VARCHAR(3) NOT NULL,
        exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
        
        -- Bank transfer details
        sender_name VARCHAR(100) NULL,
        sender_account VARCHAR(50) NULL,
        sender_bank VARCHAR(100) NULL,
        transfer_date DATE NULL,
        bank_reference VARCHAR(100) NULL COMMENT 'Bank provided reference number',
        
        -- Verification and status
        payment_status ENUM('pending', 'submitted', 'verified', 'confirmed', 'failed', 'refunded') DEFAULT 'pending',
        verification_status ENUM('pending', 'reviewing', 'approved', 'rejected') DEFAULT 'pending',
        
        -- Admin verification
        verified_by VARCHAR(36) NULL,
        verified_at TIMESTAMP NULL,
        verification_notes TEXT NULL,
        
        -- Processing details
        submitted_at TIMESTAMP NULL COMMENT 'When user submitted payment proof',
        confirmed_at TIMESTAMP NULL COMMENT 'When payment was confirmed',
        expires_at TIMESTAMP NULL COMMENT 'Payment deadline',
        
        -- File attachments
        payment_proof_path VARCHAR(500) NULL COMMENT 'Upload proof of payment',
        bank_statement_path VARCHAR(500) NULL,
        
        -- Timestamps
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Indexes
        INDEX idx_investment_id (investment_id),
        INDEX idx_user_id (user_id),
        INDEX idx_reference_number (reference_number),
        INDEX idx_payment_status (payment_status),
        INDEX idx_verification_status (verification_status),
        INDEX idx_verified_by (verified_by),
        INDEX idx_transfer_date (transfer_date),
        INDEX idx_expires_at (expires_at)
    )";
    
    $pdo->exec($sql);
    echo "✅ Created bank_payment_transactions table\n";
    
    // Verify the table was created
    $stmt = $pdo->query("DESCRIBE bank_payment_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== TABLE STRUCTURE ===\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n✅ Bank payment transactions table created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
