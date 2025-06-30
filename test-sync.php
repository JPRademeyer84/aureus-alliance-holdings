<?php
// Test script to verify sync functionality
require_once 'api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Find a user with KYC approved but email not verified
    $query = "SELECT 
        u.id, u.username, u.email, u.email_verified,
        up.kyc_status, up.contact_info_status, up.personal_info_status, up.identity_info_status
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.role = 'user'
        AND (up.kyc_status = 'verified' OR up.contact_info_status = 'approved')
        AND u.email_verified = 0
        LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users with KYC approved but email not verified:\n";
    echo "=================================================\n";
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Email: {$user['email']}\n";
        echo "Email Verified: " . ($user['email_verified'] ? 'Yes' : 'No') . "\n";
        echo "KYC Status: {$user['kyc_status']}\n";
        echo "Contact Info Status: {$user['contact_info_status']}\n";
        echo "Personal Info Status: {$user['personal_info_status']}\n";
        echo "Identity Info Status: {$user['identity_info_status']}\n";
        echo "---\n";
    }
    
    if (empty($users)) {
        echo "No users found with this issue.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
