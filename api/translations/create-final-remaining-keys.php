<?php
// Create final remaining translation keys for complete dashboard coverage
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
    
    // Final remaining translation keys for complete dashboard coverage
    $translationKeys = [
        // PurchaseDialog - Final remaining strings
        'processing_payment_type' => ['category' => 'purchase_dialog', 'english' => 'Processing {type}...', 'spanish' => 'Procesando {type}...'],
        'credit_payment' => ['category' => 'purchase_dialog', 'english' => 'Credit Payment', 'spanish' => 'Pago con Créditos'],
        'confirm_purchase_amount_currency' => ['category' => 'purchase_dialog', 'english' => 'Confirm Purchase - ${amount} {currency}', 'spanish' => 'Confirmar Compra - ${amount} {currency}'],
        'processing_transaction_title' => ['category' => 'purchase_dialog', 'english' => 'Processing Transaction', 'spanish' => 'Procesando Transacción'],
        'transaction_in_progress' => ['category' => 'purchase_dialog', 'english' => 'Transaction in Progress', 'spanish' => 'Transacción en Progreso'],
        'confirm_wallet_wait_blockchain' => ['category' => 'purchase_dialog', 'english' => 'Please confirm the transaction in your wallet and wait for blockchain confirmation.', 'spanish' => 'Por favor confirma la transacción en tu billetera y espera la confirmación de blockchain.'],
        'transaction_hash' => ['category' => 'purchase_dialog', 'english' => 'Transaction Hash:', 'spanish' => 'Hash de Transacción:'],
        'purchase_successful' => ['category' => 'purchase_dialog', 'english' => 'Purchase Successful!', 'spanish' => '¡Compra Exitosa!'],
        'transaction_confirmed' => ['category' => 'purchase_dialog', 'english' => 'Transaction Confirmed', 'spanish' => 'Transacción Confirmada'],
        'package_successfully_purchased' => ['category' => 'purchase_dialog', 'english' => 'Your {package} package has been successfully purchased and added to your investment portfolio.', 'spanish' => 'Tu paquete {package} ha sido comprado exitosamente y agregado a tu cartera de inversiones.'],
        'view_on_explorer' => ['category' => 'purchase_dialog', 'english' => 'View on Explorer:', 'spanish' => 'Ver en Explorador:'],
        'transaction' => ['category' => 'purchase_dialog', 'english' => 'Transaction', 'spanish' => 'Transacción'],
        'close' => ['category' => 'purchase_dialog', 'english' => 'Close', 'spanish' => 'Cerrar'],
        
        // AccountSettings - Complete remaining strings
        'member_since' => ['category' => 'account_settings', 'english' => 'Member Since', 'spanish' => 'Miembro Desde'],
        'account_status' => ['category' => 'account_settings', 'english' => 'Account Status', 'spanish' => 'Estado de Cuenta'],
        'active_investor' => ['category' => 'account_settings', 'english' => 'Active Investor', 'spanish' => 'Inversor Activo'],
        'edit_profile' => ['category' => 'account_settings', 'english' => 'Edit Profile', 'spanish' => 'Editar Perfil'],
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
        'na' => ['category' => 'account_settings', 'english' => 'N/A', 'spanish' => 'N/D'],
        
        // MultiPackageSelector - Complete implementation
        'set_investment_target' => ['category' => 'multi_package', 'english' => 'Set Your Investment Target', 'spanish' => 'Establece Tu Meta de Inversión'],
        'target_amount_usd' => ['category' => 'multi_package', 'english' => 'Target Amount (USD)', 'spanish' => 'Monto Objetivo (USD)'],
        'auto_optimize' => ['category' => 'multi_package', 'english' => 'Auto-Optimize', 'spanish' => 'Auto-Optimizar'],
        'quick_select_amount' => ['category' => 'multi_package', 'english' => 'Quick Select: ${amount}', 'spanish' => 'Selección Rápida: ${amount}'],
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
        
        // Common currency and amount labels
        'dollar_25' => ['category' => 'common', 'english' => '$25', 'spanish' => '$25'],
        'dollar_50000' => ['category' => 'common', 'english' => '$50,000', 'spanish' => '$50,000'],
        'usdt' => ['category' => 'common', 'english' => 'USDT', 'spanish' => 'USDT'],
        'credits' => ['category' => 'common', 'english' => 'Credits', 'spanish' => 'Créditos'],
        'wallet' => ['category' => 'common', 'english' => 'Wallet', 'spanish' => 'Billetera']
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
        'message' => 'Final remaining dashboard translation keys created successfully',
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
