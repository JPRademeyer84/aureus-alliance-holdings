<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating KYC Levels System...\n";
    
    // 1. Create kyc_levels table
    $createLevelsTable = "
        CREATE TABLE IF NOT EXISTS kyc_levels (
            id INT PRIMARY KEY,
            level_number INT NOT NULL,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            badge_color VARCHAR(20),
            badge_icon VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level_number (level_number)
        )
    ";
    $db->exec($createLevelsTable);
    echo "✓ Created kyc_levels table\n";
    
    // 2. Create kyc_level_requirements table
    $createRequirementsTable = "
        CREATE TABLE IF NOT EXISTS kyc_level_requirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level_id INT NOT NULL,
            requirement_type ENUM('email_verification', 'phone_verification', 'profile_completion', 'document_upload', 'facial_verification', 'address_verification', 'enhanced_due_diligence', 'account_activity') NOT NULL,
            requirement_name VARCHAR(100) NOT NULL,
            description TEXT,
            is_mandatory BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (level_id) REFERENCES kyc_levels(id) ON DELETE CASCADE,
            INDEX idx_level_id (level_id),
            INDEX idx_requirement_type (requirement_type)
        )
    ";
    $db->exec($createRequirementsTable);
    echo "✓ Created kyc_level_requirements table\n";
    
    // 3. Create kyc_level_benefits table
    $createBenefitsTable = "
        CREATE TABLE IF NOT EXISTS kyc_level_benefits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level_id INT NOT NULL,
            benefit_type ENUM('investment_limit', 'commission_rate', 'withdrawal_limit', 'nft_limit', 'support_tier', 'feature_access') NOT NULL,
            benefit_name VARCHAR(100) NOT NULL,
            benefit_value VARCHAR(100),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (level_id) REFERENCES kyc_levels(id) ON DELETE CASCADE,
            INDEX idx_level_id (level_id),
            INDEX idx_benefit_type (benefit_type)
        )
    ";
    $db->exec($createBenefitsTable);
    echo "✓ Created kyc_level_benefits table\n";
    
    // 4. Create user_kyc_levels table
    $createUserLevelsTable = "
        CREATE TABLE IF NOT EXISTS user_kyc_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            current_level INT DEFAULT 1,
            level_1_completed_at TIMESTAMP NULL,
            level_2_completed_at TIMESTAMP NULL,
            level_3_completed_at TIMESTAMP NULL,
            level_1_progress JSON,
            level_2_progress JSON,
            level_3_progress JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id),
            INDEX idx_current_level (current_level)
        )
    ";
    $db->exec($createUserLevelsTable);
    echo "✓ Created user_kyc_levels table\n";
    
    // 5. Create kyc_level_progress table
    $createProgressTable = "
        CREATE TABLE IF NOT EXISTS kyc_level_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            level_id INT NOT NULL,
            requirement_id INT NOT NULL,
            status ENUM('not_started', 'in_progress', 'completed', 'failed') DEFAULT 'not_started',
            completed_at TIMESTAMP NULL,
            verification_data JSON,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (level_id) REFERENCES kyc_levels(id) ON DELETE CASCADE,
            FOREIGN KEY (requirement_id) REFERENCES kyc_level_requirements(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_requirement (user_id, requirement_id),
            INDEX idx_user_level (user_id, level_id),
            INDEX idx_status (status)
        )
    ";
    $db->exec($createProgressTable);
    echo "✓ Created kyc_level_progress table\n";
    
    // Insert default KYC levels
    $insertLevels = "
        INSERT IGNORE INTO kyc_levels (id, level_number, name, description, badge_color, badge_icon) VALUES
        (1, 1, 'Basic', 'Basic verification with email and phone', '#3B82F6', 'shield'),
        (2, 2, 'Intermediate', 'Document verification with ID and address proof', '#F59E0B', 'shield-check'),
        (3, 3, 'Advanced', 'Enhanced verification with additional due diligence', '#10B981', 'shield-star')
    ";
    $db->exec($insertLevels);
    echo "✓ Inserted default KYC levels\n";
    
    // Insert Level 1 requirements
    $insertLevel1Requirements = "
        INSERT IGNORE INTO kyc_level_requirements (level_id, requirement_type, requirement_name, description, sort_order) VALUES
        (1, 'email_verification', 'Email Verification', 'Verify your email address', 1),
        (1, 'phone_verification', 'Phone Verification', 'Verify your phone number', 2),
        (1, 'profile_completion', 'Basic Profile', 'Complete basic profile information', 3)
    ";
    $db->exec($insertLevel1Requirements);
    echo "✓ Inserted Level 1 requirements\n";
    
    // Insert Level 2 requirements
    $insertLevel2Requirements = "
        INSERT IGNORE INTO kyc_level_requirements (level_id, requirement_type, requirement_name, description, sort_order) VALUES
        (2, 'document_upload', 'Government ID', 'Upload government-issued ID document', 1),
        (2, 'address_verification', 'Proof of Address', 'Upload proof of address document', 2),
        (2, 'facial_verification', 'Facial Recognition', 'Complete facial recognition verification', 3)
    ";
    $db->exec($insertLevel2Requirements);
    echo "✓ Inserted Level 2 requirements\n";
    
    // Insert Level 3 requirements
    $insertLevel3Requirements = "
        INSERT IGNORE INTO kyc_level_requirements (level_id, requirement_type, requirement_name, description, sort_order) VALUES
        (3, 'enhanced_due_diligence', 'Enhanced Due Diligence', 'Additional documentation and verification', 1),
        (3, 'account_activity', 'Account Activity', 'Minimum 30 days of account activity', 2)
    ";
    $db->exec($insertLevel3Requirements);
    echo "✓ Inserted Level 3 requirements\n";
    
    // Insert Level 1 benefits
    $insertLevel1Benefits = "
        INSERT IGNORE INTO kyc_level_benefits (level_id, benefit_type, benefit_name, benefit_value, description) VALUES
        (1, 'investment_limit', 'Investment Packages', '$25-$100', 'Access to basic investment packages'),
        (1, 'commission_rate', 'Commission Rate', '5%', 'Standard commission rate on referrals'),
        (1, 'withdrawal_limit', 'Daily Withdrawal', '$1,000', 'Maximum daily withdrawal limit'),
        (1, 'nft_limit', 'NFT Purchases', '10 packs/month', 'Monthly NFT pack purchase limit'),
        (1, 'support_tier', 'Support Level', 'Standard', 'Standard customer support'),
        (1, 'feature_access', 'Platform Access', 'Basic', 'Basic platform features')
    ";
    $db->exec($insertLevel1Benefits);
    echo "✓ Inserted Level 1 benefits\n";
    
    // Insert Level 2 benefits
    $insertLevel2Benefits = "
        INSERT IGNORE INTO kyc_level_benefits (level_id, benefit_type, benefit_name, benefit_value, description) VALUES
        (2, 'investment_limit', 'Investment Packages', '$25-$500', 'Access to intermediate investment packages'),
        (2, 'commission_rate', 'Commission Rate', '7%', 'Enhanced commission rate on referrals'),
        (2, 'withdrawal_limit', 'Daily Withdrawal', '$10,000', 'Increased daily withdrawal limit'),
        (2, 'nft_limit', 'NFT Purchases', '50 packs/month', 'Increased monthly NFT pack limit'),
        (2, 'support_tier', 'Support Level', 'Priority', 'Priority customer support'),
        (2, 'feature_access', 'Platform Access', 'Advanced', 'Advanced platform features')
    ";
    $db->exec($insertLevel2Benefits);
    echo "✓ Inserted Level 2 benefits\n";
    
    // Insert Level 3 benefits
    $insertLevel3Benefits = "
        INSERT IGNORE INTO kyc_level_benefits (level_id, benefit_type, benefit_name, benefit_value, description) VALUES
        (3, 'investment_limit', 'Investment Packages', '$25-$1,000', 'Access to all investment packages'),
        (3, 'commission_rate', 'Commission Rate', '10%', 'Premium commission rate on referrals'),
        (3, 'withdrawal_limit', 'Daily Withdrawal', 'Unlimited', 'No daily withdrawal limits'),
        (3, 'nft_limit', 'NFT Purchases', 'Unlimited', 'Unlimited NFT pack purchases'),
        (3, 'support_tier', 'Support Level', 'VIP', 'VIP customer support with dedicated manager'),
        (3, 'feature_access', 'Platform Access', 'Premium', 'All premium features and early access')
    ";
    $db->exec($insertLevel3Benefits);
    echo "✓ Inserted Level 3 benefits\n";
    
    echo "\n🎉 KYC Levels System created successfully!\n";
    echo "✓ 3 KYC levels defined\n";
    echo "✓ Requirements and benefits configured\n";
    echo "✓ Database schema ready for user level tracking\n";
    
} catch (Exception $e) {
    echo "❌ Error creating KYC Levels System: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
