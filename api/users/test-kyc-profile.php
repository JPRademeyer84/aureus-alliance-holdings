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

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        // Return mock KYC profile data
        sendResponse([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => [
                'profile' => [
                    'first_name' => '',
                    'last_name' => '',
                    'middle_name' => '',
                    'date_of_birth' => '',
                    'nationality' => '',
                    'gender' => '',
                    'place_of_birth' => '',
                    'phone' => '',
                    'whatsapp_number' => '',
                    'telegram_username' => '',
                    'twitter_handle' => '',
                    'instagram_handle' => '',
                    'linkedin_profile' => '',
                    'facebook_profile' => '',
                    'address_line_1' => '',
                    'address_line_2' => '',
                    'city' => '',
                    'state_province' => '',
                    'postal_code' => '',
                    'country' => '',
                    'id_type' => '',
                    'id_number' => '',
                    'id_expiry_date' => '',
                    'occupation' => '',
                    'employer' => '',
                    'annual_income' => '',
                    'source_of_funds' => '',
                    'purpose_of_account' => '',
                    'emergency_contact_name' => '',
                    'emergency_contact_phone' => '',
                    'emergency_contact_relationship' => ''
                ],
                'approval_status' => [
                    'personal_info_status' => 'pending',
                    'contact_info_status' => 'pending',
                    'address_info_status' => 'pending',
                    'identity_info_status' => 'pending',
                    'financial_info_status' => 'pending',
                    'emergency_contact_status' => 'pending',
                    'personal_info_rejection_reason' => '',
                    'contact_info_rejection_reason' => '',
                    'address_info_rejection_reason' => '',
                    'identity_info_rejection_reason' => '',
                    'financial_info_rejection_reason' => '',
                    'emergency_contact_rejection_reason' => ''
                ]
            ]
        ]);
        break;
        
    case 'update_profile':
        // Mock successful update
        sendResponse([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => []
        ]);
        break;
        
    default:
        sendResponse([
            'success' => false,
            'message' => 'Invalid action'
        ], 400);
}
?>
