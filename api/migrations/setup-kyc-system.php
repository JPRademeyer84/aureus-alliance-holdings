<?php
// Complete KYC System Setup Migration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [];

    // 1. Add KYC columns to users table
    $addKycColumns = "
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS kyc_status ENUM('not_verified', 'pending', 'verified', 'rejected') DEFAULT 'not_verified',
        ADD COLUMN IF NOT EXISTS facial_verification_status ENUM('not_started', 'pending', 'verified', 'failed') DEFAULT 'not_started',
        ADD COLUMN IF NOT EXISTS kyc_verified_at TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS kyc_rejected_reason TEXT NULL
    ";
    
    try {
        $db->exec($addKycColumns);
        $results['users_table'] = 'Updated successfully';
    } catch (PDOException $e) {
        $results['users_table'] = 'Error: ' . $e->getMessage();
    }

    // 2. Create KYC documents table
    $createKycDocuments = "
        CREATE TABLE IF NOT EXISTS kyc_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('drivers_license', 'national_id', 'passport') NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            rejection_reason TEXT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            verified_by INT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_type (type)
        )
    ";
    
    try {
        $db->exec($createKycDocuments);
        $results['kyc_documents_table'] = 'Created successfully';
    } catch (PDOException $e) {
        $results['kyc_documents_table'] = 'Error: ' . $e->getMessage();
    }

    // 3. Create facial verification table
    $createFacialVerifications = "
        CREATE TABLE IF NOT EXISTS facial_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            kyc_document_id INT NULL,
            captured_image_path VARCHAR(255) NOT NULL,
            confidence_score DECIMAL(5,4) DEFAULT 0.0000,
            liveness_score DECIMAL(5,4) DEFAULT 0.0000,
            verification_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
            comparison_result JSON NULL,
            failure_reason TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            verified_by INT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (kyc_document_id) REFERENCES kyc_documents(id) ON DELETE SET NULL,
            FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_status (verification_status)
        )
    ";
    
    try {
        $db->exec($createFacialVerifications);
        $results['facial_verifications_table'] = 'Created successfully';
    } catch (PDOException $e) {
        $results['facial_verifications_table'] = 'Error: ' . $e->getMessage();
    }

    // 4. Create KYC audit log table
    $createKycAuditLog = "
        CREATE TABLE IF NOT EXISTS kyc_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            document_id INT NULL,
            action ENUM('document_uploaded', 'document_verified', 'document_rejected', 'facial_verification_started', 'facial_verification_completed', 'kyc_approved', 'kyc_rejected') NOT NULL,
            performed_by INT NULL,
            details JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (document_id) REFERENCES kyc_documents(id) ON DELETE SET NULL,
            FOREIGN KEY (performed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )
    ";
    
    try {
        $db->exec($createKycAuditLog);
        $results['kyc_audit_log_table'] = 'Created successfully';
    } catch (PDOException $e) {
        $results['kyc_audit_log_table'] = 'Error: ' . $e->getMessage();
    }

    // 5. Create assets/kyc directory
    $kycDir = '../../assets/kyc';
    if (!is_dir($kycDir)) {
        if (mkdir($kycDir, 0755, true)) {
            $results['kyc_directory'] = 'Created successfully';
        } else {
            $results['kyc_directory'] = 'Failed to create directory';
        }
    } else {
        $results['kyc_directory'] = 'Already exists';
    }

    // 6. Create facial verification images directory
    $facialDir = '../../assets/kyc/facial';
    if (!is_dir($facialDir)) {
        if (mkdir($facialDir, 0755, true)) {
            $results['facial_directory'] = 'Created successfully';
        } else {
            $results['facial_directory'] = 'Failed to create directory';
        }
    } else {
        $results['facial_directory'] = 'Already exists';
    }

    echo json_encode([
        'success' => true,
        'message' => 'KYC system setup completed',
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('KYC setup error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Setup failed: ' . $e->getMessage()
    ]);
}
?>
