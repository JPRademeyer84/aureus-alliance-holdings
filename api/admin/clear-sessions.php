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
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'clear_all') {
            // Clear all chat sessions and their messages
            $db->beginTransaction();
            
            try {
                // Delete all chat messages first (due to foreign key constraint)
                $deleteMessagesQuery = "DELETE FROM chat_messages";
                $db->exec($deleteMessagesQuery);
                
                // Delete all chat sessions
                $deleteSessionsQuery = "DELETE FROM chat_sessions";
                $db->exec($deleteSessionsQuery);
                
                $db->commit();
                
                sendSuccessResponse([
                    'cleared' => true,
                    'message' => 'All chat sessions and messages have been cleared'
                ], 'Chat sessions cleared successfully');
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            
        } elseif ($action === 'clear_closed') {
            // Clear only closed chat sessions
            $db->beginTransaction();
            
            try {
                // Get closed session IDs
                $getClosedQuery = "SELECT id FROM chat_sessions WHERE status = 'closed'";
                $closedStmt = $db->prepare($getClosedQuery);
                $closedStmt->execute();
                $closedSessions = $closedStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($closedSessions)) {
                    $placeholders = str_repeat('?,', count($closedSessions) - 1) . '?';
                    
                    // Delete messages for closed sessions
                    $deleteMessagesQuery = "DELETE FROM chat_messages WHERE session_id IN ($placeholders)";
                    $deleteMessagesStmt = $db->prepare($deleteMessagesQuery);
                    $deleteMessagesStmt->execute($closedSessions);
                    
                    // Delete closed sessions
                    $deleteSessionsQuery = "DELETE FROM chat_sessions WHERE status = 'closed'";
                    $db->exec($deleteSessionsQuery);
                }
                
                $db->commit();
                
                sendSuccessResponse([
                    'cleared' => true,
                    'count' => count($closedSessions),
                    'message' => count($closedSessions) . ' closed chat sessions have been cleared'
                ], 'Closed chat sessions cleared successfully');
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            
        } elseif ($action === 'clear_specific') {
            // Clear specific session by ID
            $session_id = $input['session_id'] ?? '';
            
            if (empty($session_id)) {
                sendErrorResponse('Session ID is required', 400);
                return;
            }
            
            $db->beginTransaction();
            
            try {
                // Delete messages for the specific session
                $deleteMessagesQuery = "DELETE FROM chat_messages WHERE session_id = ?";
                $deleteMessagesStmt = $db->prepare($deleteMessagesQuery);
                $deleteMessagesStmt->execute([$session_id]);
                
                // Delete the specific session
                $deleteSessionQuery = "DELETE FROM chat_sessions WHERE id = ?";
                $deleteSessionStmt = $db->prepare($deleteSessionQuery);
                $deleteSessionStmt->execute([$session_id]);
                
                $db->commit();
                
                sendSuccessResponse([
                    'cleared' => true,
                    'session_id' => $session_id,
                    'message' => 'Chat session has been deleted'
                ], 'Chat session deleted successfully');
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            
        } else {
            sendErrorResponse('Invalid action', 400);
        }
        
    } else {
        sendErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Clear sessions error: " . $e->getMessage());
    sendErrorResponse('Internal server error', 500);
}
?>
