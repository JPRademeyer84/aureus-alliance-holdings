<?php
require_once '../config/database.php';
require_once 'email-sender.php';

/**
 * Manual Payment Notification System
 * Handles email notifications for manual payment status changes
 */

class ManualPaymentNotifications {
    private $db;
    private $emailSender;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->emailSender = new EmailSender();
    }
    
    /**
     * Send notification when manual payment is submitted
     */
    public function sendPaymentSubmittedNotification($paymentId) {
        try {
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            $subject = "Manual Payment Submitted - Payment #{$payment['payment_id']}";
            
            $emailContent = $this->generateSubmittedEmailTemplate($payment);
            
            $sent = $this->emailSender->sendEmail(
                $payment['email'],
                $payment['username'],
                $subject,
                $emailContent
            );
            
            if ($sent) {
                $this->logNotification($payment['id'], 'submitted', $payment['email']);
                
                // Also notify admin
                $this->sendAdminNotification($payment, 'new_submission');
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('Failed to send payment submitted notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification when payment is approved
     */
    public function sendPaymentApprovedNotification($paymentId) {
        try {
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            $subject = "Payment Approved - Investment Activated!";
            
            $emailContent = $this->generateApprovedEmailTemplate($payment);
            
            $sent = $this->emailSender->sendEmail(
                $payment['email'],
                $payment['username'],
                $subject,
                $emailContent
            );
            
            if ($sent) {
                $this->logNotification($payment['id'], 'approved', $payment['email']);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('Failed to send payment approved notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification when payment is rejected
     */
    public function sendPaymentRejectedNotification($paymentId, $reason = '') {
        try {
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            $subject = "Payment Review Update - Action Required";
            
            $emailContent = $this->generateRejectedEmailTemplate($payment, $reason);
            
            $sent = $this->emailSender->sendEmail(
                $payment['email'],
                $payment['username'],
                $subject,
                $emailContent
            );
            
            if ($sent) {
                $this->logNotification($payment['id'], 'rejected', $payment['email']);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('Failed to send payment rejected notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send expiry reminder notification
     */
    public function sendExpiryReminderNotification($paymentId) {
        try {
            $payment = $this->getPaymentDetails($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            $subject = "Payment Verification Expires Soon - Action Required";
            
            $emailContent = $this->generateExpiryReminderTemplate($payment);
            
            $sent = $this->emailSender->sendEmail(
                $payment['email'],
                $payment['username'],
                $subject,
                $emailContent
            );
            
            if ($sent) {
                $this->logNotification($payment['id'], 'reminder', $payment['email']);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('Failed to send expiry reminder notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment details with user information
     */
    private function getPaymentDetails($paymentId) {
        $query = "SELECT mpt.*, u.username, u.email 
                  FROM manual_payment_transactions mpt 
                  JOIN users u ON mpt.user_id = u.id 
                  WHERE mpt.payment_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$paymentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log notification in database
     */
    private function logNotification($paymentId, $type, $email) {
        $query = "INSERT INTO manual_payment_notifications 
                  (manual_payment_id, notification_type, recipient_email, email_status) 
                  VALUES (?, ?, ?, 'sent')";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$paymentId, $type, $email]);
    }
    
    /**
     * Send admin notification
     */
    private function sendAdminNotification($payment, $type) {
        $adminEmail = 'admin@aureusalliance.com'; // This should come from settings
        
        $subject = "New Manual Payment Submission - Review Required";
        $content = "A new manual payment has been submitted for review.\n\n";
        $content .= "Payment ID: {$payment['payment_id']}\n";
        $content .= "User: {$payment['username']} ({$payment['email']})\n";
        $content .= "Amount: \${$payment['amount_usd']} USDT\n";
        $content .= "Network: {$payment['chain']}\n";
        $content .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "Please review this payment in the admin dashboard.";
        
        $this->emailSender->sendEmail($adminEmail, 'Admin', $subject, $content);
    }
    
    /**
     * Generate email template for submitted payment
     */
    private function generateSubmittedEmailTemplate($payment) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #D4AF37;'>Payment Submitted Successfully</h2>
            
            <p>Dear {$payment['username']},</p>
            
            <p>We have received your manual payment submission and it is now under review.</p>
            
            <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3>Payment Details:</h3>
                <p><strong>Payment ID:</strong> {$payment['payment_id']}</p>
                <p><strong>Amount:</strong> \${$payment['amount_usd']} USDT</p>
                <p><strong>Network:</strong> {$payment['chain']}</p>
                <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s', strtotime($payment['created_at'])) . "</p>
            </div>
            
            <h3>What happens next?</h3>
            <ul>
                <li>Our team will verify your payment within 24 hours</li>
                <li>You'll receive an email confirmation once approved</li>
                <li>Your investment packages will be activated automatically</li>
                <li>You can track the status in your dashboard</li>
            </ul>
            
            <p>If you have any questions, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>The Aureus Alliance Team</p>
        </div>
        ";
    }
    
    /**
     * Generate email template for approved payment
     */
    private function generateApprovedEmailTemplate($payment) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #28a745;'>Payment Approved - Investment Activated!</h2>
            
            <p>Dear {$payment['username']},</p>
            
            <p>Great news! Your manual payment has been verified and approved. Your investment has been activated.</p>
            
            <div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;'>
                <h3>Investment Details:</h3>
                <p><strong>Payment ID:</strong> {$payment['payment_id']}</p>
                <p><strong>Amount:</strong> \${$payment['amount_usd']} USDT</p>
                <p><strong>Status:</strong> Active</p>
                <p><strong>Approved:</strong> " . date('Y-m-d H:i:s') . "</p>
            </div>
            
            <p>You can now view your active investments in your dashboard and start earning returns.</p>
            
            <p>Thank you for choosing Aureus Alliance!</p>
            
            <p>Best regards,<br>The Aureus Alliance Team</p>
        </div>
        ";
    }
    
    /**
     * Generate email template for rejected payment
     */
    private function generateRejectedEmailTemplate($payment, $reason) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #dc3545;'>Payment Review Update</h2>
            
            <p>Dear {$payment['username']},</p>
            
            <p>We have completed the review of your manual payment submission. Unfortunately, we were unable to verify your payment at this time.</p>
            
            <div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #f5c6cb;'>
                <h3>Payment Details:</h3>
                <p><strong>Payment ID:</strong> {$payment['payment_id']}</p>
                <p><strong>Amount:</strong> \${$payment['amount_usd']} USDT</p>
                <p><strong>Status:</strong> Requires Attention</p>
                " . ($reason ? "<p><strong>Reason:</strong> {$reason}</p>" : "") . "
            </div>
            
            <h3>Next Steps:</h3>
            <ul>
                <li>Please contact our support team for assistance</li>
                <li>You may need to provide additional documentation</li>
                <li>Our team will help resolve any issues</li>
            </ul>
            
            <p>Please don't hesitate to reach out to our support team - we're here to help!</p>
            
            <p>Best regards,<br>The Aureus Alliance Team</p>
        </div>
        ";
    }
    
    /**
     * Generate email template for expiry reminder
     */
    private function generateExpiryReminderTemplate($payment) {
        $expiryDate = date('Y-m-d H:i:s', strtotime($payment['expires_at']));
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #ffc107;'>Payment Verification Expires Soon</h2>
            
            <p>Dear {$payment['username']},</p>
            
            <p>This is a reminder that your manual payment verification period will expire soon.</p>
            
            <div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;'>
                <h3>Payment Details:</h3>
                <p><strong>Payment ID:</strong> {$payment['payment_id']}</p>
                <p><strong>Amount:</strong> \${$payment['amount_usd']} USDT</p>
                <p><strong>Expires:</strong> {$expiryDate}</p>
            </div>
            
            <p>If you need assistance with your payment verification, please contact our support team immediately.</p>
            
            <p>Best regards,<br>The Aureus Alliance Team</p>
        </div>
        ";
    }
}

// Convenience functions for easy use
function sendManualPaymentNotification($paymentId, $type, $reason = '') {
    $notifications = new ManualPaymentNotifications();
    
    switch ($type) {
        case 'submitted':
            return $notifications->sendPaymentSubmittedNotification($paymentId);
        case 'approved':
            return $notifications->sendPaymentApprovedNotification($paymentId);
        case 'rejected':
            return $notifications->sendPaymentRejectedNotification($paymentId, $reason);
        case 'reminder':
            return $notifications->sendExpiryReminderNotification($paymentId);
        default:
            return false;
    }
}

?>
