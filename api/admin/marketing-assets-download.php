<?php
// Marketing Assets Download Handler
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once __DIR__ . '/../config/database.php';

try {
    // Get asset ID from query parameter
    $assetId = $_GET['id'] ?? null;
    
    if (!$assetId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Asset ID is required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get asset details from database
    $query = "SELECT * FROM marketing_assets WHERE id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Asset not found']);
        exit;
    }
    
    // Construct file path
    $uploadsDir = __DIR__ . '/../../uploads/marketing-assets/';
    $filePath = $uploadsDir . $asset['file_path'];
    
    // Check if file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'File not found on server']);
        exit;
    }
    
    // Get file info
    $fileSize = filesize($filePath);
    $fileName = basename($asset['file_path']);
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    
    // Set appropriate content type based on file extension
    $contentTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'psd' => 'application/octet-stream',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript'
    ];
    
    $contentType = $contentTypes[strtolower($fileExtension)] ?? 'application/octet-stream';
    
    // Set headers for file download
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $asset['title'] . '.' . $fileExtension . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Log download activity (optional)
    try {
        $logQuery = "INSERT INTO marketing_asset_downloads (asset_id, downloaded_at, ip_address, user_agent) VALUES (?, NOW(), ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([
            $assetId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't stop download
        error_log('Failed to log download: ' . $e->getMessage());
    }
    
    // Output file content
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Download failed: ' . $e->getMessage()
    ]);
}
?>
