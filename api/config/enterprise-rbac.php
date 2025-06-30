<?php
/**
 * ENTERPRISE ROLE-BASED ACCESS CONTROL SYSTEM
 * Implements granular permissions with principle of least privilege
 */

require_once 'security-logger.php';

class EnterpriseRBAC {
    private static $instance = null;
    private $db;
    
    // Permission categories
    const CATEGORY_USER_MANAGEMENT = 'user_management';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_CONTENT = 'content';
    const CATEGORY_ANALYTICS = 'analytics';
    
    // Permission levels
    const LEVEL_READ = 'read';
    const LEVEL_WRITE = 'write';
    const LEVEL_DELETE = 'delete';
    const LEVEL_ADMIN = 'admin';
    
    // Resource types
    const RESOURCE_USERS = 'users';
    const RESOURCE_ADMINS = 'admins';
    const RESOURCE_WALLETS = 'wallets';
    const RESOURCE_TRANSACTIONS = 'transactions';
    const RESOURCE_KYC = 'kyc';
    const RESOURCE_COMMISSIONS = 'commissions';
    const RESOURCE_PACKAGES = 'packages';
    const RESOURCE_CHAT = 'chat';
    const RESOURCE_REPORTS = 'reports';
    const RESOURCE_SETTINGS = 'settings';
    const RESOURCE_SECURITY = 'security';
    const RESOURCE_LOGS = 'logs';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeRBACTables();
        $this->initializeDefaultPermissions();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize RBAC database tables
     */
    private function initializeRBACTables() {
        $tables = [
            // Permissions definition
            "CREATE TABLE IF NOT EXISTS rbac_permissions (
                id VARCHAR(36) PRIMARY KEY,
                permission_name VARCHAR(100) NOT NULL UNIQUE,
                permission_key VARCHAR(100) NOT NULL UNIQUE,
                category VARCHAR(50) NOT NULL,
                resource_type VARCHAR(50) NOT NULL,
                permission_level ENUM('read', 'write', 'delete', 'admin') NOT NULL,
                description TEXT,
                is_system_permission BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_resource_type (resource_type),
                INDEX idx_permission_level (permission_level)
            )",
            
            // Roles definition
            "CREATE TABLE IF NOT EXISTS rbac_roles (
                id VARCHAR(36) PRIMARY KEY,
                role_name VARCHAR(50) NOT NULL UNIQUE,
                role_key VARCHAR(50) NOT NULL UNIQUE,
                role_level INT NOT NULL,
                description TEXT,
                is_system_role BOOLEAN DEFAULT FALSE,
                max_session_duration INT DEFAULT 3600,
                require_mfa BOOLEAN DEFAULT FALSE,
                ip_restrictions JSON,
                time_restrictions JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_role_level (role_level),
                INDEX idx_role_key (role_key)
            )",
            
