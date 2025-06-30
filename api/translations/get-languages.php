<?php
// Simple CORS headers for development
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all active languages
    $query = "SELECT id, code, name, native_name, flag_emoji, is_default, sort_order 
              FROM languages 
              WHERE is_active = TRUE 
              ORDER BY sort_order ASC, name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $languages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $languages[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'native_name' => $row['native_name'],
            'flag' => $row['flag_emoji'],
            'is_default' => (bool)$row['is_default'],
            'sort_order' => (int)$row['sort_order']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'languages' => $languages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch languages: ' . $e->getMessage()
    ]);
}
?>
