<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method allowed");
    }

    // Create certificate templates directory if it doesn't exist
    $uploadDir = '../../assets/certificate-templates/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $response = [];
    $uploadedFiles = [];

    // Handle frame image upload
    if (isset($_FILES['frame_image']) && $_FILES['frame_image']['error'] === UPLOAD_ERR_OK) {
        $frameFile = handleFileUpload($_FILES['frame_image'], $uploadDir, 'frame');
        $uploadedFiles['frame_image_path'] = $frameFile;
        $response['frame_uploaded'] = true;
    }

    // Handle background image upload
    if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
        $backgroundFile = handleFileUpload($_FILES['background_image'], $uploadDir, 'background');
        $uploadedFiles['background_image_path'] = $backgroundFile;
        $response['background_uploaded'] = true;
    }

    if (empty($uploadedFiles)) {
        throw new Exception("No valid files uploaded");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Files uploaded successfully',
        'files' => $uploadedFiles,
        'details' => $response
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleFileUpload($file, $uploadDir, $type) {
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Invalid file type for $type. Only JPEG, PNG, GIF, and WebP are allowed.");
    }

    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        throw new Exception("File size too large for $type. Maximum 10MB allowed.");
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Failed to upload $type file");
    }

    // Return relative path for database storage
    return 'assets/certificate-templates/' . $filename;
}

function validateImage($filepath) {
    // Additional image validation
    $imageInfo = getimagesize($filepath);
    if ($imageInfo === false) {
        unlink($filepath); // Delete invalid file
        throw new Exception("Invalid image file");
    }

    // Check minimum dimensions (optional)
    $minWidth = 800;
    $minHeight = 600;
    
    if ($imageInfo[0] < $minWidth || $imageInfo[1] < $minHeight) {
        unlink($filepath); // Delete undersized file
        throw new Exception("Image dimensions too small. Minimum {$minWidth}x{$minHeight} required.");
    }

    return true;
}
?>
