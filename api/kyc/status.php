<?php
// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple response function
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

// Mock user session for testing
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'test-user-123'; // Mock user for testing
}

// Return mock KYC status data
sendResponse([
    'success' => true,
    'message' => 'KYC status retrieved successfully',
    'kyc_status' => 'pending',
    'documents' => [],
    'facial_verification_status' => 'not_started',
    'verification_level' => 1,
    'can_upload' => true,
    'required_documents' => [
        'passport',
        'national_id',
        'drivers_license'
    ]
]);
?>
