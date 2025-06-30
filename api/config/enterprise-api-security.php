<?php
/**
 * ENTERPRISE API SECURITY SYSTEM
 * Comprehensive API security with rate limiting, authentication, and abuse prevention
 */

require_once 'security-logger.php';
require_once 'enterprise-rbac.php';

class EnterpriseAPISecurity {
    private static $instance = null;
    private $db;
    
    // Rate limiting tiers
    const TIER_FREE = 'free';
    const TIER_BASIC = 'basic';
    const TIER_PREMIUM = 'premium';
    const TIER_ENTERPRISE = 'enterprise';
    
    // Authentication types
    const AUTH_SESSION = 'session';
    const AUTH_API_KEY = 'api_key';
    const AUTH_JWT = 'jwt';
    const AUTH_OAUTH = 'oauth';
    
    // Abuse detection levels
    const ABUSE_LEVEL_LOW = 1;
    const ABUSE_LEVEL_MEDIUM = 2;
    const ABUSE_LEVEL_HIGH = 3;
    const ABUSE_LEVEL_CRITICAL = 4;
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeAPISecurityTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize API security tables
     */
    private function initializeAPISecurityTables() {
        $tables = [
            // API keys management
            "CREATE TABLE IF NOT EXISTS api_keys (
                id VARCHAR(36) PRIMARY KEY,
                key_id VARCHAR(100) NOT NULL UNIQUE,
                key_hash VARCHAR(255) NOT NULL,
                key_name VARCHAR(100) NOT NULL,
                user_id VARCHAR(36),
                user_type ENUM('admin', 'user', 'service') NOT NULL,
                tier ENUM('free', 'basic', 'premium', 'enterprise') NOT NULL DEFAULT 'free',
                permissions JSON,
                rate_limit_override JSON,
                allowed_ips JSON,
                allowed_origins JSON,
                expires_at TIMESTAMP NULL,
                last_used_at TIMESTAMP NULL,
                usage_count BIGINT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key_id (key_id),
                INDEX idx_user_id (user_id),
                INDEX idx_tier (tier),
                INDEX idx_expires_at (expires_at)
            )",
            
            // API rate limiting
            "CREATE TABLE IF NOT EXISTS api_rate_limits (
                id VARCHAR(36) PRIMARY KEY,
                identifier VARCHAR(100) NOT NULL,
                identifier_type ENUM('ip', 'api_key', 'user', 'session') NOT NULL,
                endpoint_pattern VARCHAR(200) NOT NULL,
                request_count INT DEFAULT 0,
                window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                window_duration_minutes INT DEFAULT 60,
                limit_per_window INT DEFAULT 1000,
                burst_limit INT DEFAULT 100,
                burst_window_seconds INT DEFAULT 60,
                burst_count INT DEFAULT 0,
                burst_window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                blocked_until TIMESTAMP NULL,
                violation_count INT DEFAULT 0,
                last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_rate_limit (identifier, identifier_type, endpoint_pattern),
                INDEX idx_identifier (identifier, identifier_type),
                INDEX idx_endpoint_pattern (endpoint_pattern),
                INDEX idx_blocked_until (blocked_until),
                INDEX idx_window_start (window_start)
            )",
            
