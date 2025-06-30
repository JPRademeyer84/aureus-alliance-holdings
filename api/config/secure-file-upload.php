<?php
/**
 * SECURE FILE UPLOAD SYSTEM
 * Bank-level file upload security with comprehensive validation
 */

require_once 'security-logger.php';
require_once 'virus-scanner.php';

class SecureFileUpload {
    private $allowedMimeTypes = [
        'image/jpeg',
        'image/png', 
        'image/jpg',
        'application/pdf'
    ];
    
    private $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'pdf'
    ];
    
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    private $uploadPath = '';
    private $quarantinePath = '';
    
    public function __construct($uploadPath = null) {
        $this->uploadPath = $uploadPath ?: dirname(dirname(__DIR__)) . '/secure-uploads/';
        $this->quarantinePath = dirname(dirname(__DIR__)) . '/quarantine/';
        $this->initializeDirectories();
    }
    
    /**
     * Initialize secure upload directories outside web root
     */
    private function initializeDirectories() {
        // Create secure upload directory outside web root
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0700, true);
            
            // Create .htaccess to deny direct access
            file_put_contents($this->uploadPath . '.htaccess', "Deny from all\n");
        }
        
        // Create quarantine directory for suspicious files
        if (!is_dir($this->quarantinePath)) {
            mkdir($this->quarantinePath, 0700, true);
            file_put_contents($this->quarantinePath . '.htaccess', "Deny from all\n");
        }
        
        // Create subdirectories
        $subdirs = ['kyc', 'facial', 'temp'];
        foreach ($subdirs as $subdir) {
            $path = $this->uploadPath . $subdir . '/';
            if (!is_dir($path)) {
                mkdir($path, 0700, true);
                file_put_contents($path . '.htaccess', "Deny from all\n");
            }
        }
    }
    
    /**
     * Validate and process file upload with comprehensive security checks
     */
    public function processUpload($file, $documentType, $userId) {
        try {
            // Step 1: Basic validation
            $this->validateBasicUpload($file);
            
            // Step 2: File type validation
            $this->validateFileType($file);
            
            // Step 3: Content validation
            $this->validateFileContent($file);
            
            // Step 4: Security scanning
            $this->scanForThreats($file);
            
            // Step 5: Generate secure filename and path
            $secureFilename = $this->generateSecureFilename($file, $documentType, $userId);
            $finalPath = $this->uploadPath . 'kyc/' . $secureFilename;
            
            // Step 6: Move file to secure location
            if (!move_uploaded_file($file['tmp_name'], $finalPath)) {
                throw new Exception('Failed to move uploaded file to secure location');
            }
            
            // Step 7: Set secure permissions
            chmod($finalPath, 0600);
            
            // Step 8: Log successful upload
            $this->logUploadEvent($userId, $documentType, $secureFilename, 'success');
            
            return [
                'success' => true,
                'filename' => $secureFilename,
                'path' => 'secure-uploads/kyc/' . $secureFilename,
                'size' => $file['size'],
                'mime_type' => $this->getValidatedMimeType($file)
            ];
            
        } catch (Exception $e) {
            // Quarantine suspicious files
            if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
                $this->quarantineFile($file, $e->getMessage());
            }
            
            // Log security event
            $this->logUploadEvent($userId, $documentType, $file['name'] ?? 'unknown', 'failed', $e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * Basic upload validation
     */
    private function validateBasicUpload($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File too large. Maximum size is ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
        
        if ($file['size'] == 0) {
            throw new Exception('Empty file not allowed');
        }
    }
    
    /**
     * Validate file type using multiple methods
     */
    private function validateFileType($file) {
        // Method 1: Check MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception('Invalid file type detected: ' . $mimeType);
        }
        
        // Method 2: Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Invalid file extension: ' . $extension);
        }
        
        // Method 3: Magic number validation
        $this->validateMagicNumbers($file['tmp_name'], $mimeType);
    }
    
    /**
     * Validate file magic numbers (file signatures)
     */
    private function validateMagicNumbers($filePath, $expectedMimeType) {
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 10);
        fclose($handle);
        
        $magicNumbers = [
            'image/jpeg' => ["\xFF\xD8\xFF"],
            'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
            'application/pdf' => ["%PDF-"]
        ];
        
        if (!isset($magicNumbers[$expectedMimeType])) {
            throw new Exception('Unsupported file type for magic number validation');
        }
        
        $validMagic = false;
        foreach ($magicNumbers[$expectedMimeType] as $magic) {
            if (strpos($header, $magic) === 0) {
                $validMagic = true;
                break;
            }
        }
        
        if (!$validMagic) {
            throw new Exception('File magic number does not match expected type');
        }
    }
    
    /**
     * Validate file content for malicious code
     */
    private function validateFileContent($file) {
        $content = file_get_contents($file['tmp_name']);
        
        // Check for embedded scripts and malicious patterns
        $maliciousPatterns = [
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/<?php/i',
            '/<\?=/i',
            '/<%/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new Exception('Malicious content detected in file');
            }
        }
        
        // Check for suspicious binary patterns
        if ($this->containsSuspiciousBinary($content)) {
            throw new Exception('Suspicious binary content detected');
        }
    }
    
    /**
     * Check for suspicious binary patterns
     */
    private function containsSuspiciousBinary($content) {
        // Check for executable signatures
        $executableSignatures = [
            "\x4D\x5A", // PE executable
            "\x7F\x45\x4C\x46", // ELF executable
            "\xFE\xED\xFA\xCE", // Mach-O executable
            "\xCE\xFA\xED\xFE", // Mach-O executable (reverse)
        ];
        
        foreach ($executableSignatures as $signature) {
            if (strpos($content, $signature) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Scan file for threats with enhanced detection
     */
    private function scanForThreats($file) {
        $fileSize = filesize($file['tmp_name']);
        $content = file_get_contents($file['tmp_name']);

        // 1. Comprehensive virus scanning with multiple engines
        $virusScanner = VirusScanner::getInstance();
        $scanResult = $virusScanner->scanFile($file['tmp_name'], $file['name'] ?? 'unknown');

        if (!$scanResult['clean']) {
            $threats = implode(', ', $scanResult['threats_found']);
            throw new Exception("Virus/malware detected: $threats");
        }

        // 2. Check for polyglot files (files that are valid in multiple formats)
        if ($this->isPolyglotFile($content)) {
            throw new Exception('Polyglot file detected - potential security risk');
        }

        // 3. Check for steganography indicators
        if ($this->hasSteganographyIndicators($content, $fileSize)) {
            throw new Exception('File may contain hidden data');
        }

        // 4. Advanced malware patterns (additional to virus scanner)
        $this->scanAdvancedMalwarePatterns($content);

        // 5. Check for suspicious file structure
        $this->validateFileStructure($file['tmp_name'], $content);

        // 6. Entropy analysis for packed/encrypted content
        if ($this->hasHighEntropy($content)) {
            throw new Exception('File contains suspicious high-entropy data');
        }

        // Log successful scan
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'threat_scan_completed', SecurityLogger::LEVEL_INFO,
            'File threat scan completed successfully', [
                'file_name' => $file['name'] ?? 'unknown',
                'file_size' => $fileSize,
                'scan_time_ms' => $scanResult['scan_time'],
                'engines_used' => $scanResult['scan_engines']
            ]);
    }
    
    /**
     * Check for polyglot files
     */
    private function isPolyglotFile($content) {
        $formats = [
            'pdf' => '%PDF-',
            'jpeg' => "\xFF\xD8\xFF",
            'png' => "\x89\x50\x4E\x47",
            'html' => '<html',
            'zip' => 'PK'
        ];
        
        $detectedFormats = 0;
        foreach ($formats as $format => $signature) {
            if (strpos($content, $signature) !== false) {
                $detectedFormats++;
            }
        }
        
        return $detectedFormats > 1;
    }
    
    /**
     * Check for steganography indicators
     */
    private function hasSteganographyIndicators($content, $fileSize) {
        // Basic entropy check - high entropy might indicate hidden data
        $entropy = $this->calculateEntropy($content);
        
        // Suspicious if entropy is too high for the file type
        return $entropy > 7.5;
    }
    
    /**
     * Calculate Shannon entropy
     */
    private function calculateEntropy($data) {
        $frequencies = array_count_values(str_split($data));
        $length = strlen($data);
        $entropy = 0;
        
        foreach ($frequencies as $frequency) {
            $probability = $frequency / $length;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }

    /**
     * ClamAV virus scanning integration
     */
    private function scanWithClamAV($filePath) {
        // Check if ClamAV is available
        if (!function_exists('exec')) {
            return; // Skip if exec is disabled
        }

        // Try to scan with ClamAV
        $output = [];
        $returnCode = 0;

        // Use clamscan if available
        exec("which clamscan 2>/dev/null", $output, $returnCode);
        if ($returnCode === 0) {
            $output = [];
            exec("clamscan --no-summary --infected " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                // Virus found or error occurred
                $scanResult = implode("\n", $output);
                if (strpos($scanResult, 'FOUND') !== false) {
                    throw new Exception('Virus detected by ClamAV: ' . $scanResult);
                }
            }
        }
    }

    /**
     * Advanced malware pattern detection
     */
    private function scanAdvancedMalwarePatterns($content) {
        $advancedPatterns = [
            // Suspicious binary patterns
            '/\x4D\x5A.{58}\x50\x45\x00\x00/', // PE executable header
            '/\x7F\x45\x4C\x46/', // ELF executable header
            '/\xCA\xFE\xBA\xBE/', // Java class file
            '/\xFE\xED\xFA\xCE/', // Mach-O binary

            // Suspicious script patterns
            '/powershell\s+-e[nc]*\s+[A-Za-z0-9+\/=]+/i', // PowerShell encoded commands
            '/cmd\.exe\s+\/c\s+/i', // Windows command execution
            '/bash\s+-c\s+/i', // Bash command execution
            '/wget\s+http/i', // Download commands
            '/curl\s+http/i', // Download commands

            // Obfuscation patterns
            '/[A-Za-z0-9+\/]{100,}={0,2}/', // Long base64 strings
            '/\\x[0-9a-fA-F]{2}/', // Hex encoded strings
            '/chr\(\d+\)/', // Character encoding
            '/String\.fromCharCode\(/i', // JavaScript character encoding

            // Suspicious URLs and domains
            '/https?:\/\/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', // IP addresses
            '/\.tk\/|\.ml\/|\.ga\/|\.cf\//i', // Suspicious TLDs
            '/bit\.ly\/|tinyurl\.com\/|t\.co\//i', // URL shorteners
        ];

        foreach ($advancedPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new Exception('Advanced malware pattern detected');
            }
        }
    }

    /**
     * Validate file structure integrity
     */
    private function validateFileStructure($filePath, $content) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        switch ($mimeType) {
            case 'application/pdf':
                $this->validatePDFStructure($content);
                break;
            case 'image/jpeg':
                $this->validateJPEGStructure($content);
                break;
            case 'image/png':
                $this->validatePNGStructure($content);
                break;
        }
    }

    /**
     * Calculate file entropy
     */
    private function hasHighEntropy($content) {
        $entropy = $this->calculateEntropy($content);

        // High entropy (> 7.5) might indicate compressed/encrypted/packed content
        return $entropy > 7.5;
    }

    /**
     * Validate PDF structure
     */
    private function validatePDFStructure($content) {
        // Check for PDF header
        if (strpos($content, '%PDF-') !== 0) {
            throw new Exception('Invalid PDF structure - missing header');
        }

        // Check for suspicious PDF elements
        $suspiciousElements = [
            '/JavaScript/i',
            '/JS/i',
            '/OpenAction/i',
            '/Launch/i',
            '/EmbeddedFile/i',
            '/XFA/i'
        ];

        foreach ($suspiciousElements as $element) {
            if (preg_match($element, $content)) {
                throw new Exception('PDF contains suspicious elements');
            }
        }
    }

    /**
     * Validate JPEG structure
     */
    private function validateJPEGStructure($content) {
        // Check for JPEG header
        if (substr($content, 0, 2) !== "\xFF\xD8") {
            throw new Exception('Invalid JPEG structure - missing header');
        }

        // Check for JPEG footer
        if (substr($content, -2) !== "\xFF\xD9") {
            throw new Exception('Invalid JPEG structure - missing footer');
        }

        // Check for suspicious EXIF data
        if (strpos($content, 'eval(') !== false || strpos($content, '<script') !== false) {
            throw new Exception('JPEG contains suspicious EXIF data');
        }
    }

    /**
     * Validate PNG structure
     */
    private function validatePNGStructure($content) {
        // Check for PNG header
        if (substr($content, 0, 8) !== "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            throw new Exception('Invalid PNG structure - missing header');
        }

        // Check for PNG footer
        if (substr($content, -8) !== "\x00\x00\x00\x00\x49\x45\x4E\x44\xAE\x42\x60\x82") {
            throw new Exception('Invalid PNG structure - missing footer');
        }
    }

    /**
     * Generate secure filename
     */
    private function generateSecureFilename($file, $documentType, $userId) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $hash = hash('sha256', $userId . $documentType . time() . random_bytes(16));
        return $userId . '_' . $documentType . '_' . substr($hash, 0, 16) . '.' . $extension;
    }
    
    /**
     * Get validated MIME type
     */
    private function getValidatedMimeType($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        return $mimeType;
    }
    
    /**
     * Quarantine suspicious file
     */
    private function quarantineFile($file, $reason) {
        $quarantineFilename = 'quarantine_' . time() . '_' . hash('md5', $file['name']);
        $quarantinePath = $this->quarantinePath . $quarantineFilename;
        
        if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
            move_uploaded_file($file['tmp_name'], $quarantinePath);
            chmod($quarantinePath, 0600);
            
            // Log quarantine event
            logFileUploadEvent('file_quarantined', SecurityLogger::LEVEL_CRITICAL,
            "Malicious file quarantined",
            ['reason' => $reason, 'quarantine_file' => $quarantineFilename]);
        }
    }
    
    /**
     * Log upload events
     */
    private function logUploadEvent($userId, $documentType, $filename, $status, $error = null) {
        $logData = [
            'user_id' => $userId,
            'document_type' => $documentType,
            'filename' => $filename,
            'status' => $status
        ];

        if ($error) {
            $logData['error'] = $error;
        }

        if ($status === 'failed') {
            logFileUploadEvent('upload_failed', SecurityLogger::LEVEL_WARNING,
                "File upload failed", $logData, $userId);
        } else {
            logFileUploadEvent('upload_success', SecurityLogger::LEVEL_INFO,
                "File upload successful", $logData, $userId);
        }
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Serve file securely (for viewing documents)
     */
    public function serveFile($filename, $userId = null) {
        $filePath = $this->uploadPath . 'kyc/' . $filename;
        
        // Validate file exists and is within allowed directory
        if (!file_exists($filePath) || !$this->isPathSafe($filePath)) {
            throw new Exception('File not found or access denied');
        }
        
        // Additional access control can be added here
        // For example, check if user owns the file
        
        $mimeType = mime_content_type($filePath);
        $fileSize = filesize($filePath);
        
        // Set security headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        
        readfile($filePath);
    }
    
    /**
     * Check if path is safe (prevent directory traversal)
     */
    private function isPathSafe($path) {
        $realPath = realpath($path);
        $allowedPath = realpath($this->uploadPath);
        
        return $realPath && $allowedPath && strpos($realPath, $allowedPath) === 0;
    }
}
?>
