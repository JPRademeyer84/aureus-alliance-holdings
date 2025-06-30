<?php
/**
 * Create Debug System Tables
 * Creates debug configuration tables directly
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 CREATING DEBUG SYSTEM TABLES\n";
    echo "===============================\n\n";
    
    // Create debug_config table
    echo "Creating debug_config table...\n";
    $debugConfigTable = "
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
        )
    ";
    
    $db->exec($debugConfigTable);
    echo "✅ debug_config table created\n";
    
    // Create debug_sessions table
    echo "Creating debug_sessions table...\n";
    $debugSessionsTable = "
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
        )
    ";
    
    $db->exec($debugSessionsTable);
    echo "✅ debug_sessions table created\n";
    
    // Insert default debug configurations
    echo "Creating default debug configurations...\n";
    
    // Get admin user
    $adminQuery = "SELECT id FROM admin_users WHERE username = 'admin' LIMIT 1";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute();
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $defaultConfigs = [
            [
                'console_logs',
                'Console Logs',
                'View browser console logs and errors',
                true,
                true,
                'admin',
                json_encode(['max_logs' => 100, 'auto_refresh' => true]),
                json_encode(['development', 'staging'])
            ],
            [
                'network_monitor',
                'Network Monitor',
                'Monitor API requests and responses',
                true,
                true,
                'admin',
                json_encode(['show_headers' => true, 'show_body' => true, 'max_requests' => 50]),
                json_encode(['development', 'staging'])
            ],
            [
                'system_info',
                'System Information',
                'Display system and environment information',
                true,
                true,
                'admin',
                json_encode(['show_sensitive' => false, 'include_performance' => true]),
                json_encode(['development', 'staging', 'production'])
            ],
            [
                'database_queries',
                'Database Queries',
                'Monitor and log database queries',
                false,
                true,
                'admin',
                json_encode(['log_slow_queries' => true, 'slow_query_threshold' => 1000]),
                json_encode(['development'])
            ],
            [
                'api_testing',
                'API Testing',
                'Test API endpoints directly from debug panel',
                true,
                true,
                'admin',
                json_encode(['allowed_methods' => ['GET', 'POST'], 'timeout' => 30]),
                json_encode(['development', 'staging'])
            ],
            [
                'cache_management',
                'Cache Management',
                'View and clear application caches',
                true,
                true,
                'admin',
                json_encode(['show_cache_size' => true, 'allow_clear_all' => true]),
                json_encode(['development', 'staging'])
            ],
            [
                'error_tracking',
                'Error Tracking',
                'View and manage application errors',
                true,
                true,
                'admin',
                json_encode(['max_errors' => 200, 'group_similar' => true]),
                json_encode(['development', 'staging', 'production'])
            ],
            [
                'performance_metrics',
                'Performance Metrics',
                'Monitor application performance and timing',
                false,
                true,
                'developer',
                json_encode(['track_page_load' => true, 'track_api_timing' => true]),
                json_encode(['development'])
            ]
        ];
        
        $insertConfigQuery = "
            INSERT IGNORE INTO debug_config (
                feature_key, feature_name, feature_description, is_enabled, is_visible, 
                access_level, config_data, allowed_environments, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $insertConfigStmt = $db->prepare($insertConfigQuery);
        
        foreach ($defaultConfigs as $config) {
            try {
                $insertConfigStmt->execute([
                    $config[0], // feature_key
                    $config[1], // feature_name
                    $config[2], // feature_description
                    $config[3], // is_enabled
                    $config[4], // is_visible
                    $config[5], // access_level
                    $config[6], // config_data
                    $config[7], // allowed_environments
                    $admin['id'] // created_by
                ]);
                echo "✅ Created debug config: {$config[1]}\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "✅ Debug config {$config[1]} already exists\n";
                } else {
                    echo "❌ Error creating debug config {$config[1]}: " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "⚠️ No admin user found. Skipping default configurations.\n";
    }
    
    // Verify tables
    echo "\nVerifying debug system tables...\n";
    
    $tables = ['debug_config', 'debug_sessions'];
    
    foreach ($tables as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✅ $table: $count records\n";
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Show available debug features
    echo "\nAvailable debug features:\n";
    
    try {
        $featuresQuery = "
            SELECT feature_key, feature_name, is_enabled, is_visible, access_level
            FROM debug_config 
            ORDER BY feature_name
        ";
        $featuresStmt = $db->prepare($featuresQuery);
        $featuresStmt->execute();
        $features = $featuresStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($features as $feature) {
            $status = $feature['is_enabled'] ? '🟢 ENABLED' : '🔴 DISABLED';
            $visibility = $feature['is_visible'] ? 'VISIBLE' : 'HIDDEN';
            echo "  - {$feature['feature_name']} ({$feature['feature_key']}): $status, $visibility, {$feature['access_level']}\n";
        }
        
        $enabledCount = count(array_filter($features, fn($f) => $f['is_enabled']));
        echo "\nSummary: $enabledCount of " . count($features) . " debug features are enabled\n";
        
    } catch (Exception $e) {
        echo "❌ Error fetching debug features: " . $e->getMessage() . "\n";
    }
    
    echo "\n===============================\n";
    echo "🎉 DEBUG SYSTEM READY!\n";
    echo "===============================\n";
    echo "✅ All tables created successfully\n";
    echo "✅ Default debug configurations added\n";
    echo "✅ System ready for admin control\n";
    
    echo "\n🎯 ADMIN CONTROLS AVAILABLE:\n";
    echo "1. Access admin panel → Debug Manager\n";
    echo "2. Enable/disable debug features\n";
    echo "3. Control feature visibility\n";
    echo "4. Set access levels (admin/developer/support)\n";
    echo "5. Configure environment restrictions\n";
    echo "6. Monitor debug session activity\n";
    
    echo "\n🔧 USER ACCESS:\n";
    echo "1. Debug panel available via Ctrl+Shift+D\n";
    echo "2. Only enabled features are visible\n";
    echo "3. Access controlled by admin settings\n";
    echo "4. All activity is logged and monitored\n";
    
    echo "\nSetup completed at: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "❌ DEBUG SYSTEM SETUP FAILED: " . $e->getMessage() . "\n";
}
?>
