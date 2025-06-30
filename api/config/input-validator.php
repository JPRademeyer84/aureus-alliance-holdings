<?php
/**
 * CENTRALIZED INPUT VALIDATION SYSTEM
 * Bank-level input validation and sanitization
 */

require_once 'security-logger.php';

class InputValidator {
    private static $instance = null;
    
    // Validation rules
    private $rules = [
        'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'username' => '/^[a-zA-Z0-9_-]{3,30}$/',
        'password' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
        'phone' => '/^\+?[1-9]\d{1,14}$/',
        'wallet_address' => '/^(0x[a-fA-F0-9]{40}|[13][a-km-zA-HJ-NP-Z1-9]{25,34}|T[A-Za-z1-9]{33})$/',
        'amount' => '/^\d+(\.\d{1,8})?$/',
        'uuid' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',
        'alpha' => '/^[a-zA-Z]+$/',
        'numeric' => '/^\d+$/',
        'url' => '/^https?:\/\/[^\s\/$.?#].[^\s]*$/i',
        'ip_address' => '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',
        'date' => '/^\d{4}-\d{2}-\d{2}$/',
        'datetime' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
        'slug' => '/^[a-z0-9-]+$/',
        'hex_color' => '/^#[a-fA-F0-9]{6}$/',
        'base64' => '/^[A-Za-z0-9+\/]*={0,2}$/'
    ];
    
