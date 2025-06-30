<?php
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

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper functions
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

function sendErrorResponse($message, $status_code = 500) {
    sendJsonResponse(['error' => $message], $status_code);
}

function sendSuccessResponse($data, $message = 'Success') {
    sendJsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_levels':
            handleGetLevels($db);
            break;
            
        case 'get_user_level':
            handleGetUserLevel($db, $input);
            break;
            
        case 'check_requirements':
            handleCheckRequirements($db, $input);
            break;
            
        case 'upgrade_level':
            handleUpgradeLevel($db, $input);
            break;
            
        case 'get_benefits':
            handleGetBenefits($db, $input);
            break;
            
        case 'get_progress':
            handleGetProgress($db, $input);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("KYC Levels API Error: " . $e->getMessage());
    sendErrorResponse('Server error occurred', 500);
}

function handleGetLevels($db) {
    try {
        // Get all KYC levels with requirements and benefits
        $query = "SELECT 
            l.id, l.level_number, l.name, l.description, l.badge_color, l.badge_icon,
            GROUP_CONCAT(DISTINCT CONCAT(r.id, ':', r.requirement_type, ':', r.requirement_name, ':', r.description, ':', r.is_mandatory, ':', r.sort_order) SEPARATOR '||') as requirements,
            GROUP_CONCAT(DISTINCT CONCAT(b.id, ':', b.benefit_type, ':', b.benefit_name, ':', b.benefit_value, ':', b.description) SEPARATOR '||') as benefits
            FROM kyc_levels l
            LEFT JOIN kyc_level_requirements r ON l.id = r.level_id
            LEFT JOIN kyc_level_benefits b ON l.id = b.level_id
            GROUP BY l.id
            ORDER BY l.level_number";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse requirements and benefits
        foreach ($levels as &$level) {
            $requirements = [];
            $benefits = [];

            if (!empty($level['requirements'])) {
                $reqStrings = explode('||', $level['requirements']);
                foreach ($reqStrings as $reqString) {
                    $parts = explode(':', $reqString);
                    if (count($parts) >= 6) {
                        $requirements[] = [
                            'id' => $parts[0],
                            'type' => $parts[1],
                            'name' => $parts[2],
                            'description' => $parts[3],
                            'is_mandatory' => (bool)$parts[4],
                            'sort_order' => (int)$parts[5]
                        ];
                    }
                }
            }

            if (!empty($level['benefits'])) {
                $benStrings = explode('||', $level['benefits']);
                foreach ($benStrings as $benString) {
                    $parts = explode(':', $benString);
                    if (count($parts) >= 5) {
                        $benefits[] = [
                            'id' => $parts[0],
                            'type' => $parts[1],
                            'name' => $parts[2],
                            'value' => $parts[3],
                            'description' => $parts[4]
                        ];
                    }
                }
            }

            // Replace the concatenated strings with parsed arrays
            $level['requirements'] = $requirements;
            $level['benefits'] = $benefits;
        }
        
        sendSuccessResponse(['levels' => $levels], 'KYC levels retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve KYC levels: ' . $e->getMessage(), 500);
    }
}

function handleGetUserLevel($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }
        
        // Get or create user KYC level record
        $query = "SELECT * FROM user_kyc_levels WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $userLevel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userLevel) {
            // Create initial record for user
            $insertQuery = "INSERT INTO user_kyc_levels (user_id, current_level) VALUES (?, 1)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$userId]);
            
            // Get the newly created record
            $stmt->execute([$userId]);
            $userLevel = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get current level details
        $levelQuery = "SELECT * FROM kyc_levels WHERE id = ?";
        $levelStmt = $db->prepare($levelQuery);
        $levelStmt->execute([$userLevel['current_level']]);
        $currentLevel = $levelStmt->fetch(PDO::FETCH_ASSOC);
        
        sendSuccessResponse([
            'user_level' => $userLevel,
            'current_level_details' => $currentLevel
        ], 'User level retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve user level: ' . $e->getMessage(), 500);
    }
}

