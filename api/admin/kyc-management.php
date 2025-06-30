<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start session for admin authentication
session_start();

// Using CORS functions from cors.php instead of local functions

try {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    $database = new Database();
    $db = $database->getConnection();
    
    // Create tables silently
    ob_start();
    $database->createTables();
    ob_end_clean();

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'get';

    switch ($action) {
        case 'get':
            handleGetDocuments($db);
            break;

        case 'get_user_documents':
            handleGetUserDocuments($db, $input);
            break;

        case 'get_facial_verification':
            handleGetFacialVerification($db, $input);
            break;

        case 'get_all_facial_verifications':
            handleGetAllFacialVerifications($db, $input);
            break;

        case 'get_access_logs':
            handleGetAccessLogs($db, $input);
            break;

        case 'approve':
            handleApproveDocument($db, $input);
            break;

        case 'reject':
            handleRejectDocument($db, $input);
            break;

        case 'approve_kyc_section':
            handleApproveKYCSection($db, $input);
            break;

        case 'reject_kyc_section':
            handleRejectKYCSection($db, $input);
            break;

        case 'get_kyc_section_audit_logs':
            handleGetKYCSectionAuditLogs($db, $input);
            break;

        case 'approve_overall_kyc':
            handleApproveOverallKYC($db, $input);
            break;

        case 'reject_overall_kyc':
            handleRejectOverallKYC($db, $input);
            break;

        case 'complete_kyc_verification':
            handleApproveOverallKYC($db, $input);
            break;

        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("KYC Management API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetDocuments($db) {
    try {
        // Get unique users with their KYC status and document summary
        $query = "SELECT
            u.id as user_id,
            u.username,
            u.email,
            u.full_name,
            COALESCE(up.kyc_status, 'pending') as status,
            up.kyc_verified_at,
            up.kyc_rejected_reason,

            -- Get the most recent document info for display
            (SELECT kd.type FROM kyc_documents kd WHERE kd.user_id = u.id ORDER BY kd.upload_date DESC LIMIT 1) as type,
            (SELECT kd.upload_date FROM kyc_documents kd WHERE kd.user_id = u.id ORDER BY kd.upload_date DESC LIMIT 1) as upload_date,

            -- Count documents by status
            (SELECT COUNT(*) FROM kyc_documents kd WHERE kd.user_id = u.id AND kd.status = 'pending') as pending_docs,
            (SELECT COUNT(*) FROM kyc_documents kd WHERE kd.user_id = u.id AND kd.status = 'approved') as approved_docs,
            (SELECT COUNT(*) FROM kyc_documents kd WHERE kd.user_id = u.id AND kd.status = 'rejected') as rejected_docs,
            (SELECT COUNT(*) FROM kyc_documents kd WHERE kd.user_id = u.id) as total_docs

            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE EXISTS (SELECT 1 FROM kyc_documents kd WHERE kd.user_id = u.id)
            ORDER BY
                CASE COALESCE(up.kyc_status, 'pending')
                    WHEN 'pending' THEN 1
                    WHEN 'rejected' THEN 2
                    WHEN 'verified' THEN 3
                END,
                (SELECT MAX(kd.upload_date) FROM kyc_documents kd WHERE kd.user_id = u.id) DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the response to match the expected structure
        $documents = [];
        foreach ($users as $user) {
            $documents[] = [
                'id' => $user['user_id'], // Use user_id as the main ID
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'status' => $user['status'],
                'type' => $user['type'] ?: 'MULTIPLE', // Show document type or 'MULTIPLE'
                'upload_date' => $user['upload_date'],
                'kyc_verified_at' => $user['kyc_verified_at'],
                'rejection_reason' => $user['kyc_rejected_reason'],
                'pending_docs' => (int)$user['pending_docs'],
                'approved_docs' => (int)$user['approved_docs'],
                'rejected_docs' => (int)$user['rejected_docs'],
                'total_docs' => (int)$user['total_docs']
            ];
        }

        sendSuccessResponse(['documents' => $documents], 'Users retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve documents: ' . $e->getMessage(), 500);
    }
}

function handleApproveDocument($db, $input) {
    try {
        $documentId = $input['document_id'] ?? null;
        if (!$documentId) {
            sendErrorResponse('Document ID required', 400);
        }

        $adminId = $_SESSION['admin_id'];

        // Get document info
        $query = "SELECT user_id, type FROM kyc_documents WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            sendErrorResponse('Document not found', 404);
        }

        // Update document status
        $updateQuery = "UPDATE kyc_documents SET 
            status = 'approved', 
            reviewed_by = ?, 
            reviewed_at = NOW(),
            rejection_reason = NULL
            WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $success = $updateStmt->execute([$adminId, $documentId]);

        if ($success) {
            // Check if user has all required documents approved
            checkAndUpdateUserKYCStatus($db, $document['user_id']);
            
            sendSuccessResponse(['approved' => true], 'Document approved successfully');
        } else {
            sendErrorResponse('Failed to approve document', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to approve document: ' . $e->getMessage(), 500);
    }
}

function handleRejectDocument($db, $input) {
    try {
        $documentId = $input['document_id'] ?? null;
        $rejectionReason = $input['rejection_reason'] ?? null;
        
        if (!$documentId || !$rejectionReason) {
            sendErrorResponse('Document ID and rejection reason required', 400);
        }

        $adminId = $_SESSION['admin_id'];

        // Get document info
        $query = "SELECT user_id FROM kyc_documents WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            sendErrorResponse('Document not found', 404);
        }

        // Update document status
        $updateQuery = "UPDATE kyc_documents SET 
            status = 'rejected', 
            reviewed_by = ?, 
            reviewed_at = NOW(),
            rejection_reason = ?
            WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $success = $updateStmt->execute([$adminId, $rejectionReason, $documentId]);

        if ($success) {
            // Update user KYC status to rejected if any document is rejected
            $userUpdateQuery = "UPDATE user_profiles SET 
                kyc_status = 'rejected',
                kyc_rejected_reason = ?
                WHERE user_id = ?";
            $userUpdateStmt = $db->prepare($userUpdateQuery);
            $userUpdateStmt->execute([$rejectionReason, $document['user_id']]);
            
            sendSuccessResponse(['rejected' => true], 'Document rejected successfully');
        } else {
            sendErrorResponse('Failed to reject document', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to reject document: ' . $e->getMessage(), 500);
    }
}

function checkAndUpdateUserKYCStatus($db, $userId) {
    try {
        // Check if user has required documents approved
        // Required: ONE ID document (passport, drivers_license, or national_id) + proof_of_address

        $query = "SELECT type, status FROM kyc_documents WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasApprovedIdDoc = false;
        $hasApprovedProofOfAddress = false;
        $hasRejected = false;

        foreach ($documents as $doc) {
            if ($doc['status'] === 'approved') {
                if (in_array($doc['type'], ['passport', 'drivers_license', 'national_id'])) {
                    $hasApprovedIdDoc = true;
                } elseif ($doc['type'] === 'proof_of_address') {
                    $hasApprovedProofOfAddress = true;
                }
            } elseif ($doc['status'] === 'rejected') {
                $hasRejected = true;
            }
        }

        // Check if all required documents are approved
        $allRequiredApproved = $hasApprovedIdDoc && $hasApprovedProofOfAddress;

        if ($allRequiredApproved && !$hasRejected) {
            // Update user KYC status to verified
            $updateQuery = "UPDATE user_profiles SET
                kyc_status = 'verified',
                kyc_verified_at = NOW(),
                kyc_rejected_reason = NULL
                WHERE user_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$userId]);
        }

    } catch (Exception $e) {
        error_log("Failed to update user KYC status: " . $e->getMessage());
    }
}

function handleGetUserDocuments($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }

        // Get all KYC documents for specific user
        $query = "SELECT
            kd.id, kd.user_id, kd.type, kd.filename, kd.original_name,
            kd.upload_date, kd.status, kd.reviewed_by, kd.reviewed_at, kd.rejection_reason
            FROM kyc_documents kd
            WHERE kd.user_id = ?
            ORDER BY kd.upload_date DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse(['documents' => $documents], 'User documents retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve user documents: ' . $e->getMessage(), 500);
    }
}

