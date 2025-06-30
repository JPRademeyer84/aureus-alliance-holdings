<?php
/**
 * Competition List API
 * 
 * Retrieves all competitions with statistics and phase information
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

    // Get all competitions with phase information
    $competitionsQuery = "
        SELECT 
            c.*,
            p.name as phase_name,
            p.phase_number,
            COUNT(cp.id) as participants_count,
            COALESCE(SUM(cp.total_volume), 0) as total_sales_volume
        FROM competitions c
        LEFT JOIN phases p ON c.phase_id = p.id
        LEFT JOIN competition_participants cp ON c.id = cp.competition_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";
    
    $competitionsStmt = $db->query($competitionsQuery);
    $competitions = $competitionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate overall statistics
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT c.id) as total_competitions,
            SUM(CASE WHEN c.is_active = TRUE THEN 1 ELSE 0 END) as active_competitions,
            SUM(c.prize_pool) as total_prize_pool,
            COUNT(DISTINCT cp.user_id) as total_participants,
            SUM(CASE WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as competitions_this_month
        FROM competitions c
        LEFT JOIN competition_participants cp ON c.id = cp.competition_id
    ";
    
    $statsStmt = $db->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format competitions data
    $formattedCompetitions = array_map(function($competition) {
        return [
            'id' => (int)$competition['id'],
            'phase_id' => (int)$competition['phase_id'],
            'phase_name' => $competition['phase_name'] ?? 'Unknown Phase',
            'name' => $competition['name'],
            'description' => $competition['description'],
            'prize_pool' => (float)$competition['prize_pool'],
            'start_date' => $competition['start_date'],
            'end_date' => $competition['end_date'],
            'is_active' => (bool)$competition['is_active'],
            'winner_selection_criteria' => $competition['winner_selection_criteria'],
            'max_winners' => (int)$competition['max_winners'],
            'prize_distribution' => json_decode($competition['prize_distribution'], true),
            'rules' => $competition['rules'],
            'participants_count' => (int)$competition['participants_count'],
            'total_sales_volume' => (float)$competition['total_sales_volume'],
            'created_at' => $competition['created_at'],
            'updated_at' => $competition['updated_at']
        ];
    }, $competitions);
    
    $responseData = [
        'competitions' => $formattedCompetitions,
        'stats' => [
            'total_competitions' => (int)$stats['total_competitions'],
            'active_competitions' => (int)$stats['active_competitions'],
            'total_prize_pool' => (float)$stats['total_prize_pool'],
            'total_participants' => (int)$stats['total_participants'],
            'competitions_this_month' => (int)$stats['competitions_this_month']
        ]
    ];
    
    sendResponse(true, $responseData, 'Competitions retrieved successfully');

} catch (Exception $e) {
    error_log("Competition list error: " . $e->getMessage());
    sendResponse(false, null, 'Internal server error: ' . $e->getMessage(), 500);
}
?>