            // Role-Permission mapping
            "CREATE TABLE IF NOT EXISTS rbac_role_permissions (
                id VARCHAR(36) PRIMARY KEY,
                role_id VARCHAR(36) NOT NULL,
                permission_id VARCHAR(36) NOT NULL,
                granted_by VARCHAR(36),
                granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                conditions JSON,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (role_id) REFERENCES rbac_roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES rbac_permissions(id) ON DELETE CASCADE,
                UNIQUE KEY unique_role_permission (role_id, permission_id),
                INDEX idx_role_id (role_id),
                INDEX idx_permission_id (permission_id),
                INDEX idx_expires_at (expires_at)
            )",
            
            // User-Role assignments
            "CREATE TABLE IF NOT EXISTS rbac_user_roles (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                user_type ENUM('admin', 'user') NOT NULL,
                role_id VARCHAR(36) NOT NULL,
                assigned_by VARCHAR(36),
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (role_id) REFERENCES rbac_roles(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_user_type (user_type),
                INDEX idx_role_id (role_id),
                INDEX idx_expires_at (expires_at)
            )",
            
            // Permission audit trail
            "CREATE TABLE IF NOT EXISTS rbac_permission_audit (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                user_type ENUM('admin', 'user') NOT NULL,
                permission_key VARCHAR(100) NOT NULL,
                resource_type VARCHAR(50) NOT NULL,
                resource_id VARCHAR(36),
                action_attempted VARCHAR(100) NOT NULL,
                permission_granted BOOLEAN NOT NULL,
                denial_reason TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                session_id VARCHAR(100),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_permission_key (permission_key),
                INDEX idx_resource_type (resource_type),
                INDEX idx_timestamp (timestamp),
                INDEX idx_permission_granted (permission_granted)
            )",
            
            // Resource ownership
            "CREATE TABLE IF NOT EXISTS rbac_resource_ownership (
                id VARCHAR(36) PRIMARY KEY,
                resource_type VARCHAR(50) NOT NULL,
                resource_id VARCHAR(36) NOT NULL,
                owner_id VARCHAR(36) NOT NULL,
                owner_type ENUM('admin', 'user') NOT NULL,
                ownership_level ENUM('owner', 'manager', 'contributor', 'viewer') NOT NULL,
                granted_by VARCHAR(36),
                granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                UNIQUE KEY unique_resource_owner (resource_type, resource_id, owner_id),
                INDEX idx_resource (resource_type, resource_id),
                INDEX idx_owner (owner_id, owner_type),
                INDEX idx_ownership_level (ownership_level)
            )",
            
            // Permission conditions and constraints
            "CREATE TABLE IF NOT EXISTS rbac_permission_conditions (
                id VARCHAR(36) PRIMARY KEY,
                permission_id VARCHAR(36) NOT NULL,
                condition_type ENUM('time', 'ip', 'location', 'mfa', 'approval', 'custom') NOT NULL,
                condition_data JSON NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (permission_id) REFERENCES rbac_permissions(id) ON DELETE CASCADE,
                INDEX idx_permission_id (permission_id),
                INDEX idx_condition_type (condition_type)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create RBAC table: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Initialize default permissions and roles
     */
    private function initializeDefaultPermissions() {
        // Check if permissions already exist
        $query = "SELECT COUNT(*) FROM rbac_permissions";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Already initialized
        }
        
        // Define default permissions
        $permissions = [
            // User Management
            ['users.read', 'Read Users', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_USERS, self::LEVEL_READ],
            ['users.write', 'Manage Users', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_USERS, self::LEVEL_WRITE],
            ['users.delete', 'Delete Users', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_USERS, self::LEVEL_DELETE],
            ['admins.read', 'Read Admins', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_ADMINS, self::LEVEL_READ],
            ['admins.write', 'Manage Admins', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_ADMINS, self::LEVEL_WRITE],
            ['admins.delete', 'Delete Admins', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_ADMINS, self::LEVEL_DELETE],
            
            // Financial
            ['wallets.read', 'Read Wallets', self::CATEGORY_FINANCIAL, self::RESOURCE_WALLETS, self::LEVEL_READ],
            ['wallets.write', 'Manage Wallets', self::CATEGORY_FINANCIAL, self::RESOURCE_WALLETS, self::LEVEL_WRITE],
            ['wallets.admin', 'Admin Wallets', self::CATEGORY_FINANCIAL, self::RESOURCE_WALLETS, self::LEVEL_ADMIN],
            ['transactions.read', 'Read Transactions', self::CATEGORY_FINANCIAL, self::RESOURCE_TRANSACTIONS, self::LEVEL_READ],
            ['transactions.write', 'Process Transactions', self::CATEGORY_FINANCIAL, self::RESOURCE_TRANSACTIONS, self::LEVEL_WRITE],
            ['transactions.admin', 'Admin Transactions', self::CATEGORY_FINANCIAL, self::RESOURCE_TRANSACTIONS, self::LEVEL_ADMIN],
            ['commissions.read', 'Read Commissions', self::CATEGORY_FINANCIAL, self::RESOURCE_COMMISSIONS, self::LEVEL_READ],
            ['commissions.write', 'Manage Commissions', self::CATEGORY_FINANCIAL, self::RESOURCE_COMMISSIONS, self::LEVEL_WRITE],
            ['commissions.admin', 'Admin Commissions', self::CATEGORY_FINANCIAL, self::RESOURCE_COMMISSIONS, self::LEVEL_ADMIN],
            
            // Security
            ['security.read', 'Read Security', self::CATEGORY_SECURITY, self::RESOURCE_SECURITY, self::LEVEL_READ],
            ['security.write', 'Manage Security', self::CATEGORY_SECURITY, self::RESOURCE_SECURITY, self::LEVEL_WRITE],
            ['security.admin', 'Admin Security', self::CATEGORY_SECURITY, self::RESOURCE_SECURITY, self::LEVEL_ADMIN],
            ['logs.read', 'Read Logs', self::CATEGORY_SECURITY, self::RESOURCE_LOGS, self::LEVEL_READ],
            ['logs.admin', 'Admin Logs', self::CATEGORY_SECURITY, self::RESOURCE_LOGS, self::LEVEL_ADMIN],
            
            // KYC
            ['kyc.read', 'Read KYC', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_KYC, self::LEVEL_READ],
            ['kyc.write', 'Manage KYC', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_KYC, self::LEVEL_WRITE],
            ['kyc.admin', 'Admin KYC', self::CATEGORY_USER_MANAGEMENT, self::RESOURCE_KYC, self::LEVEL_ADMIN],
            
            // Content & Communication
            ['chat.read', 'Read Chat', self::CATEGORY_CONTENT, self::RESOURCE_CHAT, self::LEVEL_READ],
            ['chat.write', 'Manage Chat', self::CATEGORY_CONTENT, self::RESOURCE_CHAT, self::LEVEL_WRITE],
            ['packages.read', 'Read Packages', self::CATEGORY_CONTENT, self::RESOURCE_PACKAGES, self::LEVEL_READ],
            ['packages.write', 'Manage Packages', self::CATEGORY_CONTENT, self::RESOURCE_PACKAGES, self::LEVEL_WRITE],
            
            // System
            ['settings.read', 'Read Settings', self::CATEGORY_SYSTEM, self::RESOURCE_SETTINGS, self::LEVEL_READ],
            ['settings.write', 'Manage Settings', self::CATEGORY_SYSTEM, self::RESOURCE_SETTINGS, self::LEVEL_WRITE],
            ['settings.admin', 'Admin Settings', self::CATEGORY_SYSTEM, self::RESOURCE_SETTINGS, self::LEVEL_ADMIN],
            ['reports.read', 'Read Reports', self::CATEGORY_ANALYTICS, self::RESOURCE_REPORTS, self::LEVEL_READ],
            ['reports.admin', 'Admin Reports', self::CATEGORY_ANALYTICS, self::RESOURCE_REPORTS, self::LEVEL_ADMIN]
        ];
        
        // Insert permissions
        foreach ($permissions as $perm) {
            $this->createPermission($perm[0], $perm[1], $perm[2], $perm[3], $perm[4], true);
        }
        
        // Create default roles
        $this->createDefaultRoles();
    }
    
    /**
     * Create a permission
     */
    public function createPermission($permissionKey, $name, $category, $resourceType, $level, $isSystem = false) {
        $permissionId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO rbac_permissions (
            id, permission_name, permission_key, category, resource_type, 
            permission_level, is_system_permission
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $permissionId, $name, $permissionKey, $category, 
            $resourceType, $level, $isSystem
        ]);
    }
    
    /**
     * Create default roles
     */
    private function createDefaultRoles() {
        $roles = [
            [
                'super_admin', 'Super Administrator', 100,
                'Full system access with all permissions',
                ['*'] // All permissions
            ],
            [
                'admin', 'Administrator', 80,
                'Administrative access with most permissions',
                [
                    'users.*', 'wallets.read', 'wallets.write', 'transactions.*',
                    'commissions.*', 'kyc.*', 'packages.*', 'chat.*',
                    'settings.read', 'settings.write', 'reports.*', 'logs.read'
                ]
            ],
            [
                'financial_admin', 'Financial Administrator', 70,
                'Financial operations and oversight',
                [
                    'wallets.*', 'transactions.*', 'commissions.*',
                    'reports.read', 'logs.read', 'users.read'
                ]
            ],
            [
                'security_admin', 'Security Administrator', 75,
                'Security operations and monitoring',
                [
                    'security.*', 'logs.*', 'users.read', 'admins.read',
                    'settings.read', 'reports.read'
                ]
            ],
            [
                'kyc_admin', 'KYC Administrator', 60,
                'KYC verification and compliance',
                [
                    'kyc.*', 'users.read', 'users.write', 'reports.read'
                ]
            ],
            [
                'chat_support', 'Chat Support', 40,
                'Customer support and chat management',
                [
                    'chat.*', 'users.read', 'kyc.read'
                ]
            ],
            [
                'viewer', 'Viewer', 20,
                'Read-only access to basic information',
                [
                    'users.read', 'transactions.read', 'reports.read'
                ]
            ]
        ];
        
        foreach ($roles as $roleData) {
            $roleId = $this->createRole($roleData[0], $roleData[1], $roleData[2], $roleData[3], true);
            if ($roleId) {
                $this->assignPermissionsToRole($roleId, $roleData[4]);
            }
        }
    }
    
    /**
     * Create a role
     */
    public function createRole($roleKey, $name, $level, $description, $isSystem = false) {
        $roleId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO rbac_roles (
            id, role_name, role_key, role_level, description, is_system_role
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$roleId, $name, $roleKey, $level, $description, $isSystem]);
        
        return $success ? $roleId : false;
    }
    
    /**
     * Assign permissions to role
     */
    private function assignPermissionsToRole($roleId, $permissionPatterns) {
        foreach ($permissionPatterns as $pattern) {
            if ($pattern === '*') {
                // Assign all permissions
                $query = "SELECT id FROM rbac_permissions";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($permissions as $permissionId) {
                    $this->assignPermissionToRole($roleId, $permissionId);
                }
            } elseif (strpos($pattern, '*') !== false) {
                // Pattern matching
                $prefix = str_replace('*', '', $pattern);
                $query = "SELECT id FROM rbac_permissions WHERE permission_key LIKE ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$prefix . '%']);
                $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($permissions as $permissionId) {
                    $this->assignPermissionToRole($roleId, $permissionId);
                }
            } else {
                // Exact match
                $query = "SELECT id FROM rbac_permissions WHERE permission_key = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$pattern]);
                $permissionId = $stmt->fetchColumn();
                
                if ($permissionId) {
                    $this->assignPermissionToRole($roleId, $permissionId);
                }
            }
        }
    }
    
    /**
     * Assign permission to role
     */
    public function assignPermissionToRole($roleId, $permissionId, $grantedBy = null, $expiresAt = null) {
        $assignmentId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO rbac_role_permissions (
            id, role_id, permission_id, granted_by, expires_at
        ) VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            granted_by = VALUES(granted_by),
            expires_at = VALUES(expires_at),
            is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$assignmentId, $roleId, $permissionId, $grantedBy, $expiresAt]);
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser($userId, $userType, $roleKey, $assignedBy = null, $expiresAt = null) {
        // Get role ID
        $query = "SELECT id FROM rbac_roles WHERE role_key = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$roleKey]);
        $roleId = $stmt->fetchColumn();

        if (!$roleId) {
            throw new Exception("Role not found: $roleKey");
        }

        $assignmentId = bin2hex(random_bytes(16));

        $query = "INSERT INTO rbac_user_roles (
            id, user_id, user_type, role_id, assigned_by, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            assigned_by = VALUES(assigned_by),
            expires_at = VALUES(expires_at),
            is_active = TRUE";

        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$assignmentId, $userId, $userType, $roleId, $assignedBy, $expiresAt]);

        // Log role assignment
        $this->logPermissionAudit($userId, $userType, 'role.assign', 'roles', $roleId,
            "Role $roleKey assigned", true);

        return $success;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($userId, $userType, $permissionKey, $resourceId = null, $context = []) {
        try {
            // Get user's roles
            $userRoles = $this->getUserRoles($userId, $userType);

            if (empty($userRoles)) {
                $this->logPermissionAudit($userId, $userType, $permissionKey,
                    $this->getResourceTypeFromPermission($permissionKey), $resourceId,
                    'Permission check', false, 'No roles assigned');
                return false;
            }

            // Check each role for the permission
            foreach ($userRoles as $role) {
                if ($this->roleHasPermission($role['role_id'], $permissionKey)) {
                    // Check additional conditions
                    if ($this->checkPermissionConditions($role['role_id'], $permissionKey, $context)) {
                        // Check resource ownership if applicable
                        if ($resourceId && !$this->checkResourceAccess($userId, $userType,
                            $this->getResourceTypeFromPermission($permissionKey), $resourceId, $permissionKey)) {
                            continue;
                        }

                        $this->logPermissionAudit($userId, $userType, $permissionKey,
                            $this->getResourceTypeFromPermission($permissionKey), $resourceId,
                            'Permission check', true);
                        return true;
                    }
                }
            }

            $this->logPermissionAudit($userId, $userType, $permissionKey,
                $this->getResourceTypeFromPermission($permissionKey), $resourceId,
                'Permission check', false, 'Permission denied');
            return false;

        } catch (Exception $e) {
            error_log("Permission check error: " . $e->getMessage());
            $this->logPermissionAudit($userId, $userType, $permissionKey,
                $this->getResourceTypeFromPermission($permissionKey), $resourceId,
                'Permission check', false, 'System error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's roles
     */
    public function getUserRoles($userId, $userType) {
        $query = "SELECT ur.role_id, r.role_key, r.role_name, r.role_level
                  FROM rbac_user_roles ur
                  JOIN rbac_roles r ON ur.role_id = r.id
                  WHERE ur.user_id = ? AND ur.user_type = ?
                  AND ur.is_active = TRUE
                  AND (ur.expires_at IS NULL OR ur.expires_at > NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType]);
        return $stmt->fetchAll();
    }

    /**
     * Check if role has permission
     */
    public function roleHasPermission($roleId, $permissionKey) {
        $query = "SELECT COUNT(*) FROM rbac_role_permissions rp
                  JOIN rbac_permissions p ON rp.permission_id = p.id
                  WHERE rp.role_id = ? AND p.permission_key = ?
                  AND rp.is_active = TRUE
                  AND (rp.expires_at IS NULL OR rp.expires_at > NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$roleId, $permissionKey]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check permission conditions
     */
    private function checkPermissionConditions($roleId, $permissionKey, $context) {
        // Get permission ID
        $query = "SELECT id FROM rbac_permissions WHERE permission_key = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$permissionKey]);
        $permissionId = $stmt->fetchColumn();

        if (!$permissionId) {
            return false;
        }

        // Get conditions
        $query = "SELECT condition_type, condition_data FROM rbac_permission_conditions
                  WHERE permission_id = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$permissionId]);
        $conditions = $stmt->fetchAll();

        foreach ($conditions as $condition) {
            $conditionData = json_decode($condition['condition_data'], true);

            switch ($condition['condition_type']) {
                case 'time':
                    if (!$this->checkTimeCondition($conditionData)) {
                        return false;
                    }
                    break;

                case 'ip':
                    if (!$this->checkIPCondition($conditionData)) {
                        return false;
                    }
                    break;

                case 'mfa':
                    if (!$this->checkMFACondition($conditionData, $context)) {
                        return false;
                    }
                    break;

                case 'approval':
                    if (!$this->checkApprovalCondition($conditionData, $context)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Check resource access
     */
    private function checkResourceAccess($userId, $userType, $resourceType, $resourceId, $permissionKey) {
        // Check if user owns or has access to the resource
        $query = "SELECT ownership_level FROM rbac_resource_ownership
                  WHERE resource_type = ? AND resource_id = ?
                  AND owner_id = ? AND owner_type = ?
                  AND is_active = TRUE
                  AND (expires_at IS NULL OR expires_at > NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$resourceType, $resourceId, $userId, $userType]);
        $ownership = $stmt->fetchColumn();

        if (!$ownership) {
            // Check if permission allows access to all resources of this type
            $permissionLevel = $this->getPermissionLevel($permissionKey);
            return $permissionLevel === self::LEVEL_ADMIN;
        }

        // Check if ownership level allows the requested action
        return $this->ownershipAllowsAction($ownership, $permissionKey);
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($userId, $userType) {
        $query = "SELECT DISTINCT p.permission_key, p.permission_name, p.category,
                         p.resource_type, p.permission_level
                  FROM rbac_user_roles ur
                  JOIN rbac_role_permissions rp ON ur.role_id = rp.role_id
                  JOIN rbac_permissions p ON rp.permission_id = p.id
                  WHERE ur.user_id = ? AND ur.user_type = ?
                  AND ur.is_active = TRUE AND rp.is_active = TRUE
                  AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
                  AND (rp.expires_at IS NULL OR rp.expires_at > NOW())
                  ORDER BY p.category, p.resource_type, p.permission_level";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $userType]);
        return $stmt->fetchAll();
    }

    /**
     * Grant resource ownership
     */
    public function grantResourceOwnership($resourceType, $resourceId, $ownerId, $ownerType,
                                         $ownershipLevel, $grantedBy = null, $expiresAt = null) {
        $ownershipId = bin2hex(random_bytes(16));

        $query = "INSERT INTO rbac_resource_ownership (
            id, resource_type, resource_id, owner_id, owner_type,
            ownership_level, granted_by, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            ownership_level = VALUES(ownership_level),
            granted_by = VALUES(granted_by),
            expires_at = VALUES(expires_at),
            is_active = TRUE";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $ownershipId, $resourceType, $resourceId, $ownerId,
            $ownerType, $ownershipLevel, $grantedBy, $expiresAt
        ]);
    }

    /**
     * Revoke user role
     */
    public function revokeUserRole($userId, $userType, $roleKey, $revokedBy = null) {
        $query = "UPDATE rbac_user_roles ur
                  JOIN rbac_roles r ON ur.role_id = r.id
                  SET ur.is_active = FALSE
                  WHERE ur.user_id = ? AND ur.user_type = ? AND r.role_key = ?";

        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([$userId, $userType, $roleKey]);

        if ($success) {
            $this->logPermissionAudit($userId, $userType, 'role.revoke', 'roles', null,
                "Role $roleKey revoked", true);
        }

        return $success;
    }

    /**
     * Get permission audit trail
     */
    public function getPermissionAuditTrail($userId = null, $userType = null, $limit = 100, $offset = 0) {
        $whereClause = "WHERE 1=1";
        $params = [];

        if ($userId) {
            $whereClause .= " AND user_id = ?";
            $params[] = $userId;
        }

        if ($userType) {
            $whereClause .= " AND user_type = ?";
            $params[] = $userType;
        }

        $query = "SELECT * FROM rbac_permission_audit
                  $whereClause
                  ORDER BY timestamp DESC
                  LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Helper methods
     */

    private function logPermissionAudit($userId, $userType, $permissionKey, $resourceType,
                                      $resourceId, $action, $granted, $denialReason = null) {
        $auditId = bin2hex(random_bytes(16));

        $query = "INSERT INTO rbac_permission_audit (
            id, user_id, user_type, permission_key, resource_type, resource_id,
            action_attempted, permission_granted, denial_reason, ip_address,
            user_agent, session_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $auditId, $userId, $userType, $permissionKey, $resourceType, $resourceId,
            $action, $granted, $denialReason, $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null, session_id()
        ]);

        // Also log to security system
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'permission_check',
            $granted ? SecurityLogger::LEVEL_INFO : SecurityLogger::LEVEL_WARNING,
            "Permission check: $permissionKey", [
                'user_id' => $userId,
                'user_type' => $userType,
                'permission' => $permissionKey,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'granted' => $granted,
                'denial_reason' => $denialReason
            ], null, $userType === 'admin' ? $userId : null);
    }

    private function getResourceTypeFromPermission($permissionKey) {
        $parts = explode('.', $permissionKey);
        return $parts[0] ?? 'unknown';
    }

    private function getPermissionLevel($permissionKey) {
        $query = "SELECT permission_level FROM rbac_permissions WHERE permission_key = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$permissionKey]);
        return $stmt->fetchColumn();
    }

    private function ownershipAllowsAction($ownershipLevel, $permissionKey) {
        $permissionLevel = $this->getPermissionLevel($permissionKey);

        $ownershipHierarchy = [
            'viewer' => 1,
            'contributor' => 2,
            'manager' => 3,
            'owner' => 4
        ];

        $permissionHierarchy = [
            self::LEVEL_READ => 1,
            self::LEVEL_WRITE => 2,
            self::LEVEL_DELETE => 3,
            self::LEVEL_ADMIN => 4
        ];

        return ($ownershipHierarchy[$ownershipLevel] ?? 0) >= ($permissionHierarchy[$permissionLevel] ?? 0);
    }

    private function checkTimeCondition($conditionData) {
        $currentTime = date('H:i');
        $currentDay = date('w'); // 0 = Sunday, 6 = Saturday

        if (isset($conditionData['allowed_hours'])) {
            $start = $conditionData['allowed_hours']['start'];
            $end = $conditionData['allowed_hours']['end'];

            if ($currentTime < $start || $currentTime > $end) {
                return false;
            }
        }

        if (isset($conditionData['allowed_days'])) {
            if (!in_array($currentDay, $conditionData['allowed_days'])) {
                return false;
            }
        }

        return true;
    }

    private function checkIPCondition($conditionData) {
        $userIP = $_SERVER['REMOTE_ADDR'] ?? '';

        if (isset($conditionData['allowed_ips'])) {
            return in_array($userIP, $conditionData['allowed_ips']);
        }

        if (isset($conditionData['blocked_ips'])) {
            return !in_array($userIP, $conditionData['blocked_ips']);
        }

        return true;
    }

    private function checkMFACondition($conditionData, $context) {
        if ($conditionData['required'] ?? false) {
            return isset($context['mfa_verified']) && $context['mfa_verified'] === true;
        }
        return true;
    }

    private function checkApprovalCondition($conditionData, $context) {
        if ($conditionData['required'] ?? false) {
            return isset($context['approval_granted']) && $context['approval_granted'] === true;
        }
        return true;
    }
}

// Convenience functions
function hasPermission($userId, $userType, $permissionKey, $resourceId = null, $context = []) {
    $rbac = EnterpriseRBAC::getInstance();
    return $rbac->hasPermission($userId, $userType, $permissionKey, $resourceId, $context);
}

function assignRoleToUser($userId, $userType, $roleKey, $assignedBy = null, $expiresAt = null) {
    $rbac = EnterpriseRBAC::getInstance();
    return $rbac->assignRoleToUser($userId, $userType, $roleKey, $assignedBy, $expiresAt);
}

function getUserPermissions($userId, $userType) {
    $rbac = EnterpriseRBAC::getInstance();
    return $rbac->getUserPermissions($userId, $userType);
}

function grantResourceOwnership($resourceType, $resourceId, $ownerId, $ownerType, $ownershipLevel, $grantedBy = null) {
    $rbac = EnterpriseRBAC::getInstance();
    return $rbac->grantResourceOwnership($resourceType, $resourceId, $ownerId, $ownerType, $ownershipLevel, $grantedBy);
}
?>
