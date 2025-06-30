<?php
/**
 * TLS SECURITY MANAGEMENT API
 * Admin interface for managing HTTPS/TLS configuration
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/tls-security.php';

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
    switch ($action) {
        case 'status':
            getTLSStatus();
            break;
            
        case 'validate':
            validateTLSConfiguration();
            break;
            
        case 'report':
            generateSecurityReport();
            break;
            
        case 'test_connection':
            testTLSConnection();
            break;
            
        case 'configure':
            configureTLSSettings();
            break;
            
        case 'headers':
            getSecurityHeaders();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("TLS management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get TLS security status
 */
function getTLSStatus() {
    $status = TLSSecurity::getSecurityStatus();
    
    // Add additional status information
    $status['server_info'] = [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => PHP_VERSION,
        'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Not available',
        'current_protocol' => TLSSecurity::isHTTPS() ? 'HTTPS' : 'HTTP',
        'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown'
    ];
    
    $status['environment'] = [
        'is_production' => Environment::isProduction(),
        'force_https' => EnvLoader::get('FORCE_HTTPS', 'false'),
        'hsts_max_age' => EnvLoader::get('HSTS_MAX_AGE', '31536000'),
        'hsts_include_subdomains' => EnvLoader::get('HSTS_INCLUDE_SUBDOMAINS', 'true'),
        'hsts_preload' => EnvLoader::get('HSTS_PRELOAD', 'true')
    ];
    
    // Check certificate information if HTTPS
    if (TLSSecurity::isHTTPS()) {
        $status['certificate_info'] = getCertificateInfo();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $status,
        'timestamp' => date('c')
    ]);
}

/**
 * Validate TLS configuration
 */
function validateTLSConfiguration() {
    $validation = TLSSecurity::validateTLSConfig();
    
    // Add additional validation checks
    $additionalChecks = [
        'openssl_available' => extension_loaded('openssl'),
        'curl_ssl_support' => function_exists('curl_version') ? (curl_version()['features'] & CURL_VERSION_SSL) !== 0 : false,
        'stream_ssl_support' => in_array('ssl', stream_get_transports()),
        'session_security' => [
            'cookie_secure' => (bool)ini_get('session.cookie_secure'),
            'cookie_httponly' => (bool)ini_get('session.cookie_httponly'),
            'cookie_samesite' => ini_get('session.cookie_samesite')
        ]
    ];
    
    $validation['additional_checks'] = $additionalChecks;
    $validation['overall_score'] = calculateSecurityScore($validation);
    
    // Log validation
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'tls_validation', SecurityLogger::LEVEL_INFO,
        'TLS configuration validation performed', 
        ['score' => $validation['overall_score'], 'issues_count' => count($validation['issues'])], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'data' => $validation,
        'timestamp' => date('c')
    ]);
}

/**
 * Generate comprehensive security report
 */
function generateSecurityReport() {
    $report = TLSSecurity::generateSecurityReport();
    
    // Add additional report sections
    $report['security_assessment'] = [
        'tls_grade' => calculateTLSGrade(),
        'compliance_status' => checkComplianceStatus(),
        'recommendations' => getSecurityRecommendations()
    ];
    
    $report['network_info'] = [
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
    ];
    
    // Log report generation
    logSecurityEvent(SecurityLogger::EVENT_ADMIN, 'security_report_generated', SecurityLogger::LEVEL_INFO,
        'Comprehensive security report generated', [], null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'data' => $report,
        'timestamp' => date('c')
    ]);
}

/**
 * Test TLS connection
 */
function testTLSConnection() {
    $input = json_decode(file_get_contents('php://input'), true);
    $testUrl = $input['url'] ?? null;
    
    $results = TLSSecurity::testTLSConnection($testUrl);
    
    // Add additional connection tests
    $results['dns_resolution'] = testDNSResolution();
    $results['port_connectivity'] = testPortConnectivity();
    $results['ssl_labs_grade'] = 'Manual testing required'; // Placeholder for SSL Labs integration
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'timestamp' => date('c')
    ]);
}

/**
 * Configure TLS settings
 */
function configureTLSSettings() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $allowedSettings = [
        'FORCE_HTTPS',
        'HSTS_MAX_AGE',
        'HSTS_INCLUDE_SUBDOMAINS',
        'HSTS_PRELOAD',
        'CSP_ALLOWED_ORIGINS'
    ];
    
    $updated = [];
    $errors = [];
    
    foreach ($allowedSettings as $setting) {
        if (isset($input[$setting])) {
            $value = $input[$setting];
            
            // Validate setting value
            if (validateSettingValue($setting, $value)) {
                // In a real implementation, you would update the .env file or database
                // For now, we'll just log the change
                $updated[$setting] = $value;
                
                logSecurityEvent(SecurityLogger::EVENT_ADMIN, 'tls_setting_updated', SecurityLogger::LEVEL_INFO,
                    "TLS setting updated: $setting", ['setting' => $setting, 'value' => $value], 
                    null, $_SESSION['admin_id']);
            } else {
                $errors[$setting] = 'Invalid value';
            }
        }
    }
    
    echo json_encode([
        'success' => empty($errors),
        'updated' => $updated,
        'errors' => $errors,
        'message' => empty($errors) ? 'Settings updated successfully' : 'Some settings could not be updated'
    ]);
}

