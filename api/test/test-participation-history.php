<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Participation History API Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Database connection successful</p>";
    
    // Check if aureus_investments table exists
    $query = "SHOW TABLES LIKE 'aureus_investments'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✅ aureus_investments table exists</p>";
        
        // Check table structure
        $query = "DESCRIBE aureus_investments";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Check if there are any investments
        $query = "SELECT COUNT(*) as count FROM aureus_investments";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        echo "<p>Total investments: {$result['count']}</p>";
        
    } else {
        echo "<p>❌ aureus_investments table does not exist</p>";
    }
    
    // Test session
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User session active: {$_SESSION['user_id']}</p>";
        
        // Test API call
        echo "<h3>Testing API Call:</h3>";
        $userId = $_SESSION['user_id'];
        
        echo "<p>Making API call for user: $userId</p>";
        
        // Test the actual API endpoint
        $url = 'http://localhost/aureus-angel-alliance/api/participations/user-history.php';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        echo "<pre>API Response: $response</pre>";
        
    } else {
        echo "<p>❌ No user session found</p>";
        echo "<p>Please log in first to test the API</p>";
        echo "<p><a href='quick-login-test.php?auto_login=1'>Auto-login as test user</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
