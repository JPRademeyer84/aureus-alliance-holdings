<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get query parameters
    $language_id = $_GET['language_id'] ?? null;
    $category = $_GET['category'] ?? null;
    $severity = $_GET['severity'] ?? null;
    $resolved = $_GET['resolved'] ?? 'false'; // Default to unresolved issues
    $limit = (int)($_GET['limit'] ?? 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Build the query
    $whereConditions = [];
    $params = [];
    
    // Base query with joins
    $query = "SELECT 
                ti.id,
                ti.key_id,
                ti.language_id,
                ti.issue_type,
                ti.issue_description,
                ti.severity,
                ti.is_resolved,
                ti.resolved_at,
                ti.resolved_by,
                ti.auto_detected,
                ti.verification_run_id,
                ti.created_at,
                ti.updated_at,
                tk.key_name,
                tk.category,
                tk.description as key_description,
                l.name as language_name,
                l.code as language_code,
                t.translation_text,
                t.is_approved
              FROM translation_issues ti
              JOIN translation_keys tk ON ti.key_id = tk.id
              JOIN languages l ON ti.language_id = l.id
              LEFT JOIN translations t ON ti.key_id = t.key_id AND ti.language_id = t.language_id";
    
    // Add filters
    if ($language_id) {
        $whereConditions[] = "ti.language_id = ?";
        $params[] = $language_id;
    }
    
    if ($category) {
        $whereConditions[] = "tk.category = ?";
        $params[] = $category;
    }
    
    if ($severity) {
        $whereConditions[] = "ti.severity = ?";
        $params[] = $severity;
    }
    
    // Filter by resolved status
    if ($resolved === 'true') {
        $whereConditions[] = "ti.is_resolved = TRUE";
    } else {
        $whereConditions[] = "ti.is_resolved = FALSE";
    }
    
    // Add WHERE clause if we have conditions
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add ordering
    $query .= " ORDER BY 
                CASE ti.severity 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END,
                ti.created_at DESC";
    
    // Add pagination (can't use prepared statements for LIMIT/OFFSET)
    $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total
                   FROM translation_issues ti
                   JOIN translation_keys tk ON ti.key_id = tk.id
                   JOIN languages l ON ti.language_id = l.id";
    
    if (!empty($whereConditions)) {
        $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get summary statistics
    $summaryQuery = "SELECT 
                        ti.severity,
                        COUNT(*) as count
                     FROM translation_issues ti
                     JOIN translation_keys tk ON ti.key_id = tk.id
                     JOIN languages l ON ti.language_id = l.id";
    
    $summaryWhereConditions = array_slice($whereConditions, 0, -1); // Remove resolved filter for summary
    $summaryParams = array_slice($params, 0, count($summaryWhereConditions));

    if (!empty($summaryWhereConditions)) {
        $summaryQuery .= " WHERE " . implode(" AND ", $summaryWhereConditions);
    }

    $summaryQuery .= " GROUP BY ti.severity";

    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute($summaryParams);
    $severitySummary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format severity summary
    $severityStats = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    foreach ($severitySummary as $stat) {
        $severityStats[$stat['severity']] = (int)$stat['count'];
    }
    
    // Get category breakdown
    $categoryQuery = "SELECT 
                        tk.category,
                        COUNT(*) as count
                      FROM translation_issues ti
                      JOIN translation_keys tk ON ti.key_id = tk.id
                      JOIN languages l ON ti.language_id = l.id";
    
    if (!empty($summaryWhereConditions)) {
        $categoryQuery .= " WHERE " . implode(" AND ", $summaryWhereConditions);
    }

    $categoryQuery .= " GROUP BY tk.category ORDER BY count DESC";

    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute($summaryParams);
    $categoryBreakdown = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'issues' => $issues,
        'pagination' => [
            'total' => (int)$totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ],
        'statistics' => [
            'total_issues' => (int)$totalCount,
            'severity_breakdown' => $severityStats,
            'category_breakdown' => $categoryBreakdown
        ],
        'filters_applied' => [
            'language_id' => $language_id,
            'category' => $category,
            'severity' => $severity,
            'resolved' => $resolved === 'true'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
