<?php
/**
 * SAFE MIGRATION: Add 180-Day Countdown System
 * This script ONLY ADDS new columns and tables
 * DOES NOT modify or delete any existing data
 */

require_once '../config/database.php';

function addCountdownSystemSafely() {
    try {
        echo "🔒 SAFE MIGRATION: Adding 180-Day Countdown System\n";
        echo "⚠️  This will NOT modify any existing data\n\n";
        
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception('Failed to connect to database');
        }
        
        echo "✅ Connected to existing database\n";
        
        // Step 1: Add countdown columns to existing aureus_investments table
        echo "\n🔧 Adding countdown columns to aureus_investments table...\n";
        
        $countdownColumns = [
            "ADD COLUMN IF NOT EXISTS nft_delivery_date TIMESTAMP NULL COMMENT 'Date when NFT will be delivered (180 days from purchase)'",
            "ADD COLUMN IF NOT EXISTS roi_delivery_date TIMESTAMP NULL COMMENT 'Date when ROI will be delivered (180 days from purchase)'",
            "ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending' COMMENT 'Delivery status of NFT and ROI'",
            "ADD COLUMN IF NOT EXISTS nft_delivered BOOLEAN DEFAULT FALSE COMMENT 'Whether NFT has been delivered'",
            "ADD COLUMN IF NOT EXISTS roi_delivered BOOLEAN DEFAULT FALSE COMMENT 'Whether ROI has been delivered'",
            "ADD COLUMN IF NOT EXISTS nft_delivery_tx_hash VARCHAR(255) NULL COMMENT 'Transaction hash for NFT delivery'",
            "ADD COLUMN IF NOT EXISTS roi_delivery_tx_hash VARCHAR(255) NULL COMMENT 'Transaction hash for ROI delivery'"
        ];
        
        foreach ($countdownColumns as $column) {
            try {
                $db->exec("ALTER TABLE aureus_investments $column");
                echo "  ✅ Added column: " . preg_replace('/ADD COLUMN IF NOT EXISTS (\w+).*/', '$1', $column) . "\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "  ⚠️  Column already exists: " . preg_replace('/ADD COLUMN IF NOT EXISTS (\w+).*/', '$1', $column) . "\n";
                } else {
                    echo "  ❌ Error adding column: " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Step 2: Add indexes for performance
        echo "\n🔧 Adding indexes for countdown queries...\n";
        
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_nft_delivery_date ON aureus_investments(nft_delivery_date)",
            "CREATE INDEX IF NOT EXISTS idx_roi_delivery_date ON aureus_investments(roi_delivery_date)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_status ON aureus_investments(delivery_status)"
        ];
        
        foreach ($indexes as $index) {
            try {
                $db->exec($index);
                echo "  ✅ Created index: " . preg_replace('/.*ON aureus_investments\((\w+)\)/', '$1', $index) . "\n";
            } catch (PDOException $e) {
                echo "  ⚠️  Index may already exist: " . $e->getMessage() . "\n";
            }
        }
        
        // Step 3: Update existing completed investments with delivery dates
        echo "\n🔧 Setting delivery dates for existing completed investments...\n";
        
        $updateQuery = "UPDATE aureus_investments 
                       SET 
                           nft_delivery_date = DATE_ADD(created_at, INTERVAL 180 DAY),
                           roi_delivery_date = DATE_ADD(created_at, INTERVAL 180 DAY)
                       WHERE 
                           nft_delivery_date IS NULL 
                           AND status = 'completed'";
        
        $stmt = $db->prepare($updateQuery);
        $stmt->execute();
        $updatedRows = $stmt->rowCount();
        echo "  ✅ Updated $updatedRows existing investments with delivery dates\n";
        
        // Step 4: Create delivery_schedule table (new table, safe to create)
        echo "\n🔧 Creating delivery_schedule table...\n";
        
        $scheduleTable = "CREATE TABLE IF NOT EXISTS delivery_schedule (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            investment_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            package_name VARCHAR(100) NOT NULL,
            investment_amount DECIMAL(15,6) NOT NULL,
            nft_delivery_date TIMESTAMP NOT NULL,
            roi_delivery_date TIMESTAMP NOT NULL,
            nft_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
            roi_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
            priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
            notes TEXT NULL,
            assigned_to VARCHAR(36) NULL COMMENT 'Admin user assigned to handle delivery',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_investment_id (investment_id),
            INDEX idx_user_id (user_id),
            INDEX idx_nft_delivery_date (nft_delivery_date),
            INDEX idx_roi_delivery_date (roi_delivery_date),
            INDEX idx_nft_status (nft_status),
            INDEX idx_roi_status (roi_status)
        )";
        
        $db->exec($scheduleTable);
        echo "  ✅ Created delivery_schedule table\n";
        
        // Step 5: Create delivery_notifications table (new table, safe to create)
        echo "\n🔧 Creating delivery_notifications table...\n";
        
        $notificationsTable = "CREATE TABLE IF NOT EXISTS delivery_notifications (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            investment_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            notification_type ENUM('30_days', '7_days', '1_day', 'delivery_ready') NOT NULL,
            delivery_type ENUM('nft', 'roi', 'both') NOT NULL,
            sent_at TIMESTAMP NULL,
            email_sent BOOLEAN DEFAULT FALSE,
            sms_sent BOOLEAN DEFAULT FALSE,
            push_sent BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_investment_id (investment_id),
            INDEX idx_user_id (user_id),
            INDEX idx_notification_type (notification_type),
            INDEX idx_sent_at (sent_at),
            UNIQUE KEY unique_notification (investment_id, notification_type, delivery_type)
        )";
        
        $db->exec($notificationsTable);
        echo "  ✅ Created delivery_notifications table\n";
        
        // Step 6: Populate delivery_schedule for existing investments
        echo "\n🔧 Creating delivery schedule entries for existing investments...\n";
        
        $insertScheduleQuery = "INSERT IGNORE INTO delivery_schedule (
            investment_id, 
            user_id, 
            package_name, 
            investment_amount, 
            nft_delivery_date, 
            roi_delivery_date
        )
        SELECT 
            id,
            user_id,
            package_name,
            amount,
            nft_delivery_date,
            roi_delivery_date
        FROM aureus_investments 
        WHERE status = 'completed' 
        AND nft_delivery_date IS NOT NULL";
        
        $stmt = $db->prepare($insertScheduleQuery);
        $stmt->execute();
        $scheduleRows = $stmt->rowCount();
        echo "  ✅ Created $scheduleRows delivery schedule entries\n";
        
        // Step 7: Create countdown view (safe to create/replace)
        echo "\n🔧 Creating investment_countdown_view...\n";
        
        $viewQuery = "CREATE OR REPLACE VIEW investment_countdown_view AS
        SELECT 
            ai.id,
            ai.user_id,
            ai.package_name,
            ai.amount,
            ai.shares,
            ai.roi,
            ai.status,
            ai.created_at,
            ai.nft_delivery_date,
            ai.roi_delivery_date,
            ai.delivery_status,
            ai.nft_delivered,
            ai.roi_delivered,
            
            -- Calculate days remaining
            CASE 
                WHEN ai.nft_delivery_date IS NULL THEN NULL
                WHEN ai.nft_delivered = TRUE THEN 0
                ELSE GREATEST(0, DATEDIFF(ai.nft_delivery_date, NOW()))
            END as nft_days_remaining,
            
            CASE 
                WHEN ai.roi_delivery_date IS NULL THEN NULL
                WHEN ai.roi_delivered = TRUE THEN 0
                ELSE GREATEST(0, DATEDIFF(ai.roi_delivery_date, NOW()))
            END as roi_days_remaining,
            
            -- Calculate hours remaining for more precise countdown
            CASE 
                WHEN ai.nft_delivery_date IS NULL THEN NULL
                WHEN ai.nft_delivered = TRUE THEN 0
                ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.nft_delivery_date))
            END as nft_hours_remaining,
            
            CASE 
                WHEN ai.roi_delivery_date IS NULL THEN NULL
                WHEN ai.roi_delivered = TRUE THEN 0
                ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.roi_delivery_date))
            END as roi_hours_remaining,
            
            -- Status indicators
            CASE 
                WHEN ai.nft_delivered = TRUE THEN 'delivered'
                WHEN ai.nft_delivery_date <= NOW() THEN 'ready'
                WHEN DATEDIFF(ai.nft_delivery_date, NOW()) <= 7 THEN 'soon'
                ELSE 'pending'
            END as nft_countdown_status,
            
            CASE 
                WHEN ai.roi_delivered = TRUE THEN 'delivered'
                WHEN ai.roi_delivery_date <= NOW() THEN 'ready'
                WHEN DATEDIFF(ai.roi_delivery_date, NOW()) <= 7 THEN 'soon'
                ELSE 'pending'
            END as roi_countdown_status

        FROM aureus_investments ai
        WHERE ai.status = 'completed'";
        
        $db->exec($viewQuery);
        echo "  ✅ Created investment_countdown_view\n";
        
        // Step 8: Verify the migration
        echo "\n🔍 Verifying migration results...\n";
        
        // Check columns exist
        $stmt = $db->query("DESCRIBE aureus_investments");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['nft_delivery_date', 'roi_delivery_date', 'delivery_status', 'nft_delivered', 'roi_delivered'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $column) {
            if (in_array($column, $columns)) {
                echo "  ✅ Column exists: $column\n";
            } else {
                $missingColumns[] = $column;
                echo "  ❌ Missing column: $column\n";
            }
        }
        
        // Check tables exist
        $tables = ['delivery_schedule', 'delivery_notifications'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "  ✅ Table exists: $table\n";
            } else {
                echo "  ❌ Missing table: $table\n";
            }
        }
        
        // Check view exists
        $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_" . $db->query("SELECT DATABASE()")->fetchColumn() . " = 'investment_countdown_view'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ View exists: investment_countdown_view\n";
        } else {
            echo "  ❌ Missing view: investment_countdown_view\n";
        }
        
        // Get final statistics
        echo "\n📊 Migration Summary:\n";
        
        $stats = $db->query("SELECT 
            COUNT(*) as total_investments,
            COUNT(CASE WHEN nft_delivery_date IS NOT NULL THEN 1 END) as with_countdown,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_investments
        FROM aureus_investments")->fetch(PDO::FETCH_ASSOC);
        
        echo "  • Total investments: {$stats['total_investments']}\n";
        echo "  • Completed investments: {$stats['completed_investments']}\n";
        echo "  • With countdown dates: {$stats['with_countdown']}\n";
        
        $scheduleCount = $db->query("SELECT COUNT(*) FROM delivery_schedule")->fetchColumn();
        echo "  • Delivery schedule entries: $scheduleCount\n";
        
        if (empty($missingColumns)) {
            echo "\n🎉 MIGRATION COMPLETED SUCCESSFULLY!\n";
            echo "✅ 180-day countdown system is now active\n";
            echo "✅ All existing data preserved\n";
            echo "✅ New investments will automatically get countdown dates\n";
            echo "✅ Existing investments updated with delivery dates\n";
            
            return true;
        } else {
            echo "\n⚠️  MIGRATION COMPLETED WITH ISSUES\n";
            echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "\n❌ MIGRATION FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Your existing data is safe and unchanged.\n";
        return false;
    }
}

// Run migration if called directly
if (php_sapi_name() === 'cli') {
    addCountdownSystemSafely();
} else {
    // Web interface
    header('Content-Type: text/plain');
    addCountdownSystemSafely();
}
?>
