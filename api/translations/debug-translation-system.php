<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

$debug_results = [];
$errors = [];

try {
    $debug_results[] = "🔍 TRANSLATION SYSTEM DIAGNOSTIC";
    $debug_results[] = "================================";
    
    // Test 1: Basic PHP and file access
    $debug_results[] = "\n📋 Step 1: Basic System Check";
    $debug_results[] = "✅ PHP Version: " . phpversion();
    $debug_results[] = "✅ Current file: " . __FILE__;
    $debug_results[] = "✅ Document root: " . $_SERVER['DOCUMENT_ROOT'];
    
    // Test 2: Database class loading
    $debug_results[] = "\n📋 Step 2: Database Class Loading";
    try {
        require_once '../config/database.php';
        $debug_results[] = "✅ Database class loaded successfully";
    } catch (Exception $e) {
        $errors[] = "❌ Failed to load database class: " . $e->getMessage();
        throw $e;
    }
    
    // Test 3: Database connection
    $debug_results[] = "\n📋 Step 3: Database Connection";
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection returned null");
        }
        
        $debug_results[] = "✅ Database connection established";
        
        // Test basic query
        $testQuery = "SELECT DATABASE() as current_db, NOW() as server_time";
        $testStmt = $db->prepare($testQuery);
        $testStmt->execute();
        $result = $testStmt->fetch(PDO::FETCH_ASSOC);

        $debug_results[] = "✅ Current database: " . $result['current_db'];
        $debug_results[] = "✅ Server time: " . $result['server_time'];
        
    } catch (Exception $e) {
        $errors[] = "❌ Database connection failed: " . $e->getMessage();
        throw $e;
    }
    
    // Test 4: Check translation tables
    $debug_results[] = "\n📋 Step 4: Translation Tables Check";
    $required_tables = ['languages', 'translation_keys', 'translations'];
    $existing_tables = [];
    
    foreach ($required_tables as $table) {
        try {
            $checkQuery = "SHOW TABLES LIKE '$table'";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                $existing_tables[] = $table;
                
                // Get row count
                $countQuery = "SELECT COUNT(*) as count FROM $table";
                $countStmt = $db->prepare($countQuery);
                $countStmt->execute();
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                $debug_results[] = "✅ Table '$table' exists with $count records";
            } else {
                $errors[] = "❌ Table '$table' does not exist";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Error checking table '$table': " . $e->getMessage();
        }
    }
    
    // Test 5: Create missing tables if needed
    if (count($existing_tables) < count($required_tables)) {
        $debug_results[] = "\n📋 Step 5: Creating Missing Tables";
        
        try {
            // Create languages table
            if (!in_array('languages', $existing_tables)) {
                $createLangQuery = "CREATE TABLE languages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(10) UNIQUE NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    native_name VARCHAR(100) NOT NULL,
                    flag VARCHAR(10) NOT NULL,
                    is_default BOOLEAN DEFAULT FALSE,
                    is_active BOOLEAN DEFAULT TRUE,
                    sort_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                $db->exec($createLangQuery);
                $debug_results[] = "✅ Created 'languages' table";
                
                // Insert default languages
                $languages = [
                    ['en', 'English', 'English', '🇺🇸', 1, 1, 1],
                    ['es', 'Spanish', 'Español', '🇪🇸', 0, 1, 2],
                    ['fr', 'French', 'Français', '🇫🇷', 0, 1, 3]
                ];
                
                $insertLangQuery = "INSERT INTO languages (code, name, native_name, flag, is_default, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertLangStmt = $db->prepare($insertLangQuery);
                
                foreach ($languages as $lang) {
                    $insertLangStmt->execute($lang);
                }
                $debug_results[] = "✅ Inserted default languages";
            }
            
            // Create translation_keys table
            if (!in_array('translation_keys', $existing_tables)) {
                $createKeysQuery = "CREATE TABLE translation_keys (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    key_name VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT,
                    category VARCHAR(100) NOT NULL DEFAULT 'general',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_category (category),
                    INDEX idx_key_name (key_name)
                )";
                $db->exec($createKeysQuery);
                $debug_results[] = "✅ Created 'translation_keys' table";
                
                // Insert default keys
                $keys = [
                    ['nav.investment', 'Investment menu item', 'navigation'],
                    ['nav.affiliate', 'Affiliate menu item', 'navigation'],
                    ['hero.title', 'Main hero title', 'hero'],
                    ['common.save', 'Save button', 'common']
                ];
                
                $insertKeyQuery = "INSERT INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
                $insertKeyStmt = $db->prepare($insertKeyQuery);
                
                foreach ($keys as $key) {
                    $insertKeyStmt->execute($key);
                }
                $debug_results[] = "✅ Inserted default translation keys";
            }
            
            // Create translations table
            if (!in_array('translations', $existing_tables)) {
                $createTransQuery = "CREATE TABLE translations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    key_id INT NOT NULL,
                    language_id INT NOT NULL,
                    translation_text TEXT NOT NULL,
                    is_approved BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_translation (key_id, language_id),
                    FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
                    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
                    INDEX idx_key_language (key_id, language_id),
                    INDEX idx_is_approved (is_approved)
                )";
                $db->exec($createTransQuery);
                $debug_results[] = "✅ Created 'translations' table";
            }
            
        } catch (Exception $e) {
            $errors[] = "❌ Error creating tables: " . $e->getMessage();
        }
    } else {
        $debug_results[] = "\n📋 Step 5: All Required Tables Exist";
        $debug_results[] = "✅ All translation tables are present";
    }
    
    // Test 6: Test actual translation update
    $debug_results[] = "\n📋 Step 6: Translation Update Test";
    try {
        // Get first language and key
        $langQuery = "SELECT id, code, name FROM languages LIMIT 1";
        $langStmt = $db->prepare($langQuery);
        $langStmt->execute();
        $lang = $langStmt->fetch(PDO::FETCH_ASSOC);
        
        $keyQuery = "SELECT id, key_name FROM translation_keys LIMIT 1";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute();
        $key = $keyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lang && $key) {
            $testText = "Test translation " . date('H:i:s');
            
            // Test the exact same query used in update-translation.php
            $updateQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                           VALUES (?, ?, ?, TRUE) 
                           ON DUPLICATE KEY UPDATE 
                           translation_text = VALUES(translation_text), 
                           updated_at = CURRENT_TIMESTAMP";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$key['id'], $lang['id'], $testText]);
            
            $debug_results[] = "✅ Translation update test successful";
            $debug_results[] = "   - Key: " . $key['key_name'] . " (ID: " . $key['id'] . ")";
            $debug_results[] = "   - Language: " . $lang['name'] . " (ID: " . $lang['id'] . ")";
            $debug_results[] = "   - Text: " . $testText;
            
            // Verify the update
            $verifyQuery = "SELECT translation_text FROM translations WHERE key_id = ? AND language_id = ?";
            $verifyStmt = $db->prepare($verifyQuery);
            $verifyStmt->execute([$key['id'], $lang['id']]);
            $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verified && $verified['translation_text'] === $testText) {
                $debug_results[] = "✅ Translation update verified in database";
            } else {
                $errors[] = "❌ Translation update not found in database";
            }
            
            // Clean up test
            $cleanQuery = "DELETE FROM translations WHERE key_id = ? AND language_id = ? AND translation_text = ?";
            $cleanStmt = $db->prepare($cleanQuery);
            $cleanStmt->execute([$key['id'], $lang['id'], $testText]);
            $debug_results[] = "✅ Test cleanup completed";
            
        } else {
            $errors[] = "❌ No test data available (missing languages or keys)";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Translation update test failed: " . $e->getMessage();
    }
    
    // Test 7: API endpoint test
    $debug_results[] = "\n📋 Step 7: API Endpoint Accessibility";
    $api_files = [
        'update-translation.php',
        'get-languages.php',
        'get-translation-keys.php'
    ];
    
    foreach ($api_files as $file) {
        $file_path = __DIR__ . '/' . $file;
        if (file_exists($file_path)) {
            $debug_results[] = "✅ API file exists: $file";
        } else {
            $errors[] = "❌ API file missing: $file";
        }
    }
    
    $response = [
        'success' => count($errors) === 0,
        'message' => count($errors) === 0 ? 'Translation system is fully operational!' : 'Issues found in translation system',
        'debug_results' => $debug_results,
        'errors' => $errors,
        'summary' => [
            'total_checks' => count($debug_results),
            'errors_found' => count($errors),
            'database_connected' => isset($db) && $db !== null,
            'tables_exist' => count($existing_tables ?? []),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_results' => $debug_results,
        'errors' => array_merge($errors, [$e->getMessage()]),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
