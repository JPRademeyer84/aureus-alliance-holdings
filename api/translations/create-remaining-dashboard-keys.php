<?php
// Create remaining translation keys for dashboard components
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
    
    // Comprehensive translation keys for remaining dashboard components
    $translationKeys = [
        // AffiliateView - Remaining strings
        'copied' => ['category' => 'affiliate_view', 'english' => 'Copied!', 'spanish' => '¡Copiado!'],
        'referral_link_copied' => ['category' => 'affiliate_view', 'english' => 'Referral link copied to clipboard', 'spanish' => 'Enlace de referido copiado al portapapeles'],
        'join_aureus_alliance_nft' => ['category' => 'affiliate_view', 'english' => 'Join Aureus Alliance NFT Presale', 'spanish' => 'Únete a la Preventa NFT de Aureus Alliance'],
        'get_exclusive_nft_packs' => ['category' => 'affiliate_view', 'english' => 'Get exclusive NFT packs with amazing rewards!', 'spanish' => '¡Obtén paquetes NFT exclusivos con recompensas increíbles!'],
        'per_nft_pack' => ['category' => 'affiliate_view', 'english' => 'Per NFT Pack', 'spanish' => 'Por Paquete NFT'],
        'total_packs_available' => ['category' => 'affiliate_view', 'english' => 'Total Packs Available', 'spanish' => 'Paquetes Totales Disponibles'],
        'commission_structure' => ['category' => 'affiliate_view', 'english' => 'Commission Structure', 'spanish' => 'Estructura de Comisiones'],
        '3_levels' => ['category' => 'affiliate_view', 'english' => '3 Levels', 'spanish' => '3 Niveles'],
        'your_referral_link' => ['category' => 'affiliate_view', 'english' => 'Your Referral Link', 'spanish' => 'Tu Enlace de Referido'],
        'your_referral_code' => ['category' => 'affiliate_view', 'english' => 'Your Referral Code:', 'spanish' => 'Tu Código de Referido:'],
        'level_1' => ['category' => 'affiliate_view', 'english' => 'Level 1', 'spanish' => 'Nivel 1'],
        'level_2' => ['category' => 'affiliate_view', 'english' => 'Level 2', 'spanish' => 'Nivel 2'],
        'level_3' => ['category' => 'affiliate_view', 'english' => 'Level 3', 'spanish' => 'Nivel 3'],
        'example_sale_calculation' => ['category' => 'affiliate_view', 'english' => 'Example: $1,000 sale =', 'spanish' => 'Ejemplo: venta de $1,000 ='],
        'total_usdt_earned' => ['category' => 'affiliate_view', 'english' => 'Total USDT Earned', 'spanish' => 'USDT Total Ganado'],
        'nft_bonuses' => ['category' => 'affiliate_view', 'english' => 'NFT Bonuses', 'spanish' => 'Bonos NFT'],
        'pending_usdt' => ['category' => 'affiliate_view', 'english' => 'Pending USDT', 'spanish' => 'USDT Pendiente'],
        'level_1_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 1 Referrals', 'spanish' => 'Referidos Nivel 1'],
        'direct_referrals' => ['category' => 'affiliate_view', 'english' => 'Direct referrals', 'spanish' => 'Referidos directos'],
        'level_2_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 2 Referrals', 'spanish' => 'Referidos Nivel 2'],
        '2nd_level_referrals' => ['category' => 'affiliate_view', 'english' => '2nd level referrals', 'spanish' => 'Referidos de 2do nivel'],
        'level_3_referrals' => ['category' => 'affiliate_view', 'english' => 'Level 3 Referrals', 'spanish' => 'Referidos Nivel 3'],
        '3rd_level_referrals' => ['category' => 'affiliate_view', 'english' => '3rd level referrals', 'spanish' => 'Referidos de 3er nivel'],
        'recent_referral_activity' => ['category' => 'affiliate_view', 'english' => 'Recent Referral Activity', 'spanish' => 'Actividad Reciente de Referidos'],
        'error_loading_referral_data' => ['category' => 'affiliate_view', 'english' => '⚠️ Error loading referral data', 'spanish' => '⚠️ Error cargando datos de referidos'],
        'try_again' => ['category' => 'affiliate_view', 'english' => 'Try Again', 'spanish' => 'Intentar de Nuevo'],
        'no_referral_activity_yet' => ['category' => 'affiliate_view', 'english' => 'No referral activity yet', 'spanish' => 'Aún no hay actividad de referidos'],
        'start_sharing_referral_link' => ['category' => 'affiliate_view', 'english' => 'Start sharing your referral link to earn commissions!', 'spanish' => '¡Comienza a compartir tu enlace de referido para ganar comisiones!'],
        'user' => ['category' => 'affiliate_view', 'english' => 'User', 'spanish' => 'Usuario'],
        'level' => ['category' => 'affiliate_view', 'english' => 'Level', 'spanish' => 'Nivel'],
        'purchase' => ['category' => 'affiliate_view', 'english' => 'Purchase', 'spanish' => 'Compra'],
        'usdt_commission' => ['category' => 'affiliate_view', 'english' => 'USDT Commission', 'spanish' => 'Comisión USDT'],
        'nft_bonus' => ['category' => 'affiliate_view', 'english' => 'NFT Bonus', 'spanish' => 'Bono NFT'],
        'status' => ['category' => 'affiliate_view', 'english' => 'Status', 'spanish' => 'Estado'],
        'date' => ['category' => 'affiliate_view', 'english' => 'Date', 'spanish' => 'Fecha'],
        'paid' => ['category' => 'affiliate_view', 'english' => 'Paid', 'spanish' => 'Pagado'],
        'pending' => ['category' => 'affiliate_view', 'english' => 'Pending', 'spanish' => 'Pendiente'],
        'cancelled' => ['category' => 'affiliate_view', 'english' => 'Cancelled', 'spanish' => 'Cancelado'],
        'nfts' => ['category' => 'affiliate_view', 'english' => 'NFTs', 'spanish' => 'NFTs'],
        
        // InvestmentHistory Component
        'total_invested' => ['category' => 'investment_history', 'english' => 'Total Invested', 'spanish' => 'Total Invertido'],
        'expected_roi' => ['category' => 'investment_history', 'english' => 'Expected ROI', 'spanish' => 'ROI Esperado'],
        'total_packages' => ['category' => 'investment_history', 'english' => 'Total Packages', 'spanish' => 'Paquetes Totales'],
        'investment_history' => ['category' => 'investment_history', 'english' => 'Investment History', 'spanish' => 'Historial de Inversiones'],
        'error_loading_investments' => ['category' => 'investment_history', 'english' => '⚠️ Error loading investments', 'spanish' => '⚠️ Error cargando inversiones'],
        'loading_investment_history' => ['category' => 'investment_history', 'english' => 'Loading investment history...', 'spanish' => 'Cargando historial de inversiones...'],
        'no_investments_yet' => ['category' => 'investment_history', 'english' => 'No investments yet', 'spanish' => 'Aún no hay inversiones'],
        'investment_history_will_appear' => ['category' => 'investment_history', 'english' => 'Your investment history will appear here once you make your first purchase', 'spanish' => 'Tu historial de inversiones aparecerá aquí una vez que hagas tu primera compra'],
        'shares' => ['category' => 'investment_history', 'english' => 'shares', 'spanish' => 'acciones'],
        'transaction' => ['category' => 'investment_history', 'english' => 'Transaction', 'spanish' => 'Transacción'],
        
        // SupportView Component
        'contact_support' => ['category' => 'support_view', 'english' => 'Contact Support', 'spanish' => 'Contactar Soporte'],
        'get_help_investments_account' => ['category' => 'support_view', 'english' => 'Get help with your investments and account', 'spanish' => 'Obtén ayuda con tus inversiones y cuenta'],
        'response_time' => ['category' => 'support_view', 'english' => 'Response Time', 'spanish' => 'Tiempo de Respuesta'],
        'less_than_2_hours' => ['category' => 'support_view', 'english' => '< 2 hours', 'spanish' => '< 2 horas'],
        'support_hours' => ['category' => 'support_view', 'english' => 'Support Hours', 'spanish' => 'Horarios de Soporte'],
        '24_7' => ['category' => 'support_view', 'english' => '24/7', 'spanish' => '24/7'],
        'satisfaction_rate' => ['category' => 'support_view', 'english' => 'Satisfaction Rate', 'spanish' => 'Tasa de Satisfacción'],
        '98_percent' => ['category' => 'support_view', 'english' => '98%', 'spanish' => '98%'],
        'how_contact_us' => ['category' => 'support_view', 'english' => 'How would you like to contact us?', 'spanish' => '¿Cómo te gustaría contactarnos?'],
        'live_chat' => ['category' => 'support_view', 'english' => 'Live Chat', 'spanish' => 'Chat en Vivo'],
        'get_instant_help' => ['category' => 'support_view', 'english' => 'Get instant help from our support team', 'spanish' => 'Obtén ayuda instantánea de nuestro equipo de soporte'],
        'start_chat' => ['category' => 'support_view', 'english' => 'Start Chat', 'spanish' => 'Iniciar Chat'],
        'email_support' => ['category' => 'support_view', 'english' => 'Email Support', 'spanish' => 'Soporte por Email'],
        'send_detailed_message' => ['category' => 'support_view', 'english' => 'Send us a detailed message', 'spanish' => 'Envíanos un mensaje detallado'],
        'send_email' => ['category' => 'support_view', 'english' => 'Send Email', 'spanish' => 'Enviar Email'],
        'phone_support' => ['category' => 'support_view', 'english' => 'Phone Support', 'spanish' => 'Soporte Telefónico'],
        'speak_directly_team' => ['category' => 'support_view', 'english' => 'Speak directly with our team', 'spanish' => 'Habla directamente con nuestro equipo'],
        'call_now' => ['category' => 'support_view', 'english' => 'Call Now', 'spanish' => 'Llamar Ahora'],
        'premium' => ['category' => 'support_view', 'english' => 'Premium', 'spanish' => 'Premium'],
        'send_message' => ['category' => 'support_view', 'english' => 'Send Message', 'spanish' => 'Enviar Mensaje'],
        'my_messages' => ['category' => 'support_view', 'english' => 'My Messages', 'spanish' => 'Mis Mensajes'],
        'faq' => ['category' => 'support_view', 'english' => 'FAQ', 'spanish' => 'Preguntas Frecuentes'],
        'frequently_asked_questions' => ['category' => 'support_view', 'english' => 'Frequently Asked Questions', 'spanish' => 'Preguntas Frecuentes'],
        'still_need_help' => ['category' => 'support_view', 'english' => 'Still need help?', 'spanish' => '¿Aún necesitas ayuda?'],
        'cant_find_looking_for' => ['category' => 'support_view', 'english' => 'Can\'t find what you\'re looking for? Send us a message and we\'ll get back to you quickly.', 'spanish' => '¿No encuentras lo que buscas? Envíanos un mensaje y te responderemos rápidamente.'],
        
        // FAQ Questions and Answers
        'how_start_investing' => ['category' => 'support_faq', 'english' => 'How do I start investing?', 'spanish' => '¿Cómo empiezo a invertir?'],
        'how_start_investing_answer' => ['category' => 'support_faq', 'english' => 'Browse our investment packages, select one that fits your budget and goals, then follow the purchase process. You\'ll need to connect a wallet for transactions.', 'spanish' => 'Navega por nuestros paquetes de inversión, selecciona uno que se ajuste a tu presupuesto y objetivos, luego sigue el proceso de compra. Necesitarás conectar una billetera para las transacciones.'],
        'when_receive_dividends' => ['category' => 'support_faq', 'english' => 'When will I receive dividends?', 'spanish' => '¿Cuándo recibiré dividendos?'],
        'when_receive_dividends_answer' => ['category' => 'support_faq', 'english' => 'Dividends are paid quarterly starting from the date specified in your investment package. You\'ll receive notifications before each payment.', 'spanish' => 'Los dividendos se pagan trimestralmente a partir de la fecha especificada en tu paquete de inversión. Recibirás notificaciones antes de cada pago.'],
        'withdraw_investment_early' => ['category' => 'support_faq', 'english' => 'Can I withdraw my investment early?', 'spanish' => '¿Puedo retirar mi inversión antes de tiempo?'],
        'withdraw_investment_early_answer' => ['category' => 'support_faq', 'english' => 'Early withdrawal terms depend on your specific investment package. Please contact support for details about your particular investment.', 'spanish' => 'Los términos de retiro anticipado dependen de tu paquete de inversión específico. Por favor contacta al soporte para detalles sobre tu inversión particular.'],
        'investments_secured' => ['category' => 'support_faq', 'english' => 'How are my investments secured?', 'spanish' => '¿Cómo están aseguradas mis inversiones?'],
        'investments_secured_answer' => ['category' => 'support_faq', 'english' => 'All investments are backed by our diversified portfolio and smart contract technology. We maintain strict security protocols and regular audits.', 'spanish' => 'Todas las inversiones están respaldadas por nuestro portafolio diversificado y tecnología de contratos inteligentes. Mantenemos protocolos de seguridad estrictos y auditorías regulares.'],
        'payment_methods_accepted' => ['category' => 'support_faq', 'english' => 'What payment methods do you accept?', 'spanish' => '¿Qué métodos de pago aceptan?'],
        'payment_methods_accepted_answer' => ['category' => 'support_faq', 'english' => 'We accept various cryptocurrencies including USDT, USDC, and other major tokens. Connect your wallet to see available payment options.', 'spanish' => 'Aceptamos varias criptomonedas incluyendo USDT, USDC y otros tokens principales. Conecta tu billetera para ver las opciones de pago disponibles.']
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
        'message' => 'Remaining dashboard translation keys created successfully',
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
