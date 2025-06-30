<?php
// Create comprehensive translation keys for ALL dashboard components
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
    
    // Comprehensive translation keys for ALL dashboard components
    $translationKeys = [
        // Investment Guide Component
        'how_investment_journey_works' => ['category' => 'investment_guide', 'english' => 'How Your Investment Journey Works', 'spanish' => 'Cómo Funciona Tu Viaje de Inversión'],
        'choose_investment_amount' => ['category' => 'investment_guide', 'english' => 'Choose Your Investment Amount', 'spanish' => 'Elige Tu Monto de Inversión'],
        'select_multiple_packages' => ['category' => 'investment_guide', 'english' => 'Select one or multiple packages to match your budget. Mix and match any combination!', 'spanish' => 'Selecciona uno o múltiples paquetes para ajustarse a tu presupuesto. ¡Mezcla y combina cualquier combinación!'],
        'view_packages' => ['category' => 'investment_guide', 'english' => 'View Packages', 'spanish' => 'Ver Paquetes'],
        'eight_mining_packages' => ['category' => 'investment_guide', 'english' => '8 mining packages: Shovel ($25) to Aureus ($1,000)', 'spanish' => '8 paquetes de minería: Pala ($25) a Aureus ($1,000)'],
        'daily_roi_range' => ['category' => 'investment_guide', 'english' => 'Daily ROI from 1.7% to 5% for 180 days', 'spanish' => 'ROI diario del 1.7% al 5% por 180 días'],
        'includes_nft_shares' => ['category' => 'investment_guide', 'english' => 'Each package includes NFT mining shares', 'spanish' => 'Cada paquete incluye acciones de minería NFT'],
        'higher_packages_better_roi' => ['category' => 'investment_guide', 'english' => 'Higher packages = better daily ROI rates', 'spanish' => 'Paquetes más altos = mejores tasas de ROI diario'],
        
        'connect_safepal_wallet' => ['category' => 'investment_guide', 'english' => 'Connect Your SafePal Wallet', 'spanish' => 'Conecta Tu Billetera SafePal'],
        'secure_usdt_payments' => ['category' => 'investment_guide', 'english' => 'Use your SafePal wallet to make secure USDT payments. All transactions are recorded on blockchain.', 'spanish' => 'Usa tu billetera SafePal para hacer pagos seguros con USDT. Todas las transacciones se registran en blockchain.'],
        'only_safepal_supported' => ['category' => 'investment_guide', 'english' => 'Only SafePal wallet supported for security', 'spanish' => 'Solo billetera SafePal soportada por seguridad'],
        'pay_usdt_multiple_chains' => ['category' => 'investment_guide', 'english' => 'Pay with USDT on multiple chains', 'spanish' => 'Paga con USDT en múltiples cadenas'],
        'instant_transaction_confirmation' => ['category' => 'investment_guide', 'english' => 'Instant transaction confirmation', 'spanish' => 'Confirmación instantánea de transacción'],
        'blockchain_transparency' => ['category' => 'investment_guide', 'english' => 'Blockchain transparency and security', 'spanish' => 'Transparencia y seguridad de blockchain'],
        
        'track_180_day_countdown' => ['category' => 'investment_guide', 'english' => 'Track Your 180-Day Countdown', 'spanish' => 'Rastrea Tu Cuenta Regresiva de 180 Días'],
        'watch_nft_delivery_countdown' => ['category' => 'investment_guide', 'english' => 'Watch your NFT delivery countdown in real-time. Your digital gold shares are being prepared!', 'spanish' => '¡Observa tu cuenta regresiva de entrega de NFT en tiempo real. Tus acciones de oro digital se están preparando!'],
        'view_countdown' => ['category' => 'investment_guide', 'english' => 'View Countdown', 'spanish' => 'Ver Cuenta Regresiva'],
        '180_day_roi_period' => ['category' => 'investment_guide', 'english' => '180-day ROI earning period', 'spanish' => 'Período de ganancias ROI de 180 días'],
        'daily_roi_payments' => ['category' => 'investment_guide', 'english' => 'Daily ROI payments (1.7% to 5%)', 'spanish' => 'Pagos ROI diarios (1.7% al 5%)'],
        'real_time_earnings_tracking' => ['category' => 'investment_guide', 'english' => 'Real-time earnings tracking', 'spanish' => 'Seguimiento de ganancias en tiempo real'],
        'total_roi_range' => ['category' => 'investment_guide', 'english' => 'Total ROI: 306% to 900% over 180 days', 'spanish' => 'ROI total: 306% a 900% en 180 días'],
        
        'earn_referral_commissions' => ['category' => 'investment_guide', 'english' => 'Earn Referral Commissions', 'spanish' => 'Gana Comisiones de Referidos'],
        'share_unique_link' => ['category' => 'investment_guide', 'english' => 'Share your unique link and earn 12% USDT + 12% NFT bonuses on every referral investment.', 'spanish' => 'Comparte tu enlace único y gana 12% USDT + 12% bonos NFT en cada inversión de referido.'],
        'start_referring' => ['category' => 'investment_guide', 'english' => 'Start Referring', 'spanish' => 'Comenzar a Referir'],
        'level_1_commission' => ['category' => 'investment_guide', 'english' => 'Level 1: 12% USDT + 12% NFT bonuses', 'spanish' => 'Nivel 1: 12% USDT + 12% bonos NFT'],
        'level_2_commission' => ['category' => 'investment_guide', 'english' => 'Level 2: 5% USDT + 5% NFT bonuses', 'spanish' => 'Nivel 2: 5% USDT + 5% bonos NFT'],
        'level_3_commission' => ['category' => 'investment_guide', 'english' => 'Level 3: 3% USDT + 3% NFT bonuses', 'spanish' => 'Nivel 3: 3% USDT + 3% bonos NFT'],
        'instant_commission_payouts' => ['category' => 'investment_guide', 'english' => 'Instant commission payouts', 'spanish' => 'Pagos de comisión instantáneos'],
        
        'receive_nfts_roi' => ['category' => 'investment_guide', 'english' => 'Receive Your NFTs & ROI', 'spanish' => 'Recibe Tus NFTs y ROI'],
        'after_180_days_receive' => ['category' => 'investment_guide', 'english' => 'After 180 days, receive your digital gold NFT shares plus your guaranteed ROI amount.', 'spanish' => 'Después de 180 días, recibe tus acciones NFT de oro digital más tu cantidad de ROI garantizada.'],
        'view_portfolio' => ['category' => 'investment_guide', 'english' => 'View Portfolio', 'spanish' => 'Ver Cartera'],
        'nft_mining_shares_range' => ['category' => 'investment_guide', 'english' => 'NFT mining shares (5 to 200 shares)', 'spanish' => 'Acciones de minería NFT (5 a 200 acciones)'],
        'total_roi_amount_range' => ['category' => 'investment_guide', 'english' => 'Total ROI: $76.50 to $9,000 per package', 'spanish' => 'ROI total: $76.50 a $9,000 por paquete'],
        'tradeable_gold_certificates' => ['category' => 'investment_guide', 'english' => 'Tradeable digital gold certificates', 'spanish' => 'Certificados de oro digital comerciables'],
        'polygon_ownership_proof' => ['category' => 'investment_guide', 'english' => 'Polygon blockchain ownership proof', 'spanish' => 'Prueba de propiedad en blockchain Polygon'],
        
        'enjoy_quarterly_dividends' => ['category' => 'investment_guide', 'english' => 'Enjoy Quarterly Dividends', 'spanish' => 'Disfruta Dividendos Trimestrales'],
        'starting_q1_2026' => ['category' => 'investment_guide', 'english' => 'Starting Q1 2026, receive quarterly dividend payments from real gold mining operations.', 'spanish' => 'A partir del Q1 2026, recibe pagos de dividendos trimestrales de operaciones reales de minería de oro.'],
        'view_earnings' => ['category' => 'investment_guide', 'english' => 'View Earnings', 'spanish' => 'Ver Ganancias'],
        'quarterly_payments_q1_2026' => ['category' => 'investment_guide', 'english' => 'Quarterly payments starting Q1 2026', 'spanish' => 'Pagos trimestrales comenzando Q1 2026'],
        'based_gold_mining_profits' => ['category' => 'investment_guide', 'english' => 'Based on actual gold mining profits', 'spanish' => 'Basado en ganancias reales de minería de oro'],
        'paid_directly_wallet' => ['category' => 'investment_guide', 'english' => 'Paid directly to your wallet', 'spanish' => 'Pagado directamente a tu billetera'],
        'lifetime_passive_income' => ['category' => 'investment_guide', 'english' => 'Lifetime passive income stream', 'spanish' => 'Flujo de ingresos pasivos de por vida'],
        
        // Investment Guide Navigation
        'step' => ['category' => 'investment_guide', 'english' => 'Step', 'spanish' => 'Paso'],
        'previous' => ['category' => 'investment_guide', 'english' => 'Previous', 'spanish' => 'Anterior'],
        'next' => ['category' => 'investment_guide', 'english' => 'Next', 'spanish' => 'Siguiente'],
        'of' => ['category' => 'investment_guide', 'english' => 'of', 'spanish' => 'de'],
        
        // Investment Guide Stats
        'min_investment' => ['category' => 'investment_guide', 'english' => 'Min Investment', 'spanish' => 'Inversión Mínima'],
        'roi_period' => ['category' => 'investment_guide', 'english' => 'ROI Period', 'spanish' => 'Período ROI'],
        'days_180' => ['category' => 'investment_guide', 'english' => '180 Days', 'spanish' => '180 Días'],
        'daily_roi_range_short' => ['category' => 'investment_guide', 'english' => 'Daily ROI Range', 'spanish' => 'Rango ROI Diario'],
        'total_packages' => ['category' => 'investment_guide', 'english' => 'Total Packages', 'spanish' => 'Paquetes Totales'],
        'mining_8' => ['category' => 'investment_guide', 'english' => '8 Mining', 'spanish' => '8 Minería'],
        
        // Packages View Component
        'loading_investment_packages' => ['category' => 'packages_view', 'english' => 'Loading investment packages...', 'spanish' => 'Cargando paquetes de inversión...'],
        'select_multiple_packages_match_amount' => ['category' => 'packages_view', 'english' => 'Select multiple packages to match your investment amount', 'spanish' => 'Selecciona múltiples paquetes para ajustar tu monto de inversión'],
        'choose_individual_packages' => ['category' => 'packages_view', 'english' => 'Choose individual packages to invest in', 'spanish' => 'Elige paquetes individuales para invertir'],
        'multi_select' => ['category' => 'packages_view', 'english' => 'Multi-Select', 'spanish' => 'Multi-Selección'],
        'individual' => ['category' => 'packages_view', 'english' => 'Individual', 'spanish' => 'Individual'],
        'request_custom_package' => ['category' => 'packages_view', 'english' => 'Request Custom Package', 'spanish' => 'Solicitar Paquete Personalizado'],
        
        // Package Statistics
        'available_packages' => ['category' => 'packages_view', 'english' => 'Available Packages', 'spanish' => 'Paquetes Disponibles'],
        'average_price' => ['category' => 'packages_view', 'english' => 'Average Price', 'spanish' => 'Precio Promedio'],
        'max_roi' => ['category' => 'packages_view', 'english' => 'Max ROI', 'spanish' => 'ROI Máximo'],
        'total_shares' => ['category' => 'packages_view', 'english' => 'Total Shares', 'spanish' => 'Acciones Totales'],
        
        // Package Filters
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
        
        // No Packages Found
        'no_packages_found' => ['category' => 'packages_view', 'english' => 'No packages found', 'spanish' => 'No se encontraron paquetes'],
        'try_adjusting_search' => ['category' => 'packages_view', 'english' => 'Try adjusting your search or filter criteria.', 'spanish' => 'Intenta ajustar tu búsqueda o criterios de filtro.'],
        'no_packages_currently_available' => ['category' => 'packages_view', 'english' => 'No investment packages are currently available.', 'spanish' => 'No hay paquetes de inversión disponibles actualmente.'],
        'clear_filters' => ['category' => 'packages_view', 'english' => 'Clear Filters', 'spanish' => 'Limpiar Filtros'],

        // Affiliate View Component
        'build_network_earn_commissions' => ['category' => 'affiliate_view', 'english' => 'Build your network, earn commissions, and grow your business', 'spanish' => 'Construye tu red, gana comisiones y haz crecer tu negocio'],
        'refresh' => ['category' => 'affiliate_view', 'english' => 'Refresh', 'spanish' => 'Actualizar'],
        'overview' => ['category' => 'affiliate_view', 'english' => 'Overview', 'spanish' => 'Resumen'],
        'downline_manager' => ['category' => 'affiliate_view', 'english' => 'Downline Manager', 'spanish' => 'Gestor de Línea Descendente'],
        'marketing_tools' => ['category' => 'affiliate_view', 'english' => 'Marketing Tools', 'spanish' => 'Herramientas de Marketing'],

        // NFT Presale Info
        'per_nft_pack' => ['category' => 'affiliate_view', 'english' => 'Per NFT Pack', 'spanish' => 'Por Paquete NFT'],
        'total_packs_available' => ['category' => 'affiliate_view', 'english' => 'Total Packs Available', 'spanish' => 'Paquetes Totales Disponibles'],
        'commission_structure' => ['category' => 'affiliate_view', 'english' => 'Commission Structure', 'spanish' => 'Estructura de Comisiones'],
        '3_levels' => ['category' => 'affiliate_view', 'english' => '3 Levels', 'spanish' => '3 Niveles'],

        // Referral Link Section
        'your_referral_link' => ['category' => 'affiliate_view', 'english' => 'Your Referral Link', 'spanish' => 'Tu Enlace de Referido'],
        'your_referral_code' => ['category' => 'affiliate_view', 'english' => 'Your Referral Code:', 'spanish' => 'Tu Código de Referido:'],

        // Commission Structure Details
        'level_1' => ['category' => 'affiliate_view', 'english' => 'Level 1', 'spanish' => 'Nivel 1'],
        'level_2' => ['category' => 'affiliate_view', 'english' => 'Level 2', 'spanish' => 'Nivel 2'],
        'level_3' => ['category' => 'affiliate_view', 'english' => 'Level 3', 'spanish' => 'Nivel 3'],
        'example_sale_calculation' => ['category' => 'affiliate_view', 'english' => 'Example: $1,000 sale =', 'spanish' => 'Ejemplo: venta de $1,000 ='],

        // Stats Overview
        'total_referrals' => ['category' => 'affiliate_view', 'english' => 'Total Referrals', 'spanish' => 'Referidos Totales'],
        'total_usdt_earned' => ['category' => 'affiliate_view', 'english' => 'Total USDT Earned', 'spanish' => 'USDT Total Ganado'],
        'nft_bonuses' => ['category' => 'affiliate_view', 'english' => 'NFT Bonuses', 'spanish' => 'Bonos NFT'],
        'pending_usdt' => ['category' => 'affiliate_view', 'english' => 'Pending USDT', 'spanish' => 'USDT Pendiente'],

        // Level Breakdown
        'level_1_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 1 Referrals', 'spanish' => 'Referidos Nivel 1'],
        'direct_referrals' => ['category' => 'affiliate_view', 'english' => 'Direct referrals', 'spanish' => 'Referidos directos'],
        'level_2_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 2 Referrals', 'spanish' => 'Referidos Nivel 2'],
        '2nd_level_referrals' => ['category' => 'affiliate_view', 'english' => '2nd level referrals', 'spanish' => 'Referidos de 2do nivel'],
        'level_3_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 3 Referrals', 'spanish' => 'Referidos Nivel 3'],
        '3rd_level_referrals' => ['category' => 'affiliate_view', 'english' => '3rd level referrals', 'spanish' => 'Referidos de 3er nivel'],

        // Recent Referral Activity
        'recent_referral_activity' => ['category' => 'affiliate_view', 'english' => 'Recent Referral Activity', 'spanish' => 'Actividad Reciente de Referidos'],
        'error_loading_referral_data' => ['category' => 'affiliate_view', 'english' => '⚠️ Error loading referral data', 'spanish' => '⚠️ Error cargando datos de referidos'],
        'try_again' => ['category' => 'affiliate_view', 'english' => 'Try Again', 'spanish' => 'Intentar de Nuevo'],
        'no_referral_activity_yet' => ['category' => 'affiliate_view', 'english' => 'No referral activity yet', 'spanish' => 'Aún no hay actividad de referidos'],
        'start_sharing_referral_link' => ['category' => 'affiliate_view', 'english' => 'Start sharing your referral link to earn commissions!', 'spanish' => '¡Comienza a compartir tu enlace de referido para ganar comisiones!'],

        // Referral Table Headers
        'user' => ['category' => 'affiliate_view', 'english' => 'User', 'spanish' => 'Usuario'],
        'level' => ['category' => 'affiliate_view', 'english' => 'Level', 'spanish' => 'Nivel'],
        'purchase' => ['category' => 'affiliate_view', 'english' => 'Purchase', 'spanish' => 'Compra'],
        'usdt_commission' => ['category' => 'affiliate_view', 'english' => 'USDT Commission', 'spanish' => 'Comisión USDT'],
        'nft_bonus' => ['category' => 'affiliate_view', 'english' => 'NFT Bonus', 'spanish' => 'Bono NFT'],
        'status' => ['category' => 'affiliate_view', 'english' => 'Status', 'spanish' => 'Estado'],
        'date' => ['category' => 'affiliate_view', 'english' => 'Date', 'spanish' => 'Fecha'],
        'paid' => ['category' => 'affiliate_view', 'english' => 'Paid', 'spanish' => 'Pagado'],
        'cancelled' => ['category' => 'affiliate_view', 'english' => 'Cancelled', 'spanish' => 'Cancelado'],
        'nfts' => ['category' => 'affiliate_view', 'english' => 'NFTs', 'spanish' => 'NFTs'],

        // Purchase Dialog Component
        'purchase' => ['category' => 'purchase_dialog', 'english' => 'Purchase', 'spanish' => 'Comprar'],
        'package_details' => ['category' => 'purchase_dialog', 'english' => 'Package Details', 'spanish' => 'Detalles del Paquete'],
        'price' => ['category' => 'purchase_dialog', 'english' => 'Price:', 'spanish' => 'Precio:'],
        'shares' => ['category' => 'purchase_dialog', 'english' => 'Shares:', 'spanish' => 'Acciones:'],
        'roi' => ['category' => 'purchase_dialog', 'english' => 'ROI:', 'spanish' => 'ROI:'],

        // Payment Method Selection
        'choose_payment_method' => ['category' => 'purchase_dialog', 'english' => 'Choose Payment Method', 'spanish' => 'Elige Método de Pago'],
        'pay_with_credits' => ['category' => 'purchase_dialog', 'english' => 'Pay with Credits', 'spanish' => 'Pagar con Créditos'],
        'use_nft_credits_instant' => ['category' => 'purchase_dialog', 'english' => 'Use your NFT credits for instant purchase', 'spanish' => 'Usa tus créditos NFT para compra instantánea'],
        'available' => ['category' => 'purchase_dialog', 'english' => 'Available', 'spanish' => 'Disponible'],
        'insufficient_credits' => ['category' => 'purchase_dialog', 'english' => 'Insufficient credits. Need', 'spanish' => 'Créditos insuficientes. Necesitas'],
        'more' => ['category' => 'purchase_dialog', 'english' => 'more.', 'spanish' => 'más.'],
        'pay_with_wallet' => ['category' => 'purchase_dialog', 'english' => 'Pay with Wallet', 'spanish' => 'Pagar con Billetera'],
        'connect_crypto_wallet_usdt' => ['category' => 'purchase_dialog', 'english' => 'Connect your crypto wallet and pay with USDT', 'spanish' => 'Conecta tu billetera crypto y paga con USDT'],

        // Purchase Process Messages
        'terms_not_accepted' => ['category' => 'purchase_dialog', 'english' => 'Terms Not Accepted', 'spanish' => 'Términos No Aceptados'],
        'please_accept_terms' => ['category' => 'purchase_dialog', 'english' => 'Please accept the terms and conditions to proceed', 'spanish' => 'Por favor acepta los términos y condiciones para continuar'],
        'insufficient_credits_title' => ['category' => 'purchase_dialog', 'english' => 'Insufficient Credits', 'spanish' => 'Créditos Insuficientes'],
        'you_need_but_only_have' => ['category' => 'purchase_dialog', 'english' => 'You need ${{amount}} but only have ${{available}} in credits', 'spanish' => 'Necesitas ${{amount}} pero solo tienes ${{available}} en créditos'],
        'purchase_successful' => ['category' => 'purchase_dialog', 'english' => 'Purchase Successful!', 'spanish' => '¡Compra Exitosa!'],
        'successfully_purchased_with_credits' => ['category' => 'purchase_dialog', 'english' => 'Successfully purchased {{package}} package with credits', 'spanish' => 'Compra exitosa del paquete {{package}} con créditos'],
        'purchase_failed' => ['category' => 'purchase_dialog', 'english' => 'Purchase Failed', 'spanish' => 'Compra Fallida'],
        'error_processing_purchase' => ['category' => 'purchase_dialog', 'english' => 'There was an error processing your purchase', 'spanish' => 'Hubo un error procesando tu compra'],
        'purchase_requirements_not_met' => ['category' => 'purchase_dialog', 'english' => 'Purchase Requirements Not Met', 'spanish' => 'Requisitos de Compra No Cumplidos'],
        'ensure_wallet_connected' => ['category' => 'purchase_dialog', 'english' => 'Please ensure wallet is connected, chain is selected, you have sufficient balance, and terms are accepted', 'spanish' => 'Por favor asegúrate de que la billetera esté conectada, la cadena seleccionada, tengas saldo suficiente y los términos aceptados']
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
        'message' => 'Comprehensive dashboard translation keys created successfully',
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
