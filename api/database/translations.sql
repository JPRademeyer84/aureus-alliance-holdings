-- Translation System Database Tables
-- Run this SQL to create the translation system tables

-- Languages table - stores all supported languages
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100) NOT NULL,
    flag_emoji VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Translation keys table - stores all translatable text keys
CREATE TABLE IF NOT EXISTS translation_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Translations table - stores actual translations for each key in each language
CREATE TABLE IF NOT EXISTS translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_id INT NOT NULL,
    language_id INT NOT NULL,
    translation_text TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_translation (key_id, language_id)
);

-- Insert default languages
INSERT INTO languages (code, name, native_name, flag_emoji, is_active, is_default, sort_order) VALUES
('en', 'English', 'English', '🇺🇸', TRUE, TRUE, 1),
('es', 'Spanish', 'Español', '🇪🇸', TRUE, FALSE, 2),
('fr', 'French', 'Français', '🇫🇷', TRUE, FALSE, 3),
('de', 'German', 'Deutsch', '🇩🇪', TRUE, FALSE, 4),
('pt', 'Portuguese', 'Português', '🇵🇹', TRUE, FALSE, 5),
('it', 'Italian', 'Italiano', '🇮🇹', TRUE, FALSE, 6),
('ru', 'Russian', 'Русский', '🇷🇺', TRUE, FALSE, 7),
('zh', 'Chinese', '中文', '🇨🇳', TRUE, FALSE, 8),
('ja', 'Japanese', '日本語', '🇯🇵', TRUE, FALSE, 9),
('ar', 'Arabic', 'العربية', '🇸🇦', TRUE, FALSE, 10),
('uk', 'Ukrainian', 'Українська', '🇺🇦', TRUE, FALSE, 11),
('hi', 'Hindi', 'हिन्दी', '🇮🇳', TRUE, FALSE, 12),
('ur', 'Urdu', 'اردو', '🇵🇰', TRUE, FALSE, 13),
('bn', 'Bengali', 'বাংলা', '🇧🇩', TRUE, FALSE, 14),
('ko', 'Korean', '한국어', '🇰🇷', TRUE, FALSE, 15),
('ms', 'Malay', 'Bahasa Malaysia', '🇲🇾', TRUE, FALSE, 16);

-- Insert common translation keys with categories
INSERT INTO translation_keys (key_name, description, category) VALUES
-- Navigation
('nav.investment', 'Investment menu item', 'navigation'),
('nav.affiliate', 'Affiliate menu item', 'navigation'),
('nav.benefits', 'Benefits menu item', 'navigation'),
('nav.about', 'About menu item', 'navigation'),
('nav.contact', 'Contact menu item', 'navigation'),
('nav.sign_in', 'Sign In button', 'navigation'),

-- Hero Section
('hero.title', 'Main hero title', 'hero'),
('hero.subtitle', 'Hero subtitle about digital gold', 'hero'),
('hero.description', 'Hero description paragraph', 'hero'),
('hero.invest_now', 'Invest Now button', 'hero'),
('hero.learn_more', 'Learn More button', 'hero'),

-- Statistics
('stats.yield_investment', 'Yield on Investment label', 'statistics'),
('stats.annual_share', 'Annual per Share label', 'statistics'),
('stats.affiliate_commission', 'Affiliate Commission label', 'statistics'),
('stats.nft_presale', 'NFT Presale Launch label', 'statistics'),

-- Benefits Section
('benefits.title', 'Benefits section title', 'benefits'),
('benefits.description', 'Benefits section description', 'benefits'),
('benefits.limited_offer', 'Limited Offer benefit title', 'benefits'),
('benefits.nft_access', 'NFT Early Access benefit title', 'benefits'),
('benefits.gold_dividends', 'Gold Mine Dividends benefit title', 'benefits'),
('benefits.affiliate_program', 'Affiliate Program benefit title', 'benefits'),
('benefits.gaming_integration', 'Gaming Integration benefit title', 'benefits'),

-- How It Works
('how_it_works.title', 'How It Works section title', 'how_it_works'),
('how_it_works.description', 'How It Works description', 'how_it_works'),
('how_it_works.create_account', 'Create Your Account step', 'how_it_works'),
('how_it_works.choose_package', 'Choose Your NFT Package step', 'how_it_works'),
('how_it_works.secure_payment', 'Secure USDT Payment step', 'how_it_works'),
('how_it_works.earn_commissions', 'Earn Commissions step', 'how_it_works'),
('how_it_works.roi_period', '180-Day ROI Period step', 'how_it_works'),
('how_it_works.receive_returns', 'Receive Your Returns step', 'how_it_works'),

-- Footer
('footer.company_description', 'Company description in footer', 'footer'),
('footer.quick_links', 'Quick Links heading', 'footer'),
('footer.contact_us', 'Contact Us heading', 'footer'),
('footer.investment_inquiries', 'For investment inquiries text', 'footer'),
('footer.rights_reserved', 'All rights reserved text', 'footer'),

