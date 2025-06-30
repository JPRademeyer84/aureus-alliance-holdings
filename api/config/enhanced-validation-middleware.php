<?php
/**
 * ENHANCED VALIDATION MIDDLEWARE
 * Advanced input validation middleware with enterprise security features
 */

require_once 'enterprise-input-security.php';
require_once 'input-validator.php';

class EnhancedValidationMiddleware {
    private static $security = null;
    private static $validator = null;
    
    /**
     * Initialize security systems
     */
    private static function init() {
        if (self::$security === null) {
            self::$security = EnterpriseInputSecurity::getInstance();
        }
        if (self::$validator === null) {
            self::$validator = InputValidator::getInstance();
        }
    }
    
    /**
     * Comprehensive request validation
     */
    public static function validateRequest($validationRules = [], $options = []) {
        self::init();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Rate limiting check
        $rateLimitConfig = $options['rate_limit'] ?? ['limit' => 100, 'window' => 60];
        $rateCheck = self::$security->checkRateLimit(
            $userIP, 
            'ip', 
            $endpoint, 
            $rateLimitConfig['limit'], 
            $rateLimitConfig['window']
        );
        
        if (!$rateCheck['allowed']) {
            self::sendError(429, 'Rate limit exceeded', [
                'retry_after' => $rateCheck['blocked_until'] ?? null,
                'requests_made' => $rateCheck['requests_made'] ?? 0
            ]);
        }
        
        // Get request data
        $requestData = self::getRequestData($method);
        
        // Validate CSRF token for state-changing operations
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            self::validateCSRFToken($requestData);
        }
        
        // Validate content type
        self::validateContentType($method);
        
        // Detect parameter tampering
        if (!empty($validationRules)) {
            $tamperingDetected = self::$security->detectParameterTampering($requestData, $validationRules);
            if (!empty($tamperingDetected)) {
                $highSeverityTampering = array_filter($tamperingDetected, function($t) {
                    return $t['severity'] >= EnterpriseInputSecurity::THREAT_LEVEL_HIGH;
                });
                
                if (!empty($highSeverityTampering)) {
                    self::sendError(400, 'Parameter tampering detected', [
                        'tampering_issues' => count($tamperingDetected)
                    ]);
                }
            }
        }
        
        // Validate each input field
        $validatedData = [];
        $validationErrors = [];
        
        foreach ($validationRules as $field => $rules) {
            $value = $requestData[$field] ?? null;
            
            // Check if required field is missing
            if (($rules['required'] ?? false) && ($value === null || $value === '')) {
                $validationErrors[$field] = "Field '$field' is required";
                continue;
            }
            
            // Skip validation for optional empty fields
            if ($value === null || $value === '') {
                $validatedData[$field] = $value;
                continue;
            }
            
            // Determine validation context
            $context = self::determineValidationContext($field, $rules);
            
            // Advanced security validation
            $securityResult = self::$security->validateInput($value, $context, $rules['security_rules'] ?? []);
            
            // Check threat level
            if ($securityResult['threat_level'] >= EnterpriseInputSecurity::THREAT_LEVEL_HIGH) {
                self::sendError(400, 'Input contains security threats', [
                    'field' => $field,
                    'threat_level' => $securityResult['threat_level'],
                    'threats_detected' => count($securityResult['threats'])
                ]);
            }
            
            // Use sanitized value
            $sanitizedValue = $securityResult['sanitized'];
            
            // Standard validation using existing validator
            try {
                $standardValidation = self::$validator->validateField($sanitizedValue, $rules);
                if ($standardValidation !== true) {
                    $validationErrors[$field] = $standardValidation;
                    continue;
                }
            } catch (Exception $e) {
                $validationErrors[$field] = $e->getMessage();
                continue;
            }
            
            // Additional custom validation
            if (isset($rules['custom']) && is_callable($rules['custom'])) {
                $customResult = $rules['custom']($sanitizedValue);
                if ($customResult !== true) {
                    $validationErrors[$field] = is_string($customResult) ? $customResult : "Custom validation failed for '$field'";
                    continue;
                }
            }
            
            $validatedData[$field] = $sanitizedValue;
        }
        
        // Check for validation errors
        if (!empty($validationErrors)) {
            self::sendError(400, 'Validation failed', [
                'validation_errors' => $validationErrors
            ]);
        }
        
