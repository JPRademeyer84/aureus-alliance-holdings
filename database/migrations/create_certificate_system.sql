-- =====================================================
-- AUREUS ALLIANCE HOLDINGS - CERTIFICATE SYSTEM
-- =====================================================
-- This creates the complete certificate system for share certificates
-- that will be converted to NFTs in the future
-- =====================================================

USE aureus_angels;

-- =====================================================
-- CERTIFICATE TEMPLATES SYSTEM
-- =====================================================

-- Certificate templates for different share types
CREATE TABLE IF NOT EXISTS certificate_templates (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    template_name VARCHAR(100) NOT NULL,
    template_type ENUM('share_certificate', 'nft_certificate', 'dividend_certificate') DEFAULT 'share_certificate',
    
    -- Template files
    frame_image_path VARCHAR(500) NULL COMMENT 'Path to certificate frame/border image',
    background_image_path VARCHAR(500) NULL COMMENT 'Path to certificate background image',
    
    -- Template configuration
    template_config JSON COMMENT 'JSON config for text positions, fonts, colors, etc.',
    
    -- Template status
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    version VARCHAR(10) DEFAULT '1.0',
    
    -- Admin tracking
    created_by VARCHAR(36) NOT NULL,
    updated_by VARCHAR(36) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_template_name (template_name),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default),
    INDEX idx_created_by (created_by),
    
    -- Foreign keys
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- =====================================================
-- GENERATED CERTIFICATES SYSTEM
-- =====================================================

-- Generated certificates for users
CREATE TABLE IF NOT EXISTS share_certificates (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    certificate_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique certificate number (e.g., AAH-2024-000001)',
    
    -- Investment relationship
    investment_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    
    -- Certificate details
    template_id VARCHAR(36) NOT NULL,
    certificate_type ENUM('share_certificate', 'nft_certificate', 'dividend_certificate') DEFAULT 'share_certificate',
    
    -- Share information
    share_quantity INT NOT NULL,
    share_class VARCHAR(50) DEFAULT 'Common',
    certificate_value DECIMAL(15,6) NOT NULL,
    issue_date DATE NOT NULL,
    
    -- Certificate files
    certificate_image_path VARCHAR(500) NULL COMMENT 'Path to generated certificate image',
    certificate_pdf_path VARCHAR(500) NULL COMMENT 'Path to generated certificate PDF',
    
    -- Legal status
    legal_status ENUM('valid', 'invalidated', 'converted_to_nft') DEFAULT 'valid',
    invalidation_reason TEXT NULL,
    invalidated_at TIMESTAMP NULL,
    invalidated_by VARCHAR(36) NULL,
    
    -- NFT conversion tracking
    nft_conversion_date TIMESTAMP NULL,
    nft_token_id VARCHAR(100) NULL,
    nft_contract_address VARCHAR(255) NULL,
    nft_blockchain VARCHAR(50) NULL,
    
    -- Verification and security
    verification_hash VARCHAR(255) NOT NULL COMMENT 'Hash for certificate authenticity verification',
    qr_code_data TEXT NULL COMMENT 'QR code data for verification',
    
    -- Generation tracking
    generation_status ENUM('pending', 'generating', 'completed', 'failed') DEFAULT 'pending',
    generation_method ENUM('manual', 'automatic') DEFAULT 'manual',
    generated_by VARCHAR(36) NULL,
    generation_error TEXT NULL,
    
    -- Delivery tracking
    delivery_status ENUM('pending', 'sent', 'delivered', 'viewed') DEFAULT 'pending',
    delivery_method ENUM('email', 'dashboard', 'download') DEFAULT 'dashboard',
    delivered_at TIMESTAMP NULL,
    first_viewed_at TIMESTAMP NULL,
    view_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_investment_id (investment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_template_id (template_id),
    INDEX idx_legal_status (legal_status),
    INDEX idx_generation_status (generation_status),
    INDEX idx_delivery_status (delivery_status),
    INDEX idx_issue_date (issue_date),
    INDEX idx_verification_hash (verification_hash),
    INDEX idx_nft_token_id (nft_token_id),
    
    -- Foreign keys
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE RESTRICT,
    FOREIGN KEY (generated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (invalidated_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- =====================================================
-- CERTIFICATE ACCESS LOG
-- =====================================================

-- Track certificate access for security and audit
CREATE TABLE IF NOT EXISTS certificate_access_log (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    certificate_id VARCHAR(36) NOT NULL,

    -- Access details
    accessed_by VARCHAR(255) NOT NULL COMMENT 'User ID who accessed the certificate',
    access_type ENUM('view', 'download', 'print', 'share', 'verify') NOT NULL,
    access_method ENUM('dashboard', 'direct_link', 'email_link', 'api') DEFAULT 'dashboard',

    -- Access context
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer VARCHAR(500) NULL,

    -- Timestamps
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_certificate_id (certificate_id),
    INDEX idx_accessed_by (accessed_by),
    INDEX idx_access_type (access_type),
    INDEX idx_accessed_at (accessed_at),

    -- Foreign keys
    FOREIGN KEY (certificate_id) REFERENCES share_certificates(id) ON DELETE CASCADE
);

-- =====================================================
-- CERTIFICATE VERIFICATION SYSTEM
-- =====================================================

-- Public certificate verification (for external verification)
CREATE TABLE IF NOT EXISTS certificate_verifications (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    certificate_id VARCHAR(36) NOT NULL,

    -- Verification details
    verification_code VARCHAR(100) UNIQUE NOT NULL COMMENT 'Public verification code',
    verification_url VARCHAR(500) NOT NULL COMMENT 'Public verification URL',

    -- Verification status
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,

    -- Verification tracking
    verification_count INT DEFAULT 0,
    last_verified_at TIMESTAMP NULL,
    last_verified_ip VARCHAR(45) NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_certificate_id (certificate_id),
    INDEX idx_verification_code (verification_code),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at),

    -- Foreign keys
    FOREIGN KEY (certificate_id) REFERENCES share_certificates(id) ON DELETE CASCADE
);

-- =====================================================
-- CERTIFICATE BATCH OPERATIONS
-- =====================================================

-- Track batch certificate generation operations
CREATE TABLE IF NOT EXISTS certificate_batch_operations (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    batch_name VARCHAR(100) NOT NULL,
    operation_type ENUM('generate', 'regenerate', 'invalidate', 'convert_to_nft') NOT NULL,

    -- Batch details
    total_certificates INT NOT NULL DEFAULT 0,
    processed_certificates INT DEFAULT 0,
    successful_certificates INT DEFAULT 0,
    failed_certificates INT DEFAULT 0,

    -- Batch status
    batch_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',

    -- Batch configuration
    template_id VARCHAR(36) NULL,
    batch_config JSON NULL COMMENT 'Batch-specific configuration',

    -- Processing details
    started_by VARCHAR(36) NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_log TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_batch_name (batch_name),
    INDEX idx_operation_type (operation_type),
    INDEX idx_batch_status (batch_status),
    INDEX idx_started_by (started_by),
    INDEX idx_started_at (started_at),

    -- Foreign keys
    FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (started_by) REFERENCES admin_users(id) ON DELETE RESTRICT
);
