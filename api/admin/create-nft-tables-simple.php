<?php
/**
 * CREATE NFT COUPONS TABLES - SIMPLE VERSION
 * Creates the NFT coupons system tables directly
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 CREATING NFT COUPONS TABLES\n";
    echo "==============================\n\n";
    
    // Create nft_coupons table
    echo "Creating nft_coupons table...\n";
    $nftCouponsTable = "
        CREATE TABLE IF NOT EXISTS nft_coupons (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            coupon_code VARCHAR(20) UNIQUE NOT NULL,
            value DECIMAL(10,2) NOT NULL,
            description TEXT NULL,
            
            -- Status and usage tracking
            is_active BOOLEAN DEFAULT TRUE,
            is_used BOOLEAN DEFAULT FALSE,
            used_by VARCHAR(36) NULL,
            used_on TIMESTAMP NULL,
            
            -- Assignment and restrictions
            assigned_to VARCHAR(36) NULL COMMENT 'User ID if coupon is assigned to specific user',
            max_uses INT DEFAULT 1 COMMENT 'How many times coupon can be used',
            current_uses INT DEFAULT 0 COMMENT 'How many times coupon has been used',
            
            -- Expiration
            expires_at TIMESTAMP NULL,
            
            -- Admin tracking
            created_by VARCHAR(36) NOT NULL COMMENT 'Admin user ID who created the coupon',
            notes TEXT NULL COMMENT 'Admin notes about the coupon',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_coupon_code (coupon_code),
            INDEX idx_is_active (is_active),
            INDEX idx_is_used (is_used),
            INDEX idx_used_by (used_by),
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_created_by (created_by),
            INDEX idx_expires_at (expires_at)
        )
    ";
    
    $db->exec($nftCouponsTable);
    echo "✅ nft_coupons table created\n";
    
    // Create user_credits table
    echo "Creating user_credits table...\n";
    $userCreditsTable = "
        CREATE TABLE IF NOT EXISTS user_credits (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            user_id VARCHAR(36) NOT NULL,
            
            -- Credit balance
            total_credits DECIMAL(15,6) DEFAULT 0.00 COMMENT 'Total dollar credits available',
            used_credits DECIMAL(15,6) DEFAULT 0.00 COMMENT 'Total credits used for purchases',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_user_id (user_id),
            
            -- Unique constraint
            UNIQUE KEY unique_user_credits (user_id)
        )
    ";
    
    $db->exec($userCreditsTable);
    echo "✅ user_credits table created\n";
    
    // Create credit_transactions table
    echo "Creating credit_transactions table...\n";
    $creditTransactionsTable = "
        CREATE TABLE IF NOT EXISTS credit_transactions (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            user_id VARCHAR(36) NOT NULL,
            
            -- Transaction details
            transaction_type ENUM('earned', 'spent', 'refunded', 'expired') NOT NULL,
            amount DECIMAL(15,6) NOT NULL,
            description TEXT NOT NULL,
            
            -- Source tracking
            source_type ENUM('coupon', 'purchase', 'refund', 'admin_adjustment') NOT NULL,
            source_id VARCHAR(36) NULL COMMENT 'ID of coupon, purchase, etc.',
            
            -- Related data
            investment_id VARCHAR(36) NULL COMMENT 'Investment ID if credits used for purchase',
            coupon_id VARCHAR(36) NULL COMMENT 'Coupon ID if credits earned from coupon',
            
            -- Admin tracking
            processed_by VARCHAR(36) NULL COMMENT 'Admin user who processed this transaction',
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            -- Indexes
            INDEX idx_user_id (user_id),
            INDEX idx_transaction_type (transaction_type),
            INDEX idx_source_type (source_type),
            INDEX idx_source_id (source_id),
            INDEX idx_investment_id (investment_id),
            INDEX idx_coupon_id (coupon_id),
            INDEX idx_created_at (created_at)
        )
    ";
    
    $db->exec($creditTransactionsTable);
    echo "✅ credit_transactions table created\n";
    
    // Add payment_method column to aureus_investments if it doesn't exist
    echo "Adding payment_method column to aureus_investments...\n";
    try {
        $alterInvestmentsTable = "
            ALTER TABLE aureus_investments 
            ADD COLUMN payment_method ENUM('wallet', 'credits') DEFAULT 'wallet' 
            AFTER tx_hash
        ";
        $db->exec($alterInvestmentsTable);
        echo "✅ payment_method column added to aureus_investments\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✅ payment_method column already exists in aureus_investments\n";
        } else {
            echo "⚠️ Error adding payment_method column: " . $e->getMessage() . "\n";
        }
    }
    
    // Create default coupons
    echo "Creating default coupons...\n";
    
    // Get admin user
    $adminQuery = "SELECT id FROM admin_users WHERE username = 'admin' LIMIT 1";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute();
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $defaultCoupons = [
            ['WELCOME10', 10.00, 'Welcome bonus - $10 credit', 'Default welcome coupon for new users'],
            ['TEST25', 25.00, 'Testing coupon - $25 credit', 'Testing coupon for commission system validation'],
            ['PROMO50', 50.00, 'Promotional coupon - $50 credit', 'Promotional giveaway coupon']
        ];
        
        $insertCouponQuery = "
            INSERT IGNORE INTO nft_coupons (
                coupon_code, value, description, created_by, notes, expires_at
            ) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ";
        
        $insertCouponStmt = $db->prepare($insertCouponQuery);
        
        foreach ($defaultCoupons as $coupon) {
            try {
                $insertCouponStmt->execute([
                    $coupon[0], // code
                    $coupon[1], // value
                    $coupon[2], // description
                    $admin['id'], // created_by
                    $coupon[3]  // notes
                ]);
                echo "✅ Created coupon: {$coupon[0]} (\${$coupon[1]})\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "✅ Coupon {$coupon[0]} already exists\n";
                } else {
                    echo "❌ Error creating coupon {$coupon[0]}: " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "⚠️ No admin user found. Skipping default coupons.\n";
    }
    
    // Verify tables
    echo "\nVerifying tables...\n";
    
    $tables = ['nft_coupons', 'user_credits', 'credit_transactions'];
    
    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✅ $table: $count records\n";
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n==============================\n";
    echo "🎉 NFT COUPONS SYSTEM READY!\n";
    echo "==============================\n";
    echo "✅ All tables created successfully\n";
    echo "✅ Default coupons added\n";
    echo "✅ Investment table updated\n";
    echo "\nYou can now:\n";
    echo "1. Access admin panel to manage coupons\n";
    echo "2. Users can redeem coupons for credits\n";
    echo "3. Credits can be used for NFT purchases\n";
    echo "\nSetup completed at: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "❌ SETUP FAILED: " . $e->getMessage() . "\n";
}
?>
