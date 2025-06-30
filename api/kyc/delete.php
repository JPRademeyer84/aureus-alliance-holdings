<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $documentId = $_GET['id'] ?? '';

    error_log("KYC Delete: Attempting to delete document ID: " . $documentId);
    error_log("KYC Delete: User ID from session: " . ($_SESSION['user_id'] ?? 'Not set'));

    if (!$documentId || empty(trim($documentId))) {
        error_log("KYC Delete: Invalid document ID provided");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid document ID']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Get document info and verify ownership
    $stmt = $pdo->prepare("
        SELECT file_path, user_id, status
        FROM kyc_documents
        WHERE id = ?
    ");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        http_response_code(404);
        echo json_encode(['error' => 'Document not found']);
        exit;
    }

    // Verify user owns this document
    if ($document['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Prevent deletion of approved documents
    if ($document['status'] === 'approved') {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete approved documents. Please contact support if you need to update your KYC information.']);
        exit;
    }
    
    // Delete the file
    $filePath = "../../assets/kyc/" . basename($document['file_path']);
    if (file_exists($filePath)) {
        unlink($filePath);
        error_log("KYC Delete: File deleted successfully: " . $filePath);
    } else {
        error_log("KYC Delete: File not found: " . $filePath);
    }
    
    // Delete the database record
    $stmt = $pdo->prepare("DELETE FROM kyc_documents WHERE id = ?");
    $stmt->execute([$documentId]);
    
    // Check if user has any remaining documents
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as doc_count
        FROM kyc_documents
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no documents remain, reset KYC status to not_verified
    if ($result['doc_count'] == 0) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET kyc_status = 'not_verified', facial_verification_status = 'not_started'
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Document deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("KYC Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
