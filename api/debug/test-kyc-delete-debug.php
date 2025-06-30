<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $debug_info = [
        'session_status' => [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'Not set',
            'session_data' => $_SESSION ?? []
        ],
        'request_info' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'query_params' => $_GET,
            'headers' => getallheaders()
        ],
        'database_connection' => $pdo ? 'Connected' : 'Failed',
        'sample_documents' => []
    ];
    
    // Get some sample KYC documents for testing
    if ($pdo && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            SELECT id, type, filename, status, user_id, upload_date
            FROM kyc_documents 
            WHERE user_id = ? 
            ORDER BY upload_date DESC 
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $debug_info['sample_documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all documents for admin testing
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT id, type, filename, status, user_id, upload_date
            FROM kyc_documents 
            ORDER BY upload_date DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $debug_info['all_sample_documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Debug failed',
        'message' => $e->getMessage(),
        'session_status' => [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'Not set'
        ]
    ], JSON_PRETTY_PRINT);
}
?>
