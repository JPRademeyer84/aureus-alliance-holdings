<?php
// Test the exact same request that the frontend makes
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Frontend Request Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Frontend Request Test</h1>
    <p>This test simulates the exact request that the React frontend makes.</p>

    <?php
    // Test the auth endpoint with cURL (more reliable than file_get_contents)
    $url = 'http://localhost/aureus-angel-alliance/api/admin/auth.php';
    
    $postData = json_encode([
        'action' => 'login',
        'username' => 'admin',
        'password' => 'Underdog8406155100085@123!@#'
    ]);

    echo "<h2>Request Details</h2>";
    echo "<p><strong>URL:</strong> $url</p>";
    echo "<p><strong>Method:</strong> POST</p>";
    echo "<p><strong>Content-Type:</strong> application/json</p>";
    echo "<p><strong>Body:</strong></p>";
    echo "<pre>" . htmlspecialchars($postData) . "</pre>";

    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Capture verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    // Get verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    
    curl_close($ch);

    echo "<h2>Response</h2>";
    
    if ($error) {
        echo "<p class='error'>❌ cURL Error: $error</p>";
    } else {
        echo "<p class='success'>✅ Request completed</p>";
    }
    
    echo "<p><strong>HTTP Status Code:</strong> $httpCode</p>";
    
    if ($response) {
        echo "<p><strong>Response Body:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Try to parse JSON
        $data = json_decode($response, true);
        if ($data) {
            echo "<p><strong>Parsed JSON:</strong></p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            
            if (isset($data['success']) && $data['success']) {
                echo "<p class='success'>🎉 <strong>SUCCESS!</strong> Login worked correctly!</p>";
            } else {
                echo "<p class='error'>❌ Login failed. Error: " . ($data['error'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ Response is not valid JSON</p>";
        }
    } else {
        echo "<p class='error'>❌ No response received</p>";
    }
    
    // Show detailed connection info
    echo "<h2>Connection Details</h2>";
    echo "<p><strong>Total Time:</strong> " . $info['total_time'] . " seconds</p>";
    echo "<p><strong>Connect Time:</strong> " . $info['connect_time'] . " seconds</p>";
    echo "<p><strong>DNS Lookup Time:</strong> " . $info['namelookup_time'] . " seconds</p>";
    
    if ($verboseLog) {
        echo "<h2>Verbose Log</h2>";
        echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
    }
    ?>

    <h2>JavaScript Test</h2>
    <p>This will test the same request using JavaScript (like the frontend does):</p>
    <button onclick="testJavaScript()">Test with JavaScript</button>
    <div id="jsResult"></div>

    <script>
    async function testJavaScript() {
        const resultDiv = document.getElementById('jsResult');
        resultDiv.innerHTML = '<p>Testing...</p>';
        
        try {
            const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    username: 'admin',
                    password: 'Underdog8406155100085@123!@#'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = '<p style="color: green;">🎉 <strong>JavaScript test PASSED!</strong></p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } else {
                resultDiv.innerHTML = '<p style="color: red;">❌ JavaScript test failed: ' + (data.error || 'Unknown error') + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }
        } catch (error) {
            resultDiv.innerHTML = '<p style="color: red;">❌ JavaScript error: ' + error.message + '</p>';
            console.error('JavaScript test error:', error);
        }
    }
    </script>

    <hr>
    <p><a href="test.php">← Back to Main Test</a></p>
</body>
</html>
