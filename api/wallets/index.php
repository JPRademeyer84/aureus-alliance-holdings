<?php
require_once '../config/database.php';
require_once '../config/cors.php';

handlePreflight();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Tables should already exist - no automatic creation

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Get all investment wallets
            $query = "SELECT * FROM investment_wallets ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendSuccessResponse($wallets, 'Wallets retrieved successfully');
            break;

        case 'POST':
            // Create new wallet
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['chain']) || !isset($input['address'])) {
                sendErrorResponse('Chain and address are required', 400);
            }

            // Check if wallet already exists
            $query = "SELECT id FROM investment_wallets WHERE chain = ? AND address = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$input['chain'], $input['address']]);
            
            if ($stmt->fetch()) {
                sendErrorResponse('Wallet already exists for this chain', 400);
            }

            $is_active = isset($input['is_active']) ? $input['is_active'] : true;

            $query = "INSERT INTO investment_wallets (chain, address, is_active) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$input['chain'], $input['address'], $is_active]);

            sendSuccessResponse(['id' => $db->lastInsertId()], 'Wallet created successfully');
            break;

        case 'PUT':
            // Update wallet
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                sendErrorResponse('Wallet ID is required', 400);
            }

            $fields = [];
            $values = [];
            
            $allowed_fields = ['chain', 'address', 'is_active'];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                sendErrorResponse('No fields to update', 400);
            }
            
            $values[] = $input['id'];
            
            $query = "UPDATE investment_wallets SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute($values);

            sendSuccessResponse(null, 'Wallet updated successfully');
            break;

        case 'DELETE':
            // Delete wallet
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                sendErrorResponse('Wallet ID is required', 400);
            }

            $query = "DELETE FROM investment_wallets WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$input['id']]);

            sendSuccessResponse(null, 'Wallet deleted successfully');
            break;

        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
