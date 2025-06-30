<?php
require_once '../config/database.php';

function setupCountdownSystem() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        echo "Setting up 180-day countdown system...\n";
        
        // Add delivery countdown columns to aureus_investments
        echo "Adding countdown columns to aureus_investments table...\n";
        $alterQuery = "ALTER TABLE aureus_investments 
                      ADD COLUMN IF NOT EXISTS nft_delivery_date TIMESTAMP NULL COMMENT 'Date when NFT will be delivered (180 days from purchase)',
                      ADD COLUMN IF NOT EXISTS roi_delivery_date TIMESTAMP NULL COMMENT 'Date when ROI will be delivered (180 days from purchase)',
                      ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending' COMMENT 'Delivery status of NFT and ROI',
                      ADD COLUMN IF NOT EXISTS nft_delivered BOOLEAN DEFAULT FALSE COMMENT 'Whether NFT has been delivered',
                      ADD COLUMN IF NOT EXISTS roi_delivered BOOLEAN DEFAULT FALSE COMMENT 'Whether ROI has been delivered',
                      ADD COLUMN IF NOT EXISTS nft_delivery_tx_hash VARCHAR(255) NULL COMMENT 'Transaction hash for NFT delivery',
                      ADD COLUMN IF NOT EXISTS roi_delivery_tx_hash VARCHAR(255) NULL COMMENT 'Transaction hash for ROI delivery'";
        
        $db->exec($alterQuery);
        echo "✓ Countdown columns added successfully\n";
        
        // Create indexes
        echo "Creating indexes for countdown queries...\n";
        $indexQueries = [
            "CREATE INDEX IF NOT EXISTS idx_nft_delivery_date ON aureus_investments(nft_delivery_date)",
            "CREATE INDEX IF NOT EXISTS idx_roi_delivery_date ON aureus_investments(roi_delivery_date)",
            "CREATE INDEX IF NOT EXISTS idx_delivery_status ON aureus_investments(delivery_status)"
        ];
        
        foreach ($indexQueries as $query) {
            $db->exec($query);
        }
        echo "✓ Indexes created successfully\n";
        
        // Update existing investments to have delivery dates
        echo "Setting delivery dates for existing completed investments...\n";
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
        echo "✓ Updated $updatedRows existing investments with delivery dates\n";
        
        // Create delivery_notifications table
        echo "Creating delivery_notifications table...\n";
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
        echo "✓ Delivery notifications table created\n";
        
        // Create delivery_schedule table
        echo "Creating delivery_schedule table...\n";
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
            INDEX idx_roi_status (roi_status),
            INDEX idx_assigned_to (assigned_to)
        )";
        
        $db->exec($scheduleTable);
        echo "✓ Delivery schedule table created\n";
        
        // Insert delivery schedule records for existing completed investments
        echo "Creating delivery schedule entries for existing investments...\n";
        $insertScheduleQuery = "INSERT INTO delivery_schedule (
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
        AND nft_delivery_date IS NOT NULL
        AND id NOT IN (SELECT investment_id FROM delivery_schedule)";
        
        $stmt = $db->prepare($insertScheduleQuery);
        $stmt->execute();
        $scheduleRows = $stmt->rowCount();
        echo "✓ Created $scheduleRows delivery schedule entries\n";
        
        // Create countdown view
        echo "Creating investment_countdown_view...\n";
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
        echo "✓ Investment countdown view created\n";
        
        // Get summary statistics
        echo "\n=== COUNTDOWN SYSTEM SETUP COMPLETE ===\n";
        
        $statsQuery = "SELECT 
            COUNT(*) as total_investments,
            COUNT(CASE WHEN nft_delivery_date IS NOT NULL THEN 1 END) as with_nft_countdown,
            COUNT(CASE WHEN roi_delivery_date IS NOT NULL THEN 1 END) as with_roi_countdown,
            COUNT(CASE WHEN nft_delivered = TRUE THEN 1 END) as nft_delivered,
            COUNT(CASE WHEN roi_delivered = TRUE THEN 1 END) as roi_delivered
        FROM aureus_investments WHERE status = 'completed'";
        
        $stmt = $db->prepare($statsQuery);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📊 Summary Statistics:\n";
        echo "   • Total completed investments: {$stats['total_investments']}\n";
        echo "   • With NFT countdown: {$stats['with_nft_countdown']}\n";
        echo "   • With ROI countdown: {$stats['with_roi_countdown']}\n";
        echo "   • NFT delivered: {$stats['nft_delivered']}\n";
        echo "   • ROI delivered: {$stats['roi_delivered']}\n";
        
        echo "\n✅ 180-day countdown system is now active!\n";
        echo "   • New investments will automatically get 180-day delivery dates\n";
        echo "   • Users can track countdowns in their dashboard\n";
        echo "   • Admins can manage deliveries through the delivery schedule\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Error setting up countdown system: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the setup if called directly
if (php_sapi_name() === 'cli') {
    setupCountdownSystem();
} else {
    // Web interface
    header('Content-Type: text/plain');
    setupCountdownSystem();
}
?>
