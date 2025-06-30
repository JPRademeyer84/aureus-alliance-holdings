<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Direct Countdown API Test</h2>";

// Test the countdown API directly
echo "<h3>Testing Countdown API:</h3>";

try {
    // Simulate the exact call that the frontend makes
    $_GET['action'] = 'get_user_countdowns';
    $_GET['user_id'] = '1';
    $_SESSION['user_id'] = '1'; // Set session for auth
    
    echo "<p>Simulating API call with:</p>";
    echo "<ul>";
    echo "<li>Action: get_user_countdowns</li>";
    echo "<li>User ID: 1</li>";
    echo "<li>Session User ID: " . ($_SESSION['user_id'] ?? 'not set') . "</li>";
    echo "</ul>";
    
    // Capture the output from the countdown API
    ob_start();
    include '../investments/countdown.php';
    $output = ob_get_clean();
    
    echo "<h4>API Response:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Try to decode as JSON
    $jsonData = json_decode($output, true);
    if ($jsonData) {
        echo "<h4>Parsed JSON:</h4>";
        echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
        echo json_encode($jsonData, JSON_PRETTY_PRINT);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Response is not valid JSON</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// Test database connection and tables
echo "<h3>Database Check:</h3>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if aureus_investments table exists
    $query = "SHOW TABLES LIKE 'aureus_investments'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ aureus_investments table exists</p>";
        
        // Count records
        $countQuery = "SELECT COUNT(*) as count FROM aureus_investments";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute();
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Records in aureus_investments: $count</p>";
        
        // Check for user_id = 1
        $userQuery = "SELECT COUNT(*) as count FROM aureus_investments WHERE user_id = '1'";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute();
        $userCount = $userStmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Records for user_id=1: $userCount</p>";
        
    } else {
        echo "<p style='color: red;'>❌ aureus_investments table does not exist</p>";
    }
    
    // Check if investment_countdown_view exists
    $viewQuery = "SHOW TABLES LIKE 'investment_countdown_view'";
    $viewStmt = $db->prepare($viewQuery);
    $viewStmt->execute();
    $viewExists = $viewStmt->fetch();
    
    if ($viewExists) {
        echo "<p style='color: green;'>✅ investment_countdown_view exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ investment_countdown_view does not exist</p>";
        echo "<p>This view should be created automatically by the countdown API</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
