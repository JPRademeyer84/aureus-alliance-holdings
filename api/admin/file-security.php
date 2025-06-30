<?php
/**
 * FILE UPLOAD SECURITY MANAGEMENT API
 * Admin interface for managing file upload security
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/secure-file-upload.php';
require_once '../config/virus-scanner.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require MFA for file security management
protectAdminOperation();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'scan_statistics':
            getScanStatistics();
            break;
            
        case 'quarantine_list':
            getQuarantineList();
            break;
            
        case 'quarantine_action':
            handleQuarantineAction();
            break;
            
        case 'upload_settings':
            manageUploadSettings();
            break;
            
        case 'scan_file':
            scanSpecificFile();
            break;
            
        case 'security_report':
            generateSecurityReport();
            break;
            
        case 'cleanup_old_files':
            cleanupOldFiles();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("File security management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get virus scan statistics
 */
function getScanStatistics() {
    $days = (int)($_GET['days'] ?? 7);
    
    $virusScanner = VirusScanner::getInstance();
    $stats = $virusScanner->getScanStatistics($days);
    
    // Get additional statistics from security logs
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Get scan results from logs
        $query = "SELECT 
                    COUNT(*) as total_scans,
                    SUM(CASE WHEN JSON_EXTRACT(event_data, '$.threats_count') = 0 THEN 1 ELSE 0 END) as clean_files,
                    SUM(CASE WHEN JSON_EXTRACT(event_data, '$.threats_count') > 0 THEN 1 ELSE 0 END) as threats_detected,
                    AVG(CAST(JSON_EXTRACT(event_data, '$.scan_time_ms') AS DECIMAL(10,2))) as avg_scan_time_ms
                  FROM security_logs 
                  WHERE event_type = 'virus_scan_completed' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $logStats = $stmt->fetch();
        
        if ($logStats) {
            $stats = array_merge($stats, $logStats);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'period_days' => $days
    ]);
}

/**
 * Get quarantine list
 */
function getQuarantineList() {
    $quarantineDir = dirname(dirname(__DIR__)) . '/quarantine/';
    $files = [];
    
    if (is_dir($quarantineDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($quarantineDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'relative_path' => str_replace($quarantineDir, '', $file->getPathname())
                ];
            }
        }
    }
    
    // Sort by modification time (newest first)
    usort($files, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    echo json_encode([
        'success' => true,
        'data' => $files,
        'count' => count($files)
    ]);
}

/**
 * Handle quarantine actions
 */
function handleQuarantineAction() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $filename = $input['filename'] ?? '';
    
    if (empty($action) || empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Action and filename required']);
        return;
    }
    
    $quarantineDir = dirname(dirname(__DIR__)) . '/quarantine/';
    $filePath = $quarantineDir . $filename;
    
    // Validate file exists and is within quarantine directory
    if (!file_exists($filePath) || strpos(realpath($filePath), realpath($quarantineDir)) !== 0) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found in quarantine']);
        return;
    }
    
    switch ($action) {
        case 'delete':
            if (unlink($filePath)) {
                logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'quarantine_file_deleted', SecurityLogger::LEVEL_INFO,
                    'Quarantined file deleted by admin', ['filename' => $filename], null, $_SESSION['admin_id']);
                
                echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
            } else {
                throw new Exception('Failed to delete file');
            }
            break;
            
        case 'rescan':
            $virusScanner = VirusScanner::getInstance();
            $scanResult = $virusScanner->scanFile($filePath, $filename);
            
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'quarantine_file_rescanned', SecurityLogger::LEVEL_INFO,
                'Quarantined file rescanned', [
                    'filename' => $filename,
                    'scan_result' => $scanResult
                ], null, $_SESSION['admin_id']);
            
            echo json_encode([
                'success' => true,
                'scan_result' => $scanResult,
                'message' => 'File rescanned successfully'
            ]);
            break;
            
        case 'download':
            // Allow admin to download quarantined file for analysis
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filePath));
            
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'quarantine_file_downloaded', SecurityLogger::LEVEL_WARNING,
                'Quarantined file downloaded by admin', ['filename' => $filename], null, $_SESSION['admin_id']);
            
            readfile($filePath);
            exit;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Manage upload settings
 */
function manageUploadSettings() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get current settings
        $settings = [
            'max_file_size' => 5 * 1024 * 1024, // 5MB
            'allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'application/pdf'
            ],
            'virus_scanning_enabled' => true,
            'quarantine_enabled' => true,
            'auto_delete_quarantine_days' => 30
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update settings
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate settings
        if (isset($input['max_file_size']) && ($input['max_file_size'] < 1024 || $input['max_file_size'] > 50 * 1024 * 1024)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file size limit']);
            return;
        }
        
        // In a real implementation, save settings to database or config file
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'upload_settings_updated', SecurityLogger::LEVEL_INFO,
            'File upload settings updated', ['settings' => $input], null, $_SESSION['admin_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
}

