<?php
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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create tables if they don't exist
    $database->createTables();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle wallet connection logging
    if (isset($input['event']) && $input['event'] === 'wallet_connected') {
        $query = "INSERT INTO wallet_connections (provider, address, chain_id) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$input['provider'], $input['address'], $input['chainId']]);
        
        sendSuccessResponse(null, 'Wallet connection recorded');
    }

    // Validate required fields for investment
    $required_fields = ['name', 'email', 'walletAddress', 'chain', 'amount', 'investmentPlan'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendErrorResponse("Field '$field' is required", 400);
        }
    }

    // Get current active phase
    $phaseQuery = "SELECT id FROM phases WHERE is_active = TRUE ORDER BY phase_number ASC LIMIT 1";
    $phaseStmt = $db->query($phaseQuery);
    $activePhase = $phaseStmt->fetch(PDO::FETCH_ASSOC);
    $phaseId = $activePhase ? $activePhase['id'] : 1; // Default to phase 1

    // Calculate revenue distribution (15% commission, 15% competition, 25% platform, 10% NPO, 35% mine)
    $amount = floatval($input['amount']);
    $commissionAmount = $amount * 0.15; // 15% for direct commission
    $competitionAmount = $amount * 0.15; // 15% for competition
    $npoAmount = $amount * 0.10; // 10% for NPO
    $platformAmount = $amount * 0.25; // 25% for platform
    $mineAmount = $amount * 0.35; // 35% for mine

    $revenueDistribution = json_encode([
        'commission' => $commissionAmount,
        'competition' => $competitionAmount,
        'npo' => $npoAmount,
        'platform' => $platformAmount,
        'mine' => $mineAmount
    ]);

    // Insert investment record with 12-month NFT delivery
    $query = "INSERT INTO aureus_investments (
        user_id,
        name,
        email,
        wallet_address,
        chain,
        amount,
        investment_plan,
        status,
        nft_delivery_date,
        delivery_status,
        phase_id,
        commission_amount,
        revenue_distribution
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 12 MONTH), 'pending', ?, ?, ?)";

    $stmt = $db->prepare($query);

    $stmt->execute([
        $input['email'], // Using email as user_id
        $input['name'],
        $input['email'],
        $input['walletAddress'],
        $input['chain'],
        $input['amount'],
        $input['investmentPlan'],
        'pending',
        $phaseId,
        $commissionAmount,
        $revenueDistribution
    ]);

    $investmentId = $db->lastInsertId();

    // Process revenue distribution
    try {
        // Log revenue distribution
        $distributionQuery = "INSERT INTO revenue_distribution_log (
            investment_id, phase_id, total_amount, commission_amount,
            competition_amount, npo_amount, platform_amount, mine_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $distributionStmt = $db->prepare($distributionQuery);
        $distributionStmt->execute([
            $investmentId, $phaseId, $amount, $commissionAmount,
            $competitionAmount, $npoAmount, $platformAmount, $mineAmount
        ]);

        // Add to NPO fund
        $npoQuery = "INSERT INTO npo_fund (
            transaction_id, source_investment_id, phase_id, amount, percentage, status
        ) VALUES (?, ?, ?, ?, 10.00, 'pending')";

        $npoStmt = $db->prepare($npoQuery);
        $npoStmt->execute([
            'INV_' . $investmentId . '_NPO', $investmentId, $phaseId, $npoAmount
        ]);

        // Update phase statistics
        $updatePhaseQuery = "UPDATE phases SET
            packages_sold = packages_sold + 1,
            total_revenue = total_revenue + ?,
            competition_pool = competition_pool + ?,
            npo_fund = npo_fund + ?,
            platform_fund = platform_fund + ?,
            mine_fund = mine_fund + ?
            WHERE id = ?";

        $updatePhaseStmt = $db->prepare($updatePhaseQuery);
        $updatePhaseStmt->execute([
            $amount, $competitionAmount, $npoAmount, $platformAmount, $mineAmount, $phaseId
        ]);

        // Process referral commission if referrer exists
        if (isset($input['referrer']) && !empty($input['referrer'])) {
            $referrerQuery = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
            $referrerStmt = $db->prepare($referrerQuery);
            $referrerStmt->execute([$input['referrer'], $input['referrer']]);
            $referrer = $referrerStmt->fetch(PDO::FETCH_ASSOC);

            if ($referrer) {
                // Create commission record (20% of commission amount = 20% of 15% = 3% of total)
                $actualCommission = $commissionAmount * 0.20; // 20% of the 15% commission allocation

                $commissionQuery = "INSERT INTO commission_records (
                    user_id, referral_user_id, investment_id, commission_amount,
                    commission_percentage, commission_type, status, phase_id
                ) VALUES (?, ?, ?, ?, 20.00, 'direct_sales', 'pending', ?)";

                $commissionStmt = $db->prepare($commissionQuery);
                $commissionStmt->execute([
                    $referrer['id'], $input['email'], $investmentId, $actualCommission, $phaseId
                ]);

                // Auto-enroll referrer in active competition if exists
                $competitionQuery = "SELECT id FROM competitions WHERE phase_id = ? AND is_active = TRUE LIMIT 1";
                $competitionStmt = $db->prepare($competitionQuery);
                $competitionStmt->execute([$phaseId]);
                $competition = $competitionStmt->fetch(PDO::FETCH_ASSOC);

                if ($competition) {
                    $enrollQuery = "INSERT INTO competition_participants (
                        competition_id, user_id, sales_count, total_volume, joined_at
                    ) VALUES (?, ?, 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    sales_count = sales_count + 1,
                    total_volume = total_volume + ?";

                    $enrollStmt = $db->prepare($enrollQuery);
                    $enrollStmt->execute([$competition['id'], $referrer['id'], $amount, $amount]);
                }
            }
        }

        // Generate share certificate automatically
        try {
            $certificateNumber = 'AAA-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $shareValue = 1.00;
            $sharesAmount = (int)$amount; // 1 share per dollar invested
            $certificateTotalValue = $sharesAmount * $shareValue;
            $issueDate = date('Y-m-d H:i:s');
            $expiryDate = date('Y-m-d H:i:s', strtotime('+12 months'));

            $certificateMetadata = json_encode([
                'generation_date' => $issueDate,
                'investment_package' => $input['investmentPlan'],
                'investment_amount' => $amount,
                'phase_id' => $phaseId,
                'certificate_terms' => [
                    'validity_period' => '12 months',
                    'void_conditions' => 'Certificate becomes null and void upon NFT sale',
                    'share_type' => 'Digital Mining Shares',
                    'transferable' => false
                ]
            ]);

            $certificateQuery = "INSERT INTO share_certificates (
                certificate_number, user_id, investment_id, shares_amount,
                share_value, total_value, issue_date, expiry_date,
                is_printed, print_count, is_void, metadata, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE, 0, FALSE, ?, NOW(), NOW())";

            $certificateStmt = $db->prepare($certificateQuery);
            $certificateStmt->execute([
                $certificateNumber, $input['email'], $investmentId, $sharesAmount,
                $shareValue, $certificateTotalValue, $issueDate, $expiryDate, $certificateMetadata
            ]);

            $certificateId = $db->lastInsertId();

            // Update investment with certificate reference
            $updateCertQuery = "UPDATE aureus_investments SET certificate_id = ? WHERE id = ?";
            $updateCertStmt = $db->prepare($updateCertQuery);
            $updateCertStmt->execute([$certificateId, $investmentId]);

        } catch (Exception $e) {
            // Log error but don't fail the investment
            error_log("Certificate generation failed: " . $e->getMessage());
        }

    } catch (Exception $e) {
        // Log error but don't fail the investment
        error_log("Revenue distribution processing failed: " . $e->getMessage());
    }

    // Record terms acceptance if provided
    if (isset($input['termsData']) && is_array($input['termsData'])) {
        $termsData = $input['termsData'];

        // Get client IP address
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }

        // Insert terms acceptance record (updated for new business model)
        $termsQuery = "INSERT INTO terms_acceptance (
            user_id,
            email,
            wallet_address,
            investment_id,
            gold_mining_investment_accepted,
            nft_shares_understanding_accepted,
            delivery_timeline_accepted,
            commission_structure_accepted,
            risk_acknowledgment_accepted,
            ip_address,
            user_agent,
            acceptance_timestamp,
            terms_version
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $termsStmt = $db->prepare($termsQuery);

        $termsStmt->execute([
            $input['email'], // user_id
            $input['email'],
            $input['walletAddress'],
            $investmentId,
            $termsData['goldMiningInvestmentAccepted'] ? 1 : 0,
            $termsData['nftSharesUnderstandingAccepted'] ? 1 : 0,
            $termsData['deliveryTimelineAccepted'] ? 1 : 0,
            $termsData['commissionStructureAccepted'] ?? 1, // New field for commission understanding
            $termsData['riskAcknowledgmentAccepted'] ? 1 : 0,
            $ipAddress,
            $input['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            $termsData['acceptanceTimestamp'] ?? date('Y-m-d H:i:s'),
            $termsData['termsVersion'] ?? '1.0'
        ]);
    }

    // Get active payment wallet for the chain
    $query = "SELECT address FROM investment_wallets WHERE chain = ? AND is_active = 1 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$input['chain']]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        // Create a default wallet if none exists
        $default_address = '0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b';
        $query = "INSERT INTO investment_wallets (chain, address, is_active) VALUES (?, ?, 1)";
        $stmt = $db->prepare($query);
        $stmt->execute([$input['chain'], $default_address]);
        $payment_address = $default_address;
    } else {
        $payment_address = $wallet['address'];
    }

    // Simulate payment processing (in real app, this would be handled by blockchain listeners)
    // For demo purposes, we'll mark as completed after a delay
    
    $response_data = [
        'name' => $input['name'],
        'email' => $input['email'],
        'walletAddress' => $input['walletAddress'],
        'chain' => $input['chain'],
        'amount' => $input['amount'],
        'investmentPlan' => $input['investmentPlan'],
        'paymentAddress' => $payment_address,
        'paymentId' => $investmentId,
        'investment_id' => $investmentId,
        'nft_delivery_date' => date('Y-m-d', strtotime('+12 months')), // Updated to 12 months
        'phase_id' => $phaseId,
        'revenue_distribution' => [
            'commission' => $commissionAmount,
            'competition' => $competitionAmount,
            'npo_fund' => $npoAmount,
            'platform' => $platformAmount,
            'mine_setup' => $mineAmount
        ],
        'commission_structure' => '20% direct sales commission',
        'nft_countdown_months' => 12,
        'share_certificate' => [
            'certificate_id' => $certificateId ?? null,
            'certificate_number' => $certificateNumber ?? null,
            'shares_amount' => $sharesAmount ?? 0,
            'share_value' => $shareValue ?? 1.00,
            'total_value' => $certificateTotalValue ?? 0,
            'expiry_date' => $expiryDate ?? null,
            'validity_period' => '12 months'
        ],
        'terms_recorded' => isset($input['termsData']),
        'timestamp' => date('c')
    ];

    sendSuccessResponse($response_data, 'Investment processed successfully');

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
