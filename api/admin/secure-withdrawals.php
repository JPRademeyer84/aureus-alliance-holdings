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
    // Check if admin is authenticated
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Admin authentication required'
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize security systems
    $securityManager = new CommissionSecurityManager($db);
    $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get pending withdrawals for admin processing
        $result = $withdrawalScheduler->getPendingWithdrawalsForAdmin();
        echo json_encode($result);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'process_withdrawal':
                $withdrawalId = $input['withdrawal_id'] ?? '';
                $status = $input['status'] ?? '';
                $transactionHash = trim($input['transaction_hash'] ?? '');
                $blockchainHash = trim($input['blockchain_hash'] ?? '');
                $adminNotes = $input['admin_notes'] ?? '';
                
                if (empty($withdrawalId) || empty($status)) {
                    echo json_encode(['success' => false, 'error' => 'Withdrawal ID and status required']);
                    exit;
                }
                
                // For completed withdrawals, blockchain hash is mandatory
                if ($status === 'completed' && empty($blockchainHash)) {
                    echo json_encode(['success' => false, 'error' => 'Blockchain confirmation hash is required for completed withdrawals']);
                    exit;
                }
                
                try {
                    $result = $withdrawalScheduler->adminProcessWithdrawal(
                        $withdrawalId,
                        $_SESSION['admin_id'],
                        $status,
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
                
            case 'get_business_hours':
                // Get current business hours configuration
                $hoursQuery = "SELECT * FROM business_hours_config WHERE is_active = TRUE ORDER BY day_of_week";
                $hoursStmt = $db->prepare($hoursQuery);
                $hoursStmt->execute();
                $businessHours = $hoursStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $isWithinHours = $withdrawalScheduler->isWithinBusinessHours();
                $nextBusinessDay = $isWithinHours ? null : date('Y-m-d H:i:s', $withdrawalScheduler->getNextBusinessDay());
                
                echo json_encode([
                    'success' => true,
                    'business_hours' => $businessHours,
                    'is_within_business_hours' => $isWithinHours,
                    'next_business_day' => $nextBusinessDay,
                    'current_time' => date('Y-m-d H:i:s'),
                    'current_day_of_week' => (int)date('N')
                ]);
                break;
                
            case 'update_business_hours':
                // Update business hours configuration
                $businessHours = $input['business_hours'] ?? [];
                
                if (empty($businessHours)) {
                    echo json_encode(['success' => false, 'error' => 'Business hours data required']);
                    exit;
                }
                
                $db->beginTransaction();
                
                try {
                    // Clear existing configuration
                    $clearQuery = "DELETE FROM business_hours_config";
                    $db->exec($clearQuery);
                    
                    // Insert new configuration
                    $insertQuery = "INSERT INTO business_hours_config (day_of_week, start_hour, end_hour, is_active, updated_by) VALUES (?, ?, ?, ?, ?)";
                    $insertStmt = $db->prepare($insertQuery);
                    
                    foreach ($businessHours as $dayConfig) {
                        $insertStmt->execute([
                            $dayConfig['day_of_week'],
                            $dayConfig['start_hour'],
                            $dayConfig['end_hour'],
                            $dayConfig['is_active'] ? 1 : 0,
                            $_SESSION['admin_id']
                        ]);
                    }
                    
                    $db->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Business hours updated successfully'
                    ]);
                    
                } catch (Exception $e) {
                    $db->rollback();
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to update business hours: ' . $e->getMessage()
                    ]);
                }
                break;
                
            case 'get_security_audit':
                // Get security audit log
                $limit = intval($input['limit'] ?? 50);
                $offset = intval($input['offset'] ?? 0);
                
                $auditQuery = "
                    SELECT 
                        sal.*,
                        u.username as user_username,
                        au.username as admin_username
                    FROM security_audit_log sal
                    LEFT JOIN users u ON sal.user_id = u.id
                    LEFT JOIN admin_users au ON sal.admin_id = au.id
                    ORDER BY sal.event_timestamp DESC
                    LIMIT ? OFFSET ?
                ";
                
                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([$limit, $offset]);
                $auditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get total count
                $countQuery = "SELECT COUNT(*) as total FROM security_audit_log";
                $countStmt = $db->prepare($countQuery);
                $countStmt->execute();
                $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                echo json_encode([
                    'success' => true,
                    'audit_logs' => $auditLogs,
                    'total_count' => (int)$totalCount,
                    'limit' => $limit,
                    'offset' => $offset
                ]);
                break;
                
            case 'verify_all_balances':
                // Verify integrity of all user balances
                $usersQuery = "SELECT DISTINCT user_id FROM commission_balances_primary";
                $usersStmt = $db->prepare($usersQuery);
                $usersStmt->execute();
                $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $verificationResults = [];
                $failedVerifications = 0;
                
                foreach ($userIds as $userId) {
                    $isValid = $securityManager->verifyBalanceIntegrity($userId);
                    $verificationResults[] = [
                        'user_id' => $userId,
                        'integrity_valid' => $isValid
                    ];
                    
                    if (!$isValid) {
                        $failedVerifications++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'verification_results' => $verificationResults,
                    'total_users_checked' => count($userIds),
                    'failed_verifications' => $failedVerifications,
                    'integrity_status' => $failedVerifications === 0 ? 'SECURE' : 'COMPROMISED'
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    error_log("Admin secure withdrawals error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
