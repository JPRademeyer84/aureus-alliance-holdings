<?php
/**
 * DATABASE ENCRYPTION MANAGEMENT API
 * Enterprise-grade database encryption administration
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-database-encryption.php';
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

// Require fresh MFA for database encryption operations
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_policy':
            createEncryptionPolicyEndpoint();
            break;
            
        case 'encrypt_table':
            encryptTableDataEndpoint();
            break;
            
        case 'enable_tde':
            enableTDEEndpoint();
            break;
            
        case 'rotate_keys':
            rotateKeysEndpoint();
            break;
            
        case 'encryption_status':
            getEncryptionStatus();
            break;
            
        case 'compliance_report':
            getComplianceReport();
            break;
            
        case 'performance_metrics':
            getPerformanceMetrics();
            break;
            
        case 'audit_trail':
            getAuditTrail();
            break;
            
        case 'key_management':
            getKeyManagement();
            break;
            
        case 'data_classification':
            manageDataClassification();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Database encryption management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Create encryption policy
 */
function createEncryptionPolicyEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['table_name', 'column_name', 'encryption_level'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = createEncryptionPolicy(
        $input['table_name'],
        $input['column_name'],
        $input['encryption_level'],
        $input['compliance_requirement'] ?? null,
        $_SESSION['admin_id']
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Encryption policy created successfully',
        'data' => $result
    ]);
}

/**
 * Encrypt table data
 */
function encryptTableDataEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['table_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing table_name']);
        return;
    }
    
    $result = encryptTableData($input['table_name'], $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Table encryption completed',
        'data' => $result
    ]);
}

/**
 * Enable TDE
 */
function enableTDEEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['table_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing table_name']);
        return;
    }
    
    $encryptionLevel = $input['encryption_level'] ?? EnterpriseDatabaseEncryption::ENCRYPTION_LEVEL_STANDARD;
    
    $result = enableTDE($input['table_name'], $encryptionLevel, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'TDE enabled successfully',
        'data' => $result
    ]);
}

/**
 * Rotate encryption keys
 */
function rotateKeysEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tableName = $input['table_name'] ?? null;
    
    $result = rotateEncryptionKeys($tableName, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Key rotation completed',
        'data' => $result
    ]);
}

/**
 * Get encryption status
 */
function getEncryptionStatus() {
    $database = new Database();
    $db = $database->getConnection();
    
    $status = [
        'encryption_policies' => [],
        'encrypted_tables' => [],
        'key_statistics' => [],
        'tde_status' => []
    ];
    
    if ($db) {
        // Get encryption policies
        $query = "SELECT 
                    table_name, column_name, encryption_level, 
                    compliance_requirement, created_at
                  FROM encryption_policies 
                  WHERE is_active = TRUE
                  ORDER BY table_name, column_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $status['encryption_policies'] = $stmt->fetchAll();
        
        // Get encrypted tables summary
        $query = "SELECT 
                    table_name,
                    COUNT(*) as encrypted_columns,
                    AVG(encryption_level) as avg_encryption_level
                  FROM encryption_policies 
                  WHERE is_active = TRUE
                  GROUP BY table_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $status['encrypted_tables'] = $stmt->fetchAll();
        
        // Get key statistics
        $query = "SELECT 
                    key_type,
                    COUNT(*) as total_keys,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_keys,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_keys
                  FROM database_encryption_keys
                  GROUP BY key_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $status['key_statistics'] = $stmt->fetchAll();
        
        // Get TDE status
        $query = "SELECT 
                    table_name,
                    policy_type,
                    encryption_level,
                    created_at
                  FROM encryption_policies 
                  WHERE policy_type = 'table' AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $status['tde_status'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $status
    ]);
}

/**
 * Get compliance report
 */
function getComplianceReport() {
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    $report = generateComplianceReport($startDate, $endDate);
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
}

/**
 * Get performance metrics
 */
