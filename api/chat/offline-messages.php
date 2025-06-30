<?php
require_once '../config/database.php';
require_once '../config/cors.php';

setCorsHeaders();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendOfflineResponse($data, $message = '', $success = true, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function sendOfflineErrorResponse($message, $code = 400) {
    sendOfflineResponse(null, $message, false, $code);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendOfflineErrorResponse('Invalid JSON input');
        }
        
        $action = $input['action'] ?? '';
        
        if ($action === 'submit') {
            // Submit a new offline message
            $guestName = trim($input['guest_name'] ?? '');
            $guestEmail = trim($input['guest_email'] ?? '');
            $subject = trim($input['subject'] ?? 'Chat Support Request');
            $message = trim($input['message'] ?? '');
            
            if (empty($guestName) || empty($guestEmail) || empty($message)) {
                sendOfflineErrorResponse('Name, email, and message are required');
            }
            
            // Validate email
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                sendOfflineErrorResponse('Invalid email address');
            }
            
            // Insert offline message
            $query = "INSERT INTO offline_messages (guest_name, guest_email, subject, message) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$guestName, $guestEmail, $subject, $message])) {
                $messageId = $db->lastInsertId();
                if (!$messageId) {
                    // For UUID primary keys, we need to get the ID differently
                    $getIdQuery = "SELECT id FROM offline_messages WHERE guest_email = ? ORDER BY created_at DESC LIMIT 1";
                    $getIdStmt = $db->prepare($getIdQuery);
                    $getIdStmt->execute([$guestEmail]);
                    $result = $getIdStmt->fetch(PDO::FETCH_ASSOC);
                    $messageId = $result ? $result['id'] : null;
                }
                
                // Send email notification to admins (implementation depends on your email system)
                // sendOfflineMessageNotification($guestName, $guestEmail, $subject, $message);
                
                sendOfflineResponse([
                    'message_id' => $messageId,
                    'submitted' => true
                ], 'Your message has been submitted. We will contact you soon.');
            } else {
                sendOfflineErrorResponse('Failed to submit message');
            }
            
        } elseif ($action === 'reply') {
            // Admin reply to offline message
            $messageId = $input['message_id'] ?? '';
            $adminId = $input['admin_id'] ?? '';
            $reply = trim($input['reply'] ?? '');
            
            if (empty($messageId) || empty($adminId) || empty($reply)) {
                sendOfflineErrorResponse('Message ID, admin ID, and reply are required');
            }
            
            // Verify admin exists
            $adminQuery = "SELECT id, username FROM admin_users WHERE id = ? AND is_active = TRUE";
            $adminStmt = $db->prepare($adminQuery);
            $adminStmt->execute([$adminId]);
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                sendOfflineErrorResponse('Invalid admin credentials', 401);
            }
            
            // Get message details
            $messageQuery = "SELECT * FROM offline_messages WHERE id = ?";
            $messageStmt = $db->prepare($messageQuery);
            $messageStmt->execute([$messageId]);
            $message = $messageStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$message) {
                sendOfflineErrorResponse('Message not found');
            }
            
            // Update message with reply
            $updateQuery = "UPDATE offline_messages SET admin_reply = ?, replied_by = ?, replied_at = CURRENT_TIMESTAMP, status = 'replied' WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            
            if ($updateStmt->execute([$reply, $adminId, $messageId])) {
                // Send email with reply to guest (implementation depends on your email system)
                // sendOfflineMessageReply($message['guest_email'], $message['guest_name'], $message['subject'], $reply, $admin['username']);
                
                sendOfflineResponse([
                    'message_id' => $messageId,
                    'replied' => true
                ], 'Reply sent successfully');
            } else {
                sendOfflineErrorResponse('Failed to send reply');
            }
            
        } elseif ($action === 'mark_read') {
            // Mark message as read
            $messageId = $input['message_id'] ?? '';
            $adminId = $input['admin_id'] ?? '';
            
            if (empty($messageId) || empty($adminId)) {
                sendOfflineErrorResponse('Message ID and admin ID are required');
            }
            
            // Verify admin exists
            $adminQuery = "SELECT id FROM admin_users WHERE id = ? AND is_active = TRUE";
            $adminStmt = $db->prepare($adminQuery);
            $adminStmt->execute([$adminId]);
            
            if (!$adminStmt->fetch()) {
                sendOfflineErrorResponse('Invalid admin credentials', 401);
            }
            
            // Update message status
            $updateQuery = "UPDATE offline_messages SET status = 'read' WHERE id = ? AND status = 'unread'";
            $updateStmt = $db->prepare($updateQuery);
            
            if ($updateStmt->execute([$messageId])) {
                sendOfflineResponse([
                    'message_id' => $messageId,
                    'marked_read' => true
                ], 'Message marked as read');
            } else {
                sendOfflineErrorResponse('Failed to update message status');
            }
            
        } else {
            sendOfflineErrorResponse('Invalid action specified');
        }
        
    } elseif ($method === 'GET') {
        // Get offline messages (admin only)
        $adminId = $_GET['admin_id'] ?? '';
        $status = $_GET['status'] ?? '';
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        
        if (!$adminId) {
            sendOfflineErrorResponse('Admin ID is required');
        }
        
        // Verify admin exists
        $adminQuery = "SELECT id, role FROM admin_users WHERE id = ? AND is_active = TRUE";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute([$adminId]);
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            sendOfflineErrorResponse('Invalid admin credentials', 401);
        }
        
        // Build query
        $whereClause = '';
        $params = [];
        
        if ($status && in_array($status, ['unread', 'read', 'replied'])) {
            $whereClause = 'WHERE status = ?';
            $params[] = $status;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM offline_messages $whereClause";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get messages
        $query = "SELECT om.*, 
                 a.username as replied_by_username
                 FROM offline_messages om
                 LEFT JOIN admin_users a ON om.replied_by = a.id
                 $whereClause
                 ORDER BY om.created_at DESC
                 LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $unreadQuery = "SELECT COUNT(*) FROM offline_messages WHERE status = 'unread'";
        $unreadStmt = $db->prepare($unreadQuery);
        $unreadStmt->execute();
        $unreadCount = $unreadStmt->fetchColumn();
        
        sendOfflineResponse([
            'messages' => $messages,
            'unread_count' => intval($unreadCount),
            'pagination' => [
                'total' => intval($total),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ], 'Offline messages retrieved successfully');
        
    } else {
        sendOfflineErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Offline messages error: " . $e->getMessage());
    sendOfflineErrorResponse('Internal server error', 500);
}
?>
