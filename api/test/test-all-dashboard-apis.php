<?php
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

echo "<h2>Dashboard API Comprehensive Test</h2>";

$endpoints = [
    'Portfolio Data' => 'http://localhost/aureus-angel-alliance/api/investments/user-history.php',
    'Countdown Data' => 'http://localhost/aureus-angel-alliance/api/investments/countdown.php?action=get_user_countdowns&user_id=1',
    'Participation History' => 'http://localhost/aureus-angel-alliance/api/participations/user-history.php?user_id=1',
    'Commission Balance' => 'http://localhost/aureus-angel-alliance/api/referrals/commission-balance.php',
    'Enhanced Profile' => 'http://localhost/aureus-angel-alliance/api/users/enhanced-profile.php?action=get&user_id=1',
    'User Profile' => 'http://localhost/aureus-angel-alliance/api/users/profile/1',
    'Member Stats' => 'http://localhost/aureus-angel-alliance/api/affiliate/member-stats.php?member_id=1',
    'Member Investments' => 'http://localhost/aureus-angel-alliance/api/affiliate/member-investments.php?member_id=1'
];

echo "<h3>Testing All Dashboard API Endpoints:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Endpoint</th><th>Status</th><th>Response Type</th><th>Success</th><th>Data</th></tr>";

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
                    if ($success && isset($jsonData['data'])) {
                        $data = $jsonData['data'];
                        if (is_array($data)) {
                            if (isset($data['participations'])) {
                                echo "Participations: " . count($data['participations']);
                            } elseif (isset($data['countdowns'])) {
                                echo "Countdowns: " . count($data['countdowns']);
                            } elseif (isset($data['profile'])) {
                                echo "Profile: " . ($data['profile']['username'] ?? 'N/A');
                            } else {
                                echo "Data keys: " . implode(', ', array_keys($data));
                            }
                        } else {
                            echo "Data: " . substr(json_encode($data), 0, 50) . "...";
                        }
                    } else {
                        echo $jsonData['message'] ?? 'No data';
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

// Test session status
echo "<h3>Session Status:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ User session active: {$_SESSION['user_id']}</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No user session found</p>";
    echo "<p><a href='quick-login-test.php?auto_login=1'>Auto-login as test user</a></p>";
}

// Test database connection
echo "<h3>Database Status:</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check key tables
    $tables = ['users', 'aureus_investments', 'user_profiles', 'referral_commissions'];
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
