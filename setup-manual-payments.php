<?php
/**
 * Manual Payment System Setup Script
 * 
 * This script sets up the complete manual payment system including:
 * - Database tables
 * - Upload directories
 * - Default settings
 * - Test data (optional)
 * 
 * Run this script once to initialize the manual payment system
 */

require_once 'api/config/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Payment System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .step h3 { margin-top: 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Manual Payment System Setup</h1>
    <p>This setup will initialize the manual payment system for Aureus Alliance.</p>

    <?php
    $setupSteps = [];
    $hasErrors = false;

    // Step 1: Check database connection
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $setupSteps[] = ['step' => 'Database Connection', 'status' => 'success', 'message' => 'Connected successfully'];
        } else {
            throw new Exception('Connection failed');
        }
    } catch (Exception $e) {
        $setupSteps[] = ['step' => 'Database Connection', 'status' => 'error', 'message' => 'Failed: ' . $e->getMessage()];
        $hasErrors = true;
    }

    // Step 2: Create database tables
    if (!$hasErrors) {
        try {
            $sqlFile = 'database/migrations/create_manual_payment_system.sql';
            
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                
                // Split and execute SQL statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) {
                        return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
                    }
                );
                
                $executedCount = 0;
                foreach ($statements as $statement) {
                    if (empty(trim($statement))) continue;
                    
                    try {
                        $db->exec($statement);
                        $executedCount++;
                    } catch (PDOException $e) {
                        // Skip if table already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
                
                $setupSteps[] = ['step' => 'Database Tables', 'status' => 'success', 'message' => "Created/verified {$executedCount} database objects"];
            } else {
                throw new Exception('SQL migration file not found');
            }
        } catch (Exception $e) {
            $setupSteps[] = ['step' => 'Database Tables', 'status' => 'error', 'message' => 'Failed: ' . $e->getMessage()];
            $hasErrors = true;
        }
    }

    // Step 3: Create upload directories
    if (!$hasErrors) {
        try {
            $uploadDirs = [
                'uploads/',
                'uploads/payment_proofs/',
                'uploads/payment_proofs/' . date('Y/'),
                'uploads/payment_proofs/' . date('Y/m/'),
                'uploads/payment_proofs/' . date('Y/m/d/')
            ];
            
            $createdDirs = 0;
            foreach ($uploadDirs as $dir) {
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0755, true)) {
                        $createdDirs++;
                    }
                } else {
                    $createdDirs++;
                }
            }
            
            // Create .htaccess for security
            $htaccessPath = 'uploads/.htaccess';
            if (!file_exists($htaccessPath)) {
                $htaccessContent = "# Deny direct access to uploaded files\n";
                $htaccessContent .= "Options -Indexes\n";
                $htaccessContent .= "<Files ~ \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">\n";
                $htaccessContent .= "    deny from all\n";
                $htaccessContent .= "</Files>\n";
                file_put_contents($htaccessPath, $htaccessContent);
            }
            
            $setupSteps[] = ['step' => 'Upload Directories', 'status' => 'success', 'message' => "Created/verified {$createdDirs} directories"];
        } catch (Exception $e) {
            $setupSteps[] = ['step' => 'Upload Directories', 'status' => 'error', 'message' => 'Failed: ' . $e->getMessage()];
            $hasErrors = true;
        }
    }

    // Step 4: Insert default settings
    if (!$hasErrors) {
        try {
            $defaultSettings = [
                ['manual_payment_enabled', 'true', 'Enable manual payment system'],
                ['manual_payment_expiry_days', '7', 'Days before manual payment expires'],
                ['manual_payment_max_amount', '100000', 'Maximum amount for manual payments in USD'],
                ['manual_payment_min_amount', '10', 'Minimum amount for manual payments in USD'],
                ['manual_payment_notification_email', 'payments@aureusalliance.com', 'Email for manual payment notifications']
            ];
            
            $insertedSettings = 0;
            foreach ($defaultSettings as $setting) {
                $query = "INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt->execute($setting)) {
                    $insertedSettings++;
                }
            }
            
            $setupSteps[] = ['step' => 'Default Settings', 'status' => 'success', 'message' => "Inserted/verified {$insertedSettings} settings"];
        } catch (Exception $e) {
            $setupSteps[] = ['step' => 'Default Settings', 'status' => 'warning', 'message' => 'Partial success: ' . $e->getMessage()];
        }
    }

    // Step 5: Verify system components
    if (!$hasErrors) {
        $components = [
            'api/payments/manual-payment.php' => 'Manual Payment API',
            'api/admin/manual-payments.php' => 'Admin API',
            'api/files/serve.php' => 'File Serving API',
            'api/utils/manual-payment-security.php' => 'Security System',
            'api/utils/manual-payment-notifications.php' => 'Notification System',
            'src/components/payment/ManualPaymentInterface.tsx' => 'Frontend Interface',
            'src/components/admin/ManualPaymentReview.tsx' => 'Admin Interface'
        ];
        
        $missingComponents = [];
        foreach ($components as $file => $name) {
            if (!file_exists($file)) {
                $missingComponents[] = $name;
            }
        }
        
        if (empty($missingComponents)) {
            $setupSteps[] = ['step' => 'System Components', 'status' => 'success', 'message' => 'All components verified'];
        } else {
            $setupSteps[] = ['step' => 'System Components', 'status' => 'warning', 'message' => 'Missing: ' . implode(', ', $missingComponents)];
        }
    }

    // Display results
    foreach ($setupSteps as $step) {
        $statusClass = $step['status'];
        echo "<div class='step'>";
        echo "<h3 class='{$statusClass}'>{$step['step']}</h3>";
        echo "<p class='{$statusClass}'>{$step['message']}</p>";
        echo "</div>";
    }

    if (!$hasErrors) {
        echo "<div class='step' style='background-color: #d4edda; border-color: #c3e6cb;'>";
        echo "<h3 class='success'>✅ Setup Complete!</h3>";
        echo "<p>The manual payment system has been successfully set up. Here's what you can do next:</p>";
        echo "<ul>";
        echo "<li><strong>Test the system:</strong> Run <code>php api/tests/manual-payment-tests.php</code></li>";
        echo "<li><strong>Configure company wallets:</strong> Add your wallet addresses in the admin panel</li>";
        echo "<li><strong>Set up cron job:</strong> Schedule <code>api/cron/manual-payment-maintenance.php</code> to run hourly</li>";
        echo "<li><strong>Customize settings:</strong> Adjust limits and notifications in system settings</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<h3>Next Steps</h3>";
        echo "<ol>";
        echo "<li><strong>Add Company Wallets:</strong> Go to Admin → Wallets and add your company wallet addresses for each blockchain network</li>";
        echo "<li><strong>Test Payment Flow:</strong> Create a test investment and try the manual payment option</li>";
        echo "<li><strong>Configure Email:</strong> Set up email notifications in your email configuration</li>";
        echo "<li><strong>Set Up Monitoring:</strong> Schedule the maintenance cron job for automated cleanup</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='step' style='background-color: #f8d7da; border-color: #f5c6cb;'>";
        echo "<h3 class='error'>❌ Setup Failed</h3>";
        echo "<p>There were errors during setup. Please resolve the issues above and try again.</p>";
        echo "</div>";
    }
    ?>

    <div class="step">
        <h3>Manual Payment System Features</h3>
        <ul>
            <li>✅ User-friendly payment interface with step-by-step instructions</li>
            <li>✅ Support for multiple blockchain networks (BSC, Ethereum, Polygon, Tron)</li>
            <li>✅ Secure file upload for payment proofs</li>
            <li>✅ Admin verification interface</li>
            <li>✅ Automated email notifications</li>
            <li>✅ Security and fraud prevention</li>
            <li>✅ Payment status tracking</li>
            <li>✅ Comprehensive user guides</li>
        </ul>
    </div>

    <div class="step">
        <h3>Support</h3>
        <p>If you need assistance with the manual payment system:</p>
        <ul>
            <li>Check the user guide at <code>src/components/help/ManualPaymentGuide.tsx</code></li>
            <li>Review the API documentation in the source files</li>
            <li>Run the test suite to verify functionality</li>
            <li>Contact technical support for additional help</li>
        </ul>
    </div>

</body>
</html>
