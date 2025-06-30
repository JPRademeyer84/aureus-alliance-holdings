<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetEmailStatus($db);
            break;
        case 'POST':
            handleSendEmail($db);
            break;
        default:
            throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetEmailStatus($db) {
    try {
        $action = $_GET['action'] ?? 'status';
        
        if ($action === 'status') {
            // Get email service status
            $status = [
                'smtp_configured' => !empty($_ENV['SMTP_HOST']),
                'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@aureusangels.com',
                'templates_available' => getAvailableTemplates(),
                'queue_count' => getEmailQueueCount($db),
                'last_sent' => getLastEmailSent($db)
            ];
            
            echo json_encode([
                'success' => true,
                'status' => $status
            ]);
        }

    } catch (Exception $e) {
        throw new Exception("Failed to get email status: " . $e->getMessage());
    }
}

function handleSendEmail($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['type']) || empty($input['recipient'])) {
            throw new Exception("Email type and recipient are required");
        }
        
        $emailType = $input['type'];
        $recipient = $input['recipient'];
        $data = $input['data'] ?? [];
        $priority = $input['priority'] ?? 'normal';
        
        // Send email based on type
        switch ($emailType) {
            case 'investment_confirmation':
                $result = sendInvestmentConfirmationEmail($db, $recipient, $data);
                break;
            case 'kyc_status_update':
                $result = sendKYCStatusUpdateEmail($db, $recipient, $data);
                break;
            case 'password_reset':
                $result = sendPasswordResetEmail($db, $recipient, $data);
                break;
            case 'commission_notification':
                $result = sendCommissionNotificationEmail($db, $recipient, $data);
                break;
            case 'welcome':
                $result = sendWelcomeEmail($db, $recipient, $data);
                break;
            case 'certificate_ready':
                $result = sendCertificateReadyEmail($db, $recipient, $data);
                break;
            default:
                throw new Exception("Unknown email type: $emailType");
        }
        
        if ($result['success']) {
            // Log successful email
            logEmailSent($db, $emailType, $recipient, $result['message_id'] ?? null);
            
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully',
                'message_id' => $result['message_id'] ?? null
            ]);
        } else {
            throw new Exception($result['error'] ?? 'Failed to send email');
        }

    } catch (Exception $e) {
        // Log failed email
        logEmailFailed($db, $input['type'] ?? 'unknown', $input['recipient'] ?? 'unknown', $e->getMessage());
        
        throw new Exception("Failed to send email: " . $e->getMessage());
    }
}

function sendInvestmentConfirmationEmail($db, $recipient, $data) {
    $subject = "Investment Confirmation - Aureus Angel Alliance";
    $template = getEmailTemplate('investment_confirmation');
    
    $variables = [
        'username' => $data['username'] ?? 'Valued Investor',
        'package_name' => $data['package_name'] ?? 'Investment Package',
        'amount' => $data['amount'] ?? '0',
        'shares' => $data['shares'] ?? '0',
        'investment_date' => $data['investment_date'] ?? date('Y-m-d'),
        'nft_delivery_date' => $data['nft_delivery_date'] ?? date('Y-m-d', strtotime('+180 days')),
        'roi_delivery_date' => $data['roi_delivery_date'] ?? date('Y-m-d', strtotime('+180 days'))
    ];
    
    $html = renderEmailTemplate($template, $variables);
    
    return sendEmailSMTP($recipient, $subject, $html);
}

function sendKYCStatusUpdateEmail($db, $recipient, $data) {
    $status = $data['status'] ?? 'updated';
    $subject = "KYC Status Update - Aureus Angel Alliance";
    $template = getEmailTemplate('kyc_status_update');
    
    $variables = [
        'username' => $data['username'] ?? 'User',
        'status' => $status,
        'status_message' => getKYCStatusMessage($status),
        'next_steps' => getKYCNextSteps($status),
        'verification_level' => $data['verification_level'] ?? 'Level 1'
    ];
    
    $html = renderEmailTemplate($template, $variables);
    
    return sendEmailSMTP($recipient, $subject, $html);
}

