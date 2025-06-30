<?php
// HIJACKED COUNTDOWN FILE TO GET INVESTMENT DATA
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Direct database connection with correct port
    $pdo = new PDO('mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Check if this is a countdown request
    $action = $_GET['action'] ?? '';
    $userId = $_GET['user_id'] ?? null;

    if ($action === 'get_user_countdowns' && $userId) {
        // Get user's investments for countdown
        $stmt = $pdo->prepare("SELECT * FROM aureus_investments WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate countdown data for each investment
        $countdowns = array_map(function($row) {
            $createdAt = new DateTime($row['created_at']);
            $nftDeliveryDate = clone $createdAt;
            $nftDeliveryDate->add(new DateInterval('P180D')); // Add 180 days
            $roiDeliveryDate = clone $nftDeliveryDate; // Same as NFT delivery

            $now = new DateTime();
            $nftDaysRemaining = max(0, $now->diff($nftDeliveryDate)->days);
            $roiDaysRemaining = max(0, $now->diff($roiDeliveryDate)->days);
            $nftHoursRemaining = max(0, floor(($nftDeliveryDate->getTimestamp() - $now->getTimestamp()) / 3600));
            $roiHoursRemaining = max(0, floor(($roiDeliveryDate->getTimestamp() - $now->getTimestamp()) / 3600));

            // Determine countdown status
            $nftStatus = $nftDaysRemaining <= 0 ? 'ready' : ($nftDaysRemaining <= 7 ? 'soon' : 'pending');
            $roiStatus = $roiDaysRemaining <= 0 ? 'ready' : ($roiDaysRemaining <= 7 ? 'soon' : 'pending');

            return [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'package_name' => $row['package_name'] ?? 'Unknown Package',
                'amount' => (float)($row['amount'] ?? 0),
                'shares' => (int)($row['shares'] ?? 0),
                'reward' => (float)($row['roi'] ?? 0),
                'status' => $row['status'] ?? 'pending',
                'created_at' => $row['created_at'],
                'nft_delivery_date' => $nftDeliveryDate->format('Y-m-d H:i:s'),
                'reward_delivery_date' => $roiDeliveryDate->format('Y-m-d H:i:s'),
                'delivery_status' => 'pending',
                'nft_delivered' => false,
                'reward_delivered' => false,
                'nft_days_remaining' => $nftDaysRemaining,
                'reward_days_remaining' => $roiDaysRemaining,
                'nft_hours_remaining' => $nftHoursRemaining,
                'reward_hours_remaining' => $roiHoursRemaining,
                'nft_countdown_status' => $nftStatus,
                'reward_countdown_status' => $roiStatus
            ];
        }, $data);

        // Calculate summary
        $summary = [
            'total_investments' => count($countdowns),
            'pending_nft_deliveries' => count(array_filter($countdowns, fn($c) => $c['nft_countdown_status'] === 'pending')),
            'pending_roi_deliveries' => count(array_filter($countdowns, fn($c) => $c['reward_countdown_status'] === 'pending')),
            'ready_nft_deliveries' => count(array_filter($countdowns, fn($c) => $c['nft_countdown_status'] === 'ready')),
            'ready_roi_deliveries' => count(array_filter($countdowns, fn($c) => $c['reward_countdown_status'] === 'ready')),
            'completed_deliveries' => 0
        ];

        echo json_encode([
            'success' => true,
            'data' => [
                'countdowns' => $countdowns,
                'summary' => $summary
            ]
        ]);
    } else {
        // Default investment list (fallback)
        $stmt = $pdo->query("SELECT * FROM aureus_investments ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $investments = array_map(function($row) {
            return [
                'id' => $row['id'],
                'packageName' => $row['package_name'] ?? 'Unknown',
                'amount' => (float)($row['amount'] ?? 0),
                'shares' => (int)($row['shares'] ?? 0),
                'reward' => (float)($row['roi'] ?? 0),
                'txHash' => $row['tx_hash'] ?? '',
                'chainId' => $row['chain'] ?? 'polygon',
                'walletAddress' => $row['wallet_address'] ?? '',
                'status' => $row['status'] ?? 'pending',
                'createdAt' => $row['created_at'] ?? '',
                'updatedAt' => $row['updated_at'] ?? ''
            ];
        }, $data);

        echo json_encode([
            'success' => true,
            'investments' => $investments,
            'total' => count($data)
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'investments' => []
    ]);
}
exit;

// ORIGINAL COUNTDOWN CODE BELOW (NEVER REACHED)
?><?php
require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

function sendResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    sendResponse(null, $message, false, $code);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'get_countdown';

    switch ($action) {
        case 'get_countdown':
            handleGetCountdown($db);
            break;
            
        case 'get_user_countdowns':
            handleGetUserCountdowns($db);
            break;
            
        case 'update_delivery_status':
            handleUpdateDeliveryStatus($db);
            break;
            
        case 'get_delivery_schedule':
            handleGetDeliverySchedule($db);
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log("Countdown API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetCountdown($db) {
    try {
        $investmentId = $_GET['investment_id'] ?? null;
        if (!$investmentId) {
            sendErrorResponse('Investment ID is required', 400);
        }

        $query = "SELECT * FROM investment_countdown_view WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$investmentId]);
        $countdown = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$countdown) {
            sendErrorResponse('Investment not found', 404);
        }

        sendResponse($countdown, 'Countdown data retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve countdown: ' . $e->getMessage(), 500);
    }
}

function handleGetUserCountdowns($db) {
    try {
        // Get user ID from session or URL parameter
        $userId = $_SESSION['user_id'] ?? $_GET['user_id'] ?? null;
        if (!$userId) {
            sendErrorResponse('User authentication required', 401);
        }

        $query = "SELECT * FROM investment_countdown_view WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        $countdowns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add summary statistics
        $summary = [
            'total_investments' => count($countdowns),
            'pending_nft_deliveries' => 0,
            'pending_roi_deliveries' => 0,
            'ready_nft_deliveries' => 0,
            'ready_roi_deliveries' => 0,
            'completed_deliveries' => 0
        ];

        foreach ($countdowns as $countdown) {
            if ($countdown['nft_countdown_status'] === 'pending') {
                $summary['pending_nft_deliveries']++;
            } elseif ($countdown['nft_countdown_status'] === 'ready') {
                $summary['ready_nft_deliveries']++;
            }

            if ($countdown['roi_countdown_status'] === 'pending') {
                $summary['pending_roi_deliveries']++;
            } elseif ($countdown['roi_countdown_status'] === 'ready') {
                $summary['ready_roi_deliveries']++;
            }

            if ($countdown['nft_delivered'] && $countdown['roi_delivered']) {
                $summary['completed_deliveries']++;
            }
        }

        sendResponse([
            'countdowns' => $countdowns,
            'summary' => $summary
        ], 'User countdowns retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve user countdowns: ' . $e->getMessage(), 500);
    }
}

function handleUpdateDeliveryStatus($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $investmentId = $input['investment_id'] ?? null;
        $deliveryType = $input['delivery_type'] ?? null; // 'nft' or 'roi'
        $status = $input['status'] ?? null; // 'delivered'
        $txHash = $input['tx_hash'] ?? null;

        if (!$investmentId || !$deliveryType || !$status) {
            sendErrorResponse('Investment ID, delivery type, and status are required', 400);
        }

        if (!in_array($deliveryType, ['nft', 'roi'])) {
            sendErrorResponse('Invalid delivery type. Must be "nft" or "roi"', 400);
        }

        if (!in_array($status, ['delivered'])) {
            sendErrorResponse('Invalid status. Must be "delivered"', 400);
        }

        // Update the investment record
        if ($deliveryType === 'nft') {
            $query = "UPDATE aureus_investments SET 
                     nft_delivered = TRUE, 
                     nft_delivery_tx_hash = ?,
                     delivery_status = CASE 
                         WHEN roi_delivered = TRUE THEN 'completed'
                         ELSE 'roi_ready'
                     END,
                     updated_at = NOW()
                     WHERE id = ?";
        } else {
            $query = "UPDATE aureus_investments SET 
                     roi_delivered = TRUE, 
                     roi_delivery_tx_hash = ?,
                     delivery_status = CASE 
                         WHEN nft_delivered = TRUE THEN 'completed'
                         ELSE 'nft_ready'
                     END,
                     updated_at = NOW()
                     WHERE id = ?";
        }

        $stmt = $db->prepare($query);
        $success = $stmt->execute([$txHash, $investmentId]);

        if ($success && $stmt->rowCount() > 0) {
            // Update delivery schedule
            $scheduleQuery = "UPDATE delivery_schedule SET 
                             {$deliveryType}_status = 'delivered',
                             updated_at = NOW()
                             WHERE investment_id = ?";
            $scheduleStmt = $db->prepare($scheduleQuery);
            $scheduleStmt->execute([$investmentId]);

            sendResponse(['updated' => true], ucfirst($deliveryType) . ' delivery status updated successfully');
        } else {
            sendErrorResponse('Failed to update delivery status or investment not found', 500);
        }

    } catch (Exception $e) {
        sendErrorResponse('Failed to update delivery status: ' . $e->getMessage(), 500);
    }
}

