<?php
/**
 * CORS SECURITY FIXER
 * Automatically fixes CORS vulnerabilities in the codebase
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and require fresh MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require fresh MFA for this critical operation
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $dryRun = $input['dry_run'] ?? true;
    $targetFiles = $input['target_files'] ?? [];
    
    switch ($action) {
        case 'fix_all':
            $results = fixAllCORSVulnerabilities($dryRun);
            break;
            
        case 'fix_specific':
            $results = fixSpecificFiles($targetFiles, $dryRun);
            break;
            
        case 'backup_files':
            $results = backupVulnerableFiles();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
    // Log the security fix operation
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'cors_security_fix', SecurityLogger::LEVEL_CRITICAL,
        'CORS security fix operation performed', [
            'action' => $action,
            'dry_run' => $dryRun,
            'files_processed' => count($results['processed_files'] ?? []),
            'admin_id' => $_SESSION['admin_id']
        ], null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'dry_run' => $dryRun,
        'timestamp' => date('c'),
        'results' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("CORS security fix error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fix operation failed: ' . $e->getMessage()]);
}

/**
 * Fix all CORS vulnerabilities
 */
function fixAllCORSVulnerabilities($dryRun = true) {
    $apiDir = dirname(__DIR__);
    $processedFiles = [];
    $errors = [];
    
    // Known vulnerable files that need fixing
    $vulnerableFiles = [
        'translations/verify-database-translation.php',
        'admin/create-download-tracking-table.php',
        'translations/add-cta-keys.php',
        'translations/create-complete-dashboard-keys.php',
        'users/add-profile-columns.php',
        'translations/regenerate-all-translations.php',
        'translations/translate-homepage-keys.php',
        'translations/add-step4-keys.php',
        'translations/add-step5-keys.php'
    ];
    
    foreach ($vulnerableFiles as $file) {
        $filePath = $apiDir . '/' . $file;
        
        if (file_exists($filePath)) {
            try {
                $result = fixFileCorsSecurity($filePath, $dryRun);
                $processedFiles[] = [
                    'file' => $file,
                    'status' => 'success',
                    'changes' => $result['changes'],
                    'backup_created' => $result['backup_created'] ?? false
                ];
            } catch (Exception $e) {
                $errors[] = [
                    'file' => $file,
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    return [
        'processed_files' => $processedFiles,
        'errors' => $errors,
        'total_processed' => count($processedFiles),
        'total_errors' => count($errors)
    ];
}

/**
 * Fix specific files
 */
function fixSpecificFiles($targetFiles, $dryRun = true) {
    $apiDir = dirname(__DIR__);
    $processedFiles = [];
    $errors = [];
    
    foreach ($targetFiles as $file) {
        $filePath = $apiDir . '/' . $file;
        
        if (file_exists($filePath)) {
            try {
                $result = fixFileCorsSecurity($filePath, $dryRun);
                $processedFiles[] = [
                    'file' => $file,
                    'status' => 'success',
                    'changes' => $result['changes'],
                    'backup_created' => $result['backup_created'] ?? false
                ];
            } catch (Exception $e) {
                $errors[] = [
                    'file' => $file,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $errors[] = [
                'file' => $file,
                'error' => 'File not found'
            ];
        }
    }
    
    return [
        'processed_files' => $processedFiles,
        'errors' => $errors,
        'total_processed' => count($processedFiles),
        'total_errors' => count($errors)
    ];
}

/**
 * Fix CORS security in a specific file
 */
function fixFileCorsSecurity($filePath, $dryRun = true) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $changes = [];
    
    // Pattern 1: Replace wildcard CORS headers
    $wildcardPattern = '/header\s*\(\s*[\'"]Access-Control-Allow-Origin:\s*\*[\'"]\s*\);?/i';
    if (preg_match($wildcardPattern, $content)) {
        $content = preg_replace($wildcardPattern, '', $content);
        $changes[] = 'Removed wildcard Access-Control-Allow-Origin header';
    }
    
    // Pattern 2: Replace direct header calls with secure CORS
    $headerPatterns = [
        '/header\s*\(\s*[\'"]Access-Control-Allow-Methods:.*?[\'"]\s*\);?/i',
        '/header\s*\(\s*[\'"]Access-Control-Allow-Headers:.*?[\'"]\s*\);?/i',
        '/header\s*\(\s*[\'"]Access-Control-Allow-Credentials:.*?[\'"]\s*\);?/i'
    ];
    
    foreach ($headerPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '', $content);
            $changes[] = 'Removed insecure CORS header';
        }
    }
    
    // Add secure CORS implementation at the top
    if (!empty($changes)) {
        $secureHeaders = "require_once '../config/cors.php';\n\n// Handle CORS and preflight requests\nhandlePreflight();\nsetCorsHeaders();\n\n";
        
        // Find the position after the opening PHP tag
        $phpOpenPos = strpos($content, '<?php');
        if ($phpOpenPos !== false) {
            $insertPos = $phpOpenPos + 5; // After "<?php"
            
            // Skip any existing comments or whitespace
            while ($insertPos < strlen($content) && 
                   ($content[$insertPos] === ' ' || $content[$insertPos] === "\n" || $content[$insertPos] === "\r" || 
                    $content[$insertPos] === "\t" || substr($content, $insertPos, 2) === '//')) {
                if (substr($content, $insertPos, 2) === '//') {
                    // Skip to end of line
                    while ($insertPos < strlen($content) && $content[$insertPos] !== "\n") {
                        $insertPos++;
                    }
                }
                $insertPos++;
            }
            
            $content = substr($content, 0, $insertPos) . "\n" . $secureHeaders . substr($content, $insertPos);
            $changes[] = 'Added secure CORS implementation';
        }
    }
    
    // Create backup if not dry run
    $backupCreated = false;
    if (!$dryRun && $content !== $originalContent) {
        $backupPath = $filePath . '.cors-backup-' . date('Y-m-d-H-i-s');
        if (file_put_contents($backupPath, $originalContent)) {
            $backupCreated = true;
        }
        
        // Write the fixed content
        file_put_contents($filePath, $content);
    }
    
    return [
        'changes' => $changes,
        'backup_created' => $backupCreated,
        'content_changed' => $content !== $originalContent
    ];
}

/**
 * Backup vulnerable files
 */
function backupVulnerableFiles() {
    $apiDir = dirname(__DIR__);
    $backupDir = $apiDir . '/security-backups/cors-' . date('Y-m-d-H-i-s');
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $vulnerableFiles = [
        'translations/verify-database-translation.php',
        'admin/create-download-tracking-table.php',
        'translations/add-cta-keys.php',
        'translations/create-complete-dashboard-keys.php',
        'users/add-profile-columns.php',
        'translations/regenerate-all-translations.php',
        'translations/translate-homepage-keys.php',
        'translations/add-step4-keys.php',
        'translations/add-step5-keys.php'
    ];
    
    $backedUpFiles = [];
    $errors = [];
    
    foreach ($vulnerableFiles as $file) {
        $sourcePath = $apiDir . '/' . $file;
        $backupPath = $backupDir . '/' . str_replace('/', '_', $file);
        
        if (file_exists($sourcePath)) {
            if (copy($sourcePath, $backupPath)) {
                $backedUpFiles[] = [
                    'source' => $file,
                    'backup' => $backupPath,
                    'size' => filesize($sourcePath)
                ];
            } else {
                $errors[] = [
                    'file' => $file,
                    'error' => 'Failed to create backup'
                ];
            }
        }
    }
    
    return [
        'backup_directory' => $backupDir,
        'backed_up_files' => $backedUpFiles,
        'errors' => $errors,
        'total_backed_up' => count($backedUpFiles),
        'total_errors' => count($errors)
    ];
}
?>
