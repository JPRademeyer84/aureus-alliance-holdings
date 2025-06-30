<?php
// Create translation keys for CommissionWallet component
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
    
    // Translation keys for CommissionWallet component
    $translationKeys = [
        // Commission Wallet labels
        'available_usdt' => ['category' => 'commission_wallet', 'english' => 'Available USDT', 'spanish' => 'USDT Disponible'],
        'available_nfts' => ['category' => 'commission_wallet', 'english' => 'Available NFTs', 'spanish' => 'NFTs Disponibles'],
        'total_earned_usdt' => ['category' => 'commission_wallet', 'english' => 'Total Earned USDT', 'spanish' => 'USDT Total Ganado'],
        'total_withdrawn' => ['category' => 'commission_wallet', 'english' => 'Total Withdrawn', 'spanish' => 'Total Retirado'],
        'commission_wallet_actions' => ['category' => 'commission_wallet', 'english' => 'Commission Wallet Actions', 'spanish' => 'Acciones de Billetera de Comisiones'],
        'request_withdrawal' => ['category' => 'commission_wallet', 'english' => 'Request Withdrawal', 'spanish' => 'Solicitar Retiro'],
        'refresh_balance' => ['category' => 'commission_wallet', 'english' => 'Refresh Balance', 'spanish' => 'Actualizar Saldo'],
        'withdrawal_type' => ['category' => 'commission_wallet', 'english' => 'Withdrawal Type', 'spanish' => 'Tipo de Retiro'],
        'usdt_withdrawal' => ['category' => 'commission_wallet', 'english' => 'USDT Withdrawal', 'spanish' => 'Retiro USDT'],
        'nft_redemption' => ['category' => 'commission_wallet', 'english' => 'NFT Redemption', 'spanish' => 'Canje de NFT'],
        'reinvest_in_more_nfts' => ['category' => 'commission_wallet', 'english' => 'Reinvest in More NFTs', 'spanish' => 'Reinvertir en Más NFTs'],
        'usdt_amount' => ['category' => 'commission_wallet', 'english' => 'USDT Amount', 'spanish' => 'Cantidad USDT'],
        'nft_quantity' => ['category' => 'commission_wallet', 'english' => 'NFT Quantity', 'spanish' => 'Cantidad de NFT'],
        'wallet_address' => ['category' => 'commission_wallet', 'english' => 'Wallet Address', 'spanish' => 'Dirección de Billetera'],
        'submit_request' => ['category' => 'commission_wallet', 'english' => 'Submit Request', 'spanish' => 'Enviar Solicitud'],
        'withdrawal_history' => ['category' => 'commission_wallet', 'english' => 'Withdrawal History', 'spanish' => 'Historial de Retiros'],
        'no_withdrawal_requests_yet' => ['category' => 'commission_wallet', 'english' => 'No withdrawal requests yet', 'spanish' => 'Aún no hay solicitudes de retiro'],
        'reinvestment_successful' => ['category' => 'commission_wallet', 'english' => 'Reinvestment Successful', 'spanish' => 'Reinversión Exitosa'],
        'withdrawal_requested' => ['category' => 'commission_wallet', 'english' => 'Withdrawal Requested', 'spanish' => 'Retiro Solicitado'],
        'withdrawal_request_submitted' => ['category' => 'commission_wallet', 'english' => 'Your withdrawal request has been submitted for admin approval', 'spanish' => 'Tu solicitud de retiro ha sido enviada para aprobación del administrador'],
        'reinvestment_failed' => ['category' => 'commission_wallet', 'english' => 'Reinvestment Failed', 'spanish' => 'Reinversión Fallida'],
        'withdrawal_failed' => ['category' => 'commission_wallet', 'english' => 'Withdrawal Failed', 'spanish' => 'Retiro Fallido'],
        'failed_to_process_request' => ['category' => 'commission_wallet', 'english' => 'Failed to process request', 'spanish' => 'Error al procesar solicitud'],
        'failed_to_load_commission_data' => ['category' => 'commission_wallet', 'english' => 'Failed to load commission data', 'spanish' => 'Error al cargar datos de comisiones'],
        
        // Status labels
        'completed' => ['category' => 'commission_wallet', 'english' => 'Completed', 'spanish' => 'Completado'],
        'pending' => ['category' => 'commission_wallet', 'english' => 'Pending', 'spanish' => 'Pendiente'],
        'processing' => ['category' => 'commission_wallet', 'english' => 'Processing', 'spanish' => 'Procesando'],
        'failed' => ['category' => 'commission_wallet', 'english' => 'Failed', 'spanish' => 'Fallido'],
        'cancelled' => ['category' => 'commission_wallet', 'english' => 'Cancelled', 'spanish' => 'Cancelado']
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
        'message' => 'CommissionWallet translation keys created successfully',
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
