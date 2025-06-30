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

session_start();
setCorsHeaders();

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $userId = $_SESSION['user_id'];

    // Check if user's KYC is already admin-approved (prevent restart if approved)
    $stmt = $pdo->prepare("SELECT kyc_status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user['kyc_status'] === 'verified') {
        echo json_encode([
            'success' => false,
            'error' => 'Cannot restart verification - KYC already approved by admin'
        ]);
        exit;
    }

    // Check if user has already restarted verification (allow only one restart)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as restart_count 
        FROM facial_verifications 
        WHERE user_id = ? AND verification_status IN ('verified', 'failed', 'pending')
    ");
    $stmt->execute([$userId]);
    $restartData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($restartData['restart_count'] > 1) {
        echo json_encode([
            'success' => false,
            'error' => 'Maximum restart attempts reached. Please contact support.'
        ]);
        exit;
    }

    // Reset user's facial verification status to allow new attempt
    $stmt = $pdo->prepare("
        UPDATE users 
        SET facial_verification_status = 'not_started' 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Facial verification reset successfully. You can now start a new verification.'
    ]);

} catch (Exception $e) {
    error_log('Restart Facial Verification Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
