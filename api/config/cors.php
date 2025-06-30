<?php
/**
 * SECURE CORS CONFIGURATION
 * Bank-level CORS security with strict origin validation
 */

require_once 'env-loader.php';
require_once 'security-logger.php';
require_once 'tls-security.php';

class SecureCORS {
    private static $allowedOrigins = [];
    private static $initialized = false;
    private static $attackDetection = true;
    private static $suspiciousOrigins = [];
    private static $rateLimiter = null;

    /**
     * Initialize CORS configuration
     */
    private static function initialize() {
        if (self::$initialized) {
            return;
        }

        // Get allowed origins from environment or use secure defaults
        $envOrigins = EnvLoader::get('CORS_ALLOWED_ORIGINS');

        if ($envOrigins) {
            self::$allowedOrigins = explode(',', $envOrigins);
        } else {
            // Secure default origins for development
            self::$allowedOrigins = [
                'http://localhost:5173',
                'http://localhost:5174',
                'http://127.0.0.1:5173',
                'http://127.0.0.1:5174'
            ];

            // Add production origins if in production
            if (Environment::isProduction()) {
                $productionOrigins = [
                    'https://yourdomain.com',
                    'https://www.yourdomain.com'
                ];
                self::$allowedOrigins = array_merge(self::$allowedOrigins, $productionOrigins);
            }
        }

        self::$initialized = true;
    }

    /**
     * Validate origin against whitelist
     */
    public static function validateOrigin($origin) {
        self::initialize();

        if (empty($origin)) {
            return false;
        }

        // Exact match required
        return in_array($origin, self::$allowedOrigins, true);
    }

