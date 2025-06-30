<?php
// Update English translation for a specific key
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['key_id']) || !isset($input['translation_text'])) {
        throw new Exception('Missing required parameters: key_id and translation_text');
    }
    
    $keyId = (int)$input['key_id'];
    $translationText = trim($input['translation_text']);
    
    if (empty($translationText)) {
        throw new Exception('Translation text cannot be empty');
    }
    
    // Get English language ID
    $langQuery = "SELECT id FROM languages WHERE code = 'en' LIMIT 1";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $englishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$englishLang) {
        throw new Exception('English language not found in database');
    }
    
    $englishLangId = $englishLang['id'];
    
    // Check if translation already exists
    $checkQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$keyId, $englishLangId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing English translation
        $updateQuery = "UPDATE translations 
                       SET translation_text = ?, is_approved = TRUE, updated_at = CURRENT_TIMESTAMP 
                       WHERE key_id = ? AND language_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$translationText, $keyId, $englishLangId]);
        
        $message = 'English translation updated successfully';
    } else {
        // Insert new English translation
        $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                       VALUES (?, ?, ?, TRUE)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$keyId, $englishLangId, $translationText]);
        
        $message = 'English translation added successfully';
    }
    
    // Also update the description in translation_keys table as fallback
    $updateKeyQuery = "UPDATE translation_keys SET description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $updateKeyStmt = $db->prepare($updateKeyQuery);
    $updateKeyStmt->execute([$translationText, $keyId]);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'key_id' => $keyId,
        'translation_text' => $translationText
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
