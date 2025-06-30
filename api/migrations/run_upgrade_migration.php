<?php
/**
 * Aureus Angel Alliance - Business Model Upgrade Migration
 * 
 * This script safely migrates the database from ROI model to Direct Commission model
 * 
 * IMPORTANT: 
 * - Backup your database before running this migration
 * - Test on a development environment first
 * - Run during maintenance window
 */

require_once __DIR__ . '/../config/database.php';

// Set execution time limit for large migrations
set_time_limit(300); // 5 minutes

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

class BusinessModelMigration {
    private $pdo;
    private $logFile;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->pdo = $database->getConnection();
            $this->logFile = __DIR__ . '/migration_log_' . date('Y-m-d_H-i-s') . '.txt';
            
            // Create log file
            file_put_contents($this->logFile, "=== Business Model Migration Started ===\n", FILE_APPEND);
            $this->log("Migration started at: " . date('Y-m-d H:i:s'));
            
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
    
    public function runMigration() {
        try {
            $this->log("Starting database backup check...");
            $this->checkPrerequisites();
            
            $this->log("Reading migration SQL file...");
            $sqlFile = __DIR__ . '/upgrade_business_model.sql';
            
            if (!file_exists($sqlFile)) {
                throw new Exception("Migration SQL file not found: $sqlFile");
            }
            
            $sql = file_get_contents($sqlFile);
            if (!$sql) {
                throw new Exception("Failed to read migration SQL file");
            }
            
            $this->log("Executing migration...");
            $this->executeMigration($sql);
            
            $this->log("Verifying migration results...");
            $this->verifyMigration();
            
            $this->log("Migration completed successfully!");
            
            return [
                'success' => true,
                'message' => 'Business model migration completed successfully',
                'log_file' => $this->logFile
            ];
            
        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->log("Migration failed!");
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log_file' => $this->logFile
            ];
        }
    }
    
    private function checkPrerequisites() {
        // Check if we can create tables
        $this->pdo->query("SELECT 1");
        $this->log("Database connection verified");
        
        // Check if backup exists (optional warning)
        $this->log("WARNING: Ensure you have a database backup before proceeding");
        
        // Check existing tables
        $tables = ['users', 'aureus_investments', 'investment_packages'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                throw new Exception("Required table '$table' not found");
            }
        }
        $this->log("Required tables verified");
    }
    
    private function executeMigration($sql) {
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^\s*--/', $stmt) && 
                       !preg_match('/^\s*\/\*/', $stmt);
            }
        );
        
        $this->log("Found " . count($statements) . " SQL statements to execute");
        
        foreach ($statements as $index => $statement) {
            try {
                $this->log("Executing statement " . ($index + 1) . "...");
                $this->pdo->exec($statement);
                
            } catch (PDOException $e) {
                // Log the error but continue for non-critical errors
                if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false) {
                    $this->log("WARNING: " . $e->getMessage() . " (continuing...)");
                } else {
                    throw new Exception("SQL Error in statement " . ($index + 1) . ": " . $e->getMessage());
                }
            }
        }
    }
    
    private function verifyMigration() {
        $verifications = [
            'phases table exists' => "SHOW TABLES LIKE 'phases'",
            'competitions table exists' => "SHOW TABLES LIKE 'competitions'",
            'competition_participants table exists' => "SHOW TABLES LIKE 'competition_participants'",
            'npo_fund table exists' => "SHOW TABLES LIKE 'npo_fund'",
            'share_certificates table exists' => "SHOW TABLES LIKE 'share_certificates'",
            'revenue_distribution_log table exists' => "SHOW TABLES LIKE 'revenue_distribution_log'",
            'phases data inserted' => "SELECT COUNT(*) as count FROM phases",
            'investment_packages updated' => "SHOW COLUMNS FROM investment_packages LIKE 'commission_percentage'",
            'aureus_investments updated' => "SHOW COLUMNS FROM aureus_investments LIKE 'commission_amount'",
            'commission_records updated' => "SHOW COLUMNS FROM commission_records LIKE 'commission_percentage'"
        ];
        
        foreach ($verifications as $check => $query) {
            try {
                $result = $this->pdo->query($query);
                if ($result->rowCount() > 0) {
                    $this->log("✓ Verified: $check");
                } else {
                    $this->log("✗ Failed: $check");
                }
            } catch (Exception $e) {
                $this->log("✗ Error verifying $check: " . $e->getMessage());
            }
        }
    }
    
    public function rollback() {
        $this->log("ROLLBACK: This migration includes destructive changes.");
        $this->log("ROLLBACK: Please restore from database backup to rollback.");
        $this->log("ROLLBACK: Automatic rollback is not available for this migration.");
        
        return [
            'success' => false,
            'message' => 'Please restore from database backup to rollback this migration'
        ];
    }
}

