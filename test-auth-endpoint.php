<?php
// Simple test to check if auth endpoint is accessible
echo "<h1>Auth Endpoint Test</h1>";

// Test 1: Check if auth.php file exists
$authFile = 'api/admin/auth.php';
if (file_exists($authFile)) {
    echo "<p style='color: green;'>✅ Auth file exists: $authFile</p>";
} else {
    echo "<p style='color: red;'>❌ Auth file not found: $authFile</p>";
}

// Test 2: Try to access the auth endpoint via HTTP
echo "<h2>HTTP Request Test</h2>";

$url = 'http://localhost/aureus-angel-alliance/api/admin/auth.php';
echo "<p>Testing URL: <a href='$url' target='_blank'>$url</a></p>";

// Test GET request (should return method not allowed)
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "<p style='color: red;'>❌ Cannot reach auth endpoint</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Apache is not running</li>";
    echo "<li>Project not in correct XAMPP directory</li>";
    echo "<li>URL path is incorrect</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ Auth endpoint is reachable</p>";
    echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
}

// Test 3: Try POST request with login data
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
        'content' => $postData,
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "<p style='color: red;'>❌ POST request failed</p>";
} else {
    echo "<p style='color: green;'>✅ POST request successful</p>";
    echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p style='color: green; font-weight: bold;'>🎉 LOGIN TEST PASSED!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Login response received but not successful</p>";
    }
}

// Test 4: Check current directory and file structure
echo "<h2>File Structure Check</h2>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

if (is_dir('api')) {
    echo "<p style='color: green;'>✅ api/ directory exists</p>";
    if (is_dir('api/admin')) {
        echo "<p style='color: green;'>✅ api/admin/ directory exists</p>";
        if (file_exists('api/admin/auth.php')) {
            echo "<p style='color: green;'>✅ api/admin/auth.php exists</p>";
        } else {
            echo "<p style='color: red;'>❌ api/admin/auth.php not found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ api/admin/ directory not found</p>";
    }
} else {
    echo "<p style='color: red;'>❌ api/ directory not found</p>";
}
?>
