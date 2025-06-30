-- Step 4: Insert English translations (base language)
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
