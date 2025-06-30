<?php
/**
 * MFA MANAGEMENT API
 * Admin interface for managing multi-factor authentication
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/mfa-system.php';
require_once '../config/input-validator.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $mfa = MFASystem::getInstance();
    
    switch ($action) {
        case 'setup_totp':
            setupTOTP();
            break;
            
        case 'enable_totp':
            enableTOTP();
            break;
            
        case 'setup_sms':
            setupSMS();
            break;
            
        case 'enable_sms':
            enableSMS();
            break;
            
        case 'verify':
            verifyMFACode();
            break;
            
        case 'status':
            getMFAStatus();
            break;
            
        case 'generate_backup_codes':
            generateBackupCodes();
            break;
            
        case 'disable_mfa':
            disableMFA();
            break;
            
        case 'trusted_devices':
            manageTrustedDevices();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("MFA management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Setup TOTP for admin
 */
function setupTOTP() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $adminId = $_SESSION['admin_id'];
    
    try {
        $result = $mfa->setupTOTP($adminId, 'admin');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'secret' => $result['secret'],
                'qr_code_url' => $result['qr_code_url'],
                'backup_codes' => $result['backup_codes']
            ],
            'message' => 'TOTP setup initiated. Scan QR code with your authenticator app.'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Enable TOTP after verification
 */
