<?php
// DIRECT DATABASE CHECK - NO AUTHENTICATION BULLSHIT
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Try multiple database configurations
    $configs = [
        ['host' => 'localhost', 'port' => '3306', 'dbname' => 'aureus_angels', 'user' => 'root', 'pass' => ''],
        ['host' => 'localhost', 'port' => '3506', 'dbname' => 'aureus_angels', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'port' => '3306', 'dbname' => 'aureus_angels', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'port' => '3506', 'dbname' => 'aureus_angels', 'user' => 'root', 'pass' => '']
    ];

    $pdo = null;
    $usedConfig = null;

    foreach ($configs as $config) {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $usedConfig = $config;
            break;
        } catch (Exception $e) {
            continue; // Try next config
        }
    }

    if (!$pdo) {
        throw new Exception("Could not connect with any database configuration");
    }
    $result = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'database_connected' => true,
        'connection_config' => $usedConfig
    ];
    
    // Check if aureus_investments table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'aureus_investments'")->fetchAll();
    $result['table_exists'] = count($tables) > 0;
    
    if ($result['table_exists']) {
        // Get ALL investment records
        $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
        $investments = $stmt->fetchAll();
        
        $result['total_investments'] = count($investments);
        $result['investments'] = $investments;
        
        // Get table structure
        $structure = $pdo->query("DESCRIBE aureus_investments")->fetchAll();
        $result['table_structure'] = $structure;
        
    } else {
        // Check for alternative table names
        $altTables = ['investments', 'user_investments', 'participations'];
        foreach ($altTables as $table) {
            $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
            if (count($check) > 0) {
                $stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10");
                $data = $stmt->fetchAll();
                $result['alternative_tables'][$table] = [
                    'exists' => true,
                    'count' => count($data),
                    'data' => $data
                ];
            }
        }
    }
    
    // Check users table
    $userTables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($userTables) > 0) {
        $users = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
        $result['users'] = $users;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
