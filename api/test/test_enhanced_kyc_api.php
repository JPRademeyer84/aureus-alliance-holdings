<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Testing Enhanced KYC Management API...\n\n";
    
    // Simulate the API call
    echo "1. Testing get_users action:\n";
    
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

    echo "Found " . count($users) . " users:\n";
    
    foreach ($users as $user) {
        echo "\n--- User: {$user['username']} ---\n";
        echo "ID: {$user['id']}\n";
        echo "Email: {$user['email']}\n";
        echo "Full Name: " . ($user['full_name'] ?: 'Not set') . "\n";
        echo "First Name: " . ($user['first_name'] ?: 'Not set') . "\n";
        echo "Personal Info Status: " . ($user['personal_info_status'] ?: 'NULL') . "\n";
        echo "Contact Info Status: " . ($user['contact_info_status'] ?: 'NULL') . "\n";
        echo "Address Info Status: " . ($user['address_info_status'] ?: 'NULL') . "\n";
        echo "Identity Info Status: " . ($user['identity_info_status'] ?: 'NULL') . "\n";
        echo "Financial Info Status: " . ($user['financial_info_status'] ?: 'NULL') . "\n";
        echo "Emergency Contact Status: " . ($user['emergency_contact_status'] ?: 'NULL') . "\n";
    }
    
    // Set default status for users without profiles
    foreach ($users as &$user) {
        $user['personal_info_status'] = $user['personal_info_status'] ?? 'pending';
        $user['contact_info_status'] = $user['contact_info_status'] ?? 'pending';
        $user['address_info_status'] = $user['address_info_status'] ?? 'pending';
        $user['identity_info_status'] = $user['identity_info_status'] ?? 'pending';
        $user['financial_info_status'] = $user['financial_info_status'] ?? 'pending';
        $user['emergency_contact_status'] = $user['emergency_contact_status'] ?? 'pending';
    }
    
    echo "\n\n2. After setting defaults:\n";
    foreach ($users as $user) {
        echo "User {$user['username']}: Personal={$user['personal_info_status']}, Contact={$user['contact_info_status']}, Address={$user['address_info_status']}\n";
    }
    
    echo "\n3. API Response would be:\n";
    $response = [
        'success' => true,
        'message' => 'Users retrieved successfully',
        'data' => ['users' => $users]
    ];
    
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    echo "User count: " . count($response['data']['users']) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
