<?php
/**
 * CENTRALIZED SECURITY LOGGING SYSTEM
 * Bank-level security event logging with real-time alerting
 */

require_once 'database.php';
require_once 'env-loader.php';

class SecurityLogger {
    private $db;
    private $logToFile;
    private $logToDatabase;
    private $alertThresholds;
    private static $instance = null;
    
    // Security event types
    const EVENT_AUTHENTICATION = 'authentication';
    const EVENT_AUTHORIZATION = 'authorization';
    const EVENT_SESSION = 'session';
    const EVENT_FILE_UPLOAD = 'file_upload';
    const EVENT_DATABASE = 'database';
    const EVENT_CORS = 'cors';
    const EVENT_RATE_LIMIT = 'rate_limit';
    const EVENT_FINANCIAL = 'financial';
    const EVENT_ADMIN = 'admin';
    const EVENT_SYSTEM = 'system';
    
    // Security levels
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_EMERGENCY = 'emergency';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        $this->logToFile = EnvLoader::get('SECURITY_LOG_FILE', 'true') === 'true';
        $this->logToDatabase = EnvLoader::get('SECURITY_LOG_DATABASE', 'true') === 'true';
        
        // Alert thresholds for real-time monitoring
        $this->alertThresholds = [
            'failed_logins_per_minute' => 10,
            'cors_violations_per_minute' => 5,
            'file_upload_failures_per_minute' => 3,
            'critical_events_per_hour' => 5
        ];
        
        $this->initializeDatabase();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize security logging database tables
     */
    private function initializeDatabase() {
        if (!$this->db) return;
        
        try {
            // Main security events table
            $this->db->exec("CREATE TABLE IF NOT EXISTS security_events (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                event_type ENUM('authentication', 'authorization', 'session', 'file_upload', 'database', 'cors', 'rate_limit', 'financial', 'admin', 'system') NOT NULL,
                event_subtype VARCHAR(100),
                security_level ENUM('info', 'warning', 'critical', 'emergency') NOT NULL,
                user_id INT NULL,
                admin_id INT NULL,
                session_id VARCHAR(255) NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                event_data JSON,
                event_message TEXT NOT NULL,
                event_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed BOOLEAN DEFAULT FALSE,
                alert_sent BOOLEAN DEFAULT FALSE,
                INDEX idx_event_type (event_type),
                INDEX idx_security_level (security_level),
                INDEX idx_timestamp (event_timestamp),
                INDEX idx_ip_address (ip_address),
                INDEX idx_user_id (user_id),
                INDEX idx_processed (processed)
            )");
            
            // Security metrics table for real-time monitoring
            $this->db->exec("CREATE TABLE IF NOT EXISTS security_metrics (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                metric_type VARCHAR(100) NOT NULL,
                metric_value INT NOT NULL,
                time_window ENUM('minute', 'hour', 'day') NOT NULL,
                window_start TIMESTAMP NOT NULL,
                window_end TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_metric_type (metric_type),
                INDEX idx_time_window (time_window),
                INDEX idx_window_start (window_start)
            )");
            
            // Security alerts table
            $this->db->exec("CREATE TABLE IF NOT EXISTS security_alerts (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                alert_type VARCHAR(100) NOT NULL,
                alert_level ENUM('warning', 'critical', 'emergency') NOT NULL,
                alert_message TEXT NOT NULL,
                alert_data JSON,
                triggered_by_event VARCHAR(36),
                acknowledged BOOLEAN DEFAULT FALSE,
                acknowledged_by INT NULL,
                acknowledged_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_alert_type (alert_type),
                INDEX idx_alert_level (alert_level),
                INDEX idx_acknowledged (acknowledged),
                INDEX idx_created_at (created_at)
            )");
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Database initialization failed - " . $e->getMessage());
        }
    }
    
    /**
     * Log a security event
     */
    public function logEvent($eventType, $eventSubtype, $level, $message, $eventData = [], $userId = null, $adminId = null) {
        $eventId = $this->generateEventId();
        $timestamp = date('c');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $sessionId = session_id() ?: null;
        
        $logEntry = [
            'id' => $eventId,
            'event_type' => $eventType,
            'event_subtype' => $eventSubtype,
            'security_level' => $level,
            'user_id' => $userId,
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'event_data' => $eventData,
            'event_message' => $message,
            'timestamp' => $timestamp
        ];
        
        // Log to file
        if ($this->logToFile) {
            $this->logToFile($logEntry);
        }
        
        // Log to database
        if ($this->logToDatabase && $this->db) {
            $this->logToDatabase($logEntry);
        }
        
        // Check for alert conditions
        $this->checkAlertConditions($eventType, $level, $eventData);
        
        // Update metrics
        $this->updateMetrics($eventType, $level);
        
        return $eventId;
    }
    
    /**
     * Log to file system
     */
    private function logToFile($logEntry) {
        $logDir = dirname(dirname(__DIR__)) . '/logs/security/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0700, true);
        }
        
