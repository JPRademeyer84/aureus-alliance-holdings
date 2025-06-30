<?php
/**
 * SECURE ENVIRONMENT LOADER
 * Loads environment variables from .env files securely
 */

class EnvLoader {
    private static $loaded = false;
    private static $envPath = null;
    
    /**
     * Load environment variables from .env file
     */
    public static function load($envPath = null) {
        if (self::$loaded) {
            return;
        }
        
        // Determine .env file path
        if ($envPath === null) {
            $envPath = dirname(dirname(__DIR__)) . '/.env';
        }
        
        self::$envPath = $envPath;
        
        // Check if .env file exists
        if (!file_exists($envPath)) {
            error_log("WARNING: .env file not found at: $envPath");
            self::$loaded = true;
            return;
        }
        
        // Validate .env file permissions (should not be world-readable)
        $perms = fileperms($envPath);
        if ($perms & 0004) {
            error_log("SECURITY WARNING: .env file is world-readable. Please set permissions to 600.");
        }
        
        // Read and parse .env file
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Set environment variable if not already set
                if (!isset($_ENV[$key]) && getenv($key) === false) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
        error_log("Environment variables loaded from: $envPath");
    }
    
    /**
     * Get environment variable with default value
     */
    public static function get($key, $default = null) {
        self::load();
        
        // Try $_ENV first, then getenv(), then default
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Get required environment variable (throws error if not found)
     */
    public static function getRequired($key) {
        $value = self::get($key);
        
        if ($value === null || $value === '') {
            throw new Exception("Required environment variable '$key' is not set");
        }
        
        return $value;
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        self::load();
        return isset($_ENV[$key]) || getenv($key) !== false;
    }
    
    /**
     * Get all environment variables
     */
    public static function all() {
        self::load();
        return $_ENV;
    }
    
    /**
     * Validate required environment variables for production
     */
    public static function validateProduction() {
        $required = [
            'DB_HOST',
            'DB_NAME', 
            'DB_USER',
            'DB_PASS',
            'API_URL',
            'FRONTEND_URL'
        ];
        
        $missing = [];
        
        foreach ($required as $key) {
            if (!self::has($key) || empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Missing required environment variables for production: " . implode(', ', $missing));
        }
        
        return true;
    }
    
    /**
     * Create a secure .env template
     */
    public static function createTemplate($path = null) {
        if ($path === null) {
            $path = dirname(dirname(__DIR__)) . '/.env';
        }
        
        $template = <<<ENV
# AUREUS ANGEL ALLIANCE - ENVIRONMENT CONFIGURATION
# Copy this file to .env and configure your settings
# IMPORTANT: Never commit .env files to version control

# Database Configuration
DB_HOST=localhost
DB_NAME=aureus_angels
DB_USER=your_db_user
DB_PASS=your_secure_password

# Application URLs
API_URL=http://localhost/aureus-angel-alliance/api
FRONTEND_URL=http://localhost:5173

# Security Configuration
APP_ENV=development
DEBUG=true
SESSION_SECRET=your_session_secret_key_here

# Email Configuration (if needed)
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_email_password

# Third-party API Keys (if needed)
CAPTCHA_SECRET_KEY=your_captcha_secret
ENCRYPTION_KEY=your_encryption_key_here

# Rate Limiting Configuration
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_ATTEMPTS=5
RATE_LIMIT_TIME_WINDOW=900

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=/var/log/aureus/app.log
ENV;

        if (file_put_contents($path, $template)) {
            // Set secure permissions
            chmod($path, 0600);
            return true;
        }
        
        return false;
    }
    
    /**
     * Mask sensitive values for logging
     */
    public static function maskSensitive($key, $value) {
        $sensitiveKeys = [
            'password', 'pass', 'secret', 'key', 'token', 'api_key'
        ];
        
        foreach ($sensitiveKeys as $sensitive) {
            if (stripos($key, $sensitive) !== false) {
                return strlen($value) > 0 ? str_repeat('*', min(8, strlen($value))) : '';
            }
        }
        
        return $value;
    }
    
    /**
     * Get environment status for debugging
     */
    public static function getStatus() {
        self::load();
        
        $status = [
            'loaded' => self::$loaded,
            'env_file_path' => self::$envPath,
            'env_file_exists' => self::$envPath ? file_exists(self::$envPath) : false,
            'variables_count' => count($_ENV),
            'is_production' => Environment::isProduction()
        ];
        
        // Add masked environment variables for debugging
        $status['variables'] = [];
        foreach ($_ENV as $key => $value) {
            $status['variables'][$key] = self::maskSensitive($key, $value);
        }
        
        return $status;
    }
}

// Auto-load environment variables when this file is included
EnvLoader::load();
?>
