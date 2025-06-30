<?php
// Master script to create all dashboard translation keys
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

$results = [];
$totalKeys = 0;
$totalTranslations = 0;

// Function to execute a script and capture results
function executeScript($scriptPath) {
    $url = "http://localhost/aureus-angel-alliance/api/translations/" . $scriptPath;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    } else {
        return ['success' => false, 'error' => 'HTTP Error: ' . $httpCode];
    }
}

try {
    // Execute all translation key creation scripts
    $scripts = [
        'create-dashboard-translation-keys.php' => 'Dashboard Navigation & Stats',
        'create-investment-translation-keys.php' => 'Investment Pages & Forms',
        'create-profile-affiliate-translation-keys.php' => 'Profile, Affiliate & Support'
    ];
    
    foreach ($scripts as $script => $description) {
        echo "Executing: $description...\n";
        $result = executeScript($script);
        
        if ($result && $result['success']) {
            $results[] = [
                'script' => $description,
                'success' => true,
                'keys_processed' => $result['keys_processed'] ?? 0,
                'new_keys_created' => $result['new_keys_created'] ?? 0,
                'new_translations_created' => $result['new_translations_created'] ?? 0,
                'categories' => $result['categories'] ?? []
            ];
            
            $totalKeys += $result['new_keys_created'] ?? 0;
            $totalTranslations += $result['new_translations_created'] ?? 0;
        } else {
            $results[] = [
                'script' => $description,
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error'
            ];
        }
    }
    
    // Summary
    $allCategories = [];
    foreach ($results as $result) {
        if (isset($result['categories'])) {
            $allCategories = array_merge($allCategories, $result['categories']);
        }
    }
    $allCategories = array_unique($allCategories);
    
    echo json_encode([
        'success' => true,
        'message' => 'All dashboard translation keys creation completed',
        'summary' => [
            'total_scripts_executed' => count($scripts),
            'total_new_keys_created' => $totalKeys,
            'total_new_translations_created' => $totalTranslations,
            'categories_created' => $allCategories,
            'languages_supported' => ['English', 'Spanish']
        ],
        'detailed_results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'partial_results' => $results
    ]);
}
?>
