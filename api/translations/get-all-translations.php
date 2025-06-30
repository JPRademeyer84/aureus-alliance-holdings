<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $language_code = $_GET['language'] ?? 'en';
    
    // Get language ID
    $lang_query = "SELECT id FROM languages WHERE code = ? AND is_active = TRUE";
    $lang_stmt = $db->prepare($lang_query);
    $lang_stmt->execute([$language_code]);
    $language = $lang_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$language) {
        throw new Exception('Language not found');
    }
    
    $language_id = $language['id'];
    
    // Get all translation keys with their translations for this language
    $query = "SELECT tk.id as key_id, tk.key_name, tk.description, tk.category,
                     t.id as translation_id, t.translation_text, t.is_approved
              FROM translation_keys tk
              LEFT JOIN translations t ON tk.id = t.key_id AND t.language_id = ?
              ORDER BY tk.category, tk.key_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$language_id]);
    
    $translations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $translations[] = [
            'id' => $row['translation_id'] ? (int)$row['translation_id'] : null,
            'key_id' => (int)$row['key_id'],
            'language_id' => $language_id,
            'key_name' => $row['key_name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'translation_text' => $row['translation_text'],
            'is_approved' => (bool)$row['is_approved']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'language' => $language_code,
        'translations' => $translations,
        'count' => count($translations)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch translations: ' . $e->getMessage()
    ]);
}
?>
