<?php
// Get Facial Verification Status API
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

    // Get user's facial verification status
    $verificationQuery = "SELECT 
                            fv.verification_status,
                            fv.confidence_score,
                            fv.liveness_score,
                            fv.created_at,
                            fv.verified_at,
                            u.kyc_verified,
                            u.facial_verification_completed
                          FROM facial_verifications fv
                          JOIN users u ON fv.user_id = u.id
                          WHERE fv.user_id = ? 
                          ORDER BY fv.created_at DESC 
                          LIMIT 1";
    $verificationStmt = $db->prepare($verificationQuery);
    $verificationStmt->execute([$userId]);
    $verification = $verificationStmt->fetch(PDO::FETCH_ASSOC);

    // Get KYC document count
    $kycCountQuery = "SELECT COUNT(*) as document_count FROM kyc_documents WHERE user_id = ? AND status = 'verified'";
    $kycCountStmt = $db->prepare($kycCountQuery);
    $kycCountStmt->execute([$userId]);
    $kycCount = $kycCountStmt->fetch(PDO::FETCH_ASSOC);

    $status = 'not_started';
    $canStartVerification = false;
    $requirements = [];

    if ($verification) {
        $status = $verification['verification_status'];
    }

    // Check requirements
    if ($kycCount['document_count'] > 0) {
        $canStartVerification = true;
        $requirements[] = '✓ Identity document uploaded and verified';
    } else {
        $requirements[] = '✗ Upload and verify an identity document first';
    }

    if ($verification && $verification['verification_status'] === 'verified') {
        $requirements[] = '✓ Facial verification completed';
    } else {
        $requirements[] = '✗ Complete facial verification';
    }

    sendSuccessResponse([
        'status' => $status,
        'can_start_verification' => $canStartVerification,
        'requirements' => $requirements,
        'verification_details' => $verification,
        'kyc_document_count' => $kycCount['document_count']
    ], 'Facial verification status retrieved');

} catch (Exception $e) {
    error_log('Facial verification status error: ' . $e->getMessage());
    sendErrorResponse('Failed to get verification status: ' . $e->getMessage(), 500);
}
?>
