-- Create NFT Coupons System Tables
USE aureus_angels;

-- Create NFTcoupons table
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
    INDEX idx_expires_at (expires_at),
    
    -- Foreign keys
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
);

-- Create user_credits table to track dollar credits from coupons
CREATE TABLE IF NOT EXISTS user_credits (
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
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Unique constraint
    UNIQUE KEY unique_user_credits (user_id)
);

-- Create credit_transactions table to track credit usage
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
    INDEX idx_created_at (created_at),
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL,
    FOREIGN KEY (coupon_id) REFERENCES nft_coupons(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Insert some sample coupons for testing (admin only can create these)
-- Note: These will only be inserted if no coupons exist (first-time setup)
INSERT IGNORE INTO nft_coupons (
    coupon_code, value, description, created_by, notes, expires_at
) 
SELECT 
    'WELCOME10', 10.00, 'Welcome bonus - $10 credit', 
    (SELECT id FROM admin_users WHERE username = 'admin' LIMIT 1),
    'Default welcome coupon for new users',
    DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE NOT EXISTS (SELECT 1 FROM nft_coupons WHERE coupon_code = 'WELCOME10');

INSERT IGNORE INTO nft_coupons (
    coupon_code, value, description, created_by, notes, expires_at
) 
SELECT 
    'TEST25', 25.00, 'Testing coupon - $25 credit', 
    (SELECT id FROM admin_users WHERE username = 'admin' LIMIT 1),
    'Testing coupon for commission system validation',
    DATE_ADD(NOW(), INTERVAL 7 DAY)
WHERE NOT EXISTS (SELECT 1 FROM nft_coupons WHERE coupon_code = 'TEST25');

INSERT IGNORE INTO nft_coupons (
    coupon_code, value, description, created_by, notes, expires_at
) 
SELECT 
    'PROMO50', 50.00, 'Promotional coupon - $50 credit', 
    (SELECT id FROM admin_users WHERE username = 'admin' LIMIT 1),
    'Promotional giveaway coupon',
    DATE_ADD(NOW(), INTERVAL 14 DAY)
WHERE NOT EXISTS (SELECT 1 FROM nft_coupons WHERE coupon_code = 'PROMO50');

-- Add payment_method column to aureus_investments table if it doesn't exist
ALTER TABLE aureus_investments
ADD COLUMN IF NOT EXISTS payment_method ENUM('wallet', 'credits') DEFAULT 'wallet'
AFTER tx_hash;