            // API request logging
            "CREATE TABLE IF NOT EXISTS api_request_log (
                id VARCHAR(36) PRIMARY KEY,
                request_id VARCHAR(100) NOT NULL,
                api_key_id VARCHAR(100),
                user_id VARCHAR(36),
                user_type ENUM('admin', 'user', 'anonymous') DEFAULT 'anonymous',
                endpoint VARCHAR(200) NOT NULL,
                method VARCHAR(10) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                request_size_bytes INT,
                response_size_bytes INT,
                response_time_ms INT,
                status_code INT,
                error_message TEXT,
                rate_limited BOOLEAN DEFAULT FALSE,
                abuse_detected BOOLEAN DEFAULT FALSE,
                request_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_api_key_id (api_key_id),
                INDEX idx_user_id (user_id),
                INDEX idx_endpoint (endpoint),
                INDEX idx_status_code (status_code),
                INDEX idx_request_timestamp (request_timestamp),
                INDEX idx_rate_limited (rate_limited),
                INDEX idx_abuse_detected (abuse_detected)
            )",
            
            // API abuse detection
            "CREATE TABLE IF NOT EXISTS api_abuse_detection (
                id VARCHAR(36) PRIMARY KEY,
                identifier VARCHAR(100) NOT NULL,
                identifier_type ENUM('ip', 'api_key', 'user') NOT NULL,
                abuse_type VARCHAR(50) NOT NULL,
                abuse_level TINYINT NOT NULL,
                abuse_patterns JSON,
                detection_details JSON,
                first_detected TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_detected TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                occurrence_count INT DEFAULT 1,
                auto_blocked BOOLEAN DEFAULT FALSE,
                manual_review_required BOOLEAN DEFAULT FALSE,
                resolved BOOLEAN DEFAULT FALSE,
                resolved_by VARCHAR(36),
                resolved_at TIMESTAMP NULL,
                INDEX idx_identifier (identifier, identifier_type),
                INDEX idx_abuse_type (abuse_type),
                INDEX idx_abuse_level (abuse_level),
                INDEX idx_auto_blocked (auto_blocked),
                INDEX idx_resolved (resolved)
            )",
            
            // API endpoint configuration
            "CREATE TABLE IF NOT EXISTS api_endpoint_config (
                id VARCHAR(36) PRIMARY KEY,
                endpoint_pattern VARCHAR(200) NOT NULL UNIQUE,
                endpoint_name VARCHAR(100) NOT NULL,
                authentication_required BOOLEAN DEFAULT TRUE,
                authentication_types JSON,
                rate_limit_tier JSON,
                required_permissions JSON,
                deprecated BOOLEAN DEFAULT FALSE,
                deprecation_date TIMESTAMP NULL,
                replacement_endpoint VARCHAR(200),
                security_level ENUM('public', 'protected', 'private', 'internal') DEFAULT 'protected',
                monitoring_enabled BOOLEAN DEFAULT TRUE,
                cache_ttl_seconds INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_endpoint_pattern (endpoint_pattern),
                INDEX idx_security_level (security_level),
                INDEX idx_deprecated (deprecated)
            )",
            
            // API usage analytics
            "CREATE TABLE IF NOT EXISTS api_usage_analytics (
                id VARCHAR(36) PRIMARY KEY,
                date_hour TIMESTAMP NOT NULL,
                endpoint_pattern VARCHAR(200) NOT NULL,
                api_key_id VARCHAR(100),
                user_type ENUM('admin', 'user', 'anonymous') DEFAULT 'anonymous',
                request_count INT DEFAULT 0,
                error_count INT DEFAULT 0,
                avg_response_time_ms DECIMAL(10,2),
                total_bytes_transferred BIGINT DEFAULT 0,
                unique_ips INT DEFAULT 0,
                rate_limited_requests INT DEFAULT 0,
                abuse_detected_requests INT DEFAULT 0,
                UNIQUE KEY unique_analytics (date_hour, endpoint_pattern, api_key_id, user_type),
                INDEX idx_date_hour (date_hour),
                INDEX idx_endpoint_pattern (endpoint_pattern),
                INDEX idx_api_key_id (api_key_id)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create API security table: " . $e->getMessage());
            }
        }
        
        $this->initializeDefaultEndpoints();
    }
    
    /**
     * Initialize default endpoint configurations
     */
    private function initializeDefaultEndpoints() {
        // Check if endpoints already configured
        $query = "SELECT COUNT(*) FROM api_endpoint_config";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Already initialized
        }
        
        $defaultEndpoints = [
            // Public endpoints
            ['/api/auth/login', 'User Login', false, 'public', ['session'], ['free' => 10, 'basic' => 20]],
            ['/api/auth/register', 'User Registration', false, 'public', ['session'], ['free' => 5, 'basic' => 10]],
            ['/api/auth/forgot-password', 'Password Reset', false, 'public', ['session'], ['free' => 3, 'basic' => 5]],
            
            // Protected user endpoints
            ['/api/users/profile', 'User Profile', true, 'protected', ['session', 'api_key'], ['free' => 100, 'basic' => 500]],
            ['/api/users/kyc/*', 'KYC Operations', true, 'protected', ['session'], ['free' => 50, 'basic' => 200]],
            ['/api/users/wallet/*', 'Wallet Operations', true, 'protected', ['session'], ['free' => 200, 'basic' => 1000]],
            
            // Private admin endpoints
            ['/api/admin/*', 'Admin Operations', true, 'private', ['session'], ['enterprise' => 10000]],
            ['/api/security/*', 'Security Operations', true, 'private', ['session'], ['enterprise' => 5000]],
            
            // Internal system endpoints
            ['/api/system/*', 'System Operations', true, 'internal', ['api_key'], ['enterprise' => 50000]]
        ];
        
        foreach ($defaultEndpoints as $endpoint) {
            $this->createEndpointConfig($endpoint[0], $endpoint[1], $endpoint[2], $endpoint[3], $endpoint[4], $endpoint[5]);
        }
    }
    
    /**
     * Create endpoint configuration
     */
    public function createEndpointConfig($pattern, $name, $authRequired, $securityLevel, $authTypes, $rateLimits) {
        $configId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO api_endpoint_config (
            id, endpoint_pattern, endpoint_name, authentication_required,
            authentication_types, rate_limit_tier, security_level
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $configId, $pattern, $name, $authRequired,
            json_encode($authTypes), json_encode($rateLimits), $securityLevel
        ]);
    }
    
    /**
     * Generate API key
     */
    public function generateAPIKey($userId, $userType, $keyName, $tier = self::TIER_FREE, $permissions = [], $expiresAt = null) {
        $keyId = 'ak_' . bin2hex(random_bytes(16));
        $keySecret = bin2hex(random_bytes(32));
        $keyHash = password_hash($keySecret, PASSWORD_ARGON2ID);
        
        $apiKeyId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO api_keys (
            id, key_id, key_hash, key_name, user_id, user_type, tier,
            permissions, expires_at, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $apiKeyId, $keyId, $keyHash, $keyName, $userId, $userType,
            $tier, json_encode($permissions), $expiresAt, $_SESSION['admin_id'] ?? $userId
        ]);
        
        if (!$success) {
            throw new Exception('Failed to create API key');
        }
        
        // Log API key creation
        $this->logAPIEvent('api_key_created', $keyId, [
            'user_id' => $userId,
            'user_type' => $userType,
            'tier' => $tier,
            'key_name' => $keyName
        ]);
        
        return [
            'key_id' => $keyId,
            'key_secret' => $keySecret,
            'full_key' => $keyId . '.' . $keySecret,
            'tier' => $tier,
            'expires_at' => $expiresAt
        ];
    }
    
    /**
     * Validate API key
     */
    public function validateAPIKey($apiKey) {
        if (strpos($apiKey, '.') === false) {
            return false;
        }
        
        list($keyId, $keySecret) = explode('.', $apiKey, 2);
        
        $query = "SELECT id, key_hash, user_id, user_type, tier, permissions,
                         allowed_ips, allowed_origins, expires_at, is_active
                  FROM api_keys 
                  WHERE key_id = ? AND is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$keyId]);
        $keyData = $stmt->fetch();
        
        if (!$keyData) {
            return false;
        }
        
        // Check expiration
        if ($keyData['expires_at'] && strtotime($keyData['expires_at']) < time()) {
            return false;
        }
        
        // Verify key secret
        if (!password_verify($keySecret, $keyData['key_hash'])) {
            return false;
        }
        
        // Check IP restrictions
        if ($keyData['allowed_ips']) {
            $allowedIPs = json_decode($keyData['allowed_ips'], true);
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!in_array($clientIP, $allowedIPs)) {
                return false;
            }
        }
        
        // Check origin restrictions
        if ($keyData['allowed_origins']) {
            $allowedOrigins = json_decode($keyData['allowed_origins'], true);
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (!in_array($origin, $allowedOrigins)) {
                return false;
            }
        }
        
        // Update last used
        $this->updateAPIKeyUsage($keyId);
        
        return [
            'key_id' => $keyId,
            'user_id' => $keyData['user_id'],
            'user_type' => $keyData['user_type'],
            'tier' => $keyData['tier'],
            'permissions' => json_decode($keyData['permissions'], true) ?: []
        ];
    }
    
    /**
     * Check rate limits
     */
    public function checkRateLimit($identifier, $identifierType, $endpoint, $tier = self::TIER_FREE) {
        // Get endpoint configuration
        $endpointConfig = $this->getEndpointConfig($endpoint);
        if (!$endpointConfig) {
            // Default limits for unconfigured endpoints
            $limits = $this->getDefaultRateLimits($tier);
        } else {
            $rateLimitTier = json_decode($endpointConfig['rate_limit_tier'], true);
            $limits = $rateLimitTier[$tier] ?? $this->getDefaultRateLimits($tier);
        }
        
        $windowMinutes = 60;
        $burstWindowSeconds = 60;
        
        // Get or create rate limit record
        $query = "SELECT * FROM api_rate_limits 
                  WHERE identifier = ? AND identifier_type = ? AND endpoint_pattern = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $identifierType, $this->getEndpointPattern($endpoint)]);
        $rateLimit = $stmt->fetch();
        
        $now = time();
        
        if (!$rateLimit) {
            // Create new rate limit record
            $this->createRateLimitRecord($identifier, $identifierType, $endpoint, $limits);
            return ['allowed' => true, 'requests_remaining' => $limits - 1];
        }
        
        // Check if currently blocked
        if ($rateLimit['blocked_until'] && strtotime($rateLimit['blocked_until']) > $now) {
            return [
                'allowed' => false,
                'reason' => 'rate_limited',
                'blocked_until' => $rateLimit['blocked_until'],
                'requests_remaining' => 0
            ];
        }
        
        // Check window expiration
        $windowStart = strtotime($rateLimit['window_start']);
        if (($now - $windowStart) > ($windowMinutes * 60)) {
            // Reset window
            $this->resetRateLimitWindow($rateLimit['id'], $limits);
            return ['allowed' => true, 'requests_remaining' => $limits - 1];
        }
        
        // Check burst limits
        $burstWindowStart = strtotime($rateLimit['burst_window_start']);
        if (($now - $burstWindowStart) > $burstWindowSeconds) {
            // Reset burst window
            $this->resetBurstWindow($rateLimit['id']);
        } else {
            // Check burst limit
            $burstLimit = $this->getBurstLimit($tier);
            if ($rateLimit['burst_count'] >= $burstLimit) {
                $this->blockIdentifier($rateLimit['id'], 300); // 5 minute block
                return [
                    'allowed' => false,
                    'reason' => 'burst_limit_exceeded',
                    'requests_remaining' => 0
                ];
            }
        }
        
        // Check main window limit
        if ($rateLimit['request_count'] >= $limits) {
            $this->blockIdentifier($rateLimit['id'], 3600); // 1 hour block
            return [
                'allowed' => false,
                'reason' => 'rate_limit_exceeded',
                'requests_remaining' => 0
            ];
        }
        
        // Increment counters
        $this->incrementRateLimitCounters($rateLimit['id']);
        
        return [
            'allowed' => true,
            'requests_remaining' => $limits - ($rateLimit['request_count'] + 1)
        ];
    }

    /**
     * Detect API abuse patterns
     */
    public function detectAbuse($identifier, $identifierType, $endpoint, $requestData) {
        $abusePatterns = [];

        // Pattern 1: Rapid sequential requests
        $rapidRequests = $this->checkRapidRequests($identifier, $identifierType);
        if ($rapidRequests['abuse_detected']) {
            $abusePatterns[] = $rapidRequests;
        }

        // Pattern 2: Unusual request patterns
        $unusualPatterns = $this->checkUnusualPatterns($identifier, $identifierType, $endpoint);
        if ($unusualPatterns['abuse_detected']) {
            $abusePatterns[] = $unusualPatterns;
        }

        // Pattern 3: Error rate analysis
        $errorRateAbuse = $this->checkErrorRateAbuse($identifier, $identifierType);
        if ($errorRateAbuse['abuse_detected']) {
            $abusePatterns[] = $errorRateAbuse;
        }

        // Pattern 4: Resource exhaustion attempts
        $resourceExhaustion = $this->checkResourceExhaustion($identifier, $identifierType, $requestData);
        if ($resourceExhaustion['abuse_detected']) {
            $abusePatterns[] = $resourceExhaustion;
        }

        if (!empty($abusePatterns)) {
            $this->logAbuseDetection($identifier, $identifierType, $abusePatterns);
            return [
                'abuse_detected' => true,
                'abuse_level' => $this->calculateAbuseLevel($abusePatterns),
                'patterns' => $abusePatterns
            ];
        }

        return ['abuse_detected' => false];
    }

    /**
     * Log API request
     */
    public function logAPIRequest($requestId, $apiKeyId, $userId, $userType, $endpoint, $method, $statusCode, $responseTime, $requestSize, $responseSize, $rateLimited = false, $abuseDetected = false, $errorMessage = null) {
        $logId = bin2hex(random_bytes(16));

        $query = "INSERT INTO api_request_log (
            id, request_id, api_key_id, user_id, user_type, endpoint, method,
            ip_address, user_agent, request_size_bytes, response_size_bytes,
            response_time_ms, status_code, error_message, rate_limited, abuse_detected
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $logId, $requestId, $apiKeyId, $userId, $userType, $endpoint, $method,
            $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null,
            $requestSize, $responseSize, $responseTime, $statusCode, $errorMessage,
            $rateLimited, $abuseDetected
        ]);

        // Update usage analytics
        $this->updateUsageAnalytics($endpoint, $apiKeyId, $userType, $statusCode, $responseTime, $requestSize + $responseSize, $rateLimited, $abuseDetected);
    }

    /**
     * Helper methods
     */

    private function getEndpointConfig($endpoint) {
        $query = "SELECT * FROM api_endpoint_config
                  WHERE ? LIKE endpoint_pattern OR endpoint_pattern = ?
                  ORDER BY LENGTH(endpoint_pattern) DESC
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$endpoint, $endpoint]);
        return $stmt->fetch();
    }

    private function getEndpointPattern($endpoint) {
        $config = $this->getEndpointConfig($endpoint);
        return $config ? $config['endpoint_pattern'] : '/api/*';
    }

    private function getDefaultRateLimits($tier) {
        $limits = [
            self::TIER_FREE => 100,
            self::TIER_BASIC => 1000,
            self::TIER_PREMIUM => 10000,
            self::TIER_ENTERPRISE => 100000
        ];

        return $limits[$tier] ?? $limits[self::TIER_FREE];
    }

    private function getBurstLimit($tier) {
        $limits = [
            self::TIER_FREE => 10,
            self::TIER_BASIC => 50,
            self::TIER_PREMIUM => 200,
            self::TIER_ENTERPRISE => 1000
        ];

        return $limits[$tier] ?? $limits[self::TIER_FREE];
    }

    private function createRateLimitRecord($identifier, $identifierType, $endpoint, $limits) {
        $rateLimitId = bin2hex(random_bytes(16));

        $query = "INSERT INTO api_rate_limits (
            id, identifier, identifier_type, endpoint_pattern, request_count,
            limit_per_window, burst_limit, burst_count
        ) VALUES (?, ?, ?, ?, 1, ?, ?, 1)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $rateLimitId, $identifier, $identifierType,
            $this->getEndpointPattern($endpoint), $limits, $this->getBurstLimit('free')
        ]);
    }

    private function resetRateLimitWindow($rateLimitId, $limits) {
        $query = "UPDATE api_rate_limits
                 SET request_count = 1, window_start = NOW(), limit_per_window = ?,
                     blocked_until = NULL, last_request = NOW()
                 WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$limits, $rateLimitId]);
    }

    private function resetBurstWindow($rateLimitId) {
        $query = "UPDATE api_rate_limits
                 SET burst_count = 1, burst_window_start = NOW()
                 WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$rateLimitId]);
    }

    private function blockIdentifier($rateLimitId, $blockDurationSeconds) {
        $blockedUntil = date('Y-m-d H:i:s', time() + $blockDurationSeconds);

        $query = "UPDATE api_rate_limits
                 SET blocked_until = ?, violation_count = violation_count + 1
                 WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$blockedUntil, $rateLimitId]);
    }

    private function incrementRateLimitCounters($rateLimitId) {
        $query = "UPDATE api_rate_limits
                 SET request_count = request_count + 1,
                     burst_count = burst_count + 1,
                     last_request = NOW()
                 WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$rateLimitId]);
    }

    private function updateAPIKeyUsage($keyId) {
        $query = "UPDATE api_keys
                 SET last_used_at = NOW(), usage_count = usage_count + 1
                 WHERE key_id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$keyId]);
    }

    private function checkRapidRequests($identifier, $identifierType) {
        $query = "SELECT COUNT(*) as request_count
                  FROM api_request_log
                  WHERE (? = 'ip' AND ip_address = ?) OR
                        (? = 'api_key' AND api_key_id = ?) OR
                        (? = 'user' AND user_id = ?)
                  AND request_timestamp >= DATE_SUB(NOW(), INTERVAL 10 SECOND)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $identifierType, $identifier,
            $identifierType, $identifier,
            $identifierType, $identifier
        ]);

        $result = $stmt->fetch();
        $requestCount = $result['request_count'];

        if ($requestCount > 50) { // More than 50 requests in 10 seconds
            return [
                'abuse_detected' => true,
                'type' => 'rapid_requests',
                'level' => self::ABUSE_LEVEL_HIGH,
                'details' => ['requests_in_10s' => $requestCount]
            ];
        }

        return ['abuse_detected' => false];
    }

    private function checkUnusualPatterns($identifier, $identifierType, $endpoint) {
        // Check for unusual endpoint access patterns
        $query = "SELECT endpoint, COUNT(*) as count
                  FROM api_request_log
                  WHERE (? = 'ip' AND ip_address = ?) OR
                        (? = 'api_key' AND api_key_id = ?) OR
                        (? = 'user' AND user_id = ?)
                  AND request_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                  GROUP BY endpoint
                  ORDER BY count DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $identifierType, $identifier,
            $identifierType, $identifier,
            $identifierType, $identifier
        ]);

        $endpoints = $stmt->fetchAll();

        // Check for endpoint scanning behavior
        if (count($endpoints) > 20) { // Accessing more than 20 different endpoints in 1 hour
            return [
                'abuse_detected' => true,
                'type' => 'endpoint_scanning',
                'level' => self::ABUSE_LEVEL_MEDIUM,
                'details' => ['unique_endpoints' => count($endpoints)]
            ];
        }

        return ['abuse_detected' => false];
    }

    private function checkErrorRateAbuse($identifier, $identifierType) {
        $query = "SELECT
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests
                  FROM api_request_log
                  WHERE (? = 'ip' AND ip_address = ?) OR
                        (? = 'api_key' AND api_key_id = ?) OR
                        (? = 'user' AND user_id = ?)
                  AND request_timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $identifierType, $identifier,
            $identifierType, $identifier,
            $identifierType, $identifier
        ]);

        $result = $stmt->fetch();
        $totalRequests = $result['total_requests'];
        $errorRequests = $result['error_requests'];

        if ($totalRequests > 10 && ($errorRequests / $totalRequests) > 0.5) { // More than 50% error rate
            return [
                'abuse_detected' => true,
                'type' => 'high_error_rate',
                'level' => self::ABUSE_LEVEL_MEDIUM,
                'details' => [
                    'total_requests' => $totalRequests,
                    'error_requests' => $errorRequests,
                    'error_rate' => round(($errorRequests / $totalRequests) * 100, 2)
                ]
            ];
        }

        return ['abuse_detected' => false];
    }

    private function checkResourceExhaustion($identifier, $identifierType, $requestData) {
        // Check for large request sizes that might indicate resource exhaustion attempts
        $requestSize = strlen(json_encode($requestData));

        if ($requestSize > 1048576) { // Larger than 1MB
            return [
                'abuse_detected' => true,
                'type' => 'large_request',
                'level' => self::ABUSE_LEVEL_HIGH,
                'details' => ['request_size_bytes' => $requestSize]
            ];
        }

        return ['abuse_detected' => false];
    }

    private function calculateAbuseLevel($abusePatterns) {
        $maxLevel = 0;
        foreach ($abusePatterns as $pattern) {
            $maxLevel = max($maxLevel, $pattern['level']);
        }
        return $maxLevel;
    }

    private function logAbuseDetection($identifier, $identifierType, $abusePatterns) {
        foreach ($abusePatterns as $pattern) {
            $abuseId = bin2hex(random_bytes(16));

            $query = "INSERT INTO api_abuse_detection (
                id, identifier, identifier_type, abuse_type, abuse_level,
                abuse_patterns, detection_details, auto_blocked
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                last_detected = NOW(),
                occurrence_count = occurrence_count + 1,
                detection_details = VALUES(detection_details)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $abuseId, $identifier, $identifierType, $pattern['type'],
                $pattern['level'], json_encode([$pattern]), json_encode($pattern['details']),
                $pattern['level'] >= self::ABUSE_LEVEL_HIGH
            ]);
        }
    }

    private function updateUsageAnalytics($endpoint, $apiKeyId, $userType, $statusCode, $responseTime, $bytesTransferred, $rateLimited, $abuseDetected) {
        $dateHour = date('Y-m-d H:00:00');
        $endpointPattern = $this->getEndpointPattern($endpoint);

        $query = "INSERT INTO api_usage_analytics (
            id, date_hour, endpoint_pattern, api_key_id, user_type,
            request_count, error_count, avg_response_time_ms, total_bytes_transferred,
            rate_limited_requests, abuse_detected_requests
        ) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            request_count = request_count + 1,
            error_count = error_count + ?,
            avg_response_time_ms = (avg_response_time_ms * (request_count - 1) + ?) / request_count,
            total_bytes_transferred = total_bytes_transferred + ?,
            rate_limited_requests = rate_limited_requests + ?,
            abuse_detected_requests = abuse_detected_requests + ?";

        $analyticsId = bin2hex(random_bytes(16));
        $isError = $statusCode >= 400 ? 1 : 0;
        $isRateLimited = $rateLimited ? 1 : 0;
        $isAbuseDetected = $abuseDetected ? 1 : 0;

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $analyticsId, $dateHour, $endpointPattern, $apiKeyId, $userType,
            $isError, $responseTime, $bytesTransferred, $isRateLimited, $isAbuseDetected,
            $isError, $responseTime, $bytesTransferred, $isRateLimited, $isAbuseDetected
        ]);
    }

    private function logAPIEvent($eventType, $keyId, $details) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, $eventType, SecurityLogger::LEVEL_INFO,
            "API security event: $eventType", array_merge($details, [
                'key_id' => $keyId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]));
    }
}

