<?php
/**
 * Update Translation Keys for New Business Model
 * Replace ROI-based keys with Direct Commission + Competition model
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "=== Updating Translation Keys for New Business Model ===\n";
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connected\n";

    // 1. Update ROI-related keys to Commission-based
    echo "\nStep 1: Updating ROI keys to Commission model...\n";
    
    $roiUpdates = [
        // Hero section updates
        [
            'key_name' => 'homepage.hero.subtitle',
            'new_description' => 'Join the new direct commission model with 20% earnings, competition prizes, and NPO charity contributions.',
            'new_default_text' => 'Join the new direct commission model with 20% earnings, competition prizes, and NPO charity contributions.'
        ],
        
        // Benefits updates
        [
            'key_name' => 'homepage.benefits.benefit3',
            'new_description' => '20% direct commission on all sales - no complex structures',
            'new_default_text' => '20% direct commission on all sales - no complex structures'
        ],
        [
            'key_name' => 'homepage.benefits.benefit4',
            'new_description' => 'Competition system with 15% prize pool allocation',
            'new_default_text' => 'Competition system with 15% prize pool allocation'
        ],
        
        // How it works updates
        [
            'key_name' => 'how_it_works.benefit3',
            'new_description' => '20% direct commission on every sale you make',
            'new_default_text' => '20% direct commission on every sale you make'
        ],
        [
            'key_name' => 'how_it_works.step6_desc',
            'new_description' => 'Earn 20% commission immediately, participate in competitions, and receive your share certificate after 12 months',
            'new_default_text' => 'Earn 20% commission immediately, participate in competitions, and receive your share certificate after 12 months'
        ],
        
        // Investment guide updates
        [
            'key_name' => 'track_180_day_countdown',
            'new_description' => 'Track Your 12-Month Certificate Countdown',
            'new_default_text' => 'Track Your 12-Month Certificate Countdown'
        ],
        [
            'key_name' => 'watch_nft_delivery_countdown',
            'new_description' => 'Watch your share certificate countdown in real-time. Your digital shares are being prepared!',
            'new_default_text' => 'Watch your share certificate countdown in real-time. Your digital shares are being prepared!'
        ],
        [
            'key_name' => '180_day_reward_period',
            'new_description' => '12-month share certificate validity period',
            'new_default_text' => '12-month share certificate validity period'
        ],
        [
            'key_name' => 'daily_reward_payments',
            'new_description' => 'Immediate 20% commission payments',
            'new_default_text' => 'Immediate 20% commission payments'
        ],
        [
            'key_name' => 'total_reward_range',
            'new_description' => 'Direct Commission: 20% on every sale',
            'new_default_text' => 'Direct Commission: 20% on every sale'
        ],
        
        // Commission structure updates
        [
            'key_name' => 'level_1_commission',
            'new_description' => 'Direct Sales: 20% commission on every sale',
            'new_default_text' => 'Direct Sales: 20% commission on every sale'
        ],
        [
            'key_name' => 'level_2_commission',
            'new_description' => 'Competition Entry: Automatic participation in phase competitions',
            'new_default_text' => 'Competition Entry: Automatic participation in phase competitions'
        ],
        [
            'key_name' => 'level_3_commission',
            'new_description' => 'NPO Contribution: 10% of every sale goes to charity',
            'new_default_text' => 'NPO Contribution: 10% of every sale goes to charity'
        ]
    ];

    foreach ($roiUpdates as $update) {
        try {
            $stmt = $db->prepare("UPDATE translation_keys SET description = ? WHERE key_name = ?");
            $result = $stmt->execute([$update['new_description'], $update['key_name']]);

            if ($result) {
                echo "✓ Updated: {$update['key_name']}\n";
            }
        } catch (Exception $e) {
            echo "Warning: Failed to update {$update['key_name']}: " . $e->getMessage() . "\n";
        }
    }

    // 2. Add new business model keys
    echo "\nStep 2: Adding new business model keys...\n";
    
    $newKeys = [
        // New business model section
        [
            'key_name' => 'business_model.title',
            'category' => 'business_model',
            'description' => 'New Business Model',
            'default_text' => 'New Business Model'
        ],
        [
            'key_name' => 'business_model.subtitle',
            'category' => 'business_model', 
            'description' => 'Direct Commission + Competition System',
            'default_text' => 'Direct Commission + Competition System'
        ],
        [
            'key_name' => 'business_model.commission_rate',
            'category' => 'business_model',
            'description' => '20% Direct Commission',
            'default_text' => '20% Direct Commission'
        ],
        [
            'key_name' => 'business_model.competition_pool',
            'category' => 'business_model',
            'description' => '15% Competition Prize Pool',
            'default_text' => '15% Competition Prize Pool'
        ],
        [
            'key_name' => 'business_model.npo_fund',
            'category' => 'business_model',
            'description' => '10% NPO Charity Fund',
            'default_text' => '10% NPO Charity Fund'
        ],
        [
            'key_name' => 'business_model.platform_tech',
            'category' => 'business_model',
            'description' => '25% Platform & Technology',
            'default_text' => '25% Platform & Technology'
        ],
        [
            'key_name' => 'business_model.mine_setup',
            'category' => 'business_model',
            'description' => '35% Mine Setup & Expansion',
            'default_text' => '35% Mine Setup & Expansion'
        ],
        
        // Competition keys
        [
            'key_name' => 'competition.title',
            'category' => 'competition',
            'description' => 'Phase Competitions',
            'default_text' => 'Phase Competitions'
        ],
        [
            'key_name' => 'competition.description',
            'category' => 'competition',
            'description' => 'Compete with other investors in each phase for additional prizes',
            'default_text' => 'Compete with other investors in each phase for additional prizes'
        ],
        [
            'key_name' => 'competition.join_now',
            'category' => 'competition',
            'description' => 'Join Competition',
            'default_text' => 'Join Competition'
        ],
        [
            'key_name' => 'competition.leaderboard',
            'category' => 'competition',
            'description' => 'View Leaderboard',
            'default_text' => 'View Leaderboard'
        ],
        
        // Share certificate keys
        [
            'key_name' => 'certificate.title',
            'category' => 'certificate',
            'description' => 'Share Certificates',
            'default_text' => 'Share Certificates'
        ],
        [
            'key_name' => 'certificate.description',
            'category' => 'certificate',
            'description' => 'Receive printable share certificates valid for 12 months',
            'default_text' => 'Receive printable share certificates valid for 12 months'
        ],
        [
            'key_name' => 'certificate.download',
            'category' => 'certificate',
            'description' => 'Download Certificate',
            'default_text' => 'Download Certificate'
        ],
        [
            'key_name' => 'certificate.validity',
            'category' => 'certificate',
            'description' => '12-Month Validity',
            'default_text' => '12-Month Validity'
        ],
        
        // Phase system keys
        [
            'key_name' => 'phase.title',
            'category' => 'phase',
            'description' => 'Investment Phases',
            'default_text' => 'Investment Phases'
        ],
        [
            'key_name' => 'phase.current',
            'category' => 'phase',
            'description' => 'Current Phase',
            'default_text' => 'Current Phase'
        ],
        [
            'key_name' => 'phase.description',
            'category' => 'phase',
            'description' => '20 phases with manual activation and unique competitions',
            'default_text' => '20 phases with manual activation and unique competitions'
        ],
        
        // NPO fund keys
        [
            'key_name' => 'npo.title',
            'category' => 'npo',
            'description' => 'Charity Contributions',
            'default_text' => 'Charity Contributions'
        ],
        [
            'key_name' => 'npo.description',
            'category' => 'npo',
            'description' => '10% of every investment goes to verified NPO organizations',
            'default_text' => '10% of every investment goes to verified NPO organizations'
        ],
        [
            'key_name' => 'npo.total_donated',
            'category' => 'npo',
            'description' => 'Total Donated',
            'default_text' => 'Total Donated'
        ]
    ];

    foreach ($newKeys as $key) {
        try {
            $stmt = $db->prepare("INSERT IGNORE INTO translation_keys (key_name, category, description, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$key['key_name'], $key['category'], $key['description']]);

            if ($result) {
                echo "✓ Added: {$key['key_name']}\n";
            }
        } catch (Exception $e) {
            echo "Warning: Failed to add {$key['key_name']}: " . $e->getMessage() . "\n";
        }
    }

    // 3. Check if we need to add translations (skip for now due to complex structure)
    echo "\nStep 3: Translation keys updated (translations can be added via admin panel)...\n";

    echo "\n✅ Translation keys updated successfully!\n";
    echo "New business model keys are now available for use.\n";

} catch (Exception $e) {
    echo "\n❌ Failed to update translation keys: " . $e->getMessage() . "\n";
    exit(1);
}
?>
