<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['original_text']) || !isset($input['translated_text']) || !isset($input['target_language'])) {
        throw new Exception('Missing required parameters: original_text, translated_text, target_language');
    }
    
    $originalText = $input['original_text'];
    $translatedText = $input['translated_text'];
    $targetLanguage = $input['target_language'];
    $languageCode = $input['language_code'] ?? '';
    
    // Translation verification function
    function verifyTranslation($original, $translated, $targetLanguage) {
        // Known correct translations for verification
        $knownTranslations = [
            'Spanish' => [
                'Investment' => ['Inversión'],
                'Affiliate' => ['Afiliado'],
                'Benefits' => ['Beneficios'],
                'About' => ['Acerca de', 'Sobre'],
                'Contact' => ['Contacto'],
                'Sign In' => ['Iniciar Sesión', 'Ingresar'],
                'Become an Angel Investor' => ['Conviértete en un Inversionista Ángel'],
                'in the Future of Digital Gold' => ['en el Futuro del Oro Digital'],
                'Invest Now' => ['Invertir Ahora'],
                'Learn More' => ['Aprende Más', 'Saber Más'],
                'Welcome Back' => ['Bienvenido de Vuelta', 'Bienvenido de nuevo'],
                'Email' => ['Correo Electrónico', 'Email'],
                'Password' => ['Contraseña'],
                'Dashboard' => ['Panel de Control', 'Tablero'],
                'Portfolio' => ['Portafolio', 'Cartera'],
                'Balance' => ['Saldo'],
                'Transaction' => ['Transacción'],
                'Active' => ['Activo'],
                'Pending' => ['Pendiente'],
                'Completed' => ['Completado', 'Terminado'],
                'Loading...' => ['Cargando...'],
                'Success' => ['Éxito'],
                'Error' => ['Error'],
                'Cancel' => ['Cancelar'],
                'Save' => ['Guardar'],
                'Edit' => ['Editar'],
                'Delete' => ['Eliminar', 'Borrar'],
                'View' => ['Ver'],
                'Close' => ['Cerrar'],
                'Submit' => ['Enviar'],
                'Confirm' => ['Confirmar'],
                'Back' => ['Atrás', 'Volver'],
                'Next' => ['Siguiente'],
                'Previous' => ['Anterior'],
                'Search' => ['Buscar'],
                'Filter' => ['Filtrar'],
                'Sort' => ['Ordenar'],
                'Refresh' => ['Actualizar'],
                'Download' => ['Descargar'],
                'Upload' => ['Subir', 'Cargar'],
                'Copy' => ['Copiar'],
                'Share' => ['Compartir'],
                'Help' => ['Ayuda'],
                'Settings' => ['Configuración', 'Ajustes']
            ],
            'French' => [
                'Investment' => ['Investissement'],
                'Affiliate' => ['Affilié'],
                'Benefits' => ['Avantages'],
                'About' => ['À propos'],
                'Contact' => ['Contact'],
                'Sign In' => ['Se connecter', 'Connexion'],
                'Dashboard' => ['Tableau de bord'],
                'Portfolio' => ['Portefeuille'],
                'Balance' => ['Solde'],
                'Active' => ['Actif'],
                'Pending' => ['En attente'],
                'Completed' => ['Terminé', 'Complété']
            ]
        ];
        
        $suggestions = [];
        $accuracyScore = 0;
        
        // Check if we have known translations for this text
        if (isset($knownTranslations[$targetLanguage][$original])) {
            $correctTranslations = $knownTranslations[$targetLanguage][$original];
            
            // Check if the translation matches any of the correct ones
            $isCorrect = false;
            foreach ($correctTranslations as $correctTranslation) {
                if (strcasecmp(trim($translated), trim($correctTranslation)) === 0) {
                    $isCorrect = true;
                    $accuracyScore = 100;
                    break;
                }
            }
            
            if (!$isCorrect) {
                // Check for partial matches
                $bestMatch = '';
                $bestScore = 0;
                
                foreach ($correctTranslations as $correctTranslation) {
                    $similarity = 0;
                    similar_text(strtolower($translated), strtolower($correctTranslation), $similarity);
                    if ($similarity > $bestScore) {
                        $bestScore = $similarity;
                        $bestMatch = $correctTranslation;
                    }
                }
                
                $accuracyScore = (int)$bestScore;
                
                if ($bestScore < 90) {
                    $suggestions[] = "Consider using: '{$bestMatch}'";
                }
                
                if (count($correctTranslations) > 1) {
                    $suggestions[] = "Alternative translations: " . implode(', ', $correctTranslations);
                }
            }
        } else {
            // For unknown translations, do basic checks
            $accuracyScore = performBasicChecks($original, $translated, $targetLanguage);
            
            if ($accuracyScore < 70) {
                $suggestions[] = "Translation may need review - not found in verified dictionary";
                $suggestions[] = "Check for proper grammar and terminology";
            }
        }
        
        // Additional quality checks
        if (empty(trim($translated))) {
            $accuracyScore = 0;
            $suggestions[] = "Translation is empty";
        } elseif ($translated === $original) {
            $accuracyScore = 10;
            $suggestions[] = "Translation appears to be the same as original text";
        }
        
        return [
            'accuracy_score' => $accuracyScore,
            'suggestions' => $suggestions,
            'has_known_translation' => isset($knownTranslations[$targetLanguage][$original])
        ];
    }
    
    // Basic checks for unknown translations
    function performBasicChecks($original, $translated, $targetLanguage) {
        $score = 50; // Base score for unknown translations
        
        // Check if translation is different from original
        if ($translated !== $original) {
            $score += 20;
        }
        
        // Check length similarity (translations shouldn't be too different in length)
        $lengthRatio = strlen($translated) / max(strlen($original), 1);
        if ($lengthRatio >= 0.5 && $lengthRatio <= 2.0) {
            $score += 15;
        }
        
        // Check for common patterns based on target language
        if ($targetLanguage === 'Spanish') {
            // Spanish-specific checks
            if (preg_match('/[ñáéíóúü]/i', $translated)) {
                $score += 10; // Contains Spanish characters
            }
            if (preg_match('/ción$|sión$/i', $translated)) {
                $score += 5; // Common Spanish endings
            }
        } elseif ($targetLanguage === 'French') {
            // French-specific checks
            if (preg_match('/[àâäéèêëïîôöùûüÿç]/i', $translated)) {
                $score += 10; // Contains French characters
            }
            if (preg_match('/tion$|sion$/i', $translated)) {
                $score += 5; // Common French endings
            }
        }
        
        return min($score, 85); // Cap at 85% for unknown translations
    }
    
    // Perform verification
    $result = verifyTranslation($originalText, $translatedText, $targetLanguage);
    
    echo json_encode([
        'success' => true,
        'accuracy_score' => $result['accuracy_score'],
        'suggestions' => $result['suggestions'],
        'has_known_translation' => $result['has_known_translation'],
        'original_text' => $originalText,
        'translated_text' => $translatedText,
        'target_language' => $targetLanguage,
        'verification_status' => $result['accuracy_score'] >= 90 ? 'excellent' : 
                                ($result['accuracy_score'] >= 70 ? 'good' : 'needs_improvement')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Translation verification failed'
    ]);
}
?>
