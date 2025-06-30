<?php
// ============================================================================
// SECURITY HEADERS IMPLEMENTATION FOR AUREUS ANGEL ALLIANCE
// ============================================================================
// This script implements comprehensive security headers for production
// ============================================================================

class SecurityHeadersManager {
    private $headers;
    private $configFile;
    
    public function __construct() {
        $this->configFile = __DIR__ . '/security-config.json';
        $this->initializeHeaders();
    }
    
    private function initializeHeaders() {
        $this->headers = [
            // Content Security Policy
            'Content-Security-Policy' => $this->buildCSP(),
            
            // HTTP Strict Transport Security
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            
            // X-Frame-Options
            'X-Frame-Options' => 'DENY',
            
            // X-Content-Type-Options
            'X-Content-Type-Options' => 'nosniff',
            
            // X-XSS-Protection
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer Policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions Policy
            'Permissions-Policy' => $this->buildPermissionsPolicy(),
            
            // Cross-Origin Embedder Policy
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            
            // Cross-Origin Opener Policy
            'Cross-Origin-Opener-Policy' => 'same-origin',
            
            // Cross-Origin Resource Policy
            'Cross-Origin-Resource-Policy' => 'same-origin',
            
            // Cache Control for sensitive pages
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            
            // Pragma
            'Pragma' => 'no-cache',
            
            // Expires
            'Expires' => '0',
            
            // Server header (hide server information)
            'Server' => 'Aureus-Server',
            
            // X-Powered-By (remove PHP version info)
            'X-Powered-By' => '',
            
            // Feature Policy (deprecated but still supported)
            'Feature-Policy' => $this->buildFeaturePolicy()
        ];
    }
    
    private function buildCSP() {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.gpteng.co https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "media-src 'self' data: blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
            "block-all-mixed-content",
            "connect-src 'self' https://api.aureusangels.com https://polygon-rpc.com wss: ws:",
            "worker-src 'self' blob:",
            "manifest-src 'self'",
            "prefetch-src 'self'"
        ];
        
