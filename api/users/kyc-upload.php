<?php
// KYC Document Upload API
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/secure-file-upload.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

SecureSession::start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $userId = $_SESSION['user_id'];
    $documentType = $_POST['type'] ?? '';
    
    if (empty($documentType)) {
        sendErrorResponse('Document type is required', 400);
    }

    // Validate document type
    $allowedTypes = ['drivers_license', 'national_id', 'passport'];
    if (!in_array($documentType, $allowedTypes)) {
        sendErrorResponse('Invalid document type', 400);
    }

    // Check if file was uploaded
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        sendErrorResponse('No file uploaded or upload error', 400);
    }

    $file = $_FILES['document'];

    // Use secure file upload system
    try {
        $secureUpload = new SecureFileUpload();
        $uploadResult = $secureUpload->processUpload($file, $documentType, $userId);
    } catch (Exception $e) {
        sendErrorResponse('File upload failed: ' . $e->getMessage(), 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Create KYC documents table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS kyc_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            document_type ENUM('drivers_license', 'national_id', 'passport') NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            verified_by INT NULL,
            rejection_reason TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        )
    ";
    $db->exec($createTableQuery);

    // File has been securely processed and stored by SecureFileUpload
    $fileName = $uploadResult['filename'];
    $filePath = $uploadResult['path'];
    $mimeType = $uploadResult['mime_type'];

    // Check if user already has this document type
    $checkQuery = "SELECT id FROM kyc_documents WHERE user_id = ? AND document_type = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$userId, $documentType]);
    $existingDoc = $checkStmt->fetch();

    if ($existingDoc) {
        // Update existing document
        $updateQuery = "UPDATE kyc_documents SET 
                        file_path = ?, 
                        file_name = ?, 
                        file_size = ?, 
                        mime_type = ?, 
                        status = 'pending', 
                        uploaded_at = NOW(),
                        verified_at = NULL,
                        verified_by = NULL,
                        rejection_reason = NULL
                        WHERE user_id = ? AND document_type = ?";
        $updateStmt = $db->prepare($updateQuery);
        $success = $updateStmt->execute([
            $filePath,
            $file['name'],
            $uploadResult['size'],
            $mimeType,
            $userId,
            $documentType
        ]);
    } else {
        // Insert new document
        $insertQuery = "INSERT INTO kyc_documents 
                        (user_id, document_type, file_path, file_name, file_size, mime_type, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $insertStmt = $db->prepare($insertQuery);
        $success = $insertStmt->execute([
            $userId,
            $documentType,
            $filePath,
            $file['name'],
            $uploadResult['size'],
            $mimeType
        ]);
    }

    if (!$success) {
        // Delete uploaded file if database insert failed
        $fullPath = dirname(dirname(__DIR__)) . '/' . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        sendErrorResponse('Failed to save document information', 500);
    }

    sendSuccessResponse([
        'document_type' => $documentType,
        'file_name' => $file['name'],
        'status' => 'pending'
    ], 'KYC document uploaded successfully');

} catch (Exception $e) {
    error_log('KYC upload error: ' . $e->getMessage());
    sendErrorResponse('Failed to upload document: ' . $e->getMessage(), 500);
}
?>