// Convenience functions
function generateAPIKey($userId, $userType, $keyName, $tier = EnterpriseAPISecurity::TIER_FREE, $permissions = [], $expiresAt = null) {
    $apiSecurity = EnterpriseAPISecurity::getInstance();
    return $apiSecurity->generateAPIKey($userId, $userType, $keyName, $tier, $permissions, $expiresAt);
}

function validateAPIKey($apiKey) {
    $apiSecurity = EnterpriseAPISecurity::getInstance();
    return $apiSecurity->validateAPIKey($apiKey);
}

function checkAPIRateLimit($identifier, $identifierType, $endpoint, $tier = EnterpriseAPISecurity::TIER_FREE) {
    $apiSecurity = EnterpriseAPISecurity::getInstance();
    return $apiSecurity->checkRateLimit($identifier, $identifierType, $endpoint, $tier);
}

function detectAPIAbuse($identifier, $identifierType, $endpoint, $requestData) {
    $apiSecurity = EnterpriseAPISecurity::getInstance();
    return $apiSecurity->detectAbuse($identifier, $identifierType, $endpoint, $requestData);
}

function logAPIRequest($requestId, $apiKeyId, $userId, $userType, $endpoint, $method, $statusCode, $responseTime, $requestSize, $responseSize, $rateLimited = false, $abuseDetected = false, $errorMessage = null) {
    $apiSecurity = EnterpriseAPISecurity::getInstance();
    return $apiSecurity->logAPIRequest($requestId, $apiKeyId, $userId, $userType, $endpoint, $method, $statusCode, $responseTime, $requestSize, $responseSize, $rateLimited, $abuseDetected, $errorMessage);
}
?>
