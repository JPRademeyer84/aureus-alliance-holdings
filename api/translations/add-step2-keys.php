<?php
// Add Step 2 "Choose Your NFT Package" translation keys
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Choose Your NFT Package' => 'Elige Tu Paquete NFT',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Selecciona de 8 paquetes de minería (Pala $25 a Aureus $1000) o combina múltiples paquetes para tu cantidad de inversión perfecta.'
        ],
        'French' => [
            'Choose Your NFT Package' => 'Choisissez Votre Pack NFT',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Sélectionnez parmi 8 packs de minage (Pelle 25$ à Aureus 1000$) ou combinez plusieurs packs pour votre montant d\'investissement parfait.'
        ],
        'German' => [
            'Choose Your NFT Package' => 'Wählen Sie Ihr NFT-Paket',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Wählen Sie aus 8 Mining-Paketen (Schaufel $25 bis Aureus $1000) oder kombinieren Sie mehrere Pakete für Ihren perfekten Investitionsbetrag.'
        ],
        'Portuguese' => [
            'Choose Your NFT Package' => 'Escolha Seu Pacote NFT',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Selecione entre 8 pacotes de mineração (Pá $25 a Aureus $1000) ou combine múltiplos pacotes para sua quantidade de investimento perfeita.'
        ],
        'Italian' => [
            'Choose Your NFT Package' => 'Scegli Il Tuo Pacchetto NFT',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Seleziona tra 8 pacchetti di mining (Pala $25 a Aureus $1000) o combina più pacchetti per il tuo importo di investimento perfetto.'
        ],
        'Russian' => [
            'Choose Your NFT Package' => 'Выберите Свой NFT Пакет',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Выберите из 8 майнинг пакетов (Лопата $25 до Aureus $1000) или объедините несколько пакетов для вашей идеальной суммы инвестиций.'
        ],
        'Chinese' => [
            'Choose Your NFT Package' => '选择您的NFT套餐',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => '从8个挖矿套餐中选择（铲子$25到Aureus $1000），或组合多个套餐以达到您的完美投资金额。'
        ],
        'Japanese' => [
            'Choose Your NFT Package' => 'NFTパッケージを選択',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => '8つのマイニングパッケージ（ショベル$25からAureus $1000）から選択するか、複数のパッケージを組み合わせて完璧な投資額にしてください。'
        ],
        'Arabic' => [
            'Choose Your NFT Package' => 'اختر حزمة NFT الخاصة بك',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'اختر من 8 حزم تعدين (مجرفة $25 إلى Aureus $1000) أو ادمج حزم متعددة لمبلغ الاستثمار المثالي.'
        ],
        'Ukrainian' => [
            'Choose Your NFT Package' => 'Оберіть Свій NFT Пакет',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Оберіть з 8 майнінг пакетів (Лопата $25 до Aureus $1000) або об\'єднайте кілька пакетів для вашої ідеальної суми інвестицій.'
        ],
        'Hindi' => [
            'Choose Your NFT Package' => 'अपना NFT पैकेज चुनें',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => '8 माइनिंग पैकेज (फावड़ा $25 से Aureus $1000) में से चुनें या अपनी सही निवेश राशि के लिए कई पैकेज को मिलाएं।'
        ],
        'Urdu' => [
            'Choose Your NFT Package' => 'اپنا NFT پیکج منتخب کریں',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => '8 مائننگ پیکجز (بیلچہ $25 سے Aureus $1000) میں سے منتخب کریں یا اپنی مکمل سرمایہ کاری کی رقم کے لیے متعدد پیکجز کو ملائیں۔'
        ],
        'Bengali' => [
            'Choose Your NFT Package' => 'আপনার NFT প্যাকেজ বেছে নিন',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => '৮টি মাইনিং প্যাকেজ (কোদাল $২৫ থেকে Aureus $১০০০) থেকে বেছে নিন বা আপনার নিখুঁত বিনিয়োগের পরিমাণের জন্য একাধিক প্যাকেজ একত্রিত করুন।'
        ],
        'Korean' => [
            'Choose Your NFT Package' => 'NFT 패키지 선택',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => '8개의 마이닝 패키지(삽 $25부터 Aureus $1000까지) 중에서 선택하거나 여러 패키지를 결합하여 완벽한 투자 금액을 만드세요.'
        ],
        'Malay' => [
            'Choose Your NFT Package' => 'Pilih Pakej NFT Anda',
            'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.' => 'Pilih dari 8 pakej perlombongan (Penyodok $25 hingga Aureus $1000) atau gabungkan beberapa pakej untuk jumlah pelaburan yang sempurna.'
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
    
    // Step 2 translation keys to add
    $step2Keys = [
        [
            'key_name' => 'homepage.steps.step2.title',
            'category' => 'homepage',
            'description' => 'Choose Your NFT Package'
        ],
        [
            'key_name' => 'homepage.steps.step2.description',
            'category' => 'homepage',
            'description' => 'Select from 8 mining packages (Shovel $25 to Aureus $1000) or combine multiple packages for your perfect investment amount.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($step2Keys as $keyData) {
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
        'message' => 'Step 2 translation keys processed successfully',
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
        'message' => 'Failed to add Step 2 translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
