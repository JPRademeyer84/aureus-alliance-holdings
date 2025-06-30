<?php
require_once '../config/database.php';

// Response utility functions
function sendSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Simple CORS headers with credentials support
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Create user_profiles table if it doesn't exist
function createUserProfilesTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS user_profiles (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        country VARCHAR(100),
        city VARCHAR(100),
        date_of_birth DATE,
        profile_image VARCHAR(255),
        bio TEXT,
        telegram_username VARCHAR(100),
        whatsapp_number VARCHAR(20),
        twitter_handle VARCHAR(100),
        instagram_handle VARCHAR(100),
        linkedin_profile VARCHAR(255),
        facebook_profile VARCHAR(255),
        kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
        kyc_verified_at TIMESTAMP NULL,
        kyc_rejected_reason TEXT,
        profile_completion INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_profile (user_id),
        INDEX idx_kyc_status (kyc_status),
        INDEX idx_completion (profile_completion)
    )";

    $db->exec($query);
}

function createKycDocumentsTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS kyc_documents (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id VARCHAR(255) NOT NULL,
        type ENUM('passport', 'drivers_license', 'national_id', 'proof_of_address') NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_by VARCHAR(36) NULL,
        reviewed_at TIMESTAMP NULL,
        rejection_reason TEXT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_type (type)
    )";

    $db->exec($query);
}

