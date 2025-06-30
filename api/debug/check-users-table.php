<?php
// Debug API to check users table structure
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if users table exists and get its structure
    $query = "SHOW COLUMNS FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also check if there are any users in the table
    $countQuery = "SELECT COUNT(*) as user_count FROM users";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $userCount = $countStmt->fetch(PDO::FETCH_ASSOC);

    sendSuccessResponse([
        'table_exists' => true,
        'columns' => $columns,
        'user_count' => $userCount['user_count']
    ], 'Users table structure retrieved successfully');

} catch (Exception $e) {
    error_log("Database check error: " . $e->getMessage());
    sendErrorResponse('Database error: ' . $e->getMessage(), 500);
}
?>
