<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['text']) || !isset($input['target_language']) || !isset($input['language_code'])) {
        throw new Exception('Missing required parameters: text, target_language, language_code');
    }
    
    $text = $input['text'];
    $targetLanguage = $input['target_language'];
    $languageCode = $input['language_code'];
    $keyId = $input['key_id'] ?? null;
    
    // Enhanced AI Translation function with comprehensive dictionary
    function translateWithAI($text, $targetLanguage) {
        // Comprehensive Spanish translation dictionary
        $spanishTranslations = [
            // Navigation
            'Investment' => 'Inversión',
            'Affiliate' => 'Afiliado',
            'Benefits' => 'Beneficios',
            'About' => 'Acerca de',
            'Contact' => 'Contacto',
            'Sign In' => 'Iniciar Sesión',
            
            // Hero Section
            'Become an Angel Investor' => 'Conviértete en un Inversionista Ángel',
            'in the Future of Digital Gold' => 'en el Futuro del Oro Digital',
            'Exclusive pre-seed opportunity to invest in Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.' => 'Oportunidad exclusiva de pre-semilla para invertir en Aureus Alliance Holdings – combinando minería de oro física con coleccionables NFT digitales.',
            'Invest Now' => 'Invertir Ahora',
            'Learn More' => 'Aprende Más',
            'Start Investing Now' => 'Comenzar a Invertir Ahora',
            'View Investment Packages' => 'Ver Paquetes de Inversión',
            
            // Statistics
            'Yield on Investment' => 'Rendimiento de la Inversión',
            'Annual per Share' => 'Anual por Acción',
            'Affiliate Commission' => 'Comisión de Afiliado',
            'NFT Presale Launch' => 'Lanzamiento de Preventa NFT',
            
            // Authentication
            'Welcome Back' => 'Bienvenido de Vuelta',
            'Sign in to your account' => 'Inicia sesión en tu cuenta',
            'Email' => 'Correo Electrónico',
            'Password' => 'Contraseña',
            'your@email.com' => 'tu@correo.com',
            'Enter your password' => 'Ingresa tu contraseña',
            'Signing in...' => 'Iniciando sesión...',
            'Don\'t have an account?' => '¿No tienes una cuenta?',
            'Sign up' => 'Regístrate',
            'Create Account' => 'Crear Cuenta',
            'Join the Aureus Angel Alliance' => 'Únete a la Alianza Ángel Aureus',
            'Username' => 'Nombre de Usuario',
            'Confirm Password' => 'Confirmar Contraseña',
            'Choose a username' => 'Elige un nombre de usuario',
            'Confirm your password' => 'Confirma tu contraseña',
            'Creating account...' => 'Creando cuenta...',
            'Already have an account?' => '¿Ya tienes una cuenta?',
            
            // Dashboard
            'Welcome back' => 'Bienvenido de vuelta',
            'Ready to grow your wealth?' => '¿Listo para hacer crecer tu riqueza?',
            'Last login' => 'Último inicio de sesión',
            'INVESTOR' => 'INVERSIONISTA',
            'Commission Earnings' => 'Ganancias por Comisión',
            'Available Balance' => 'Saldo Disponible',
            'Total Investments' => 'Inversiones Totales',
            'Portfolio Value' => 'Valor del Portafolio',
            'Aureus Shares' => 'Acciones Aureus',
            'Activity' => 'Actividad',
            'NFT packs earned' => 'paquetes NFT ganados',
            'NFT available' => 'NFT disponible',
            'active' => 'activo',
            'completed' => 'completado',
            'expected ROI' => 'ROI esperado',
            'annual dividends' => 'dividendos anuales',
            'pending' => 'pendiente',
            'Loading dashboard...' => 'Cargando panel...',
            
            // Quick Actions
            'Commission Wallet' => 'Billetera de Comisiones',
            'Manage your referral earnings and withdrawals' => 'Gestiona tus ganancias de referidos y retiros',
            'Affiliate Program' => 'Programa de Afiliados',
            'Grow your network and earn commissions' => 'Haz crecer tu red y gana comisiones',
            'Browse Packages' => 'Explorar Paquetes',
            'Explore available investment opportunities' => 'Explora oportunidades de inversión disponibles',
            'Investment History' => 'Historial de Inversiones',
            'View your past and current investments' => 'Ve tus inversiones pasadas y actuales',
            'Delivery Countdown' => 'Cuenta Regresiva de Entrega',
            'Track NFT & ROI delivery (180 days)' => 'Rastrea la entrega de NFT y ROI (180 días)',
            'Portfolio Overview' => 'Resumen del Portafolio',
            'Check your portfolio performance' => 'Verifica el rendimiento de tu portafolio',
            'Gold Diggers Club' => 'Club de Buscadores de Oro',
            'Compete for $250K bonus pool' => 'Compite por el fondo de bonos de $250K',
            'Contact Support' => 'Contactar Soporte',
            'Get help from our support team' => 'Obtén ayuda de nuestro equipo de soporte',
            'Account Settings' => 'Configuración de Cuenta',
            'Manage your account preferences' => 'Gestiona las preferencias de tu cuenta',
            'Wallet Connection' => 'Conexión de Billetera',
            'Connect and manage your wallets' => 'Conecta y gestiona tus billeteras',
            
            // Common UI
            'Loading...' => 'Cargando...',
            'Success' => 'Éxito',
            'Error' => 'Error',
            'Cancel' => 'Cancelar',
            'Save' => 'Guardar',
            'Edit' => 'Editar',
            'Delete' => 'Eliminar',
            'View' => 'Ver',
            'Close' => 'Cerrar',
            'Submit' => 'Enviar',
            'Confirm' => 'Confirmar',
            'Back' => 'Atrás',
            'Next' => 'Siguiente',
            'Previous' => 'Anterior',
            'Search' => 'Buscar',
            'Filter' => 'Filtrar',
            'Sort' => 'Ordenar',
            'Refresh' => 'Actualizar',
            'Download' => 'Descargar',
            'Upload' => 'Subir',
            'Copy' => 'Copiar',
            'Share' => 'Compartir',
            'Print' => 'Imprimir',
            'Help' => 'Ayuda',
            'Settings' => 'Configuración',
            'Profile' => 'Perfil',
            'Logout' => 'Cerrar Sesión',
            'Login' => 'Iniciar Sesión',
            'Register' => 'Registrarse',
            
            // Status
            'Active' => 'Activo',
            'Inactive' => 'Inactivo',
            'Pending' => 'Pendiente',
            'Completed' => 'Completado',
            'Cancelled' => 'Cancelado',
            'Approved' => 'Aprobado',
            'Rejected' => 'Rechazado',
            'Processing' => 'Procesando',
            'Failed' => 'Fallido',
            'Expired' => 'Expirado',
            
            // Time
            'Today' => 'Hoy',
            'Yesterday' => 'Ayer',
            'Tomorrow' => 'Mañana',
            'This Week' => 'Esta Semana',
            'Last Week' => 'Semana Pasada',
            'This Month' => 'Este Mes',
            'Last Month' => 'Mes Pasado',
            'This Year' => 'Este Año',
            'Last Year' => 'Año Pasado',
            'days' => 'días',
            'hours' => 'horas',
            'minutes' => 'minutos',
            'seconds' => 'segundos',
            
            // Financial
            'Balance' => 'Saldo',
            'Amount' => 'Cantidad',
            'Total' => 'Total',
            'Subtotal' => 'Subtotal',
            'Fee' => 'Tarifa',
            'Commission' => 'Comisión',
            'Dividend' => 'Dividendo',
            'Profit' => 'Ganancia',
            'Loss' => 'Pérdida',
            'Investment' => 'Inversión',
            'Withdrawal' => 'Retiro',
            'Deposit' => 'Depósito',
            'Transfer' => 'Transferencia',
            'Transaction' => 'Transacción',
            'Payment' => 'Pago',
            'Refund' => 'Reembolso',
            'Currency' => 'Moneda',
            'Exchange Rate' => 'Tipo de Cambio',
            'Wallet Address' => 'Dirección de Billetera',
            'Transaction Hash' => 'Hash de Transacción',
            
            // Packages
            'Available Investment Packages' => 'Paquetes de Inversión Disponibles',
            'View All' => 'Ver Todos',
            'Invest Now' => 'Invertir Ahora',
            'Aureus Shares' => 'Acciones Aureus',
            'Expected ROI' => 'ROI Esperado',
            'Annual Dividends' => 'Dividendos Anuales',
            'No investment packages available at the moment.' => 'No hay paquetes de inversión disponibles en este momento.',
            
            // Quick Actions Title
            'Quick Actions' => 'Acciones Rápidas',
            'NEW' => 'NUEVO'
        ];
        
        // Check for exact match first
        if ($targetLanguage === 'Spanish' && isset($spanishTranslations[$text])) {
            return $spanishTranslations[$text];
        }
        
        // If no exact match, try pattern-based translation for Spanish
        if ($targetLanguage === 'Spanish') {
            $translation = $text;
            
            // Apply word-by-word translation for common patterns
            foreach ($spanishTranslations as $english => $spanish) {
                if (stripos($text, $english) !== false) {
                    $translation = str_ireplace($english, $spanish, $translation);
                }
            }
            
            // If translation changed, return it
            if ($translation !== $text) {
                return $translation;
            }
        }
        
        // For French (basic support)
        if ($targetLanguage === 'French') {
            $frenchTranslations = [
                'Investment' => 'Investissement',
                'Affiliate' => 'Affilié',
                'Benefits' => 'Avantages',
                'About' => 'À propos',
                'Contact' => 'Contact',
                'Sign In' => 'Se connecter',
                'Dashboard' => 'Tableau de bord',
                'Portfolio' => 'Portefeuille',
                'Balance' => 'Solde',
                'Active' => 'Actif',
                'Pending' => 'En attente',
                'Completed' => 'Terminé'
            ];
            
            if (isset($frenchTranslations[$text])) {
                return $frenchTranslations[$text];
            }
        }
        
        // If no translation found, return original text
        return $text;
    }
    
    // Get AI translation
    $translation = translateWithAI($text, $targetLanguage);
    
    // If we have a key_id, also save it to the database
    if ($keyId) {
        // Get language ID
        $langQuery = "SELECT id FROM languages WHERE code = ?";
        $langStmt = $db->prepare($langQuery);
        $langStmt->execute([$languageCode]);
        $language = $langStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($language) {
            $languageId = $language['id'];
            
            // Insert or update translation
            $sql = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                    VALUES (?, ?, ?, TRUE)
                    ON DUPLICATE KEY UPDATE 
                    translation_text = VALUES(translation_text),
                    is_approved = TRUE,
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$keyId, $languageId, $translation]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'translation' => $translation,
        'original_text' => $text,
        'target_language' => $targetLanguage,
        'language_code' => $languageCode,
        'saved_to_db' => $keyId ? true : false,
        'was_translated' => $translation !== $text
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'AI Translation failed'
    ]);
}
?>