function handleGetFacialVerification($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }

        // Get facial verification data for specific user
        $query = "SELECT
            fv.id, fv.captured_image_path, fv.confidence_score, fv.liveness_score,
            fv.verification_status, fv.created_at, fv.verified_at
            FROM facial_verifications fv
            WHERE fv.user_id = ?
            ORDER BY fv.created_at DESC
            LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);

        sendSuccessResponse(['verification' => $verification], 'Facial verification data retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve facial verification: ' . $e->getMessage(), 500);
    }
}

function handleGetAllFacialVerifications($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }

        // Get all facial verification attempts for specific user
        $query = "SELECT
            fv.id, fv.captured_image_path, fv.confidence_score, fv.liveness_score,
            fv.verification_status, fv.comparison_result, fv.created_at, fv.verified_at
            FROM facial_verifications fv
            WHERE fv.user_id = ?
            ORDER BY fv.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse(['verifications' => $verifications], 'All facial verifications retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve facial verifications: ' . $e->getMessage(), 500);
    }
}

function handleGetAccessLogs($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }

        // Create access log table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS kyc_document_access_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id VARCHAR(36) NOT NULL,
            accessed_by VARCHAR(36) NOT NULL,
            access_type ENUM('admin', 'owner') NOT NULL,
            accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_document_id (document_id),
            INDEX idx_accessed_by (accessed_by),
            INDEX idx_accessed_at (accessed_at)
        )";
        $db->exec($createTableQuery);

        // Get access logs for user's documents
        $query = "SELECT
            dal.id, dal.document_id, dal.accessed_by, dal.access_type, dal.accessed_at,
            kd.type as document_type, kd.filename
            FROM kyc_document_access_log dal
            JOIN kyc_documents kd ON dal.document_id = kd.id
            WHERE kd.user_id = ?
            ORDER BY dal.accessed_at DESC
            LIMIT 50";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse(['logs' => $logs], 'Access logs retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve access logs: ' . $e->getMessage(), 500);
    }
}

