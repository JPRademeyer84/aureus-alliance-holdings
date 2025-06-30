<?php
// Verify dashboard translation keys were created successfully
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get total translation keys count
    $totalKeysQuery = "SELECT COUNT(*) as total FROM translation_keys";
    $totalKeysStmt = $db->prepare($totalKeysQuery);
    $totalKeysStmt->execute();
    $totalKeys = $totalKeysStmt->fetchColumn();
    
    // Get translation keys by category
    $categoriesQuery = "SELECT category, COUNT(*) as count FROM translation_keys GROUP BY category ORDER BY category";
    $categoriesStmt = $db->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total translations count
    $totalTranslationsQuery = "SELECT COUNT(*) as total FROM translations";
    $totalTranslationsStmt = $db->prepare($totalTranslationsQuery);
    $totalTranslationsStmt->execute();
    $totalTranslations = $totalTranslationsStmt->fetchColumn();
    
    // Get translations by language
    $languagesQuery = "SELECT l.name, l.code, COUNT(t.id) as translation_count 
                      FROM languages l 
                      LEFT JOIN translations t ON l.id = t.language_id 
                      GROUP BY l.id, l.name, l.code 
                      ORDER BY l.name";
    $languagesStmt = $db->prepare($languagesQuery);
    $languagesStmt->execute();
    $languages = $languagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample dashboard keys
    $sampleKeysQuery = "SELECT tk.key_name, tk.category, tk.description,
                               te.translation_text as english_text,
                               ts.translation_text as spanish_text
                        FROM translation_keys tk
                        LEFT JOIN translations te ON tk.id = te.key_id AND te.language_id = (SELECT id FROM languages WHERE code = 'en' LIMIT 1)
                        LEFT JOIN translations ts ON tk.id = ts.key_id AND ts.language_id = (SELECT id FROM languages WHERE code = 'es' LIMIT 1)
                        WHERE tk.category IN ('dashboard_navigation', 'dashboard_stats', 'investment_packages', 'payment', 'affiliate')
                        ORDER BY tk.category, tk.key_name
                        LIMIT 20";
    $sampleKeysStmt = $db->prepare($sampleKeysQuery);
    $sampleKeysStmt->execute();
    $sampleKeys = $sampleKeysStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for missing Spanish translations
    $missingSpanishQuery = "SELECT tk.key_name, tk.category 
                           FROM translation_keys tk
                           LEFT JOIN translations ts ON tk.id = ts.key_id AND ts.language_id = (SELECT id FROM languages WHERE code = 'es' LIMIT 1)
                           WHERE ts.id IS NULL
                           ORDER BY tk.category, tk.key_name";
    $missingSpanishStmt = $db->prepare($missingSpanishQuery);
    $missingSpanishStmt->execute();
    $missingSpanish = $missingSpanishStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'verification_results' => [
            'total_translation_keys' => $totalKeys,
            'total_translations' => $totalTranslations,
            'categories' => $categories,
            'languages' => $languages,
            'sample_keys' => $sampleKeys,
            'missing_spanish_translations' => count($missingSpanish),
            'missing_spanish_keys' => array_slice($missingSpanish, 0, 10) // Show first 10 missing
        ],
        'status' => [
            'dashboard_keys_created' => $totalKeys > 100 ? 'SUCCESS' : 'INCOMPLETE',
            'spanish_translations_created' => count($missingSpanish) < 10 ? 'SUCCESS' : 'INCOMPLETE',
            'ready_for_implementation' => $totalKeys > 100 && count($missingSpanish) < 10 ? 'YES' : 'NO'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
