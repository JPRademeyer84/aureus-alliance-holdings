-- Create commission plan tables for referral system
USE aureus_angels;

-- Create commission_plans table for managing compensation plans
CREATE TABLE IF NOT EXISTS commission_plans (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    plan_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    
    -- Commission structure
    level_1_usdt_percent DECIMAL(5,2) NOT NULL DEFAULT 12.00,
    level_1_nft_percent DECIMAL(5,2) NOT NULL DEFAULT 12.00,
    level_2_usdt_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    level_2_nft_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    level_3_usdt_percent DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    level_3_nft_percent DECIMAL(5,2) NOT NULL DEFAULT 3.00,
    
    -- NFT pack pricing
    nft_pack_price DECIMAL(10,2) NOT NULL DEFAULT 5.00,
    nft_total_supply INT NOT NULL DEFAULT 200000,
    nft_remaining_supply INT NOT NULL DEFAULT 200000,
    
    -- Plan settings
    max_levels INT NOT NULL DEFAULT 3,
    minimum_investment DECIMAL(10,2) DEFAULT 0.00,
    commission_cap DECIMAL(10,2) DEFAULT NULL, -- NULL = no cap
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36),
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default),
    INDEX idx_created_at (created_at)
);

-- Create commission_transactions table for tracking all commission payments
CREATE TABLE IF NOT EXISTS commission_transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    commission_plan_id VARCHAR(36) NOT NULL,
    
    -- Referral relationship
    referrer_user_id VARCHAR(255) NOT NULL, -- Can be wallet address or user ID
    referred_user_id VARCHAR(255) NOT NULL,
    referrer_username VARCHAR(100),
    referred_username VARCHAR(100),
    
    -- Investment details
    investment_id VARCHAR(36), -- Links to aureus_investments table
    investment_amount DECIMAL(15,2) NOT NULL,
    investment_package VARCHAR(100),
    
    -- Commission details
    commission_level TINYINT NOT NULL, -- 1, 2, or 3
    usdt_commission_percent DECIMAL(5,2) NOT NULL,
    nft_commission_percent DECIMAL(5,2) NOT NULL,
    usdt_commission_amount DECIMAL(15,2) NOT NULL,
    nft_commission_amount INT NOT NULL, -- Number of NFT packs
    
    -- Transaction status
    status ENUM('pending', 'approved', 'paid', 'cancelled', 'failed') DEFAULT 'pending',
    payment_method ENUM('wallet_transfer', 'manual', 'escrow') DEFAULT 'wallet_transfer',
    
    -- Payment details
    usdt_tx_hash VARCHAR(255) NULL,
    nft_tx_hash VARCHAR(255) NULL,
    payment_wallet VARCHAR(255) NULL,
    payment_chain VARCHAR(50) NULL,
    
    -- Admin actions
    approved_by VARCHAR(36) NULL,
    approved_at TIMESTAMP NULL,
    paid_by VARCHAR(36) NULL,
    paid_at TIMESTAMP NULL,
    cancelled_by VARCHAR(36) NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (commission_plan_id) REFERENCES commission_plans(id) ON DELETE RESTRICT,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (paid_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_referrer (referrer_user_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_level (commission_level),
    INDEX idx_status (status),
    INDEX idx_investment (investment_id),
    INDEX idx_created_at (created_at),
    INDEX idx_payment_status (status, paid_at)
);

-- Create referral_relationships table for tracking the referral tree
CREATE TABLE IF NOT EXISTS referral_relationships (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    referrer_user_id VARCHAR(255) NOT NULL,
    referred_user_id VARCHAR(255) NOT NULL,
    referrer_username VARCHAR(100),
    referred_username VARCHAR(100),
    
    -- Relationship details
    referral_code VARCHAR(50), -- Optional referral code used
    referral_source VARCHAR(100), -- social_media, direct_link, etc.
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- Status
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    
    -- Statistics
    total_investments DECIMAL(15,2) DEFAULT 0.00,
    total_commissions_generated DECIMAL(15,2) DEFAULT 0.00,
    total_nft_bonuses_generated INT DEFAULT 0,
    last_investment_date TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_referrer (referrer_user_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_referral_code (referral_code),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    -- Unique constraint to prevent duplicate relationships
    UNIQUE KEY unique_referral_relationship (referrer_user_id, referred_user_id)
);

-- Create commission_payouts table for batch payout management
CREATE TABLE IF NOT EXISTS commission_payouts (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    payout_batch_name VARCHAR(100) NOT NULL,
    
    -- Payout details
    total_transactions INT NOT NULL DEFAULT 0,
    total_usdt_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_nft_amount INT NOT NULL DEFAULT 0,
    
    -- Status
    status ENUM('preparing', 'ready', 'processing', 'completed', 'failed') DEFAULT 'preparing',
    
    -- Processing details
    processed_transactions INT DEFAULT 0,
    failed_transactions INT DEFAULT 0,
    processing_errors JSON NULL,
    
    -- Admin actions
    created_by VARCHAR(36) NOT NULL,
    processed_by VARCHAR(36) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
);

-- Create payout_transaction_items table for linking transactions to payouts
CREATE TABLE IF NOT EXISTS payout_transaction_items (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    payout_id VARCHAR(36) NOT NULL,
    commission_transaction_id VARCHAR(36) NOT NULL,
    
    -- Item status
    status ENUM('included', 'processed', 'failed') DEFAULT 'included',
    error_message TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (payout_id) REFERENCES commission_payouts(id) ON DELETE CASCADE,
    FOREIGN KEY (commission_transaction_id) REFERENCES commission_transactions(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_payout (payout_id),
    INDEX idx_transaction (commission_transaction_id),
    INDEX idx_status (status),
    
    -- Unique constraint
    UNIQUE KEY unique_payout_transaction (payout_id, commission_transaction_id)
);

-- WARNING: DO NOT RUN THIS MIGRATION MULTIPLE TIMES
-- This INSERT IGNORE was causing duplicate commission plans
-- Commission plans should only be created manually by admin through the admin panel
-- Insert default commission plan (ONLY FOR INITIAL SETUP)
INSERT IGNORE INTO commission_plans (
    plan_name,
    description,
    is_active,
    is_default,
    level_1_usdt_percent,
    level_1_nft_percent,
    level_2_usdt_percent,
    level_2_nft_percent,
    level_3_usdt_percent,
    level_3_nft_percent,
    nft_pack_price,
    nft_total_supply,
    nft_remaining_supply,
    max_levels
) VALUES (
    'Default 3-Level Unilevel Plan',
    'Standard 3-level unilevel commission structure with USDT and NFT bonuses',
    TRUE,
    TRUE,
    12.00,
    12.00,
    5.00,
    5.00,
    3.00,
    3.00,
    5.00,
    200000,
    200000,
    3
);
