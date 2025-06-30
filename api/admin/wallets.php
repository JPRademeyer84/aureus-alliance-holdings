<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../utils/WalletSecurity.php';

// Set CORS headers immediately
setCorsHeaders();

handlePreflight();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create tables if they don't exist
    $database->createTables();

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendErrorResponse('Invalid JSON input', 400);
    }

    if (!isset($input['action'])) {
        sendErrorResponse('Action is required', 400);
    }

    $action = $input['action'];

    // Verify admin authentication for all actions
    if (!isset($input['adminId'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    $adminId = $input['adminId'];
    if (!WalletSecurity::verifyAdminPermissions($db, $adminId)) {
        sendErrorResponse('Invalid admin permissions', 403);
    }

    switch ($action) {
        case 'list':
            handleListWallets($db);
            break;
            
        case 'create':
            handleCreateWallet($db, $input, $adminId);
            break;
            
        case 'update':
            handleUpdateWallet($db, $input, $adminId);
            break;
            
        case 'delete':
            handleDeleteWallet($db, $input, $adminId);
            break;
            
        case 'get_active':
            handleGetActiveWallets($db);
            break;
            
        case 'backup':
            handleBackupWallets($db, $adminId);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleListWallets($db) {
    try {
        $query = "SELECT id, chain, is_active, created_at, updated_at FROM company_wallets ORDER BY chain";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add masked addresses for display
        foreach ($wallets as &$wallet) {
            // Get the actual address for masking
            $addressQuery = "SELECT address_hash, salt FROM company_wallets WHERE id = ?";
            $addressStmt = $db->prepare($addressQuery);
            $addressStmt->execute([$wallet['id']]);
            $addressData = $addressStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($addressData) {
                try {
                    $actualAddress = WalletSecurity::decryptWalletAddress($addressData['address_hash'], $addressData['salt']);
                    $wallet['masked_address'] = WalletSecurity::maskWalletAddress($actualAddress);
                } catch (Exception $e) {
                    $wallet['masked_address'] = 'Error decrypting';
                }
            } else {
                $wallet['masked_address'] = 'Not found';
            }
        }
        
        sendSuccessResponse($wallets, 'Wallets retrieved successfully');
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve wallets: ' . $e->getMessage(), 500);
    }
}

function handleCreateWallet($db, $input, $adminId) {
    try {
        // Validate required fields
        if (!isset($input['chain']) || !isset($input['address'])) {
            sendErrorResponse('Chain and address are required', 400);
        }
        
        $chain = strtolower(trim($input['chain']));
        $address = WalletSecurity::sanitizeWalletAddress($input['address']);
        
        // Validate chain
        $supportedChains = ['ethereum', 'bsc', 'polygon', 'tron'];
        if (!in_array($chain, $supportedChains)) {
            sendErrorResponse('Unsupported chain. Supported: ' . implode(', ', $supportedChains), 400);
        }
        
        // Validate address format
        if (!WalletSecurity::validateWalletAddress($address, $chain)) {
            sendErrorResponse('Invalid wallet address format for ' . $chain, 400);
        }
        
        // Check if wallet for this chain already exists
        $checkQuery = "SELECT id FROM company_wallets WHERE chain = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$chain]);
        
        if ($checkStmt->rowCount() > 0) {
            sendErrorResponse('Wallet for ' . $chain . ' already exists. Use update instead.', 409);
        }
        
        // Generate salt and hash the address
        $salt = WalletSecurity::generateSalt();
        $hashedAddress = WalletSecurity::hashWalletAddress($address, $salt);
        
        // Insert new wallet
        $insertQuery = "INSERT INTO company_wallets (chain, address_hash, salt, created_by) VALUES (?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertQuery);
        $success = $insertStmt->execute([$chain, $hashedAddress, $salt, $adminId]);
        
        if (!$success) {
            throw new Exception('Failed to insert wallet record');
        }
        
        // Log the operation
        $auditLog = WalletSecurity::generateAuditLog('create_wallet', $chain, $adminId, [
            'masked_address' => WalletSecurity::maskWalletAddress($address)
        ]);
        error_log("Wallet Management: " . json_encode($auditLog));
        
        sendSuccessResponse([
            'chain' => $chain,
            'masked_address' => WalletSecurity::maskWalletAddress($address),
            'is_active' => true
        ], 'Wallet created successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to create wallet: ' . $e->getMessage(), 500);
    }
}

function handleUpdateWallet($db, $input, $adminId) {
    try {
        // Validate required fields
        if (!isset($input['chain'])) {
            sendErrorResponse('Chain is required', 400);
        }
        
        $chain = strtolower(trim($input['chain']));
        
        // Check if wallet exists
        $checkQuery = "SELECT id FROM company_wallets WHERE chain = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$chain]);
        
        if ($checkStmt->rowCount() === 0) {
            sendErrorResponse('Wallet for ' . $chain . ' not found', 404);
        }
        
        $updateFields = [];
        $updateValues = [];
        
        // Update address if provided
        if (isset($input['address'])) {
            $address = WalletSecurity::sanitizeWalletAddress($input['address']);
            
            if (!WalletSecurity::validateWalletAddress($address, $chain)) {
                sendErrorResponse('Invalid wallet address format for ' . $chain, 400);
            }
            
            $salt = WalletSecurity::generateSalt();
            $hashedAddress = WalletSecurity::hashWalletAddress($address, $salt);
            
            $updateFields[] = "address_hash = ?";
            $updateFields[] = "salt = ?";
            $updateValues[] = $hashedAddress;
            $updateValues[] = $salt;
        }
        
        // Update active status if provided
        if (isset($input['is_active'])) {
            $updateFields[] = "is_active = ?";
            $updateValues[] = $input['is_active'] ? 1 : 0;
        }
        
        if (empty($updateFields)) {
            sendErrorResponse('No fields to update', 400);
        }
        
        $updateValues[] = $chain;
        
        $updateQuery = "UPDATE company_wallets SET " . implode(', ', $updateFields) . " WHERE chain = ?";
        $updateStmt = $db->prepare($updateQuery);
        $success = $updateStmt->execute($updateValues);
        
        if (!$success) {
            throw new Exception('Failed to update wallet record');
        }
        
        // Log the operation
        $auditLog = WalletSecurity::generateAuditLog('update_wallet', $chain, $adminId, [
            'updated_fields' => array_keys($input),
            'masked_address' => isset($address) ? WalletSecurity::maskWalletAddress($address) : null
        ]);
        error_log("Wallet Management: " . json_encode($auditLog));
        
        sendSuccessResponse(['chain' => $chain], 'Wallet updated successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to update wallet: ' . $e->getMessage(), 500);
    }
}

function handleDeleteWallet($db, $input, $adminId) {
    try {
        if (!isset($input['chain'])) {
            sendErrorResponse('Chain is required', 400);
        }
        
        $chain = strtolower(trim($input['chain']));
        
        // Check if wallet exists
        $checkQuery = "SELECT id FROM company_wallets WHERE chain = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$chain]);
        
        if ($checkStmt->rowCount() === 0) {
            sendErrorResponse('Wallet for ' . $chain . ' not found', 404);
        }
        
        // Delete wallet
        $deleteQuery = "DELETE FROM company_wallets WHERE chain = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $success = $deleteStmt->execute([$chain]);
        
        if (!$success) {
            throw new Exception('Failed to delete wallet record');
        }
        
        // Log the operation
        $auditLog = WalletSecurity::generateAuditLog('delete_wallet', $chain, $adminId);
        error_log("Wallet Management: " . json_encode($auditLog));
        
        sendSuccessResponse(['chain' => $chain], 'Wallet deleted successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to delete wallet: ' . $e->getMessage(), 500);
    }
}

function handleGetActiveWallets($db) {
    try {
        $query = "SELECT chain, address_hash, salt FROM company_wallets WHERE is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $activeWallets = [];
        foreach ($wallets as $wallet) {
            try {
                $address = WalletSecurity::decryptWalletAddress($wallet['address_hash'], $wallet['salt']);
                $activeWallets[$wallet['chain']] = $address;
            } catch (Exception $e) {
                error_log("Failed to decrypt wallet for chain " . $wallet['chain'] . ": " . $e->getMessage());
            }
        }
        
        sendSuccessResponse($activeWallets, 'Active wallets retrieved successfully');
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve active wallets: ' . $e->getMessage(), 500);
    }
}

function handleBackupWallets($db, $adminId) {
    try {
        $query = "SELECT * FROM company_wallets ORDER BY chain";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $backup = WalletSecurity::generateSecureBackup($wallets, $adminId);
        
        // Log the backup operation
        $auditLog = WalletSecurity::generateAuditLog('backup_wallets', 'all', $adminId, [
            'wallet_count' => count($wallets)
        ]);
        error_log("Wallet Management: " . json_encode($auditLog));
        
        sendSuccessResponse($backup, 'Wallet backup generated successfully');
    } catch (Exception $e) {
        sendErrorResponse('Failed to generate backup: ' . $e->getMessage(), 500);
    }
}
?>
