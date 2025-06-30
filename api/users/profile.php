<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract user ID from URL
$userId = isset($pathParts[3]) ? $pathParts[3] : null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $database->createTables();
    
    switch ($method) {
        case 'GET':
            // Get user profile
            $stmt = $pdo->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.full_name,
                    u.created_at,
                    up.phone,
                    up.country,
                    up.city,
                    up.date_of_birth,
                    up.profile_image,
                    up.bio,
                    up.telegram_username,
                    up.whatsapp_number,
                    up.twitter_handle,
                    up.instagram_handle,
                    up.linkedin_profile,
                    up.facebook_profile,
                    up.kyc_status,
                    up.profile_completion,
                    up.updated_at
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE u.id = ?
            ");
            
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Calculate profile completion if not set
            if (!$profile['profile_completion']) {
                $fields = ['full_name', 'phone', 'country', 'city', 'date_of_birth', 'telegram_username', 'whatsapp_number'];
                $completedFields = 0;
                foreach ($fields as $field) {
                    if (!empty($profile[$field])) {
                        $completedFields++;
                    }
                }
                $profile['profile_completion'] = round(($completedFields / count($fields)) * 100);
            }
            
            echo json_encode([
                'success' => true,
                'profile' => $profile
            ]);
            break;
            
        case 'PUT':
            // Update user profile
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                exit;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Update users table
                if (isset($input['full_name'])) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                    $stmt->execute([$input['full_name'], $userId]);
                }
                
                // Check if profile exists
                $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$userId]);
                $profileExists = $stmt->fetch();
                
                if ($profileExists) {
                    // Update existing profile
                    $stmt = $pdo->prepare("
                        UPDATE user_profiles SET
                            phone = ?,
                            country = ?,
                            city = ?,
                            date_of_birth = ?,
                            bio = ?,
                            telegram_username = ?,
                            whatsapp_number = ?,
                            twitter_handle = ?,
                            instagram_handle = ?,
                            linkedin_profile = ?,
                            facebook_profile = ?,
                            profile_completion = ?,
                            updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    
                    $stmt->execute([
                        $input['phone'] ?? null,
                        $input['country'] ?? null,
                        $input['city'] ?? null,
                        $input['date_of_birth'] ?? null,
                        $input['bio'] ?? null,
                        $input['telegram_username'] ?? null,
                        $input['whatsapp_number'] ?? null,
                        $input['twitter_handle'] ?? null,
                        $input['instagram_handle'] ?? null,
                        $input['linkedin_profile'] ?? null,
                        $input['facebook_profile'] ?? null,
                        $input['profile_completion'] ?? 0,
                        $userId
                    ]);
                } else {
                    // Create new profile
                    $stmt = $pdo->prepare("
                        INSERT INTO user_profiles (
                            user_id, phone, country, city, date_of_birth, bio,
                            telegram_username, whatsapp_number, twitter_handle,
                            instagram_handle, linkedin_profile, facebook_profile,
                            profile_completion, kyc_status, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $userId,
                        $input['phone'] ?? null,
                        $input['country'] ?? null,
                        $input['city'] ?? null,
                        $input['date_of_birth'] ?? null,
                        $input['bio'] ?? null,
                        $input['telegram_username'] ?? null,
                        $input['whatsapp_number'] ?? null,
                        $input['twitter_handle'] ?? null,
                        $input['instagram_handle'] ?? null,
                        $input['linkedin_profile'] ?? null,
                        $input['facebook_profile'] ?? null,
                        $input['profile_completion'] ?? 0
                    ]);
                }
                
                $pdo->commit();
                
                // Return updated profile
                $stmt = $pdo->prepare("
                    SELECT 
                        u.id,
                        u.username,
                        u.email,
                        u.full_name,
                        u.created_at,
                        up.phone,
                        up.country,
                        up.city,
                        up.date_of_birth,
                        up.profile_image,
                        up.bio,
                        up.telegram_username,
                        up.whatsapp_number,
                        up.twitter_handle,
                        up.instagram_handle,
                        up.linkedin_profile,
                        up.facebook_profile,
                        up.kyc_status,
                        up.profile_completion,
                        up.updated_at
                    FROM users u
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    WHERE u.id = ?
                ");
                
                $stmt->execute([$userId]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'profile' => $profile
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
