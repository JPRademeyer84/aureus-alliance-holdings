<?php
/**
 * API SECURITY MIDDLEWARE
 * Comprehensive API security middleware with authentication, rate limiting, and abuse detection
 */

require_once 'enterprise-api-security.php';
require_once 'enhanced-validation-middleware.php';
require_once 'permission-middleware.php';

class APISecurityMiddleware {
    private static $apiSecurity = null;
    private static $requestStartTime = null;
    private static $requestId = null;
    
    /**
     * Initialize API security
     */
    private static function init() {
        if (self::$apiSecurity === null) {
            self::$apiSecurity = EnterpriseAPISecurity::getInstance();
            self::$requestStartTime = microtime(true);
            self::$requestId = bin2hex(random_bytes(16));
        }
    }
    
    /**
     * Secure API endpoint with comprehensive protection
     */
    public static function secureEndpoint($options = []) {
        self::init();
        
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        try {
            // Step 1: Basic security headers and HTTPS enforcement
            self::setSecurityHeaders();
            self::enforceHTTPS();
            
            // Step 2: Authentication
            $authResult = self::authenticateRequest($options);
            
            // Step 3: Rate limiting
            $rateLimitResult = self::checkRateLimits($authResult, $endpoint);
            if (!$rateLimitResult['allowed']) {
                self::sendRateLimitResponse($rateLimitResult);
            }
            
            // Step 4: Authorization (if permissions specified)
            if (isset($options['required_permissions'])) {
                self::checkPermissions($authResult, $options['required_permissions']);
            }
            
            // Step 5: Input validation (if validation rules specified)
            $validatedData = [];
            if (isset($options['validation_rules'])) {
                $validatedData = self::validateInput($options['validation_rules'], $options);
            }
            
            // Step 6: Abuse detection
            $requestData = self::getRequestData();
            $abuseResult = self::detectAbuse($authResult, $endpoint, $requestData);
            if ($abuseResult['abuse_detected'] && $abuseResult['abuse_level'] >= EnterpriseAPISecurity::ABUSE_LEVEL_HIGH) {
                self::sendAbuseDetectedResponse($abuseResult);
            }
            
            // Step 7: Set rate limit headers
            self::setRateLimitHeaders($rateLimitResult);
            
            // Store authentication and validation results for the request
            $_REQUEST['_api_auth'] = $authResult;
            $_REQUEST['_api_validated_data'] = $validatedData;
            $_REQUEST['_api_request_id'] = self::$requestId;
            
            return [
                'authenticated' => true,
                'auth_data' => $authResult,
                'validated_data' => $validatedData,
                'request_id' => self::$requestId
            ];
            
        } catch (Exception $e) {
            self::logAPIRequest($authResult ?? [], $endpoint, $method, 500, null, null, false, false, $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal server error',
                'request_id' => self::$requestId,
                'timestamp' => date('c')
            ]);
            exit;
        }
    }
    
    /**
     * Log API request completion
     */
    public static function logRequestCompletion($statusCode = 200, $responseData = null, $errorMessage = null) {
        if (!self::$apiSecurity || !self::$requestStartTime) {
            return;
        }
        
        $responseTime = round((microtime(true) - self::$requestStartTime) * 1000); // milliseconds
        $requestSize = self::getRequestSize();
        $responseSize = $responseData ? strlen(json_encode($responseData)) : 0;
        
        $authData = $_REQUEST['_api_auth'] ?? [];
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        self::logAPIRequest(
            $authData,
            $endpoint,
            $method,
            $statusCode,
            $responseTime,
            $requestSize,
            false, // rate limited (would be set earlier)
            false, // abuse detected (would be set earlier)
            $errorMessage
        );
    }
    
    /**
     * Authenticate API request
     */
    private static function authenticateRequest($options) {
        $authRequired = $options['auth_required'] ?? true;
        $allowedAuthTypes = $options['auth_types'] ?? ['session', 'api_key'];
        
        if (!$authRequired) {
            return [
                'authenticated' => false,
                'auth_type' => 'none',
                'user_id' => null,
                'user_type' => 'anonymous',
                'tier' => EnterpriseAPISecurity::TIER_FREE
            ];
        }
        
        // Try API key authentication first
        if (in_array('api_key', $allowedAuthTypes)) {
            $apiKey = self::extractAPIKey();
            if ($apiKey) {
                $keyValidation = self::$apiSecurity->validateAPIKey($apiKey);
                if ($keyValidation) {
                    return [
                        'authenticated' => true,
                        'auth_type' => 'api_key',
                        'api_key_id' => $keyValidation['key_id'],
                        'user_id' => $keyValidation['user_id'],
                        'user_type' => $keyValidation['user_type'],
                        'tier' => $keyValidation['tier'],
                        'permissions' => $keyValidation['permissions']
                    ];
                }
            }
        }
        
        // Try session authentication
        if (in_array('session', $allowedAuthTypes)) {
            if (!isset($_SESSION)) {
                session_start();
            }
            
            if (isset($_SESSION['admin_id'])) {
                return [
                    'authenticated' => true,
                    'auth_type' => 'session',
                    'user_id' => $_SESSION['admin_id'],
                    'user_type' => 'admin',
                    'tier' => EnterpriseAPISecurity::TIER_ENTERPRISE
                ];
            } elseif (isset($_SESSION['user_id'])) {
                return [
                    'authenticated' => true,
                    'auth_type' => 'session',
                    'user_id' => $_SESSION['user_id'],
                    'user_type' => 'user',
                    'tier' => EnterpriseAPISecurity::TIER_BASIC
                ];
            }
        }
        
        // Authentication failed
        http_response_code(401);
        echo json_encode([
            'error' => 'Authentication required',
            'supported_auth_types' => $allowedAuthTypes,
            'request_id' => self::$requestId,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    /**
     * Check rate limits
     */
    private static function checkRateLimits($authResult, $endpoint) {
        $identifier = self::getRateLimitIdentifier($authResult);
        $identifierType = self::getRateLimitIdentifierType($authResult);
        $tier = $authResult['tier'] ?? EnterpriseAPISecurity::TIER_FREE;
        
        return self::$apiSecurity->checkRateLimit($identifier, $identifierType, $endpoint, $tier);
    }
    
    /**
     * Check permissions
     */
    private static function checkPermissions($authResult, $requiredPermissions) {
        if (!$authResult['authenticated']) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Authentication required for this endpoint',
                'request_id' => self::$requestId
            ]);
            exit;
        }
        
        $userId = $authResult['user_id'];
        $userType = $authResult['user_type'];
        
        foreach ($requiredPermissions as $permission) {
            if (!checkPermission($userId, $userType, $permission)) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Insufficient permissions',
                    'required_permission' => $permission,
                    'request_id' => self::$requestId
                ]);
                exit;
            }
        }
    }
    
    /**
     * Validate input
     */
    private static function validateInput($validationRules, $options) {
        try {
            return validateEnhancedRequest($validationRules, $options);
        } catch (Exception $e) {
            // Enhanced validation middleware will handle the response
            exit;
        }
    }
    
    /**
     * Detect abuse
     */
    private static function detectAbuse($authResult, $endpoint, $requestData) {
        $identifier = self::getRateLimitIdentifier($authResult);
        $identifierType = self::getRateLimitIdentifierType($authResult);
        
        return self::$apiSecurity->detectAbuse($identifier, $identifierType, $endpoint, $requestData);
    }
    
    /**
     * Helper methods
     */
    
    private static function setSecurityHeaders() {
        if (headers_sent()) {
            return;
        }
        
        // API security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-API-Version: 1.0');
        header('X-Security-Level: Enterprise');
        header('X-Request-ID: ' . self::$requestId);
        
        // CORS headers (if needed)
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            setCorsHeaders();
        }
    }
    
    private static function enforceHTTPS() {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            if ($_ENV['FORCE_HTTPS'] ?? 'false' === 'true') {
                http_response_code(426);
                echo json_encode([
                    'error' => 'HTTPS required',
                    'upgrade_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]);
                exit;
            }
        }
    }
    
    private static function extractAPIKey() {
        // Check Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        // Check X-API-Key header
        $apiKeyHeader = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apiKeyHeader)) {
            return $apiKeyHeader;
        }
        
        // Check query parameter
        return $_GET['api_key'] ?? null;
    }
    
    private static function getRateLimitIdentifier($authResult) {
        if ($authResult['authenticated']) {
            if ($authResult['auth_type'] === 'api_key') {
                return $authResult['api_key_id'];
            } else {
                return $authResult['user_id'];
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private static function getRateLimitIdentifierType($authResult) {
        if ($authResult['authenticated']) {
            if ($authResult['auth_type'] === 'api_key') {
                return 'api_key';
            } else {
                return 'user';
            }
        }
        
        return 'ip';
    }
    
    private static function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        switch ($method) {
            case 'GET':
                return $_GET;
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (strpos($contentType, 'application/json') !== false) {
                    return json_decode(file_get_contents('php://input'), true) ?: [];
                }
                return $_POST;
            default:
                return [];
        }
    }
    
    private static function getRequestSize() {
        $headers = getallheaders();
        $contentLength = $headers['Content-Length'] ?? $_SERVER['CONTENT_LENGTH'] ?? 0;
        return (int)$contentLength;
    }
    
    private static function setRateLimitHeaders($rateLimitResult) {
        if (headers_sent()) {
            return;
        }
        
        header('X-RateLimit-Remaining: ' . ($rateLimitResult['requests_remaining'] ?? 0));
        
        if (isset($rateLimitResult['blocked_until'])) {
            header('X-RateLimit-Reset: ' . strtotime($rateLimitResult['blocked_until']));
        }
    }
    
    private static function sendRateLimitResponse($rateLimitResult) {
        http_response_code(429);
        
        $response = [
            'error' => 'Rate limit exceeded',
            'reason' => $rateLimitResult['reason'] ?? 'rate_limited',
            'requests_remaining' => 0,
            'request_id' => self::$requestId,
            'timestamp' => date('c')
        ];
        
        if (isset($rateLimitResult['blocked_until'])) {
            $response['retry_after'] = $rateLimitResult['blocked_until'];
        }
        
        echo json_encode($response);
        exit;
    }
    
    private static function sendAbuseDetectedResponse($abuseResult) {
        http_response_code(429);
        
        echo json_encode([
            'error' => 'Abuse detected',
            'abuse_level' => $abuseResult['abuse_level'],
            'patterns_detected' => count($abuseResult['patterns']),
            'request_id' => self::$requestId,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    private static function logAPIRequest($authData, $endpoint, $method, $statusCode, $responseTime, $requestSize, $rateLimited = false, $abuseDetected = false, $errorMessage = null) {
        $apiKeyId = $authData['api_key_id'] ?? null;
        $userId = $authData['user_id'] ?? null;
        $userType = $authData['user_type'] ?? 'anonymous';
        $responseSize = 0; // Will be calculated when response is sent
        
        self::$apiSecurity->logAPIRequest(
            self::$requestId,
            $apiKeyId,
            $userId,
            $userType,
            $endpoint,
            $method,
            $statusCode,
            $responseTime,
            $requestSize,
            $responseSize,
            $rateLimited,
            $abuseDetected,
            $errorMessage
        );
    }
}

// Convenience functions
function secureAPIEndpoint($options = []) {
    return APISecurityMiddleware::secureEndpoint($options);
}

function logAPICompletion($statusCode = 200, $responseData = null, $errorMessage = null) {
    return APISecurityMiddleware::logRequestCompletion($statusCode, $responseData, $errorMessage);
}
?>
