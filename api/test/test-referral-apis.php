<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Referral API Test</h2>";

// Set up test session
$_SESSION['user_id'] = '1';

$endpoints = [
    'User Stats' => 'http://localhost/aureus-angel-alliance/api/referrals/user-stats.php?userId=1',
    'User History' => 'http://localhost/aureus-angel-alliance/api/referrals/user-history.php?userId=1',
    'Commission Balance' => 'http://localhost/aureus-angel-alliance/api/referrals/commission-balance.php',
    'Leaderboard' => 'http://localhost/aureus-angel-alliance/api/referrals/leaderboard.php',
    'Gold Diggers' => 'http://localhost/aureus-angel-alliance/api/referrals/gold-diggers-leaderboard.php'
];

echo "<h3>Testing Referral API Endpoints:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Endpoint</th><th>Status</th><th>Response Type</th><th>Success</th><th>Data Summary</th></tr>";

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
                        if (isset($jsonData['stats'])) {
                            $stats = $jsonData['stats'];
                            echo "Stats: " . $stats['totalReferrals'] . " referrals, $" . $stats['totalCommissions'] . " commissions";
                        } elseif (isset($jsonData['records'])) {
                            echo "Records: " . count($jsonData['records']) . " commission records";
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
                } else {
                    echo "<td style='color: orange;'>Unknown</td>";
                    echo "<td>" . substr(json_encode($jsonData), 0, 100) . "...</td>";
                }
            } else {
                echo "<td style='color: red;'>Non-JSON</td>";
                echo "<td style='color: red;'>Invalid JSON</td>";
                echo "<td>" . substr(htmlspecialchars($response), 0, 100) . "...</td>";
            }
        } else {
            echo "<td style='color: red;'>❌ Failed</td>";
            echo "<td style='color: red;'>No Response</td>";
            echo "<td style='color: red;'>Connection failed</td>";
            echo "<td>-</td>";
        }
    } catch (Exception $e) {
        echo "<td style='color: red;'>❌ Error</td>";
        echo "<td style='color: red;'>Exception</td>";
        echo "<td style='color: red;'>Error</td>";
        echo "<td style='color: red;'>" . $e->getMessage() . "</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Test database tables
echo "<h3>Database Tables Check:</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tables = ['referral_commissions', 'users', 'aureus_investments'];
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

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
