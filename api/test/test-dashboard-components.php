<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Dashboard Components Test</h2>";

// Set up test session
$_SESSION['user_id'] = '1';

$endpoints = [
    'Investment History' => 'http://localhost/aureus-angel-alliance/api/investments/user-history.php',
    'Investment Countdown' => 'http://localhost/aureus-angel-alliance/api/investments/countdown.php?action=get_user_countdowns&user_id=1',
    'Participation History' => 'http://localhost/aureus-angel-alliance/api/participations/user-history.php?user_id=1',
    'Portfolio Data' => 'http://localhost/aureus-angel-alliance/api/investments/user-history.php',
    'Enhanced Profile' => 'http://localhost/aureus-angel-alliance/api/users/enhanced-profile.php?action=get&user_id=1',
    'User Profile' => 'http://localhost/aureus-angel-alliance/api/users/profile/1',
    'Referral Stats' => 'http://localhost/aureus-angel-alliance/api/referrals/user-stats.php?userId=1',
    'Referral History' => 'http://localhost/aureus-angel-alliance/api/referrals/user-history.php?userId=1',
    'Commission Balance' => 'http://localhost/aureus-angel-alliance/api/referrals/commission-balance.php',
    'Commission Records' => 'http://localhost/aureus-angel-alliance/api/admin/commission-records.php',
    'Withdrawal History' => 'http://localhost/aureus-angel-alliance/api/referrals/withdrawal-history.php'
];

echo "<h3>Testing Dashboard Component APIs:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Component</th><th>Status</th><th>Response Type</th><th>Success</th><th>Data Summary</th><th>Error Details</th></tr>";

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
                            if (isset($jsonData['stats'])) {
                                $stats = $jsonData['stats'];
                                echo "Stats: " . ($stats['totalReferrals'] ?? 0) . " referrals, $" . ($stats['totalCommissions'] ?? 0) . " commissions";
                            } elseif (isset($jsonData['records'])) {
                                echo "Records: " . count($jsonData['records']) . " commission records";
                            } elseif (isset($jsonData['data'])) {
                                if (is_array($jsonData['data'])) {
                                    if (isset($jsonData['data']['participations'])) {
                                        echo "Participations: " . count($jsonData['data']['participations']);
                                    } elseif (isset($jsonData['data']['countdowns'])) {
                                        echo "Countdowns: " . count($jsonData['data']['countdowns']);
                                    } elseif (isset($jsonData['data']['profile'])) {
                                        echo "Profile: " . ($jsonData['data']['profile']['username'] ?? 'N/A');
                                    } else {
                                        echo "Data: " . count($jsonData['data']) . " items";
                                    }
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

// Component Status Summary
echo "<h3>Component Status Summary:</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Fixed Components:</h4>";
echo "<ul>";
echo "<li><strong>DeliveryCountdown:</strong> Fixed undefined property access errors and prop mapping</li>";
echo "<li><strong>InvestmentCountdownList:</strong> Fixed prop names (participationId → investmentId, reward → roi)</li>";
echo "<li><strong>Referral APIs:</strong> Fixed CORS headers and API configuration</li>";
echo "<li><strong>Commission APIs:</strong> Fixed relative paths to full URLs</li>";
echo "<li><strong>Portfolio View:</strong> Fixed API endpoint URLs</li>";
echo "<li><strong>All Components:</strong> Added comprehensive error handling and safety checks</li>";
echo "</ul>";

echo "<h4>🎯 Expected Results:</h4>";
echo "<ul>";
echo "<li>No more TypeError: Cannot read properties of undefined</li>";
echo "<li>No more Failed to fetch errors</li>";
echo "<li>All components render without crashes</li>";
echo "<li>Proper fallback data when APIs are unavailable</li>";
echo "<li>Clean console without critical errors</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
