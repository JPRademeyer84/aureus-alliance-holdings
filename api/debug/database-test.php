<?php
require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tests = [];
    
    // Test 1: Database connection
    $tests['database_connection'] = [
        'status' => $db ? 'success' : 'failed',
        'message' => $db ? 'Database connected successfully' : 'Failed to connect to database'
    ];
    
    if ($db) {
        // Test 2: Users table
        try {
            $userQuery = "SELECT COUNT(*) as count FROM users";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute();
            $userCount = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $tests['users_table'] = [
                'status' => 'success',
                'message' => "Users table accessible",
                'count' => $userCount['count']
            ];
        } catch (Exception $e) {
            $tests['users_table'] = [
                'status' => 'failed',
                'message' => 'Users table error: ' . $e->getMessage()
            ];
        }
        
        // Test 3: User profiles table
        try {
            $profileQuery = "SELECT COUNT(*) as count FROM user_profiles";
            $profileStmt = $db->prepare($profileQuery);
            $profileStmt->execute();
            $profileCount = $profileStmt->fetch(PDO::FETCH_ASSOC);
            
            $tests['user_profiles_table'] = [
                'status' => 'success',
                'message' => "User profiles table accessible",
                'count' => $profileCount['count']
            ];
        } catch (Exception $e) {
            $tests['user_profiles_table'] = [
                'status' => 'failed',
                'message' => 'User profiles table error: ' . $e->getMessage()
            ];
        }
        
        // Test 4: Investment packages table
        try {
            $packageQuery = "SELECT COUNT(*) as count FROM investment_packages";
            $packageStmt = $db->prepare($packageQuery);
            $packageStmt->execute();
            $packageCount = $packageStmt->fetch(PDO::FETCH_ASSOC);
            
            $tests['investment_packages_table'] = [
                'status' => 'success',
                'message' => "Investment packages table accessible",
                'count' => $packageCount['count']
            ];
        } catch (Exception $e) {
            $tests['investment_packages_table'] = [
                'status' => 'failed',
                'message' => 'Investment packages table error: ' . $e->getMessage()
            ];
        }
        
        // Test 5: Company wallets table
        try {
            $walletQuery = "SELECT COUNT(*) as count FROM company_wallets";
            $walletStmt = $db->prepare($walletQuery);
            $walletStmt->execute();
            $walletCount = $walletStmt->fetch(PDO::FETCH_ASSOC);
            
            $tests['company_wallets_table'] = [
                'status' => 'success',
                'message' => "Company wallets table accessible",
                'count' => $walletCount['count']
            ];
        } catch (Exception $e) {
            $tests['company_wallets_table'] = [
                'status' => 'failed',
                'message' => 'Company wallets table error: ' . $e->getMessage()
            ];
        }
        
        // Test 6: Current session user data
        if (isset($_SESSION['user_id'])) {
            try {
                $userDataQuery = "SELECT u.id, u.username, u.email, u.full_name, 
                                         up.phone, up.country, up.city 
                                  FROM users u 
                                  LEFT JOIN user_profiles up ON u.id = up.user_id 
                                  WHERE u.id = ?";
                $userDataStmt = $db->prepare($userDataQuery);
                $userDataStmt->execute([$_SESSION['user_id']]);
                $userData = $userDataStmt->fetch(PDO::FETCH_ASSOC);
                
                $tests['current_user_data'] = [
                    'status' => 'success',
                    'message' => 'Current user data retrieved',
                    'data' => $userData
                ];
            } catch (Exception $e) {
                $tests['current_user_data'] = [
                    'status' => 'failed',
                    'message' => 'Current user data error: ' . $e->getMessage()
                ];
            }
        } else {
            $tests['current_user_data'] = [
                'status' => 'warning',
                'message' => 'No user session active'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database tests completed',
        'tests' => $tests,
        'session_info' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id()
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database test error: ' . $e->getMessage()
    ]);
}
?>
