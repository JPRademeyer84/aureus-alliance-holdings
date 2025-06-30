<?php
/**
 * API SECURITY MANAGEMENT
 * Enterprise API security administration and monitoring
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-api-security.php';
require_once '../config/api-security-middleware.php';
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

// Require fresh MFA for API security operations
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'dashboard':
            getAPIDashboard();
            break;
            
        case 'generate_key':
            generateAPIKey();
            break;
            
        case 'list_keys':
            listAPIKeys();
            break;
            
        case 'revoke_key':
            revokeAPIKey();
            break;
            
        case 'rate_limits':
            getRateLimits();
            break;
            
        case 'request_logs':
            getRequestLogs();
            break;
            
        case 'abuse_detection':
            getAbuseDetection();
            break;
            
        case 'endpoint_config':
            getEndpointConfig();
            break;
            
        case 'update_endpoint':
            updateEndpointConfig();
            break;
            
        case 'usage_analytics':
            getUsageAnalytics();
            break;
            
        case 'security_metrics':
            getSecurityMetrics();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("API security management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get API security dashboard
 */
function getAPIDashboard() {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboard = [
        'api_keys_summary' => [],
        'rate_limits_summary' => [],
        'recent_requests' => [],
        'abuse_alerts' => [],
        'top_endpoints' => []
    ];
    
    if ($db) {
        // API keys summary
        $query = "SELECT 
                    tier,
                    COUNT(*) as total_keys,
                    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_keys,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_keys
                  FROM api_keys 
                  GROUP BY tier";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['api_keys_summary'] = $stmt->fetchAll();
        
        // Rate limits summary
        $query = "SELECT 
                    COUNT(*) as total_limits,
                    COUNT(CASE WHEN blocked_until > NOW() THEN 1 END) as currently_blocked,
                    AVG(request_count) as avg_requests_per_window
                  FROM api_rate_limits";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['rate_limits_summary'] = $stmt->fetch();
        
        // Recent requests
        $query = "SELECT 
                    endpoint, method, status_code, response_time_ms,
                    rate_limited, abuse_detected, request_timestamp
                  FROM api_request_log 
                  ORDER BY request_timestamp DESC 
                  LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['recent_requests'] = $stmt->fetchAll();
        
        // Abuse alerts
        $query = "SELECT 
                    identifier, abuse_type, abuse_level, occurrence_count,
                    auto_blocked, last_detected
                  FROM api_abuse_detection 
                  WHERE resolved = FALSE
                  ORDER BY abuse_level DESC, last_detected DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['abuse_alerts'] = $stmt->fetchAll();
        
        // Top endpoints
        $query = "SELECT 
                    endpoint_pattern,
                    SUM(request_count) as total_requests,
                    AVG(avg_response_time_ms) as avg_response_time
                  FROM api_usage_analytics 
                  WHERE date_hour >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY endpoint_pattern
                  ORDER BY total_requests DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['top_endpoints'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard
    ]);
}

/**
 * Generate API key
 */
function generateAPIKey() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['user_id', 'user_type', 'key_name', 'tier'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $permissions = $input['permissions'] ?? [];
    $expiresAt = $input['expires_at'] ?? null;
    
    $result = generateAPIKey(
        $input['user_id'],
        $input['user_type'],
        $input['key_name'],
        $input['tier'],
        $permissions,
        $expiresAt
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'API key generated successfully',
        'data' => $result
    ]);
}

/**
 * List API keys
 */
function listAPIKeys() {
    $database = new Database();
    $db = $database->getConnection();
    
    $userId = $_GET['user_id'] ?? null;
    $tier = $_GET['tier'] ?? null;
    $active = $_GET['active'] ?? null;
    
    $whereConditions = [];
    $params = [];
    
    if ($userId) {
        $whereConditions[] = "user_id = ?";
        $params[] = $userId;
    }
    
    if ($tier) {
        $whereConditions[] = "tier = ?";
        $params[] = $tier;
    }
    
    if ($active !== null) {
        $whereConditions[] = "is_active = ?";
        $params[] = $active === 'true' ? 1 : 0;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT 
                key_id, key_name, user_id, user_type, tier, permissions,
                expires_at, last_used_at, usage_count, is_active, created_at
              FROM api_keys 
              $whereClause
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $apiKeys = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $apiKeys
    ]);
}

/**
 * Revoke API key
 */
function revokeAPIKey() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $keyId = $input['key_id'] ?? '';
    
    if (empty($keyId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing key_id']);
        return;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE api_keys SET is_active = FALSE, updated_at = NOW() WHERE key_id = ?";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([$keyId]);
    
    if ($success) {
        // Log revocation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'api_key_revoked', SecurityLogger::LEVEL_INFO,
            'API key revoked', ['key_id' => $keyId], null, $_SESSION['admin_id']);
    }
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'API key revoked successfully' : 'Failed to revoke API key'
    ]);
}

/**
 * Get rate limits
 */
function getRateLimits() {
    $database = new Database();
    $db = $database->getConnection();
    
    $blocked = $_GET['blocked'] ?? null;
    $identifierType = $_GET['identifier_type'] ?? null;
    
    $whereConditions = [];
    $params = [];
    
    if ($blocked === 'true') {
        $whereConditions[] = "blocked_until > NOW()";
    }
    
    if ($identifierType) {
        $whereConditions[] = "identifier_type = ?";
        $params[] = $identifierType;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT * FROM api_rate_limits 
              $whereClause
              ORDER BY last_request DESC
              LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rateLimits = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $rateLimits
    ]);
}

