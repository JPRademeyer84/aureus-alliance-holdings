<?php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../security/commission-security.php';
require_once '../config/financial-security.php';
require_once '../config/input-validator.php';

session_start();
handlePreflight();

try {
    $database = new Database();
    $db = $database->getConnection();

    // Initialize security manager
    $securityManager = new CommissionSecurityManager($db);

    // Create tables if they don't exist
    $database->createTables();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Get user ID from session (if logged in) or use wallet address as fallback
    $user_id = $_SESSION['user_id'] ?? $input['walletAddress'] ?? null;

    if (!$user_id) {
        sendErrorResponse('User authentication required or wallet address missing', 401);
    }

    // Use centralized validation
    $validationRules = ValidationRules::investment();
    $validationRules['shares'] = [
        'type' => 'integer',
        'required' => true,
        'min_value' => 1,
        'max_value' => 1000000
    ];
    $validationRules['roi'] = [
        'type' => 'float',
        'required' => true,
        'min_value' => 0,
        'max_value' => 999999999
    ];

    $validatedData = validateApiRequest($validationRules, 'investment_creation');

    // Extract validated data
    $packageName = $validatedData['package_name'];
    $amount = $validatedData['amount'];
    $shares = $validatedData['shares'];
    $roi = $validatedData['roi'];
    $walletAddress = $validatedData['wallet_address'] ?? '';
    $chain = $validatedData['chain'] ?? '';

    // Check KYC level restrictions
    require_once '../services/KYCLevelService.php';
    $kycService = new KYCLevelService($db);

    if (!$kycService->canPurchasePackage($user_id, $amount)) {
        $userLimits = $kycService->getInvestmentLimits($user_id);
        $userLevel = $kycService->getUserLevel($user_id);
        sendErrorResponse("Investment amount not allowed for your KYC level ($userLevel). Allowed range: $" . $userLimits['min'] . " - $" . $userLimits['max'], 403);
    }

    // Financial transaction validation
    $transactionId = uniqid('inv_', true);
    $additionalData = [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'payment_method' => $input['paymentMethod'] ?? 'wallet'
    ];

    $validation = validateFinancialTransaction(
        $transactionId,
        'investment',
        $user_id,
        $amount,
        'USDT',
        $additionalData
    );

    // Check validation result
    if ($validation['status'] === 'rejected') {
        logFinancialEvent('investment_rejected', SecurityLogger::LEVEL_WARNING,
            "Investment rejected due to validation failure", [
                'validation_id' => $validation['validation_id'],
                'risk_score' => $validation['risk_score'],
                'amount' => $input['amount']
            ], $user_id);

        sendErrorResponse("Investment cannot be processed due to security validation. Please contact support.", 403);
    }

    if ($validation['status'] === 'flagged' || $validation['approval_required']) {
        logFinancialEvent('investment_flagged', SecurityLogger::LEVEL_WARNING,
            "Investment flagged for manual review", [
                'validation_id' => $validation['validation_id'],
                'risk_score' => $validation['risk_score'],
                'amount' => $input['amount']
            ], $user_id);

        sendErrorResponse("Investment requires manual review. You will be notified once approved.", 202);
    }

    // Check payment method
    $paymentMethod = $input['paymentMethod'] ?? 'wallet';
    $usingCredits = $paymentMethod === 'credits';

    if (!$usingCredits) {
        // For wallet payments, require wallet info
        if (!isset($input['chainId']) || !isset($input['walletAddress'])) {
            sendErrorResponse("Wallet information required for wallet payments", 400);
        }
    }

    // Generate unique investment ID
    $investment_id = uniqid('inv_', true);

    // Handle credit payment if using credits
    if ($usingCredits) {
        // Check if user has sufficient credits
        $creditsQuery = "SELECT available_credits FROM user_credits WHERE user_id = ?";
        $creditsStmt = $db->prepare($creditsQuery);
        $creditsStmt->execute([$user_id]);
        $userCredits = $creditsStmt->fetch(PDO::FETCH_ASSOC);

        $availableCredits = $userCredits ? floatval($userCredits['available_credits']) : 0;
        $requiredAmount = floatval($input['amount']);

        if ($availableCredits < $requiredAmount) {
            sendErrorResponse("Insufficient credits. Available: $" . number_format($availableCredits, 2) . ", Required: $" . number_format($requiredAmount, 2), 400);
        }

        // Start transaction for credit payment
        $db->beginTransaction();

        try {
            // Deduct credits from user account
            $updateCreditsQuery = "
                UPDATE user_credits
                SET used_credits = used_credits + ?, updated_at = NOW()
                WHERE user_id = ?
            ";
            $updateCreditsStmt = $db->prepare($updateCreditsQuery);
            $updateCreditsStmt->execute([$requiredAmount, $user_id]);

            // Record credit transaction
            $creditTransactionQuery = "
                INSERT INTO credit_transactions (
                    user_id, transaction_type, amount, description,
                    source_type, source_id, investment_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $creditTransactionStmt = $db->prepare($creditTransactionQuery);
            $creditTransactionStmt->execute([
                $user_id,
                'spent',
                $requiredAmount,
                "NFT purchase: " . $input['packageName'],
                'purchase',
                $investment_id,
                $investment_id
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception('Failed to process credit payment: ' . $e->getMessage());
        }
    }

    // Insert investment record with 180-day delivery dates
    $query = "INSERT INTO aureus_investments (
        id,
        user_id,
        name,
        email,
        wallet_address,
        chain,
        amount,
        investment_plan,
        package_name,
        shares,
        roi,
        tx_hash,
        payment_method,
        status,
        nft_delivery_date,
        roi_delivery_date,
        delivery_status,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 180 DAY), DATE_ADD(NOW(), INTERVAL 180 DAY), 'pending', NOW())";

    $stmt = $db->prepare($query);

    $success = $stmt->execute([
        $investment_id,
        $user_id, // Using proper user_id from session or wallet address
        $input['userName'] ?? '',
        $input['userEmail'] ?? '',
        $input['walletAddress'] ?? '',
        $input['chainId'] ?? '',
        $input['amount'],
        strtolower($input['packageName']), // investment_plan
        $input['packageName'],
        $input['shares'],
        $input['roi'],
        $input['txHash'] ?? '',
        $paymentMethod,
        $usingCredits ? 'completed' : 'pending' // Credits are instant, wallet payments are pending
    ]);

    if (!$success) {
        if ($usingCredits) {
            $db->rollBack();
        }
        throw new Exception('Failed to create investment record');
    }

    // Commit credit transaction if using credits
    if ($usingCredits) {
        $db->commit();
    }

    // Create delivery schedule entry for 180-day countdown
    try {
        $scheduleQuery = "INSERT INTO delivery_schedule (
            investment_id,
            user_id,
            package_name,
            investment_amount,
            nft_delivery_date,
            roi_delivery_date
        ) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 180 DAY), DATE_ADD(NOW(), INTERVAL 180 DAY))";

        $scheduleStmt = $db->prepare($scheduleQuery);
        $scheduleStmt->execute([
            $investment_id,
            $user_id, // Using proper user_id
            $input['packageName'],
            $input['amount']
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the investment
        error_log("Failed to create delivery schedule for investment $investment_id: " . $e->getMessage());
    }

    // Process referral commissions if there's a referrer in session
    $commissionResult = null;
    if (isset($_SESSION['referral_data'])) {
        try {
            $referralData = $_SESSION['referral_data'];

            // Include KYC Level Service for dynamic commission rates
            require_once '../services/KYCLevelService.php';
            $kycService = new KYCLevelService($db);

            // Base commission levels (will be multiplied by KYC level multiplier)
            $baseCommissionLevels = [
                1 => ['usdt' => 12, 'nft' => 12],
                2 => ['usdt' => 5, 'nft' => 5],
                3 => ['usdt' => 3, 'nft' => 3]
            ];

            $commissionsCreated = 0;
            $totalUSDT = 0;
            $totalNFT = 0;

            // Build multi-level referral chain and create commissions for all levels
            $referralChain = [];
            $currentReferrer = $referralData['referrer_user_id'];

            // Build the referral chain (up to 3 levels)
            for ($level = 1; $level <= 3; $level++) {
                if (!$currentReferrer) break;

                $referralChain[$level] = $currentReferrer;

                // Get the next level referrer
                $nextReferrerQuery = "SELECT referrer_user_id FROM referral_relationships WHERE referred_user_id = ? LIMIT 1";
                $nextReferrerStmt = $db->prepare($nextReferrerQuery);
                $nextReferrerStmt->execute([$currentReferrer]);
                $nextReferrer = $nextReferrerStmt->fetchColumn();

                $currentReferrer = $nextReferrer;
            }

            // Create commission records for each level in the chain
            foreach ($referralChain as $level => $referrerUserId) {
                // Get the referrer's KYC level and apply multiplier
                $referrerKYCLevel = $kycService->getUserLevel($referrerUserId);
                $kycMultiplier = $kycService->getCommissionRate($referrerUserId) / 0.05; // Base rate is 5%

                $baseRates = $baseCommissionLevels[$level];
                $rates = [
                    'usdt' => $baseRates['usdt'] * $kycMultiplier,
                    'nft' => $baseRates['nft'] * $kycMultiplier
                ];

                $commissionUSDT = (floatval($input['amount']) * $rates['usdt']) / 100;
                $commissionNFT = intval((floatval($input['amount']) * $rates['nft']) / 100 / 5); // $5 per NFT pack

                // Insert commission record
                $commissionQuery = "INSERT INTO referral_commissions (
                    referrer_user_id, referred_user_id, investment_id, level,
                    purchase_amount, commission_usdt, commission_nft, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

                $commissionStmt = $db->prepare($commissionQuery);
                $commissionStmt->execute([
                    $referrerUserId,
                    $user_id,
                    $investment_id,
                    $level,
                    floatval($input['amount']),
                    $commissionUSDT,
                    $commissionNFT
                ]);

                $commissionsCreated++;
                $totalUSDT += $commissionUSDT;
                $totalNFT += $commissionNFT;

                // Update secure balance for referrer (commissions start as earned but not available)
                try {
                    $currentBalance = $securityManager->getSecureUserBalance($referrerUserId);
                    $securityManager->updateUserBalance(
                        $referrerUserId,
                        $currentBalance['total_usdt_earned'] + $commissionUSDT,
                        $currentBalance['total_nft_earned'] + $commissionNFT,
                        $currentBalance['available_usdt_balance'], // Don't add to available yet (pending)
                        $currentBalance['available_nft_balance'], // Don't add to available yet (pending)
                        $currentBalance['total_usdt_withdrawn'],
                        $currentBalance['total_nft_redeemed'],
                        $investment_id,
                        null // No admin ID for commission creation
                    );

                    // Log commission creation
                    $securityManager->logTransaction(
                        $referrerUserId,
                        'commission_earned',
                        $commissionUSDT,
                        $commissionNFT,
                        $currentBalance['available_usdt_balance'],
                        $currentBalance['available_nft_balance'],
                        $currentBalance['available_usdt_balance'], // No change to available yet
                        $currentBalance['available_nft_balance'], // No change to available yet
                        null,
                        null,
                        $investment_id
                    );

                } catch (Exception $e) {
                    error_log("Failed to update secure balance for user $referrerUserId: " . $e->getMessage());
                    // Don't fail the investment if commission update fails
                }
            }

            // Create referral relationship record for the direct referrer
            $relationshipQuery = "INSERT IGNORE INTO referral_relationships (
                referrer_user_id, referred_user_id, referrer_username, referred_username,
                referral_source, ip_address, user_agent, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";

            $relationshipStmt = $db->prepare($relationshipQuery);
            $relationshipStmt->execute([
                $referralData['referrer_user_id'],
                $user_id,
                $referralData['referrer_username'],
                $_SESSION['user_username'] ?? '',
                $referralData['source'] ?? 'direct_link',
                $referralData['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
                $referralData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT']
            ]);

            // Clear referral data after processing
            unset($_SESSION['referral_data']);

            $commissionResult = [
                'processed' => true,
                'commissions_created' => $commissionsCreated,
                'total_usdt' => $totalUSDT,
                'total_nft' => $totalNFT,
                'referrer_username' => $referralData['referrer_username']
            ];

        } catch (Exception $e) {
            // Log commission processing error but don't fail the investment
            error_log("Commission processing error for investment $investment_id: " . $e->getMessage());
            $commissionResult = [
                'processed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Return the created investment with countdown information
    $nftDeliveryDate = date('c', strtotime('+180 days'));
    $roiDeliveryDate = date('c', strtotime('+180 days'));

    $response_data = [
        'id' => $investment_id,
        'packageName' => $input['packageName'],
        'amount' => $input['amount'],
        'shares' => $input['shares'],
        'roi' => $input['roi'],
        'txHash' => $input['txHash'] ?? '',
        'chainId' => $input['chainId'],
        'walletAddress' => $input['walletAddress'],
        'status' => 'pending',
        'createdAt' => date('c'),
        'updatedAt' => date('c'),
        'nftDeliveryDate' => $nftDeliveryDate,
        'roiDeliveryDate' => $roiDeliveryDate,
        'deliveryCountdownDays' => 180,
        'commissions' => $commissionResult
    ];

    sendSuccessResponse($response_data, 'Investment record created successfully');

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
