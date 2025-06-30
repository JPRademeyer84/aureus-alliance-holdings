<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../utils/response.php';

header('Content-Type: application/json');

// Enable CORS
enableCORS();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Check admin authentication
    session_start();
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGetManualPayments($db);
            break;
        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetManualPayments($db) {
    $filter = $_GET['filter'] ?? 'all';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Validate limit
    if ($limit > 100) $limit = 100;
    if ($limit < 1) $limit = 10;
    
    // Build WHERE clause based on filter
    $whereClause = '';
    $params = [];
    
    switch ($filter) {
        case 'pending':
            $whereClause = 'WHERE mpt.verification_status = ?';
            $params[] = 'pending';
            break;
        case 'approved':
            $whereClause = 'WHERE mpt.verification_status = ?';
            $params[] = 'approved';
            break;
        case 'rejected':
            $whereClause = 'WHERE mpt.verification_status = ?';
            $params[] = 'rejected';
            break;
        case 'expired':
            $whereClause = 'WHERE mpt.expires_at < NOW() AND mpt.payment_status = ?';
            $params[] = 'pending';
            break;
        case 'all':
        default:
            // No filter
            break;
    }
    
    try {
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM manual_payment_transactions mpt 
                       JOIN users u ON mpt.user_id = u.id 
                       $whereClause";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get payments with pagination
        $query = "SELECT 
            mpt.id,
            mpt.payment_id,
            mpt.user_id,
            u.username,
            u.email,
            mpt.amount_usd,
            mpt.chain,
            mpt.company_wallet_address,
            mpt.sender_name,
            mpt.sender_wallet_address,
            mpt.transaction_hash,
            mpt.notes,
            mpt.payment_status,
            mpt.verification_status,
            mpt.created_at,
            mpt.expires_at,
            mpt.verified_by,
            mpt.verified_at,
            mpt.verification_notes,
            admin.username as verified_by_username,
            CASE 
                WHEN mpt.expires_at < NOW() AND mpt.payment_status = 'pending' THEN 'expired'
                ELSE mpt.payment_status
            END as effective_status,
            DATEDIFF(mpt.expires_at, NOW()) as days_until_expiry
        FROM manual_payment_transactions mpt
        JOIN users u ON mpt.user_id = u.id
        LEFT JOIN admin_users admin ON mpt.verified_by = admin.id
        $whereClause
        ORDER BY 
            CASE 
                WHEN mpt.verification_status = 'pending' AND mpt.expires_at > NOW() THEN 1
                WHEN mpt.verification_status = 'pending' AND mpt.expires_at <= NOW() THEN 2
                ELSE 3
            END,
            mpt.created_at DESC
        LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get summary statistics
        $statsQuery = "SELECT 
            COUNT(*) as total_payments,
            SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN verification_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN expires_at < NOW() AND payment_status = 'pending' THEN 1 ELSE 0 END) as expired_count,
            SUM(CASE WHEN verification_status = 'pending' THEN amount_usd ELSE 0 END) as pending_amount,
            SUM(CASE WHEN verification_status = 'approved' THEN amount_usd ELSE 0 END) as approved_amount
        FROM manual_payment_transactions mpt";
        
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format the response
        $response = [
            'data' => $payments,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'statistics' => [
                'total_payments' => (int)$stats['total_payments'],
                'pending_count' => (int)$stats['pending_count'],
                'approved_count' => (int)$stats['approved_count'],
                'rejected_count' => (int)$stats['rejected_count'],
                'expired_count' => (int)$stats['expired_count'],
                'pending_amount' => (float)$stats['pending_amount'],
                'approved_amount' => (float)$stats['approved_amount']
            ]
        ];
        
        sendSuccessResponse($response, 'Manual payments retrieved successfully');
        
    } catch (Exception $e) {
        throw new Exception('Failed to retrieve manual payments: ' . $e->getMessage());
    }
}

// Helper function to get payment proof file path
function getPaymentProofPath($paymentId) {
    // This would be used by a separate file serving endpoint
    // to securely serve payment proof files to authorized admins
    return "/api/files/serve.php?type=payment_proof&payment_id=" . urlencode($paymentId);
}

// Helper function to log admin actions
function logAdminAction($db, $adminId, $action, $paymentId, $details = []) {
    try {
        $query = "INSERT INTO security_audit_log (
            event_type, admin_id, event_details, security_level,
            ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'manual_payment_' . $action,
            $adminId,
            json_encode(array_merge(['payment_id' => $paymentId], $details)),
            'medium',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log('Failed to log admin action: ' . $e->getMessage());
    }
}

?>
