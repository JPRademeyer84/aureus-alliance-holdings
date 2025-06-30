<?php
// Test the auth endpoint directly
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

echo "<h1>Testing Auth Endpoint</h1>";

// Test GET request first
echo "<h2>GET Request Test</h2>";
$url = 'http://localhost/aureus-angel-alliance/api/admin/auth.php';
$response = @file_get_contents($url);
if ($response === false) {
    echo "<p style='color: red;'>❌ GET request failed</p>";
} else {
    echo "<p style='color: green;'>✅ GET request successful</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Test POST request
echo "<h2>POST Request Test</h2>";
$postData = json_encode([
    'action' => 'login',
    'username' => 'admin',
    'password' => 'Underdog8406155100085@123!@#'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "<p style='color: red;'>❌ POST request failed</p>";
    
    // Try with cURL
    echo "<h3>Trying with cURL</h3>";
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $curlResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo "<p style='color: red;'>❌ cURL error: $curlError</p>";
        } else {
            echo "<p style='color: green;'>✅ cURL request successful</p>";
            echo "<pre>" . htmlspecialchars($curlResponse) . "</pre>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ cURL not available</p>";
    }
} else {
    echo "<p style='color: green;'>✅ POST request successful</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Test database connection directly
echo "<h2>Direct Database Test</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;port=3506", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS aureus_angels");
    $db = new PDO("mysql:host=localhost;port=3506;dbname=aureus_angels", 'root', '');
    echo "<p style='color: green;'>✅ Database aureus_angels accessible</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
