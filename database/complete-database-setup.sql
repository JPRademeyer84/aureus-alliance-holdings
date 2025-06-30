-- =====================================================
-- AUREUS ANGEL ALLIANCE - COMPLETE DATABASE SETUP
-- =====================================================
-- This script creates all required tables and columns
-- No dummy data included - production ready
-- =====================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS aureus_angel_alliance;
USE aureus_angel_alliance;

-- =====================================================
-- CORE USER SYSTEM
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_email_verified (email_verified)
);

-- Enhanced user profiles with KYC and social media
CREATE TABLE IF NOT EXISTS user_profiles (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    phone VARCHAR(20),
    country VARCHAR(100),
    city VARCHAR(100),
    date_of_birth DATE,
    profile_image VARCHAR(255),
    bio TEXT,
    
    -- Social Media Links
    telegram_username VARCHAR(100),
    whatsapp_number VARCHAR(20),
    twitter_handle VARCHAR(100),
    instagram_handle VARCHAR(100),
    linkedin_profile VARCHAR(255),
    facebook_profile VARCHAR(255),
    
    -- KYC Information
    kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    kyc_verified_at TIMESTAMP NULL,
    kyc_rejected_reason TEXT,
    
    -- Profile Completion
    profile_completion INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_profile (user_id),
    INDEX idx_kyc_status (kyc_status),
    INDEX idx_completion (profile_completion),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- KYC Documents
CREATE TABLE IF NOT EXISTS kyc_documents (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    type ENUM('passport', 'drivers_license', 'national_id', 'proof_of_address') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by VARCHAR(36) NULL,
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- ADMIN SYSTEM
-- =====================================================

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    permissions JSON,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- =====================================================
-- INVESTMENT SYSTEM
-- =====================================================

-- Investment packages
CREATE TABLE IF NOT EXISTS investment_packages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    shares INT NOT NULL,
    roi DECIMAL(15,2) NOT NULL,
    annual_dividends DECIMAL(15,2) NOT NULL,
    quarter_dividends DECIMAL(15,2) NOT NULL,
    icon VARCHAR(50) DEFAULT 'star',
    icon_color VARCHAR(50) DEFAULT 'bg-green-500',
    bonuses JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_price (price),
    INDEX idx_active (is_active)
);

-- Investment wallets
CREATE TABLE IF NOT EXISTS investment_wallets (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    chain VARCHAR(50) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_chain (chain),
    INDEX idx_active (is_active)
);

-- Aureus investments with 180-day countdown system
CREATE TABLE IF NOT EXISTS aureus_investments (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    chain VARCHAR(50) NOT NULL,
    amount DECIMAL(15,6) NOT NULL,
    investment_plan VARCHAR(50) NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    shares INT NOT NULL DEFAULT 0,
    roi DECIMAL(15,6) NOT NULL DEFAULT 0.00,
    tx_hash VARCHAR(255) NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    
    -- 180-Day Countdown System
    nft_delivery_date TIMESTAMP NULL COMMENT 'Date when NFT will be delivered (180 days from purchase)',
    roi_delivery_date TIMESTAMP NULL COMMENT 'Date when ROI will be delivered (180 days from purchase)',
    delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending',
    nft_delivered BOOLEAN DEFAULT FALSE,
    roi_delivered BOOLEAN DEFAULT FALSE,
    nft_delivery_tx_hash VARCHAR(255) NULL,
    roi_delivery_tx_hash VARCHAR(255) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_wallet_address (wallet_address),
    INDEX idx_status (status),
    INDEX idx_package_name (package_name),
    INDEX idx_nft_delivery_date (nft_delivery_date),
    INDEX idx_roi_delivery_date (roi_delivery_date),
    INDEX idx_delivery_status (delivery_status)
);

-- =====================================================
-- REFERRAL & COMMISSION SYSTEM
-- =====================================================

-- Referral relationships
CREATE TABLE IF NOT EXISTS referral_relationships (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    referrer_user_id VARCHAR(255) NOT NULL,
    referred_user_id VARCHAR(255) NOT NULL,
    level INT NOT NULL DEFAULT 1,
    investment_amount DECIMAL(15,6) DEFAULT 0,
    commission_earned DECIMAL(15,6) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_referrer (referrer_user_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_level (level),
    INDEX idx_status (status),
    UNIQUE KEY unique_referral (referrer_user_id, referred_user_id, level)
);

-- Commission plans
CREATE TABLE IF NOT EXISTS commission_plans (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    description TEXT,
    commission_structure JSON NOT NULL COMMENT 'Level-based commission rates',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_active (is_active)
);

-- Commission transactions
CREATE TABLE IF NOT EXISTS commission_transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    commission_plan_id VARCHAR(36),
    referrer_user_id VARCHAR(255) NOT NULL,
    referred_user_id VARCHAR(255) NOT NULL,
    investment_id VARCHAR(36),
    level INT NOT NULL,
    investment_amount DECIMAL(15,6) NOT NULL,
    usdt_commission_amount DECIMAL(15,6) NOT NULL,
    nft_commission_amount DECIMAL(15,6) DEFAULT 0,
    commission_percentage DECIMAL(5,2) NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    transaction_hash VARCHAR(255),
    payment_method ENUM('usdt', 'bank_transfer', 'crypto') DEFAULT 'usdt',
    
    -- Admin tracking
    approved_by VARCHAR(36) NULL,
    approved_at TIMESTAMP NULL,
    paid_by VARCHAR(36) NULL,
    paid_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_referrer (referrer_user_id),
    INDEX idx_referred (referred_user_id),
    INDEX idx_investment (investment_id),
    INDEX idx_status (status),
    INDEX idx_level (level),
    FOREIGN KEY (commission_plan_id) REFERENCES commission_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL
);

-- =====================================================
-- TERMS AND CONDITIONS SYSTEM
-- =====================================================

-- Terms and conditions acceptance tracking
CREATE TABLE IF NOT EXISTS terms_acceptance (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    investment_id VARCHAR(36) NULL,

    -- Terms acceptance checkboxes
    gold_mining_investment_accepted BOOLEAN DEFAULT FALSE,
    nft_shares_understanding_accepted BOOLEAN DEFAULT FALSE,
    delivery_timeline_accepted BOOLEAN DEFAULT FALSE,
    dividend_timeline_accepted BOOLEAN DEFAULT FALSE,
    risk_acknowledgment_accepted BOOLEAN DEFAULT FALSE,

    -- Acceptance metadata
    ip_address VARCHAR(45),
    user_agent TEXT,
    acceptance_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    terms_version VARCHAR(10) DEFAULT '1.0',

    -- Compliance tracking
    all_terms_accepted BOOLEAN GENERATED ALWAYS AS (
        gold_mining_investment_accepted = TRUE AND
        nft_shares_understanding_accepted = TRUE AND
        delivery_timeline_accepted = TRUE AND
        dividend_timeline_accepted = TRUE AND
        risk_acknowledgment_accepted = TRUE
    ) STORED,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_wallet_address (wallet_address),
    INDEX idx_investment_id (investment_id),
    INDEX idx_all_accepted (all_terms_accepted),
    INDEX idx_acceptance_timestamp (acceptance_timestamp),
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL
);

-- Terms and conditions versions for audit trail
CREATE TABLE IF NOT EXISTS terms_versions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    version VARCHAR(10) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    effective_date TIMESTAMP NOT NULL,
    created_by VARCHAR(36) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_version (version),
    INDEX idx_effective_date (effective_date),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- =====================================================
-- DELIVERY MANAGEMENT SYSTEM
-- =====================================================

-- Delivery schedule for admin management
CREATE TABLE IF NOT EXISTS delivery_schedule (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    investment_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    investment_amount DECIMAL(15,6) NOT NULL,
    nft_delivery_date TIMESTAMP NOT NULL,
    roi_delivery_date TIMESTAMP NOT NULL,
    nft_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
    roi_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    notes TEXT NULL,
    assigned_to VARCHAR(36) NULL COMMENT 'Admin user assigned to handle delivery',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_investment_id (investment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_nft_delivery_date (nft_delivery_date),
    INDEX idx_roi_delivery_date (roi_delivery_date),
    INDEX idx_nft_status (nft_status),
    INDEX idx_roi_status (roi_status),
    INDEX idx_assigned_to (assigned_to),
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Delivery notifications
CREATE TABLE IF NOT EXISTS delivery_notifications (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    investment_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    notification_type ENUM('30_days', '7_days', '1_day', 'delivery_ready') NOT NULL,
    delivery_type ENUM('nft', 'roi', 'both') NOT NULL,
    sent_at TIMESTAMP NULL,
    email_sent BOOLEAN DEFAULT FALSE,
    sms_sent BOOLEAN DEFAULT FALSE,
    push_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_investment_id (investment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_sent_at (sent_at),
    UNIQUE KEY unique_notification (investment_id, notification_type, delivery_type),
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE
);

-- =====================================================
-- COMMUNICATION SYSTEM
-- =====================================================

-- Contact messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    admin_response TEXT NULL,
    responded_by VARCHAR(36) NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (responded_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Live chat sessions
CREATE TABLE IF NOT EXISTS chat_sessions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_email VARCHAR(255),
    user_name VARCHAR(255),
    admin_id VARCHAR(36),
    status ENUM('active', 'closed', 'waiting') DEFAULT 'waiting',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    
    INDEX idx_user_email (user_email),
    INDEX idx_admin_id (admin_id),
    INDEX idx_status (status),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Chat messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    session_id VARCHAR(36) NOT NULL,
    sender_type ENUM('user', 'admin') NOT NULL,
    sender_id VARCHAR(36),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_sender_type (sender_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
);

-- Agent status for live chat
CREATE TABLE IF NOT EXISTS agent_status (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    admin_id VARCHAR(36) NOT NULL,
    is_online BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    max_concurrent_chats INT DEFAULT 5,
    current_chat_count INT DEFAULT 0,
    
    UNIQUE KEY unique_admin_status (admin_id),
    INDEX idx_is_online (is_online),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- =====================================================
-- WALLET CONNECTION SYSTEM
-- =====================================================

-- Wallet connections log
CREATE TABLE IF NOT EXISTS wallet_connections (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(255),
    wallet_address VARCHAR(255) NOT NULL,
    wallet_type VARCHAR(50) NOT NULL,
    chain_id VARCHAR(10) NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    disconnected_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_wallet_address (wallet_address),
    INDEX idx_wallet_type (wallet_type),
    INDEX idx_is_active (is_active)
);

-- =====================================================
-- VIEWS FOR OPTIMIZED QUERIES
-- =====================================================

-- Investment countdown view for easy countdown queries
CREATE OR REPLACE VIEW investment_countdown_view AS
SELECT 
    ai.id,
    ai.user_id,
    ai.package_name,
    ai.amount,
    ai.shares,
    ai.roi,
    ai.status,
    ai.created_at,
    ai.nft_delivery_date,
    ai.roi_delivery_date,
    ai.delivery_status,
    ai.nft_delivered,
    ai.roi_delivered,
    
    -- Calculate days remaining
    CASE 
        WHEN ai.nft_delivery_date IS NULL THEN NULL
        WHEN ai.nft_delivered = TRUE THEN 0
        ELSE GREATEST(0, DATEDIFF(ai.nft_delivery_date, NOW()))
    END as nft_days_remaining,
    
    CASE 
        WHEN ai.roi_delivery_date IS NULL THEN NULL
        WHEN ai.roi_delivered = TRUE THEN 0
        ELSE GREATEST(0, DATEDIFF(ai.roi_delivery_date, NOW()))
    END as roi_days_remaining,
    
    -- Calculate hours remaining for more precise countdown
    CASE 
        WHEN ai.nft_delivery_date IS NULL THEN NULL
        WHEN ai.nft_delivered = TRUE THEN 0
        ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.nft_delivery_date))
    END as nft_hours_remaining,
    
    CASE 
        WHEN ai.roi_delivery_date IS NULL THEN NULL
        WHEN ai.roi_delivered = TRUE THEN 0
        ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.roi_delivery_date))
    END as roi_hours_remaining,
    
    -- Status indicators
    CASE 
        WHEN ai.nft_delivered = TRUE THEN 'delivered'
        WHEN ai.nft_delivery_date <= NOW() THEN 'ready'
        WHEN DATEDIFF(ai.nft_delivery_date, NOW()) <= 7 THEN 'soon'
        ELSE 'pending'
    END as nft_countdown_status,
    
    CASE 
        WHEN ai.roi_delivered = TRUE THEN 'delivered'
        WHEN ai.roi_delivery_date <= NOW() THEN 'ready'
        WHEN DATEDIFF(ai.roi_delivery_date, NOW()) <= 7 THEN 'soon'
        ELSE 'pending'
    END as roi_countdown_status

FROM aureus_investments ai
WHERE ai.status = 'completed';