function handleApproveKYCSection($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $section = $input['section'] ?? null;

        if (!$userId || !$section) {
            sendErrorResponse('User ID and section required', 400);
        }

        $adminId = $_SESSION['admin_id'];
        $validSections = ['personal_info', 'contact_info', 'address_info', 'identity_info', 'financial_info', 'emergency_contact'];

        if (!in_array($section, $validSections)) {
            sendErrorResponse('Invalid section', 400);
        }

        // Update the specific section status to approved
        $statusField = $section . '_status';
        $rejectionField = $section . '_rejection_reason';

        $updateQuery = "UPDATE user_profiles SET
            $statusField = 'approved',
            $rejectionField = NULL
            WHERE user_id = ?";

        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute([$userId]);

        if ($success) {
            // Update related user fields based on section
            updateRelatedUserFields($db, $userId, $section, 'approved');

            // Log the approval action
            $logQuery = "INSERT INTO kyc_section_audit_log
                (user_id, section, action, admin_id, created_at)
                VALUES (?, ?, 'approved', ?, NOW())";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$userId, $section, $adminId]);

            sendSuccessResponse(['approved' => true], 'KYC section approved successfully');
        } else {
            sendErrorResponse('Failed to approve section', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to approve KYC section: ' . $e->getMessage(), 500);
    }
}

function handleRejectKYCSection($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $section = $input['section'] ?? null;
        $rejectionReason = $input['rejection_reason'] ?? null;

        if (!$userId || !$section || !$rejectionReason) {
            sendErrorResponse('User ID, section, and rejection reason required', 400);
        }

        $adminId = $_SESSION['admin_id'];
        $validSections = ['personal_info', 'contact_info', 'address_info', 'identity_info', 'financial_info', 'emergency_contact'];

        if (!in_array($section, $validSections)) {
            sendErrorResponse('Invalid section', 400);
        }

        // Update the specific section status to rejected
        $statusField = $section . '_status';
        $rejectionField = $section . '_rejection_reason';

        $updateQuery = "UPDATE user_profiles SET
            $statusField = 'rejected',
            $rejectionField = ?
            WHERE user_id = ?";

        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute([$rejectionReason, $userId]);

        if ($success) {
            // Update related user fields based on section
            updateRelatedUserFields($db, $userId, $section, 'rejected');

            // Log the rejection action
            $logQuery = "INSERT INTO kyc_section_audit_log
                (user_id, section, action, admin_id, rejection_reason, created_at)
                VALUES (?, ?, 'rejected', ?, ?, NOW())";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$userId, $section, $adminId, $rejectionReason]);

            sendSuccessResponse(['rejected' => true], 'KYC section rejected successfully');
        } else {
            sendErrorResponse('Failed to reject section', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to reject KYC section: ' . $e->getMessage(), 500);
    }
}

function handleGetKYCSectionAuditLogs($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }

        // Get KYC section audit logs for specific user
        $query = "SELECT
            ksal.id, ksal.user_id, ksal.section, ksal.action, ksal.admin_id,
            ksal.rejection_reason, ksal.created_at,
            a.username as admin_username
            FROM kyc_section_audit_log ksal
            LEFT JOIN users a ON ksal.admin_id = a.id
            WHERE ksal.user_id = ?
            ORDER BY ksal.created_at DESC
            LIMIT 100";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse(['logs' => $logs], 'KYC section audit logs retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve KYC section audit logs: ' . $e->getMessage(), 500);
    }
}

