<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Additional response function needed for this API
function sendResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function validateAdminAuth($db) {
    // Start session to check admin authentication (only if not already started)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['admin_id'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    // Verify admin exists and is active
    $query = "SELECT id, username, role FROM admin_users WHERE id = ? AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        sendErrorResponse('Invalid admin session', 401);
    }

    return $admin;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // DO NOT RUN MIGRATIONS HERE - this was causing duplicate commission plans
    // Tables should already exist from initial setup

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? 'list';

    // Validate admin authentication
    $admin = validateAdminAuth($db);
    
    // Check permissions for sensitive operations
    if (in_array($action, ['create', 'update', 'delete', 'set_default']) && 
        !in_array($admin['role'], ['super_admin', 'admin'])) {
        sendErrorResponse('Insufficient permissions', 403);
    }

    switch ($action) {
        case 'list':
            handleListPlans($db);
            break;
            
        case 'get':
            handleGetPlan($db, $input);
            break;
            
        case 'create':
            handleCreatePlan($db, $input, $admin['id']);
            break;
            
        case 'update':
            handleUpdatePlan($db, $input, $admin['id']);
            break;
            
        case 'delete':
            handleDeletePlan($db, $input, $admin['id']);
            break;
            
        case 'set_default':
            handleSetDefaultPlan($db, $input, $admin['id']);
            break;
            
        case 'stats':
            handleGetStats($db);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Commission Plans API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleListPlans($db) {
    try {
        $query = "SELECT 
            id, plan_name, description, is_active, is_default,
            level_1_usdt_percent, level_1_nft_percent,
            level_2_usdt_percent, level_2_nft_percent,
            level_3_usdt_percent, level_3_nft_percent,
            nft_pack_price, nft_total_supply, nft_remaining_supply,
            max_levels, minimum_investment, commission_cap,
            created_at, updated_at
            FROM commission_plans 
            ORDER BY is_default DESC, is_active DESC, created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get usage statistics for each plan
        foreach ($plans as &$plan) {
            $statsQuery = "SELECT 
                COUNT(*) as total_transactions,
                SUM(usdt_commission_amount) as total_usdt_paid,
                SUM(nft_commission_amount) as total_nft_paid,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions
                FROM commission_transactions 
                WHERE commission_plan_id = ?";
            
            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute([$plan['id']]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            $plan['usage_stats'] = [
                'total_transactions' => intval($stats['total_transactions']),
                'total_usdt_paid' => floatval($stats['total_usdt_paid'] ?? 0),
                'total_nft_paid' => intval($stats['total_nft_paid'] ?? 0),
                'pending_transactions' => intval($stats['pending_transactions'])
            ];
        }
        
        sendResponse($plans, 'Commission plans retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve commission plans: ' . $e->getMessage(), 500);
    }
}

function handleGetPlan($db, $input) {
    try {
        $planId = $input['plan_id'] ?? null;
        if (!$planId) {
            sendErrorResponse('Plan ID is required', 400);
        }
        
        $query = "SELECT * FROM commission_plans WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            sendErrorResponse('Commission plan not found', 404);
        }
        
        sendResponse($plan, 'Commission plan retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve commission plan: ' . $e->getMessage(), 500);
    }
}

function handleCreatePlan($db, $input, $adminId) {
    try {
        // Check if plan with same name already exists
        $checkQuery = "SELECT COUNT(*) as count FROM commission_plans WHERE plan_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$input['plan_name']]);
        $existingCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($existingCount > 0) {
            sendErrorResponse('A commission plan with this name already exists', 400);
        }

        // Validate required fields
        $required = ['plan_name', 'level_1_usdt_percent', 'level_1_nft_percent'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                sendErrorResponse("Field '$field' is required", 400);
            }
        }
        
        // Validate percentage values
        $percentageFields = [
            'level_1_usdt_percent', 'level_1_nft_percent',
            'level_2_usdt_percent', 'level_2_nft_percent', 
            'level_3_usdt_percent', 'level_3_nft_percent'
        ];
        
        foreach ($percentageFields as $field) {
            $value = floatval($input[$field] ?? 0);
            if ($value < 0 || $value > 100) {
                sendErrorResponse("$field must be between 0 and 100", 400);
            }
        }
        
        $query = "INSERT INTO commission_plans (
            plan_name, description, is_active,
            level_1_usdt_percent, level_1_nft_percent,
            level_2_usdt_percent, level_2_nft_percent,
            level_3_usdt_percent, level_3_nft_percent,
            nft_pack_price, nft_total_supply, nft_remaining_supply,
            max_levels, minimum_investment, commission_cap,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            $input['plan_name'],
            $input['description'] ?? '',
            $input['is_active'] ?? true,
            $input['level_1_usdt_percent'],
            $input['level_1_nft_percent'],
            $input['level_2_usdt_percent'] ?? 0,
            $input['level_2_nft_percent'] ?? 0,
            $input['level_3_usdt_percent'] ?? 0,
            $input['level_3_nft_percent'] ?? 0,
            $input['nft_pack_price'] ?? 5.00,
            $input['nft_total_supply'] ?? 200000,
            $input['nft_remaining_supply'] ?? 200000,
            $input['max_levels'] ?? 3,
            $input['minimum_investment'] ?? 0.00,
            $input['commission_cap'] ?? null,
            $adminId
        ]);
        
        if ($success) {
            $planId = $db->lastInsertId();
            sendResponse(['plan_id' => $planId], 'Commission plan created successfully');
        } else {
            sendErrorResponse('Failed to create commission plan', 500);
        }
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to create commission plan: ' . $e->getMessage(), 500);
    }
}

