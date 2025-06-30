<?php
// Create final translation keys for complete dashboard audit completion
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get English and Spanish language IDs
    $langQuery = "SELECT id, code FROM languages WHERE code IN ('en', 'es')";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $englishId = null;
    $spanishId = null;
    
    foreach ($languages as $lang) {
        if ($lang['code'] === 'en') $englishId = $lang['id'];
        if ($lang['code'] === 'es') $spanishId = $lang['id'];
    }
    
    if (!$englishId || !$spanishId) {
        throw new Exception('English or Spanish language not found in database');
    }
    
    // Final translation keys for complete dashboard audit completion
    $translationKeys = [
        // AffiliateView - Complete remaining strings
        'level_1_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 1 Referrals', 'spanish' => 'Referencias Nivel 1'],
        'direct_referrals' => ['category' => 'affiliate_view', 'english' => 'Direct referrals', 'spanish' => 'Referencias directas'],
        'level_2_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 2 Referrals', 'spanish' => 'Referencias Nivel 2'],
        'second_level_referrals' => ['category' => 'affiliate_view', 'english' => '2nd level referrals', 'spanish' => 'Referencias de 2do nivel'],
        'level_3_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 3 Referrals', 'spanish' => 'Referencias Nivel 3'],
        'third_level_referrals' => ['category' => 'affiliate_view', 'english' => '3rd level referrals', 'spanish' => 'Referencias de 3er nivel'],
        'recent_referral_activity' => ['category' => 'affiliate_view', 'english' => 'Recent Referral Activity', 'spanish' => 'Actividad Reciente de Referencias'],
        'error_loading_referral_data' => ['category' => 'affiliate_view', 'english' => '⚠️ Error loading referral data', 'spanish' => '⚠️ Error cargando datos de referencias'],
        'try_again' => ['category' => 'affiliate_view', 'english' => 'Try Again', 'spanish' => 'Intentar de Nuevo'],
        'no_referral_activity_yet' => ['category' => 'affiliate_view', 'english' => 'No referral activity yet', 'spanish' => 'Aún no hay actividad de referencias'],
        'start_sharing_referral_link' => ['category' => 'affiliate_view', 'english' => 'Start sharing your referral link to earn commissions!', 'spanish' => '¡Comienza a compartir tu enlace de referido para ganar comisiones!'],
        'user' => ['category' => 'affiliate_view', 'english' => 'User', 'spanish' => 'Usuario'],
        'level' => ['category' => 'affiliate_view', 'english' => 'Level', 'spanish' => 'Nivel'],
        'purchase' => ['category' => 'affiliate_view', 'english' => 'Purchase', 'spanish' => 'Compra'],
        'usdt_commission' => ['category' => 'affiliate_view', 'english' => 'USDT Commission', 'spanish' => 'Comisión USDT'],
        'nft_bonus' => ['category' => 'affiliate_view', 'english' => 'NFT Bonus', 'spanish' => 'Bono NFT'],
        'status' => ['category' => 'affiliate_view', 'english' => 'Status', 'spanish' => 'Estado'],
        'date' => ['category' => 'affiliate_view', 'english' => 'Date', 'spanish' => 'Fecha'],
        'level_number' => ['category' => 'affiliate_view', 'english' => 'Level {number}', 'spanish' => 'Nivel {number}'],
        'nfts_count' => ['category' => 'affiliate_view', 'english' => '{count} NFTs', 'spanish' => '{count} NFTs'],
        'paid' => ['category' => 'affiliate_view', 'english' => 'Paid', 'spanish' => 'Pagado'],
        'pending' => ['category' => 'affiliate_view', 'english' => 'Pending', 'spanish' => 'Pendiente'],
        'cancelled' => ['category' => 'affiliate_view', 'english' => 'Cancelled', 'spanish' => 'Cancelado'],
        
        // PackagesView - Complete remaining strings (already mostly translated, but adding any missing ones)
        'multi_select' => ['category' => 'packages_view', 'english' => 'Multi-Select', 'spanish' => 'Selección Múltiple'],
        'individual' => ['category' => 'packages_view', 'english' => 'Individual', 'spanish' => 'Individual'],
        'select_multiple_packages_match_amount' => ['category' => 'packages_view', 'english' => 'Select multiple packages to match your investment amount', 'spanish' => 'Selecciona múltiples paquetes para ajustar tu monto de inversión'],
        'choose_individual_packages' => ['category' => 'packages_view', 'english' => 'Choose individual packages to invest in', 'spanish' => 'Elige paquetes individuales para invertir'],
        'search_packages' => ['category' => 'packages_view', 'english' => 'Search packages...', 'spanish' => 'Buscar paquetes...'],
        'all_packages' => ['category' => 'packages_view', 'english' => 'All Packages', 'spanish' => 'Todos los Paquetes'],
        'under_100' => ['category' => 'packages_view', 'english' => 'Under $100', 'spanish' => 'Menos de $100'],
        'between_100_500' => ['category' => 'packages_view', 'english' => '$100 - $500', 'spanish' => '$100 - $500'],
        'over_500' => ['category' => 'packages_view', 'english' => 'Over $500', 'spanish' => 'Más de $500'],
        'sort_by' => ['category' => 'packages_view', 'english' => 'Sort by:', 'spanish' => 'Ordenar por:'],
        'name' => ['category' => 'packages_view', 'english' => 'Name', 'spanish' => 'Nombre'],
        'price_low_to_high' => ['category' => 'packages_view', 'english' => 'Price (Low to High)', 'spanish' => 'Precio (Menor a Mayor)'],
        'price_high_to_low' => ['category' => 'packages_view', 'english' => 'Price (High to Low)', 'spanish' => 'Precio (Mayor a Menor)'],
        'roi_highest_first' => ['category' => 'packages_view', 'english' => 'ROI (Highest First)', 'spanish' => 'ROI (Mayor Primero)'],
        'shares_most_first' => ['category' => 'packages_view', 'english' => 'Shares (Most First)', 'spanish' => 'Acciones (Más Primero)'],
        'no_packages_found' => ['category' => 'packages_view', 'english' => 'No packages found', 'spanish' => 'No se encontraron paquetes'],
        'try_adjusting_search' => ['category' => 'packages_view', 'english' => 'Try adjusting your search or filter criteria.', 'spanish' => 'Intenta ajustar tu búsqueda o criterios de filtro.'],
        'no_packages_currently_available' => ['category' => 'packages_view', 'english' => 'No investment packages are currently available.', 'spanish' => 'No hay paquetes de inversión disponibles actualmente.'],
        'clear_filters' => ['category' => 'packages_view', 'english' => 'Clear Filters', 'spanish' => 'Limpiar Filtros'],
        
        // Common status and UI elements
        'loading' => ['category' => 'common', 'english' => 'Loading...', 'spanish' => 'Cargando...'],
        'error' => ['category' => 'common', 'english' => 'Error', 'spanish' => 'Error'],
        'success' => ['category' => 'common', 'english' => 'Success', 'spanish' => 'Éxito'],
        'warning' => ['category' => 'common', 'english' => 'Warning', 'spanish' => 'Advertencia'],
        'info' => ['category' => 'common', 'english' => 'Info', 'spanish' => 'Información'],
        'confirm' => ['category' => 'common', 'english' => 'Confirm', 'spanish' => 'Confirmar'],
        'cancel' => ['category' => 'common', 'english' => 'Cancel', 'spanish' => 'Cancelar'],
        'save' => ['category' => 'common', 'english' => 'Save', 'spanish' => 'Guardar'],
        'edit' => ['category' => 'common', 'english' => 'Edit', 'spanish' => 'Editar'],
        'delete' => ['category' => 'common', 'english' => 'Delete', 'spanish' => 'Eliminar'],
        'view' => ['category' => 'common', 'english' => 'View', 'spanish' => 'Ver'],
        'close' => ['category' => 'common', 'english' => 'Close', 'spanish' => 'Cerrar'],
        'open' => ['category' => 'common', 'english' => 'Open', 'spanish' => 'Abrir'],
        'yes' => ['category' => 'common', 'english' => 'Yes', 'spanish' => 'Sí'],
        'no' => ['category' => 'common', 'english' => 'No', 'spanish' => 'No'],
        'ok' => ['category' => 'common', 'english' => 'OK', 'spanish' => 'OK'],
        'apply' => ['category' => 'common', 'english' => 'Apply', 'spanish' => 'Aplicar'],
        'reset' => ['category' => 'common', 'english' => 'Reset', 'spanish' => 'Restablecer'],
        'clear' => ['category' => 'common', 'english' => 'Clear', 'spanish' => 'Limpiar'],
        'search' => ['category' => 'common', 'english' => 'Search', 'spanish' => 'Buscar'],
        'filter' => ['category' => 'common', 'english' => 'Filter', 'spanish' => 'Filtrar'],
        'sort' => ['category' => 'common', 'english' => 'Sort', 'spanish' => 'Ordenar'],
        'refresh' => ['category' => 'common', 'english' => 'Refresh', 'spanish' => 'Actualizar'],
        'reload' => ['category' => 'common', 'english' => 'Reload', 'spanish' => 'Recargar'],
        'back' => ['category' => 'common', 'english' => 'Back', 'spanish' => 'Atrás'],
        'next' => ['category' => 'common', 'english' => 'Next', 'spanish' => 'Siguiente'],
        'previous' => ['category' => 'common', 'english' => 'Previous', 'spanish' => 'Anterior'],
        'continue' => ['category' => 'common', 'english' => 'Continue', 'spanish' => 'Continuar'],
        'finish' => ['category' => 'common', 'english' => 'Finish', 'spanish' => 'Finalizar'],
        'complete' => ['category' => 'common', 'english' => 'Complete', 'spanish' => 'Completar'],
        'submit' => ['category' => 'common', 'english' => 'Submit', 'spanish' => 'Enviar'],
        'send' => ['category' => 'common', 'english' => 'Send', 'spanish' => 'Enviar'],
        'receive' => ['category' => 'common', 'english' => 'Receive', 'spanish' => 'Recibir'],
        'copy' => ['category' => 'common', 'english' => 'Copy', 'spanish' => 'Copiar'],
        'paste' => ['category' => 'common', 'english' => 'Paste', 'spanish' => 'Pegar'],
        'cut' => ['category' => 'common', 'english' => 'Cut', 'spanish' => 'Cortar'],
        'select_all' => ['category' => 'common', 'english' => 'Select All', 'spanish' => 'Seleccionar Todo'],
        'deselect_all' => ['category' => 'common', 'english' => 'Deselect All', 'spanish' => 'Deseleccionar Todo'],
        'show' => ['category' => 'common', 'english' => 'Show', 'spanish' => 'Mostrar'],
        'hide' => ['category' => 'common', 'english' => 'Hide', 'spanish' => 'Ocultar'],
        'expand' => ['category' => 'common', 'english' => 'Expand', 'spanish' => 'Expandir'],
        'collapse' => ['category' => 'common', 'english' => 'Collapse', 'spanish' => 'Contraer'],
        'minimize' => ['category' => 'common', 'english' => 'Minimize', 'spanish' => 'Minimizar'],
        'maximize' => ['category' => 'common', 'english' => 'Maximize', 'spanish' => 'Maximizar'],
        'fullscreen' => ['category' => 'common', 'english' => 'Fullscreen', 'spanish' => 'Pantalla Completa'],
        'exit_fullscreen' => ['category' => 'common', 'english' => 'Exit Fullscreen', 'spanish' => 'Salir de Pantalla Completa']
    ];
    
    $createdKeys = 0;
    $createdTranslations = 0;
    
    foreach ($translationKeys as $keyName => $data) {
        // Insert translation key
        $keyQuery = "INSERT INTO translation_keys (key_name, description, category) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     description = VALUES(description), 
                     category = VALUES(category)";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute([$keyName, $data['english'], $data['category']]);
        
        // Get the key ID
        $keyId = $db->lastInsertId();
        if ($keyId == 0) {
            // Key already exists, get its ID
            $getKeyQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
            $getKeyStmt = $db->prepare($getKeyQuery);
            $getKeyStmt->execute([$keyName]);
            $keyId = $getKeyStmt->fetchColumn();
        } else {
            $createdKeys++;
        }
        
        // Insert English translation
        $englishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                        VALUES (?, ?, ?, TRUE) 
                        ON DUPLICATE KEY UPDATE 
                        translation_text = VALUES(translation_text), 
                        is_approved = TRUE";
        $englishStmt = $db->prepare($englishQuery);
        $englishStmt->execute([$keyId, $englishId, $data['english']]);
        if ($db->lastInsertId() > 0) $createdTranslations++;
        
        // Insert Spanish translation
        $spanishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                        VALUES (?, ?, ?, TRUE) 
                        ON DUPLICATE KEY UPDATE 
                        translation_text = VALUES(translation_text), 
                        is_approved = TRUE";
        $spanishStmt = $db->prepare($spanishQuery);
        $spanishStmt->execute([$keyId, $spanishId, $data['spanish']]);
        if ($db->lastInsertId() > 0) $createdTranslations++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Final audit completion translation keys created successfully',
        'keys_processed' => count($translationKeys),
        'new_keys_created' => $createdKeys,
        'new_translations_created' => $createdTranslations,
        'categories' => array_unique(array_column($translationKeys, 'category'))
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
