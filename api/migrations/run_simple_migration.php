<?php
/**
 * Simple Business Model Migration Script
 * Executes migration in correct order without complex parsing
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "=== Aureus Angel Alliance - Business Model Migration ===\n";
    echo "Starting migration...\n";

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connected\n";

    // Step 1: Remove ROI columns from investment_packages
    echo "Step 1: Updating investment_packages table...\n";
    try {
        $db->exec("ALTER TABLE investment_packages DROP COLUMN IF EXISTS roi_percentage");
        $db->exec("ALTER TABLE investment_packages DROP COLUMN IF EXISTS annual_dividends");
        $db->exec("ALTER TABLE investment_packages DROP COLUMN IF EXISTS quarter_dividends");
        echo "✓ Removed ROI columns\n";
    } catch (Exception $e) {
        echo "Warning: " . $e->getMessage() . "\n";
    }

    // Step 2: Add new columns to investment_packages
    try {
        $db->exec("ALTER TABLE investment_packages ADD COLUMN commission_percentage DECIMAL(5,2) DEFAULT 20.00");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN competition_allocation DECIMAL(5,2) DEFAULT 15.00");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN npo_allocation DECIMAL(5,2) DEFAULT 10.00");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN platform_allocation DECIMAL(5,2) DEFAULT 25.00");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN mine_allocation DECIMAL(5,2) DEFAULT 35.00");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN phase_id INT DEFAULT 1");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN is_active BOOLEAN DEFAULT FALSE");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN max_participants INT DEFAULT NULL");
        $db->exec("ALTER TABLE investment_packages ADD COLUMN participants_count INT DEFAULT 0");
        echo "✓ Added new revenue distribution columns\n";
    } catch (Exception $e) {
        echo "Warning: " . $e->getMessage() . "\n";
    }

    // Step 3: Create phases table
    echo "Step 3: Creating phases table...\n";
    $phasesSQL = "CREATE TABLE IF NOT EXISTS phases (
        id INT PRIMARY KEY AUTO_INCREMENT,
        phase_number INT NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT FALSE,
        start_date DATETIME NULL,
        end_date DATETIME NULL,
        total_packages_available INT DEFAULT 0,
        packages_sold INT DEFAULT 0,
        total_revenue DECIMAL(15,2) DEFAULT 0.00,
        commission_paid DECIMAL(15,2) DEFAULT 0.00,
        competition_pool DECIMAL(15,2) DEFAULT 0.00,
        npo_fund DECIMAL(15,2) DEFAULT 0.00,
        platform_fund DECIMAL(15,2) DEFAULT 0.00,
        mine_fund DECIMAL(15,2) DEFAULT 0.00,
        revenue_distribution JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($phasesSQL);
    echo "✓ Created phases table\n";

    // Step 4: Create competitions table
    echo "Step 4: Creating competitions table...\n";
    $competitionsSQL = "CREATE TABLE IF NOT EXISTS competitions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        phase_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        prize_pool DECIMAL(15,2) DEFAULT 0.00,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        winner_selection_criteria ENUM('sales_volume', 'sales_count', 'referrals') DEFAULT 'sales_volume',
        max_winners INT DEFAULT 10,
        prize_distribution JSON,
        rules TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($competitionsSQL);
    echo "✓ Created competitions table\n";

    // Step 5: Create competition_participants table
    echo "Step 5: Creating competition_participants table...\n";
    $participantsSQL = "CREATE TABLE IF NOT EXISTS competition_participants (
        id INT PRIMARY KEY AUTO_INCREMENT,
        competition_id INT NOT NULL,
        user_id INT NOT NULL,
        sales_count INT DEFAULT 0,
        total_volume DECIMAL(15,2) DEFAULT 0.00,
        referrals_count INT DEFAULT 0,
        current_rank INT DEFAULT 0,
        prize_amount DECIMAL(15,2) DEFAULT 0.00,
        is_winner BOOLEAN DEFAULT FALSE,
        prize_paid BOOLEAN DEFAULT FALSE,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_participant (competition_id, user_id)
    )";
    $db->exec($participantsSQL);
    echo "✓ Created competition_participants table\n";

    // Step 6: Create npo_fund table
    echo "Step 6: Creating npo_fund table...\n";
    $npoSQL = "CREATE TABLE IF NOT EXISTS npo_fund (
        id INT PRIMARY KEY AUTO_INCREMENT,
        transaction_id VARCHAR(255) UNIQUE,
        source_investment_id INT,
        phase_id INT,
        amount DECIMAL(15,2) NOT NULL,
        percentage DECIMAL(5,2) DEFAULT 10.00,
        status ENUM('pending', 'allocated', 'distributed') DEFAULT 'pending',
        npo_recipient VARCHAR(255),
        distribution_date DATETIME NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($npoSQL);
    echo "✓ Created npo_fund table\n";

    // Step 7: Create share_certificates table
    echo "Step 7: Creating share_certificates table...\n";
    $certificatesSQL = "CREATE TABLE IF NOT EXISTS share_certificates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        certificate_number VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        investment_id INT NOT NULL,
        shares_amount INT NOT NULL,
        share_value DECIMAL(10,2) NOT NULL,
        total_value DECIMAL(15,2) NOT NULL,
        issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        expiry_date DATETIME NOT NULL,
        is_printed BOOLEAN DEFAULT FALSE,
        print_count INT DEFAULT 0,
        is_void BOOLEAN DEFAULT FALSE,
        void_reason VARCHAR(255),
        void_date DATETIME NULL,
        pdf_path VARCHAR(500),
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($certificatesSQL);
    echo "✓ Created share_certificates table\n";

    // Step 8: Insert default phases
    echo "Step 8: Inserting default phases...\n";
    for ($i = 1; $i <= 20; $i++) {
        $name = $i <= 5 ? "Phase $i - Foundation" : "Phase $i - Advanced";
        $description = $i <= 5 ? "Foundation phase $i" : "Advanced phase $i";
        
        $stmt = $db->prepare("INSERT IGNORE INTO phases (phase_number, name, description) VALUES (?, ?, ?)");
        $stmt->execute([$i, $name, $description]);
    }
    echo "✓ Inserted 20 phases\n";

    // Step 9: Update existing data
    echo "Step 9: Updating existing data...\n";
    $db->exec("UPDATE investment_packages SET phase_id = 1, is_active = TRUE WHERE phase_id IS NULL OR phase_id = 0");
    echo "✓ Updated existing packages\n";

    echo "\n✅ Migration completed successfully!\n";
    echo "New business model is now active.\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
