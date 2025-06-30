<?php
// ============================================================================
// ENVIRONMENT VARIABLES SETUP FOR AUREUS ANGEL ALLIANCE
// ============================================================================
// This script manages secure environment variable configuration
// ============================================================================

class EnvironmentManager {
    private $envFile;
    private $templateFile;
    private $backupDir;
    
    public function __construct() {
        $this->envFile = dirname(__DIR__) . '/.env';
        $this->templateFile = __DIR__ . '/.env.template';
        $this->backupDir = __DIR__ . '/backups';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function createEnvironmentTemplate() {
        $template = $this->getEnvironmentTemplate();
        
        if (file_put_contents($this->templateFile, $template)) {
            echo "✅ Environment template created: {$this->templateFile}\n";
            return true;
        } else {
            echo "❌ Failed to create environment template\n";
            return false;
        }
    }
    
    public function setupEnvironment($config = []) {
        // Backup existing .env if it exists
        if (file_exists($this->envFile)) {
            $this->backupEnvironment();
        }
        
        // Generate secure values
        $envConfig = $this->generateSecureConfig($config);
        
        // Create .env file
        $envContent = $this->buildEnvironmentFile($envConfig);
        
        if (file_put_contents($this->envFile, $envContent)) {
            chmod($this->envFile, 0600); // Secure permissions
            echo "✅ Environment file created: {$this->envFile}\n";
            return true;
        } else {
            echo "❌ Failed to create environment file\n";
            return false;
        }
    }
    
    private function backupEnvironment() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . "/.env.backup.$timestamp";
        
        if (copy($this->envFile, $backupFile)) {
            echo "📦 Environment backed up to: $backupFile\n";
        }
    }
    
    private function generateSecureConfig($userConfig = []) {
        $config = [
            // Database Configuration
            'DB_HOST' => $userConfig['DB_HOST'] ?? 'localhost',
            'DB_NAME' => $userConfig['DB_NAME'] ?? 'aureus_angels_prod',
            'DB_USER' => $userConfig['DB_USER'] ?? 'aureus_user',
            'DB_PASS' => $userConfig['DB_PASS'] ?? $this->generateSecurePassword(32),
            'DB_PORT' => $userConfig['DB_PORT'] ?? '3306',
            
            // Security Keys
            'ENCRYPTION_KEY' => $this->generateSecureKey(64),
            'JWT_SECRET' => $this->generateSecureKey(64),
            'SESSION_SECRET' => $this->generateSecureKey(32),
            'API_SECRET' => $this->generateSecureKey(32),
            
            // Application Configuration
            'APP_ENV' => $userConfig['APP_ENV'] ?? 'production',
            'APP_DEBUG' => $userConfig['APP_DEBUG'] ?? 'false',
            'APP_URL' => $userConfig['APP_URL'] ?? 'https://aureusangels.com',
            'APP_NAME' => 'Aureus Angel Alliance',
            
            // Email Configuration
            'SMTP_HOST' => $userConfig['SMTP_HOST'] ?? 'smtp.gmail.com',
            'SMTP_PORT' => $userConfig['SMTP_PORT'] ?? '587',
            'SMTP_USERNAME' => $userConfig['SMTP_USERNAME'] ?? '',
            'SMTP_PASSWORD' => $userConfig['SMTP_PASSWORD'] ?? '',
            'SMTP_ENCRYPTION' => $userConfig['SMTP_ENCRYPTION'] ?? 'tls',
            'FROM_EMAIL' => $userConfig['FROM_EMAIL'] ?? 'noreply@aureusangels.com',
            'FROM_NAME' => 'Aureus Angel Alliance',
            
            // File Upload Configuration
            'MAX_FILE_SIZE' => '10485760', // 10MB
            'UPLOAD_PATH' => '/var/www/uploads',
            'ALLOWED_FILE_TYPES' => 'jpg,jpeg,png,pdf,doc,docx',
            
            // Security Configuration
            'SECURE_COOKIES' => 'true',
            'HTTPS_ONLY' => 'true',
            'SESSION_TIMEOUT' => '1800', // 30 minutes
            'MAX_LOGIN_ATTEMPTS' => '5',
            'LOGIN_LOCKOUT_TIME' => '900', // 15 minutes
            
            // API Configuration
            'API_RATE_LIMIT' => '100',
            'API_RATE_LIMIT_WINDOW' => '60',
            'API_TIMEOUT' => '30',
            
            // Cache Configuration
            'CACHE_DRIVER' => 'redis',
            'REDIS_HOST' => $userConfig['REDIS_HOST'] ?? 'localhost',
            'REDIS_PORT' => $userConfig['REDIS_PORT'] ?? '6379',
            'REDIS_PASSWORD' => $userConfig['REDIS_PASSWORD'] ?? '',
            
            // Blockchain Configuration
            'POLYGON_RPC_URL' => $userConfig['POLYGON_RPC_URL'] ?? 'https://polygon-rpc.com',
            'USDT_CONTRACT_ADDRESS' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
            'PRIVATE_KEY' => $userConfig['PRIVATE_KEY'] ?? '',
            
            // External Services
            'GOOGLE_ANALYTICS_ID' => $userConfig['GOOGLE_ANALYTICS_ID'] ?? '',
            'RECAPTCHA_SITE_KEY' => $userConfig['RECAPTCHA_SITE_KEY'] ?? '',
            'RECAPTCHA_SECRET_KEY' => $userConfig['RECAPTCHA_SECRET_KEY'] ?? '',
            
            // Monitoring
            'LOG_LEVEL' => 'error',
            'LOG_PATH' => '/var/log/aureus',
            'MONITORING_ENABLED' => 'true',
            
            // Backup Configuration
            'BACKUP_ENABLED' => 'true',
            'BACKUP_PATH' => '/var/backups/aureus',
            'BACKUP_RETENTION_DAYS' => '30',
            
            // Social Media API Keys
            'FACEBOOK_APP_ID' => $userConfig['FACEBOOK_APP_ID'] ?? '',
            'FACEBOOK_APP_SECRET' => $userConfig['FACEBOOK_APP_SECRET'] ?? '',
            'TWITTER_API_KEY' => $userConfig['TWITTER_API_KEY'] ?? '',
            'TWITTER_API_SECRET' => $userConfig['TWITTER_API_SECRET'] ?? '',
            'LINKEDIN_CLIENT_ID' => $userConfig['LINKEDIN_CLIENT_ID'] ?? '',
            'LINKEDIN_CLIENT_SECRET' => $userConfig['LINKEDIN_CLIENT_SECRET'] ?? '',
        ];
        
        return $config;
    }
    
