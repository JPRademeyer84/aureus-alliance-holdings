<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all translation keys
    $query = "SELECT id, key_name, description, category, created_at
              FROM translation_keys 
              ORDER BY category, key_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $keys = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $keys[] = [
            'id' => (int)$row['id'],
            'key_name' => $row['key_name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'keys' => $keys,
        'count' => count($keys)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch translation keys: ' . $e->getMessage()
    ]);
}
?>
