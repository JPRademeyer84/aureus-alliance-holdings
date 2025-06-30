<?php
require_once '../config/database.php';
require_once '../config/cors.php';

handlePreflight();

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendErrorResponse('Method not allowed', 405);
    }

    // Get investment ID from query parameters
    $investment_id = $_GET['id'] ?? null;

    if (!$investment_id) {
        sendErrorResponse('Investment ID is required', 400);
    }

    // Get investment by ID
    $query = "SELECT * FROM aureus_investments WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$investment_id]);
    $investment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$investment) {
        sendErrorResponse('Investment not found', 404);
    }

    // Format response data
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

    sendSuccessResponse($response_data, 'Investment retrieved successfully');

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
