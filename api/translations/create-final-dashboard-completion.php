<?php
// Create final translation keys for complete dashboard coverage
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
    
    // Final translation keys for complete dashboard coverage
    $translationKeys = [
        // AccountSettings - Complete remaining strings
        'save_changes' => ['category' => 'account_settings', 'english' => 'Save Changes', 'spanish' => 'Guardar Cambios'],
        'cancel' => ['category' => 'account_settings', 'english' => 'Cancel', 'spanish' => 'Cancelar'],
        'wallet_connection' => ['category' => 'account_settings', 'english' => 'Wallet Connection', 'spanish' => 'Conexión de Billetera'],
        'connect_wallet_start_investing' => ['category' => 'account_settings', 'english' => 'Connect your wallet to start investing and track your portfolio', 'spanish' => 'Conecta tu billetera para comenzar a invertir y rastrear tu cartera'],
        'wallet_connected' => ['category' => 'account_settings', 'english' => 'Wallet Connected', 'spanish' => 'Billetera Conectada'],
        'address' => ['category' => 'account_settings', 'english' => 'Address:', 'spanish' => 'Dirección:'],
        'usdt_balance' => ['category' => 'account_settings', 'english' => 'USDT Balance:', 'spanish' => 'Saldo USDT:'],
        'network' => ['category' => 'account_settings', 'english' => 'Network:', 'spanish' => 'Red:'],
        'disconnect' => ['category' => 'account_settings', 'english' => 'Disconnect', 'spanish' => 'Desconectar'],
        'switch_wallet' => ['category' => 'account_settings', 'english' => 'Switch Wallet', 'spanish' => 'Cambiar Billetera'],
        'security_settings' => ['category' => 'account_settings', 'english' => 'Security Settings', 'spanish' => 'Configuración de Seguridad'],
        'security_features_coming_soon' => ['category' => 'account_settings', 'english' => 'Security features coming soon...', 'spanish' => 'Funciones de seguridad próximamente...'],
        'two_factor_authentication' => ['category' => 'account_settings', 'english' => 'Two-Factor Authentication', 'spanish' => 'Autenticación de Dos Factores'],
        'add_extra_security_layer' => ['category' => 'account_settings', 'english' => 'Add an extra layer of security to your account', 'spanish' => 'Agrega una capa extra de seguridad a tu cuenta'],
        'enable_2fa_coming_soon' => ['category' => 'account_settings', 'english' => 'Enable 2FA (Coming Soon)', 'spanish' => 'Habilitar 2FA (Próximamente)'],
        'password_change' => ['category' => 'account_settings', 'english' => 'Password Change', 'spanish' => 'Cambio de Contraseña'],
        'update_account_password' => ['category' => 'account_settings', 'english' => 'Update your account password', 'spanish' => 'Actualiza la contraseña de tu cuenta'],
        'change_password_coming_soon' => ['category' => 'account_settings', 'english' => 'Change Password (Coming Soon)', 'spanish' => 'Cambiar Contraseña (Próximamente)'],
        'polygon' => ['category' => 'account_settings', 'english' => 'Polygon', 'spanish' => 'Polygon'],
        'bsc' => ['category' => 'account_settings', 'english' => 'BSC', 'spanish' => 'BSC'],
        'ethereum' => ['category' => 'account_settings', 'english' => 'Ethereum', 'spanish' => 'Ethereum'],
        'unknown' => ['category' => 'account_settings', 'english' => 'Unknown', 'spanish' => 'Desconocido'],
        
        // MultiPackageSelector - Complete remaining strings
        'quick_select_amount' => ['category' => 'multi_package', 'english' => 'Quick Select: ${amount}', 'spanish' => 'Selección Rápida: ${amount}'],
        'dollar_25' => ['category' => 'multi_package', 'english' => '$25', 'spanish' => '$25'],
        'dollar_50000' => ['category' => 'multi_package', 'english' => '$50,000', 'spanish' => '$50,000'],
        'choose_your_packages' => ['category' => 'multi_package', 'english' => 'Choose Your Packages', 'spanish' => 'Elige Tus Paquetes'],
        'clear_all' => ['category' => 'multi_package', 'english' => 'Clear All', 'spanish' => 'Limpiar Todo'],
        'aureus_shares_count' => ['category' => 'multi_package', 'english' => '{count} Aureus Shares', 'spanish' => '{count} Acciones Aureus'],
        'roi_amount' => ['category' => 'multi_package', 'english' => '${amount} ROI', 'spanish' => '${amount} ROI'],
        'annual_dividends_amount' => ['category' => 'multi_package', 'english' => '${amount} Annual Dividends', 'spanish' => '${amount} Dividendos Anuales'],
        'your_investment_summary' => ['category' => 'multi_package', 'english' => 'Your Investment Summary', 'spanish' => 'Resumen de Tu Inversión'],
        'total_investment' => ['category' => 'multi_package', 'english' => 'Total Investment', 'spanish' => 'Inversión Total'],
        'total_shares' => ['category' => 'multi_package', 'english' => 'Total Shares', 'spanish' => 'Acciones Totales'],
        'expected_roi' => ['category' => 'multi_package', 'english' => 'Expected ROI', 'spanish' => 'ROI Esperado'],
        'annual_dividends' => ['category' => 'multi_package', 'english' => 'Annual Dividends', 'spanish' => 'Dividendos Anuales'],
        'proceed_to_payment_amount' => ['category' => 'multi_package', 'english' => 'Proceed to Payment - ${amount}', 'spanish' => 'Proceder al Pago - ${amount}'],
        'optimized_selection_target' => ['category' => 'multi_package', 'english' => '✨ Optimized selection for ${amount} target', 'spanish' => '✨ Selección optimizada para objetivo de ${amount}'],
        
        // Toast/Error Messages - Complete remaining strings
        'purchase_failed' => ['category' => 'error_messages', 'english' => 'Purchase Failed', 'spanish' => 'Compra Fallida'],
        'error_processing_purchase' => ['category' => 'error_messages', 'english' => 'There was an error processing your purchase', 'spanish' => 'Hubo un error procesando tu compra'],
        'purchase_requirements_not_met' => ['category' => 'error_messages', 'english' => 'Purchase Requirements Not Met', 'spanish' => 'Requisitos de Compra No Cumplidos'],
        'ensure_wallet_connected_requirements' => ['category' => 'error_messages', 'english' => 'Please ensure wallet is connected, chain is selected, you have sufficient balance, and terms are accepted', 'spanish' => 'Por favor asegúrate de que la billetera esté conectada, la cadena seleccionada, tengas saldo suficiente y los términos aceptados'],
        'transaction_failed' => ['category' => 'error_messages', 'english' => 'Transaction failed', 'spanish' => 'Transacción fallida'],
        'successfully_purchased_package' => ['category' => 'error_messages', 'english' => 'Successfully purchased {package} package', 'spanish' => 'Compra exitosa del paquete {package}'],
        'copied' => ['category' => 'common', 'english' => 'Copied!', 'spanish' => '¡Copiado!'],
        'wallet_address_copied' => ['category' => 'common', 'english' => 'Wallet address copied to clipboard', 'spanish' => 'Dirección de billetera copiada al portapapeles'],
        'profile_updated' => ['category' => 'common', 'english' => 'Profile Updated', 'spanish' => 'Perfil Actualizado'],
        'profile_updated_successfully' => ['category' => 'common', 'english' => 'Your profile has been updated successfully', 'spanish' => 'Tu perfil ha sido actualizado exitosamente'],
        
        // Common UI elements
        'x_quantity' => ['category' => 'common', 'english' => 'x{quantity}', 'spanish' => 'x{quantity}'],
        'usdt' => ['category' => 'common', 'english' => 'USDT', 'spanish' => 'USDT'],
        'credits' => ['category' => 'common', 'english' => 'Credits', 'spanish' => 'Créditos'],
        'wallet' => ['category' => 'common', 'english' => 'Wallet', 'spanish' => 'Billetera'],
        'na' => ['category' => 'common', 'english' => 'N/A', 'spanish' => 'N/D'],
        'zero_decimal' => ['category' => 'common', 'english' => '0.00', 'spanish' => '0,00']
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
        'message' => 'Final dashboard completion translation keys created successfully',
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
