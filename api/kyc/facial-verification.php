<?php
error_log("Facial verification API called");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("OPTIONS request handled");
    http_response_code(200);
    exit();
}

error_log("Method: " . $_SERVER['REQUEST_METHOD']);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

error_log("Starting session...");
// Start session to get user info
session_start();

error_log("Session started, checking user...");
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not authenticated");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
error_log("User ID: " . $user_id);

try {
    error_log("Processing facial verification data...");

    // Get raw input to check for JSON parsing issues
    $raw_input = file_get_contents('php://input');
    $input_size = strlen($raw_input);
    error_log("Input size: " . $input_size . " bytes");

    // Parse JSON input with error handling
    $input = json_decode($raw_input, true);
    $json_error = json_last_error();

    if ($json_error !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . " (Error code: $json_error)");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit();
    }

    if (!$input) {
        error_log("Empty JSON input");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Empty JSON input']);
        exit();
    }

    // Don't log the full input as it contains large base64 image data
    error_log("Input received with " . count($input) . " fields");

    // Validate required fields
    $required_fields = ['success', 'confidence', 'livenessScore', 'capturedImage'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            error_log("Missing field: " . $field);
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit();
        }
    }

    // Extract and validate data
    $verification_success = (bool)$input['success'];
    $confidence = (float)$input['confidence'];
    $liveness_score = (float)$input['livenessScore'];
    $captured_image = $input['capturedImage'];

    // Validate image data format
    if (!is_string($captured_image) || !preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $captured_image)) {
        error_log("Invalid image format");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid image format']);
        exit();
    }

    error_log("All fields validated - Success: " . ($verification_success ? 'true' : 'false') . ", Confidence: $confidence, Liveness: $liveness_score");

    // Database connection
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        error_log("Database connection failed");
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }

    // Ensure facial_verifications table exists (using the standard table name)
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS facial_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            captured_image_path VARCHAR(255) NOT NULL,
            confidence_score DECIMAL(5,4) NOT NULL,
            liveness_score DECIMAL(5,4) NOT NULL,
            verification_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
            comparison_result JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (verification_status)
        )
    ";

    $pdo->exec($create_table_sql);

    // Add facial_verification_status column to users table if it doesn't exist
    $add_column_sql = "
        ALTER TABLE users
        ADD COLUMN IF NOT EXISTS facial_verification_status ENUM('not_started', 'pending', 'verified', 'failed') DEFAULT 'not_started'
    ";

    try {
        $pdo->exec($add_column_sql);
    } catch (PDOException $e) {
        // Column might already exist, ignore error
        error_log("Column addition note: " . $e->getMessage());
    }

    // Save the captured image if provided
    $image_path = null;
    if (!empty($captured_image) && strpos($captured_image, 'data:image/') === 0) {
        // Extract image data from base64
        $image_parts = explode(',', $captured_image);
        if (count($image_parts) === 2) {
            $image_data = base64_decode($image_parts[1]);

            // Create facial verification directory if it doesn't exist
            $facial_dir = '../../assets/kyc/facial';
            if (!is_dir($facial_dir)) {
                mkdir($facial_dir, 0755, true);
            }

            // Generate unique filename
            $filename = 'facial_' . $user_id . '_' . time() . '.jpg';
            $full_path = $facial_dir . '/' . $filename;

            // Save the image
            if (file_put_contents($full_path, $image_data)) {
                $image_path = 'assets/kyc/facial/' . $filename;
                error_log("Facial image saved successfully: " . $image_path);
            } else {
                error_log("Failed to save facial image to: " . $full_path);
            }
        }
    }

    // Determine status based on verification results
    $status = 'failed';
    if ($verification_success && $confidence >= 0.5 && $liveness_score >= 0.6) {
        $status = 'verified';
    } elseif ($verification_success && ($confidence >= 0.3 || $liveness_score >= 0.4)) {
        $status = 'pending'; // Needs admin review
    }

    // Insert new facial verification record (always create new record for audit trail)
    $insert_sql = "
        INSERT INTO facial_verifications
        (user_id, captured_image_path, confidence_score, liveness_score, verification_status, verified_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $insert_stmt = $pdo->prepare($insert_sql);
    $verified_at = ($status === 'verified') ? date('Y-m-d H:i:s') : null;

    $insert_result = $insert_stmt->execute([
        $user_id,
        $image_path,
        $confidence,
        $liveness_score,
        $status,
        $verified_at
    ]);

    if (!$insert_result) {
        error_log("Failed to insert facial verification record");
        throw new Exception('Failed to save verification record');
    }

    // Update user's facial verification status
    $user_status = ($status === 'verified') ? 'verified' :
                   (($status === 'pending') ? 'pending' : 'failed');

    $update_user_sql = "UPDATE users SET facial_verification_status = ? WHERE id = ?";
    $update_user_stmt = $pdo->prepare($update_user_sql);
    $update_result = $update_user_stmt->execute([$user_status, $user_id]);

    if (!$update_result) {
        error_log("Failed to update user facial verification status");
        // Don't fail here, verification record was saved
    }

    // Log the verification attempt
    error_log("Facial verification for user $user_id: success=$verification_success, confidence=$confidence, liveness=$liveness_score, status=$status");

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Facial verification result saved successfully',
        'verification_status' => $user_status,
        'requires_admin_review' => ($status === 'pending'),
        'confidence' => $confidence,
        'liveness_score' => $liveness_score
    ]);

} catch (PDOException $e) {
    error_log("Database error in facial verification: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in facial verification: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
