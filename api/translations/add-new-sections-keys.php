<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // New translation keys for the homepage sections
    $newKeys = [
        // Commission Section
        ['commission.title', 'Networker Commission', 'commission'],
        ['commission.structure', 'Structure', 'commission'],
        ['commission.plan_type', 'Unilevel 3-Level Plan', 'commission'],
        ['commission.description', 'Earn dual rewards in USDT + NFT Pack bonuses through our transparent 3-level commission structure.', 'commission'],
        ['commission.structure_title', 'Commission Structure', 'commission'],
        ['commission.example_title', 'Example Calculation', 'commission'],
        ['commission.benefits_title', 'Key Benefits', 'commission'],
        ['commission.benefit1_title', 'Dual Reward System', 'commission'],
        ['commission.benefit1_desc', 'Earn both USDT commissions and NFT pack bonuses for maximum value.', 'commission'],
        ['commission.benefit2_title', '3-Level Deep', 'commission'],
        ['commission.benefit2_desc', 'Build a sustainable network with rewards from 3 levels of referrals.', 'commission'],
        ['commission.benefit3_title', 'Instant Payouts', 'commission'],
        ['commission.benefit3_desc', 'Receive USDT commissions immediately upon successful referral sales.', 'commission'],
        ['commission.benefit4_title', 'NFT Ownership', 'commission'],
        ['commission.benefit4_desc', 'NFT bonuses provide real ownership in gold mining operations with future dividends.', 'commission'],
        ['commission.pool_title', 'Total Commission Pool', 'commission'],
        ['commission.pool_note', 'Commission pool funded from presale proceeds, ensuring sustainable rewards.', 'commission'],
        
        // ROI Section
        ['roi.title', 'Investor ROI', 'roi'],
        ['roi.model', 'Model', 'roi'],
        ['roi.duration', '180 Days Duration', 'roi'],
        ['roi.funding', 'Funded by Main Sales', 'roi'],
        ['roi.description', 'Choose from 8 investment packages with guaranteed daily ROI over 180 days, plus NFT shares for long-term dividends.', 'roi'],
        ['roi.how_it_works', 'How ROI Works', 'roi'],
        ['roi.step1_title', 'Choose Your Package', 'roi'],
        ['roi.step1_desc', 'Select from 8 investment packages ranging from $25 to $1,000 based on your budget.', 'roi'],
        ['roi.step2_title', 'Daily ROI Payments', 'roi'],
        ['roi.step2_desc', 'Receive daily ROI payments ranging from 1.7% to 5% for 180 consecutive days.', 'roi'],
        ['roi.step3_title', 'Receive NFT Shares', 'roi'],
        ['roi.step3_desc', 'After 180 days, receive your NFT shares representing ownership in gold mining operations.', 'roi'],
        ['roi.guarantee_title', 'ROI Guarantee', 'roi'],
        ['roi.guarantee1', 'ROI funded from future main sale proceeds', 'roi'],
        ['roi.guarantee2', 'Transparent blockchain-based payment system', 'roi'],
        ['roi.guarantee3', 'Backed by real gold mining operations', 'roi'],
        ['roi.benefits_title', 'Investment Benefits', 'roi'],
        ['roi.benefit1_title', 'High Daily Returns', 'roi'],
        ['roi.benefit1_desc', 'Earn up to 5% daily ROI with our premium Aureus package for maximum returns.', 'roi'],
        ['roi.benefit2_title', 'NFT Ownership', 'roi'],
        ['roi.benefit2_desc', 'Receive NFT shares representing real ownership in gold mining operations.', 'roi'],
        ['roi.benefit3_title', 'Future Dividends', 'roi'],
        ['roi.benefit3_desc', 'NFT shares provide ongoing dividends from gold mining profits after ROI period.', 'roi'],
        ['roi.benefit4_title', 'Fixed 180-Day Term', 'roi'],
        ['roi.benefit4_desc', 'Clear timeline with guaranteed daily payments for exactly 180 days.', 'roi'],
        ['roi.timeline_title', 'Investment Timeline', 'roi'],
        
        // Leaderboard Section
        ['leaderboard.title', 'Gold Diggers Club', 'leaderboard'],
        ['leaderboard.bonus_pool', 'BONUS POOL', 'leaderboard'],
        ['leaderboard.description', 'Special leaderboard competition for the Top 10 Direct Sellers in the presale. Minimum $2,500 in direct referrals to qualify.', 'leaderboard'],
        ['leaderboard.how_it_works', 'How It Works', 'leaderboard'],
        ['leaderboard.prize_distribution', 'Prize Distribution', 'leaderboard'],
        ['leaderboard.join_competition', 'Join the Competition', 'leaderboard'],
        ['leaderboard.live_rankings', 'Live Rankings', 'leaderboard'],
        ['leaderboard.live', 'LIVE', 'leaderboard'],
        ['leaderboard.total_participants', 'Total Participants', 'leaderboard'],
        ['leaderboard.leading_volume', 'Leading Volume', 'leaderboard'],
        
        // Common terms
        ['common.level', 'Level', 'common'],
        ['common.daily_roi', 'Daily ROI', 'common'],
        ['common.total_roi', 'Total ROI', 'common'],
        ['common.nft_shares', 'NFT Shares', 'common'],
        ['common.total_return', 'Total Return', 'common'],
        ['common.usdt_commission', 'USDT Commission', 'common'],
        ['common.nft_pack_bonus', 'NFT Pack Bonus', 'common'],
        ['common.earns', 'Earns', 'common'],
        ['common.day', 'Day', 'common'],
        ['common.ongoing', 'Ongoing', 'common'],
        ['common.investment', 'Investment', 'common'],
        ['common.first_roi_payment', 'First ROI Payment', 'common'],
        ['common.final_roi_payment', 'Final ROI Payment', 'common'],
        ['common.nft_dividends_mining', 'NFT Dividends from Mining', 'common']
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    foreach ($newKeys as $keyData) {
        list($keyName, $description, $category) = $keyData;
        
        // Check if key already exists
        $checkQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyName]);
        
        if ($checkStmt->fetch()) {
            $skippedKeys[] = $keyName;
            continue;
        }
        
        // Insert new translation key
        $insertQuery = "INSERT INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$keyName, $description, $category]);
        
        $keyId = $db->lastInsertId();
        
        // Add English translation
        $englishLangQuery = "SELECT id FROM languages WHERE code = 'en'";
        $englishLangStmt = $db->prepare($englishLangQuery);
        $englishLangStmt->execute();
        $englishLang = $englishLangStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($englishLang) {
            $translationQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) VALUES (?, ?, ?, TRUE)";
            $translationStmt = $db->prepare($translationQuery);
            $translationStmt->execute([$keyId, $englishLang['id'], $description]);
        }
        
        $addedKeys[] = $keyName;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Translation keys added successfully',
        'added_keys' => $addedKeys,
        'skipped_keys' => $skippedKeys,
        'total_added' => count($addedKeys),
        'total_skipped' => count($skippedKeys)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding translation keys: ' . $e->getMessage()
    ]);
}
?>
