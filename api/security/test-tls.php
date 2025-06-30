<?php
/**
 * TLS SECURITY TEST ENDPOINT
 * Tests the TLS/HTTPS security implementation
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

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $testType = $input['test_type'] ?? 'all';
    
    $results = [];
    
    // Test 1: TLS Configuration
    if ($testType === 'all' || $testType === 'configuration') {
        $results['tls_configuration'] = testTLSConfiguration();
    }
    
    // Test 2: Security Headers
    if ($testType === 'all' || $testType === 'headers') {
        $results['security_headers'] = testSecurityHeaders();
    }
    
    // Test 3: Certificate Validation
    if ($testType === 'all' || $testType === 'certificate') {
        $results['certificate_validation'] = testCertificateValidation();
    }
    
    // Test 4: HTTPS Enforcement
    if ($testType === 'all' || $testType === 'enforcement') {
        $results['https_enforcement'] = testHTTPSEnforcement();
    }
    
    // Test 5: Cookie Security
    if ($testType === 'all' || $testType === 'cookies') {
        $results['cookie_security'] = testCookieSecurity();
    }
    
    // Test 6: Protocol Security
    if ($testType === 'all' || $testType === 'protocol') {
        $results['protocol_security'] = testProtocolSecurity();
    }
    
    // Log test completion
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'tls_test_suite', SecurityLogger::LEVEL_INFO,
        'TLS security test suite completed', 
        ['test_type' => $testType, 'tests_run' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'TLS security test suite completed',
        'test_type' => $testType,
        'results' => $results,
        'overall_score' => calculateOverallScore($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("TLS test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}

/**
 * Test TLS configuration
 */
function testTLSConfiguration() {
    $status = TLSSecurity::getSecurityStatus();
    $validation = TLSSecurity::validateTLSConfig();
    
    return [
        'status' => 'completed',
        'https_enabled' => $status['https_enabled'],
        'strict_mode' => $status['strict_mode'],
        'validation_passed' => $validation['valid'],
        'issues_found' => count($validation['issues']),
        'issues' => $validation['issues'],
        'score' => $validation['valid'] ? 100 : max(0, 100 - (count($validation['issues']) * 20))
    ];
}

/**
 * Test security headers
 */
function testSecurityHeaders() {
    $requiredHeaders = [
        'Strict-Transport-Security' => 'HSTS protection',
        'X-Content-Type-Options' => 'MIME type sniffing protection',
        'X-Frame-Options' => 'Clickjacking protection',
        'X-XSS-Protection' => 'XSS protection',
        'Content-Security-Policy' => 'Content injection protection',
        'Referrer-Policy' => 'Referrer information control'
    ];
    
    $headers = headers_list();
    $headerMap = [];
    
    foreach ($headers as $header) {
        $parts = explode(':', $header, 2);
        if (count($parts) === 2) {
            $headerMap[trim($parts[0])] = trim($parts[1]);
        }
    }
    
    $results = [];
    $score = 0;
    $maxScore = count($requiredHeaders) * 20;
    
    foreach ($requiredHeaders as $headerName => $description) {
        $present = isset($headerMap[$headerName]);
        $value = $present ? $headerMap[$headerName] : null;
        
        $results[$headerName] = [
            'present' => $present,
            'value' => $value,
            'description' => $description,
            'secure' => $present && validateHeaderSecurity($headerName, $value)
        ];
        
        if ($results[$headerName]['secure']) {
            $score += 20;
        } elseif ($present) {
            $score += 10;
        }
    }
    
    return [
        'status' => 'completed',
        'headers_tested' => count($requiredHeaders),
        'headers_present' => count(array_filter($results, function($r) { return $r['present']; })),
        'headers_secure' => count(array_filter($results, function($r) { return $r['secure']; })),
        'results' => $results,
        'score' => round(($score / $maxScore) * 100)
    ];
}

/**
 * Test certificate validation
 */
function testCertificateValidation() {
    if (!TLSSecurity::isHTTPS()) {
        return [
            'status' => 'skipped',
            'reason' => 'HTTPS not enabled',
            'score' => 0
        ];
    }
    
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = 443;
    
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false
        ]
    ]);
    
    $stream = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    
    if (!$stream) {
        return [
            'status' => 'failed',
            'error' => "$errno: $errstr",
            'score' => 0
        ];
    }
    
    $params = stream_context_get_params($stream);
    $cert = $params['options']['ssl']['peer_certificate'] ?? null;
    
    if (!$cert) {
        fclose($stream);
        return [
            'status' => 'failed',
            'error' => 'No certificate found',
            'score' => 0
        ];
    }
    
    $certInfo = openssl_x509_parse($cert);
    fclose($stream);
    
    $now = time();
    $validFrom = $certInfo['validFrom_time_t'] ?? 0;
    $validTo = $certInfo['validTo_time_t'] ?? 0;
    $isValid = $now >= $validFrom && $now <= $validTo;
    $daysUntilExpiry = max(0, ceil(($validTo - $now) / 86400));
    
    $score = 0;
    if ($isValid) $score += 40;
    if ($daysUntilExpiry > 30) $score += 30;
    if ($daysUntilExpiry > 90) $score += 30;
    
    return [
        'status' => 'completed',
        'certificate_valid' => $isValid,
        'days_until_expiry' => $daysUntilExpiry,
        'subject' => $certInfo['subject'] ?? [],
        'issuer' => $certInfo['issuer'] ?? [],
        'signature_algorithm' => $certInfo['signatureTypeSN'] ?? 'Unknown',
        'score' => $score
    ];
}

