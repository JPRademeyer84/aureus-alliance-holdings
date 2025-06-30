<?php
require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetUserCertificates($db);
            break;
        case 'POST':
            handleCertificateAction($db);
            break;
        default:
            throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetUserCertificates($db) {
    try {
        $userId = $_GET['user_id'] ?? null;
        $certificateId = $_GET['certificate_id'] ?? null;
        
        if (!$userId) {
            throw new Exception("User ID is required");
        }

        if ($certificateId) {
            // Get specific certificate
            $certificate = getCertificateDetails($db, $certificateId, $userId);
            if (!$certificate) {
                throw new Exception("Certificate not found");
            }
            
            // Log certificate view
            logCertificateAccess($db, $certificateId, $userId, 'view');
            
            echo json_encode([
                'success' => true,
                'certificate' => $certificate
            ]);
        } else {
            // Get all user certificates
            $certificates = getUserCertificates($db, $userId);
            
            echo json_encode([
                'success' => true,
                'certificates' => $certificates,
                'count' => count($certificates)
            ]);
        }

    } catch (Exception $e) {
        throw new Exception("Failed to fetch certificates: " . $e->getMessage());
    }
}

function handleCertificateAction($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['certificate_id']) || empty($input['user_id']) || empty($input['action'])) {
            throw new Exception("Certificate ID, user ID, and action are required");
        }

        $certificateId = $input['certificate_id'];
        $userId = $input['user_id'];
        $action = $input['action'];

        // Verify certificate belongs to user
        $certificate = getCertificateDetails($db, $certificateId, $userId);
        if (!$certificate) {
            throw new Exception("Certificate not found or access denied");
        }

        switch ($action) {
            case 'download':
                handleDownload($db, $certificateId, $userId, $certificate);
                break;
            case 'share':
                handleShare($db, $certificateId, $userId, $input);
                break;
            case 'verify':
                handleVerify($db, $certificateId, $userId);
                break;
            default:
                throw new Exception("Invalid action");
        }

    } catch (Exception $e) {
        throw new Exception("Failed to process action: " . $e->getMessage());
    }
}

function getUserCertificates($db, $userId) {
    try {
        $query = "SELECT
            sc.*,
            ai.package_name,
            ai.amount as investment_amount,
            ai.created_at as investment_date,
            ct.template_name,
            cv.verification_code,
            cv.verification_url
        FROM share_certificates sc
        LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
        LEFT JOIN certificate_templates ct ON sc.template_id = ct.id
        LEFT JOIN certificate_verifications cv ON sc.id = cv.certificate_id
        WHERE sc.user_id = ? AND sc.legal_status = 'valid'
        ORDER BY sc.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If certificate tables don't exist, return empty array
        error_log("Certificate tables not found: " . $e->getMessage());
        return [];
    }
}

function getCertificateDetails($db, $certificateId, $userId) {
    $query = "SELECT 
        sc.*,
        ai.package_name,
        ai.amount as investment_amount,
        ai.created_at as investment_date,
        ai.wallet_address,
        ai.chain,
        ct.template_name,
        cv.verification_code,
        cv.verification_url
    FROM share_certificates sc
    LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
    LEFT JOIN certificate_templates ct ON sc.template_id = ct.id
    LEFT JOIN certificate_verifications cv ON sc.id = cv.certificate_id
    WHERE sc.id = ? AND sc.user_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function handleDownload($db, $certificateId, $userId, $certificate) {
    // Log download action
    logCertificateAccess($db, $certificateId, $userId, 'download');
    
    // Update view count and first viewed timestamp
    updateCertificateViewing($db, $certificateId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Download logged successfully',
        'download_url' => $certificate['certificate_image_path'] ? 
            'http://localhost/aureus-angel-alliance/' . $certificate['certificate_image_path'] : null,
        'pdf_url' => $certificate['certificate_pdf_path'] ? 
            'http://localhost/aureus-angel-alliance/' . $certificate['certificate_pdf_path'] : null
    ]);
}

function handleShare($db, $certificateId, $userId, $input) {
    $shareMethod = $input['share_method'] ?? 'link';
    
    // Log share action
    logCertificateAccess($db, $certificateId, $userId, 'share');
    
    // Generate shareable link
    $shareUrl = generateShareableLink($db, $certificateId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Certificate shared successfully',
        'share_url' => $shareUrl,
        'share_method' => $shareMethod
    ]);
}

function handleVerify($db, $certificateId, $userId) {
    // Get verification details
    $query = "SELECT cv.verification_code, cv.verification_url 
              FROM certificate_verifications cv 
              WHERE cv.certificate_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateId]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verification) {
        throw new Exception("Verification details not found");
    }
    
    // Log verify action
    logCertificateAccess($db, $certificateId, $userId, 'verify');
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification details retrieved',
        'verification_code' => $verification['verification_code'],
        'verification_url' => $verification['verification_url']
    ]);
}

function logCertificateAccess($db, $certificateId, $userId, $accessType) {
    $query = "INSERT INTO certificate_access_log (
        certificate_id, accessed_by, access_type, access_method, 
        ip_address, user_agent, accessed_at
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $certificateId,
        $userId,
        $accessType,
        'dashboard',
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function updateCertificateViewing($db, $certificateId) {
    // Update view count and set first_viewed_at if not set
    $query = "UPDATE share_certificates 
              SET view_count = view_count + 1,
                  first_viewed_at = COALESCE(first_viewed_at, NOW()),
                  delivery_status = CASE 
                    WHEN delivery_status = 'pending' THEN 'viewed'
                    ELSE delivery_status
                  END
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateId]);
}

function generateShareableLink($db, $certificateId) {
    // Get verification code for shareable link
    $query = "SELECT cv.verification_code 
              FROM certificate_verifications cv 
              WHERE cv.certificate_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateId]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verification) {
        return "https://aureusangels.com/verify/" . $verification['verification_code'];
    }
    
    return null;
}
?>
