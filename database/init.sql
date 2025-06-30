-- Create the aureus_angels database
CREATE DATABASE IF NOT EXISTS aureus_angels;
USE aureus_angels;

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    full_name VARCHAR(100) NULL,
    role ENUM('super_admin', 'admin', 'chat_support') DEFAULT 'chat_support',
    is_active BOOLEAN DEFAULT TRUE,
    chat_status ENUM('online', 'offline', 'busy') DEFAULT 'offline',
    last_activity TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_chat_status (chat_status),
    INDEX idx_is_active (is_active)
);

-- Create investment_packages table
CREATE TABLE IF NOT EXISTS investment_packages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    shares INT NOT NULL,
    roi DECIMAL(10,2) NOT NULL,
    annual_dividends DECIMAL(10,2) NOT NULL,
    quarter_dividends DECIMAL(10,2) NOT NULL,
    icon VARCHAR(50) DEFAULT 'star',
    icon_color VARCHAR(50) DEFAULT 'bg-green-500',
    bonuses JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create investment_wallets table
CREATE TABLE IF NOT EXISTS investment_wallets (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    chain VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create aureus_investments table
CREATE TABLE IF NOT EXISTS aureus_investments (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    chain VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    investment_plan VARCHAR(50) NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    shares INT NOT NULL DEFAULT 0,
    roi DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tx_hash VARCHAR(255) NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create wallet_connections table for logging
CREATE TABLE IF NOT EXISTS wallet_connections (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    provider VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    chain_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create offline_messages table for when no admin is available
CREATE TABLE IF NOT EXISTS offline_messages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    admin_reply TEXT NULL,
    replied_by VARCHAR(36) NULL,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guest_email (guest_email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (replied_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_is_active (is_active)
);

-- SECURITY: Admin user will be created with temporary password via API
-- Password must be changed on first login
-- No default passwords stored in database files

-- Insert default investment packages
INSERT IGNORE INTO investment_packages (name, price, shares, roi, annual_dividends, quarter_dividends, icon, icon_color, bonuses) VALUES 
('Starter', 50.00, 2, 400.00, 200.00, 50.00, 'star', 'bg-green-500', '["Community Discord Access", "Guaranteed Common NFT Card"]'),
('Bronze', 100.00, 10, 800.00, 800.00, 200.00, 'square', 'bg-amber-700', '["All Starter Bonuses", "Guaranteed Uncommon NFT Card", "Early Game Access", "Priority Support"]'),
('Silver', 250.00, 30, 2000.00, 2500.00, 625.00, 'circle', 'bg-gray-300', '["All Bronze Bonuses", "Guaranteed Epic NFT Card", "Exclusive Game Events Access", "VIP Game Benefits"]'),
('Gold', 500.00, 75, 4000.00, 6000.00, 1500.00, 'diamond', 'bg-yellow-500', '["All Silver Bonuses", "Guaranteed Rare NFT Card", "Monthly Strategy Calls", "Beta Testing Access"]'),
('Platinum', 1000.00, 175, 8000.00, 15000.00, 3750.00, 'crown', 'bg-purple-500', '["All Gold Bonuses", "Guaranteed Legendary NFT Card", "Quarterly Executive Briefings", "Priority Feature Requests"]'),
('Diamond', 2500.00, 500, 20000.00, 50000.00, 12500.00, 'gem', 'bg-blue-500', '["All Platinum Bonuses", "Guaranteed Mythic NFT Card", "Direct Line to Development Team", "Annual VIP Event Invitation"]'),
('Obsidian', 50000.00, 12500, 250000.00, 1000000.00, 500000.00, 'square', 'bg-black', '["All Diamond Bonuses", "Lifetime Executive Board Membership", "Custom Executive NFT Card", "Personal Jet Invitation to Strategy Summit", "Handwritten Letter of Appreciation from the Founders"]');

-- Insert default wallet addresses for different chains
INSERT IGNORE INTO investment_wallets (chain, address, is_active) VALUES 
('ethereum', '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b', TRUE),
('polygon', '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b', TRUE),
('bsc', '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b', TRUE);
