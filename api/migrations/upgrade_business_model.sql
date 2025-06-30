-- =====================================================
-- Aureus Angel Alliance - Business Model Upgrade
-- Migration Script: ROI Model → Direct Commission Model
-- =====================================================

-- Start transaction for safe migration
START TRANSACTION;

-- =====================================================
-- 1. MODIFY EXISTING INVESTMENT_PACKAGES TABLE
-- =====================================================

-- Remove ROI-related columns
ALTER TABLE investment_packages 
DROP COLUMN IF EXISTS roi_percentage,
DROP COLUMN IF EXISTS annual_dividends,
DROP COLUMN IF EXISTS quarter_dividends;

-- Add new revenue distribution columns (without foreign key constraints yet)
ALTER TABLE investment_packages
ADD COLUMN commission_percentage DECIMAL(5,2) DEFAULT 20.00 COMMENT 'Direct sales commission percentage',
ADD COLUMN competition_allocation DECIMAL(5,2) DEFAULT 15.00 COMMENT 'Competition prize pool allocation percentage',
ADD COLUMN npo_allocation DECIMAL(5,2) DEFAULT 10.00 COMMENT 'NPO charity fund allocation percentage',
ADD COLUMN platform_allocation DECIMAL(5,2) DEFAULT 25.00 COMMENT 'Platform & Tech allocation percentage',
ADD COLUMN mine_allocation DECIMAL(5,2) DEFAULT 35.00 COMMENT 'Mine setup & expansion allocation percentage',
ADD COLUMN phase_id INT DEFAULT 1 COMMENT 'Phase number (1-20)',
ADD COLUMN is_active BOOLEAN DEFAULT FALSE COMMENT 'Manual activation control',
ADD COLUMN max_participants INT DEFAULT NULL COMMENT 'Maximum participants per package',
ADD COLUMN participants_count INT DEFAULT 0 COMMENT 'Current participants count';

-- =====================================================
-- 2. CREATE PHASES TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS phases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phase_number INT NOT NULL UNIQUE COMMENT 'Phase number (1-20)',
    name VARCHAR(255) NOT NULL COMMENT 'Phase name',
    description TEXT COMMENT 'Phase description',
    is_active BOOLEAN DEFAULT FALSE COMMENT 'Phase activation status',
    start_date DATETIME NULL COMMENT 'Phase start date',
    end_date DATETIME NULL COMMENT 'Phase end date',
    total_packages_available INT DEFAULT 0 COMMENT 'Total packages in this phase',
    packages_sold INT DEFAULT 0 COMMENT 'Packages sold in this phase',
    total_revenue DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total revenue generated',
    commission_paid DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total commissions paid',
    competition_pool DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Competition prize pool',
    npo_fund DECIMAL(15,2) DEFAULT 0.00 COMMENT 'NPO fund accumulated',
    platform_fund DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Platform fund accumulated',
    mine_fund DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Mine fund accumulated',
    revenue_distribution JSON COMMENT 'Revenue distribution settings',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. CREATE COMPETITIONS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS competitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phase_id INT NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT 'Competition name',
    description TEXT COMMENT 'Competition description',
    prize_pool DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total prize pool amount',
    start_date DATETIME NOT NULL COMMENT 'Competition start date',
    end_date DATETIME NOT NULL COMMENT 'Competition end date',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Competition status',
    winner_selection_criteria ENUM('sales_volume', 'sales_count', 'referrals') DEFAULT 'sales_volume',
    max_winners INT DEFAULT 10 COMMENT 'Maximum number of winners',
    prize_distribution JSON COMMENT 'Prize distribution structure',
    rules TEXT COMMENT 'Competition rules and terms',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE CASCADE
);

-- =====================================================
-- 4. CREATE COMPETITION_PARTICIPANTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS competition_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competition_id INT NOT NULL,
    user_id INT NOT NULL,
    sales_count INT DEFAULT 0 COMMENT 'Number of sales made',
    total_volume DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total sales volume',
    referrals_count INT DEFAULT 0 COMMENT 'Number of referrals',
    current_rank INT DEFAULT 0 COMMENT 'Current ranking position',
    prize_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Prize amount won',
    is_winner BOOLEAN DEFAULT FALSE COMMENT 'Winner status',
    prize_paid BOOLEAN DEFAULT FALSE COMMENT 'Prize payment status',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (competition_id, user_id)
);

