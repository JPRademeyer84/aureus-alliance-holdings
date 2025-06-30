<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [];
    
    // Check if user_credits table exists
    $checkTableQuery = "SHOW TABLES LIKE 'user_credits'";
    $stmt = $db->prepare($checkTableQuery);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create user_credits table
        $createTableQuery = "
            CREATE TABLE user_credits (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                
                -- Credit balance
                total_credits DECIMAL(15,6) DEFAULT 0.00 COMMENT 'Total dollar credits available',
                used_credits DECIMAL(15,6) DEFAULT 0.00 COMMENT 'Total credits used for purchases',
                available_credits DECIMAL(15,6) GENERATED ALWAYS AS (total_credits - used_credits) STORED,
                
                -- Timestamps
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indexes
                INDEX idx_user_id (user_id),
                INDEX idx_available_credits (available_credits),
                INDEX idx_created_at (created_at),
                
                -- Foreign key constraint
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        $db->exec($createTableQuery);
        $results['user_credits_table'] = 'Created successfully';
    } else {
        // Check if available_credits column exists
        $checkColumnQuery = "SHOW COLUMNS FROM user_credits LIKE 'available_credits'";
        $stmt = $db->prepare($checkColumnQuery);
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if (!$columnExists) {
            // Add available_credits column as generated column
            $addColumnQuery = "
                ALTER TABLE user_credits 
                ADD COLUMN available_credits DECIMAL(15,6) GENERATED ALWAYS AS (total_credits - used_credits) STORED
            ";
            $db->exec($addColumnQuery);
            $results['available_credits_column'] = 'Added successfully';
        } else {
            $results['available_credits_column'] = 'Already exists';
        }
        
        $results['user_credits_table'] = 'Already exists';
    }
    
    // Check if credit_transactions table exists
    $checkTransactionsQuery = "SHOW TABLES LIKE 'credit_transactions'";
    $stmt = $db->prepare($checkTransactionsQuery);
    $stmt->execute();
    $transactionsExists = $stmt->fetch();
    
    if (!$transactionsExists) {
        // Create credit_transactions table
        $createTransactionsQuery = "
            CREATE TABLE credit_transactions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                
                -- Transaction details
                transaction_type ENUM('earned', 'used', 'refunded') NOT NULL,
                amount DECIMAL(15,6) NOT NULL,
                description TEXT,
                
                -- Source tracking
                source_type ENUM('coupon', 'purchase', 'refund', 'admin_adjustment') NOT NULL,
                source_id VARCHAR(36),
                coupon_id VARCHAR(36),
                
                -- Timestamps
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                -- Indexes
                INDEX idx_user_id (user_id),
                INDEX idx_transaction_type (transaction_type),
                INDEX idx_source_type (source_type),
                INDEX idx_coupon_id (coupon_id),
                INDEX idx_created_at (created_at),
                
                -- Foreign key constraints
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (coupon_id) REFERENCES nft_coupons(id) ON DELETE SET NULL
            )
        ";
        
        $db->exec($createTransactionsQuery);
        $results['credit_transactions_table'] = 'Created successfully';
    } else {
        $results['credit_transactions_table'] = 'Already exists';
    }
    
    // Check if nft_coupons table exists
    $checkCouponsQuery = "SHOW TABLES LIKE 'nft_coupons'";
    $stmt = $db->prepare($checkCouponsQuery);
    $stmt->execute();
    $couponsExists = $stmt->fetch();
    
    if (!$couponsExists) {
        // Create nft_coupons table
        $createCouponsQuery = "
            CREATE TABLE nft_coupons (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                
                -- Coupon details
                coupon_code VARCHAR(20) UNIQUE NOT NULL,
                value DECIMAL(15,6) NOT NULL COMMENT 'Dollar value of the coupon',
                description TEXT,
                
                -- Usage tracking
                is_active BOOLEAN DEFAULT TRUE,
                is_used BOOLEAN DEFAULT FALSE,
                max_uses INT DEFAULT 1 COMMENT 'Maximum number of times this coupon can be used',
                current_uses INT DEFAULT 0 COMMENT 'Current number of times used',
                
                -- Assignment and usage
                assigned_to VARCHAR(36) COMMENT 'User ID if coupon is assigned to specific user',
                used_by VARCHAR(36) COMMENT 'User ID who used the coupon',
                used_on TIMESTAMP NULL COMMENT 'When the coupon was used',
                
                -- Expiration
                expires_at TIMESTAMP NULL COMMENT 'When the coupon expires',
                
                -- Admin tracking
                created_by VARCHAR(36) NOT NULL COMMENT 'Admin ID who created the coupon',
                notes TEXT COMMENT 'Admin notes about the coupon',
                
                -- Timestamps
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indexes
                INDEX idx_coupon_code (coupon_code),
                INDEX idx_is_active (is_active),
                INDEX idx_is_used (is_used),
                INDEX idx_assigned_to (assigned_to),
                INDEX idx_used_by (used_by),
                INDEX idx_expires_at (expires_at),
                INDEX idx_created_by (created_by),
                INDEX idx_created_at (created_at),
                
                -- Foreign key constraints
                FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
            )
        ";
        
        $db->exec($createCouponsQuery);
        $results['nft_coupons_table'] = 'Created successfully';
    } else {
        $results['nft_coupons_table'] = 'Already exists';
    }
    
    // Get the current table structures
    $tables = ['user_credits', 'credit_transactions', 'nft_coupons'];
    foreach ($tables as $table) {
        $describeQuery = "DESCRIBE $table";
        $stmt = $db->prepare($describeQuery);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results[$table . '_columns'] = array_column($columns, 'Field');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Credits tables structure fixed',
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Credits tables fix error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fix tables: ' . $e->getMessage()
    ]);
}
?>
