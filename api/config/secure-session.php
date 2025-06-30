<?php
/**
 * SECURE SESSION CONFIGURATION
 * Bank-level session security implementation
 */

class SecureSession {
    private static $initialized = false;
    private static $sessionTimeout = 1800; // 30 minutes
    private static $maxIdleTime = 900; // 15 minutes
    
    /**
     * Initialize secure session configuration
     */
    public static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        // Prevent session fixation attacks
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Configure secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.gc_maxlifetime', self::$sessionTimeout);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        
        // Set secure cookie parameters
        session_set_cookie_params([
            'lifetime' => self::$sessionTimeout,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        // Use custom session name
        session_name('AUREUS_SECURE_SESSION');
        
        self::$initialized = true;
    }
    
    /**
     * Start secure session with validation
     */
    public static function start() {
        self::initialize();
        
        session_start();
        
        // Validate session security
        self::validateSession();
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Validate session security and detect hijacking
     */
    private static function validateSession() {
        // Check if session is expired
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > self::$maxIdleTime) {
            self::destroy();
            return;
        }
        
        // Check for session hijacking - IP address validation
        if (isset($_SESSION['ip_address']) && 
            $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            error_log("SECURITY ALERT: Session hijacking attempt detected. IP mismatch.");
            self::destroy();
            return;
        }
        
        // Check for session hijacking - User agent validation
        if (isset($_SESSION['user_agent']) && 
            $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            error_log("SECURITY ALERT: Session hijacking attempt detected. User agent mismatch.");
            self::destroy();
            return;
        }
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    /**
     * Regenerate session ID for privilege escalation
     */
    public static function regenerateOnLogin() {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Destroy session securely
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear session data
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destroy session
            session_destroy();
        }
    }
    
    /**
     * Check if session is valid and not expired
     */
    public static function isValid() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > self::$maxIdleTime) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get session timeout information
     */
    public static function getTimeoutInfo() {
        if (!isset($_SESSION['last_activity'])) {
            return null;
        }
        
        $timeLeft = self::$maxIdleTime - (time() - $_SESSION['last_activity']);
        
        return [
            'time_left' => max(0, $timeLeft),
            'max_idle_time' => self::$maxIdleTime,
            'last_activity' => $_SESSION['last_activity'],
            'expires_at' => $_SESSION['last_activity'] + self::$maxIdleTime
        ];
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Log security events
     */
    private static function logSecurityEvent($event, $details = []) {
        $logData = [
            'timestamp' => date('c'),
            'event' => $event,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        error_log("SECURITY EVENT: " . json_encode($logData));
    }
    
    /**
     * Get session security status
     */
    public static function getSecurityStatus() {
        return [
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'session_id' => session_id(),
            'is_valid' => self::isValid(),
            'timeout_info' => self::getTimeoutInfo(),
            'security_flags' => [
                'httponly' => ini_get('session.cookie_httponly'),
                'secure' => ini_get('session.cookie_secure'),
                'samesite' => ini_get('session.cookie_samesite'),
                'strict_mode' => ini_get('session.use_strict_mode')
            ]
        ];
    }
}

// Auto-initialize when file is included
SecureSession::initialize();
?>
