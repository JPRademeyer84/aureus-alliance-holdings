<?php
/**
 * Email Sender Utility
 * Handles sending emails for the manual payment system
 */

class EmailSender {
    private $fromEmail;
    private $fromName;
    private $smtpEnabled;
    
    public function __construct() {
        $this->fromEmail = 'noreply@aureusalliance.com';
        $this->fromName = 'Aureus Alliance';
        $this->smtpEnabled = false; // Set to true when SMTP is configured
    }
    
    /**
     * Send email using PHP mail() function or SMTP
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $textContent = null) {
        try {
            // If SMTP is enabled, use PHPMailer or similar
            if ($this->smtpEnabled) {
                return $this->sendSMTPEmail($toEmail, $toName, $subject, $htmlContent, $textContent);
            } else {
                // Use PHP's built-in mail() function
                return $this->sendBasicEmail($toEmail, $toName, $subject, $htmlContent, $textContent);
            }
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using PHP's mail() function
     */
    private function sendBasicEmail($toEmail, $toName, $subject, $htmlContent, $textContent = null) {
        // Prepare headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        $headers[] = "Reply-To: {$this->fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        // Prepare the email body
        $body = $this->wrapEmailContent($htmlContent);
        
        // Send the email
        $success = mail($toEmail, $subject, $body, implode("\r\n", $headers));
        
        if ($success) {
            error_log("Email sent successfully to: {$toEmail}");
        } else {
            error_log("Failed to send email to: {$toEmail}");
        }
        
        return $success;
    }
    
    /**
     * Send email using SMTP (placeholder for future implementation)
     */
    private function sendSMTPEmail($toEmail, $toName, $subject, $htmlContent, $textContent = null) {
        // This would use PHPMailer or similar SMTP library
        // For now, fall back to basic email
        return $this->sendBasicEmail($toEmail, $toName, $subject, $htmlContent, $textContent);
    }
    
    /**
     * Wrap email content in a professional template
     */
    private function wrapEmailContent($content) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Aureus Alliance</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #D4AF37, #F4E4BC); padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .header h1 { color: #000; margin: 0; font-size: 24px; }
                .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 12px 24px; background: #D4AF37; color: #000; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 10px 0; }
                .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
                .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
                .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
                .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏆 Aureus Alliance</h1>
                    <p style='margin: 0; color: #000;'>Premium Investment Platform</p>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Aureus Alliance. All rights reserved.</p>
                    <p>This email was sent regarding your investment account. Please do not reply to this email.</p>
                    <p>If you have questions, please contact our support team.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send test email to verify configuration
     */
    public function sendTestEmail($toEmail) {
        $subject = 'Aureus Alliance - Email System Test';
        $content = "
            <h2>Email System Test</h2>
            <p>This is a test email to verify that the Aureus Alliance email system is working correctly.</p>
            <p><strong>Test Details:</strong></p>
            <ul>
                <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
                <li>To: {$toEmail}</li>
                <li>System: Manual Payment Notifications</li>
            </ul>
            <p>If you received this email, the system is working properly.</p>
        ";
        
        return $this->sendEmail($toEmail, 'Test Recipient', $subject, $content);
    }
    
    /**
     * Validate email address
     */
    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Get email sending statistics (placeholder)
     */
    public function getEmailStats() {
        // This could be implemented to track email sending statistics
        return [
            'total_sent' => 0,
            'success_rate' => 100,
            'last_sent' => null
        ];
    }
}

// Convenience function for quick email sending
function sendQuickEmail($to, $subject, $content) {
    $emailSender = new EmailSender();
    return $emailSender->sendEmail($to, '', $subject, $content);
}

?>
