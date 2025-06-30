<?php
/**
 * SECURE DOCUMENT SERVING ENDPOINT
 * Serves KYC documents with proper access control and security headers
 */

require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/secure-file-upload.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get parameters
    $documentId = $_GET['id'] ?? '';
    $filename = $_GET['file'] ?? '';
    
    if (empty($documentId) && empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Document ID or filename required']);
        exit;
    }
    
    // Check authentication
    $isAdmin = isset($_SESSION['admin_id']);
    $isUser = isset($_SESSION['user_id']);
    
    if (!$isAdmin && !$isUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Get document information
    if ($documentId) {
        $query = "SELECT * FROM kyc_documents WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$documentId]);
    } else {
        // Extract user ID from filename for additional security
        $filenameParts = explode('_', $filename);
        if (count($filenameParts) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid filename format']);
            exit;
        }
        
        $query = "SELECT * FROM kyc_documents WHERE file_path LIKE ?";
        $stmt = $db->prepare($query);
        $stmt->execute(['%' . $filename]);
    }
    
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        echo json_encode(['error' => 'Document not found']);
        exit;
    }
    
    // Access control - users can only view their own documents
    if ($isUser && !$isAdmin) {
        if ($document['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }
    
    // Get the actual filename from the file_path
    $actualFilename = basename($document['file_path']);
    
    // Serve the file securely
    $secureUpload = new SecureFileUpload();
    
    try {
        // Log file access
        error_log("Document accessed: ID={$document['id']}, User=" . 
                 ($isAdmin ? "Admin:{$_SESSION['admin_id']}" : "User:{$_SESSION['user_id']}") . 
                 ", File={$actualFilename}");
        
        $secureUpload->serveFile($actualFilename, $document['user_id']);
        
    } catch (Exception $e) {
        error_log("SECURITY: File serving failed - " . $e->getMessage());
        http_response_code(404);
        echo json_encode(['error' => 'File not found or access denied']);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Document serving error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
