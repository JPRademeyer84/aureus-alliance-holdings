<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/rate-limiter.php';
require_once '../config/captcha.php';
require_once '../config/input-validator.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session for admin authentication
SecureSession::start();

// Log all requests for debugging
error_log("Admin auth request: " . $_SERVER['REQUEST_METHOD'] . " " . file_get_contents('php://input'));

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create tables silently
    ob_start();
    $database->createTables();
    $database->insertDefaultData();
    ob_end_clean();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Debug logging
    error_log("Parsed input: " . json_encode($input));

    if (!$input) {
        sendErrorResponse('Invalid JSON input', 400);
    }

    if (!isset($input['action'])) {
        sendErrorResponse('Action is required', 400);
    }

    $action = $input['action'];

    if ($action === 'login') {
        // Use centralized validation for login
        $validatedData = validateApiRequest(ValidationRules::adminLogin(), 'admin_login');

        $username = $validatedData['username'];
        $password = $validatedData['password'];

        // Rate limiting check
        $rateLimiter = RateLimiter::getInstance();
        $identifier = RateLimiter::generateIdentifier($username);

        if (!$rateLimiter->isAllowed($identifier, 'admin_login', 5, 900)) {
            $blockedTime = $rateLimiter->getBlockedTime($identifier, 'admin_login');
            $minutes = ceil($blockedTime / 60);
            sendErrorResponse("Too many failed login attempts. Please try again in $minutes minutes.", 429);
        }

        // Check if CAPTCHA is required and validate if provided
        if (SimpleCaptcha::isRequired($identifier, 'admin_login')) {
            $captchaAnswer = $validatedData['captcha_answer'] ?? '';
            $captchaToken = $validatedData['captcha_token'] ?? '';

            if (empty($captchaAnswer) || empty($captchaToken)) {
                sendErrorResponse('CAPTCHA verification required after multiple failed attempts', 400);
            }

            if (!SimpleCaptcha::validate($captchaAnswer, $captchaToken)) {
                $rateLimiter->recordAttempt($identifier, 'admin_login', false);
                sendErrorResponse('Invalid CAPTCHA. Please try again.', 400);
            }
        }

        error_log("Admin login attempt: username=$username");

        // Get admin user with role and status
        $query = "SELECT id, username, password_hash, role, email, full_name, is_active, password_change_required FROM admin_users WHERE username = ? AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            // Record failed attempt
            $rateLimiter->recordAttempt($identifier, 'admin_login', false);
            sendErrorResponse('Invalid credentials', 401);
        }

        // Verify password
        if (password_verify($password, $admin['password_hash'])) {
            // Check if MFA is required
            $mfa = MFASystem::getInstance();
            $mfaRequired = $mfa->isMFARequired($admin['id'], 'admin');
            $mfaEnabled = $mfa->isMFAEnabled($admin['id'], 'admin');

            if ($mfaRequired && $mfaEnabled) {
                // MFA is required - create temporary session
                SecureSession::regenerateOnLogin();

                $_SESSION['admin_id_pending'] = $admin['id'];
                $_SESSION['admin_username_pending'] = $admin['username'];
                $_SESSION['admin_role_pending'] = $admin['role'] ?? 'super_admin';
                $_SESSION['admin_email_pending'] = $admin['email'];
                $_SESSION['admin_full_name_pending'] = $admin['full_name'];
                $_SESSION['mfa_required'] = true;
                $_SESSION['mfa_verified'] = false;

                // Get MFA status for response
                $mfaStatus = $mfa->getMFAStatus($admin['id'], 'admin');

                sendSuccessResponse([
                    'mfa_required' => true,
                    'mfa_methods' => $mfaStatus['methods'],
                    'phone_masked' => $mfaStatus['phone_masked'] ?? null,
                    'backup_codes_remaining' => $mfaStatus['backup_codes_remaining']
                ], 'MFA verification required');

            } else {
                // No MFA required or not set up - complete login
                SecureSession::regenerateOnLogin();

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'super_admin';
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['mfa_verified'] = false; // No MFA required

                // Record successful login (clears failed attempts)
                $rateLimiter->recordAttempt($identifier, 'admin_login', true);

                // Update last activity
                $updateQuery = "UPDATE admin_users SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$admin['id']]);

                sendSuccessResponse([
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'role' => $admin['role'] ?? 'super_admin',
                        'email' => $admin['email'],
                        'full_name' => $admin['full_name'],
                        'password_change_required' => (bool)($admin['password_change_required'] ?? false)
                    ],
                    'session_id' => session_id()
                ], $admin['password_change_required'] ? 'Login successful - Password change required' : 'Login successful');
            }
        } else {
            // Record failed attempt
            $rateLimiter->recordAttempt($identifier, 'admin_login', false);
            sendErrorResponse('Invalid credentials', 401);
        }
    } elseif ($action === 'verify_mfa') {
        // Verify MFA code and complete login
        if (!isset($_SESSION['admin_id_pending']) || !isset($_SESSION['mfa_required'])) {
            sendErrorResponse('No pending MFA verification', 400);
        }

        $validatedData = validateApiRequest([
            'mfa_code' => [
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
            ],
            'trust_device' => [
                'type' => 'boolean',
                'required' => false
            ]
        ], 'mfa_verification');

        $adminId = $_SESSION['admin_id_pending'];
        $mfaCode = $validatedData['mfa_code'];
        $method = $validatedData['method'] ?? 'auto';
        $trustDevice = $validatedData['trust_device'] ?? false;

        try {
            $mfa = MFASystem::getInstance();
            $result = $mfa->verifyMFA($adminId, 'admin', $mfaCode, $method);

            if ($result['verified']) {
                // Get admin data
                $query = "SELECT id, username, password_hash, role, email, full_name, is_active, password_change_required FROM admin_users WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                // Complete login - move pending session to active
                $_SESSION['admin_id'] = $_SESSION['admin_id_pending'];
                $_SESSION['admin_username'] = $_SESSION['admin_username_pending'];
                $_SESSION['admin_role'] = $_SESSION['admin_role_pending'];
                $_SESSION['admin_email'] = $_SESSION['admin_email_pending'];
                $_SESSION['admin_full_name'] = $_SESSION['admin_full_name_pending'];
                $_SESSION['mfa_verified'] = true;
                $_SESSION['mfa_verified_at'] = time();
                $_SESSION['mfa_method'] = $result['method'];

                // Clear pending session data
                unset($_SESSION['admin_id_pending']);
                unset($_SESSION['admin_username_pending']);
                unset($_SESSION['admin_role_pending']);
                unset($_SESSION['admin_email_pending']);
                unset($_SESSION['admin_full_name_pending']);
                unset($_SESSION['mfa_required']);

                // Record successful login
                $rateLimiter->recordAttempt($identifier, 'admin_login', true);

                // Update last activity
                $updateQuery = "UPDATE admin_users SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$adminId]);

                sendSuccessResponse([
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'role' => $admin['role'] ?? 'super_admin',
                        'email' => $admin['email'],
                        'full_name' => $admin['full_name'],
                        'password_change_required' => (bool)($admin['password_change_required'] ?? false)
                    ],
                    'mfa_verified' => true,
                    'mfa_method' => $result['method']
                ], 'MFA verification successful - Login complete');

            } else {
                sendErrorResponse('Invalid MFA code', 401);
            }

        } catch (Exception $e) {
            sendErrorResponse('MFA verification failed: ' . $e->getMessage(), 401);
        }
        // Verify MFA code and complete login
        if (!isset($_SESSION['admin_id_pending']) || !isset($_SESSION['mfa_required'])) {
            sendErrorResponse('No pending MFA verification', 400);
        }

        $validatedData = validateApiRequest([
            'mfa_code' => [
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
        ], 'mfa_verification');

        $adminId = $_SESSION['admin_id_pending'];
        $mfaCode = $validatedData['mfa_code'];
        $method = $validatedData['method'] ?? 'auto';

        try {
            $mfa = MFASystem::getInstance();
            $result = $mfa->verifyMFA($adminId, 'admin', $mfaCode, $method);

            if ($result['verified']) {
                // Get admin data
                $query = "SELECT id, username, password_hash, role, email, full_name, is_active, password_change_required FROM admin_users WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                // Complete login - move pending session to active
                $_SESSION['admin_id'] = $_SESSION['admin_id_pending'];
                $_SESSION['admin_username'] = $_SESSION['admin_username_pending'];
                $_SESSION['admin_role'] = $_SESSION['admin_role_pending'];
                $_SESSION['admin_email'] = $_SESSION['admin_email_pending'];
                $_SESSION['admin_full_name'] = $_SESSION['admin_full_name_pending'];
                $_SESSION['mfa_verified'] = true;
                $_SESSION['mfa_verified_at'] = time();
                $_SESSION['mfa_method'] = $result['method'];

                // Clear pending session data
                unset($_SESSION['admin_id_pending']);
                unset($_SESSION['admin_username_pending']);
                unset($_SESSION['admin_role_pending']);
                unset($_SESSION['admin_email_pending']);
                unset($_SESSION['admin_full_name_pending']);
                unset($_SESSION['mfa_required']);

                // Record successful login
                $rateLimiter->recordAttempt($identifier, 'admin_login', true);

                // Update last activity
                $updateQuery = "UPDATE admin_users SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$adminId]);

                sendSuccessResponse([
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'role' => $admin['role'] ?? 'super_admin',
                        'email' => $admin['email'],
                        'full_name' => $admin['full_name'],
                        'password_change_required' => (bool)($admin['password_change_required'] ?? false)
                    ],
                    'mfa_verified' => true,
                    'mfa_method' => $result['method']
                ], 'MFA verification successful - Login complete');

            } else {
                sendErrorResponse('Invalid MFA code', 401);
            }

        } catch (Exception $e) {
            sendErrorResponse('MFA verification failed: ' . $e->getMessage(), 401);
        }

    } elseif ($action === 'logout') {
        // Securely destroy session
        SecureSession::destroy();

        sendSuccessResponse(null, 'Logout successful');
    } elseif ($action === 'change_password') {
        // Change password (especially for forced password changes)
        if (!isset($_SESSION['admin_id'])) {
            sendErrorResponse('Admin authentication required', 401);
        }

        if (!isset($input['current_password']) || !isset($input['new_password'])) {
            sendErrorResponse('Current password and new password are required', 400);
        }

        $currentPassword = $input['current_password'];
        $newPassword = $input['new_password'];

        // Validate new password strength
        if (strlen($newPassword) < 12) {
            sendErrorResponse('New password must be at least 12 characters long', 400);
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $newPassword)) {
            sendErrorResponse('New password must contain uppercase, lowercase, number, and special character', 400);
        }

        // Get current admin data
        $query = "SELECT password_hash FROM admin_users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
            sendErrorResponse('Current password is incorrect', 401);
        }

        // Update password and clear password_change_required flag
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE admin_users SET password_hash = ?, password_change_required = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$newPasswordHash, $_SESSION['admin_id']]);

        sendSuccessResponse(null, 'Password changed successfully');

    } elseif ($action === 'check') {
        // Check if admin is logged in
        if (isset($_SESSION['admin_id'])) {
            // Get password change requirement status
            $query = "SELECT password_change_required FROM admin_users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['admin_id']]);
            $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

            sendSuccessResponse([
                'admin' => [
                    'id' => $_SESSION['admin_id'],
                    'username' => $_SESSION['admin_username'],
                    'role' => $_SESSION['admin_role'],
                    'email' => $_SESSION['admin_email'],
                    'full_name' => $_SESSION['admin_full_name'],
                    'password_change_required' => (bool)($adminData['password_change_required'] ?? false)
                ]
            ], 'Admin session active');
        } else {
            sendErrorResponse('No active admin session', 401);
        }
    } elseif ($action === 'get_captcha') {
        // Generate CAPTCHA for login form
        $captcha = SimpleCaptcha::generate();
        sendSuccessResponse($captcha, 'CAPTCHA generated');

    } elseif ($action === 'check_captcha_required') {
        // Check if CAPTCHA is required for this user
        $username = $input['username'] ?? '';
        if (empty($username)) {
            sendErrorResponse('Username is required', 400);
        }

        $identifier = RateLimiter::generateIdentifier($username);
        $required = SimpleCaptcha::isRequired($identifier, 'admin_login');

        sendSuccessResponse(['captcha_required' => $required], 'CAPTCHA requirement checked');

    } else {
        sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

// Response functions are now provided by cors.php
?>
