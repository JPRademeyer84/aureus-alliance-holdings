-- Debug Configuration System
-- Allows admins to control debugging features and visibility

CREATE TABLE IF NOT EXISTS debug_config (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Debug feature identification
    feature_key VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique identifier for debug feature',
    feature_name VARCHAR(255) NOT NULL COMMENT 'Human-readable name',
    feature_description TEXT NULL COMMENT 'Description of what this debug feature does',
    
    -- Control settings
    is_enabled BOOLEAN DEFAULT FALSE COMMENT 'Whether this debug feature is active',
    is_visible BOOLEAN DEFAULT TRUE COMMENT 'Whether this feature appears in debug panel',
    access_level ENUM('admin', 'developer', 'support') DEFAULT 'admin' COMMENT 'Who can access this feature',
    
    -- Configuration data
    config_data JSON NULL COMMENT 'Feature-specific configuration options',
    
    -- Environment restrictions
    allowed_environments JSON NULL COMMENT 'Environments where this feature is allowed',
    
    -- Admin tracking
    created_by VARCHAR(36) NOT NULL COMMENT 'Admin user who created this config',
    updated_by VARCHAR(36) NULL COMMENT 'Admin user who last updated this config',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_feature_key (feature_key),
    INDEX idx_is_enabled (is_enabled),
    INDEX idx_is_visible (is_visible),
    INDEX idx_access_level (access_level),
    INDEX idx_created_by (created_by)
);

-- Debug Sessions Table - Track who is using debug features
CREATE TABLE IF NOT EXISTS debug_sessions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    
    -- Session identification
    session_id VARCHAR(255) NOT NULL COMMENT 'User session identifier',
    user_id VARCHAR(36) NULL COMMENT 'User ID if authenticated',
    admin_id VARCHAR(36) NULL COMMENT 'Admin ID if admin user',
    
    -- Debug activity
    feature_key VARCHAR(100) NOT NULL COMMENT 'Which debug feature was used',
    action_type ENUM('view', 'execute', 'download', 'clear') NOT NULL COMMENT 'Type of debug action',
    action_data JSON NULL COMMENT 'Additional action data',
    
    -- Environment info
    ip_address VARCHAR(45) NULL COMMENT 'User IP address',
    user_agent TEXT NULL COMMENT 'User browser/client info',
    environment VARCHAR(50) NULL COMMENT 'Environment where action occurred',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_feature_key (feature_key),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
);

-- Insert default debug configurations
INSERT IGNORE INTO debug_config (
    feature_key, feature_name, feature_description, is_enabled, is_visible, 
    access_level, config_data, allowed_environments, created_by
) VALUES 
(
    'console_logs',
    'Console Logs',
    'View browser console logs and errors',
    TRUE,
    TRUE,
    'admin',
    JSON_OBJECT('max_logs', 100, 'auto_refresh', true),
    JSON_ARRAY('development', 'staging'),
    '1'
),
(
    'network_monitor',
    'Network Monitor',
    'Monitor API requests and responses',
    TRUE,
    TRUE,
    'admin',
    JSON_OBJECT('show_headers', true, 'show_body', true, 'max_requests', 50),
    JSON_ARRAY('development', 'staging'),
    '1'
),
(
    'system_info',
    'System Information',
    'Display system and environment information',
    TRUE,
    TRUE,
    'admin',
    JSON_OBJECT('show_sensitive', false, 'include_performance', true),
    JSON_ARRAY('development', 'staging', 'production'),
    '1'
),
(
    'database_queries',
    'Database Queries',
    'Monitor and log database queries',
    FALSE,
    TRUE,
    'admin',
    JSON_OBJECT('log_slow_queries', true, 'slow_query_threshold', 1000),
    JSON_ARRAY('development'),
    '1'
),
(
    'api_testing',
    'API Testing',
    'Test API endpoints directly from debug panel',
    TRUE,
    TRUE,
    'admin',
    JSON_OBJECT('allowed_methods', JSON_ARRAY('GET', 'POST'), 'timeout', 30),
    JSON_ARRAY('development', 'staging'),
    '1'
),
(
    'cache_management',
    'Cache Management',
    'View and clear application caches',
    TRUE,
    TRUE,
    'admin',
    JSON_OBJECT('show_cache_size', true, 'allow_clear_all', true),
    JSON_ARRAY('development', 'staging'),
    '1'
),
(
    'error_tracking',
    'Error Tracking',
    'View and manage application errors',
    TRUE,
    TRUE,
    'admin',
    JSON_OBJECT('max_errors', 200, 'group_similar', true),
    JSON_ARRAY('development', 'staging', 'production'),
    '1'
),
(
    'performance_metrics',
    'Performance Metrics',
    'Monitor application performance and timing',
    FALSE,
    TRUE,
    'developer',
    JSON_OBJECT('track_page_load', true, 'track_api_timing', true),
    JSON_ARRAY('development'),
    '1'
);

-- Create admin permissions for debug management
INSERT IGNORE INTO admin_permissions (permission_name, description) VALUES 
('debug_management', 'Manage debug configuration and settings'),
('debug_access', 'Access debug panel and features'),
('debug_sessions', 'View debug session logs and activity');

-- Grant debug permissions to admin role
INSERT IGNORE INTO admin_role_permissions (role_id, permission_id)
SELECT 
    ar.id as role_id,
    ap.id as permission_id
FROM admin_roles ar
CROSS JOIN admin_permissions ap
WHERE ar.role_name = 'admin' 
AND ap.permission_name IN ('debug_management', 'debug_access', 'debug_sessions');
