<?php
/**
 * VIRUS SCANNING SERVICE
 * Integrates with multiple antivirus engines for comprehensive file scanning
 */

require_once 'security-logger.php';

class VirusScanner {
    private static $instance = null;
    private $config;
    
    private function __construct() {
        $this->config = [
            'clamav_enabled' => true,
            'virustotal_enabled' => false, // Requires API key
            'virustotal_api_key' => $_ENV['VIRUSTOTAL_API_KEY'] ?? '',
            'timeout' => 30,
            'quarantine_on_detection' => true
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Scan file with all available engines
     */
    public function scanFile($filePath, $originalName = '') {
        $results = [
            'clean' => true,
            'threats_found' => [],
            'scan_engines' => [],
            'scan_time' => 0
        ];
        
        $startTime = microtime(true);
        
        try {
            // 1. ClamAV scanning
            if ($this->config['clamav_enabled']) {
                $clamavResult = $this->scanWithClamAV($filePath);
                $results['scan_engines'][] = 'clamav';
                
                if (!$clamavResult['clean']) {
                    $results['clean'] = false;
                    $results['threats_found'] = array_merge($results['threats_found'], $clamavResult['threats']);
                }
            }
            
            // 2. VirusTotal scanning (if enabled and API key available)
            if ($this->config['virustotal_enabled'] && !empty($this->config['virustotal_api_key'])) {
                $vtResult = $this->scanWithVirusTotal($filePath);
                $results['scan_engines'][] = 'virustotal';
                
                if (!$vtResult['clean']) {
                    $results['clean'] = false;
                    $results['threats_found'] = array_merge($results['threats_found'], $vtResult['threats']);
                }
            }
            
            // 3. Custom signature scanning
            $customResult = $this->scanWithCustomSignatures($filePath);
            $results['scan_engines'][] = 'custom_signatures';
            
            if (!$customResult['clean']) {
                $results['clean'] = false;
                $results['threats_found'] = array_merge($results['threats_found'], $customResult['threats']);
            }
            
            // 4. Behavioral analysis
            $behaviorResult = $this->performBehavioralAnalysis($filePath);
            $results['scan_engines'][] = 'behavioral_analysis';
            
            if (!$behaviorResult['clean']) {
                $results['clean'] = false;
                $results['threats_found'] = array_merge($results['threats_found'], $behaviorResult['threats']);
            }
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'virus_scan_error', SecurityLogger::LEVEL_CRITICAL,
                'Virus scanning failed', ['error' => $e->getMessage(), 'file' => $originalName]);
            
            // On scan error, assume file is suspicious
            $results['clean'] = false;
            $results['threats_found'][] = 'Scan error: ' . $e->getMessage();
        }
        
        $results['scan_time'] = round((microtime(true) - $startTime) * 1000, 2); // milliseconds
        
        // Log scan results
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'virus_scan_completed', SecurityLogger::LEVEL_INFO,
            'File virus scan completed', [
                'file' => $originalName,
                'clean' => $results['clean'],
                'threats_count' => count($results['threats_found']),
                'scan_time_ms' => $results['scan_time'],
                'engines_used' => $results['scan_engines']
            ]);
        
        return $results;
    }
    
    /**
     * ClamAV scanning
     */
    private function scanWithClamAV($filePath) {
        $result = ['clean' => true, 'threats' => []];
        
        if (!function_exists('exec')) {
            return $result; // Skip if exec is disabled
        }
        
        // Check if ClamAV is available
        $output = [];
        $returnCode = 0;
        exec("which clamscan 2>/dev/null", $output, $returnCode);
        
        if ($returnCode !== 0) {
            // ClamAV not available
            return $result;
        }
        
        // Perform scan
        $output = [];
        $command = "timeout {$this->config['timeout']} clamscan --no-summary --infected " . escapeshellarg($filePath) . " 2>&1";
        exec($command, $output, $returnCode);
        
        $scanOutput = implode("\n", $output);
        
        if ($returnCode === 1) {
            // Virus found
            $result['clean'] = false;
            
            // Extract threat names
            foreach ($output as $line) {
                if (strpos($line, 'FOUND') !== false) {
                    preg_match('/: (.+) FOUND/', $line, $matches);
                    if (isset($matches[1])) {
                        $result['threats'][] = 'ClamAV: ' . $matches[1];
                    }
                }
            }
        } elseif ($returnCode === 2) {
            // Error occurred
            throw new Exception('ClamAV scan error: ' . $scanOutput);
        }
        
        return $result;
    }
    
    /**
     * VirusTotal scanning
     */
    private function scanWithVirusTotal($filePath) {
        $result = ['clean' => true, 'threats' => []];
        
        if (empty($this->config['virustotal_api_key'])) {
            return $result;
        }
        
        try {
            $fileHash = hash_file('sha256', $filePath);
            
            // Check if file is already scanned
            $reportUrl = "https://www.virustotal.com/vtapi/v2/file/report";
            $reportData = [
                'apikey' => $this->config['virustotal_api_key'],
                'resource' => $fileHash
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($reportData),
                    'timeout' => $this->config['timeout']
                ]
            ]);
            
            $response = file_get_contents($reportUrl, false, $context);
            $reportResult = json_decode($response, true);
            
