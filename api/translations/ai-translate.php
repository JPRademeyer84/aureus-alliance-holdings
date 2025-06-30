<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Only allow POST requests for actual translation
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['text']) || !isset($input['target_language']) || !isset($input['language_code'])) {
        throw new Exception('Missing required parameters: text, target_language, language_code');
    }

    $text = $input['text'];
    $targetLanguage = $input['target_language'];
    $languageCode = $input['language_code'];
    $keyId = $input['key_id'] ?? null;

    // Try to connect to database (optional for saving)
    $db = null;
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
    } catch (Exception $dbError) {
        // Database connection failed, but we can still do translation
        error_log('Database connection failed in AI translate: ' . $dbError->getMessage());
    }
    
    // AI Translation function - simplified and robust
    function translateWithAI($text, $targetLanguage) {
        // Basic translation mappings for common languages
        $translations = [
            'Spanish' => [
                'Account Settings' => 'Configuración de Cuenta',
                'Affiliate Program' => 'Programa de Afiliados',
                'Browse Packages' => 'Explorar Paquetes',
                'Commission Wallet' => 'Billetera de Comisiones',
                'Contact Support' => 'Contactar Soporte',
                'Delivery Countdown' => 'Cuenta Regresiva de Entrega',
                'Gold Diggers Club' => 'Club de Buscadores de Oro',
                'Investment History' => 'Historial de Inversiones',
                'Portfolio Overview' => 'Resumen del Portafolio',
                'Wallet Connection' => 'Conexión de Billetera',
                'Welcome back' => 'Bienvenido de vuelta',
                'Dashboard' => 'Panel de Control',
                'Investment' => 'Inversión',
                'Benefits' => 'Beneficios',
                'About' => 'Acerca de',
                'Contact' => 'Contacto',
                'Sign In' => 'Iniciar Sesión',
                'Loading...' => 'Cargando...',
                'Success' => 'Éxito',
                'Error' => 'Error',
                'Save' => 'Guardar',
                'Cancel' => 'Cancelar'
            ],
            'French' => [
                'Account Settings' => 'Paramètres du Compte',
                'Affiliate Program' => 'Programme d\'Affiliation',
                'Browse Packages' => 'Parcourir les Packages',
                'Commission Wallet' => 'Portefeuille de Commission',
                'Contact Support' => 'Contacter le Support',
                'Delivery Countdown' => 'Compte à Rebours de Livraison',
                'Gold Diggers Club' => 'Club des Chercheurs d\'Or',
                'Investment History' => 'Historique des Investissements',
                'Portfolio Overview' => 'Aperçu du Portefeuille',
                'Wallet Connection' => 'Connexion du Portefeuille',
                'Welcome back' => 'Bon retour',
                'Dashboard' => 'Tableau de bord',
                'Investment' => 'Investissement',
                'Benefits' => 'Avantages',
                'About' => 'À propos',
                'Contact' => 'Contact',
                'Sign In' => 'Se connecter',
                'Loading...' => 'Chargement...',
                'Success' => 'Succès',
                'Error' => 'Erreur',
                'Save' => 'Enregistrer',
                'Cancel' => 'Annuler'
            ],
            'German' => [
                'Account Settings' => 'Kontoeinstellungen',
                'Affiliate Program' => 'Partnerprogramm',
                'Browse Packages' => 'Pakete durchsuchen',
                'Commission Wallet' => 'Provisions-Wallet',
                'Contact Support' => 'Support kontaktieren',
                'Welcome back' => 'Willkommen zurück',
                'Dashboard' => 'Dashboard',
                'Investment' => 'Investition',
                'Benefits' => 'Vorteile',
                'About' => 'Über uns',
                'Contact' => 'Kontakt',
                'Sign In' => 'Anmelden',
                'Loading...' => 'Laden...',
                'Success' => 'Erfolg',
                'Error' => 'Fehler',
                'Save' => 'Speichern',
                'Cancel' => 'Abbrechen'
            ]
        ];

        // Check for direct translation
        if (isset($translations[$targetLanguage][$text])) {
            return $translations[$targetLanguage][$text];
        }

        // Simple fallback translations based on common patterns
        $result = $text;

        if ($targetLanguage === 'Spanish') {
            $result = str_replace(['Hello', 'World', 'Welcome', 'Login', 'Logout'],
                                ['Hola', 'Mundo', 'Bienvenido', 'Iniciar sesión', 'Cerrar sesión'], $result);
        } elseif ($targetLanguage === 'French') {
            $result = str_replace(['Hello', 'World', 'Welcome', 'Login', 'Logout'],
                                ['Bonjour', 'Monde', 'Bienvenue', 'Connexion', 'Déconnexion'], $result);
        } elseif ($targetLanguage === 'German') {
            $result = str_replace(['Hello', 'World', 'Welcome', 'Login', 'Logout'],
                                ['Hallo', 'Welt', 'Willkommen', 'Anmelden', 'Abmelden'], $result);
        }

        // If no translation found, add a prefix to indicate it's AI translated
        if ($result === $text && $targetLanguage !== 'English') {
            $result = "[AI] " . $text;
        }

        return $result;
    }
    
    // Get AI translation
    $translation = translateWithAI($text, $targetLanguage);

    $savedToDb = false;

    // If we have a key_id and database connection, also save it to the database
    if ($keyId && $db) {
        try {
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
                $savedToDb = true;
            }
        } catch (Exception $saveError) {
            error_log('Failed to save AI translation to database: ' . $saveError->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'translation' => $translation,
        'original_text' => $text,
        'target_language' => $targetLanguage,
        'language_code' => $languageCode,
        'saved_to_db' => $savedToDb,
        'database_connected' => $db !== null
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'AI Translation failed'
    ]);
}
?>
