<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

try {
    $database = new Database();
    $db = $database->getConnection();
    // Tables should already exist - no automatic creation

    // Get all admin users
    $query = "SELECT id, username, email, full_name, role, is_active, created_at FROM admin_users ORDER BY created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $admins,
        'count' => count($admins)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
