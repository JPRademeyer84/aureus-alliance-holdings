<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Create company_wallets table
    $query = "CREATE TABLE IF NOT EXISTS company_wallets (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        chain VARCHAR(50) UNIQUE NOT NULL,
        address_hash VARCHAR(255) NOT NULL,
        salt VARCHAR(255) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by VARCHAR(36),
        INDEX idx_chain (chain),
        INDEX idx_active (is_active)
    )";
    
    $db->exec($query);
    
    // Check if table was created
    $stmt = $db->query("SHOW TABLES LIKE 'company_wallets'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo json_encode([
            'success' => true,
            'message' => 'company_wallets table created successfully',
            'table_exists' => true
        ]);
    } else {
        throw new Exception('Table creation failed');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'table_exists' => false
    ]);
}
?>
