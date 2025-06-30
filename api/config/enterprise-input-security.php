<?php
/**
 * ENTERPRISE INPUT SECURITY SYSTEM
 * Advanced input validation, sanitization, and threat detection
 */

require_once 'input-validator.php';
require_once 'security-logger.php';

class EnterpriseInputSecurity {
    private static $instance = null;
    private $db;
    private $validator;
    
    // Threat levels
    const THREAT_LEVEL_LOW = 1;
    const THREAT_LEVEL_MEDIUM = 2;
    const THREAT_LEVEL_HIGH = 3;
    const THREAT_LEVEL_CRITICAL = 4;
    
    // Input contexts
    const CONTEXT_HTML = 'html';
    const CONTEXT_SQL = 'sql';
    const CONTEXT_JAVASCRIPT = 'javascript';
    const CONTEXT_CSS = 'css';
    const CONTEXT_URL = 'url';
    const CONTEXT_EMAIL = 'email';
    const CONTEXT_FILENAME = 'filename';
    const CONTEXT_JSON = 'json';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->validator = InputValidator::getInstance();
        $this->initializeSecurityTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize security monitoring tables
     */
    private function initializeSecurityTables() {
        $tables = [
            // Input threat detection log
            "CREATE TABLE IF NOT EXISTS input_threat_log (
                id VARCHAR(36) PRIMARY KEY,
                threat_type VARCHAR(50) NOT NULL,
                threat_level TINYINT NOT NULL,
                input_source VARCHAR(100) NOT NULL,
                input_data_hash VARCHAR(64) NOT NULL,
                threat_patterns JSON,
                user_id VARCHAR(36),
                user_type ENUM('admin', 'user', 'anonymous') DEFAULT 'anonymous',
                ip_address VARCHAR(45),
                user_agent TEXT,
                endpoint VARCHAR(200),
                request_method VARCHAR(10),
                blocked BOOLEAN DEFAULT FALSE,
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_threat_type (threat_type),
                INDEX idx_threat_level (threat_level),
                INDEX idx_ip_address (ip_address),
                INDEX idx_detected_at (detected_at)
            )",
            
            // Input validation rules
            "CREATE TABLE IF NOT EXISTS input_validation_rules (
                id VARCHAR(36) PRIMARY KEY,
                rule_name VARCHAR(100) NOT NULL UNIQUE,
                rule_type ENUM('regex', 'function', 'whitelist', 'blacklist') NOT NULL,
                rule_pattern TEXT NOT NULL,
                context VARCHAR(50) NOT NULL,
                severity TINYINT NOT NULL DEFAULT 2,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_rule_type (rule_type),
                INDEX idx_context (context),
                INDEX idx_severity (severity)
            )",
            
