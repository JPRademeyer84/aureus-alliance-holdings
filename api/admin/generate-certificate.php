<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../services/CertificateGenerator.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method allowed");
    }

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['certificate_id'])) {
        throw new Exception("Certificate ID is required");
    }

    // Initialize certificate generator
    $generator = new CertificateGenerator($db);
    
    // Generate the certificate
    $result = $generator->generateCertificate($input['certificate_id']);
    
    // Log the generation activity
    logCertificateActivity($db, $input['certificate_id'], 'generated', $input['admin_id'] ?? null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Certificate generated successfully',
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function logCertificateActivity($db, $certificateId, $activity, $adminId = null) {
    try {
        $query = "INSERT INTO certificate_access_log (
            certificate_id, accessed_by, access_type, access_method, accessed_at
        ) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $certificateId,
            $adminId ?: 'system',
            $activity,
            'api'
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log certificate activity: " . $e->getMessage());
    }
}
?>
