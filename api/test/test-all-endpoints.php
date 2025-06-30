<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>API Endpoints Test</h2>";

$endpoints = [
    'Admin Debug Config' => '/api/admin/debug-config.php?action=active',
    'Translation Languages' => '/api/translations/get-languages.php',
    'Translation Keys' => '/api/translations/get-translation-keys.php',
    'Participation History' => '/api/participations/user-history.php',
    'Enhanced Profile' => '/api/users/enhanced-profile.php?action=get&user_id=1',
    'Chat Agent Status' => '/api/chat/agent-status.php',
    'Coupons' => '/api/coupons/index.php?action=admin_coupons'
];

echo "<h3>Testing API Endpoints:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Endpoint</th><th>Status</th><th>Response</th></tr>";

foreach ($endpoints as $name => $endpoint) {
    $url = 'http://localhost/aureus-angel-alliance' . $endpoint;
    
    echo "<tr>";
    echo "<td>$name</td>";
    
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => 'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? '')
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $httpCode = 200;
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (strpos($header, 'HTTP/') === 0) {
                        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches);
                        if (isset($matches[1])) {
                            $httpCode = (int)$matches[1];
                        }
                    }
                }
            }
            
            echo "<td style='color: " . ($httpCode < 400 ? 'green' : 'red') . ";'>$httpCode</td>";
            
            // Try to decode JSON response
            $jsonData = json_decode($response, true);
            if ($jsonData) {
                if (isset($jsonData['success'])) {
                    echo "<td style='color: " . ($jsonData['success'] ? 'green' : 'orange') . ";'>";
                    echo $jsonData['success'] ? '✅ Success' : '⚠️ ' . ($jsonData['message'] ?? 'Failed');
                    echo "</td>";
                } else {
                    echo "<td>📄 JSON Response</td>";
                }
            } else {
                echo "<td>📝 " . substr($response, 0, 50) . "...</td>";
            }
        } else {
            echo "<td style='color: red;'>❌ Failed</td>";
            echo "<td style='color: red;'>Connection failed</td>";
        }
    } catch (Exception $e) {
        echo "<td style='color: red;'>❌ Error</td>";
        echo "<td style='color: red;'>" . $e->getMessage() . "</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Test session status
echo "<h3>Session Status:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ User session active: {$_SESSION['user_id']}</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No user session found</p>";
    echo "<p><a href='quick-login-test.php?auto_login=1'>Auto-login as test user</a></p>";
}

// Test database connection
echo "<h3>Database Status:</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check key tables
    $tables = ['users', 'aureus_investments', 'user_profiles', 'translations', 'languages'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
