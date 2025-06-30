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

    // Get wallet address from query parameters
    $wallet_address = $_GET['wallet'] ?? null;

    if (!$wallet_address) {
        sendErrorResponse('Wallet address is required', 400);
    }

    // Get investment history for the wallet
    $query = "SELECT * FROM aureus_investments WHERE wallet_address = ? ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$wallet_address]);
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response data
    $response_data = [];
    foreach ($investments as $investment) {
        $response_data[] = [
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
    }

    sendSuccessResponse($response_data, 'Investment history retrieved successfully');

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
