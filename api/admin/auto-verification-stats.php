<?php
/**
 * Auto-Verification Statistics API
 * Provides stats on automatic payment verification system
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
        $stats = getAutoVerificationStats($db);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
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

function getAutoVerificationStats($db) {
    // Get overall statistics
    $totalQuery = "SELECT COUNT(*) as total FROM manual_payment_transactions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $totalPayments = $totalResult['total'];
    
    // Get auto-approved count
    $autoApprovedQuery = "SELECT COUNT(*) as auto_approved FROM manual_payment_transactions 
                         WHERE verification_status = 'auto_approved' 
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $autoStmt = $db->prepare($autoApprovedQuery);
    $autoStmt->execute();
    $autoResult = $autoStmt->fetch(PDO::FETCH_ASSOC);
    $autoApproved = $autoResult['auto_approved'];
    
    // Get manual review count
    $manualQuery = "SELECT COUNT(*) as manual_review FROM manual_payment_transactions 
                   WHERE verification_status IN ('pending', 'manual_review_required') 
                   AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $manualStmt = $db->prepare($manualQuery);
    $manualStmt->execute();
    $manualResult = $manualStmt->fetch(PDO::FETCH_ASSOC);
    $manualReview = $manualResult['manual_review'];
    
    // Calculate auto-approval rate
    $autoApprovalRate = $totalPayments > 0 ? round(($autoApproved / $totalPayments) * 100, 1) : 0;
    
    // Get average confidence score
    $avgConfidenceQuery = "SELECT AVG(verification_confidence) as avg_confidence 
                          FROM manual_payment_transactions 
                          WHERE verification_confidence IS NOT NULL 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $avgStmt = $db->prepare($avgConfidenceQuery);
    $avgStmt->execute();
    $avgResult = $avgStmt->fetch(PDO::FETCH_ASSOC);
    $avgConfidence = $avgResult['avg_confidence'] ? round($avgResult['avg_confidence'], 1) : 0;
    
    // Get recent payments
    $recentQuery = "SELECT 
                      payment_id,
                      amount_usd,
                      verification_status,
                      verification_confidence,
                      verification_reason,
                      created_at,
                      CASE WHEN verification_status = 'auto_approved' THEN 1 ELSE 0 END as auto_approved
                    FROM manual_payment_transactions 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY created_at DESC 
                    LIMIT 10";
    
    $recentStmt = $db->prepare($recentQuery);
    $recentStmt->execute();
    $recentPayments = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format recent payments
    $formattedRecent = array_map(function($payment) {
        return [
            'payment_id' => $payment['payment_id'],
            'amount_usd' => (float)$payment['amount_usd'],
            'auto_approved' => (bool)$payment['auto_approved'],
            'confidence' => $payment['verification_confidence'] ? (int)$payment['verification_confidence'] : 0,
            'created_at' => $payment['created_at'],
            'reason' => $payment['verification_reason'] ?: 'No reason provided'
        ];
    }, $recentPayments);
    
    return [
        'total_payments' => (int)$totalPayments,
        'auto_approved' => (int)$autoApproved,
        'manual_review' => (int)$manualReview,
        'auto_approval_rate' => $autoApprovalRate,
        'avg_confidence' => $avgConfidence,
        'recent_payments' => $formattedRecent,
        'system_status' => [
            'enabled' => true,
            'confidence_threshold' => 80,
            'max_auto_amount' => 50000,
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ];
}
?>