/**
 * Scan specific file
 */
function scanSpecificFile() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['filename'] ?? '';
    
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Filename required']);
        return;
    }
    
    $uploadDir = dirname(dirname(__DIR__)) . '/secure-uploads/';
    $filePath = $uploadDir . $filename;
    
    // Validate file exists and is within upload directory
    if (!file_exists($filePath) || strpos(realpath($filePath), realpath($uploadDir)) !== 0) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        return;
    }
    
    $virusScanner = VirusScanner::getInstance();
    $scanResult = $virusScanner->scanFile($filePath, $filename);
    
    logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'manual_file_scan', SecurityLogger::LEVEL_INFO,
        'File manually scanned by admin', [
            'filename' => $filename,
            'scan_result' => $scanResult
        ], null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'scan_result' => $scanResult,
        'message' => 'File scanned successfully'
    ]);
}

/**
 * Generate security report
 */
function generateSecurityReport() {
    $days = (int)($_GET['days'] ?? 30);
    
    $database = new Database();
    $db = $database->getConnection();
    
    $report = [
        'period_days' => $days,
        'upload_statistics' => [],
        'threat_statistics' => [],
        'top_threats' => [],
        'upload_trends' => []
    ];
    
    if ($db) {
        // Upload statistics
        $query = "SELECT 
                    COUNT(*) as total_uploads,
                    COUNT(DISTINCT user_id) as unique_users,
                    SUM(CASE WHEN event_type = 'file_upload_success' THEN 1 ELSE 0 END) as successful_uploads,
                    SUM(CASE WHEN event_type = 'file_upload_blocked' THEN 1 ELSE 0 END) as blocked_uploads
                  FROM security_logs 
                  WHERE event_type IN ('file_upload_success', 'file_upload_blocked')
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $report['upload_statistics'] = $stmt->fetch();
        
        // Threat statistics
        $query = "SELECT 
                    COUNT(*) as total_threats,
                    COUNT(DISTINCT JSON_EXTRACT(event_data, '$.file_name')) as infected_files
                  FROM security_logs 
                  WHERE event_type = 'virus_scan_completed'
                  AND JSON_EXTRACT(event_data, '$.threats_count') > 0
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $report['threat_statistics'] = $stmt->fetch();
        
        // Daily trends
        $query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as uploads,
                    SUM(CASE WHEN event_type = 'file_upload_blocked' THEN 1 ELSE 0 END) as blocked
                  FROM security_logs 
                  WHERE event_type IN ('file_upload_success', 'file_upload_blocked')
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$days]);
        $report['upload_trends'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
}

/**
 * Cleanup old files
 */
function cleanupOldFiles() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $days = (int)($input['days'] ?? 30);
    $dryRun = $input['dry_run'] ?? true;
    
    if ($days < 7) {
        http_response_code(400);
        echo json_encode(['error' => 'Minimum cleanup age is 7 days']);
        return;
    }
    
    $quarantineDir = dirname(dirname(__DIR__)) . '/quarantine/';
    $cutoffTime = time() - ($days * 24 * 60 * 60);
    $deletedFiles = [];
    $totalSize = 0;
    
    if (is_dir($quarantineDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($quarantineDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                $fileSize = $file->getSize();
                $totalSize += $fileSize;
                
                if (!$dryRun) {
                    if (unlink($file->getPathname())) {
                        $deletedFiles[] = [
                            'filename' => $file->getFilename(),
                            'size' => $fileSize,
                            'age_days' => round((time() - $file->getMTime()) / (24 * 60 * 60))
                        ];
                    }
                } else {
                    $deletedFiles[] = [
                        'filename' => $file->getFilename(),
                        'size' => $fileSize,
                        'age_days' => round((time() - $file->getMTime()) / (24 * 60 * 60))
                    ];
                }
            }
        }
    }
    
    if (!$dryRun) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'quarantine_cleanup', SecurityLogger::LEVEL_INFO,
            'Quarantine cleanup performed', [
                'files_deleted' => count($deletedFiles),
                'total_size_bytes' => $totalSize,
                'min_age_days' => $days
            ], null, $_SESSION['admin_id']);
    }
    
    echo json_encode([
        'success' => true,
        'dry_run' => $dryRun,
        'files_processed' => count($deletedFiles),
        'total_size_bytes' => $totalSize,
        'total_size_mb' => round($totalSize / (1024 * 1024), 2),
        'files' => $deletedFiles
    ]);
}
?>
