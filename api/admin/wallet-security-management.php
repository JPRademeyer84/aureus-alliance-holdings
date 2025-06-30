<?php
/**
 * WALLET SECURITY MANAGEMENT API
 * Enterprise-grade wallet security administration
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/enterprise-wallet-security.php';
require_once '../config/multi-signature-wallet.php';
require_once '../config/cold-storage-manager.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and require fresh MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require fresh MFA for wallet operations
requireFreshMFA('admin', 300); // 5 minutes

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_secure_wallet':
            createSecureWalletEndpoint();
            break;
            
        case 'initiate_transaction':
            initiateTransactionEndpoint();
            break;
            
        case 'submit_approval':
            submitApprovalEndpoint();
            break;
            
        case 'pending_approvals':
            getPendingApprovalsEndpoint();
            break;
            
        case 'execute_transaction':
            executeTransactionEndpoint();
            break;
            
        case 'create_cold_storage':
            createColdStorageEndpoint();
            break;
            
        case 'cold_storage_transfer':
            coldStorageTransferEndpoint();
            break;
            
        case 'balance_check':
            balanceCheckEndpoint();
            break;
            
        case 'security_dashboard':
            getSecurityDashboard();
            break;
            
        case 'audit_trail':
            getAuditTrail();
            break;
            
        case 'emergency_override':
            emergencyOverrideEndpoint();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Wallet security management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Create secure wallet
 */
function createSecureWalletEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['name', 'chain', 'address', 'type'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $walletData = [
        'name' => $input['name'],
        'chain' => $input['chain'],
        'address' => $input['address'],
        'type' => $input['type'],
        'daily_limit' => $input['daily_limit'] ?? 10000,
        'monthly_limit' => $input['monthly_limit'] ?? 100000
    ];
    
    $result = createSecureWallet($walletData, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Secure wallet created successfully',
        'data' => $result
    ]);
}

/**
 * Initiate transaction
 */
function initiateTransactionEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['wallet_id', 'type', 'amount', 'destination'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $transactionData = [
        'type' => $input['type'],
        'amount' => $input['amount'],
        'destination' => $input['destination'],
        'description' => $input['description'] ?? '',
        'urgency' => $input['urgency'] ?? 'normal'
    ];
    
    $result = initiateSecureTransaction($input['wallet_id'], $transactionData, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction approval request created',
        'data' => $result
    ]);
}

/**
 * Submit approval
 */
function submitApprovalEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['approval_id', 'signature_type'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = submitWalletApproval(
        $input['approval_id'],
        $_SESSION['admin_id'],
        $input['signature_type'],
        $input['mfa_code'] ?? null
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Approval submitted successfully',
        'data' => $result
    ]);
}

/**
 * Get pending approvals
 */
function getPendingApprovalsEndpoint() {
    $signatureType = $_GET['signature_type'] ?? null;
    
    $approvals = getPendingWalletApprovals($_SESSION['admin_id'], $signatureType);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'pending_approvals' => $approvals,
            'total_count' => count($approvals)
        ]
    ]);
}

/**
 * Execute transaction
 */
function executeTransactionEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['approval_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing approval_id']);
        return;
    }
    
    $result = executeWalletTransaction($input['approval_id'], $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction executed successfully',
        'data' => $result
    ]);
}

/**
 * Create cold storage
 */
function createColdStorageEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['name', 'type', 'storage_protocol'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $vaultData = [
        'name' => $input['name'],
        'type' => $input['type'],
        'storage_protocol' => $input['storage_protocol'],
        'physical_location' => $input['physical_location'] ?? null,
        'emergency_recovery' => $input['emergency_recovery'] ?? null,
        'insurance_policy' => $input['insurance_policy'] ?? null,
        'insurance_amount' => $input['insurance_amount'] ?? 0
    ];
    
    $result = createColdStorageVault($vaultData, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cold storage vault created successfully',
        'data' => $result
    ]);
}

/**
 * Cold storage transfer
 */
function coldStorageTransferEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['vault_id', 'transfer_type', 'destination_address', 'amount', 'chain', 'justification'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = initiateColdStorageTransfer($input, $_SESSION['admin_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cold storage transfer initiated',
        'data' => $result
    ]);
}

/**
 * Balance check
 */
function balanceCheckEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['vault_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing vault_id']);
        return;
    }
    
    $coldStorage = ColdStorageManager::getInstance();
    $result = $coldStorage->performBalanceCheck(
        $input['vault_id'],
        $_SESSION['admin_id'],
        $input['physical_verification'] ?? false
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Balance check completed',
        'data' => $result
    ]);
}

/**
 * Get security dashboard
 */
function getSecurityDashboard() {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboard = [
        'wallet_statistics' => [],
        'pending_approvals' => [],
        'recent_transactions' => [],
        'security_alerts' => [],
        'cold_storage_summary' => []
    ];
    
    if ($db) {
        // Wallet statistics
        $query = "SELECT 
                    wallet_type,
                    security_level,
                    COUNT(*) as count,
                    SUM(daily_limit_usdt) as total_daily_limit
                  FROM secure_wallets 
                  WHERE is_active = TRUE
                  GROUP BY wallet_type, security_level";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['wallet_statistics'] = $stmt->fetchAll();
        
        // Pending approvals summary
        $query = "SELECT 
                    COUNT(*) as total_pending,
                    SUM(amount_usdt) as total_amount,
                    AVG(risk_score) as avg_risk_score
                  FROM wallet_transaction_approvals 
                  WHERE status = 'pending' AND expires_at > NOW()";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['pending_approvals'] = $stmt->fetch();
        
        // Recent transactions
        $query = "SELECT 
                    wta.id, wta.transaction_type, wta.amount_usdt, wta.status,
                    wta.initiated_at, sw.wallet_name, sw.chain
                  FROM wallet_transaction_approvals wta
                  JOIN secure_wallets sw ON wta.wallet_id = sw.id
                  ORDER BY wta.initiated_at DESC
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['recent_transactions'] = $stmt->fetchAll();
        
        // Cold storage summary
        $query = "SELECT 
                    vault_type,
                    COUNT(*) as vault_count,
                    SUM(total_balance_usdt) as total_balance
                  FROM cold_storage_vaults 
                  WHERE is_active = TRUE
                  GROUP BY vault_type";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $dashboard['cold_storage_summary'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dashboard
    ]);
}

/**
 * Get audit trail
 */
function getAuditTrail() {
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $walletId = $_GET['wallet_id'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($walletId) {
        $whereClause .= " AND wallet_id = ?";
        $params[] = $walletId;
    }
    
    $query = "SELECT 
                wsa.id, wsa.wallet_id, wsa.operation_type, wsa.operation_details,
                wsa.security_level_required, wsa.mfa_verified, wsa.timestamp,
                wsa.ip_address, au.username as admin_username
              FROM wallet_security_audit wsa
              LEFT JOIN admin_users au ON wsa.admin_id = au.id
              $whereClause
              ORDER BY wsa.timestamp DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $auditTrail = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'audit_trail' => $auditTrail,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Emergency override
 */
function emergencyOverrideEndpoint() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requiredFields = ['approval_id', 'override_reason', 'mfa_code'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $multiSig = MultiSignatureWallet::getInstance();
    $result = $multiSig->emergencyOverride(
        $input['approval_id'],
        $_SESSION['admin_id'],
        $input['override_reason'],
        $input['mfa_code']
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Emergency override executed',
        'data' => $result
    ]);
}
?>
