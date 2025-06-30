<?php
// Create marketing asset download tracking table
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create marketing_asset_downloads table for tracking
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS marketing_asset_downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        INDEX idx_asset_id (asset_id),
        INDEX idx_downloaded_at (downloaded_at),
        FOREIGN KEY (asset_id) REFERENCES marketing_assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($createTableQuery);
    
    echo json_encode([
        'success' => true,
        'message' => 'Marketing asset download tracking table created successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create table: ' . $e->getMessage()
    ]);
}
?>
