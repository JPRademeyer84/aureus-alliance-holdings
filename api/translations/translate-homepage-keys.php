<?php
// Translate all homepage keys to all supported languages
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Become an' => 'Conviértete en un',
            'Angel Investor' => 'Inversionista Ángel',
            'in the Future of Digital' => 'en el Futuro del Oro',
            'Gold' => 'Digital',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Oportunidad exclusiva de pre-semilla para invertir en Aureus Alliance Holdings: combinando la minería de oro físico con coleccionables NFT digitales.',
            'Invertir Ahora' => 'Invertir Ahora',
            'Aprende Más' => 'Aprende Más',
            'Invest Now' => 'Invertir Ahora',
            'Learn More' => 'Aprende Más'
        ],
        'French' => [
            'Become an' => 'Devenez un',
            'Angel Investor' => 'Investisseur Providentiel',
            'in the Future of Digital' => 'dans l\'Avenir de l\'Or',
            'Gold' => 'Numérique',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Opportunité exclusive de pré-amorçage pour investir dans Aureus Alliance Holdings - combinant l\'extraction d\'or physique avec des objets de collection NFT numériques.',
            'Invertir Ahora' => 'Investir Maintenant',
            'Aprende Más' => 'En Savoir Plus',
            'Invest Now' => 'Investir Maintenant',
            'Learn More' => 'En Savoir Plus'
        ],
        'German' => [
            'Become an' => 'Werden Sie ein',
            'Angel Investor' => 'Angel-Investor',
            'in the Future of Digital' => 'in der Zukunft des digitalen',
            'Gold' => 'Goldes',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Exklusive Pre-Seed-Gelegenheit, in Aureus Alliance Holdings zu investieren - Kombination von physischem Goldabbau mit digitalen NFT-Sammlerstücken.',
            'Invertir Ahora' => 'Jetzt Investieren',
            'Aprende Más' => 'Mehr Erfahren',
            'Invest Now' => 'Jetzt Investieren',
            'Learn More' => 'Mehr Erfahren'
        ],
        'Portuguese' => [
            'Become an' => 'Torne-se um',
            'Angel Investor' => 'Investidor Anjo',
            'in the Future of Digital' => 'no Futuro do Ouro',
            'Gold' => 'Digital',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Oportunidade exclusiva de pré-semente para investir na Aureus Alliance Holdings - combinando mineração de ouro físico com colecionáveis NFT digitais.',
            'Invertir Ahora' => 'Investir Agora',
            'Aprende Más' => 'Saiba Mais',
            'Invest Now' => 'Investir Agora',
            'Learn More' => 'Saiba Mais'
        ],
        'Italian' => [
            'Become an' => 'Diventa un',
            'Angel Investor' => 'Investitore Angelo',
            'in the Future of Digital' => 'nel Futuro dell\'Oro',
            'Gold' => 'Digitale',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Opportunità esclusiva di pre-seed per investire in Aureus Alliance Holdings - combinando l\'estrazione di oro fisico con oggetti da collezione NFT digitali.',
            'Invertir Ahora' => 'Investi Ora',
            'Aprende Más' => 'Scopri di Più',
            'Invest Now' => 'Investi Ora',
            'Learn More' => 'Scopri di Più'
        ],
        'Russian' => [
            'Become an' => 'Станьте',
            'Angel Investor' => 'Ангел-Инвестором',
            'in the Future of Digital' => 'в Будущем Цифрового',
            'Gold' => 'Золота',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Эксклюзивная возможность предпосевных инвестиций в Aureus Alliance Holdings - сочетание физической добычи золота с цифровыми коллекционными NFT.',
            'Invertir Ahora' => 'Инвестировать Сейчас',
            'Aprende Más' => 'Узнать Больше',
            'Invest Now' => 'Инвестировать Сейчас',
            'Learn More' => 'Узнать Больше'
        ],
        'Chinese' => [
            'Become an' => '成为',
            'Angel Investor' => '天使投资者',
            'in the Future of Digital' => '在数字黄金的',
            'Gold' => '未来中',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => '独家种子前投资机会，投资Aureus Alliance Holdings - 将物理黄金开采与数字NFT收藏品相结合。',
            'Invertir Ahora' => '立即投资',
            'Aprende Más' => '了解更多',
            'Invest Now' => '立即投资',
            'Learn More' => '了解更多'
        ],
        'Japanese' => [
            'Become an' => 'エンジェル',
            'Angel Investor' => '投資家になる',
            'in the Future of Digital' => 'デジタルゴールドの',
            'Gold' => '未来へ',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Aureus Alliance Holdingsへの独占的なプレシード投資機会 - 物理的な金採掘とデジタルNFTコレクティブルを組み合わせ。',
            'Invertir Ahora' => '今すぐ投資',
            'Aprende Más' => 'もっと学ぶ',
            'Invest Now' => '今すぐ投資',
            'Learn More' => 'もっと学ぶ'
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
    
    // Get all languages except English
    $langQuery = "SELECT id, name, code FROM languages WHERE code != 'en' AND is_active = TRUE";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all homepage keys
    $keyQuery = "SELECT id, key_name, description FROM translation_keys WHERE category = 'homepage'";
    $keyStmt = $db->prepare($keyQuery);
    $keyStmt->execute();
    $keys = $keyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $translationsAdded = [];
    $translationsSkipped = [];
    
    foreach ($languages as $language) {
        foreach ($keys as $key) {
            // Check if translation already exists
            $checkQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$key['id'], $language['id']]);
            
            if ($checkStmt->fetch()) {
                $translationsSkipped[] = $key['key_name'] . ' (' . $language['code'] . ')';
                continue;
            }
            
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Homepage translations completed successfully',
        'results' => [
            'translations_added' => $translationsAdded,
            'translations_skipped' => $translationsSkipped,
            'added_count' => count($translationsAdded),
            'skipped_count' => count($translationsSkipped),
            'languages_processed' => count($languages),
            'keys_processed' => count($keys)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to translate homepage keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
