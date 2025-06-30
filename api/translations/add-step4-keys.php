<?php
// Add Step 4 "Earn Commissions" translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Earn Commissions' => 'Gana Comisiones',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Comparte tu enlace de referido y gana 12% USDT + 12% bonos NFT en Nivel 1, más recompensas multinivel.'
        ],
        'French' => [
            'Earn Commissions' => 'Gagnez des Commissions',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Partagez votre lien de parrainage et gagnez 12% USDT + 12% de bonus NFT au Niveau 1, plus des récompenses multi-niveaux.'
        ],
        'German' => [
            'Earn Commissions' => 'Provisionen Verdienen',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Teilen Sie Ihren Empfehlungslink und verdienen Sie 12% USDT + 12% NFT-Boni auf Level 1, plus mehrstufige Belohnungen.'
        ],
        'Portuguese' => [
            'Earn Commissions' => 'Ganhe Comissões',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Compartilhe seu link de indicação e ganhe 12% USDT + 12% bônus NFT no Nível 1, mais recompensas multinível.'
        ],
        'Italian' => [
            'Earn Commissions' => 'Guadagna Commissioni',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Condividi il tuo link di referral e guadagna 12% USDT + 12% bonus NFT al Livello 1, più ricompense multi-livello.'
        ],
        'Russian' => [
            'Earn Commissions' => 'Зарабатывайте Комиссии',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Поделитесь своей реферальной ссылкой и зарабатывайте 12% USDT + 12% NFT бонусы на Уровне 1, плюс многоуровневые награды.'
        ],
        'Chinese' => [
            'Earn Commissions' => '赚取佣金',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => '分享您的推荐链接，在第1级赚取12% USDT + 12% NFT奖金，还有多级奖励。'
        ],
        'Japanese' => [
            'Earn Commissions' => 'コミッションを獲得',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => '紹介リンクを共有して、レベル1で12% USDT + 12% NFTボーナス、さらにマルチレベル報酬を獲得しましょう。'
        ],
        'Arabic' => [
            'Earn Commissions' => 'اكسب العمولات',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'شارك رابط الإحالة الخاص بك واكسب 12% USDT + 12% مكافآت NFT في المستوى 1، بالإضافة إلى مكافآت متعددة المستويات.'
        ],
        'Ukrainian' => [
            'Earn Commissions' => 'Заробляйте Комісії',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Поділіться своїм реферальним посиланням і заробляйте 12% USDT + 12% NFT бонуси на Рівні 1, плюс багаторівневі нагороди.'
        ],
        'Hindi' => [
            'Earn Commissions' => 'कमीशन कमाएं',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'अपना रेफरल लिंक साझा करें और लेवल 1 पर 12% USDT + 12% NFT बोनस कमाएं, साथ ही मल्टी-लेवल रिवार्ड्स भी।'
        ],
        'Urdu' => [
            'Earn Commissions' => 'کمیشن کمائیں',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'اپنا ریفرل لنک شیئر کریں اور لیول 1 پر 12% USDT + 12% NFT بونس کمائیں، نیز ملٹی لیول ریوارڈز بھی۔'
        ],
        'Bengali' => [
            'Earn Commissions' => 'কমিশন অর্জন করুন',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'আপনার রেফারেল লিঙ্ক শেয়ার করুন এবং লেভেল 1-এ 12% USDT + 12% NFT বোনাস অর্জন করুন, পাশাপাশি মাল্টি-লেভেল রিওয়ার্ডও।'
        ],
        'Korean' => [
            'Earn Commissions' => '커미션 획득',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => '추천 링크를 공유하고 레벨 1에서 12% USDT + 12% NFT 보너스를 획득하세요. 멀티 레벨 보상도 있습니다.'
        ],
        'Malay' => [
            'Earn Commissions' => 'Peroleh Komisen',
            'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.' => 'Kongsi pautan rujukan anda dan peroleh 12% USDT + 12% bonus NFT di Tahap 1, ditambah ganjaran pelbagai tahap.'
        ]
    ];
    
    if (isset($translations[$targetLanguage][$text])) {
        return $translations[$targetLanguage][$text];
    }
    
    return $text; // Return original if no translation found
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 4 translation keys to add
    $step4Keys = [
        [
            'key_name' => 'homepage.steps.step4.title',
            'category' => 'homepage',
            'description' => 'Earn Commissions'
        ],
        [
            'key_name' => 'homepage.steps.step4.description',
            'category' => 'homepage',
            'description' => 'Share your referral link and earn 12% USDT + 12% NFT bonuses on Level 1, plus multi-level rewards.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($step4Keys as $keyData) {
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
                $keyData['description']
            ]);
        }
    }
    
    // Add translations for all other languages
    if (!empty($addedKeys)) {
        $langQuery = "SELECT id, name, code FROM languages WHERE code != 'en' AND is_active = TRUE";
        $langStmt = $db->prepare($langQuery);
        $langStmt->execute();
        $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $translationsAdded = [];
        
        foreach ($languages as $language) {
            foreach ($addedKeys as $key) {
                // Get AI translation
                $translation = translateWithAI($key['description'], $language['name']);
                
                // Insert translation
                $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                               VALUES (?, ?, ?, TRUE, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$key['id'], $language['id'], $translation]);
                
                $translationsAdded[] = [
                    'key_name' => $key['key_name'],
                    'language' => $language['name'],
                    'language_code' => $language['code'],
                    'original' => $key['description'],
                    'translation' => $translation
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Step 4 translation keys processed successfully',
        'results' => [
            'added_keys' => $addedKeys,
            'skipped_keys' => $skippedKeys,
            'added_count' => count($addedKeys),
            'skipped_count' => count($skippedKeys),
            'translations_added' => isset($translationsAdded) ? count($translationsAdded) : 0
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add Step 4 translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
