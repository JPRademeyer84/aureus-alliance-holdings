<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendAdminResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendAdminErrorResponse($message, $code = 400) {
    sendAdminResponse(null, $message, false, $code);
}

function hasPermission($adminRole, $requiredRole) {
    $roleHierarchy = [
        'super_admin' => 3,
        'admin' => 2,
        'chat_support' => 1
    ];
    
    return ($roleHierarchy[$adminRole] ?? 0) >= ($roleHierarchy[$requiredRole] ?? 0);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendAdminErrorResponse('Invalid JSON input');
        }
        
        $action = $input['action'] ?? '';
        $currentAdminId = $input['current_admin_id'] ?? '';
        
        // Verify current admin permissions
        if (!$currentAdminId) {
            sendAdminErrorResponse('Current admin ID is required');
        }
        
        $currentAdminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $currentAdminStmt = $db->prepare($currentAdminQuery);
        $currentAdminStmt->execute([$currentAdminId]);
        $currentAdmin = $currentAdminStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentAdmin) {
            sendAdminErrorResponse('Invalid admin credentials', 401);
        }
        
        if ($action === 'create') {
            // Only super_admin and admin can create new admins
            if (!hasPermission($currentAdmin['role'], 'admin')) {
                sendAdminErrorResponse('Insufficient permissions to create admin users', 403);
            }
            
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            $email = trim($input['email'] ?? '');
            $fullName = trim($input['full_name'] ?? '');
            $role = $input['role'] ?? 'chat_support';
            
            if (empty($username) || empty($password) || empty($email)) {
                sendAdminErrorResponse('Username, password, and email are required');
            }
            
            if (!in_array($role, ['super_admin', 'admin', 'chat_support'])) {
                sendAdminErrorResponse('Invalid role specified');
            }
            
            // Only super_admin can create super_admin or admin roles
            if (in_array($role, ['super_admin', 'admin']) && $currentAdmin['role'] !== 'super_admin') {
                sendAdminErrorResponse('Only super administrators can create admin or super admin users', 403);
            }
            
            // Check if username already exists
            $checkQuery = "SELECT id FROM admin_users WHERE username = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$username]);
            
            if ($checkStmt->fetch()) {
                sendAdminErrorResponse('Username already exists');
            }
            
            // Check if email already exists
            $checkEmailQuery = "SELECT id FROM admin_users WHERE email = ?";
            $checkEmailStmt = $db->prepare($checkEmailQuery);
            $checkEmailStmt->execute([$email]);
            
            if ($checkEmailStmt->fetch()) {
                sendAdminErrorResponse('Email already exists');
            }
            
            // Create new admin
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO admin_users (username, password_hash, email, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, TRUE)";
            $insertStmt = $db->prepare($insertQuery);
            
            if ($insertStmt->execute([$username, $passwordHash, $email, $fullName, $role])) {
                sendAdminResponse([
                    'admin_created' => true,
                    'username' => $username,
                    'role' => $role
                ], 'Admin user created successfully');
            } else {
                sendAdminErrorResponse('Failed to create admin user');
            }
            
        } elseif ($action === 'update') {
            $adminId = $input['admin_id'] ?? '';
            $updates = $input['updates'] ?? [];
            
            if (!$adminId || empty($updates)) {
                sendAdminErrorResponse('Admin ID and updates are required');
            }
            
            // Get target admin info
            $targetAdminQuery = "SELECT role, username FROM admin_users WHERE id = ?";
            $targetAdminStmt = $db->prepare($targetAdminQuery);
            $targetAdminStmt->execute([$adminId]);
            $targetAdmin = $targetAdminStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$targetAdmin) {
                sendAdminErrorResponse('Admin user not found');
            }
            
            // Permission checks
            if (isset($updates['role'])) {
                // Only super_admin can change roles
                if ($currentAdmin['role'] !== 'super_admin') {
                    sendAdminErrorResponse('Only super administrators can change user roles', 403);
                }
                
                // Can't change own role
                if ($adminId === $currentAdminId) {
                    sendAdminErrorResponse('Cannot change your own role');
                }
            }
            
            // Build update query
            $allowedFields = ['email', 'full_name', 'role', 'is_active', 'chat_status'];
            $updateFields = [];
            $updateValues = [];
            
            foreach ($updates as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $value;
                }
            }
            
            if (empty($updateFields)) {
                sendAdminErrorResponse('No valid fields to update');
            }
            
            $updateValues[] = $adminId;
            $updateQuery = "UPDATE admin_users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            
            if ($updateStmt->execute($updateValues)) {
                sendAdminResponse([
                    'admin_updated' => true,
                    'admin_id' => $adminId
                ], 'Admin user updated successfully');
            } else {
                sendAdminErrorResponse('Failed to update admin user');
            }
            
        } elseif ($action === 'delete') {
            // Only super_admin can delete admins
            if ($currentAdmin['role'] !== 'super_admin') {
                sendAdminErrorResponse('Only super administrators can delete admin users', 403);
            }
            
            $adminId = $input['admin_id'] ?? '';
            
            if (!$adminId) {
                sendAdminErrorResponse('Admin ID is required');
            }
            
            // Can't delete yourself
            if ($adminId === $currentAdminId) {
                sendAdminErrorResponse('Cannot delete your own account');
            }
            
            // Soft delete (deactivate)
            $deleteQuery = "UPDATE admin_users SET is_active = FALSE WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            
            if ($deleteStmt->execute([$adminId])) {
                sendAdminResponse([
                    'admin_deleted' => true,
                    'admin_id' => $adminId
                ], 'Admin user deactivated successfully');
            } else {
                sendAdminErrorResponse('Failed to delete admin user');
            }
            
        } elseif ($action === 'update_chat_status') {
            $chatStatus = $input['chat_status'] ?? '';
            $targetAdminId = $input['admin_id'] ?? $currentAdminId; // Allow updating other admins

            if (!in_array($chatStatus, ['online', 'offline', 'busy'])) {
                sendAdminErrorResponse('Invalid chat status');
            }

            // Check if current admin can update other admin's status
            if ($targetAdminId !== $currentAdminId) {
                if (!hasPermission($currentAdmin['role'], 'admin')) {
                    sendAdminErrorResponse('Insufficient permissions to update other admin chat status');
                }

                // Verify target admin exists
                $checkQuery = "SELECT id, username FROM admin_users WHERE id = ? AND is_active = TRUE";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$targetAdminId]);
                $targetAdmin = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$targetAdmin) {
                    sendAdminErrorResponse('Target admin not found or inactive');
                }
            }

            $updateQuery = "UPDATE admin_users SET chat_status = ?, last_activity = CURRENT_TIMESTAMP WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);

            if ($updateStmt->execute([$chatStatus, $targetAdminId])) {
                $message = $targetAdminId === $currentAdminId
                    ? 'Your chat status updated successfully'
                    : "Chat status updated for " . ($targetAdmin['username'] ?? 'admin');

                sendAdminResponse([
                    'chat_status_updated' => true,
                    'new_status' => $chatStatus,
                    'target_admin_id' => $targetAdminId
                ], $message);
            } else {
                sendAdminErrorResponse('Failed to update chat status');
            }
            
        } else {
            sendAdminErrorResponse('Invalid action specified');
        }
        
    } elseif ($method === 'GET') {
        $currentAdminId = $_GET['current_admin_id'] ?? '';
        
        if (!$currentAdminId) {
            sendAdminErrorResponse('Current admin ID is required');
        }
        
        // Verify current admin
        $currentAdminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $currentAdminStmt = $db->prepare($currentAdminQuery);
        $currentAdminStmt->execute([$currentAdminId]);
        $currentAdmin = $currentAdminStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentAdmin) {
            sendAdminErrorResponse('Invalid admin credentials', 401);
        }
        
        // Get all admins (only super_admin and admin can see all)
        if (hasPermission($currentAdmin['role'], 'admin')) {
            $query = "SELECT id, username, email, full_name, role, is_active, chat_status, last_activity, created_at FROM admin_users ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
        } else {
            // Chat support can only see themselves
            $query = "SELECT id, username, email, full_name, role, is_active, chat_status, last_activity, created_at FROM admin_users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$currentAdminId]);
        }
        
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get online admin count
        $onlineQuery = "SELECT COUNT(*) as online_count FROM admin_users WHERE chat_status = 'online' AND is_active = TRUE";
        $onlineStmt = $db->prepare($onlineQuery);
        $onlineStmt->execute();
        $onlineCount = $onlineStmt->fetch(PDO::FETCH_ASSOC)['online_count'];
        
        sendAdminResponse([
            'admins' => $admins,
            'online_count' => intval($onlineCount),
            'current_admin_role' => $currentAdmin['role']
        ], 'Admin users retrieved successfully');
        
    } else {
        sendAdminErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Admin management error: " . $e->getMessage());
    sendAdminErrorResponse('Internal server error', 500);
}
?>