            // Input sanitization log
            "CREATE TABLE IF NOT EXISTS input_sanitization_log (
                id VARCHAR(36) PRIMARY KEY,
                original_input_hash VARCHAR(64) NOT NULL,
                sanitized_input_hash VARCHAR(64) NOT NULL,
                sanitization_method VARCHAR(100) NOT NULL,
                context VARCHAR(50) NOT NULL,
                changes_made JSON,
                user_id VARCHAR(36),
                ip_address VARCHAR(45),
                endpoint VARCHAR(200),
                sanitized_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sanitization_method (sanitization_method),
                INDEX idx_context (context),
                INDEX idx_sanitized_at (sanitized_at)
            )",
            
            // Rate limiting for input validation
            "CREATE TABLE IF NOT EXISTS input_rate_limiting (
                id VARCHAR(36) PRIMARY KEY,
                identifier VARCHAR(100) NOT NULL,
                identifier_type ENUM('ip', 'user', 'session') NOT NULL,
                endpoint VARCHAR(200) NOT NULL,
                request_count INT DEFAULT 1,
                window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                blocked_until TIMESTAMP NULL,
                UNIQUE KEY unique_rate_limit (identifier, identifier_type, endpoint),
                INDEX idx_identifier (identifier, identifier_type),
                INDEX idx_window_start (window_start),
                INDEX idx_blocked_until (blocked_until)
            )",
            
            // Parameter tampering detection
            "CREATE TABLE IF NOT EXISTS parameter_tampering_log (
                id VARCHAR(36) PRIMARY KEY,
                parameter_name VARCHAR(100) NOT NULL,
                expected_type VARCHAR(50) NOT NULL,
                received_type VARCHAR(50) NOT NULL,
                expected_pattern VARCHAR(200),
                received_value_hash VARCHAR(64) NOT NULL,
                tampering_indicators JSON,
                user_id VARCHAR(36),
                ip_address VARCHAR(45),
                endpoint VARCHAR(200),
                detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parameter_name (parameter_name),
                INDEX idx_endpoint (endpoint),
                INDEX idx_detected_at (detected_at)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create input security table: " . $e->getMessage());
            }
        }
        
        $this->initializeDefaultRules();
    }
    
    /**
     * Initialize default validation rules
     */
    private function initializeDefaultRules() {
        // Check if rules already exist
        $query = "SELECT COUNT(*) FROM input_validation_rules";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return; // Already initialized
        }
        
        $defaultRules = [
            // Advanced SQL injection patterns
            ['sql_injection_union', 'regex', '/(\bUNION\b.*\bSELECT\b)/i', self::CONTEXT_SQL, self::THREAT_LEVEL_CRITICAL],
            ['sql_injection_stacked', 'regex', '/;\s*(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b/i', self::CONTEXT_SQL, self::THREAT_LEVEL_CRITICAL],
            ['sql_injection_comment', 'regex', '/(\/\*.*\*\/|--.*$|#.*$)/m', self::CONTEXT_SQL, self::THREAT_LEVEL_HIGH],
            ['sql_injection_blind', 'regex', '/(\bAND\b|\bOR\b)\s+\d+\s*[=<>]\s*\d+/i', self::CONTEXT_SQL, self::THREAT_LEVEL_HIGH],
            ['sql_injection_time', 'regex', '/\b(SLEEP|WAITFOR|DELAY)\s*\(/i', self::CONTEXT_SQL, self::THREAT_LEVEL_CRITICAL],
            
            // Advanced XSS patterns
            ['xss_script_advanced', 'regex', '/<script[^>]*>[\s\S]*?<\/script>/i', self::CONTEXT_HTML, self::THREAT_LEVEL_CRITICAL],
            ['xss_event_handlers', 'regex', '/\bon\w+\s*=\s*["\']?[^"\'>\s]+/i', self::CONTEXT_HTML, self::THREAT_LEVEL_HIGH],
            ['xss_javascript_protocol', 'regex', '/javascript\s*:/i', self::CONTEXT_HTML, self::THREAT_LEVEL_HIGH],
            ['xss_data_protocol', 'regex', '/data\s*:\s*[^,]*[,;]/i', self::CONTEXT_HTML, self::THREAT_LEVEL_MEDIUM],
            ['xss_svg_script', 'regex', '/<svg[^>]*>[\s\S]*?<script/i', self::CONTEXT_HTML, self::THREAT_LEVEL_HIGH],
            
            // Code injection patterns
            ['code_injection_eval', 'regex', '/\beval\s*\(/i', self::CONTEXT_JAVASCRIPT, self::THREAT_LEVEL_CRITICAL],
            ['code_injection_function', 'regex', '/\bFunction\s*\(/i', self::CONTEXT_JAVASCRIPT, self::THREAT_LEVEL_HIGH],
            ['code_injection_php', 'regex', '/<\?(?:php)?\s/i', self::CONTEXT_HTML, self::THREAT_LEVEL_CRITICAL],
            ['code_injection_asp', 'regex', '/<%[\s\S]*?%>/i', self::CONTEXT_HTML, self::THREAT_LEVEL_CRITICAL],
            
            // Path traversal patterns
            ['path_traversal_basic', 'regex', '/\.\.[\/\\\\]/i', self::CONTEXT_FILENAME, self::THREAT_LEVEL_HIGH],
            ['path_traversal_encoded', 'regex', '/%2e%2e[%2f%5c]/i', self::CONTEXT_URL, self::THREAT_LEVEL_HIGH],
            ['path_traversal_unicode', 'regex', '/\u002e\u002e[\u002f\u005c]/i', self::CONTEXT_FILENAME, self::THREAT_LEVEL_HIGH],
            
            // Command injection patterns
            ['command_injection_basic', 'regex', '/[;&|`$(){}[\]]/i', self::CONTEXT_FILENAME, self::THREAT_LEVEL_HIGH],
            ['command_injection_encoded', 'regex', '/%[0-9a-f]{2}/i', self::CONTEXT_URL, self::THREAT_LEVEL_MEDIUM],
            
            // LDAP injection patterns
            ['ldap_injection', 'regex', '/[()&|!*]/i', 'ldap', self::THREAT_LEVEL_HIGH],
            
            // NoSQL injection patterns
            ['nosql_injection', 'regex', '/\$\w+\s*:/i', 'nosql', self::THREAT_LEVEL_HIGH]
        ];
        
        foreach ($defaultRules as $rule) {
            $this->createValidationRule($rule[0], $rule[1], $rule[2], $rule[3], $rule[4]);
        }
    }
    
    /**
     * Create validation rule
     */
    public function createValidationRule($name, $type, $pattern, $context, $severity) {
        $ruleId = bin2hex(random_bytes(16));
        
        $query = "INSERT INTO input_validation_rules (
            id, rule_name, rule_type, rule_pattern, context, severity
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$ruleId, $name, $type, $pattern, $context, $severity]);
    }
    
    /**
     * Advanced input validation with threat detection
     */
    public function validateInput($input, $context = self::CONTEXT_HTML, $rules = []) {
        $threats = [];
        $sanitized = $input;
        
        // Get validation rules for context
        $contextRules = $this->getValidationRules($context);
        
        // Check against all rules
        foreach ($contextRules as $rule) {
            $threatFound = $this->checkRule($input, $rule);
            if ($threatFound) {
                $threats[] = $threatFound;
            }
        }
        
        // Check custom rules
        foreach ($rules as $customRule) {
            $threatFound = $this->checkCustomRule($input, $customRule);
            if ($threatFound) {
                $threats[] = $threatFound;
            }
        }
        
        // Determine overall threat level
        $maxThreatLevel = $this->calculateMaxThreatLevel($threats);
        
        // Log threats if found
        if (!empty($threats)) {
            $this->logThreat($input, $threats, $maxThreatLevel, $context);
        }
        
        // Sanitize input based on context
        $sanitized = $this->sanitizeInput($input, $context);
        
        // Log sanitization if changes were made
        if ($sanitized !== $input) {
            $this->logSanitization($input, $sanitized, $context);
        }
        
        return [
            'original' => $input,
            'sanitized' => $sanitized,
            'threats' => $threats,
            'threat_level' => $maxThreatLevel,
            'is_safe' => $maxThreatLevel < self::THREAT_LEVEL_HIGH
        ];
    }
    
    /**
     * Advanced input sanitization
     */
    public function sanitizeInput($input, $context = self::CONTEXT_HTML) {
        if (!is_string($input)) {
            return $input;
        }
        
        switch ($context) {
            case self::CONTEXT_HTML:
                return $this->sanitizeHTML($input);
                
            case self::CONTEXT_SQL:
                return $this->sanitizeSQL($input);
                
            case self::CONTEXT_JAVASCRIPT:
                return $this->sanitizeJavaScript($input);
                
            case self::CONTEXT_CSS:
                return $this->sanitizeCSS($input);
                
            case self::CONTEXT_URL:
                return $this->sanitizeURL($input);
                
            case self::CONTEXT_EMAIL:
                return $this->sanitizeEmail($input);
                
            case self::CONTEXT_FILENAME:
                return $this->sanitizeFilename($input);
                
            case self::CONTEXT_JSON:
                return $this->sanitizeJSON($input);
                
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Detect parameter tampering
     */
    public function detectParameterTampering($parameters, $expectedSchema) {
        $tamperingDetected = [];
        
        foreach ($expectedSchema as $paramName => $expectedConfig) {
            if (!isset($parameters[$paramName])) {
                if ($expectedConfig['required'] ?? false) {
                    $tamperingDetected[] = [
                        'parameter' => $paramName,
                        'issue' => 'missing_required_parameter',
                        'severity' => self::THREAT_LEVEL_MEDIUM
                    ];
                }
                continue;
            }
            
            $value = $parameters[$paramName];
            $expectedType = $expectedConfig['type'] ?? 'string';
            $actualType = gettype($value);
            
            // Type validation
            if (!$this->validateParameterType($value, $expectedType)) {
                $tamperingDetected[] = [
                    'parameter' => $paramName,
                    'issue' => 'type_mismatch',
                    'expected' => $expectedType,
                    'actual' => $actualType,
                    'severity' => self::THREAT_LEVEL_HIGH
                ];
            }
            
            // Length validation
            if (isset($expectedConfig['max_length']) && strlen($value) > $expectedConfig['max_length']) {
                $tamperingDetected[] = [
                    'parameter' => $paramName,
                    'issue' => 'length_exceeded',
                    'max_length' => $expectedConfig['max_length'],
                    'actual_length' => strlen($value),
                    'severity' => self::THREAT_LEVEL_MEDIUM
                ];
            }
            
            // Pattern validation
            if (isset($expectedConfig['pattern']) && !preg_match($expectedConfig['pattern'], $value)) {
                $tamperingDetected[] = [
                    'parameter' => $paramName,
                    'issue' => 'pattern_mismatch',
                    'pattern' => $expectedConfig['pattern'],
                    'severity' => self::THREAT_LEVEL_HIGH
                ];
            }
            
            // Range validation for numeric values
            if (is_numeric($value)) {
                if (isset($expectedConfig['min_value']) && $value < $expectedConfig['min_value']) {
                    $tamperingDetected[] = [
                        'parameter' => $paramName,
                        'issue' => 'value_below_minimum',
                        'min_value' => $expectedConfig['min_value'],
                        'actual_value' => $value,
                        'severity' => self::THREAT_LEVEL_MEDIUM
                    ];
                }
                
                if (isset($expectedConfig['max_value']) && $value > $expectedConfig['max_value']) {
                    $tamperingDetected[] = [
                        'parameter' => $paramName,
                        'issue' => 'value_above_maximum',
                        'max_value' => $expectedConfig['max_value'],
                        'actual_value' => $value,
                        'severity' => self::THREAT_LEVEL_MEDIUM
                    ];
                }
            }
        }
        
        // Check for unexpected parameters
        foreach ($parameters as $paramName => $value) {
            if (!isset($expectedSchema[$paramName])) {
                $tamperingDetected[] = [
                    'parameter' => $paramName,
                    'issue' => 'unexpected_parameter',
                    'severity' => self::THREAT_LEVEL_LOW
                ];
            }
        }
        
        // Log tampering if detected
        if (!empty($tamperingDetected)) {
            $this->logParameterTampering($tamperingDetected);
        }
        
        return $tamperingDetected;
    }
    
    /**
     * Rate limiting for input validation
     */
    public function checkRateLimit($identifier, $identifierType = 'ip', $endpoint = null, $limit = 100, $windowMinutes = 60) {
        $endpoint = $endpoint ?: ($_SERVER['REQUEST_URI'] ?? 'unknown');
        
        $query = "SELECT request_count, window_start, blocked_until 
                  FROM input_rate_limiting 
                  WHERE identifier = ? AND identifier_type = ? AND endpoint = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$identifier, $identifierType, $endpoint]);
        $existing = $stmt->fetch();
        
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        if ($existing) {
            // Check if currently blocked
            if ($existing['blocked_until'] && strtotime($existing['blocked_until']) > $now) {
                return [
                    'allowed' => false,
                    'reason' => 'rate_limited',
                    'blocked_until' => $existing['blocked_until'],
                    'requests_made' => $existing['request_count']
                ];
            }
            
            // Check if window has expired
            if (strtotime($existing['window_start']) < $windowStart) {
                // Reset window
                $query = "UPDATE input_rate_limiting 
                         SET request_count = 1, window_start = NOW(), last_request = NOW(), blocked_until = NULL
                         WHERE identifier = ? AND identifier_type = ? AND endpoint = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$identifier, $identifierType, $endpoint]);
                
                return ['allowed' => true, 'requests_made' => 1, 'limit' => $limit];
            }
            
            // Increment counter
            $newCount = $existing['request_count'] + 1;
            
            if ($newCount > $limit) {
                // Block for next window
                $blockedUntil = date('Y-m-d H:i:s', $now + ($windowMinutes * 60));
                
                $query = "UPDATE input_rate_limiting 
                         SET request_count = ?, last_request = NOW(), blocked_until = ?
                         WHERE identifier = ? AND identifier_type = ? AND endpoint = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$newCount, $blockedUntil, $identifier, $identifierType, $endpoint]);
                
                return [
                    'allowed' => false,
                    'reason' => 'rate_limit_exceeded',
                    'blocked_until' => $blockedUntil,
                    'requests_made' => $newCount
                ];
            }
            
            // Update counter
            $query = "UPDATE input_rate_limiting 
                     SET request_count = ?, last_request = NOW()
                     WHERE identifier = ? AND identifier_type = ? AND endpoint = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$newCount, $identifier, $identifierType, $endpoint]);
            
            return ['allowed' => true, 'requests_made' => $newCount, 'limit' => $limit];
            
        } else {
            // Create new rate limit entry
            $rateLimitId = bin2hex(random_bytes(16));
            
            $query = "INSERT INTO input_rate_limiting 
                     (id, identifier, identifier_type, endpoint, request_count) 
                     VALUES (?, ?, ?, ?, 1)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$rateLimitId, $identifier, $identifierType, $endpoint]);
            
            return ['allowed' => true, 'requests_made' => 1, 'limit' => $limit];
        }
    }

    /**
     * Helper methods
     */

    private function getValidationRules($context) {
        $query = "SELECT * FROM input_validation_rules WHERE context = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$context]);
        return $stmt->fetchAll();
    }

    private function checkRule($input, $rule) {
        switch ($rule['rule_type']) {
            case 'regex':
                if (preg_match($rule['rule_pattern'], $input, $matches)) {
                    return [
                        'rule_name' => $rule['rule_name'],
                        'threat_level' => $rule['severity'],
                        'matches' => $matches,
                        'pattern' => $rule['rule_pattern']
                    ];
                }
                break;

            case 'function':
                // Custom function validation
                if (function_exists($rule['rule_pattern'])) {
                    $result = call_user_func($rule['rule_pattern'], $input);
                    if ($result !== true) {
                        return [
                            'rule_name' => $rule['rule_name'],
                            'threat_level' => $rule['severity'],
                            'result' => $result
                        ];
                    }
                }
                break;

            case 'blacklist':
                $blacklist = json_decode($rule['rule_pattern'], true);
                if (in_array($input, $blacklist)) {
                    return [
                        'rule_name' => $rule['rule_name'],
                        'threat_level' => $rule['severity'],
                        'blacklisted_value' => $input
                    ];
                }
                break;

            case 'whitelist':
                $whitelist = json_decode($rule['rule_pattern'], true);
                if (!in_array($input, $whitelist)) {
                    return [
                        'rule_name' => $rule['rule_name'],
                        'threat_level' => $rule['severity'],
                        'not_whitelisted' => $input
                    ];
                }
                break;
        }

        return false;
    }

    private function checkCustomRule($input, $rule) {
        if (isset($rule['pattern']) && preg_match($rule['pattern'], $input)) {
            return [
                'rule_name' => $rule['name'] ?? 'custom_rule',
                'threat_level' => $rule['severity'] ?? self::THREAT_LEVEL_MEDIUM,
                'custom_rule' => true
            ];
        }
        return false;
    }

    private function calculateMaxThreatLevel($threats) {
        $maxLevel = 0;
        foreach ($threats as $threat) {
            $maxLevel = max($maxLevel, $threat['threat_level']);
        }
        return $maxLevel;
    }

    private function sanitizeHTML($input) {
        // Remove dangerous tags and attributes
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6>';
        $cleaned = strip_tags($input, $allowedTags);

        // Remove dangerous attributes
        $cleaned = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleaned);
        $cleaned = preg_replace('/\sjavascript\s*:/i', '', $cleaned);
        $cleaned = preg_replace('/\sdata\s*:/i', '', $cleaned);

        return htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeSQL($input) {
        // This should never be used - always use prepared statements
        // But as a last resort, escape dangerous characters
        return addslashes($input);
    }

    private function sanitizeJavaScript($input) {
        // Remove dangerous JavaScript patterns
        $patterns = [
            '/eval\s*\(/i',
            '/Function\s*\(/i',
            '/setTimeout\s*\(/i',
            '/setInterval\s*\(/i',
            '/document\./i',
            '/window\./i'
        ];

        foreach ($patterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    private function sanitizeCSS($input) {
        // Allow only safe CSS characters
        return preg_replace('/[^a-zA-Z0-9\-_#.]/', '', $input);
    }

    private function sanitizeURL($input) {
        // Validate and sanitize URL
        $parsed = parse_url($input);
        if (!$parsed || !isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return '';
        }

        return filter_var($input, FILTER_SANITIZE_URL);
    }

    private function sanitizeEmail($input) {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }

    private function sanitizeFilename($input) {
        // Remove dangerous characters from filename
        $cleaned = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $input);
        $cleaned = preg_replace('/\.{2,}/', '.', $cleaned); // Remove multiple dots
        return substr($cleaned, 0, 255); // Limit length
    }

    private function sanitizeJSON($input) {
        $decoded = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }

        return json_encode($decoded, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    private function validateParameterType($value, $expectedType) {
        switch ($expectedType) {
            case 'string':
                return is_string($value);
            case 'integer':
            case 'int':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'float':
            case 'double':
                return is_float($value) || is_numeric($value);
            case 'boolean':
            case 'bool':
                return is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0]);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value) || (is_string($value) && json_decode($value) !== null);
            default:
                return true;
        }
    }

    private function logThreat($input, $threats, $threatLevel, $context) {
        $threatId = bin2hex(random_bytes(16));

        $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
        $userType = isset($_SESSION['admin_id']) ? 'admin' : (isset($_SESSION['user_id']) ? 'user' : 'anonymous');

        $query = "INSERT INTO input_threat_log (
            id, threat_type, threat_level, input_source, input_data_hash,
            threat_patterns, user_id, user_type, ip_address, user_agent,
            endpoint, request_method, blocked
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $threatId,
            $context,
            $threatLevel,
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            hash('sha256', $input),
            json_encode($threats),
            $userId,
            $userType,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['REQUEST_URI'] ?? null,
            $_SERVER['REQUEST_METHOD'] ?? null,
            $threatLevel >= self::THREAT_LEVEL_HIGH
        ]);

        // Also log to security system
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'input_threat_detected',
            $threatLevel >= self::THREAT_LEVEL_HIGH ? SecurityLogger::LEVEL_CRITICAL : SecurityLogger::LEVEL_WARNING,
            'Input threat detected', [
                'threat_level' => $threatLevel,
                'context' => $context,
                'threat_count' => count($threats),
                'blocked' => $threatLevel >= self::THREAT_LEVEL_HIGH
            ], null, $userType === 'admin' ? $userId : null);
    }

    private function logSanitization($original, $sanitized, $context) {
        $sanitizationId = bin2hex(random_bytes(16));

        $changes = [
            'length_changed' => strlen($original) !== strlen($sanitized),
            'content_modified' => $original !== $sanitized,
            'original_length' => strlen($original),
            'sanitized_length' => strlen($sanitized)
        ];

        $query = "INSERT INTO input_sanitization_log (
            id, original_input_hash, sanitized_input_hash, sanitization_method,
            context, changes_made, user_id, ip_address, endpoint
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $sanitizationId,
            hash('sha256', $original),
            hash('sha256', $sanitized),
            'context_based_sanitization',
            $context,
            json_encode($changes),
            $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['REQUEST_URI'] ?? null
        ]);
    }

    private function logParameterTampering($tamperingDetected) {
        foreach ($tamperingDetected as $tampering) {
            $tamperingId = bin2hex(random_bytes(16));

            $query = "INSERT INTO parameter_tampering_log (
                id, parameter_name, expected_type, received_type, expected_pattern,
                received_value_hash, tampering_indicators, user_id, ip_address, endpoint
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $tamperingId,
                $tampering['parameter'],
                $tampering['expected'] ?? 'unknown',
                $tampering['actual'] ?? 'unknown',
                $tampering['pattern'] ?? null,
                hash('sha256', serialize($tampering)),
                json_encode($tampering),
                $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['REQUEST_URI'] ?? null
            ]);
        }

        // Log to security system
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'parameter_tampering_detected', SecurityLogger::LEVEL_WARNING,
            'Parameter tampering detected', [
                'tampering_count' => count($tamperingDetected),
                'parameters' => array_column($tamperingDetected, 'parameter')
            ], null, isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null);
    }
}

// Convenience functions
function validateInputSecurity($input, $context = EnterpriseInputSecurity::CONTEXT_HTML, $rules = []) {
    $security = EnterpriseInputSecurity::getInstance();
    return $security->validateInput($input, $context, $rules);
}

function sanitizeInputSecurity($input, $context = EnterpriseInputSecurity::CONTEXT_HTML) {
    $security = EnterpriseInputSecurity::getInstance();
    return $security->sanitizeInput($input, $context);
}

function detectParameterTampering($parameters, $expectedSchema) {
    $security = EnterpriseInputSecurity::getInstance();
    return $security->detectParameterTampering($parameters, $expectedSchema);
}

function checkInputRateLimit($identifier, $identifierType = 'ip', $endpoint = null, $limit = 100, $windowMinutes = 60) {
    $security = EnterpriseInputSecurity::getInstance();
    return $security->checkRateLimit($identifier, $identifierType, $endpoint, $limit, $windowMinutes);
}
?>
