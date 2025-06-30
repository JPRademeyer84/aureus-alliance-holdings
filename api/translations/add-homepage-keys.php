<?php
// Add homepage translation keys to database
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Homepage translation keys to add
    $homepageKeys = [
        // Hero Section
        [
            'key_name' => 'homepage.hero.title_part1',
            'category' => 'homepage',
            'description' => 'Become an',
            'default_text' => 'Become an'
        ],
        [
            'key_name' => 'homepage.hero.title_part2',
            'category' => 'homepage', 
            'description' => 'Angel Investor',
            'default_text' => 'Angel Investor'
        ],
        [
            'key_name' => 'homepage.hero.title_part3',
            'category' => 'homepage',
            'description' => 'in the Future of Digital',
            'default_text' => 'in the Future of Digital'
        ],
        [
            'key_name' => 'homepage.hero.title_part4',
            'category' => 'homepage',
            'description' => 'Gold',
            'default_text' => 'Gold'
        ],
        [
            'key_name' => 'homepage.hero.subtitle',
            'category' => 'homepage',
            'description' => 'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.',
            'default_text' => 'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.'
        ],
        [
            'key_name' => 'homepage.hero.cta_invest',
            'category' => 'homepage',
            'description' => 'Invertir Ahora',
            'default_text' => 'Invertir Ahora'
        ],
        [
            'key_name' => 'homepage.hero.cta_learn',
            'category' => 'homepage',
            'description' => 'Aprende Más',
            'default_text' => 'Aprende Más'
        ],
        
        // Alternative English versions for the CTAs
        [
            'key_name' => 'homepage.hero.cta_invest_en',
            'category' => 'homepage',
            'description' => 'Invest Now',
            'default_text' => 'Invest Now'
        ],
        [
            'key_name' => 'homepage.hero.cta_learn_en',
            'category' => 'homepage',
            'description' => 'Learn More',
            'default_text' => 'Learn More'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    foreach ($homepageKeys as $keyData) {
        // Check if key already exists
        $checkQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyData['key_name']]);
        
        if ($checkStmt->fetch()) {
            $skippedKeys[] = $keyData['key_name'] . ' (already exists)';
            continue;
        }
        
        // Insert new translation key
        $insertQuery = "INSERT INTO translation_keys (key_name, category, description, created_at)
                       VALUES (?, ?, ?, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            $keyData['key_name'],
            $keyData['category'],
            $keyData['description']
        ]);
        
        $keyId = $db->lastInsertId();
        $addedKeys[] = [
            'id' => $keyId,
            'key_name' => $keyData['key_name'],
            'description' => $keyData['description']
        ];
        
        // Add English translation (default)
        $englishLangQuery = "SELECT id FROM languages WHERE code = 'en'";
        $englishLangStmt = $db->prepare($englishLangQuery);
        $englishLangStmt->execute();
        $englishLang = $englishLangStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($englishLang) {
            $translationQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                               VALUES (?, ?, ?, TRUE, NOW())";
            $translationStmt = $db->prepare($translationQuery);
            $translationStmt->execute([
                $keyId,
                $englishLang['id'],
                $keyData['default_text']
            ]);
        }
    }
    
    // Get total counts
    $totalKeysQuery = "SELECT COUNT(*) as total FROM translation_keys";
    $totalKeysStmt = $db->prepare($totalKeysQuery);
    $totalKeysStmt->execute();
    $totalKeys = $totalKeysStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $homepageKeysQuery = "SELECT COUNT(*) as total FROM translation_keys WHERE category = 'homepage'";
    $homepageKeysStmt = $db->prepare($homepageKeysQuery);
    $homepageKeysStmt->execute();
    $homepageKeysCount = $homepageKeysStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Homepage translation keys processed successfully',
        'results' => [
            'added_keys' => $addedKeys,
            'skipped_keys' => $skippedKeys,
            'added_count' => count($addedKeys),
            'skipped_count' => count($skippedKeys),
            'total_processed' => count($homepageKeys)
        ],
        'statistics' => [
            'total_translation_keys' => (int)$totalKeys,
            'homepage_keys_count' => (int)$homepageKeysCount
        ],
        'next_steps' => [
            'Go to Translation Management',
            'Select each language to translate the new homepage keys',
            'Use AI Translate or manual translation for each key',
            'Update your homepage component to use these translation keys'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add homepage translation keys',
        'error' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
