<?php
// Add KYC verification columns to users table
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Add KYC columns to users table
    $alterUserTable = "
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS kyc_verified TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS kyc_verified_at TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS facial_verification_completed TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS facial_verification_at TIMESTAMP NULL
    ";
    
    try {
        $db->exec($alterUserTable);
        $userTableUpdated = true;
    } catch (PDOException $e) {
        // Columns might already exist
        $userTableUpdated = false;
        $userTableError = $e->getMessage();
    }

    // Create facial verification table
    $createFacialTable = "
        CREATE TABLE IF NOT EXISTS facial_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            captured_image_path VARCHAR(255) NOT NULL,
            confidence_score DECIMAL(5,4) NOT NULL,
            liveness_score DECIMAL(5,4) NOT NULL,
            verification_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
            comparison_result JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (verification_status)
        )
    ";
    $db->exec($createFacialTable);

    // Update KYC documents table to include facial verification reference
    $alterKycTable = "
        ALTER TABLE kyc_documents 
        ADD COLUMN IF NOT EXISTS facial_verification_id INT NULL,
        ADD FOREIGN KEY IF NOT EXISTS (facial_verification_id) REFERENCES facial_verifications(id)
    ";
    
    try {
        $db->exec($alterKycTable);
        $kycTableUpdated = true;
    } catch (PDOException $e) {
        $kycTableUpdated = false;
        $kycTableError = $e->getMessage();
    }

    sendSuccessResponse([
        'user_table_updated' => $userTableUpdated,
        'user_table_error' => $userTableError ?? null,
        'facial_verification_table_created' => true,
        'kyc_table_updated' => $kycTableUpdated,
        'kyc_table_error' => $kycTableError ?? null
    ], 'KYC database migration completed');

} catch (Exception $e) {
    error_log('KYC migration error: ' . $e->getMessage());
    sendErrorResponse('Migration failed: ' . $e->getMessage(), 500);
}
?>