    private function generateSecureKey($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    private function generateSecurePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    private function buildEnvironmentFile($config) {
        $content = "# ============================================================================\n";
        $content .= "# AUREUS ANGEL ALLIANCE - PRODUCTION ENVIRONMENT VARIABLES\n";
        $content .= "# ============================================================================\n";
        $content .= "# Generated on: " . date('Y-m-d H:i:s') . "\n";
        $content .= "# IMPORTANT: Keep this file secure and never commit to version control\n";
        $content .= "# ============================================================================\n\n";
        
        $sections = [
            'DATABASE CONFIGURATION' => ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'],
            'SECURITY KEYS' => ['ENCRYPTION_KEY', 'JWT_SECRET', 'SESSION_SECRET', 'API_SECRET'],
            'APPLICATION SETTINGS' => ['APP_ENV', 'APP_DEBUG', 'APP_URL', 'APP_NAME'],
            'EMAIL CONFIGURATION' => ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_ENCRYPTION', 'FROM_EMAIL', 'FROM_NAME'],
            'FILE UPLOAD SETTINGS' => ['MAX_FILE_SIZE', 'UPLOAD_PATH', 'ALLOWED_FILE_TYPES'],
            'SECURITY SETTINGS' => ['SECURE_COOKIES', 'HTTPS_ONLY', 'SESSION_TIMEOUT', 'MAX_LOGIN_ATTEMPTS', 'LOGIN_LOCKOUT_TIME'],
            'API CONFIGURATION' => ['API_RATE_LIMIT', 'API_RATE_LIMIT_WINDOW', 'API_TIMEOUT'],
            'CACHE CONFIGURATION' => ['CACHE_DRIVER', 'REDIS_HOST', 'REDIS_PORT', 'REDIS_PASSWORD'],
            'BLOCKCHAIN CONFIGURATION' => ['POLYGON_RPC_URL', 'USDT_CONTRACT_ADDRESS', 'PRIVATE_KEY'],
            'EXTERNAL SERVICES' => ['GOOGLE_ANALYTICS_ID', 'RECAPTCHA_SITE_KEY', 'RECAPTCHA_SECRET_KEY'],
            'MONITORING & LOGGING' => ['LOG_LEVEL', 'LOG_PATH', 'MONITORING_ENABLED'],
            'BACKUP CONFIGURATION' => ['BACKUP_ENABLED', 'BACKUP_PATH', 'BACKUP_RETENTION_DAYS'],
            'SOCIAL MEDIA APIs' => ['FACEBOOK_APP_ID', 'FACEBOOK_APP_SECRET', 'TWITTER_API_KEY', 'TWITTER_API_SECRET', 'LINKEDIN_CLIENT_ID', 'LINKEDIN_CLIENT_SECRET']
        ];
        
        foreach ($sections as $sectionName => $keys) {
            $content .= "# $sectionName\n";
            foreach ($keys as $key) {
                if (isset($config[$key])) {
                    $content .= "$key=\"{$config[$key]}\"\n";
                }
            }
            $content .= "\n";
        }
        
        return $content;
    }
    
    private function getEnvironmentTemplate() {
        return "# ============================================================================
# AUREUS ANGEL ALLIANCE - ENVIRONMENT TEMPLATE
# ============================================================================
# Copy this file to .env and fill in your actual values
# ============================================================================

# DATABASE CONFIGURATION
DB_HOST=localhost
DB_NAME=aureus_angels_prod
DB_USER=aureus_user
DB_PASS=your_secure_database_password
DB_PORT=3306

# SECURITY KEYS (Generate secure random keys)
ENCRYPTION_KEY=your_64_character_encryption_key
JWT_SECRET=your_64_character_jwt_secret
SESSION_SECRET=your_32_character_session_secret
API_SECRET=your_32_character_api_secret

# APPLICATION SETTINGS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aureusangels.com
APP_NAME=\"Aureus Angel Alliance\"

# EMAIL CONFIGURATION
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_email_password
SMTP_ENCRYPTION=tls
FROM_EMAIL=noreply@aureusangels.com
FROM_NAME=\"Aureus Angel Alliance\"

# BLOCKCHAIN CONFIGURATION
POLYGON_RPC_URL=https://polygon-rpc.com
USDT_CONTRACT_ADDRESS=0xc2132D05D31c914a87C6611C10748AEb04B58e8F
PRIVATE_KEY=your_private_key_for_transactions

# EXTERNAL SERVICES
GOOGLE_ANALYTICS_ID=your_ga_tracking_id
RECAPTCHA_SITE_KEY=your_recaptcha_site_key
RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key

# SOCIAL MEDIA APIs
FACEBOOK_APP_ID=your_facebook_app_id
FACEBOOK_APP_SECRET=your_facebook_app_secret
TWITTER_API_KEY=your_twitter_api_key
TWITTER_API_SECRET=your_twitter_api_secret
LINKEDIN_CLIENT_ID=your_linkedin_client_id
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret
";
    }
    
    public function validateEnvironment() {
        if (!file_exists($this->envFile)) {
            echo "❌ Environment file not found: {$this->envFile}\n";
            return false;
        }
        
        $env = $this->loadEnvironment();
        $requiredKeys = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
            'ENCRYPTION_KEY', 'JWT_SECRET', 'SESSION_SECRET',
            'APP_ENV', 'APP_URL'
        ];
        
        $missing = [];
        foreach ($requiredKeys as $key) {
            if (empty($env[$key])) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            echo "❌ Missing required environment variables: " . implode(', ', $missing) . "\n";
            return false;
        }
        
        echo "✅ Environment validation passed\n";
        return true;
    }
    
