<?php
// Add "Why Choose Aureus Alliance?" benefits translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Why Choose Aureus Alliance?' => '¿Por Qué Elegir Aureus Alliance?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Comienza con solo $25 (paquete Pala) - sin barreras mínimas',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 paquetes de minería de $25 a $1,000 - perfecto para cualquier presupuesto',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'ROI diario del 1.7% al 5% durante 180 días garantizado',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% bonos NFT en referencias de Nivel 1',
            'Polygon blockchain transparency with USDT payments' => 'Transparencia blockchain Polygon con pagos USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Respaldado por operaciones reales de minería de oro Aureus Alliance'
        ],
        'French' => [
            'Why Choose Aureus Alliance?' => 'Pourquoi Choisir Aureus Alliance ?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Commencez avec seulement 25$ (package Pelle) - aucune barrière minimale',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 packages de minage de 25$ à 1 000$ - parfait pour tout budget',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'ROI quotidien de 1,7% à 5% pendant 180 jours garanti',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% bonus NFT sur les parrainages Niveau 1',
            'Polygon blockchain transparency with USDT payments' => 'Transparence blockchain Polygon avec paiements USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Soutenu par de vraies opérations de minage d\'or Aureus Alliance'
        ],
        'German' => [
            'Why Choose Aureus Alliance?' => 'Warum Aureus Alliance Wählen?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Beginnen Sie mit nur 25$ (Schaufel-Paket) - keine Mindesthürden',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 Mining-Pakete von 25$ bis 1.000$ - perfekt für jedes Budget',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'Täglicher ROI von 1,7% bis 5% für 180 Tage garantiert',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% NFT-Boni auf Level 1 Empfehlungen',
            'Polygon blockchain transparency with USDT payments' => 'Polygon-Blockchain-Transparenz mit USDT-Zahlungen',
            'Backed by real Aureus Alliance gold mining operations' => 'Unterstützt von echten Aureus Alliance Goldbergbau-Operationen'
        ],
        'Portuguese' => [
            'Why Choose Aureus Alliance?' => 'Por Que Escolher Aureus Alliance?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Comece com apenas $25 (pacote Pá) - sem barreiras mínimas',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 pacotes de mineração de $25 a $1.000 - perfeito para qualquer orçamento',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'ROI diário de 1,7% a 5% por 180 dias garantido',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% bônus NFT em indicações Nível 1',
            'Polygon blockchain transparency with USDT payments' => 'Transparência blockchain Polygon com pagamentos USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Apoiado por operações reais de mineração de ouro Aureus Alliance'
        ],
        'Italian' => [
            'Why Choose Aureus Alliance?' => 'Perché Scegliere Aureus Alliance?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Inizia con soli $25 (pacchetto Pala) - nessuna barriera minima',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 pacchetti di mining da $25 a $1.000 - perfetto per qualsiasi budget',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'ROI giornaliero dall\'1,7% al 5% per 180 giorni garantito',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% bonus NFT sui referral Livello 1',
            'Polygon blockchain transparency with USDT payments' => 'Trasparenza blockchain Polygon con pagamenti USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Supportato da vere operazioni di mining dell\'oro Aureus Alliance'
        ],
        'Russian' => [
            'Why Choose Aureus Alliance?' => 'Почему Выбрать Aureus Alliance?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Начните всего с $25 (пакет Лопата) - никаких минимальных барьеров',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 майнинг-пакетов от $25 до $1,000 - идеально для любого бюджета',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'Ежедневный ROI от 1,7% до 5% в течение 180 дней гарантирован',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% NFT бонусы с рефералов Уровня 1',
            'Polygon blockchain transparency with USDT payments' => 'Прозрачность блокчейна Polygon с платежами USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Поддерживается реальными операциями золотодобычи Aureus Alliance'
        ],
        'Chinese' => [
            'Why Choose Aureus Alliance?' => '为什么选择Aureus Alliance？',
            'Start with just $25 (Shovel package) - no minimum barriers' => '仅需$25起步（铲子套餐）- 无最低门槛',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8个挖矿套餐从$25到$1,000 - 适合任何预算',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => '180天内每日1.7%至5%投资回报率保证',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '一级推荐12% USDT + 12% NFT奖金',
            'Polygon blockchain transparency with USDT payments' => 'Polygon区块链透明度与USDT支付',
            'Backed by real Aureus Alliance gold mining operations' => '由真实的Aureus Alliance黄金开采业务支持'
        ],
        'Japanese' => [
            'Why Choose Aureus Alliance?' => 'なぜAureus Allianceを選ぶのか？',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'わずか$25から開始（シャベルパッケージ）- 最低限の障壁なし',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '$25から$1,000まで8つのマイニングパッケージ - あらゆる予算に最適',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => '180日間、日次1.7%から5%のROI保証',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => 'レベル1紹介で12% USDT + 12% NFTボーナス',
            'Polygon blockchain transparency with USDT payments' => 'USDTペイメントによるPolygonブロックチェーンの透明性',
            'Backed by real Aureus Alliance gold mining operations' => '実際のAureus Alliance金採掘事業に支えられています'
        ],
        'Arabic' => [
            'Why Choose Aureus Alliance?' => 'لماذا تختار Aureus Alliance؟',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'ابدأ بـ 25 دولارًا فقط (حزمة المجرفة) - بلا حواجز دنيا',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 حزم تعدين من 25 دولارًا إلى 1000 دولار - مثالية لأي ميزانية',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'عائد استثمار يومي من 1.7% إلى 5% لمدة 180 يومًا مضمون',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% مكافآت NFT على إحالات المستوى الأول',
            'Polygon blockchain transparency with USDT payments' => 'شفافية بلوك تشين Polygon مع مدفوعات USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'مدعوم بعمليات تعدين الذهب الحقيقية لـ Aureus Alliance'
        ],
        'Ukrainian' => [
            'Why Choose Aureus Alliance?' => 'Чому Обрати Aureus Alliance?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Почніть всього з $25 (пакет Лопата) - жодних мінімальних бар\'єрів',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 майнінг-пакетів від $25 до $1,000 - ідеально для будь-якого бюджету',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'Щоденний ROI від 1,7% до 5% протягом 180 днів гарантований',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% NFT бонуси з рефералів Рівня 1',
            'Polygon blockchain transparency with USDT payments' => 'Прозорість блокчейну Polygon з платежами USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Підтримується реальними операціями золотодобування Aureus Alliance'
        ],
        'Hindi' => [
            'Why Choose Aureus Alliance?' => 'Aureus Alliance क्यों चुनें?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'केवल $25 से शुरू करें (शॉवल पैकेज) - कोई न्यूनतम बाधाएं नहीं',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '$25 से $1,000 तक 8 माइनिंग पैकेज - किसी भी बजट के लिए परफेक्ट',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => '180 दिनों के लिए 1.7% से 5% तक दैनिक ROI गारंटीशुदा',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => 'लेवल 1 रेफरल पर 12% USDT + 12% NFT बोनस',
            'Polygon blockchain transparency with USDT payments' => 'USDT पेमेंट्स के साथ Polygon ब्लॉकचेन पारदर्शिता',
            'Backed by real Aureus Alliance gold mining operations' => 'वास्तविक Aureus Alliance गोल्ड माइनिंग ऑपरेशन्स द्वारा समर्थित'
        ],
        'Urdu' => [
            'Why Choose Aureus Alliance?' => 'Aureus Alliance کیوں منتخب کریں؟',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'صرف $25 سے شروع کریں (شاول پیکج) - کوئی کم سے کم رکاوٹیں نہیں',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '$25 سے $1,000 تک 8 مائننگ پیکجز - کسی بھی بجٹ کے لیے بہترین',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => '180 دنوں کے لیے 1.7% سے 5% تک روزانہ ROI کی ضمانت',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => 'لیول 1 ریفرلز پر 12% USDT + 12% NFT بونس',
            'Polygon blockchain transparency with USDT payments' => 'USDT پیمنٹس کے ساتھ Polygon بلاک چین شفافیت',
            'Backed by real Aureus Alliance gold mining operations' => 'حقیقی Aureus Alliance گولڈ مائننگ آپریشنز کی حمایت یافتہ'
        ],
        'Bengali' => [
            'Why Choose Aureus Alliance?' => 'কেন Aureus Alliance বেছে নেবেন?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'মাত্র $25 দিয়ে শুরু করুন (শাভেল প্যাকেজ) - কোন ন্যূনতম বাধা নেই',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '$25 থেকে $1,000 পর্যন্ত 8টি মাইনিং প্যাকেজ - যেকোনো বাজেটের জন্য নিখুঁত',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => '180 দিনের জন্য 1.7% থেকে 5% পর্যন্ত দৈনিক ROI গ্যারান্টিযুক্ত',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => 'লেভেল 1 রেফারেলে 12% USDT + 12% NFT বোনাস',
            'Polygon blockchain transparency with USDT payments' => 'USDT পেমেন্টের সাথে Polygon ব্লকচেইন স্বচ্ছতা',
            'Backed by real Aureus Alliance gold mining operations' => 'প্রকৃত Aureus Alliance সোনার খনন কার্যক্রম দ্বারা সমর্থিত'
        ],
        'Korean' => [
            'Why Choose Aureus Alliance?' => '왜 Aureus Alliance를 선택해야 할까요?',
            'Start with just $25 (Shovel package) - no minimum barriers' => '단 $25로 시작 (삽 패키지) - 최소 장벽 없음',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '$25부터 $1,000까지 8개 마이닝 패키지 - 모든 예산에 완벽',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => '180일간 일일 1.7%에서 5% ROI 보장',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '레벨 1 추천에서 12% USDT + 12% NFT 보너스',
            'Polygon blockchain transparency with USDT payments' => 'USDT 결제를 통한 Polygon 블록체인 투명성',
            'Backed by real Aureus Alliance gold mining operations' => '실제 Aureus Alliance 금 채굴 운영으로 뒷받침됨'
        ],
        'Malay' => [
            'Why Choose Aureus Alliance?' => 'Mengapa Pilih Aureus Alliance?',
            'Start with just $25 (Shovel package) - no minimum barriers' => 'Mulakan dengan hanya $25 (pakej Penyodok) - tiada halangan minimum',
            '8 mining packages from $25 to $1,000 - perfect for any budget' => '8 pakej perlombongan dari $25 hingga $1,000 - sempurna untuk sebarang bajet',
            'Daily ROI from 1.7% to 5% for 180 days guaranteed' => 'ROI harian dari 1.7% hingga 5% selama 180 hari dijamin',
            '12% USDT + 12% NFT bonuses on Level 1 referrals' => '12% USDT + 12% bonus NFT pada rujukan Tahap 1',
            'Polygon blockchain transparency with USDT payments' => 'Ketelusan blockchain Polygon dengan pembayaran USDT',
            'Backed by real Aureus Alliance gold mining operations' => 'Disokong oleh operasi perlombongan emas Aureus Alliance yang sebenar'
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
    
    // Benefits section translation keys to add
    $benefitsKeys = [
        [
            'key_name' => 'homepage.benefits.title',
            'category' => 'homepage',
            'description' => 'Why Choose Aureus Alliance?'
        ],
        [
            'key_name' => 'homepage.benefits.benefit1',
            'category' => 'homepage',
            'description' => 'Start with just $25 (Shovel package) - no minimum barriers'
        ],
        [
            'key_name' => 'homepage.benefits.benefit2',
            'category' => 'homepage',
            'description' => '8 mining packages from $25 to $1,000 - perfect for any budget'
        ],
        [
            'key_name' => 'homepage.benefits.benefit3',
            'category' => 'homepage',
            'description' => 'Daily ROI from 1.7% to 5% for 180 days guaranteed'
        ],
        [
            'key_name' => 'homepage.benefits.benefit4',
            'category' => 'homepage',
            'description' => '12% USDT + 12% NFT bonuses on Level 1 referrals'
        ],
        [
            'key_name' => 'homepage.benefits.benefit5',
            'category' => 'homepage',
            'description' => 'Polygon blockchain transparency with USDT payments'
        ],
        [
            'key_name' => 'homepage.benefits.benefit6',
            'category' => 'homepage',
            'description' => 'Backed by real Aureus Alliance gold mining operations'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($benefitsKeys as $keyData) {
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
        'message' => 'Benefits section translation keys processed successfully',
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
        'message' => 'Failed to add benefits section translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
