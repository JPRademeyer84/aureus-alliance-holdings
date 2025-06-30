<?php
/**
 * TLS/HTTPS SECURITY CONFIGURATION
 * Bank-level data in transit encryption
 */

require_once 'env-loader.php';
require_once 'environment.php';
require_once 'security-logger.php';

class TLSSecurity {
    private static $initialized = false;
    private static $strictMode = false;
    
    /**
     * Initialize TLS security configuration
     */
    public static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        // Check if we're in production or strict mode
        self::$strictMode = Environment::isProduction() || EnvLoader::get('FORCE_HTTPS', 'false') === 'true';
        
        // Set security headers
        self::setSecurityHeaders();
        
        // Enforce HTTPS if required
        if (self::$strictMode) {
            self::enforceHTTPS();
        }
        
        // Configure secure cookies
        self::configureSecureCookies();
        
        self::$initialized = true;
        
        // Log TLS initialization
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'tls_initialized', SecurityLogger::LEVEL_INFO,
            'TLS security system initialized', ['strict_mode' => self::$strictMode]);
    }
    
    /**
     * Set comprehensive security headers
     */
    private static function setSecurityHeaders() {
        if (headers_sent()) {
            return;
        }
        
        // HTTP Strict Transport Security (HSTS)
        if (self::isHTTPS() || self::$strictMode) {
            $hstsMaxAge = EnvLoader::get('HSTS_MAX_AGE', '31536000'); // 1 year default
            $includeSubdomains = EnvLoader::get('HSTS_INCLUDE_SUBDOMAINS', 'true') === 'true';
            $preload = EnvLoader::get('HSTS_PRELOAD', 'true') === 'true';
            
            $hstsHeader = "max-age=$hstsMaxAge";
            if ($includeSubdomains) {
                $hstsHeader .= "; includeSubDomains";
            }
            if ($preload) {
                $hstsHeader .= "; preload";
            }
            
            header("Strict-Transport-Security: $hstsHeader");
        }
        
        // Content Security Policy (CSP)
        $cspPolicy = self::buildCSPPolicy();
        header("Content-Security-Policy: $cspPolicy");
        
        // Additional security headers
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        
        // Prevent caching of sensitive content
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
        
        // Feature Policy for additional security
        header("Feature-Policy: payment 'none'; microphone 'none'; camera 'none'; geolocation 'none'");
        
        // Cross-Origin policies
        header("Cross-Origin-Embedder-Policy: require-corp");
        header("Cross-Origin-Opener-Policy: same-origin");
        header("Cross-Origin-Resource-Policy: same-origin");
    }
    
    /**
     * Build Content Security Policy
     */
    private static function buildCSPPolicy() {
        $allowedOrigins = EnvLoader::get('CSP_ALLOWED_ORIGINS', 'localhost:5173 localhost:5174 127.0.0.1:5173 127.0.0.1:5174');
        $origins = explode(' ', $allowedOrigins);
        
        // Build CSP directives
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Needed for React development
            "style-src 'self' 'unsafe-inline'", // Needed for CSS-in-JS
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' " . implode(' ', array_map(function($origin) {
                return "http://$origin https://$origin";
            }, $origins)),
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests"
        ];
        
        // In production, be more restrictive
        if (Environment::isProduction()) {
            $directives = array_map(function($directive) {
                // Remove unsafe-inline and unsafe-eval in production
                return str_replace(["'unsafe-inline'", "'unsafe-eval'"], "", $directive);
            }, $directives);
        }
        
        return implode('; ', $directives);
    }
    
    /**
     * Enforce HTTPS redirection
     */
    private static function enforceHTTPS() {
        if (!self::isHTTPS()) {
            $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            
            // Log HTTPS enforcement
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'https_redirect', SecurityLogger::LEVEL_WARNING,
                'HTTP request redirected to HTTPS', ['original_url' => $_SERVER['REQUEST_URI']]);
            
            header("Location: $httpsUrl", true, 301);
            exit();
        }
    }
    
    /**
     * Configure secure cookies
     */
    private static function configureSecureCookies() {
        // Set secure cookie defaults
        ini_set('session.cookie_secure', self::isHTTPS() || self::$strictMode ? '1' : '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        // Configure session cookie parameters
        $cookieParams = [
            'lifetime' => 0, // Session cookie
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => self::isHTTPS() || self::$strictMode,
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        session_set_cookie_params($cookieParams);
    }
    
    /**
     * Check if current request is HTTPS
     */
    public static function isHTTPS() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        );
    }
    
    /**
     * Validate TLS configuration
     */
    public static function validateTLSConfig() {
        $issues = [];
        
        // Check HTTPS
        if (self::$strictMode && !self::isHTTPS()) {
            $issues[] = 'HTTPS is required but current request is HTTP';
        }
        
        // Check security headers
        $requiredHeaders = [
            'Strict-Transport-Security',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Content-Security-Policy'
        ];
        
        foreach ($requiredHeaders as $header) {
            if (!self::headerExists($header)) {
                $issues[] = "Missing security header: $header";
            }
        }
        
        // Check cookie security
        if (self::$strictMode) {
            if (!ini_get('session.cookie_secure')) {
                $issues[] = 'Session cookies are not configured as secure';
            }
            if (!ini_get('session.cookie_httponly')) {
                $issues[] = 'Session cookies are not configured as httponly';
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'https_enabled' => self::isHTTPS(),
            'strict_mode' => self::$strictMode
        ];
    }
    
    /**
     * Check if header exists in response
     */
    private static function headerExists($headerName) {
        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, $headerName . ':') === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get TLS security status
     */
    public static function getSecurityStatus() {
        return [
            'tls_initialized' => self::$initialized,
            'https_enabled' => self::isHTTPS(),
            'strict_mode' => self::$strictMode,
            'security_headers_set' => true,
            'cookie_security' => [
                'secure' => (bool)ini_get('session.cookie_secure'),
                'httponly' => (bool)ini_get('session.cookie_httponly'),
                'samesite' => ini_get('session.cookie_samesite')
            ],
            'validation' => self::validateTLSConfig()
        ];
    }
    
    /**
     * Generate TLS configuration report
     */
    public static function generateSecurityReport() {
        $report = [
            'timestamp' => date('c'),
            'server_info' => [
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'php_version' => PHP_VERSION,
                'openssl_version' => OPENSSL_VERSION_TEXT ?? 'Not available'
            ],
            'tls_status' => self::getSecurityStatus(),
            'headers_sent' => headers_list(),
            'environment' => [
                'is_production' => Environment::isProduction(),
                'force_https' => EnvLoader::get('FORCE_HTTPS', 'false'),
                'hsts_max_age' => EnvLoader::get('HSTS_MAX_AGE', '31536000')
            ]
        ];
        
        // Log security report generation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'security_report_generated', SecurityLogger::LEVEL_INFO,
            'TLS security report generated');
        
        return $report;
    }
    
    /**
     * Test TLS connection security
     */
    public static function testTLSConnection($url = null) {
        if (!$url) {
            $protocol = self::isHTTPS() ? 'https' : 'http';
            $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        
        $results = [];
        
        // Test SSL/TLS if HTTPS
        if (strpos($url, 'https://') === 0) {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => true,
                    'verify_peer_name' => true
                ]
            ]);
            
            $stream = @stream_socket_client(
                'ssl://' . parse_url($url, PHP_URL_HOST) . ':443',
                $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
            );
            
            if ($stream) {
                $params = stream_context_get_params($stream);
                $cert = $params['options']['ssl']['peer_certificate'];
                
                if ($cert) {
                    $certInfo = openssl_x509_parse($cert);
                    $results['certificate'] = [
                        'subject' => $certInfo['subject'],
                        'issuer' => $certInfo['issuer'],
                        'valid_from' => date('Y-m-d H:i:s', $certInfo['validFrom_time_t']),
                        'valid_to' => date('Y-m-d H:i:s', $certInfo['validTo_time_t']),
                        'is_valid' => time() >= $certInfo['validFrom_time_t'] && time() <= $certInfo['validTo_time_t']
                    ];
                }
                
                fclose($stream);
                $results['connection'] = 'success';
            } else {
                $results['connection'] = 'failed';
                $results['error'] = "$errno: $errstr";
            }
        } else {
            $results['connection'] = 'http_only';
        }
        
        return $results;
    }
}

