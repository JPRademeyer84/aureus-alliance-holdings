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
    
    switch ($method) {
        case 'GET':
            handleGetShareStats($db);
            break;
        case 'POST':
            handleTrackShare($db);
            break;
        default:
            throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleGetShareStats($db) {
    try {
        $action = $_GET['action'] ?? 'user_stats';
        $userId = $_GET['user_id'] ?? null;
        
        if ($action === 'user_stats' && $userId) {
            // Get user's sharing statistics
            $query = "SELECT 
                platform,
                COUNT(*) as share_count,
                MAX(created_at) as last_shared,
                SUM(CASE WHEN clicks > 0 THEN 1 ELSE 0 END) as clicked_shares,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions
            FROM social_shares 
            WHERE user_id = ? 
            GROUP BY platform
            ORDER BY share_count DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$userId]);
            $platformStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total stats
            $totalQuery = "SELECT 
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                COUNT(DISTINCT platform) as platforms_used
            FROM social_shares 
            WHERE user_id = ?";
            
            $stmt = $db->prepare($totalQuery);
            $stmt->execute([$userId]);
            $totalStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get recent shares
            $recentQuery = "SELECT 
                platform, content_type, created_at, clicks, conversions
            FROM social_shares 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10";
            
            $stmt = $db->prepare($recentQuery);
            $stmt->execute([$userId]);
            $recentShares = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'platform_stats' => $platformStats,
                'total_stats' => $totalStats,
                'recent_shares' => $recentShares
            ]);
            
        } elseif ($action === 'leaderboard') {
            // Get sharing leaderboard
            $query = "SELECT 
                u.username,
                u.full_name,
                COUNT(ss.id) as total_shares,
                SUM(ss.clicks) as total_clicks,
                SUM(ss.conversions) as total_conversions,
                COUNT(DISTINCT ss.platform) as platforms_used
            FROM users u
            LEFT JOIN social_shares ss ON u.id = ss.user_id
            WHERE ss.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY u.id
            HAVING total_shares > 0
            ORDER BY total_shares DESC, total_clicks DESC
            LIMIT 20";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'leaderboard' => $leaderboard
            ]);
        }

    } catch (Exception $e) {
        throw new Exception("Failed to get share stats: " . $e->getMessage());
    }
}

function handleTrackShare($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['user_id']) || empty($input['platform']) || empty($input['content_type'])) {
            throw new Exception("User ID, platform, and content type are required");
        }
        
        $userId = $input['user_id'];
        $platform = $input['platform'];
        $contentType = $input['content_type'];
        $content = $input['content'] ?? '';
        $referralLink = $input['referral_link'] ?? '';
        $campaignId = $input['campaign_id'] ?? null;
        
        // Generate unique share ID for tracking
        $shareId = generateShareId();
        
        // Insert share record
        $query = "INSERT INTO social_shares (
            id, user_id, platform, content_type, content, 
            referral_link, campaign_id, share_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            generateUUID(),
            $userId,
            $platform,
            $contentType,
            $content,
            $referralLink,
            $campaignId,
            $shareId
        ]);
        
        // Update user sharing stats
        updateUserSharingStats($db, $userId, $platform);
        
        echo json_encode([
            'success' => true,
            'share_id' => $shareId,
            'message' => 'Share tracked successfully'
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to track share: " . $e->getMessage());
    }
}

function updateUserSharingStats($db, $userId, $platform) {
    try {
        // Update or insert user sharing statistics
        $query = "INSERT INTO user_sharing_stats (
            user_id, platform, total_shares, last_shared, created_at
        ) VALUES (?, ?, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            total_shares = total_shares + 1,
            last_shared = NOW(),
            updated_at = NOW()";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$userId, $platform]);
        
    } catch (Exception $e) {
        error_log("Failed to update user sharing stats: " . $e->getMessage());
    }
}

function generateShareId() {
    return 'share_' . uniqid() . '_' . time();
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

// Create social sharing tables if they don't exist
function createSocialSharingTables($db) {
    // Social shares table
    $sharesTable = "CREATE TABLE IF NOT EXISTS social_shares (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        platform ENUM('facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram', 'instagram', 'tiktok') NOT NULL,
        content_type ENUM('referral', 'achievement', 'investment', 'custom', 'template') NOT NULL,
        content TEXT NULL,
        referral_link VARCHAR(500) NULL,
        campaign_id VARCHAR(100) NULL,
        share_id VARCHAR(100) UNIQUE NOT NULL,
        clicks INT DEFAULT 0,
        conversions INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_user_id (user_id),
        INDEX idx_platform (platform),
        INDEX idx_content_type (content_type),
        INDEX idx_share_id (share_id),
        INDEX idx_created_at (created_at),
        INDEX idx_campaign_id (campaign_id)
    )";
    
    // User sharing statistics table
    $statsTable = "CREATE TABLE IF NOT EXISTS user_sharing_stats (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id VARCHAR(255) NOT NULL,
        platform ENUM('facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram', 'instagram', 'tiktok') NOT NULL,
        total_shares INT DEFAULT 0,
        total_clicks INT DEFAULT 0,
        total_conversions INT DEFAULT 0,
        last_shared TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        UNIQUE KEY unique_user_platform (user_id, platform),
        INDEX idx_user_id (user_id),
        INDEX idx_platform (platform),
        INDEX idx_total_shares (total_shares),
        INDEX idx_last_shared (last_shared)
    )";
    
    // Share click tracking table
    $clicksTable = "CREATE TABLE IF NOT EXISTS share_clicks (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        share_id VARCHAR(100) NOT NULL,
        user_id VARCHAR(255) NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        referrer VARCHAR(500) NULL,
        converted BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_share_id (share_id),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        INDEX idx_converted (converted),
        
        FOREIGN KEY (share_id) REFERENCES social_shares(share_id) ON DELETE CASCADE
    )";
    
    try {
        $db->exec($sharesTable);
        $db->exec($statsTable);
        $db->exec($clicksTable);
    } catch (PDOException $e) {
        error_log("Social sharing tables creation: " . $e->getMessage());
    }
}

// Initialize tables
createSocialSharingTables($db);
?>
