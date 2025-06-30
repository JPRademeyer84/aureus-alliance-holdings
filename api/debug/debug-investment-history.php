<?php
// Simple debug script to avoid 503 errors
header('Content-Type: application/json');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'simple_test' => 'Debug script is working',
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
];

// Test basic functionality first
try {
    // Test if we can include the config files
    if (file_exists('../config/database.php')) {
        $debug['config_files']['database'] = 'exists';
        require_once '../config/database.php';
        $debug['config_files']['database'] = 'loaded';
    } else {
        $debug['config_files']['database'] = 'missing';
    }

    if (file_exists('../config/cors.php')) {
        $debug['config_files']['cors'] = 'exists';
        require_once '../config/cors.php';
        $debug['config_files']['cors'] = 'loaded';
    } else {
        $debug['config_files']['cors'] = 'missing';
    }

} catch (Exception $e) {
    $debug['config_error'] = $e->getMessage();
}

// Now test session and database
try {
    session_start();

    $debug['session_info'] = [
        'session_started' => true,
        'session_id' => session_id(),
        'user_id_exists' => isset($_SESSION['user_id']),
        'user_id_value' => $_SESSION['user_id'] ?? null,
        'session_keys' => array_keys($_SESSION)
    ];

    // Test database connection
    if (class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();

        $debug['database_info'] = [
            'class_exists' => true,
            'connection_successful' => $db !== null,
            'connection_type' => $db ? get_class($db) : 'null'
        ];

        if ($db) {
            // Test simple query
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            $debug['database_info']['test_query'] = $result ? 'success' : 'failed';
        }
    } else {
        $debug['database_info'] = ['class_exists' => false];
    }

} catch (Exception $e) {
    $debug['error'] = $e->getMessage();
}

// Output the debug information
echo json_encode($debug, JSON_PRETTY_PRINT);
?>
