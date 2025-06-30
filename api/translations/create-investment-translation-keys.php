<?php
// Create comprehensive translation keys for investment pages and forms (English → Spanish focus)
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Simple database connection for command line execution
try {
    $host = 'localhost';
    $dbname = 'aureus_angels';
    $username = 'root';
    $password = '';

    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8");
    
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
    
    // Investment and form translation keys with English and Spanish translations
    $translationKeys = [
        // Investment Page Headers
        'invest_in_aureus_alliance' => [
            'category' => 'investment_page',
            'english' => 'Invest in Aureus Alliance',
            'spanish' => 'Invierte en Aureus Alliance'
        ],
        'complete_your_investment' => [
            'category' => 'investment_page',
            'english' => 'Complete Your Investment',
            'spanish' => 'Completa Tu Inversión'
        ],
        'choose_investment_package' => [
            'category' => 'investment_page',
            'english' => 'Choose Your Investment Package',
            'spanish' => 'Elige Tu Paquete de Inversión'
        ],
        
        // Investment Package Names
        'bronze_package' => [
            'category' => 'investment_packages',
            'english' => 'Bronze',
            'spanish' => 'Bronce'
        ],
        'silver_package' => [
            'category' => 'investment_packages',
            'english' => 'Silver',
            'spanish' => 'Plata'
        ],
        'gold_package' => [
            'category' => 'investment_packages',
            'english' => 'Gold',
            'spanish' => 'Oro'
        ],
        'platinum_package' => [
            'category' => 'investment_packages',
            'english' => 'Platinum',
            'spanish' => 'Platino'
        ],
        'diamond_package' => [
            'category' => 'investment_packages',
            'english' => 'Diamond',
            'spanish' => 'Diamante'
        ],
        'obsidian_package' => [
            'category' => 'investment_packages',
            'english' => 'Obsidian',
            'spanish' => 'Obsidiana'
        ],
        
        // Investment Package Details
        'shares' => [
            'category' => 'investment_details',
            'english' => 'shares',
            'spanish' => 'acciones'
        ],
        'yield' => [
            'category' => 'investment_details',
            'english' => 'yield',
            'spanish' => 'rendimiento'
        ],
        'quarterly_dividends' => [
            'category' => 'investment_details',
            'english' => 'Quarterly Dividends',
            'spanish' => 'Dividendos Trimestrales'
        ],
        'bonuses' => [
            'category' => 'investment_details',
            'english' => 'Bonuses',
            'spanish' => 'Bonificaciones'
        ],
        'select_package' => [
            'category' => 'investment_details',
            'english' => 'Select Package',
            'spanish' => 'Seleccionar Paquete'
        ],
        'selected' => [
            'category' => 'investment_details',
            'english' => 'Selected',
            'spanish' => 'Seleccionado'
        ],
        
        // Payment and Wallet
        'payment_method' => [
            'category' => 'payment',
            'english' => 'Payment Method',
            'spanish' => 'Método de Pago'
        ],
        'connect_wallet' => [
            'category' => 'payment',
            'english' => 'Connect Wallet',
            'spanish' => 'Conectar Billetera'
        ],
        'wallet_connected' => [
            'category' => 'payment',
            'english' => 'Wallet Connected',
            'spanish' => 'Billetera Conectada'
        ],
        'wallet_address' => [
            'category' => 'payment',
            'english' => 'Wallet Address',
            'spanish' => 'Dirección de Billetera'
        ],
        'payment_amount' => [
            'category' => 'payment',
            'english' => 'Payment Amount',
            'spanish' => 'Monto de Pago'
        ],
        'transaction_hash' => [
            'category' => 'payment',
            'english' => 'Transaction Hash',
            'spanish' => 'Hash de Transacción'
        ],
        'confirm_payment' => [
            'category' => 'payment',
            'english' => 'Confirm Payment',
            'spanish' => 'Confirmar Pago'
        ],
        'processing_payment' => [
            'category' => 'payment',
            'english' => 'Processing Payment...',
            'spanish' => 'Procesando Pago...'
        ],
        'payment_successful' => [
            'category' => 'payment',
            'english' => 'Payment Successful',
            'spanish' => 'Pago Exitoso'
        ],
        'payment_failed' => [
            'category' => 'payment',
            'english' => 'Payment Failed',
            'spanish' => 'Pago Fallido'
        ],
        
        // Investment Form Fields
        'full_name' => [
            'category' => 'form_fields',
            'english' => 'Full Name',
            'spanish' => 'Nombre Completo'
        ],
        'email_address' => [
            'category' => 'form_fields',
            'english' => 'Email Address',
            'spanish' => 'Dirección de Correo Electrónico'
        ],
        'phone_number' => [
            'category' => 'form_fields',
            'english' => 'Phone Number',
            'spanish' => 'Número de Teléfono'
        ],
        'country' => [
            'category' => 'form_fields',
            'english' => 'Country',
            'spanish' => 'País'
        ],
        'referral_code' => [
            'category' => 'form_fields',
            'english' => 'Referral Code',
            'spanish' => 'Código de Referido'
        ],
        'optional' => [
            'category' => 'form_fields',
            'english' => 'Optional',
            'spanish' => 'Opcional'
        ],
        'required' => [
            'category' => 'form_fields',
            'english' => 'Required',
            'spanish' => 'Requerido'
        ],
        
        // Terms and Conditions
        'terms_and_conditions' => [
            'category' => 'terms',
            'english' => 'Terms and Conditions',
            'spanish' => 'Términos y Condiciones'
        ],
        'i_agree_to_terms' => [
            'category' => 'terms',
            'english' => 'I agree to the Terms and Conditions',
            'spanish' => 'Acepto los Términos y Condiciones'
        ],
        'i_understand_investment_risks' => [
            'category' => 'terms',
            'english' => 'I understand the investment risks',
            'spanish' => 'Entiendo los riesgos de inversión'
        ],
        'i_confirm_investment_details' => [
            'category' => 'terms',
            'english' => 'I confirm the investment details are correct',
            'spanish' => 'Confirmo que los detalles de inversión son correctos'
        ],
        
        // Investment Status Messages
        'investment_pending' => [
            'category' => 'investment_status',
            'english' => 'Investment Pending',
            'spanish' => 'Inversión Pendiente'
        ],
        'investment_confirmed' => [
            'category' => 'investment_status',
            'english' => 'Investment Confirmed',
            'spanish' => 'Inversión Confirmada'
        ],
        'investment_processing' => [
            'category' => 'investment_status',
            'english' => 'Investment Processing',
            'spanish' => 'Inversión en Proceso'
        ],
        'investment_completed' => [
            'category' => 'investment_status',
            'english' => 'Investment Completed',
            'spanish' => 'Inversión Completada'
        ],
        
        // Error Messages
        'please_connect_wallet' => [
            'category' => 'error_messages',
            'english' => 'Please connect your wallet to continue',
            'spanish' => 'Por favor conecta tu billetera para continuar'
        ],
        'please_select_package' => [
            'category' => 'error_messages',
            'english' => 'Please select an investment package',
            'spanish' => 'Por favor selecciona un paquete de inversión'
        ],
        'please_fill_required_fields' => [
            'category' => 'error_messages',
            'english' => 'Please fill in all required fields',
            'spanish' => 'Por favor completa todos los campos requeridos'
        ],
        'please_accept_terms' => [
            'category' => 'error_messages',
            'english' => 'Please accept the terms and conditions',
            'spanish' => 'Por favor acepta los términos y condiciones'
        ],
        'invalid_email_format' => [
            'category' => 'error_messages',
            'english' => 'Invalid email format',
            'spanish' => 'Formato de correo electrónico inválido'
        ],
        'connection_error' => [
            'category' => 'error_messages',
            'english' => 'Connection error. Please try again.',
            'spanish' => 'Error de conexión. Por favor intenta de nuevo.'
        ],
        
        // Success Messages
        'investment_successful' => [
            'category' => 'success_messages',
            'english' => 'Investment completed successfully!',
            'spanish' => '¡Inversión completada exitosamente!'
        ],
        'welcome_to_aureus' => [
            'category' => 'success_messages',
            'english' => 'Welcome to Aureus Angel Alliance!',
            'spanish' => '¡Bienvenido a Aureus Angel Alliance!'
        ],
        'check_email_confirmation' => [
            'category' => 'success_messages',
            'english' => 'Please check your email for confirmation',
            'spanish' => 'Por favor revisa tu correo electrónico para confirmación'
        ]
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
        'message' => 'Investment translation keys created successfully',
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
