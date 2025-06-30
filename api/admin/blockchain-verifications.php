<?php
/**
 * Blockchain Verifications API
 * Provides detailed blockchain verification data for admin dashboard
 */

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $verifications = getBlockchainVerifications($db);
        
        echo json_encode([
            'success' => true,
            'data' => $verifications
        ]);
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getBlockchainVerifications($db) {
    $query = "SELECT 
                payment_id,
                transaction_hash,
                amount_usd,
                chain,
                verification_status,
                blockchain_verified,
                verification_confidence,
                verification_reason,
                blockchain_verification_data,
                created_at,
                auto_verified_at
              FROM manual_payment_transactions 
              WHERE transaction_hash IS NOT NULL 
              AND transaction_hash != ''
              ORDER BY created_at DESC 
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $verifications = [];
    
    foreach ($results as $row) {
        $blockchainData = null;
        $verificationChecks = [];
        $verificationErrors = [];
        
        // Parse blockchain verification data if available
        if (!empty($row['blockchain_verification_data'])) {
            $verificationData = json_decode($row['blockchain_verification_data'], true);
            
            if ($verificationData) {
                $blockchainData = $verificationData['blockchain_data'] ?? null;
                $verificationChecks = $verificationData['checks'] ?? [];
                $verificationErrors = $verificationData['errors'] ?? [];
            }
        }
        
        $verifications[] = [
            'payment_id' => $row['payment_id'],
            'transaction_hash' => $row['transaction_hash'],
            'amount_usd' => (float)$row['amount_usd'],
            'chain' => $row['chain'],
            'verification_status' => $row['verification_status'],
            'blockchain_verified' => (bool)$row['blockchain_verified'],
            'verification_confidence' => (int)$row['verification_confidence'],
            'verification_checks' => $verificationChecks,
            'verification_errors' => $verificationErrors,
            'verification_reason' => $row['verification_reason'],
            'created_at' => $row['created_at'],
            'auto_verified_at' => $row['auto_verified_at'],
            'blockchain_data' => $blockchainData
        ];
    }
    
    return $verifications;
}
?>
