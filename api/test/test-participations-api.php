<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Participations API Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>Database Connection: ✅ Success</h3>";
    
    // Test participations API endpoint
    echo "<h3>Testing Participations API:</h3>";
    
    $testUrl = 'http://localhost/aureus-angel-alliance/api/participations/user-history.php?user_id=1';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => 'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? '')
        ]
    ]);
    
    $response = @file_get_contents($testUrl, false, $context);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✅ Participations API Response received</p>";
        
        $jsonData = json_decode($response, true);
        if ($jsonData) {
            echo "<p style='color: green;'>✅ Valid JSON response</p>";
            echo "<h4>Response Structure:</h4>";
            echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
            echo json_encode($jsonData, JSON_PRETTY_PRINT);
            echo "</pre>";
            
            if (isset($jsonData['data']['participations'])) {
                $participations = $jsonData['data']['participations'];
                echo "<h4>Participations Found: " . count($participations) . "</h4>";
                
                if (count($participations) > 0) {
                    echo "<h4>Sample Participation Data:</h4>";
                    echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
                    echo json_encode($participations[0], JSON_PRETTY_PRINT);
                    echo "</pre>";
                    
                    // Calculate countdown for sample
                    $sample = $participations[0];
                    $createdAt = new DateTime($sample['created_at']);
                    $deliveryDate = clone $createdAt;
                    $deliveryDate->add(new DateInterval('P180D')); // Add 180 days
                    $now = new DateTime();
                    $daysRemaining = max(0, $deliveryDate->diff($now)->days);
                    
                    echo "<h4>Countdown Calculation Example:</h4>";
                    echo "<ul>";
                    echo "<li>Created: " . $createdAt->format('Y-m-d H:i:s') . "</li>";
                    echo "<li>Delivery Date: " . $deliveryDate->format('Y-m-d H:i:s') . "</li>";
                    echo "<li>Days Remaining: " . $daysRemaining . "</li>";
                    echo "</ul>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to get API response</p>";
    }
    
    // Test database tables directly
    echo "<h3>Direct Database Query:</h3>";
    
    $query = "SELECT * FROM aureus_investments WHERE user_id = '1' LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Direct query found: " . count($investments) . " investments for user_id=1</p>";
    
    if (count($investments) > 0) {
        echo "<h4>Sample Investment Record:</h4>";
        echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
        echo json_encode($investments[0], JSON_PRETTY_PRINT);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