function updateRelatedUserFields($db, $userId, $section, $action) {
    try {
        // Update related fields in users table based on the approved/rejected section
        switch ($section) {
            case 'contact_info':
                // When contact info is approved, mark email as verified
                if ($action === 'approved') {
                    $updateQuery = "UPDATE users SET email_verified = 1 WHERE id = ?";
                    $stmt = $db->prepare($updateQuery);
                    $stmt->execute([$userId]);
                } else {
                    // When contact info is rejected, mark email as not verified
                    $updateQuery = "UPDATE users SET email_verified = 0 WHERE id = ?";
                    $stmt = $db->prepare($updateQuery);
                    $stmt->execute([$userId]);
                }
                break;

            case 'personal_info':
                // Update profile completion when personal info is approved
                if ($action === 'approved') {
                    updateProfileCompletion($db, $userId);
                }
                break;

            case 'identity_info':
                // When identity info is approved, we could update KYC status
                if ($action === 'approved') {
                    // Check if all required sections are approved
                    checkAndUpdateOverallKYCStatus($db, $userId);
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Failed to update related user fields: " . $e->getMessage());
    }
}

function updateProfileCompletion($db, $userId) {
    try {
        // Calculate profile completion based on approved sections
        $query = "SELECT
            personal_info_status,
            contact_info_status,
            address_info_status,
            identity_info_status,
            financial_info_status,
            emergency_contact_status
            FROM user_profiles WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            $sections = [
                'personal_info_status',
                'contact_info_status',
                'address_info_status',
                'identity_info_status',
                'financial_info_status',
                'emergency_contact_status'
            ];

            $approvedCount = 0;
            foreach ($sections as $sectionStatus) {
                if ($profile[$sectionStatus] === 'approved') {
                    $approvedCount++;
                }
            }

            $completion = round(($approvedCount / count($sections)) * 100);

            // Update profile completion
            $updateQuery = "UPDATE user_profiles SET profile_completion = ? WHERE user_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$completion, $userId]);
        }
    } catch (Exception $e) {
        error_log("Failed to update profile completion: " . $e->getMessage());
    }
}

function checkAndUpdateOverallKYCStatus($db, $userId) {
    try {
        // Get all section statuses
        $query = "SELECT
            personal_info_status,
            contact_info_status,
            address_info_status,
            identity_info_status,
            financial_info_status,
            emergency_contact_status
            FROM user_profiles WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            // Check if core sections are approved (personal, contact, identity)
            $coreApproved = (
                $profile['personal_info_status'] === 'approved' &&
                $profile['contact_info_status'] === 'approved' &&
                $profile['identity_info_status'] === 'approved'
            );

            if ($coreApproved) {
                // Update overall KYC status to verified
                $updateQuery = "UPDATE user_profiles SET
                    kyc_status = 'verified',
                    kyc_verified_at = NOW()
                    WHERE user_id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$userId]);

                // Also ensure email is verified when KYC is fully approved
                $updateEmailQuery = "UPDATE users SET email_verified = 1 WHERE id = ?";
                $emailStmt = $db->prepare($updateEmailQuery);
                $emailStmt->execute([$userId]);

                // Update profile completion to 100% when KYC is fully approved
                $updateProfileQuery = "UPDATE user_profiles SET profile_completion = 100 WHERE user_id = ?";
                $profileStmt = $db->prepare($updateProfileQuery);
                $profileStmt->execute([$userId]);
            }
        }
    } catch (Exception $e) {
        error_log("Failed to update overall KYC status: " . $e->getMessage());
    }
}

