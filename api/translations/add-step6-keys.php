<?php
// Add Step 6 "Receive Your Returns" translation keys
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Receive Your Returns' => 'Recibe Tus Retornos',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Después de 180 días, recibe tus acciones de minería NFT más el ROI total. Luego gana dividendos trimestrales de las ganancias de minería de oro Aureus.'
        ],
        'French' => [
            'Receive Your Returns' => 'Recevez Vos Retours',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Après 180 jours, recevez vos parts de minage NFT plus le ROI total. Ensuite, gagnez des dividendes trimestriels des profits de minage d\'or Aureus.'
        ],
        'German' => [
            'Receive Your Returns' => 'Erhalten Sie Ihre Renditen',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Nach 180 Tagen erhalten Sie Ihre NFT-Mining-Anteile plus Gesamt-ROI. Dann verdienen Sie vierteljährliche Dividenden aus den Aureus-Goldbergbau-Gewinnen.'
        ],
        'Portuguese' => [
            'Receive Your Returns' => 'Receba Seus Retornos',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Após 180 dias, receba suas ações de mineração NFT mais o ROI total. Então ganhe dividendos trimestrais dos lucros de mineração de ouro Aureus.'
        ],
        'Italian' => [
            'Receive Your Returns' => 'Ricevi i Tuoi Ritorni',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Dopo 180 giorni, ricevi le tue quote di mining NFT più il ROI totale. Poi guadagna dividendi trimestrali dai profitti del mining dell\'oro Aureus.'
        ],
        'Russian' => [
            'Receive Your Returns' => 'Получите Свои Доходы',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Через 180 дней получите свои доли NFT-майнинга плюс общий ROI. Затем зарабатывайте квартальные дивиденды от прибыли золотодобычи Aureus.'
        ],
        'Chinese' => [
            'Receive Your Returns' => '获得您的回报',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => '180天后，获得您的NFT挖矿股份加上总投资回报。然后从Aureus黄金挖矿利润中赚取季度股息。'
        ],
        'Japanese' => [
            'Receive Your Returns' => 'リターンを受け取る',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => '180日後、NFTマイニングシェアと総ROIを受け取ります。その後、Aureusゴールドマイニング利益から四半期配当を獲得します。'
        ],
        'Arabic' => [
            'Receive Your Returns' => 'احصل على عوائدك',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'بعد 180 يومًا، احصل على أسهم تعدين NFT الخاصة بك بالإضافة إلى إجمالي عائد الاستثمار. ثم اكسب أرباحًا ربع سنوية من أرباح تعدين الذهب Aureus.'
        ],
        'Ukrainian' => [
            'Receive Your Returns' => 'Отримайте Свої Доходи',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Через 180 днів отримайте свої частки NFT-майнінгу плюс загальний ROI. Потім заробляйте квартальні дивіденди від прибутку золотодобування Aureus.'
        ],
        'Hindi' => [
            'Receive Your Returns' => 'अपने रिटर्न प्राप्त करें',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => '180 दिनों के बाद, अपने NFT माइनिंग शेयर प्लस कुल ROI प्राप्त करें। फिर Aureus गोल्ड माइनिंग मुनाफे से त्रैमासिक लाभांश कमाएं।'
        ],
        'Urdu' => [
            'Receive Your Returns' => 'اپنے منافع حاصل کریں',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => '180 دنوں کے بعد، اپنے NFT مائننگ شیئرز پلس کل ROI حاصل کریں۔ پھر Aureus گولڈ مائننگ منافع سے سہ ماہی منافع کمائیں۔'
        ],
        'Bengali' => [
            'Receive Your Returns' => 'আপনার রিটার্ন পান',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => '১৮০ দিন পর, আপনার NFT মাইনিং শেয়ার প্লাস মোট ROI পান। তারপর Aureus গোল্ড মাইনিং মুনাফা থেকে ত্রৈমাসিক লভ্যাংশ অর্জন করুন।'
        ],
        'Korean' => [
            'Receive Your Returns' => '수익을 받으세요',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => '180일 후, NFT 마이닝 지분과 총 ROI를 받으세요. 그 다음 Aureus 금 채굴 수익에서 분기별 배당금을 획득하세요.'
        ],
        'Malay' => [
            'Receive Your Returns' => 'Terima Pulangan Anda',
            'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.' => 'Selepas 180 hari, terima saham perlombongan NFT anda ditambah jumlah ROI. Kemudian peroleh dividen suku tahunan daripada keuntungan perlombongan emas Aureus.'
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
    
    // Step 6 translation keys to add
    $step6Keys = [
        [
            'key_name' => 'homepage.steps.step6.title',
            'category' => 'homepage',
            'description' => 'Receive Your Returns'
        ],
        [
            'key_name' => 'homepage.steps.step6.description',
            'category' => 'homepage',
            'description' => 'After 180 days, receive your NFT mining shares plus total ROI. Then earn quarterly dividends from Aureus gold mining profits.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($step6Keys as $keyData) {
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
        'message' => 'Step 6 translation keys processed successfully',
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
        'message' => 'Failed to add Step 6 translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
