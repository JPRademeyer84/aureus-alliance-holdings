<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

try {
    $host = "localhost";
    $port = "3506";
    $dbname = "aureus_angels";
    $username = "root";
    $password = "";

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    if (!isset($_GET['short_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Short ID parameter is required"
        ]);
        exit();
    }

    $shortId = $_GET['short_id'];

    // Find custom investment where UUID starts with the short ID
    $stmt = $pdo->prepare("
        SELECT 
            ci.*,
            u.email,
            u.first_name,
            u.last_name
        FROM custom_investments ci
        LEFT JOIN users u ON ci.user_id = u.id
        WHERE ci.id LIKE CONCAT(?, '%')
        LIMIT 1
    ");
    
    $stmt->execute([$shortId]);
    $investment = $stmt->fetch();

    if (!$investment) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Custom investment not found"
        ]);
        exit();
    }

    // Format the response
    $response = [
        "id" => $investment["id"],
        "user_id" => $investment["user_id"],
        "package_name" => $investment["package_name"],
        "amount" => (float)$investment["amount"],
        "shares" => (int)$investment["shares"],
        "status" => $investment["status"],
        "created_at" => $investment["created_at"],
        "updated_at" => $investment["updated_at"],
        "user" => [
            "email" => $investment["email"],
            "first_name" => $investment["first_name"],
            "last_name" => $investment["last_name"]
        ]
    ];

    echo json_encode([
        "success" => true,
        "message" => "Custom investment retrieved successfully",
        "data" => $response
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
