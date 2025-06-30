<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Enhanced Profile API Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Database connection successful</p>";
    
    // Check if user_profiles table exists
    $query = "SHOW TABLES LIKE 'user_profiles'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✅ user_profiles table exists</p>";
        
        // Check table structure
        $query = "DESCRIBE user_profiles";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Check if there are any profiles
        $query = "SELECT COUNT(*) as count FROM user_profiles";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        
        echo "<p>Total profiles: {$result['count']}</p>";
        
    } else {
        echo "<p>❌ user_profiles table does not exist</p>";
    }
    
    // Check if kyc_documents table exists
    $query = "SHOW TABLES LIKE 'kyc_documents'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✅ kyc_documents table exists</p>";
    } else {
        echo "<p>❌ kyc_documents table does not exist</p>";
    }
    
    // Test session
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User session active: {$_SESSION['user_id']}</p>";
        
        // Test API call
        echo "<h3>Testing API Call:</h3>";
        $userId = $_SESSION['user_id'];
        
        // Simulate GET request
        $_GET['action'] = 'get';
        $_GET['user_id'] = $userId;
        
        echo "<p>Making API call for user: $userId</p>";
        
        // Include the API file to test
        ob_start();
        include '../users/enhanced-profile.php';
        $output = ob_get_clean();
        
        echo "<pre>API Response: $output</pre>";
        
    } else {
        echo "<p>❌ No user session found</p>";
        echo "<p>Please log in first to test the API</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
