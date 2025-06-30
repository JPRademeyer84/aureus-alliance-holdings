<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if we need to filter investments without certificates
    $withoutCertificates = isset($_GET['without_certificates']) && $_GET['without_certificates'] === 'true';
    $userId = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    // Build the query
    $query = "SELECT 
        ai.*,
        u.username,
        u.email,
        u.full_name" . 
        ($withoutCertificates ? ", sc.id as certificate_id" : "") . "
    FROM aureus_investments ai
    LEFT JOIN users u ON ai.user_id = u.id";
    
    if ($withoutCertificates) {
        $query .= " LEFT JOIN share_certificates sc ON ai.id = sc.investment_id";
    }
    
    $query .= " WHERE 1=1";
    
    $params = [];
    
    if ($withoutCertificates) {
        $query .= " AND sc.id IS NULL";
    }
    
    if ($userId) {
        $query .= " AND ai.user_id = ?";
        $params[] = $userId;
    }
    
    if ($status) {
        $query .= " AND ai.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY ai.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM aureus_investments ai";
    if ($withoutCertificates) {
        $countQuery .= " LEFT JOIN share_certificates sc ON ai.id = sc.investment_id";
    }
    $countQuery .= " WHERE 1=1";
    
    $countParams = [];
    
    if ($withoutCertificates) {
        $countQuery .= " AND sc.id IS NULL";
    }
    
    if ($userId) {
        $countQuery .= " AND ai.user_id = ?";
        $countParams[] = $userId;
    }
    
    if ($status) {
        $countQuery .= " AND ai.status = ?";
        $countParams[] = $status;
    }

    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Format the investments data
    $formattedInvestments = array_map(function($investment) {
        return [
            'id' => $investment['id'],
            'user_id' => $investment['user_id'],
            'username' => $investment['username'],
            'email' => $investment['email'],
            'full_name' => $investment['full_name'],
            'package_name' => $investment['package_name'],
            'amount' => (float)$investment['amount'],
            'shares' => (int)$investment['shares'],
            'roi' => (float)$investment['roi'],
            'tx_hash' => $investment['tx_hash'],
            'chain' => $investment['chain'],
            'wallet_address' => $investment['wallet_address'],
            'status' => $investment['status'],
            'created_at' => $investment['created_at'],
            'updated_at' => $investment['updated_at']
        ];
    }, $investments);

    echo json_encode([
        'success' => true,
        'investments' => $formattedInvestments,
        'pagination' => [
            'total' => (int)$totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($formattedInvestments)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
