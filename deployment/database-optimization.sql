-- ============================================================================
-- DATABASE OPTIMIZATION FOR AUREUS ANGEL ALLIANCE
-- ============================================================================
-- This script optimizes the database for production performance
-- ============================================================================

-- Set MySQL configuration for production
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_log_file_size = 268435456; -- 256MB
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SET GLOBAL innodb_flush_method = 'O_DIRECT';
SET GLOBAL query_cache_size = 134217728; -- 128MB
SET GLOBAL query_cache_type = 1;
SET GLOBAL max_connections = 200;
SET GLOBAL thread_cache_size = 16;
SET GLOBAL table_open_cache = 2000;

-- ============================================================================
-- INDEX OPTIMIZATION
-- ============================================================================

-- Users table indexes
ALTER TABLE users 
ADD INDEX idx_username (username),
ADD INDEX idx_email (email),
ADD INDEX idx_status (status),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by);

-- Aureus investments table indexes
ALTER TABLE aureus_investments 
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_package_id (package_id),
ADD INDEX idx_status (status),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_confirmed_at (confirmed_at),
ADD INDEX idx_amount (amount),
ADD INDEX idx_wallet_address (wallet_address),
ADD INDEX idx_transaction_hash (transaction_hash);

-- Commission tracking indexes
ALTER TABLE commission_tracking 
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_referrer_id (referrer_id),
ADD INDEX idx_investment_id (investment_id),
ADD INDEX idx_level (level),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_status (status);

-- KYC verification indexes
ALTER TABLE kyc_verification 
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_status (status),
ADD INDEX idx_verification_level (verification_level),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_updated_at (updated_at);

-- Live chat indexes
ALTER TABLE live_chat_sessions 
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_admin_id (admin_id),
ADD INDEX idx_status (status),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_updated_at (updated_at);

ALTER TABLE live_chat_messages 
ADD INDEX idx_session_id (session_id),
ADD INDEX idx_sender_id (sender_id),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_message_type (message_type);

-- Social sharing indexes
ALTER TABLE social_shares 
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_platform (platform),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_content_type (content_type);

-- Gold Diggers Club indexes
ALTER TABLE gold_diggers_prizes 
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_rank (rank),
ADD INDEX idx_status (status),
ADD INDEX idx_calculated_at (calculated_at),
ADD INDEX idx_distributed_at (distributed_at);

-- Email notifications indexes
ALTER TABLE email_log 
ADD INDEX idx_recipient (recipient),
ADD INDEX idx_email_type (email_type),
ADD INDEX idx_status (status),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_sent_at (sent_at);

-- Manual verification payments indexes
ALTER TABLE manual_verification_payments 
ADD INDEX idx_investment_id (investment_id),
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_verification_status (verification_status),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_verified_at (verified_at);

-- ============================================================================
-- COMPOSITE INDEXES FOR COMPLEX QUERIES
-- ============================================================================

-- User investment summary
ALTER TABLE aureus_investments 
ADD INDEX idx_user_status_created (user_id, status, created_at);

-- Commission calculations
ALTER TABLE commission_tracking 
ADD INDEX idx_referrer_level_created (referrer_id, level, created_at),
ADD INDEX idx_user_investment_level (user_id, investment_id, level);

-- KYC status tracking
ALTER TABLE kyc_verification 
ADD INDEX idx_user_status_level (user_id, status, verification_level);

-- Chat session management
ALTER TABLE live_chat_sessions 
ADD INDEX idx_admin_status_updated (admin_id, status, updated_at);

-- Social media analytics
ALTER TABLE social_shares 
ADD INDEX idx_user_platform_created (user_id, platform, created_at);

-- Prize distribution tracking
ALTER TABLE gold_diggers_prizes 
ADD INDEX idx_status_rank_calculated (status, rank, calculated_at);

-- ============================================================================
-- TABLE OPTIMIZATION
-- ============================================================================

-- Optimize table storage engines and settings
ALTER TABLE users ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE aureus_investments ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE commission_tracking ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE kyc_verification ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE live_chat_sessions ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE live_chat_messages ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE social_shares ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE gold_diggers_prizes ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE email_log ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
ALTER TABLE manual_verification_payments ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

-- ============================================================================
-- PARTITIONING FOR LARGE TABLES
-- ============================================================================

-- Partition email_log by month for better performance
ALTER TABLE email_log 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    PARTITION p202403 VALUES LESS THAN (202404),
    PARTITION p202404 VALUES LESS THAN (202405),
    PARTITION p202405 VALUES LESS THAN (202406),
    PARTITION p202406 VALUES LESS THAN (202407),
    PARTITION p202407 VALUES LESS THAN (202408),
    PARTITION p202408 VALUES LESS THAN (202409),
    PARTITION p202409 VALUES LESS THAN (202410),
    PARTITION p202410 VALUES LESS THAN (202411),
    PARTITION p202411 VALUES LESS THAN (202412),
    PARTITION p202412 VALUES LESS THAN (202501),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Partition live_chat_messages by month
ALTER TABLE live_chat_messages 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    PARTITION p202403 VALUES LESS THAN (202404),
    PARTITION p202404 VALUES LESS THAN (202405),
    PARTITION p202405 VALUES LESS THAN (202406),
    PARTITION p202406 VALUES LESS THAN (202407),
    PARTITION p202407 VALUES LESS THAN (202408),
    PARTITION p202408 VALUES LESS THAN (202409),
    PARTITION p202409 VALUES LESS THAN (202410),
    PARTITION p202410 VALUES LESS THAN (202411),
    PARTITION p202411 VALUES LESS THAN (202412),
    PARTITION p202412 VALUES LESS THAN (202501),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- ============================================================================
