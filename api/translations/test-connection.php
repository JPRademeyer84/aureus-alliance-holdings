<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$results = [];
$errors = [];

try {
    $results[] = "🔍 Testing database connection...";
    
    // Test 1: Include database file
    require_once '../config/database.php';
    $results[] = "✅ Database class loaded successfully";
    
    // Test 2: Create database instance
    $database = new Database();
    $results[] = "✅ Database instance created";
    
    // Test 3: Get connection
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Database connection returned null");
    }
    $results[] = "✅ Database connection established";
    
    // Test 4: Test basic query
    $testQuery = "SELECT 1 as test";
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute();
    $testResult = $testStmt->fetch();
    if ($testResult['test'] == 1) {
        $results[] = "✅ Basic database query works";
    } else {
        throw new Exception("Basic query failed");
    }
    
    // Test 5: Check if translation tables exist
    $tables = ['languages', 'translation_keys', 'translations'];
    foreach ($tables as $table) {
        try {
            $checkQuery = "SELECT COUNT(*) as count FROM $table LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            $count = $checkStmt->fetch()['count'];
            $results[] = "✅ Table '$table' exists with $count records";
        } catch (Exception $e) {
            $errors[] = "❌ Table '$table' missing or inaccessible: " . $e->getMessage();
        }
    }
    
    // Test 6: Test actual translation update (if tables exist)
    if (count($errors) == 0) {
        try {
            // Get first language and key for testing
            $langQuery = "SELECT id FROM languages LIMIT 1";
            $langStmt = $db->prepare($langQuery);
            $langStmt->execute();
            $lang = $langStmt->fetch();
            
            $keyQuery = "SELECT id FROM translation_keys LIMIT 1";
            $keyStmt = $db->prepare($keyQuery);
            $keyStmt->execute();
            $key = $keyStmt->fetch();
            
            if ($lang && $key) {
                // Test insert/update
                $testText = "Test translation " . date('H:i:s');
                $updateQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                               VALUES (?, ?, ?, TRUE) 
                               ON DUPLICATE KEY UPDATE 
                               translation_text = VALUES(translation_text), 
                               updated_at = CURRENT_TIMESTAMP";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$key['id'], $lang['id'], $testText]);
                
                $results[] = "✅ Translation update test successful";
                
                // Clean up test
                $cleanQuery = "DELETE FROM translations WHERE key_id = ? AND language_id = ? AND translation_text = ?";
                $cleanStmt = $db->prepare($cleanQuery);
                $cleanStmt->execute([$key['id'], $lang['id'], $testText]);
                $results[] = "✅ Test cleanup completed";
            } else {
                $errors[] = "❌ No test data available (languages or keys missing)";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Translation update test failed: " . $e->getMessage();
        }
    }
    
    // Test 7: Check server environment
    $results[] = "📋 Server Info:";
    $results[] = "  - PHP Version: " . phpversion();
    $results[] = "  - Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
    $results[] = "  - Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown');
    $results[] = "  - Script Path: " . __FILE__;
    
    $response = [
        'success' => count($errors) == 0,
        'message' => count($errors) == 0 ? 'All connection tests passed!' : 'Some tests failed',
        'results' => $results,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s'),
        'total_tests' => count($results),
        'failed_tests' => count($errors)
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Connection test failed',
        'results' => $results,
        'errors' => array_merge($errors, [$e->getMessage()]),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
