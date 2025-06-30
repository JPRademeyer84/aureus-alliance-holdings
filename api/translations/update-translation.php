<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $key_id = (int)($input['key_id'] ?? 0);
    $language_id = (int)($input['language_id'] ?? 0);
    $translation_text = trim($input['translation_text'] ?? '');
    
    if ($key_id <= 0 || $language_id <= 0) {
        throw new Exception('Valid key ID and language ID are required');
    }
    
    if (empty($translation_text)) {
        // Delete translation if text is empty
        $query = "DELETE FROM translations WHERE key_id = ? AND language_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$key_id, $language_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Translation deleted successfully'
        ]);
        exit;
    }
    
    // Check if translation exists
    $check_query = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$key_id, $language_id]);
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        // Update existing translation
        $query = "UPDATE translations 
                  SET translation_text = ?, is_approved = TRUE, updated_at = CURRENT_TIMESTAMP 
                  WHERE key_id = ? AND language_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$translation_text, $key_id, $language_id]);
        
        $message = 'Translation updated successfully';
    } else {
        // Insert new translation
        $query = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                  VALUES (?, ?, ?, TRUE)";
        $stmt = $db->prepare($query);
        $stmt->execute([$key_id, $language_id, $translation_text]);
        
        $message = 'Translation added successfully';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
