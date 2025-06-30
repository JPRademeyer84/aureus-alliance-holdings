<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../config/database.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper functions
function sendSuccessResponse($data, $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_all_user_levels':
            handleGetAllUserLevels($db, $input);
            break;
            
        case 'update_user_level':
            handleUpdateUserLevel($db, $input);
            break;
            
        case 'get_level_statistics':
            handleGetLevelStatistics($db);
            break;
            
        case 'force_upgrade_user':
            handleForceUpgradeUser($db, $input);
            break;
            
        case 'get_user_requirements':
            handleGetUserRequirements($db, $input);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("Admin KYC Levels API Error: " . $e->getMessage());
    sendErrorResponse('Server error occurred', 500);
}

function handleGetAllUserLevels($db, $input) {
    try {
        $page = (int)($input['page'] ?? $_GET['page'] ?? 1);
        $limit = (int)($input['limit'] ?? $_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        // Get users with their KYC levels
        $query = "SELECT 
            u.id, u.username, u.email, u.full_name, u.created_at,
            ukl.current_level, ukl.level_1_completed_at, ukl.level_2_completed_at, ukl.level_3_completed_at,
            kl.name as level_name, kl.badge_color, kl.badge_icon,
            up.kyc_status, up.profile_completion
            FROM users u
            LEFT JOIN user_kyc_levels ukl ON u.id = ukl.user_id
            LEFT JOIN kyc_levels kl ON ukl.current_level = kl.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.role = 'user'
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?";
            
        $stmt = $db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        sendSuccessResponse([
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ], 'User levels retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve user levels: ' . $e->getMessage(), 500);
    }
}

function handleUpdateUserLevel($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $newLevel = $input['new_level'] ?? null;
        $adminId = $_SESSION['admin_id'];
        
        if (!$userId || !$newLevel) {
            sendErrorResponse('User ID and new level required', 400);
        }
        
        // Check if user exists
        $userQuery = "SELECT id FROM users WHERE id = ?";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute([$userId]);
        if (!$userStmt->fetch()) {
            sendErrorResponse('User not found', 404);
        }
        
        // Update or create user level record
        $checkQuery = "SELECT id FROM user_kyc_levels WHERE user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$userId]);
        
        if ($checkStmt->fetch()) {
            // Update existing record
            $updateQuery = "UPDATE user_kyc_levels SET 
                current_level = ?,
                level_{$newLevel}_completed_at = NOW(),
                updated_at = NOW()
                WHERE user_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$newLevel, $userId]);
        } else {
            // Create new record
            $insertQuery = "INSERT INTO user_kyc_levels (user_id, current_level, level_{$newLevel}_completed_at) VALUES (?, ?, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$userId, $newLevel]);
        }
        
        // Log the admin action
        $logQuery = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) 
                     VALUES (?, 'kyc_level_update', 'user', ?, ?, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$adminId, $userId, json_encode(['new_level' => $newLevel])]);
        
        sendSuccessResponse(['new_level' => $newLevel], 'User level updated successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to update user level: ' . $e->getMessage(), 500);
    }
}

