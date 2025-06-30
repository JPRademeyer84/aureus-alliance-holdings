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

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }

    $userId = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // First check if the table exists and what columns are available
        $tableCheck = "SHOW TABLES LIKE 'aureus_investments'";
        $tableStmt = $db->prepare($tableCheck);
        $tableStmt->execute();
        $tableExists = $tableStmt->fetch();

        if (!$tableExists) {
            sendSuccessResponse([
                'participations' => [],
                'total_count' => 0,
                'total_invested' => 0
            ], 'No investment table found - returning empty results');
            return;
        }

        // First, let's check what columns actually exist
        $columnsQuery = "SHOW COLUMNS FROM aureus_investments";
        $columnsStmt = $db->prepare($columnsQuery);
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Build query based on available columns
        $selectFields = [
            'ai.id',
            'COALESCE(ai.amount, 0) as amount',
            'ai.created_at'
        ];

        // Add optional fields if they exist
        if (in_array('package_name', $columns)) {
            $selectFields[] = 'ai.package_name';
        } elseif (in_array('investment_plan', $columns)) {
            $selectFields[] = 'ai.investment_plan as package_name';
        } else {
            $selectFields[] = "'Unknown Package' as package_name";
        }

        if (in_array('shares', $columns)) {
            $selectFields[] = 'COALESCE(ai.shares, 0) as shares_purchased';
        } else {
            $selectFields[] = '0 as shares_purchased';
        }

        if (in_array('status', $columns)) {
            $selectFields[] = 'COALESCE(ai.status, "pending") as status';
        } else {
            $selectFields[] = '"pending" as status';
        }

        if (in_array('tx_hash', $columns)) {
            $selectFields[] = 'COALESCE(ai.tx_hash, "") as transaction_hash';
        } elseif (in_array('transaction_hash', $columns)) {
            $selectFields[] = 'COALESCE(ai.transaction_hash, "") as transaction_hash';
        } else {
            $selectFields[] = '"" as transaction_hash';
        }

        if (in_array('wallet_address', $columns)) {
            $selectFields[] = 'COALESCE(ai.wallet_address, "") as wallet_address';
        } else {
            $selectFields[] = '"" as wallet_address';
        }

        if (in_array('updated_at', $columns)) {
            $selectFields[] = 'COALESCE(ai.updated_at, ai.created_at) as updated_at';
        } else {
            $selectFields[] = 'ai.created_at as updated_at';
        }

        if (in_array('nft_delivery_date', $columns)) {
            $selectFields[] = 'ai.nft_delivery_date';
        } else {
            $selectFields[] = 'NULL as nft_delivery_date';
        }

        if (in_array('roi_delivery_date', $columns)) {
            $selectFields[] = 'ai.roi_delivery_date';
        } else {
            $selectFields[] = 'NULL as roi_delivery_date';
        }

        // Add participation status based on status field
        $selectFields[] = 'CASE
            WHEN COALESCE(ai.status, "pending") = "completed" THEN "active"
            WHEN COALESCE(ai.status, "pending") = "pending" THEN "pending"
            ELSE "inactive"
        END as participation_status';

        $query = "SELECT " . implode(', ', $selectFields) . "
            FROM aureus_investments ai
            WHERE ai.user_id = ?
            ORDER BY ai.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for frontend consumption
        $formattedParticipations = [];
        foreach ($participations as $participation) {
            $amount = floatval($participation['amount'] ?? 0);
            $formattedParticipations[] = [
                'id' => $participation['id'],
                'amount' => $amount,
                'package_name' => $participation['package_name'] ?? 'Unknown Package',
                'package_price' => $amount, // Use amount as package price if not available
                'shares_purchased' => intval($participation['shares_purchased'] ?? 0),
                'status' => $participation['status'] ?? 'pending',
                'participation_status' => $participation['participation_status'] ?? 'pending',
                'transaction_hash' => $participation['transaction_hash'] ?? '',
                'wallet_address' => $participation['wallet_address'] ?? '',
                'created_at' => $participation['created_at'],
                'updated_at' => $participation['updated_at'],
                'nft_delivery_date' => $participation['nft_delivery_date'],
                'roi_delivery_date' => $participation['roi_delivery_date'],
                'days_remaining' => $participation['nft_delivery_date'] ?
                    max(0, ceil((strtotime($participation['nft_delivery_date']) - time()) / (24 * 60 * 60))) : 0
            ];
        }

        sendSuccessResponse([
            'participations' => $formattedParticipations,
            'total_count' => count($formattedParticipations),
            'total_invested' => array_sum(array_column($formattedParticipations, 'amount'))
        ], 'Participation history retrieved successfully');

    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Participation history error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