function sendPasswordResetEmail($db, $recipient, $data) {
    $subject = "Password Reset Request - Aureus Angel Alliance";
    $template = getEmailTemplate('password_reset');
    
    $variables = [
        'username' => $data['username'] ?? 'User',
        'reset_link' => $data['reset_link'] ?? '#',
        'expiry_time' => $data['expiry_time'] ?? '1 hour',
        'ip_address' => $data['ip_address'] ?? 'Unknown'
    ];
    
    $html = renderEmailTemplate($template, $variables);
    
    return sendEmailSMTP($recipient, $subject, $html);
}

function sendCommissionNotificationEmail($db, $recipient, $data) {
    $subject = "Commission Earned - Aureus Angel Alliance";
    $template = getEmailTemplate('commission_notification');
    
    $variables = [
        'username' => $data['username'] ?? 'User',
        'commission_amount' => $data['commission_amount'] ?? '0',
        'nft_bonus' => $data['nft_bonus'] ?? '0',
        'referred_username' => $data['referred_username'] ?? 'New User',
        'commission_level' => $data['commission_level'] ?? '1',
        'total_earnings' => $data['total_earnings'] ?? '0'
    ];
    
    $html = renderEmailTemplate($template, $variables);
    
    return sendEmailSMTP($recipient, $subject, $html);
}

function sendWelcomeEmail($db, $recipient, $data) {
    $subject = "Welcome to Aureus Angel Alliance!";
    $template = getEmailTemplate('welcome');
    
    $variables = [
        'username' => $data['username'] ?? 'New Member',
        'referral_link' => $data['referral_link'] ?? '#',
        'dashboard_link' => 'https://aureusangels.com/dashboard',
        'support_email' => 'support@aureusangels.com'
    ];
    
    $html = renderEmailTemplate($template, $variables);
    
    return sendEmailSMTP($recipient, $subject, $html);
}

function sendCertificateReadyEmail($db, $recipient, $data) {
    $subject = "Your Share Certificate is Ready - Aureus Angel Alliance";
    $template = getEmailTemplate('certificate_ready');
    
    $variables = [
        'username' => $data['username'] ?? 'User',
        'certificate_number' => $data['certificate_number'] ?? 'N/A',
        'share_quantity' => $data['share_quantity'] ?? '0',
        'download_link' => $data['download_link'] ?? '#'
    ];
    
    $html = renderEmailTemplate($template, $variables);
    
    return sendEmailSMTP($recipient, $subject, $html);
}

function sendEmailSMTP($to, $subject, $html) {
    try {
        // Use PHPMailer if available, otherwise fall back to mail()
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return sendEmailPHPMailer($to, $subject, $html);
        } else {
            return sendEmailBasic($to, $subject, $html);
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function sendEmailBasic($to, $subject, $html) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . ($_ENV['FROM_NAME'] ?? 'Aureus Angel Alliance') . ' <' . ($_ENV['FROM_EMAIL'] ?? 'noreply@aureusangels.com') . '>',
        'Reply-To: ' . ($_ENV['FROM_EMAIL'] ?? 'noreply@aureusangels.com'),
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $success = mail($to, $subject, $html, implode("\r\n", $headers));
    
    return [
        'success' => $success,
        'message_id' => $success ? uniqid('email_') : null,
        'error' => $success ? null : 'Failed to send email using mail() function'
    ];
}

function getEmailTemplate($templateName) {
    $templatePath = __DIR__ . "/../templates/email/{$templateName}.html";
    
    if (file_exists($templatePath)) {
        return file_get_contents($templatePath);
    }
    
    // Return basic template if specific template not found
    return getBasicEmailTemplate();
}

function getBasicEmailTemplate() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{subject}}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: #FFD700; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .footer { background: #333; color: #fff; padding: 20px; text-align: center; font-size: 12px; }
            .button { display: inline-block; background: #FFD700; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Aureus Angel Alliance</h1>
                <p>Premium Digital Gold Investment Platform</p>
            </div>
            <div class="content">
                {{content}}
            </div>
            <div class="footer">
                <p>&copy; 2024 Aureus Angel Alliance. All rights reserved.</p>
                <p>This email was sent to {{recipient}}. If you did not expect this email, please contact support.</p>
            </div>
        </div>
    </body>
    </html>';
}

