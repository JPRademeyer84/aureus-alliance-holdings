<?php
// Sync KYC Status - Admin Tool
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

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $syncResults = [];
    
    // 1. Sync email verification status for contact_info approved users
    $emailQuery = "
        UPDATE users u
        JOIN user_profiles up ON u.id = up.user_id
        SET u.email_verified = 1
        WHERE up.contact_info_status = 'approved' AND u.email_verified = 0
    ";
    $emailResult = $db->exec($emailQuery);
    $syncResults['email_verification_synced'] = $emailResult;

    // 1b. Also sync email verification for fully KYC verified users
    $emailKycQuery = "
        UPDATE users u
        JOIN user_profiles up ON u.id = up.user_id
        SET u.email_verified = 1
        WHERE up.kyc_status = 'verified' AND u.email_verified = 0
    ";
    $emailKycResult = $db->exec($emailKycQuery);
    $syncResults['email_verification_kyc_synced'] = $emailKycResult;
    
    // 2. Update profile completion for users with approved personal info
    $profileQuery = "
        UPDATE user_profiles
        SET profile_completion = 100
        WHERE personal_info_status = 'approved' AND profile_completion < 100
    ";
    $profileResult = $db->exec($profileQuery);
    $syncResults['profile_completion_synced'] = $profileResult;

    // 2b. Update profile completion to 100% for fully KYC verified users
    $profileKycQuery = "
        UPDATE user_profiles
        SET profile_completion = 100
        WHERE kyc_status = 'verified' AND profile_completion < 100
    ";
    $profileKycResult = $db->exec($profileKycQuery);
    $syncResults['profile_completion_kyc_synced'] = $profileKycResult;
    
    // 3. Update overall KYC status for users with core sections approved
    $kycQuery = "
        UPDATE user_profiles 
        SET kyc_status = 'verified', kyc_verified_at = NOW()
        WHERE personal_info_status = 'approved' 
        AND contact_info_status = 'approved' 
        AND identity_info_status = 'approved'
        AND kyc_status != 'verified'
    ";
    $kycResult = $db->exec($kycQuery);
    $syncResults['kyc_status_synced'] = $kycResult;
    
    // 4. Get summary of current status
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN u.email_verified = 1 THEN 1 ELSE 0 END) as email_verified_count,
            SUM(CASE WHEN up.contact_info_status = 'approved' THEN 1 ELSE 0 END) as contact_approved_count,
            SUM(CASE WHEN up.personal_info_status = 'approved' THEN 1 ELSE 0 END) as personal_approved_count,
            SUM(CASE WHEN up.identity_info_status = 'approved' THEN 1 ELSE 0 END) as identity_approved_count,
            SUM(CASE WHEN up.kyc_status = 'verified' THEN 1 ELSE 0 END) as kyc_verified_count
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.role = 'user'
    ";
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'KYC status synchronization completed',
        'sync_results' => $syncResults,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Sync failed: ' . $e->getMessage()
    ]);
}
?>
