<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/rate-limiter.php';
require_once '../config/captcha.php';
require_once '../config/input-validator.php';
require_once '../config/mfa-system.php';

SecureSession::start();
handlePreflight();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create users table if it doesn't exist
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $db->exec($createUsersTable);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        sendErrorResponse('Only POST method allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['action'])) {
        sendErrorResponse('Action is required', 400);
    }

    $action = $input['action'];

    if ($action === 'login') {
        // Use centralized validation for login
        $validatedData = validateApiRequest([
            'email' => [
                'type' => 'email',
                'required' => true,
                'max_length' => 255,
                'sanitize' => ['trim', 'lowercase']
            ],
            'password' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 1,
                'max_length' => 255
            ],
            'captcha_answer' => [
                'type' => 'string',
                'required' => false,
                'max_length' => 10
            ],
            'captcha_token' => [
                'type' => 'string',
                'required' => false,
                'max_length' => 255
            ]
        ], 'user_login');

        $email = $validatedData['email'];
        $password = $validatedData['password'];

        // Rate limiting check
        $rateLimiter = RateLimiter::getInstance();
        $identifier = RateLimiter::generateIdentifier($email);

        if (!$rateLimiter->isAllowed($identifier, 'user_login', 5, 900)) {
            $blockedTime = $rateLimiter->getBlockedTime($identifier, 'user_login');
            $minutes = ceil($blockedTime / 60);
            sendErrorResponse("Too many failed login attempts. Please try again in $minutes minutes.", 429);
        }

        // Check if CAPTCHA is required and validate if provided
        if (SimpleCaptcha::isRequired($identifier, 'user_login')) {
            $captchaAnswer = $validatedData['captcha_answer'] ?? '';
            $captchaToken = $validatedData['captcha_token'] ?? '';

            if (empty($captchaAnswer) || empty($captchaToken)) {
                sendErrorResponse('CAPTCHA verification required after multiple failed attempts', 400);
            }

            if (!SimpleCaptcha::validate($captchaAnswer, $captchaToken)) {
                $rateLimiter->recordAttempt($identifier, 'user_login', false);
                sendErrorResponse('Invalid CAPTCHA. Please try again.', 400);
            }
        }

        // Get user
        $query = "SELECT id, username, email, password_hash, created_at FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Record failed attempt
            $rateLimiter->recordAttempt($identifier, 'user_login', false);
            sendErrorResponse('Invalid credentials', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            // Record failed attempt
            $rateLimiter->recordAttempt($identifier, 'user_login', false);
            sendErrorResponse('Invalid credentials', 401);
        }

        // Record successful login (clears failed attempts)
        $rateLimiter->recordAttempt($identifier, 'user_login', true);

        // Regenerate session ID for security
        SecureSession::regenerateOnLogin();

        // Set session variables for PHP session-based authentication
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_username'] = $user['username'];

        // Remove password hash from response
        unset($user['password_hash']);

        sendSuccessResponse(['user' => $user], 'Login successful');

    } elseif ($action === 'register') {
        // Use centralized validation for registration
        $validatedData = validateApiRequest([
            'username' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 3,
                'max_length' => 30,
                'pattern' => 'username',
                'sanitize' => ['trim', 'lowercase']
            ],
            'email' => [
                'type' => 'email',
                'required' => true,
                'max_length' => 255,
                'sanitize' => ['trim', 'lowercase']
            ],
            'password' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 8,
                'max_length' => 128,
                'pattern' => 'password'
            ]
        ], 'user_registration');

        $username = $validatedData['username'];
        $email = $validatedData['email'];
        $password = $validatedData['password'];

        // Check if user already exists
        $checkQuery = "SELECT id FROM users WHERE email = ? OR username = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$email, $username]);
        
        if ($checkStmt->fetch()) {
            sendErrorResponse('User with this email or username already exists', 409);
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $insertQuery = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        
        if ($insertStmt->execute([$username, $email, $passwordHash])) {
            $userId = $db->lastInsertId();

            // Get the created user
            $getUserQuery = "SELECT id, username, email, created_at FROM users WHERE id = ?";
            $getUserStmt = $db->prepare($getUserQuery);
            $getUserStmt->execute([$userId]);
            $newUser = $getUserStmt->fetch(PDO::FETCH_ASSOC);

            // Regenerate session ID for security
            SecureSession::regenerateOnLogin();

            // Set session variables for the newly registered user
            $_SESSION['user_id'] = $newUser['id'];
            $_SESSION['user_email'] = $newUser['email'];
            $_SESSION['user_username'] = $newUser['username'];

            sendSuccessResponse(['user' => $newUser], 'Registration successful');
        } else {
            sendErrorResponse('Failed to create user', 500);
        }

    } elseif ($action === 'logout') {
        // Securely destroy session
        SecureSession::destroy();
        sendSuccessResponse([], 'Logout successful');

    } else {
        sendErrorResponse('Invalid action', 400);
    }

} catch(PDOException $exception) {
    error_log("Database error in users/auth.php: " . $exception->getMessage());
    sendErrorResponse('Database error: ' . $exception->getMessage(), 500);
} catch(Exception $exception) {
    error_log("General error in users/auth.php: " . $exception->getMessage());
    sendErrorResponse('Server error: ' . $exception->getMessage(), 500);
}
?>
