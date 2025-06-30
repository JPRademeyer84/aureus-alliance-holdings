-- Step 2: Create Translation Keys Table
CREATE TABLE IF NOT EXISTS translation_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

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
