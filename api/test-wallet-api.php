<?php
require_once 'config/database.php';
require_once 'config/cors.php';
require_once 'utils/WalletSecurity.php';

setCorsHeaders();

try {
    echo "<h2>Testing Wallet API</h2>";
    
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Create tables
    $database->createTables();
    echo "<p>✅ Tables created/verified</p>";
    
    // Check if company_wallets table exists
    $query = "SHOW TABLES LIKE 'company_wallets'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p>✅ company_wallets table exists</p>";
        
        // Check table structure
        $query = "DESCRIBE company_wallets";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ company_wallets table does not exist</p>";
    }
    
    // Test admin user exists
    $query = "SELECT id, username FROM admin_users LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✅ Admin user found: {$admin['username']} (ID: {$admin['id']})</p>";
        
        // Test admin permission verification
        $hasPermission = WalletSecurity::verifyAdminPermissions($db, $admin['id']);
        echo "<p>" . ($hasPermission ? "✅" : "❌") . " Admin permission verification: " . ($hasPermission ? "PASS" : "FAIL") . "</p>";
        
        // Test wallet address validation
        $testAddress = "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7";
        $isValid = WalletSecurity::validateWalletAddress($testAddress, 'ethereum');
        echo "<p>" . ($isValid ? "✅" : "❌") . " Address validation test: " . ($isValid ? "PASS" : "FAIL") . "</p>";
        
        // Test encryption/decryption
        try {
            $salt = WalletSecurity::generateSalt();
            $encrypted = WalletSecurity::hashWalletAddress($testAddress, $salt);
            $decrypted = WalletSecurity::decryptWalletAddress($encrypted, $salt);
            $encryptionWorks = ($decrypted === strtolower($testAddress));
            echo "<p>" . ($encryptionWorks ? "✅" : "❌") . " Encryption/Decryption test: " . ($encryptionWorks ? "PASS" : "FAIL") . "</p>";
            if (!$encryptionWorks) {
                echo "<p>Original: $testAddress</p>";
                echo "<p>Decrypted: $decrypted</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Encryption test failed: " . $e->getMessage() . "</p>";
        }
        
        // Test creating a wallet
        try {
            echo "<h3>Testing Wallet Creation</h3>";
            
            // Check if polygon wallet already exists
            $checkQuery = "SELECT id FROM company_wallets WHERE chain = 'polygon'";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                echo "<p>⚠️ Polygon wallet already exists, deleting for test...</p>";
                $deleteQuery = "DELETE FROM company_wallets WHERE chain = 'polygon'";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute();
            }
            
            $chain = 'polygon';
            $address = '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7';
            $salt = WalletSecurity::generateSalt();
            $hashedAddress = WalletSecurity::hashWalletAddress($address, $salt);
            
            $insertQuery = "INSERT INTO company_wallets (chain, address_hash, salt, created_by) VALUES (?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $success = $insertStmt->execute([$chain, $hashedAddress, $salt, $admin['id']]);
            
            if ($success) {
                echo "<p>✅ Test wallet created successfully</p>";
                
                // Test retrieval
                $selectQuery = "SELECT * FROM company_wallets WHERE chain = 'polygon'";
                $selectStmt = $db->prepare($selectQuery);
                $selectStmt->execute();
                $wallet = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($wallet) {
                    $decryptedAddress = WalletSecurity::decryptWalletAddress($wallet['address_hash'], $wallet['salt']);
                    $maskedAddress = WalletSecurity::maskWalletAddress($decryptedAddress);
                    echo "<p>✅ Wallet retrieved and decrypted: $maskedAddress</p>";
                } else {
                    echo "<p>❌ Failed to retrieve created wallet</p>";
                }
            } else {
                echo "<p>❌ Failed to create test wallet</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Wallet creation test failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ No admin user found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
