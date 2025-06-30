-- =====================================================
-- ENHANCED ADMIN PANEL DATABASE SCHEMA
-- =====================================================

-- =====================================================
-- USER COMMUNICATION SYSTEM
-- =====================================================

-- User messages to admin
CREATE TABLE IF NOT EXISTS admin_user_messages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    telegram_id BIGINT NOT NULL,
    user_id INT NULL,
    username VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    message_text TEXT NOT NULL,
    message_type ENUM('contact_admin', 'support_request', 'complaint', 'general') DEFAULT 'contact_admin',
    status ENUM('new', 'read', 'replied', 'resolved', 'closed') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_telegram_id (telegram_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Admin replies to user messages
CREATE TABLE IF NOT EXISTS admin_message_replies (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    original_message_id VARCHAR(36) NOT NULL,
    admin_telegram_id BIGINT NOT NULL,
    admin_username VARCHAR(255),
    reply_text TEXT NOT NULL,
    sent_to_user BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_original_message (original_message_id),
    INDEX idx_admin_telegram_id (admin_telegram_id),
    INDEX idx_sent_to_user (sent_to_user),
    FOREIGN KEY (original_message_id) REFERENCES admin_user_messages(id) ON DELETE CASCADE
);

-- =====================================================
-- PASSWORD RESET ADMIN NOTIFICATIONS
-- =====================================================

-- Password reset requests for admin approval
CREATE TABLE IF NOT EXISTS admin_password_reset_requests (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id INT NOT NULL,
    telegram_id BIGINT NULL,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(255),
    request_reason TEXT,
    request_ip VARCHAR(45),
    request_user_agent TEXT,
    status ENUM('pending', 'approved', 'denied', 'expired') DEFAULT 'pending',
    admin_decision_by BIGINT NULL,
    admin_decision_reason TEXT,
    admin_decision_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_telegram_id (telegram_id),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- ADMIN ACTIONS LOG
-- =====================================================

-- Comprehensive admin action logging
CREATE TABLE IF NOT EXISTS admin_action_logs (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    admin_telegram_id BIGINT NOT NULL,
    admin_username VARCHAR(255),
    action_type ENUM(
        'user_password_change', 'user_email_update', 'user_search',
        'payment_approval', 'payment_rejection', 'message_reply',
        'password_reset_approval', 'password_reset_denial',
        'user_account_status_change', 'terms_acceptance_review'
    ) NOT NULL,
    target_user_id INT NULL,
    target_telegram_id BIGINT NULL,
    action_details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_admin_telegram_id (admin_telegram_id),
    INDEX idx_action_type (action_type),
    INDEX idx_target_user_id (target_user_id),
    INDEX idx_target_telegram_id (target_telegram_id),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success),
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- PAYMENT CONFIRMATION SYSTEM
-- =====================================================

-- Enhanced payment confirmations for admin review
CREATE TABLE IF NOT EXISTS admin_payment_confirmations (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    investment_id VARCHAR(36) NOT NULL,
    user_id INT NOT NULL,
    telegram_id BIGINT NULL,
    payment_method ENUM('crypto', 'bank_transfer', 'manual') NOT NULL,
    amount DECIMAL(15,6) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    transaction_reference VARCHAR(255),
    payment_proof_url VARCHAR(500),
    package_name VARCHAR(100),
    shares INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'processing') DEFAULT 'pending',
    admin_reviewed_by BIGINT NULL,
    admin_review_notes TEXT,
    admin_reviewed_at TIMESTAMP NULL,
    auto_allocation_triggered BOOLEAN DEFAULT FALSE,
    allocation_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_investment_id (investment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_telegram_id (telegram_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE
);

-- =====================================================
-- TERMS AND CONDITIONS TRACKING
-- =====================================================

-- Enhanced terms acceptance tracking
CREATE TABLE IF NOT EXISTS telegram_terms_acceptance (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id INT NULL,
    telegram_id BIGINT NOT NULL,
    investment_id VARCHAR(36) NULL,

    -- Individual terms acceptance
    general_terms_accepted BOOLEAN DEFAULT FALSE,
    privacy_policy_accepted BOOLEAN DEFAULT FALSE,
    investment_risks_accepted BOOLEAN DEFAULT FALSE,
    gold_mining_terms_accepted BOOLEAN DEFAULT FALSE,
    nft_terms_accepted BOOLEAN DEFAULT FALSE,
    dividend_terms_accepted BOOLEAN DEFAULT FALSE,

    -- Acceptance metadata
    acceptance_ip VARCHAR(45),
    acceptance_user_agent TEXT,
    acceptance_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Compliance tracking
    all_terms_accepted BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_telegram_id (telegram_id),
    INDEX idx_investment_id (investment_id),
    INDEX idx_all_accepted (all_terms_accepted),
    INDEX idx_acceptance_timestamp (acceptance_timestamp),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL
);

-- =====================================================
-- ADMIN NOTIFICATION QUEUE
-- =====================================================

-- Queue for admin notifications
CREATE TABLE IF NOT EXISTS admin_notification_queue (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    notification_type ENUM(
        'new_user_message', 'password_reset_request', 'payment_confirmation',
        'system_alert', 'user_registration', 'investment_submission'
    ) NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_user_id INT NULL,
    related_telegram_id BIGINT NULL,
    metadata JSON,
    sent_to_admin BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_notification_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_sent_to_admin (sent_to_admin),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL
);