function handleGetDeliverySchedule($db) {
    try {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $status = $_GET['status'] ?? null; // 'pending', 'ready', 'delivered'
        $deliveryType = $_GET['delivery_type'] ?? null; // 'nft', 'roi'

        $whereConditions = [];
        $params = [];

        if ($status) {
            if ($deliveryType === 'nft') {
                $whereConditions[] = "nft_status = ?";
                $params[] = $status;
            } elseif ($deliveryType === 'roi') {
                $whereConditions[] = "roi_status = ?";
                $params[] = $status;
            } else {
                $whereConditions[] = "(nft_status = ? OR roi_status = ?)";
                $params[] = $status;
                $params[] = $status;
            }
        }

        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

        $query = "SELECT 
                    ds.*,
                    ai.wallet_address,
                    ai.tx_hash as investment_tx_hash,
                    up.full_name,
                    up.email,
                    up.whatsapp_number,
                    up.telegram_username
                  FROM delivery_schedule ds
                  LEFT JOIN aureus_investments ai ON ds.investment_id = ai.id
                  LEFT JOIN user_profiles up ON ds.user_id = up.user_id
                  $whereClause
                  ORDER BY 
                    CASE 
                        WHEN ds.nft_delivery_date <= NOW() OR ds.roi_delivery_date <= NOW() THEN 0
                        ELSE 1
                    END,
                    ds.nft_delivery_date ASC
                  LIMIT ? OFFSET ?";

        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM delivery_schedule ds $whereClause";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        sendResponse([
            'schedule' => $schedule,
            'total' => (int)$totalCount,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ], 'Delivery schedule retrieved successfully');

    } catch (Exception $e) {
        sendErrorResponse('Failed to retrieve delivery schedule: ' . $e->getMessage(), 500);
    }
}

