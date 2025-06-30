<?php
/**
 * CORS SECURITY SCANNER
 * Scans codebase for CORS vulnerabilities and insecure configurations
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $scanResults = scanForCORSVulnerabilities();
    
    echo json_encode([
        'success' => true,
        'scan_timestamp' => date('c'),
        'vulnerabilities_found' => count($scanResults['vulnerable_files']),
        'total_files_scanned' => $scanResults['total_files'],
        'results' => $scanResults,
        'recommendations' => generateRecommendations($scanResults)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("CORS security scan error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Scan failed: ' . $e->getMessage()]);
}

/**
 * Scan for CORS vulnerabilities
 */
function scanForCORSVulnerabilities() {
    $apiDir = dirname(__DIR__);
    $vulnerableFiles = [];
    $secureFiles = [];
    $totalFiles = 0;
    
    $vulnerablePatterns = [
        'wildcard_origin' => '/Access-Control-Allow-Origin:\s*\*/i',
        'wildcard_header' => '/header\s*\(\s*[\'"]Access-Control-Allow-Origin:\s*\*[\'"]\s*\)/i',
        'insecure_credentials' => '/Access-Control-Allow-Credentials:\s*true.*Access-Control-Allow-Origin:\s*\*/is',
        'missing_validation' => '/header\s*\(\s*[\'"]Access-Control-Allow-Origin:\s*[\'"].*\$_SERVER\[.*\]/i'
    ];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($apiDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $totalFiles++;
            $filePath = $file->getPathname();
            $relativePath = str_replace($apiDir . DIRECTORY_SEPARATOR, '', $filePath);
            $content = file_get_contents($filePath);
            
            $vulnerabilities = [];
            
            foreach ($vulnerablePatterns as $type => $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $vulnerabilities[] = [
                        'type' => $type,
                        'pattern' => $pattern,
                        'match' => trim($matches[0]),
                        'severity' => getSeverityLevel($type)
                    ];
                }
            }
            
            if (!empty($vulnerabilities)) {
                $vulnerableFiles[] = [
                    'file' => $relativePath,
                    'full_path' => $filePath,
                    'vulnerabilities' => $vulnerabilities,
                    'file_size' => $file->getSize(),
                    'last_modified' => date('Y-m-d H:i:s', $file->getMTime())
                ];
            } else {
                // Check if file uses secure CORS
                if (strpos($content, 'SecureCORS::') !== false || 
                    strpos($content, 'setCorsHeaders()') !== false ||
                    strpos($content, 'handlePreflight()') !== false) {
                    $secureFiles[] = [
                        'file' => $relativePath,
                        'security_level' => 'secure'
                    ];
                }
            }
        }
    }
    
    return [
        'vulnerable_files' => $vulnerableFiles,
        'secure_files' => $secureFiles,
        'total_files' => $totalFiles,
        'scan_summary' => [
            'critical_vulnerabilities' => count(array_filter($vulnerableFiles, function($file) {
                return array_filter($file['vulnerabilities'], function($vuln) {
                    return $vuln['severity'] === 'critical';
                });
            })),
            'high_vulnerabilities' => count(array_filter($vulnerableFiles, function($file) {
                return array_filter($file['vulnerabilities'], function($vuln) {
                    return $vuln['severity'] === 'high';
                });
            })),
            'medium_vulnerabilities' => count(array_filter($vulnerableFiles, function($file) {
                return array_filter($file['vulnerabilities'], function($vuln) {
                    return $vuln['severity'] === 'medium';
                });
            }))
        ]
    ];
}

/**
 * Get severity level for vulnerability type
 */
function getSeverityLevel($type) {
    $severityMap = [
        'wildcard_origin' => 'critical',
        'wildcard_header' => 'critical',
        'insecure_credentials' => 'critical',
        'missing_validation' => 'high'
    ];
    
    return $severityMap[$type] ?? 'medium';
}

/**
 * Generate security recommendations
 */
function generateRecommendations($scanResults) {
    $recommendations = [];
    
    if (!empty($scanResults['vulnerable_files'])) {
        $recommendations[] = [
            'priority' => 'critical',
            'title' => 'Fix Wildcard CORS Permissions',
            'description' => 'Replace all wildcard (*) CORS origins with specific allowed origins',
            'action' => 'Update vulnerable files to use SecureCORS class',
            'affected_files' => count($scanResults['vulnerable_files'])
        ];
        
        $recommendations[] = [
            'priority' => 'high',
            'title' => 'Implement Origin Validation',
            'description' => 'Ensure all CORS implementations validate origins against whitelist',
            'action' => 'Use SecureCORS::validateOrigin() for all origin checks',
            'affected_files' => count($scanResults['vulnerable_files'])
        ];
        
        $recommendations[] = [
            'priority' => 'medium',
            'title' => 'Add CORS Security Monitoring',
            'description' => 'Monitor and log all CORS requests for security analysis',
            'action' => 'Implement comprehensive CORS logging',
            'affected_files' => 'all'
        ];
    }
    
    if (count($scanResults['secure_files']) < $scanResults['total_files'] / 2) {
        $recommendations[] = [
            'priority' => 'medium',
            'title' => 'Standardize CORS Implementation',
            'description' => 'Ensure all API endpoints use the secure CORS implementation',
            'action' => 'Migrate remaining files to use SecureCORS class',
            'affected_files' => $scanResults['total_files'] - count($scanResults['secure_files'])
        ];
    }
    
    return $recommendations;
}
?>
