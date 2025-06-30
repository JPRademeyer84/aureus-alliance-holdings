<?php
/**
 * VALIDATION MIGRATION UTILITY
 * Helps migrate existing endpoints to use centralized validation
 */

require_once '../config/input-validator.php';

class ValidationMigration {
    
    /**
     * Analyze endpoint for validation patterns
     */
    public static function analyzeEndpoint($filePath) {
        if (!file_exists($filePath)) {
            return ['error' => 'File not found'];
        }
        
        $content = file_get_contents($filePath);
        $analysis = [
            'file' => $filePath,
            'validation_patterns' => [],
            'security_issues' => [],
            'recommendations' => []
        ];
        
        // Check for existing validation patterns
        $patterns = [
            'isset_checks' => '/isset\(\$_[A-Z]+\[/',
            'empty_checks' => '/empty\(\$_[A-Z]+\[/',
            'filter_var' => '/filter_var\(/',
            'preg_match' => '/preg_match\(/',
            'htmlspecialchars' => '/htmlspecialchars\(/',
            'strip_tags' => '/strip_tags\(/',
            'addslashes' => '/addslashes\(/',
            'mysql_real_escape_string' => '/mysql_real_escape_string\(/',
            'prepared_statements' => '/prepare\(/',
            'file_upload_checks' => '/\$_FILES\[/'
        ];
        
        foreach ($patterns as $name => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $analysis['validation_patterns'][$name] = count($matches[0]);
            }
        }
        
        // Check for security issues
        $securityIssues = [
            'direct_sql' => '/\$[a-zA-Z_]+\s*=\s*["\']SELECT|INSERT|UPDATE|DELETE/',
            'eval_usage' => '/eval\s*\(/',
            'exec_usage' => '/exec\s*\(/',
            'system_usage' => '/system\s*\(/',
            'shell_exec_usage' => '/shell_exec\s*\(/',
            'unfiltered_input' => '/\$_[A-Z]+\[[^\]]+\]\s*[^;]*;/',
            'echo_unescaped' => '/echo\s+\$_[A-Z]+\[/',
            'print_unescaped' => '/print\s+\$_[A-Z]+\[/'
        ];
        
        foreach ($securityIssues as $issue => $pattern) {
            if (preg_match($pattern, $content)) {
                $analysis['security_issues'][] = $issue;
            }
        }
        
        // Generate recommendations
        $analysis['recommendations'] = self::generateRecommendations($analysis);
        
