<?php
/**
 * PERMISSION MIDDLEWARE
 * Enhanced permission checking middleware that integrates with existing system
 */

require_once 'enterprise-rbac.php';

class PermissionMiddleware {
    private static $rbac = null;
    
    /**
     * Initialize RBAC system
     */
    private static function initRBAC() {
        if (self::$rbac === null) {
            self::$rbac = EnterpriseRBAC::getInstance();
        }
    }
    
    /**
     * Enhanced permission check that works with existing system
     */
    public static function checkPermission($permissionKey, $resourceId = null, $context = []) {
        self::initRBAC();
        
        // Check if user is authenticated
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Determine user type and ID
        if (isset($_SESSION['admin_id'])) {
            $userId = $_SESSION['admin_id'];
            $userType = 'admin';
        } else {
            $userId = $_SESSION['user_id'];
            $userType = 'user';
        }
        
        // Use enhanced RBAC if user has roles assigned
        if (self::userHasRBACRoles($userId, $userType)) {
            return self::$rbac->hasPermission($userId, $userType, $permissionKey, $resourceId, $context);
        }
        
        // Fallback to legacy permission system for backward compatibility
        return self::legacyPermissionCheck($permissionKey, $userType);
    }
    
    /**
     * Check if user has RBAC roles assigned
     */
    private static function userHasRBACRoles($userId, $userType) {
        $roles = self::$rbac->getUserRoles($userId, $userType);
        return !empty($roles);
    }
    
