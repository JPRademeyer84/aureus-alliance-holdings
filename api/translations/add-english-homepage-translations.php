<?php
// Add English translations for homepage keys
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get English language ID
    $langQuery = "SELECT id FROM languages WHERE code = 'en'";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $englishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$englishLang) {
        throw new Exception('English language not found in database');
    }
    
    $englishLangId = $englishLang['id'];
    
    // Get all homepage keys
    $keyQuery = "SELECT id, key_name, description FROM translation_keys WHERE category = 'homepage'";
    $keyStmt = $db->prepare($keyQuery);
    $keyStmt->execute();
    $keys = $keyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $translationsAdded = [];
    $translationsSkipped = [];
    
    foreach ($keys as $key) {
        // Check if English translation already exists
        $checkQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$key['id'], $englishLangId]);
        
        if ($checkStmt->fetch()) {
            $translationsSkipped[] = $key['key_name'] . ' (already exists)';
            continue;
        }
        
        // Insert English translation using the description as the translation text
        $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                       VALUES (?, ?, ?, TRUE, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$key['id'], $englishLangId, $key['description']]);
        
        $translationsAdded[] = [
            'key_name' => $key['key_name'],
            'translation_text' => $key['description']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'English homepage translations processed successfully',
        'results' => [
            'translations_added' => $translationsAdded,
            'translations_skipped' => $translationsSkipped,
            'added_count' => count($translationsAdded),
            'skipped_count' => count($translationsSkipped)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add English homepage translations',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
