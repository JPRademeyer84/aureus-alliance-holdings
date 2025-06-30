<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wallet Database Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Wallet Database Debug</h1>

    <div class="section">
        <h2>Database Connection Test</h2>
        <?php
        try {
            $pdo = new PDO(
                'mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4',
                'root',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo '<p class="success">✅ Database connection successful!</p>';
            echo '<p>Connected to: aureus_angels on port 3506</p>';
        } catch (Exception $e) {
            echo '<p class="error">❌ Database connection failed: ' . $e->getMessage() . '</p>';
            exit;
        }
        ?>
    </div>

    <div class="section">
        <h2>Check if investment_wallets table exists</h2>
        <?php
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'investment_wallets'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo '<p class="success">✅ investment_wallets table exists</p>';
            } else {
                echo '<p class="error">❌ investment_wallets table does not exist</p>';
                
                // Show all tables
                echo '<h3>Available tables:</h3>';
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo '<ul>';
                foreach ($tables as $table) {
                    echo '<li>' . htmlspecialchars($table) . '</li>';
                }
                echo '</ul>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error checking table: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Wallet Data</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM investment_wallets ORDER BY id ASC");
            $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($wallets) > 0) {
                echo '<p class="success">✅ Found ' . count($wallets) . ' wallet(s)</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Chain</th><th>Address</th><th>Is Active</th><th>Created At</th><th>Updated At</th></tr>';
                foreach ($wallets as $wallet) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($wallet['id'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($wallet['chain'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($wallet['address'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($wallet['is_active'] ? 'Yes' : 'No') . '</td>';
                    echo '<td>' . htmlspecialchars($wallet['created_at'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($wallet['updated_at'] ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="error">❌ No wallets found in database</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error fetching wallets: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Table Structure</h2>
        <?php
        try {
            $stmt = $pdo->query("DESCRIBE investment_wallets");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } catch (Exception $e) {
            echo '<p class="error">❌ Error getting table structure: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>API Test</h2>
        <p>Test the simple-wallets.php API:</p>
        <a href="api/simple-wallets.php" target="_blank">Open API Endpoint</a>
    </div>

</body>
</html>
