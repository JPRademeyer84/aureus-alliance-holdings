<?php
// Execute all translation key creation scripts via web interface
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $results = [];
    $totalKeys = 0;
    $totalTranslations = 0;
    
    // Script 1: Dashboard Translation Keys
    echo "Creating dashboard translation keys...\n";
    
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
    
    // Dashboard translation keys
    $dashboardKeys = [
        'dashboard' => ['category' => 'dashboard_navigation', 'english' => 'Dashboard', 'spanish' => 'Panel de Control'],
        'my_profile' => ['category' => 'dashboard_navigation', 'english' => 'My Profile', 'spanish' => 'Mi Perfil'],
        'investment_packages' => ['category' => 'dashboard_navigation', 'english' => 'Investment Packages', 'spanish' => 'Paquetes de Inversión'],
        'investment_history' => ['category' => 'dashboard_navigation', 'english' => 'Investment History', 'spanish' => 'Historial de Inversiones'],
        'delivery_countdown' => ['category' => 'dashboard_navigation', 'english' => 'Delivery Countdown', 'spanish' => 'Cuenta Regresiva de Entrega'],
        'portfolio_overview' => ['category' => 'dashboard_navigation', 'english' => 'Portfolio Overview', 'spanish' => 'Resumen de Cartera'],
        'affiliate_program' => ['category' => 'dashboard_navigation', 'english' => 'Affiliate Program', 'spanish' => 'Programa de Afiliados'],
        'commission_wallet' => ['category' => 'dashboard_navigation', 'english' => 'Commission Wallet', 'spanish' => 'Billetera de Comisiones'],
        'nft_coupons' => ['category' => 'dashboard_navigation', 'english' => 'NFT Coupons', 'spanish' => 'Cupones NFT'],
        'gold_diggers_club' => ['category' => 'dashboard_navigation', 'english' => 'Gold Diggers Club', 'spanish' => 'Club de Buscadores de Oro'],
        'contact_support' => ['category' => 'dashboard_navigation', 'english' => 'Contact Support', 'spanish' => 'Contactar Soporte'],
        'account_settings' => ['category' => 'dashboard_navigation', 'english' => 'Account Settings', 'spanish' => 'Configuración de Cuenta'],
        'logout' => ['category' => 'dashboard_navigation', 'english' => 'Logout', 'spanish' => 'Cerrar Sesión'],
        
        // Dashboard Statistics
        'commission_earnings' => ['category' => 'dashboard_stats', 'english' => 'Commission Earnings', 'spanish' => 'Ganancias por Comisión'],
        'available_balance' => ['category' => 'dashboard_stats', 'english' => 'Available Balance', 'spanish' => 'Saldo Disponible'],
        'total_investments' => ['category' => 'dashboard_stats', 'english' => 'Total Investments', 'spanish' => 'Inversiones Totales'],
        'portfolio_value' => ['category' => 'dashboard_stats', 'english' => 'Portfolio Value', 'spanish' => 'Valor de Cartera'],
        'aureus_shares' => ['category' => 'dashboard_stats', 'english' => 'Aureus Shares', 'spanish' => 'Acciones Aureus'],
        'activity' => ['category' => 'dashboard_stats', 'english' => 'Activity', 'spanish' => 'Actividad'],
        'nft_packs_earned' => ['category' => 'dashboard_stats', 'english' => 'NFT packs earned', 'spanish' => 'Paquetes NFT ganados'],
        'nft_available' => ['category' => 'dashboard_stats', 'english' => 'NFT available', 'spanish' => 'NFT disponibles'],
        'active' => ['category' => 'dashboard_stats', 'english' => 'active', 'spanish' => 'activo'],
        'completed' => ['category' => 'dashboard_stats', 'english' => 'completed', 'spanish' => 'completado'],
        'expected_roi' => ['category' => 'dashboard_stats', 'english' => 'expected ROI', 'spanish' => 'ROI esperado'],
        'annual_dividends' => ['category' => 'dashboard_stats', 'english' => 'annual dividends', 'spanish' => 'dividendos anuales'],
        'pending' => ['category' => 'dashboard_stats', 'english' => 'pending', 'spanish' => 'pendiente'],
        
        // Wallet Connection
        'wallet_connection' => ['category' => 'wallet', 'english' => 'Wallet Connection', 'spanish' => 'Conexión de Billetera'],
        'connect_wallet_to_start' => ['category' => 'wallet', 'english' => 'Connect wallet to start investing', 'spanish' => 'Conecta la billetera para comenzar a invertir'],
        'connect_safepal' => ['category' => 'wallet', 'english' => 'Connect SafePal', 'spanish' => 'Conectar SafePal'],
        'connected' => ['category' => 'wallet', 'english' => 'Connected', 'spanish' => 'Conectado'],
        'disconnect' => ['category' => 'wallet', 'english' => 'Disconnect', 'spanish' => 'Desconectar'],
        
        // Welcome Section
        'welcome_back_user' => ['category' => 'dashboard_welcome', 'english' => 'Welcome back, {username}!', 'spanish' => '¡Bienvenido de vuelta, {username}!'],
        'ready_to_grow_wealth' => ['category' => 'dashboard_welcome', 'english' => 'Ready to grow your wealth?', 'spanish' => '¿Listo para hacer crecer tu riqueza?'],
        'last_login' => ['category' => 'dashboard_welcome', 'english' => 'Last login:', 'spanish' => 'Último acceso:'],
        'investor_badge' => ['category' => 'dashboard_welcome', 'english' => 'INVESTOR', 'spanish' => 'INVERSOR'],
        
        // Investment Packages
        'available_investment_packages' => ['category' => 'investment_packages', 'english' => 'Available Investment Packages', 'spanish' => 'Paquetes de Inversión Disponibles'],
        'view_all' => ['category' => 'investment_packages', 'english' => 'View All', 'spanish' => 'Ver Todo'],
        'invest_now' => ['category' => 'investment_packages', 'english' => 'Invest Now', 'spanish' => 'Invertir Ahora'],
        'no_packages_available' => ['category' => 'investment_packages', 'english' => 'No investment packages available at the moment.', 'spanish' => 'No hay paquetes de inversión disponibles en este momento.'],
        
        // Quick Actions
        'quick_actions' => ['category' => 'dashboard_actions', 'english' => 'Quick Actions', 'spanish' => 'Acciones Rápidas'],
        'browse_packages' => ['category' => 'dashboard_actions', 'english' => 'Browse Packages', 'spanish' => 'Explorar Paquetes'],
        'explore_available_opportunities' => ['category' => 'dashboard_actions', 'english' => 'Explore available investment opportunities', 'spanish' => 'Explora las oportunidades de inversión disponibles'],
        'view_past_current_investments' => ['category' => 'dashboard_actions', 'english' => 'View your past and current investments', 'spanish' => 'Ve tus inversiones pasadas y actuales'],
        'track_nft_roi_delivery_180' => ['category' => 'dashboard_actions', 'english' => 'Track NFT & ROI delivery (180 days)', 'spanish' => 'Rastrea la entrega de NFT y ROI (180 días)'],
        'check_portfolio_performance' => ['category' => 'dashboard_actions', 'english' => 'Check your portfolio performance', 'spanish' => 'Verifica el rendimiento de tu cartera'],
        'compete_250k_bonus_pool' => ['category' => 'dashboard_actions', 'english' => 'Compete for $250K bonus pool', 'spanish' => 'Compite por el fondo de bonificación de $250K'],
        'get_help_support_team' => ['category' => 'dashboard_actions', 'english' => 'Get help from our support team', 'spanish' => 'Obtén ayuda de nuestro equipo de soporte'],
        'manage_account_preferences' => ['category' => 'dashboard_actions', 'english' => 'Manage your account preferences', 'spanish' => 'Gestiona las preferencias de tu cuenta'],
        'connect_manage_wallets' => ['category' => 'dashboard_actions', 'english' => 'Connect and manage your wallets', 'spanish' => 'Conecta y gestiona tus billeteras'],
        
        // Loading States
        'loading_dashboard' => ['category' => 'loading_states', 'english' => 'Loading dashboard...', 'spanish' => 'Cargando panel de control...'],
        
        // Company Branding
        'aureus_capital' => ['category' => 'branding', 'english' => 'Aureus Capital', 'spanish' => 'Aureus Capital'],
        'investment_portal' => ['category' => 'branding', 'english' => 'Investment Portal', 'spanish' => 'Portal de Inversión']
    ];
    
    $createdKeys = 0;
    $createdTranslations = 0;
    
    foreach ($dashboardKeys as $keyName => $data) {
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
    
    $totalKeys += $createdKeys;
    $totalTranslations += $createdTranslations;

    echo "Dashboard keys created: $createdKeys keys, $createdTranslations translations\n";

    // Script 2: Investment and Form Translation Keys
    echo "Creating investment and form translation keys...\n";

    $investmentKeys = [
        // Investment Package Names
        'bronze_package' => ['category' => 'investment_packages', 'english' => 'Bronze', 'spanish' => 'Bronce'],
        'silver_package' => ['category' => 'investment_packages', 'english' => 'Silver', 'spanish' => 'Plata'],
        'gold_package' => ['category' => 'investment_packages', 'english' => 'Gold', 'spanish' => 'Oro'],
        'platinum_package' => ['category' => 'investment_packages', 'english' => 'Platinum', 'spanish' => 'Platino'],
        'diamond_package' => ['category' => 'investment_packages', 'english' => 'Diamond', 'spanish' => 'Diamante'],
        'obsidian_package' => ['category' => 'investment_packages', 'english' => 'Obsidian', 'spanish' => 'Obsidiana'],

        // Investment Details
        'shares' => ['category' => 'investment_details', 'english' => 'shares', 'spanish' => 'acciones'],
        'yield' => ['category' => 'investment_details', 'english' => 'yield', 'spanish' => 'rendimiento'],
        'quarterly_dividends' => ['category' => 'investment_details', 'english' => 'Quarterly Dividends', 'spanish' => 'Dividendos Trimestrales'],
        'bonuses' => ['category' => 'investment_details', 'english' => 'Bonuses', 'spanish' => 'Bonificaciones'],
        'select_package' => ['category' => 'investment_details', 'english' => 'Select Package', 'spanish' => 'Seleccionar Paquete'],
        'selected' => ['category' => 'investment_details', 'english' => 'Selected', 'spanish' => 'Seleccionado'],

        // Payment and Wallet
        'payment_method' => ['category' => 'payment', 'english' => 'Payment Method', 'spanish' => 'Método de Pago'],
        'connect_wallet' => ['category' => 'payment', 'english' => 'Connect Wallet', 'spanish' => 'Conectar Billetera'],
        'wallet_connected' => ['category' => 'payment', 'english' => 'Wallet Connected', 'spanish' => 'Billetera Conectada'],
        'wallet_address' => ['category' => 'payment', 'english' => 'Wallet Address', 'spanish' => 'Dirección de Billetera'],
        'payment_amount' => ['category' => 'payment', 'english' => 'Payment Amount', 'spanish' => 'Monto de Pago'],
        'transaction_hash' => ['category' => 'payment', 'english' => 'Transaction Hash', 'spanish' => 'Hash de Transacción'],
        'confirm_payment' => ['category' => 'payment', 'english' => 'Confirm Payment', 'spanish' => 'Confirmar Pago'],
        'processing_payment' => ['category' => 'payment', 'english' => 'Processing Payment...', 'spanish' => 'Procesando Pago...'],
        'payment_successful' => ['category' => 'payment', 'english' => 'Payment Successful', 'spanish' => 'Pago Exitoso'],
        'payment_failed' => ['category' => 'payment', 'english' => 'Payment Failed', 'spanish' => 'Pago Fallido'],

        // Form Fields
        'full_name' => ['category' => 'form_fields', 'english' => 'Full Name', 'spanish' => 'Nombre Completo'],
        'email_address' => ['category' => 'form_fields', 'english' => 'Email Address', 'spanish' => 'Dirección de Correo Electrónico'],
        'phone_number' => ['category' => 'form_fields', 'english' => 'Phone Number', 'spanish' => 'Número de Teléfono'],
        'country' => ['category' => 'form_fields', 'english' => 'Country', 'spanish' => 'País'],
        'referral_code' => ['category' => 'form_fields', 'english' => 'Referral Code', 'spanish' => 'Código de Referido'],
        'optional' => ['category' => 'form_fields', 'english' => 'Optional', 'spanish' => 'Opcional'],
        'required' => ['category' => 'form_fields', 'english' => 'Required', 'spanish' => 'Requerido'],

        // Terms and Conditions
        'terms_and_conditions' => ['category' => 'terms', 'english' => 'Terms and Conditions', 'spanish' => 'Términos y Condiciones'],
        'i_agree_to_terms' => ['category' => 'terms', 'english' => 'I agree to the Terms and Conditions', 'spanish' => 'Acepto los Términos y Condiciones'],
        'i_understand_investment_risks' => ['category' => 'terms', 'english' => 'I understand the investment risks', 'spanish' => 'Entiendo los riesgos de inversión'],
        'i_confirm_investment_details' => ['category' => 'terms', 'english' => 'I confirm the investment details are correct', 'spanish' => 'Confirmo que los detalles de inversión son correctos'],

        // Error Messages
        'please_connect_wallet' => ['category' => 'error_messages', 'english' => 'Please connect your wallet to continue', 'spanish' => 'Por favor conecta tu billetera para continuar'],
        'please_select_package' => ['category' => 'error_messages', 'english' => 'Please select an investment package', 'spanish' => 'Por favor selecciona un paquete de inversión'],
        'please_fill_required_fields' => ['category' => 'error_messages', 'english' => 'Please fill in all required fields', 'spanish' => 'Por favor completa todos los campos requeridos'],
        'please_accept_terms' => ['category' => 'error_messages', 'english' => 'Please accept the terms and conditions', 'spanish' => 'Por favor acepta los términos y condiciones'],
        'invalid_email_format' => ['category' => 'error_messages', 'english' => 'Invalid email format', 'spanish' => 'Formato de correo electrónico inválido'],
        'connection_error' => ['category' => 'error_messages', 'english' => 'Connection error. Please try again.', 'spanish' => 'Error de conexión. Por favor intenta de nuevo.'],

        // Success Messages
        'investment_successful' => ['category' => 'success_messages', 'english' => 'Investment completed successfully!', 'spanish' => '¡Inversión completada exitosamente!'],
        'welcome_to_aureus' => ['category' => 'success_messages', 'english' => 'Welcome to Aureus Angel Alliance!', 'spanish' => '¡Bienvenido a Aureus Angel Alliance!'],
        'check_email_confirmation' => ['category' => 'success_messages', 'english' => 'Please check your email for confirmation', 'spanish' => 'Por favor revisa tu correo electrónico para confirmación']
    ];

    $createdKeys2 = 0;
    $createdTranslations2 = 0;

    foreach ($investmentKeys as $keyName => $data) {
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
            $createdKeys2++;
        }

        // Insert English translation
        $englishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved)
                        VALUES (?, ?, ?, TRUE)
                        ON DUPLICATE KEY UPDATE
                        translation_text = VALUES(translation_text),
                        is_approved = TRUE";
        $englishStmt = $db->prepare($englishQuery);
        $englishStmt->execute([$keyId, $englishId, $data['english']]);
        if ($db->lastInsertId() > 0) $createdTranslations2++;

        // Insert Spanish translation
        $spanishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved)
                        VALUES (?, ?, ?, TRUE)
                        ON DUPLICATE KEY UPDATE
                        translation_text = VALUES(translation_text),
                        is_approved = TRUE";
        $spanishStmt = $db->prepare($spanishQuery);
        $spanishStmt->execute([$keyId, $spanishId, $data['spanish']]);
        if ($db->lastInsertId() > 0) $createdTranslations2++;
    }

    $totalKeys += $createdKeys2;
    $totalTranslations += $createdTranslations2;

    echo "Investment keys created: $createdKeys2 keys, $createdTranslations2 translations\n";

    // Script 3: Profile, Affiliate, and Support Translation Keys
    echo "Creating profile, affiliate, and support translation keys...\n";

    $profileKeys = [
        // User Profile
        'personal_information' => ['category' => 'user_profile', 'english' => 'Personal Information', 'spanish' => 'Información Personal'],
        'profile_picture' => ['category' => 'user_profile', 'english' => 'Profile Picture', 'spanish' => 'Foto de Perfil'],
        'upload_photo' => ['category' => 'user_profile', 'english' => 'Upload Photo', 'spanish' => 'Subir Foto'],
        'change_password' => ['category' => 'user_profile', 'english' => 'Change Password', 'spanish' => 'Cambiar Contraseña'],
        'current_password' => ['category' => 'user_profile', 'english' => 'Current Password', 'spanish' => 'Contraseña Actual'],
        'new_password' => ['category' => 'user_profile', 'english' => 'New Password', 'spanish' => 'Nueva Contraseña'],
        'confirm_new_password' => ['category' => 'user_profile', 'english' => 'Confirm New Password', 'spanish' => 'Confirmar Nueva Contraseña'],
        'save_changes' => ['category' => 'user_profile', 'english' => 'Save Changes', 'spanish' => 'Guardar Cambios'],
        'profile_updated_successfully' => ['category' => 'user_profile', 'english' => 'Profile updated successfully', 'spanish' => 'Perfil actualizado exitosamente'],

        // KYC Verification
        'kyc_verification' => ['category' => 'kyc', 'english' => 'KYC Verification', 'spanish' => 'Verificación KYC'],
        'identity_verification' => ['category' => 'kyc', 'english' => 'Identity Verification', 'spanish' => 'Verificación de Identidad'],
        'upload_id_document' => ['category' => 'kyc', 'english' => 'Upload ID Document', 'spanish' => 'Subir Documento de Identidad'],
        'drivers_license' => ['category' => 'kyc', 'english' => 'Driver\'s License', 'spanish' => 'Licencia de Conducir'],
        'national_id' => ['category' => 'kyc', 'english' => 'National ID', 'spanish' => 'Cédula Nacional'],
        'passport' => ['category' => 'kyc', 'english' => 'Passport', 'spanish' => 'Pasaporte'],
        'verification_pending' => ['category' => 'kyc', 'english' => 'Verification Pending', 'spanish' => 'Verificación Pendiente'],
        'verification_approved' => ['category' => 'kyc', 'english' => 'Verification Approved', 'spanish' => 'Verificación Aprobada'],
        'verification_rejected' => ['category' => 'kyc', 'english' => 'Verification Rejected', 'spanish' => 'Verificación Rechazada'],

        // Affiliate Program
        'referral_link' => ['category' => 'affiliate', 'english' => 'Referral Link', 'spanish' => 'Enlace de Referido'],
        'copy_link' => ['category' => 'affiliate', 'english' => 'Copy Link', 'spanish' => 'Copiar Enlace'],
        'share_on_social_media' => ['category' => 'affiliate', 'english' => 'Share on Social Media', 'spanish' => 'Compartir en Redes Sociales'],
        'referral_statistics' => ['category' => 'affiliate', 'english' => 'Referral Statistics', 'spanish' => 'Estadísticas de Referidos'],
        'total_referrals' => ['category' => 'affiliate', 'english' => 'Total Referrals', 'spanish' => 'Total de Referidos'],
        'active_referrals' => ['category' => 'affiliate', 'english' => 'Active Referrals', 'spanish' => 'Referidos Activos'],
        'commission_earned' => ['category' => 'affiliate', 'english' => 'Commission Earned', 'spanish' => 'Comisión Ganada'],
        'pending_commissions' => ['category' => 'affiliate', 'english' => 'Pending Commissions', 'spanish' => 'Comisiones Pendientes'],
        'commission_history' => ['category' => 'affiliate', 'english' => 'Commission History', 'spanish' => 'Historial de Comisiones'],
        'withdraw_commissions' => ['category' => 'affiliate', 'english' => 'Withdraw Commissions', 'spanish' => 'Retirar Comisiones'],
        'minimum_withdrawal' => ['category' => 'affiliate', 'english' => 'Minimum Withdrawal', 'spanish' => 'Retiro Mínimo'],
        'withdrawal_address' => ['category' => 'affiliate', 'english' => 'Withdrawal Address', 'spanish' => 'Dirección de Retiro'],

        // Support & Contact
        'contact_subject' => ['category' => 'support', 'english' => 'Subject', 'spanish' => 'Asunto'],
        'message' => ['category' => 'support', 'english' => 'Message', 'spanish' => 'Mensaje'],
        'send_message' => ['category' => 'support', 'english' => 'Send Message', 'spanish' => 'Enviar Mensaje'],
        'live_chat' => ['category' => 'support', 'english' => 'Live Chat', 'spanish' => 'Chat en Vivo'],
        'start_chat' => ['category' => 'support', 'english' => 'Start Chat', 'spanish' => 'Iniciar Chat'],
        'chat_with_support' => ['category' => 'support', 'english' => 'Chat with Support', 'spanish' => 'Chatear con Soporte'],
        'support_hours' => ['category' => 'support', 'english' => 'Support Hours', 'spanish' => 'Horarios de Soporte'],
        'monday_to_friday' => ['category' => 'support', 'english' => 'Monday to Friday', 'spanish' => 'Lunes a Viernes'],
        'business_hours' => ['category' => 'support', 'english' => '9:00 AM - 6:00 PM EST', 'spanish' => '9:00 AM - 6:00 PM EST'],
        'offline_message' => ['category' => 'support', 'english' => 'Leave an offline message', 'spanish' => 'Dejar un mensaje fuera de línea'],

        // Gold Diggers Club / Leaderboard
        'leaderboard' => ['category' => 'leaderboard', 'english' => 'Leaderboard', 'spanish' => 'Tabla de Clasificación'],
        'rank' => ['category' => 'leaderboard', 'english' => 'Rank', 'spanish' => 'Rango'],
        'username' => ['category' => 'leaderboard', 'english' => 'Username', 'spanish' => 'Nombre de Usuario'],
        'bonus_pool_share' => ['category' => 'leaderboard', 'english' => 'Bonus Pool Share', 'spanish' => 'Participación en Fondo de Bonificación'],
        'qualification_period' => ['category' => 'leaderboard', 'english' => 'Qualification Period', 'spanish' => 'Período de Calificación'],
        'minimum_referrals_required' => ['category' => 'leaderboard', 'english' => 'Minimum $2,500 in direct referrals required', 'spanish' => 'Se requiere un mínimo de $2,500 en referidos directos'],

        // Portfolio & Investment History
        'investment_date' => ['category' => 'portfolio', 'english' => 'Investment Date', 'spanish' => 'Fecha de Inversión'],
        'package_name' => ['category' => 'portfolio', 'english' => 'Package Name', 'spanish' => 'Nombre del Paquete'],
        'amount_invested' => ['category' => 'portfolio', 'english' => 'Amount Invested', 'spanish' => 'Monto Invertido'],
        'current_value' => ['category' => 'portfolio', 'english' => 'Current Value', 'spanish' => 'Valor Actual'],
        'roi_percentage' => ['category' => 'portfolio', 'english' => 'ROI %', 'spanish' => 'ROI %'],
        'status' => ['category' => 'portfolio', 'english' => 'Status', 'spanish' => 'Estado'],
        'maturity_date' => ['category' => 'portfolio', 'english' => 'Maturity Date', 'spanish' => 'Fecha de Vencimiento'],
        'days_remaining' => ['category' => 'portfolio', 'english' => 'Days Remaining', 'spanish' => 'Días Restantes'],

        // NFT Coupons
        'coupon_code' => ['category' => 'nft_coupons', 'english' => 'Coupon Code', 'spanish' => 'Código de Cupón'],
        'redeem_coupon' => ['category' => 'nft_coupons', 'english' => 'Redeem Coupon', 'spanish' => 'Canjear Cupón'],
        'enter_coupon_code' => ['category' => 'nft_coupons', 'english' => 'Enter coupon code', 'spanish' => 'Ingresa el código de cupón'],
        'coupon_redeemed_successfully' => ['category' => 'nft_coupons', 'english' => 'Coupon redeemed successfully!', 'spanish' => '¡Cupón canjeado exitosamente!'],
        'invalid_coupon_code' => ['category' => 'nft_coupons', 'english' => 'Invalid coupon code', 'spanish' => 'Código de cupón inválido'],
        'coupon_already_used' => ['category' => 'nft_coupons', 'english' => 'Coupon already used', 'spanish' => 'Cupón ya utilizado'],

        // General Actions
        'edit' => ['category' => 'general_actions', 'english' => 'Edit', 'spanish' => 'Editar'],
        'delete' => ['category' => 'general_actions', 'english' => 'Delete', 'spanish' => 'Eliminar'],
        'cancel' => ['category' => 'general_actions', 'english' => 'Cancel', 'spanish' => 'Cancelar'],
        'confirm' => ['category' => 'general_actions', 'english' => 'Confirm', 'spanish' => 'Confirmar'],
        'submit' => ['category' => 'general_actions', 'english' => 'Submit', 'spanish' => 'Enviar'],
        'close' => ['category' => 'general_actions', 'english' => 'Close', 'spanish' => 'Cerrar'],
        'back' => ['category' => 'general_actions', 'english' => 'Back', 'spanish' => 'Atrás'],
        'next' => ['category' => 'general_actions', 'english' => 'Next', 'spanish' => 'Siguiente'],
        'previous' => ['category' => 'general_actions', 'english' => 'Previous', 'spanish' => 'Anterior'],
        'refresh' => ['category' => 'general_actions', 'english' => 'Refresh', 'spanish' => 'Actualizar']
    ];

    $createdKeys3 = 0;
    $createdTranslations3 = 0;

    foreach ($profileKeys as $keyName => $data) {
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
            $createdKeys3++;
        }

        // Insert English translation
        $englishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved)
                        VALUES (?, ?, ?, TRUE)
                        ON DUPLICATE KEY UPDATE
                        translation_text = VALUES(translation_text),
                        is_approved = TRUE";
        $englishStmt = $db->prepare($englishQuery);
        $englishStmt->execute([$keyId, $englishId, $data['english']]);
        if ($db->lastInsertId() > 0) $createdTranslations3++;

        // Insert Spanish translation
        $spanishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved)
                        VALUES (?, ?, ?, TRUE)
                        ON DUPLICATE KEY UPDATE
                        translation_text = VALUES(translation_text),
                        is_approved = TRUE";
        $spanishStmt = $db->prepare($spanishQuery);
        $spanishStmt->execute([$keyId, $spanishId, $data['spanish']]);
        if ($db->lastInsertId() > 0) $createdTranslations3++;
    }

    $totalKeys += $createdKeys3;
    $totalTranslations += $createdTranslations3;

    echo "Profile/Affiliate/Support keys created: $createdKeys3 keys, $createdTranslations3 translations\n";

    echo json_encode([
        'success' => true,
        'message' => 'All dashboard translation keys created successfully!',
        'summary' => [
            'dashboard_keys_processed' => count($dashboardKeys),
            'investment_keys_processed' => count($investmentKeys),
            'profile_keys_processed' => count($profileKeys),
            'total_keys_processed' => count($dashboardKeys) + count($investmentKeys) + count($profileKeys),
            'total_new_keys_created' => $totalKeys,
            'total_new_translations_created' => $totalTranslations,
            'languages_supported' => ['English', 'Spanish'],
            'categories_created' => [
                'dashboard_navigation', 'dashboard_stats', 'wallet', 'dashboard_welcome',
                'investment_packages', 'dashboard_actions', 'loading_states', 'branding',
                'investment_details', 'payment', 'form_fields', 'terms', 'error_messages',
                'success_messages', 'user_profile', 'kyc', 'affiliate', 'support',
                'leaderboard', 'portfolio', 'nft_coupons', 'general_actions'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
