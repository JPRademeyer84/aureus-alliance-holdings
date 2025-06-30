-- Create user_profiles table for extended user information
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Personal Information
    phone VARCHAR(20),
    country VARCHAR(100),
    city VARCHAR(100),
    date_of_birth DATE,
    profile_image VARCHAR(255),
    bio TEXT,
    
    -- Social Media & Contact
    telegram_username VARCHAR(100),
    whatsapp_number VARCHAR(20),
    twitter_handle VARCHAR(100),
    instagram_handle VARCHAR(100),
    linkedin_profile VARCHAR(255),
    facebook_profile VARCHAR(255),
    
    -- KYC Information
    kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    kyc_documents JSON,
    kyc_verified_at TIMESTAMP NULL,
    kyc_rejected_reason TEXT,
    
    -- Profile Completion
    profile_completion INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_kyc_status (kyc_status),
    INDEX idx_country (country)
);

-- Create affiliate_downline table for tracking referral relationships
CREATE TABLE IF NOT EXISTS affiliate_downline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    level TINYINT NOT NULL DEFAULT 1,
    
    -- Investment tracking
    total_invested DECIMAL(15,2) DEFAULT 0.00,
    commission_generated DECIMAL(15,2) DEFAULT 0.00,
    nft_bonus_generated INT DEFAULT 0,
    
    -- Activity tracking
    last_activity TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_id),
    INDEX idx_level (level),
    INDEX idx_status (status),
    
    -- Unique constraint to prevent duplicate relationships
    UNIQUE KEY unique_referral (referrer_id, referred_id)
);

-- Create marketing_campaigns table for tracking social media campaigns
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Campaign details
    campaign_name VARCHAR(255),
    platform VARCHAR(50), -- facebook, twitter, instagram, whatsapp, telegram
    content TEXT,
    referral_link VARCHAR(500),
    
    -- Tracking
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    shares INT DEFAULT 0,
    
    -- Status
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_platform (platform),
    INDEX idx_status (status)
);

-- Create marketing_analytics table for detailed tracking
CREATE TABLE IF NOT EXISTS marketing_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    user_id INT NOT NULL,
    
    -- Event details
    event_type ENUM('click', 'conversion', 'share', 'view') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    
    -- Additional data
    metadata JSON,
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_campaign (campaign_id),
    INDEX idx_user (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);

-- Create marketing_assets table for admin-uploaded marketing materials
CREATE TABLE IF NOT EXISTS marketing_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Asset details
    type ENUM('banner', 'image', 'video', 'logo', 'document') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,

    -- File information
    file_url VARCHAR(500) NOT NULL,
    file_size VARCHAR(50),
    file_format VARCHAR(10),

    -- Status and metadata
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    download_count INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Add some sample data for testing (remove in production)
-- This is just for development - will be removed when we have real users

-- Insert sample profile data
-- INSERT INTO user_profiles (user_id, country, kyc_status, profile_completion)
-- SELECT id, 'United States', 'pending', 20 FROM users LIMIT 1;
