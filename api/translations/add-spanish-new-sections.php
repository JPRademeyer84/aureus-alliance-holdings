<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Spanish translations for new sections
    $spanishTranslations = [
        // Commission Section
        ['commission.title', 'Comisión de Networker'],
        ['commission.structure', 'Estructura'],
        ['commission.plan_type', 'Plan Unilevel de 3 Niveles'],
        ['commission.description', 'Gana recompensas duales en bonos USDT + NFT Pack a través de nuestra estructura de comisiones transparente de 3 niveles.'],
        ['commission.structure_title', 'Estructura de Comisiones'],
        ['commission.example_title', 'Cálculo de Ejemplo'],
        ['commission.benefits_title', 'Beneficios Clave'],
        ['commission.benefit1_title', 'Sistema de Recompensa Dual'],
        ['commission.benefit1_desc', 'Gana tanto comisiones USDT como bonos de paquetes NFT para máximo valor.'],
        ['commission.benefit2_title', '3 Niveles de Profundidad'],
        ['commission.benefit2_desc', 'Construye una red sostenible con recompensas de 3 niveles de referidos.'],
        ['commission.benefit3_title', 'Pagos Instantáneos'],
        ['commission.benefit3_desc', 'Recibe comisiones USDT inmediatamente tras ventas exitosas de referidos.'],
        ['commission.benefit4_title', 'Propiedad NFT'],
        ['commission.benefit4_desc', 'Los bonos NFT proporcionan propiedad real en operaciones de minería de oro con dividendos futuros.'],
        ['commission.pool_title', 'Pool Total de Comisiones'],
        ['commission.pool_note', 'Pool de comisiones financiado por ingresos de preventa, asegurando recompensas sostenibles.'],
        
        // ROI Section
        ['roi.title', 'ROI del Inversionista'],
        ['roi.model', 'Modelo'],
        ['roi.duration', 'Duración de 180 Días'],
        ['roi.funding', 'Financiado por Ventas Principales'],
        ['roi.description', 'Elige entre 8 paquetes de inversión con ROI diario garantizado durante 180 días, más acciones NFT para dividendos a largo plazo.'],
        ['roi.how_it_works', 'Cómo Funciona el ROI'],
        ['roi.step1_title', 'Elige Tu Paquete'],
        ['roi.step1_desc', 'Selecciona entre 8 paquetes de inversión que van desde $25 a $1,000 según tu presupuesto.'],
        ['roi.step2_title', 'Pagos Diarios de ROI'],
        ['roi.step2_desc', 'Recibe pagos diarios de ROI que van del 1.7% al 5% durante 180 días consecutivos.'],
        ['roi.step3_title', 'Recibe Acciones NFT'],
        ['roi.step3_desc', 'Después de 180 días, recibe tus acciones NFT que representan propiedad en operaciones de minería de oro.'],
        ['roi.guarantee_title', 'Garantía de ROI'],
        ['roi.guarantee1', 'ROI financiado por ingresos de ventas principales futuras'],
        ['roi.guarantee2', 'Sistema de pagos transparente basado en blockchain'],
        ['roi.guarantee3', 'Respaldado por operaciones reales de minería de oro'],
        ['roi.benefits_title', 'Beneficios de Inversión'],
        ['roi.benefit1_title', 'Altos Retornos Diarios'],
        ['roi.benefit1_desc', 'Gana hasta 5% de ROI diario con nuestro paquete premium Aureus para máximos retornos.'],
        ['roi.benefit2_title', 'Propiedad NFT'],
        ['roi.benefit2_desc', 'Recibe acciones NFT que representan propiedad real en operaciones de minería de oro.'],
        ['roi.benefit3_title', 'Dividendos Futuros'],
        ['roi.benefit3_desc', 'Las acciones NFT proporcionan dividendos continuos de ganancias de minería de oro después del período ROI.'],
        ['roi.benefit4_title', 'Término Fijo de 180 Días'],
        ['roi.benefit4_desc', 'Cronograma claro con pagos diarios garantizados por exactamente 180 días.'],
        ['roi.timeline_title', 'Cronograma de Inversión'],
        
        // Leaderboard Section
        ['leaderboard.title', 'Club de Buscadores de Oro'],
        ['leaderboard.bonus_pool', 'POOL DE BONOS'],
        ['leaderboard.description', 'Competencia especial de tabla de clasificación para los Top 10 Vendedores Directos en la preventa. Mínimo $2,500 en referidos directos para calificar.'],
        ['leaderboard.how_it_works', 'Cómo Funciona'],
        ['leaderboard.prize_distribution', 'Distribución de Premios'],
        ['leaderboard.join_competition', 'Únete a la Competencia'],
        ['leaderboard.live_rankings', 'Clasificaciones en Vivo'],
        ['leaderboard.live', 'EN VIVO'],
        ['leaderboard.total_participants', 'Total de Participantes'],
        ['leaderboard.leading_volume', 'Volumen Líder'],
        
        // Common terms
        ['common.level', 'Nivel'],
        ['common.daily_roi', 'ROI Diario'],
        ['common.total_roi', 'ROI Total'],
        ['common.nft_shares', 'Acciones NFT'],
        ['common.total_return', 'Retorno Total'],
        ['common.usdt_commission', 'Comisión USDT'],
        ['common.nft_pack_bonus', 'Bono de Paquete NFT'],
        ['common.earns', 'Gana'],
        ['common.day', 'Día'],
        ['common.ongoing', 'Continuo'],
        ['common.investment', 'Inversión'],
        ['common.first_roi_payment', 'Primer Pago ROI'],
        ['common.final_roi_payment', 'Pago Final ROI'],
        ['common.nft_dividends_mining', 'Dividendos NFT de Minería']
    ];
    
    // Get Spanish language ID
    $langQuery = "SELECT id FROM languages WHERE code = 'es'";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $spanishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spanishLang) {
        throw new Exception('Spanish language not found in database');
    }
    
    $spanishLangId = $spanishLang['id'];
    $addedTranslations = [];
    $skippedTranslations = [];
    
    foreach ($spanishTranslations as $translationData) {
        list($keyName, $translationText) = $translationData;
        
        // Get key ID
        $keyQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute([$keyName]);
        $key = $keyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$key) {
            $skippedTranslations[] = $keyName . ' (key not found)';
            continue;
        }
        
        $keyId = $key['id'];
        
        // Check if translation already exists
        $checkQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyId, $spanishLangId]);
        
        if ($checkStmt->fetch()) {
            // Update existing translation
            $updateQuery = "UPDATE translations SET translation_text = ?, is_approved = TRUE, updated_at = CURRENT_TIMESTAMP WHERE key_id = ? AND language_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$translationText, $keyId, $spanishLangId]);
            $addedTranslations[] = $keyName . ' (updated)';
        } else {
            // Insert new translation
            $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) VALUES (?, ?, ?, TRUE)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$keyId, $spanishLangId, $translationText]);
            $addedTranslations[] = $keyName . ' (new)';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Spanish translations added successfully',
        'added_translations' => $addedTranslations,
        'skipped_translations' => $skippedTranslations,
        'total_added' => count($addedTranslations),
        'total_skipped' => count($skippedTranslations)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding Spanish translations: ' . $e->getMessage()
    ]);
}
?>
