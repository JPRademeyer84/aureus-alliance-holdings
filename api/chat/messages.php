<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendChatSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function sendChatErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendChatErrorResponse('Database connection failed', 500);
    }

    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            sendChatErrorResponse('Invalid JSON input');
        }

        $action = $input['action'] ?? '';

        if ($action === 'send') {
            // Send new chat message
            if (!isset($input['session_id']) || !isset($input['sender_type']) || !isset($input['message'])) {
                sendChatErrorResponse('Session ID, sender type, and message are required');
            }

            $session_id = $input['session_id'];
            $sender_type = $input['sender_type'];
            $message = trim($input['message']);

            // Handle sender ID for both user and guest
            if ($sender_type === 'user') {
                if (isset($input['sender_id'])) {
                    $sender_id = $input['sender_id'];
                } else {
                    // Guest user
                    $sender_id = 'guest';
                    $guest_email = $input['guest_email'] ?? '';
                    $guest_name = $input['guest_name'] ?? '';
                }
            } else {
                if (!isset($input['sender_id'])) {
                    sendChatErrorResponse('Sender ID is required for admin messages');
                }
                $sender_id = $input['sender_id'];
            }

            if (!in_array($sender_type, ['user', 'admin'])) {
                sendChatErrorResponse('Invalid sender type');
            }

            if (empty($message)) {
                sendChatErrorResponse('Message cannot be empty');
            }

            if (strlen($message) > 2000) {
                sendChatErrorResponse('Message is too long (max 2000 characters)');
            }
            
            // Verify session exists and is active
            $sessionQuery = "SELECT id, status FROM chat_sessions WHERE id = ?";
            $sessionStmt = $db->prepare($sessionQuery);
            $sessionStmt->execute([$session_id]);
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                sendChatErrorResponse('Chat session not found');
            }

            if ($session['status'] === 'closed') {
                sendChatErrorResponse('Cannot send message to closed chat session');
            }
            
            // Insert chat message
            $query = "INSERT INTO chat_messages (session_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$session_id, $sender_type, $sender_id, $message])) {
                $messageId = $db->lastInsertId();
                if (!$messageId) {
                    // For UUID primary keys, we need to get the ID differently
                    $getIdQuery = "SELECT id FROM chat_messages WHERE session_id = ? AND sender_type = ? AND sender_id = ? AND message = ? ORDER BY created_at DESC LIMIT 1";
                    $getIdStmt = $db->prepare($getIdQuery);
                    $getIdStmt->execute([$session_id, $sender_type, $sender_id, $message]);
                    $result = $getIdStmt->fetch(PDO::FETCH_ASSOC);
                    $messageId = $result ? $result['id'] : null;
                }
                
                // Update session timestamp
                $updateSessionQuery = "UPDATE chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateSessionStmt = $db->prepare($updateSessionQuery);
                $updateSessionStmt->execute([$session_id]);
                
                // Get the created message with sender info
                $getQuery = "SELECT cm.*,
                           CASE
                               WHEN cm.sender_type = 'user' AND cm.sender_id != 'guest' THEN u.username
                               WHEN cm.sender_type = 'user' AND cm.sender_id = 'guest' THEN cs.guest_name
                               WHEN cm.sender_type = 'admin' THEN a.username
                           END as sender_name
                           FROM chat_messages cm
                           LEFT JOIN chat_sessions cs ON cm.session_id = cs.id
                           LEFT JOIN users u ON cm.sender_type = 'user' AND cm.sender_id = u.id
                           LEFT JOIN admin_users a ON cm.sender_type = 'admin' AND cm.sender_id = a.id
                           WHERE cm.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$messageId]);
                $newMessage = $getStmt->fetch(PDO::FETCH_ASSOC);
                
                sendChatSuccessResponse($newMessage, 'Message sent successfully');
            } else {
                sendChatErrorResponse('Failed to send message', 500);
            }

        } elseif ($action === 'mark_read') {
            // Mark messages as read
            if (!isset($input['session_id']) || !isset($input['reader_type'])) {
                sendChatErrorResponse('Session ID and reader type are required');
            }

            $session_id = $input['session_id'];
            $reader_type = $input['reader_type'];

            if (!in_array($reader_type, ['user', 'admin'])) {
                sendChatErrorResponse('Invalid reader type');
            }
            
            // Mark all unread messages from the opposite sender type as read
            $opposite_type = $reader_type === 'user' ? 'admin' : 'user';
            
            $query = "UPDATE chat_messages SET is_read = TRUE 
                     WHERE session_id = ? AND sender_type = ? AND is_read = FALSE";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$session_id, $opposite_type])) {
                $affected_rows = $stmt->rowCount();
                sendChatSuccessResponse([
                    'session_id' => $session_id,
                    'marked_read' => $affected_rows
                ], 'Messages marked as read');
            } else {
                sendChatErrorResponse('Failed to mark messages as read', 500);
            }

        } else {
            sendChatErrorResponse('Invalid action');
        }
        
    } elseif ($method === 'GET') {
        // Get chat messages for a session
        $session_id = $_GET['session_id'] ?? null;
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        $since = $_GET['since'] ?? null; // For polling new messages
        
        if (!$session_id) {
            sendChatErrorResponse('Session ID is required');
        }
        
        // Build query
        $whereClause = 'WHERE cm.session_id = ?';
        $params = [$session_id];
        
        if ($since) {
            $whereClause .= ' AND cm.created_at > ?';
            $params[] = $since;
        }
        
        $query = "SELECT cm.*,
                 CASE
                     WHEN cm.sender_type = 'user' AND cm.sender_id != 'guest' THEN u.username
                     WHEN cm.sender_type = 'user' AND cm.sender_id = 'guest' THEN cs.guest_name
                     WHEN cm.sender_type = 'admin' THEN a.username
                 END as sender_name
                 FROM chat_messages cm
                 LEFT JOIN chat_sessions cs ON cm.session_id = cs.id
                 LEFT JOIN users u ON cm.sender_type = 'user' AND cm.sender_id = u.id
                 LEFT JOIN admin_users a ON cm.sender_type = 'admin' AND cm.sender_id = a.id
                 $whereClause
                 ORDER BY cm.created_at ASC
                 LIMIT $limit OFFSET $offset";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM chat_messages WHERE session_id = ?" . 
                     ($since ? " AND created_at > ?" : "");
        $countParams = $since ? [$session_id, $since] : [$session_id];
        
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        // Get unread counts
        $unreadUserQuery = "SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND sender_type = 'user' AND is_read = FALSE";
        $unreadUserStmt = $db->prepare($unreadUserQuery);
        $unreadUserStmt->execute([$session_id]);
        $unread_from_user = $unreadUserStmt->fetchColumn();
        
        $unreadAdminQuery = "SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND sender_type = 'admin' AND is_read = FALSE";
        $unreadAdminStmt = $db->prepare($unreadAdminQuery);
        $unreadAdminStmt->execute([$session_id]);
        $unread_from_admin = $unreadAdminStmt->fetchColumn();

        // Get session status
        $sessionStatusQuery = "SELECT status FROM chat_sessions WHERE id = ?";
        $sessionStatusStmt = $db->prepare($sessionStatusQuery);
        $sessionStatusStmt->execute([$session_id]);
        $session_status = $sessionStatusStmt->fetchColumn();

        sendChatSuccessResponse([
            'messages' => $messages,
            'session_status' => $session_status,
            'unread_counts' => [
                'from_user' => intval($unread_from_user),
                'from_admin' => intval($unread_from_admin)
            ],
            'pagination' => [
                'total' => intval($total),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);

    } else {
        sendChatErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Chat messages API error: " . $e->getMessage());
    sendChatErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
