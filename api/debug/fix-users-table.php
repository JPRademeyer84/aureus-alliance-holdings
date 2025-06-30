<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [];

    // First, let's check the current users table structure
    $describeQuery = "DESCRIBE users";
    $stmt = $db->prepare($describeQuery);
    $stmt->execute();
    $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results['current_columns'] = array_column($currentColumns, 'Field');
    
    // Check if kyc_status column exists
    $hasKycStatus = in_array('kyc_status', $results['current_columns']);
    $results['has_kyc_status'] = $hasKycStatus;
    
    if (!$hasKycStatus) {
        // Add the missing kyc_status column
        $addColumnQuery = "ALTER TABLE users ADD COLUMN kyc_status ENUM('not_verified', 'pending', 'verified', 'rejected') DEFAULT 'not_verified'";
        $db->exec($addColumnQuery);
        $results['kyc_status_added'] = 'Success';
    } else {
        $results['kyc_status_added'] = 'Already exists';
    }
    
    // Check if facial_verification_status column exists
    $hasFacialStatus = in_array('facial_verification_status', $results['current_columns']);
    $results['has_facial_verification_status'] = $hasFacialStatus;
    
    if (!$hasFacialStatus) {
        // Add the missing facial_verification_status column
        $addFacialStatusQuery = "ALTER TABLE users ADD COLUMN facial_verification_status ENUM('not_started', 'pending', 'verified', 'failed') DEFAULT 'not_started'";
        $db->exec($addFacialStatusQuery);
        $results['facial_verification_status_added'] = 'Success';
    } else {
        $results['facial_verification_status_added'] = 'Already exists';
    }
    
    // Get the updated table structure
    $stmt = $db->prepare($describeQuery);
    $stmt->execute();
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results['updated_columns'] = array_column($updatedColumns, 'Field');
    
    echo json_encode([
        'success' => true,
        'message' => 'Users table structure fixed',
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Users table fix error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fix table: ' . $e->getMessage()
    ]);
}
?>