function handleApproveOverallKYC($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;

        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }

        $adminId = $_SESSION['admin_id'];

        // Approve all core KYC sections
        $updateQuery = "UPDATE user_profiles SET
            personal_info_status = 'approved',
            contact_info_status = 'approved',
            identity_info_status = 'approved',
            kyc_status = 'verified',
            kyc_verified_at = NOW(),
            kyc_rejected_reason = NULL,
            profile_completion = 100
            WHERE user_id = ?";

        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute([$userId]);

        if ($success) {
            // Also ensure email is verified
            $updateEmailQuery = "UPDATE users SET email_verified = 1 WHERE id = ?";
            $emailStmt = $db->prepare($updateEmailQuery);
            $emailStmt->execute([$userId]);

            // Update facial verification status if exists
            $updateFacialQuery = "UPDATE user_profiles SET
                facial_verification_status = 'verified',
                facial_verification_at = NOW()
                WHERE user_id = ? AND facial_verification_status IS NOT NULL";
            $facialStmt = $db->prepare($updateFacialQuery);
            $facialStmt->execute([$userId]);

            // Log the approval action
            $logQuery = "INSERT INTO kyc_section_audit_log
                (user_id, section, action, admin_id, created_at)
                VALUES (?, 'overall_kyc', 'approved', ?, NOW())";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$userId, $adminId]);

            sendSuccessResponse(['approved' => true], 'Overall KYC approved successfully');
        } else {
            sendErrorResponse('Failed to approve overall KYC', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to approve overall KYC: ' . $e->getMessage(), 500);
    }
}

function handleRejectOverallKYC($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $rejectionReason = $input['rejection_reason'] ?? null;

        if (!$userId || !$rejectionReason) {
            sendErrorResponse('User ID and rejection reason required', 400);
        }

        $adminId = $_SESSION['admin_id'];

        // Reject overall KYC status
        $updateQuery = "UPDATE user_profiles SET
            kyc_status = 'rejected',
            kyc_rejected_reason = ?,
            kyc_verified_at = NULL
            WHERE user_id = ?";

        $stmt = $db->prepare($updateQuery);
        $success = $stmt->execute([$rejectionReason, $userId]);

        if ($success) {
            // Log the rejection action
            $logQuery = "INSERT INTO kyc_section_audit_log
                (user_id, section, action, admin_id, rejection_reason, created_at)
                VALUES (?, 'overall_kyc', 'rejected', ?, ?, NOW())";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([$userId, $adminId, $rejectionReason]);

            sendSuccessResponse(['rejected' => true], 'Overall KYC rejected successfully');
        } else {
            sendErrorResponse('Failed to reject overall KYC', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to reject overall KYC: ' . $e->getMessage(), 500);
    }
}

?>
