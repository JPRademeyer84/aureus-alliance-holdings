<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    
    $code = trim($input['code'] ?? '');
    $name = trim($input['name'] ?? '');
    $native_name = trim($input['native_name'] ?? '');
    $flag = trim($input['flag'] ?? '');
    $sort_order = (int)($input['sort_order'] ?? 0);
    
    if (empty($code) || empty($name) || empty($native_name)) {
        throw new Exception('Code, name, and native name are required');
    }
    
    // Check if language code already exists
    $check_query = "SELECT id FROM languages WHERE code = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$code]);
    
    if ($check_stmt->fetch()) {
        throw new Exception('Language code already exists');
    }
    
    // Insert new language
    $query = "INSERT INTO languages (code, name, native_name, flag_emoji, sort_order, is_active) 
              VALUES (?, ?, ?, ?, ?, TRUE)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$code, $name, $native_name, $flag, $sort_order]);
    
    $language_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Language added successfully',
        'language_id' => $language_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
