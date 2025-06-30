<?php
// Simple login test without all the security layers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Log the request
error_log("Simple login test - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Simple login test - Input: " . file_get_contents('php://input'));

try {
    // Simple database connection
    $pdo = new PDO(
        'mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'No JSON input received']);
            exit;
        }

        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password required']);
            exit;
        }

        // Check admin user
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            echo json_encode(['success' => false, 'error' => 'Admin user not found']);
            exit;
        }

        // Verify password
        if (password_verify($password, $admin['password_hash'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'role' => $admin['role'],
                        'full_name' => $admin['full_name'] ?? 'Admin User'
                    ]
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
        }

    } else {
        // GET request - show status
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Simple login test endpoint ready',
            'admin_user_exists' => $result['count'] > 0,
            'database' => 'aureus_angels',
            'port' => 3506
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
