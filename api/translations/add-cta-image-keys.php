<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // CTA translation keys from the image provided by user
    $ctaImageTranslations = [
        // Main CTA text from image
        ['cta.become_angel_investor_today', 'Become an Angel Investor Today', 'Conviértete en un Inversionista Ángel Hoy', 'cta'],
        ['cta.only_250k_preseed_available', 'Only $250,000 of pre-seed investment available. Secure your position before the opportunity closes.', 'Solo $250,000 de inversión pre-semilla disponible. Asegura tu posición antes de que se cierre la oportunidad.', 'cta'],
        ['cta.invest_now_button', 'Invest Now', 'Invertir Ahora', 'cta'],
        ['cta.10x_yield_january_2026', '10x yield by 1 January 2026. Inversion closes when we reach our $250,000 cap or when NFT presale begins in June.', 'Rendimiento 10x para el 1 de enero de 2026. La inversión se cierra cuando alcancemos nuestro límite de $250,000 o cuando comience la preventa NFT en junio.', 'cta'],
        
        // Individual components for flexibility
        ['cta.only', 'Only', 'Solo', 'cta'],
        ['cta.250k_amount', '$250,000', '$250,000', 'cta'],
        ['cta.preseed_investment', 'pre-seed investment', 'inversión pre-semilla', 'cta'],
        ['cta.available', 'available', 'disponible', 'cta'],
        ['cta.secure_position', 'Secure your position', 'Asegura tu posición', 'cta'],
        ['cta.before_opportunity_closes', 'before the opportunity closes', 'antes de que se cierre la oportunidad', 'cta'],
        ['cta.10x_yield', '10x yield', 'Rendimiento 10x', 'cta'],
        ['cta.by_january_1_2026', 'by 1 January 2026', 'para el 1 de enero de 2026', 'cta'],
        ['cta.inversion_closes', 'Inversion closes', 'La inversión se cierra', 'cta'],
        ['cta.when_reach_cap', 'when we reach our $250,000 cap', 'cuando alcancemos nuestro límite de $250,000', 'cta'],
        ['cta.or_when_nft_presale', 'or when NFT presale begins in June', 'o cuando comience la preventa NFT en junio', 'cta'],
        
        // Additional CTA related terms
        ['cta.opportunity', 'opportunity', 'oportunidad', 'cta'],
        ['cta.closes', 'closes', 'se cierra', 'cta'],
        ['cta.reach', 'reach', 'alcancemos', 'cta'],
        ['cta.cap', 'cap', 'límite', 'cta'],
        ['cta.nft_presale', 'NFT presale', 'preventa NFT', 'cta'],
        ['cta.begins', 'begins', 'comience', 'cta'],
        ['cta.june', 'June', 'junio', 'cta'],
        ['cta.january', 'January', 'enero', 'cta'],
        ['cta.yield', 'yield', 'rendimiento', 'cta'],
        ['cta.investment', 'investment', 'inversión', 'cta'],
        ['cta.position', 'position', 'posición', 'cta'],
        ['cta.secure', 'secure', 'asegurar', 'cta'],
        ['cta.angel_investor', 'Angel Investor', 'Inversionista Ángel', 'cta'],
        ['cta.become', 'Become', 'Conviértete', 'cta'],
        ['cta.today', 'Today', 'Hoy', 'cta'],
        ['cta.invest', 'Invest', 'Invertir', 'cta'],
        ['cta.now', 'Now', 'Ahora', 'cta']
    ];
    
    // Get language IDs
    $englishLangQuery = "SELECT id FROM languages WHERE code = 'en'";
    $englishLangStmt = $db->prepare($englishLangQuery);
    $englishLangStmt->execute();
    $englishLang = $englishLangStmt->fetch(PDO::FETCH_ASSOC);
    
    $spanishLangQuery = "SELECT id FROM languages WHERE code = 'es'";
    $spanishLangStmt = $db->prepare($spanishLangQuery);
    $spanishLangStmt->execute();
    $spanishLang = $spanishLangStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$englishLang || !$spanishLang) {
        throw new Exception('English or Spanish language not found in database');
    }
    
    $addedKeys = [];
    $skippedKeys = [];
    
    foreach ($ctaImageTranslations as $ctaData) {
        list($keyName, $englishText, $spanishText, $category) = $ctaData;
        
        // Check if key already exists
        $checkQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyName]);
        
        if ($checkStmt->fetch()) {
            $skippedKeys[] = $keyName;
            continue;
        }
        
        // Insert new translation key
        $insertQuery = "INSERT INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$keyName, $englishText, $category]);
        
        $keyId = $db->lastInsertId();
        
        // Add English translation
        $englishTranslationQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) VALUES (?, ?, ?, TRUE)";
        $englishTranslationStmt = $db->prepare($englishTranslationQuery);
        $englishTranslationStmt->execute([$keyId, $englishLang['id'], $englishText]);
        
        // Add Spanish translation
        $spanishTranslationQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) VALUES (?, ?, ?, TRUE)";
        $spanishTranslationStmt = $db->prepare($spanishTranslationQuery);
        $spanishTranslationStmt->execute([$keyId, $spanishLang['id'], $spanishText]);
        
        $addedKeys[] = $keyName;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'CTA image translation keys added successfully',
        'added_keys' => $addedKeys,
        'skipped_keys' => $skippedKeys,
        'total_added' => count($addedKeys),
        'total_skipped' => count($skippedKeys)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding CTA image translation keys: ' . $e->getMessage()
    ]);
}
?>
