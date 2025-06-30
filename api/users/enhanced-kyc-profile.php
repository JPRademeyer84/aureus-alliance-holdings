<?php
require_once '../config/database.php';

// Response utility functions
function sendSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

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

session_start();

// Using CORS functions from cors.php instead of local functions

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'get';

    // Check if user is logged in via session
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        sendErrorResponse('User not authenticated', 401);
    }

    switch ($action) {
        case 'get':
            handleGetProfile($db, $userId);
            break;

        case 'update_profile':
            handleUpdateProfile($db, $userId, $input);
            break;

        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Enhanced KYC Profile API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetProfile($db, $userId) {
    try {
        // Get user profile with all KYC fields
        $query = "SELECT 
            u.id, u.username, u.email, u.full_name, u.created_at,
            up.first_name, up.last_name, up.middle_name, up.date_of_birth, 
            up.nationality, up.gender, up.place_of_birth,
            up.phone, up.whatsapp_number, up.telegram_username, up.twitter_handle, 
            up.instagram_handle, up.linkedin_profile, up.facebook_profile,
            up.address_line_1, up.address_line_2, up.city, up.state_province, 
            up.postal_code, up.country,
            up.id_type, up.id_number, up.id_expiry_date,
            up.occupation, up.employer, up.annual_income, up.source_of_funds, 
            up.purpose_of_account,
            up.emergency_contact_name, up.emergency_contact_phone, 
            up.emergency_contact_relationship,
            up.personal_info_status, up.personal_info_rejection_reason,
            up.contact_info_status, up.contact_info_rejection_reason,
            up.address_info_status, up.address_info_rejection_reason,
            up.identity_info_status, up.identity_info_rejection_reason,
            up.financial_info_status, up.financial_info_rejection_reason,
            up.emergency_contact_status, up.emergency_contact_rejection_reason,
            up.kyc_status, up.profile_completion
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            sendErrorResponse('Profile not found', 404);
        }

        // Prepare approval status
        $approvalStatus = [
            'personal_info_status' => $profile['personal_info_status'] ?? 'pending',
            'contact_info_status' => $profile['contact_info_status'] ?? 'pending',
            'address_info_status' => $profile['address_info_status'] ?? 'pending',
            'identity_info_status' => $profile['identity_info_status'] ?? 'pending',
            'financial_info_status' => $profile['financial_info_status'] ?? 'pending',
            'emergency_contact_status' => $profile['emergency_contact_status'] ?? 'pending',
            'personal_info_rejection_reason' => $profile['personal_info_rejection_reason'],
            'contact_info_rejection_reason' => $profile['contact_info_rejection_reason'],
            'address_info_rejection_reason' => $profile['address_info_rejection_reason'],
            'identity_info_rejection_reason' => $profile['identity_info_rejection_reason'],
            'financial_info_rejection_reason' => $profile['financial_info_rejection_reason'],
            'emergency_contact_rejection_reason' => $profile['emergency_contact_rejection_reason']
        ];

        sendSuccessResponse([
            'profile' => $profile,
            'approval_status' => $approvalStatus
        ], 'Profile retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve profile: ' . $e->getMessage(), 500);
    }
}

function handleUpdateProfile($db, $userId, $input) {
    try {
        // Check if profile exists
        $checkQuery = "SELECT id FROM user_profiles WHERE user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$userId]);
        $exists = $checkStmt->fetch();

        $profileData = [
            // Personal Information
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'middle_name' => $input['middle_name'] ?? null,
            'date_of_birth' => $input['date_of_birth'] ?? null,
            'nationality' => $input['nationality'] ?? null,
            'gender' => $input['gender'] ?? null,
            'place_of_birth' => $input['place_of_birth'] ?? null,
            
            // Contact Information
            'phone' => $input['phone'] ?? null,
            'whatsapp_number' => $input['whatsapp_number'] ?? null,
            'telegram_username' => $input['telegram_username'] ?? null,
            'twitter_handle' => $input['twitter_handle'] ?? null,
            'instagram_handle' => $input['instagram_handle'] ?? null,
            'linkedin_profile' => $input['linkedin_profile'] ?? null,
            'facebook_profile' => $input['facebook_profile'] ?? null,
            
            // Address Information
            'address_line_1' => $input['address_line_1'] ?? null,
            'address_line_2' => $input['address_line_2'] ?? null,
            'city' => $input['city'] ?? null,
            'state_province' => $input['state_province'] ?? null,
            'postal_code' => $input['postal_code'] ?? null,
            'country' => $input['country'] ?? null,
            
            // Identity Information
            'id_type' => $input['id_type'] ?? null,
            'id_number' => $input['id_number'] ?? null,
            'id_expiry_date' => $input['id_expiry_date'] ?? null,
            
            // Financial Information
            'occupation' => $input['occupation'] ?? null,
            'employer' => $input['employer'] ?? null,
            'annual_income' => $input['annual_income'] ?? null,
            'source_of_funds' => $input['source_of_funds'] ?? null,
            'purpose_of_account' => $input['purpose_of_account'] ?? null,
            
            // Emergency Contact
            'emergency_contact_name' => $input['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $input['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $input['emergency_contact_relationship'] ?? null,
            
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($exists) {
            // Update existing profile
            $updateFields = [];
            $updateValues = [];
            
            foreach ($profileData as $field => $value) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
            
            $updateValues[] = $userId;
            
            $updateQuery = "UPDATE user_profiles SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $db->prepare($updateQuery);
            $success = $stmt->execute($updateValues);
        } else {
            // Create new profile
            $profileData['user_id'] = $userId;
            $profileData['created_at'] = date('Y-m-d H:i:s');
            
            $fields = implode(', ', array_keys($profileData));
            $placeholders = implode(', ', array_fill(0, count($profileData), '?'));
            
            $insertQuery = "INSERT INTO user_profiles ($fields) VALUES ($placeholders)";
            $stmt = $db->prepare($insertQuery);
            $success = $stmt->execute(array_values($profileData));
        }

        if ($success) {
            sendSuccessResponse(['updated' => true], 'Profile updated successfully');
        } else {
            sendErrorResponse('Failed to update profile', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to update profile: ' . $e->getMessage(), 500);
    }
}

?>
