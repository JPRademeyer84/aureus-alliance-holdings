<?php
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, ensure the translation_issues table exists
    $createIssuesTable = "CREATE TABLE IF NOT EXISTS translation_issues (
        id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
        key_id INT NOT NULL,
        language_id INT NOT NULL,
        issue_type VARCHAR(100) NOT NULL,
        issue_description TEXT NOT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        is_resolved BOOLEAN DEFAULT FALSE,
        resolved_at TIMESTAMP NULL,
        resolved_by VARCHAR(36) NULL,
        auto_detected BOOLEAN DEFAULT TRUE,
        verification_run_id VARCHAR(36) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_issue (key_id, language_id, issue_type),
        INDEX idx_key_language (key_id, language_id),
        INDEX idx_is_resolved (is_resolved),
        INDEX idx_issue_type (issue_type),
        INDEX idx_severity (severity),
        INDEX idx_verification_run (verification_run_id)
    )";
    $db->exec($createIssuesTable);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $key_id = (int)($input['key_id'] ?? 0);
    $language_id = (int)($input['language_id'] ?? 0);
    $verification_run_id = $input['verification_run_id'] ?? uniqid('verify_', true);
    
    if ($key_id <= 0 || $language_id <= 0) {
        throw new Exception('Valid key ID and language ID are required');
    }
    
    // Get the translation with key and language info
    $query = "SELECT t.translation_text, t.is_approved, tk.key_name, tk.category, l.name as language_name, l.code as language_code
              FROM translations t
              JOIN translation_keys tk ON t.key_id = tk.id
              JOIN languages l ON t.language_id = l.id
              WHERE t.key_id = ? AND t.language_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$key_id, $language_id]);
    $translation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$translation) {
        throw new Exception('Translation not found');
    }
    
    // Check if this translation was already verified recently (within last hour)
    $recentCheckQuery = "SELECT COUNT(*) as count FROM translation_issues 
                        WHERE key_id = ? AND language_id = ? 
                        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $recentCheckStmt = $db->prepare($recentCheckQuery);
    $recentCheckStmt->execute([$key_id, $language_id]);
    $recentCheck = $recentCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    // If recently checked and no new issues, return cached results
    if ($recentCheck['count'] > 0) {
        $existingIssuesQuery = "SELECT issue_type, issue_description, severity, is_resolved 
                               FROM translation_issues 
                               WHERE key_id = ? AND language_id = ? AND is_resolved = FALSE
                               ORDER BY severity DESC, created_at DESC";
        $existingIssuesStmt = $db->prepare($existingIssuesQuery);
        $existingIssuesStmt->execute([$key_id, $language_id]);
        $existingIssues = $existingIssuesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'is_approved' => count($existingIssues) === 0,
            'issues' => array_map(function($issue) {
                return $issue['issue_description'];
            }, $existingIssues),
            'issues_count' => count($existingIssues),
            'translation' => $translation,
            'verification_completed' => true,
            'cached_result' => true,
            'message' => 'Using cached verification results from recent check'
        ]);
        exit;
    }
    
    // Clear existing unresolved issues for this translation (we'll re-evaluate)
    $clearIssuesQuery = "UPDATE translation_issues 
                        SET is_resolved = TRUE, resolved_at = NOW(), resolved_by = 'system_reverify'
                        WHERE key_id = ? AND language_id = ? AND is_resolved = FALSE";
    $clearIssuesStmt = $db->prepare($clearIssuesQuery);
    $clearIssuesStmt->execute([$key_id, $language_id]);
    
    // Perform comprehensive verification
    $issues = [];
    $text = trim($translation['translation_text']);
    $keyName = $translation['key_name'];
    $languageCode = $translation['language_code'];
    
    // Critical Issues (High Priority)
    
    // Check if translation is empty
    if (empty($text)) {
        $issues[] = [
            'type' => 'empty_translation',
            'description' => 'Translation is empty or contains only whitespace',
            'severity' => 'critical'
        ];
    }
    
    // Check for security issues
    if (preg_match('/<script|<iframe|javascript:|data:|vbscript:|onload=|onerror=/i', $text)) {
        $issues[] = [
            'type' => 'security_risk',
            'description' => 'Translation contains potentially unsafe content (scripts, iframes, or event handlers)',
            'severity' => 'critical'
        ];
    }
    
    // High Priority Issues
    
    // Check if translation is identical to key name (likely untranslated)
    if (strcasecmp($text, $keyName) === 0) {
        $issues[] = [
            'type' => 'untranslated',
            'description' => 'Translation appears to be identical to the key name - likely not translated',
            'severity' => 'high'
        ];
    }
    
    // Check for placeholder text
    $placeholders = ['TODO', 'TRANSLATE', 'PLACEHOLDER', 'XXX', 'TBD', 'FIXME', 'TEMP', 'TEST'];
    foreach ($placeholders as $placeholder) {
        if (stripos($text, $placeholder) !== false) {
            $issues[] = [
                'type' => 'placeholder_text',
                'description' => "Translation contains placeholder text: '$placeholder'",
                'severity' => 'high'
            ];
            break; // Only report one placeholder issue
        }
    }
    
    // Medium Priority Issues
    
    // Check if translation is too short (less than 2 characters for most keys)
    if (strlen($text) < 2 && !in_array($keyName, ['ok', 'no', 'go', 'hi'])) {
        $issues[] = [
            'type' => 'too_short',
            'description' => 'Translation is unusually short (less than 2 characters)',
            'severity' => 'medium'
        ];
    }
    
    // Check if translation is excessively long (over 500 characters)
    if (strlen($text) > 500) {
        $issues[] = [
            'type' => 'too_long',
            'description' => 'Translation is unusually long (' . strlen($text) . ' characters) - may contain extra content',
            'severity' => 'medium'
        ];
    }
    
    // Check for proper localization (non-English languages)
    if ($languageCode !== 'en') {
        // Check if translation appears to be in English for non-English languages
        $commonEnglishWords = [
            'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
            'hello', 'welcome', 'login', 'logout', 'home', 'about', 'contact', 'save', 
            'cancel', 'submit', 'yes', 'no', 'edit', 'delete', 'view', 'close', 'back',
            'next', 'previous', 'search', 'filter', 'loading', 'success', 'error'
        ];
        
        $lowerText = strtolower($text);
        $englishWordCount = 0;
        $totalWords = str_word_count($text);
        
        if ($totalWords > 0) {
            foreach ($commonEnglishWords as $englishWord) {
                if (strpos($lowerText, $englishWord) !== false) {
                    $englishWordCount++;
                }
            }
            
            // If more than 50% of words appear to be English, flag it
            if ($englishWordCount / $totalWords > 0.5 && $totalWords > 1) {
                $issues[] = [
                    'type' => 'not_localized',
                    'description' => 'Translation may not be properly localized - appears to contain English text',
                    'severity' => 'medium'
                ];
            }
        }
        
        // Check for English-only characters in languages that use different scripts
        $scriptsRequiringNonLatin = ['ar', 'zh', 'ja', 'ko', 'ru', 'hi', 'th', 'he'];
        if (in_array($languageCode, $scriptsRequiringNonLatin)) {
            if (preg_match('/^[a-zA-Z0-9\s\.,!?\-_()]+$/', $text) && strlen($text) > 5) {
                $issues[] = [
                    'type' => 'wrong_script',
                    'description' => 'Translation uses Latin script but target language typically uses a different writing system',
                    'severity' => 'medium'
                ];
            }
        }
    }
    
    // Low Priority Issues
    
    // Check for repeated characters (like "aaaa" or "!!!!")
    if (preg_match('/(.)\1{4,}/', $text)) {
        $issues[] = [
            'type' => 'repeated_characters',
            'description' => 'Translation contains repeated characters that may indicate an error',
            'severity' => 'low'
        ];
    }
    
    // Check for mixed case issues (like "hELLo WoRLD")
    if (preg_match('/[a-z][A-Z][a-z]|[A-Z][a-z][A-Z]/', $text)) {
        $issues[] = [
            'type' => 'mixed_case',
            'description' => 'Translation has unusual mixed case pattern',
            'severity' => 'low'
        ];
    }
    
    // Insert new issues into the database
    if (!empty($issues)) {
        $insertIssueQuery = "INSERT INTO translation_issues (key_id, language_id, issue_type, issue_description, severity, verification_run_id) VALUES (?, ?, ?, ?, ?, ?)";
        $insertIssueStmt = $db->prepare($insertIssueQuery);
        
        foreach ($issues as $issue) {
            $insertIssueStmt->execute([
                $key_id, 
                $language_id, 
                $issue['type'], 
                $issue['description'], 
                $issue['severity'],
                $verification_run_id
            ]);
        }
    }
    
    // Update verification status in translations table
    $isApproved = count($issues) === 0;
    $updateQuery = "UPDATE translations SET is_approved = ?, updated_at = CURRENT_TIMESTAMP WHERE key_id = ? AND language_id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$isApproved, $key_id, $language_id]);
    
    // Get issue descriptions for response
    $issueDescriptions = array_map(function($issue) {
        return $issue['description'];
    }, $issues);
    
    // Get severity counts
    $severityCounts = [
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    foreach ($issues as $issue) {
        $severityCounts[$issue['severity']]++;
    }
    
    echo json_encode([
        'success' => true,
        'is_approved' => $isApproved,
        'issues' => $issueDescriptions,
        'issues_count' => count($issues),
        'severity_counts' => $severityCounts,
        'translation' => $translation,
        'verification_completed' => true,
        'verification_run_id' => $verification_run_id,
        'cached_result' => false,
        'message' => count($issues) === 0 ? 'Translation passed all verification checks' : 'Issues found and recorded in database'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>
