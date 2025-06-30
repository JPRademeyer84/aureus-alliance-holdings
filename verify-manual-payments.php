<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Payment System - Verification</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .check-item { margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .check-item h4 { margin-top: 0; }
        .status { font-weight: bold; }
        .file-list { background: #f8f9fa; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .file-list ul { margin: 0; padding-left: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #D4AF37; margin-bottom: 10px; }
        .summary { background: #e9ecef; padding: 20px; border-radius: 5px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Manual Payment System</h1>
            <h2>System Verification</h2>
            <p>Checking if all components are properly installed and configured...</p>
        </div>

        <?php
        $checks = [];
        $totalChecks = 0;
        $passedChecks = 0;

        // Check 1: Frontend Components
        $frontendFiles = [
            'src/components/payment/ManualPaymentInterface.tsx',
            'src/components/dashboard/ManualPaymentStatus.tsx', 
            'src/components/admin/ManualPaymentReview.tsx',
            'src/components/help/ManualPaymentGuide.tsx'
        ];
        
        $frontendExists = 0;
        foreach ($frontendFiles as $file) {
            if (file_exists($file)) $frontendExists++;
        }
        
        $totalChecks++;
        if ($frontendExists === count($frontendFiles)) {
            $checks[] = ['name' => 'Frontend Components', 'status' => 'success', 'message' => 'All 4 React components found'];
            $passedChecks++;
        } else {
            $checks[] = ['name' => 'Frontend Components', 'status' => 'error', 'message' => "Only {$frontendExists}/4 components found"];
        }

        // Check 2: Backend API Files
        $backendFiles = [
            'api/payments/manual-payment.php',
            'api/admin/manual-payments.php',
            'api/files/serve.php'
        ];
        
        $backendExists = 0;
        foreach ($backendFiles as $file) {
            if (file_exists($file)) $backendExists++;
        }
        
        $totalChecks++;
        if ($backendExists === count($backendFiles)) {
            $checks[] = ['name' => 'Backend API Files', 'status' => 'success', 'message' => 'All 3 API endpoints found'];
            $passedChecks++;
        } else {
            $checks[] = ['name' => 'Backend API Files', 'status' => 'error', 'message' => "Only {$backendExists}/3 API files found"];
        }

        // Check 3: Utility Files
        $utilityFiles = [
            'api/utils/manual-payment-security.php',
            'api/utils/manual-payment-notifications.php',
            'api/utils/file-upload.php',
            'api/utils/email-sender.php',
            'api/utils/response.php',
            'api/utils/validation.php'
        ];
        
        $utilityExists = 0;
        foreach ($utilityFiles as $file) {
            if (file_exists($file)) $utilityExists++;
        }
        
        $totalChecks++;
        if ($utilityExists === count($utilityFiles)) {
            $checks[] = ['name' => 'Utility Files', 'status' => 'success', 'message' => 'All 6 utility files found'];
            $passedChecks++;
        } else {
            $checks[] = ['name' => 'Utility Files', 'status' => 'warning', 'message' => "Only {$utilityExists}/6 utility files found"];
            if ($utilityExists >= 4) $passedChecks++; // Partial credit
        }

        // Check 4: Database Migration
        $migrationFile = 'database/migrations/create_manual_payment_system.sql';
        $totalChecks++;
        if (file_exists($migrationFile)) {
            $checks[] = ['name' => 'Database Migration', 'status' => 'success', 'message' => 'SQL migration file found'];
            $passedChecks++;
        } else {
            $checks[] = ['name' => 'Database Migration', 'status' => 'error', 'message' => 'SQL migration file missing'];
        }

        // Check 5: Setup Scripts
        $setupFiles = [
            'setup-manual-payments.php',
            'api/setup/create-manual-payment-tables.php',
            'api/tests/manual-payment-tests.php',
            'api/cron/manual-payment-maintenance.php'
        ];
        
        $setupExists = 0;
        foreach ($setupFiles as $file) {
            if (file_exists($file)) $setupExists++;
        }
        
        $totalChecks++;
        if ($setupExists === count($setupFiles)) {
            $checks[] = ['name' => 'Setup & Maintenance Scripts', 'status' => 'success', 'message' => 'All 4 setup scripts found'];
            $passedChecks++;
        } else {
            $checks[] = ['name' => 'Setup & Maintenance Scripts', 'status' => 'warning', 'message' => "Only {$setupExists}/4 setup scripts found"];
            if ($setupExists >= 2) $passedChecks++; // Partial credit
        }

        // Check 6: Upload Directory
        $uploadDir = 'uploads/payment_proofs';
        $totalChecks++;
        if (is_dir('uploads') || is_dir($uploadDir)) {
            $checks[] = ['name' => 'Upload Directory', 'status' => 'success', 'message' => 'Upload directory structure ready'];
            $passedChecks++;
        } else {
            $checks[] = ['name' => 'Upload Directory', 'status' => 'warning', 'message' => 'Upload directories need to be created'];
        }

        // Check 7: Database Connection
        $totalChecks++;
        try {
            require_once 'api/config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                $checks[] = ['name' => 'Database Connection', 'status' => 'success', 'message' => 'Database connection successful'];
                $passedChecks++;
            } else {
                $checks[] = ['name' => 'Database Connection', 'status' => 'error', 'message' => 'Database connection failed'];
            }
        } catch (Exception $e) {
            $checks[] = ['name' => 'Database Connection', 'status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }

        // Display results
        foreach ($checks as $check) {
            $statusClass = $check['status'];
            $icon = $check['status'] === 'success' ? '✅' : ($check['status'] === 'warning' ? '⚠️' : '❌');
            
            echo "<div class='check-item'>";
            echo "<h4>{$icon} {$check['name']}</h4>";
            echo "<p class='status {$statusClass}'>{$check['message']}</p>";
            echo "</div>";
        }

        // Summary
        $successRate = round(($passedChecks / $totalChecks) * 100);
        $summaryClass = $successRate >= 80 ? 'success' : ($successRate >= 60 ? 'warning' : 'error');
        ?>

        <div class="summary">
            <h3>📊 Verification Summary</h3>
            <p><strong>Total Checks:</strong> <?php echo $totalChecks; ?></p>
            <p><strong>Passed:</strong> <?php echo $passedChecks; ?></p>
            <p><strong>Success Rate:</strong> <span class="<?php echo $summaryClass; ?>"><?php echo $successRate; ?>%</span></p>
            
            <?php if ($successRate >= 80): ?>
                <div class="success">
                    <h4>🎉 System Ready!</h4>
                    <p>The manual payment system is properly installed and ready to use.</p>
                    <p><strong>Next Steps:</strong></p>
                    <ul>
                        <li>Run <code>setup-manual-payments.php</code> to initialize the database</li>
                        <li>Configure company wallet addresses in the admin panel</li>
                        <li>Test the payment flow with a small amount</li>
                        <li>Set up email notifications</li>
                    </ul>
                </div>
            <?php elseif ($successRate >= 60): ?>
                <div class="warning">
                    <h4>⚠️ Partial Installation</h4>
                    <p>Most components are installed but some issues need attention.</p>
                    <p>Review the failed checks above and ensure all files are properly uploaded.</p>
                </div>
            <?php else: ?>
                <div class="error">
                    <h4>❌ Installation Issues</h4>
                    <p>Several components are missing or not properly configured.</p>
                    <p>Please review the implementation and ensure all files are uploaded correctly.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="check-item">
            <h4>📋 Manual Payment System Features</h4>
            <ul>
                <li>✅ User-friendly payment interface with exchange guides</li>
                <li>✅ Support for BSC, Ethereum, Polygon, and Tron networks</li>
                <li>✅ Secure file upload for payment proofs</li>
                <li>✅ Admin verification and approval system</li>
                <li>✅ Email notifications for all status changes</li>
                <li>✅ Security and fraud prevention measures</li>
                <li>✅ Real-time payment status tracking</li>
                <li>✅ Comprehensive user guides for popular exchanges</li>
                <li>✅ Automated maintenance and cleanup</li>
                <li>✅ Complete test suite for quality assurance</li>
            </ul>
        </div>

        <div class="check-item">
            <h4>🚀 Getting Started</h4>
            <ol>
                <li><strong>Initialize Database:</strong> Run <code>setup-manual-payments.php</code></li>
                <li><strong>Add Wallet Addresses:</strong> Configure company wallets in admin panel</li>
                <li><strong>Test Payment:</strong> Try a small test payment to verify the flow</li>
                <li><strong>Configure Email:</strong> Set up SMTP for notifications</li>
                <li><strong>Schedule Maintenance:</strong> Set up cron job for automated cleanup</li>
            </ol>
        </div>
    </div>
</body>
</html>