// Using CORS functions from cors.php instead of local functions

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create tables if they don't exist
    createUserProfilesTable($db);
    createKycDocumentsTable($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'get';

    // Check if user is logged in via session
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        // For profile GET requests, try to get user ID from query parameter
        // This allows frontend to work even if session is lost
        if ($action === 'get' && isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
        } else {
            sendErrorResponse('User not authenticated', 401);
        }
    }

    switch ($action) {
        case 'get':
            handleGetProfile($db, $userId);
            break;

        case 'update':
            handleUpdateProfile($db, $userId, $input);
            break;

        case 'kyc_documents':
            handleKycDocuments($db, $userId);
            break;

        case 'upload_kyc':
            handleKycUpload($db, $userId);
            break;

        case 'delete_kyc':
            handleKycDelete($db, $userId, $input);
            break;

        case 'stats':
            handleGetStats($db, $userId);
            break;

        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Enhanced Profile API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetProfile($db, $userId) {
    try {
        error_log("Enhanced Profile: Fetching profile for user ID: " . $userId);

        // Get user profile with extended information
        $query = "SELECT 
            u.id, u.username, u.email, u.full_name, u.created_at,
            up.phone, up.country, up.city, up.date_of_birth, up.profile_image, up.bio,
            up.telegram_username, up.whatsapp_number, up.twitter_handle, 
            up.instagram_handle, up.linkedin_profile, up.facebook_profile,
            up.kyc_status, up.kyc_verified_at, up.kyc_rejected_reason,
            up.profile_completion, up.updated_at,
            COALESCE(inv_stats.total_invested, 0) as total_invested,
            COALESCE(comm_stats.total_commissions, 0) as total_commissions,
            COALESCE(ref_stats.referral_count, 0) as referral_count
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (
                SELECT user_id, SUM(amount) as total_invested
                FROM aureus_investments
                WHERE status = 'completed'
                GROUP BY user_id
            ) inv_stats ON u.id = inv_stats.user_id
            LEFT JOIN (
                SELECT referrer_user_id, SUM(usdt_commission_amount) as total_commissions
                FROM commission_transactions 
                WHERE status = 'paid'
                GROUP BY referrer_user_id
            ) comm_stats ON u.id = comm_stats.referrer_user_id
            LEFT JOIN (
                SELECT referrer_user_id, COUNT(*) as referral_count
                FROM referral_relationships 
                WHERE status = 'active'
                GROUP BY referrer_user_id
            ) ref_stats ON u.id = ref_stats.referrer_user_id
            WHERE u.id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Enhanced Profile: Query result: " . json_encode($profile));

        if (!$profile) {
            error_log("Enhanced Profile: No profile found for user ID: " . $userId);
            sendErrorResponse('Profile not found', 404);
        }

        // Calculate profile completion if not set
        if (!$profile['profile_completion']) {
            $profile['profile_completion'] = calculateProfileCompletion($profile);
        }

        sendSuccessResponse(['profile' => $profile], 'Profile retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve profile: ' . $e->getMessage(), 500);
    }
}

function handleUpdateProfile($db, $userId, $input) {
    try {
        error_log("Profile update started for user: " . $userId);
        error_log("Input data: " . json_encode($input));

        // Check if profile exists
        $checkQuery = "SELECT id FROM user_profiles WHERE user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$userId]);
        $exists = $checkStmt->fetch();

        error_log("Profile exists: " . ($exists ? 'yes' : 'no'));

        $profileData = [
            'phone' => $input['phone'] ?? null,
            'country' => $input['country'] ?? null,
            'city' => $input['city'] ?? null,
            'date_of_birth' => $input['date_of_birth'] ?? null,
            'bio' => $input['bio'] ?? null,
            'telegram_username' => $input['telegram_username'] ?? null,
            'whatsapp_number' => $input['whatsapp_number'] ?? null,
            'twitter_handle' => $input['twitter_handle'] ?? null,
            'instagram_handle' => $input['instagram_handle'] ?? null,
            'linkedin_profile' => $input['linkedin_profile'] ?? null,
            'facebook_profile' => $input['facebook_profile'] ?? null,
            'profile_completion' => $input['profile_completion'] ?? 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($exists) {
            // Update existing profile
            $updateFields = [];
            $updateValues = [];
            
            foreach ($profileData as $field => $value) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
            
            $updateValues[] = $userId;
            
            $updateQuery = "UPDATE user_profiles SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            error_log("Update query: " . $updateQuery);
            error_log("Update values: " . json_encode($updateValues));
            $stmt = $db->prepare($updateQuery);
            $success = $stmt->execute($updateValues);
            error_log("Update success: " . ($success ? 'yes' : 'no'));
        } else {
            // Create new profile
            $profileData['user_id'] = $userId;
            $profileData['created_at'] = date('Y-m-d H:i:s');
            
            $fields = implode(', ', array_keys($profileData));
            $placeholders = implode(', ', array_fill(0, count($profileData), '?'));
            
            $insertQuery = "INSERT INTO user_profiles ($fields) VALUES ($placeholders)";
            error_log("Insert query: " . $insertQuery);
            error_log("Insert values: " . json_encode(array_values($profileData)));
            $stmt = $db->prepare($insertQuery);
            $success = $stmt->execute(array_values($profileData));
            error_log("Insert success: " . ($success ? 'yes' : 'no'));
        }

        if ($success) {
            // Also update user's full_name if provided
            if (isset($input['full_name'])) {
                $userUpdateQuery = "UPDATE users SET full_name = ? WHERE id = ?";
                $userStmt = $db->prepare($userUpdateQuery);
                $userStmt->execute([$input['full_name'], $userId]);
            }

            // Get the updated profile data
            $query = "SELECT
                u.id, u.username, u.email, u.full_name, u.created_at,
                up.phone, up.country, up.city, up.date_of_birth, up.profile_image, up.bio,
                up.telegram_username, up.whatsapp_number, up.twitter_handle,
                up.instagram_handle, up.linkedin_profile, up.facebook_profile,
                up.kyc_status, up.kyc_verified_at, up.kyc_rejected_reason,
                up.profile_completion, up.updated_at,
                COALESCE(inv_stats.total_invested, 0) as total_invested,
                COALESCE(comm_stats.total_commissions, 0) as total_commissions,
                COALESCE(ref_stats.referral_count, 0) as referral_count
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                LEFT JOIN (
                    SELECT user_id, SUM(amount) as total_invested
                    FROM aureus_investments
                    WHERE status = 'completed'
                    GROUP BY user_id
                ) inv_stats ON u.id = inv_stats.user_id
                LEFT JOIN (
                    SELECT referrer_user_id, SUM(usdt_commission_amount) as total_commissions
                    FROM commission_transactions
                    WHERE status = 'paid'
                    GROUP BY referrer_user_id
                ) comm_stats ON u.id = comm_stats.referrer_user_id
                LEFT JOIN (
                    SELECT referrer_user_id, COUNT(*) as referral_count
                    FROM referral_relationships
                    WHERE status = 'active'
                    GROUP BY referrer_user_id
                ) ref_stats ON u.id = ref_stats.referrer_user_id
                WHERE u.id = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($profile) {
                // Calculate profile completion if not set
                if (!$profile['profile_completion']) {
                    $profile['profile_completion'] = calculateProfileCompletion($profile);
                }
                sendSuccessResponse(['profile' => $profile], 'Profile updated successfully');
            } else {
                sendErrorResponse('Failed to retrieve updated profile', 500);
            }
        } else {
            sendErrorResponse('Failed to update profile', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to update profile: ' . $e->getMessage(), 500);
    }
}

function handleKycDocuments($db, $userId) {
    try {

        $query = "SELECT id, type, filename, upload_date, status FROM kyc_documents WHERE user_id = ? ORDER BY upload_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse(['documents' => $documents], 'KYC documents retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve KYC documents: ' . $e->getMessage(), 500);
    }
}

function handleKycUpload($db, $userId) {
    try {
        if (!isset($_FILES['document']) || !isset($_POST['type'])) {
            sendErrorResponse('Missing required fields', 400);
        }
        $documentType = $_POST['type'];
        $file = $_FILES['document'];

        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            sendErrorResponse('Invalid file type. Only JPEG, PNG, and PDF files are allowed.', 400);
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            sendErrorResponse('File size too large. Maximum 5MB allowed.', 400);
        }

        // Create upload directory if it doesn't exist
        $uploadDir = '../assets/kyc/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $userId . '_' . $documentType . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Save to database
            $query = "INSERT INTO kyc_documents (user_id, type, filename, original_name, file_path, upload_date, status) 
                     VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
            $stmt = $db->prepare($query);
            $success = $stmt->execute([
                $userId,
                $documentType,
                $filename,
                $file['name'],
                $filepath
            ]);

            if ($success) {
                sendSuccessResponse(['filename' => $filename], 'Document uploaded successfully');
            } else {
                unlink($filepath); // Remove file if database insert failed
                sendErrorResponse('Failed to save document record', 500);
            }
        } else {
            sendErrorResponse('Failed to upload file', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to upload document: ' . $e->getMessage(), 500);
    }
}

function handleKycDelete($db, $userId, $input) {
    try {
        $documentId = $input['document_id'] ?? null;
        if (!$documentId) {
            sendErrorResponse('Document ID required', 400);
        }

        // Check if document belongs to user
        $query = "SELECT id, file_path, status FROM kyc_documents WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$documentId, $userId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            sendErrorResponse('Document not found or access denied', 404);
        }

        // Only allow deletion of pending or rejected documents
        if ($document['status'] === 'approved') {
            sendErrorResponse('Cannot delete approved documents', 400);
        }

        // Delete file from filesystem
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }

        // Delete from database
        $deleteQuery = "DELETE FROM kyc_documents WHERE id = ? AND user_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $success = $deleteStmt->execute([$documentId, $userId]);

        if ($success) {
            sendSuccessResponse(['deleted' => true], 'Document deleted successfully');
        } else {
            sendErrorResponse('Failed to delete document', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to delete document: ' . $e->getMessage(), 500);
    }
}

function handleGetStats($db, $userId) {
    try {

        // Get comprehensive user statistics
        $stats = [];

        // Investment statistics
        $invQuery = "SELECT 
            COUNT(*) as investment_count,
            SUM(amount) as total_invested,
            AVG(amount) as avg_investment,
            MAX(amount) as largest_investment
            FROM aureus_investments
            WHERE user_id = ? AND status = 'completed'";
        $invStmt = $db->prepare($invQuery);
        $invStmt->execute([$userId]);
        $stats['investments'] = $invStmt->fetch(PDO::FETCH_ASSOC);

        // Commission statistics
        $commQuery = "SELECT 
            COUNT(*) as commission_count,
            SUM(usdt_commission_amount) as total_commissions,
            SUM(CASE WHEN status = 'paid' THEN usdt_commission_amount ELSE 0 END) as paid_commissions,
            SUM(CASE WHEN status = 'pending' THEN usdt_commission_amount ELSE 0 END) as pending_commissions
            FROM commission_transactions 
            WHERE referrer_user_id = ?";
        $commStmt = $db->prepare($commQuery);
        $commStmt->execute([$userId]);
        $stats['commissions'] = $commStmt->fetch(PDO::FETCH_ASSOC);

        // Referral statistics
        $refQuery = "SELECT 
            COUNT(*) as total_referrals,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_referrals,
            SUM(total_investments) as referral_investments
            FROM referral_relationships 
            WHERE referrer_user_id = ?";
        $refStmt = $db->prepare($refQuery);
        $refStmt->execute([$userId]);
        $stats['referrals'] = $refStmt->fetch(PDO::FETCH_ASSOC);

        sendSuccessResponse($stats, 'Statistics retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
    }
}

function calculateProfileCompletion($profile) {
    $requiredFields = ['full_name', 'phone', 'country', 'city', 'date_of_birth', 'whatsapp_number', 'telegram_username'];
    $optionalFields = ['bio', 'twitter_handle', 'instagram_handle', 'linkedin_profile'];
    
    $completedRequired = 0;
    foreach ($requiredFields as $field) {
        if (!empty($profile[$field])) {
            $completedRequired++;
        }
    }
    
    $completedOptional = 0;
    foreach ($optionalFields as $field) {
        if (!empty($profile[$field])) {
            $completedOptional++;
        }
    }
    
    $requiredScore = ($completedRequired / count($requiredFields)) * 70; // 70% for required
    $optionalScore = ($completedOptional / count($optionalFields)) * 20; // 20% for optional
    $kycScore = ($profile['kyc_status'] === 'verified') ? 10 : 0; // 10% for KYC
    
    return round($requiredScore + $optionalScore + $kycScore);
}

?>