function handleGetLevelStatistics($db) {
    try {
        // Get level distribution
        $levelStatsQuery = "SELECT 
            kl.id, kl.name, kl.level_number,
            COUNT(ukl.user_id) as user_count
            FROM kyc_levels kl
            LEFT JOIN user_kyc_levels ukl ON kl.id = ukl.current_level
            GROUP BY kl.id
            ORDER BY kl.level_number";
            
        $levelStatsStmt = $db->prepare($levelStatsQuery);
        $levelStatsStmt->execute();
        $levelStats = $levelStatsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get completion rates
        $completionQuery = "SELECT 
            COUNT(CASE WHEN level_1_completed_at IS NOT NULL THEN 1 END) as level_1_completed,
            COUNT(CASE WHEN level_2_completed_at IS NOT NULL THEN 1 END) as level_2_completed,
            COUNT(CASE WHEN level_3_completed_at IS NOT NULL THEN 1 END) as level_3_completed,
            COUNT(*) as total_users
            FROM user_kyc_levels";
            
        $completionStmt = $db->prepare($completionQuery);
        $completionStmt->execute();
        $completionStats = $completionStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get recent upgrades
        $recentQuery = "SELECT 
            u.username, u.full_name, ukl.current_level, kl.name as level_name,
            GREATEST(
                COALESCE(ukl.level_1_completed_at, '1970-01-01'),
                COALESCE(ukl.level_2_completed_at, '1970-01-01'),
                COALESCE(ukl.level_3_completed_at, '1970-01-01')
            ) as last_upgrade
            FROM user_kyc_levels ukl
            JOIN users u ON ukl.user_id = u.id
            JOIN kyc_levels kl ON ukl.current_level = kl.id
            WHERE GREATEST(
                COALESCE(ukl.level_1_completed_at, '1970-01-01'),
                COALESCE(ukl.level_2_completed_at, '1970-01-01'),
                COALESCE(ukl.level_3_completed_at, '1970-01-01')
            ) > '1970-01-01'
            ORDER BY last_upgrade DESC
            LIMIT 10";
            
        $recentStmt = $db->prepare($recentQuery);
        $recentStmt->execute();
        $recentUpgrades = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccessResponse([
            'level_distribution' => $levelStats,
            'completion_stats' => $completionStats,
            'recent_upgrades' => $recentUpgrades
        ], 'Level statistics retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve statistics: ' . $e->getMessage(), 500);
    }
}

function handleForceUpgradeUser($db, $input) {
    try {
        $userId = $input['user_id'] ?? null;
        $targetLevel = $input['target_level'] ?? null;
        $reason = $input['reason'] ?? 'Admin override';
        $adminId = $_SESSION['admin_id'];
        
        if (!$userId || !$targetLevel) {
            sendErrorResponse('User ID and target level required', 400);
        }
        
        // Force upgrade without checking requirements
        $checkQuery = "SELECT id FROM user_kyc_levels WHERE user_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$userId]);
        
        if ($checkStmt->fetch()) {
            // Update existing record
            $updateQuery = "UPDATE user_kyc_levels SET 
                current_level = ?,
                level_{$targetLevel}_completed_at = NOW(),
                updated_at = NOW()
                WHERE user_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$targetLevel, $userId]);
        } else {
            // Create new record
            $insertQuery = "INSERT INTO user_kyc_levels (user_id, current_level, level_{$targetLevel}_completed_at) VALUES (?, ?, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$userId, $targetLevel]);
        }
        
        // Log the forced upgrade
        $logQuery = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) 
                     VALUES (?, 'force_kyc_upgrade', 'user', ?, ?, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$adminId, $userId, json_encode(['target_level' => $targetLevel, 'reason' => $reason])]);
        
        sendSuccessResponse(['new_level' => $targetLevel], 'User forcefully upgraded successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to force upgrade user: ' . $e->getMessage(), 500);
    }
}

function handleGetUserRequirements($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }
        
        // Get user's current level
        $levelQuery = "SELECT current_level FROM user_kyc_levels WHERE user_id = ?";
        $levelStmt = $db->prepare($levelQuery);
        $levelStmt->execute([$userId]);
        $userLevel = $levelStmt->fetch(PDO::FETCH_ASSOC);
        $currentLevel = $userLevel['current_level'] ?? 1;
        
        $allRequirements = [];
        
        // Check requirements for all levels
        for ($level = 1; $level <= 3; $level++) {
            $reqQuery = "SELECT * FROM kyc_level_requirements WHERE level_id = ? ORDER BY sort_order";
            $reqStmt = $db->prepare($reqQuery);
            $reqStmt->execute([$level]);
            $requirements = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $levelRequirements = [];
            foreach ($requirements as $requirement) {
                $status = checkSingleRequirement($db, $userId, $requirement);
                $levelRequirements[] = [
                    'requirement' => $requirement,
                    'status' => $status['status'],
                    'completed' => $status['completed'],
                    'details' => $status['details']
                ];
            }
            
            $allRequirements["level_$level"] = [
                'level' => $level,
                'is_current' => $level == $currentLevel,
                'requirements' => $levelRequirements
            ];
        }
        
        sendSuccessResponse([
            'user_id' => $userId,
            'current_level' => $currentLevel,
            'requirements' => $allRequirements
        ], 'User requirements retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve user requirements: ' . $e->getMessage(), 500);
    }
}

