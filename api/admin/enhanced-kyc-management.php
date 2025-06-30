<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

// Using CORS functions from cors.php instead of local functions

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    sendErrorResponse('Admin authentication required', 401);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'get_users';

    switch ($action) {
        case 'get_users':
            handleGetUsers($db);
            break;

        case 'approve_section':
            handleApproveSection($db, $input);
            break;

        case 'reject_section':
            handleRejectSection($db, $input);
            break;

        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Enhanced KYC Management API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetUsers($db) {
    try {
        // Get all users with their KYC profile information
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
            WHERE u.role = 'user'
            ORDER BY u.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set default status for users without profiles
        foreach ($users as &$user) {
            $user['personal_info_status'] = $user['personal_info_status'] ?? 'pending';
            $user['contact_info_status'] = $user['contact_info_status'] ?? 'pending';
            $user['address_info_status'] = $user['address_info_status'] ?? 'pending';
            $user['identity_info_status'] = $user['identity_info_status'] ?? 'pending';
            $user['financial_info_status'] = $user['financial_info_status'] ?? 'pending';
            $user['emergency_contact_status'] = $user['emergency_contact_status'] ?? 'pending';
        }

        sendSuccessResponse(['users' => $users], 'Users retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve users: ' . $e->getMessage(), 500);
    }
}

function handleApproveSection($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $section = $input['section'] ?? null;
        $notes = $input['notes'] ?? null;
        $adminId = $_SESSION['admin_id'];

        if (!$userId || !$section) {
            sendErrorResponse('User ID and section are required', 400);
        }

        // Validate section
        $validSections = ['personal_info', 'contact_info', 'address_info', 'identity_info', 'financial_info', 'emergency_contact'];
        if (!in_array($section, $validSections)) {
            sendErrorResponse('Invalid section', 400);
        }

        // Check if user profile exists
        $checkQuery = "SELECT id FROM user_profiles WHERE user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$userId]);
        $exists = $checkStmt->fetch();

        if (!$exists) {
            sendErrorResponse('User profile not found', 404);
        }

        // Update the section status
        $updateQuery = "UPDATE user_profiles SET 
            {$section}_status = 'approved',
            {$section}_approved_by = ?,
            {$section}_approved_at = NOW(),
            {$section}_rejection_reason = NULL
            WHERE user_id = ?";
        
        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute([$adminId, $userId]);

        if ($success) {
            // Log the approval in history
            logKYCAction($db, $userId, $section, 'pending', 'approved', $adminId, $notes);
            
            sendSuccessResponse(['approved' => true], 'Section approved successfully');
        } else {
            sendErrorResponse('Failed to approve section', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to approve section: ' . $e->getMessage(), 500);
    }
}

function handleRejectSection($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $section = $input['section'] ?? null;
        $reason = $input['reason'] ?? null;
        $adminId = $_SESSION['admin_id'];

        if (!$userId || !$section || !$reason) {
            sendErrorResponse('User ID, section, and rejection reason are required', 400);
        }

        // Validate section
        $validSections = ['personal_info', 'contact_info', 'address_info', 'identity_info', 'financial_info', 'emergency_contact'];
        if (!in_array($section, $validSections)) {
            sendErrorResponse('Invalid section', 400);
        }

        // Check if user profile exists
        $checkQuery = "SELECT id FROM user_profiles WHERE user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$userId]);
        $exists = $checkStmt->fetch();

        if (!$exists) {
            sendErrorResponse('User profile not found', 404);
        }

        // Update the section status
        $updateQuery = "UPDATE user_profiles SET 
            {$section}_status = 'rejected',
            {$section}_approved_by = ?,
            {$section}_approved_at = NOW(),
            {$section}_rejection_reason = ?
            WHERE user_id = ?";
        
        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute([$adminId, $reason, $userId]);

        if ($success) {
            // Log the rejection in history
            logKYCAction($db, $userId, $section, 'pending', 'rejected', $adminId, null, $reason);
            
            sendSuccessResponse(['rejected' => true], 'Section rejected successfully');
        } else {
            sendErrorResponse('Failed to reject section', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to reject section: ' . $e->getMessage(), 500);
    }
}

function logKYCAction($db, $userId, $section, $oldStatus, $newStatus, $adminId, $notes = null, $rejectionReason = null) {
    try {
        $query = "INSERT INTO kyc_verification_history 
            (user_id, section_type, old_status, new_status, approved_by, admin_notes, rejection_reason, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$userId, $section, $oldStatus, $newStatus, $adminId, $notes, $rejectionReason]);
    } catch (Exception $e) {
        error_log("Failed to log KYC action: " . $e->getMessage());
    }
}

?>
