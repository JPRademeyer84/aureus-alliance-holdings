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

    // First, let's check the current table structure
    $describeQuery = "DESCRIBE kyc_documents";
    $stmt = $db->prepare($describeQuery);
    $stmt->execute();
    $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results['current_columns'] = array_column($currentColumns, 'Field');
    
    // Check if uploaded_at column exists
    $hasUploadedAt = in_array('uploaded_at', $results['current_columns']);
    $results['has_uploaded_at'] = $hasUploadedAt;
    
    if (!$hasUploadedAt) {
        // Add the missing uploaded_at column
        $addColumnQuery = "ALTER TABLE kyc_documents ADD COLUMN uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $db->exec($addColumnQuery);
        $results['uploaded_at_added'] = 'Success';
    } else {
        $results['uploaded_at_added'] = 'Already exists';
    }
    
    // Check if verified_at column exists
    $hasVerifiedAt = in_array('verified_at', $results['current_columns']);
    $results['has_verified_at'] = $hasVerifiedAt;
    
    if (!$hasVerifiedAt) {
        // Add the missing verified_at column
        $addVerifiedAtQuery = "ALTER TABLE kyc_documents ADD COLUMN verified_at TIMESTAMP NULL";
        $db->exec($addVerifiedAtQuery);
        $results['verified_at_added'] = 'Success';
    } else {
        $results['verified_at_added'] = 'Already exists';
    }
    
    // Check if verified_by column exists
    $hasVerifiedBy = in_array('verified_by', $results['current_columns']);
    $results['has_verified_by'] = $hasVerifiedBy;
    
    if (!$hasVerifiedBy) {
        // Add the missing verified_by column
        $addVerifiedByQuery = "ALTER TABLE kyc_documents ADD COLUMN verified_by INT NULL";
        $db->exec($addVerifiedByQuery);
        $results['verified_by_added'] = 'Success';
    } else {
        $results['verified_by_added'] = 'Already exists';
    }
    
    // Check if rejection_reason column exists
    $hasRejectionReason = in_array('rejection_reason', $results['current_columns']);
    $results['has_rejection_reason'] = $hasRejectionReason;
    
    if (!$hasRejectionReason) {
        // Add the missing rejection_reason column
        $addRejectionReasonQuery = "ALTER TABLE kyc_documents ADD COLUMN rejection_reason TEXT NULL";
        $db->exec($addRejectionReasonQuery);
        $results['rejection_reason_added'] = 'Success';
    } else {
        $results['rejection_reason_added'] = 'Already exists';
    }
    
    // Get the updated table structure
    $stmt = $db->prepare($describeQuery);
    $stmt->execute();
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results['updated_columns'] = array_column($updatedColumns, 'Field');
    
    echo json_encode([
        'success' => true,
        'message' => 'KYC table structure fixed',
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('KYC table fix error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fix table: ' . $e->getMessage()
    ]);
}
?>
