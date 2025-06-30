<?php
/**
 * SECURITY LOGGING TEST ENDPOINT
 * Tests the centralized security logging system
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/security-logger.php';

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
    
    // Test different types of security events
    if ($testType === 'all' || $testType === 'authentication') {
        // Test authentication events
        $eventId1 = logAuthenticationEvent('login_failed', SecurityLogger::LEVEL_WARNING, 
            'Test failed login attempt', ['username' => 'test_user'], 123);
        $results['authentication_warning'] = $eventId1;
        
        $eventId2 = logAuthenticationEvent('login_success', SecurityLogger::LEVEL_INFO, 
            'Test successful login', ['username' => 'test_user'], 123);
        $results['authentication_info'] = $eventId2;
    }
    
    if ($testType === 'all' || $testType === 'file_upload') {
        // Test file upload events
        $eventId3 = logFileUploadEvent('malicious_detected', SecurityLogger::LEVEL_CRITICAL, 
            'Test malicious file detected', ['filename' => 'test_malware.exe'], 123);
        $results['file_upload_critical'] = $eventId3;
        
        $eventId4 = logFileUploadEvent('upload_success', SecurityLogger::LEVEL_INFO, 
            'Test file upload success', ['filename' => 'test_document.pdf'], 123);
        $results['file_upload_info'] = $eventId4;
    }
    
    if ($testType === 'all' || $testType === 'cors') {
        // Test CORS events
        $eventId5 = logCorsEvent('origin_blocked', SecurityLogger::LEVEL_WARNING, 
            'Test CORS origin blocked', ['origin' => 'https://malicious-site.com']);
        $results['cors_warning'] = $eventId5;
    }
    
    if ($testType === 'all' || $testType === 'financial') {
        // Test financial events
        $eventId6 = logFinancialEvent('suspicious_transaction', SecurityLogger::LEVEL_CRITICAL, 
            'Test suspicious financial transaction', 
            ['amount' => 10000, 'currency' => 'USDT', 'transaction_type' => 'withdrawal'], 123);
        $results['financial_critical'] = $eventId6;
    }
    
    if ($testType === 'all' || $testType === 'rate_limit') {
        // Test rate limiting events
        $eventId7 = logSecurityEvent(SecurityLogger::EVENT_RATE_LIMIT, 'limit_exceeded', 
            SecurityLogger::LEVEL_CRITICAL, 'Test rate limit exceeded', 
            ['attempts' => 10, 'action' => 'login']);
        $results['rate_limit_critical'] = $eventId7;
    }
    
    if ($testType === 'all' || $testType === 'emergency') {
        // Test emergency level event
        $eventId8 = logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'security_breach', 
            SecurityLogger::LEVEL_EMERGENCY, 'Test emergency security event', 
            ['breach_type' => 'unauthorized_access', 'affected_systems' => ['database', 'file_system']]);
        $results['emergency_event'] = $eventId8;
    }
    
    // Log this test activity
    logSecurityEvent(SecurityLogger::EVENT_ADMIN, 'security_test', SecurityLogger::LEVEL_INFO,
        'Security logging system test performed', 
        ['test_type' => $testType, 'events_created' => count($results)], 
        null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Security logging test completed',
        'test_type' => $testType,
        'events_created' => $results,
        'total_events' => count($results),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Security logging test error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}
?>
