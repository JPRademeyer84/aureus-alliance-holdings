<?php
require_once '../config/database.php';
require_once '../utils/response.php';

// Secure file serving endpoint for payment proofs and other sensitive files

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Start session for authentication
    session_start();

    $fileType = $_GET['type'] ?? '';
    $paymentId = $_GET['payment_id'] ?? '';
    $filePath = $_GET['file'] ?? '';

    if (empty($fileType)) {
        http_response_code(400);
        die('File type required');
    }

    // Handle different file types
    switch ($fileType) {
        case 'payment_proof':
            servePaymentProof($db, $paymentId);
            break;
        default:
            http_response_code(400);
            die('Invalid file type');
    }

} catch (Exception $e) {
    http_response_code(500);
    die('Server error: ' . $e->getMessage());
}

function servePaymentProof($db, $paymentId) {
    // Check authentication - admin or payment owner
    $isAdmin = isset($_SESSION['admin_id']);
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$isAdmin && !$userId) {
        http_response_code(401);
        die('Authentication required');
    }

    if (empty($paymentId)) {
        http_response_code(400);
        die('Payment ID required');
    }

    try {
        // Get payment details and file path
        $query = "SELECT mpt.*, u.id as owner_user_id 
                  FROM manual_payment_transactions mpt 
                  JOIN users u ON mpt.user_id = u.id 
                  WHERE mpt.payment_id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            http_response_code(404);
            die('Payment not found');
        }

        // Check authorization
        if (!$isAdmin && $payment['owner_user_id'] !== $userId) {
            http_response_code(403);
            die('Access denied');
        }

        $filePath = $payment['payment_proof_path'];
        if (empty($filePath)) {
            http_response_code(404);
            die('No payment proof file found');
        }

        // Construct full file path
        $fullPath = '../uploads/' . $filePath;
        
        if (!file_exists($fullPath)) {
            http_response_code(404);
            die('File not found on server');
        }

        // Get file info
        $fileInfo = pathinfo($fullPath);
        $mimeType = getMimeType($fullPath);
        $fileSize = filesize($fullPath);

        // Log file access
        logFileAccess($db, $paymentId, $isAdmin ? 'admin' : 'user', $_SESSION['admin_id'] ?? $userId);

        // Set appropriate headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Content-Disposition: inline; filename="payment_proof_' . $paymentId . '.' . $fileInfo['extension'] . '"');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');

        // Stream the file
        $handle = fopen($fullPath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        } else {
            http_response_code(500);
            die('Failed to read file');
        }

    } catch (Exception $e) {
        http_response_code(500);
        die('Error serving file: ' . $e->getMessage());
    }
}

function getMimeType($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'gif' => 'image/gif'
    ];
    
    if (isset($mimeTypes[$extension])) {
        return $mimeTypes[$extension];
    }
    
    // Fallback to PHP's mime_content_type if available
    if (function_exists('mime_content_type')) {
        return mime_content_type($filePath);
    }
    
    return 'application/octet-stream';
}

function logFileAccess($db, $paymentId, $accessorType, $accessorId) {
    try {
        $query = "INSERT INTO security_audit_log (
            event_type, " . ($accessorType === 'admin' ? 'admin_id' : 'user_id') . ", 
            event_details, security_level, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'payment_proof_accessed',
            $accessorId,
            json_encode([
                'payment_id' => $paymentId,
                'accessor_type' => $accessorType
            ]),
            'low',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the file serving
        error_log('Failed to log file access: ' . $e->getMessage());
    }
}

// Additional security function to validate file integrity
function validateFileIntegrity($filePath) {
    // Check if file is actually an image/PDF and not a malicious file
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
        case 'png':
            // Validate image files
            $imageInfo = @getimagesize($filePath);
            return $imageInfo !== false;
            
        case 'pdf':
            // Basic PDF validation - check for PDF header
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                $header = fread($handle, 4);
                fclose($handle);
                return $header === '%PDF';
            }
            return false;
            
        default:
            return false;
    }
}

?>