function enableTOTP() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $validatedData = validateApiRequest([
        'verification_code' => [
            'type' => 'string',
            'required' => true,
            'pattern' => 'numeric',
            'min_length' => 6,
            'max_length' => 6
        ]
    ], 'mfa_enable');
    
    $adminId = $_SESSION['admin_id'];
    $verificationCode = $validatedData['verification_code'];
    
    try {
        $mfa->enableTOTP($adminId, 'admin', $verificationCode);
        
        echo json_encode([
            'success' => true,
            'message' => 'TOTP MFA enabled successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Setup SMS MFA
 */
function setupSMS() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $validatedData = validateApiRequest([
        'phone_number' => [
            'type' => 'string',
            'required' => true,
            'pattern' => 'phone',
            'min_length' => 10,
            'max_length' => 20
        ]
    ], 'mfa_sms_setup');
    
    $adminId = $_SESSION['admin_id'];
    $phoneNumber = $validatedData['phone_number'];
    
    try {
        $result = $mfa->setupSMS($adminId, 'admin', $phoneNumber);
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'SMS verification code sent'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Enable SMS MFA
 */
function enableSMS() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $validatedData = validateApiRequest([
        'verification_code' => [
            'type' => 'string',
            'required' => true,
            'pattern' => 'numeric',
            'min_length' => 6,
            'max_length' => 6
        ]
    ], 'mfa_sms_enable');
    
    $adminId = $_SESSION['admin_id'];
    $verificationCode = $validatedData['verification_code'];
    
    try {
        $result = $mfa->verifyMFA($adminId, 'admin', $verificationCode, 'sms');
        
        if ($result['verified']) {
            // Enable SMS MFA
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE mfa_settings SET sms_enabled = TRUE WHERE user_id = ? AND user_type = 'admin'";
            $stmt = $db->prepare($query);
            $stmt->execute([$adminId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'SMS MFA enabled successfully'
            ]);
        } else {
            throw new Exception('Invalid verification code');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Verify MFA code
 */
function verifyMFACode() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $validatedData = validateApiRequest([
        'code' => [
            'type' => 'string',
            'required' => true,
            'min_length' => 6,
            'max_length' => 8
        ],
        'method' => [
            'type' => 'string',
            'required' => false,
            'custom' => function($value) {
                $allowed = ['auto', 'totp', 'sms', 'backup_code'];
                return in_array($value, $allowed) ? true : 'Invalid MFA method';
            }
        ]
    ], 'mfa_verify');
    
    $adminId = $_SESSION['admin_id'];
    $code = $validatedData['code'];
    $method = $validatedData['method'] ?? 'auto';
    
    try {
        $result = $mfa->verifyMFA($adminId, 'admin', $code, $method);
        
        // Set MFA verified flag in session
        $_SESSION['mfa_verified'] = true;
        $_SESSION['mfa_verified_at'] = time();
        $_SESSION['mfa_method'] = $result['method'];
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'message' => 'MFA verification successful'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Get MFA status
 */
function getMFAStatus() {
    global $mfa;
    
    $adminId = $_SESSION['admin_id'];
    
    try {
        $status = $mfa->getMFAStatus($adminId, 'admin');
        
        // Add session MFA status
        $status['session_verified'] = isset($_SESSION['mfa_verified']) && $_SESSION['mfa_verified'];
        $status['session_method'] = $_SESSION['mfa_method'] ?? null;
        $status['session_verified_at'] = $_SESSION['mfa_verified_at'] ?? null;
        
        echo json_encode([
            'success' => true,
            'data' => $status
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Generate new backup codes
 */
function generateBackupCodes() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    // Require MFA verification for this sensitive operation
    if (!isset($_SESSION['mfa_verified']) || !$_SESSION['mfa_verified']) {
        http_response_code(403);
        echo json_encode(['error' => 'MFA verification required']);
        return;
    }
    
    $adminId = $_SESSION['admin_id'];
    
    try {
        $backupCodes = $mfa->generateBackupCodes($adminId, 'admin');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'backup_codes' => $backupCodes
            ],
            'message' => 'New backup codes generated. Store them securely.'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Disable MFA
 */
function disableMFA() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    // Require MFA verification for this sensitive operation
    if (!isset($_SESSION['mfa_verified']) || !$_SESSION['mfa_verified']) {
        http_response_code(403);
        echo json_encode(['error' => 'MFA verification required']);
        return;
    }
    
    $validatedData = validateApiRequest([
        'method' => [
            'type' => 'string',
            'required' => true,
            'custom' => function($value) {
                $allowed = ['totp', 'sms', 'all'];
                return in_array($value, $allowed) ? true : 'Invalid method';
            }
        ],
        'confirmation' => [
            'type' => 'string',
            'required' => true,
            'custom' => function($value) {
                return $value === 'DISABLE_MFA' ? true : 'Invalid confirmation';
            }
        ]
    ], 'mfa_disable');
    
    $adminId = $_SESSION['admin_id'];
    $method = $validatedData['method'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($method === 'all') {
            $query = "UPDATE mfa_settings SET totp_enabled = FALSE, sms_enabled = FALSE, 
                     totp_secret = NULL, phone_number = NULL, backup_codes = NULL 
                     WHERE user_id = ? AND user_type = 'admin'";
        } elseif ($method === 'totp') {
            $query = "UPDATE mfa_settings SET totp_enabled = FALSE, totp_secret = NULL, backup_codes = NULL 
                     WHERE user_id = ? AND user_type = 'admin'";
        } elseif ($method === 'sms') {
            $query = "UPDATE mfa_settings SET sms_enabled = FALSE, phone_number = NULL 
                     WHERE user_id = ? AND user_type = 'admin'";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute([$adminId]);
        
        // Log MFA disable
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'mfa_disabled', SecurityLogger::LEVEL_WARNING,
            'MFA disabled by admin', ['method' => $method], $adminId);
        
        echo json_encode([
            'success' => true,
            'message' => ucfirst($method) . ' MFA disabled successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Manage trusted devices
 */
function manageTrustedDevices() {
    $subAction = $_GET['sub_action'] ?? 'list';
    $adminId = $_SESSION['admin_id'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        switch ($subAction) {
            case 'list':
                $query = "SELECT id, device_name, ip_address, last_used, expires_at, is_active 
                         FROM mfa_trusted_devices 
                         WHERE user_id = ? AND user_type = 'admin' 
                         ORDER BY last_used DESC";
                $stmt = $db->prepare($query);
                $stmt->execute([$adminId]);
                $devices = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'data' => $devices
                ]);
                break;
                
            case 'revoke':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    return;
                }
                
                $validatedData = validateApiRequest([
                    'device_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => 'uuid'
                    ]
                ], 'device_revoke');
                
                $deviceId = $validatedData['device_id'];
                
                $query = "UPDATE mfa_trusted_devices SET is_active = FALSE 
                         WHERE id = ? AND user_id = ? AND user_type = 'admin'";
                $stmt = $db->prepare($query);
                $stmt->execute([$deviceId, $adminId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Device revoked successfully'
                ]);
                break;
                
            case 'revoke_all':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    return;
                }
                
                $query = "UPDATE mfa_trusted_devices SET is_active = FALSE 
                         WHERE user_id = ? AND user_type = 'admin'";
                $stmt = $db->prepare($query);
                $stmt->execute([$adminId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'All devices revoked successfully'
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid sub-action']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