    // Dangerous patterns to detect
    private $dangerousPatterns = [
        'sql_injection' => [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";].*(\bOR\b|\bAND\b)/i',
            '/\b(exec|execute|sp_|xp_)\b/i'
        ],
        'xss' => [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<link[^>]*>/i',
            '/<meta[^>]*>/i'
        ],
        'code_injection' => [
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/<?php/i',
            '/<\?=/i',
            '/<%/i',
            '/\${.*}/i'
        ],
        'path_traversal' => [
            '/\.\.\//i',
            '/\.\.\\\/i',
            '/%2e%2e%2f/i',
            '/%2e%2e%5c/i',
            '/\0/i'
        ]
    ];
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Validate input data against rules
     */
    public function validate($data, $rules, $context = 'general') {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            try {
                // Check if field is required
                if (isset($rule['required']) && $rule['required'] && empty($value)) {
                    $errors[$field] = "Field '$field' is required";
                    continue;
                }
                
                // Skip validation if field is empty and not required
                if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
                    $sanitized[$field] = null;
                    continue;
                }
                
                // Validate field
                $validatedValue = $this->validateField($field, $value, $rule, $context);
                $sanitized[$field] = $validatedValue;
                
            } catch (ValidationException $e) {
                $errors[$field] = $e->getMessage();
                
                // Log validation failure
                logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'validation_failed', SecurityLogger::LEVEL_WARNING,
                    "Input validation failed", [
                        'field' => $field,
                        'context' => $context,
                        'error' => $e->getMessage(),
                        'value_length' => strlen($value ?? '')
                    ]);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }
    
    /**
     * Validate individual field
     */
    private function validateField($field, $value, $rule, $context) {
        // Convert value to string for validation
        $stringValue = (string)$value;
        
        // Check for dangerous patterns first
        $this->checkDangerousPatterns($field, $stringValue, $context);
        
        // Check length constraints
        if (isset($rule['min_length']) && strlen($stringValue) < $rule['min_length']) {
            throw new ValidationException("Field '$field' must be at least {$rule['min_length']} characters long");
        }
        
        if (isset($rule['max_length']) && strlen($stringValue) > $rule['max_length']) {
            throw new ValidationException("Field '$field' must not exceed {$rule['max_length']} characters");
        }
        
        // Check numeric constraints
        if (isset($rule['min_value']) && is_numeric($value) && $value < $rule['min_value']) {
            throw new ValidationException("Field '$field' must be at least {$rule['min_value']}");
        }
        
        if (isset($rule['max_value']) && is_numeric($value) && $value > $rule['max_value']) {
            throw new ValidationException("Field '$field' must not exceed {$rule['max_value']}");
        }
        
        // Apply type validation
        if (isset($rule['type'])) {
            $this->validateType($field, $value, $rule['type']);
        }
        
        // Apply pattern validation
        if (isset($rule['pattern'])) {
            $pattern = is_string($rule['pattern']) ? $rule['pattern'] : $this->rules[$rule['pattern']] ?? null;
            if ($pattern && !preg_match($pattern, $stringValue)) {
                throw new ValidationException("Field '$field' has invalid format");
            }
        }
        
        // Apply custom validation
        if (isset($rule['custom']) && is_callable($rule['custom'])) {
            $customResult = $rule['custom']($value);
            if ($customResult !== true) {
                throw new ValidationException($customResult ?: "Field '$field' failed custom validation");
            }
        }
        
        // Apply sanitization
        return $this->sanitizeValue($value, $rule);
    }
    
    /**
     * Check for dangerous patterns
     */
    private function checkDangerousPatterns($field, $value, $context) {
        foreach ($this->dangerousPatterns as $patternType => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    // Log security threat
                    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'dangerous_pattern_detected', SecurityLogger::LEVEL_CRITICAL,
                        "Dangerous pattern detected in input", [
                            'field' => $field,
                            'context' => $context,
                            'pattern_type' => $patternType,
                            'value_sample' => substr($value, 0, 100)
                        ]);
                    
                    throw new ValidationException("Field '$field' contains potentially dangerous content");
                }
            }
        }
    }
    
    /**
     * Validate data type
     */
    private function validateType($field, $value, $type) {
        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    throw new ValidationException("Field '$field' must be a string");
                }
                break;
                
            case 'integer':
                if (!is_numeric($value) || !is_int($value + 0)) {
                    throw new ValidationException("Field '$field' must be an integer");
                }
                break;
                
            case 'float':
                if (!is_numeric($value)) {
                    throw new ValidationException("Field '$field' must be a number");
                }
                break;
                
            case 'boolean':
                if (!is_bool($value) && !in_array($value, ['true', 'false', '1', '0', 1, 0], true)) {
                    throw new ValidationException("Field '$field' must be a boolean");
                }
                break;
                
            case 'array':
                if (!is_array($value)) {
                    throw new ValidationException("Field '$field' must be an array");
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new ValidationException("Field '$field' must be a valid email address");
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new ValidationException("Field '$field' must be a valid URL");
                }
                break;
                
            case 'ip':
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    throw new ValidationException("Field '$field' must be a valid IP address");
                }
                break;
        }
    }
    
    /**
     * Sanitize value based on rules
     */
    private function sanitizeValue($value, $rule) {
        // Apply sanitization filters
        if (isset($rule['sanitize'])) {
            foreach ((array)$rule['sanitize'] as $filter) {
                switch ($filter) {
                    case 'trim':
                        $value = trim($value);
                        break;
                        
                    case 'lowercase':
                        $value = strtolower($value);
                        break;
                        
                    case 'uppercase':
                        $value = strtoupper($value);
                        break;
                        
                    case 'strip_tags':
                        $value = strip_tags($value);
                        break;
                        
                    case 'htmlspecialchars':
                        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        break;
                        
                    case 'filter_var':
                        $value = filter_var($value, FILTER_SANITIZE_STRING);
                        break;
                        
                    case 'alphanumeric':
                        $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
                        break;
                        
                    case 'numeric':
                        $value = preg_replace('/[^0-9.]/', '', $value);
                        break;
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Quick validation for common patterns
     */
    public function validateEmail($email) {
        return $this->validate(['email' => $email], [
            'email' => ['type' => 'email', 'required' => true, 'max_length' => 255]
        ]);
    }
    
    public function validateAmount($amount) {
        return $this->validate(['amount' => $amount], [
            'amount' => ['type' => 'float', 'required' => true, 'min_value' => 0, 'max_value' => 999999999]
        ]);
    }
    
    public function validateWalletAddress($address) {
        return $this->validate(['address' => $address], [
            'address' => ['pattern' => 'wallet_address', 'required' => true, 'max_length' => 100]
        ]);
    }
    
    public function validatePassword($password) {
        return $this->validate(['password' => $password], [
            'password' => ['pattern' => 'password', 'required' => true, 'min_length' => 8, 'max_length' => 128]
        ]);
    }
    
    /**
     * Sanitize output for display
     */
    public function sanitizeOutput($data, $context = 'html') {
        if (is_array($data)) {
            return array_map(function($item) use ($context) {
                return $this->sanitizeOutput($item, $context);
            }, $data);
        }
        
        if (!is_string($data)) {
            return $data;
        }
        
        switch ($context) {
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
                
            case 'url':
                return urlencode($data);
                
            case 'js':
                return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                
            case 'css':
                return preg_replace('/[^a-zA-Z0-9\-_]/', '', $data);
                
            case 'sql':
                // This should use prepared statements instead
                return addslashes($data);
                
            default:
                return $data;
        }
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'], $maxSize = 5242880) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('Invalid file upload');
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            throw new ValidationException('File size exceeds maximum allowed size');
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new ValidationException('File type not allowed');
        }
        
        // Check for malicious content
        $content = file_get_contents($file['tmp_name']);
        foreach ($this->dangerousPatterns['xss'] as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new ValidationException('File contains potentially dangerous content');
            }
        }
        
        return true;
    }
}

/**
 * Custom validation exception
 */
class ValidationException extends Exception {}

// Convenience functions
function validateInput($data, $rules, $context = 'general') {
    $validator = InputValidator::getInstance();
    return $validator->validate($data, $rules, $context);
}

function sanitizeOutput($data, $context = 'html') {
    $validator = InputValidator::getInstance();
    return $validator->sanitizeOutput($data, $context);
}

function validateEmail($email) {
    $validator = InputValidator::getInstance();
    return $validator->validateEmail($email);
}

function validateAmount($amount) {
    $validator = InputValidator::getInstance();
    return $validator->validateAmount($amount);
}

function validateWalletAddress($address) {
    $validator = InputValidator::getInstance();
    return $validator->validateWalletAddress($address);
}

function validatePassword($password) {
    $validator = InputValidator::getInstance();
    return $validator->validatePassword($password);
}

/**
 * VALIDATION MIDDLEWARE
 * Easy integration for existing endpoints
 */
class ValidationMiddleware {
    private $validator;

    public function __construct() {
        $this->validator = InputValidator::getInstance();
    }

    /**
     * Validate API request
     */
    public function validateRequest($rules, $context = 'api') {
        $method = $_SERVER['REQUEST_METHOD'];
        $data = [];

        // Get input data based on method
        switch ($method) {
            case 'GET':
                $data = $_GET;
                break;

            case 'POST':
            case 'PUT':
            case 'PATCH':
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

                if (strpos($contentType, 'application/json') !== false) {
                    $json = file_get_contents('php://input');
                    $data = json_decode($json, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->sendValidationError('Invalid JSON format');
                    }
                } else {
                    $data = $_POST;
                }
                break;

            default:
                $this->sendValidationError('Unsupported HTTP method');
        }

        // Validate the data
        $result = $this->validator->validate($data, $rules, $context);

        if (!$result['valid']) {
            $this->sendValidationError('Validation failed', $result['errors']);
        }

        return $result['sanitized'];
    }

    /**
     * Send validation error response
     */
    private function sendValidationError($message, $errors = []) {
        http_response_code(400);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => $message,
            'validation_errors' => $errors,
            'timestamp' => date('c')
        ];

        echo json_encode($response);
        exit;
    }

    /**
     * Validate file uploads
     */
    public function validateFiles($fileRules) {
        $validatedFiles = [];

        foreach ($fileRules as $fieldName => $rules) {
            if (!isset($_FILES[$fieldName])) {
                if ($rules['required'] ?? false) {
                    $this->sendValidationError("File '$fieldName' is required");
                }
                continue;
            }

            $file = $_FILES[$fieldName];

            try {
                $allowedTypes = $rules['allowed_types'] ?? ['image/jpeg', 'image/png', 'application/pdf'];
                $maxSize = $rules['max_size'] ?? 5242880; // 5MB default

                $this->validator->validateFileUpload($file, $allowedTypes, $maxSize);
                $validatedFiles[$fieldName] = $file;

            } catch (ValidationException $e) {
                $this->sendValidationError("File validation failed for '$fieldName'", [$fieldName => $e->getMessage()]);
            }
        }

        return $validatedFiles;
    }
}

