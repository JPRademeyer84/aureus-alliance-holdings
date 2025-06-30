<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
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
    $debug = [
        'session_active' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'session_data' => $_SESSION
    ];

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'No user session',
            'debug' => $debug
        ]);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Get user's KYC status
    $stmt = $pdo->prepare("
        SELECT kyc_status, facial_verification_status
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $userKyc = $stmt->fetch(PDO::FETCH_ASSOC);

    $debug['user_kyc_data'] = $userKyc;

    // Get uploaded documents
    $stmt = $pdo->prepare("
        SELECT id, type, filename, status, uploaded_at, file_path
        FROM kyc_documents
        WHERE user_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$userId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug['documents_count'] = count($documents);
    $debug['documents_raw'] = $documents;

    echo json_encode([
        'success' => true,
        'kyc_status' => $userKyc['kyc_status'] ?? 'not_verified',
        'facial_verification_status' => $userKyc['facial_verification_status'] ?? 'not_started',
        'documents' => $documents,
        'debug' => $debug
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('KYC Status Test Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'debug' => $debug ?? []
    ]);
}
?>