function getPerformanceMetrics() {
    $days = (int)($_GET['days'] ?? 7);
    
    $database = new Database();
    $db = $database->getConnection();
    
    $metrics = [
        'operation_performance' => [],
        'encryption_throughput' => [],
        'resource_usage' => []
    ];
    
    if ($db) {
        // Operation performance
        $query = "SELECT 
                    operation_type,
                    COUNT(*) as operation_count,
                    AVG(operation_duration_ms) as avg_duration,
                    MAX(operation_duration_ms) as max_duration,
                    MIN(operation_duration_ms) as min_duration
                  FROM encryption_performance_metrics 
                  WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY operation_type";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['operation_performance'] = $stmt->fetchAll();
        
        // Daily throughput
        $query = "SELECT 
                    DATE(recorded_at) as date,
                    SUM(record_count) as total_records,
                    AVG(encryption_throughput_mbps) as avg_throughput
                  FROM encryption_performance_metrics 
                  WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(recorded_at)
                  ORDER BY date";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['encryption_throughput'] = $stmt->fetchAll();
        
        // Resource usage trends
        $query = "SELECT 
                    DATE(recorded_at) as date,
                    AVG(cpu_usage_percent) as avg_cpu,
                    AVG(memory_usage_mb) as avg_memory
                  FROM encryption_performance_metrics 
                  WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND cpu_usage_percent IS NOT NULL
                  GROUP BY DATE(recorded_at)
                  ORDER BY date";
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $metrics['resource_usage'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $metrics
    ]);
}

/**
 * Get audit trail
 */
function getAuditTrail() {
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $operationType = $_GET['operation_type'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($operationType) {
        $whereClause .= " AND operation_type = ?";
        $params[] = $operationType;
    }
    
    $query = "SELECT 
                eat.id, eat.operation_type, eat.table_name, eat.column_name,
                eat.operation_details, eat.operation_timestamp, eat.success,
                au.username as performed_by_username
              FROM encryption_audit_trail eat
              LEFT JOIN admin_users au ON eat.performed_by = au.id
              $whereClause
              ORDER BY eat.operation_timestamp DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $auditTrail = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'audit_trail' => $auditTrail,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get key management information
 */
function getKeyManagement() {
    $database = new Database();
    $db = $database->getConnection();
    
    $keyInfo = [
        'active_keys' => [],
        'expired_keys' => [],
        'rotation_schedule' => []
    ];
    
    if ($db) {
        // Active keys
        $query = "SELECT 
                    key_id, key_type, algorithm, associated_table, associated_column,
                    created_at, expires_at, last_rotated, rotation_count
                  FROM database_encryption_keys 
                  WHERE is_active = TRUE
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $keyInfo['active_keys'] = $stmt->fetchAll();
        
        // Expired keys
        $query = "SELECT 
                    key_id, key_type, associated_table, associated_column, expires_at
                  FROM database_encryption_keys 
                  WHERE expires_at <= NOW() AND is_active = TRUE
                  ORDER BY expires_at";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $keyInfo['expired_keys'] = $stmt->fetchAll();
        
        // Keys due for rotation
        $query = "SELECT 
                    key_id, key_type, associated_table, associated_column,
                    last_rotated, rotation_schedule
                  FROM database_encryption_keys 
                  WHERE is_active = TRUE 
                  AND (last_rotated < DATE_SUB(NOW(), INTERVAL 90 DAY) OR last_rotated IS NULL)
                  ORDER BY last_rotated ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $keyInfo['rotation_schedule'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $keyInfo
    ]);
}

/**
 * Manage data classification
 */
function manageDataClassification() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get current classifications
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM data_classification ORDER BY table_name, column_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $classifications = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $classifications
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create or update classification
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['table_name', 'column_name', 'classification_level'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        $classificationId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO data_classification (
            id, table_name, column_name, classification_level, data_category,
            compliance_tags, retention_period_days, anonymization_required, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            classification_level = VALUES(classification_level),
            data_category = VALUES(data_category),
            compliance_tags = VALUES(compliance_tags),
            retention_period_days = VALUES(retention_period_days),
            anonymization_required = VALUES(anonymization_required),
            reviewed_at = NOW()";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $classificationId,
            $input['table_name'],
            $input['column_name'],
            $input['classification_level'],
            $input['data_category'] ?? null,
            json_encode($input['compliance_tags'] ?? []),
            $input['retention_period_days'] ?? null,
            $input['anonymization_required'] ?? false,
            $_SESSION['admin_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Data classification updated successfully'
        ]);
    }
}
?>
