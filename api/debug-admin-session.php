<?php
require_once 'config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo json_encode([
    'session_id' => session_id(),
    'admin_session_data' => $_SESSION,
    'admin_authenticated' => isset($_SESSION['admin_id']),
    'admin_id' => $_SESSION['admin_id'] ?? null,
    'admin_username' => $_SESSION['admin_username'] ?? null,
    'admin_role' => $_SESSION['admin_role'] ?? null,
    'cookies' => $_COOKIE,
    'timestamp' => date('c')
], JSON_PRETTY_PRINT);
?>
