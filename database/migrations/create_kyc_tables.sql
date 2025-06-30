-- Create KYC documents table
CREATE TABLE IF NOT EXISTS kyc_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('drivers_license', 'national_id', 'passport') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    verified_by INT NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type)
);

-- Add KYC status columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS kyc_status ENUM('not_verified', 'pending', 'verified', 'rejected') DEFAULT 'not_verified',
ADD COLUMN IF NOT EXISTS facial_verification_status ENUM('not_started', 'pending', 'verified', 'failed') DEFAULT 'not_started',
ADD COLUMN IF NOT EXISTS kyc_verified_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS kyc_verified_by INT NULL,
ADD COLUMN IF NOT EXISTS kyc_rejection_reason TEXT NULL;

-- Add foreign key for kyc_verified_by if it doesn't exist
-- Note: This might fail if the column already exists with a foreign key, so we'll handle it gracefully
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'kyc_verified_by' 
     AND CONSTRAINT_NAME != 'PRIMARY') = 0,
    'ALTER TABLE users ADD CONSTRAINT fk_users_kyc_verified_by FOREIGN KEY (kyc_verified_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" as message'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for KYC columns if they don't exist
CREATE INDEX IF NOT EXISTS idx_users_kyc_status ON users(kyc_status);
CREATE INDEX IF NOT EXISTS idx_users_facial_verification_status ON users(facial_verification_status);

-- Create assets/kyc directory structure (this will be handled by PHP)
-- The PHP upload script will create the directory if it doesn't exist
