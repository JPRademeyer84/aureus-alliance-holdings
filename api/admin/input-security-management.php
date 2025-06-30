<?php
/**
 * INPUT SECURITY MANAGEMENT API
 * Enterprise input validation and threat monitoring administration
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-input-security.php';
require_once '../config/enhanced-validation-middleware.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and require fresh MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require fresh MFA for input security operations
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'threat_dashboard':
            getThreatDashboard();
            break;
            
        case 'threat_log':
            getThreatLog();
            break;
            
        case 'validation_rules':
            getValidationRules();
            break;
            
        case 'create_rule':
            createValidationRule();
            break;
            
        case 'update_rule':
            updateValidationRule();
            break;
            
        case 'sanitization_log':
            getSanitizationLog();
            break;
            
        case 'tampering_log':
            getTamperingLog();
            break;
            
        case 'rate_limit_status':
            getRateLimitStatus();
            break;
            
        case 'test_validation':
            testValidation();
            break;
            
        case 'security_metrics':
            getSecurityMetrics();
            break;
            
        case 'export_threats':
            exportThreats();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Input security management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get threat dashboard
 */
function getThreatDashboard() {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboard = [
        'threat_summary' => [],
        'recent_threats' => [],
        'top_threat_sources' => [],
        'threat_trends' => [],
        'blocked_requests' => []
    ];
    
    if ($db) {
        // Threat summary
        $query = "SELECT 
                    threat_level,
                    COUNT(*) as threat_count,
                    COUNT(CASE WHEN blocked = TRUE THEN 1 END) as blocked_count
                  FROM input_threat_log 
                  WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY threat_level";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['threat_summary'] = $stmt->fetchAll();
        
        // Recent threats
        $query = "SELECT 
                    threat_type, threat_level, input_source, blocked,
                    detected_at, ip_address, endpoint
                  FROM input_threat_log 
                  ORDER BY detected_at DESC 
                  LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['recent_threats'] = $stmt->fetchAll();
        
        // Top threat sources
        $query = "SELECT 
                    ip_address,
                    COUNT(*) as threat_count,
                    MAX(threat_level) as max_threat_level
                  FROM input_threat_log 
                  WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY ip_address
                  ORDER BY threat_count DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['top_threat_sources'] = $stmt->fetchAll();
        
        // Threat trends (hourly for last 24 hours)
        $query = "SELECT 
                    HOUR(detected_at) as hour,
                    COUNT(*) as threat_count
                  FROM input_threat_log 
                  WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY HOUR(detected_at)
                  ORDER BY hour";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['threat_trends'] = $stmt->fetchAll();
        
        // Blocked requests
        $query = "SELECT COUNT(*) as blocked_count
                  FROM input_threat_log 
                  WHERE blocked = TRUE AND detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['blocked_requests'] = $stmt->fetch();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard
    ]);
}

/**
 * Get threat log
 */
function getThreatLog() {
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $threatLevel = $_GET['threat_level'] ?? null;
    $threatType = $_GET['threat_type'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $whereConditions = [];
    $params = [];
    
    if ($threatLevel) {
        $whereConditions[] = "threat_level = ?";
        $params[] = $threatLevel;
    }
    
    if ($threatType) {
        $whereConditions[] = "threat_type = ?";
        $params[] = $threatType;
    }
    
    if ($startDate) {
        $whereConditions[] = "detected_at >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $whereConditions[] = "detected_at <= ?";
        $params[] = $endDate;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT * FROM input_threat_log 
              $whereClause
              ORDER BY detected_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $threats = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'threats' => $threats,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get validation rules
 */
function getValidationRules() {
    $database = new Database();
    $db = $database->getConnection();
    
    $context = $_GET['context'] ?? null;
    $ruleType = $_GET['rule_type'] ?? null;
    
    $whereConditions = ['is_active = TRUE'];
    $params = [];
    
    if ($context) {
        $whereConditions[] = "context = ?";
        $params[] = $context;
    }
    
    if ($ruleType) {
        $whereConditions[] = "rule_type = ?";
        $params[] = $ruleType;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    $query = "SELECT * FROM input_validation_rules 
              $whereClause
              ORDER BY context, severity DESC, rule_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rules = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $rules
    ]);
}

/**
 * Create validation rule
 */
function createValidationRule() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['rule_name', 'rule_type', 'rule_pattern', 'context', 'severity'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $security = EnterpriseInputSecurity::getInstance();
    $result = $security->createValidationRule(
        $input['rule_name'],
        $input['rule_type'],
        $input['rule_pattern'],
        $input['context'],
        $input['severity']
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Validation rule created successfully',
        'data' => $result
    ]);
}

/**
 * Update validation rule
 */
function updateValidationRule() {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ruleId = $_GET['rule_id'] ?? '';
    
    if (empty($ruleId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing rule_id']);
        return;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $updateFields = [];
    $params = [];
    
    $allowedFields = ['rule_name', 'rule_pattern', 'context', 'severity', 'is_active'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $updateFields[] = "updated_at = NOW()";
    $params[] = $ruleId;
    
    $query = "UPDATE input_validation_rules 
              SET " . implode(', ', $updateFields) . "
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $success = $stmt->execute($params);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Rule updated successfully' : 'Failed to update rule'
    ]);
}

/**
 * Get sanitization log
 */
function getSanitizationLog() {
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $context = $_GET['context'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $whereClause = $context ? "WHERE context = ?" : "";
    $params = $context ? [$context] : [];
    
    $query = "SELECT * FROM input_sanitization_log 
              $whereClause
              ORDER BY sanitized_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $sanitizations = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'sanitizations' => $sanitizations,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get tampering log
 */
function getTamperingLog() {
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM parameter_tampering_log 
              ORDER BY detected_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$limit, $offset]);
    $tampering = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'tampering_events' => $tampering,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get rate limit status
 */
function getRateLimitStatus() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT 
                identifier, identifier_type, endpoint, request_count,
                window_start, last_request, blocked_until
              FROM input_rate_limiting 
              WHERE blocked_until > NOW() OR last_request >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
              ORDER BY last_request DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rateLimits = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $rateLimits
    ]);
}