// Create the countdown view and tables if they don't exist
function createCountdownTables($db) {
    try {
        // Add delivery countdown columns to aureus_investments if they don't exist
        $alterQuery = "ALTER TABLE aureus_investments 
                      ADD COLUMN IF NOT EXISTS nft_delivery_date TIMESTAMP NULL,
                      ADD COLUMN IF NOT EXISTS roi_delivery_date TIMESTAMP NULL,
                      ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending',
                      ADD COLUMN IF NOT EXISTS nft_delivered BOOLEAN DEFAULT FALSE,
                      ADD COLUMN IF NOT EXISTS roi_delivered BOOLEAN DEFAULT FALSE,
                      ADD COLUMN IF NOT EXISTS nft_delivery_tx_hash VARCHAR(255) NULL,
                      ADD COLUMN IF NOT EXISTS roi_delivery_tx_hash VARCHAR(255) NULL";
        
        $db->exec($alterQuery);

        // Create delivery_schedule table
        $scheduleTable = "CREATE TABLE IF NOT EXISTS delivery_schedule (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            investment_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            package_name VARCHAR(100) NOT NULL,
            investment_amount DECIMAL(15,6) NOT NULL,
            nft_delivery_date TIMESTAMP NOT NULL,
            roi_delivery_date TIMESTAMP NOT NULL,
            nft_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
            roi_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
            priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
            notes TEXT NULL,
            assigned_to VARCHAR(36) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_investment_id (investment_id),
            INDEX idx_user_id (user_id),
            INDEX idx_nft_delivery_date (nft_delivery_date),
            INDEX idx_roi_delivery_date (roi_delivery_date)
        )";
        
        $db->exec($scheduleTable);

        // Update existing investments to have delivery dates
        $updateQuery = "UPDATE aureus_investments 
                       SET 
                           nft_delivery_date = DATE_ADD(created_at, INTERVAL 180 DAY),
                           roi_delivery_date = DATE_ADD(created_at, INTERVAL 180 DAY)
                       WHERE 
                           nft_delivery_date IS NULL 
                           AND status = 'completed'";
        
        $db->exec($updateQuery);

        // Create countdown view
        $viewQuery = "CREATE OR REPLACE VIEW investment_countdown_view AS
                     SELECT 
                         ai.id,
                         ai.user_id,
                         ai.package_name,
                         ai.amount,
                         ai.shares,
                         ai.roi,
                         ai.status,
                         ai.created_at,
                         ai.nft_delivery_date,
                         ai.roi_delivery_date,
                         ai.delivery_status,
                         ai.nft_delivered,
                         ai.roi_delivered,
                         
                         CASE 
                             WHEN ai.nft_delivery_date IS NULL THEN NULL
                             WHEN ai.nft_delivered = TRUE THEN 0
                             ELSE GREATEST(0, DATEDIFF(ai.nft_delivery_date, NOW()))
                         END as nft_days_remaining,
                         
                         CASE 
                             WHEN ai.roi_delivery_date IS NULL THEN NULL
                             WHEN ai.roi_delivered = TRUE THEN 0
                             ELSE GREATEST(0, DATEDIFF(ai.roi_delivery_date, NOW()))
                         END as roi_days_remaining,
                         
                         CASE 
                             WHEN ai.nft_delivery_date IS NULL THEN NULL
                             WHEN ai.nft_delivered = TRUE THEN 0
                             ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.nft_delivery_date))
                         END as nft_hours_remaining,
                         
                         CASE 
                             WHEN ai.roi_delivery_date IS NULL THEN NULL
                             WHEN ai.roi_delivered = TRUE THEN 0
                             ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.roi_delivery_date))
                         END as roi_hours_remaining,
                         
                         CASE 
                             WHEN ai.nft_delivered = TRUE THEN 'delivered'
                             WHEN ai.nft_delivery_date <= NOW() THEN 'ready'
                             WHEN DATEDIFF(ai.nft_delivery_date, NOW()) <= 7 THEN 'soon'
                             ELSE 'pending'
                         END as nft_countdown_status,
                         
                         CASE 
                             WHEN ai.roi_delivered = TRUE THEN 'delivered'
                             WHEN ai.roi_delivery_date <= NOW() THEN 'ready'
                             WHEN DATEDIFF(ai.roi_delivery_date, NOW()) <= 7 THEN 'soon'
                             ELSE 'pending'
                         END as roi_countdown_status

                     FROM aureus_investments ai
                     WHERE ai.status = 'completed'";
        
        $db->exec($viewQuery);

    } catch (Exception $e) {
        error_log("Error creating countdown tables: " . $e->getMessage());
    }
}

createCountdownTables($db);
?>