        return implode('; ', $csp);
    }
    
    private function buildPermissionsPolicy() {
        $policies = [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=()',
            'battery=()',
            'camera=()',
            'cross-origin-isolated=()',
            'display-capture=()',
            'document-domain=()',
            'encrypted-media=()',
            'execution-while-not-rendered=()',
            'execution-while-out-of-viewport=()',
            'fullscreen=(self)',
            'geolocation=()',
            'gyroscope=()',
            'keyboard-map=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'navigation-override=()',
            'payment=(self)',
            'picture-in-picture=()',
            'publickey-credentials-get=(self)',
            'screen-wake-lock=()',
            'sync-xhr=()',
            'usb=()',
            'web-share=(self)',
            'xr-spatial-tracking=()'
        ];
        
        return implode(', ', $policies);
    }
    
    private function buildFeaturePolicy() {
        $policies = [
            "accelerometer 'none'",
            "ambient-light-sensor 'none'",
            "autoplay 'none'",
            "battery 'none'",
            "camera 'none'",
            "display-capture 'none'",
            "document-domain 'none'",
            "encrypted-media 'none'",
            "fullscreen 'self'",
            "geolocation 'none'",
            "gyroscope 'none'",
            "magnetometer 'none'",
            "microphone 'none'",
            "midi 'none'",
            "payment 'self'",
            "picture-in-picture 'none'",
            "speaker 'self'",
            "sync-xhr 'none'",
            "usb 'none'",
            "vr 'none'",
            "wake-lock 'none'"
        ];
        
        return implode('; ', $policies);
    }
    
    public function applyHeaders($pageType = 'default') {
        // Remove PHP version information
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        
        // Apply base security headers
        foreach ($this->headers as $name => $value) {
            if (!empty($value)) {
                header("$name: $value");
            } else {
                header_remove($name);
            }
        }
        
        // Apply page-specific headers
        $this->applyPageSpecificHeaders($pageType);
    }
    
    private function applyPageSpecificHeaders($pageType) {
        switch ($pageType) {
            case 'api':
                header('Content-Type: application/json; charset=utf-8');
                header('X-Content-Type-Options: nosniff');
                header('X-Frame-Options: DENY');
                break;
                
            case 'admin':
                header('Cache-Control: no-store, no-cache, must-revalidate, private');
                header('Pragma: no-cache');
                header('Expires: 0');
                header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
                break;
                
            case 'public':
                header('Cache-Control: public, max-age=3600');
                header('X-Robots-Tag: index, follow');
                break;
                
            case 'static':
                header('Cache-Control: public, max-age=31536000, immutable');
                break;
                
            case 'upload':
                header('X-Content-Type-Options: nosniff');
                header('Content-Disposition: attachment');
                break;
        }
    }
    
    public function generateApacheConfig() {
        $config = "# ============================================================================\n";
        $config .= "# SECURITY HEADERS CONFIGURATION FOR APACHE\n";
        $config .= "# Add this to your .htaccess or Apache virtual host configuration\n";
        $config .= "# ============================================================================\n\n";
        
        $config .= "<IfModule mod_headers.c>\n";
        
        foreach ($this->headers as $name => $value) {
            if (!empty($value)) {
                $config .= "    Header always set \"$name\" \"$value\"\n";
            } else {
                $config .= "    Header always unset \"$name\"\n";
            }
        }
        
        $config .= "\n    # Remove server signature\n";
        $config .= "    Header always unset \"Server\"\n";
        $config .= "    Header always set \"Server\" \"Aureus-Server\"\n";
        
        $config .= "\n    # Security headers for specific file types\n";
        $config .= "    <FilesMatch \"\\.(js|css|png|jpg|jpeg|gif|ico|svg)$\">\n";
        $config .= "        Header set Cache-Control \"public, max-age=31536000, immutable\"\n";
        $config .= "    </FilesMatch>\n";
        
        $config .= "\n    # Admin area security\n";
        $config .= "    <LocationMatch \"/admin\">\n";
        $config .= "        Header always set Cache-Control \"no-store, no-cache, must-revalidate, private\"\n";
        $config .= "        Header always set X-Robots-Tag \"noindex, nofollow, noarchive, nosnippet\"\n";
        $config .= "    </LocationMatch>\n";
        
        $config .= "\n    # API security\n";
        $config .= "    <LocationMatch \"/api\">\n";
        $config .= "        Header always set Content-Type \"application/json; charset=utf-8\"\n";
        $config .= "        Header always set X-Content-Type-Options \"nosniff\"\n";
        $config .= "    </LocationMatch>\n";
        
        $config .= "</IfModule>\n\n";
        
        $config .= "# Additional security configurations\n";
        $config .= "ServerTokens Prod\n";
        $config .= "ServerSignature Off\n\n";
        
        $config .= "# Disable server-status and server-info\n";
        $config .= "<Location \"/server-status\">\n";
        $config .= "    Require all denied\n";
        $config .= "</Location>\n";
        $config .= "<Location \"/server-info\">\n";
        $config .= "    Require all denied\n";
        $config .= "</Location>\n\n";
        
        $config .= "# Hide .env and other sensitive files\n";
        $config .= "<FilesMatch \"^\\.(env|htaccess|htpasswd)\">\n";
        $config .= "    Require all denied\n";
        $config .= "</FilesMatch>\n\n";
        
        $config .= "# Disable directory browsing\n";
        $config .= "Options -Indexes\n\n";
        
        $config .= "# Prevent access to PHP files in uploads directory\n";
        $config .= "<Directory \"/var/www/uploads\">\n";
        $config .= "    <FilesMatch \"\\.php$\">\n";
        $config .= "        Require all denied\n";
        $config .= "    </FilesMatch>\n";
        $config .= "</Directory>\n";
        
        return $config;
    }
    
    public function generateNginxConfig() {
        $config = "# ============================================================================\n";
        $config .= "# SECURITY HEADERS CONFIGURATION FOR NGINX\n";
        $config .= "# Add this to your Nginx server block\n";
        $config .= "# ============================================================================\n\n";
        
        foreach ($this->headers as $name => $value) {
            if (!empty($value)) {
                $config .= "add_header $name \"$value\" always;\n";
            }
        }
        
        $config .= "\n# Remove server signature\n";
        $config .= "server_tokens off;\n";
        $config .= "more_set_headers 'Server: Aureus-Server';\n\n";
        
        $config .= "# Security configurations\n";
        $config .= "location ~ /\\. {\n";
        $config .= "    deny all;\n";
        $config .= "}\n\n";
        
        $config .= "location /admin {\n";
        $config .= "    add_header Cache-Control \"no-store, no-cache, must-revalidate, private\" always;\n";
        $config .= "    add_header X-Robots-Tag \"noindex, nofollow, noarchive, nosnippet\" always;\n";
        $config .= "}\n\n";
        
        $config .= "location /api {\n";
        $config .= "    add_header Content-Type \"application/json; charset=utf-8\" always;\n";
        $config .= "    add_header X-Content-Type-Options \"nosniff\" always;\n";
        $config .= "}\n\n";
        
        $config .= "location ~* \\.(js|css|png|jpg|jpeg|gif|ico|svg)$ {\n";
        $config .= "    add_header Cache-Control \"public, max-age=31536000, immutable\";\n";
        $config .= "    expires 1y;\n";
        $config .= "}\n\n";
        
        $config .= "location /uploads {\n";
        $config .= "    location ~ \\.php$ {\n";
        $config .= "        deny all;\n";
        $config .= "    }\n";
        $config .= "}\n";
        
        return $config;
    }
    
    public function saveConfiguration() {
        $config = [
            'headers' => $this->headers,
            'generated_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ];
        
        if (file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT))) {
            echo "✅ Security configuration saved to: {$this->configFile}\n";
            return true;
        } else {
            echo "❌ Failed to save security configuration\n";
            return false;
        }
    }
    
    public function testHeaders($url = null) {
        if (!$url) {
            $url = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url = 'https://' . $url;
        }
        
        echo "🔍 Testing security headers for: $url\n";
        echo "=====================================\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            echo "❌ Failed to connect to $url\n";
            return false;
        }
        
        echo "HTTP Status: $httpCode\n\n";
        
        $expectedHeaders = array_keys($this->headers);
        $foundHeaders = [];
        
        $headerLines = explode("\n", $response);
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($name, $value) = explode(':', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (in_array($name, $expectedHeaders)) {
                    $foundHeaders[$name] = $value;
                    echo "✅ $name: $value\n";
                }
            }
        }
        
        $missingHeaders = array_diff($expectedHeaders, array_keys($foundHeaders));
        if (!empty($missingHeaders)) {
            echo "\n❌ Missing headers:\n";
            foreach ($missingHeaders as $header) {
                echo "   - $header\n";
            }
        }
        
        return empty($missingHeaders);
    }
    
    public function generateSecurityReport() {
        echo "\n🔒 SECURITY HEADERS REPORT\n";
        echo "=========================\n";
        
        $securityScore = 0;
        $maxScore = count($this->headers);
        
        foreach ($this->headers as $name => $value) {
            if (!empty($value)) {
                $securityScore++;
                echo "✅ $name: Configured\n";
            } else {
                echo "⚠️  $name: Not set\n";
            }
        }
        
        $percentage = round(($securityScore / $maxScore) * 100);
        echo "\nSecurity Score: $securityScore/$maxScore ($percentage%)\n";
        
        if ($percentage >= 90) {
            echo "🟢 Excellent security configuration!\n";
        } elseif ($percentage >= 70) {
            echo "🟡 Good security configuration with room for improvement\n";
        } else {
            echo "🔴 Security configuration needs significant improvement\n";
        }
    }
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

