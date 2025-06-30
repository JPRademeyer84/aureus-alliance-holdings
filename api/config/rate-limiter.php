<?php
/**
 * RATE LIMITING SYSTEM
 * Prevents brute force attacks and abuse
 */

class RateLimiter {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        require_once 'database.php';
        require_once 'security-logger.php';
        $database = new Database();
        $this->db = $database->getConnection();
        $this->initializeTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize rate limiting tables
     */
    private function initializeTables() {
        $createTable = "
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(255) NOT NULL,
                action VARCHAR(100) NOT NULL,
                attempts INT DEFAULT 1,
                first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                blocked_until TIMESTAMP NULL,
                INDEX idx_identifier_action (identifier, action),
                INDEX idx_blocked_until (blocked_until)
            )
        ";
        
        try {
            $this->db->exec($createTable);
        } catch (PDOException $e) {
            error_log("Rate limiter table creation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if action is allowed for identifier
     */
    public function isAllowed($identifier, $action, $maxAttempts = 5, $timeWindow = 900) {
        $this->cleanupExpiredEntries();
        
        // Get current attempts
        $query = "SELECT attempts, first_attempt, blocked_until FROM rate_limits 
                  WHERE identifier = ? AND action = ? 
                  AND first_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $action, $timeWindow]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if currently blocked
        if ($record && $record['blocked_until'] && strtotime($record['blocked_until']) > time()) {
            return false;
        }
        
        // Check if within limits
        if ($record && $record['attempts'] >= $maxAttempts) {
            // Block for extended period
            $blockDuration = $this->calculateBlockDuration($record['attempts']);
            $this->blockIdentifier($identifier, $action, $blockDuration);
            return false;
        }
        
        return true;
    }
    
    /**
     * Record an attempt
     */
    public function recordAttempt($identifier, $action, $success = false) {
        if ($success) {
            // Clear attempts on successful action
            $this->clearAttempts($identifier, $action);
            return;
        }
        
        // Check if record exists
        $query = "SELECT id, attempts FROM rate_limits 
                  WHERE identifier = ? AND action = ? 
                  AND first_attempt > DATE_SUB(NOW(), INTERVAL 900 SECOND)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            // Update existing record
            $updateQuery = "UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$record['id']]);
        } else {
            // Create new record
            $insertQuery = "INSERT INTO rate_limits (identifier, action, attempts) VALUES (?, ?, 1)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->execute([$identifier, $action]);
        }
        
        // Log security event
        $this->logSecurityEvent($identifier, $action, $record ? $record['attempts'] + 1 : 1);
    }
    
    /**
     * Block identifier for specific duration
     */
    private function blockIdentifier($identifier, $action, $duration) {
        $blockUntil = date('Y-m-d H:i:s', time() + $duration);
        
        $query = "UPDATE rate_limits SET blocked_until = ? 
                  WHERE identifier = ? AND action = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$blockUntil, $identifier, $action]);
        
        // Log blocking event
        logSecurityEvent(SecurityLogger::EVENT_RATE_LIMIT, 'blocked', SecurityLogger::LEVEL_CRITICAL,
            "Rate limit exceeded - identifier blocked",
            ['identifier' => $identifier, 'action' => $action, 'blocked_until' => $blockUntil]);
    }
    
    /**
     * Calculate progressive block duration
     */
    private function calculateBlockDuration($attempts) {
        // Progressive blocking: 5 min, 15 min, 1 hour, 6 hours, 24 hours
        $durations = [300, 900, 3600, 21600, 86400];
        $index = min($attempts - 5, count($durations) - 1);
        return $durations[$index];
    }
    
    /**
     * Clear attempts for successful action
     */
    private function clearAttempts($identifier, $action) {
        $query = "DELETE FROM rate_limits WHERE identifier = ? AND action = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $action]);
    }
    
    /**
     * Get remaining time until unblocked
     */
    public function getBlockedTime($identifier, $action) {
        $query = "SELECT blocked_until FROM rate_limits 
                  WHERE identifier = ? AND action = ? 
                  AND blocked_until > NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            return strtotime($record['blocked_until']) - time();
        }
        
        return 0;
    }
    
    /**
     * Get attempt count
     */
    public function getAttemptCount($identifier, $action, $timeWindow = 900) {
        $query = "SELECT attempts FROM rate_limits 
                  WHERE identifier = ? AND action = ? 
                  AND first_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $action, $timeWindow]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $record ? $record['attempts'] : 0;
    }
    
    /**
     * Clean up expired entries
     */
    private function cleanupExpiredEntries() {
        $query = "DELETE FROM rate_limits 
                  WHERE first_attempt < DATE_SUB(NOW(), INTERVAL 86400 SECOND) 
                  AND (blocked_until IS NULL OR blocked_until < NOW())";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($identifier, $action, $attempts) {
        $logData = [
            'identifier' => $identifier,
            'action' => $action,
            'attempts' => $attempts
        ];

        if ($attempts >= 3 && $attempts < 5) {
            logSecurityEvent(SecurityLogger::EVENT_RATE_LIMIT, 'multiple_attempts', SecurityLogger::LEVEL_WARNING,
                "Multiple failed attempts detected", $logData);
        }

        if ($attempts >= 5) {
            logSecurityEvent(SecurityLogger::EVENT_RATE_LIMIT, 'limit_exceeded', SecurityLogger::LEVEL_CRITICAL,
                "Rate limit threshold exceeded", $logData);
        }
    }
    
    /**
     * Get rate limit status
     */
    public function getStatus($identifier, $action) {
        $attempts = $this->getAttemptCount($identifier, $action);
        $blockedTime = $this->getBlockedTime($identifier, $action);
        
        return [
            'identifier' => $identifier,
            'action' => $action,
            'attempts' => $attempts,
            'is_blocked' => $blockedTime > 0,
            'blocked_seconds' => $blockedTime,
            'max_attempts' => 5
        ];
    }
    
    /**
     * Generate identifier from IP and optional user info
     */
    public static function generateIdentifier($userInfo = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if ($userInfo) {
            return hash('sha256', $ip . '|' . $userInfo);
        }
        
        return hash('sha256', $ip);
    }
}
?>
