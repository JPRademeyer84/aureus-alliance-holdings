<?php
/**
 * GDPR MANAGEMENT API
 * Administrative interface for GDPR compliance management
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/gdpr-compliance.php';
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

// Require fresh MFA for GDPR operations
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'dashboard':
            getGDPRDashboard();
            break;
            
        case 'consent_records':
            getConsentRecords();
            break;
            
        case 'data_requests':
            getDataSubjectRequests();
            break;
            
        case 'process_request':
            processDataSubjectRequest();
            break;
            
        case 'data_breaches':
            getDataBreaches();
            break;
            
        case 'report_breach':
            reportDataBreach();
            break;
            
        case 'processing_activities':
            getProcessingActivities();
            break;
            
        case 'retention_policies':
            getRetentionPolicies();
            break;
            
        case 'privacy_assessments':
            getPrivacyAssessments();
            break;
            
        case 'compliance_report':
            generateComplianceReport();
            break;
            
        case 'user_data_export':
            exportUserData();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("GDPR management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get GDPR compliance dashboard
 */
function getGDPRDashboard() {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboard = [
        'consent_summary' => [],
        'pending_requests' => [],
        'recent_breaches' => [],
        'compliance_metrics' => []
    ];
    
    if ($db) {
        // Consent summary
        $query = "SELECT 
                    consent_type,
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN consent_given = TRUE THEN 1 END) as consents_given,
                    COUNT(CASE WHEN withdrawn_at IS NOT NULL THEN 1 END) as consents_withdrawn
                  FROM gdpr_consent_records 
                  WHERE is_active = TRUE
                  GROUP BY consent_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['consent_summary'] = $stmt->fetchAll();
        
        // Pending requests
        $query = "SELECT 
                    request_type,
                    COUNT(*) as pending_count,
                    MIN(due_date) as earliest_due_date
                  FROM gdpr_data_requests 
                  WHERE request_status = 'pending'
                  GROUP BY request_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['pending_requests'] = $stmt->fetchAll();
        
        // Recent breaches
        $query = "SELECT 
                    breach_reference, breach_type, severity, affected_individuals_count,
                    notification_required, authority_notified, discovered_at
                  FROM gdpr_data_breaches 
                  ORDER BY discovered_at DESC 
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['recent_breaches'] = $stmt->fetchAll();
        
        // Compliance metrics
        $query = "SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN request_status = 'completed' THEN 1 END) as completed_requests,
                    COUNT(CASE WHEN due_date < NOW() AND request_status != 'completed' THEN 1 END) as overdue_requests,
                    AVG(TIMESTAMPDIFF(HOUR, requested_at, completed_at)) as avg_completion_hours
                  FROM gdpr_data_requests";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['compliance_metrics'] = $stmt->fetch();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard
    ]);
}

/**
 * Get consent records
 */