// Include the checkSingleRequirement function from the main levels API
function checkSingleRequirement($db, $userId, $requirement) {
    $status = 'not_started';
    $completed = false;
    $details = [];
    
    switch ($requirement['requirement_type']) {
        case 'email_verification':
            $stmt = $db->prepare("SELECT email_verified FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = (bool)$user['email_verified'];
            $status = $completed ? 'completed' : 'not_started';
            break;
            
        case 'phone_verification':
            // Check if contact info is approved (which includes phone verification)
            $stmt = $db->prepare("SELECT phone, contact_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = !empty($profile['phone']) && ($profile['contact_info_status'] === 'approved');
            $status = $completed ? 'completed' : (!empty($profile['phone']) ? 'in_progress' : 'not_started');
            break;
            
        case 'profile_completion':
            // Check if personal info is approved (which indicates profile completion)
            $stmt = $db->prepare("SELECT profile_completion, personal_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            $completion = (int)($profile['profile_completion'] ?? 0);
            $personalInfoApproved = ($profile['personal_info_status'] === 'approved');
            $completed = $personalInfoApproved || $completion >= 75; // Either approved by admin or 75% completion
            $status = $completed ? 'completed' : ($completion > 0 ? 'in_progress' : 'not_started');
            $details['completion_percentage'] = $completion;
            $details['personal_info_status'] = $profile['personal_info_status'] ?? 'pending';
            break;
            
        case 'document_upload':
            // Check if identity documents are approved by admin
            $stmt = $db->prepare("SELECT identity_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = ($result && $result['identity_info_status'] === 'approved');
            $status = $completed ? 'completed' : 'not_started';
            $details['identity_info_status'] = $result['identity_info_status'] ?? 'pending';
            break;

        case 'facial_verification':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM facial_verifications WHERE user_id = ? AND verification_status = 'verified'");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = $result['count'] > 0;
            $status = $completed ? 'completed' : 'not_started';
            break;

        case 'address_verification':
            // Check if address information is approved by admin
            $stmt = $db->prepare("SELECT address_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = ($result && $result['address_info_status'] === 'approved');
            $status = $completed ? 'completed' : 'not_started';
            $details['address_info_status'] = $result['address_info_status'] ?? 'pending';
            break;

        case 'personal_info_verification':
            // Check if personal information is approved by admin
            $stmt = $db->prepare("SELECT personal_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = ($result && $result['personal_info_status'] === 'approved');
            $status = $completed ? 'completed' : 'not_started';
            $details['personal_info_status'] = $result['personal_info_status'] ?? 'pending';
            break;

        case 'contact_info_verification':
            // Check if contact information is approved by admin
            $stmt = $db->prepare("SELECT contact_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = ($result && $result['contact_info_status'] === 'approved');
            $status = $completed ? 'completed' : 'not_started';
            $details['contact_info_status'] = $result['contact_info_status'] ?? 'pending';
            break;

        case 'financial_info_verification':
            // Check if financial information is approved by admin
            $stmt = $db->prepare("SELECT financial_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = ($result && $result['financial_info_status'] === 'approved');
            $status = $completed ? 'completed' : 'not_started';
            $details['financial_info_status'] = $result['financial_info_status'] ?? 'pending';
            break;

        case 'enhanced_due_diligence':
            // Check if additional documentation is provided and approved
            $stmt = $db->prepare("SELECT additional_info_status FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $completed = ($result && $result['additional_info_status'] === 'approved');
            $status = $completed ? 'completed' : 'not_started';
            $details['additional_info_status'] = $result['additional_info_status'] ?? 'pending';
            break;

        case 'account_activity':
            $stmt = $db->prepare("SELECT created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $daysSinceCreation = (time() - strtotime($user['created_at'])) / (60 * 60 * 24);
            $completed = $daysSinceCreation >= 30;
            $status = $completed ? 'completed' : 'in_progress';
            $details['days_since_creation'] = round($daysSinceCreation);
            break;
    }
    
    return [
        'status' => $status,
        'completed' => $completed,
        'details' => $details
    ];
}

?>
