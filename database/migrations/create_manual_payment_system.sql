-- =====================================================
-- MANUAL PAYMENT SYSTEM TABLES
-- =====================================================
-- This migration creates the necessary tables for the manual payment system
-- that allows users to pay directly from exchanges like Binance to company wallets

-- Manual Payment Transactions Table
CREATE TABLE IF NOT EXISTS manual_payment_transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    payment_id VARCHAR(50) UNIQUE NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    
    -- Payment Details
    amount_usd DECIMAL(15,2) NOT NULL,
    chain VARCHAR(50) NOT NULL,
    company_wallet_address VARCHAR(100) NOT NULL,
    
    -- Sender Information
    sender_name VARCHAR(100) NOT NULL,
    sender_wallet_address VARCHAR(100) NULL,
    transaction_hash VARCHAR(100) NULL,
    notes TEXT NULL,
    
    -- File Upload
    payment_proof_path VARCHAR(500) NULL,
    
    -- Status Tracking
    payment_status ENUM('pending', 'confirmed', 'failed', 'expired') DEFAULT 'pending',
    verification_status ENUM('pending', 'approved', 'rejected', 'reviewing') DEFAULT 'pending',
    
    -- Admin Verification
    verified_by VARCHAR(36) NULL,
    verified_at TIMESTAMP NULL,
    verification_notes TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    -- Indexes
    INDEX idx_payment_id (payment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_verification_status (verification_status),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Manual Payment Investment Links Table
-- Links manual payments to investment packages
CREATE TABLE IF NOT EXISTS manual_payment_investments (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    manual_payment_id VARCHAR(36) NOT NULL,
    investment_id VARCHAR(36) NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    package_price DECIMAL(15,2) NOT NULL,
    shares_allocated INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_manual_payment_id (manual_payment_id),
    INDEX idx_investment_id (investment_id),
    
    FOREIGN KEY (manual_payment_id) REFERENCES manual_payment_transactions(id) ON DELETE CASCADE
);

-- Manual Payment Status History Table
-- Track status changes for audit purposes
CREATE TABLE IF NOT EXISTS manual_payment_status_history (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    manual_payment_id VARCHAR(36) NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(36) NULL,
    change_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_manual_payment_id (manual_payment_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (manual_payment_id) REFERENCES manual_payment_transactions(id) ON DELETE CASCADE
);

-- Manual Payment Notifications Table
-- Track email notifications sent to users
CREATE TABLE IF NOT EXISTS manual_payment_notifications (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    manual_payment_id VARCHAR(36) NOT NULL,
    notification_type ENUM('submitted', 'approved', 'rejected', 'expired', 'reminder') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email_status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    
    INDEX idx_manual_payment_id (manual_payment_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_sent_at (sent_at),
    
    FOREIGN KEY (manual_payment_id) REFERENCES manual_payment_transactions(id) ON DELETE CASCADE
);

-- Create upload directory for payment proofs
-- This would typically be handled by the application, but we document it here
-- Directory: /uploads/payment_proofs/
-- Structure: /uploads/payment_proofs/YYYY/MM/DD/filename

-- Insert default configuration for manual payments
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('manual_payment_enabled', 'true', 'Enable manual payment system'),
('manual_payment_expiry_days', '7', 'Days before manual payment expires'),
('manual_payment_max_amount', '100000', 'Maximum amount for manual payments in USD'),
('manual_payment_min_amount', '10', 'Minimum amount for manual payments in USD'),
('manual_payment_auto_approve', 'false', 'Auto-approve manual payments (not recommended)'),
('manual_payment_notification_email', 'payments@aureusalliance.com', 'Email for manual payment notifications');

-- Create triggers for status history tracking
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS manual_payment_status_change_trigger
AFTER UPDATE ON manual_payment_transactions
FOR EACH ROW
BEGIN
    IF OLD.payment_status != NEW.payment_status OR OLD.verification_status != NEW.verification_status THEN
        INSERT INTO manual_payment_status_history (
            manual_payment_id, 
            old_status, 
            new_status, 
            changed_by,
            change_reason
        ) VALUES (
            NEW.id,
            CONCAT(OLD.payment_status, '/', OLD.verification_status),
            CONCAT(NEW.payment_status, '/', NEW.verification_status),
            NEW.verified_by,
            NEW.verification_notes
        );
    END IF;
END$$

DELIMITER ;

-- Create view for admin dashboard
CREATE OR REPLACE VIEW manual_payments_admin_view AS
SELECT 
    mpt.id,
    mpt.payment_id,
    mpt.user_id,
    u.username,
    u.email,
    mpt.amount_usd,
    mpt.chain,
    mpt.sender_name,
    mpt.payment_status,
    mpt.verification_status,
    mpt.created_at,
    mpt.expires_at,
    mpt.verified_by,
    mpt.verified_at,
    admin.username as verified_by_username,
    CASE 
        WHEN mpt.expires_at < NOW() AND mpt.payment_status = 'pending' THEN 'expired'
        ELSE mpt.payment_status
    END as effective_status,
    DATEDIFF(mpt.expires_at, NOW()) as days_until_expiry
FROM manual_payment_transactions mpt
JOIN users u ON mpt.user_id = u.id
LEFT JOIN admin_users admin ON mpt.verified_by = admin.id
ORDER BY mpt.created_at DESC;

-- Create view for user dashboard
CREATE OR REPLACE VIEW manual_payments_user_view AS
SELECT 
    mpt.payment_id,
    mpt.amount_usd,
    mpt.chain,
    mpt.payment_status,
    mpt.verification_status,
    mpt.created_at,
    mpt.expires_at,
    CASE 
        WHEN mpt.expires_at < NOW() AND mpt.payment_status = 'pending' THEN 'expired'
        ELSE mpt.payment_status
    END as effective_status,
    DATEDIFF(mpt.expires_at, NOW()) as days_until_expiry
FROM manual_payment_transactions mpt
WHERE mpt.user_id = @user_id
ORDER BY mpt.created_at DESC;

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_manual_payment_user_status ON manual_payment_transactions(user_id, payment_status);
CREATE INDEX IF NOT EXISTS idx_manual_payment_verification ON manual_payment_transactions(verification_status, created_at);
CREATE INDEX IF NOT EXISTS idx_manual_payment_expiry ON manual_payment_transactions(expires_at, payment_status);

-- Success message
SELECT 'Manual payment system tables created successfully' as message;
