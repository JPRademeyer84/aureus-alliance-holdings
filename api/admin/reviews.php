<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        $rating_filter = $_GET['rating'] ?? null;
        $sort_by = $_GET['sort_by'] ?? 'rated_at';
        $sort_order = $_GET['sort_order'] ?? 'DESC';
        
        // Validate sort parameters
        $allowed_sort_fields = ['rated_at', 'rating', 'created_at'];
        $allowed_sort_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'rated_at';
        }
        
        if (!in_array($sort_order, $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        // Build WHERE clause
        $whereClause = 'WHERE cs.rating IS NOT NULL';
        $params = [];
        
        if ($rating_filter && in_array($rating_filter, ['1', '2', '3', '4', '5'])) {
            $whereClause .= ' AND cs.rating = ?';
            $params[] = intval($rating_filter);
        }
        
        // Get reviews with session and user information
        $query = "SELECT cs.id,
                         cs.rating,
                         cs.feedback,
                         cs.rated_at,
                         cs.created_at,
                         cs.status,
                         COALESCE(u.username, cs.guest_name) as user_name,
                         COALESCE(u.email, cs.guest_email) as user_email,
                         CASE WHEN cs.user_id IS NOT NULL THEN 'user' ELSE 'guest' END as user_type,
                         a.username as admin_username,
                         a.full_name as admin_full_name
                  FROM chat_sessions cs
                  LEFT JOIN users u ON cs.user_id = u.id
                  LEFT JOIN admin_users a ON cs.admin_id = a.id
                  $whereClause
                  ORDER BY cs.$sort_by $sort_order
                  LIMIT $limit OFFSET $offset";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) FROM chat_sessions cs $whereClause";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();
        
        // Get rating statistics
        $statsQuery = "SELECT 
                          COUNT(*) as total_reviews,
                          AVG(rating) as average_rating,
                          COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                          COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                          COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                          COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                          COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
                       FROM chat_sessions 
                       WHERE rating IS NOT NULL";
        
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Format the reviews
        $formattedReviews = array_map(function($review) {
            return [
                'id' => $review['id'],
                'rating' => intval($review['rating']),
                'feedback' => $review['feedback'],
                'rated_at' => $review['rated_at'],
                'created_at' => $review['created_at'],
                'status' => $review['status'],
                'user_name' => $review['user_name'],
                'user_email' => $review['user_email'],
                'user_type' => $review['user_type'],
                'admin_username' => $review['admin_username'],
                'admin_full_name' => $review['admin_full_name'],
                'session_duration' => $review['rated_at'] ? 
                    (strtotime($review['rated_at']) - strtotime($review['created_at'])) : null
            ];
        }, $reviews);
        
        sendSuccessResponse([
            'reviews' => $formattedReviews,
            'pagination' => [
                'total' => intval($totalCount),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'statistics' => [
                'total_reviews' => intval($stats['total_reviews']),
                'average_rating' => round(floatval($stats['average_rating']), 2),
                'rating_breakdown' => [
                    '5' => intval($stats['five_star']),
                    '4' => intval($stats['four_star']),
                    '3' => intval($stats['three_star']),
                    '2' => intval($stats['two_star']),
                    '1' => intval($stats['one_star'])
                ]
            ]
        ], 'Reviews retrieved successfully');
        
    } elseif ($method === 'DELETE') {
        // Delete a specific review
        $input = json_decode(file_get_contents('php://input'), true);
        $session_id = $input['session_id'] ?? '';
        
        if (empty($session_id)) {
            sendErrorResponse('Session ID is required', 400);
            return;
        }
        
        // Remove rating and feedback from session
        $query = "UPDATE chat_sessions SET rating = NULL, feedback = NULL, rated_at = NULL WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$session_id]);
        
        if ($stmt->rowCount() > 0) {
            sendSuccessResponse([
                'session_id' => $session_id,
                'message' => 'Review has been deleted'
            ], 'Review deleted successfully');
        } else {
            sendErrorResponse('Review not found', 404);
        }
        
    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Reviews error: " . $e->getMessage());
    sendErrorResponse('Internal server error', 500);
}
?>
