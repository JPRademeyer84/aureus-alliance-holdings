<?php
// Add Step 5 "180-Day ROI Period" translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            '180-Day ROI Period' => 'Período ROI de 180 Días',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Gana ROI diario durante 180 días (1.7% a 5% diario según el paquete). Rastrea tus ganancias en tiempo real en tu panel.'
        ],
        'French' => [
            '180-Day ROI Period' => 'Période ROI de 180 Jours',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Gagnez un ROI quotidien pendant 180 jours (1,7% à 5% quotidien selon le package). Suivez vos gains en temps réel sur votre tableau de bord.'
        ],
        'German' => [
            '180-Day ROI Period' => '180-Tage ROI-Zeitraum',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Verdienen Sie 180 Tage lang täglich ROI (1,7% bis 5% täglich je nach Paket). Verfolgen Sie Ihre Einnahmen in Echtzeit auf Ihrem Dashboard.'
        ],
        'Portuguese' => [
            '180-Day ROI Period' => 'Período ROI de 180 Dias',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Ganhe ROI diário por 180 dias (1,7% a 5% diário baseado no pacote). Acompanhe seus ganhos em tempo real no seu painel.'
        ],
        'Italian' => [
            '180-Day ROI Period' => 'Periodo ROI di 180 Giorni',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Guadagna ROI giornaliero per 180 giorni (dall\'1,7% al 5% giornaliero in base al pacchetto). Traccia i tuoi guadagni in tempo reale sulla tua dashboard.'
        ],
        'Russian' => [
            '180-Day ROI Period' => '180-дневный период ROI',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Зарабатывайте ежедневный ROI в течение 180 дней (от 1,7% до 5% в день в зависимости от пакета). Отслеживайте свои доходы в реальном времени на панели управления.'
        ],
        'Chinese' => [
            '180-Day ROI Period' => '180天投资回报期',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => '180天内赚取每日投资回报（根据套餐每日1.7%至5%）。在您的仪表板上实时跟踪您的收益。'
        ],
        'Japanese' => [
            '180-Day ROI Period' => '180日間ROI期間',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => '180日間毎日ROIを獲得（パッケージに基づいて毎日1.7%から5%）。ダッシュボードでリアルタイムに収益を追跡できます。'
        ],
        'Arabic' => [
            '180-Day ROI Period' => 'فترة عائد الاستثمار 180 يوم',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'اكسب عائد استثمار يومي لمدة 180 يومًا (1.7% إلى 5% يوميًا حسب الحزمة). تتبع أرباحك في الوقت الفعلي على لوحة التحكم الخاصة بك.'
        ],
        'Ukrainian' => [
            '180-Day ROI Period' => '180-денний період ROI',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Заробляйте щоденний ROI протягом 180 днів (від 1,7% до 5% на день залежно від пакету). Відстежуйте свої доходи в реальному часі на панелі управління.'
        ],
        'Hindi' => [
            '180-Day ROI Period' => '180-दिन ROI अवधि',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => '180 दिनों के लिए दैनिक ROI कमाएं (पैकेज के आधार पर दैनिक 1.7% से 5%)। अपने डैशबोर्ड पर रियल-टाइम में अपनी कमाई को ट्रैक करें।'
        ],
        'Urdu' => [
            '180-Day ROI Period' => '180 دن ROI مدت',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => '180 دنوں کے لیے روزانہ ROI کمائیں (پیکج کی بنیاد پر روزانہ 1.7% سے 5%)۔ اپنے ڈیش بورڈ پر ریئل ٹائم میں اپنی کمائی کو ٹریک کریں۔'
        ],
        'Bengali' => [
            '180-Day ROI Period' => '১৮০-দিন ROI সময়কাল',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => '১৮০ দিনের জন্য দৈনিক ROI অর্জন করুন (প্যাকেজের ভিত্তিতে দৈনিক ১.৭% থেকে ৫%)। আপনার ড্যাশবোর্ডে রিয়েল-টাইমে আপনার আয় ট্র্যাক করুন।'
        ],
        'Korean' => [
            '180-Day ROI Period' => '180일 ROI 기간',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => '180일 동안 일일 ROI를 획득하세요 (패키지에 따라 일일 1.7%에서 5%). 대시보드에서 실시간으로 수익을 추적하세요.'
        ],
        'Malay' => [
            '180-Day ROI Period' => 'Tempoh ROI 180 Hari',
            'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.' => 'Peroleh ROI harian selama 180 hari (1.7% hingga 5% harian berdasarkan pakej). Jejaki pendapatan anda secara masa nyata di papan pemuka anda.'
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
    
    // Step 5 translation keys to add
    $step5Keys = [
        [
            'key_name' => 'homepage.steps.step5.title',
            'category' => 'homepage',
            'description' => '180-Day ROI Period'
        ],
        [
            'key_name' => 'homepage.steps.step5.description',
            'category' => 'homepage',
            'description' => 'Earn daily ROI for 180 days (1.7% to 5% daily based on package). Track your earnings in real-time on your dashboard.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($step5Keys as $keyData) {
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
        'message' => 'Step 5 translation keys processed successfully',
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
        'message' => 'Failed to add Step 5 translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
