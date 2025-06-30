<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'error' => 'Username is required'
        ]);
        exit;
    }

    // Check if username exists and get user ID
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$referrer) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid referral username'
        ]);
        exit;
    }

    // Store referral data in session for when user registers/invests
    $_SESSION['referral_data'] = [
        'referrer_user_id' => $referrer['id'],
        'referrer_username' => $referrer['username'],
        'timestamp' => date('c'),
        'source' => $input['source'] ?? 'direct_link',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Referral tracked successfully',
        'referrer' => [
            'username' => $referrer['username'],
            'user_id' => $referrer['id']
        ],
        'expires_in_days' => 30
    ]);

} catch (Exception $e) {
    error_log("Referral tracking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
