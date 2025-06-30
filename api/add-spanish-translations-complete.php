<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get Spanish language ID
    $langQuery = "SELECT id FROM languages WHERE code = 'es'";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $spanishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spanishLang) {
        throw new Exception('Spanish language not found');
    }
    
    $spanishId = $spanishLang['id'];
    
    // Complete Spanish translations for ALL keys
    $spanishTranslations = [
        // Navigation
        ['nav.investment', 'Inversión'],
        ['nav.affiliate', 'Afiliado'],
        ['nav.benefits', 'Beneficios'],
        ['nav.about', 'Acerca de'],
        ['nav.contact', 'Contacto'],
        ['nav.sign_in', 'Iniciar Sesión'],
        
        // Hero Section
        ['hero.title', 'Conviértete en un Inversionista Ángel'],
        ['hero.subtitle', 'en el Futuro del Oro Digital'],
        ['hero.description', 'Oportunidad exclusiva de pre-semilla para invertir en Aureus Alliance Holdings – combinando minería de oro física con coleccionables NFT digitales.'],
        ['hero.invest_now', 'Invertir Ahora'],
        ['hero.learn_more', 'Aprende Más'],
        ['hero.start_investing', 'Comenzar a Invertir Ahora'],
        ['hero.view_packages', 'Ver Paquetes de Inversión'],
        
        // Statistics
        ['stats.yield_investment', 'Rendimiento de la Inversión'],
        ['stats.annual_share', 'Anual por Acción'],
        ['stats.affiliate_commission', 'Comisión de Afiliado'],
        ['stats.nft_presale', 'Lanzamiento de Preventa NFT'],
        
        // Call to Action
        ['cta.become_investor_today', 'Conviértete en un Inversionista Ángel Hoy'],
        ['cta.limited_investment', 'Solo $100,000 de inversión de pre-semilla disponible. Asegura tu posición antes de que se cierre la oportunidad.'],
        ['cta.yield_deadline', 'Rendimiento 10x para junio de 2025. La inversión se cierra cuando alcancemos nuestro límite de $100,000 o cuando comience la preventa NFT en junio.'],
        
        // Benefits Section
        ['benefits.title', 'Beneficios Exclusivos del Inversionista Ángel'],
        ['benefits.description', 'Como partidario temprano de la Alianza Ángel Aureus, recibirás ventajas incomparables que no estarán disponibles después de nuestro lanzamiento público.'],
        ['benefits.limited_offer', 'Oferta Limitada'],
        ['benefits.limited_offer_desc', 'Solo $100,000 disponibles para inversión de pre-semilla, asegurando acceso exclusivo antes del lanzamiento público.'],
        ['benefits.nft_access', 'Acceso Temprano a NFT'],
        ['benefits.nft_access_desc', 'Obtén NFTs premium a las tarifas más bajas posibles antes de que comience la preventa en junio.'],
        ['benefits.gold_dividends', 'Dividendos de Mina de Oro'],
        ['benefits.gold_dividends_desc', 'Comparte las ganancias de las operaciones de minería de oro de Aureus Alliance a $89 por acción anualmente.'],
        ['benefits.affiliate_program', 'Programa de Afiliados'],
        ['benefits.affiliate_program_desc', 'Gana 20% de comisión en una estructura de afiliados de 2 niveles cuando refieras otros inversionistas ángel.'],
        ['benefits.gaming_integration', 'Integración de Juegos'],
        ['benefits.gaming_integration_desc', 'Acceso exclusivo al próximo ecosistema de juegos MMO con ventajas únicas en el juego.'],
        ['benefits.why_choose_title', '¿Por Qué Elegir Aureus Alliance?'],
        ['benefits.early_supporter_desc', 'Como partidario temprano de la Alianza Ángel Aureus, recibirás ventajas incomparables que no estarán disponibles después de nuestro lanzamiento público.'],
        
        // How It Works
        ['how_it_works.title', 'Cómo Funciona la Inversión Ángel'],
        ['how_it_works.description', 'Únete a la Alianza Ángel Aureus en 6 pasos simples. Sin procesos complicados, sin tarifas ocultas - solo un camino directo hacia la propiedad de oro digital.'],
        ['how_it_works.create_account', 'Crea Tu Cuenta'],
        ['how_it_works.step1_desc', 'Regístrate en menos de 2 minutos solo con tu email. No se requiere verificación compleja para comenzar.'],
        ['how_it_works.choose_package', 'Elige Tu Paquete NFT'],
        ['how_it_works.step2_desc', 'Selecciona de 8 paquetes de minería (Pala $25 a Aureus $1000) o combina múltiples paquetes para tu cantidad de inversión perfecta.'],
        ['how_it_works.secure_payment', 'Pago Seguro USDT'],
        ['how_it_works.step3_desc', 'Conecta tu billetera y paga con USDT en la red Polygon. Tarifas bajas, transacciones rápidas, transparencia completa.'],
        ['how_it_works.earn_commissions', 'Gana Comisiones'],
        ['how_it_works.step4_desc', 'Comparte tu enlace de referido y gana 12% USDT + 12% bonos NFT en Nivel 1, más 8% USDT + 8% NFT en Nivel 2.'],
        ['how_it_works.roi_period', 'Período ROI de 180 Días'],
        ['how_it_works.step5_desc', 'Observa crecer tu inversión con ROI diario del 1.7% al 5% durante 180 días, dependiendo de tu paquete.'],
        ['how_it_works.receive_returns', 'Recibe Tus Retornos'],
        ['how_it_works.step6_desc', 'Después de 180 días, recibe tus acciones de minería NFT más el ROI total. Luego gana dividendos trimestrales de las ganancias de minería de oro Aureus.'],
        ['how_it_works.benefit1', 'Comienza con solo $25 (paquete Pala) - sin barreras mínimas'],
        ['how_it_works.benefit2', '8 paquetes de minería de $25 a $1,000 - perfecto para cualquier presupuesto'],
        ['how_it_works.benefit3', 'ROI diario del 1.7% al 5% durante 180 días garantizado'],
        ['how_it_works.benefit4', '12% USDT + 12% bonos NFT en referencias de Nivel 1'],
        ['how_it_works.benefit5', 'Transparencia blockchain Polygon con pagos USDT'],
        ['how_it_works.benefit6', 'Respaldado por operaciones reales de minería de oro Aureus Alliance'],
        
        // Authentication
        ['auth.welcome_back', 'Bienvenido de Vuelta'],
        ['auth.sign_in_account', 'Inicia sesión en tu cuenta'],
        ['auth.email', 'Correo Electrónico'],
        ['auth.password', 'Contraseña'],
        ['auth.email_placeholder', 'tu@correo.com'],
        ['auth.password_placeholder', 'Ingresa tu contraseña'],
        ['auth.signing_in', 'Iniciando sesión...'],
        ['auth.no_account', '¿No tienes una cuenta?'],
        ['auth.sign_up', 'Regístrate'],
        ['auth.create_account', 'Crear Cuenta'],
        ['auth.join_alliance', 'Únete a la Alianza Ángel Aureus'],
        ['auth.username', 'Nombre de Usuario'],
        ['auth.confirm_password', 'Confirmar Contraseña'],
        ['auth.username_placeholder', 'Elige un nombre de usuario'],
        ['auth.confirm_password_placeholder', 'Confirma tu contraseña'],
        ['auth.creating_account', 'Creando cuenta...'],
        ['auth.have_account', '¿Ya tienes una cuenta?'],
        
        // Dashboard
        ['dashboard.welcome_back', 'Bienvenido de vuelta'],
        ['dashboard.ready_grow_wealth', '¿Listo para hacer crecer tu riqueza?'],
        ['dashboard.last_login', 'Último inicio de sesión'],
        ['dashboard.investor', 'INVERSIONISTA'],
        ['dashboard.commission_earnings', 'Ganancias por Comisión'],
        ['dashboard.available_balance', 'Saldo Disponible'],
        ['dashboard.total_investments', 'Inversiones Totales'],
        ['dashboard.portfolio_value', 'Valor del Portafolio'],
        ['dashboard.aureus_shares', 'Acciones Aureus'],
        ['dashboard.activity', 'Actividad'],
        ['dashboard.nft_packs_earned', 'paquetes NFT ganados'],
        ['dashboard.nft_available', 'NFT disponible'],
        ['dashboard.active', 'activo'],
        ['dashboard.completed', 'completado'],
        ['dashboard.expected_roi', 'ROI esperado'],
        ['dashboard.annual_dividends', 'dividendos anuales'],
        ['dashboard.pending', 'pendiente'],
        ['dashboard.loading', 'Cargando panel...'],
        
        // Quick Actions
        ['actions.commission_wallet', 'Billetera de Comisiones'],
        ['actions.commission_wallet_desc', 'Gestiona tus ganancias de referidos y retiros'],
        ['actions.affiliate_program', 'Programa de Afiliados'],
        ['actions.affiliate_program_desc', 'Haz crecer tu red y gana comisiones'],
        ['actions.browse_packages', 'Explorar Paquetes'],
        ['actions.browse_packages_desc', 'Explora oportunidades de inversión disponibles'],
        ['actions.investment_history', 'Historial de Inversiones'],
        ['actions.investment_history_desc', 'Ve tus inversiones pasadas y actuales'],
        ['actions.delivery_countdown', 'Cuenta Regresiva de Entrega'],
        ['actions.delivery_countdown_desc', 'Rastrea la entrega de NFT y ROI (180 días)'],
        ['actions.portfolio_overview', 'Resumen del Portafolio'],
        ['actions.portfolio_overview_desc', 'Verifica el rendimiento de tu portafolio'],
        ['actions.gold_diggers_club', 'Club de Buscadores de Oro'],
        ['actions.gold_diggers_club_desc', 'Compite por el fondo de bonos de $250K'],
        ['actions.contact_support', 'Contactar Soporte'],
        ['actions.contact_support_desc', 'Obtén ayuda de nuestro equipo de soporte'],
        ['actions.account_settings', 'Configuración de Cuenta'],
        ['actions.account_settings_desc', 'Gestiona las preferencias de tu cuenta'],
        ['actions.wallet_connection', 'Conexión de Billetera'],
        ['actions.wallet_connection_desc', 'Conecta y gestiona tus billeteras'],
        
        // Investment Packages
        ['packages.available_packages', 'Paquetes de Inversión Disponibles'],
        ['packages.view_all', 'Ver Todos'],
        ['packages.invest_now', 'Invertir Ahora'],
        ['packages.shares', 'Acciones Aureus'],
        ['packages.expected_roi', 'ROI Esperado'],
        ['packages.annual_dividends', 'Dividendos Anuales'],
        ['packages.no_packages', 'No hay paquetes de inversión disponibles en este momento.'],
        
        // Quick Actions Section
        ['quick_actions.title', 'Acciones Rápidas'],
        ['quick_actions.new', 'NUEVO'],
        
        // Common UI Elements
        ['common.loading', 'Cargando...'],
        ['common.error', 'Error'],
        ['common.success', 'Éxito'],
        ['common.cancel', 'Cancelar'],
        ['common.save', 'Guardar'],
        ['common.edit', 'Editar'],
        ['common.delete', 'Eliminar'],
        ['common.view', 'Ver'],
        ['common.close', 'Cerrar'],
        ['common.submit', 'Enviar'],
        ['common.confirm', 'Confirmar'],
        ['common.back', 'Atrás'],
        ['common.next', 'Siguiente'],
        ['common.previous', 'Anterior'],
        ['common.search', 'Buscar'],
        ['common.filter', 'Filtrar'],
        ['common.sort', 'Ordenar'],
        ['common.refresh', 'Actualizar'],
        ['common.download', 'Descargar'],
        ['common.upload', 'Subir'],
        ['common.copy', 'Copiar'],
        ['common.share', 'Compartir'],
        ['common.print', 'Imprimir'],
        ['common.help', 'Ayuda'],
        ['common.settings', 'Configuración'],
        ['common.profile', 'Perfil'],
        ['common.logout', 'Cerrar Sesión'],
        ['common.login', 'Iniciar Sesión'],
        ['common.register', 'Registrarse'],
        
        // Status and States
        ['status.active', 'Activo'],
        ['status.inactive', 'Inactivo'],
        ['status.pending', 'Pendiente'],
        ['status.completed', 'Completado'],
        ['status.cancelled', 'Cancelado'],
        ['status.approved', 'Aprobado'],
        ['status.rejected', 'Rechazado'],
        ['status.processing', 'Procesando'],
        ['status.failed', 'Fallido'],
        ['status.expired', 'Expirado'],
        
        // Time and Dates
        ['time.today', 'Hoy'],
        ['time.yesterday', 'Ayer'],
        ['time.tomorrow', 'Mañana'],
        ['time.this_week', 'Esta Semana'],
        ['time.last_week', 'Semana Pasada'],
        ['time.this_month', 'Este Mes'],
        ['time.last_month', 'Mes Pasado'],
        ['time.this_year', 'Este Año'],
        ['time.last_year', 'Año Pasado'],
        ['time.days', 'días'],
        ['time.hours', 'horas'],
        ['time.minutes', 'minutos'],
        ['time.seconds', 'segundos'],
        
        // Financial Terms
        ['finance.balance', 'Saldo'],
        ['finance.amount', 'Cantidad'],
        ['finance.total', 'Total'],
        ['finance.subtotal', 'Subtotal'],
        ['finance.fee', 'Tarifa'],
        ['finance.commission', 'Comisión'],
        ['finance.dividend', 'Dividendo'],
        ['finance.profit', 'Ganancia'],
        ['finance.loss', 'Pérdida'],
        ['finance.investment', 'Inversión'],
        ['finance.withdrawal', 'Retiro'],
        ['finance.deposit', 'Depósito'],
        ['finance.transfer', 'Transferencia'],
        ['finance.transaction', 'Transacción'],
        ['finance.payment', 'Pago'],
        ['finance.refund', 'Reembolso'],
        ['finance.currency', 'Moneda'],
        ['finance.exchange_rate', 'Tipo de Cambio'],
        ['finance.wallet_address', 'Dirección de Billetera'],
        ['finance.transaction_hash', 'Hash de Transacción'],

        // KYC and Verification
        ['kyc.verification', 'Verificación'],
        ['kyc.identity_verification', 'Verificación de Identidad'],
        ['kyc.upload_document', 'Subir Documento'],
        ['kyc.document_type', 'Tipo de Documento'],
        ['kyc.drivers_license', 'Licencia de Conducir'],
        ['kyc.national_id', 'Cédula Nacional'],
        ['kyc.passport', 'Pasaporte'],
        ['kyc.document_uploaded', 'Documento subido exitosamente'],
        ['kyc.pending_review', 'Revisión Pendiente'],
        ['kyc.verified', 'Verificado'],
        ['kyc.rejected', 'Rechazado'],

        // Wallet and Blockchain
        ['wallet.connect_wallet', 'Conectar Billetera'],
        ['wallet.disconnect_wallet', 'Desconectar Billetera'],
        ['wallet.wallet_connected', 'Billetera Conectada'],
        ['wallet.wallet_disconnected', 'Billetera Desconectada'],
        ['wallet.select_wallet', 'Seleccionar Billetera'],
        ['wallet.safepal_wallet', 'Billetera SafePal'],
        ['wallet.metamask', 'MetaMask'],
        ['wallet.wallet_balance', 'Saldo de Billetera'],
        ['wallet.insufficient_balance', 'Saldo Insuficiente'],
        ['wallet.transaction_pending', 'Transacción Pendiente'],
        ['wallet.transaction_confirmed', 'Transacción Confirmada'],
        ['wallet.transaction_failed', 'Transacción Fallida'],

        // Affiliate and Referral
        ['affiliate.referral_link', 'Enlace de Referido'],
        ['affiliate.copy_link', 'Copiar Enlace'],
        ['affiliate.share_link', 'Compartir Enlace'],
        ['affiliate.referrals', 'Referencias'],
        ['affiliate.level_1', 'Nivel 1'],
        ['affiliate.level_2', 'Nivel 2'],
        ['affiliate.commission_rate', 'Tasa de Comisión'],
        ['affiliate.total_referrals', 'Referencias Totales'],
        ['affiliate.active_referrals', 'Referencias Activas'],
        ['affiliate.commission_earned', 'Comisión Ganada'],
        ['affiliate.downline', 'Red Descendente'],
        ['affiliate.upline', 'Red Ascendente'],

        // Support and Contact
        ['support.live_chat', 'Chat en Vivo'],
        ['support.contact_form', 'Formulario de Contacto'],
        ['support.send_message', 'Enviar Mensaje'],
        ['support.message_sent', 'Mensaje Enviado'],
        ['support.support_ticket', 'Ticket de Soporte'],
        ['support.ticket_number', 'Número de Ticket'],
        ['support.priority', 'Prioridad'],
        ['support.high', 'Alta'],
        ['support.medium', 'Media'],
        ['support.low', 'Baja'],
        ['support.subject', 'Asunto'],
        ['support.message', 'Mensaje'],
        ['support.attachment', 'Adjunto'],

        // Notifications and Alerts
        ['notification.success', '¡Éxito!'],
        ['notification.error', '¡Error!'],
        ['notification.warning', '¡Advertencia!'],
        ['notification.info', 'Información'],
        ['notification.new_message', 'Nuevo Mensaje'],
        ['notification.investment_confirmed', 'Inversión Confirmada'],
        ['notification.commission_received', 'Comisión Recibida'],
        ['notification.withdrawal_processed', 'Retiro Procesado'],

        // Terms and Legal
        ['terms.terms_conditions', 'Términos y Condiciones'],
        ['terms.privacy_policy', 'Política de Privacidad'],
        ['terms.accept_terms', 'Acepto los términos y condiciones'],
        ['terms.agree_privacy', 'Acepto la política de privacidad'],
        ['terms.legal_disclaimer', 'Descargo Legal'],
        ['terms.risk_warning', 'Advertencia de Riesgo'],
        ['terms.investment_risk', 'Las oportunidades de inversión involucran riesgo. Por favor lee nuestros términos cuidadosamente.'],

        // Countdown and Delivery
        ['countdown.delivery_countdown', 'Cuenta Regresiva de Entrega'],
        ['countdown.days_remaining', 'Días Restantes'],
        ['countdown.nft_delivery', 'Entrega de NFT'],
        ['countdown.roi_completion', 'Finalización de ROI'],
        ['countdown.countdown_expired', 'Cuenta Regresiva Expirada'],

        // Leaderboard and Competition
        ['leaderboard.gold_diggers_club', 'Club de Buscadores de Oro'],
        ['leaderboard.bonus_pool', 'Fondo de Bonos'],
        ['leaderboard.rank', 'Rango'],
        ['leaderboard.points', 'Puntos'],
        ['leaderboard.prize', 'Premio'],
        ['leaderboard.competition', 'Competencia'],
        ['leaderboard.winner', 'Ganador'],

        // Social Media and Marketing
        ['social.share_facebook', 'Compartir en Facebook'],
        ['social.share_twitter', 'Compartir en Twitter'],
        ['social.share_linkedin', 'Compartir en LinkedIn'],
        ['social.share_telegram', 'Compartir en Telegram'],
        ['social.share_whatsapp', 'Compartir en WhatsApp'],
        ['social.follow_us', 'Síguenos'],
        ['social.social_media', 'Redes Sociales']
    ];
    
    $results = [];
    
    // Insert or update Spanish translations
    $sql = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
            SELECT tk.id, ?, ?, TRUE 
            FROM translation_keys tk 
            WHERE tk.key_name = ?
            ON DUPLICATE KEY UPDATE 
            translation_text = VALUES(translation_text),
            is_approved = TRUE,
            updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $db->prepare($sql);
    
    foreach ($spanishTranslations as $trans) {
        $stmt->execute([$spanishId, $trans[1], $trans[0]]);
        $results[] = "Added/Updated: " . $trans[0] . " → " . $trans[1];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Complete Spanish translations added successfully!',
        'count' => count($spanishTranslations),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to add Spanish translations'
    ]);
}
?>
