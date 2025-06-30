<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['translations']) || !isset($input['target_language'])) {
        throw new Exception('Missing required parameters: translations, target_language');
    }
    
    $translations = $input['translations'];
    $targetLanguage = $input['target_language'];
    $languageCode = $input['language_code'] ?? '';
    $category = $input['category'] ?? 'unknown';
    
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
            'Settings' => ['Configuración', 'Ajustes'],
            'Welcome back' => ['Bienvenido de vuelta'],
            'Ready to grow your wealth?' => ['¿Listo para hacer crecer tu riqueza?'],
            'Last login' => ['Último inicio de sesión'],
            'INVESTOR' => ['INVERSIONISTA'],
            'Commission Earnings' => ['Ganancias por Comisión'],
            'Available Balance' => ['Saldo Disponible'],
            'Total Investments' => ['Inversiones Totales'],
            'Portfolio Value' => ['Valor del Portafolio'],
            'Aureus Shares' => ['Acciones Aureus'],
            'Activity' => ['Actividad'],
            'Commission Wallet' => ['Billetera de Comisiones'],
            'Affiliate Program' => ['Programa de Afiliados'],
            'Browse Packages' => ['Explorar Paquetes'],
            'Investment History' => ['Historial de Inversiones'],
            'Account Settings' => ['Configuración de Cuenta'],
            'Contact Support' => ['Contactar Soporte'],
            'Quick Actions' => ['Acciones Rápidas']
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
    
    // Verify single translation
    function verifyTranslation($original, $translated, $targetLanguage, $knownTranslations) {
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
            }
        } else {
            // For unknown translations, do basic checks
            $accuracyScore = performBasicChecks($original, $translated, $targetLanguage);
            
            if ($accuracyScore < 70) {
                $suggestions[] = "Translation may need review - not found in verified dictionary";
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
            'suggestions' => $suggestions
        ];
    }
    
    // Basic checks for unknown translations
    function performBasicChecks($original, $translated, $targetLanguage) {
        $score = 50; // Base score for unknown translations
        
        // Check if translation is different from original
        if ($translated !== $original) {
            $score += 20;
        }
        
        // Check length similarity
        $lengthRatio = strlen($translated) / max(strlen($original), 1);
        if ($lengthRatio >= 0.5 && $lengthRatio <= 2.0) {
            $score += 15;
        }
        
        // Check for common patterns based on target language
        if ($targetLanguage === 'Spanish') {
            if (preg_match('/[ñáéíóúü]/i', $translated)) {
                $score += 10;
            }
            if (preg_match('/ción$|sión$/i', $translated)) {
                $score += 5;
            }
        } elseif ($targetLanguage === 'French') {
            if (preg_match('/[àâäéèêëïîôöùûüÿç]/i', $translated)) {
                $score += 10;
            }
            if (preg_match('/tion$|sion$/i', $translated)) {
                $score += 5;
            }
        }
        
        return min($score, 85);
    }
    
    $results = [];
    $totalAccuracy = 0;
    $verifiedCount = 0;
    $issues = [];
    
    // Verify each translation
    foreach ($translations as $item) {
        $keyId = $item['key_id'];
        $keyName = $item['key_name'];
        $originalText = $item['original_text'];
        $translatedText = $item['translated_text'];
        
        $verification = verifyTranslation($originalText, $translatedText, $targetLanguage, $knownTranslations);
        $accuracy = $verification['accuracy_score'];
        $suggestions = $verification['suggestions'];
        
        $totalAccuracy += $accuracy;
        $verifiedCount++;
        
        $results[] = [
            'key_id' => $keyId,
            'key_name' => $keyName,
            'original' => $originalText,
            'translated' => $translatedText,
            'accuracy' => $accuracy,
            'suggestions' => $suggestions
        ];
        
        // Track issues (accuracy < 80%)
        if ($accuracy < 80) {
            $issues[] = [
                'key_name' => $keyName,
                'accuracy' => $accuracy,
                'suggestion' => !empty($suggestions) ? $suggestions[0] : 'Review translation quality'
            ];
        }
    }
    
    $averageAccuracy = $verifiedCount > 0 ? round($totalAccuracy / $verifiedCount) : 0;
    
    echo json_encode([
        'success' => true,
        'category' => $category,
        'target_language' => $targetLanguage,
        'verified_count' => $verifiedCount,
        'average_accuracy' => $averageAccuracy,
        'issues' => $issues,
        'results' => $results,
        'quality_status' => $averageAccuracy >= 90 ? 'excellent' : 
                           ($averageAccuracy >= 70 ? 'good' : 'needs_improvement')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Category verification failed'
    ]);
}
?>
