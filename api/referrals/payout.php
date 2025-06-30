<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../security/commission-security.php';
require_once '../security/withdrawal-scheduler.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Initialize security systems
    $securityManager = new CommissionSecurityManager($db);
    $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // All withdrawal operations now use the secure withdrawal scheduler
    // No need to create legacy tables

    switch ($action) {
        case 'request_withdrawal':
            // User requests SECURE withdrawal with business hours validation
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'User authentication required']);
                exit;
            }

            $userId = $_SESSION['user_id'];
            $withdrawalType = $input['type'] ?? 'usdt'; // usdt, nft (no reinvest here)
            $amount = floatval($input['amount'] ?? 0);
            $walletAddress = trim($input['wallet_address'] ?? '');
            $nftQuantity = intval($input['nft_quantity'] ?? 0);

            // Validate wallet address
            if (empty($walletAddress)) {
                echo json_encode(['success' => false, 'error' => 'Wallet address is required']);
                exit;
            }

            // Use secure withdrawal scheduler
            try {
                $result = $withdrawalScheduler->submitWithdrawalRequest(
                    $userId,
                    $withdrawalType,
                    $amount,
                    $nftQuantity,
                    $walletAddress
                );

                echo json_encode($result);

            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'admin_process':
            // Admin processes a withdrawal using SECURE withdrawal scheduler
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
                exit;
            }

            $withdrawalId = $input['withdrawal_id'] ?? '';
            $newStatus = $input['status'] ?? ''; // completed, failed, cancelled
            $transactionHash = trim($input['transaction_hash'] ?? '');
            $blockchainHash = trim($input['blockchain_hash'] ?? ''); // REQUIRED for completed withdrawals
            $adminNotes = $input['admin_notes'] ?? '';

            if (empty($withdrawalId) || empty($newStatus)) {
                echo json_encode(['success' => false, 'error' => 'Withdrawal ID and status required']);
                exit;
            }

            // For completed withdrawals, blockchain hash is MANDATORY
            if ($newStatus === 'completed' && empty($blockchainHash)) {
                echo json_encode(['success' => false, 'error' => 'Blockchain confirmation hash is required for completed withdrawals']);
                exit;
            }

            try {
                // Use secure withdrawal scheduler for admin processing
                $result = $withdrawalScheduler->adminProcessWithdrawal(
                    $withdrawalId,
                    $_SESSION['admin_id'],
                    $newStatus,
                    $transactionHash,
                    $blockchainHash,
                    $adminNotes
                );

                echo json_encode($result);

            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'update_balances':
            // This action is deprecated - balances are now managed by the secure system
            echo json_encode([
                'success' => false,
                'error' => 'Balance updates are now handled automatically by the secure commission system'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Commission payout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
