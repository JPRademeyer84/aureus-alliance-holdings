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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $dashboardData = [];
    
    // Test 1: User Profile Data
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        $profileQuery = "SELECT 
            u.id, u.username, u.email, u.full_name, u.created_at,
            up.phone, up.country, up.city, up.date_of_birth, up.profile_image, up.bio,
            up.telegram_username, up.whatsapp_number, up.twitter_handle, 
            up.instagram_handle, up.linkedin_profile, up.facebook_profile,
            up.kyc_status, up.kyc_verified_at, up.kyc_rejected_reason,
            up.profile_completion, up.updated_at
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?";
        
        $profileStmt = $db->prepare($profileQuery);
        $profileStmt->execute([$userId]);
        $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
        
        $dashboardData['user_profile'] = $profile;
    } else {
        $dashboardData['user_profile'] = null;
        $dashboardData['error'] = 'No user session active';
    }
    
    // Test 2: Investment Packages
    try {
        $packagesQuery = "SELECT id, name, description, min_investment, max_investment, 
                                 expected_return, duration_months, status, created_at 
                          FROM investment_packages 
                          WHERE status = 'active' 
                          ORDER BY min_investment ASC";
        $packagesStmt = $db->prepare($packagesQuery);
        $packagesStmt->execute();
        $packages = $packagesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dashboardData['investment_packages'] = $packages;
    } catch (Exception $e) {
        $dashboardData['investment_packages'] = [];
        $dashboardData['packages_error'] = $e->getMessage();
    }
    
    // Test 3: Company Wallets
    try {
        $walletsQuery = "SELECT id, currency, wallet_address, network, status, created_at 
                         FROM company_wallets 
                         WHERE status = 'active' 
                         ORDER BY currency ASC";
        $walletsStmt = $db->prepare($walletsQuery);
        $walletsStmt->execute();
        $wallets = $walletsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dashboardData['company_wallets'] = $wallets;
    } catch (Exception $e) {
        $dashboardData['company_wallets'] = [];
        $dashboardData['wallets_error'] = $e->getMessage();
    }
    
    // Test 4: KYC Status
    if (isset($_SESSION['user_id'])) {
        try {
            $kycQuery = "SELECT kyc_status FROM user_profiles WHERE user_id = ?";
            $kycStmt = $db->prepare($kycQuery);
            $kycStmt->execute([$_SESSION['user_id']]);
            $kycData = $kycStmt->fetch(PDO::FETCH_ASSOC);
            
            $dashboardData['kyc_status'] = $kycData['kyc_status'] ?? 'pending';
        } catch (Exception $e) {
            $dashboardData['kyc_status'] = 'pending';
            $dashboardData['kyc_error'] = $e->getMessage();
        }
    }
    
    // Test 5: Investment Stats (if user has investments)
    if (isset($_SESSION['user_id'])) {
        try {
            $statsQuery = "SELECT 
                COUNT(*) as total_investments,
                COALESCE(SUM(amount), 0) as total_invested,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_investments
                FROM aureus_investments 
                WHERE user_id = ?";
            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute([$_SESSION['user_id']]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            $dashboardData['investment_stats'] = $stats;
        } catch (Exception $e) {
            $dashboardData['investment_stats'] = [
                'total_investments' => 0,
                'total_invested' => 0,
                'completed_investments' => 0
            ];
            $dashboardData['stats_error'] = $e->getMessage();
        }
    }
    
    // Test 6: Leaderboard Data
    try {
        $leaderboardQuery = "SELECT 
            u.username, u.full_name,
            COALESCE(SUM(ai.amount), 0) as total_invested,
            COUNT(ai.id) as investment_count
            FROM users u
            LEFT JOIN aureus_investments ai ON u.id = ai.user_id AND ai.status = 'completed'
            GROUP BY u.id, u.username, u.full_name
            HAVING total_invested > 0
            ORDER BY total_invested DESC
            LIMIT 10";
        $leaderboardStmt = $db->prepare($leaderboardQuery);
        $leaderboardStmt->execute();
        $leaderboard = $leaderboardStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dashboardData['leaderboard'] = $leaderboard;
    } catch (Exception $e) {
        $dashboardData['leaderboard'] = [];
        $dashboardData['leaderboard_error'] = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Dashboard test completed',
        'data' => $dashboardData,
        'session_info' => [
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id(),
            'session_active' => session_status() === PHP_SESSION_ACTIVE
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Dashboard test error: ' . $e->getMessage()
    ]);
}
?>