            if ($reportResult && $reportResult['response_code'] === 1) {
                // File found in VirusTotal database
                if ($reportResult['positives'] > 0) {
                    $result['clean'] = false;
                    $result['threats'][] = "VirusTotal: {$reportResult['positives']}/{$reportResult['total']} engines detected threats";
                }
            } else {
                // File not in database, upload for scanning
                $this->uploadToVirusTotal($filePath);
            }
            
        } catch (Exception $e) {
            // VirusTotal error - don't fail the entire scan
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'virustotal_error', SecurityLogger::LEVEL_WARNING,
                'VirusTotal scan failed', ['error' => $e->getMessage()]);
        }
        
        return $result;
    }
    
    /**
     * Custom signature scanning
     */
    private function scanWithCustomSignatures($filePath) {
        $result = ['clean' => true, 'threats' => []];
        
        $content = file_get_contents($filePath);
        
        // Known malware signatures (simplified examples)
        $signatures = [
            'eicar_test' => '58354F2150254041505B345C50585D54283750295E4348244D4C',
            'suspicious_js' => '/eval\s*\(\s*unescape\s*\(/i',
            'suspicious_php' => '/eval\s*\(\s*base64_decode\s*\(/i',
            'suspicious_powershell' => '/powershell\s+-e[nc]*\s+[A-Za-z0-9+\/=]{50,}/i',
            'suspicious_vbs' => '/CreateObject\s*\(\s*["\']WScript\.Shell["\']\s*\)/i'
        ];
        
        foreach ($signatures as $name => $signature) {
            if (is_string($signature)) {
                // Hex signature
                if (strpos(bin2hex($content), $signature) !== false) {
                    $result['clean'] = false;
                    $result['threats'][] = "Custom signature: $name";
                }
            } else {
                // Regex signature
                if (preg_match($signature, $content)) {
                    $result['clean'] = false;
                    $result['threats'][] = "Custom signature: $name";
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Behavioral analysis
     */
    private function performBehavioralAnalysis($filePath) {
        $result = ['clean' => true, 'threats' => []];
        
        $content = file_get_contents($filePath);
        $fileSize = filesize($filePath);
        
        // 1. Entropy analysis
        $entropy = $this->calculateEntropy($content);
        if ($entropy > 7.8) {
            $result['threats'][] = 'High entropy detected (possible packer/encryption)';
        }
        
        // 2. Suspicious string analysis
        $suspiciousStrings = [
            'cmd.exe', 'powershell.exe', 'wscript.exe', 'cscript.exe',
            'regsvr32', 'rundll32', 'mshta.exe', 'bitsadmin',
            'certutil', 'schtasks', 'at.exe', 'reg.exe'
        ];
        
        $suspiciousCount = 0;
        foreach ($suspiciousStrings as $string) {
            if (stripos($content, $string) !== false) {
                $suspiciousCount++;
            }
        }
        
        if ($suspiciousCount >= 3) {
            $result['threats'][] = 'Multiple suspicious system commands detected';
        }
        
        // 3. URL analysis
        $urlPattern = '/https?:\/\/[^\s\'"<>]+/i';
        preg_match_all($urlPattern, $content, $urls);
        
        if (count($urls[0]) > 10) {
            $result['threats'][] = 'Excessive URL references detected';
        }
        
        // 4. Base64 analysis
        $base64Pattern = '/[A-Za-z0-9+\/]{100,}={0,2}/';
        preg_match_all($base64Pattern, $content, $base64Strings);
        
        if (count($base64Strings[0]) > 5) {
            $result['threats'][] = 'Multiple large base64 strings detected';
        }
        
        if (!empty($result['threats'])) {
            $result['clean'] = false;
        }
        
        return $result;
    }
    
    /**
     * Calculate entropy of content
     */
    private function calculateEntropy($content) {
        $frequencies = array_count_values(str_split($content));
        $length = strlen($content);
        $entropy = 0;
        
        foreach ($frequencies as $frequency) {
            $probability = $frequency / $length;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }
    
    /**
     * Upload file to VirusTotal for scanning
     */
    private function uploadToVirusTotal($filePath) {
        if (empty($this->config['virustotal_api_key'])) {
            return;
        }
        
        try {
            $uploadUrl = "https://www.virustotal.com/vtapi/v2/file/scan";
            
            $postData = [
                'apikey' => $this->config['virustotal_api_key']
            ];
            
            $boundary = '----WebKitFormBoundary' . uniqid();
            $data = '';
            
            foreach ($postData as $key => $value) {
                $data .= "--$boundary\r\n";
                $data .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
                $data .= "$value\r\n";
            }
            
            $data .= "--$boundary\r\n";
            $data .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($filePath) . "\"\r\n";
            $data .= "Content-Type: application/octet-stream\r\n\r\n";
            $data .= file_get_contents($filePath) . "\r\n";
            $data .= "--$boundary--\r\n";
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: multipart/form-data; boundary=$boundary",
                    'content' => $data,
                    'timeout' => $this->config['timeout']
                ]
            ]);
            
            $response = file_get_contents($uploadUrl, false, $context);
            $result = json_decode($response, true);
            
            if ($result && isset($result['scan_id'])) {
                logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'virustotal_upload', SecurityLogger::LEVEL_INFO,
                    'File uploaded to VirusTotal', ['scan_id' => $result['scan_id']]);
            }
            
        } catch (Exception $e) {
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'virustotal_upload_error', SecurityLogger::LEVEL_WARNING,
                'VirusTotal upload failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get scan statistics
     */
    public function getScanStatistics($days = 7) {
        // This would query the security logs for scan statistics
        // Implementation depends on your logging system
        
        return [
            'total_scans' => 0,
            'clean_files' => 0,
            'threats_detected' => 0,
            'avg_scan_time' => 0,
            'engines_used' => $this->config
        ];
    }
}

// Convenience function
function scanFileForViruses($filePath, $originalName = '') {
    $scanner = VirusScanner::getInstance();
    return $scanner->scanFile($filePath, $originalName);
}
?>
