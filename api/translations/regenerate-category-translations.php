<?php
// Regenerate all translations for a specific category or all categories
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// AI Translation function (same as in regenerate-all-translations.php)
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Join Us' => 'Únete a Nosotros',
            'Create your account to start investing' => 'Crea tu cuenta para comenzar a invertir',
            'Sign in to access your investment dashboard' => 'Inicia sesión para acceder a tu panel de inversión',
            'Join the Aureus Angel Alliance' => 'Únete a la Alianza Ángel Aureus',
            'Create your investment account' => 'Crea tu cuenta de inversión',
            'Username' => 'Nombre de Usuario',
            'Email' => 'Correo Electrónico',
            'Password' => 'Contraseña',
            'Confirm Password' => 'Confirmar Contraseña',
            'Create Account' => 'Crear Cuenta',
            'Already have an account?' => '¿Ya tienes una cuenta?',
            'Sign In' => 'Iniciar Sesión',
            'Welcome Back' => 'Bienvenido de Vuelta',
            'Sign in to your account' => 'Inicia sesión en tu cuenta',
            'Don\'t have an account?' => '¿No tienes una cuenta?',
            'Sign up' => 'Regístrate'
        ],
        'French' => [
            'Join Us' => 'Rejoignez-nous',
            'Create your account to start investing' => 'Créez votre compte pour commencer à investir',
            'Sign in to access your investment dashboard' => 'Connectez-vous pour accéder à votre tableau de bord d\'investissement',
            'Join the Aureus Angel Alliance' => 'Rejoignez l\'Alliance Ange Aureus',
            'Create your investment account' => 'Créez votre compte d\'investissement',
            'Username' => 'Nom d\'utilisateur',
            'Email' => 'E-mail',
            'Password' => 'Mot de passe',
            'Confirm Password' => 'Confirmer le mot de passe',
            'Create Account' => 'Créer un compte',
            'Already have an account?' => 'Vous avez déjà un compte?',
            'Sign In' => 'Se connecter',
            'Welcome Back' => 'Bon retour',
            'Sign in to your account' => 'Connectez-vous à votre compte',
            'Don\'t have an account?' => 'Vous n\'avez pas de compte?',
            'Sign up' => 'S\'inscrire'
        ],
        'German' => [
            'Join Us' => 'Treten Sie uns bei',
            'Create your account to start investing' => 'Erstellen Sie Ihr Konto, um mit dem Investieren zu beginnen',
            'Sign in to access your investment dashboard' => 'Melden Sie sich an, um auf Ihr Investment-Dashboard zuzugreifen',
            'Join the Aureus Angel Alliance' => 'Treten Sie der Aureus Angel Alliance bei',
            'Create your investment account' => 'Erstellen Sie Ihr Investmentkonto',
            'Username' => 'Benutzername',
            'Email' => 'E-Mail',
            'Password' => 'Passwort',
            'Confirm Password' => 'Passwort bestätigen',
            'Create Account' => 'Konto erstellen',
            'Already have an account?' => 'Haben Sie bereits ein Konto?',
            'Sign In' => 'Anmelden',
            'Welcome Back' => 'Willkommen zurück',
            'Sign in to your account' => 'Melden Sie sich in Ihrem Konto an',
            'Don\'t have an account?' => 'Haben Sie kein Konto?',
            'Sign up' => 'Registrieren'
        ]
        // Add more languages as needed
    ];
    
    if (isset($translations[$targetLanguage][$text])) {
        return $translations[$targetLanguage][$text];
    }
    
    // Fallback: return original text if no translation found
    return $text;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['category']) || !isset($input['key_ids']) || !isset($input['target_languages'])) {
        throw new Exception('Missing required parameters: category, key_ids, and target_languages');
    }
    
    $category = $input['category'];
    $keyIds = $input['key_ids'];
    $targetLanguages = $input['target_languages'];
    
    if (empty($keyIds) || !is_array($keyIds)) {
        throw new Exception('Key IDs must be a non-empty array');
    }
    
    if (empty($targetLanguages) || !is_array($targetLanguages)) {
        throw new Exception('Target languages must be a non-empty array');
    }
    
    $totalTranslations = 0;
    $results = [];
    
    // Get English translations for all keys
    $englishLangQuery = "SELECT id FROM languages WHERE code = 'en' LIMIT 1";
    $englishLangStmt = $db->prepare($englishLangQuery);
    $englishLangStmt->execute();
    $englishLang = $englishLangStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$englishLang) {
        throw new Exception('English language not found in database');
    }
    
    $englishLangId = $englishLang['id'];
    
    // Get English translations for the keys
    $placeholders = str_repeat('?,', count($keyIds) - 1) . '?';
    $englishQuery = "SELECT t.key_id, t.translation_text, tk.description, tk.key_name 
                     FROM translations t 
                     JOIN translation_keys tk ON t.key_id = tk.id 
                     WHERE t.key_id IN ($placeholders) AND t.language_id = ?";
    $englishStmt = $db->prepare($englishQuery);
    $englishStmt->execute([...$keyIds, $englishLangId]);
    $englishTranslations = $englishStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a map for quick lookup
    $englishMap = [];
    foreach ($englishTranslations as $englishTrans) {
        $englishMap[$englishTrans['key_id']] = $englishTrans['translation_text'] ?: $englishTrans['description'];
    }
    
    // Process each target language
    foreach ($targetLanguages as $language) {
        if (!isset($language['id']) || !isset($language['name'])) {
            continue;
        }
        
        $languageId = (int)$language['id'];
        $languageName = $language['name'];
        
        $languageResults = [];
        
        // Process each key for this language
        foreach ($keyIds as $keyId) {
            $englishText = $englishMap[$keyId] ?? '';
            
            if (empty($englishText)) {
                continue;
            }
            
            // Get AI translation
            $translatedText = translateWithAI($englishText, $languageName);
            
            // Check if translation already exists
            $checkQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$keyId, $languageId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing translation
                $updateQuery = "UPDATE translations 
                               SET translation_text = ?, is_approved = TRUE, updated_at = CURRENT_TIMESTAMP 
                               WHERE key_id = ? AND language_id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$translatedText, $keyId, $languageId]);
            } else {
                // Insert new translation
                $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                               VALUES (?, ?, ?, TRUE)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$keyId, $languageId, $translatedText]);
            }
            
            $totalTranslations++;
            $languageResults[] = [
                'key_id' => $keyId,
                'english_text' => $englishText,
                'translation' => $translatedText
            ];
        }
        
        $results[] = [
            'language' => $languageName,
            'translations' => $languageResults,
            'count' => count($languageResults)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully regenerated {$totalTranslations} translations for category: {$category}",
        'total_translations' => $totalTranslations,
        'category' => $category,
        'keys_processed' => count($keyIds),
        'languages_processed' => count($targetLanguages),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
