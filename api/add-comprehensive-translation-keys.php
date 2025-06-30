<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Comprehensive translation keys for ALL user sections
    $translationKeys = [
        // Hero Section (Extended)
        ['hero.description', 'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.', 'hero'],
        ['hero.start_investing', 'Start Investing Now', 'hero'],
        ['hero.view_packages', 'View Investment Packages', 'hero'],
        
        // Call to Action
        ['cta.become_investor_today', 'Become an Angel Investor Today', 'cta'],
        ['cta.limited_investment', 'Only $100,000 of pre-seed investment available. Secure your position before the opportunity closes.', 'cta'],
        ['cta.yield_deadline', '10x Yield by June 2025. Investment closes when we reach our $100,000 cap or when NFT presale begins in June.', 'cta'],
        
        // Benefits Section (Complete)
        ['benefits.limited_offer_desc', 'Only $100,000 available for pre-seed investment, ensuring exclusive access before public launch.', 'benefits'],
        ['benefits.nft_access_desc', 'Get premium NFTs at the lowest possible rates before the presale begins in June.', 'benefits'],
        ['benefits.gold_dividends_desc', 'Share in profits from Aureus Alliance\'s gold mining operations at $89 per share annually.', 'benefits'],
        ['benefits.affiliate_program_desc', 'Earn 20% commission on a 2-level affiliate structure when you refer other angel investors.', 'benefits'],
        ['benefits.gaming_integration_desc', 'Exclusive access to the upcoming MMO gaming ecosystem with unique in-game advantages.', 'benefits'],
        ['benefits.why_choose_title', 'Why Choose Aureus Alliance?', 'benefits'],
        ['benefits.early_supporter_desc', 'As an early supporter of the Aureus Angel Alliance, you\'ll receive unparalleled advantages that won\'t be available after our public launch.', 'benefits'],
        
        // How It Works Section (Complete)
        ['how_it_works.step1_desc', 'Sign up in under 2 minutes with just your email. No complex verification required to get started.', 'how_it_works'],
        ['how_it_works.step2_desc', 'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.', 'how_it_works'],
        ['how_it_works.step3_desc', 'Connect your wallet and pay with USDT on Polygon network. Low fees, fast transactions, complete transparency.', 'how_it_works'],
        ['how_it_works.step4_desc', 'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus 8% USDT + 8% NFT on Level 2.', 'how_it_works'],
        ['how_it_works.step5_desc', 'Watch your investment grow with daily ROI from 1.7% to 5% for 180 days, depending on your package.', 'how_it_works'],
        ['how_it_works.step6_desc', 'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.', 'how_it_works'],
        ['how_it_works.benefit1', 'Start with just $25 (Shovel package) - no minimum barriers', 'how_it_works'],
        ['how_it_works.benefit2', '8 mining packages from $25 to $1,000 - perfect for any budget', 'how_it_works'],
        ['how_it_works.benefit3', 'Daily ROI from 1.7% to 5% for 180 days guaranteed', 'how_it_works'],
        ['how_it_works.benefit4', '12% USDT + 12% NFT bonuses on Level 1 referrals', 'how_it_works'],
        ['how_it_works.benefit5', 'Polygon blockchain transparency with USDT payments', 'how_it_works'],
        ['how_it_works.benefit6', 'Backed by real Aureus Alliance gold mining operations', 'how_it_works'],
        
        // Authentication
        ['auth.welcome_back', 'Welcome Back', 'auth'],
        ['auth.sign_in_account', 'Sign in to your account', 'auth'],
        ['auth.email', 'Email', 'auth'],
        ['auth.password', 'Password', 'auth'],
        ['auth.email_placeholder', 'your@email.com', 'auth'],
        ['auth.password_placeholder', 'Enter your password', 'auth'],
        ['auth.signing_in', 'Signing in...', 'auth'],
        ['auth.no_account', 'Don\'t have an account?', 'auth'],
        ['auth.sign_up', 'Sign up', 'auth'],
        ['auth.create_account', 'Create Account', 'auth'],
        ['auth.join_alliance', 'Join the Aureus Angel Alliance', 'auth'],
        ['auth.username', 'Username', 'auth'],
        ['auth.confirm_password', 'Confirm Password', 'auth'],
        ['auth.username_placeholder', 'Choose a username', 'auth'],
        ['auth.confirm_password_placeholder', 'Confirm your password', 'auth'],
        ['auth.creating_account', 'Creating account...', 'auth'],
        ['auth.have_account', 'Already have an account?', 'auth'],
        
        // Dashboard
        ['dashboard.welcome_back', 'Welcome back', 'dashboard'],
        ['dashboard.ready_grow_wealth', 'Ready to grow your wealth?', 'dashboard'],
        ['dashboard.last_login', 'Last login', 'dashboard'],
        ['dashboard.investor', 'INVESTOR', 'dashboard'],
        ['dashboard.commission_earnings', 'Commission Earnings', 'dashboard'],
        ['dashboard.available_balance', 'Available Balance', 'dashboard'],
        ['dashboard.total_investments', 'Total Investments', 'dashboard'],
        ['dashboard.portfolio_value', 'Portfolio Value', 'dashboard'],
        ['dashboard.aureus_shares', 'Aureus Shares', 'dashboard'],
        ['dashboard.activity', 'Activity', 'dashboard'],
        ['dashboard.nft_packs_earned', 'NFT packs earned', 'dashboard'],
        ['dashboard.nft_available', 'NFT available', 'dashboard'],
        ['dashboard.active', 'active', 'dashboard'],
        ['dashboard.completed', 'completed', 'dashboard'],
        ['dashboard.expected_roi', 'expected ROI', 'dashboard'],
        ['dashboard.annual_dividends', 'annual dividends', 'dashboard'],
        ['dashboard.pending', 'pending', 'dashboard'],
        ['dashboard.loading', 'Loading dashboard...', 'dashboard'],
        
        // Quick Actions
        ['actions.commission_wallet', 'Commission Wallet', 'actions'],
        ['actions.commission_wallet_desc', 'Manage your referral earnings and withdrawals', 'actions'],
        ['actions.affiliate_program', 'Affiliate Program', 'actions'],
        ['actions.affiliate_program_desc', 'Grow your network and earn commissions', 'actions'],
        ['actions.browse_packages', 'Browse Packages', 'actions'],
        ['actions.browse_packages_desc', 'Explore available investment opportunities', 'actions'],
        ['actions.investment_history', 'Investment History', 'actions'],
        ['actions.investment_history_desc', 'View your past and current investments', 'actions'],
        ['actions.delivery_countdown', 'Delivery Countdown', 'actions'],
        ['actions.delivery_countdown_desc', 'Track NFT & ROI delivery (180 days)', 'actions'],
        ['actions.portfolio_overview', 'Portfolio Overview', 'actions'],
        ['actions.portfolio_overview_desc', 'Check your portfolio performance', 'actions'],
        ['actions.gold_diggers_club', 'Gold Diggers Club', 'actions'],
        ['actions.gold_diggers_club_desc', 'Compete for $250K bonus pool', 'actions'],
        ['actions.contact_support', 'Contact Support', 'actions'],
        ['actions.contact_support_desc', 'Get help from our support team', 'actions'],
        ['actions.account_settings', 'Account Settings', 'actions'],
        ['actions.account_settings_desc', 'Manage your account preferences', 'actions'],
        ['actions.wallet_connection', 'Wallet Connection', 'actions'],
        ['actions.wallet_connection_desc', 'Connect and manage your wallets', 'actions'],
        
        // Investment Packages
        ['packages.available_packages', 'Available Investment Packages', 'packages'],
        ['packages.view_all', 'View All', 'packages'],
        ['packages.invest_now', 'Invest Now', 'packages'],
        ['packages.shares', 'Aureus Shares', 'packages'],
        ['packages.expected_roi', 'Expected ROI', 'packages'],
        ['packages.annual_dividends', 'Annual Dividends', 'packages'],
        ['packages.no_packages', 'No investment packages available at the moment.', 'packages'],
        
        // Quick Actions Section
        ['quick_actions.title', 'Quick Actions', 'quick_actions'],
        ['quick_actions.new', 'NEW', 'quick_actions'],
        
        // Common UI Elements
        ['common.loading', 'Loading...', 'common'],
        ['common.error', 'Error', 'common'],
        ['common.success', 'Success', 'common'],
        ['common.cancel', 'Cancel', 'common'],
        ['common.save', 'Save', 'common'],
        ['common.edit', 'Edit', 'common'],
        ['common.delete', 'Delete', 'common'],
        ['common.view', 'View', 'common'],
        ['common.close', 'Close', 'common'],
        ['common.submit', 'Submit', 'common'],
        ['common.confirm', 'Confirm', 'common'],
        ['common.back', 'Back', 'common'],
        ['common.next', 'Next', 'common'],
        ['common.previous', 'Previous', 'common'],
        ['common.search', 'Search', 'common'],
        ['common.filter', 'Filter', 'common'],
        ['common.sort', 'Sort', 'common'],
        ['common.refresh', 'Refresh', 'common'],
        ['common.download', 'Download', 'common'],
        ['common.upload', 'Upload', 'common'],
        ['common.copy', 'Copy', 'common'],
        ['common.share', 'Share', 'common'],
        ['common.print', 'Print', 'common'],
        ['common.help', 'Help', 'common'],
        ['common.settings', 'Settings', 'common'],
        ['common.profile', 'Profile', 'common'],
        ['common.logout', 'Logout', 'common'],
        ['common.login', 'Login', 'common'],
        ['common.register', 'Register', 'common'],
        
        // Status and States
        ['status.active', 'Active', 'status'],
        ['status.inactive', 'Inactive', 'status'],
        ['status.pending', 'Pending', 'status'],
        ['status.completed', 'Completed', 'status'],
        ['status.cancelled', 'Cancelled', 'status'],
        ['status.approved', 'Approved', 'status'],
        ['status.rejected', 'Rejected', 'status'],
        ['status.processing', 'Processing', 'status'],
        ['status.failed', 'Failed', 'status'],
        ['status.expired', 'Expired', 'status'],
        
        // Time and Dates
        ['time.today', 'Today', 'time'],
        ['time.yesterday', 'Yesterday', 'time'],
        ['time.tomorrow', 'Tomorrow', 'time'],
        ['time.this_week', 'This Week', 'time'],
        ['time.last_week', 'Last Week', 'time'],
        ['time.this_month', 'This Month', 'time'],
        ['time.last_month', 'Last Month', 'time'],
        ['time.this_year', 'This Year', 'time'],
        ['time.last_year', 'Last Year', 'time'],
        ['time.days', 'days', 'time'],
        ['time.hours', 'hours', 'time'],
        ['time.minutes', 'minutes', 'time'],
        ['time.seconds', 'seconds', 'time'],
        
        // Financial Terms
        ['finance.balance', 'Balance', 'finance'],
        ['finance.amount', 'Amount', 'finance'],
        ['finance.total', 'Total', 'finance'],
        ['finance.subtotal', 'Subtotal', 'finance'],
        ['finance.fee', 'Fee', 'finance'],
        ['finance.commission', 'Commission', 'finance'],
        ['finance.dividend', 'Dividend', 'finance'],
        ['finance.profit', 'Profit', 'finance'],
        ['finance.loss', 'Loss', 'finance'],
        ['finance.investment', 'Investment', 'finance'],
        ['finance.withdrawal', 'Withdrawal', 'finance'],
        ['finance.deposit', 'Deposit', 'finance'],
        ['finance.transfer', 'Transfer', 'finance'],
        ['finance.transaction', 'Transaction', 'finance'],
        ['finance.payment', 'Payment', 'finance'],
        ['finance.refund', 'Refund', 'finance'],
        ['finance.currency', 'Currency', 'finance'],
        ['finance.exchange_rate', 'Exchange Rate', 'finance'],
        ['finance.wallet_address', 'Wallet Address', 'finance'],
        ['finance.transaction_hash', 'Transaction Hash', 'finance'],

        // KYC and Verification
        ['kyc.verification', 'Verification', 'kyc'],
        ['kyc.identity_verification', 'Identity Verification', 'kyc'],
        ['kyc.upload_document', 'Upload Document', 'kyc'],
        ['kyc.document_type', 'Document Type', 'kyc'],
        ['kyc.drivers_license', 'Driver\'s License', 'kyc'],
        ['kyc.national_id', 'National ID', 'kyc'],
        ['kyc.passport', 'Passport', 'kyc'],
        ['kyc.document_uploaded', 'Document uploaded successfully', 'kyc'],
        ['kyc.pending_review', 'Pending Review', 'kyc'],
        ['kyc.verified', 'Verified', 'kyc'],
        ['kyc.rejected', 'Rejected', 'kyc'],

        // Wallet and Blockchain
        ['wallet.connect_wallet', 'Connect Wallet', 'wallet'],
        ['wallet.disconnect_wallet', 'Disconnect Wallet', 'wallet'],
        ['wallet.wallet_connected', 'Wallet Connected', 'wallet'],
        ['wallet.wallet_disconnected', 'Wallet Disconnected', 'wallet'],
        ['wallet.select_wallet', 'Select Wallet', 'wallet'],
        ['wallet.safepal_wallet', 'SafePal Wallet', 'wallet'],
        ['wallet.metamask', 'MetaMask', 'wallet'],
        ['wallet.wallet_balance', 'Wallet Balance', 'wallet'],
        ['wallet.insufficient_balance', 'Insufficient Balance', 'wallet'],
        ['wallet.transaction_pending', 'Transaction Pending', 'wallet'],
        ['wallet.transaction_confirmed', 'Transaction Confirmed', 'wallet'],
        ['wallet.transaction_failed', 'Transaction Failed', 'wallet'],

        // Affiliate and Referral
        ['affiliate.referral_link', 'Referral Link', 'affiliate'],
        ['affiliate.copy_link', 'Copy Link', 'affiliate'],
        ['affiliate.share_link', 'Share Link', 'affiliate'],
        ['affiliate.referrals', 'Referrals', 'affiliate'],
        ['affiliate.level_1', 'Level 1', 'affiliate'],
        ['affiliate.level_2', 'Level 2', 'affiliate'],
        ['affiliate.commission_rate', 'Commission Rate', 'affiliate'],
        ['affiliate.total_referrals', 'Total Referrals', 'affiliate'],
        ['affiliate.active_referrals', 'Active Referrals', 'affiliate'],
        ['affiliate.commission_earned', 'Commission Earned', 'affiliate'],
        ['affiliate.downline', 'Downline', 'affiliate'],
        ['affiliate.upline', 'Upline', 'affiliate'],

        // Support and Contact
        ['support.live_chat', 'Live Chat', 'support'],
        ['support.contact_form', 'Contact Form', 'support'],
        ['support.send_message', 'Send Message', 'support'],
        ['support.message_sent', 'Message Sent', 'support'],
        ['support.support_ticket', 'Support Ticket', 'support'],
        ['support.ticket_number', 'Ticket Number', 'support'],
        ['support.priority', 'Priority', 'support'],
        ['support.high', 'High', 'support'],
        ['support.medium', 'Medium', 'support'],
        ['support.low', 'Low', 'support'],
        ['support.subject', 'Subject', 'support'],
        ['support.message', 'Message', 'support'],
        ['support.attachment', 'Attachment', 'support'],

        // Notifications and Alerts
        ['notification.success', 'Success!', 'notification'],
        ['notification.error', 'Error!', 'notification'],
        ['notification.warning', 'Warning!', 'notification'],
        ['notification.info', 'Information', 'notification'],
        ['notification.new_message', 'New Message', 'notification'],
        ['notification.investment_confirmed', 'Investment Confirmed', 'notification'],
        ['notification.commission_received', 'Commission Received', 'notification'],
        ['notification.withdrawal_processed', 'Withdrawal Processed', 'notification'],

        // Terms and Legal
        ['terms.terms_conditions', 'Terms and Conditions', 'terms'],
        ['terms.privacy_policy', 'Privacy Policy', 'terms'],
        ['terms.accept_terms', 'I accept the terms and conditions', 'terms'],
        ['terms.agree_privacy', 'I agree to the privacy policy', 'terms'],
        ['terms.legal_disclaimer', 'Legal Disclaimer', 'terms'],
        ['terms.risk_warning', 'Risk Warning', 'terms'],
        ['terms.investment_risk', 'Investment opportunities involve risk. Please read our terms carefully.', 'terms'],

        // Countdown and Delivery
        ['countdown.delivery_countdown', 'Delivery Countdown', 'countdown'],
        ['countdown.days_remaining', 'Days Remaining', 'countdown'],
        ['countdown.nft_delivery', 'NFT Delivery', 'countdown'],
        ['countdown.roi_completion', 'ROI Completion', 'countdown'],
        ['countdown.countdown_expired', 'Countdown Expired', 'countdown'],

        // Leaderboard and Competition
        ['leaderboard.gold_diggers_club', 'Gold Diggers Club', 'leaderboard'],
        ['leaderboard.bonus_pool', 'Bonus Pool', 'leaderboard'],
        ['leaderboard.rank', 'Rank', 'leaderboard'],
        ['leaderboard.points', 'Points', 'leaderboard'],
        ['leaderboard.prize', 'Prize', 'leaderboard'],
        ['leaderboard.competition', 'Competition', 'leaderboard'],
        ['leaderboard.winner', 'Winner', 'leaderboard'],

        // Social Media and Marketing
        ['social.share_facebook', 'Share on Facebook', 'social'],
        ['social.share_twitter', 'Share on Twitter', 'social'],
        ['social.share_linkedin', 'Share on LinkedIn', 'social'],
        ['social.share_telegram', 'Share on Telegram', 'social'],
        ['social.share_whatsapp', 'Share on WhatsApp', 'social'],
        ['social.follow_us', 'Follow Us', 'social'],
        ['social.social_media', 'Social Media', 'social']
    ];
    
    $results = [];
    
    // Insert translation keys
    $sql = "INSERT IGNORE INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    foreach ($translationKeys as $key) {
        $stmt->execute([$key[0], $key[1], $key[2]]);
        $results[] = "Added key: " . $key[0] . " (" . $key[2] . ")";
    }
    
    // Get English language ID
    $langQuery = "SELECT id FROM languages WHERE code = 'en'";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $englishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($englishLang) {
        $englishId = $englishLang['id'];
        
        // Insert English translations
        $sql = "INSERT IGNORE INTO translations (key_id, language_id, translation_text, is_approved) 
                SELECT tk.id, ?, ?, TRUE 
                FROM translation_keys tk 
                WHERE tk.key_name = ?";
        $stmt = $db->prepare($sql);
        
        foreach ($translationKeys as $key) {
            $stmt->execute([$englishId, $key[1], $key[0]]);
        }
        
        $results[] = "Added " . count($translationKeys) . " English translations";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Comprehensive translation keys added successfully!',
        'count' => count($translationKeys),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to add translation keys'
    ]);
}
?>