/**
 * Test HTTPS enforcement
 */
function testHTTPSEnforcement() {
    $forceHTTPS = Environment::isProduction() || EnvLoader::get('FORCE_HTTPS', 'false') === 'true';
    $currentlyHTTPS = TLSSecurity::isHTTPS();
    
    $score = 0;
    $issues = [];
    
    if ($forceHTTPS) {
        if ($currentlyHTTPS) {
            $score += 50;
        } else {
            $issues[] = 'HTTPS enforcement enabled but current request is HTTP';
        }
    } else {
        $issues[] = 'HTTPS enforcement is not enabled';
    }
    
    // Check for HSTS header
    $headers = headers_list();
    $hstsPresent = false;
    foreach ($headers as $header) {
        if (stripos($header, 'Strict-Transport-Security:') === 0) {
            $hstsPresent = true;
            $score += 50;
            break;
        }
    }
    
    if (!$hstsPresent) {
        $issues[] = 'HSTS header not present';
    }
    
    return [
        'status' => 'completed',
        'https_enforcement_enabled' => $forceHTTPS,
        'current_request_https' => $currentlyHTTPS,
        'hsts_header_present' => $hstsPresent,
        'issues' => $issues,
        'score' => $score
    ];
}

/**
 * Test cookie security
 */
function testCookieSecurity() {
    $cookieSettings = [
        'secure' => (bool)ini_get('session.cookie_secure'),
        'httponly' => (bool)ini_get('session.cookie_httponly'),
        'samesite' => ini_get('session.cookie_samesite')
    ];
    
    $score = 0;
    $issues = [];
    
    if ($cookieSettings['secure']) {
        $score += 40;
    } else {
        $issues[] = 'Session cookies not marked as secure';
    }
    
    if ($cookieSettings['httponly']) {
        $score += 30;
    } else {
        $issues[] = 'Session cookies not marked as httponly';
    }
    
    if ($cookieSettings['samesite'] === 'Strict') {
        $score += 30;
    } elseif ($cookieSettings['samesite'] === 'Lax') {
        $score += 20;
    } else {
        $issues[] = 'Session cookies do not have proper SameSite setting';
    }
    
    return [
        'status' => 'completed',
        'cookie_settings' => $cookieSettings,
        'issues' => $issues,
        'score' => $score
    ];
}

/**
 * Test protocol security
 */
function testProtocolSecurity() {
    $results = [
        'openssl_available' => extension_loaded('openssl'),
        'curl_ssl_support' => function_exists('curl_version') ? (curl_version()['features'] & CURL_VERSION_SSL) !== 0 : false,
        'stream_ssl_support' => in_array('ssl', stream_get_transports()),
        'tls_version' => 'Unknown'
    ];
    
    $score = 0;
    
    if ($results['openssl_available']) $score += 25;
    if ($results['curl_ssl_support']) $score += 25;
    if ($results['stream_ssl_support']) $score += 25;
    
    // Try to determine TLS version
    if (TLSSecurity::isHTTPS()) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $context = stream_context_create(['ssl' => ['capture_peer_cert_chain' => true]]);
        $stream = @stream_socket_client("ssl://$host:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        
        if ($stream) {
            $score += 25;
            $results['tls_version'] = 'TLS (version detection requires additional tools)';
            fclose($stream);
        }
    }
    
    return [
        'status' => 'completed',
        'protocol_support' => $results,
        'score' => $score
    ];
}

/**
 * Helper functions
 */

function validateHeaderSecurity($headerName, $value) {
    switch ($headerName) {
        case 'Strict-Transport-Security':
            return strpos($value, 'max-age=') !== false && (int)substr($value, strpos($value, 'max-age=') + 8) > 0;
        case 'X-Content-Type-Options':
            return $value === 'nosniff';
        case 'X-Frame-Options':
            return in_array($value, ['DENY', 'SAMEORIGIN']);
        case 'X-XSS-Protection':
            return strpos($value, '1') === 0;
        case 'Content-Security-Policy':
            return !empty($value) && strpos($value, 'default-src') !== false;
        case 'Referrer-Policy':
            return in_array($value, ['strict-origin-when-cross-origin', 'strict-origin', 'no-referrer']);
        default:
            return !empty($value);
    }
}

function calculateOverallScore($results) {
    $totalScore = 0;
    $testCount = 0;
    
    foreach ($results as $testName => $result) {
        if (isset($result['score'])) {
            $totalScore += $result['score'];
            $testCount++;
        }
    }
    
    return $testCount > 0 ? round($totalScore / $testCount) : 0;
}
?>
