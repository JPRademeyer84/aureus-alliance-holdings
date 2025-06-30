<?php
/**
 * Test Debug API Response
 * Check what the debug API is returning
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 TESTING DEBUG API RESPONSES\n";
    echo "==============================\n\n";
    
    // Test 1: Check if debug_config table exists and has data
    echo "1. Checking debug_config table...\n";
    
    try {
        $query = "SELECT COUNT(*) as count FROM debug_config";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ debug_config table exists with $count records\n";
        
        // Show sample data
        $sampleQuery = "SELECT feature_key, feature_name, is_enabled, is_visible FROM debug_config LIMIT 3";
        $sampleStmt = $db->prepare($sampleQuery);
        $sampleStmt->execute();
        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample records:\n";
        foreach ($samples as $sample) {
            $enabled = $sample['is_enabled'] ? 'ENABLED' : 'DISABLED';
            $visible = $sample['is_visible'] ? 'VISIBLE' : 'HIDDEN';
            echo "  - {$sample['feature_name']} ({$sample['feature_key']}): $enabled, $visible\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error checking debug_config table: " . $e->getMessage() . "\n";
    }
    
    echo "\n2. Testing debug config API query...\n";
    
    // Test the exact query from the API
    try {
        $query = "
            SELECT 
                dc.*,
                au.username as created_by_username,
                au2.username as updated_by_username
            FROM debug_config dc
            LEFT JOIN admin_users au ON dc.created_by = au.id
            LEFT JOIN admin_users au2 ON dc.updated_by = au2.id
            ORDER BY dc.feature_name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ API query returned " . count($configs) . " configurations\n";
        
        if (count($configs) > 0) {
            echo "First configuration:\n";
            $first = $configs[0];
            echo "  - ID: {$first['id']}\n";
            echo "  - Feature Key: {$first['feature_key']}\n";
            echo "  - Feature Name: {$first['feature_name']}\n";
            echo "  - Enabled: " . ($first['is_enabled'] ? 'YES' : 'NO') . "\n";
            echo "  - Visible: " . ($first['is_visible'] ? 'YES' : 'NO') . "\n";
            echo "  - Access Level: {$first['access_level']}\n";
            echo "  - Created By: {$first['created_by_username']}\n";
        }
        
        // Parse JSON fields like the API does
        foreach ($configs as &$config) {
            $config['config_data'] = $config['config_data'] ? json_decode($config['config_data'], true) : null;
            $config['allowed_environments'] = $config['allowed_environments'] ? json_decode($config['allowed_environments'], true) : [];
        }
        
        echo "\nAfter JSON parsing:\n";
        if (count($configs) > 0) {
            $first = $configs[0];
            echo "  - Config Data: " . ($first['config_data'] ? json_encode($first['config_data']) : 'NULL') . "\n";
            echo "  - Allowed Environments: " . json_encode($first['allowed_environments']) . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error testing API query: " . $e->getMessage() . "\n";
    }
    
    echo "\n3. Testing active debug features query...\n";
    
    // Test the active features query
    try {
        $environment = 'development';
        
        $query = "
            SELECT 
                feature_key,
                feature_name,
                feature_description,
                config_data,
                access_level
            FROM debug_config 
            WHERE is_enabled = TRUE 
            AND is_visible = TRUE
            AND (
                allowed_environments IS NULL 
                OR JSON_CONTAINS(allowed_environments, ?)
            )
            ORDER BY feature_name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([json_encode($environment)]);
        $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Active features query returned " . count($features) . " features\n";
        
        foreach ($features as $feature) {
            echo "  - {$feature['feature_name']} ({$feature['feature_key']})\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error testing active features query: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Simulating API response format...\n";
    
    // Simulate what the API should return
    try {
        $query = "
            SELECT 
                dc.*,
                au.username as created_by_username,
                au2.username as updated_by_username
            FROM debug_config dc
            LEFT JOIN admin_users au ON dc.created_by = au.id
            LEFT JOIN admin_users au2 ON dc.updated_by = au2.id
            ORDER BY dc.feature_name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON fields
        foreach ($configs as &$config) {
            $config['config_data'] = $config['config_data'] ? json_decode($config['config_data'], true) : null;
            $config['allowed_environments'] = $config['allowed_environments'] ? json_decode($config['allowed_environments'], true) : [];
        }
        
        $response = [
            'success' => true,
            'message' => 'Debug configurations retrieved successfully',
            'data' => $configs
        ];
        
        echo "API Response Structure:\n";
        echo "  - Success: " . ($response['success'] ? 'true' : 'false') . "\n";
        echo "  - Message: {$response['message']}\n";
        echo "  - Data Count: " . count($response['data']) . "\n";
        echo "  - First Item Keys: " . implode(', ', array_keys($response['data'][0] ?? [])) . "\n";
        
        echo "\nJSON Response Preview:\n";
        echo substr(json_encode($response, JSON_PRETTY_PRINT), 0, 500) . "...\n";
        
    } catch (Exception $e) {
        echo "❌ Error simulating API response: " . $e->getMessage() . "\n";
    }
    
    echo "\n==============================\n";
    echo "🎯 DIAGNOSIS COMPLETE\n";
    echo "==============================\n";

} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
}
?>
