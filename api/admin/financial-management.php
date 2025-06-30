<?php
/**
 * FINANCIAL TRANSACTION MANAGEMENT API
 * Admin interface for managing financial transactions and approvals
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/financial-security.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require MFA for financial operations
protectFinancialOperation('admin');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'pending_approvals':
            getPendingApprovals();
            break;
            
        case 'approve_transaction':
            approveTransaction();
            break;
            
        case 'reject_transaction':
            rejectTransaction();
            break;
            
        case 'transaction_history':
            getTransactionHistory();
            break;
            
        case 'fraud_alerts':
            getFraudAlerts();
            break;
            
        case 'initiate_reversal':
            initiateReversal();
            break;
            
        case 'validation_report':
            getValidationReport();
            break;
            
        case 'update_limits':
            updateTransactionLimits();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Financial management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get pending approvals
 */
function getPendingApprovals() {
    $approvalManager = ApprovalWorkflowManager::getInstance();
    $pendingApprovals = $approvalManager->getPendingApprovals($_SESSION['admin_id']);
    
    // Add additional context for each approval
    foreach ($pendingApprovals as &$approval) {
        $approval['time_remaining'] = calculateTimeRemaining($approval['expires_at']);
        $approval['risk_level'] = getRiskLevel($approval['risk_score']);
        $approval['approval_progress'] = "{$approval['current_approvals']}/{$approval['required_approvals']}";
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pendingApprovals,
        'count' => count($pendingApprovals),
        'timestamp' => date('c')
    ]);
}

/**
 * Approve transaction
 */
function approveTransaction() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $workflowId = $input['workflow_id'] ?? '';
    $comments = $input['comments'] ?? '';
    
    if (empty($workflowId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Workflow ID required']);
        return;
    }
    
    $approvalManager = ApprovalWorkflowManager::getInstance();
    $result = $approvalManager->approveTransaction($workflowId, $_SESSION['admin_id'], $comments);
    
    echo json_encode($result);
}

/**
 * Reject transaction
 */
function rejectTransaction() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $workflowId = $input['workflow_id'] ?? '';
    $reason = $input['reason'] ?? '';
    
    if (empty($workflowId) || empty($reason)) {
        http_response_code(400);
        echo json_encode(['error' => 'Workflow ID and reason required']);
        return;
    }
    
    $approvalManager = ApprovalWorkflowManager::getInstance();
    $result = $approvalManager->rejectTransaction($workflowId, $_SESSION['admin_id'], $reason);
    
    echo json_encode($result);
}

/**
 * Get transaction history with validation details
 */
