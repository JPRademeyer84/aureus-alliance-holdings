<?php
/**
 * CORS SECURITY MANAGEMENT API
 * Admin interface for monitoring and managing CORS security
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require MFA for CORS security management
protectAdminOperation();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'security_stats':
            getCORSSecurityStats();
            break;
            
        case 'allowed_origins':
            manageAllowedOrigins();
            break;
            
        case 'suspicious_activity':
            getSuspiciousActivity();
            break;
            
        case 'security_logs':
            getCORSSecurityLogs();
            break;
            
        case 'block_origin':
            blockSuspiciousOrigin();
            break;
            
        case 'security_report':
            generateCORSSecurityReport();
            break;
            
        case 'test_origin':
            testOriginSecurity();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("CORS security management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get CORS security statistics
 */
function getCORSSecurityStats() {
    $stats = SecureCORS::getSecurityStats();
    
    // Get additional stats from database
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Get CORS events from last 24 hours
        $query = "SELECT 
                    event_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT JSON_EXTRACT(event_data, '$.origin')) as unique_origins,
                    COUNT(DISTINCT JSON_EXTRACT(event_data, '$.ip')) as unique_ips
                  FROM security_logs 
                  WHERE event_category = 'cors' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY event_type";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $corsEvents = $stmt->fetchAll();
        
        $stats['last_24h_events'] = $corsEvents;
        
        // Get top blocked origins
        $query = "SELECT 
                    JSON_EXTRACT(event_data, '$.origin') as origin,
                    COUNT(*) as block_count,
                    MAX(created_at) as last_blocked
                  FROM security_logs 
                  WHERE event_type = 'origin_blocked'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY JSON_EXTRACT(event_data, '$.origin')
                  ORDER BY block_count DESC
                  LIMIT 10";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['top_blocked_origins'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'timestamp' => date('c')
    ]);
}

/**
 * Manage allowed origins
 */
function manageAllowedOrigins() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get current allowed origins
        $allowedOrigins = SecureCORS::getAllowedOrigins();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'allowed_origins' => $allowedOrigins,
                'total_count' => count($allowedOrigins)
            ]
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add or remove origins (this would require environment variable updates)
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $origin = $input['origin'] ?? '';
        
        if (empty($origin)) {
            http_response_code(400);
            echo json_encode(['error' => 'Origin required']);
            return;
        }
        
        // Validate origin format
        if (!filter_var($origin, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid origin format']);
            return;
        }
        
        // Log the origin management action
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cors_origin_management', SecurityLogger::LEVEL_INFO,
            "CORS origin management action", [
                'action' => $action,
                'origin' => $origin,
                'admin_id' => $_SESSION['admin_id']
            ], null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'message' => "Origin $action request logged. Update environment configuration to apply changes.",
            'action' => $action,
            'origin' => $origin
        ]);
    }
}

/**
 * Get suspicious activity
 */
function getSuspiciousActivity() {
    $days = (int)($_GET['days'] ?? 7);
    
    $database = new Database();
    $db = $database->getConnection();
    
    $suspiciousActivity = [];
    
    if ($db) {
        // Get suspicious origin attempts
        $query = "SELECT 
                    JSON_EXTRACT(event_data, '$.origin') as origin,
                    JSON_EXTRACT(event_data, '$.ip') as ip,
                    JSON_EXTRACT(event_data, '$.patterns_matched') as patterns,
                    COUNT(*) as attempt_count,
                    MIN(created_at) as first_attempt,
                    MAX(created_at) as last_attempt
                  FROM security_logs 
                  WHERE event_type = 'suspicious_origin_detected'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY JSON_EXTRACT(event_data, '$.origin'), JSON_EXTRACT(event_data, '$.ip')
                  ORDER BY attempt_count DESC, last_attempt DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $suspiciousActivity = $stmt->fetchAll();
        
        // Get rate limited requests
        $query = "SELECT 
                    JSON_EXTRACT(event_data, '$.origin') as origin,
                    COUNT(*) as rate_limit_count,
                    MAX(created_at) as last_rate_limited
                  FROM security_logs 
                  WHERE event_type = 'cors_rate_limited'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY JSON_EXTRACT(event_data, '$.origin')
                  ORDER BY rate_limit_count DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $rateLimitedActivity = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'suspicious_origins' => $suspiciousActivity,
            'rate_limited_origins' => $rateLimitedActivity ?? [],
            'period_days' => $days
        ]
    ]);
}

/**
 * Get CORS security logs
 */
