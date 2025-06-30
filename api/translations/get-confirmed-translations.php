<?php
// Get confirmed translations for a specific language
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET method is allowed');
    }
    
    $languageId = $_GET['language_id'] ?? null;
    
    if (!$languageId) {
        throw new Exception('Missing required parameter: language_id');
    }
    
    // Check if translation_confirmations table exists
    $checkTableQuery = "SHOW TABLES LIKE 'translation_confirmations'";
    $checkTableStmt = $db->prepare($checkTableQuery);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() === 0) {
        // Table doesn't exist, return empty result
        echo json_encode([
            'success' => true,
            'confirmations' => [],
            'message' => 'No confirmations table found - no confirmed translations'
        ]);
        exit;
    }
    
    // Get all confirmed translations for the language
    $query = "SELECT tc.key_id, tc.language_id, tc.confirmed_by, tc.confirmation_reason, tc.created_at,
                     tk.key_name, l.name as language_name
              FROM translation_confirmations tc
              JOIN translation_keys tk ON tc.key_id = tk.id
              JOIN languages l ON tc.language_id = l.id
              WHERE tc.language_id = ?
              ORDER BY tc.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$languageId]);
    $confirmations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'confirmations' => $confirmations,
        'count' => count($confirmations),
        'language_id' => $languageId,
        'message' => count($confirmations) > 0 ? 'Confirmed translations retrieved successfully' : 'No confirmed translations found'
    ]);
    
} catch (Exception $e) {
    error_log('Get confirmed translations error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to get confirmed translations'
    ]);
}
?>
