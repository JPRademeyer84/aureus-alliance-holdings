<?php
/**
 * NPO Fund Balance API
 * 
 * Manages NPO fund tracking with 10% allocation from each sale
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

    // Get all NPO fund entries
    $fundQuery = "
        SELECT 
            nf.*,
            ai.package_name,
            ai.amount as investment_amount,
            p.name as phase_name
        FROM npo_fund nf
        LEFT JOIN aureus_investments ai ON nf.source_investment_id = ai.id
        LEFT JOIN phases p ON nf.phase_id = p.id
        ORDER BY nf.created_at DESC
    ";
    
    $fundStmt = $db->query($fundQuery);
    $fundEntries = $fundStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate fund statistics
    $statsQuery = "
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_allocations,
            SUM(CASE WHEN status = 'distributed' THEN amount ELSE 0 END) as distributed_amount,
            SUM(amount) as total_fund_balance,
            COUNT(*) as total_donations,
            COUNT(DISTINCT npo_recipient) as beneficiary_count,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN amount ELSE 0 END) as this_month_contributions
        FROM npo_fund
    ";
    
    $statsStmt = $db->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get NPO recipients (mock data for now - would come from a recipients table)
    $recipients = [
        [
            'id' => 1,
            'name' => 'World Wildlife Fund',
            'description' => 'Global conservation organization',
            'website' => 'https://www.worldwildlife.org',
            'contact_email' => 'contact@wwf.org',
            'total_received' => 15000.00,
            'last_donation_date' => '2024-01-15',
            'is_active' => true
        ],
        [
            'id' => 2,
            'name' => 'Doctors Without Borders',
            'description' => 'International medical humanitarian organization',
            'website' => 'https://www.doctorswithoutborders.org',
            'contact_email' => 'info@msf.org',
            'total_received' => 22500.00,
            'last_donation_date' => '2024-01-20',
            'is_active' => true
        ],
        [
            'id' => 3,
            'name' => 'Clean Water Initiative',
            'description' => 'Providing clean water access worldwide',
            'website' => 'https://www.cleanwater.org',
            'contact_email' => 'help@cleanwater.org',
            'total_received' => 8750.00,
            'last_donation_date' => '2024-01-10',
            'is_active' => true
        ],
        [
            'id' => 4,
            'name' => 'Education for All',
            'description' => 'Global education access initiative',
            'website' => 'https://www.educationforall.org',
            'contact_email' => 'contact@educationforall.org',
            'total_received' => 12300.00,
            'last_donation_date' => '2024-01-25',
            'is_active' => true
        ],
        [
            'id' => 5,
            'name' => 'Hunger Relief Network',
            'description' => 'Fighting global hunger and malnutrition',
            'website' => 'https://www.hungerrelief.org',
            'contact_email' => 'info@hungerrelief.org',
            'total_received' => 18900.00,
            'last_donation_date' => '2024-01-18',
            'is_active' => true
        ]
    ];
    
    // Format fund entries
    $formattedEntries = array_map(function($entry) {
        return [
            'id' => (int)$entry['id'],
            'transaction_id' => $entry['transaction_id'],
            'source_investment_id' => (int)$entry['source_investment_id'],
            'phase_id' => (int)$entry['phase_id'],
            'amount' => (float)$entry['amount'],
            'percentage' => (float)$entry['percentage'],
            'status' => $entry['status'],
            'npo_recipient' => $entry['npo_recipient'],
            'distribution_date' => $entry['distribution_date'],
            'notes' => $entry['notes'],
            'created_at' => $entry['created_at'],
            'updated_at' => $entry['updated_at'],
            'package_name' => $entry['package_name'],
            'investment_amount' => (float)($entry['investment_amount'] ?? 0),
            'phase_name' => $entry['phase_name']
        ];
    }, $fundEntries);
    
    $responseData = [
        'fund_entries' => $formattedEntries,
        'stats' => [
            'total_fund_balance' => (float)$stats['total_fund_balance'],
            'pending_allocations' => (float)$stats['pending_allocations'],
            'distributed_amount' => (float)$stats['distributed_amount'],
            'total_donations' => (int)$stats['total_donations'],
            'beneficiary_count' => count($recipients), // Use actual count from recipients
            'this_month_contributions' => (float)$stats['this_month_contributions']
        ],
        'recipients' => $recipients
    ];
    
    sendResponse(true, $responseData, 'NPO fund data retrieved successfully');

} catch (Exception $e) {
    error_log("NPO fund balance error: " . $e->getMessage());
    sendResponse(false, null, 'Internal server error: ' . $e->getMessage(), 500);
}
?>