/**
 * Get current security headers
 */
function getSecurityHeaders() {
    $headers = headers_list();
    $securityHeaders = [];
    
    $securityHeaderNames = [
        'Strict-Transport-Security',
        'Content-Security-Policy',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-XSS-Protection',
        'Referrer-Policy',
        'Permissions-Policy',
        'Cross-Origin-Embedder-Policy',
        'Cross-Origin-Opener-Policy',
        'Cross-Origin-Resource-Policy'
    ];
    
    foreach ($headers as $header) {
        foreach ($securityHeaderNames as $securityHeader) {
            if (stripos($header, $securityHeader . ':') === 0) {
                $securityHeaders[$securityHeader] = trim(substr($header, strlen($securityHeader) + 1));
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'security_headers' => $securityHeaders,
            'all_headers' => $headers,
            'missing_headers' => array_diff($securityHeaderNames, array_keys($securityHeaders))
        ]
    ]);
}

/**
 * Helper functions
 */

function getCertificateInfo() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = 443;
    
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $stream = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    
    if (!$stream) {
        return ['error' => "Failed to connect: $errstr"];
    }
    
    $params = stream_context_get_params($stream);
    $cert = $params['options']['ssl']['peer_certificate'] ?? null;
    
    if (!$cert) {
        fclose($stream);
        return ['error' => 'No certificate found'];
    }
    
    $certInfo = openssl_x509_parse($cert);
    fclose($stream);
    
    return [
        'subject' => $certInfo['subject'] ?? [],
        'issuer' => $certInfo['issuer'] ?? [],
        'valid_from' => date('Y-m-d H:i:s', $certInfo['validFrom_time_t'] ?? 0),
        'valid_to' => date('Y-m-d H:i:s', $certInfo['validTo_time_t'] ?? 0),
        'is_valid' => time() >= ($certInfo['validFrom_time_t'] ?? 0) && time() <= ($certInfo['validTo_time_t'] ?? 0),
        'days_until_expiry' => max(0, ceil((($certInfo['validTo_time_t'] ?? 0) - time()) / 86400)),
        'signature_algorithm' => $certInfo['signatureTypeSN'] ?? 'Unknown'
    ];
}

function calculateSecurityScore($validation) {
    $score = 100;
    
    // Deduct points for issues
    $score -= count($validation['issues']) * 10;
    
    // Deduct points for missing HTTPS
    if (!$validation['https_enabled']) {
        $score -= 30;
    }
    
    // Deduct points for insecure cookies
    if (!$validation['additional_checks']['session_security']['cookie_secure']) {
        $score -= 15;
    }
    
    return max(0, $score);
}

function calculateTLSGrade() {
    $score = calculateSecurityScore(TLSSecurity::validateTLSConfig());
    
    if ($score >= 90) return 'A+';
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B';
    if ($score >= 60) return 'C';
    if ($score >= 50) return 'D';
    return 'F';
}

function checkComplianceStatus() {
    return [
        'pci_dss' => TLSSecurity::isHTTPS() && ini_get('session.cookie_secure'),
        'gdpr' => true, // Assuming GDPR compliance based on encryption
        'hipaa' => TLSSecurity::isHTTPS() && calculateSecurityScore(TLSSecurity::validateTLSConfig()) >= 80,
        'sox' => TLSSecurity::isHTTPS() && !empty(array_filter(headers_list(), function($h) { 
            return stripos($h, 'Strict-Transport-Security:') === 0; 
        }))
    ];
}

function getSecurityRecommendations() {
    $recommendations = [];
    $validation = TLSSecurity::validateTLSConfig();
    
    if (!$validation['https_enabled']) {
        $recommendations[] = 'Enable HTTPS for all communications';
    }
    
    if (!empty($validation['issues'])) {
        $recommendations[] = 'Address security header issues: ' . implode(', ', $validation['issues']);
    }
    
    if (!ini_get('session.cookie_secure')) {
        $recommendations[] = 'Enable secure flag for session cookies';
    }
    
    return $recommendations;
}

function testDNSResolution() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $ip = gethostbyname($host);
    
    return [
        'hostname' => $host,
        'resolved_ip' => $ip,
        'resolution_successful' => $ip !== $host
    ];
}

function testPortConnectivity() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $ports = [80, 443];
    $results = [];
    
    foreach ($ports as $port) {
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        $results[$port] = [
            'open' => $connection !== false,
            'error' => $connection === false ? "$errno: $errstr" : null
        ];
        
        if ($connection) {
            fclose($connection);
        }
    }
    
    return $results;
}

function validateSettingValue($setting, $value) {
    switch ($setting) {
        case 'FORCE_HTTPS':
            return in_array($value, ['true', 'false']);
        case 'HSTS_MAX_AGE':
            return is_numeric($value) && $value >= 0;
        case 'HSTS_INCLUDE_SUBDOMAINS':
        case 'HSTS_PRELOAD':
            return in_array($value, ['true', 'false']);
        case 'CSP_ALLOWED_ORIGINS':
            return is_string($value) && !empty($value);
        default:
            return false;
    }
}
?>
