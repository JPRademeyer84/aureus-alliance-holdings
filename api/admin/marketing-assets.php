<?php
require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $database->createTables();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Check if marketing_assets table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'marketing_assets'");
            if ($tableCheck->rowCount() == 0) {
                // Table doesn't exist, return empty assets
                echo json_encode([
                    'success' => true,
                    'assets' => []
                ]);
                break;
            }

            // Get all marketing assets
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    type,
                    title,
                    description,
                    COALESCE(file_url, '') as url,
                    COALESCE(file_size, 0) as size,
                    COALESCE(file_format, '') as format,
                    COALESCE(status, 'active') as status,
                    created_at,
                    updated_at
                FROM marketing_assets
                WHERE status = 'active'
                ORDER BY created_at DESC
            ");

            $stmt->execute();
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'assets' => $assets
            ]);
            break;
            
        case 'POST':
            // Upload new marketing asset (admin only)
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                exit;
            }

            // Admin authentication check
            session_start();
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
                exit;
            }

            // Check if marketing_assets table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'marketing_assets'");
            if ($tableCheck->rowCount() == 0) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Marketing assets table not found']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO marketing_assets (
                    type, title, description, file_url, file_size,
                    file_format, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['type'],
                $input['title'],
                $input['description'],
                $input['file_url'],
                $input['file_size'],
                $input['file_format']
            ]);
            
            $assetId = $pdo->lastInsertId();
            
            // Return the created asset
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    type,
                    title,
                    description,
                    file_url as url,
                    file_size as size,
                    file_format as format,
                    status,
                    created_at,
                    updated_at
                FROM marketing_assets 
                WHERE id = ?
            ");
            
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Marketing asset uploaded successfully',
                'asset' => $asset
            ]);
            break;
            
        case 'DELETE':
            // Delete marketing asset (admin only)
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $pathParts = explode('/', trim($path, '/'));
            $assetId = end($pathParts);
            
            if (!$assetId || !is_numeric($assetId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
                exit;
            }
            
            // Admin authentication check
            session_start();
            if (!isset($_SESSION['admin_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE marketing_assets SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$assetId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Marketing asset deleted successfully'
            ]);
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
