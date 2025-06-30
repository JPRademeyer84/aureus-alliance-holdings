<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Read and execute the SQL migration file
    $sqlFile = '../../database/migrations/create_manual_payment_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Migration file not found');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $executedCount = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $db->exec($statement);
            $executedCount++;
        } catch (PDOException $e) {
            // Skip if table already exists
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = 'Statement failed: ' . substr($statement, 0, 100) . '... Error: ' . $e->getMessage();
            }
        }
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = '../../uploads/payment_proofs';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Manual payment system tables created successfully',
        'executed_statements' => $executedCount,
        'errors' => $errors,
        'uploads_directory_created' => is_dir($uploadsDir)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