function handleUpdatePlan($db, $input, $adminId) {
    try {
        $planId = $input['plan_id'] ?? null;
        if (!$planId) {
            sendErrorResponse('Plan ID is required', 400);
        }
        
        // Check if plan exists and is not being used in active transactions
        $checkQuery = "SELECT COUNT(*) as active_transactions FROM commission_transactions 
                      WHERE commission_plan_id = ? AND status IN ('pending', 'approved')";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$planId]);
        $activeTransactions = $checkStmt->fetch(PDO::FETCH_ASSOC)['active_transactions'];
        
        if ($activeTransactions > 0) {
            sendErrorResponse('Cannot modify plan with active transactions. Please process pending transactions first.', 400);
        }
        
        // Build dynamic update query
        $updateFields = [];
        $updateValues = [];
        
        $allowedFields = [
            'plan_name', 'description', 'is_active',
            'level_1_usdt_percent', 'level_1_nft_percent',
            'level_2_usdt_percent', 'level_2_nft_percent',
            'level_3_usdt_percent', 'level_3_nft_percent',
            'nft_pack_price', 'nft_total_supply', 'nft_remaining_supply',
            'max_levels', 'minimum_investment', 'commission_cap'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            sendErrorResponse('No fields to update', 400);
        }
        
        $updateValues[] = $planId;
        
        $query = "UPDATE commission_plans SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $success = $stmt->execute($updateValues);
        
        if ($success) {
            sendResponse(null, 'Commission plan updated successfully');
        } else {
            sendErrorResponse('Failed to update commission plan', 500);
        }
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to update commission plan: ' . $e->getMessage(), 500);
    }
}

function handleDeletePlan($db, $input, $adminId) {
    try {
        $planId = $input['plan_id'] ?? null;
        if (!$planId) {
            sendErrorResponse('Plan ID is required', 400);
        }

        // Check if plan is default
        $checkQuery = "SELECT is_default FROM commission_plans WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$planId]);
        $plan = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            sendErrorResponse('Commission plan not found', 404);
        }

        if ($plan['is_default']) {
            sendErrorResponse('Cannot delete default commission plan', 400);
        }

        // Check if plan has transactions
        $transactionQuery = "SELECT COUNT(*) as count FROM commission_transactions WHERE commission_plan_id = ?";
        $transactionStmt = $db->prepare($transactionQuery);
        $transactionStmt->execute([$planId]);
        $transactionCount = $transactionStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($transactionCount > 0) {
            sendErrorResponse('Cannot delete commission plan with existing transactions', 400);
        }

        // Delete the plan
        $deleteQuery = "DELETE FROM commission_plans WHERE id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $success = $deleteStmt->execute([$planId]);

        if ($success) {
            sendResponse(null, 'Commission plan deleted successfully');
        } else {
            sendErrorResponse('Failed to delete commission plan', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to delete commission plan: ' . $e->getMessage(), 500);
    }
}

function handleSetDefaultPlan($db, $input, $adminId) {
    try {
        $planId = $input['plan_id'] ?? null;
        if (!$planId) {
            sendErrorResponse('Plan ID is required', 400);
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Remove default from all plans
        $resetQuery = "UPDATE commission_plans SET is_default = FALSE";
        $db->exec($resetQuery);
        
        // Set new default
        $setQuery = "UPDATE commission_plans SET is_default = TRUE WHERE id = ?";
        $stmt = $db->prepare($setQuery);
        $success = $stmt->execute([$planId]);
        
        if ($success) {
            $db->commit();
            sendResponse(null, 'Default commission plan updated successfully');
        } else {
            $db->rollback();
            sendErrorResponse('Failed to set default commission plan', 500);
        }
        
    } catch (Exception $e) {
        $db->rollback();
        sendErrorResponse('Failed to set default commission plan: ' . $e->getMessage(), 500);
    }
}

function handleGetStats($db) {
    try {
        $statsQuery = "SELECT 
            COUNT(*) as total_plans,
            SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_plans,
            SUM(CASE WHEN is_default = TRUE THEN 1 ELSE 0 END) as default_plans
            FROM commission_plans";
        
        $stmt = $db->prepare($statsQuery);
        $stmt->execute();
        $planStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $transactionStatsQuery = "SELECT 
            COUNT(*) as total_transactions,
            SUM(usdt_commission_amount) as total_usdt_commissions,
            SUM(nft_commission_amount) as total_nft_commissions,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_transactions
            FROM commission_transactions";
        
        $stmt = $db->prepare($transactionStatsQuery);
        $stmt->execute();
        $transactionStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats = [
            'plans' => [
                'total' => intval($planStats['total_plans']),
                'active' => intval($planStats['active_plans']),
                'default' => intval($planStats['default_plans'])
            ],
            'transactions' => [
                'total' => intval($transactionStats['total_transactions']),
                'pending' => intval($transactionStats['pending_transactions']),
                'paid' => intval($transactionStats['paid_transactions']),
                'total_usdt_commissions' => floatval($transactionStats['total_usdt_commissions'] ?? 0),
                'total_nft_commissions' => intval($transactionStats['total_nft_commissions'] ?? 0)
            ]
        ];
        
        sendResponse($stats, 'Commission statistics retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
    }
}
?>
