<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $input['action'] ?? 'resolve'; // 'resolve', 'unresolve', 'delete'
    $issue_ids = $input['issue_ids'] ?? [];
    $key_id = $input['key_id'] ?? null;
    $language_id = $input['language_id'] ?? null;
    $resolved_by = $input['resolved_by'] ?? 'admin'; // Could be admin user ID
    
    if (empty($issue_ids) && (!$key_id || !$language_id)) {
        throw new Exception('Either issue_ids or key_id+language_id must be provided');
    }
    
    $results = [];
    
    if ($action === 'resolve') {
        // Resolve specific issues or all issues for a translation
        if (!empty($issue_ids)) {
            // Resolve specific issues by ID
            $placeholders = str_repeat('?,', count($issue_ids) - 1) . '?';
            $query = "UPDATE translation_issues 
                     SET is_resolved = TRUE, resolved_at = NOW(), resolved_by = ?
                     WHERE id IN ($placeholders) AND is_resolved = FALSE";
            $params = array_merge([$resolved_by], $issue_ids);
        } else {
            // Resolve all issues for a specific translation
            $query = "UPDATE translation_issues 
                     SET is_resolved = TRUE, resolved_at = NOW(), resolved_by = ?
                     WHERE key_id = ? AND language_id = ? AND is_resolved = FALSE";
            $params = [$resolved_by, $key_id, $language_id];
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $affected_rows = $stmt->rowCount();
        
        $results['resolved_count'] = $affected_rows;
        $results['message'] = "Resolved $affected_rows issue(s)";
        
        // If resolving all issues for a translation, update the translation approval status
        if ($key_id && $language_id && $affected_rows > 0) {
            $updateTranslationQuery = "UPDATE translations 
                                      SET is_approved = TRUE, updated_at = CURRENT_TIMESTAMP 
                                      WHERE key_id = ? AND language_id = ?";
            $updateTranslationStmt = $db->prepare($updateTranslationQuery);
            $updateTranslationStmt->execute([$key_id, $language_id]);
            
            $results['translation_approved'] = true;
        }
        
    } elseif ($action === 'unresolve') {
        // Unresolve issues (mark as unresolved again)
        if (!empty($issue_ids)) {
            $placeholders = str_repeat('?,', count($issue_ids) - 1) . '?';
            $query = "UPDATE translation_issues 
                     SET is_resolved = FALSE, resolved_at = NULL, resolved_by = NULL
                     WHERE id IN ($placeholders) AND is_resolved = TRUE";
            $params = $issue_ids;
        } else {
            $query = "UPDATE translation_issues 
                     SET is_resolved = FALSE, resolved_at = NULL, resolved_by = NULL
                     WHERE key_id = ? AND language_id = ? AND is_resolved = TRUE";
            $params = [$key_id, $language_id];
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $affected_rows = $stmt->rowCount();
        
        $results['unresolved_count'] = $affected_rows;
        $results['message'] = "Unresolved $affected_rows issue(s)";
        
        // If unresolving issues for a translation, update the translation approval status
        if ($key_id && $language_id && $affected_rows > 0) {
            $updateTranslationQuery = "UPDATE translations 
                                      SET is_approved = FALSE, updated_at = CURRENT_TIMESTAMP 
                                      WHERE key_id = ? AND language_id = ?";
            $updateTranslationStmt = $db->prepare($updateTranslationQuery);
            $updateTranslationStmt->execute([$key_id, $language_id]);
            
            $results['translation_unapproved'] = true;
        }
        
    } elseif ($action === 'delete') {
        // Permanently delete issues
        if (!empty($issue_ids)) {
            $placeholders = str_repeat('?,', count($issue_ids) - 1) . '?';
            $query = "DELETE FROM translation_issues WHERE id IN ($placeholders)";
            $params = $issue_ids;
        } else {
            $query = "DELETE FROM translation_issues WHERE key_id = ? AND language_id = ?";
            $params = [$key_id, $language_id];
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $affected_rows = $stmt->rowCount();
        
        $results['deleted_count'] = $affected_rows;
        $results['message'] = "Deleted $affected_rows issue(s)";
        
        // Check if there are any remaining issues for this translation
        if ($key_id && $language_id) {
            $checkQuery = "SELECT COUNT(*) as count FROM translation_issues 
                          WHERE key_id = ? AND language_id = ? AND is_resolved = FALSE";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$key_id, $language_id]);
            $remainingIssues = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($remainingIssues == 0) {
                // No remaining issues, approve the translation
                $updateTranslationQuery = "UPDATE translations 
                                          SET is_approved = TRUE, updated_at = CURRENT_TIMESTAMP 
                                          WHERE key_id = ? AND language_id = ?";
                $updateTranslationStmt = $db->prepare($updateTranslationQuery);
                $updateTranslationStmt->execute([$key_id, $language_id]);
                
                $results['translation_approved'] = true;
            }
        }
        
    } else {
        throw new Exception('Invalid action. Must be "resolve", "unresolve", or "delete"');
    }
    
    // Get updated issue counts for response
    $countQuery = "SELECT 
                    COUNT(*) as total_issues,
                    SUM(CASE WHEN is_resolved = FALSE THEN 1 ELSE 0 END) as unresolved_issues,
                    SUM(CASE WHEN is_resolved = TRUE THEN 1 ELSE 0 END) as resolved_issues
                   FROM translation_issues";
    
    if ($key_id && $language_id) {
        $countQuery .= " WHERE key_id = ? AND language_id = ?";
        $countParams = [$key_id, $language_id];
    } else {
        $countParams = [];
    }
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'results' => $results,
        'updated_counts' => [
            'total_issues' => (int)$counts['total_issues'],
            'unresolved_issues' => (int)$counts['unresolved_issues'],
            'resolved_issues' => (int)$counts['resolved_issues']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>
