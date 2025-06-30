<?php
// ============================================================================
// PRODUCTION CONFIGURATION FOR AUREUS ANGEL ALLIANCE
// ============================================================================
// This file contains production-ready configuration settings
// ============================================================================

// Environment Configuration
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('ERROR_REPORTING', false);

// Database Configuration (Production)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'aureus_angels_prod');
define('DB_USER', $_ENV['DB_USER'] ?? 'aureus_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('SECURE_COOKIES', true);
define('HTTPS_ONLY', true);
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Encryption Configuration
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? '');
define('ENCRYPTION_CIPHER', 'aes-256-gcm');
define('HASH_ALGORITHM', 'sha256');

// File Upload Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', '/var/www/uploads/');
define('VIRUS_SCAN_ENABLED', true);

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_RATE_LIMIT_WINDOW', 60); // seconds
define('API_TIMEOUT', 30); // seconds

// Email Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'noreply@aureusangels.com');
define('FROM_NAME', 'Aureus Angel Alliance');

// Logging Configuration
define('LOG_LEVEL', 'ERROR');
define('LOG_PATH', '/var/log/aureus/');
define('LOG_MAX_SIZE', 100 * 1024 * 1024); // 100MB
define('LOG_RETENTION_DAYS', 30);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_TYPE', 'redis'); // redis, memcached, file
define('CACHE_HOST', $_ENV['CACHE_HOST'] ?? 'localhost');
define('CACHE_PORT', $_ENV['CACHE_PORT'] ?? 6379);
define('CACHE_TTL', 3600); // 1 hour

// CDN Configuration
define('CDN_ENABLED', true);
define('CDN_URL', $_ENV['CDN_URL'] ?? 'https://cdn.aureusangels.com');
define('STATIC_ASSETS_URL', CDN_URL . '/assets');

// Backup Configuration
define('BACKUP_ENABLED', true);
define('BACKUP_SCHEDULE', '0 2 * * *'); // Daily at 2 AM
define('BACKUP_RETENTION_DAYS', 30);
define('BACKUP_PATH', '/var/backups/aureus/');

// Monitoring Configuration
define('MONITORING_ENABLED', true);
define('HEALTH_CHECK_ENDPOINT', '/api/health');
define('METRICS_ENDPOINT', '/api/metrics');

// Security Headers Configuration
$securityHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self' https:; frame-ancestors 'none';",
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    'X-Permitted-Cross-Domain-Policies' => 'none'
];

// Apply security headers
function applySecurityHeaders() {
    global $securityHeaders;
    
    foreach ($securityHeaders as $header => $value) {
        header("$header: $value");
    }
}

// Database Connection with Production Settings
function getProductionDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt'
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Set SQL mode for strict data handling
        $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        die('Database connection failed');
    }
}

// Error Handling for Production
function productionErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';
    $logMessage = "[$errorType] $errstr in $errfile on line $errline";
    
    error_log($logMessage);
    
    // Don't expose errors to users in production
    if ($errno === E_ERROR || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        http_response_code(500);
        die('Internal server error');
    }
    
    return true;
}

// Exception Handler for Production
function productionExceptionHandler($exception) {
    $logMessage = "Uncaught exception: " . $exception->getMessage() . 
                  " in " . $exception->getFile() . 
                  " on line " . $exception->getLine();
    
    error_log($logMessage);
    error_log("Stack trace: " . $exception->getTraceAsString());
    
    http_response_code(500);
    die('Internal server error');
}

// Session Configuration for Production
function configureProductionSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    ini_set('session.name', 'AUREUSSID');
    
    // Use Redis for session storage if available
    if (CACHE_ENABLED && CACHE_TYPE === 'redis') {
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://' . CACHE_HOST . ':' . CACHE_PORT);
    }
}

// PHP Configuration for Production
function configureProductionPHP() {
    // Error reporting
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php_errors.log');
    
    // Security settings
    ini_set('expose_php', 0);
    ini_set('allow_url_fopen', 0);
    ini_set('allow_url_include', 0);
    ini_set('enable_dl', 0);
    
    // File upload settings
    ini_set('file_uploads', 1);
    ini_set('upload_max_filesize', MAX_FILE_SIZE);
    ini_set('post_max_size', MAX_FILE_SIZE * 2);
    ini_set('max_file_uploads', 10);
    
    // Memory and execution limits
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 30);
    ini_set('max_input_time', 30);
    
    // Set timezone
    date_default_timezone_set('UTC');
}

// Health Check Function
function healthCheck() {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '1.0.0',
        'environment' => ENVIRONMENT,
        'checks' => []
    ];
    
    // Database check
    try {
        $db = getProductionDatabase();
        $stmt = $db->query('SELECT 1');
        $health['checks']['database'] = 'healthy';
    } catch (Exception $e) {
        $health['checks']['database'] = 'unhealthy';
        $health['status'] = 'unhealthy';
    }
    
    // Cache check
    if (CACHE_ENABLED) {
        try {
            if (CACHE_TYPE === 'redis') {
                $redis = new Redis();
                $redis->connect(CACHE_HOST, CACHE_PORT);
                $redis->ping();
                $health['checks']['cache'] = 'healthy';
            }
        } catch (Exception $e) {
            $health['checks']['cache'] = 'unhealthy';
        }
    }
    
    // Disk space check
    $freeSpace = disk_free_space('/');
    $totalSpace = disk_total_space('/');
    $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
    
    if ($usagePercent > 90) {
        $health['checks']['disk_space'] = 'critical';
        $health['status'] = 'unhealthy';
    } elseif ($usagePercent > 80) {
        $health['checks']['disk_space'] = 'warning';
    } else {
        $health['checks']['disk_space'] = 'healthy';
    }
    
    return $health;
}

// Initialize Production Environment
function initializeProduction() {
    // Set error handlers
    set_error_handler('productionErrorHandler');
    set_exception_handler('productionExceptionHandler');
    
    // Configure PHP
    configureProductionPHP();
    
    // Configure session
    configureProductionSession();
    
    // Apply security headers
    applySecurityHeaders();
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Auto-initialize if this file is included
if (!defined('PRODUCTION_CONFIG_LOADED')) {
    define('PRODUCTION_CONFIG_LOADED', true);
    initializeProduction();
}
?>
