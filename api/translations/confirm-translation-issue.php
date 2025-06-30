<?php
// Confirm/Override translation issue for proper nouns, program names, etc.
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['key_id']) || !isset($input['language_id'])) {
        throw new Exception('Missing required parameters: key_id and language_id');
    }
    
    $keyId = (int)$input['key_id'];
    $languageId = (int)$input['language_id'];
    $overrideReason = $input['override_reason'] ?? 'Manual confirmation - translation approved';
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // 1. Check if translation_issues table exists and has the required columns
        $checkIssuesTableQuery = "SHOW TABLES LIKE 'translation_issues'";
        $checkIssuesTableStmt = $db->prepare($checkIssuesTableQuery);
        $checkIssuesTableStmt->execute();

        $resolvedIssuesCount = 0;

        if ($checkIssuesTableStmt->rowCount() > 0) {
            // Check if resolution_notes column exists
            $checkColumnQuery = "SHOW COLUMNS FROM translation_issues LIKE 'resolution_notes'";
            $checkColumnStmt = $db->prepare($checkColumnQuery);
            $checkColumnStmt->execute();

            if ($checkColumnStmt->rowCount() > 0) {
                // Column exists, use the full query
                $resolveIssuesQuery = "UPDATE translation_issues
                                      SET is_resolved = TRUE,
                                          resolved_at = CURRENT_TIMESTAMP,
                                          resolution_notes = ?
                                      WHERE key_id = ? AND language_id = ? AND is_resolved = FALSE";
                $resolveIssuesStmt = $db->prepare($resolveIssuesQuery);
                $resolveIssuesStmt->execute([$overrideReason, $keyId, $languageId]);
            } else {
                // Column doesn't exist, use simpler query
                $resolveIssuesQuery = "UPDATE translation_issues
                                      SET is_resolved = TRUE,
                                          resolved_at = CURRENT_TIMESTAMP
                                      WHERE key_id = ? AND language_id = ? AND is_resolved = FALSE";
                $resolveIssuesStmt = $db->prepare($resolveIssuesQuery);
                $resolveIssuesStmt->execute([$keyId, $languageId]);
            }

            $resolvedIssuesCount = $resolveIssuesStmt->rowCount();
        }
        
        // 2. Approve the translation in the translations table (or create if doesn't exist)
        $checkTranslationQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
        $checkTranslationStmt = $db->prepare($checkTranslationQuery);
        $checkTranslationStmt->execute([$keyId, $languageId]);

        if ($checkTranslationStmt->rowCount() > 0) {
            // Translation exists, update it
            $approveTranslationQuery = "UPDATE translations
                                       SET is_approved = TRUE,
                                           updated_at = CURRENT_TIMESTAMP
                                       WHERE key_id = ? AND language_id = ?";
            $approveTranslationStmt = $db->prepare($approveTranslationQuery);
            $approveTranslationStmt->execute([$keyId, $languageId]);
            $translationUpdated = $approveTranslationStmt->rowCount() > 0;
        } else {
            // Translation doesn't exist, this is just confirming the issue without a translation
            $translationUpdated = false;
        }
        
        // 3. Add a confirmation record to track manual overrides
        $confirmationQuery = "INSERT INTO translation_confirmations 
                             (key_id, language_id, confirmed_by, confirmation_reason, created_at) 
                             VALUES (?, ?, 'admin', ?, CURRENT_TIMESTAMP)
                             ON DUPLICATE KEY UPDATE 
                             confirmation_reason = VALUES(confirmation_reason),
                             updated_at = CURRENT_TIMESTAMP";
        
        // Check if translation_confirmations table exists, if not create it
        $checkTableQuery = "SHOW TABLES LIKE 'translation_confirmations'";
        $checkTableStmt = $db->prepare($checkTableQuery);
        $checkTableStmt->execute();
        
        if ($checkTableStmt->rowCount() === 0) {
            // Create the table without foreign key constraints to avoid issues
            $createTableQuery = "CREATE TABLE translation_confirmations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_id INT NOT NULL,
                language_id INT NOT NULL,
                confirmed_by VARCHAR(100) NOT NULL,
                confirmation_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_confirmation (key_id, language_id),
                INDEX idx_key_id (key_id),
                INDEX idx_language_id (language_id)
            )";
            $db->exec($createTableQuery);
        }
        
        $confirmationStmt = $db->prepare($confirmationQuery);
        $confirmationStmt->execute([$keyId, $languageId, $overrideReason]);
        
        // Commit transaction
        $db->commit();
        
        // Get translation details for response
        $translationQuery = "SELECT tk.key_name, t.translation_text, l.name as language_name
                            FROM translation_keys tk
                            JOIN translations t ON tk.id = t.key_id
                            JOIN languages l ON t.language_id = l.id
                            WHERE tk.id = ? AND t.language_id = ?";
        $translationStmt = $db->prepare($translationQuery);
        $translationStmt->execute([$keyId, $languageId]);
        $translationDetails = $translationStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Translation issue confirmed and resolved successfully',
            'resolved_issues_count' => $resolvedIssuesCount,
            'translation_approved' => $translationUpdated,
            'key_id' => $keyId,
            'language_id' => $languageId,
            'override_reason' => $overrideReason,
            'translation_details' => $translationDetails
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Translation confirmation error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to confirm translation issue',
        'debug_info' => [
            'key_id' => $input['key_id'] ?? 'not provided',
            'language_id' => $input['language_id'] ?? 'not provided',
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile())
        ]
    ]);
}
?>
