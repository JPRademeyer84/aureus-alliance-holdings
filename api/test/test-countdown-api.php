<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Countdown API Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>Database Connection: ✅ Success</h3>";
    
    // Test if investment_countdown_view exists
    $query = "SHOW TABLES LIKE 'investment_countdown_view'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $viewExists = $stmt->fetch();
    
    if ($viewExists) {
        echo "<p style='color: green;'>✅ investment_countdown_view exists</p>";
    } else {
        echo "<p style='color: red;'>❌ investment_countdown_view does not exist</p>";
        
        // Try to create the view
        echo "<p>Attempting to create countdown tables and view...</p>";
        
        // Include the countdown API to trigger table creation
        $createResult = file_get_contents('http://localhost/aureus-angel-alliance/api/investments/countdown.php?action=get_user_countdowns&user_id=1');
        echo "<p>Create result: " . substr($createResult, 0, 100) . "...</p>";
    }
    
    // Test the API endpoint
    echo "<h3>Testing API Endpoint:</h3>";
    
    $testUrl = 'http://localhost/aureus-angel-alliance/api/investments/countdown.php?action=get_user_countdowns&user_id=1';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => 'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? '')
        ]
    ]);
    
    $response = @file_get_contents($testUrl, false, $context);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✅ API Response received</p>";
        
        $jsonData = json_decode($response, true);
        if ($jsonData) {
            echo "<p style='color: green;'>✅ Valid JSON response</p>";
            echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to get API response</p>";
        
        if (isset($http_response_header)) {
            echo "<p>Headers: " . implode(', ', $http_response_header) . "</p>";
        }
    }
    
    // Test database tables
    echo "<h3>Database Tables Check:</h3>";
    
    $tables = ['aureus_investments', 'delivery_schedule', 'users'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
            
            // Count records
            $countQuery = "SELECT COUNT(*) as count FROM $table";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute();
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>&nbsp;&nbsp;&nbsp;Records: $count</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
