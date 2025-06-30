# Database Schema Reference - Aureus Angel Alliance

## 🗄️ Database Overview

**Database Name**: `aureus_angels`  
**Engine**: MySQL 8.0+  
**Character Set**: utf8mb4_unicode_ci  
**Collation**: utf8mb4_unicode_ci  

## 👥 User Management Tables

### `users`
Primary user accounts table
```sql
CREATE TABLE users (
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
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);
```

### `admin_users`
Administrative user accounts
```sql
CREATE TABLE admin_users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('super_admin', 'admin', 'chat_support') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    password_change_required BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `user_profiles`
Extended user profile information
```sql
CREATE TABLE user_profiles (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    phone VARCHAR(20),
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    date_of_birth DATE,
    social_media JSON,
    profile_picture VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 💰 Investment System Tables

### `investment_packages`
Available investment packages
```sql
CREATE TABLE investment_packages (
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
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `investments`
User investment records
```sql
CREATE TABLE investments (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    package_id VARCHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    shares INT NOT NULL,
    roi DECIMAL(10,2) NOT NULL,
    tx_hash VARCHAR(255),
    chain_id VARCHAR(50),
    wallet_address VARCHAR(255),
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('wallet', 'bank_transfer', 'credits') DEFAULT 'wallet',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES investment_packages(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

### `investment_wallets`
Configured payment wallets
```sql
CREATE TABLE investment_wallets (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    network VARCHAR(50) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🔍 KYC System Tables

### `kyc_documents`
KYC document uploads
```sql
CREATE TABLE kyc_documents (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    document_type ENUM('license', 'id', 'passport') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by VARCHAR(36),
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES admin_users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

### `kyc_facial_verification`
Facial recognition verification records
```sql
CREATE TABLE kyc_facial_verification (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    confidence_score DECIMAL(5,4) NOT NULL,
    liveness_score DECIMAL(5,4) NOT NULL,
    verification_image VARCHAR(500) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `kyc_levels`
KYC verification levels
```sql
CREATE TABLE kyc_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level_name VARCHAR(50) NOT NULL,
    level_number INT NOT NULL,
    requirements JSON NOT NULL,
    benefits JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 💬 Chat System Tables

### `chat_sessions`
Live chat sessions
```sql
CREATE TABLE chat_sessions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36),
    guest_email VARCHAR(255),
    guest_name VARCHAR(100),
    agent_id VARCHAR(36),
    status ENUM('active', 'closed', 'waiting') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (agent_id) REFERENCES admin_users(id) ON DELETE SET NULL
);
```

### `chat_messages`
Chat conversation messages
```sql
CREATE TABLE chat_messages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    session_id VARCHAR(36) NOT NULL,
    sender_id VARCHAR(36),
    sender_type ENUM('user', 'agent', 'guest') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
);
```

## 🏆 Commission System Tables

### `commission_plans`
Commission structure configuration
```sql
CREATE TABLE commission_plans (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    plan_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    level_1_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    level_2_percentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    level_3_percentage DECIMAL(5,2) NOT NULL DEFAULT 2.50,
    nft_pack_price DECIMAL(10,2) NOT NULL DEFAULT 5.00,
    nft_total_supply INT NOT NULL DEFAULT 200000,
    nft_remaining_supply INT NOT NULL DEFAULT 200000,
    max_levels INT NOT NULL DEFAULT 3,
    minimum_investment DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    commission_cap DECIMAL(15,6) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `commission_transactions`
Commission payment records
```sql
CREATE TABLE commission_transactions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    plan_id VARCHAR(36) NOT NULL,
    referrer_user_id VARCHAR(36) NOT NULL,
    referred_user_id VARCHAR(36) NOT NULL,
    investment_id VARCHAR(36) NOT NULL,
    level INT NOT NULL,
    commission_percentage DECIMAL(5,2) NOT NULL,
    investment_amount DECIMAL(15,6) NOT NULL,
    commission_usdt DECIMAL(15,6) NOT NULL,
    commission_nft INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES commission_plans(id),
    FOREIGN KEY (referrer_user_id) REFERENCES users(id),
    FOREIGN KEY (referred_user_id) REFERENCES users(id),
    FOREIGN KEY (investment_id) REFERENCES investments(id)
);
```

### `referral_links`
User referral tracking
```sql
CREATE TABLE referral_links (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    referral_code VARCHAR(50) UNIQUE NOT NULL,
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 📜 Certificate System Tables

### `certificates`
Investment certificates
```sql
CREATE TABLE certificates (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    investment_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    template_id INT,
    shares INT NOT NULL,
    investment_amount DECIMAL(10,2) NOT NULL,
    issue_date DATE NOT NULL,
    verification_hash VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('pending', 'issued', 'revoked') DEFAULT 'pending',
    generation_method ENUM('manual', 'automatic') DEFAULT 'manual',
    generated_by VARCHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_id) REFERENCES investments(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (generated_by) REFERENCES admin_users(id)
);
```

### `certificate_templates`
Certificate design templates
```sql
CREATE TABLE certificate_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    template_data JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🌍 Translation System Tables

### `translation_languages`
Supported languages
```sql
CREATE TABLE translation_languages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `translation_keys`
Translation key definitions
```sql
CREATE TABLE translation_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `translations`
Actual translations
```sql
CREATE TABLE translations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_id INT NOT NULL,
    language_id INT NOT NULL,
    translation_text TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES translation_languages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_translation (key_id, language_id)
);
```

## 🎫 Coupon & NFT System Tables

### `coupons`
Promotional coupon codes
```sql
CREATE TABLE coupons (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_investment DECIMAL(10,2) DEFAULT 0.00,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `coupon_usage`
Coupon usage tracking
```sql
CREATE TABLE coupon_usage (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    coupon_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    investment_id VARCHAR(36) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (investment_id) REFERENCES investments(id)
);
```

## 🔐 Security & Session Tables

### `user_sessions`
Active user sessions
```sql
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `security_logs`
Security event logging
```sql
CREATE TABLE security_logs (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    event_type VARCHAR(100) NOT NULL,
    user_id VARCHAR(36),
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    event_data JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## 📊 Indexes and Performance

### Key Indexes
- User lookup: `idx_username`, `idx_email`
- Investment queries: `idx_user_id`, `idx_status`
- Commission tracking: `idx_referrer_user_id`, `idx_level`
- Chat performance: `idx_session_id`, `idx_sender_type`
- Security monitoring: `idx_event_type`, `idx_severity`

### Foreign Key Constraints
- Cascade deletes for user-related data
- Referential integrity for all relationships
- Null handling for optional references

This schema supports a scalable, secure investment platform with comprehensive feature coverage.
