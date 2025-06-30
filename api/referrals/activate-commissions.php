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
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Initialize security manager
    $securityManager = new CommissionSecurityManager($db);
    
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
    
    switch ($action) {
        case 'activate_pending':
            // Activate all pending commissions (make them available for withdrawal)
            // This would typically be called by admin or automated system
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
                exit;
            }
            
            $db->beginTransaction();
            
            try {
                // Update all pending commissions to paid status
                $updateQuery = "UPDATE referral_commissions SET status = 'paid' WHERE status = 'pending'";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute();
                
                $activatedCount = $updateStmt->rowCount();

                // Update secure user commission balances for all affected users
                $affectedUsersQuery = "SELECT DISTINCT referrer_user_id FROM referral_commissions WHERE status = 'paid'";
                $affectedUsersStmt = $db->prepare($affectedUsersQuery);
                $affectedUsersStmt->execute();
                $affectedUsers = $affectedUsersStmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($affectedUsers as $userId) {
                    try {
                        // Get user's commission totals
                        $userCommissionsQuery = "
                            SELECT
                                SUM(commission_usdt) as total_usdt,
                                SUM(commission_nft) as total_nft,
                                SUM(CASE WHEN status = 'paid' THEN commission_usdt ELSE 0 END) as available_usdt,
                                SUM(CASE WHEN status = 'paid' THEN commission_nft ELSE 0 END) as available_nft
                            FROM referral_commissions
                            WHERE referrer_user_id = ?
                        ";

                        $userCommissionsStmt = $db->prepare($userCommissionsQuery);
                        $userCommissionsStmt->execute([$userId]);
                        $userCommissions = $userCommissionsStmt->fetch(PDO::FETCH_ASSOC);

                        // Get current balance to preserve withdrawal history
                        $currentBalance = $securityManager->getSecureUserBalance($userId);

                        // Update secure balance
                        $securityManager->updateUserBalance(
                            $userId,
                            (float)$userCommissions['total_usdt'],
                            (int)$userCommissions['total_nft'],
                            (float)$userCommissions['available_usdt'],
                            (int)$userCommissions['available_nft'],
                            $currentBalance['total_usdt_withdrawn'],
                            $currentBalance['total_nft_redeemed'],
                            uniqid('activation_', true),
                            $_SESSION['admin_id']
                        );

                    } catch (Exception $e) {
                        error_log("Failed to update secure balance for user $userId during activation: " . $e->getMessage());
                    }
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Activated $activatedCount commission records",
                    'activated_count' => $activatedCount
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'activate_specific':
            // Activate specific commission records
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Admin authentication required']);
                exit;
            }
            
            $commissionIds = $input['commission_ids'] ?? [];
            
            if (empty($commissionIds)) {
                echo json_encode(['success' => false, 'error' => 'Commission IDs required']);
                exit;
            }
            
            $db->beginTransaction();
            
            try {
                $placeholders = str_repeat('?,', count($commissionIds) - 1) . '?';
                $updateQuery = "UPDATE referral_commissions SET status = 'paid' WHERE id IN ($placeholders) AND status = 'pending'";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute($commissionIds);
                
                $activatedCount = $updateStmt->rowCount();
                
                // Update affected user balances
                $affectedUsersQuery = "SELECT DISTINCT referrer_user_id FROM referral_commissions WHERE id IN ($placeholders)";
                $affectedUsersStmt = $db->prepare($affectedUsersQuery);
                $affectedUsersStmt->execute($commissionIds);
                $affectedUsers = $affectedUsersStmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($affectedUsers as $userId) {
                    $balanceUpdateQuery = "
                        INSERT INTO user_commission_balances (user_id, total_usdt_earned, total_nft_earned, available_usdt_balance, available_nft_balance)
                        SELECT 
                            ?,
                            COALESCE(SUM(commission_usdt), 0) as total_usdt,
                            COALESCE(SUM(commission_nft), 0) as total_nft,
                            COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_usdt ELSE 0 END), 0) as available_usdt,
                            COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_nft ELSE 0 END), 0) as available_nft
                        FROM referral_commissions 
                        WHERE referrer_user_id = ?
                        ON DUPLICATE KEY UPDATE
                            total_usdt_earned = VALUES(total_usdt_earned),
                            total_nft_earned = VALUES(total_nft_earned),
                            available_usdt_balance = VALUES(available_usdt_balance) - total_usdt_withdrawn,
                            available_nft_balance = VALUES(available_nft_balance) - total_nft_redeemed
                    ";
                    
                    $balanceUpdateStmt = $db->prepare($balanceUpdateQuery);
                    $balanceUpdateStmt->execute([$userId, $userId]);
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Activated $activatedCount commission records",
                    'activated_count' => $activatedCount
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'auto_activate':
            // Auto-activate commissions based on business rules
            // For example: activate commissions after 24 hours
            $db->beginTransaction();
            
            try {
                $autoActivateQuery = "
                    UPDATE referral_commissions 
                    SET status = 'paid' 
                    WHERE status = 'pending' 
                    AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ";
                
                $autoActivateStmt = $db->prepare($autoActivateQuery);
                $autoActivateStmt->execute();
                
                $activatedCount = $autoActivateStmt->rowCount();
                
                if ($activatedCount > 0) {
                    // Update user balances for auto-activated commissions
                    $balanceUpdateQuery = "
                        INSERT INTO user_commission_balances (user_id, total_usdt_earned, total_nft_earned, available_usdt_balance, available_nft_balance)
                        SELECT 
                            rc.referrer_user_id,
                            SUM(rc.commission_usdt) as total_usdt,
                            SUM(rc.commission_nft) as total_nft,
                            SUM(CASE WHEN rc.status = 'paid' THEN rc.commission_usdt ELSE 0 END) as available_usdt,
                            SUM(CASE WHEN rc.status = 'paid' THEN rc.commission_nft ELSE 0 END) as available_nft
                        FROM referral_commissions rc
                        GROUP BY rc.referrer_user_id
                        ON DUPLICATE KEY UPDATE
                            total_usdt_earned = VALUES(total_usdt_earned),
                            total_nft_earned = VALUES(total_nft_earned),
                            available_usdt_balance = VALUES(available_usdt_balance) - total_usdt_withdrawn,
                            available_nft_balance = VALUES(available_nft_balance) - total_nft_redeemed
                    ";
                    
                    $db->exec($balanceUpdateQuery);
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Auto-activated $activatedCount commission records",
                    'activated_count' => $activatedCount
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    error_log("Commission activation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