function getCORSSecurityLogs() {
    $limit = (int)($_GET['limit'] ?? 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $eventType = $_GET['event_type'] ?? '';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $logs = [];
    $totalCount = 0;
    
    if ($db) {
        // Build query with optional event type filter
        $whereClause = "WHERE event_category = 'cors'";
        $params = [];
        
        if (!empty($eventType)) {
            $whereClause .= " AND event_type = ?";
            $params[] = $eventType;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM security_logs $whereClause";
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $totalCount = $stmt->fetch()['total'];
        
        // Get logs
        $query = "SELECT 
                    id, event_type, event_level, event_message, event_data, 
                    user_id, admin_id, ip_address, user_agent, created_at
                  FROM security_logs 
                  $whereClause
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'total_count' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Block suspicious origin
 */
function blockSuspiciousOrigin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $origin = $input['origin'] ?? '';
    $reason = $input['reason'] ?? 'Manual block by admin';
    
    if (empty($origin)) {
        http_response_code(400);
        echo json_encode(['error' => 'Origin required']);
        return;
    }
    
    // Log the block action
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cors_origin_blocked', SecurityLogger::LEVEL_WARNING,
        "Origin manually blocked by admin", [
            'origin' => $origin,
            'reason' => $reason,
            'admin_id' => $_SESSION['admin_id']
        ], null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Origin block logged successfully',
        'origin' => $origin,
        'reason' => $reason
    ]);
}

/**
 * Generate CORS security report
 */
function generateCORSSecurityReport() {
    $days = (int)($_GET['days'] ?? 30);
    
    $database = new Database();
    $db = $database->getConnection();
    
    $report = [
        'period_days' => $days,
        'summary' => [],
        'trends' => [],
        'top_threats' => [],
        'recommendations' => []
    ];
    
    if ($db) {
        // Summary statistics
        $query = "SELECT 
                    event_type,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT JSON_EXTRACT(event_data, '$.origin')) as unique_origins,
                    COUNT(DISTINCT JSON_EXTRACT(event_data, '$.ip')) as unique_ips
                  FROM security_logs 
                  WHERE event_category = 'cors'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY event_type";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $report['summary'] = $stmt->fetchAll();
        
        // Daily trends
        $query = "SELECT 
                    DATE(created_at) as date,
                    event_type,
                    COUNT(*) as events
                  FROM security_logs 
                  WHERE event_category = 'cors'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(created_at), event_type
                  ORDER BY date";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $report['trends'] = $stmt->fetchAll();
        
        // Top threat patterns
        $query = "SELECT 
                    JSON_EXTRACT(event_data, '$.patterns_matched') as patterns,
                    COUNT(*) as occurrence_count
                  FROM security_logs 
                  WHERE event_type = 'suspicious_origin_detected'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND JSON_EXTRACT(event_data, '$.patterns_matched') IS NOT NULL
                  GROUP BY JSON_EXTRACT(event_data, '$.patterns_matched')
                  ORDER BY occurrence_count DESC
                  LIMIT 10";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $report['top_threats'] = $stmt->fetchAll();
    }
    
    // Generate recommendations
    $report['recommendations'] = generateCORSRecommendations($report);
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
}

/**
 * Test origin security
 */
function testOriginSecurity() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $testOrigin = $input['origin'] ?? '';
    
    if (empty($testOrigin)) {
        http_response_code(400);
        echo json_encode(['error' => 'Origin required for testing']);
        return;
    }
    
    $testResults = [
        'origin' => $testOrigin,
        'is_allowed' => SecureCORS::validateOrigin($testOrigin),
        'is_suspicious' => false,
        'suspicious_patterns' => [],
        'security_score' => 0
    ];
    
    // Test for suspicious patterns
    $reflection = new ReflectionClass('SecureCORS');
    $method = $reflection->getMethod('detectSuspiciousOrigin');
    $method->setAccessible(true);
    $testResults['is_suspicious'] = $method->invoke(null, $testOrigin);
    
    if ($testResults['is_suspicious']) {
        $patternMethod = $reflection->getMethod('getSuspiciousPatterns');
        $patternMethod->setAccessible(true);
        $testResults['suspicious_patterns'] = $patternMethod->invoke(null, $testOrigin);
    }
    
    // Calculate security score
    $testResults['security_score'] = calculateOriginSecurityScore($testOrigin, $testResults);
    
    echo json_encode([
        'success' => true,
        'data' => $testResults
    ]);
}

/**
 * Generate CORS recommendations
 */
function generateCORSRecommendations($report) {
    $recommendations = [];
    
    // Check for high suspicious activity
    $suspiciousEvents = 0;
    foreach ($report['summary'] as $summary) {
        if ($summary['event_type'] === 'suspicious_origin_detected') {
            $suspiciousEvents = $summary['total_events'];
            break;
        }
    }
    
    if ($suspiciousEvents > 100) {
        $recommendations[] = [
            'priority' => 'high',
            'title' => 'High Suspicious Activity Detected',
            'description' => "Detected $suspiciousEvents suspicious origin attempts in the last {$report['period_days']} days",
            'action' => 'Review and potentially implement stricter origin validation'
        ];
    }
    
    // Check for rate limiting effectiveness
    $rateLimitedEvents = 0;
    foreach ($report['summary'] as $summary) {
        if ($summary['event_type'] === 'cors_rate_limited') {
            $rateLimitedEvents = $summary['total_events'];
            break;
        }
    }
    
    if ($rateLimitedEvents > 50) {
        $recommendations[] = [
            'priority' => 'medium',
            'title' => 'High Rate Limiting Activity',
            'description' => "Rate limited $rateLimitedEvents CORS requests",
            'action' => 'Consider adjusting rate limiting thresholds or investigating potential abuse'
        ];
    }
    
    return $recommendations;
}

/**
 * Calculate origin security score
 */
function calculateOriginSecurityScore($origin, $testResults) {
    $score = 100; // Start with perfect score
    
    if (!$testResults['is_allowed']) {
        $score -= 50; // Major penalty for not being in whitelist
    }
    
    if ($testResults['is_suspicious']) {
        $score -= 30; // Penalty for suspicious patterns
        $score -= count($testResults['suspicious_patterns']) * 5; // Additional penalty per pattern
    }
    
    // Check for HTTPS
    if (strpos($origin, 'https://') !== 0) {
        $score -= 20; // Penalty for non-HTTPS
    }
    
    return max(0, $score); // Ensure score doesn't go below 0
}
?>