/**
 * COMMON VALIDATION RULE SETS
 * Pre-defined rules for common use cases
 */
class ValidationRules {

    public static function userRegistration() {
        return [
            'username' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 3,
                'max_length' => 30,
                'pattern' => 'username',
                'sanitize' => ['trim', 'lowercase']
            ],
            'email' => [
                'type' => 'email',
                'required' => true,
                'max_length' => 255,
                'sanitize' => ['trim', 'lowercase']
            ],
            'password' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 8,
                'max_length' => 128,
                'pattern' => 'password'
            ],
            'full_name' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'sanitize' => ['trim', 'strip_tags']
            ]
        ];
    }

    public static function investment() {
        return [
            'amount' => [
                'type' => 'float',
                'required' => true,
                'min_value' => 25,
                'max_value' => 1000000,
                'pattern' => 'amount'
            ],
            'wallet_address' => [
                'type' => 'string',
                'required' => true,
                'pattern' => 'wallet_address',
                'max_length' => 100,
                'sanitize' => ['trim']
            ],
            'chain' => [
                'type' => 'string',
                'required' => true,
                'custom' => function($value) {
                    $allowedChains = ['ethereum', 'polygon', 'bnb', 'tron'];
                    return in_array($value, $allowedChains) ? true : 'Invalid blockchain chain';
                }
            ],
            'package_name' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'sanitize' => ['trim', 'strip_tags']
            ]
        ];
    }

    public static function kycUpload() {
        return [
            'document_type' => [
                'type' => 'string',
                'required' => true,
                'custom' => function($value) {
                    $allowedTypes = ['passport', 'drivers_license', 'national_id', 'proof_of_address'];
                    return in_array($value, $allowedTypes) ? true : 'Invalid document type';
                }
            ],
            'user_id' => [
                'type' => 'string',
                'required' => true,
                'pattern' => 'uuid'
            ]
        ];
    }

    public static function adminLogin() {
        return [
            'username' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 3,
                'max_length' => 50,
                'sanitize' => ['trim']
            ],
            'password' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 1,
                'max_length' => 255
            ],
            'captcha_token' => [
                'type' => 'string',
                'required' => false,
                'max_length' => 255
            ],
            'captcha_answer' => [
                'type' => 'string',
                'required' => false,
                'max_length' => 10
            ]
        ];
    }

    public static function chatMessage() {
        return [
            'message' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 1,
                'max_length' => 1000,
                'sanitize' => ['trim', 'strip_tags']
            ],
            'session_id' => [
                'type' => 'string',
                'required' => true,
                'pattern' => 'alphanumeric',
                'max_length' => 50
            ]
        ];
    }

    public static function translation() {
        return [
            'key' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 1,
                'max_length' => 255,
                'pattern' => '/^[a-zA-Z0-9._-]+$/',
                'sanitize' => ['trim']
            ],
            'value' => [
                'type' => 'string',
                'required' => true,
                'min_length' => 1,
                'max_length' => 5000,
                'sanitize' => ['trim']
            ],
            'language' => [
                'type' => 'string',
                'required' => true,
                'pattern' => '/^[a-z]{2}(-[A-Z]{2})?$/',
                'max_length' => 5
            ]
        ];
    }

    public static function fileUpload() {
        return [
            'document' => [
                'required' => true,
                'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                'max_size' => 5242880 // 5MB
            ]
        ];
    }

    public static function kycFileUpload() {
        return [
            'document' => [
                'required' => true,
                'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                'max_size' => 10485760 // 10MB for KYC documents
            ]
        ];
    }
}

// Convenience function for middleware
function validateApiRequest($rules, $context = 'api') {
    $middleware = new ValidationMiddleware();
    return $middleware->validateRequest($rules, $context);
}

function validateApiFiles($fileRules) {
    $middleware = new ValidationMiddleware();
    return $middleware->validateFiles($fileRules);
}
?>
