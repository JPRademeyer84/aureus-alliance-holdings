<?php
// Add Gold Diggers Club translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'Compite por el premio final! Los mejores participantes comparten $250,000 en bonos basados en volumen de referidos y crecimiento de red.',
            'How It Works' => 'Cómo Funciona',
            'Refer & Earn' => 'Refiere y Gana',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'Construye tu red refiriendo nuevos inversionistas. Cada referido calificado cuenta para tu clasificación.',
            'Minimum Qualification' => 'Calificación Mínima',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'Alcanza un mínimo de $2,500 en volumen directo de referidos para calificar para la distribución del fondo de bonos.',
            'Climb the Rankings' => 'Escala en las Clasificaciones',
            'Your position is determined by total referral volume and network growth metrics.' => 'Tu posición se determina por el volumen total de referidos y métricas de crecimiento de red.',
            'Prize Distribution' => 'Distribución de Premios',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'El fondo restante se distribuye proporcionalmente entre los 10 participantes calificados principales',
            'Join the Competition' => 'Únete a la Competencia',
            'Competition ends when presale reaches $250,000 total volume' => 'La competencia termina cuando la preventa alcance $250,000 de volumen total',
            'Live Rankings' => 'Clasificaciones en Vivo',
            'LIVE' => 'EN VIVO',
            'Qualified' => 'Calificado',
            'Total Participants' => 'Participantes Totales',
            'Leading Volume' => 'Volumen Líder'
        ],
        'French' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'Concourez pour le prix ultime ! Les meilleurs participants partagent 250 000 $ de bonus basés sur le volume de parrainage et la croissance du réseau.',
            'How It Works' => 'Comment Ça Marche',
            'Refer & Earn' => 'Parrainez et Gagnez',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'Construisez votre réseau en parrainant de nouveaux investisseurs. Chaque parrainage qualifié compte pour votre classement.',
            'Minimum Qualification' => 'Qualification Minimale',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'Atteignez un minimum de 2 500 $ en volume de parrainage direct pour vous qualifier pour la distribution du pool de bonus.',
            'Climb the Rankings' => 'Grimpez dans les Classements',
            'Your position is determined by total referral volume and network growth metrics.' => 'Votre position est déterminée par le volume total de parrainage et les métriques de croissance du réseau.',
            'Prize Distribution' => 'Distribution des Prix',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'Le pool restant est distribué proportionnellement parmi les 10 participants qualifiés principaux',
            'Join the Competition' => 'Rejoignez la Compétition',
            'Competition ends when presale reaches $250,000 total volume' => 'La compétition se termine lorsque la prévente atteint 250 000 $ de volume total',
            'Live Rankings' => 'Classements en Direct',
            'LIVE' => 'EN DIRECT',
            'Qualified' => 'Qualifié',
            'Total Participants' => 'Participants Totaux',
            'Leading Volume' => 'Volume Principal'
        ],
        'German' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'Kämpfen Sie um den ultimativen Preispool! Top-Performer teilen sich $250.000 an Boni basierend auf Empfehlungsvolumen und Netzwerkwachstum.',
            'How It Works' => 'Wie Es Funktioniert',
            'Refer & Earn' => 'Empfehlen & Verdienen',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'Bauen Sie Ihr Netzwerk auf, indem Sie neue Investoren empfehlen. Jede qualifizierte Empfehlung zählt für Ihr Ranking.',
            'Minimum Qualification' => 'Mindestqualifikation',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'Erreichen Sie mindestens $2.500 an direktem Empfehlungsvolumen, um sich für die Bonus-Pool-Verteilung zu qualifizieren.',
            'Climb the Rankings' => 'Steigen Sie in den Rankings',
            'Your position is determined by total referral volume and network growth metrics.' => 'Ihre Position wird durch das gesamte Empfehlungsvolumen und Netzwerkwachstumsmetriken bestimmt.',
            'Prize Distribution' => 'Preisverteilung',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'Der verbleibende Pool wird proportional unter den Top 10 qualifizierten Teilnehmern verteilt',
            'Join the Competition' => 'Treten Sie der Konkurrenz bei',
            'Competition ends when presale reaches $250,000 total volume' => 'Der Wettbewerb endet, wenn der Vorverkauf $250.000 Gesamtvolumen erreicht',
            'Live Rankings' => 'Live-Rankings',
            'LIVE' => 'LIVE',
            'Qualified' => 'Qualifiziert',
            'Total Participants' => 'Gesamtteilnehmer',
            'Leading Volume' => 'Führendes Volumen'
        ],
        'Portuguese' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'Compita pelo prêmio final! Os melhores participantes compartilham $250.000 em bônus baseados no volume de indicações e crescimento da rede.',
            'How It Works' => 'Como Funciona',
            'Refer & Earn' => 'Indique e Ganhe',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'Construa sua rede indicando novos investidores. Cada indicação qualificada conta para sua classificação.',
            'Minimum Qualification' => 'Qualificação Mínima',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'Alcance um mínimo de $2.500 em volume direto de indicações para se qualificar para a distribuição do pool de bônus.',
            'Climb the Rankings' => 'Suba nas Classificações',
            'Your position is determined by total referral volume and network growth metrics.' => 'Sua posição é determinada pelo volume total de indicações e métricas de crescimento da rede.',
            'Prize Distribution' => 'Distribuição de Prêmios',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'O pool restante é distribuído proporcionalmente entre os 10 participantes qualificados principais',
            'Join the Competition' => 'Junte-se à Competição',
            'Competition ends when presale reaches $250,000 total volume' => 'A competição termina quando a pré-venda atingir $250.000 de volume total',
            'Live Rankings' => 'Rankings ao Vivo',
            'LIVE' => 'AO VIVO',
            'Qualified' => 'Qualificado',
            'Total Participants' => 'Participantes Totais',
            'Leading Volume' => 'Volume Líder'
        ],
        'Italian' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'Competi per il premio finale! I migliori performer condividono $250.000 in bonus basati sul volume di referral e crescita della rete.',
            'How It Works' => 'Come Funziona',
            'Refer & Earn' => 'Riferisci e Guadagna',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'Costruisci la tua rete riferendo nuovi investitori. Ogni referral qualificato conta per la tua classifica.',
            'Minimum Qualification' => 'Qualificazione Minima',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'Raggiungi un minimo di $2.500 in volume diretto di referral per qualificarti per la distribuzione del pool bonus.',
            'Climb the Rankings' => 'Scala le Classifiche',
            'Your position is determined by total referral volume and network growth metrics.' => 'La tua posizione è determinata dal volume totale di referral e metriche di crescita della rete.',
            'Prize Distribution' => 'Distribuzione Premi',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'Il pool rimanente è distribuito proporzionalmente tra i 10 partecipanti qualificati principali',
            'Join the Competition' => 'Unisciti alla Competizione',
            'Competition ends when presale reaches $250,000 total volume' => 'La competizione termina quando la prevendita raggiunge $250.000 di volume totale',
            'Live Rankings' => 'Classifiche Live',
            'LIVE' => 'LIVE',
            'Qualified' => 'Qualificato',
            'Total Participants' => 'Partecipanti Totali',
            'Leading Volume' => 'Volume Leader'
        ],
        'Russian' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'Соревнуйтесь за главный призовой фонд! Лучшие участники делят $250,000 бонусов на основе объема рефералов и роста сети.',
            'How It Works' => 'Как Это Работает',
            'Refer & Earn' => 'Приглашайте и Зарабатывайте',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'Создавайте свою сеть, приглашая новых инвесторов. Каждый квалифицированный реферал засчитывается в ваш рейтинг.',
            'Minimum Qualification' => 'Минимальная Квалификация',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'Достигните минимум $2,500 прямого объема рефералов для квалификации на распределение бонусного фонда.',
            'Climb the Rankings' => 'Поднимайтесь в Рейтинге',
            'Your position is determined by total referral volume and network growth metrics.' => 'Ваша позиция определяется общим объемом рефералов и метриками роста сети.',
            'Prize Distribution' => 'Распределение Призов',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'Оставшийся фонд распределяется пропорционально среди топ-10 квалифицированных участников',
            'Join the Competition' => 'Присоединиться к Конкурсу',
            'Competition ends when presale reaches $250,000 total volume' => 'Конкурс заканчивается, когда предпродажа достигнет $250,000 общего объема',
            'Live Rankings' => 'Живой Рейтинг',
            'LIVE' => 'ПРЯМОЙ ЭФИР',
            'Qualified' => 'Квалифицирован',
            'Total Participants' => 'Всего Участников',
            'Leading Volume' => 'Ведущий Объем'
        ],
        'Chinese' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => '争夺终极奖池！顶级表现者根据推荐量和网络增长分享$250,000奖金。',
            'How It Works' => '运作方式',
            'Refer & Earn' => '推荐赚钱',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => '通过推荐新投资者建立您的网络。每个合格的推荐都计入您的排名。',
            'Minimum Qualification' => '最低资格',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => '达到最低$2,500的直接推荐量以获得奖金池分配资格。',
            'Climb the Rankings' => '攀登排名',
            'Your position is determined by total referral volume and network growth metrics.' => '您的位置由总推荐量和网络增长指标决定。',
            'Prize Distribution' => '奖金分配',
            'Remaining pool distributed proportionally among top 10 qualified participants' => '剩余奖池按比例分配给前10名合格参与者',
            'Join the Competition' => '加入竞赛',
            'Competition ends when presale reaches $250,000 total volume' => '当预售达到$250,000总量时竞赛结束',
            'Live Rankings' => '实时排名',
            'LIVE' => '直播',
            'Qualified' => '合格',
            'Total Participants' => '总参与者',
            'Leading Volume' => '领先量'
        ],
        'Japanese' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => '究極の賞金プールを競い合いましょう！トップパフォーマーは紹介量とネットワーク成長に基づいて$250,000のボーナスを共有します。',
            'How It Works' => '仕組み',
            'Refer & Earn' => '紹介して稼ぐ',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => '新しい投資家を紹介してネットワークを構築します。各適格な紹介はランキングにカウントされます。',
            'Minimum Qualification' => '最低資格',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'ボーナスプール配布の資格を得るために、最低$2,500の直接紹介量を達成してください。',
            'Climb the Rankings' => 'ランキングを上る',
            'Your position is determined by total referral volume and network growth metrics.' => 'あなたのポジションは総紹介量とネットワーク成長指標によって決定されます。',
            'Prize Distribution' => '賞金配布',
            'Remaining pool distributed proportionally among top 10 qualified participants' => '残りのプールはトップ10の適格参加者に比例配分されます',
            'Join the Competition' => '競争に参加',
            'Competition ends when presale reaches $250,000 total volume' => 'プレセールが$250,000の総量に達すると競争は終了します',
            'Live Rankings' => 'ライブランキング',
            'LIVE' => 'ライブ',
            'Qualified' => '適格',
            'Total Participants' => '総参加者',
            'Leading Volume' => 'リーディングボリューム'
        ],
        'Arabic' => [
            'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.' => 'تنافس على مجموعة الجوائز النهائية! أفضل المؤدين يتشاركون $250,000 في المكافآت بناءً على حجم الإحالات ونمو الشبكة.',
            'How It Works' => 'كيف يعمل',
            'Refer & Earn' => 'أحل واكسب',
            'Build your network by referring new investors. Each qualified referral counts toward your ranking.' => 'ابن شبكتك من خلال إحالة مستثمرين جدد. كل إحالة مؤهلة تحتسب في ترتيبك.',
            'Minimum Qualification' => 'الحد الأدنى للتأهيل',
            'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.' => 'حقق حداً أدنى قدره $2,500 في حجم الإحالات المباشرة للتأهل لتوزيع مجموعة المكافآت.',
            'Climb the Rankings' => 'تسلق التصنيفات',
            'Your position is determined by total referral volume and network growth metrics.' => 'يتم تحديد موقعك من خلال إجمالي حجم الإحالات ومقاييس نمو الشبكة.',
            'Prize Distribution' => 'توزيع الجوائز',
            'Remaining pool distributed proportionally among top 10 qualified participants' => 'يتم توزيع المجموعة المتبقية بالتناسب بين أفضل 10 مشاركين مؤهلين',
            'Join the Competition' => 'انضم للمنافسة',
            'Competition ends when presale reaches $250,000 total volume' => 'تنتهي المنافسة عندما يصل البيع المسبق إلى $250,000 حجم إجمالي',
            'Live Rankings' => 'التصنيفات المباشرة',
            'LIVE' => 'مباشر',
            'Qualified' => 'مؤهل',
            'Total Participants' => 'إجمالي المشاركين',
            'Leading Volume' => 'الحجم الرائد'
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
    
    // Gold Diggers Club translation keys to add
    $goldDiggersKeys = [
        [
            'key_name' => 'leaderboard.competition_description',
            'category' => 'leaderboard',
            'description' => 'Compete for the ultimate prize pool! Top performers share $250,000 in bonuses based on referral volume and network growth.'
        ],
        [
            'key_name' => 'leaderboard.how_it_works',
            'category' => 'leaderboard',
            'description' => 'How It Works'
        ],
        [
            'key_name' => 'leaderboard.step1_title',
            'category' => 'leaderboard',
            'description' => 'Refer & Earn'
        ],
        [
            'key_name' => 'leaderboard.step1_desc',
            'category' => 'leaderboard',
            'description' => 'Build your network by referring new investors. Each qualified referral counts toward your ranking.'
        ],
        [
            'key_name' => 'leaderboard.step2_title',
            'category' => 'leaderboard',
            'description' => 'Minimum Qualification'
        ],
        [
            'key_name' => 'leaderboard.step2_desc',
            'category' => 'leaderboard',
            'description' => 'Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution.'
        ],
        [
            'key_name' => 'leaderboard.step3_title',
            'category' => 'leaderboard',
            'description' => 'Climb the Rankings'
        ],
        [
            'key_name' => 'leaderboard.step3_desc',
            'category' => 'leaderboard',
            'description' => 'Your position is determined by total referral volume and network growth metrics.'
        ],
        [
            'key_name' => 'leaderboard.prize_distribution',
            'category' => 'leaderboard',
            'description' => 'Prize Distribution'
        ],
        [
            'key_name' => 'leaderboard.distribution_note',
            'category' => 'leaderboard',
            'description' => 'Remaining pool distributed proportionally among top 10 qualified participants'
        ],
        [
            'key_name' => 'leaderboard.join_competition',
            'category' => 'leaderboard',
            'description' => 'Join the Competition'
        ],
        [
            'key_name' => 'leaderboard.competition_ends',
            'category' => 'leaderboard',
            'description' => 'Competition ends when presale reaches $250,000 total volume'
        ],
        [
            'key_name' => 'leaderboard.live_rankings',
            'category' => 'leaderboard',
            'description' => 'Live Rankings'
        ],
        [
            'key_name' => 'leaderboard.live',
            'category' => 'leaderboard',
            'description' => 'LIVE'
        ],
        [
            'key_name' => 'leaderboard.qualified',
            'category' => 'leaderboard',
            'description' => 'Qualified'
        ],
        [
            'key_name' => 'leaderboard.total_participants',
            'category' => 'leaderboard',
            'description' => 'Total Participants'
        ],
        [
            'key_name' => 'leaderboard.leading_volume',
            'category' => 'leaderboard',
            'description' => 'Leading Volume'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($goldDiggersKeys as $keyData) {
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
        'message' => 'Gold Diggers Club translation keys processed successfully',
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
        'message' => 'Failed to add Gold Diggers Club translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
