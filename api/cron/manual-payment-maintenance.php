<?php
/**
 * Manual Payment Maintenance Cron Job
 * 
 * This script should be run periodically (e.g., every hour) to:
 * - Clean up expired payments
 * - Send expiry reminder notifications
 * - Generate security reports
 * 
 * Usage: php manual-payment-maintenance.php
 */

require_once '../config/database.php';
require_once '../utils/manual-payment-security.php';
require_once '../utils/manual-payment-notifications.php';

// Prevent direct web access
if (isset($_SERVER['HTTP_HOST'])) {
    die('This script can only be run from command line');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    echo "Manual Payment Maintenance Started: " . date('Y-m-d H:i:s') . "\n";
    
    // Initialize classes
    $security = new ManualPaymentSecurity();
    $notifications = new ManualPaymentNotifications();
    
    // 1. Clean up expired payments
    echo "Cleaning up expired payments...\n";
    $expiredCount = $security->cleanupExpiredPayments();
    echo "Marked {$expiredCount} payments as expired\n";
    
    // 2. Send expiry reminders (24 hours before expiry)
    echo "Sending expiry reminders...\n";
    $reminderCount = sendExpiryReminders($db);
    echo "Sent {$reminderCount} expiry reminder emails\n";
    
    // 3. Generate security report for high-risk activities
    echo "Generating security report...\n";
    $securityStats = $security->getSecurityStatistics();
    generateSecurityReport($securityStats);
    
    // 4. Clean up old notification records (older than 30 days)
    echo "Cleaning up old notification records...\n";
    $cleanedNotifications = cleanupOldNotifications($db);
    echo "Cleaned up {$cleanedNotifications} old notification records\n";
    
    // 5. Update payment statistics
    echo "Updating payment statistics...\n";
    updatePaymentStatistics($db);
    
    echo "Manual Payment Maintenance Completed: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Send expiry reminders for payments expiring in 24 hours
 */
function sendExpiryReminders($db) {
    $query = "SELECT payment_id FROM manual_payment_transactions 
              WHERE verification_status = 'pending' 
              AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
              AND payment_id NOT IN (
                  SELECT DISTINCT manual_payment_id 
                  FROM manual_payment_notifications 
                  WHERE notification_type = 'reminder' 
                  AND sent_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
              )";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $sentCount = 0;
    foreach ($payments as $paymentId) {
        if (sendManualPaymentNotification($paymentId, 'reminder')) {
            $sentCount++;
        }
    }
    
    return $sentCount;
}

/**
 * Generate security report
 */
function generateSecurityReport($stats) {
    $reportFile = '../logs/security-report-' . date('Y-m-d') . '.log';
    
    $report = "Manual Payment Security Report - " . date('Y-m-d H:i:s') . "\n";
    $report .= "================================================\n\n";
    
    $report .= "Payment Status Summary:\n";
    foreach ($stats['status_counts'] ?? [] as $status => $count) {
        $report .= "  {$status}: {$count}\n";
    }
    
    $report .= "\nHigh-Risk Activities (Last 24h): " . ($stats['high_risk_24h'] ?? 0) . "\n";
    
    $report .= "\nGenerated at: " . date('Y-m-d H:i:s') . "\n";
    
    file_put_contents($reportFile, $report, FILE_APPEND | LOCK_EX);
    echo "Security report saved to: {$reportFile}\n";
}

/**
 * Clean up old notification records
 */
function cleanupOldNotifications($db) {
    $query = "DELETE FROM manual_payment_notifications 
              WHERE sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->rowCount();
}

/**
 * Update payment statistics in system settings
 */
function updatePaymentStatistics($db) {
    try {
        // Get current statistics
        $statsQuery = "SELECT 
            COUNT(*) as total_payments,
            SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN verification_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN payment_status = 'expired' THEN 1 ELSE 0 END) as expired_count,
            SUM(CASE WHEN verification_status = 'approved' THEN amount_usd ELSE 0 END) as total_approved_amount,
            AVG(CASE WHEN verification_status = 'approved' THEN amount_usd ELSE NULL END) as avg_approved_amount
        FROM manual_payment_transactions";
        
        $stmt = $db->prepare($statsQuery);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update system settings
        $settings = [
            'manual_payment_total_count' => $stats['total_payments'],
            'manual_payment_pending_count' => $stats['pending_count'],
            'manual_payment_approved_count' => $stats['approved_count'],
            'manual_payment_rejected_count' => $stats['rejected_count'],
            'manual_payment_expired_count' => $stats['expired_count'],
            'manual_payment_total_approved_amount' => $stats['total_approved_amount'],
            'manual_payment_avg_approved_amount' => $stats['avg_approved_amount']
        ];
        
        foreach ($settings as $key => $value) {
            $updateQuery = "INSERT INTO system_settings (setting_key, setting_value, description) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                $key, 
                $value, 
                'Manual payment statistics (auto-updated)'
            ]);
        }
        
        echo "Updated payment statistics\n";
        
    } catch (Exception $e) {
        echo "Failed to update statistics: " . $e->getMessage() . "\n";
    }
}

/**
 * Send alert for suspicious activities
 */
function sendSuspiciousActivityAlert($db) {
    // Check for unusual patterns in the last hour
    $query = "SELECT COUNT(*) as count FROM security_audit_log 
              WHERE event_type LIKE 'manual_payment_%' 
              AND security_level IN ('high', 'critical')
              AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 5) { // Threshold for alert
        // Send alert email to admin
        $adminEmail = 'security@aureusalliance.com';
        $subject = 'Manual Payment Security Alert';
        $message = "High number of suspicious manual payment activities detected in the last hour: {$result['count']} incidents.";
        
        // This would use your email system
        mail($adminEmail, $subject, $message);
        echo "Security alert sent to admin\n";
    }
}

// Create logs directory if it doesn't exist
$logsDir = '../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

?>
