<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding approval tracking columns to user_profiles table...\n";
    
    // Add approval tracking columns
    $alterQueries = [
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS personal_info_approved_by VARCHAR(36)",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS personal_info_approved_at TIMESTAMP NULL",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS contact_info_approved_by VARCHAR(36)",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS contact_info_approved_at TIMESTAMP NULL",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS address_info_approved_by VARCHAR(36)",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS address_info_approved_at TIMESTAMP NULL",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS identity_info_approved_by VARCHAR(36)",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS identity_info_approved_at TIMESTAMP NULL",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS financial_info_approved_by VARCHAR(36)",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS financial_info_approved_at TIMESTAMP NULL",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS emergency_contact_approved_by VARCHAR(36)",
        "ALTER TABLE user_profiles ADD COLUMN IF NOT EXISTS emergency_contact_approved_at TIMESTAMP NULL"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "✓ Executed: " . substr($query, 0, 80) . "...\n";
        } catch (Exception $e) {
            echo "✗ Failed: " . substr($query, 0, 80) . "... Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nApproval tracking columns added successfully!\n";
    
    // Test the Enhanced KYC Management API
    echo "\nTesting Enhanced KYC Management API...\n";
    
    // Check if we have users with KYC data
    $testQuery = "SELECT 
        u.id, u.username, 
        up.personal_info_status, up.contact_info_status, up.address_info_status
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE u.role = 'user' 
        LIMIT 3";
    
    $stmt = $db->prepare($testQuery);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users:\n";
    foreach ($users as $user) {
        echo "- {$user['username']}: Personal={$user['personal_info_status']}, Contact={$user['contact_info_status']}, Address={$user['address_info_status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
