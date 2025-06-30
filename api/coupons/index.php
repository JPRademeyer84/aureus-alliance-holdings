<?php
/**
 * NFT Coupons API
 * Handles coupon creation, redemption, and management
 */

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

// Response utility functions
function sendSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

/**
 * Validate admin authentication and return admin details
 * @param PDO $db Database connection
 * @return array Admin details if authenticated
 * @throws Exception if authentication fails
 */
function validateAdminAuth($db) {
    // Start session to check admin authentication (only if not already started)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['admin_id'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    // Verify admin exists and is active
    $query = "SELECT id, username, role, full_name FROM admin_users WHERE id = ? AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        sendErrorResponse('Invalid admin session', 401);
    }

    // Check if admin has sufficient permissions for coupon management
    if (!in_array($admin['role'], ['super_admin', 'admin'])) {
        sendErrorResponse('Insufficient permissions for coupon management', 403);
    }

    return $admin;
}

function generateCouponCode($length = 8) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get coupons (admin) or user's credits (user)
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'admin_coupons':
                        // ADMIN ONLY: Get all coupons
                        $admin = validateAdminAuth($db);

                        $query = "
                            SELECT
                                nc.id, nc.coupon_code, nc.value, nc.description,
                                nc.is_active, nc.is_used, nc.max_uses, nc.current_uses,
                                nc.expires_at, nc.notes,
                                nc.created_at, nc.updated_at,
                                u_used.username as used_by_username,
                                u_assigned.username as assigned_to_username,
                                au.username as created_by_username
                            FROM nft_coupons nc
                            LEFT JOIN users u_used ON nc.used_by = u_used.id
                            LEFT JOIN users u_assigned ON nc.assigned_to = u_assigned.id
                            LEFT JOIN admin_users au ON nc.created_by = au.id
                            ORDER BY nc.created_at DESC
                        ";

                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Log admin action for audit trail
                        $auditQuery = "
                            INSERT INTO security_audit_log (
                                event_type, admin_id, event_details, security_level,
                                ip_address, user_agent
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ";

                        $auditStmt = $db->prepare($auditQuery);
                        $auditStmt->execute([
                            'admin_action',
                            $admin['id'],
                            json_encode([
                                'action' => 'view_all_coupons',
                                'admin_username' => $admin['username'],
                                'timestamp' => date('c')
                            ]),
                            'info',
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);

                        sendSuccessResponse($coupons, 'Coupons retrieved successfully');
                        break;
                        
                    case 'user_credits':
                        // Get user's credit balance
                        session_start();
                        if (!isset($_SESSION['user_id'])) {
                            sendErrorResponse('User authentication required', 401);
                        }
                        
                        $query = "
                            SELECT 
                                uc.total_credits,
                                uc.used_credits,
                                uc.available_credits,
                                uc.updated_at
                            FROM user_credits uc
                            WHERE uc.user_id = ?
                        ";
                        
                        $stmt = $db->prepare($query);
                        $stmt->execute([$_SESSION['user_id']]);
                        $credits = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$credits) {
                            // Create user credits record if doesn't exist
                            $createQuery = "INSERT INTO user_credits (user_id) VALUES (?)";
                            $createStmt = $db->prepare($createQuery);
                            $createStmt->execute([$_SESSION['user_id']]);
                            
                            $credits = [
                                'total_credits' => '0.00',
                                'used_credits' => '0.00',
                                'available_credits' => '0.00',
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                        
                        sendSuccessResponse($credits, 'User credits retrieved successfully');
                        break;
                        
                    case 'credit_history':
                        // Get user's credit transaction history
                        session_start();
                        if (!isset($_SESSION['user_id'])) {
                            sendErrorResponse('User authentication required', 401);
                        }
                        
                        $query = "
                            SELECT 
                                ct.id, ct.transaction_type, ct.amount, ct.description,
                                ct.source_type, ct.created_at,
                                nc.coupon_code
                            FROM credit_transactions ct
                            LEFT JOIN nft_coupons nc ON ct.coupon_id = nc.id
                            WHERE ct.user_id = ?
                            ORDER BY ct.created_at DESC
                            LIMIT 50
                        ";
                        
                        $stmt = $db->prepare($query);
                        $stmt->execute([$_SESSION['user_id']]);
                        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        sendSuccessResponse($history, 'Credit history retrieved successfully');
                        break;
                        
                    default:
                        sendErrorResponse('Invalid action', 400);
                }
            } else {
                sendErrorResponse('Action parameter required', 400);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'create_coupon':
                        // ADMIN ONLY: Create new coupon
                        $admin = validateAdminAuth($db);
                        $adminId = $admin['id'];
                        
                        $required_fields = ['value'];
                        foreach ($required_fields as $field) {
                            if (!isset($input[$field]) || empty($input[$field])) {
                                sendErrorResponse("Field '$field' is required", 400);
                            }
                        }
                        
                        // Generate unique coupon code
                        $couponCode = $input['coupon_code'] ?? null;
                        if (!$couponCode) {
                            do {
                                $couponCode = generateCouponCode();
                                $checkQuery = "SELECT COUNT(*) as count FROM nft_coupons WHERE coupon_code = ?";
                                $checkStmt = $db->prepare($checkQuery);
                                $checkStmt->execute([$couponCode]);
                                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                            } while ($exists);
                        } else {
                            // Check if custom code already exists
                            $checkQuery = "SELECT COUNT(*) as count FROM nft_coupons WHERE coupon_code = ?";
                            $checkStmt = $db->prepare($checkQuery);
                            $checkStmt->execute([$couponCode]);
                            if ($checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                                sendErrorResponse('Coupon code already exists', 409);
                            }
                        }
                        
                        $query = "
                            INSERT INTO nft_coupons (
                                coupon_code, value, description, created_by, notes,
                                max_uses, expires_at, assigned_to
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ";
                        
                        $expiresAt = null;
                        if (isset($input['expires_in_days']) && $input['expires_in_days'] > 0) {
                            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $input['expires_in_days'] . ' days'));
                        }
                        
                        $assignedTo = null;
                        if (isset($input['assigned_username']) && !empty($input['assigned_username'])) {
                            $userQuery = "SELECT id FROM users WHERE username = ?";
                            $userStmt = $db->prepare($userQuery);
                            $userStmt->execute([$input['assigned_username']]);
                            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                            if ($user) {
                                $assignedTo = $user['id'];
                            }
                        }
                        
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            $couponCode,
                            $input['value'],
                            $input['description'] ?? '',
                            $adminId,
                            $input['notes'] ?? '',
                            $input['max_uses'] ?? 1,
                            $expiresAt,
                            $assignedTo
                        ]);
                        
                        // Log admin action
                        $auditQuery = "
                            INSERT INTO security_audit_log (
                                event_type, admin_id, event_details, security_level, 
                                ip_address, user_agent
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ";
                        
                        $auditStmt = $db->prepare($auditQuery);
                        $auditStmt->execute([
                            'admin_action',
                            $adminId,
                            json_encode([
                                'action' => 'create_nft_coupon',
                                'coupon_code' => $couponCode,
                                'value' => $input['value'],
                                'admin_username' => $admin['username'],
                                'admin_full_name' => $admin['full_name'],
                                'timestamp' => date('c')
                            ]),
                            'info',
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        sendSuccessResponse([
                            'coupon_code' => $couponCode,
                            'value' => $input['value']
                        ], 'Coupon created successfully');
                        break;
                        
                    case 'redeem_coupon':
                        // USER: Redeem coupon for credits
                        session_start();
                        if (!isset($_SESSION['user_id'])) {
                            sendErrorResponse('User authentication required', 401);
                        }
                        
                        if (!isset($input['coupon_code']) || empty($input['coupon_code'])) {
                            sendErrorResponse('Coupon code is required', 400);
                        }
                        
                        $couponCode = strtoupper(trim($input['coupon_code']));
                        
                        // Start transaction
                        $db->beginTransaction();
                        
                        try {
                            // Get coupon details with lock
                            $couponQuery = "
                                SELECT id, value, is_active, is_used, max_uses, current_uses,
                                       expires_at, assigned_to
                                FROM nft_coupons 
                                WHERE coupon_code = ? 
                                FOR UPDATE
                            ";
                            
                            $couponStmt = $db->prepare($couponQuery);
                            $couponStmt->execute([$couponCode]);
                            $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$coupon) {
                                throw new Exception('Invalid coupon code');
                            }
                            
                            if (!$coupon['is_active']) {
                                throw new Exception('Coupon is not active');
                            }
                            
                            if ($coupon['current_uses'] >= $coupon['max_uses']) {
                                throw new Exception('Coupon has been fully used');
                            }
                            
                            if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
                                throw new Exception('Coupon has expired');
                            }
                            
                            if ($coupon['assigned_to'] && $coupon['assigned_to'] !== $_SESSION['user_id']) {
                                throw new Exception('Coupon is assigned to another user');
                            }
                            
                            // Check if user already used this coupon
                            $usageQuery = "
                                SELECT COUNT(*) as count 
                                FROM credit_transactions 
                                WHERE user_id = ? AND coupon_id = ? AND transaction_type = 'earned'
                            ";
                            $usageStmt = $db->prepare($usageQuery);
                            $usageStmt->execute([$_SESSION['user_id'], $coupon['id']]);
                            if ($usageStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                                throw new Exception('You have already used this coupon');
                            }
                            
                            // Update coupon usage
                            $updateCouponQuery = "
                                UPDATE nft_coupons 
                                SET current_uses = current_uses + 1,
                                    is_used = CASE WHEN current_uses + 1 >= max_uses THEN TRUE ELSE FALSE END,
                                    used_by = CASE WHEN current_uses = 0 THEN ? ELSE used_by END,
                                    used_on = CASE WHEN current_uses = 0 THEN NOW() ELSE used_on END,
                                    updated_at = NOW()
                                WHERE id = ?
                            ";
                            
                            $updateCouponStmt = $db->prepare($updateCouponQuery);
                            $updateCouponStmt->execute([$_SESSION['user_id'], $coupon['id']]);
                            
                            // Create or update user credits
                            $creditsQuery = "
                                INSERT INTO user_credits (user_id, total_credits) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE 
                                total_credits = total_credits + VALUES(total_credits),
                                updated_at = NOW()
                            ";
                            
                            $creditsStmt = $db->prepare($creditsQuery);
                            $creditsStmt->execute([$_SESSION['user_id'], $coupon['value']]);
                            
                            // Record credit transaction
                            $transactionQuery = "
                                INSERT INTO credit_transactions (
                                    user_id, transaction_type, amount, description,
                                    source_type, source_id, coupon_id
                                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                            ";
                            
                            $transactionStmt = $db->prepare($transactionQuery);
                            $transactionStmt->execute([
                                $_SESSION['user_id'],
                                'earned',
                                $coupon['value'],
                                "Redeemed coupon: $couponCode",
                                'coupon',
                                $coupon['id'],
                                $coupon['id']
                            ]);
                            
                            $db->commit();
                            
                            sendSuccessResponse([
                                'credits_earned' => $coupon['value'],
                                'coupon_code' => $couponCode
                            ], 'Coupon redeemed successfully! $' . $coupon['value'] . ' credits added to your account.');
                            
                        } catch (Exception $e) {
                            $db->rollBack();
                            sendErrorResponse($e->getMessage(), 400);
                        }
                        break;
                        
                    default:
                        sendErrorResponse('Invalid action', 400);
                }
            } else {
                sendErrorResponse('Action parameter required', 400);
            }
            break;
            
        case 'PUT':
            // ADMIN ONLY: Update coupon
            $admin = validateAdminAuth($db);

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                sendErrorResponse('Coupon ID is required', 400);
            }
            
            $updateFields = [];
            $updateValues = [];
            
            if (isset($input['is_active'])) {
                $updateFields[] = 'is_active = ?';
                $updateValues[] = $input['is_active'] ? 1 : 0;
            }
            
            if (isset($input['description'])) {
                $updateFields[] = 'description = ?';
                $updateValues[] = $input['description'];
            }
            
            if (isset($input['notes'])) {
                $updateFields[] = 'notes = ?';
                $updateValues[] = $input['notes'];
            }
            
            if (isset($input['expires_at'])) {
                $updateFields[] = 'expires_at = ?';
                $updateValues[] = $input['expires_at'];
            }
            
            if (empty($updateFields)) {
                sendErrorResponse('No fields to update', 400);
            }
            
            $updateFields[] = 'updated_at = NOW()';
            $updateValues[] = $input['id'];
            
            $query = "UPDATE nft_coupons SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute($updateValues);

            // Log admin action for audit trail
            $auditQuery = "
                INSERT INTO security_audit_log (
                    event_type, admin_id, event_details, security_level,
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";

            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute([
                'admin_action',
                $admin['id'],
                json_encode([
                    'action' => 'update_nft_coupon',
                    'coupon_id' => $input['id'],
                    'updated_fields' => array_keys($input),
                    'admin_username' => $admin['username'],
                    'admin_full_name' => $admin['full_name'],
                    'timestamp' => date('c')
                ]),
                'info',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            sendSuccessResponse(null, 'Coupon updated successfully');
            break;
            
        case 'DELETE':
            // ADMIN ONLY: Delete coupon
            $admin = validateAdminAuth($db);

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                sendErrorResponse('Coupon ID is required', 400);
            }
            
            // Check if coupon has been used
            $checkQuery = "SELECT is_used, coupon_code FROM nft_coupons WHERE id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$input['id']]);
            $coupon = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                sendErrorResponse('Coupon not found', 404);
            }
            
            if ($coupon['is_used']) {
                sendErrorResponse('Cannot delete used coupon', 400);
            }
            
            $query = "DELETE FROM nft_coupons WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$input['id']]);

            // Log admin action for audit trail
            $auditQuery = "
                INSERT INTO security_audit_log (
                    event_type, admin_id, event_details, security_level,
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";

            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute([
                'admin_action',
                $admin['id'],
                json_encode([
                    'action' => 'delete_nft_coupon',
                    'coupon_id' => $input['id'],
                    'coupon_code' => $coupon['coupon_code'],
                    'admin_username' => $admin['username'],
                    'admin_full_name' => $admin['full_name'],
                    'timestamp' => date('c')
                ]),
                'warning',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            sendSuccessResponse(null, 'Coupon deleted successfully');
            break;
            
        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("NFT Coupons API error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