        return $validatedData;
    }
    
    /**
     * Validate file uploads with enhanced security
     */
    public static function validateFiles($fileRules = []) {
        self::init();
        
        if (empty($_FILES)) {
            return [];
        }
        
        $validatedFiles = [];
        $validationErrors = [];
        
        foreach ($_FILES as $fieldName => $file) {
            $rules = $fileRules[$fieldName] ?? [];
            
            // Check if required
            if (($rules['required'] ?? false) && $file['error'] === UPLOAD_ERR_NO_FILE) {
                $validationErrors[$fieldName] = "File '$fieldName' is required";
                continue;
            }
            
            // Skip if no file uploaded and not required
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $validationErrors[$fieldName] = self::getUploadErrorMessage($file['error']);
                continue;
            }
            
            // Enhanced file validation
            try {
                $fileValidation = self::validateFileAdvanced($file, $rules);
                if ($fileValidation !== true) {
                    $validationErrors[$fieldName] = $fileValidation;
                    continue;
                }
            } catch (Exception $e) {
                $validationErrors[$fieldName] = $e->getMessage();
                continue;
            }
            
            $validatedFiles[$fieldName] = $file;
        }
        
        if (!empty($validationErrors)) {
            self::sendError(400, 'File validation failed', [
                'file_errors' => $validationErrors
            ]);
        }
        
        return $validatedFiles;
    }
    
    /**
     * Validate API key or token
     */
    public static function validateAPIAuthentication($requiredLevel = 'user') {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $apiKey = $_GET['api_key'] ?? '';
        
        if (empty($authHeader) && empty($apiKey)) {
            self::sendError(401, 'Authentication required');
        }
        
        // Extract token from header
        if (!empty($authHeader)) {
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            } else {
                self::sendError(401, 'Invalid authorization header format');
            }
        } else {
            $token = $apiKey;
        }
        
        // Validate token format
        $tokenValidation = self::$security->validateInput($token, EnterpriseInputSecurity::CONTEXT_HTML);
        if ($tokenValidation['threat_level'] >= EnterpriseInputSecurity::THREAT_LEVEL_MEDIUM) {
            self::sendError(401, 'Invalid token format');
        }
        
        // Additional token validation would go here
        // For now, we'll just check basic format
        if (strlen($token) < 32) {
            self::sendError(401, 'Invalid token');
        }
        
        return $token;
    }
    
    /**
     * Helper methods
     */
    
    private static function getRequestData($method) {
        switch ($method) {
            case 'GET':
                return $_GET;
                
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                
                if (strpos($contentType, 'application/json') !== false) {
                    $json = file_get_contents('php://input');
                    $data = json_decode($json, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        self::sendError(400, 'Invalid JSON format: ' . json_last_error_msg());
                    }
                    
                    return $data ?: [];
                } else {
                    return $_POST;
                }
                
            case 'DELETE':
                parse_str(file_get_contents('php://input'), $data);
                return $data;
                
            default:
                return [];
        }
    }
    
    private static function validateCSRFToken($requestData) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $token = $requestData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token)) {
            self::sendError(403, 'CSRF token required');
        }
        
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            self::sendError(403, 'Invalid CSRF token');
        }
    }
    
    private static function validateContentType($method) {
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return;
        }
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $allowedTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data'
        ];
        
        $isValidType = false;
        foreach ($allowedTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                $isValidType = true;
                break;
            }
        }
        
        if (!$isValidType && !empty($contentType)) {
            self::sendError(415, 'Unsupported content type');
        }
    }
    
    private static function determineValidationContext($field, $rules) {
        // Determine context based on field name and rules
        if (isset($rules['context'])) {
            return $rules['context'];
        }
        
        $fieldLower = strtolower($field);
        
        if (strpos($fieldLower, 'email') !== false) {
            return EnterpriseInputSecurity::CONTEXT_EMAIL;
        }
        
        if (strpos($fieldLower, 'url') !== false || strpos($fieldLower, 'link') !== false) {
            return EnterpriseInputSecurity::CONTEXT_URL;
        }
        
        if (strpos($fieldLower, 'filename') !== false || strpos($fieldLower, 'file') !== false) {
            return EnterpriseInputSecurity::CONTEXT_FILENAME;
        }
        
        if (isset($rules['type']) && $rules['type'] === 'json') {
            return EnterpriseInputSecurity::CONTEXT_JSON;
        }
        
        return EnterpriseInputSecurity::CONTEXT_HTML;
    }
    
    private static function validateFileAdvanced($file, $rules) {
        // File size validation
        $maxSize = $rules['max_size'] ?? 10485760; // 10MB default
        if ($file['size'] > $maxSize) {
            return "File size exceeds maximum allowed size of " . number_format($maxSize / 1024 / 1024, 2) . "MB";
        }
        
        // MIME type validation
        $allowedTypes = $rules['allowed_types'] ?? ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            return "File type '{$file['type']}' is not allowed";
        }
        
        // File extension validation
        $allowedExtensions = $rules['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'pdf'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return "File extension '$extension' is not allowed";
        }
        
        // Advanced content validation
        try {
            self::$validator->validateFile($file);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        
        // Additional malware scanning could be added here
        
        return true;
    }
    
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    private static function sendError($statusCode, $message, $details = []) {
        http_response_code($statusCode);
        
        $response = [
            'error' => $message,
            'status_code' => $statusCode,
            'timestamp' => date('c')
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        // Log security event for high-severity errors
        if ($statusCode >= 400) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'validation_error', 
                $statusCode >= 500 ? SecurityLogger::LEVEL_CRITICAL : SecurityLogger::LEVEL_WARNING,
                "Validation error: $message", array_merge($response, [
                    'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]));
        }
        
        echo json_encode($response);
        exit;
    }
}

// Convenience functions
function validateEnhancedRequest($validationRules = [], $options = []) {
    return EnhancedValidationMiddleware::validateRequest($validationRules, $options);
}

function validateEnhancedFiles($fileRules = []) {
    return EnhancedValidationMiddleware::validateFiles($fileRules);
}

function validateAPIAuth($requiredLevel = 'user') {
    return EnhancedValidationMiddleware::validateAPIAuthentication($requiredLevel);
}
?>
