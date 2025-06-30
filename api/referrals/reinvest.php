<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../security/commission-security.php';
session_start();

try {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'User authentication required'
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Initialize security manager
    $securityManager = new CommissionSecurityManager($db);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    $reinvestAmount = floatval($input['amount'] ?? 0);
    $reinvestType = $input['type'] ?? 'usdt'; // usdt or nft
    $nftQuantity = intval($input['nft_quantity'] ?? 0);
    
    if ($reinvestAmount <= 0 && $nftQuantity <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid reinvestment amount'
        ]);
        exit;
    }
    
    // Create tables if they don't exist
    $db->exec("CREATE TABLE IF NOT EXISTS user_commission_balances (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id INT NOT NULL,
        total_usdt_earned DECIMAL(15, 6) DEFAULT 0.00,
        total_nft_earned INT DEFAULT 0,
        available_usdt_balance DECIMAL(15, 6) DEFAULT 0.00,
        available_nft_balance INT DEFAULT 0,
        total_usdt_withdrawn DECIMAL(15, 6) DEFAULT 0.00,
        total_nft_redeemed INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (user_id),
        INDEX idx_user_id (user_id)
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS commission_reinvestments (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        user_id INT NOT NULL,
        reinvestment_type ENUM('usdt_to_nft', 'nft_to_shares') NOT NULL,
        usdt_amount DECIMAL(15, 6) DEFAULT 0.00,
        nft_quantity INT DEFAULT 0,
        shares_purchased INT DEFAULT 0,
        nft_packs_purchased INT DEFAULT 0,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    )");
    
    // Get secure user balance
    try {
        $balance = $securityManager->getSecureUserBalance($userId);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Balance verification failed: ' . $e->getMessage()
        ]);
        exit;
    }

    if ($balance['available_usdt_balance'] <= 0 && $balance['available_nft_balance'] <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'No available commission balance for reinvestment'
        ]);
        exit;
    }
    
    $db->beginTransaction();
    
    try {
        if ($reinvestType === 'usdt') {
            // Reinvest USDT to buy NFT packs
            if ($reinvestAmount > $balance['available_usdt_balance']) {
                throw new Exception('Insufficient USDT balance for reinvestment');
            }
            
            $nftPackPrice = 5.00; // $5 per NFT pack
            $nftPacksToBuy = floor($reinvestAmount / $nftPackPrice);
            $actualUsdtUsed = $nftPacksToBuy * $nftPackPrice;
            
            if ($nftPacksToBuy <= 0) {
                throw new Exception('Insufficient amount to purchase NFT packs (minimum $5)');
            }
            
            // Create reinvestment record
            $reinvestQuery = "INSERT INTO commission_reinvestments (
                user_id, reinvestment_type, usdt_amount, nft_packs_purchased, status
            ) VALUES (?, 'usdt_to_nft', ?, ?, 'completed')";
            
            $reinvestStmt = $db->prepare($reinvestQuery);
            $reinvestStmt->execute([$userId, $actualUsdtUsed, $nftPacksToBuy]);

            // Create investment record for the NFT purchase
            $investmentId = uniqid('reinvest_', true);

            // Update secure balance - deduct USDT, add NFT packs
            $securityManager->updateUserBalance(
                $userId,
                $balance['total_usdt_earned'], // No change to total earned
                $balance['total_nft_earned'] + $nftPacksToBuy, // Add NFT packs to total earned
                $balance['available_usdt_balance'] - $actualUsdtUsed, // Deduct USDT from available
                $balance['available_nft_balance'] + $nftPacksToBuy, // Add NFT packs to available
                $balance['total_usdt_withdrawn'], // No change to withdrawn
                $balance['total_nft_redeemed'], // No change to redeemed
                $investmentId, // Transaction reference
                null // No admin ID for user action
            );
            $investmentQuery = "INSERT INTO aureus_investments (
                id, user_id, wallet_address, amount, investment_plan, package_name,
                shares, roi, status, nft_delivery_date, roi_delivery_date,
                delivery_status, created_at
            ) VALUES (?, ?, 'commission_reinvestment', ?, 'nft_reinvestment', 'NFT Pack Reinvestment',
                     ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 180 DAY), DATE_ADD(NOW(), INTERVAL 180 DAY),
                     'pending', NOW())";
            
            $sharesPerNFT = 10; // Assuming 10 shares per NFT pack
            $roiPerNFT = 10; // Assuming $10 ROI per NFT pack
            $totalShares = $nftPacksToBuy * $sharesPerNFT;
            $totalROI = $nftPacksToBuy * $roiPerNFT;
            
            $investmentStmt = $db->prepare($investmentQuery);
            $investmentStmt->execute([
                $investmentId,
                $userId,
                $actualUsdtUsed,
                $totalShares,
                $totalROI
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Successfully reinvested $actualUsdtUsed USDT into $nftPacksToBuy NFT packs",
                'reinvestment' => [
                    'type' => 'usdt_to_nft',
                    'usdt_used' => $actualUsdtUsed,
                    'nft_packs_purchased' => $nftPacksToBuy,
                    'shares_gained' => $totalShares,
                    'roi_potential' => $totalROI,
                    'investment_id' => $investmentId
                ]
            ]);
            
        } elseif ($reinvestType === 'nft') {
            // Convert NFT packs to additional shares
            if ($nftQuantity > $balance['available_nft_balance']) {
                throw new Exception('Insufficient NFT balance for reinvestment');
            }
            
            $sharesPerNFT = 10; // 10 shares per NFT pack
            $additionalShares = $nftQuantity * $sharesPerNFT;
            $equivalentValue = $nftQuantity * 5; // $5 per NFT pack
            
            // Create reinvestment record
            $reinvestQuery = "INSERT INTO commission_reinvestments (
                user_id, reinvestment_type, nft_quantity, shares_purchased, status
            ) VALUES (?, 'nft_to_shares', ?, ?, 'completed')";
            
            $reinvestStmt = $db->prepare($reinvestQuery);
            $reinvestStmt->execute([$userId, $nftQuantity, $additionalShares]);
            
            // Create investment record for the share purchase
            $investmentId = uniqid('nft_reinvest_', true);

            // Update secure balance - deduct NFT packs
            $securityManager->updateUserBalance(
                $userId,
                $balance['total_usdt_earned'], // No change to total earned
                $balance['total_nft_earned'], // No change to total NFT earned
                $balance['available_usdt_balance'], // No change to available USDT
                $balance['available_nft_balance'] - $nftQuantity, // Deduct NFT packs from available
                $balance['total_usdt_withdrawn'], // No change to withdrawn
                $balance['total_nft_redeemed'] + $nftQuantity, // Add to redeemed NFTs
                $investmentId, // Transaction reference
                null // No admin ID for user action
            );
            

            $investmentQuery = "INSERT INTO aureus_investments (
                id, user_id, wallet_address, amount, investment_plan, package_name,
                shares, roi, status, nft_delivery_date, roi_delivery_date,
                delivery_status, created_at
            ) VALUES (?, ?, 'nft_commission_reinvestment', ?, 'share_reinvestment', 'Share Reinvestment',
                     ?, ?, 'completed', NOW(), NOW(), 'completed', NOW())";
            
            $roiPerShare = 1; // $1 ROI per share
            $totalROI = $additionalShares * $roiPerShare;
            
            $investmentStmt = $db->prepare($investmentQuery);
            $investmentStmt->execute([
                $investmentId,
                $userId,
                $equivalentValue,
                $additionalShares,
                $totalROI
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Successfully converted $nftQuantity NFT packs into $additionalShares additional shares",
                'reinvestment' => [
                    'type' => 'nft_to_shares',
                    'nft_packs_used' => $nftQuantity,
                    'shares_gained' => $additionalShares,
                    'roi_potential' => $totalROI,
                    'investment_id' => $investmentId
                ]
            ]);
            
        } else {
            throw new Exception('Invalid reinvestment type');
        }
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Commission reinvestment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
