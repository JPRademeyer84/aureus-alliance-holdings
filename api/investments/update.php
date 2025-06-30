<?php
require_once '../config/database.php';
require_once '../config/cors.php';

handlePreflight();

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['id']) || !isset($input['status'])) {
        sendErrorResponse('Investment ID and status are required', 400);
    }

    $investment_id = $input['id'];
    $status = $input['status'];
    $tx_hash = $input['txHash'] ?? null;

    // Validate status
    $valid_statuses = ['pending', 'completed', 'failed'];
    if (!in_array($status, $valid_statuses)) {
        sendErrorResponse('Invalid status. Must be one of: ' . implode(', ', $valid_statuses), 400);
    }

    // Build update query
    $query = "UPDATE aureus_investments SET status = ?, updated_at = NOW()";
    $params = [$status];

    if ($tx_hash !== null) {
        $query .= ", tx_hash = ?";
        $params[] = $tx_hash;
    }

    $query .= " WHERE id = ?";
    $params[] = $investment_id;

    $stmt = $db->prepare($query);
    $success = $stmt->execute($params);

    if (!$success) {
        throw new Exception('Failed to update investment record');
    }

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        sendErrorResponse('Investment not found', 404);
    }

    // Get updated investment record
    $query = "SELECT * FROM aureus_investments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$investment_id]);
    $investment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$investment) {
        sendErrorResponse('Investment not found after update', 404);
    }

    // Format response
    $response_data = [
        'id' => $investment['id'],
        'packageName' => $investment['package_name'],
        'amount' => (float)$investment['amount'],
        'shares' => (int)$investment['shares'],
        'roi' => (float)$investment['roi'],
        'txHash' => $investment['tx_hash'],
        'chainId' => $investment['chain'],
        'walletAddress' => $investment['wallet_address'],
        'status' => $investment['status'],
        'createdAt' => $investment['created_at'],
        'updatedAt' => $investment['updated_at']
    ];

    sendSuccessResponse($response_data, 'Investment updated successfully');

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
