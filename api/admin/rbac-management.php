<?php
/**
 * RBAC MANAGEMENT API
 * Enterprise Role-Based Access Control administration
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-rbac.php';
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

// Require fresh MFA for RBAC operations
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'assign_role':
            assignRoleEndpoint();
            break;
            
        case 'revoke_role':
            revokeRoleEndpoint();
            break;
            
        case 'check_permission':
            checkPermissionEndpoint();
            break;
            
        case 'user_permissions':
            getUserPermissionsEndpoint();
            break;
            
        case 'user_roles':
            getUserRolesEndpoint();
            break;
            
        case 'create_role':
            createRoleEndpoint();
            break;
            
        case 'create_permission':
            createPermissionEndpoint();
            break;
            
        case 'assign_permission':
            assignPermissionEndpoint();
            break;
            
        case 'grant_ownership':
            grantOwnershipEndpoint();
            break;
            
        case 'audit_trail':
            getAuditTrailEndpoint();
            break;
            
        case 'roles_list':
            getRolesListEndpoint();
            break;
            
        case 'permissions_list':
            getPermissionsListEndpoint();
            break;
            
        case 'rbac_dashboard':
            getRBACDashboard();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("RBAC management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Assign role to user
 */
function assignRoleEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['user_id', 'user_type', 'role_key'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = assignRoleToUser(
        $input['user_id'],
        $input['user_type'],
        $input['role_key'],
        $_SESSION['admin_id'],
        $input['expires_at'] ?? null
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Role assigned successfully',
        'data' => $result
    ]);
}

/**
 * Revoke role from user
 */
function revokeRoleEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['user_id', 'user_type', 'role_key'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $rbac = EnterpriseRBAC::getInstance();
    $result = $rbac->revokeUserRole(
        $input['user_id'],
        $input['user_type'],
        $input['role_key'],
        $_SESSION['admin_id']
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Role revoked successfully',
        'data' => $result
    ]);
}

/**
 * Check permission
 */
function checkPermissionEndpoint() {
    $userId = $_GET['user_id'] ?? '';
    $userType = $_GET['user_type'] ?? '';
    $permissionKey = $_GET['permission_key'] ?? '';
    $resourceId = $_GET['resource_id'] ?? null;
    
    if (empty($userId) || empty($userType) || empty($permissionKey)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    $hasPermission = hasPermission($userId, $userType, $permissionKey, $resourceId);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'user_type' => $userType,
            'permission_key' => $permissionKey,
            'resource_id' => $resourceId,
            'has_permission' => $hasPermission
        ]
    ]);
}

/**
 * Get user permissions
 */
function getUserPermissionsEndpoint() {
    $userId = $_GET['user_id'] ?? '';
    $userType = $_GET['user_type'] ?? '';
    
    if (empty($userId) || empty($userType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id or user_type']);
        return;
    }
    
    $permissions = getUserPermissions($userId, $userType);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'user_type' => $userType,
            'permissions' => $permissions
        ]
    ]);
}

/**
 * Get user roles
 */
function getUserRolesEndpoint() {
    $userId = $_GET['user_id'] ?? '';
    $userType = $_GET['user_type'] ?? '';
    
    if (empty($userId) || empty($userType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_id or user_type']);
        return;
    }
    
    $rbac = EnterpriseRBAC::getInstance();
    $roles = $rbac->getUserRoles($userId, $userType);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => $userId,
            'user_type' => $userType,
            'roles' => $roles
        ]
    ]);
}

/**
 * Create new role
 */
function createRoleEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['role_key', 'role_name', 'role_level'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $rbac = EnterpriseRBAC::getInstance();
    $roleId = $rbac->createRole(
        $input['role_key'],
        $input['role_name'],
        $input['role_level'],
        $input['description'] ?? '',
        false // Not a system role
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Role created successfully',
        'data' => ['role_id' => $roleId]
    ]);
}

/**
 * Create new permission
 */
function createPermissionEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['permission_key', 'permission_name', 'category', 'resource_type', 'permission_level'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $rbac = EnterpriseRBAC::getInstance();
    $result = $rbac->createPermission(
        $input['permission_key'],
        $input['permission_name'],
        $input['category'],
        $input['resource_type'],
        $input['permission_level'],
        false // Not a system permission
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Permission created successfully',
        'data' => $result
    ]);
}

