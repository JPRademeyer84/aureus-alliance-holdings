<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/input-validator.php';

session_start();
setCorsHeaders();

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Validate POST data using centralized validation
    $validatedData = validateApiRequest([
        'type' => [
            'type' => 'string',
            'required' => true,
            'custom' => function($value) {
                $allowedTypes = ['drivers_license', 'national_id', 'passport', 'proof_of_address'];
                return in_array($value, $allowedTypes) ? true : 'Invalid document type';
            }
        ]
    ], 'kyc_upload');

    $documentType = $validatedData['type'];

    // Validate file upload using centralized validation
    $validatedFiles = validateApiFiles(ValidationRules::kycFileUpload());
    $file = $validatedFiles['document'];

    $database = new Database();
    $pdo = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Check if user already has a document of this type
    $stmt = $pdo->prepare("
        SELECT id FROM kyc_documents
        WHERE user_id = ? AND type = ?
    ");
    $stmt->execute([$userId, $documentType]);
    $existingDoc = $stmt->fetch();

    if ($existingDoc) {
        // Delete the existing document file and record
        $stmt = $pdo->prepare("
            SELECT file_path FROM kyc_documents
            WHERE id = ?
        ");
        $stmt->execute([$existingDoc['id']]);
        $oldDoc = $stmt->fetch();

        if ($oldDoc && file_exists("../../assets/kyc/" . $oldDoc['file_path'])) {
            unlink("../../assets/kyc/" . $oldDoc['file_path']);
        }

        $stmt = $pdo->prepare("DELETE FROM kyc_documents WHERE id = ?");
        $stmt->execute([$existingDoc['id']]);
    }

    // Create assets/kyc directory if it doesn't exist
    $uploadDir = "../../assets/kyc/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $userId . '_' . $documentType . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }

    // Save document record to database
    $stmt = $pdo->prepare("
        INSERT INTO kyc_documents (user_id, type, filename, file_path, status, uploaded_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $documentType, $file['name'], $filename]);

    // Update user's KYC status to pending if not already verified
    $stmt = $pdo->prepare("
        UPDATE users
        SET kyc_status = CASE
            WHEN kyc_status = 'verified' THEN 'verified'
            ELSE 'pending'
        END
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Document uploaded successfully',
        'document_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("KYC Upload Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