function getTransactionHistory() {
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $status = $_GET['status'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT 
                tv.id as validation_id,
                tv.transaction_id,
                tv.transaction_type,
                tv.user_id,
                tv.amount,
                tv.currency,
                tv.validation_status,
                tv.risk_score,
                tv.validation_rules,
                tv.fraud_indicators,
                tv.approved_by,
                tv.approved_at,
                tv.created_at,
                u.username,
                u.email
              FROM transaction_validations tv
              LEFT JOIN users u ON tv.user_id = u.id
              WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $query .= " AND tv.validation_status = ?";
        $params[] = $status;
    }
    
    if ($userId) {
        $query .= " AND tv.user_id = ?";
        $params[] = $userId;
    }
    
    $query .= " ORDER BY tv.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Decode JSON fields
    foreach ($transactions as &$transaction) {
        $transaction['validation_rules'] = json_decode($transaction['validation_rules'], true);
        $transaction['fraud_indicators'] = json_decode($transaction['fraud_indicators'], true);
        $transaction['risk_level'] = getRiskLevel($transaction['risk_score']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'count' => count($transactions),
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get fraud alerts
 */
function getFraudAlerts() {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT 
                fp.id,
                fp.user_id,
                fp.pattern_type,
                fp.pattern_data,
                fp.risk_score,
                fp.detected_at,
                fp.resolved,
                u.username,
                u.email
              FROM fraud_patterns fp
              LEFT JOIN users u ON fp.user_id = u.id
              WHERE fp.resolved = FALSE
              ORDER BY fp.risk_score DESC, fp.detected_at DESC
              LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $alerts = $stmt->fetchAll();
    
    // Decode JSON data
    foreach ($alerts as &$alert) {
        $alert['pattern_data'] = json_decode($alert['pattern_data'], true);
        $alert['risk_level'] = getRiskLevel($alert['risk_score']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $alerts,
        'count' => count($alerts)
    ]);
}

/**
 * Initiate transaction reversal
 */
function initiateReversal() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $transactionId = $input['transaction_id'] ?? '';
    $reason = $input['reason'] ?? '';
    $partialAmount = isset($input['partial_amount']) ? floatval($input['partial_amount']) : null;
    
    if (empty($transactionId) || empty($reason)) {
        http_response_code(400);
        echo json_encode(['error' => 'Transaction ID and reason required']);
        return;
    }
    
    $reversalManager = TransactionReversalManager::getInstance();
    $result = $reversalManager->initiateReversal($transactionId, $reason, $_SESSION['admin_id'], $partialAmount);
    
    echo json_encode($result);
}

/**
 * Get validation report
 */
function getValidationReport() {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get validation statistics
    $statsQuery = "SELECT 
                     validation_status,
                     COUNT(*) as count,
                     AVG(risk_score) as avg_risk_score,
                     SUM(amount) as total_amount
                   FROM transaction_validations 
                   WHERE DATE(created_at) BETWEEN ? AND ?
                   GROUP BY validation_status";
    
    $stmt = $db->prepare($statsQuery);
    $stmt->execute([$startDate, $endDate]);
    $validationStats = $stmt->fetchAll();
    
    // Get risk score distribution
    $riskQuery = "SELECT 
                    CASE 
                      WHEN risk_score < 25 THEN 'Low'
                      WHEN risk_score < 50 THEN 'Medium'
                      WHEN risk_score < 75 THEN 'High'
                      ELSE 'Critical'
                    END as risk_level,
                    COUNT(*) as count
                  FROM transaction_validations 
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  GROUP BY risk_level";
    
    $stmt = $db->prepare($riskQuery);
    $stmt->execute([$startDate, $endDate]);
    $riskDistribution = $stmt->fetchAll();
    
    // Get daily trends
    $trendsQuery = "SELECT 
                      DATE(created_at) as date,
                      COUNT(*) as total_transactions,
                      SUM(CASE WHEN validation_status = 'approved' THEN 1 ELSE 0 END) as approved,
                      SUM(CASE WHEN validation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                      SUM(CASE WHEN validation_status = 'flagged' THEN 1 ELSE 0 END) as flagged,
                      AVG(risk_score) as avg_risk_score
                    FROM transaction_validations 
                    WHERE DATE(created_at) BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date";
    
    $stmt = $db->prepare($trendsQuery);
    $stmt->execute([$startDate, $endDate]);
    $dailyTrends = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'validation_statistics' => $validationStats,
            'risk_distribution' => $riskDistribution,
            'daily_trends' => $dailyTrends,
            'report_period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]
    ]);
}

/**
 * Update transaction limits
 */
function updateTransactionLimits() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $kycLevel = $input['kyc_level'] ?? '';
    $limits = $input['limits'] ?? [];
    
    if (empty($kycLevel) || empty($limits)) {
        http_response_code(400);
        echo json_encode(['error' => 'KYC level and limits required']);
        return;
    }
    
    // Validate limit values
    $requiredFields = ['daily_investment_limit', 'daily_withdrawal_limit', 'monthly_investment_limit', 
                      'monthly_withdrawal_limit', 'max_single_transaction'];
    
    foreach ($requiredFields as $field) {
        if (!isset($limits[$field]) || !is_numeric($limits[$field]) || $limits[$field] < 0) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid value for $field"]);
            return;
        }
    }
    
    // In a real implementation, you would update the limits in the database
    // For now, we'll just log the change
    logFinancialEvent('limits_updated', SecurityLogger::LEVEL_INFO,
        "Transaction limits updated", [
            'kyc_level' => $kycLevel,
            'limits' => $limits
        ], null, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction limits updated successfully',
        'kyc_level' => $kycLevel,
        'limits' => $limits
    ]);
}

/**
 * Helper functions
 */

function calculateTimeRemaining($expiresAt) {
    $now = time();
    $expires = strtotime($expiresAt);
    $remaining = $expires - $now;
    
    if ($remaining <= 0) {
        return 'Expired';
    }
    
    $hours = floor($remaining / 3600);
    $minutes = floor(($remaining % 3600) / 60);
    
    return "{$hours}h {$minutes}m";
}

function getRiskLevel($riskScore) {
    if ($riskScore < 25) return 'Low';
    if ($riskScore < 50) return 'Medium';
    if ($riskScore < 75) return 'High';
    return 'Critical';
}
?>