-- =====================================================
-- 5. CREATE NPO_FUND TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS npo_fund (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id VARCHAR(255) UNIQUE COMMENT 'Unique transaction identifier',
    source_investment_id INT COMMENT 'Source investment ID',
    phase_id INT COMMENT 'Phase ID',
    amount DECIMAL(15,2) NOT NULL COMMENT 'NPO fund amount',
    percentage DECIMAL(5,2) DEFAULT 10.00 COMMENT 'Percentage allocated',
    status ENUM('pending', 'allocated', 'distributed') DEFAULT 'pending',
    npo_recipient VARCHAR(255) COMMENT 'NPO recipient organization',
    distribution_date DATETIME NULL COMMENT 'Date when distributed',
    notes TEXT COMMENT 'Additional notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (source_investment_id) REFERENCES aureus_investments(id) ON DELETE SET NULL,
    FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL
);

-- =====================================================
-- 6. CREATE SHARE_CERTIFICATES TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS share_certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique certificate number',
    user_id INT NOT NULL,
    investment_id INT NOT NULL,
    shares_amount INT NOT NULL COMMENT 'Number of shares',
    share_value DECIMAL(10,2) NOT NULL COMMENT 'Value per share',
    total_value DECIMAL(15,2) NOT NULL COMMENT 'Total certificate value',
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Certificate issue date',
    expiry_date DATETIME NOT NULL COMMENT 'Certificate expiry date (12 months)',
    is_printed BOOLEAN DEFAULT FALSE COMMENT 'Whether certificate was printed',
    print_count INT DEFAULT 0 COMMENT 'Number of times printed',
    is_void BOOLEAN DEFAULT FALSE COMMENT 'Certificate void status',
    void_reason VARCHAR(255) COMMENT 'Reason for voiding',
    void_date DATETIME NULL COMMENT 'Date when voided',
    pdf_path VARCHAR(500) COMMENT 'Path to generated PDF',
    metadata JSON COMMENT 'Additional certificate metadata',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE
);

-- =====================================================
-- 7. MODIFY AUREUS_INVESTMENTS TABLE
-- =====================================================

-- Remove ROI-related columns
ALTER TABLE aureus_investments 
DROP COLUMN IF EXISTS roi,
DROP COLUMN IF EXISTS roi_delivery_date,
DROP COLUMN IF EXISTS roi_delivered;

-- Update NFT delivery to 12 months and add new columns
ALTER TABLE aureus_investments 
ADD COLUMN commission_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Commission amount for referrer',
ADD COLUMN commission_paid BOOLEAN DEFAULT FALSE COMMENT 'Commission payment status',
ADD COLUMN commission_paid_date DATETIME NULL COMMENT 'Commission payment date',
ADD COLUMN certificate_id INT NULL COMMENT 'Associated share certificate',
ADD COLUMN phase_id INT DEFAULT 1 COMMENT 'Phase when investment was made',
ADD COLUMN revenue_distribution JSON COMMENT 'Revenue distribution breakdown',
MODIFY COLUMN nft_delivery_date DATETIME COMMENT 'NFT delivery date (12 months from creation)';

-- Foreign key constraints will be added later after all tables are created

-- =====================================================
-- 8. MODIFY COMMISSION_RECORDS TABLE
-- =====================================================

-- Remove 3-level commission structure
ALTER TABLE commission_records 
DROP COLUMN IF EXISTS level,
DROP COLUMN IF EXISTS nft_bonus_amount,
DROP COLUMN IF EXISTS nft_bonus_paid;

-- Simplify to single-level commission
ALTER TABLE commission_records 
ADD COLUMN commission_percentage DECIMAL(5,2) DEFAULT 20.00 COMMENT 'Commission percentage (20%)',
ADD COLUMN phase_id INT COMMENT 'Phase when commission was earned',
MODIFY COLUMN commission_type ENUM('direct_sales') DEFAULT 'direct_sales';

