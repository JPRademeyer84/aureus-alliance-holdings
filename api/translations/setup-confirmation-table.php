<?php
// Setup translation confirmations table
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if translation_confirmations table exists
    $checkTableQuery = "SHOW TABLES LIKE 'translation_confirmations'";
    $checkTableStmt = $db->prepare($checkTableQuery);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() === 0) {
        // Create the translation_confirmations table
        $createTableQuery = "CREATE TABLE translation_confirmations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_id INT NOT NULL,
            language_id INT NOT NULL,
            confirmed_by VARCHAR(100) NOT NULL DEFAULT 'admin',
            confirmation_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_confirmation (key_id, language_id),
            FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
            FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
            INDEX idx_key_language (key_id, language_id),
            INDEX idx_confirmed_by (confirmed_by)
        )";
        
        $db->exec($createTableQuery);
        
        echo json_encode([
            'success' => true,
            'message' => 'Translation confirmations table created successfully',
            'table_created' => true
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Translation confirmations table already exists',
            'table_created' => false
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to setup translation confirmations table'
    ]);
}
?>
