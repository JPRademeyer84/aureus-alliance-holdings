<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';
session_start();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check investments for this user
    $investments = [];
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT * FROM aureus_investments WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check all investments in database
    $stmt = $db->prepare("SELECT COUNT(*) as total_investments FROM aureus_investments");
    $stmt->execute();
    $total_investments = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all investments to see what user_ids they have
    $stmt = $db->prepare("SELECT id, user_id, email, package_name, amount, created_at FROM aureus_investments ORDER BY created_at DESC");
    $stmt->execute();
    $all_investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'user_authenticated' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_email' => $_SESSION['user_email'] ?? null,
        'user_username' => $_SESSION['user_username'] ?? null,
        'user_investments' => $investments,
        'total_investments_in_db' => $total_investments['total_investments'],
        'all_investments' => $all_investments,
        'cookies' => $_COOKIE,
        'headers' => getallheaders(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'session_data' => $_SESSION ?? null,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>