-- QUERY OPTIMIZATION VIEWS
-- ============================================================================

-- User investment summary view
CREATE OR REPLACE VIEW user_investment_summary AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    COUNT(ai.id) as total_investments,
    COALESCE(SUM(ai.amount), 0) as total_invested,
    COALESCE(SUM(ai.shares), 0) as total_shares,
    COALESCE(SUM(ai.roi_amount), 0) as total_roi,
    MAX(ai.created_at) as last_investment_date,
    u.created_at as registration_date
FROM users u
LEFT JOIN aureus_investments ai ON u.id = ai.user_id AND ai.status IN ('confirmed', 'active', 'completed')
GROUP BY u.id, u.username, u.email, u.created_at;

-- Commission summary view
CREATE OR REPLACE VIEW commission_summary AS
SELECT 
    ct.referrer_id as user_id,
    COUNT(ct.id) as total_commissions,
    COALESCE(SUM(ct.commission_amount), 0) as total_earned,
    COALESCE(SUM(CASE WHEN ct.level = 1 THEN ct.commission_amount ELSE 0 END), 0) as level1_earnings,
    COALESCE(SUM(CASE WHEN ct.level = 2 THEN ct.commission_amount ELSE 0 END), 0) as level2_earnings,
    COALESCE(SUM(CASE WHEN ct.level = 3 THEN ct.commission_amount ELSE 0 END), 0) as level3_earnings,
    MAX(ct.created_at) as last_commission_date
FROM commission_tracking ct
WHERE ct.status = 'confirmed'
GROUP BY ct.referrer_id;

-- Active chat sessions view
CREATE OR REPLACE VIEW active_chat_sessions AS
SELECT 
    lcs.*,
    u.username,
    u.email,
    COUNT(lcm.id) as message_count,
    MAX(lcm.created_at) as last_message_time
FROM live_chat_sessions lcs
JOIN users u ON lcs.user_id = u.id
LEFT JOIN live_chat_messages lcm ON lcs.id = lcm.session_id
WHERE lcs.status IN ('active', 'waiting')
GROUP BY lcs.id, u.username, u.email;

-- ============================================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- ============================================================================

DELIMITER //

-- Calculate user commission earnings
CREATE PROCEDURE CalculateUserCommissions(IN userId INT)
BEGIN
    DECLARE total_earnings DECIMAL(10,2) DEFAULT 0;
    
    SELECT COALESCE(SUM(commission_amount), 0) INTO total_earnings
    FROM commission_tracking 
    WHERE referrer_id = userId AND status = 'confirmed';
    
    UPDATE users 
    SET commission_balance = total_earnings 
    WHERE id = userId;
END //

-- Update investment ROI status
CREATE PROCEDURE UpdateInvestmentROI(IN investmentId INT)
BEGIN
    DECLARE roi_date DATE;
    
    SELECT DATE_ADD(confirmed_at, INTERVAL 180 DAY) INTO roi_date
    FROM aureus_investments 
    WHERE id = investmentId AND status = 'active';
    
    IF roi_date <= CURDATE() THEN
        UPDATE aureus_investments 
        SET status = 'completed', 
            completed_at = NOW() 
        WHERE id = investmentId;
    END IF;
END //

-- Clean old email logs
CREATE PROCEDURE CleanOldEmailLogs()
BEGIN
    DELETE FROM email_log 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND status = 'sent';
END //

-- Update leaderboard rankings
CREATE PROCEDURE UpdateLeaderboardRankings()
BEGIN
    -- Update Gold Diggers Club rankings
    UPDATE users u
    SET leaderboard_rank = (
        SELECT ranking FROM (
            SELECT user_id, 
                   ROW_NUMBER() OVER (ORDER BY total_invested DESC, total_referrals DESC) as ranking
            FROM (
                SELECT u2.id as user_id,
                       COALESCE(SUM(ai.amount), 0) as total_invested,
                       COUNT(DISTINCT ref.id) as total_referrals
                FROM users u2
                LEFT JOIN aureus_investments ai ON u2.id = ai.user_id AND ai.status IN ('confirmed', 'active', 'completed')
                LEFT JOIN users ref ON u2.id = ref.referred_by
                GROUP BY u2.id
            ) rankings
        ) ranked_users
        WHERE ranked_users.user_id = u.id
    );
END //

DELIMITER ;

-- ============================================================================
-- MAINTENANCE PROCEDURES
-- ============================================================================

-- Create events for automatic maintenance
CREATE EVENT IF NOT EXISTS daily_maintenance
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Update ROI status for investments
    CALL UpdateInvestmentROI(0);
    
    -- Update leaderboard rankings
    CALL UpdateLeaderboardRankings();
    
    -- Optimize tables
    OPTIMIZE TABLE users, aureus_investments, commission_tracking;
END;

CREATE EVENT IF NOT EXISTS weekly_cleanup
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Clean old email logs
    CALL CleanOldEmailLogs();
    
    -- Analyze tables for optimization
    ANALYZE TABLE users, aureus_investments, commission_tracking, kyc_verification;
END;

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- ============================================================================
-- PERFORMANCE MONITORING
-- ============================================================================

-- Create performance monitoring table
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name (metric_name),
    INDEX idx_recorded_at (recorded_at)
);

-- Insert initial performance baseline
INSERT INTO performance_metrics (metric_name, metric_value) VALUES
('avg_query_time', 0.0),
('total_connections', 0),
('buffer_pool_hit_ratio', 0.0),
('slow_queries_per_hour', 0);

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================

SELECT 'Database optimization completed successfully!' as status,
       'Indexes created, tables optimized, procedures installed' as details,
       NOW() as completed_at;
