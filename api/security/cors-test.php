<?php
/**
 * CORS SECURITY TEST ENDPOINT
 * Tests the secure CORS implementation
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Only allow GET and POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? 'No origin header';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Get allowed origins for comparison
    $allowedOrigins = SecureCORS::getAllowedOrigins();
    
    $response = [
        'success' => true,
        'message' => 'CORS security test successful',
        'security_info' => [
            'origin_received' => $origin,
            'origin_validated' => SecureCORS::validateOrigin($origin),
            'allowed_origins' => $allowedOrigins,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'user_agent' => substr($userAgent, 0, 100), // Truncate for security
            'ip_address' => $ip,
            'timestamp' => date('c'),
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'session_id' => session_id()
        ],
        'headers_sent' => [
            'access_control_allow_origin' => $origin,
            'access_control_allow_credentials' => 'true',
            'x_content_type_options' => 'nosniff',
            'x_frame_options' => 'DENY',
            'x_xss_protection' => '1; mode=block'
        ]
    ];
    
    // Log the test
    error_log("CORS Security Test: Origin=$origin, Valid=" . (SecureCORS::validateOrigin($origin) ? 'true' : 'false') . ", IP=$ip");
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("CORS test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
