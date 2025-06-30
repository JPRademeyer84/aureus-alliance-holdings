<?php
// Add Investment Package Calculator translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Investment Package:' => 'Paquete de Inversión:',
            'Shovel - $25.00' => 'Pala - $25.00',
            'Calculate Returns' => 'Calcular Retornos',
            'View Investment Plans' => 'Ver Planes de Inversión',
            'Total Yield' => 'Rendimiento Total',
            'Aureus Shares' => 'Acciones Aureus',
            'Annual Dividend' => 'Dividendo Anual',
            'By 1 January 2026' => 'Para el 1 de Enero 2026',
            'Shovel Package' => 'Paquete Pala',
            'Starting Q3 2026' => 'Comenzando T3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Al invertir $25.00 hoy, podrías recibir $76.50 en rendimiento para el 1 de enero de 2026, más $5.00 anuales en dividendos comenzando T3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Inversiones entre $25 y $1,000. Máximo total por ronda: $250,000.'
        ],
        'French' => [
            'Investment Package:' => 'Package d\'Investissement :',
            'Shovel - $25.00' => 'Pelle - 25,00$',
            'Calculate Returns' => 'Calculer les Rendements',
            'View Investment Plans' => 'Voir les Plans d\'Investissement',
            'Total Yield' => 'Rendement Total',
            'Aureus Shares' => 'Actions Aureus',
            'Annual Dividend' => 'Dividende Annuel',
            'By 1 January 2026' => 'D\'ici le 1er Janvier 2026',
            'Shovel Package' => 'Package Pelle',
            'Starting Q3 2026' => 'À partir du T3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'En investissant 25,00$ aujourd\'hui, vous pourriez recevoir 76,50$ de rendement d\'ici le 1er janvier 2026, plus 5,00$ annuellement en dividendes à partir du T3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Investissements entre 25$ et 1 000$. Maximum total par tour : 250 000$.'
        ],
        'German' => [
            'Investment Package:' => 'Investment-Paket:',
            'Shovel - $25.00' => 'Schaufel - $25,00',
            'Calculate Returns' => 'Renditen Berechnen',
            'View Investment Plans' => 'Investment-Pläne Ansehen',
            'Total Yield' => 'Gesamtertrag',
            'Aureus Shares' => 'Aureus-Anteile',
            'Annual Dividend' => 'Jährliche Dividende',
            'By 1 January 2026' => 'Bis 1. Januar 2026',
            'Shovel Package' => 'Schaufel-Paket',
            'Starting Q3 2026' => 'Ab Q3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Durch eine Investition von $25,00 heute könnten Sie bis zum 1. Januar 2026 $76,50 Ertrag erhalten, plus $5,00 jährlich an Dividenden ab Q3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Investitionen zwischen $25 und $1.000. Maximaler Gesamtbetrag pro Runde: $250.000.'
        ],
        'Portuguese' => [
            'Investment Package:' => 'Pacote de Investimento:',
            'Shovel - $25.00' => 'Pá - $25,00',
            'Calculate Returns' => 'Calcular Retornos',
            'View Investment Plans' => 'Ver Planos de Investimento',
            'Total Yield' => 'Rendimento Total',
            'Aureus Shares' => 'Ações Aureus',
            'Annual Dividend' => 'Dividendo Anual',
            'By 1 January 2026' => 'Até 1º de Janeiro 2026',
            'Shovel Package' => 'Pacote Pá',
            'Starting Q3 2026' => 'Começando T3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Ao investir $25,00 hoje, você poderia receber $76,50 em rendimento até 1º de janeiro de 2026, mais $5,00 anuais em dividendos começando T3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Investimentos entre $25 e $1.000. Máximo total por rodada: $250.000.'
        ],
        'Italian' => [
            'Investment Package:' => 'Pacchetto di Investimento:',
            'Shovel - $25.00' => 'Pala - $25,00',
            'Calculate Returns' => 'Calcola Rendimenti',
            'View Investment Plans' => 'Visualizza Piani di Investimento',
            'Total Yield' => 'Rendimento Totale',
            'Aureus Shares' => 'Azioni Aureus',
            'Annual Dividend' => 'Dividendo Annuale',
            'By 1 January 2026' => 'Entro il 1° Gennaio 2026',
            'Shovel Package' => 'Pacchetto Pala',
            'Starting Q3 2026' => 'A partire da Q3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Investendo $25,00 oggi, potresti ricevere $76,50 di rendimento entro il 1° gennaio 2026, più $5,00 annui in dividendi a partire da Q3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Investimenti tra $25 e $1.000. Massimo totale per round: $250.000.'
        ],
        'Russian' => [
            'Investment Package:' => 'Инвестиционный Пакет:',
            'Shovel - $25.00' => 'Лопата - $25,00',
            'Calculate Returns' => 'Рассчитать Доходность',
            'View Investment Plans' => 'Посмотреть Инвестиционные Планы',
            'Total Yield' => 'Общая Доходность',
            'Aureus Shares' => 'Акции Aureus',
            'Annual Dividend' => 'Годовой Дивиденд',
            'By 1 January 2026' => 'К 1 января 2026',
            'Shovel Package' => 'Пакет Лопата',
            'Starting Q3 2026' => 'Начиная с Q3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Инвестируя $25,00 сегодня, вы можете получить $76,50 доходности к 1 января 2026 года, плюс $5,00 ежегодно в виде дивидендов начиная с Q3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Инвестиции от $25 до $1.000. Максимальная общая сумма раунда: $250.000.'
        ],
        'Chinese' => [
            'Investment Package:' => '投资套餐：',
            'Shovel - $25.00' => '铲子 - $25.00',
            'Calculate Returns' => '计算回报',
            'View Investment Plans' => '查看投资计划',
            'Total Yield' => '总收益',
            'Aureus Shares' => 'Aureus股份',
            'Annual Dividend' => '年度股息',
            'By 1 January 2026' => '到2026年1月1日',
            'Shovel Package' => '铲子套餐',
            'Starting Q3 2026' => '从2026年第三季度开始',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => '今天投资$25.00，您可以在2026年1月1日前获得$76.50的收益，外加从2026年第三季度开始每年$5.00的股息。',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => '投资金额在$25到$1,000之间。每轮最大总额：$250,000。'
        ],
        'Japanese' => [
            'Investment Package:' => '投資パッケージ：',
            'Shovel - $25.00' => 'シャベル - $25.00',
            'Calculate Returns' => 'リターンを計算',
            'View Investment Plans' => '投資プランを見る',
            'Total Yield' => '総利回り',
            'Aureus Shares' => 'Aureusシェア',
            'Annual Dividend' => '年間配当',
            'By 1 January 2026' => '2026年1月1日まで',
            'Shovel Package' => 'シャベルパッケージ',
            'Starting Q3 2026' => '2026年第3四半期開始',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => '今日$25.00を投資することで、2026年1月1日までに$76.50の利回りを受け取ることができ、さらに2026年第3四半期から年間$5.00の配当を受け取れます。',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => '$25から$1,000の間の投資。ラウンドあたりの最大総額：$250,000。'
        ],
        'Arabic' => [
            'Investment Package:' => 'حزمة الاستثمار:',
            'Shovel - $25.00' => 'مجرفة - $25.00',
            'Calculate Returns' => 'حساب العوائد',
            'View Investment Plans' => 'عرض خطط الاستثمار',
            'Total Yield' => 'العائد الإجمالي',
            'Aureus Shares' => 'أسهم Aureus',
            'Annual Dividend' => 'أرباح سنوية',
            'By 1 January 2026' => 'بحلول 1 يناير 2026',
            'Shovel Package' => 'حزمة المجرفة',
            'Starting Q3 2026' => 'بدءاً من الربع الثالث 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'من خلال استثمار $25.00 اليوم، يمكنك الحصول على $76.50 كعائد بحلول 1 يناير 2026، بالإضافة إلى $5.00 سنوياً كأرباح بدءاً من الربع الثالث 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'استثمارات بين $25 و $1,000. الحد الأقصى الإجمالي للجولة: $250,000.'
        ],
        'Ukrainian' => [
            'Investment Package:' => 'Інвестиційний Пакет:',
            'Shovel - $25.00' => 'Лопата - $25,00',
            'Calculate Returns' => 'Розрахувати Прибутковість',
            'View Investment Plans' => 'Переглянути Інвестиційні Плани',
            'Total Yield' => 'Загальна Прибутковість',
            'Aureus Shares' => 'Акції Aureus',
            'Annual Dividend' => 'Річний Дивіденд',
            'By 1 January 2026' => 'До 1 січня 2026',
            'Shovel Package' => 'Пакет Лопата',
            'Starting Q3 2026' => 'Починаючи з Q3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Інвестуючи $25,00 сьогодні, ви можете отримати $76,50 прибутковості до 1 січня 2026 року, плюс $5,00 щорічно у вигляді дивідендів починаючи з Q3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Інвестиції від $25 до $1.000. Максимальна загальна сума раунду: $250.000.'
        ],
        'Hindi' => [
            'Investment Package:' => 'निवेश पैकेज:',
            'Shovel - $25.00' => 'शॉवल - $25.00',
            'Calculate Returns' => 'रिटर्न की गणना करें',
            'View Investment Plans' => 'निवेश योजनाएं देखें',
            'Total Yield' => 'कुल उपज',
            'Aureus Shares' => 'Aureus शेयर',
            'Annual Dividend' => 'वार्षिक लाभांश',
            'By 1 January 2026' => '1 जनवरी 2026 तक',
            'Shovel Package' => 'शॉवल पैकेज',
            'Starting Q3 2026' => 'Q3 2026 से शुरू',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'आज $25.00 निवेश करके, आप 1 जनवरी 2026 तक $76.50 उपज प्राप्त कर सकते हैं, साथ ही Q3 2026 से शुरू होकर वार्षिक $5.00 लाभांश।',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => '$25 और $1,000 के बीच निवेश। अधिकतम कुल राउंड: $250,000।'
        ],
        'Urdu' => [
            'Investment Package:' => 'سرمایہ کاری کا پیکج:',
            'Shovel - $25.00' => 'شاول - $25.00',
            'Calculate Returns' => 'منافع کا حساب لگائیں',
            'View Investment Plans' => 'سرمایہ کاری کے منصوبے دیکھیں',
            'Total Yield' => 'کل منافع',
            'Aureus Shares' => 'Aureus شیئرز',
            'Annual Dividend' => 'سالانہ منافع',
            'By 1 January 2026' => '1 جنوری 2026 تک',
            'Shovel Package' => 'شاول پیکج',
            'Starting Q3 2026' => 'Q3 2026 سے شروع',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'آج $25.00 سرمایہ کاری کرکے، آپ 1 جنوری 2026 تک $76.50 منافع حاصل کر سکتے ہیں، اور Q3 2026 سے شروع ہوکر سالانہ $5.00 منافع۔',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => '$25 اور $1,000 کے درمیان سرمایہ کاری۔ زیادہ سے زیادہ کل راؤنڈ: $250,000۔'
        ],
        'Bengali' => [
            'Investment Package:' => 'বিনিয়োগ প্যাকেজ:',
            'Shovel - $25.00' => 'শাভেল - $25.00',
            'Calculate Returns' => 'রিটার্ন গণনা করুন',
            'View Investment Plans' => 'বিনিয়োগ পরিকল্পনা দেখুন',
            'Total Yield' => 'মোট ফলন',
            'Aureus Shares' => 'Aureus শেয়ার',
            'Annual Dividend' => 'বার্ষিক লভ্যাংশ',
            'By 1 January 2026' => '১ জানুয়ারি ২০২৬ এর মধ্যে',
            'Shovel Package' => 'শাভেল প্যাকেজ',
            'Starting Q3 2026' => 'Q3 2026 থেকে শুরু',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'আজ $25.00 বিনিয়োগ করে, আপনি ১ জানুয়ারি ২০২৬ এর মধ্যে $76.50 ফলন পেতে পারেন, এবং Q3 2026 থেকে শুরু করে বার্ষিক $5.00 লভ্যাংশ।',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => '$25 এবং $1,000 এর মধ্যে বিনিয়োগ। সর্বোচ্চ মোট রাউন্ড: $250,000।'
        ],
        'Korean' => [
            'Investment Package:' => '투자 패키지:',
            'Shovel - $25.00' => '삽 - $25.00',
            'Calculate Returns' => '수익률 계산',
            'View Investment Plans' => '투자 계획 보기',
            'Total Yield' => '총 수익률',
            'Aureus Shares' => 'Aureus 주식',
            'Annual Dividend' => '연간 배당금',
            'By 1 January 2026' => '2026년 1월 1일까지',
            'Shovel Package' => '삽 패키지',
            'Starting Q3 2026' => '2026년 3분기 시작',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => '오늘 $25.00를 투자하면 2026년 1월 1일까지 $76.50의 수익을 받을 수 있으며, 2026년 3분기부터 연간 $5.00의 배당금을 받을 수 있습니다.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => '$25에서 $1,000 사이의 투자. 라운드당 최대 총액: $250,000.'
        ],
        'Malay' => [
            'Investment Package:' => 'Pakej Pelaburan:',
            'Shovel - $25.00' => 'Penyodok - $25.00',
            'Calculate Returns' => 'Kira Pulangan',
            'View Investment Plans' => 'Lihat Pelan Pelaburan',
            'Total Yield' => 'Jumlah Hasil',
            'Aureus Shares' => 'Saham Aureus',
            'Annual Dividend' => 'Dividen Tahunan',
            'By 1 January 2026' => 'Menjelang 1 Januari 2026',
            'Shovel Package' => 'Pakej Penyodok',
            'Starting Q3 2026' => 'Bermula Q3 2026',
            'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.' => 'Dengan melabur $25.00 hari ini, anda boleh menerima $76.50 dalam hasil menjelang 1 Januari 2026, ditambah $5.00 setiap tahun dalam dividen bermula Q3 2026.',
            'Inversions between $25 and $1,000. Maximum total round: $250,000.' => 'Pelaburan antara $25 dan $1,000. Maksimum jumlah pusingan: $250,000.'
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
    
    // Investment calculator translation keys to add
    $calculatorKeys = [
        [
            'key_name' => 'calculator.investment_package',
            'category' => 'calculator',
            'description' => 'Investment Package:'
        ],
        [
            'key_name' => 'calculator.shovel_package',
            'category' => 'calculator',
            'description' => 'Shovel - $25.00'
        ],
        [
            'key_name' => 'calculator.calculate_returns',
            'category' => 'calculator',
            'description' => 'Calculate Returns'
        ],
        [
            'key_name' => 'calculator.view_investment_plans',
            'category' => 'calculator',
            'description' => 'View Investment Plans'
        ],
        [
            'key_name' => 'calculator.total_yield',
            'category' => 'calculator',
            'description' => 'Total Yield'
        ],
        [
            'key_name' => 'calculator.aureus_shares',
            'category' => 'calculator',
            'description' => 'Aureus Shares'
        ],
        [
            'key_name' => 'calculator.annual_dividend',
            'category' => 'calculator',
            'description' => 'Annual Dividend'
        ],
        [
            'key_name' => 'calculator.by_date',
            'category' => 'calculator',
            'description' => 'By 1 January 2026'
        ],
        [
            'key_name' => 'calculator.package_name',
            'category' => 'calculator',
            'description' => 'Shovel Package'
        ],
        [
            'key_name' => 'calculator.starting_date',
            'category' => 'calculator',
            'description' => 'Starting Q3 2026'
        ],
        [
            'key_name' => 'calculator.investment_summary',
            'category' => 'calculator',
            'description' => 'By investing $25.00 today, you could receive $76.50 in yield by 1 January 2026, plus $5.00 annually in dividends starting Q3 2026.'
        ],
        [
            'key_name' => 'calculator.investment_limits',
            'category' => 'calculator',
            'description' => 'Inversions between $25 and $1,000. Maximum total round: $250,000.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($calculatorKeys as $keyData) {
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
        'message' => 'Investment calculator translation keys processed successfully',
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
        'message' => 'Failed to add investment calculator translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
