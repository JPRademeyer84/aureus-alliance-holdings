<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../utils/response.php';

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    sendErrorResponse('Unauthorized access', 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    
    if (!$userId) {
        sendErrorResponse('User ID is required', 400);
    }
    
    $syncResults = [];
    
    // Get user's current KYC profile status
    $profileQuery = "SELECT 
        personal_info_status,
        contact_info_status,
        address_info_status,
        identity_info_status,
        financial_info_status,
        emergency_contact_status,
        kyc_status,
        profile_completion
        FROM user_profiles WHERE user_id = ?";
    $profileStmt = $db->prepare($profileQuery);
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        sendErrorResponse('User profile not found', 404);
    }
    
    // 1. Sync email verification based on contact_info_status or kyc_status
    if ($profile['contact_info_status'] === 'approved' || $profile['kyc_status'] === 'verified') {
        $emailQuery = "UPDATE users SET email_verified = 1 WHERE id = ? AND email_verified = 0";
        $emailStmt = $db->prepare($emailQuery);
        $emailResult = $emailStmt->execute([$userId]);
        $syncResults['email_verification_updated'] = $emailStmt->rowCount() > 0;
    }
    
    // 2. Calculate and update profile completion
    $sections = [
        'personal_info_status',
        'contact_info_status',
        'address_info_status',
        'identity_info_status',
        'financial_info_status',
        'emergency_contact_status'
    ];
    
    $approvedCount = 0;
    foreach ($sections as $sectionStatus) {
        if ($profile[$sectionStatus] === 'approved') {
            $approvedCount++;
        }
    }
    
    $calculatedCompletion = round(($approvedCount / count($sections)) * 100);
    
    // If KYC is fully verified, set completion to 100%
    if ($profile['kyc_status'] === 'verified') {
        $calculatedCompletion = 100;
    }
    
    if ($calculatedCompletion !== (int)$profile['profile_completion']) {
        $profileQuery = "UPDATE user_profiles SET profile_completion = ? WHERE user_id = ?";
        $profileStmt = $db->prepare($profileQuery);
        $profileResult = $profileStmt->execute([$calculatedCompletion, $userId]);
        $syncResults['profile_completion_updated'] = $profileStmt->rowCount() > 0;
        $syncResults['new_completion_percentage'] = $calculatedCompletion;
    } else {
        $syncResults['profile_completion_updated'] = false;
        $syncResults['new_completion_percentage'] = $calculatedCompletion;
    }
    
    // 3. Check and update overall KYC status if needed
    $coreApproved = (
        $profile['personal_info_status'] === 'approved' &&
        $profile['contact_info_status'] === 'approved' &&
        $profile['identity_info_status'] === 'approved'
    );
    
    if ($coreApproved && $profile['kyc_status'] !== 'verified') {
        $kycQuery = "UPDATE user_profiles SET 
            kyc_status = 'verified',
            kyc_verified_at = NOW(),
            profile_completion = 100
            WHERE user_id = ?";
        $kycStmt = $db->prepare($kycQuery);
        $kycResult = $kycStmt->execute([$userId]);
        $syncResults['kyc_status_updated'] = $kycStmt->rowCount() > 0;
        
        // Also ensure email is verified
        $emailQuery = "UPDATE users SET email_verified = 1 WHERE id = ?";
        $emailStmt = $db->prepare($emailQuery);
        $emailStmt->execute([$userId]);
    } else {
        $syncResults['kyc_status_updated'] = false;
    }
    
    // Get updated user data
    $userQuery = "SELECT 
        u.id, u.username, u.email, u.email_verified,
        up.kyc_status, up.kyc_verified_at, up.profile_completion,
        up.personal_info_status, up.contact_info_status, up.identity_info_status
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$userId]);
    $updatedUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccessResponse([
        'sync_results' => $syncResults,
        'updated_user' => $updatedUser
    ], 'User status synchronized successfully');
    
} catch (Exception $e) {
    error_log('Sync user status error: ' . $e->getMessage());
    sendErrorResponse('Failed to sync user status: ' . $e->getMessage(), 500);
}
?>
