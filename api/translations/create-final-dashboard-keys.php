<?php
// Create final translation keys for remaining dashboard components
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
    
    // Final comprehensive translation keys for remaining dashboard components
    $translationKeys = [
        // SupportView - Remaining strings
        'how_contact_us' => ['category' => 'support_view', 'english' => 'How would you like to contact us?', 'spanish' => '¿Cómo te gustaría contactarnos?'],
        'premium' => ['category' => 'support_view', 'english' => 'Premium', 'spanish' => 'Premium'],
        'send_message' => ['category' => 'support_view', 'english' => 'Send Message', 'spanish' => 'Enviar Mensaje'],
        'my_messages' => ['category' => 'support_view', 'english' => 'My Messages', 'spanish' => 'Mis Mensajes'],
        'faq' => ['category' => 'support_view', 'english' => 'FAQ', 'spanish' => 'Preguntas Frecuentes'],
        'frequently_asked_questions' => ['category' => 'support_view', 'english' => 'Frequently Asked Questions', 'spanish' => 'Preguntas Frecuentes'],
        'still_need_help' => ['category' => 'support_view', 'english' => 'Still need help?', 'spanish' => '¿Aún necesitas ayuda?'],
        'cant_find_looking_for' => ['category' => 'support_view', 'english' => 'Can\'t find what you\'re looking for? Send us a message and we\'ll get back to you quickly.', 'spanish' => '¿No encuentras lo que buscas? Envíanos un mensaje y te responderemos rápidamente.'],
        
        // PurchaseDialog - Complete translation
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
        
        // PortfolioView Component
        'portfolio_overview' => ['category' => 'portfolio_view', 'english' => 'Portfolio Overview', 'spanish' => 'Resumen de Cartera'],
        'track_investment_performance' => ['category' => 'portfolio_view', 'english' => 'Track your investment performance and growth', 'spanish' => 'Rastrea el rendimiento y crecimiento de tus inversiones'],
        'refresh' => ['category' => 'portfolio_view', 'english' => 'Refresh', 'spanish' => 'Actualizar'],
        'export_report' => ['category' => 'portfolio_view', 'english' => 'Export Report', 'spanish' => 'Exportar Reporte'],
        'loading_portfolio_data' => ['category' => 'portfolio_view', 'english' => 'Loading portfolio data...', 'spanish' => 'Cargando datos de cartera...'],
        'no_investments_yet' => ['category' => 'portfolio_view', 'english' => 'No Investments Yet', 'spanish' => 'Aún No Hay Inversiones'],
        'start_building_portfolio' => ['category' => 'portfolio_view', 'english' => 'Start building your portfolio by investing in our available packages. Track your growth and dividends all in one place.', 'spanish' => 'Comienza a construir tu cartera invirtiendo en nuestros paquetes disponibles. Rastrea tu crecimiento y dividendos en un solo lugar.'],
        'browse_investment_packages' => ['category' => 'portfolio_view', 'english' => 'Browse Investment Packages', 'spanish' => 'Explorar Paquetes de Inversión'],
        'total_invested' => ['category' => 'portfolio_view', 'english' => 'Total Invested', 'spanish' => 'Total Invertido'],
        'current_value' => ['category' => 'portfolio_view', 'english' => 'Current Value', 'spanish' => 'Valor Actual'],
        'aureus_shares' => ['category' => 'portfolio_view', 'english' => 'Aureus Shares', 'spanish' => 'Acciones Aureus'],
        'annual_dividends' => ['category' => 'portfolio_view', 'english' => 'Annual Dividends', 'spanish' => 'Dividendos Anuales'],
        'next' => ['category' => 'portfolio_view', 'english' => 'Next:', 'spanish' => 'Próximo:'],
        'portfolio_performance' => ['category' => 'portfolio_view', 'english' => 'Portfolio Performance', 'spanish' => 'Rendimiento de Cartera'],
        'performance_chart_displayed' => ['category' => 'portfolio_view', 'english' => 'Performance chart will be displayed here', 'spanish' => 'El gráfico de rendimiento se mostrará aquí'],
        'chart_integration_coming' => ['category' => 'portfolio_view', 'english' => 'Chart integration coming soon', 'spanish' => 'Integración de gráficos próximamente'],
        'active_investments' => ['category' => 'portfolio_view', 'english' => 'Active Investments', 'spanish' => 'Inversiones Activas'],
        'invested' => ['category' => 'portfolio_view', 'english' => 'Invested', 'spanish' => 'Invertido'],
        'shares' => ['category' => 'portfolio_view', 'english' => 'Shares', 'spanish' => 'Acciones'],
        'roi' => ['category' => 'portfolio_view', 'english' => 'ROI', 'spanish' => 'ROI'],
        'recent_dividend_payments' => ['category' => 'portfolio_view', 'english' => 'Recent Dividend Payments', 'spanish' => 'Pagos de Dividendos Recientes'],
        'no_dividend_payments_yet' => ['category' => 'portfolio_view', 'english' => 'No dividend payments yet', 'spanish' => 'Aún no hay pagos de dividendos'],
        'dividends_appear_investments_mature' => ['category' => 'portfolio_view', 'english' => 'Dividends will appear here once your investments mature', 'spanish' => 'Los dividendos aparecerán aquí una vez que tus inversiones maduren']
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
        'message' => 'Final dashboard translation keys created successfully',
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
