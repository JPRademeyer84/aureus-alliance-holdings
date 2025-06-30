<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Wallets API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Test Wallets API</h1>

    <div class="section">
        <h2>Test GET Request</h2>
        <?php
        $url = 'http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo '<p><strong>HTTP Code:</strong> ' . $httpCode . '</p>';
        echo '<p><strong>Response:</strong></p>';
        echo '<pre>' . htmlspecialchars($response) . '</pre>';
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data) {
                echo '<p class="success">✅ Valid JSON response</p>';
                echo '<p><strong>Success:</strong> ' . ($data['success'] ? 'true' : 'false') . '</p>';
                echo '<p><strong>Message:</strong> ' . htmlspecialchars($data['message'] ?? 'N/A') . '</p>';
                echo '<p><strong>Data count:</strong> ' . (is_array($data['data']) ? count($data['data']) : 'N/A') . '</p>';
            } else {
                echo '<p class="error">❌ Invalid JSON response</p>';
            }
        } else {
            echo '<p class="error">❌ HTTP error: ' . $httpCode . '</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Test POST Request with action=list</h2>
        <?php
        $url = 'http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php';
        $postData = json_encode([
            'action' => 'list',
            'adminId' => 1
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo '<p><strong>POST Data:</strong></p>';
        echo '<pre>' . htmlspecialchars($postData) . '</pre>';
        echo '<p><strong>HTTP Code:</strong> ' . $httpCode . '</p>';
        echo '<p><strong>Response:</strong></p>';
        echo '<pre>' . htmlspecialchars($response) . '</pre>';
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data) {
                echo '<p class="success">✅ Valid JSON response</p>';
                echo '<p><strong>Success:</strong> ' . ($data['success'] ? 'true' : 'false') . '</p>';
                echo '<p><strong>Message:</strong> ' . htmlspecialchars($data['message'] ?? 'N/A') . '</p>';
                echo '<p><strong>Data count:</strong> ' . (is_array($data['data']) ? count($data['data']) : 'N/A') . '</p>';
            } else {
                echo '<p class="error">❌ Invalid JSON response</p>';
            }
        } else {
            echo '<p class="error">❌ HTTP error: ' . $httpCode . '</p>';
        }
        ?>
    </div>

</body>
</html>
