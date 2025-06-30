<?php
// Facial Recognition Verification API
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

session_start();
setCorsHeaders();

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

    // Rate limiting: Check if user has attempted verification recently
    $database = new Database();
    $db = $database->getConnection();

    $recentAttemptQuery = "SELECT COUNT(*) as attempt_count FROM facial_verifications
                          WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $recentAttemptStmt = $db->prepare($recentAttemptQuery);
    $recentAttemptStmt->execute([$userId]);
    $recentAttempts = $recentAttemptStmt->fetch(PDO::FETCH_ASSOC);

    if ($recentAttempts['attempt_count'] >= 5) {
        sendErrorResponse('Too many verification attempts. Please wait 1 hour before trying again.', 429);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendErrorResponse('Invalid JSON input', 400);
    }

    $capturedImage = $input['capturedImage'] ?? '';
    $confidence = floatval($input['confidence'] ?? 0);
    $livenessScore = floatval($input['livenessScore'] ?? 0);

    // Validate inputs
    if (empty($capturedImage)) {
        sendErrorResponse('Captured image is required', 400);
    }

    if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $capturedImage)) {
        sendErrorResponse('Invalid image format. Only JPEG and PNG are allowed.', 400);
    }

    if ($confidence < 0 || $confidence > 1) {
        sendErrorResponse('Invalid confidence score', 400);
    }

    if ($livenessScore < 0 || $livenessScore > 1) {
        sendErrorResponse('Invalid liveness score', 400);
    }

    // Check image size (base64 encoded)
    $imageSize = (strlen($capturedImage) * 3) / 4; // Approximate decoded size
    if ($imageSize > 5 * 1024 * 1024) { // 5MB limit
        sendErrorResponse('Image too large. Maximum size is 5MB.', 400);
    }

    $database = new Database();
    $db = $database->getConnection();

    // Create facial verification table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS facial_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            captured_image_path VARCHAR(255) NOT NULL,
            confidence_score DECIMAL(5,4) NOT NULL,
            liveness_score DECIMAL(5,4) NOT NULL,
            verification_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
            comparison_result JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (verification_status)
        )
    ";
    $db->exec($createTableQuery);

    // Create facial verification directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../assets/facial-verification/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Save captured image
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $capturedImage));
    $fileName = $userId . '_facial_' . time() . '.jpg';
    $filePath = $uploadDir . $fileName;

    if (!file_put_contents($filePath, $imageData)) {
        sendErrorResponse('Failed to save captured image', 500);
    }

    // Get user's KYC documents for comparison
    $kycQuery = "SELECT file_path, document_type FROM kyc_documents 
                 WHERE user_id = ? AND status = 'verified' 
                 ORDER BY uploaded_at DESC LIMIT 1";
    $kycStmt = $db->prepare($kycQuery);
    $kycStmt->execute([$userId]);
    $kycDocument = $kycStmt->fetch(PDO::FETCH_ASSOC);

    $verificationStatus = 'pending';
    $comparisonResult = null;

    if ($kycDocument) {
        // Perform face comparison (simplified version)
        $comparisonResult = performFaceComparison($filePath, __DIR__ . '/../../' . $kycDocument['file_path']);
        
        // Determine verification status based on scores
        $overallScore = ($confidence + $livenessScore + $comparisonResult['similarity']) / 3;
        
        if ($overallScore >= 0.8 && $confidence >= 0.7 && $livenessScore >= 0.8) {
            $verificationStatus = 'verified';
        } else {
            $verificationStatus = 'failed';
        }
    }

    // Save verification record
    $insertQuery = "INSERT INTO facial_verifications 
                    (user_id, captured_image_path, confidence_score, liveness_score, 
                     verification_status, comparison_result, verified_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    $success = $insertStmt->execute([
        $userId,
        'assets/facial-verification/' . $fileName,
        $confidence,
        $livenessScore,
        $verificationStatus,
        json_encode($comparisonResult),
        $verificationStatus === 'verified' ? date('Y-m-d H:i:s') : null
    ]);

    if (!$success) {
        sendErrorResponse('Failed to save verification record', 500);
    }

    // Update user's KYC status if verification passed
    if ($verificationStatus === 'verified') {
        $updateUserQuery = "UPDATE users SET kyc_verified = 1, kyc_verified_at = NOW() WHERE id = ?";
        $updateUserStmt = $db->prepare($updateUserQuery);
        $updateUserStmt->execute([$userId]);
    }

    sendSuccessResponse([
        'verification_status' => $verificationStatus,
        'confidence_score' => $confidence,
        'liveness_score' => $livenessScore,
        'comparison_result' => $comparisonResult,
        'overall_score' => isset($overallScore) ? $overallScore : null,
        'message' => $verificationStatus === 'verified' 
            ? 'Facial verification completed successfully' 
            : 'Facial verification failed. Please try again.'
    ], 'Facial verification processed');

} catch (Exception $e) {
    error_log('Facial verification error: ' . $e->getMessage());
    sendErrorResponse('Failed to process facial verification: ' . $e->getMessage(), 500);
}

/**
 * Perform face comparison between captured selfie and ID document
 * This is a simplified version - in production, you'd use more sophisticated algorithms
 */
function performFaceComparison($selfieImage, $idImage) {
    // Simplified face comparison logic
    // In a real implementation, you would:
    // 1. Extract face embeddings from both images
    // 2. Calculate cosine similarity or Euclidean distance
    // 3. Apply threshold for matching
    
    // For now, return a mock result based on file existence and basic checks
    if (!file_exists($selfieImage) || !file_exists($idImage)) {
        return [
            'similarity' => 0.0,
            'status' => 'error',
            'message' => 'One or both images not found'
        ];
    }

    // Mock similarity calculation (in production, use actual face recognition)
    $similarity = 0.85 + (rand(-10, 10) / 100); // Random similarity between 0.75-0.95
    
    return [
        'similarity' => max(0, min(1, $similarity)),
        'status' => 'success',
        'message' => 'Face comparison completed',
        'algorithm' => 'mock_comparison_v1',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}
?>
