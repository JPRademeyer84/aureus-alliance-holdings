<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Footer translation keys from the user's screenshot
    $footerTranslations = [
        // Main footer content
        ['footer.company_description', 'The future of gold mining meets blockchain innovation, NFT collectibles, and immersive gaming.', 'El futuro de la minería de oro se encuentra con la innovación blockchain, los coleccionables NFT y los juegos inmersivos.', 'footer'],
        ['footer.quick_links', 'Quick Links', 'Enlaces Rápidos', 'footer'],
        ['footer.contact_us', 'Contact Us', 'Contáctanos', 'footer'],
        ['footer.investment_inquiries', 'For investment inquiries:', 'Para consultas de inversión:', 'footer'],
        ['footer.investment', 'Investment', 'Inversión', 'footer'],
        ['footer.benefits', 'Benefits', 'Beneficios', 'footer'],
        ['footer.about', 'About', 'Acerca de', 'footer'],
        ['footer.contact', 'Contact', 'Contacto', 'footer'],
        ['footer.rights_reserved', 'All rights reserved.', 'Todos los derechos reservados.', 'footer'],
        ['footer.investment_risk_disclaimer', 'Investment opportunities involve risk. Please consult with a professional financial advisor before investing.', 'Las oportunidades de inversión implican riesgo. Por favor consulte con un asesor financiero profesional antes de invertir.', 'footer'],
        ['footer.company_name', 'Aureus Alliance Holdings', 'Aureus Alliance Holdings', 'footer']
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

    foreach ($footerTranslations as $footerData) {
        list($keyName, $englishText, $spanishText, $category) = $footerData;

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
        'message' => 'Footer translation keys added successfully',
        'added_keys' => $addedKeys,
        'skipped_keys' => $skippedKeys,
        'total_added' => count($addedKeys),
        'total_skipped' => count($skippedKeys)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding footer translation keys: ' . $e->getMessage()
    ]);
}
?>
