<?php
// KYC Documents Retrieval API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendErrorResponse('Method not allowed', 405);
    }

    $userId = $_SESSION['user_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Get user's KYC documents
    $documentsQuery = "SELECT 
                        id,
                        document_type,
                        file_name,
                        file_size,
                        mime_type,
                        status,
                        uploaded_at,
                        verified_at,
                        rejection_reason
                       FROM kyc_documents 
                       WHERE user_id = ? 
                       ORDER BY uploaded_at DESC";
    $documentsStmt = $db->prepare($documentsQuery);
    $documentsStmt->execute([$userId]);
    $documents = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine overall KYC status
    $kycStatus = 'not_verified';
    if (!empty($documents)) {
        $hasVerified = false;
        $hasPending = false;
        
        foreach ($documents as $doc) {
            if ($doc['status'] === 'verified') {
                $hasVerified = true;
                break;
            } elseif ($doc['status'] === 'pending') {
                $hasPending = true;
            }
        }
        
        if ($hasVerified) {
            $kycStatus = 'verified';
        } elseif ($hasPending) {
            $kycStatus = 'pending';
        }
    }

    sendSuccessResponse([
        'documents' => $documents,
        'status' => $kycStatus,
        'total_documents' => count($documents)
    ], 'KYC documents retrieved successfully');

} catch (Exception $e) {
    error_log('KYC documents retrieval error: ' . $e->getMessage());
    sendErrorResponse('Failed to retrieve KYC documents: ' . $e->getMessage(), 500);
}
?>