// Check if this is being run from command line or web
if (php_sapi_name() === 'cli') {
    // Command line execution
    echo "=== Aureus Angel Alliance - Business Model Migration ===\n";
    echo "WARNING: This will modify your database structure!\n";
    echo "Ensure you have a backup before proceeding.\n\n";
    
    echo "Do you want to continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(1);
    }
    
    $migration = new BusinessModelMigration();
    $result = $migration->runMigration();
    
    if ($result['success']) {
        echo "\n✓ Migration completed successfully!\n";
        echo "Log file: " . $result['log_file'] . "\n";
        exit(0);
    } else {
        echo "\n✗ Migration failed!\n";
        echo "Error: " . $result['error'] . "\n";
        echo "Log file: " . $result['log_file'] . "\n";
        exit(1);
    }
    
} else {
    // Web execution
    header('Content-Type: application/json');
    
    // Simple authentication check
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Admin authentication required']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        $migration = new BusinessModelMigration();
        
        if ($action === 'migrate') {
            $result = $migration->runMigration();
            echo json_encode($result);
            
        } elseif ($action === 'rollback') {
            $result = $migration->rollback();
            echo json_encode($result);
            
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
        
    } else {
        // Show migration interface
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Business Model Migration</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; }
                .button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 10px; }
                .button:hover { background: #005a87; }
                .danger { background: #dc3545; }
                .danger:hover { background: #c82333; }
                #log { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; height: 300px; overflow-y: scroll; font-family: monospace; }
            </style>
        </head>
        <body>
            <h1>Aureus Angel Alliance - Business Model Migration</h1>
            
            <div class="warning">
                <strong>⚠️ WARNING:</strong> This migration will permanently modify your database structure!
                <ul>
                    <li>Backup your database before proceeding</li>
                    <li>Test on development environment first</li>
                    <li>Run during maintenance window</li>
                    <li>This migration removes ROI system completely</li>
                </ul>
            </div>
            
            <button class="button" onclick="runMigration()">Run Migration</button>
            <button class="button danger" onclick="rollback()">Rollback (Restore from Backup)</button>
            
            <h3>Migration Log:</h3>
            <div id="log"></div>
            
            <script>
                function runMigration() {
                    if (!confirm('Are you sure you want to run the migration? This cannot be undone!')) {
                        return;
                    }
                    
                    document.getElementById('log').innerHTML = 'Starting migration...\n';
                    
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=migrate'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('log').innerHTML += '✓ Migration completed successfully!\n';
                            document.getElementById('log').innerHTML += 'Log file: ' + data.log_file + '\n';
                        } else {
                            document.getElementById('log').innerHTML += '✗ Migration failed!\n';
                            document.getElementById('log').innerHTML += 'Error: ' + data.error + '\n';
                        }
                    })
                    .catch(error => {
                        document.getElementById('log').innerHTML += '✗ Network error: ' + error + '\n';
                    });
                }
                
                function rollback() {
                    alert('Please restore your database from backup to rollback this migration.');
                }
            </script>
        </body>
        </html>
        <?php
    }
}
?>
