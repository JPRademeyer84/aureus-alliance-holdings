<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get Spanish language ID
    $langQuery = "SELECT id FROM languages WHERE code = 'es'";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $spanishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spanishLang) {
        throw new Exception('Spanish language not found');
    }
    
    $spanishId = $spanishLang['id'];
    
    // Spanish translations
    $spanishTranslations = [
        ['nav.investment', 'Inversión'],
        ['nav.affiliate', 'Afiliado'],
        ['nav.benefits', 'Beneficios'],
        ['nav.about', 'Acerca de'],
        ['nav.contact', 'Contacto'],
        ['nav.sign_in', 'Iniciar Sesión'],
        ['hero.title', 'Conviértete en un Inversionista Ángel'],
        ['hero.subtitle', 'en el Futuro del Oro Digital'],
        ['hero.invest_now', 'Invertir Ahora'],
        ['hero.learn_more', 'Aprende Más'],
        ['stats.yield_investment', 'Rendimiento de la Inversión'],
        ['stats.annual_share', 'Anual por Acción'],
        ['stats.affiliate_commission', 'Comisión de Afiliado'],
        ['stats.nft_presale', 'Lanzamiento de Preventa NFT'],
        ['benefits.affiliate_program', 'Programa de Afiliados'],
        ['benefits.description', 'Como partidario temprano de la Alianza Ángel Aureus, recibirás ventajas incomparables que no estarán disponibles después de nuestro lanzamiento público.'],
        ['benefits.gaming_integration', 'Integración de Juegos'],
        ['benefits.gold_dividends', 'Dividendos de Mina de Oro'],
        ['benefits.limited_offer', 'Oferta Limitada']
    ];
    
    $results = [];
    
    // Insert or update Spanish translations
    $sql = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
            SELECT tk.id, ?, ?, TRUE 
            FROM translation_keys tk 
            WHERE tk.key_name = ?
            ON DUPLICATE KEY UPDATE 
            translation_text = VALUES(translation_text),
            is_approved = TRUE,
            updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $db->prepare($sql);
    
    foreach ($spanishTranslations as $trans) {
        $stmt->execute([$spanishId, $trans[1], $trans[0]]);
        $results[] = "Added/Updated: " . $trans[0] . " → " . $trans[1];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Spanish translations added successfully!',
        'count' => count($spanishTranslations),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to add Spanish translations'
    ]);
}
?>
