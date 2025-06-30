<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendChatTranscriptEmail($userEmail, $userName, $session, $messages) {
    $subject = "Chat Transcript - Aureus Angel Alliance Support";

    // Build HTML email content
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Chat Transcript</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .session-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
            .message { margin-bottom: 15px; padding: 10px; border-radius: 8px; }
            .user-message { background: #e3f2fd; margin-left: 20px; }
            .admin-message { background: #f3e5f5; margin-right: 20px; }
            .message-header { font-weight: bold; font-size: 12px; color: #666; margin-bottom: 5px; }
            .message-time { font-size: 11px; color: #999; margin-top: 5px; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🌟 Aureus Angel Alliance</h1>
            <h2>Chat Support Transcript</h2>
        </div>

        <div class='content'>
            <div class='session-info'>
                <h3>Session Information</h3>
                <p><strong>Date:</strong> " . date('F j, Y g:i A', strtotime($session['created_at'])) . "</p>
                <p><strong>Duration:</strong> " . calculateSessionDuration($session['created_at'], $session['updated_at']) . "</p>
                <p><strong>Status:</strong> " . ucfirst($session['status']) . "</p>
                " . ($session['admin_username'] ? "<p><strong>Support Agent:</strong> " . htmlspecialchars($session['admin_username']) . "</p>" : "") . "
                " . ($session['rating'] ? "<p><strong>Your Rating:</strong> " . str_repeat('⭐', $session['rating']) . " (" . $session['rating'] . "/5)</p>" : "") . "
            </div>

            <h3>Conversation</h3>";

    if (empty($messages)) {
        $html .= "<p><em>No messages in this conversation.</em></p>";
    } else {
        foreach ($messages as $message) {
            $messageClass = $message['sender_type'] === 'user' ? 'user-message' : 'admin-message';
            $senderIcon = $message['sender_type'] === 'user' ? '👤' : '🎧';

            $html .= "
            <div class='message $messageClass'>
                <div class='message-header'>$senderIcon " . htmlspecialchars($message['sender_name']) . "</div>
                <div>" . nl2br(htmlspecialchars($message['message'])) . "</div>
                <div class='message-time'>" . date('g:i A', strtotime($message['created_at'])) . "</div>
            </div>";
        }
    }

    $html .= "
        </div>

        <div class='footer'>
            <p>Thank you for contacting Aureus Angel Alliance Support!</p>
            <p>If you need further assistance, please don't hesitate to reach out.</p>
            <p><strong>Aureus Angel Alliance</strong> | Premium Digital Gold Investment Platform</p>
        </div>
    </body>
    </html>";

    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Aureus Angel Alliance Support <support@aureusangelalliance.com>',
        'Reply-To: support@aureusangelalliance.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    // Send email
    return mail($userEmail, $subject, $html, implode("\r\n", $headers));
}

function calculateSessionDuration($startTime, $endTime) {
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $interval = $start->diff($end);

    if ($interval->h > 0) {
        return $interval->h . 'h ' . $interval->i . 'm';
    } elseif ($interval->i > 0) {
        return $interval->i . 'm ' . $interval->s . 's';
    } else {
        return $interval->s . 's';
    }
}

function sendSessionSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function sendSessionErrorResponse($message, $code = 400) {
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
        sendSessionErrorResponse('Database connection failed', 500);
    }

    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            sendSessionErrorResponse('Invalid JSON input');
        }

        $action = $input['action'] ?? '';

        if ($action === 'create') {
            // Create new chat session
            if (!isset($input['user_id'])) {
                sendSessionErrorResponse('User ID is required');
            }
            
            $user_id = $input['user_id'];
            
            // Check if user already has an active session
            $checkQuery = "SELECT id FROM chat_sessions WHERE user_id = ? AND status IN ('waiting', 'active')";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$user_id]);
            $existingSession = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingSession) {
                // Return existing session
                $getQuery = "SELECT cs.*, u.username, u.email 
                           FROM chat_sessions cs 
                           JOIN users u ON cs.user_id = u.id 
                           WHERE cs.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$existingSession['id']]);
                $session = $getStmt->fetch(PDO::FETCH_ASSOC);
                
                sendSessionSuccessResponse($session, 'Existing chat session found');
            }

            // Create new session
            $query = "INSERT INTO chat_sessions (user_id) VALUES (?)";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$user_id])) {
                $sessionId = $db->lastInsertId();
                if (!$sessionId) {
                    // For UUID primary keys, we need to get the ID differently
                    $getIdQuery = "SELECT id FROM chat_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
                    $getIdStmt = $db->prepare($getIdQuery);
                    $getIdStmt->execute([$user_id]);
                    $result = $getIdStmt->fetch(PDO::FETCH_ASSOC);
                    $sessionId = $result ? $result['id'] : null;
                }

                // Get the created session
                $getQuery = "SELECT cs.*, u.username, u.email
                           FROM chat_sessions cs
                           JOIN users u ON cs.user_id = u.id
                           WHERE cs.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$sessionId]);
                $newSession = $getStmt->fetch(PDO::FETCH_ASSOC);

                sendSessionSuccessResponse($newSession, 'Chat session created successfully');
            } else {
                sendSessionErrorResponse('Failed to create chat session', 500);
            }
            
        } elseif ($action === 'assign') {
            // Assign admin to session
            if (!isset($input['session_id']) || !isset($input['admin_id'])) {
                sendSessionErrorResponse('Session ID and Admin ID are required');
            }

            $session_id = $input['session_id'];
            $admin_id = $input['admin_id'];

            $query = "UPDATE chat_sessions SET admin_id = ?, status = 'active' WHERE id = ? AND status = 'waiting'";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$admin_id, $session_id])) {
                // Get the updated session
                $getQuery = "SELECT cs.*, u.username, u.email, a.username as admin_username
                           FROM chat_sessions cs
                           JOIN users u ON cs.user_id = u.id
                           LEFT JOIN admin_users a ON cs.admin_id = a.id
                           WHERE cs.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$session_id]);
                $updatedSession = $getStmt->fetch(PDO::FETCH_ASSOC);

                sendSessionSuccessResponse($updatedSession, 'Admin assigned to chat session');
            } else {
                sendSessionErrorResponse('Failed to assign admin to session', 500);
            }
            
        } elseif ($action === 'close') {
            // Close chat session
            if (!isset($input['session_id'])) {
                sendSessionErrorResponse('Session ID is required');
            }

            $session_id = $input['session_id'];

            $query = "UPDATE chat_sessions SET status = 'closed' WHERE id = ?";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$session_id])) {
                sendSessionSuccessResponse(['session_id' => $session_id], 'Chat session closed');
            } else {
                sendSessionErrorResponse('Failed to close chat session', 500);
            }

        } elseif ($action === 'create_guest') {
            // Create new chat session for guest user
            if (!isset($input['guest_email']) || !isset($input['guest_name'])) {
                sendSessionErrorResponse('Guest email and name are required');
            }

            $guest_email = trim($input['guest_email']);
            $guest_name = trim($input['guest_name']);

            if (empty($guest_email) || empty($guest_name)) {
                sendSessionErrorResponse('Guest email and name cannot be empty');
            }

            if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
                sendSessionErrorResponse('Invalid email address');
            }

            // Check if any admin is online
            $onlineAdminQuery = "SELECT COUNT(*) FROM admin_users WHERE chat_status = 'online' AND is_active = TRUE";
            $onlineAdminStmt = $db->prepare($onlineAdminQuery);
            $onlineAdminStmt->execute();
            $onlineAdminCount = $onlineAdminStmt->fetchColumn();

            if ($onlineAdminCount == 0) {
                // No admin online, suggest offline message
                sendSessionSuccessResponse([
                    'no_admin_available' => true,
                    'message' => 'No support agents are currently online. Please leave a message and we will get back to you soon.',
                    'guest_email' => $guest_email,
                    'guest_name' => $guest_name
                ], 'No admin available for live chat');
                return;
            }

            // Create new guest session
            $query = "INSERT INTO chat_sessions (guest_email, guest_name) VALUES (?, ?)";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$guest_email, $guest_name])) {
                $sessionId = $db->lastInsertId();
                if (!$sessionId) {
                    // For UUID primary keys, we need to get the ID differently
                    $getIdQuery = "SELECT id FROM chat_sessions WHERE guest_email = ? AND guest_name = ? ORDER BY created_at DESC LIMIT 1";
                    $getIdStmt = $db->prepare($getIdQuery);
                    $getIdStmt->execute([$guest_email, $guest_name]);
                    $result = $getIdStmt->fetch(PDO::FETCH_ASSOC);
                    $sessionId = $result ? $result['id'] : null;
                }

                // Get the created session
                $getQuery = "SELECT cs.*, cs.guest_name as username, cs.guest_email as email
                           FROM chat_sessions cs
                           WHERE cs.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$sessionId]);
                $newSession = $getStmt->fetch(PDO::FETCH_ASSOC);

                sendSessionSuccessResponse($newSession, 'Guest chat session created successfully');
            } else {
                sendSessionErrorResponse('Failed to create guest chat session', 500);
            }

        } elseif ($action === 'rate') {
            // Rate chat session
            if (!isset($input['session_id']) || !isset($input['rating'])) {
                sendSessionErrorResponse('Session ID and rating are required');
            }

            $session_id = $input['session_id'];
            $rating = intval($input['rating']);
            $feedback = trim($input['feedback'] ?? '');
            $user_email = trim($input['user_email'] ?? '');

            if ($rating < 1 || $rating > 5) {
                sendSessionErrorResponse('Rating must be between 1 and 5');
            }

            $query = "UPDATE chat_sessions SET rating = ?, feedback = ?, rated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$rating, $feedback, $session_id])) {
                sendSessionSuccessResponse([
                    'session_id' => $session_id,
                    'rating' => $rating,
                    'feedback' => $feedback
                ], 'Rating submitted successfully');
            } else {
                sendSessionErrorResponse('Failed to submit rating', 500);
            }

        } elseif ($action === 'send_transcript') {
            // Send chat transcript via email
            if (!isset($input['session_id']) || !isset($input['user_email'])) {
                sendSessionErrorResponse('Session ID and user email are required');
            }

            $session_id = $input['session_id'];
            $user_email = trim($input['user_email']);
            $user_name = trim($input['user_name'] ?? 'User');

            if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                sendSessionErrorResponse('Invalid email address');
            }

            // Get session details and messages
            $sessionQuery = "SELECT cs.*,
                           COALESCE(u.username, cs.guest_name) as username,
                           COALESCE(u.email, cs.guest_email) as email,
                           a.username as admin_username
                           FROM chat_sessions cs
                           LEFT JOIN users u ON cs.user_id = u.id
                           LEFT JOIN admin_users a ON cs.admin_id = a.id
                           WHERE cs.id = ?";
            $sessionStmt = $db->prepare($sessionQuery);
            $sessionStmt->execute([$session_id]);
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                sendSessionErrorResponse('Session not found');
            }

            // Get messages
            $messagesQuery = "SELECT cm.*,
                            CASE
                                WHEN cm.sender_type = 'user' THEN COALESCE(u.username, cs.guest_name)
                                WHEN cm.sender_type = 'admin' THEN a.username
                                ELSE 'System'
                            END as sender_name
                            FROM chat_messages cm
                            LEFT JOIN chat_sessions cs ON cm.session_id = cs.id
                            LEFT JOIN users u ON cs.user_id = u.id
                            LEFT JOIN admin_users a ON cm.admin_id = a.id
                            WHERE cm.session_id = ?
                            ORDER BY cm.created_at ASC";
            $messagesStmt = $db->prepare($messagesQuery);
            $messagesStmt->execute([$session_id]);
            $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Send email with transcript
            $emailSent = sendChatTranscriptEmail($user_email, $user_name, $session, $messages);

            if ($emailSent) {
                // Update session to mark transcript as sent
                $updateQuery = "UPDATE chat_sessions SET transcript_sent = 1, transcript_sent_at = NOW() WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$session_id]);

                sendSessionSuccessResponse([
                    'session_id' => $session_id,
                    'email' => $user_email
                ], 'Chat transcript sent successfully');
            } else {
                sendSessionErrorResponse('Failed to send chat transcript', 500);
            }

        } else {
            sendSessionErrorResponse('Invalid action');
        }
        
    } elseif ($method === 'GET') {
        $user_id = $_GET['user_id'] ?? null;
        $admin_view = $_GET['admin_view'] ?? false;
        $status = $_GET['status'] ?? null;
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        
        if ($admin_view) {
            // Admin view - get all sessions
            $whereClause = '';
            $params = [];
            
            if ($status && in_array($status, ['waiting', 'active', 'closed'])) {
                $whereClause = 'WHERE cs.status = ?';
                $params[] = $status;
            }
            
            $query = "SELECT cs.*,
                     COALESCE(u.username, cs.guest_name) as username,
                     COALESCE(u.email, cs.guest_email) as email,
                     CASE WHEN cs.user_id IS NOT NULL THEN 'user' ELSE 'guest' END as user_type,
                     a.username as admin_username
                     FROM chat_sessions cs
                     LEFT JOIN users u ON cs.user_id = u.id
                     LEFT JOIN admin_users a ON cs.admin_id = a.id
                     $whereClause
                     ORDER BY cs.updated_at DESC
                     LIMIT $limit OFFSET $offset";
            
        } else {
            // User view - get sessions for specific user
            if (!$user_id) {
                sendSessionErrorResponse('User ID is required for user view');
            }
            
            $query = "SELECT cs.*,
                     COALESCE(u.username, cs.guest_name) as username,
                     COALESCE(u.email, cs.guest_email) as email,
                     CASE WHEN cs.user_id IS NOT NULL THEN 'user' ELSE 'guest' END as user_type,
                     a.username as admin_username
                     FROM chat_sessions cs
                     LEFT JOIN users u ON cs.user_id = u.id
                     LEFT JOIN admin_users a ON cs.admin_id = a.id
                     WHERE cs.user_id = ?
                     ORDER BY cs.updated_at DESC
                     LIMIT $limit OFFSET $offset";
            $params = [$user_id];
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        if ($admin_view) {
            $countQuery = "SELECT COUNT(*) FROM chat_sessions cs" . 
                         ($status ? " WHERE cs.status = ?" : "");
            $countParams = $status ? [$status] : [];
        } else {
            $countQuery = "SELECT COUNT(*) FROM chat_sessions WHERE user_id = ?";
            $countParams = [$user_id];
        }
        
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        sendSessionSuccessResponse([
            'sessions' => $sessions,
            'pagination' => [
                'total' => intval($total),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);

    } else {
        sendSessionErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Chat sessions API error: " . $e->getMessage());
    sendSessionErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
