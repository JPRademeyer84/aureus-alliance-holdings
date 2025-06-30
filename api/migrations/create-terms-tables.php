<?php
require_once '../config/cors.php';
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    echo "Creating Terms and Conditions tables...\n";

    // Create terms_acceptance table
    $termsAcceptanceTable = "CREATE TABLE IF NOT EXISTS terms_acceptance (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id VARCHAR(255) NULL,
        email VARCHAR(255) NOT NULL,
        wallet_address VARCHAR(255) NOT NULL,
        investment_id VARCHAR(36) NULL,
        
        -- Terms acceptance checkboxes
        gold_mining_investment_accepted BOOLEAN DEFAULT FALSE,
        nft_shares_understanding_accepted BOOLEAN DEFAULT FALSE,
        delivery_timeline_accepted BOOLEAN DEFAULT FALSE,
        dividend_timeline_accepted BOOLEAN DEFAULT FALSE,
        risk_acknowledgment_accepted BOOLEAN DEFAULT FALSE,
        
        -- Acceptance metadata
        ip_address VARCHAR(45),
        user_agent TEXT,
        acceptance_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        terms_version VARCHAR(10) DEFAULT '1.0',
        
        -- Compliance tracking
        all_terms_accepted BOOLEAN GENERATED ALWAYS AS (
            gold_mining_investment_accepted = TRUE AND
            nft_shares_understanding_accepted = TRUE AND
            delivery_timeline_accepted = TRUE AND
            dividend_timeline_accepted = TRUE AND
            risk_acknowledgment_accepted = TRUE
        ) STORED,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_user_id (user_id),
        INDEX idx_email (email),
        INDEX idx_wallet_address (wallet_address),
        INDEX idx_investment_id (investment_id),
        INDEX idx_all_accepted (all_terms_accepted),
        INDEX idx_acceptance_timestamp (acceptance_timestamp),
        FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL
    )";

    $db->exec($termsAcceptanceTable);
    echo "✓ terms_acceptance table created successfully\n";

    // Create terms_versions table
    $termsVersionsTable = "CREATE TABLE IF NOT EXISTS terms_versions (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        version VARCHAR(10) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT NOT NULL,
        effective_date TIMESTAMP NOT NULL,
        created_by VARCHAR(36) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_version (version),
        INDEX idx_effective_date (effective_date),
        INDEX idx_is_active (is_active),
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
    )";

    $db->exec($termsVersionsTable);
    echo "✓ terms_versions table created successfully\n";

    // Insert default terms version
    $checkVersion = $db->prepare("SELECT COUNT(*) FROM terms_versions WHERE version = '1.0'");
    $checkVersion->execute();
    
    if ($checkVersion->fetchColumn() == 0) {
        $defaultTermsContent = "
# Aureus Angel Alliance Investment Terms & Conditions

## Investment Purpose
Your investment funds will be used exclusively to secure your investment position in the Gold Mining Sector through the pre-purchase of NFT shares before they have been created and minted.

## NFT Shares Understanding
You understand that you are purchasing rights to future NFT shares that represent ownership stakes in gold mining operations. These NFTs are currently in development and will be minted upon completion of the development phase.

## 180-Day Delivery Timeline
You acknowledge and understand that it will take 180 days (6 months) from your investment date before you will receive your mine shares and NFT assets.

## Dividend Payment Schedule
The first dividend payout from mining operations will occur at the end of Q1 2026 (March 31, 2026), with quarterly payments thereafter based on mining profitability.

## Risk Acknowledgment
You acknowledge and understand the risks involved in gold mining investments, including market volatility, operational risks, and that dividend payments are not guaranteed and depend on mining profitability.
        ";

        $insertDefaultTerms = $db->prepare("
            INSERT INTO terms_versions (version, title, content, effective_date, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $insertDefaultTerms->execute([
            '1.0',
            'Aureus Angel Alliance Investment Terms & Conditions v1.0',
            trim($defaultTermsContent),
            date('Y-m-d H:i:s'),
            1
        ]);
        
        echo "✓ Default terms version 1.0 inserted successfully\n";
    } else {
        echo "✓ Default terms version 1.0 already exists\n";
    }

    // Update existing aureus_investments table to ensure it has the required columns
    $alterInvestments = [
        "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS nft_delivery_date TIMESTAMP NULL COMMENT 'Date when NFT will be delivered (180 days from purchase)'",
        "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS roi_delivery_date TIMESTAMP NULL COMMENT 'Date when ROI will be delivered (180 days from purchase)'",
        "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending'",
        "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS nft_delivered BOOLEAN DEFAULT FALSE",
        "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS roi_delivered BOOLEAN DEFAULT FALSE"
    ];

    foreach ($alterInvestments as $alterQuery) {
        try {
            $db->exec($alterQuery);
        } catch (PDOException $e) {
            // Column might already exist, continue
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    echo "✓ aureus_investments table updated with delivery columns\n";

    echo "\n=== Terms and Conditions Tables Setup Complete ===\n";
    echo "✓ All tables created successfully\n";
    echo "✓ Default terms version installed\n";
    echo "✓ Investment table updated with delivery tracking\n";
    echo "✓ Ready for terms acceptance tracking\n\n";

    // Return success response for API calls
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Terms and conditions tables created successfully',
            'tables_created' => ['terms_acceptance', 'terms_versions'],
            'default_version' => '1.0'
        ]);
    }

} catch (Exception $e) {
    $error_message = "Error creating terms tables: " . $e->getMessage();
    echo $error_message . "\n";
    
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
    }
}
?>
