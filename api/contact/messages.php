<?php
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

function sendContactSuccessResponse($data, $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function sendContactErrorResponse($message, $code = 400) {
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
        sendContactErrorResponse('Database connection failed', 500);
    }
    
    $database->createTables();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        // Handle contact form submission
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendContactErrorResponse('Invalid JSON input');
        }

        $action = $input['action'] ?? '';

        if ($action === 'submit') {
            // Submit new contact message
            if (!isset($input['user_id']) || !isset($input['subject']) || !isset($input['message'])) {
                sendContactErrorResponse('User ID, subject, and message are required');
            }
            
            $user_id = $input['user_id'];
            $subject = trim($input['subject']);
            $message = trim($input['message']);
            
            if (empty($subject) || empty($message)) {
                sendContactErrorResponse('Subject and message cannot be empty');
            }

            if (strlen($subject) > 255) {
                sendContactErrorResponse('Subject is too long (max 255 characters)');
            }

            if (strlen($message) > 5000) {
                sendContactErrorResponse('Message is too long (max 5000 characters)');
            }
            
            // Insert contact message
            $query = "INSERT INTO contact_messages (user_id, subject, message) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$user_id, $subject, $message])) {
                $messageId = $db->lastInsertId();
                if (!$messageId) {
                    // For UUID primary keys, we need to get the ID differently
                    $getIdQuery = "SELECT id FROM contact_messages WHERE user_id = ? AND subject = ? AND message = ? ORDER BY created_at DESC LIMIT 1";
                    $getIdStmt = $db->prepare($getIdQuery);
                    $getIdStmt->execute([$user_id, $subject, $message]);
                    $result = $getIdStmt->fetch(PDO::FETCH_ASSOC);
                    $messageId = $result ? $result['id'] : null;
                }
                
                // Get the created message
                $getQuery = "SELECT cm.*, u.username, u.email 
                           FROM contact_messages cm 
                           JOIN users u ON cm.user_id = u.id 
                           WHERE cm.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$messageId]);
                $newMessage = $getStmt->fetch(PDO::FETCH_ASSOC);
                
                sendContactSuccessResponse($newMessage, 'Contact message submitted successfully');
            } else {
                sendContactErrorResponse('Failed to submit contact message', 500);
            }

        } elseif ($action === 'reply') {
            // Admin reply to contact message
            if (!isset($input['message_id']) || !isset($input['admin_reply'])) {
                sendContactErrorResponse('Message ID and admin reply are required');
            }

            $message_id = $input['message_id'];
            $admin_reply = trim($input['admin_reply']);

            if (empty($admin_reply)) {
                sendContactErrorResponse('Admin reply cannot be empty');
            }

            if (strlen($admin_reply) > 5000) {
                sendContactErrorResponse('Reply is too long (max 5000 characters)');
            }
            
            // Update contact message with reply
            $query = "UPDATE contact_messages SET admin_reply = ?, status = 'replied' WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$admin_reply, $message_id])) {
                // Get the updated message
                $getQuery = "SELECT cm.*, u.username, u.email 
                           FROM contact_messages cm 
                           JOIN users u ON cm.user_id = u.id 
                           WHERE cm.id = ?";
                $getStmt = $db->prepare($getQuery);
                $getStmt->execute([$message_id]);
                $updatedMessage = $getStmt->fetch(PDO::FETCH_ASSOC);
                
                sendContactSuccessResponse($updatedMessage, 'Reply sent successfully');
            } else {
                sendContactErrorResponse('Failed to send reply', 500);
            }

        } elseif ($action === 'mark_read') {
            // Mark message as read
            if (!isset($input['message_id'])) {
                sendContactErrorResponse('Message ID is required');
            }

            $message_id = $input['message_id'];

            $query = "UPDATE contact_messages SET status = 'read' WHERE id = ? AND status = 'unread'";
            $stmt = $db->prepare($query);

            if ($stmt->execute([$message_id])) {
                sendContactSuccessResponse(['message_id' => $message_id], 'Message marked as read');
            } else {
                sendContactErrorResponse('Failed to mark message as read', 500);
            }

        } else {
            sendContactErrorResponse('Invalid action');
        }
        
    } elseif ($method === 'GET') {
        // Handle getting contact messages
        $user_id = $_GET['user_id'] ?? null;
        $admin_view = $_GET['admin_view'] ?? false;
        $status = $_GET['status'] ?? null;
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        $offset = max(0, intval($_GET['offset'] ?? 0));
        
        if ($admin_view) {
            // Admin view - get all messages with user info
            $whereClause = '';
            $params = [];
            
            if ($status && in_array($status, ['unread', 'read', 'replied'])) {
                $whereClause = 'WHERE cm.status = ?';
                $params[] = $status;
            }
            
            $query = "SELECT cm.*, u.username, u.email
                     FROM contact_messages cm
                     JOIN users u ON cm.user_id = u.id
                     $whereClause
                     ORDER BY cm.created_at DESC
                     LIMIT $limit OFFSET $offset";
            
        } else {
            // User view - get messages for specific user
            if (!$user_id) {
                sendContactErrorResponse('User ID is required for user view');
            }
            
            $query = "SELECT cm.*, u.username, u.email
                     FROM contact_messages cm
                     JOIN users u ON cm.user_id = u.id
                     WHERE cm.user_id = ?
                     ORDER BY cm.created_at DESC
                     LIMIT $limit OFFSET $offset";
            $params = [$user_id];
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        if ($admin_view) {
            $countQuery = "SELECT COUNT(*) FROM contact_messages cm" . 
                         ($status ? " WHERE cm.status = ?" : "");
            $countParams = $status ? [$status] : [];
        } else {
            $countQuery = "SELECT COUNT(*) FROM contact_messages WHERE user_id = ?";
            $countParams = [$user_id];
        }
        
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        sendContactSuccessResponse([
            'messages' => $messages,
            'pagination' => [
                'total' => intval($total),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);

    } else {
        sendContactErrorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Contact messages API error: " . $e->getMessage());
    sendContactErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>