/**
 * Assign permission to role
 */
function assignPermissionEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['role_id', 'permission_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $rbac = EnterpriseRBAC::getInstance();
    $result = $rbac->assignPermissionToRole(
        $input['role_id'],
        $input['permission_id'],
        $_SESSION['admin_id'],
        $input['expires_at'] ?? null
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Permission assigned to role successfully',
        'data' => $result
    ]);
}

/**
 * Grant resource ownership
 */
function grantOwnershipEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['resource_type', 'resource_id', 'owner_id', 'owner_type', 'ownership_level'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = grantResourceOwnership(
        $input['resource_type'],
        $input['resource_id'],
        $input['owner_id'],
        $input['owner_type'],
        $input['ownership_level'],
        $_SESSION['admin_id']
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Resource ownership granted successfully',
        'data' => $result
    ]);
}

/**
 * Get audit trail
 */
function getAuditTrailEndpoint() {
    $userId = $_GET['user_id'] ?? null;
    $userType = $_GET['user_type'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $rbac = EnterpriseRBAC::getInstance();
    $auditTrail = $rbac->getPermissionAuditTrail($userId, $userType, $limit, $offset);
    
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
 * Get roles list
 */
function getRolesListEndpoint() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, role_name, role_key, role_level, description, 
                     is_system_role, created_at
              FROM rbac_roles 
              ORDER BY role_level DESC, role_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $roles = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $roles
    ]);
}

/**
 * Get permissions list
 */
function getPermissionsListEndpoint() {
    $database = new Database();
    $db = $database->getConnection();
    
    $category = $_GET['category'] ?? null;
    $resourceType = $_GET['resource_type'] ?? null;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($category) {
        $whereClause .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($resourceType) {
        $whereClause .= " AND resource_type = ?";
        $params[] = $resourceType;
    }
    
    $query = "SELECT id, permission_name, permission_key, category, 
                     resource_type, permission_level, description, 
                     is_system_permission, created_at
              FROM rbac_permissions 
              $whereClause
              ORDER BY category, resource_type, permission_level";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $permissions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $permissions
    ]);
}

/**
 * Get RBAC dashboard
 */
function getRBACDashboard() {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboard = [
        'roles_summary' => [],
        'permissions_summary' => [],
        'user_roles_summary' => [],
        'recent_audit_events' => [],
        'security_metrics' => []
    ];
    
    if ($db) {
        // Roles summary
        $query = "SELECT 
                    COUNT(*) as total_roles,
                    COUNT(CASE WHEN is_system_role = TRUE THEN 1 END) as system_roles,
                    COUNT(CASE WHEN is_system_role = FALSE THEN 1 END) as custom_roles
                  FROM rbac_roles";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['roles_summary'] = $stmt->fetch();
        
        // Permissions summary
        $query = "SELECT 
                    category,
                    COUNT(*) as permission_count
                  FROM rbac_permissions 
                  GROUP BY category
                  ORDER BY permission_count DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['permissions_summary'] = $stmt->fetchAll();
        
        // User roles summary
        $query = "SELECT 
                    user_type,
                    COUNT(DISTINCT user_id) as user_count,
                    COUNT(*) as role_assignments
                  FROM rbac_user_roles 
                  WHERE is_active = TRUE
                  GROUP BY user_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['user_roles_summary'] = $stmt->fetchAll();
        
        // Recent audit events
        $query = "SELECT 
                    user_id, user_type, permission_key, action_attempted,
                    permission_granted, timestamp
                  FROM rbac_permission_audit 
                  ORDER BY timestamp DESC 
                  LIMIT 20";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['recent_audit_events'] = $stmt->fetchAll();
        
        // Security metrics
        $query = "SELECT 
                    COUNT(*) as total_permission_checks,
                    COUNT(CASE WHEN permission_granted = TRUE THEN 1 END) as granted_permissions,
                    COUNT(CASE WHEN permission_granted = FALSE THEN 1 END) as denied_permissions,
                    COUNT(DISTINCT user_id) as active_users
                  FROM rbac_permission_audit 
                  WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['security_metrics'] = $stmt->fetch();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard
    ]);
}
?>
