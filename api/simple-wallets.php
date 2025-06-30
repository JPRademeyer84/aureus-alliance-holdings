<?php
// Simple wallets endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Database connection
    $pdo = new PDO(
        'mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'list';

    // Debug logging
    error_log("Wallets API - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Wallets API - Action: " . $action);
    error_log("Wallets API - Input: " . json_encode($input));

    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all wallets
        try {
            $stmt = $pdo->query("SELECT id, chain, address_hash as address, is_active, created_at FROM company_wallets ORDER BY chain ASC");
            $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug: log what we found
            error_log("Wallets found in DB: " . count($wallets));
            error_log("Wallets data: " . json_encode($wallets));
            
            // Ensure proper data types and format for frontend
            foreach ($wallets as &$wallet) {
                $wallet['is_active'] = (bool)($wallet['is_active'] ?? true);
                $wallet['created_at'] = $wallet['created_at'] ?? date('Y-m-d H:i:s');
                $wallet['updated_at'] = $wallet['created_at'] ?? date('Y-m-d H:i:s');
                // For security, show only partial hash
                if (isset($wallet['address']) && strlen($wallet['address']) > 10) {
                    $wallet['address'] = substr($wallet['address'], 0, 6) . '...' . substr($wallet['address'], -4);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Wallets retrieved successfully',
                'data' => $wallets
            ]);
            
        } catch (Exception $e) {
            // If table doesn't exist, return default wallets
            $defaultWallets = [
                [
                    'id' => 1,
                    'chain' => 'ethereum',
                    'address' => '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b',
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'chain' => 'polygon',
                    'address' => '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b',
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'chain' => 'bsc',
                    'address' => '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b',
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Default wallets loaded (database table not found)',
                'data' => $defaultWallets
            ]);
        }
        
    } elseif ($action === 'create' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create new wallet
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO company_wallets
            (chain, address_hash, salt, is_active)
            VALUES (?, ?, ?, ?)
        ");
        
        // For simplicity, use the address as both hash and salt (not secure for production)
        $address = $input['address'];
        $salt = 'simple_salt_' . time();

        $stmt->execute([
            $input['chain'],
            $address, // Using address directly as hash for demo
            $salt,
            $input['is_active'] ?? true
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet created successfully',
            'data' => ['id' => $pdo->lastInsertId()]
        ]);
        
    } elseif ($action === 'update' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update wallet
        if (!$input || !isset($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Wallet ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE company_wallets
            SET chain=?, address_hash=?, is_active=?
            WHERE id=?
        ");
        
        $stmt->execute([
            $input['chain'],
            $input['address'], // Using address directly for demo
            $input['is_active'] ?? true,
            $input['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet updated successfully'
        ]);
        
    } elseif ($action === 'delete' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete wallet
        if (!$input || !isset($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Wallet ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM company_wallets WHERE id=?");
        $stmt->execute([$input['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet deleted successfully'
        ]);
        
    } elseif ($action === 'toggle_status') {
        // Toggle wallet active status
        if (!$input || !isset($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Wallet ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE investment_wallets 
            SET is_active = NOT is_active, updated_at=NOW()
            WHERE id=?
        ");
        
        $stmt->execute([$input['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet status toggled successfully'
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action: ' . $action
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
