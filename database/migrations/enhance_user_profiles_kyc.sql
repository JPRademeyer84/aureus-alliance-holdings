-- Enhance user_profiles table with comprehensive KYC fields
-- Users will fill in all these fields, admins will approve/reject them

ALTER TABLE user_profiles
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS nationality VARCHAR(100),
ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
ADD COLUMN IF NOT EXISTS address_line_1 VARCHAR(255),
ADD COLUMN IF NOT EXISTS address_line_2 VARCHAR(255),
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20),
ADD COLUMN IF NOT EXISTS state_province VARCHAR(100),
ADD COLUMN IF NOT EXISTS id_number VARCHAR(100),
ADD COLUMN IF NOT EXISTS id_type ENUM('passport', 'national_id', 'drivers_license'),
ADD COLUMN IF NOT EXISTS id_expiry_date DATE,
ADD COLUMN IF NOT EXISTS place_of_birth VARCHAR(100),
ADD COLUMN IF NOT EXISTS occupation VARCHAR(100),
ADD COLUMN IF NOT EXISTS employer VARCHAR(100),
ADD COLUMN IF NOT EXISTS annual_income DECIMAL(15,2),
ADD COLUMN IF NOT EXISTS source_of_funds VARCHAR(255),
ADD COLUMN IF NOT EXISTS purpose_of_account VARCHAR(255),
ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS emergency_contact_relationship VARCHAR(50);

-- Admin approval fields - admins approve/reject each section
ALTER TABLE user_profiles
ADD COLUMN IF NOT EXISTS personal_info_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS personal_info_approved_by VARCHAR(36),
ADD COLUMN IF NOT EXISTS personal_info_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS personal_info_rejection_reason TEXT,

ADD COLUMN IF NOT EXISTS contact_info_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS contact_info_approved_by VARCHAR(36),
ADD COLUMN IF NOT EXISTS contact_info_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS contact_info_rejection_reason TEXT,

ADD COLUMN IF NOT EXISTS address_info_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS address_info_approved_by VARCHAR(36),
ADD COLUMN IF NOT EXISTS address_info_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS address_info_rejection_reason TEXT,

ADD COLUMN IF NOT EXISTS identity_info_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS identity_info_approved_by VARCHAR(36),
ADD COLUMN IF NOT EXISTS identity_info_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS identity_info_rejection_reason TEXT,

ADD COLUMN IF NOT EXISTS financial_info_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS financial_info_approved_by VARCHAR(36),
ADD COLUMN IF NOT EXISTS financial_info_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS financial_info_rejection_reason TEXT,

ADD COLUMN IF NOT EXISTS emergency_contact_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS emergency_contact_approved_by VARCHAR(36),
ADD COLUMN IF NOT EXISTS emergency_contact_approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS emergency_contact_rejection_reason TEXT;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_personal_info_status ON user_profiles(personal_info_status);
CREATE INDEX IF NOT EXISTS idx_contact_info_status ON user_profiles(contact_info_status);
CREATE INDEX IF NOT EXISTS idx_address_info_status ON user_profiles(address_info_status);
CREATE INDEX IF NOT EXISTS idx_identity_info_status ON user_profiles(identity_info_status);
CREATE INDEX IF NOT EXISTS idx_financial_info_status ON user_profiles(financial_info_status);
CREATE INDEX IF NOT EXISTS idx_emergency_contact_status ON user_profiles(emergency_contact_status);
CREATE INDEX IF NOT EXISTS idx_nationality ON user_profiles(nationality);
CREATE INDEX IF NOT EXISTS idx_id_number ON user_profiles(id_number);

-- Create comprehensive KYC verification tracking table
CREATE TABLE IF NOT EXISTS kyc_verification_history (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    section_type ENUM('personal_info', 'contact_info', 'address_info', 'identity_info', 'financial_info', 'emergency_contact', 'documents', 'facial_verification') NOT NULL,
    old_status ENUM('pending', 'approved', 'rejected'),
    new_status ENUM('pending', 'approved', 'rejected') NOT NULL,
    approved_by VARCHAR(36),
    admin_notes TEXT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_section_type (section_type),
    INDEX idx_new_status (new_status),
    INDEX idx_created_at (created_at)
);

-- Update kyc_documents table to include more document types
ALTER TABLE kyc_documents
MODIFY COLUMN type ENUM('passport', 'drivers_license', 'national_id', 'proof_of_address', 'utility_bill', 'bank_statement', 'rental_agreement', 'selfie_with_id') NOT NULL;

-- Add document verification details
ALTER TABLE kyc_documents
ADD COLUMN IF NOT EXISTS document_number VARCHAR(100),
ADD COLUMN IF NOT EXISTS issue_date DATE,
ADD COLUMN IF NOT EXISTS expiry_date DATE,
ADD COLUMN IF NOT EXISTS issuing_authority VARCHAR(100),
ADD COLUMN IF NOT EXISTS verification_notes TEXT,
ADD COLUMN IF NOT EXISTS admin_notes TEXT;
