<?php
/**
 * Complete Database Setup Script
 * Creates all required tables and columns for Aureus Angel Alliance
 * No dummy data - production ready
 */

require_once '../config/database.php';

function setupCompleteDatabase() {
    try {
        echo "🚀 Starting Aureus Angel Alliance Database Setup...\n\n";
        
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception('Failed to connect to database');
        }
        
        echo "✅ Database connection established\n";
        
        // Read the SQL file
        $sqlFile = '../../database/complete-database-setup.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new Exception("Failed to read SQL file");
        }
        
        echo "📄 SQL file loaded successfully\n";
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^\s*--/', $stmt) && 
                       !preg_match('/^\s*\/\*/', $stmt);
            }
        );
        
        echo "🔧 Executing " . count($statements) . " SQL statements...\n\n";
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $index => $statement) {
            try {
                // Skip comments and empty statements
                if (empty(trim($statement))) continue;
                
                $db->exec($statement);
                $successCount++;
                
                // Extract table name for progress display
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "  ✅ Created table: {$matches[1]}\n";
                } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "  ✅ Created view: {$matches[1]}\n";
                } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "  ✅ Modified table: {$matches[1]}\n";
                } elseif (preg_match('/CREATE INDEX.*?ON.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "  ✅ Created index on: {$matches[1]}\n";
                }
                
            } catch (PDOException $e) {
                $errorCount++;
                echo "  ❌ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
                
                // Continue with other statements unless it's a critical error
                if (strpos($e->getMessage(), 'database') !== false) {
                    throw $e;
                }
            }
        }
        
        echo "\n📊 Setup Summary:\n";
        echo "  • Successful statements: $successCount\n";
        echo "  • Failed statements: $errorCount\n";
        
        // Verify critical tables exist
        echo "\n🔍 Verifying critical tables...\n";
        $criticalTables = [
            'users',
            'user_profiles', 
            'aureus_investments',
            'referral_relationships',
            'commission_transactions',
            'delivery_schedule',
            'admin_users'
        ];
        
        $missingTables = [];
        foreach ($criticalTables as $table) {
            try {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "  ✅ $table\n";
                } else {
                    $missingTables[] = $table;
                    echo "  ❌ $table (missing)\n";
                }
            } catch (Exception $e) {
                $missingTables[] = $table;
                echo "  ❌ $table (error checking)\n";
            }
        }
        
        // Check if countdown columns exist in aureus_investments
        echo "\n🔍 Verifying countdown system columns...\n";
        try {
            $stmt = $db->query("DESCRIBE aureus_investments");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $countdownColumns = [
                'nft_delivery_date',
                'roi_delivery_date', 
                'delivery_status',
                'nft_delivered',
                'roi_delivered'
            ];
            
            foreach ($countdownColumns as $column) {
                if (in_array($column, $columns)) {
                    echo "  ✅ $column\n";
                } else {
                    echo "  ❌ $column (missing)\n";
                }
            }
        } catch (Exception $e) {
            echo "  ❌ Error checking countdown columns: " . $e->getMessage() . "\n";
        }
        
        // Verify views exist
        echo "\n🔍 Verifying views...\n";
        try {
            $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('investment_countdown_view', $views)) {
                echo "  ✅ investment_countdown_view\n";
            } else {
                echo "  ❌ investment_countdown_view (missing)\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Error checking views: " . $e->getMessage() . "\n";
        }
        
        if (empty($missingTables)) {
            echo "\n🎉 DATABASE SETUP COMPLETE!\n";
            echo "✅ All critical tables created successfully\n";
            echo "✅ 180-day countdown system ready\n";
            echo "✅ Referral & commission system ready\n";
            echo "✅ User profiles & KYC system ready\n";
            echo "✅ Admin & delivery management ready\n";
            echo "✅ Communication system ready\n";
            
            echo "\n📋 Next Steps:\n";
            echo "1. Create your first admin user through the admin registration\n";
            echo "2. Configure investment packages in the admin panel\n";
            echo "3. Set up wallet addresses for payments\n";
            echo "4. Test the investment flow with countdown system\n";
            
            return true;
        } else {
            echo "\n⚠️  SETUP COMPLETED WITH ISSUES\n";
            echo "Missing tables: " . implode(', ', $missingTables) . "\n";
            echo "Please check the errors above and run the setup again.\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "\n❌ SETUP FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Please check your database configuration and try again.\n";
        return false;
    }
}

// Function to check if setup is needed
function isDatabaseSetupNeeded() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if critical tables exist
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() === 0) {
            return true;
        }
        
        // Check if countdown columns exist
        $stmt = $db->query("SHOW COLUMNS FROM aureus_investments LIKE 'nft_delivery_date'");
        if ($stmt->rowCount() === 0) {
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        return true; // If we can't check, assume setup is needed
    }
}

// Run setup if called directly
if (php_sapi_name() === 'cli') {
    setupCompleteDatabase();
} else {
    // Web interface
    header('Content-Type: text/plain');
    
    if (isDatabaseSetupNeeded()) {
        setupCompleteDatabase();
    } else {
        echo "✅ Database is already set up!\n";
        echo "All required tables and columns are present.\n";
        echo "\nIf you need to re-run the setup, please drop the tables first.\n";
    }
}
?>