function getConsentRecords() {
    $database = new Database();
    $db = $database->getConnection();
    
    $userId = $_GET['user_id'] ?? null;
    $consentType = $_GET['consent_type'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $whereConditions = ['is_active = TRUE'];
    $params = [];
    
    if ($userId) {
        $whereConditions[] = "user_id = ?";
        $params[] = $userId;
    }
    
    if ($consentType) {
        $whereConditions[] = "consent_type = ?";
        $params[] = $consentType;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    $query = "SELECT 
                gcr.*,
                u.username,
                u.email
              FROM gdpr_consent_records gcr
              LEFT JOIN users u ON gcr.user_id = u.id
              $whereClause
              ORDER BY gcr.consent_timestamp DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $consentRecords = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'consent_records' => $consentRecords,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get data subject requests
 */
function getDataSubjectRequests() {
    $database = new Database();
    $db = $database->getConnection();
    
    $status = $_GET['status'] ?? null;
    $requestType = $_GET['request_type'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $whereConditions = [];
    $params = [];
    
    if ($status) {
        $whereConditions[] = "request_status = ?";
        $params[] = $status;
    }
    
    if ($requestType) {
        $whereConditions[] = "request_type = ?";
        $params[] = $requestType;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT 
                gdr.*,
                u.username,
                u.email,
                CASE 
                    WHEN gdr.due_date < NOW() AND gdr.request_status != 'completed' THEN TRUE
                    ELSE FALSE
                END as is_overdue
              FROM gdpr_data_requests gdr
              LEFT JOIN users u ON gdr.user_id = u.id
              $whereClause
              ORDER BY gdr.requested_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $dataRequests = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'data_requests' => $dataRequests,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Process data subject request
 */
function processDataSubjectRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = $input['request_id'] ?? '';
    $action = $input['action'] ?? '';
    
    if (empty($requestId) || empty($action)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing request_id or action']);
        return;
    }
    
    $gdpr = GDPRCompliance::getInstance();
    
    try {
        switch ($action) {
            case 'process_access':
                $result = $gdpr->processAccessRequest($requestId);
                break;
                
            case 'process_erasure':
                $result = $gdpr->processErasureRequest($requestId, $_SESSION['admin_id']);
                break;
                
            case 'process_portability':
                $result = $gdpr->processPortabilityRequest($requestId);
                break;
                
            default:
                throw new Exception("Invalid action: $action");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Request processed successfully',
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Get data breaches
 */
function getDataBreaches() {
    $database = new Database();
    $db = $database->getConnection();
    
    $severity = $_GET['severity'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $whereConditions = [];
    $params = [];
    
    if ($severity) {
        $whereConditions[] = "severity = ?";
        $params[] = $severity;
    }
    
    if ($status) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT * FROM gdpr_data_breaches 
              $whereClause
              ORDER BY discovered_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $dataBreaches = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'data_breaches' => $dataBreaches,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Report data breach
 */
function reportDataBreach() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['breach_type', 'severity', 'affected_data_categories', 'description'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = reportGDPRBreach(
        $input['breach_type'],
        $input['severity'],
        $input['affected_data_categories'],
        $input['affected_individuals_count'] ?? 0,
        $input['description'],
        $input['cause'] ?? null
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Data breach reported successfully',
        'data' => $result
    ]);
}

/**
 * Get processing activities
 */
function getProcessingActivities() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM gdpr_processing_activities WHERE is_active = TRUE ORDER BY activity_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $activities
    ]);
}

/**
 * Get retention policies
 */
function getRetentionPolicies() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM gdpr_retention_policies WHERE is_active = TRUE ORDER BY data_category";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $policies = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $policies
    ]);
}

/**
 * Get privacy assessments
 */
function getPrivacyAssessments() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT 
                gpa.*,
                gpa2.activity_name
              FROM gdpr_privacy_assessments gpa
              LEFT JOIN gdpr_processing_activities gpa2 ON gpa.processing_activity_id = gpa2.id
              ORDER BY gpa.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $assessments = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $assessments
    ]);
}

/**
 * Generate compliance report
 */
function generateComplianceReport() {
    $reportType = $_GET['report_type'] ?? 'summary';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $database = new Database();
    $db = $database->getConnection();
    
    $report = [
        'report_type' => $reportType,
        'period' => ['start' => $startDate, 'end' => $endDate],
        'generated_at' => date('c'),
        'generated_by' => $_SESSION['admin_id']
    ];
    
    // Consent metrics
    $query = "SELECT 
                consent_type,
                COUNT(*) as total_consents,
                COUNT(CASE WHEN consent_given = TRUE THEN 1 END) as active_consents,
                COUNT(CASE WHEN withdrawn_at BETWEEN ? AND ? THEN 1 END) as withdrawn_consents
              FROM gdpr_consent_records 
              GROUP BY consent_type";
    $stmt = $db->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    $report['consent_metrics'] = $stmt->fetchAll();
    
    // Request metrics
    $query = "SELECT 
                request_type,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN request_status = 'completed' THEN 1 END) as completed_requests,
                COUNT(CASE WHEN due_date < NOW() AND request_status != 'completed' THEN 1 END) as overdue_requests
              FROM gdpr_data_requests 
              WHERE requested_at BETWEEN ? AND ?
              GROUP BY request_type";
    $stmt = $db->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    $report['request_metrics'] = $stmt->fetchAll();
    
    // Breach metrics
    $query = "SELECT 
                severity,
                COUNT(*) as breach_count,
                COUNT(CASE WHEN authority_notified = TRUE THEN 1 END) as notified_count
              FROM gdpr_data_breaches 
              WHERE discovered_at BETWEEN ? AND ?
              GROUP BY severity";
    $stmt = $db->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    $report['breach_metrics'] = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
}

/**
 * Export user data
 */
function exportUserData() {
    $userId = $_GET['user_id'] ?? '';
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id']);
        return;
    }
    
    $gdpr = GDPRCompliance::getInstance();
    
    // Create access request and process it
    $request = $gdpr->createDataSubjectRequest($userId, GDPRCompliance::RIGHT_ACCESS);
    $userData = $gdpr->processAccessRequest($request['request_id']);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="user_data_' . $userId . '_' . date('Y-m-d') . '.json"');
    
    echo json_encode([
        'user_id' => $userId,
        'export_date' => date('c'),
        'data' => $userData
    ], JSON_PRETTY_PRINT);
}
?>
