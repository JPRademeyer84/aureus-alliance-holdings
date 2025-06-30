<?php
// ============================================================================
// AUTOMATED BACKUP SYSTEM FOR AUREUS ANGEL ALLIANCE
// ============================================================================
// This script manages automated database and file backups
// ============================================================================

require_once '../api/config/database.php';

class BackupManager {
    private $backupDir;
    private $dbBackupDir;
    private $fileBackupDir;
    private $logFile;
    private $config;
    
    public function __construct() {
        $this->config = $this->loadConfig();
        $this->backupDir = $this->config['backup_path'] ?? '/var/backups/aureus';
        $this->dbBackupDir = $this->backupDir . '/database';
        $this->fileBackupDir = $this->backupDir . '/files';
        $this->logFile = $this->backupDir . '/backup.log';
        
        $this->createDirectories();
    }
    
    private function loadConfig() {
        $envFile = dirname(__DIR__) . '/.env';
        $config = [];
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $config[strtolower(trim($key))] = trim($value, '"\'');
                }
            }
        }
        
        return $config;
    }
    
    private function createDirectories() {
        $dirs = [$this->backupDir, $this->dbBackupDir, $this->fileBackupDir];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->log("Created backup directory: $dir");
            }
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }
    
    public function createDatabaseBackup($type = 'full') {
        $this->log("Starting database backup ($type)...");
        
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "aureus_db_{$type}_{$timestamp}.sql";
            $filepath = $this->dbBackupDir . '/' . $filename;
            
            $dbHost = $this->config['db_host'] ?? 'localhost';
            $dbName = $this->config['db_name'] ?? 'aureus_angels_prod';
            $dbUser = $this->config['db_user'] ?? 'aureus_user';
            $dbPass = $this->config['db_pass'] ?? '';
            
            // Build mysqldump command
            $command = "mysqldump";
            $command .= " --host=$dbHost";
            $command .= " --user=$dbUser";
            $command .= " --password='$dbPass'";
            $command .= " --single-transaction";
            $command .= " --routines";
            $command .= " --triggers";
            $command .= " --events";
            
            if ($type === 'structure') {
                $command .= " --no-data";
            } elseif ($type === 'data') {
                $command .= " --no-create-info";
            }
            
            $command .= " $dbName > $filepath";
            
            // Execute backup
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filepath)) {
                $size = $this->formatBytes(filesize($filepath));
                $this->log("Database backup completed: $filename ($size)");
                
                // Compress backup
                $this->compressFile($filepath);
                
                return $filepath;
            } else {
                throw new Exception("Database backup failed with return code: $returnCode");
            }
            
        } catch (Exception $e) {
            $this->log("ERROR: Database backup failed - " . $e->getMessage());
            return false;
        }
    }
    
    public function createFileBackup($directories = null) {
        $this->log("Starting file backup...");
        
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "aureus_files_{$timestamp}.tar.gz";
            $filepath = $this->fileBackupDir . '/' . $filename;
            
            if ($directories === null) {
                $directories = [
                    '/var/www/uploads',
                    '/var/www/html/assets',
                    dirname(__DIR__) . '/src',
                    dirname(__DIR__) . '/api',
                    dirname(__DIR__) . '/deployment'
                ];
            }
            
            // Filter existing directories
            $existingDirs = array_filter($directories, 'is_dir');
            
            if (empty($existingDirs)) {
                throw new Exception("No valid directories found for backup");
            }
            
            // Create tar command
            $command = "tar -czf $filepath";
            foreach ($existingDirs as $dir) {
                $command .= " '$dir'";
            }
            
            // Execute backup
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filepath)) {
                $size = $this->formatBytes(filesize($filepath));
                $this->log("File backup completed: $filename ($size)");
                return $filepath;
            } else {
                throw new Exception("File backup failed with return code: $returnCode");
            }
            
        } catch (Exception $e) {
            $this->log("ERROR: File backup failed - " . $e->getMessage());
            return false;
        }
    }
    
    public function createFullBackup() {
        $this->log("Starting full system backup...");
        
        $results = [
            'database' => $this->createDatabaseBackup('full'),
            'files' => $this->createFileBackup(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($results['database'] && $results['files']) {
            $this->log("Full backup completed successfully");
            $this->createBackupManifest($results);
            return $results;
        } else {
            $this->log("Full backup failed");
            return false;
        }
    }
    
    private function createBackupManifest($results) {
        $manifest = [
            'backup_type' => 'full',
            'timestamp' => $results['timestamp'],
            'database_backup' => basename($results['database']),
            'file_backup' => basename($results['files']),
            'backup_size' => [
                'database' => $this->formatBytes(filesize($results['database'])),
                'files' => $this->formatBytes(filesize($results['files']))
            ],
            'retention_policy' => $this->config['backup_retention_days'] ?? 30,
            'created_by' => 'automated_backup_system',
            'version' => '1.0.0'
        ];
        
        $manifestFile = $this->backupDir . '/manifest_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        
        $this->log("Backup manifest created: " . basename($manifestFile));
    }
    
    private function compressFile($filepath) {
        if (file_exists($filepath)) {
            $compressedPath = $filepath . '.gz';
            $command = "gzip '$filepath'";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($compressedPath)) {
                $this->log("File compressed: " . basename($compressedPath));
                return $compressedPath;
            }
        }
        return $filepath;
    }
    
    public function cleanOldBackups($retentionDays = null) {
        $retentionDays = $retentionDays ?? ($this->config['backup_retention_days'] ?? 30);
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        $this->log("Cleaning backups older than $retentionDays days...");
        
        $directories = [$this->dbBackupDir, $this->fileBackupDir];
        $deletedCount = 0;
        $freedSpace = 0;
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoffTime) {
                        $size = filesize($file);
                        if (unlink($file)) {
                            $deletedCount++;
                            $freedSpace += $size;
                            $this->log("Deleted old backup: " . basename($file));
                        }
                    }
                }
            }
        }
        
        if ($deletedCount > 0) {
            $this->log("Cleanup completed: $deletedCount files deleted, " . $this->formatBytes($freedSpace) . " freed");
        } else {
            $this->log("No old backups found for cleanup");
        }
        
        return $deletedCount;
    }
    
    public function verifyBackup($backupPath) {
        if (!file_exists($backupPath)) {
            return false;
        }
        
        $this->log("Verifying backup: " . basename($backupPath));
        
        // Check file size
        $size = filesize($backupPath);
        if ($size < 1024) { // Less than 1KB is suspicious
            $this->log("WARNING: Backup file is very small ($size bytes)");
            return false;
        }
        
        // Check if it's a SQL file
        if (strpos($backupPath, '.sql') !== false) {
            return $this->verifyDatabaseBackup($backupPath);
        }
        
        // Check if it's a tar.gz file
        if (strpos($backupPath, '.tar.gz') !== false) {
            return $this->verifyFileBackup($backupPath);
        }
        
        return true;
    }
    
    private function verifyDatabaseBackup($backupPath) {
        // Check if file contains SQL content
        $handle = fopen($backupPath, 'r');
        if ($handle) {
            $firstLine = fgets($handle);
            fclose($handle);
            
            if (strpos($firstLine, 'mysqldump') !== false || strpos($firstLine, 'CREATE') !== false) {
                $this->log("Database backup verification passed");
                return true;
            }
        }
        
        $this->log("WARNING: Database backup verification failed");
        return false;
    }
    
    private function verifyFileBackup($backupPath) {
        // Test tar.gz file integrity
        $command = "tar -tzf '$backupPath' > /dev/null 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->log("File backup verification passed");
            return true;
        } else {
            $this->log("WARNING: File backup verification failed");
            return false;
        }
    }
    
    public function getBackupStatus() {
        $status = [
            'backup_directory' => $this->backupDir,
            'disk_usage' => $this->getDiskUsage(),
            'recent_backups' => $this->getRecentBackups(),
            'next_cleanup' => $this->getNextCleanupDate(),
            'total_backups' => $this->getTotalBackupCount(),
            'last_backup' => $this->getLastBackupInfo()
        ];
        
        return $status;
    }
    
    private function getDiskUsage() {
        $totalSize = 0;
        $directories = [$this->dbBackupDir, $this->fileBackupDir];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $totalSize += filesize($file);
                    }
                }
            }
        }
        
        return $this->formatBytes($totalSize);
    }
    
    private function getRecentBackups($limit = 10) {
        $backups = [];
        $directories = [$this->dbBackupDir, $this->fileBackupDir];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $backups[] = [
                            'name' => basename($file),
                            'path' => $file,
                            'size' => $this->formatBytes(filesize($file)),
                            'created' => date('Y-m-d H:i:s', filemtime($file)),
                            'type' => strpos($file, 'db_') !== false ? 'database' : 'files'
                        ];
                    }
                }
            }
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        return array_slice($backups, 0, $limit);
    }
    
    private function getNextCleanupDate() {
        $retentionDays = $this->config['backup_retention_days'] ?? 30;
        return date('Y-m-d', time() + (24 * 60 * 60)); // Tomorrow
    }
    
    private function getTotalBackupCount() {
        $count = 0;
        $directories = [$this->dbBackupDir, $this->fileBackupDir];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                $count += count(array_filter($files, 'is_file'));
            }
        }
        
        return $count;
    }
    
    private function getLastBackupInfo() {
        $backups = $this->getRecentBackups(1);
        return !empty($backups) ? $backups[0] : null;
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function setupCronJob() {
        $cronScript = $this->createCronScript();
        $cronFile = '/etc/cron.d/aureus-backup';
        
        if (file_put_contents($cronFile, $cronScript)) {
            $this->log("Cron job setup completed: $cronFile");
            return true;
        } else {
            $this->log("Failed to setup cron job");
            return false;
        }
    }
    
    private function createCronScript() {
        $scriptPath = __FILE__;
        
        $cron = "# Aureus Angel Alliance Backup Cron Jobs\n";
        $cron .= "# Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $cron .= "# Daily full backup at 2:00 AM\n";
        $cron .= "0 2 * * * root /usr/bin/php $scriptPath --full-backup\n\n";
        $cron .= "# Weekly cleanup at 3:00 AM on Sundays\n";
        $cron .= "0 3 * * 0 root /usr/bin/php $scriptPath --cleanup\n\n";
        $cron .= "# Database backup every 6 hours\n";
        $cron .= "0 */6 * * * root /usr/bin/php $scriptPath --db-backup\n\n";
        
        return $cron;
    }
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

if (php_sapi_name() === 'cli') {
    $manager = new BackupManager();
    
    // Parse command line arguments
    $options = getopt('', ['full-backup', 'db-backup', 'file-backup', 'cleanup', 'status', 'setup-cron']);
    
    if (isset($options['full-backup'])) {
        echo "Starting full backup...\n";
        $result = $manager->createFullBackup();
        exit($result ? 0 : 1);
    } elseif (isset($options['db-backup'])) {
        echo "Starting database backup...\n";
        $result = $manager->createDatabaseBackup();
        exit($result ? 0 : 1);
    } elseif (isset($options['file-backup'])) {
        echo "Starting file backup...\n";
        $result = $manager->createFileBackup();
        exit($result ? 0 : 1);
    } elseif (isset($options['cleanup'])) {
        echo "Starting backup cleanup...\n";
        $manager->cleanOldBackups();
        exit(0);
    } elseif (isset($options['status'])) {
        echo "Backup Status:\n";
        print_r($manager->getBackupStatus());
        exit(0);
    } elseif (isset($options['setup-cron'])) {
        echo "Setting up cron jobs...\n";
        $result = $manager->setupCronJob();
        exit($result ? 0 : 1);
    } else {
        echo "Aureus Angel Alliance - Backup System\n";
        echo "Usage: php backup-system.php [options]\n";
        echo "Options:\n";
        echo "  --full-backup   Create full system backup\n";
        echo "  --db-backup     Create database backup only\n";
        echo "  --file-backup   Create file backup only\n";
        echo "  --cleanup       Clean old backups\n";
        echo "  --status        Show backup status\n";
        echo "  --setup-cron    Setup automated cron jobs\n";
    }
} else {
    // Web interface
    header('Content-Type: application/json');
    
    try {
        $manager = new BackupManager();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'status';
            
            switch ($action) {
                case 'full-backup':
                    $result = $manager->createFullBackup();
                    echo json_encode(['success' => (bool)$result, 'data' => $result]);
                    break;
                case 'db-backup':
                    $result = $manager->createDatabaseBackup();
                    echo json_encode(['success' => (bool)$result, 'data' => $result]);
                    break;
                case 'cleanup':
                    $result = $manager->cleanOldBackups();
                    echo json_encode(['success' => true, 'deleted_count' => $result]);
                    break;
                default:
                    $status = $manager->getBackupStatus();
                    echo json_encode(['success' => true, 'data' => $status]);
            }
        } else {
            $status = $manager->getBackupStatus();
            echo json_encode(['success' => true, 'data' => $status]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
