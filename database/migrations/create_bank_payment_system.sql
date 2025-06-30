-- =====================================================
-- DUAL PAYMENT SYSTEM - BANK PAYMENT INTEGRATION
-- =====================================================
-- This creates the bank payment system for countries with crypto restrictions
-- while maintaining crypto-based commission structure
-- =====================================================

USE aureus_angels;

-- =====================================================
-- COUNTRY PAYMENT CONFIGURATION
-- =====================================================

-- Countries and their allowed payment methods
CREATE TABLE IF NOT EXISTS country_payment_config (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    country_code VARCHAR(3) NOT NULL COMMENT 'ISO 3166-1 alpha-3 country code',
    country_name VARCHAR(100) NOT NULL,
    
    -- Payment method availability
    crypto_payments_allowed BOOLEAN DEFAULT TRUE,
    bank_payments_allowed BOOLEAN DEFAULT FALSE,
    default_payment_method ENUM('crypto', 'bank') DEFAULT 'crypto',
    
    -- Regional settings
    currency_code VARCHAR(3) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    
    -- Compliance and restrictions
    kyc_required_level INT DEFAULT 1 COMMENT '1=Basic, 2=Enhanced, 3=Full',
    investment_limit_usd DECIMAL(15,2) NULL COMMENT 'Maximum investment per user',
    requires_bank_verification BOOLEAN DEFAULT FALSE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    compliance_notes TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY idx_country_code (country_code),
    INDEX idx_country_name (country_name),
    INDEX idx_crypto_allowed (crypto_payments_allowed),
    INDEX idx_bank_allowed (bank_payments_allowed),
    INDEX idx_is_active (is_active)
);

-- =====================================================
-- BANK ACCOUNT CONFIGURATION
-- =====================================================

-- Company bank accounts for different regions/currencies
CREATE TABLE IF NOT EXISTS company_bank_accounts (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    account_name VARCHAR(100) NOT NULL,
    
    -- Bank details
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    routing_number VARCHAR(50) NULL,
    swift_code VARCHAR(20) NULL,
    iban VARCHAR(50) NULL,
    
    -- Account details
    account_holder_name VARCHAR(100) NOT NULL,
    currency_code VARCHAR(3) NOT NULL DEFAULT 'USD',
    country_code VARCHAR(3) NOT NULL,
    
    -- Address information
    bank_address TEXT NULL,
    branch_name VARCHAR(100) NULL,
    branch_code VARCHAR(20) NULL,
    
    -- Usage configuration
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    minimum_amount DECIMAL(15,2) DEFAULT 0.00,
    maximum_amount DECIMAL(15,2) NULL,
    
    -- Processing details
    processing_time_days INT DEFAULT 3 COMMENT 'Expected processing time in business days',
    verification_required BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_currency_code (currency_code),
    INDEX idx_country_code (country_code),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default),
    
    -- Constraints
    UNIQUE KEY idx_account_currency (account_number, currency_code)
);

-- =====================================================
-- BANK PAYMENT TRANSACTIONS
-- =====================================================

-- Bank payment transaction tracking
CREATE TABLE IF NOT EXISTS bank_payment_transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Investment relationship
    investment_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    
    -- Payment details
    bank_account_id VARCHAR(36) NOT NULL,
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
    
    -- Commission processing
    commissions_calculated BOOLEAN DEFAULT FALSE,
    commissions_paid BOOLEAN DEFAULT FALSE,
    commission_payment_date TIMESTAMP NULL,
    
    -- File attachments
    payment_proof_path VARCHAR(500) NULL COMMENT 'Upload proof of payment',
    bank_statement_path VARCHAR(500) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_investment_id (investment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_bank_account_id (bank_account_id),
    INDEX idx_reference_number (reference_number),
    INDEX idx_payment_status (payment_status),
    INDEX idx_verification_status (verification_status),
    INDEX idx_verified_by (verified_by),
    INDEX idx_transfer_date (transfer_date),
    INDEX idx_expires_at (expires_at),
    
    -- Foreign keys
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_account_id) REFERENCES company_bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- =====================================================
-- COMMISSION TRACKING FOR BANK PAYMENTS
-- =====================================================

