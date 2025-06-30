<?php
// Environment configuration
class Environment {
    public static function getConfig() {
        // Detect if we're in production or development
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isProduction = !in_array($httpHost, ['localhost', '127.0.0.1', 'localhost:8080']);
        
        if ($isProduction) {
            // Production configuration
            return [
                'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
                'db_name' => $_ENV['DB_NAME'] ?? 'your_production_db',
                'db_user' => $_ENV['DB_USER'] ?? 'your_db_user',
                'db_pass' => $_ENV['DB_PASS'] ?? 'your_db_password',
                'api_url' => 'https://yourdomain.com/api',
                'frontend_url' => 'https://yourdomain.com',
                'debug' => false
            ];
        } else {
            // Development configuration - use environment variables when available
            return [
                'db_host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
                'db_name' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'aureus_angels',
                'db_user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
                'db_pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '',
                'api_url' => $_ENV['API_URL'] ?? getenv('API_URL') ?: 'http://localhost/aureus-angel-alliance/api',
                'frontend_url' => $_ENV['FRONTEND_URL'] ?? getenv('FRONTEND_URL') ?: 'http://localhost:8080',
                'debug' => filter_var($_ENV['DEBUG'] ?? getenv('DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN)
            ];
        }
    }
    
    public static function isProduction() {
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return !in_array($httpHost, ['localhost', '127.0.0.1', 'localhost:8080', 'localhost:5173']);
    }

    /**
     * Get current environment name
     */
    public static function getEnvironment() {
        return self::isProduction() ? 'production' : 'development';
    }
    
    public static function getApiUrl() {
        $config = self::getConfig();
        return $config['api_url'];
    }
    
    public static function getFrontendUrl() {
        $config = self::getConfig();
        return $config['frontend_url'];
    }
}
?>