if (php_sapi_name() === 'cli') {
    echo "Aureus Angel Alliance - Security Headers Setup\n";
    echo "==============================================\n\n";
    
    $manager = new SecurityHeadersManager();
    
    // Save configuration
    $manager->saveConfiguration();
    
    // Generate Apache configuration
    $apacheConfig = $manager->generateApacheConfig();
    file_put_contents(__DIR__ . '/apache-security.conf', $apacheConfig);
    echo "✅ Apache configuration saved to: apache-security.conf\n";
    
    // Generate Nginx configuration
    $nginxConfig = $manager->generateNginxConfig();
    file_put_contents(__DIR__ . '/nginx-security.conf', $nginxConfig);
    echo "✅ Nginx configuration saved to: nginx-security.conf\n";
    
    // Generate security report
    $manager->generateSecurityReport();
    
    echo "\n📝 Next steps:\n";
    echo "1. Copy the appropriate configuration to your web server\n";
    echo "2. Restart your web server\n";
    echo "3. Test headers using the test function\n";
    echo "4. Monitor security headers regularly\n";
    
} else {
    // Apply headers for web requests
    $manager = new SecurityHeadersManager();
    
    // Determine page type based on URL
    $pageType = 'default';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (strpos($requestUri, '/api/') !== false) {
        $pageType = 'api';
    } elseif (strpos($requestUri, '/admin') !== false) {
        $pageType = 'admin';
    } elseif (strpos($requestUri, '/uploads/') !== false) {
        $pageType = 'upload';
    } elseif (preg_match('/\\.(js|css|png|jpg|jpeg|gif|ico|svg)$/', $requestUri)) {
        $pageType = 'static';
    }
    
    $manager->applyHeaders($pageType);
}
?>
