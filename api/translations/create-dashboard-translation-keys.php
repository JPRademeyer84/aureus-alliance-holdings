<?php
// Create comprehensive translation keys for user dashboard (English → Spanish focus)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    
    // Comprehensive dashboard translation keys with English and Spanish translations
    $translationKeys = [
        // Dashboard Navigation & Sidebar
        'dashboard' => [
            'category' => 'dashboard_navigation',
            'english' => 'Dashboard',
            'spanish' => 'Panel de Control'
        ],
        'my_profile' => [
            'category' => 'dashboard_navigation',
            'english' => 'My Profile',
            'spanish' => 'Mi Perfil'
        ],
        'investment_packages' => [
            'category' => 'dashboard_navigation',
            'english' => 'Investment Packages',
            'spanish' => 'Paquetes de Inversión'
        ],
        'investment_history' => [
            'category' => 'dashboard_navigation',
            'english' => 'Investment History',
            'spanish' => 'Historial de Inversiones'
        ],
        'delivery_countdown' => [
            'category' => 'dashboard_navigation',
            'english' => 'Delivery Countdown',
            'spanish' => 'Cuenta Regresiva de Entrega'
        ],
        'portfolio_overview' => [
            'category' => 'dashboard_navigation',
            'english' => 'Portfolio Overview',
            'spanish' => 'Resumen de Cartera'
        ],
        'affiliate_program' => [
            'category' => 'dashboard_navigation',
            'english' => 'Affiliate Program',
            'spanish' => 'Programa de Afiliados'
        ],
        'commission_wallet' => [
            'category' => 'dashboard_navigation',
            'english' => 'Commission Wallet',
            'spanish' => 'Billetera de Comisiones'
        ],
        'nft_coupons' => [
            'category' => 'dashboard_navigation',
            'english' => 'NFT Coupons',
            'spanish' => 'Cupones NFT'
        ],
        'gold_diggers_club' => [
            'category' => 'dashboard_navigation',
            'english' => 'Gold Diggers Club',
            'spanish' => 'Club de Buscadores de Oro'
        ],
        'contact_support' => [
            'category' => 'dashboard_navigation',
            'english' => 'Contact Support',
            'spanish' => 'Contactar Soporte'
        ],
        'account_settings' => [
            'category' => 'dashboard_navigation',
            'english' => 'Account Settings',
            'spanish' => 'Configuración de Cuenta'
        ],
        'logout' => [
            'category' => 'dashboard_navigation',
            'english' => 'Logout',
            'spanish' => 'Cerrar Sesión'
        ],
        
        // Dashboard Header Descriptions
        'welcome_back_to_portal' => [
            'category' => 'dashboard_headers',
            'english' => 'Welcome back to your investment portal',
            'spanish' => 'Bienvenido de vuelta a tu portal de inversión'
        ],
        'explore_investment_opportunities' => [
            'category' => 'dashboard_headers',
            'english' => 'Explore available investment opportunities',
            'spanish' => 'Explora las oportunidades de inversión disponibles'
        ],
        'track_investment_performance' => [
            'category' => 'dashboard_headers',
            'english' => 'Track your investment performance',
            'spanish' => 'Rastrea el rendimiento de tus inversiones'
        ],
        'track_nft_roi_delivery' => [
            'category' => 'dashboard_headers',
            'english' => 'Track your NFT and ROI delivery schedules',
            'spanish' => 'Rastrea tus horarios de entrega de NFT y ROI'
        ],
        'monitor_portfolio_growth' => [
            'category' => 'dashboard_headers',
            'english' => 'Monitor your portfolio growth',
            'spanish' => 'Monitorea el crecimiento de tu cartera'
        ],
        'grow_network_earn_commissions' => [
            'category' => 'dashboard_headers',
            'english' => 'Grow your network and earn commissions',
            'spanish' => 'Haz crecer tu red y gana comisiones'
        ],
        'manage_referral_earnings' => [
            'category' => 'dashboard_headers',
            'english' => 'Manage your referral earnings and withdrawals',
            'spanish' => 'Gestiona tus ganancias de referidos y retiros'
        ],
        'compete_for_bonus_pool' => [
            'category' => 'dashboard_headers',
            'english' => 'Compete for the $250K bonus pool',
            'spanish' => 'Compite por el fondo de bonificación de $250K'
        ],
        
        // Wallet Connection
        'wallet_connection' => [
            'category' => 'wallet',
            'english' => 'Wallet Connection',
            'spanish' => 'Conexión de Billetera'
        ],
        'connect_wallet_to_start' => [
            'category' => 'wallet',
            'english' => 'Connect wallet to start investing',
            'spanish' => 'Conecta la billetera para comenzar a invertir'
        ],
        'connect_safepal' => [
            'category' => 'wallet',
            'english' => 'Connect SafePal',
            'spanish' => 'Conectar SafePal'
        ],
        'connected' => [
            'category' => 'wallet',
            'english' => 'Connected',
            'spanish' => 'Conectado'
        ],
        'disconnect' => [
            'category' => 'wallet',
            'english' => 'Disconnect',
            'spanish' => 'Desconectar'
        ],
        
        // Dashboard Welcome Section
        'welcome_back_user' => [
            'category' => 'dashboard_welcome',
            'english' => 'Welcome back, {username}!',
            'spanish' => '¡Bienvenido de vuelta, {username}!'
        ],
        'ready_to_grow_wealth' => [
            'category' => 'dashboard_welcome',
            'english' => 'Ready to grow your wealth?',
            'spanish' => '¿Listo para hacer crecer tu riqueza?'
        ],
        'last_login' => [
            'category' => 'dashboard_welcome',
            'english' => 'Last login:',
            'spanish' => 'Último acceso:'
        ],
        'investor_badge' => [
            'category' => 'dashboard_welcome',
            'english' => 'INVESTOR',
            'spanish' => 'INVERSOR'
        ],
        
        // Dashboard Statistics
        'commission_earnings' => [
            'category' => 'dashboard_stats',
            'english' => 'Commission Earnings',
            'spanish' => 'Ganancias por Comisión'
        ],
        'available_balance' => [
            'category' => 'dashboard_stats',
            'english' => 'Available Balance',
            'spanish' => 'Saldo Disponible'
        ],
        'total_investments' => [
            'category' => 'dashboard_stats',
            'english' => 'Total Investments',
            'spanish' => 'Inversiones Totales'
        ],
        'portfolio_value' => [
            'category' => 'dashboard_stats',
            'english' => 'Portfolio Value',
            'spanish' => 'Valor de Cartera'
        ],
        'aureus_shares' => [
            'category' => 'dashboard_stats',
            'english' => 'Aureus Shares',
            'spanish' => 'Acciones Aureus'
        ],
        'activity' => [
            'category' => 'dashboard_stats',
            'english' => 'Activity',
            'spanish' => 'Actividad'
        ],
        'nft_packs_earned' => [
            'category' => 'dashboard_stats',
            'english' => 'NFT packs earned',
            'spanish' => 'Paquetes NFT ganados'
        ],
        'nft_available' => [
            'category' => 'dashboard_stats',
            'english' => 'NFT available',
            'spanish' => 'NFT disponibles'
        ],
        'active' => [
            'category' => 'dashboard_stats',
            'english' => 'active',
            'spanish' => 'activo'
        ],
        'completed' => [
            'category' => 'dashboard_stats',
            'english' => 'completed',
            'spanish' => 'completado'
        ],
        'expected_roi' => [
            'category' => 'dashboard_stats',
            'english' => 'expected ROI',
            'spanish' => 'ROI esperado'
        ],
        'annual_dividends' => [
            'category' => 'dashboard_stats',
            'english' => 'annual dividends',
            'spanish' => 'dividendos anuales'
        ],
        'pending' => [
            'category' => 'dashboard_stats',
            'english' => 'pending',
            'spanish' => 'pendiente'
        ],

        // Investment Packages
        'available_investment_packages' => [
            'category' => 'investment_packages',
            'english' => 'Available Investment Packages',
            'spanish' => 'Paquetes de Inversión Disponibles'
        ],
        'view_all' => [
            'category' => 'investment_packages',
            'english' => 'View All',
            'spanish' => 'Ver Todo'
        ],
        'aureus_shares' => [
            'category' => 'investment_packages',
            'english' => 'Aureus Shares',
            'spanish' => 'Acciones Aureus'
        ],
        'expected_roi' => [
            'category' => 'investment_packages',
            'english' => 'Expected ROI',
            'spanish' => 'ROI Esperado'
        ],
        'annual_dividends' => [
            'category' => 'investment_packages',
            'english' => 'Annual Dividends',
            'spanish' => 'Dividendos Anuales'
        ],
        'invest_now' => [
            'category' => 'investment_packages',
            'english' => 'Invest Now',
            'spanish' => 'Invertir Ahora'
        ],
        'no_packages_available' => [
            'category' => 'investment_packages',
            'english' => 'No investment packages available at the moment.',
            'spanish' => 'No hay paquetes de inversión disponibles en este momento.'
        ],

        // Quick Actions
        'quick_actions' => [
            'category' => 'dashboard_actions',
            'english' => 'Quick Actions',
            'spanish' => 'Acciones Rápidas'
        ],
        'browse_packages' => [
            'category' => 'dashboard_actions',
            'english' => 'Browse Packages',
            'spanish' => 'Explorar Paquetes'
        ],
        'explore_available_opportunities' => [
            'category' => 'dashboard_actions',
            'english' => 'Explore available investment opportunities',
            'spanish' => 'Explora las oportunidades de inversión disponibles'
        ],
        'view_past_current_investments' => [
            'category' => 'dashboard_actions',
            'english' => 'View your past and current investments',
            'spanish' => 'Ve tus inversiones pasadas y actuales'
        ],
        'track_nft_roi_delivery_180' => [
            'category' => 'dashboard_actions',
            'english' => 'Track NFT & ROI delivery (180 days)',
            'spanish' => 'Rastrea la entrega de NFT y ROI (180 días)'
        ],
        'check_portfolio_performance' => [
            'category' => 'dashboard_actions',
            'english' => 'Check your portfolio performance',
            'spanish' => 'Verifica el rendimiento de tu cartera'
        ],
        'compete_250k_bonus_pool' => [
            'category' => 'dashboard_actions',
            'english' => 'Compete for $250K bonus pool',
            'spanish' => 'Compite por el fondo de bonificación de $250K'
        ],
        'get_help_support_team' => [
            'category' => 'dashboard_actions',
            'english' => 'Get help from our support team',
            'spanish' => 'Obtén ayuda de nuestro equipo de soporte'
        ],
        'manage_account_preferences' => [
            'category' => 'dashboard_actions',
            'english' => 'Manage your account preferences',
            'spanish' => 'Gestiona las preferencias de tu cuenta'
        ],
        'connect_manage_wallets' => [
            'category' => 'dashboard_actions',
            'english' => 'Connect and manage your wallets',
            'spanish' => 'Conecta y gestiona tus billeteras'
        ],

        // Loading States
        'loading_dashboard' => [
            'category' => 'loading_states',
            'english' => 'Loading dashboard...',
            'spanish' => 'Cargando panel de control...'
        ],

        // Company Branding
        'aureus_capital' => [
            'category' => 'branding',
            'english' => 'Aureus Capital',
            'spanish' => 'Aureus Capital'
        ],
        'investment_portal' => [
            'category' => 'branding',
            'english' => 'Investment Portal',
            'spanish' => 'Portal de Inversión'
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
        'message' => 'Dashboard translation keys created successfully',
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