    /**
     * Legacy permission check for backward compatibility
     */
    private static function legacyPermissionCheck($permissionKey, $userType) {
        if ($userType !== 'admin') {
            return false;
        }
        
        // Get admin role from database
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT role FROM admin_users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['admin_id']]);
        $adminRole = $stmt->fetchColumn();
        
        if (!$adminRole) {
            return false;
        }
        
        // Map legacy permissions to roles
        return self::legacyRoleHasPermission($adminRole, $permissionKey);
    }
    
    /**
     * Legacy role permission mapping
     */
    private static function legacyRoleHasPermission($role, $permissionKey) {
        $roleHierarchy = [
            'super_admin' => 100,
            'admin' => 80,
            'financial_admin' => 70,
            'security_admin' => 75,
            'kyc_admin' => 60,
            'chat_support' => 40
        ];
        
        $permissionRequirements = [
            // User Management
            'users.read' => 40,
            'users.write' => 60,
            'users.delete' => 80,
            'admins.read' => 60,
            'admins.write' => 80,
            'admins.delete' => 100,
            
            // Financial
            'wallets.read' => 60,
            'wallets.write' => 70,
            'wallets.admin' => 80,
            'transactions.read' => 60,
            'transactions.write' => 70,
            'transactions.admin' => 80,
            'commissions.read' => 60,
            'commissions.write' => 70,
            'commissions.admin' => 80,
            
            // Security
            'security.read' => 60,
            'security.write' => 75,
            'security.admin' => 100,
            'logs.read' => 60,
            'logs.admin' => 75,
            
            // KYC
            'kyc.read' => 40,
            'kyc.write' => 60,
            'kyc.admin' => 80,
            
            // Content
            'chat.read' => 40,
            'chat.write' => 40,
            'packages.read' => 60,
            'packages.write' => 80,
            
            // System
            'settings.read' => 60,
            'settings.write' => 80,
            'settings.admin' => 100,
            'reports.read' => 60,
            'reports.admin' => 80
        ];
        
        $userLevel = $roleHierarchy[$role] ?? 0;
        $requiredLevel = $permissionRequirements[$permissionKey] ?? 100;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Require permission or throw exception
     */
    public static function requirePermission($permissionKey, $resourceId = null, $context = []) {
        if (!self::checkPermission($permissionKey, $resourceId, $context)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Insufficient permissions',
                'required_permission' => $permissionKey,
                'resource_id' => $resourceId
            ]);
            exit;
        }
    }
    
    /**
     * Migrate user to RBAC system
     */
    public static function migrateUserToRBAC($userId, $userType) {
        self::initRBAC();
        
        if ($userType === 'admin') {
            // Get admin role from database
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT role FROM admin_users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            $adminRole = $stmt->fetchColumn();
            
            if ($adminRole) {
                // Map legacy role to RBAC role
                $rbacRole = self::mapLegacyRoleToRBAC($adminRole);
                if ($rbacRole) {
                    return self::$rbac->assignRoleToUser($userId, $userType, $rbacRole, 'system_migration');
                }
            }
        } else {
            // Regular users get basic user role
            return self::$rbac->assignRoleToUser($userId, $userType, 'viewer', 'system_migration');
        }
        
        return false;
    }
    
    /**
     * Map legacy roles to RBAC roles
     */
    private static function mapLegacyRoleToRBAC($legacyRole) {
        $mapping = [
            'super_admin' => 'super_admin',
            'admin' => 'admin',
            'chat_support' => 'chat_support'
        ];
        
        return $mapping[$legacyRole] ?? null;
    }
    
    /**
     * Get user's effective permissions
     */
    public static function getUserEffectivePermissions($userId = null, $userType = null) {
        self::initRBAC();
        
        if (!$userId || !$userType) {
            if (isset($_SESSION['admin_id'])) {
                $userId = $_SESSION['admin_id'];
                $userType = 'admin';
            } elseif (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $userType = 'user';
            } else {
                return [];
            }
        }
        
        // Check if user has RBAC roles
        if (self::userHasRBACRoles($userId, $userType)) {
            return self::$rbac->getUserPermissions($userId, $userType);
        }
        
        // Return legacy permissions
        return self::getLegacyPermissions($userId, $userType);
    }
    
    /**
     * Get legacy permissions for backward compatibility
     */
    private static function getLegacyPermissions($userId, $userType) {
        if ($userType !== 'admin') {
            return [
                ['permission_key' => 'users.read', 'permission_name' => 'Read Own Profile', 'category' => 'user_management']
            ];
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT role FROM admin_users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $adminRole = $stmt->fetchColumn();
        
        if (!$adminRole) {
            return [];
        }
        
        // Generate permissions based on legacy role
        $permissions = [];
        $allPermissions = [
            'users.read', 'users.write', 'users.delete',
            'admins.read', 'admins.write', 'admins.delete',
            'wallets.read', 'wallets.write', 'wallets.admin',
            'transactions.read', 'transactions.write', 'transactions.admin',
            'commissions.read', 'commissions.write', 'commissions.admin',
            'security.read', 'security.write', 'security.admin',
            'logs.read', 'logs.admin',
            'kyc.read', 'kyc.write', 'kyc.admin',
            'chat.read', 'chat.write',
            'packages.read', 'packages.write',
            'settings.read', 'settings.write', 'settings.admin',
            'reports.read', 'reports.admin'
        ];
        
        foreach ($allPermissions as $permission) {
            if (self::legacyRoleHasPermission($adminRole, $permission)) {
                $permissions[] = [
                    'permission_key' => $permission,
                    'permission_name' => ucwords(str_replace(['.', '_'], [' ', ' '], $permission)),
                    'category' => explode('.', $permission)[0]
                ];
            }
        }
        
        return $permissions;
    }
    
    /**
     * Check resource ownership
     */
    public static function checkResourceOwnership($resourceType, $resourceId, $userId = null, $userType = null) {
        self::initRBAC();
        
        if (!$userId || !$userType) {
            if (isset($_SESSION['admin_id'])) {
                $userId = $_SESSION['admin_id'];
                $userType = 'admin';
            } elseif (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $userType = 'user';
            } else {
                return false;
            }
        }
        
        // Check RBAC resource ownership
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT ownership_level FROM rbac_resource_ownership 
                  WHERE resource_type = ? AND resource_id = ? 
                  AND owner_id = ? AND owner_type = ?
                  AND is_active = TRUE
                  AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$resourceType, $resourceId, $userId, $userType]);
        $ownership = $stmt->fetchColumn();
        
        if ($ownership) {
            return $ownership;
        }
        
        // Fallback to legacy ownership checks
        return self::checkLegacyResourceOwnership($resourceType, $resourceId, $userId, $userType);
    }
    
    /**
     * Legacy resource ownership check
     */
    private static function checkLegacyResourceOwnership($resourceType, $resourceId, $userId, $userType) {
        $database = new Database();
        $db = $database->getConnection();
        
        switch ($resourceType) {
            case 'users':
                if ($userType === 'user' && $resourceId === $userId) {
                    return 'owner';
                }
                break;
                
            case 'kyc':
                $query = "SELECT user_id FROM kyc_documents WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$resourceId]);
                $ownerId = $stmt->fetchColumn();
                
                if ($userType === 'user' && $ownerId === $userId) {
                    return 'owner';
                }
                break;
        }
        
        return false;
    }
}

// Convenience functions for backward compatibility
function checkPermission($permissionKey, $resourceId = null, $context = []) {
    return PermissionMiddleware::checkPermission($permissionKey, $resourceId, $context);
}

function requirePermission($permissionKey, $resourceId = null, $context = []) {
    return PermissionMiddleware::requirePermission($permissionKey, $resourceId, $context);
}

function getUserEffectivePermissions($userId = null, $userType = null) {
    return PermissionMiddleware::getUserEffectivePermissions($userId, $userType);
}

function checkResourceOwnership($resourceType, $resourceId, $userId = null, $userType = null) {
    return PermissionMiddleware::checkResourceOwnership($resourceType, $resourceId, $userId, $userType);
}

function migrateUserToRBAC($userId, $userType) {
    return PermissionMiddleware::migrateUserToRBAC($userId, $userType);
}
?>
