<?php
require_once '../config/cors.php';
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendErrorResponse('Database connection failed', 500);
    }

    // Ensure tables exist
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        // Handle terms acceptance recording
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendErrorResponse('Invalid JSON input', 400);
        }

        // Validate required fields
        $required_fields = ['email', 'wallet_address', 'terms_data'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                sendErrorResponse("Field '$field' is required", 400);
            }
        }

        $termsData = $input['terms_data'];
        
        // Validate all terms are accepted
        $requiredTerms = [
            'goldMiningInvestmentAccepted',
            'nftSharesUnderstandingAccepted', 
            'deliveryTimelineAccepted',
            'dividendTimelineAccepted',
            'riskAcknowledgmentAccepted'
        ];
        
        foreach ($requiredTerms as $term) {
            if (!isset($termsData[$term]) || $termsData[$term] !== true) {
                sendErrorResponse("All terms must be accepted. Missing: $term", 400);
            }
        }

        // Get client IP address
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }

        // Insert terms acceptance record
        $query = "INSERT INTO terms_acceptance (
            user_id,
            email,
            wallet_address,
            investment_id,
            gold_mining_investment_accepted,
            nft_shares_understanding_accepted,
            delivery_timeline_accepted,
            dividend_timeline_accepted,
            risk_acknowledgment_accepted,
            ip_address,
            user_agent,
            acceptance_timestamp,
            terms_version
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        
        $success = $stmt->execute([
            $input['user_id'] ?? null,
            $input['email'],
            $input['wallet_address'],
            $input['investment_id'] ?? null,
            $termsData['goldMiningInvestmentAccepted'] ? 1 : 0,
            $termsData['nftSharesUnderstandingAccepted'] ? 1 : 0,
            $termsData['deliveryTimelineAccepted'] ? 1 : 0,
            $termsData['dividendTimelineAccepted'] ? 1 : 0,
            $termsData['riskAcknowledgmentAccepted'] ? 1 : 0,
            $ipAddress,
            $input['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            $termsData['acceptanceTimestamp'] ?? date('Y-m-d H:i:s'),
            $termsData['termsVersion'] ?? '1.0'
        ]);

        if (!$success) {
            throw new Exception('Failed to record terms acceptance');
        }

        $acceptanceId = $db->lastInsertId();

        sendSuccessResponse([
            'acceptance_id' => $acceptanceId,
            'message' => 'Terms acceptance recorded successfully',
            'timestamp' => date('Y-m-d H:i:s'),
            'all_terms_accepted' => true
        ], 'Terms acceptance recorded');

    } elseif ($method === 'GET') {
        // Handle retrieving terms acceptance records
        $email = $_GET['email'] ?? null;
        $walletAddress = $_GET['wallet_address'] ?? null;
        $investmentId = $_GET['investment_id'] ?? null;

        if (!$email && !$walletAddress && !$investmentId) {
            sendErrorResponse('Email, wallet address, or investment ID required', 400);
        }

        $query = "SELECT 
            id,
            user_id,
            email,
            wallet_address,
            investment_id,
            gold_mining_investment_accepted,
            nft_shares_understanding_accepted,
            delivery_timeline_accepted,
            dividend_timeline_accepted,
            risk_acknowledgment_accepted,
            all_terms_accepted,
            ip_address,
            acceptance_timestamp,
            terms_version,
            created_at
        FROM terms_acceptance 
        WHERE 1=1";

        $params = [];
        
        if ($email) {
            $query .= " AND email = ?";
            $params[] = $email;
        }
        
        if ($walletAddress) {
            $query .= " AND wallet_address = ?";
            $params[] = $walletAddress;
        }
        
        if ($investmentId) {
            $query .= " AND investment_id = ?";
            $params[] = $investmentId;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendSuccessResponse([
            'terms_acceptance_records' => $records,
            'count' => count($records)
        ], 'Terms acceptance records retrieved');

    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Terms acceptance API error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function sendSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>
