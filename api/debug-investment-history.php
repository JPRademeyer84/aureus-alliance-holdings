<?php
require_once 'config/database.php';
require_once 'config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

session_start();

header('Content-Type: application/json');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_info' => [],
    'database_info' => [],
    'table_info' => [],
    'investment_data' => []
];

try {
    // 1. Check session information
    $debug['session_info'] = [
        'session_id' => session_id(),
        'user_id_exists' => isset($_SESSION['user_id']),
        'user_id_value' => $_SESSION['user_id'] ?? null,
        'all_session_keys' => array_keys($_SESSION),
        'session_data' => $_SESSION
    ];

    // 2. Check database connection
    $database = new Database();
    $db = $database->getConnection();
    
    $debug['database_info'] = [
        'connection_successful' => $db !== null,
        'pdo_class' => get_class($db)
    ];

    // 3. Check if aureus_investments table exists
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE 'aureus_investments'");
        $tableExists = $tableCheck->rowCount() > 0;
        
        $debug['table_info']['aureus_investments_exists'] = $tableExists;
        
        if ($tableExists) {
            // Get table structure
            $structure = $db->query("DESCRIBE aureus_investments")->fetchAll(PDO::FETCH_ASSOC);
            $debug['table_info']['table_structure'] = $structure;
            
            // Count total records
            $countStmt = $db->query("SELECT COUNT(*) as total FROM aureus_investments");
            $debug['table_info']['total_records'] = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get all records (limited to 10 for debugging)
            $allRecords = $db->query("SELECT * FROM aureus_investments LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            $debug['table_info']['sample_records'] = $allRecords;
            
            // Check if user has any investments
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $userStmt = $db->prepare("SELECT * FROM aureus_investments WHERE user_id = ?");
                $userStmt->execute([$userId]);
                $userInvestments = $userStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $debug['investment_data'] = [
                    'user_id_searched' => $userId,
                    'user_investments_count' => count($userInvestments),
                    'user_investments' => $userInvestments
                ];
            }
        }
    } catch (Exception $e) {
        $debug['table_info']['error'] = $e->getMessage();
    }

    // 4. Check other possible table names
    $possibleTables = ['investments', 'user_investments', 'participations', 'aureus_participations'];
    foreach ($possibleTables as $tableName) {
        try {
            $tableCheck = $db->query("SHOW TABLES LIKE '$tableName'");
            if ($tableCheck->rowCount() > 0) {
                $debug['table_info']['alternative_tables'][$tableName] = [
                    'exists' => true,
                    'record_count' => $db->query("SELECT COUNT(*) as total FROM $tableName")->fetch(PDO::FETCH_ASSOC)['total']
                ];
            }
        } catch (Exception $e) {
            $debug['table_info']['alternative_tables'][$tableName] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // 5. Check users table to see if user exists
    if (isset($_SESSION['user_id'])) {
        try {
            $userStmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
            $userStmt->execute([$_SESSION['user_id']]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            $debug['user_info'] = [
                'user_exists' => $userData !== false,
                'user_data' => $userData
            ];
        } catch (Exception $e) {
            $debug['user_info'] = [
                'error' => $e->getMessage()
            ];
        }
    }

    // 6. Test the exact query from user-history.php
    if (isset($_SESSION['user_id'])) {
        try {
            $userId = $_SESSION['user_id'];
            $query = "SELECT 
                id,
                user_id,
                name,
                email,
                wallet_address,
                chain,
                amount,
                investment_plan,
                package_name,
                shares,
                roi,
                tx_hash,
                status,
                created_at,
                updated_at,
                nft_delivery_date,
                roi_delivery_date,
                delivery_status,
                nft_delivered,
                roi_delivered
            FROM aureus_investments 
            WHERE user_id = ? 
            ORDER BY created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $debug['api_test'] = [
                'query_executed' => true,
                'results_count' => count($investments),
                'results' => $investments
            ];
        } catch (Exception $e) {
            $debug['api_test'] = [
                'query_executed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode($debug, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Debug script failed',
        'message' => $e->getMessage(),
        'partial_debug' => $debug
    ], JSON_PRETTY_PRINT);
}
?>
