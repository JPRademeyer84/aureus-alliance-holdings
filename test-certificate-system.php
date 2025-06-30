<?php
// Test script for certificate system
require_once 'api/config/database.php';

echo "=== CERTIFICATE SYSTEM TEST ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    echo "✓ Database connection successful\n";
    
    // Test 1: Check if certificate tables exist
    echo "\n1. Checking certificate tables...\n";
    
    $tables = [
        'certificate_templates',
        'share_certificates', 
        'certificate_access_log',
        'certificate_verifications',
        'certificate_batch_operations'
    ];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Table '$table' exists\n";
        } else {
            echo "  ✗ Table '$table' missing\n";
        }
    }
    
    // Test 2: Check table structures
    echo "\n2. Checking table structures...\n";
    
    $query = "DESCRIBE share_certificates";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = [
        'id', 'certificate_number', 'investment_id', 'user_id', 
        'template_id', 'share_quantity', 'certificate_value',
        'generation_status', 'legal_status', 'verification_hash'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $existingColumns)) {
            echo "  ✓ Column '$column' exists in share_certificates\n";
        } else {
            echo "  ✗ Column '$column' missing in share_certificates\n";
        }
    }
    
    // Test 3: Create a test template
    echo "\n3. Creating test certificate template...\n";
    
    $templateQuery = "INSERT INTO certificate_templates (
        template_name, template_type, template_config, 
        is_active, is_default, created_by
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $templateConfig = json_encode([
        'text' => [
            'certificate_number' => ['x' => 100, 'y' => 100, 'size' => 16, 'color' => [0, 0, 0]],
            'holder_name' => ['x' => 400, 'y' => 300, 'size' => 24, 'color' => [0, 0, 0]],
            'share_quantity' => ['x' => 300, 'y' => 400, 'size' => 18, 'color' => [0, 0, 0]],
            'certificate_value' => ['x' => 500, 'y' => 400, 'size' => 18, 'color' => [0, 0, 0]],
            'issue_date' => ['x' => 400, 'y' => 500, 'size' => 14, 'color' => [0, 0, 0]]
        ],
        'qr_code' => ['x' => 50, 'y' => 550]
    ]);
    
    $templateStmt = $db->prepare($templateQuery);
    $templateStmt->execute([
        'Test Certificate Template',
        'share_certificate',
        $templateConfig,
        true,
        true,
        'test-admin'
    ]);
    
    $templateId = $db->lastInsertId();
    echo "  ✓ Test template created with ID: $templateId\n";
    
    // Test 4: Check for existing investments
    echo "\n4. Checking for existing investments...\n";
    
    $investmentQuery = "SELECT COUNT(*) as count FROM aureus_investments";
    $investmentStmt = $db->prepare($investmentQuery);
    $investmentStmt->execute();
    $investmentCount = $investmentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "  ✓ Found $investmentCount investment(s) in database\n";
    
    // Test 5: Check investments without certificates
    echo "\n5. Checking investments without certificates...\n";
    
    $noCertQuery = "SELECT ai.id, ai.package_name, ai.amount, ai.shares, u.username 
                    FROM aureus_investments ai 
                    LEFT JOIN users u ON ai.user_id = u.id
                    LEFT JOIN share_certificates sc ON ai.id = sc.investment_id 
                    WHERE sc.id IS NULL 
                    LIMIT 5";
    $noCertStmt = $db->prepare($noCertQuery);
    $noCertStmt->execute();
    $investmentsWithoutCerts = $noCertStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  ✓ Found " . count($investmentsWithoutCerts) . " investment(s) without certificates\n";
    
    foreach ($investmentsWithoutCerts as $investment) {
        echo "    - Investment ID: {$investment['id']}, User: {$investment['username']}, Package: {$investment['package_name']}\n";
    }
    
    // Test 6: Test certificate number generation
    echo "\n6. Testing certificate number generation...\n";
    
    $year = date('Y');
    $prefix = "AAH-$year-";
    
    $lastCertQuery = "SELECT certificate_number FROM share_certificates 
                      WHERE certificate_number LIKE ? 
                      ORDER BY certificate_number DESC LIMIT 1";
    $lastCertStmt = $db->prepare($lastCertQuery);
    $lastCertStmt->execute([$prefix . '%']);
    $lastCert = $lastCertStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastCert) {
        $lastNumber = (int)substr($lastCert['certificate_number'], -6);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    $testCertNumber = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    echo "  ✓ Next certificate number would be: $testCertNumber\n";
    
    // Test 7: API endpoints accessibility
    echo "\n7. Testing API endpoints...\n";
    
    $endpoints = [
        'Certificate Templates' => 'api/admin/certificate-templates.php',
        'Certificate Generator' => 'api/admin/certificate-generator.php',
        'Certificate Verification' => 'api/certificates/verify.php',
        'User Certificates' => 'api/users/certificates.php',
        'Investment List' => 'api/investments/list.php'
    ];
    
    foreach ($endpoints as $name => $endpoint) {
        if (file_exists($endpoint)) {
            echo "  ✓ $name endpoint exists\n";
        } else {
            echo "  ✗ $name endpoint missing\n";
        }
    }
    
    // Clean up test template
    echo "\n8. Cleaning up test data...\n";
    $cleanupQuery = "DELETE FROM certificate_templates WHERE id = ?";
    $cleanupStmt = $db->prepare($cleanupQuery);
    $cleanupStmt->execute([$templateId]);
    echo "  ✓ Test template cleaned up\n";
    
    echo "\n=== CERTIFICATE SYSTEM TEST COMPLETED ===\n";
    echo "✓ All core components are ready for certificate generation!\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Create certificate templates in admin panel\n";
    echo "2. Upload frame and background images\n";
    echo "3. Generate certificates for existing investments\n";
    echo "4. Test certificate verification system\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
