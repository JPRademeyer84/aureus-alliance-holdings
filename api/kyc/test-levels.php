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

// Test response
$action = $_GET['action'] ?? 'test';

switch ($action) {
    case 'get_levels':
        sendResponse([
            'success' => true,
            'message' => 'KYC levels retrieved successfully',
            'data' => [
                'levels' => [
                    [
                        'id' => 1,
                        'level_number' => 1,
                        'name' => 'Basic Verification',
                        'description' => 'Email and phone verification',
                        'badge_color' => 'blue',
                        'badge_icon' => 'shield',
                        'requirements' => [
                            [
                                'id' => 1,
                                'type' => 'email_verification',
                                'name' => 'Email Verification',
                                'description' => 'Verify your email address',
                                'is_mandatory' => true
                            ]
                        ],
                        'benefits' => [
                            [
                                'id' => 1,
                                'type' => 'investment_limit',
                                'name' => 'Investment Limit',
                                'value' => '1000',
                                'description' => 'Up to $1,000 investment'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        break;

    case 'get_user_level':
        sendResponse([
            'success' => true,
            'message' => 'User level retrieved successfully',
            'data' => [
                'user_level' => [
                    'current_level' => 1,
                    'level_1_completed_at' => null,
                    'level_2_completed_at' => null,
                    'level_3_completed_at' => null
                ]
            ]
        ]);
        break;

    case 'get_progress':
        sendResponse([
            'success' => true,
            'message' => 'Progress retrieved successfully',
            'data' => [
                'progress' => [
                    'level_1' => [
                        'requirements' => [],
                        'progress' => 0,
                        'completed_count' => 0,
                        'total_count' => 1,
                        'can_upgrade' => false
                    ],
                    'level_2' => [
                        'requirements' => [],
                        'progress' => 0,
                        'completed_count' => 0,
                        'total_count' => 1,
                        'can_upgrade' => false
                    ],
                    'level_3' => [
                        'requirements' => [],
                        'progress' => 0,
                        'completed_count' => 0,
                        'total_count' => 1,
                        'can_upgrade' => false
                    ]
                ]
            ]
        ]);
        break;

    default:
        sendResponse([
            'success' => true,
            'message' => 'KYC API test successful',
            'data' => ['test' => true]
        ]);
}
?>