function handleCheckRequirements($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        $levelId = $input['level_id'] ?? null;
        
        if (!$userId || !$levelId) {
            sendErrorResponse('User ID and Level ID required', 400);
        }
        
        // Get requirements for the level
        $reqQuery = "SELECT * FROM kyc_level_requirements WHERE level_id = ? ORDER BY sort_order";
        $reqStmt = $db->prepare($reqQuery);
        $reqStmt->execute([$levelId]);
        $requirements = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $requirementStatus = [];
        $totalRequirements = count($requirements);
        $completedRequirements = 0;
        
        foreach ($requirements as $requirement) {
            $status = checkSingleRequirement($db, $userId, $requirement);
            $requirementStatus[] = [
                'requirement' => $requirement,
                'status' => $status['status'],
                'completed' => $status['completed'],
                'details' => $status['details']
            ];
            
            if ($status['completed']) {
                $completedRequirements++;
            }
        }
        
        $progress = $totalRequirements > 0 ? ($completedRequirements / $totalRequirements) * 100 : 0;
        $canUpgrade = $completedRequirements === $totalRequirements;
        
        sendSuccessResponse([
            'requirements' => $requirementStatus,
            'progress' => round($progress, 2),
            'completed_count' => $completedRequirements,
            'total_count' => $totalRequirements,
            'can_upgrade' => $canUpgrade
        ], 'Requirements checked successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to check requirements: ' . $e->getMessage(), 500);
    }
}

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

function handleUpgradeLevel($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        $targetLevel = $input['target_level'] ?? null;
        
        if (!$userId || !$targetLevel) {
            sendErrorResponse('User ID and target level required', 400);
        }
        
        // Check if user can upgrade to target level
        $canUpgrade = checkUserCanUpgrade($db, $userId, $targetLevel);
        if (!$canUpgrade) {
            sendErrorResponse('Requirements not met for level upgrade', 400);
        }
        
        // Update user level
        $updateQuery = "UPDATE user_kyc_levels SET 
            current_level = ?,
            level_{$targetLevel}_completed_at = NOW(),
            updated_at = NOW()
            WHERE user_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$targetLevel, $userId]);
        
        sendSuccessResponse(['new_level' => $targetLevel], 'Level upgraded successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to upgrade level: ' . $e->getMessage(), 500);
    }
}

function handleGetBenefits($db, $input) {
    try {
        $levelId = $input['level_id'] ?? $_GET['level_id'] ?? null;
        if (!$levelId) {
            sendErrorResponse('Level ID required', 400);
        }
        
        $query = "SELECT * FROM kyc_level_benefits WHERE level_id = ? ORDER BY benefit_type";
        $stmt = $db->prepare($query);
        $stmt->execute([$levelId]);
        $benefits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendSuccessResponse(['benefits' => $benefits], 'Benefits retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve benefits: ' . $e->getMessage(), 500);
    }
}

function handleGetProgress($db, $input) {
    try {
        $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User ID required', 400);
        }
        
        $progress = [];
        
        // Check progress for all 3 levels
        for ($level = 1; $level <= 3; $level++) {
            $checkInput = ['user_id' => $userId, 'level_id' => $level];
            $checkResult = checkRequirementsForLevel($db, $checkInput);
            $progress["level_$level"] = $checkResult;
        }
        
        sendSuccessResponse(['progress' => $progress], 'Progress retrieved successfully');
        
    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve progress: ' . $e->getMessage(), 500);
    }
}

function checkUserCanUpgrade($db, $userId, $targetLevel) {
    try {
        $input = ['user_id' => $userId, 'level_id' => $targetLevel];
        $result = checkRequirementsForLevel($db, $input);
        return $result['can_upgrade'];
    } catch (Exception $e) {
        return false;
    }
}

function checkRequirementsForLevel($db, $input) {
    $userId = $input['user_id'];
    $levelId = $input['level_id'];

    // Get requirements for the level
    $reqQuery = "SELECT * FROM kyc_level_requirements WHERE level_id = ? ORDER BY sort_order";
    $reqStmt = $db->prepare($reqQuery);
    $reqStmt->execute([$levelId]);
    $requirements = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

    $requirementStatus = [];
    $totalRequirements = count($requirements);
    $completedRequirements = 0;

    foreach ($requirements as $requirement) {
        $status = checkSingleRequirement($db, $userId, $requirement);
        $requirementStatus[] = [
            'requirement' => $requirement,
            'status' => $status['status'],
            'completed' => $status['completed'],
            'details' => $status['details']
        ];

        if ($status['completed']) {
            $completedRequirements++;
        }
    }

    $progress = $totalRequirements > 0 ? ($completedRequirements / $totalRequirements) * 100 : 0;
    $canUpgrade = $completedRequirements === $totalRequirements;

    return [
        'requirements' => $requirementStatus,
        'progress' => round($progress, 2),
        'completed_count' => $completedRequirements,
        'total_count' => $totalRequirements,
        'can_upgrade' => $canUpgrade
    ];
}

?>
