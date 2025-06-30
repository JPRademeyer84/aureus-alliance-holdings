<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating test Enhanced KYC profile data...\n";
    
    // Get existing users
    $userQuery = "SELECT id, username, email FROM users LIMIT 3";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found. Please run create_test_kyc_documents.php first.\n";
        exit(1);
    }
    
    // Update user_profiles with enhanced KYC data
    $updateQuery = "UPDATE user_profiles SET 
        first_name = ?, last_name = ?, middle_name = ?, date_of_birth = ?, nationality = ?, gender = ?, place_of_birth = ?,
        phone = ?, whatsapp_number = ?, telegram_username = ?, twitter_handle = ?, instagram_handle = ?, linkedin_profile = ?, facebook_profile = ?,
        address_line_1 = ?, address_line_2 = ?, city = ?, state_province = ?, postal_code = ?, country = ?,
        id_type = ?, id_number = ?, id_expiry_date = ?,
        occupation = ?, employer = ?, annual_income = ?, source_of_funds = ?, purpose_of_account = ?,
        emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relationship = ?,
        personal_info_status = ?, contact_info_status = ?, address_info_status = ?, identity_info_status = ?, financial_info_status = ?, emergency_contact_status = ?
        WHERE user_id = ?";
    
    $updateStmt = $db->prepare($updateQuery);
    
    $testData = [
        [
            // Personal Info
            'John', 'Rademeyer', 'Paul', '1990-05-15', 'South African', 'Male', 'Cape Town',
            // Contact Info
            '+27123456789', '+27123456789', '@johnrad', '@johnrad_twitter', '@johnrad_insta', 'linkedin.com/in/johnrad', 'facebook.com/johnrad',
            // Address Info
            '123 Main Street', 'Apartment 4B', 'Cape Town', 'Western Cape', '8001', 'South Africa',
            // Identity Info
            'passport', 'ZA123456789', '2030-12-31',
            // Financial Info
            'Software Developer', 'Tech Company Ltd', '$50,000 - $75,000', 'Employment Salary', 'Investment and Trading',
            // Emergency Contact
            'Jane Rademeyer', '+27987654321', 'Spouse',
            // Status (mix of pending and approved)
            'pending', 'pending', 'pending', 'pending', 'pending', 'pending'
        ],
        [
            // Personal Info
            'Pieter', 'Smith', 'Johannes', '1985-08-22', 'South African', 'Male', 'Johannesburg',
            // Contact Info
            '+27111222333', '+27111222333', '@pietersmith', '@pieter_twitter', '@pieter_insta', 'linkedin.com/in/pietersmith', 'facebook.com/pietersmith',
            // Address Info
            '456 Oak Avenue', 'Unit 12', 'Johannesburg', 'Gauteng', '2000', 'South Africa',
            // Identity Info
            'drivers_license', 'DL987654321', '2028-06-30',
            // Financial Info
            'Business Owner', 'Smith Enterprises', '$75,000 - $100,000', 'Business Income', 'Investment Portfolio Growth',
            // Emergency Contact
            'Maria Smith', '+27444555666', 'Sister',
            // Status (some approved)
            'approved', 'approved', 'pending', 'pending', 'pending', 'pending'
        ],
        [
            // Personal Info
            'Test', 'User', 'Middle', '1992-03-10', 'American', 'Female', 'New York',
            // Contact Info
            '+1555123456', '+1555123456', '@testuser', '@test_twitter', '@test_insta', 'linkedin.com/in/testuser', 'facebook.com/testuser',
            // Address Info
            '789 Broadway', 'Floor 5', 'New York', 'New York', '10001', 'United States',
            // Identity Info
            'national_id', 'US123456789', '2029-09-15',
            // Financial Info
            'Marketing Manager', 'Global Corp', '$60,000 - $80,000', 'Employment', 'Long-term Investment',
            // Emergency Contact
            'Bob User', '+1555987654', 'Father',
            // Status (mix including some rejected)
            'approved', 'approved', 'approved', 'rejected', 'pending', 'pending'
        ]
    ];
    
    foreach ($users as $index => $user) {
        if (isset($testData[$index])) {
            $data = $testData[$index];
            $data[] = $user['id']; // Add user_id at the end
            
            $updateStmt->execute($data);
            echo "Updated enhanced KYC data for user: {$user['username']}\n";
        }
    }
    
    echo "\nSuccessfully created enhanced KYC profile data!\n";
    
    // Show summary
    $summaryQuery = "SELECT 
        personal_info_status, 
        COUNT(*) as count 
        FROM user_profiles 
        WHERE personal_info_status IS NOT NULL 
        GROUP BY personal_info_status";
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nEnhanced KYC Status Summary:\n";
    foreach ($summary as $row) {
        echo "- {$row['personal_info_status']}: {$row['count']} profiles\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
