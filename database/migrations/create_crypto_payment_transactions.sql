-- =====================================================
-- CRYPTO PAYMENT TRANSACTIONS TABLE
-- =====================================================

-- Create crypto payment transactions table for Telegram bot payments
CREATE TABLE IF NOT EXISTS crypto_payment_transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Investment relationship
    investment_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(255) NOT NULL COMMENT 'Telegram user ID',
    
    -- Payment details
    network VARCHAR(50) NOT NULL COMMENT 'Blockchain network (bsc, ethereum, polygon, tron)',
    transaction_hash VARCHAR(255) NOT NULL COMMENT 'Blockchain transaction hash',
    
    -- Amount details
    amount_usd DECIMAL(15,6) NOT NULL,
    
    -- Wallet addresses
    wallet_address VARCHAR(255) NOT NULL COMMENT 'Company wallet address that received payment',
    sender_wallet_address VARCHAR(255) NULL COMMENT 'User wallet address that sent payment',
    
    -- Status tracking
    payment_status ENUM('pending', 'confirmed', 'failed', 'expired') DEFAULT 'pending',
    verification_status ENUM('pending', 'approved', 'rejected', 'reviewing') DEFAULT 'pending',
    
    -- Admin verification
    verified_by VARCHAR(36) NULL,
    verified_at TIMESTAMP NULL,
    verification_notes TEXT NULL,
    
    -- Processing details
    blockchain_confirmations INT DEFAULT 0,
    gas_fee DECIMAL(15,6) NULL,
    exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
    
    -- File attachments
    payment_screenshot_path VARCHAR(500) NULL COMMENT 'Screenshot of payment from user wallet',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'Payment verification deadline',
    
    -- Indexes
    INDEX idx_investment_id (investment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_network (network),
    INDEX idx_transaction_hash (transaction_hash),
    INDEX idx_payment_status (payment_status),
    INDEX idx_verification_status (verification_status),
    INDEX idx_verified_by (verified_by),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),
    
    -- Unique constraints
    UNIQUE KEY idx_tx_hash_network (transaction_hash, network),
    
    -- Foreign keys
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Add some sample data for testing (optional)
-- INSERT INTO crypto_payment_transactions (
--     id, investment_id, user_id, network, transaction_hash,
--     amount_usd, wallet_address, sender_wallet_address,
--     payment_status, verification_status
-- ) VALUES (
--     UUID(), 'test-investment-id', '123456789', 'ethereum',
--     '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
--     1000.00, '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7',
--     '0x9876543210fedcba9876543210fedcba9876543210fedcba9876543210fedcba',
--     'pending', 'pending'
-- );
