<?php
// Add Call-to-Action section translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Ready to Become an Angel Investor?' => '¿Listo para Convertirte en un Inversionista Ángel?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Únete a la preventa de 200,000 paquetes NFT a $5 cada uno. Oportunidad por tiempo limitado para asegurar tus acciones de minería de oro digital antes de que comiencen las fases de venta principal.',
            'Start Investing Now' => 'Comenzar a Invertir Ahora',
            'View Investment Packages' => 'Ver Paquetes de Inversión'
        ],
        'French' => [
            'Ready to Become an Angel Investor?' => 'Prêt à Devenir un Investisseur Providentiel ?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Rejoignez la prévente de 200 000 packs NFT à 5$ chacun. Opportunité limitée dans le temps pour sécuriser vos parts de minage d\'or numérique avant le début des phases de vente principales.',
            'Start Investing Now' => 'Commencer à Investir Maintenant',
            'View Investment Packages' => 'Voir les Packages d\'Investissement'
        ],
        'German' => [
            'Ready to Become an Angel Investor?' => 'Bereit, ein Angel-Investor zu werden?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Nehmen Sie am Vorverkauf von 200.000 NFT-Paketen zu je 5$ teil. Zeitlich begrenzte Gelegenheit, Ihre digitalen Goldbergbau-Anteile zu sichern, bevor die Hauptverkaufsphasen beginnen.',
            'Start Investing Now' => 'Jetzt Investieren',
            'View Investment Packages' => 'Investment-Pakete Ansehen'
        ],
        'Portuguese' => [
            'Ready to Become an Angel Investor?' => 'Pronto para se Tornar um Investidor Anjo?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Junte-se à pré-venda de 200.000 pacotes NFT a $5 cada. Oportunidade por tempo limitado para garantir suas ações de mineração de ouro digital antes do início das fases de venda principal.',
            'Start Investing Now' => 'Começar a Investir Agora',
            'View Investment Packages' => 'Ver Pacotes de Investimento'
        ],
        'Italian' => [
            'Ready to Become an Angel Investor?' => 'Pronto a Diventare un Investitore Angelo?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Unisciti alla prevendita di 200.000 pacchetti NFT a $5 ciascuno. Opportunità a tempo limitato per assicurarti le tue quote di mining dell\'oro digitale prima dell\'inizio delle fasi di vendita principali.',
            'Start Investing Now' => 'Inizia a Investire Ora',
            'View Investment Packages' => 'Visualizza Pacchetti di Investimento'
        ],
        'Russian' => [
            'Ready to Become an Angel Investor?' => 'Готовы Стать Ангелом-Инвестором?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Присоединяйтесь к предпродаже 200,000 NFT-пакетов по $5 за каждый. Ограниченная по времени возможность обеспечить свои доли цифровой золотодобычи до начала основных фаз продаж.',
            'Start Investing Now' => 'Начать Инвестировать Сейчас',
            'View Investment Packages' => 'Посмотреть Инвестиционные Пакеты'
        ],
        'Chinese' => [
            'Ready to Become an Angel Investor?' => '准备成为天使投资者了吗？',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => '加入20万个NFT包的预售，每个5美元。在主要销售阶段开始之前，这是确保您的数字黄金挖矿股份的限时机会。',
            'Start Investing Now' => '立即开始投资',
            'View Investment Packages' => '查看投资套餐'
        ],
        'Japanese' => [
            'Ready to Become an Angel Investor?' => 'エンジェル投資家になる準備はできていますか？',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => '1パック5ドルで20万個のNFTパックのプレセールに参加しましょう。メインセール段階が始まる前にデジタル金採掘シェアを確保する限定時間の機会です。',
            'Start Investing Now' => '今すぐ投資を開始',
            'View Investment Packages' => '投資パッケージを見る'
        ],
        'Arabic' => [
            'Ready to Become an Angel Investor?' => 'مستعد لتصبح مستثمر ملاك؟',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'انضم إلى البيع المسبق لـ 200,000 حزمة NFT بسعر 5 دولارات لكل منها. فرصة محدودة الوقت لتأمين أسهم تعدين الذهب الرقمي قبل بدء مراحل البيع الرئيسية.',
            'Start Investing Now' => 'ابدأ الاستثمار الآن',
            'View Investment Packages' => 'عرض حزم الاستثمار'
        ],
        'Ukrainian' => [
            'Ready to Become an Angel Investor?' => 'Готові Стати Ангелом-Інвестором?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Приєднуйтесь до передпродажу 200,000 NFT-пакетів по $5 за кожен. Обмежена в часі можливість забезпечити свої частки цифрового золотодобування до початку основних фаз продажів.',
            'Start Investing Now' => 'Почати Інвестувати Зараз',
            'View Investment Packages' => 'Переглянути Інвестиційні Пакети'
        ],
        'Hindi' => [
            'Ready to Become an Angel Investor?' => 'एंजेल इन्वेस्टर बनने के लिए तैयार हैं?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => '200,000 NFT पैक्स के प्रीसेल में शामिल हों, प्रत्येक $5 में। मुख्य बिक्री चरण शुरू होने से पहले अपने डिजिटल गोल्ड माइनिंग शेयर्स को सुरक्षित करने का सीमित समय का अवसर।',
            'Start Investing Now' => 'अभी निवेश शुरू करें',
            'View Investment Packages' => 'निवेश पैकेज देखें'
        ],
        'Urdu' => [
            'Ready to Become an Angel Investor?' => 'ایک فرشتہ سرمایہ کار بننے کے لیے تیار ہیں؟',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => '200,000 NFT پیکس کی پری سیل میں شامل ہوں، ہر ایک $5 میں۔ اصل فروخت کے مراحل شروع ہونے سے پہلے اپنے ڈیجیٹل گولڈ مائننگ شیئرز محفوظ کرنے کا محدود وقت کا موقع۔',
            'Start Investing Now' => 'ابھی سرمایہ کاری شروع کریں',
            'View Investment Packages' => 'سرمایہ کاری کے پیکجز دیکھیں'
        ],
        'Bengali' => [
            'Ready to Become an Angel Investor?' => 'একজন এঞ্জেল বিনিয়োগকারী হতে প্রস্তুত?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => '200,000 NFT প্যাকের প্রিসেলে যোগ দিন, প্রতিটি $5 এ। মূল বিক্রয় পর্যায় শুরু হওয়ার আগে আপনার ডিজিটাল সোনার খনন শেয়ার সুরক্ষিত করার সীমিত সময়ের সুযোগ।',
            'Start Investing Now' => 'এখনই বিনিয়োগ শুরু করুন',
            'View Investment Packages' => 'বিনিয়োগ প্যাকেজ দেখুন'
        ],
        'Korean' => [
            'Ready to Become an Angel Investor?' => '엔젤 투자자가 될 준비가 되셨나요?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => '개당 5달러인 200,000개 NFT 팩의 사전 판매에 참여하세요. 주요 판매 단계가 시작되기 전에 디지털 금 채굴 지분을 확보할 수 있는 제한된 시간의 기회입니다.',
            'Start Investing Now' => '지금 투자 시작',
            'View Investment Packages' => '투자 패키지 보기'
        ],
        'Malay' => [
            'Ready to Become an Angel Investor?' => 'Bersedia untuk Menjadi Pelabur Malaikat?',
            'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.' => 'Sertai prajualan 200,000 pek NFT pada $5 setiap satu. Peluang masa terhad untuk mendapatkan saham perlombongan emas digital anda sebelum fasa jualan utama bermula.',
            'Start Investing Now' => 'Mula Melabur Sekarang',
            'View Investment Packages' => 'Lihat Pakej Pelaburan'
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
    
    // CTA section translation keys to add
    $ctaKeys = [
        [
            'key_name' => 'homepage.cta.title',
            'category' => 'homepage',
            'description' => 'Ready to Become an Angel Investor?'
        ],
        [
            'key_name' => 'homepage.cta.description',
            'category' => 'homepage',
            'description' => 'Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to secure your digital gold mining shares before the main sale phases begin.'
        ],
        [
            'key_name' => 'homepage.cta.start_investing',
            'category' => 'homepage',
            'description' => 'Start Investing Now'
        ],
        [
            'key_name' => 'homepage.cta.view_packages',
            'category' => 'homepage',
            'description' => 'View Investment Packages'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($ctaKeys as $keyData) {
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
        'message' => 'CTA section translation keys processed successfully',
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
        'message' => 'Failed to add CTA section translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
