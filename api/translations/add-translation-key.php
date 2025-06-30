<?php
require_once '../config/cors.php';
require_once '../config/database.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $key_name = trim($input['key_name'] ?? '');
    $description = trim($input['description'] ?? '');
    $category = trim($input['category'] ?? '');
    
    if (empty($key_name) || empty($category)) {
        throw new Exception('Key name and category are required');
    }
    
    // Check if key already exists
    $check_query = "SELECT id FROM translation_keys WHERE key_name = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$key_name]);
    
    if ($check_stmt->fetch()) {
        throw new Exception('Translation key already exists');
    }
    
    // Insert new translation key
    $query = "INSERT INTO translation_keys (key_name, description, category) 
              VALUES (?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$key_name, $description, $category]);
    
    $key_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Translation key added successfully',
        'key_id' => $key_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
