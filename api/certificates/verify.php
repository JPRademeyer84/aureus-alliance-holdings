<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleVerifyCertificate($db);
            break;
        case 'POST':
            handleBatchVerification($db);
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

function handleVerifyCertificate($db) {
    try {
        $verificationCode = $_GET['code'] ?? null;
        $certificateNumber = $_GET['certificate_number'] ?? null;
        $verificationHash = $_GET['hash'] ?? null;
        
        if (!$verificationCode && !$certificateNumber && !$verificationHash) {
            throw new Exception("Verification code, certificate number, or hash is required");
        }

        $certificate = null;
        
        if ($verificationCode) {
            $certificate = verifyCertificateByCode($db, $verificationCode);
        } elseif ($certificateNumber) {
            $certificate = verifyCertificateByNumber($db, $certificateNumber);
        } elseif ($verificationHash) {
            $certificate = verifyCertificateByHash($db, $verificationHash);
        }

        if (!$certificate) {
            echo json_encode([
                'success' => false,
                'verified' => false,
                'message' => 'Certificate not found or invalid'
            ]);
            return;
        }

        // Log verification attempt
        logVerificationAttempt($db, $certificate['id'], $_SERVER['REMOTE_ADDR'] ?? null);

        // Check certificate validity
        $isValid = checkCertificateValidity($certificate);

        echo json_encode([
            'success' => true,
            'verified' => $isValid,
            'certificate' => [
                'certificate_number' => $certificate['certificate_number'],
                'holder_name' => $certificate['username'],
                'package_name' => $certificate['package_name'],
                'share_quantity' => (int)$certificate['share_quantity'],
                'certificate_value' => (float)$certificate['certificate_value'],
                'issue_date' => $certificate['issue_date'],
                'legal_status' => $certificate['legal_status'],
                'verification_status' => $isValid ? 'valid' : 'invalid',
                'issued_by' => 'Aureus Alliance Holdings',
                'verification_timestamp' => date('Y-m-d H:i:s')
            ],
            'verification_details' => [
                'verification_method' => $verificationCode ? 'code' : ($certificateNumber ? 'number' : 'hash'),
                'verification_count' => (int)$certificate['verification_count'] + 1,
                'last_verified' => $certificate['last_verified_at'],
                'certificate_age_days' => floor((time() - strtotime($certificate['created_at'])) / 86400)
            ]
        ]);

    } catch (Exception $e) {
        throw new Exception("Verification failed: " . $e->getMessage());
    }
}

function handleBatchVerification($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['certificates']) || !is_array($input['certificates'])) {
            throw new Exception("Certificates array is required");
        }

        $results = [];
        
        foreach ($input['certificates'] as $certData) {
            try {
                $certificate = null;
                
                if (isset($certData['verification_code'])) {
                    $certificate = verifyCertificateByCode($db, $certData['verification_code']);
                } elseif (isset($certData['certificate_number'])) {
                    $certificate = verifyCertificateByNumber($db, $certData['certificate_number']);
                } elseif (isset($certData['verification_hash'])) {
                    $certificate = verifyCertificateByHash($db, $certData['verification_hash']);
                }

                if ($certificate) {
                    logVerificationAttempt($db, $certificate['id'], $_SERVER['REMOTE_ADDR'] ?? null);
                    $isValid = checkCertificateValidity($certificate);
                    
                    $results[] = [
                        'input' => $certData,
                        'verified' => $isValid,
                        'certificate_number' => $certificate['certificate_number'],
                        'legal_status' => $certificate['legal_status']
                    ];
                } else {
                    $results[] = [
                        'input' => $certData,
                        'verified' => false,
                        'error' => 'Certificate not found'
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'input' => $certData,
                    'verified' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'results' => $results,
            'summary' => [
                'total_checked' => count($results),
                'valid_certificates' => count(array_filter($results, fn($r) => $r['verified'])),
                'invalid_certificates' => count(array_filter($results, fn($r) => !$r['verified']))
            ]
        ]);

    } catch (Exception $e) {
        throw new Exception("Batch verification failed: " . $e->getMessage());
    }
}

function verifyCertificateByCode($db, $verificationCode) {
    $query = "SELECT 
        sc.*,
        u.username,
        ai.package_name,
        cv.verification_count,
        cv.last_verified_at
    FROM certificate_verifications cv
    JOIN share_certificates sc ON cv.certificate_id = sc.id
    LEFT JOIN users u ON sc.user_id = u.id
    LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
    WHERE cv.verification_code = ? AND cv.is_active = TRUE
    AND (cv.expires_at IS NULL OR cv.expires_at > NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$verificationCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verifyCertificateByNumber($db, $certificateNumber) {
    $query = "SELECT 
        sc.*,
        u.username,
        ai.package_name,
        COALESCE(cv.verification_count, 0) as verification_count,
        cv.last_verified_at
    FROM share_certificates sc
    LEFT JOIN users u ON sc.user_id = u.id
    LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
    LEFT JOIN certificate_verifications cv ON sc.id = cv.certificate_id
    WHERE sc.certificate_number = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verifyCertificateByHash($db, $verificationHash) {
    $query = "SELECT 
        sc.*,
        u.username,
        ai.package_name,
        COALESCE(cv.verification_count, 0) as verification_count,
        cv.last_verified_at
    FROM share_certificates sc
    LEFT JOIN users u ON sc.user_id = u.id
    LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
    LEFT JOIN certificate_verifications cv ON sc.id = cv.certificate_id
    WHERE sc.verification_hash = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$verificationHash]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function checkCertificateValidity($certificate) {
    // Check if certificate is in valid legal status
    if ($certificate['legal_status'] !== 'valid') {
        return false;
    }
    
    // Check if certificate generation is completed
    if ($certificate['generation_status'] !== 'completed') {
        return false;
    }
    
    // Additional validity checks can be added here
    // For example: expiration dates, revocation lists, etc.
    
    return true;
}

function logVerificationAttempt($db, $certificateId, $ipAddress) {
    try {
        // Update verification count and last verified timestamp
        $updateQuery = "UPDATE certificate_verifications 
                       SET verification_count = verification_count + 1,
                           last_verified_at = NOW(),
                           last_verified_ip = ?
                       WHERE certificate_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$ipAddress, $certificateId]);
        
        // Log the verification attempt
        $logQuery = "INSERT INTO certificate_access_log (
            certificate_id, accessed_by, access_type, access_method,
            ip_address, accessed_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([
            $certificateId,
            'public',
            'verify',
            'api',
            $ipAddress
        ]);
        
    } catch (Exception $e) {
        // Log error but don't fail the verification
        error_log("Failed to log verification attempt: " . $e->getMessage());
    }
}
?>