/**
 * Get request logs
 */
function getRequestLogs() {
    $database = new Database();
    $db = $database->getConnection();
    
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $endpoint = $_GET['endpoint'] ?? null;
    $statusCode = $_GET['status_code'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $whereConditions = [];
    $params = [];
    
    if ($endpoint) {
        $whereConditions[] = "endpoint LIKE ?";
        $params[] = "%$endpoint%";
    }
    
    if ($statusCode) {
        $whereConditions[] = "status_code = ?";
        $params[] = $statusCode;
    }
    
    if ($startDate) {
        $whereConditions[] = "request_timestamp >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $whereConditions[] = "request_timestamp <= ?";
        $params[] = $endDate;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT * FROM api_request_log 
              $whereClause
              ORDER BY request_timestamp DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $requestLogs = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $requestLogs,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get abuse detection
 */
function getAbuseDetection() {
    $database = new Database();
    $db = $database->getConnection();
    
    $resolved = $_GET['resolved'] ?? 'false';
    $abuseLevel = $_GET['abuse_level'] ?? null;
    
    $whereConditions = ['resolved = ?'];
    $params = [$resolved === 'true' ? 1 : 0];
    
    if ($abuseLevel) {
        $whereConditions[] = "abuse_level = ?";
        $params[] = $abuseLevel;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    $query = "SELECT * FROM api_abuse_detection 
              $whereClause
              ORDER BY abuse_level DESC, last_detected DESC
              LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $abuseDetection = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $abuseDetection
    ]);
}

/**
 * Get endpoint configuration
 */
function getEndpointConfig() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM api_endpoint_config ORDER BY endpoint_pattern";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $endpointConfig = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $endpointConfig
    ]);
}

/**
 * Update endpoint configuration
 */
function updateEndpointConfig() {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $endpointId = $_GET['endpoint_id'] ?? '';
    
    if (empty($endpointId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing endpoint_id']);
        return;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $updateFields = [];
    $params = [];
    
    $allowedFields = ['endpoint_name', 'authentication_required', 'authentication_types', 'rate_limit_tier', 'required_permissions', 'deprecated', 'security_level'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = is_array($input[$field]) ? json_encode($input[$field]) : $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $updateFields[] = "updated_at = NOW()";
    $params[] = $endpointId;
    
    $query = "UPDATE api_endpoint_config 
              SET " . implode(', ', $updateFields) . "
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $success = $stmt->execute($params);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Endpoint configuration updated successfully' : 'Failed to update endpoint configuration'
    ]);
}

/**
 * Get usage analytics
 */
function getUsageAnalytics() {
    $database = new Database();
    $db = $database->getConnection();
    
    $days = (int)($_GET['days'] ?? 7);
    $endpointPattern = $_GET['endpoint_pattern'] ?? null;
    
    $whereConditions = ['date_hour >= DATE_SUB(NOW(), INTERVAL ? DAY)'];
    $params = [$days];
    
    if ($endpointPattern) {
        $whereConditions[] = "endpoint_pattern = ?";
        $params[] = $endpointPattern;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    $query = "SELECT 
                DATE(date_hour) as date,
                endpoint_pattern,
                SUM(request_count) as total_requests,
                SUM(error_count) as total_errors,
                AVG(avg_response_time_ms) as avg_response_time,
                SUM(total_bytes_transferred) as total_bytes,
                SUM(rate_limited_requests) as rate_limited_requests,
                SUM(abuse_detected_requests) as abuse_detected_requests
              FROM api_usage_analytics 
              $whereClause
              GROUP BY DATE(date_hour), endpoint_pattern
              ORDER BY date DESC, total_requests DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $analytics = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $analytics
    ]);
}

/**
 * Get security metrics
 */
function getSecurityMetrics() {
    $database = new Database();
    $db = $database->getConnection();
    
    $metrics = [
        'request_metrics' => [],
        'security_events' => [],
        'performance_metrics' => []
    ];
    
    if ($db) {
        // Request metrics
        $query = "SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                    COUNT(CASE WHEN rate_limited = TRUE THEN 1 END) as rate_limited_requests,
                    COUNT(CASE WHEN abuse_detected = TRUE THEN 1 END) as abuse_detected_requests,
                    AVG(response_time_ms) as avg_response_time
                  FROM api_request_log 
                  WHERE request_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $metrics['request_metrics'] = $stmt->fetch();
        
        // Security events
        $query = "SELECT 
                    abuse_type,
                    COUNT(*) as occurrence_count,
                    MAX(abuse_level) as max_level
                  FROM api_abuse_detection 
                  WHERE last_detected >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY abuse_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $metrics['security_events'] = $stmt->fetchAll();
        
        // Performance metrics
        $query = "SELECT 
                    endpoint_pattern,
                    AVG(avg_response_time_ms) as avg_response_time,
                    SUM(request_count) as total_requests
                  FROM api_usage_analytics 
                  WHERE date_hour >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY endpoint_pattern
                  ORDER BY avg_response_time DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $metrics['performance_metrics'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $metrics
    ]);
}
?>
