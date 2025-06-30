<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'linkedin_auth':
            handleLinkedInAuth();
            break;
        case 'facebook_share':
            handleFacebookShare($db);
            break;
        case 'twitter_share':
            handleTwitterShare($db);
            break;
        case 'generate_share_urls':
            handleGenerateShareUrls($db);
            break;
        case 'validate_share':
            handleValidateShare($db);
            break;
        default:
            throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleLinkedInAuth() {
    try {
        // LinkedIn OAuth 2.0 configuration
        $clientId = $_ENV['LINKEDIN_CLIENT_ID'] ?? 'your_linkedin_client_id';
        $redirectUri = $_ENV['LINKEDIN_REDIRECT_URI'] ?? 'https://aureusangels.com/auth/linkedin';
        $scope = 'r_liteprofile r_emailaddress w_member_social';
        
        $authUrl = "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => generateStateToken()
        ]);
        
        echo json_encode([
            'success' => true,
            'auth_url' => $authUrl,
            'instructions' => 'Redirect user to this URL for LinkedIn authorization'
        ]);

    } catch (Exception $e) {
        throw new Exception("LinkedIn auth failed: " . $e->getMessage());
    }
}

function handleFacebookShare($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $content = $input['content'] ?? '';
        $link = $input['link'] ?? '';
        $userId = $input['user_id'] ?? '';
        
        // Facebook Share Dialog URL (client-side sharing)
        $shareUrl = "https://www.facebook.com/dialog/share?" . http_build_query([
            'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? 'your_facebook_app_id',
            'href' => $link,
            'quote' => $content,
            'display' => 'popup',
            'redirect_uri' => 'https://aureusangels.com/share/callback'
        ]);
        
        // Track the share attempt
        if ($userId) {
            trackShareAttempt($db, $userId, 'facebook', 'referral', $content, $link);
        }
        
        echo json_encode([
            'success' => true,
            'share_url' => $shareUrl,
            'method' => 'popup',
            'instructions' => 'Open this URL in a popup window'
        ]);

    } catch (Exception $e) {
        throw new Exception("Facebook share failed: " . $e->getMessage());
    }
}

function handleTwitterShare($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $text = $input['text'] ?? '';
        $url = $input['url'] ?? '';
        $hashtags = $input['hashtags'] ?? [];
        $userId = $input['user_id'] ?? '';
        
        // Twitter Web Intent URL
        $shareUrl = "https://twitter.com/intent/tweet?" . http_build_query([
            'text' => $text,
            'url' => $url,
            'hashtags' => implode(',', $hashtags),
            'via' => 'AureusAngels'
        ]);
        
        // Track the share attempt
        if ($userId) {
            trackShareAttempt($db, $userId, 'twitter', 'referral', $text, $url);
        }
        
        echo json_encode([
            'success' => true,
            'share_url' => $shareUrl,
            'method' => 'popup',
            'instructions' => 'Open this URL in a popup window'
        ]);

    } catch (Exception $e) {
        throw new Exception("Twitter share failed: " . $e->getMessage());
    }
}

function handleGenerateShareUrls($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $content = $input['content'] ?? '';
        $referralLink = $input['referral_link'] ?? '';
        $userId = $input['user_id'] ?? '';
        $contentType = $input['content_type'] ?? 'referral';
        
        $shareUrls = [
            'facebook' => [
                'url' => "https://www.facebook.com/dialog/share?" . http_build_query([
                    'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? 'your_facebook_app_id',
                    'href' => $referralLink,
                    'quote' => $content,
                    'display' => 'popup'
                ]),
                'method' => 'popup',
                'supported' => true
            ],
            'twitter' => [
                'url' => "https://twitter.com/intent/tweet?" . http_build_query([
                    'text' => $content,
                    'url' => $referralLink,
                    'via' => 'AureusAngels'
                ]),
                'method' => 'popup',
                'supported' => true
            ],
            'linkedin' => [
                'url' => "https://www.linkedin.com/sharing/share-offsite/?" . http_build_query([
                    'url' => $referralLink,
                    'summary' => $content
                ]),
                'method' => 'popup',
                'supported' => true,
                'note' => 'LinkedIn sharing may require user to be logged in'
            ],
            'whatsapp' => [
                'url' => "https://wa.me/?" . http_build_query([
                    'text' => $content . "\n\n" . $referralLink
                ]),
                'method' => 'direct',
                'supported' => true
            ],
            'telegram' => [
                'url' => "https://t.me/share/url?" . http_build_query([
                    'url' => $referralLink,
                    'text' => $content
                ]),
                'method' => 'direct',
                'supported' => true
            ]
        ];
        
        // Track share URL generation
        if ($userId) {
            foreach ($shareUrls as $platform => $data) {
                if ($data['supported']) {
                    trackShareAttempt($db, $userId, $platform, $contentType, $content, $referralLink);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'share_urls' => $shareUrls,
            'tracking_enabled' => !empty($userId)
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to generate share URLs: " . $e->getMessage());
    }
}

function handleValidateShare($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $shareId = $input['share_id'] ?? '';
        $platform = $input['platform'] ?? '';
        $success = $input['success'] ?? false;
        
        if (empty($shareId)) {
            throw new Exception("Share ID is required");
        }
        
        // Update share status
        $query = "UPDATE social_shares SET 
            status = ?, 
            shared_at = NOW(),
            updated_at = NOW()
            WHERE share_id = ?";
        
        $status = $success ? 'completed' : 'failed';
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $shareId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Share status updated successfully'
            ]);
        } else {
            throw new Exception("Share not found or already updated");
        }

    } catch (Exception $e) {
        throw new Exception("Failed to validate share: " . $e->getMessage());
    }
}

function trackShareAttempt($db, $userId, $platform, $contentType, $content, $link) {
    try {
        $shareId = 'share_' . uniqid() . '_' . time();
        
        $query = "INSERT INTO social_shares (
            id, user_id, platform, content_type, content, 
            referral_link, share_id, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'attempted', NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            generateUUID(),
            $userId,
            $platform,
            $contentType,
            $content,
            $link,
            $shareId
        ]);
        
        return $shareId;
        
    } catch (Exception $e) {
        error_log("Failed to track share attempt: " . $e->getMessage());
        return null;
    }
}

function generateStateToken() {
    return bin2hex(random_bytes(16));
}

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Enhanced social sharing table with status tracking
function createEnhancedSocialSharingTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS social_shares (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        platform ENUM('facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram', 'instagram', 'tiktok') NOT NULL,
        content_type ENUM('referral', 'achievement', 'investment', 'custom', 'template') NOT NULL,
        content TEXT NULL,
        referral_link VARCHAR(500) NULL,
        campaign_id VARCHAR(100) NULL,
        share_id VARCHAR(100) UNIQUE NOT NULL,
        status ENUM('attempted', 'completed', 'failed', 'cancelled') DEFAULT 'attempted',
        clicks INT DEFAULT 0,
        conversions INT DEFAULT 0,
        shared_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_user_id (user_id),
        INDEX idx_platform (platform),
        INDEX idx_content_type (content_type),
        INDEX idx_share_id (share_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_campaign_id (campaign_id)
    )";
    
    try {
        $db->exec($query);
    } catch (PDOException $e) {
        error_log("Enhanced social sharing table creation: " . $e->getMessage());
    }
}

// Initialize enhanced table
createEnhancedSocialSharingTable($db);
?>
