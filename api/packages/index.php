<?php
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

function sendSuccessResponse($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Tables should already exist - no automatic creation
    // This prevents duplicate plan generation

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Get all investment packages
            $query = "SELECT * FROM investment_packages ORDER BY price ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decode JSON bonuses
            foreach ($packages as &$package) {
                $package['bonuses'] = json_decode($package['bonuses'], true);
            }

            // Filter packages based on user's KYC level if user is authenticated
            session_start();
            if (isset($_SESSION['user_id'])) {
                require_once '../services/KYCLevelService.php';
                $kycService = new KYCLevelService($db);
                $userLimits = $kycService->getInvestmentLimits($_SESSION['user_id']);

                // Filter packages based on KYC level limits
                $filteredPackages = [];
                foreach ($packages as $package) {
                    $package['kyc_accessible'] = $package['price'] >= $userLimits['min'] && $package['price'] <= $userLimits['max'];
                    $package['kyc_level_required'] = $package['price'] > 100 ? ($package['price'] > 500 ? 3 : 2) : 1;
                    $filteredPackages[] = $package;
                }
                $packages = $filteredPackages;
            } else {
                // For non-authenticated users, show all packages but mark accessibility
                foreach ($packages as &$package) {
                    $package['kyc_accessible'] = true; // Show all for non-authenticated
                    $package['kyc_level_required'] = $package['price'] > 100 ? ($package['price'] > 500 ? 3 : 2) : 1;
                }
            }

            sendSuccessResponse($packages, 'Packages retrieved successfully');
            break;

        case 'POST':
            // ADMIN ONLY: Create new package
            session_start();
            if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
                sendErrorResponse('Admin authentication required. Only admins can create investment packages.', 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $required_fields = ['name', 'price', 'shares', 'roi', 'annual_dividends', 'quarter_dividends'];
            foreach ($required_fields as $field) {
                if (!isset($input[$field])) {
                    sendErrorResponse("Field '$field' is required", 400);
                }
            }

            // Check for duplicate plan names
            $duplicateCheckQuery = "SELECT COUNT(*) as count FROM investment_packages WHERE name = ?";
            $duplicateCheckStmt = $db->prepare($duplicateCheckQuery);
            $duplicateCheckStmt->execute([$input['name']]);
            $duplicateCount = $duplicateCheckStmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($duplicateCount > 0) {
                sendErrorResponse("A package with the name '{$input['name']}' already exists. Please choose a different name.", 409);
            }

            $query = "INSERT INTO investment_packages (name, price, shares, roi, annual_dividends, quarter_dividends, icon, icon_color, bonuses) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            $bonuses = isset($input['bonuses']) ? json_encode($input['bonuses']) : '[]';
            $icon = $input['icon'] ?? 'star';
            $icon_color = $input['icon_color'] ?? 'bg-green-500';
            
            $stmt->execute([
                $input['name'],
                $input['price'],
                $input['shares'],
                $input['roi'],
                $input['annual_dividends'],
                $input['quarter_dividends'],
                $icon,
                $icon_color,
                $bonuses
            ]);

            // Log admin action
            $auditQuery = "
                INSERT INTO security_audit_log (
                    event_type, admin_id, event_details, security_level,
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";

            $auditStmt = $db->prepare($auditQuery);
            $auditStmt->execute([
                'admin_action',
                $_SESSION['admin_id'],
                json_encode([
                    'action' => 'create_investment_package',
                    'package_name' => $input['name'],
                    'package_price' => $input['price'],
                    'admin_username' => $_SESSION['admin_username'],
                    'timestamp' => date('c')
                ]),
                'info',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            sendSuccessResponse(['id' => $db->lastInsertId()], 'Package created successfully by admin');
            break;

        case 'PUT':
            // ADMIN ONLY: Update package
            session_start();
            if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
                sendErrorResponse('Admin authentication required. Only admins can update investment packages.', 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['id'])) {
                sendErrorResponse('Package ID is required', 400);
            }

            $fields = [];
            $values = [];
            
            $allowed_fields = ['name', 'price', 'shares', 'roi', 'annual_dividends', 'quarter_dividends', 'icon', 'icon_color'];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (isset($input['bonuses'])) {
                $fields[] = "bonuses = ?";
                $values[] = json_encode($input['bonuses']);
            }
            
            if (empty($fields)) {
                sendErrorResponse('No fields to update', 400);
            }
            
            $values[] = $input['id'];
            
            $query = "UPDATE investment_packages SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute($values);

            sendSuccessResponse(null, 'Package updated successfully');
            break;

        case 'DELETE':
            // ADMIN ONLY: Delete package
            session_start();
            if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
                sendErrorResponse('Admin authentication required. Only admins can delete investment packages.', 401);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['id'])) {
                sendErrorResponse('Package ID is required', 400);
            }

            // Get package details before deletion for audit log
            $packageQuery = "SELECT name, price FROM investment_packages WHERE id = ?";
            $packageStmt = $db->prepare($packageQuery);
            $packageStmt->execute([$input['id']]);
            $packageDetails = $packageStmt->fetch(PDO::FETCH_ASSOC);

            $query = "DELETE FROM investment_packages WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$input['id']]);

            // Log admin action
            if ($packageDetails) {
                $auditQuery = "
                    INSERT INTO security_audit_log (
                        event_type, admin_id, event_details, security_level,
                        ip_address, user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ";

                $auditStmt = $db->prepare($auditQuery);
                $auditStmt->execute([
                    'admin_action',
                    $_SESSION['admin_id'],
                    json_encode([
                        'action' => 'delete_investment_package',
                        'package_name' => $packageDetails['name'],
                        'package_price' => $packageDetails['price'],
                        'admin_username' => $_SESSION['admin_username'],
                        'timestamp' => date('c')
                    ]),
                    'warning',
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            }

            sendSuccessResponse(null, 'Package deleted successfully by admin');
            break;

        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
