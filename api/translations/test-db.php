<?php
// Simple CORS headers for development
header('Content-Type: application/json');
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
    
    // Test database connection
    $result = [
        'success' => true,
        'message' => 'Database connection successful',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Check if languages table exists
    $tables = $db->query("SHOW TABLES LIKE 'languages'")->fetchAll();
    $result['languages_table_exists'] = count($tables) > 0;
    
    if ($result['languages_table_exists']) {
        // Get languages count
        $count = $db->query("SELECT COUNT(*) as count FROM languages")->fetch();
        $result['languages_count'] = $count['count'];
        
        // Get sample languages
        $languages = $db->query("SELECT * FROM languages LIMIT 5")->fetchAll();
        $result['sample_languages'] = $languages;
    } else {
        $result['message'] = 'Languages table does not exist';
    }
    
    // Check if translations table exists
    $trans_tables = $db->query("SHOW TABLES LIKE 'translations'")->fetchAll();
    $result['translations_table_exists'] = count($trans_tables) > 0;
    
    if ($result['translations_table_exists']) {
        $trans_count = $db->query("SELECT COUNT(*) as count FROM translations")->fetch();
        $result['translations_count'] = $trans_count['count'];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
