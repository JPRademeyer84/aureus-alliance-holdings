<?php
// Add "How Angel Investing Works" section translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'How' => 'Cómo',
            'Angel Investing' => 'la Inversión Ángel',
            'Works' => 'Funciona',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Únete a la Alianza Ángel Aureus en 6 pasos simples. Sin procesos complicados, sin tarifas ocultas - solo un camino directo hacia la propiedad de oro digital.'
        ],
        'French' => [
            'How' => 'Comment',
            'Angel Investing' => 'l\'Investissement Providentiel',
            'Works' => 'Fonctionne',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Rejoignez l\'Alliance Ange Aureus en 6 étapes simples. Aucun processus compliqué, aucun frais caché - juste un chemin direct vers la propriété d\'or numérique.'
        ],
        'German' => [
            'How' => 'Wie',
            'Angel Investing' => 'Angel-Investitionen',
            'Works' => 'Funktionieren',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Treten Sie der Aureus Angel Alliance in 6 einfachen Schritten bei. Keine komplizierten Prozesse, keine versteckten Gebühren - nur ein direkter Weg zum digitalen Goldbesitz.'
        ],
        'Portuguese' => [
            'How' => 'Como',
            'Angel Investing' => 'o Investimento Anjo',
            'Works' => 'Funciona',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Junte-se à Aliança Anjo Aureus em 6 passos simples. Sem processos complicados, sem taxas ocultas - apenas um caminho direto para a propriedade de ouro digital.'
        ],
        'Italian' => [
            'How' => 'Come',
            'Angel Investing' => 'l\'Investimento Angelo',
            'Works' => 'Funziona',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Unisciti all\'Alleanza Angelo Aureus in 6 semplici passaggi. Nessun processo complicato, nessuna commissione nascosta - solo un percorso diretto verso la proprietà dell\'oro digitale.'
        ],
        'Russian' => [
            'How' => 'Как',
            'Angel Investing' => 'Ангельские Инвестиции',
            'Works' => 'Работают',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Присоединяйтесь к Альянсу Ангелов Aureus за 6 простых шагов. Никаких сложных процессов, никаких скрытых комиссий - только прямой путь к владению цифровым золотом.'
        ],
        'Chinese' => [
            'How' => '如何',
            'Angel Investing' => '天使投资',
            'Works' => '运作',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => '通过6个简单步骤加入Aureus天使联盟。没有复杂的流程，没有隐藏费用 - 只有通往数字黄金所有权的直接路径。'
        ],
        'Japanese' => [
            'How' => 'どのように',
            'Angel Investing' => 'エンジェル投資が',
            'Works' => '機能するか',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => '6つの簡単なステップでAureusエンジェルアライアンスに参加してください。複雑なプロセスも隠れた手数料もありません - デジタルゴールド所有への直接的な道のりです。'
        ],
        'Arabic' => [
            'How' => 'كيف',
            'Angel Investing' => 'الاستثمار الملائكي',
            'Works' => 'يعمل',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'انضم إلى تحالف الملائكة أوريوس في 6 خطوات بسيطة. لا توجد عمليات معقدة، ولا رسوم مخفية - فقط طريق مباشر لملكية الذهب الرقمي.'
        ],
        'Ukrainian' => [
            'How' => 'Як',
            'Angel Investing' => 'Ангельські Інвестиції',
            'Works' => 'Працюють',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Приєднайтеся до Альянсу Ангелів Aureus за 6 простих кроків. Ніяких складних процесів, ніяких прихованих комісій - лише прямий шлях до володіння цифровим золотом.'
        ],
        'Hindi' => [
            'How' => 'कैसे',
            'Angel Investing' => 'एंजेल निवेश',
            'Works' => 'काम करता है',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => '6 सरल चरणों में Aureus एंजेल एलायंस में शामिल हों। कोई जटिल प्रक्रिया नहीं, कोई छुपी हुई फीस नहीं - केवल डिजिटल सोने के स्वामित्व का सीधा रास्ता।'
        ],
        'Urdu' => [
            'How' => 'کیسے',
            'Angel Investing' => 'فرشتہ سرمایہ کاری',
            'Works' => 'کام کرتی ہے',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => '6 آسان مراحل میں Aureus فرشتہ اتحاد میں شامل ہوں۔ کوئی پیچیدہ عمل نہیں، کوئی چھپی ہوئی فیس نہیں - صرف ڈیجیٹل سونے کی ملکیت کا سیدھا راستہ۔'
        ],
        'Bengali' => [
            'How' => 'কীভাবে',
            'Angel Investing' => 'অ্যাঞ্জেল বিনিয়োগ',
            'Works' => 'কাজ করে',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => '৬টি সহজ ধাপে Aureus অ্যাঞ্জেল অ্যালায়েন্সে যোগ দিন। কোনো জটিল প্রক্রিয়া নেই, কোনো লুকানো ফি নেই - শুধু ডিজিটাল সোনার মালিকানার সরাসরি পথ।'
        ],
        'Korean' => [
            'How' => '어떻게',
            'Angel Investing' => '엔젤 투자가',
            'Works' => '작동하는지',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => '6가지 간단한 단계로 Aureus 엔젤 얼라이언스에 가입하세요. 복잡한 과정도, 숨겨진 수수료도 없습니다 - 디지털 금 소유권으로 가는 직접적인 길입니다.'
        ],
        'Malay' => [
            'How' => 'Bagaimana',
            'Angel Investing' => 'Pelaburan Malaikat',
            'Works' => 'Berfungsi',
            'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.' => 'Sertai Aureus Angel Alliance dalam 6 langkah mudah. Tiada proses rumit, tiada yuran tersembunyi - hanya laluan terus kepada pemilikan emas digital.'
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
    
    // How It Works section translation keys to add
    $howItWorksKeys = [
        [
            'key_name' => 'homepage.how_it_works.title_part1',
            'category' => 'homepage',
            'description' => 'How'
        ],
        [
            'key_name' => 'homepage.how_it_works.title_part2',
            'category' => 'homepage',
            'description' => 'Angel Investing'
        ],
        [
            'key_name' => 'homepage.how_it_works.title_part3',
            'category' => 'homepage',
            'description' => 'Works'
        ],
        [
            'key_name' => 'homepage.how_it_works.description',
            'category' => 'homepage',
            'description' => 'Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($howItWorksKeys as $keyData) {
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
        'message' => 'How It Works section translation keys processed successfully',
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
        'message' => 'Failed to add How It Works translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
