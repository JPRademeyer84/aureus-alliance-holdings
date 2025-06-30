<?php
/**
 * Process Commission API - New 20% Direct Commission Model
 * 
 * This API processes commissions when a referral makes an investment
 * Uses the new business model: 20% direct commission (no multi-level)
 */

require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, null, 'Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['investment_id', 'referrer_identifier', 'investment_amount'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendResponse(false, null, "Field '$field' is required", 400);
        }
    }

    $investmentId = $input['investment_id'];
    $referrerIdentifier = $input['referrer_identifier']; // username or email
    $investmentAmount = floatval($input['investment_amount']);
    
    // Find the referrer user
    $referrerQuery = "SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1";
    $referrerStmt = $db->prepare($referrerQuery);
    $referrerStmt->execute([$referrerIdentifier, $referrerIdentifier]);
    $referrer = $referrerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$referrer) {
        sendResponse(false, null, 'Referrer not found', 404);
    }

    // Get investment details
    $investmentQuery = "SELECT * FROM aureus_investments WHERE id = ?";
    $investmentStmt = $db->prepare($investmentQuery);
    $investmentStmt->execute([$investmentId]);
    $investment = $investmentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$investment) {
        sendResponse(false, null, 'Investment not found', 404);
    }

    // Check if commission already processed for this investment
    $existingQuery = "SELECT id FROM commission_records WHERE investment_id = ? AND user_id = ?";
    $existingStmt = $db->prepare($existingQuery);
    $existingStmt->execute([$investmentId, $referrer['id']]);
    
    if ($existingStmt->fetch()) {
        sendResponse(false, null, 'Commission already processed for this investment', 409);
    }

    // Calculate commission using new model
    // 20% of the 15% commission allocation = 3% of total investment
    $commissionAllocation = $investmentAmount * 0.15; // 15% goes to commission pool
    $actualCommission = $commissionAllocation * 0.20; // 20% of that goes to referrer
    
    // Get current phase
    $phaseQuery = "SELECT id FROM phases WHERE is_active = TRUE ORDER BY phase_number ASC LIMIT 1";
    $phaseStmt = $db->query($phaseQuery);
    $activePhase = $phaseStmt->fetch(PDO::FETCH_ASSOC);
    $phaseId = $activePhase ? $activePhase['id'] : 1;

    // Create commission record
    $commissionQuery = "INSERT INTO commission_records (
        user_id, 
        referral_user_id, 
        investment_id, 
        commission_amount, 
        commission_percentage, 
        commission_type, 
        status, 
        phase_id,
        created_at
    ) VALUES (?, ?, ?, ?, 20.00, 'direct_sales', 'pending', ?, NOW())";
    
    $commissionStmt = $db->prepare($commissionQuery);
    $success = $commissionStmt->execute([
        $referrer['id'],
        $investment['user_id'],
        $investmentId,
        $actualCommission,
        $phaseId
    ]);
    
    if (!$success) {
        sendResponse(false, null, 'Failed to create commission record', 500);
    }

    $commissionId = $db->lastInsertId();

    // Update investment record with commission info
    $updateInvestmentQuery = "UPDATE aureus_investments SET 
        commission_amount = ?, 
        commission_paid = FALSE 
        WHERE id = ?";
    
    $updateStmt = $db->prepare($updateInvestmentQuery);
    $updateStmt->execute([$actualCommission, $investmentId]);

    // Update phase commission statistics
    $updatePhaseQuery = "UPDATE phases SET 
        commission_paid = commission_paid + ? 
        WHERE id = ?";
    
    $updatePhaseStmt = $db->prepare($updatePhaseQuery);
    $updatePhaseStmt->execute([$actualCommission, $phaseId]);

    // Prepare response data
    $responseData = [
        'commission_id' => $commissionId,
        'referrer' => [
            'id' => $referrer['id'],
            'username' => $referrer['username'],
            'email' => $referrer['email']
        ],
        'investment' => [
            'id' => $investmentId,
            'amount' => $investmentAmount,
            'package_name' => $investment['package_name']
        ],
        'commission' => [
            'amount' => $actualCommission,
            'percentage' => 20.0,
            'type' => 'direct_sales',
            'status' => 'pending',
            'phase_id' => $phaseId
        ],
        'calculation' => [
            'investment_amount' => $investmentAmount,
            'commission_pool_percentage' => 15.0,
            'commission_pool_amount' => $commissionAllocation,
            'referrer_percentage' => 20.0,
            'final_commission' => $actualCommission,
            'formula' => 'Investment × 15% × 20% = Commission'
        ]
    ];

    sendResponse(true, $responseData, 'Commission processed successfully');

} catch (Exception $e) {
    error_log("Commission processing error: " . $e->getMessage());
    sendResponse(false, null, 'Internal server error: ' . $e->getMessage(), 500);
}
?>