-- Track commissions from bank payments (paid in USDT)
CREATE TABLE IF NOT EXISTS bank_payment_commissions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Payment relationship
    bank_payment_id VARCHAR(36) NOT NULL,
    investment_id VARCHAR(36) NOT NULL,
    
    -- Commission details
    referrer_user_id VARCHAR(255) NOT NULL,
    commission_level INT NOT NULL COMMENT '1, 2, or 3',
    commission_percentage DECIMAL(5,2) NOT NULL,
    
    -- Amount calculations
    investment_amount_usd DECIMAL(15,6) NOT NULL,
    commission_amount_usd DECIMAL(15,6) NOT NULL,
    commission_amount_usdt DECIMAL(15,6) NOT NULL COMMENT 'Always paid in USDT',
    
    -- Status tracking
    calculation_status ENUM('pending', 'calculated', 'approved', 'paid', 'failed') DEFAULT 'pending',
    payment_status ENUM('pending', 'queued', 'processing', 'completed', 'failed') DEFAULT 'pending',
    
    -- Payment details
    usdt_wallet_address VARCHAR(255) NULL,
    transaction_hash VARCHAR(255) NULL,
    blockchain_network VARCHAR(50) DEFAULT 'polygon',
    
    -- Processing tracking
    calculated_at TIMESTAMP NULL,
    approved_by VARCHAR(36) NULL,
    approved_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_bank_payment_id (bank_payment_id),
    INDEX idx_investment_id (investment_id),
    INDEX idx_referrer_user_id (referrer_user_id),
    INDEX idx_commission_level (commission_level),
    INDEX idx_calculation_status (calculation_status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_approved_by (approved_by),
    INDEX idx_transaction_hash (transaction_hash),
    
    -- Foreign keys
    FOREIGN KEY (bank_payment_id) REFERENCES bank_payment_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- =====================================================
-- PAYMENT METHOD DETECTION LOG
-- =====================================================

-- Log payment method selection and country detection
CREATE TABLE IF NOT EXISTS payment_method_log (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- User and session info
    user_id VARCHAR(255) NULL,
    session_id VARCHAR(100) NULL,
    ip_address VARCHAR(45) NOT NULL,
    
    -- Detection details
    detected_country VARCHAR(3) NULL,
    user_selected_country VARCHAR(3) NULL,
    available_methods JSON NULL COMMENT 'Available payment methods for user',
    selected_method ENUM('crypto', 'bank') NULL,
    
    -- Context
    investment_package VARCHAR(50) NULL,
    investment_amount DECIMAL(15,6) NULL,
    user_agent TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_detected_country (detected_country),
    INDEX idx_selected_method (selected_method),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- INSERT DEFAULT CONFIGURATION DATA
-- =====================================================

-- Insert default country configurations
INSERT INTO country_payment_config (country_code, country_name, crypto_payments_allowed, bank_payments_allowed, default_payment_method, kyc_required_level) VALUES
-- Crypto-friendly countries
('USA', 'United States', TRUE, TRUE, 'crypto', 2),
('CAN', 'Canada', TRUE, TRUE, 'crypto', 2),
('GBR', 'United Kingdom', TRUE, TRUE, 'crypto', 2),
('DEU', 'Germany', TRUE, TRUE, 'crypto', 2),
('FRA', 'France', TRUE, TRUE, 'crypto', 2),
('AUS', 'Australia', TRUE, TRUE, 'crypto', 2),
('JPN', 'Japan', TRUE, TRUE, 'crypto', 2),
('SGP', 'Singapore', TRUE, TRUE, 'crypto', 2),
('CHE', 'Switzerland', TRUE, TRUE, 'crypto', 1),
('NLD', 'Netherlands', TRUE, TRUE, 'crypto', 2),

-- Countries with crypto restrictions (bank payments preferred)
('CHN', 'China', FALSE, TRUE, 'bank', 3),
('IND', 'India', FALSE, TRUE, 'bank', 3),
('RUS', 'Russia', FALSE, TRUE, 'bank', 3),
('TUR', 'Turkey', FALSE, TRUE, 'bank', 3),
('IDN', 'Indonesia', FALSE, TRUE, 'bank', 3),
('THA', 'Thailand', FALSE, TRUE, 'bank', 3),
('VNM', 'Vietnam', FALSE, TRUE, 'bank', 3),
('BGD', 'Bangladesh', FALSE, TRUE, 'bank', 3),
('PAK', 'Pakistan', FALSE, TRUE, 'bank', 3),
('EGY', 'Egypt', FALSE, TRUE, 'bank', 3),

-- Default for other countries
('ZZZ', 'Other Countries', TRUE, TRUE, 'crypto', 2);

-- Insert default company bank account
INSERT INTO company_bank_accounts (
    account_name, bank_name, account_number, swift_code, 
    account_holder_name, currency_code, country_code, 
    bank_address, is_active, is_default, processing_time_days
) VALUES (
    'Aureus Alliance Holdings - USD Account',
    'JPMorgan Chase Bank',
    '1234567890',
    'CHASUS33',
    'Aureus Alliance Holdings Ltd',
    'USD',
    'USA',
    '270 Park Avenue, New York, NY 10017, United States',
    TRUE,
    TRUE,
    3
);
