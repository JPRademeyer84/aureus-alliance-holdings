<?php
session_start();

// Set admin session for testing
$_SESSION['admin_id'] = 'admin-test-123';
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_role'] = 'admin';

echo "Admin session set:\n";
echo "Admin ID: " . $_SESSION['admin_id'] . "\n";
echo "Admin Username: " . $_SESSION['admin_username'] . "\n";
echo "Admin Role: " . $_SESSION['admin_role'] . "\n\n";

// Now test the Enhanced KYC Management API
echo "Testing Enhanced KYC Management API with admin session...\n";

// Include the API file
ob_start();
try {
    $_GET['action'] = 'get_users';
    include __DIR__ . '/../admin/enhanced-kyc-management.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "API Output:\n";
echo $output . "\n";
?>
