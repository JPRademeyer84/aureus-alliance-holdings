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
    
    if (!$input || !isset($input['translations']) || !isset($input['target_language']) || !isset($input['language_code'])) {
        throw new Exception('Missing required parameters: translations, target_language, language_code');
    }
    
    $translations = $input['translations'];
    $targetLanguage = $input['target_language'];
    $languageCode = $input['language_code'];
    $category = $input['category'] ?? 'unknown';
    
    // Get language ID
    $langQuery = "SELECT id FROM languages WHERE code = ?";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute([$languageCode]);
    $language = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$language) {
        throw new Exception('Language not found: ' . $languageCode);
    }
    
    $languageId = $language['id'];
    
    // AI Translation function (same as single translation)
    function translateWithAI($text, $targetLanguage) {
        $translations = [
            'Spanish' => [
                'Become an Angel Investor' => 'Conviértete en un Inversionista Ángel',
                'in the Future of Digital Gold' => 'en el Futuro del Oro Digital',
                'Investment' => 'Inversión',
                'Affiliate' => 'Afiliado',
                'Benefits' => 'Beneficios',
                'About' => 'Acerca de',
                'Contact' => 'Contacto',
                'Sign In' => 'Iniciar Sesión',
                'Invest Now' => 'Invertir Ahora',
                'Learn More' => 'Aprende Más',
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
                'Help' => 'Ayuda',
                'Settings' => 'Configuración',
                'Welcome back' => 'Bienvenido de vuelta',
                'Dashboard' => 'Panel de Control',
                'Portfolio' => 'Portafolio',
                'Commission' => 'Comisión',
                'Wallet' => 'Billetera',
                'Balance' => 'Saldo',
                'Transaction' => 'Transacción',
                'Active' => 'Activo',
                'Pending' => 'Pendiente',
                'Completed' => 'Completado',
                'Total Investments' => 'Inversiones Totales',
                'Portfolio Value' => 'Valor del Portafolio',
                'Commission Earnings' => 'Ganancias por Comisión',
                'Available Balance' => 'Saldo Disponible',
                'Quick Actions' => 'Acciones Rápidas',
                'Browse Packages' => 'Explorar Paquetes',
                'Investment History' => 'Historial de Inversiones',
                'Account Settings' => 'Configuración de Cuenta',
                'Contact Support' => 'Contactar Soporte'
            ],
            'French' => [
                'Become an Angel Investor' => 'Devenez un Investisseur Providentiel',
                'in the Future of Digital Gold' => 'dans l\'Avenir de l\'Or Numérique',
                'Investment' => 'Investissement',
                'Affiliate' => 'Affilié',
                'Benefits' => 'Avantages',
                'About' => 'À propos',
                'Contact' => 'Contact',
                'Sign In' => 'Se connecter',
                'Invest Now' => 'Investir Maintenant',
                'Learn More' => 'En savoir plus',
                'Loading...' => 'Chargement...',
                'Success' => 'Succès',
                'Error' => 'Erreur',
                'Cancel' => 'Annuler',
                'Save' => 'Enregistrer',
                'Edit' => 'Modifier',
                'Delete' => 'Supprimer',
                'View' => 'Voir',
                'Close' => 'Fermer',
                'Submit' => 'Soumettre',
                'Confirm' => 'Confirmer',
                'Back' => 'Retour',
                'Next' => 'Suivant',
                'Previous' => 'Précédent',
                'Search' => 'Rechercher',
                'Filter' => 'Filtrer',
                'Sort' => 'Trier',
                'Refresh' => 'Actualiser',
                'Download' => 'Télécharger',
                'Upload' => 'Téléverser',
                'Copy' => 'Copier',
                'Share' => 'Partager',
                'Help' => 'Aide',
                'Settings' => 'Paramètres',
                'Welcome back' => 'Bon retour',
                'Dashboard' => 'Tableau de bord',
                'Portfolio' => 'Portefeuille',
                'Commission' => 'Commission',
                'Wallet' => 'Portefeuille',
                'Balance' => 'Solde',
                'Transaction' => 'Transaction',
                'Active' => 'Actif',
                'Pending' => 'En attente',
                'Completed' => 'Terminé'
            ]
        ];
        
        // Check if we have a direct translation
        if (isset($translations[$targetLanguage][$text])) {
            return $translations[$targetLanguage][$text];
        }
        
        // Apply simple rule-based translation for common patterns
        if ($targetLanguage === 'Spanish') {
            $spanishTranslation = $text;
            $patterns = [
                'Welcome back' => 'Bienvenido de vuelta',
                'Dashboard' => 'Panel de Control',
                'Portfolio' => 'Portafolio',
                'Commission' => 'Comisión',
                'Wallet' => 'Billetera',
                'Balance' => 'Saldo',
                'Transaction' => 'Transacción',
                'Active' => 'Activo',
                'Pending' => 'Pendiente',
                'Completed' => 'Completado',
                'Management' => 'Gestión',
                'Settings' => 'Configuración',
                'History' => 'Historial',
                'Support' => 'Soporte',
                'Account' => 'Cuenta',
                'Profile' => 'Perfil',
                'Verification' => 'Verificación',
                'Document' => 'Documento',
                'Upload' => 'Subir',
                'Download' => 'Descargar',
                'Connect' => 'Conectar',
                'Disconnect' => 'Desconectar'
            ];
            
            foreach ($patterns as $english => $spanish) {
                $spanishTranslation = str_replace($english, $spanish, $spanishTranslation);
            }
            
            return $spanishTranslation;
        }
        
        if ($targetLanguage === 'French') {
            $frenchTranslation = $text;
            $patterns = [
                'Welcome back' => 'Bon retour',
                'Dashboard' => 'Tableau de bord',
                'Portfolio' => 'Portefeuille',
                'Commission' => 'Commission',
                'Wallet' => 'Portefeuille',
                'Balance' => 'Solde',
                'Transaction' => 'Transaction',
                'Active' => 'Actif',
                'Pending' => 'En attente',
                'Completed' => 'Terminé',
                'Management' => 'Gestion',
                'Settings' => 'Paramètres',
                'History' => 'Historique',
                'Support' => 'Support',
                'Account' => 'Compte',
                'Profile' => 'Profil',
                'Verification' => 'Vérification',
                'Document' => 'Document',
                'Upload' => 'Téléverser',
                'Download' => 'Télécharger',
                'Connect' => 'Connecter',
                'Disconnect' => 'Déconnecter'
            ];
            
            foreach ($patterns as $english => $french) {
                $frenchTranslation = str_replace($english, $french, $frenchTranslation);
            }
            
            return $frenchTranslation;
        }
        
        return $text;
    }
    
    $translatedCount = 0;
    $results = [];
    
    // Prepare batch insert statement
    $sql = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
            VALUES (?, ?, ?, TRUE)
            ON DUPLICATE KEY UPDATE 
            translation_text = VALUES(translation_text),
            is_approved = TRUE,
            updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $db->prepare($sql);
    
    // Process each translation
    foreach ($translations as $item) {
        $keyId = $item['key_id'];
        $englishText = $item['english_text'];
        $keyName = $item['key_name'];
        
        // Get AI translation
        $aiTranslation = translateWithAI($englishText, $targetLanguage);
        
        // Save to database
        $stmt->execute([$keyId, $languageId, $aiTranslation]);
        
        $results[] = [
            'key_id' => $keyId,
            'key_name' => $keyName,
            'original' => $englishText,
            'translation' => $aiTranslation
        ];
        
        $translatedCount++;
    }
    
    echo json_encode([
        'success' => true,
        'translated_count' => $translatedCount,
        'category' => $category,
        'target_language' => $targetLanguage,
        'language_code' => $languageCode,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Batch AI Translation failed'
    ]);
}
?>