        $logFile = $logDir . date('Y-m-d') . '.log';
        $logLine = json_encode($logEntry) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log to database
     */
    private function logToDatabase($logEntry) {
        try {
            $query = "INSERT INTO security_events (
                id, event_type, event_subtype, security_level, user_id, admin_id, 
                session_id, ip_address, user_agent, event_data, event_message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $logEntry['id'],
                $logEntry['event_type'],
                $logEntry['event_subtype'],
                $logEntry['security_level'],
                $logEntry['user_id'],
                $logEntry['admin_id'],
                $logEntry['session_id'],
                $logEntry['ip_address'],
                $logEntry['user_agent'],
                json_encode($logEntry['event_data']),
                $logEntry['event_message']
            ]);
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Database logging failed - " . $e->getMessage());
        }
    }
    
    /**
     * Check for alert conditions and trigger alerts
     */
    private function checkAlertConditions($eventType, $level, $eventData) {
        // Critical and emergency events always trigger alerts
        if (in_array($level, [self::LEVEL_CRITICAL, self::LEVEL_EMERGENCY])) {
            $this->triggerAlert($eventType, $level, "Critical security event detected", $eventData);
        }
        
        // Check rate-based alerts
        $this->checkRateBasedAlerts($eventType);
    }
    
    /**
     * Check for rate-based security alerts
     */
    private function checkRateBasedAlerts($eventType) {
        if (!$this->db) return;
        
        $now = date('Y-m-d H:i:s');
        $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        try {
            // Check failed login attempts
            if ($eventType === self::EVENT_AUTHENTICATION) {
                $query = "SELECT COUNT(*) as count FROM security_events 
                         WHERE event_type = ? AND security_level IN ('warning', 'critical') 
                         AND event_timestamp BETWEEN ? AND ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([self::EVENT_AUTHENTICATION, $oneMinuteAgo, $now]);
                $result = $stmt->fetch();
                
                if ($result['count'] >= $this->alertThresholds['failed_logins_per_minute']) {
                    $this->triggerAlert('rate_limit_exceeded', self::LEVEL_CRITICAL, 
                        "Excessive failed login attempts: {$result['count']} in 1 minute", 
                        ['count' => $result['count'], 'threshold' => $this->alertThresholds['failed_logins_per_minute']]);
                }
            }
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Alert check failed - " . $e->getMessage());
        }
    }
    
    /**
     * Trigger a security alert
     */
    private function triggerAlert($alertType, $level, $message, $data = []) {
        if (!$this->db) return;
        
        try {
            $query = "INSERT INTO security_alerts (alert_type, alert_level, alert_message, alert_data) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$alertType, $level, $message, json_encode($data)]);
            
            // In production, integrate with external alerting systems
            // (email, SMS, Slack, PagerDuty, etc.)
            $this->sendExternalAlert($alertType, $level, $message, $data);
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Alert creation failed - " . $e->getMessage());
        }
    }
    
    /**
     * Send external alerts (email, SMS, etc.)
     */
    private function sendExternalAlert($alertType, $level, $message, $data) {
        // Log to system error log for immediate visibility
        error_log("SECURITY ALERT [{$level}]: {$alertType} - {$message}");
        
        // In production, implement:
        // - Email notifications to security team
        // - SMS alerts for critical/emergency events
        // - Slack/Teams notifications
        // - PagerDuty integration
        // - SIEM system integration
    }
    
    /**
     * Update security metrics
     */
    private function updateMetrics($eventType, $level) {
        if (!$this->db) return;
        
        try {
            $metricType = $eventType . '_' . $level;
            $windowStart = date('Y-m-d H:i:00'); // Current minute
            $windowEnd = date('Y-m-d H:i:59');
            
            $query = "INSERT INTO security_metrics (metric_type, metric_value, time_window, window_start, window_end) 
                     VALUES (?, 1, 'minute', ?, ?) 
                     ON DUPLICATE KEY UPDATE metric_value = metric_value + 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$metricType, $windowStart, $windowEnd]);
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Metrics update failed - " . $e->getMessage());
        }
    }
    
    /**
     * Generate unique event ID
     */
    private function generateEventId() {
        return uniqid('sec_', true);
    }
    
    /**
     * Get recent security events
     */
    public function getRecentEvents($limit = 100, $level = null, $eventType = null) {
        if (!$this->db) return [];
        
        try {
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
            
            $query .= " ORDER BY event_timestamp DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Failed to retrieve events - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active security alerts
     */
    public function getActiveAlerts() {
        if (!$this->db) return [];
        
        try {
            $query = "SELECT * FROM security_alerts WHERE acknowledged = FALSE 
                     ORDER BY alert_level DESC, created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("SECURITY LOGGER: Failed to retrieve alerts - " . $e->getMessage());
            return [];
        }
    }
}

// Convenience functions for common security events
function logSecurityEvent($eventType, $eventSubtype, $level, $message, $eventData = [], $userId = null, $adminId = null) {
    $logger = SecurityLogger::getInstance();
    return $logger->logEvent($eventType, $eventSubtype, $level, $message, $eventData, $userId, $adminId);
}

function logAuthenticationEvent($subtype, $level, $message, $data = [], $userId = null) {
    return logSecurityEvent(SecurityLogger::EVENT_AUTHENTICATION, $subtype, $level, $message, $data, $userId);
}

function logFileUploadEvent($subtype, $level, $message, $data = [], $userId = null) {
    return logSecurityEvent(SecurityLogger::EVENT_FILE_UPLOAD, $subtype, $level, $message, $data, $userId);
}

function logFinancialEvent($subtype, $level, $message, $data = [], $userId = null) {
    return logSecurityEvent(SecurityLogger::EVENT_FINANCIAL, $subtype, $level, $message, $data, $userId);
}

// logCorsEvent function is defined in cors.php to avoid redeclaration
?>
