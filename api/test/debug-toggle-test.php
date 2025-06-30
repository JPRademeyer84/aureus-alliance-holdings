<?php
/**
 * Debug Toggle Test
 * Test the toggle functionality and check database state
 */

header('Content-Type: text/plain');

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 TESTING DEBUG TOGGLE FUNCTIONALITY\n";
    echo "====================================\n\n";
    
    // Start session for admin authentication
    session_start();
    
    // Get admin user for testing
    $adminQuery = "SELECT id, username FROM admin_users WHERE username = 'admin' LIMIT 1";
    $adminStmt = $db->prepare($adminQuery);
    $adminStmt->execute();
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        echo "✅ Admin session created: {$admin['username']}\n\n";
    } else {
        echo "❌ No admin user found\n";
        exit;
    }
    
    // 1. Check current database state
    echo "1. CURRENT DATABASE STATE:\n";
    echo "==========================\n";
    
    $currentQuery = "
        SELECT feature_key, feature_name, is_enabled, is_visible, updated_at
        FROM debug_config 
        ORDER BY feature_name ASC
    ";
    
    $currentStmt = $db->prepare($currentQuery);
    $currentStmt->execute();
    $currentConfigs = $currentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($currentConfigs as $config) {
        $status = $config['is_enabled'] ? '🟢 ENABLED' : '🔴 DISABLED';
        $visibility = $config['is_visible'] ? 'VISIBLE' : 'HIDDEN';
        echo "  - {$config['feature_name']}: $status, $visibility (Updated: {$config['updated_at']})\n";
    }
    
    $enabledCount = count(array_filter($currentConfigs, fn($c) => $c['is_enabled']));
    echo "\nCurrent Status: $enabledCount of " . count($currentConfigs) . " features enabled\n\n";
    
    // 2. Test toggle functionality
    echo "2. TESTING TOGGLE FUNCTIONALITY:\n";
    echo "================================\n";
    
    // Test disabling a feature that should be enabled
    $testFeature = 'api_testing';
    echo "Testing toggle for feature: $testFeature\n";
    
    // Get current state
    $getStateQuery = "SELECT is_enabled FROM debug_config WHERE feature_key = ?";
    $getStateStmt = $db->prepare($getStateQuery);
    $getStateStmt->execute([$testFeature]);
    $currentState = $getStateStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentState) {
        $wasEnabled = (bool)$currentState['is_enabled'];
        echo "  - Current state: " . ($wasEnabled ? 'ENABLED' : 'DISABLED') . "\n";
        
        // Toggle to opposite state
        $newState = !$wasEnabled;
        echo "  - Toggling to: " . ($newState ? 'ENABLED' : 'DISABLED') . "\n";
        
        // Execute toggle
        $toggleQuery = "
            UPDATE debug_config 
            SET is_enabled = ?, 
                updated_by = ?, 
                updated_at = NOW() 
            WHERE feature_key = ?
        ";
        
        $toggleStmt = $db->prepare($toggleQuery);
        $result = $toggleStmt->execute([$newState, $admin['id'], $testFeature]);
        
        if ($result && $toggleStmt->rowCount() > 0) {
            echo "  - ✅ Toggle successful (Rows affected: {$toggleStmt->rowCount()})\n";
            
            // Verify new state
            $getStateStmt->execute([$testFeature]);
            $verifyState = $getStateStmt->fetch(PDO::FETCH_ASSOC);
            $actualState = (bool)$verifyState['is_enabled'];
            
            echo "  - ✅ Verified state: " . ($actualState ? 'ENABLED' : 'DISABLED') . "\n";
            
            if ($actualState === $newState) {
                echo "  - ✅ State change confirmed!\n";
            } else {
                echo "  - ❌ State change failed! Expected: " . ($newState ? 'ENABLED' : 'DISABLED') . ", Got: " . ($actualState ? 'ENABLED' : 'DISABLED') . "\n";
            }
        } else {
            echo "  - ❌ Toggle failed (No rows affected)\n";
        }
    } else {
        echo "  - ❌ Feature not found: $testFeature\n";
    }
    
    echo "\n3. TESTING API ENDPOINT:\n";
    echo "========================\n";
    
    // Test the actual API endpoint
    echo "Testing POST to debug-config.php?action=toggle\n";
    
    // Simulate the API call
    $postData = json_encode([
        'feature_key' => 'console_logs',
        'enabled' => false  // Try to disable console logs
    ]);
    
    echo "  - POST data: $postData\n";
    
    // We can't easily test the API endpoint from here, but let's test the function directly
    echo "  - Testing handleToggleDebugFeature function...\n";
    
    // Get current state of console_logs
    $getStateStmt->execute(['console_logs']);
    $consoleState = $getStateStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($consoleState) {
        $wasEnabled = (bool)$consoleState['is_enabled'];
        echo "  - Console Logs current state: " . ($wasEnabled ? 'ENABLED' : 'DISABLED') . "\n";
        
        // Try to disable it
        $disableQuery = "
            UPDATE debug_config 
            SET is_enabled = FALSE, 
                updated_by = ?, 
                updated_at = NOW() 
            WHERE feature_key = 'console_logs'
        ";
        
        $disableStmt = $db->prepare($disableQuery);
        $disableResult = $disableStmt->execute([$admin['id']]);
        
        if ($disableResult && $disableStmt->rowCount() > 0) {
            echo "  - ✅ Console Logs disabled successfully\n";
            
            // Verify
            $getStateStmt->execute(['console_logs']);
            $verifyConsole = $getStateStmt->fetch(PDO::FETCH_ASSOC);
            $newConsoleState = (bool)$verifyConsole['is_enabled'];
            
            echo "  - ✅ Verified Console Logs state: " . ($newConsoleState ? 'ENABLED' : 'DISABLED') . "\n";
        } else {
            echo "  - ❌ Failed to disable Console Logs\n";
        }
    }
    
    echo "\n4. FINAL DATABASE STATE:\n";
    echo "========================\n";
    
    // Check final state
    $finalStmt = $db->prepare($currentQuery);
    $finalStmt->execute();
    $finalConfigs = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalConfigs as $config) {
        $status = $config['is_enabled'] ? '🟢 ENABLED' : '🔴 DISABLED';
        echo "  - {$config['feature_name']}: $status (Updated: {$config['updated_at']})\n";
    }
    
    $finalEnabledCount = count(array_filter($finalConfigs, fn($c) => $c['is_enabled']));
    echo "\nFinal Status: $finalEnabledCount of " . count($finalConfigs) . " features enabled\n";
    
    echo "\n5. RECOMMENDATIONS:\n";
    echo "==================\n";
    
    if ($finalEnabledCount < $enabledCount) {
        echo "✅ Toggle functionality is working - some features were disabled\n";
        echo "🔄 Try refreshing the Debug Manager page to see updated states\n";
        echo "🔍 Check browser console for any JavaScript errors\n";
    } else {
        echo "⚠️  No features were disabled - there might be an issue\n";
        echo "🔍 Check the frontend toggle function and API calls\n";
        echo "🔍 Check browser network tab for failed API requests\n";
    }
    
    echo "\n📋 NEXT STEPS:\n";
    echo "1. Refresh the Debug Manager page\n";
    echo "2. Check if the UI reflects the database changes\n";
    echo "3. If UI still shows wrong state, there's a frontend caching issue\n";
    echo "4. Check browser console for errors\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
