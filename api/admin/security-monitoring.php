<?php
/**
 * SECURITY MONITORING DASHBOARD API
 * Real-time security event monitoring and alerting
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
$action = $_GET['action'] ?? '';

try {
    $logger = SecurityLogger::getInstance();
    
    switch ($action) {
        case 'dashboard':
            getDashboardData($logger);
            break;
            
        case 'events':
            getSecurityEvents($logger);
            break;
            
        case 'alerts':
            getSecurityAlerts($logger);
            break;
            
        case 'acknowledge_alert':
            acknowledgeAlert($logger);
            break;
            
        case 'metrics':
            getSecurityMetrics($logger);
            break;
            
        case 'export':
            exportSecurityData($logger);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Security monitoring error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Get security dashboard overview data
 */
function getDashboardData($logger) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get recent events summary
    $eventsSummary = [];
    $eventTypes = [
        SecurityLogger::EVENT_AUTHENTICATION,
        SecurityLogger::EVENT_FILE_UPLOAD,
        SecurityLogger::EVENT_CORS,
        SecurityLogger::EVENT_FINANCIAL,
        SecurityLogger::EVENT_ADMIN
    ];
    
    foreach ($eventTypes as $type) {
        $query = "SELECT 
                    security_level,
                    COUNT(*) as count 
                  FROM security_events 
                  WHERE event_type = ? 
                    AND event_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  GROUP BY security_level";
        $stmt = $db->prepare($query);
        $stmt->execute([$type]);
        $eventsSummary[$type] = $stmt->fetchAll();
    }
    
    // Get active alerts count
    $alertsQuery = "SELECT alert_level, COUNT(*) as count 
                   FROM security_alerts 
                   WHERE acknowledged = FALSE 
                   GROUP BY alert_level";
    $stmt = $db->prepare($alertsQuery);
    $stmt->execute();
    $activeAlerts = $stmt->fetchAll();
    
    // Get recent critical events
    $recentCritical = $logger->getRecentEvents(10, SecurityLogger::LEVEL_CRITICAL);
    
    // Get hourly event trends (last 24 hours)
    $trendsQuery = "SELECT 
                      DATE_FORMAT(event_timestamp, '%Y-%m-%d %H:00:00') as hour,
                      event_type,
                      security_level,
                      COUNT(*) as count
                    FROM security_events 
                    WHERE event_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY hour, event_type, security_level
                    ORDER BY hour DESC";
    $stmt = $db->prepare($trendsQuery);
    $stmt->execute();
    $trends = $stmt->fetchAll();
    
    // Get top IP addresses by event count
    $topIpsQuery = "SELECT 
                      ip_address,
                      COUNT(*) as event_count,
                      COUNT(CASE WHEN security_level IN ('critical', 'emergency') THEN 1 END) as critical_count
                    FROM security_events 
                    WHERE event_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY ip_address 
                    ORDER BY event_count DESC 
                    LIMIT 10";
    $stmt = $db->prepare($topIpsQuery);
    $stmt->execute();
    $topIps = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'events_summary' => $eventsSummary,
            'active_alerts' => $activeAlerts,
            'recent_critical' => $recentCritical,
            'trends' => $trends,
            'top_ips' => $topIps,
            'last_updated' => date('c')
        ]
    ]);
}

/**
 * Get security events with filtering
 */
function getSecurityEvents($logger) {
    $limit = min((int)($_GET['limit'] ?? 100), 1000);
    $level = $_GET['level'] ?? null;
    $eventType = $_GET['event_type'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT * FROM security_events WHERE 1=1";
    $params = [];
    
    if ($level) {
        $query .= " AND security_level = ?";
        $params[] = $level;
    }
    
    if ($eventType) {
        $query .= " AND event_type = ?";
        $params[] = $eventType;
    }
    
    if ($startDate) {
        $query .= " AND event_timestamp >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND event_timestamp <= ?";
        $params[] = $endDate;
    }
    
    $query .= " ORDER BY event_timestamp DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
    // Decode JSON event data
    foreach ($events as &$event) {
        $event['event_data'] = json_decode($event['event_data'], true);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $events,
        'count' => count($events)
    ]);
}

/**
 * Get security alerts
 */
function getSecurityAlerts($logger) {
    $includeAcknowledged = $_GET['include_acknowledged'] ?? 'false';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT * FROM security_alerts";
    if ($includeAcknowledged !== 'true') {
        $query .= " WHERE acknowledged = FALSE";
    }
    $query .= " ORDER BY alert_level DESC, created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $alerts = $stmt->fetchAll();
    
    // Decode JSON alert data
    foreach ($alerts as &$alert) {
        $alert['alert_data'] = json_decode($alert['alert_data'], true);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $alerts,
        'count' => count($alerts)
    ]);
}

/**
 * Acknowledge a security alert
 */
function acknowledgeAlert($logger) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $alertId = $input['alert_id'] ?? '';
    
    if (empty($alertId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Alert ID required']);
        return;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "UPDATE security_alerts 
              SET acknowledged = TRUE, 
                  acknowledged_by = ?, 
                  acknowledged_at = NOW() 
              WHERE id = ?";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([$_SESSION['admin_id'], $alertId]);
    
    if ($success) {
        // Log the acknowledgment
        logSecurityEvent(
            SecurityLogger::EVENT_ADMIN,
            'alert_acknowledged',
            SecurityLogger::LEVEL_INFO,
            "Security alert acknowledged",
            ['alert_id' => $alertId],
            null,
            $_SESSION['admin_id']
        );
        
        echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to acknowledge alert']);
    }
}

/**
 * Get security metrics
 */
function getSecurityMetrics($logger) {
    $timeWindow = $_GET['time_window'] ?? 'hour';
    $hours = min((int)($_GET['hours'] ?? 24), 168); // Max 1 week
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT 
                metric_type,
                SUM(metric_value) as total_value,
                window_start
              FROM security_metrics 
              WHERE time_window = ? 
                AND window_start >= DATE_SUB(NOW(), INTERVAL ? HOUR)
              GROUP BY metric_type, window_start
              ORDER BY window_start DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$timeWindow, $hours]);
    $metrics = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $metrics,
        'time_window' => $timeWindow,
        'hours' => $hours
    ]);
}

/**
 * Export security data
 */
function exportSecurityData($logger) {
    $format = $_GET['format'] ?? 'json';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT * FROM security_events 
              WHERE DATE(event_timestamp) BETWEEN ? AND ?
              ORDER BY event_timestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    $events = $stmt->fetchAll();
    
    // Decode JSON data
    foreach ($events as &$event) {
        $event['event_data'] = json_decode($event['event_data'], true);
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="security_events_' . $startDate . '_to_' . $endDate . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Event Type', 'Event Subtype', 'Security Level', 'User ID', 
            'Admin ID', 'IP Address', 'Event Message', 'Timestamp'
        ]);
        
        foreach ($events as $event) {
            fputcsv($output, [
                $event['id'],
                $event['event_type'],
                $event['event_subtype'],
                $event['security_level'],
                $event['user_id'],
                $event['admin_id'],
                $event['ip_address'],
                $event['event_message'],
                $event['event_timestamp']
            ]);
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="security_events_' . $startDate . '_to_' . $endDate . '.json"');
        
        echo json_encode([
            'export_info' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_events' => count($events),
                'exported_at' => date('c')
            ],
            'events' => $events
        ], JSON_PRETTY_PRINT);
    }
}
?>
