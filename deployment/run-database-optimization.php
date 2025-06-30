<?php
// ============================================================================
// DATABASE OPTIMIZATION RUNNER FOR AUREUS ANGEL ALLIANCE
// ============================================================================
// This script executes database optimization safely with error handling
// ============================================================================

require_once '../api/config/database.php';

class DatabaseOptimizer {
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/optimization.log';
        $this->log("Database optimization started at " . date('Y-m-d H:i:s'));
        
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }
            
            $this->log("Database connection established successfully");
        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }
    
    public function runOptimization() {
        $this->log("Starting database optimization process...");
        
        // Check if optimization has already been run
        if ($this->isOptimizationComplete()) {
            $this->log("Database optimization already completed. Skipping...");
            return true;
        }
        
        try {
            // Step 1: Create backup before optimization
            $this->createBackup();
            
            // Step 2: Run optimization SQL
            $this->executeOptimizationSQL();
            
            // Step 3: Verify optimization
            $this->verifyOptimization();
            
            // Step 4: Mark optimization as complete
            $this->markOptimizationComplete();
            
            $this->log("Database optimization completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->log("ERROR during optimization: " . $e->getMessage());
            return false;
        }
    }
    
    private function isOptimizationComplete() {
        try {
            $query = "SELECT COUNT(*) as count FROM information_schema.statistics 
                      WHERE table_schema = DATABASE() 
                      AND index_name = 'idx_username' 
                      AND table_name = 'users'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function createBackup() {
        $this->log("Creating database backup...");
        
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/pre_optimization_backup_$timestamp.sql";
        
        // Get database name from connection
        $dbName = $this->db->query('SELECT DATABASE()')->fetchColumn();
        
        $command = "mysqldump -h localhost -u " . DB_USER . " -p" . DB_PASS . " $dbName > $backupFile";
        
        // Execute backup command
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->log("Backup created successfully: $backupFile");
        } else {
            throw new Exception("Backup creation failed");
        }
    }
    
    private function executeOptimizationSQL() {
        $this->log("Executing optimization SQL...");
        
        $sqlFile = __DIR__ . '/database-optimization.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("Optimization SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = $this->splitSQLStatements($sql);
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue; // Skip empty lines and comments
            }
            
            try {
                $this->db->exec($statement);
                $successCount++;
                $this->log("Executed: " . substr($statement, 0, 100) . "...");
            } catch (Exception $e) {
                $errorCount++;
                $this->log("WARNING: Failed to execute statement: " . $e->getMessage());
                $this->log("Statement: " . substr($statement, 0, 200) . "...");
            }
        }
        
        $this->log("Optimization SQL execution completed. Success: $successCount, Errors: $errorCount");
        
        if ($errorCount > $successCount) {
            throw new Exception("Too many errors during optimization");
        }
    }
    
    private function splitSQLStatements($sql) {
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $statements = explode(';', $sql);
        
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }
    
    private function verifyOptimization() {
        $this->log("Verifying optimization results...");
        
        $checks = [
            'users_username_index' => "SHOW INDEX FROM users WHERE Key_name = 'idx_username'",
            'investments_user_index' => "SHOW INDEX FROM aureus_investments WHERE Key_name = 'idx_user_id'",
            'commission_referrer_index' => "SHOW INDEX FROM commission_tracking WHERE Key_name = 'idx_referrer_id'",
            'user_summary_view' => "SHOW TABLES LIKE 'user_investment_summary'",
            'commission_summary_view' => "SHOW TABLES LIKE 'commission_summary'"
        ];
        
        $passedChecks = 0;
        $totalChecks = count($checks);
        
        foreach ($checks as $checkName => $query) {
            try {
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll();
                
                if (!empty($result)) {
                    $this->log("✓ $checkName: PASSED");
                    $passedChecks++;
                } else {
                    $this->log("✗ $checkName: FAILED");
                }
            } catch (Exception $e) {
                $this->log("✗ $checkName: ERROR - " . $e->getMessage());
            }
        }
        
        $this->log("Verification completed: $passedChecks/$totalChecks checks passed");
        
        if ($passedChecks < ($totalChecks * 0.8)) {
            throw new Exception("Verification failed: too many checks failed");
        }
    }
    
    private function markOptimizationComplete() {
        try {
            // Create optimization status table if it doesn't exist
            $createTable = "CREATE TABLE IF NOT EXISTS optimization_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                optimization_type VARCHAR(100) NOT NULL,
                completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                version VARCHAR(50) NOT NULL,
                status ENUM('completed', 'failed') DEFAULT 'completed',
                UNIQUE KEY unique_optimization (optimization_type)
            )";
            
            $this->db->exec($createTable);
            
            // Mark database optimization as complete
            $insertStatus = "INSERT INTO optimization_status (optimization_type, version, status) 
                           VALUES ('database_optimization', '1.0.0', 'completed')
                           ON DUPLICATE KEY UPDATE 
                           completed_at = CURRENT_TIMESTAMP, 
                           version = '1.0.0', 
                           status = 'completed'";
            
            $this->db->exec($insertStatus);
            
            $this->log("Optimization status marked as complete");
        } catch (Exception $e) {
            $this->log("WARNING: Could not mark optimization as complete: " . $e->getMessage());
        }
    }
    
    public function getOptimizationReport() {
        $this->log("Generating optimization report...");
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database_size' => $this->getDatabaseSize(),
            'table_count' => $this->getTableCount(),
            'index_count' => $this->getIndexCount(),
            'view_count' => $this->getViewCount(),
            'procedure_count' => $this->getProcedureCount()
        ];
        
        foreach ($report as $key => $value) {
            $this->log("Report - $key: $value");
        }
        
        return $report;
    }
    
    private function getDatabaseSize() {
        try {
            $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                      FROM information_schema.tables 
                      WHERE table_schema = DATABASE()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['size_mb'] . ' MB';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getTableCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getIndexCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = DATABASE()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getViewCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = DATABASE()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getProcedureCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM information_schema.routines 
                      WHERE routine_schema = DATABASE() AND routine_type = 'PROCEDURE'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

if (php_sapi_name() === 'cli') {
    // Command line execution
    echo "Aureus Angel Alliance - Database Optimization\n";
    echo "=============================================\n\n";
    
    $optimizer = new DatabaseOptimizer();
    
    if ($optimizer->runOptimization()) {
        echo "\n✅ Database optimization completed successfully!\n";
        $optimizer->getOptimizationReport();
    } else {
        echo "\n❌ Database optimization failed. Check logs for details.\n";
        exit(1);
    }
} else {
    // Web execution
    header('Content-Type: application/json');
    
    try {
        $optimizer = new DatabaseOptimizer();
        
        if ($optimizer->runOptimization()) {
            $report = $optimizer->getOptimizationReport();
            echo json_encode([
                'success' => true,
                'message' => 'Database optimization completed successfully',
                'report' => $report
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Database optimization failed'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database optimization error: ' . $e->getMessage()
        ]);
    }
}
?>
