<?php

class CertificateGenerator {
    private $db;
    private $outputDir;
    
    public function __construct($database) {
        $this->db = $database;
        $this->outputDir = __DIR__ . '/../../assets/certificates/';
        
        // Create output directory if it doesn't exist
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function generateCertificate($certificateId) {
        try {
            // Get certificate details
            $certificate = $this->getCertificateDetails($certificateId);
            if (!$certificate) {
                throw new Exception("Certificate not found");
            }
            
            // Update status to generating
            $this->updateCertificateStatus($certificateId, 'generating');
            
            // Get template details
            $template = $this->getTemplateDetails($certificate['template_id']);
            if (!$template) {
                throw new Exception("Template not found");
            }
            
            // Generate certificate image
            $imagePath = $this->createCertificateImage($certificate, $template);
            
            // Generate PDF if needed
            $pdfPath = $this->createCertificatePDF($certificate, $template, $imagePath);
            
            // Update certificate with file paths
            $this->updateCertificateFiles($certificateId, $imagePath, $pdfPath);
            
            // Update status to completed
            $this->updateCertificateStatus($certificateId, 'completed');
            
            return [
                'success' => true,
                'certificate_id' => $certificateId,
                'image_path' => $imagePath,
                'pdf_path' => $pdfPath
            ];
            
        } catch (Exception $e) {
            // Update status to failed with error
            $this->updateCertificateStatus($certificateId, 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    private function getCertificateDetails($certificateId) {
        $query = "SELECT 
            sc.*,
            ai.package_name,
            ai.amount as investment_amount,
            ai.created_at as investment_date,
            u.username,
            u.email,
            u.full_name,
            up.phone,
            up.country
        FROM share_certificates sc
        LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
        LEFT JOIN users u ON sc.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE sc.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$certificateId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getTemplateDetails($templateId) {
        $query = "SELECT * FROM certificate_templates WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template && $template['template_config']) {
            $template['template_config'] = json_decode($template['template_config'], true);
        }
        
        return $template;
    }
    
    private function createCertificateImage($certificate, $template) {
        // Load background image
        $backgroundPath = __DIR__ . '/../../' . $template['background_image_path'];
        if (!file_exists($backgroundPath)) {
            throw new Exception("Background image not found: " . $backgroundPath);
        }
        
        $background = $this->loadImage($backgroundPath);
        if (!$background) {
            throw new Exception("Failed to load background image");
        }
        
        $width = imagesx($background);
        $height = imagesy($background);
        
        // Create final image
        $finalImage = imagecreatetruecolor($width, $height);
        imagecopy($finalImage, $background, 0, 0, 0, 0, $width, $height);
        
        // Load frame image if exists
        if ($template['frame_image_path']) {
            $framePath = __DIR__ . '/../../' . $template['frame_image_path'];
            if (file_exists($framePath)) {
                $frame = $this->loadImage($framePath);
                if ($frame) {
                    imagecopy($finalImage, $frame, 0, 0, 0, 0, imagesx($frame), imagesy($frame));
                    imagedestroy($frame);
                }
            }
        }
        
        // Add text elements
        $this->addTextToCertificate($finalImage, $certificate, $template);
        
        // Generate QR code and add it
        $this->addQRCode($finalImage, $certificate, $template);
        
        // Save image
        $filename = 'certificate_' . $certificate['certificate_number'] . '_' . time() . '.png';
        $outputPath = $this->outputDir . $filename;
        
        if (!imagepng($finalImage, $outputPath)) {
            throw new Exception("Failed to save certificate image");
        }
        
        // Clean up
        imagedestroy($background);
        imagedestroy($finalImage);
        
        return 'assets/certificates/' . $filename;
    }
    
    private function loadImage($path) {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    private function addTextToCertificate($image, $certificate, $template) {
        $config = $template['template_config'] ?? [];
        
        // Default text configuration
        $defaultConfig = [
            'certificate_number' => ['x' => 100, 'y' => 100, 'size' => 16, 'color' => [0, 0, 0]],
            'holder_name' => ['x' => 400, 'y' => 300, 'size' => 24, 'color' => [0, 0, 0]],
            'share_quantity' => ['x' => 300, 'y' => 400, 'size' => 18, 'color' => [0, 0, 0]],
            'certificate_value' => ['x' => 500, 'y' => 400, 'size' => 18, 'color' => [0, 0, 0]],
            'issue_date' => ['x' => 400, 'y' => 500, 'size' => 14, 'color' => [0, 0, 0]],
            'package_name' => ['x' => 400, 'y' => 350, 'size' => 16, 'color' => [0, 0, 0]]
        ];
        
        // Merge with template config
        $textConfig = array_merge($defaultConfig, $config['text'] ?? []);
        
        // Prepare text data
        $textData = [
            'certificate_number' => $certificate['certificate_number'],
            'holder_name' => $certificate['full_name'] ?: $certificate['username'],
            'share_quantity' => number_format($certificate['share_quantity']),
            'certificate_value' => '$' . number_format($certificate['certificate_value'], 2),
            'issue_date' => date('F j, Y', strtotime($certificate['issue_date'])),
            'package_name' => $certificate['package_name']
        ];
        
        // Add text to image
        foreach ($textData as $key => $text) {
            if (isset($textConfig[$key]) && $text) {
                $this->addText($image, $text, $textConfig[$key]);
            }
        }
    }
    
    private function addText($image, $text, $config) {
        $color = imagecolorallocate($image, $config['color'][0], $config['color'][1], $config['color'][2]);
        
        // Use built-in font for now (can be enhanced with TTF fonts)
        $fontSize = min(5, max(1, intval($config['size'] / 4))); // Convert to built-in font size
        
        imagestring($image, $fontSize, $config['x'], $config['y'], $text, $color);
    }
    
    private function addQRCode($image, $certificate, $template) {
        // Simple QR code placeholder - in production, use a proper QR code library
        $qrData = "https://aureusangels.com/verify/" . $certificate['verification_hash'];
        
        // For now, just add the verification text
        $color = imagecolorallocate($image, 0, 0, 0);
        $qrConfig = $template['template_config']['qr_code'] ?? ['x' => 50, 'y' => 550];
        
        imagestring($image, 2, $qrConfig['x'], $qrConfig['y'], "Verify: " . substr($certificate['verification_hash'], 0, 16), $color);
    }
    
    private function createCertificatePDF($certificate, $template, $imagePath) {
        // PDF generation would require a library like TCPDF or FPDF
        // For now, return null - can be implemented later
        return null;
    }
    
    private function updateCertificateStatus($certificateId, $status, $error = null) {
        $query = "UPDATE share_certificates SET generation_status = ?, generation_error = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$status, $error, $certificateId]);
    }
    
    private function updateCertificateFiles($certificateId, $imagePath, $pdfPath) {
        $query = "UPDATE share_certificates SET certificate_image_path = ?, certificate_pdf_path = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$imagePath, $pdfPath, $certificateId]);
    }
}
?>