-- Foreign key constraint will be added later

-- =====================================================
-- 9. CREATE REVENUE_DISTRIBUTION_LOG TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS revenue_distribution_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    investment_id INT NOT NULL,
    phase_id INT NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL COMMENT 'Total investment amount',
    commission_amount DECIMAL(15,2) NOT NULL COMMENT 'Commission amount (15%)',
    competition_amount DECIMAL(15,2) NOT NULL COMMENT 'Competition amount (15%)',
    npo_amount DECIMAL(15,2) NOT NULL COMMENT 'NPO amount (10%)',
    platform_amount DECIMAL(15,2) NOT NULL COMMENT 'Platform amount (25%)',
    mine_amount DECIMAL(15,2) NOT NULL COMMENT 'Mine amount (35%)',
    distribution_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investment_id) REFERENCES aureus_investments(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE CASCADE
);

-- =====================================================
-- 10. INSERT DEFAULT PHASES (1-20)
-- =====================================================

INSERT INTO phases (phase_number, name, description) VALUES
(1, 'Phase 1 - Foundation', 'Initial presale phase with foundation setup'),
(2, 'Phase 2 - Growth', 'Growth phase with expanded features'),
(3, 'Phase 3 - Expansion', 'Expansion phase with new markets'),
(4, 'Phase 4 - Development', 'Development phase with advanced features'),
(5, 'Phase 5 - Innovation', 'Innovation phase with cutting-edge technology');

-- Insert remaining phases (6-20) with similar structure
INSERT INTO phases (phase_number, name, description)
SELECT
    n.num as phase_number,
    CONCAT('Phase ', n.num, ' - Advanced') as name,
    CONCAT('Advanced phase ', n.num, ' with enhanced capabilities') as description
FROM (
    SELECT 6 as num UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
    SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
    SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
) n;

-- =====================================================
-- 11. UPDATE EXISTING DATA
-- =====================================================

-- Update existing investments to 12-month NFT delivery
UPDATE aureus_investments 
SET nft_delivery_date = DATE_ADD(created_at, INTERVAL 12 MONTH)
WHERE nft_delivery_date IS NULL OR nft_delivery_date = DATE_ADD(created_at, INTERVAL 6 MONTH);

-- Update existing packages to Phase 1
UPDATE investment_packages 
SET phase_id = 1, is_active = TRUE 
WHERE phase_id IS NULL;

-- =====================================================
-- 12. ADD FOREIGN KEY CONSTRAINTS
-- =====================================================

-- Add foreign key constraints for investment_packages
ALTER TABLE investment_packages
ADD CONSTRAINT fk_packages_phase FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL;

-- Add foreign key constraints for aureus_investments
ALTER TABLE aureus_investments
ADD CONSTRAINT fk_investments_certificate FOREIGN KEY (certificate_id) REFERENCES share_certificates(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_investments_phase FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL;

-- Add foreign key constraint for commission_records
ALTER TABLE commission_records
ADD CONSTRAINT fk_commission_phase FOREIGN KEY (phase_id) REFERENCES phases(id) ON DELETE SET NULL;

-- =====================================================
-- 13. CREATE INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX idx_phases_active ON phases(is_active);
CREATE INDEX idx_phases_number ON phases(phase_number);
CREATE INDEX idx_competitions_phase ON competitions(phase_id);
CREATE INDEX idx_competitions_active ON competitions(is_active);
CREATE INDEX idx_competition_participants_user ON competition_participants(user_id);
CREATE INDEX idx_npo_fund_status ON npo_fund(status);
CREATE INDEX idx_certificates_user ON share_certificates(user_id);
CREATE INDEX idx_certificates_void ON share_certificates(is_void);
CREATE INDEX idx_investments_phase ON aureus_investments(phase_id);
CREATE INDEX idx_commission_records_phase ON commission_records(phase_id);

-- Commit the transaction
COMMIT;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
