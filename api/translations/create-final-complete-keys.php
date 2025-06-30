<?php
// Create final complete translation keys for all remaining dashboard components
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
    
    // Final complete translation keys for all remaining dashboard components
    $translationKeys = [
        // PurchaseDialog - Complete remaining strings
        'step_1_connect_wallet' => ['category' => 'purchase_dialog', 'english' => 'Step 1: Connect Your Wallet', 'spanish' => 'Paso 1: Conecta Tu Billetera'],
        'wallet_connected' => ['category' => 'purchase_dialog', 'english' => 'Wallet Connected', 'spanish' => 'Billetera Conectada'],
        'continue' => ['category' => 'purchase_dialog', 'english' => 'Continue', 'spanish' => 'Continuar'],
        'disconnect' => ['category' => 'purchase_dialog', 'english' => 'Disconnect', 'spanish' => 'Desconectar'],
        'step_2_select_chain' => ['category' => 'purchase_dialog', 'english' => 'Step 2: Select Payment Chain', 'spanish' => 'Paso 2: Selecciona Cadena de Pago'],
        'step_3_check_balance' => ['category' => 'purchase_dialog', 'english' => 'Step 3: Check Balance', 'spanish' => 'Paso 3: Verificar Saldo'],
        'step_4_terms_conditions' => ['category' => 'purchase_dialog', 'english' => 'Step 4: Terms & Conditions', 'spanish' => 'Paso 4: Términos y Condiciones'],
        'step_2_terms_conditions' => ['category' => 'purchase_dialog', 'english' => 'Step 2: Terms & Conditions', 'spanish' => 'Paso 2: Términos y Condiciones'],
        'step_5_confirm_purchase' => ['category' => 'purchase_dialog', 'english' => 'Step 5: Confirm Purchase', 'spanish' => 'Paso 5: Confirmar Compra'],
        'step_3_confirm_purchase' => ['category' => 'purchase_dialog', 'english' => 'Step 3: Confirm Purchase', 'spanish' => 'Paso 3: Confirmar Compra'],
        'transaction_summary' => ['category' => 'purchase_dialog', 'english' => 'Transaction Summary', 'spanish' => 'Resumen de Transacción'],
        'package' => ['category' => 'purchase_dialog', 'english' => 'Package:', 'spanish' => 'Paquete:'],
        'amount' => ['category' => 'purchase_dialog', 'english' => 'Amount:', 'spanish' => 'Monto:'],
        'payment_method' => ['category' => 'purchase_dialog', 'english' => 'Payment Method:', 'spanish' => 'Método de Pago:'],
        'credits' => ['category' => 'purchase_dialog', 'english' => 'Credits', 'spanish' => 'Créditos'],
        'wallet' => ['category' => 'purchase_dialog', 'english' => 'Wallet', 'spanish' => 'Billetera'],
        'chain' => ['category' => 'purchase_dialog', 'english' => 'Chain:', 'spanish' => 'Cadena:'],
        'your_balance' => ['category' => 'purchase_dialog', 'english' => 'Your Balance:', 'spanish' => 'Tu Saldo:'],
        'your_credits' => ['category' => 'purchase_dialog', 'english' => 'Your Credits:', 'spanish' => 'Tus Créditos:'],
        'processing_credit_payment' => ['category' => 'purchase_dialog', 'english' => 'Processing Credit Payment', 'spanish' => 'Procesando Pago con Créditos'],
        'processing_transaction' => ['category' => 'purchase_dialog', 'english' => 'Processing Transaction', 'spanish' => 'Procesando Transacción'],
        'confirm_purchase_amount' => ['category' => 'purchase_dialog', 'english' => 'Confirm Purchase - ${amount}', 'spanish' => 'Confirmar Compra - ${amount}'],
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
        'usdt' => ['category' => 'purchase_dialog', 'english' => 'USDT', 'spanish' => 'USDT'],
        
        // AccountSettings - Complete remaining strings
        'profile_information' => ['category' => 'account_settings', 'english' => 'Profile Information', 'spanish' => 'Información del Perfil'],
        'username' => ['category' => 'account_settings', 'english' => 'Username', 'spanish' => 'Nombre de Usuario'],
        'email' => ['category' => 'account_settings', 'english' => 'Email', 'spanish' => 'Correo Electrónico'],
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
        'copied' => ['category' => 'account_settings', 'english' => 'Copied!', 'spanish' => '¡Copiado!'],
        'wallet_address_copied' => ['category' => 'account_settings', 'english' => 'Wallet address copied to clipboard', 'spanish' => 'Dirección de billetera copiada al portapapeles'],
        'profile_updated' => ['category' => 'account_settings', 'english' => 'Profile Updated', 'spanish' => 'Perfil Actualizado'],
        'profile_updated_successfully' => ['category' => 'account_settings', 'english' => 'Your profile has been updated successfully', 'spanish' => 'Tu perfil ha sido actualizado exitosamente'],
        'polygon' => ['category' => 'account_settings', 'english' => 'Polygon', 'spanish' => 'Polygon'],
        'bsc' => ['category' => 'account_settings', 'english' => 'BSC', 'spanish' => 'BSC'],
        'ethereum' => ['category' => 'account_settings', 'english' => 'Ethereum', 'spanish' => 'Ethereum'],
        'unknown' => ['category' => 'account_settings', 'english' => 'Unknown', 'spanish' => 'Desconocido'],
        'na' => ['category' => 'account_settings', 'english' => 'N/A', 'spanish' => 'N/D'],
        
        // MultiPackageSelector - Complete implementation
        'set_investment_target' => ['category' => 'multi_package', 'english' => 'Set Your Investment Target', 'spanish' => 'Establece Tu Meta de Inversión'],
        'target_amount_usd' => ['category' => 'multi_package', 'english' => 'Target Amount (USD)', 'spanish' => 'Monto Objetivo (USD)'],
        'auto_optimize' => ['category' => 'multi_package', 'english' => 'Auto-Optimize', 'spanish' => 'Auto-Optimizar'],
        'quick_select' => ['category' => 'multi_package', 'english' => 'Quick Select:', 'spanish' => 'Selección Rápida:'],
        'choose_your_packages' => ['category' => 'multi_package', 'english' => 'Choose Your Packages', 'spanish' => 'Elige Tus Paquetes'],
        'clear_all' => ['category' => 'multi_package', 'english' => 'Clear All', 'spanish' => 'Limpiar Todo'],
        'loading_investment_packages' => ['category' => 'multi_package', 'english' => 'Loading investment packages...', 'spanish' => 'Cargando paquetes de inversión...'],
        'aureus_shares' => ['category' => 'multi_package', 'english' => 'Aureus Shares', 'spanish' => 'Acciones Aureus'],
        'roi' => ['category' => 'multi_package', 'english' => 'ROI', 'spanish' => 'ROI'],
        'annual_dividends' => ['category' => 'multi_package', 'english' => 'Annual Dividends', 'spanish' => 'Dividendos Anuales'],
        'your_investment_summary' => ['category' => 'multi_package', 'english' => 'Your Investment Summary', 'spanish' => 'Resumen de Tu Inversión'],
        'total_investment' => ['category' => 'multi_package', 'english' => 'Total Investment', 'spanish' => 'Inversión Total'],
        'total_shares' => ['category' => 'multi_package', 'english' => 'Total Shares', 'spanish' => 'Acciones Totales'],
        'expected_roi' => ['category' => 'multi_package', 'english' => 'Expected ROI', 'spanish' => 'ROI Esperado'],
        'proceed_to_payment' => ['category' => 'multi_package', 'english' => 'Proceed to Payment', 'spanish' => 'Proceder al Pago'],
        'optimized_selection_for_target' => ['category' => 'multi_package', 'english' => '✨ Optimized selection for ${amount} target', 'spanish' => '✨ Selección optimizada para objetivo de ${amount}'],
        
        // Error Messages and Toast Notifications
        'chain_switch_failed' => ['category' => 'error_messages', 'english' => 'Chain Switch Failed', 'spanish' => 'Cambio de Cadena Fallido'],
        'failed_switch_selected_chain' => ['category' => 'error_messages', 'english' => 'Failed to switch to the selected chain', 'spanish' => 'Falló el cambio a la cadena seleccionada'],
        'chain_switch_error' => ['category' => 'error_messages', 'english' => 'Chain Switch Error', 'spanish' => 'Error de Cambio de Cadena'],
        'error_switching_chains' => ['category' => 'error_messages', 'english' => 'Error switching chains. Please try again.', 'spanish' => 'Error cambiando cadenas. Por favor intenta de nuevo.'],
        'terms_not_accepted' => ['category' => 'error_messages', 'english' => 'Terms Not Accepted', 'spanish' => 'Términos No Aceptados'],
        'please_accept_terms' => ['category' => 'error_messages', 'english' => 'Please accept the terms and conditions to proceed', 'spanish' => 'Por favor acepta los términos y condiciones para continuar'],
        'insufficient_credits' => ['category' => 'error_messages', 'english' => 'Insufficient Credits', 'spanish' => 'Créditos Insuficientes'],
        'need_amount_only_have' => ['category' => 'error_messages', 'english' => 'You need ${needed} but only have ${available} in credits', 'spanish' => 'Necesitas ${needed} pero solo tienes ${available} en créditos'],
        'purchase_successful_title' => ['category' => 'error_messages', 'english' => 'Purchase Successful!', 'spanish' => '¡Compra Exitosa!'],
        'successfully_purchased_package_credits' => ['category' => 'error_messages', 'english' => 'Successfully purchased {package} package with credits', 'spanish' => 'Compra exitosa del paquete {package} con créditos'],
        'purchase_failed' => ['category' => 'error_messages', 'english' => 'Purchase Failed', 'spanish' => 'Compra Fallida'],
        'error_processing_purchase' => ['category' => 'error_messages', 'english' => 'There was an error processing your purchase', 'spanish' => 'Hubo un error procesando tu compra'],
        'purchase_requirements_not_met' => ['category' => 'error_messages', 'english' => 'Purchase Requirements Not Met', 'spanish' => 'Requisitos de Compra No Cumplidos'],
        'ensure_wallet_connected_requirements' => ['category' => 'error_messages', 'english' => 'Please ensure wallet is connected, chain is selected, you have sufficient balance, and terms are accepted', 'spanish' => 'Por favor asegúrate de que la billetera esté conectada, la cadena seleccionada, tengas saldo suficiente y los términos aceptados'],
        'transaction_failed' => ['category' => 'error_messages', 'english' => 'Transaction failed', 'spanish' => 'Transacción fallida'],
        'successfully_purchased_package' => ['category' => 'error_messages', 'english' => 'Successfully purchased {package} package', 'spanish' => 'Compra exitosa del paquete {package}'],
        'transaction_confirmation_timeout' => ['category' => 'error_messages', 'english' => 'Transaction confirmation timeout', 'spanish' => 'Tiempo de confirmación de transacción agotado'],

        // PurchaseDialog - Additional remaining strings
        'step_3_confirm_purchase' => ['category' => 'purchase_dialog', 'english' => 'Step 3: Confirm Purchase', 'spanish' => 'Paso 3: Confirmar Compra'],
        'step_5_confirm_purchase' => ['category' => 'purchase_dialog', 'english' => 'Step 5: Confirm Purchase', 'spanish' => 'Paso 5: Confirmar Compra'],
        'processing_credit_payment_transaction' => ['category' => 'purchase_dialog', 'english' => 'Processing {type}...', 'spanish' => 'Procesando {type}...'],
        'confirm_purchase_with_amount' => ['category' => 'purchase_dialog', 'english' => 'Confirm Purchase - ${amount} {currency}', 'spanish' => 'Confirmar Compra - ${amount} {currency}'],

        // MultiPackageSelector - Add translation import
        'loading_investment_packages' => ['category' => 'multi_package', 'english' => 'Loading investment packages...', 'spanish' => 'Cargando paquetes de inversión...'],
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

        // AccountSettings - Additional strings
        'profile_information' => ['category' => 'account_settings', 'english' => 'Profile Information', 'spanish' => 'Información del Perfil'],
        'username' => ['category' => 'account_settings', 'english' => 'Username', 'spanish' => 'Nombre de Usuario'],
        'email' => ['category' => 'account_settings', 'english' => 'Email', 'spanish' => 'Correo Electrónico'],
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
        'na' => ['category' => 'account_settings', 'english' => 'N/A', 'spanish' => 'N/D']
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
        'message' => 'Final complete dashboard translation keys created successfully',
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
