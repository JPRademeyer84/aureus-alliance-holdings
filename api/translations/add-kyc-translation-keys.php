<?php
// Add KYC Translation Keys
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
    
    $kycTranslations = [
        // Navigation and Headers
        'kyc_verification' => 'KYC Verification',
        'back_to_dashboard' => 'Back to Dashboard',
        'verify_identity_secure_account' => 'Verify your identity to secure your account',
        
        // Status and Verification
        'verification_status' => 'Verification Status',
        'verified' => 'Verified',
        'pending' => 'Pending',
        'not_verified' => 'Not Verified',
        'rejected' => 'Rejected',
        
        // Document Upload
        'upload_identity_document' => 'Upload Identity Document',
        'kyc_upload_description' => 'Upload ONE of the following identity documents. The document must be clear and all information must be visible.',
        'uploaded_documents' => 'Uploaded Documents',
        
        // Facial Verification
        'facial_verification' => 'Facial Verification',
        'facial_verification_description' => 'Take a selfie to verify your identity matches your uploaded document. This step is required after uploading your identity document.',
        'live_selfie_verification' => 'Live Selfie Verification',
        'selfie_instructions' => 'Position your face in the center of the camera and follow the on-screen instructions. Make sure you\'re in a well-lit area.',
        'verification_complete' => 'Verification Complete',
        'start_facial_verification' => 'Start Facial Verification',
        'upload_document_first' => 'Please upload an identity document first',
        
        // Requirements
        'verification_requirements' => 'Verification Requirements',
        'document_requirements' => 'Document Requirements',
        'photo_requirements' => 'Photo Requirements',
        'req_clear_readable' => 'Document must be clear and readable',
        'req_all_corners_visible' => 'All four corners must be visible',
        'req_no_glare_shadows' => 'No glare or shadows on the document',
        'req_valid_not_expired' => 'Document must be valid and not expired',
        'req_face_clearly_visible' => 'Face must be clearly visible',
        'req_good_lighting' => 'Good lighting conditions',
        'req_no_sunglasses_hat' => 'No sunglasses or hat covering face',
        'req_look_directly_camera' => 'Look directly at the camera',
        
        // Document Types
        'drivers_license' => 'Driver\'s License',
        'national_id' => 'National ID',
        'passport' => 'Passport',
        
        // Actions and Messages
        'upload' => 'Upload',
        'uploading' => 'Uploading...',
        'delete_document' => 'Delete Document',
        'view_document' => 'View Document',
        'document_uploaded_successfully' => 'Document uploaded successfully',
        'document_deleted_successfully' => 'Document deleted successfully',
        'upload_failed' => 'Upload failed. Please try again.',
        'file_too_large' => 'File too large. Maximum size is 10MB.',
        'invalid_file_type' => 'Invalid file type. Please upload an image or PDF.',
        'confirm_delete_document' => 'Are you sure you want to delete this document?',
        
        // Status Messages
        'kyc_not_started' => 'KYC verification not started',
        'kyc_in_progress' => 'KYC verification in progress',
        'kyc_completed' => 'KYC verification completed',
        'kyc_rejected' => 'KYC verification rejected',
        'facial_verification_pending' => 'Facial verification pending',
        'facial_verification_completed' => 'Facial verification completed',
        'facial_verification_failed' => 'Facial verification failed',
        
        // Process Steps
        'step_document_upload' => 'Document Upload',
        'step_facial_verification' => 'Facial Verification',
        'step_admin_review' => 'Admin Review',
        'step_complete' => 'Complete',
        'step_required' => 'Required',
        'step_approved' => 'Approved'
    ];
    
    $results = [];
    $category = 'kyc';
    
    // Get English language ID
    $langStmt = $db->prepare("SELECT id FROM languages WHERE code = 'en'");
    $langStmt->execute();
    $englishLang = $langStmt->fetch(PDO::FETCH_ASSOC);

    if (!$englishLang) {
        throw new Exception('English language not found in database');
    }

    $englishId = $englishLang['id'];

    foreach ($kycTranslations as $key => $englishText) {
        // Check if key already exists
        $checkStmt = $db->prepare("SELECT id FROM translation_keys WHERE key_name = ?");
        $checkStmt->execute([$key]);
        $existingKey = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingKey) {
            // Insert new translation key
            $insertStmt = $db->prepare("
                INSERT INTO translation_keys (key_name, category, description, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $insertStmt->execute([$key, $category, $englishText]);

            $keyId = $db->lastInsertId();

            // Add English translation
            $translationStmt = $db->prepare("
                INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at)
                VALUES (?, ?, ?, TRUE, NOW())
            ");
            $translationStmt->execute([$keyId, $englishId, $englishText]);

            $results[$key] = 'Added successfully';
        } else {
            $results[$key] = 'Already exists';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'KYC translation keys processed',
        'total_keys' => count($kycTranslations),
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('KYC translation keys error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add translation keys: ' . $e->getMessage()
    ]);
}
?>
