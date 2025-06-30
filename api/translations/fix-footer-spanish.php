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
    
    // Get Spanish language ID
    $spanishLangQuery = "SELECT id FROM languages WHERE code = 'es'";
    $spanishLangStmt = $db->prepare($spanishLangQuery);
    $spanishLangStmt->execute();
    $spanishLang = $spanishLangStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spanishLang) {
        throw new Exception('Spanish language not found in database');
    }
    
    // Footer translations that need to be fixed
    $footerFixes = [
        'footer.company_description' => 'El futuro de la minería de oro se encuentra con la innovación blockchain, los coleccionables NFT y los juegos inmersivos.',
        'footer.contact_us' => 'Contáctanos',
        'footer.investment_inquiries' => 'Para consultas de inversión:',
        'footer.quick_links' => 'Enlaces Rápidos',
        'footer.rights_reserved' => 'Todos los derechos reservados.'
    ];
    
    $updatedKeys = [];
    $skippedKeys = [];
    
    foreach ($footerFixes as $keyName => $spanishTranslation) {
        // Get the translation key ID
        $keyQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute([$keyName]);
        $keyResult = $keyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$keyResult) {
            $skippedKeys[] = $keyName . ' (key not found)';
            continue;
        }
        
        // Update the Spanish translation
        $updateQuery = "UPDATE translations SET translation_text = ?, is_approved = TRUE WHERE key_id = ? AND language_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $success = $updateStmt->execute([$spanishTranslation, $keyResult['id'], $spanishLang['id']]);
        
        if ($success) {
            $updatedKeys[] = $keyName;
        } else {
            $skippedKeys[] = $keyName . ' (update failed)';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Footer Spanish translations fixed successfully',
        'updated_keys' => $updatedKeys,
        'skipped_keys' => $skippedKeys,
        'total_updated' => count($updatedKeys),
        'total_skipped' => count($skippedKeys)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fixing footer Spanish translations: ' . $e->getMessage()
    ]);
}
?>