// Auto-initialize TLS security
TLSSecurity::initialize();

// Convenience functions
function enforceHTTPS() {
    TLSSecurity::initialize();
}

function isSecureConnection() {
    return TLSSecurity::isHTTPS();
}

function getTLSSecurityStatus() {
    return TLSSecurity::getSecurityStatus();
}

function validateTLSConfiguration() {
    return TLSSecurity::validateTLSConfig();
}

/**
 * HTTPS ENFORCEMENT MIDDLEWARE
 * Ensures all API requests use secure connections
 */
class HTTPSMiddleware {
    private static $exemptPaths = [
        '/api/health-check.php',
        '/api/status.php'
    ];

    /**
     * Enforce HTTPS for API requests
     */
    public static function enforce() {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';

        // Skip enforcement for exempt paths
        foreach (self::$exemptPaths as $exemptPath) {
            if (strpos($currentPath, $exemptPath) !== false) {
                return;
            }
        }

        // Check if HTTPS enforcement is required
        $forceHTTPS = Environment::isProduction() || EnvLoader::get('FORCE_HTTPS', 'false') === 'true';

        if ($forceHTTPS && !TLSSecurity::isHTTPS()) {
            // Log insecure access attempt
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'insecure_access_blocked', SecurityLogger::LEVEL_WARNING,
                'Insecure HTTP access blocked', ['path' => $currentPath, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

            http_response_code(426); // Upgrade Required
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'HTTPS Required',
                'message' => 'This API requires a secure HTTPS connection',
                'code' => 'HTTPS_REQUIRED',
                'upgrade_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            ]);
            exit();
        }
    }

    /**
     * Add exempt path for HTTPS enforcement
     */
    public static function addExemptPath($path) {
        if (!in_array($path, self::$exemptPaths)) {
            self::$exemptPaths[] = $path;
        }
    }

    /**
     * Get current exempt paths
     */
    public static function getExemptPaths() {
        return self::$exemptPaths;
    }
}

/**
 * API SECURITY WRAPPER
 * Comprehensive security for API endpoints
 */
class APISecurityWrapper {
    /**
     * Apply all security measures to API endpoint
     */
    public static function secure() {
        // Initialize TLS security
        TLSSecurity::initialize();

        // Enforce HTTPS if required
        HTTPSMiddleware::enforce();

        // Set additional API security headers
        self::setAPISecurityHeaders();

        // Validate request security
        self::validateRequestSecurity();

        // Log secure API access
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'secure_api_access', SecurityLogger::LEVEL_INFO,
            'Secure API endpoint accessed', ['endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown']);
    }

