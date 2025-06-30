<?php
/**
 * Competition Creation API
 * 
 * Creates new competitions for phases with 15% prize allocation
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['phase_id', 'name', 'description', 'start_date', 'end_date'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendErrorResponse("Field '$field' is required");
        }
    }

    $phaseId = (int)$input['phase_id'];
    $name = $input['name'];
    $description = $input['description'];
    $startDate = $input['start_date'];
    $endDate = $input['end_date'];
    $winnerCriteria = $input['winner_selection_criteria'] ?? 'sales_volume';
    $maxWinners = (int)($input['max_winners'] ?? 10);
    $rules = $input['rules'] ?? '';

    // Get phase information to calculate prize pool (15% of phase revenue)
    $phaseQuery = "SELECT * FROM phases WHERE id = ?";
    $phaseStmt = $db->prepare($phaseQuery);
    $phaseStmt->execute([$phaseId]);
    $phase = $phaseStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$phase) {
        sendErrorResponse('Phase not found', 404);
    }

    // Calculate prize pool from phase competition fund (15% allocation)
    $prizePool = (float)$phase['competition_pool'];
    
    // Default prize distribution
    $prizeDistribution = json_encode([
        'first' => 50,    // 50% to 1st place
        'second' => 30,   // 30% to 2nd place
        'third' => 15,    // 15% to 3rd place
        'participation' => 5  // 5% distributed among participants
    ]);

    // Create competition
    $createQuery = "INSERT INTO competitions (
        phase_id, name, description, prize_pool, start_date, end_date,
        is_active, winner_selection_criteria, max_winners, prize_distribution,
        rules, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, TRUE, ?, ?, ?, ?, NOW(), NOW())";
    
    $createStmt = $db->prepare($createQuery);
    $success = $createStmt->execute([
        $phaseId, $name, $description, $prizePool, $startDate, $endDate,
        $winnerCriteria, $maxWinners, $prizeDistribution, $rules
    ]);
    
    if (!$success) {
        sendErrorResponse('Failed to create competition', 500);
    }

    $competitionId = $db->lastInsertId();

    // Auto-enroll existing users who made investments in this phase
    $enrollQuery = "
        INSERT INTO competition_participants (competition_id, user_id, sales_count, total_volume, joined_at)
        SELECT ?, ai.user_id, COUNT(*), SUM(ai.amount), NOW()
        FROM aureus_investments ai
        WHERE ai.phase_id = ?
        GROUP BY ai.user_id
        ON DUPLICATE KEY UPDATE
        sales_count = VALUES(sales_count),
        total_volume = VALUES(total_volume)
    ";
    
    $enrollStmt = $db->prepare($enrollQuery);
    $enrollStmt->execute([$competitionId, $phaseId]);

    // Update rankings
    updateCompetitionRankings($db, $competitionId, $winnerCriteria);

    $responseData = [
        'competition_id' => $competitionId,
        'phase_id' => $phaseId,
        'name' => $name,
        'prize_pool' => $prizePool,
        'participants_enrolled' => $enrollStmt->rowCount()
    ];

    sendResponse(true, $responseData, 'Competition created successfully');

} catch (Exception $e) {
    error_log("Competition creation error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function updateCompetitionRankings($db, $competitionId, $criteria) {
    // Determine ranking field
    $rankingField = 'total_volume'; // default
    switch ($criteria) {
        case 'sales_count':
            $rankingField = 'sales_count';
            break;
        case 'referrals':
            $rankingField = 'referrals_count';
            break;
        case 'sales_volume':
        default:
            $rankingField = 'total_volume';
            break;
    }

    // Update rankings
    $rankingQuery = "
        UPDATE competition_participants cp1
        SET current_rank = (
            SELECT COUNT(*) + 1
            FROM competition_participants cp2
            WHERE cp2.competition_id = cp1.competition_id
            AND cp2.$rankingField > cp1.$rankingField
        )
        WHERE cp1.competition_id = ?
    ";
    
    $rankingStmt = $db->prepare($rankingQuery);
    $rankingStmt->execute([$competitionId]);
}
?>
