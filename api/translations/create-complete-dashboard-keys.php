<?php
// Create complete translation keys for remaining dashboard components
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

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
    
    // Complete translation keys for remaining dashboard components
    $translationKeys = [
        // PortfolioView - Remaining strings
        'no_investments_yet' => ['category' => 'portfolio_view', 'english' => 'No Investments Yet', 'spanish' => 'Aún No Hay Inversiones'],
        'start_building_portfolio' => ['category' => 'portfolio_view', 'english' => 'Start building your portfolio by investing in our available packages. Track your growth and dividends all in one place.', 'spanish' => 'Comienza a construir tu cartera invirtiendo en nuestros paquetes disponibles. Rastrea tu crecimiento y dividendos en un solo lugar.'],
        'browse_investment_packages' => ['category' => 'portfolio_view', 'english' => 'Browse Investment Packages', 'spanish' => 'Explorar Paquetes de Inversión'],
        'total_invested' => ['category' => 'portfolio_view', 'english' => 'Total Invested', 'spanish' => 'Total Invertido'],
        'current_value' => ['category' => 'portfolio_view', 'english' => 'Current Value', 'spanish' => 'Valor Actual'],
        'aureus_shares' => ['category' => 'portfolio_view', 'english' => 'Aureus Shares', 'spanish' => 'Acciones Aureus'],
        'annual_dividends' => ['category' => 'portfolio_view', 'english' => 'Annual Dividends', 'spanish' => 'Dividendos Anuales'],
        'next' => ['category' => 'portfolio_view', 'english' => 'Next:', 'spanish' => 'Próximo:'],
        'tbd' => ['category' => 'portfolio_view', 'english' => 'TBD', 'spanish' => 'Por Determinar'],
        'portfolio_performance' => ['category' => 'portfolio_view', 'english' => 'Portfolio Performance', 'spanish' => 'Rendimiento de Cartera'],
        'performance_chart_displayed' => ['category' => 'portfolio_view', 'english' => 'Performance chart will be displayed here', 'spanish' => 'El gráfico de rendimiento se mostrará aquí'],
        'chart_integration_coming' => ['category' => 'portfolio_view', 'english' => 'Chart integration coming soon', 'spanish' => 'Integración de gráficos próximamente'],
        'active_investments' => ['category' => 'portfolio_view', 'english' => 'Active Investments', 'spanish' => 'Inversiones Activas'],
        'invested' => ['category' => 'portfolio_view', 'english' => 'Invested', 'spanish' => 'Invertido'],
        'shares' => ['category' => 'portfolio_view', 'english' => 'Shares', 'spanish' => 'Acciones'],
        'roi' => ['category' => 'portfolio_view', 'english' => 'ROI', 'spanish' => 'ROI'],
        'recent_dividend_payments' => ['category' => 'portfolio_view', 'english' => 'Recent Dividend Payments', 'spanish' => 'Pagos de Dividendos Recientes'],
        'no_dividend_payments_yet' => ['category' => 'portfolio_view', 'english' => 'No dividend payments yet', 'spanish' => 'Aún no hay pagos de dividendos'],
        'dividends_appear_investments_mature' => ['category' => 'portfolio_view', 'english' => 'Dividends will appear here once your investments mature', 'spanish' => 'Los dividendos aparecerán aquí una vez que tus inversiones maduren'],
        
        // PurchaseDialog - Complete implementation
        'purchase' => ['category' => 'purchase_dialog', 'english' => 'Purchase', 'spanish' => 'Comprar'],
        'package_details' => ['category' => 'purchase_dialog', 'english' => 'Package Details', 'spanish' => 'Detalles del Paquete'],
        'price' => ['category' => 'purchase_dialog', 'english' => 'Price:', 'spanish' => 'Precio:'],
        'shares' => ['category' => 'purchase_dialog', 'english' => 'Shares:', 'spanish' => 'Acciones:'],
        'roi' => ['category' => 'purchase_dialog', 'english' => 'ROI:', 'spanish' => 'ROI:'],
        'choose_payment_method' => ['category' => 'purchase_dialog', 'english' => 'Choose Payment Method', 'spanish' => 'Elige Método de Pago'],
        'pay_with_credits' => ['category' => 'purchase_dialog', 'english' => 'Pay with Credits', 'spanish' => 'Pagar con Créditos'],
        'use_nft_credits_instant' => ['category' => 'purchase_dialog', 'english' => 'Use your NFT credits for instant purchase', 'spanish' => 'Usa tus créditos NFT para compra instantánea'],
        'available' => ['category' => 'purchase_dialog', 'english' => 'Available', 'spanish' => 'Disponible'],
        'insufficient_credits_need_more' => ['category' => 'purchase_dialog', 'english' => 'Insufficient credits. Need ${amount} more.', 'spanish' => 'Créditos insuficientes. Necesitas ${amount} más.'],
        'pay_with_wallet' => ['category' => 'purchase_dialog', 'english' => 'Pay with Wallet', 'spanish' => 'Pagar con Billetera'],
        'connect_crypto_wallet_usdt' => ['category' => 'purchase_dialog', 'english' => 'Connect your crypto wallet and pay with USDT', 'spanish' => 'Conecta tu billetera crypto y paga con USDT'],
        'continue_with_credits' => ['category' => 'purchase_dialog', 'english' => 'Continue with Credits', 'spanish' => 'Continuar con Créditos'],
        
        // PurchaseDialog - Wallet Steps
        'step_1_connect_wallet' => ['category' => 'purchase_dialog', 'english' => 'Step 1: Connect Your Wallet', 'spanish' => 'Paso 1: Conecta Tu Billetera'],
        'wallet_connected' => ['category' => 'purchase_dialog', 'english' => 'Wallet Connected', 'spanish' => 'Billetera Conectada'],
        'continue_to_chain' => ['category' => 'purchase_dialog', 'english' => 'Continue to Chain Selection', 'spanish' => 'Continuar a Selección de Cadena'],
        'disconnect' => ['category' => 'purchase_dialog', 'english' => 'Disconnect', 'spanish' => 'Desconectar'],
        'step_2_select_chain' => ['category' => 'purchase_dialog', 'english' => 'Step 2: Select Payment Chain', 'spanish' => 'Paso 2: Selecciona Cadena de Pago'],
        'step_3_check_balance' => ['category' => 'purchase_dialog', 'english' => 'Step 3: Check Balance', 'spanish' => 'Paso 3: Verificar Saldo'],
        'step_4_terms_conditions' => ['category' => 'purchase_dialog', 'english' => 'Step 4: Terms & Conditions', 'spanish' => 'Paso 4: Términos y Condiciones'],
        'step_2_terms_conditions' => ['category' => 'purchase_dialog', 'english' => 'Step 2: Terms & Conditions', 'spanish' => 'Paso 2: Términos y Condiciones'],
        'step_5_confirm_purchase' => ['category' => 'purchase_dialog', 'english' => 'Step 5: Confirm Purchase', 'spanish' => 'Paso 5: Confirmar Compra'],
        'step_3_confirm_purchase' => ['category' => 'purchase_dialog', 'english' => 'Step 3: Confirm Purchase', 'spanish' => 'Paso 3: Confirmar Compra'],
        
        // PurchaseDialog - Transaction Summary
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
        
        // PurchaseDialog - Processing States
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
        
        // PurchaseDialog - Error Messages
        'chain_switch_failed' => ['category' => 'purchase_dialog', 'english' => 'Chain Switch Failed', 'spanish' => 'Cambio de Cadena Fallido'],
        'failed_switch_selected_chain' => ['category' => 'purchase_dialog', 'english' => 'Failed to switch to the selected chain', 'spanish' => 'Falló el cambio a la cadena seleccionada'],
        'chain_switch_error' => ['category' => 'purchase_dialog', 'english' => 'Chain Switch Error', 'spanish' => 'Error de Cambio de Cadena'],
        'error_switching_chains' => ['category' => 'purchase_dialog', 'english' => 'Error switching chains. Please try again.', 'spanish' => 'Error cambiando cadenas. Por favor intenta de nuevo.'],
        'terms_not_accepted' => ['category' => 'purchase_dialog', 'english' => 'Terms Not Accepted', 'spanish' => 'Términos No Aceptados'],
        'please_accept_terms' => ['category' => 'purchase_dialog', 'english' => 'Please accept the terms and conditions to proceed', 'spanish' => 'Por favor acepta los términos y condiciones para continuar'],
        'insufficient_credits' => ['category' => 'purchase_dialog', 'english' => 'Insufficient Credits', 'spanish' => 'Créditos Insuficientes'],
        'need_amount_only_have' => ['category' => 'purchase_dialog', 'english' => 'You need ${needed} but only have ${available} in credits', 'spanish' => 'Necesitas ${needed} pero solo tienes ${available} en créditos'],
        'purchase_successful_title' => ['category' => 'purchase_dialog', 'english' => 'Purchase Successful!', 'spanish' => '¡Compra Exitosa!'],
        'successfully_purchased_package_credits' => ['category' => 'purchase_dialog', 'english' => 'Successfully purchased {package} package with credits', 'spanish' => 'Compra exitosa del paquete {package} con créditos'],
        'purchase_failed' => ['category' => 'purchase_dialog', 'english' => 'Purchase Failed', 'spanish' => 'Compra Fallida'],
        'error_processing_purchase' => ['category' => 'purchase_dialog', 'english' => 'There was an error processing your purchase', 'spanish' => 'Hubo un error procesando tu compra'],
        'purchase_requirements_not_met' => ['category' => 'purchase_dialog', 'english' => 'Purchase Requirements Not Met', 'spanish' => 'Requisitos de Compra No Cumplidos'],
        'ensure_wallet_connected_requirements' => ['category' => 'purchase_dialog', 'english' => 'Please ensure wallet is connected, chain is selected, you have sufficient balance, and terms are accepted', 'spanish' => 'Por favor asegúrate de que la billetera esté conectada, la cadena seleccionada, tengas saldo suficiente y los términos aceptados'],
        'transaction_failed' => ['category' => 'purchase_dialog', 'english' => 'Transaction failed', 'spanish' => 'Transacción fallida'],
        'successfully_purchased_package' => ['category' => 'purchase_dialog', 'english' => 'Successfully purchased {package} package', 'spanish' => 'Compra exitosa del paquete {package}'],
        'transaction_confirmation_timeout' => ['category' => 'purchase_dialog', 'english' => 'Transaction confirmation timeout', 'spanish' => 'Tiempo de confirmación de transacción agotado'],
        
        // Status labels
        'active' => ['category' => 'status_labels', 'english' => 'ACTIVE', 'spanish' => 'ACTIVO'],
        'completed' => ['category' => 'status_labels', 'english' => 'COMPLETED', 'spanish' => 'COMPLETADO'],
        'pending' => ['category' => 'status_labels', 'english' => 'PENDING', 'spanish' => 'PENDIENTE'],
        'failed' => ['category' => 'status_labels', 'english' => 'FAILED', 'spanish' => 'FALLIDO'],

        // PurchaseDialog - Complete remaining strings
        'continue' => ['category' => 'purchase_dialog', 'english' => 'Continue', 'spanish' => 'Continuar'],
        'usdt' => ['category' => 'purchase_dialog', 'english' => 'USDT', 'spanish' => 'USDT'],

        // AccountSettings Component
        'account_settings' => ['category' => 'account_settings', 'english' => 'Account Settings', 'spanish' => 'Configuración de Cuenta'],
        'manage_account_wallet' => ['category' => 'account_settings', 'english' => 'Manage your account and wallet connections', 'spanish' => 'Gestiona tu cuenta y conexiones de billetera'],
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

        // MultiPackageSelector Component
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
        'optimized_selection_for_target' => ['category' => 'multi_package', 'english' => '✨ Optimized selection for ${amount} target', 'spanish' => '✨ Selección optimizada para objetivo de ${amount}']
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
        'message' => 'Complete dashboard translation keys created successfully',
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