-- Common Actions
('common.get', 'Get action word', 'common'),
('common.start', 'Start action word', 'common'),
('common.earn', 'Earn action word', 'common'),
('common.share', 'Share action word', 'common'),
('common.receive', 'Receive action word', 'common'),

-- Common Words
('common.and', 'And conjunction', 'common'),
('common.with', 'With preposition', 'common'),
('common.for', 'For preposition', 'common'),
('common.your', 'Your possessive', 'common'),
('common.exclusive', 'Exclusive adjective', 'common'),
('common.opportunity', 'Opportunity noun', 'common'),
('common.digital', 'Digital adjective', 'common'),
('common.gold', 'Gold noun', 'common'),
('common.mining', 'Mining noun', 'common');

-- Insert English translations (base language)
INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
SELECT tk.id, l.id, 
CASE tk.key_name
    -- Navigation
    WHEN 'nav.investment' THEN 'Investment'
    WHEN 'nav.affiliate' THEN 'Affiliate'
    WHEN 'nav.benefits' THEN 'Benefits'
    WHEN 'nav.about' THEN 'About'
    WHEN 'nav.contact' THEN 'Contact'
    WHEN 'nav.sign_in' THEN 'Sign In'
    
    -- Hero Section
    WHEN 'hero.title' THEN 'Become an Angel Investor'
    WHEN 'hero.subtitle' THEN 'in the Future of Digital Gold'
    WHEN 'hero.description' THEN 'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.'
    WHEN 'hero.invest_now' THEN 'Invest Now'
    WHEN 'hero.learn_more' THEN 'Learn More'
    
    -- Statistics
    WHEN 'stats.yield_investment' THEN 'Yield on Investment'
    WHEN 'stats.annual_share' THEN 'Annual per Share'
    WHEN 'stats.affiliate_commission' THEN 'Affiliate Commission'
    WHEN 'stats.nft_presale' THEN 'NFT Presale Launch'
    
    -- Benefits
    WHEN 'benefits.title' THEN 'Exclusive Angel Investor Benefits'
    WHEN 'benefits.description' THEN 'As an early supporter of the Aureus Angel Alliance, you''ll receive unparalleled advantages that won''t be available after our public launch.'
    WHEN 'benefits.limited_offer' THEN 'Limited Offer'
    WHEN 'benefits.nft_access' THEN 'NFT Early Access'
    WHEN 'benefits.gold_dividends' THEN 'Gold Mine Dividends'
    WHEN 'benefits.affiliate_program' THEN 'Affiliate Program'
    WHEN 'benefits.gaming_integration' THEN 'Gaming Integration'
    
    -- How It Works
    WHEN 'how_it_works.title' THEN 'How Angel Investing Works'
    WHEN 'how_it_works.description' THEN 'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.'
    WHEN 'how_it_works.create_account' THEN 'Create Your Account'
    WHEN 'how_it_works.choose_package' THEN 'Choose Your NFT Package'
    WHEN 'how_it_works.secure_payment' THEN 'Secure USDT Payment'
    WHEN 'how_it_works.earn_commissions' THEN 'Earn Commissions'
    WHEN 'how_it_works.roi_period' THEN '180-Day ROI Period'
    WHEN 'how_it_works.receive_returns' THEN 'Receive Your Returns'
    
    -- Footer
    WHEN 'footer.company_description' THEN 'The future of gold mining meets blockchain innovation, NFT collectibles, and immersive gaming.'
    WHEN 'footer.quick_links' THEN 'Quick Links'
    WHEN 'footer.contact_us' THEN 'Contact Us'
    WHEN 'footer.investment_inquiries' THEN 'For investment inquiries:'
    WHEN 'footer.rights_reserved' THEN 'All rights reserved.'
    
    -- Common Actions
    WHEN 'common.get' THEN 'get'
    WHEN 'common.start' THEN 'start'
    WHEN 'common.earn' THEN 'earn'
    WHEN 'common.share' THEN 'share'
    WHEN 'common.receive' THEN 'receive'
    
    -- Common Words
    WHEN 'common.and' THEN 'and'
    WHEN 'common.with' THEN 'with'
    WHEN 'common.for' THEN 'for'
    WHEN 'common.your' THEN 'your'
    WHEN 'common.exclusive' THEN 'exclusive'
    WHEN 'common.opportunity' THEN 'opportunity'
    WHEN 'common.digital' THEN 'digital'
    WHEN 'common.gold' THEN 'gold'
    WHEN 'common.mining' THEN 'mining'
END,
TRUE
FROM translation_keys tk
CROSS JOIN languages l
WHERE l.code = 'en';

-- Create indexes for better performance
CREATE INDEX idx_translations_key_lang ON translations(key_id, language_id);
CREATE INDEX idx_translation_keys_category ON translation_keys(category);
CREATE INDEX idx_languages_active ON languages(is_active);
CREATE INDEX idx_languages_code ON languages(code);
