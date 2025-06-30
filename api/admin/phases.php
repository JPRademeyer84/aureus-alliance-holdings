<?php
/**
 * Phase Management API
 * 
 * Manages the 20-phase system with manual activation controls
 * Handles CRUD operations for phases and phase statistics
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

session_start();

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

function sendErrorResponse($message, $code = 400) {
    sendResponse(false, null, $message, $code);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check admin authentication
    if (!isset($_SESSION['admin_id'])) {
        sendErrorResponse('Admin authentication required', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all phases with statistics
        $phasesQuery = "
            SELECT 
                p.*,
                JSON_EXTRACT(p.revenue_distribution, '$.commission') as commission_percentage,
                JSON_EXTRACT(p.revenue_distribution, '$.competition') as competition_percentage,
                JSON_EXTRACT(p.revenue_distribution, '$.platform') as platform_percentage,
                JSON_EXTRACT(p.revenue_distribution, '$.npo') as npo_percentage,
                JSON_EXTRACT(p.revenue_distribution, '$.mine') as mine_percentage
            FROM phases p 
            ORDER BY p.phase_number ASC
        ";
        
        $phasesStmt = $db->query($phasesQuery);
        $phases = $phasesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate overall statistics
        $statsQuery = "
            SELECT 
                COUNT(*) as total_phases,
                SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_phases,
                SUM(total_revenue) as total_revenue,
                SUM(packages_sold) as total_packages_sold
            FROM phases
        ";
        
        $statsStmt = $db->query($statsQuery);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get current active phase
        $currentPhaseQuery = "SELECT * FROM phases WHERE is_active = TRUE ORDER BY phase_number ASC LIMIT 1";
        $currentPhaseStmt = $db->query($currentPhaseQuery);
        $currentPhase = $currentPhaseStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format phases data
        $formattedPhases = array_map(function($phase) {
            return [
                'id' => (int)$phase['id'],
                'phase_number' => (int)$phase['phase_number'],
                'name' => $phase['name'],
                'description' => $phase['description'],
                'is_active' => (bool)$phase['is_active'],
                'start_date' => $phase['start_date'],
                'end_date' => $phase['end_date'],
                'total_packages_available' => (int)$phase['total_packages_available'],
                'packages_sold' => (int)$phase['packages_sold'],
                'total_revenue' => (float)$phase['total_revenue'],
                'commission_paid' => (float)$phase['commission_paid'],
                'competition_pool' => (float)$phase['competition_pool'],
                'npo_fund' => (float)$phase['nfo_fund'],
                'platform_fund' => (float)$phase['platform_fund'],
                'mine_fund' => (float)$phase['mine_fund'],
                'revenue_distribution' => json_decode($phase['revenue_distribution'], true),
                'created_at' => $phase['created_at'],
                'updated_at' => $phase['updated_at']
            ];
        }, $phases);
        
        $responseData = [
            'phases' => $formattedPhases,
            'stats' => [
                'total_phases' => (int)$stats['total_phases'],
                'active_phases' => (int)$stats['active_phases'],
                'total_revenue' => (float)$stats['total_revenue'],
                'total_packages_sold' => (int)$stats['total_packages_sold'],
                'current_phase' => $currentPhase ? [
                    'id' => (int)$currentPhase['id'],
                    'phase_number' => (int)$currentPhase['phase_number'],
                    'name' => $currentPhase['name'],
                    'description' => $currentPhase['description'],
                    'packages_sold' => (int)$currentPhase['packages_sold'],
                    'total_revenue' => (float)$currentPhase['total_revenue']
                ] : null
            ]
        ];
        
        sendResponse(true, $responseData, 'Phases retrieved successfully');
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action'])) {
            sendErrorResponse('Action is required');
        }
        
        $action = $input['action'];
        
        switch ($action) {
            case 'toggle_status':
                if (!isset($input['phase_id']) || !isset($input['is_active'])) {
                    sendErrorResponse('Phase ID and status are required');
                }
                
                $phaseId = (int)$input['phase_id'];
                $isActive = (bool)$input['is_active'];
                
                // If activating a phase, deactivate all others (only one active at a time)
                if ($isActive) {
                    $deactivateQuery = "UPDATE phases SET is_active = FALSE, end_date = NOW() WHERE is_active = TRUE";
                    $db->exec($deactivateQuery);
                }
                
                // Update the target phase
                $updateQuery = "UPDATE phases SET 
                    is_active = ?, 
                    start_date = CASE WHEN ? = TRUE THEN NOW() ELSE start_date END,
                    end_date = CASE WHEN ? = FALSE THEN NOW() ELSE NULL END,
                    updated_at = NOW()
                    WHERE id = ?";
                
                $updateStmt = $db->prepare($updateQuery);
                $success = $updateStmt->execute([$isActive, $isActive, $isActive, $phaseId]);
                
                if ($success) {
                    sendResponse(true, null, 'Phase status updated successfully');
                } else {
                    sendErrorResponse('Failed to update phase status', 500);
                }
                break;
                
            case 'update':
                if (!isset($input['phase_id'])) {
                    sendErrorResponse('Phase ID is required');
                }
                
                $phaseId = (int)$input['phase_id'];
                $name = $input['name'] ?? '';
                $description = $input['description'] ?? '';
                $totalPackages = (int)($input['total_packages_available'] ?? 0);
                
                $updateQuery = "UPDATE phases SET 
                    name = ?, 
                    description = ?, 
                    total_packages_available = ?,
                    updated_at = NOW()
                    WHERE id = ?";
                
                $updateStmt = $db->prepare($updateQuery);
                $success = $updateStmt->execute([$name, $description, $totalPackages, $phaseId]);
                
                if ($success) {
                    sendResponse(true, null, 'Phase updated successfully');
                } else {
                    sendErrorResponse('Failed to update phase', 500);
                }
                break;
                
            case 'create':
                $phaseNumber = (int)($input['phase_number'] ?? 0);
                $name = $input['name'] ?? '';
                $description = $input['description'] ?? '';
                $totalPackages = (int)($input['total_packages_available'] ?? 0);
                
                // Default revenue distribution
                $revenueDistribution = json_encode([
                    'commission' => 15,
                    'competition' => 15,
                    'platform' => 25,
                    'npo' => 10,
                    'mine' => 35
                ]);
                
                $createQuery = "INSERT INTO phases (
                    phase_number, name, description, total_packages_available, 
                    revenue_distribution, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                
                $createStmt = $db->prepare($createQuery);
                $success = $createStmt->execute([$phaseNumber, $name, $description, $totalPackages, $revenueDistribution]);
                
                if ($success) {
                    sendResponse(true, ['phase_id' => $db->lastInsertId()], 'Phase created successfully');
                } else {
                    sendErrorResponse('Failed to create phase', 500);
                }
                break;
                
            case 'delete':
                if (!isset($input['phase_id'])) {
                    sendErrorResponse('Phase ID is required');
                }
                
                $phaseId = (int)$input['phase_id'];
                
                // Check if phase has any investments
                $checkQuery = "SELECT COUNT(*) as count FROM aureus_investments WHERE phase_id = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$phaseId]);
                $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    sendErrorResponse('Cannot delete phase with existing investments');
                }
                
                $deleteQuery = "DELETE FROM phases WHERE id = ?";
                $deleteStmt = $db->prepare($deleteQuery);
                $success = $deleteStmt->execute([$phaseId]);
                
                if ($success) {
                    sendResponse(true, null, 'Phase deleted successfully');
                } else {
                    sendErrorResponse('Failed to delete phase', 500);
                }
                break;
                
            default:
                sendErrorResponse('Invalid action');
        }
        
    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Phase management error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
