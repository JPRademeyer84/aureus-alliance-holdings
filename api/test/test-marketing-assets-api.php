<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Marketing Assets API Test</h2>";

// Set up test session
$_SESSION['admin_id'] = '1';

$endpoints = [
    'Marketing Assets' => 'http://localhost/aureus-angel-alliance/api/admin/marketing-assets.php',
    'Marketing Assets Download' => 'http://localhost/aureus-angel-alliance/api/admin/marketing-assets-download.php?asset_id=1'
];

echo "<h3>Testing Marketing Assets API Endpoints:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Endpoint</th><th>Status</th><th>Response Type</th><th>Success</th><th>Data Summary</th><th>Error Details</th></tr>";

foreach ($endpoints as $name => $url) {
    echo "<tr>";
    echo "<td><strong>$name</strong><br><small>" . htmlspecialchars($url) . "</small></td>";
    
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => 'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? '')
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $httpCode = 200;
            if (isset($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (strpos($header, 'HTTP/') === 0) {
                        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches);
                        if (isset($matches[1])) {
                            $httpCode = (int)$matches[1];
                        }
                    }
                }
            }
            
            echo "<td style='color: " . ($httpCode < 400 ? 'green' : 'red') . ";'>$httpCode</td>";
            
            // Check if response starts with PHP error
            if (strpos($response, '<?php') === 0 || strpos($response, 'Parse error') !== false || strpos($response, 'Fatal error') !== false) {
                echo "<td style='color: red;'>PHP Error</td>";
                echo "<td style='color: red;'>❌ PHP Error</td>";
                echo "<td style='color: red;'>-</td>";
                echo "<td style='color: red;'>" . substr(htmlspecialchars($response), 0, 100) . "...</td>";
            } else {
                // Try to decode JSON response
                $jsonData = json_decode($response, true);
                if ($jsonData) {
                    echo "<td style='color: green;'>JSON</td>";
                    
                    if (isset($jsonData['success'])) {
                        $success = $jsonData['success'];
                        echo "<td style='color: " . ($success ? 'green' : 'orange') . ";'>";
                        echo $success ? '✅ Success' : '⚠️ Failed';
                        echo "</td>";
                        
                        // Show data summary
                        echo "<td>";
                        if ($success) {
                            if (isset($jsonData['assets'])) {
                                echo "Assets: " . count($jsonData['assets']) . " marketing assets";
                            } elseif (isset($jsonData['asset'])) {
                                echo "Asset: " . ($jsonData['asset']['title'] ?? 'N/A');
                            } elseif (isset($jsonData['data'])) {
                                if (is_array($jsonData['data'])) {
                                    echo "Data: " . count($jsonData['data']) . " items";
                                } else {
                                    echo "Data: " . substr(json_encode($jsonData['data']), 0, 50) . "...";
                                }
                            } else {
                                echo "Success response";
                            }
                        } else {
                            echo $jsonData['error'] ?? $jsonData['message'] ?? 'Unknown error';
                        }
                        echo "</td>";
                        
                        // Error details
                        echo "<td>";
                        if (!$success) {
                            echo $jsonData['error'] ?? $jsonData['message'] ?? 'No error details';
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                    } else {
                        echo "<td style='color: orange;'>Unknown</td>";
                        echo "<td>" . substr(json_encode($jsonData), 0, 100) . "...</td>";
                        echo "<td>-</td>";
                    }
                } else {
                    echo "<td style='color: red;'>Non-JSON</td>";
                    echo "<td style='color: red;'>Invalid JSON</td>";
                    echo "<td>" . substr(htmlspecialchars($response), 0, 100) . "...</td>";
                    echo "<td style='color: red;'>Invalid JSON response</td>";
                }
            }
        } else {
            echo "<td style='color: red;'>❌ Failed</td>";
            echo "<td style='color: red;'>No Response</td>";
            echo "<td style='color: red;'>Connection failed</td>";
            echo "<td>-</td>";
            echo "<td style='color: red;'>Connection failed</td>";
        }
    } catch (Exception $e) {
        echo "<td style='color: red;'>❌ Error</td>";
        echo "<td style='color: red;'>Exception</td>";
        echo "<td style='color: red;'>Error</td>";
        echo "<td>-</td>";
        echo "<td style='color: red;'>" . $e->getMessage() . "</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Database Tables Check
echo "<h3>Database Tables Check:</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tables = ['marketing_assets', 'marketing_asset_downloads'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Count records
            $countQuery = "SELECT COUNT(*) as count FROM $table";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute();
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p style='color: green;'>✅ Table '$table' exists ($count records)</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Component Status Summary
echo "<h3>Marketing Assets Component Status:</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Fixed Issues:</h4>";
echo "<ul>";
echo "<li><strong>MarketingAssetsManager:</strong> Fixed relative path to full URL</li>";
echo "<li><strong>SocialMediaTools:</strong> Fixed relative path to full URL</li>";
echo "<li><strong>Marketing Assets API:</strong> Fixed CORS headers to use proper configuration</li>";
echo "<li><strong>Error Handling:</strong> Added proper success/error response handling</li>";
echo "<li><strong>Credentials:</strong> Added credentials: 'include' for session support</li>";
echo "</ul>";

echo "<h4>🎯 Expected Results:</h4>";
echo "<ul>";
echo "<li>No more 'Failed to fetch marketing assets' errors</li>";
echo "<li>Proper CORS handling for all ports</li>";
echo "<li>Clean JSON responses without PHP errors</li>";
echo "<li>Graceful handling of empty marketing assets data</li>";
echo "<li>Admin authentication working correctly</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