    /**
     * Set secure CORS headers
     */
    public static function setHeaders() {
        self::initialize();

        // Remove any existing headers to prevent duplicates
        if (!headers_sent()) {
            header_remove('Access-Control-Allow-Origin');
            header_remove('Access-Control-Allow-Methods');
            header_remove('Access-Control-Allow-Headers');
            header_remove('Access-Control-Max-Age');
            header_remove('Access-Control-Allow-Credentials');

            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            // Validate origin
            if (self::validateOrigin($origin)) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Credentials: true");

                // Log successful CORS validation
                logCorsEvent('origin_allowed', SecurityLogger::LEVEL_INFO,
                    "Valid CORS origin allowed", ['origin' => $origin]);
            } else {
                // Log invalid origin attempt
                if (!empty($origin)) {
                    logCorsEvent('origin_blocked', SecurityLogger::LEVEL_WARNING,
                        "Invalid CORS origin blocked",
                        ['origin' => $origin, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                }

                // Don't set CORS headers for invalid origins
                // This will cause the browser to block the request
                return false;
            }

            // Set secure CORS headers
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
            header("Access-Control-Max-Age: 3600");
            header("Content-Type: application/json; charset=UTF-8");

            // Security headers are now handled by TLS security system
            // TLSSecurity::initialize() sets comprehensive security headers
        }

        return true;
    }

    /**
     * Get allowed origins for debugging
     */
    public static function getAllowedOrigins() {
        self::initialize();
        return self::$allowedOrigins;
    }

    /**
     * Enhanced origin validation with attack detection
     */
    public static function validateOriginWithSecurity($origin) {
        self::initialize();

        if (empty($origin)) {
            return false;
        }

        // Check for suspicious patterns
        if (self::$attackDetection && self::detectSuspiciousOrigin($origin)) {
            self::$suspiciousOrigins[] = [
                'origin' => $origin,
                'timestamp' => time(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ];

            logCorsEvent('suspicious_origin_detected', SecurityLogger::LEVEL_CRITICAL,
                "Suspicious CORS origin detected", [
                    'origin' => $origin,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'patterns_matched' => self::getSuspiciousPatterns($origin)
                ]);

            return false;
        }

        // Rate limiting for CORS requests
        if (self::isRateLimited($origin)) {
            logCorsEvent('cors_rate_limited', SecurityLogger::LEVEL_WARNING,
                "CORS request rate limited", ['origin' => $origin]);
            return false;
        }

        // Exact match required
        return in_array($origin, self::$allowedOrigins, true);
    }

    /**
     * Detect suspicious origin patterns
     */
    private static function detectSuspiciousOrigin($origin) {
        $suspiciousPatterns = [
            // IP addresses (potential bypass attempts)
            '/^https?:\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',

            // Suspicious TLDs
            '/\.(tk|ml|ga|cf|bit|ly)($|\/)/i',

            // URL shorteners
            '/(bit\.ly|tinyurl|t\.co|goo\.gl|short\.link)/i',

            // Suspicious subdomains
            '/(admin|api|test|dev|staging|internal|private)\./i',

            // Homograph attacks (similar looking domains)
            '/[а-я]/u', // Cyrillic characters
            '/[αβγδεζηθικλμνξοπρστυφχψω]/u', // Greek characters

            // Suspicious ports
            '/:(?:22|23|25|53|80|110|143|443|993|995|1433|3306|3389|5432|6379|27017)(?:\/|$)/',

            // Data URIs (potential XSS)
            '/^data:/',

            // File protocols
            '/^file:/',

            // Localhost variations (potential SSRF)
            '/(localhost|127\.0\.0\.1|0\.0\.0\.0|::1)/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get matched suspicious patterns for logging
     */
    private static function getSuspiciousPatterns($origin) {
        $patterns = [];
        $suspiciousPatterns = [
            'ip_address' => '/^https?:\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/',
            'suspicious_tld' => '/\.(tk|ml|ga|cf|bit|ly)($|\/)/i',
            'url_shortener' => '/(bit\.ly|tinyurl|t\.co|goo\.gl|short\.link)/i',
            'suspicious_subdomain' => '/(admin|api|test|dev|staging|internal|private)\./i',
            'cyrillic_chars' => '/[а-я]/u',
            'greek_chars' => '/[αβγδεζηθικλμνξοπρστυφχψω]/u',
            'suspicious_port' => '/:(?:22|23|25|53|80|110|143|443|993|995|1433|3306|3389|5432|6379|27017)(?:\/|$)/',
            'data_uri' => '/^data:/',
            'file_protocol' => '/^file:/',
            'localhost_variant' => '/(localhost|127\.0\.0\.1|0\.0\.0\.0|::1)/i'
        ];

        foreach ($suspiciousPatterns as $name => $pattern) {
            if (preg_match($pattern, $origin)) {
                $patterns[] = $name;
            }
        }

        return $patterns;
    }

    /**
     * Rate limiting for CORS requests
     */
    private static function isRateLimited($origin) {
        // Simple in-memory rate limiting (in production, use Redis)
        $key = 'cors_' . md5($origin . $_SERVER['REMOTE_ADDR']);

        if (!isset($_SESSION['cors_rate_limit'])) {
            $_SESSION['cors_rate_limit'] = [];
        }

        $now = time();
        $window = 60; // 1 minute window
        $maxRequests = 100; // Max 100 CORS requests per minute per origin+IP

        // Clean old entries
        $_SESSION['cors_rate_limit'] = array_filter($_SESSION['cors_rate_limit'], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        // Count requests for this key
        $requests = array_filter($_SESSION['cors_rate_limit'], function($timestamp, $k) use ($key) {
            return strpos($k, $key) === 0;
        }, ARRAY_FILTER_USE_BOTH);

        if (count($requests) >= $maxRequests) {
            return true;
        }

        // Record this request
        $_SESSION['cors_rate_limit'][$key . '_' . $now] = $now;

        return false;
    }

    /**
     * Get CORS security statistics
     */
    public static function getSecurityStats() {
        return [
            'allowed_origins' => count(self::$allowedOrigins),
            'suspicious_origins_detected' => count(self::$suspiciousOrigins),
            'attack_detection_enabled' => self::$attackDetection,
            'recent_suspicious_origins' => array_slice(self::$suspiciousOrigins, -10)
        ];
    }
}

// Legacy function for backward compatibility
if (!function_exists('setCorsHeaders')) {
    function setCorsHeaders() {
        return SecureCORS::setHeaders();
    }
}

if (!function_exists('handlePreflight')) {
    function handlePreflight() {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        // Validate origin before setting headers
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (SecureCORS::validateOrigin($origin)) {
            SecureCORS::setHeaders();
            http_response_code(200);
        } else {
            // Block invalid preflight requests
            logCorsEvent('preflight_blocked', SecurityLogger::LEVEL_WARNING,
                "Blocked preflight request from invalid origin", ['origin' => $origin]);
            http_response_code(403);
            echo json_encode(['error' => 'Origin not allowed']);
        }
        exit();
    }
    }
}

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($data, $status_code = 200) {
    // Validate origin before sending response
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (!empty($origin) && !SecureCORS::validateOrigin($origin)) {
        logCorsEvent('response_blocked', SecurityLogger::LEVEL_WARNING,
            "Blocked response to invalid origin", ['origin' => $origin]);
        http_response_code(403);
        echo json_encode(['error' => 'Origin not allowed']);
        exit();
    }

    SecureCORS::setHeaders();
    http_response_code($status_code);
    echo json_encode($data);
    exit();
    }
}

if (!function_exists('sendErrorResponse')) {
    function sendErrorResponse($message, $status_code = 500) {
        sendJsonResponse(['error' => $message], $status_code);
    }
}

if (!function_exists('sendSuccessResponse')) {
    function sendSuccessResponse($data, $message = 'Success') {
        sendJsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
    }
}

/**
 * Log CORS security events
 */
if (!function_exists('logCorsEvent')) {
    function logCorsEvent($eventType, $level, $message, $data = []) {
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('cors', $eventType, $level, $message, $data);
        } else {
            // Fallback logging
            error_log("CORS Security Event [$eventType]: $message - " . json_encode($data));
        }
    }
}

/**
 * Validate origin with enhanced security
 */
if (!function_exists('validateOriginWithSecurity')) {
    function validateOriginWithSecurity($origin) {
        return SecureCORS::validateOriginWithSecurity($origin);
    }
}
?>