/**
 * Test validation
 */
function testValidation() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $testInput = $input['test_input'] ?? '';
    $context = $input['context'] ?? EnterpriseInputSecurity::CONTEXT_HTML;
    $customRules = $input['custom_rules'] ?? [];
    
    if (empty($testInput)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing test_input']);
        return;
    }
    
    $result = validateInputSecurity($testInput, $context, $customRules);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
}

/**
 * Get security metrics
 */
function getSecurityMetrics() {
    $days = (int)($_GET['days'] ?? 7);
    
    $database = new Database();
    $db = $database->getConnection();
    
    $metrics = [
        'threat_statistics' => [],
        'sanitization_statistics' => [],
        'tampering_statistics' => [],
        'rate_limit_statistics' => []
    ];
    
    if ($db) {
        // Threat statistics
        $query = "SELECT 
                    DATE(detected_at) as date,
                    threat_level,
                    COUNT(*) as count
                  FROM input_threat_log 
                  WHERE detected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(detected_at), threat_level
                  ORDER BY date, threat_level";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['threat_statistics'] = $stmt->fetchAll();
        
        // Sanitization statistics
        $query = "SELECT 
                    DATE(sanitized_at) as date,
                    context,
                    COUNT(*) as count
                  FROM input_sanitization_log 
                  WHERE sanitized_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(sanitized_at), context
                  ORDER BY date, context";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['sanitization_statistics'] = $stmt->fetchAll();
        
        // Tampering statistics
        $query = "SELECT 
                    DATE(detected_at) as date,
                    COUNT(*) as count
                  FROM parameter_tampering_log 
                  WHERE detected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(detected_at)
                  ORDER BY date";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['tampering_statistics'] = $stmt->fetchAll();
        
        // Rate limit statistics
        $query = "SELECT 
                    DATE(last_request) as date,
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN blocked_until IS NOT NULL THEN 1 END) as blocked_requests
                  FROM input_rate_limiting 
                  WHERE last_request >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(last_request)
                  ORDER BY date";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['rate_limit_statistics'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $metrics
    ]);
}

/**
 * Export threats
 */
function exportThreats() {
    $format = $_GET['format'] ?? 'json';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM input_threat_log 
              WHERE detected_at BETWEEN ? AND ?
              ORDER BY detected_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    $threats = $stmt->fetchAll();
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="threats_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Threat Type', 'Threat Level', 'Input Source', 'IP Address',
            'Endpoint', 'User ID', 'User Type', 'Blocked', 'Detected At'
        ]);
        
        foreach ($threats as $threat) {
            fputcsv($output, [
                $threat['id'],
                $threat['threat_type'],
                $threat['threat_level'],
                $threat['input_source'],
                $threat['ip_address'],
                $threat['endpoint'],
                $threat['user_id'],
                $threat['user_type'],
                $threat['blocked'] ? 'Yes' : 'No',
                $threat['detected_at']
            ]);
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="threats_' . date('Y-m-d') . '.json"');
        
        echo json_encode([
            'export_date' => date('c'),
            'date_range' => ['start' => $startDate, 'end' => $endDate],
            'total_threats' => count($threats),
            'threats' => $threats
        ], JSON_PRETTY_PRINT);
    }
}
?>