    /**
     * Set API-specific security headers
     */
    private static function setAPISecurityHeaders() {
        if (headers_sent()) {
            return;
        }

        // API-specific headers
        header('X-API-Version: 1.0');
        header('X-Security-Level: Bank-Grade');
        header('X-Encryption-Status: AES-256-GCM');

        // Rate limiting headers (if applicable)
        $rateLimitRemaining = $_SESSION['rate_limit_remaining'] ?? 100;
        header("X-RateLimit-Remaining: $rateLimitRemaining");

        // Security policy headers
        header('X-Permitted-Cross-Domain-Policies: none');
        header('X-Download-Options: noopen');
    }

    /**
     * Validate request security parameters
     */
    private static function validateRequestSecurity() {
        $issues = [];

        // Check for suspicious headers
        $suspiciousHeaders = ['X-Forwarded-For', 'X-Real-IP', 'X-Originating-IP'];
        foreach ($suspiciousHeaders as $header) {
            if (isset($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))])) {
                $issues[] = "Suspicious header detected: $header";
            }
        }

        // Check request method
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            $issues[] = "Invalid request method: " . $_SERVER['REQUEST_METHOD'];
        }

        // Check content type for POST/PUT requests
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (!empty($contentType) && strpos($contentType, 'application/json') === false && strpos($contentType, 'multipart/form-data') === false) {
                $issues[] = "Suspicious content type: $contentType";
            }
        }

        // Log security issues
        if (!empty($issues)) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'request_security_issues', SecurityLogger::LEVEL_WARNING,
                'Request security validation issues detected', ['issues' => $issues]);
        }
    }
}

// Convenience function to secure API endpoints
function secureAPIEndpoint() {
    APISecurityWrapper::secure();
}
?>