    private function loadEnvironment() {
        $env = [];
        
        if (file_exists($this->envFile)) {
            $lines = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue; // Skip comments
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $env[trim($key)] = trim($value, '"\'');
                }
            }
        }
        
        return $env;
    }
    
    public function generateSecurityReport() {
        echo "\n🔒 SECURITY CONFIGURATION REPORT\n";
        echo "================================\n";
        
        $env = $this->loadEnvironment();
        
        $securityChecks = [
            'HTTPS Enabled' => ($env['HTTPS_ONLY'] ?? 'false') === 'true',
            'Secure Cookies' => ($env['SECURE_COOKIES'] ?? 'false') === 'true',
            'Debug Disabled' => ($env['APP_DEBUG'] ?? 'true') === 'false',
            'Strong Encryption Key' => strlen($env['ENCRYPTION_KEY'] ?? '') >= 32,
            'Session Timeout Set' => !empty($env['SESSION_TIMEOUT']),
            'Rate Limiting Enabled' => !empty($env['API_RATE_LIMIT']),
            'File Upload Limits' => !empty($env['MAX_FILE_SIZE']),
            'Monitoring Enabled' => ($env['MONITORING_ENABLED'] ?? 'false') === 'true'
        ];
        
        foreach ($securityChecks as $check => $passed) {
            $status = $passed ? '✅' : '❌';
            echo "$status $check\n";
        }
        
        echo "\n";
    }
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

if (php_sapi_name() === 'cli') {
    echo "Aureus Angel Alliance - Environment Setup\n";
    echo "========================================\n\n";
    
    $manager = new EnvironmentManager();
    
    // Create template
    $manager->createEnvironmentTemplate();
    
    // Setup environment with secure defaults
    if ($manager->setupEnvironment()) {
        echo "\n✅ Environment setup completed successfully!\n";
        
        // Validate configuration
        $manager->validateEnvironment();
        
        // Generate security report
        $manager->generateSecurityReport();
        
        echo "📝 Next steps:\n";
        echo "1. Review and update .env file with your actual values\n";
        echo "2. Ensure .env file has secure permissions (600)\n";
        echo "3. Never commit .env file to version control\n";
        echo "4. Test database connection with new credentials\n";
    } else {
        echo "\n❌ Environment setup failed\n";
        exit(1);
    }
} else {
    // Web interface
    header('Content-Type: application/json');
    
    try {
        $manager = new EnvironmentManager();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if ($manager->setupEnvironment($config)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Environment setup completed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Environment setup failed'
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Environment manager ready',
                'template_available' => file_exists(__DIR__ . '/.env.template')
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Environment setup error: ' . $e->getMessage()
        ]);
    }
}
?>
