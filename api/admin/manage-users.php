<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Use functions from cors.php: sendSuccessResponse() and sendErrorResponse()

function hasPermission($adminRole, $requiredRole) {
    $roleHierarchy = [
        'super_admin' => 3,
        'admin' => 2,
        'chat_support' => 1
    ];
    
    return ($roleHierarchy[$adminRole] ?? 0) >= ($roleHierarchy[$requiredRole] ?? 0);
}

try {
    error_log("User management: Starting request");
    $database = new Database();
    error_log("User management: Database object created");
    $db = $database->getConnection();
    error_log("User management: Database connection established");
    $database->createTables();
    error_log("User management: Tables created/verified");

    $method = $_SERVER['REQUEST_METHOD'];
    error_log("User management: Request method: " . $method);
    
    if ($method === 'GET') {
        error_log("User management: Processing GET request");

        $action = $_GET['action'] ?? 'list';
        $userId = $_GET['user_id'] ?? '';

        // For now, skip admin authentication for testing
        // TODO: Re-enable admin authentication after testing

        // Debug: Log all GET parameters
        error_log("User management GET params: " . json_encode($_GET));

        if ($action === 'get_user' && $userId) {
            // Get complete user details with all profile information including enhanced KYC fields
            $userQuery = "SELECT
                u.id, u.username, u.email, u.full_name, u.is_active, u.created_at, u.updated_at,
                u.email_verified, u.last_login, u.role, u.facial_verification_status,
                up.phone, up.country, up.city, up.date_of_birth, up.profile_image, up.bio,
                up.telegram_username, up.whatsapp_number, up.twitter_handle,
                up.instagram_handle, up.linkedin_profile, up.facebook_profile,
                up.kyc_status, up.kyc_verified_at, up.kyc_rejected_reason, up.profile_completion,

                -- Enhanced KYC fields
                up.first_name, up.last_name, up.middle_name, up.nationality, up.gender, up.place_of_birth,
                up.address_line_1, up.address_line_2, up.state_province, up.postal_code,
                up.id_type, up.id_number, up.id_expiry_date, up.occupation, up.employer,
                up.annual_income, up.source_of_funds, up.purpose_of_account,
                up.emergency_contact_name, up.emergency_contact_phone, up.emergency_contact_relationship,

                -- Enhanced KYC status fields
                up.personal_info_status, up.contact_info_status, up.address_info_status,
                up.identity_info_status, up.financial_info_status, up.emergency_contact_status,
                up.personal_info_rejection_reason, up.contact_info_rejection_reason,
                up.address_info_rejection_reason, up.identity_info_rejection_reason,
                up.financial_info_rejection_reason, up.emergency_contact_rejection_reason

                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE u.id = ?";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                sendErrorResponse('User not found', 404);
            }

            sendSuccessResponse(['user' => $user], 'User details retrieved successfully');

        } else {
            // Get all users (existing functionality)
            $query = "SELECT id, username, email, is_active, created_at, updated_at FROM users ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics
            $statsQuery = "SELECT
                          COUNT(*) as total_users,
                          SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
                          SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_users
                          FROM users";
            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            sendSuccessResponse([
                'users' => $users,
                'statistics' => [
                    'total' => intval($stats['total_users']),
                    'active' => intval($stats['active_users']),
                    'inactive' => intval($stats['inactive_users'])
                ]
            ], 'Users retrieved successfully');
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendErrorResponse('Invalid JSON input');
        }

        $action = $input['action'] ?? '';
        $adminId = $input['admin_id'] ?? '';

        // Verify admin permissions
        if (!$adminId) {
            sendErrorResponse('Admin ID is required');
        }

        $adminQuery = "SELECT role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute([$adminId]);
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !hasPermission($admin['role'], 'admin')) {
            sendErrorResponse('Insufficient permissions', 403);
        }
        
        if ($action === 'create') {
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                sendErrorResponse('Username, email, and password are required');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendErrorResponse('Invalid email address');
            }

            // Check if username already exists
            $checkQuery = "SELECT id FROM users WHERE username = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$username]);

            if ($checkStmt->fetch()) {
                sendErrorResponse('Username already exists');
            }

            // Check if email already exists
            $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
            $checkEmailStmt = $db->prepare($checkEmailQuery);
            $checkEmailStmt->execute([$email]);

            if ($checkEmailStmt->fetch()) {
                sendErrorResponse('Email already exists');
            }
            
            // Create new user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO users (username, email, password_hash, is_active) VALUES (?, ?, ?, TRUE)";
            $insertStmt = $db->prepare($insertQuery);
            
            if ($insertStmt->execute([$username, $email, $passwordHash])) {
                sendSuccessResponse([
                    'user_created' => true,
                    'username' => $username,
                    'email' => $email
                ], 'User created successfully');
            } else {
                sendErrorResponse('Failed to create user');
            }
        } else {
            sendErrorResponse('Invalid action specified');
        }

    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("User management error: " . $e->getMessage());
    error_log("User management stack trace: " . $e->getTraceAsString());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
