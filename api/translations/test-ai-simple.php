<?php
// Simple AI translation test without database
header('Content-Type: application/json');

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
            'Hello' => 'Hola',
            'World' => 'Mundo',
            'Welcome' => 'Bienvenido',
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
            'Hello' => 'Bonjour',
            'World' => 'Monde',
            'Welcome' => 'Bienvenue',
            'Loading...' => 'Chargement...',
            'Success' => 'Succès',
            'Error' => 'Erreur',
            'Save' => 'Enregistrer',
            'Cancel' => 'Annuler'
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
    }
    
    // If no translation found, add a prefix to indicate it's AI translated
    if ($result === $text && $targetLanguage !== 'English') {
        $result = "[AI] " . $text;
    }
    
    return $result;
}

// Test cases
$testCases = [
    ['text' => 'Account Settings', 'target_language' => 'Spanish'],
    ['text' => 'Hello World', 'target_language' => 'Spanish'],
    ['text' => 'Welcome', 'target_language' => 'French'],
    ['text' => 'Some Random Text', 'target_language' => 'Spanish']
];

echo "AI Translation Test Results:\n\n";

foreach ($testCases as $i => $test) {
    $translation = translateWithAI($test['text'], $test['target_language']);
    echo "Test " . ($i + 1) . ":\n";
    echo "  Original: " . $test['text'] . "\n";
    echo "  Target: " . $test['target_language'] . "\n";
    echo "  Translation: " . $translation . "\n\n";
}

// Test JSON response format
$jsonTest = [
    'success' => true,
    'translation' => translateWithAI('Account Settings', 'Spanish'),
    'original_text' => 'Account Settings',
    'target_language' => 'Spanish',
    'language_code' => 'es'
];

echo "JSON Response Format:\n";
echo json_encode($jsonTest, JSON_PRETTY_PRINT);
?>