        return $analysis;
    }
    
    /**
     * Generate migration recommendations
     */
    private static function generateRecommendations($analysis) {
        $recommendations = [];
        
        // Check if centralized validation is already used
        if (!isset($analysis['validation_patterns']['input-validator'])) {
            $recommendations[] = 'Add centralized input validation using InputValidator class';
        }
        
        // Check for manual validation patterns
        if (isset($analysis['validation_patterns']['isset_checks']) && 
            $analysis['validation_patterns']['isset_checks'] > 3) {
            $recommendations[] = 'Replace manual isset() checks with validateApiRequest()';
        }
        
        if (isset($analysis['validation_patterns']['empty_checks']) && 
            $analysis['validation_patterns']['empty_checks'] > 2) {
            $recommendations[] = 'Use required field validation instead of manual empty() checks';
        }
        
        // Check for security issues
        if (in_array('direct_sql', $analysis['security_issues'])) {
            $recommendations[] = 'CRITICAL: Replace direct SQL queries with prepared statements';
        }
        
        if (in_array('eval_usage', $analysis['security_issues'])) {
            $recommendations[] = 'CRITICAL: Remove eval() usage - major security risk';
        }
        
        if (in_array('unfiltered_input', $analysis['security_issues'])) {
            $recommendations[] = 'HIGH: Add input validation and sanitization';
        }
        
        if (in_array('echo_unescaped', $analysis['security_issues']) || 
            in_array('print_unescaped', $analysis['security_issues'])) {
            $recommendations[] = 'MEDIUM: Use sanitizeOutput() for displaying user input';
        }
        
        // Check for file upload handling
        if (isset($analysis['validation_patterns']['file_upload_checks'])) {
            $recommendations[] = 'Use validateApiFiles() for secure file upload handling';
        }
        
        // Check for outdated functions
        if (isset($analysis['validation_patterns']['mysql_real_escape_string'])) {
            $recommendations[] = 'Replace mysql_real_escape_string() with prepared statements';
        }
        
        if (isset($analysis['validation_patterns']['addslashes'])) {
            $recommendations[] = 'Replace addslashes() with proper input validation';
        }
        
        return $recommendations;
    }
    
    /**
     * Generate migration code for common patterns
     */
    public static function generateMigrationCode($endpointType) {
        $templates = [
            'api_endpoint' => self::getApiEndpointTemplate(),
            'file_upload' => self::getFileUploadTemplate(),
            'admin_endpoint' => self::getAdminEndpointTemplate(),
            'user_endpoint' => self::getUserEndpointTemplate()
        ];
        
        return $templates[$endpointType] ?? null;
    }
    
    private static function getApiEndpointTemplate() {
        return '<?php
// Add to top of file
require_once \'../config/input-validator.php\';

// Replace manual validation with:
try {
    $validatedData = validateApiRequest([
        \'field1\' => [
            \'type\' => \'string\',
            \'required\' => true,
            \'min_length\' => 3,
            \'max_length\' => 50,
            \'sanitize\' => [\'trim\', \'strip_tags\']
        ],
        \'field2\' => [
            \'type\' => \'email\',
            \'required\' => true
        ],
        \'field3\' => [
            \'type\' => \'float\',
            \'required\' => false,
            \'min_value\' => 0,
            \'max_value\' => 999999
        ]
    ], \'endpoint_context\');
    
    // Use validated data
    $field1 = $validatedData[\'field1\'];
    $field2 = $validatedData[\'field2\'];
    $field3 = $validatedData[\'field3\'] ?? null;
    
} catch (ValidationException $e) {
    // Validation errors are automatically handled
    // This catch block is optional
}
?>';
    }
    
    private static function getFileUploadTemplate() {
        return '<?php
// Add to top of file
require_once \'../config/input-validator.php\';

// Replace manual file validation with:
try {
    // Validate POST data
    $validatedData = validateApiRequest([
        \'document_type\' => [
            \'type\' => \'string\',
            \'required\' => true,
            \'custom\' => function($value) {
                $allowed = [\'passport\', \'drivers_license\', \'national_id\'];
                return in_array($value, $allowed) ? true : \'Invalid document type\';
            }
        ]
    ]);
    
    // Validate file uploads
    $validatedFiles = validateApiFiles([
        \'document\' => [
            \'required\' => true,
            \'allowed_types\' => [\'image/jpeg\', \'image/png\', \'application/pdf\'],
            \'max_size\' => 10485760 // 10MB
        ]
    ]);
    
    $documentType = $validatedData[\'document_type\'];
    $file = $validatedFiles[\'document\'];
    
} catch (ValidationException $e) {
    // Validation errors are automatically handled
}
?>';
    }
    
    private static function getAdminEndpointTemplate() {
        return '<?php
// Add to top of file
require_once \'../config/input-validator.php\';

// For admin login
$validatedData = validateApiRequest(ValidationRules::adminLogin(), \'admin_auth\');

// For other admin operations
$validatedData = validateApiRequest([
    \'action\' => [
        \'type\' => \'string\',
        \'required\' => true,
        \'custom\' => function($value) {
            $allowed = [\'create\', \'update\', \'delete\', \'view\'];
            return in_array($value, $allowed) ? true : \'Invalid action\';
        }
    ],
    \'id\' => [
        \'type\' => \'string\',
        \'required\' => false,
        \'pattern\' => \'uuid\'
    ]
], \'admin_operation\');
?>';
    }
    
    private static function getUserEndpointTemplate() {
        return '<?php
// Add to top of file
require_once \'../config/input-validator.php\';

// For user registration
$validatedData = validateApiRequest(ValidationRules::userRegistration(), \'user_registration\');

// For investment
$validatedData = validateApiRequest(ValidationRules::investment(), \'investment_creation\');

// For custom validation
$validatedData = validateApiRequest([
    \'message\' => [
        \'type\' => \'string\',
        \'required\' => true,
        \'min_length\' => 1,
        \'max_length\' => 1000,
        \'sanitize\' => [\'trim\', \'strip_tags\']
    ]
], \'user_message\');
?>';
    }
    
    /**
     * Scan directory for endpoints needing migration
     */
    public static function scanDirectory($directory) {
        $results = [];
        
        if (!is_dir($directory)) {
            return ['error' => 'Directory not found'];
        }
        
        $files = glob($directory . '/*.php');
        
        foreach ($files as $file) {
            $analysis = self::analyzeEndpoint($file);
            
            // Calculate risk score
            $riskScore = 0;
            $riskScore += count($analysis['security_issues']) * 20;
            $riskScore += (isset($analysis['validation_patterns']['isset_checks']) ? 
                          min($analysis['validation_patterns']['isset_checks'] * 2, 20) : 0);
            
            $analysis['risk_score'] = min($riskScore, 100);
            $analysis['risk_level'] = self::getRiskLevel($riskScore);
            
            $results[] = $analysis;
        }
        
        // Sort by risk score (highest first)
        usort($results, function($a, $b) {
            return $b['risk_score'] - $a['risk_score'];
        });
        
        return $results;
    }
    
    private static function getRiskLevel($score) {
        if ($score >= 80) return 'CRITICAL';
        if ($score >= 60) return 'HIGH';
        if ($score >= 40) return 'MEDIUM';
        if ($score >= 20) return 'LOW';
        return 'MINIMAL';
    }
    
    /**
     * Generate migration report
     */
    public static function generateReport($directory) {
        $scan = self::scanDirectory($directory);
        
        if (isset($scan['error'])) {
            return $scan;
        }
        
        $report = [
            'directory' => $directory,
            'total_files' => count($scan),
            'risk_summary' => [
                'CRITICAL' => 0,
                'HIGH' => 0,
                'MEDIUM' => 0,
                'LOW' => 0,
                'MINIMAL' => 0
            ],
            'common_issues' => [],
            'priority_files' => [],
            'recommendations' => []
        ];
        
        foreach ($scan as $file) {
            $report['risk_summary'][$file['risk_level']]++;
            
            if ($file['risk_score'] >= 60) {
                $report['priority_files'][] = [
                    'file' => basename($file['file']),
                    'risk_score' => $file['risk_score'],
                    'risk_level' => $file['risk_level'],
                    'issues' => $file['security_issues']
                ];
            }
        }
        
        // Generate overall recommendations
        $criticalCount = $report['risk_summary']['CRITICAL'];
        $highCount = $report['risk_summary']['HIGH'];
        
        if ($criticalCount > 0) {
            $report['recommendations'][] = "URGENT: $criticalCount files have critical security issues requiring immediate attention";
        }
        
        if ($highCount > 0) {
            $report['recommendations'][] = "HIGH PRIORITY: $highCount files have high-risk validation issues";
        }
        
        $report['recommendations'][] = 'Implement centralized validation across all endpoints';
        $report['recommendations'][] = 'Replace manual validation with ValidationRules class';
        $report['recommendations'][] = 'Add input sanitization using sanitizeOutput()';
        
        return $report;
    }
}

// CLI usage
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $action = $argv[1];
    
    switch ($action) {
        case 'scan':
            $directory = $argv[2] ?? '../';
            $report = ValidationMigration::generateReport($directory);
            echo json_encode($report, JSON_PRETTY_PRINT);
            break;
            
        case 'analyze':
            $file = $argv[2] ?? '';
            if ($file) {
                $analysis = ValidationMigration::analyzeEndpoint($file);
                echo json_encode($analysis, JSON_PRETTY_PRINT);
            } else {
                echo "Usage: php validation-migration.php analyze <file_path>\n";
            }
            break;
            
        case 'template':
            $type = $argv[2] ?? 'api_endpoint';
            $template = ValidationMigration::generateMigrationCode($type);
            echo $template;
            break;
            
        default:
            echo "Usage: php validation-migration.php [scan|analyze|template] [args]\n";
            echo "  scan <directory>     - Scan directory for validation issues\n";
            echo "  analyze <file>       - Analyze specific file\n";
            echo "  template <type>      - Generate migration template\n";
    }
}
?>
