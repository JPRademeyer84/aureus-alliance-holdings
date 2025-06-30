<?php
/**
 * USER MFA MANAGEMENT API
 * User interface for managing multi-factor authentication
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

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User authentication required']);
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
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("User MFA error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Setup TOTP for user
 */
function setupTOTP() {
    global $mfa;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $result = $mfa->setupTOTP($userId, 'user');
        
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
    ], 'user_mfa_enable');
    
    $userId = $_SESSION['user_id'];
    $verificationCode = $validatedData['verification_code'];
    
    try {
        $mfa->enableTOTP($userId, 'user', $verificationCode);
        
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
    ], 'user_mfa_sms_setup');
    
    $userId = $_SESSION['user_id'];
    $phoneNumber = $validatedData['phone_number'];
    
    try {
        $result = $mfa->setupSMS($userId, 'user', $phoneNumber);
        
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
    ], 'user_mfa_sms_enable');
    
    $userId = $_SESSION['user_id'];
    $verificationCode = $validatedData['verification_code'];
    
    try {
        $result = $mfa->verifyMFA($userId, 'user', $verificationCode, 'sms');
        
        if ($result['verified']) {
            // Enable SMS MFA
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "UPDATE mfa_settings SET sms_enabled = TRUE WHERE user_id = ? AND user_type = 'user'";
            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            
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
    ], 'user_mfa_verify');
    
    $userId = $_SESSION['user_id'];
    $code = $validatedData['code'];
    $method = $validatedData['method'] ?? 'auto';
    
    try {
        $result = $mfa->verifyMFA($userId, 'user', $code, $method);
        
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
    
    $userId = $_SESSION['user_id'];
    
    try {
        $status = $mfa->getMFAStatus($userId, 'user');
        
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
    
    // Require password confirmation for this sensitive operation
    $validatedData = validateApiRequest([
        'password' => [
            'type' => 'string',
            'required' => true,
            'min_length' => 1,
            'max_length' => 255
        ]
    ], 'user_backup_codes');
    
    $userId = $_SESSION['user_id'];
    $password = $validatedData['password'];
    
    try {
        // Verify user password
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT password_hash FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid password');
        }
        
        $backupCodes = $mfa->generateBackupCodes($userId, 'user');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'backup_codes' => $backupCodes
            ],
            'message' => 'New backup codes generated. Store them securely.'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
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
    
    $validatedData = validateApiRequest([
        'method' => [
            'type' => 'string',
            'required' => true,
            'custom' => function($value) {
                $allowed = ['totp', 'sms', 'all'];
                return in_array($value, $allowed) ? true : 'Invalid method';
            }
        ],
        'password' => [
            'type' => 'string',
            'required' => true,
            'min_length' => 1,
            'max_length' => 255
        ]
    ], 'user_mfa_disable');
    
    $userId = $_SESSION['user_id'];
    $method = $validatedData['method'];
    $password = $validatedData['password'];
    
    try {
        // Verify user password
        $database = new Database();
        $db = $database->getConnection();
        
        $userQuery = "SELECT password_hash FROM users WHERE id = ?";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid password');
        }
        
        if ($method === 'all') {
            $query = "UPDATE mfa_settings SET totp_enabled = FALSE, sms_enabled = FALSE, 
                     totp_secret = NULL, phone_number = NULL, backup_codes = NULL 
                     WHERE user_id = ? AND user_type = 'user'";
        } elseif ($method === 'totp') {
            $query = "UPDATE mfa_settings SET totp_enabled = FALSE, totp_secret = NULL, backup_codes = NULL 
                     WHERE user_id = ? AND user_type = 'user'";
        } elseif ($method === 'sms') {
            $query = "UPDATE mfa_settings SET sms_enabled = FALSE, phone_number = NULL 
                     WHERE user_id = ? AND user_type = 'user'";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        
        // Log MFA disable
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'user_mfa_disabled', SecurityLogger::LEVEL_WARNING,
            'MFA disabled by user', ['method' => $method], $userId);
        
        echo json_encode([
            'success' => true,
            'message' => ucfirst($method) . ' MFA disabled successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
