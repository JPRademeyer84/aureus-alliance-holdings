<?php

/**
 * Secure file upload utility for payment proofs and other documents
 */

class FileUploadSecurity {
    // Allowed file types for payment proofs
    const ALLOWED_PAYMENT_PROOF_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf'
    ];
    
    // Maximum file sizes (in bytes)
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const MAX_IMAGE_SIZE = 3 * 1024 * 1024; // 3MB for images
    
    // Upload directories
    const UPLOAD_BASE_DIR = '../uploads/';
    const PAYMENT_PROOF_DIR = 'payment_proofs/';
    
    /**
     * Validate uploaded file
     */
    public static function validateFile($file, $allowedTypes = null) {
        $allowedTypes = $allowedTypes ?? self::ALLOWED_PAYMENT_PROOF_TYPES;
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload error: ' . $file['error']];
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'File too large. Maximum size is 5MB'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, PDF'];
        }
        
        // Additional security checks for images
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['valid' => false, 'error' => 'Invalid image file'];
            }
            
            // Check image dimensions (reasonable limits)
            if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
                return ['valid' => false, 'error' => 'Image dimensions too large'];
            }
        }
        
        return ['valid' => true, 'mime_type' => $mimeType, 'extension' => $allowedTypes[$mimeType]];
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName, $extension) {
        // Remove any path information
        $originalName = basename($originalName);
        
        // Generate unique filename
        $timestamp = date('Y-m-d_H-i-s');
        $randomString = bin2hex(random_bytes(8));
        
        // Clean original name (keep only alphanumeric and some safe characters)
        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $cleanName = substr($cleanName, 0, 50); // Limit length
        
        return $timestamp . '_' . $randomString . '_' . $cleanName . '.' . $extension;
    }
    
    /**
     * Create upload directory structure
     */
    public static function createUploadDirectory($subDir) {
        $baseDir = self::UPLOAD_BASE_DIR;
        $fullDir = $baseDir . $subDir;
        
        // Create year/month/day structure
        $dateDir = date('Y/m/d/');
        $fullPath = $fullDir . $dateDir;
        
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                return ['success' => false, 'error' => 'Failed to create upload directory'];
            }
        }
        
        // Create .htaccess file for security
        $htaccessPath = $baseDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Deny direct access to uploaded files\n";
            $htaccessContent .= "Options -Indexes\n";
            $htaccessContent .= "<Files ~ \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">\n";
            $htaccessContent .= "    deny from all\n";
            $htaccessContent .= "</Files>\n";
            file_put_contents($htaccessPath, $htaccessContent);
        }
        
        return ['success' => true, 'path' => $fullPath];
    }
}

/**
 * Handle file upload for payment proofs
 */
function handleFileUpload($file, $uploadType = 'payment_proofs') {
    try {
        // Validate file
        $validation = FileUploadSecurity::validateFile($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Create upload directory
        $dirResult = FileUploadSecurity::createUploadDirectory($uploadType . '/');
        if (!$dirResult['success']) {
            return ['success' => false, 'error' => $dirResult['error']];
        }
        
        // Generate secure filename
        $filename = FileUploadSecurity::generateSecureFilename(
            $file['name'], 
            $validation['extension']
        );
        
        $uploadPath = $dirResult['path'] . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file'];
        }
        
        // Set proper permissions
        chmod($uploadPath, 0644);
        
        // Return relative path for database storage
        $relativePath = str_replace(FileUploadSecurity::UPLOAD_BASE_DIR, '', $uploadPath);
        
        return [
            'success' => true,
            'file_path' => $relativePath,
            'full_path' => $uploadPath,
            'filename' => $filename,
            'mime_type' => $validation['mime_type'],
            'file_size' => $file['size']
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
    }
}

/**
 * Get secure file URL for serving files
 */
function getSecureFileUrl($filePath, $type = 'payment_proof') {
    // This would typically go through a secure file serving script
    // that checks user permissions before serving the file
    return '/api/files/serve.php?type=' . $type . '&file=' . urlencode($filePath);
}

/**
 * Delete uploaded file
 */
function deleteUploadedFile($filePath) {
    $fullPath = FileUploadSecurity::UPLOAD_BASE_DIR . $filePath;
    
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    
    return true; // File doesn't exist, consider it deleted
}

/**
 * Get file info
 */
function getFileInfo($filePath) {
    $fullPath = FileUploadSecurity::UPLOAD_BASE_DIR . $filePath;
    
    if (!file_exists($fullPath)) {
        return null;
    }
    
    return [
        'exists' => true,
        'size' => filesize($fullPath),
        'modified' => filemtime($fullPath),
        'mime_type' => mime_content_type($fullPath)
    ];
}

/**
 * Clean up expired files (should be run via cron job)
 */
function cleanupExpiredFiles($daysOld = 30) {
    $uploadDir = FileUploadSecurity::UPLOAD_BASE_DIR;
    $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir)
    );
    
    $deletedCount = 0;
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getMTime() < $cutoffTime) {
            // Check if file is still referenced in database
            // This would require database connection and queries
            // For now, we'll just delete files older than the cutoff
            if (unlink($file->getPathname())) {
                $deletedCount++;
            }
        }
    }
    
    return $deletedCount;
}

?>