function renderEmailTemplate($template, $variables) {
    $html = $template;
    
    foreach ($variables as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }
    
    return $html;
}

function getKYCStatusMessage($status) {
    switch ($status) {
        case 'approved':
            return 'Your KYC verification has been approved! You now have full access to all platform features.';
        case 'rejected':
            return 'Your KYC verification was not approved. Please review the requirements and submit new documents.';
        case 'pending':
            return 'Your KYC documents are being reviewed. This process typically takes 24-48 hours.';
        case 'incomplete':
            return 'Your KYC verification is incomplete. Please upload the required documents to continue.';
        default:
            return 'Your KYC status has been updated. Please check your dashboard for details.';
    }
}

function getKYCNextSteps($status) {
    switch ($status) {
        case 'approved':
            return 'You can now make investments and access all premium features.';
        case 'rejected':
            return 'Please upload new, clear documents that meet our verification requirements.';
        case 'pending':
            return 'No action required. We will notify you once the review is complete.';
        case 'incomplete':
            return 'Please log in to your dashboard and complete the KYC verification process.';
        default:
            return 'Please check your dashboard for more information.';
    }
}

function getAvailableTemplates() {
    return [
        'investment_confirmation',
        'kyc_status_update',
        'password_reset',
        'commission_notification',
        'welcome',
        'certificate_ready'
    ];
}

function getEmailQueueCount($db) {
    try {
        $query = "SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getLastEmailSent($db) {
    try {
        $query = "SELECT created_at FROM email_log ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['created_at'] ?? null;
    } catch (Exception $e) {
        return null;
    }
}

function logEmailSent($db, $type, $recipient, $messageId) {
    try {
        $query = "INSERT INTO email_log (
            type, recipient, message_id, status, created_at
        ) VALUES (?, ?, ?, 'sent', NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$type, $recipient, $messageId]);
    } catch (Exception $e) {
        error_log("Failed to log email sent: " . $e->getMessage());
    }
}

function logEmailFailed($db, $type, $recipient, $error) {
    try {
        $query = "INSERT INTO email_log (
            type, recipient, status, error_message, created_at
        ) VALUES (?, ?, 'failed', ?, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$type, $recipient, $error]);
    } catch (Exception $e) {
        error_log("Failed to log email failure: " . $e->getMessage());
    }
}

// Create email tables if they don't exist
function createEmailTables($db) {
    $emailLogTable = "CREATE TABLE IF NOT EXISTS email_log (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        type VARCHAR(50) NOT NULL,
        recipient VARCHAR(255) NOT NULL,
        message_id VARCHAR(100) NULL,
        status ENUM('sent', 'failed', 'bounced') NOT NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_type (type),
        INDEX idx_recipient (recipient),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    $emailQueueTable = "CREATE TABLE IF NOT EXISTS email_queue (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        type VARCHAR(50) NOT NULL,
        recipient VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        html_content TEXT NOT NULL,
        priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
        status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 3,
        scheduled_at TIMESTAMP NULL,
        processed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_scheduled_at (scheduled_at),
        INDEX idx_created_at (created_at)
    )";
    
    try {
        $db->exec($emailLogTable);
        $db->exec($emailQueueTable);
    } catch (PDOException $e) {
        error_log("Email tables creation: " . $e->getMessage());
    }
}

// Initialize tables
createEmailTables($db);
?>
