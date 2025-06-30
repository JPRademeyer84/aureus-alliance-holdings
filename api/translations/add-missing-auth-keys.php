<?php
// Add missing authentication translation keys found in components
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Missing authentication keys found in components
    $missingKeys = [
        // Keys used in UserRegister.tsx
        [
            'key_name' => 'auth.password_min_length',
            'category' => 'auth',
            'description' => 'Password must be at least 6 characters long'
        ],
        [
            'key_name' => 'auth.create_password_placeholder',
            'category' => 'auth',
            'description' => 'Create a secure password'
        ],
        [
            'key_name' => 'auth.have_account',
            'category' => 'auth',
            'description' => 'Already have an account?'
        ],
        [
            'key_name' => 'auth.sign_in_link',
            'category' => 'auth',
            'description' => 'Sign In'
        ],
        
        // Keys used in UserLogin.tsx
        [
            'key_name' => 'auth.no_account',
            'category' => 'auth',
            'description' => 'Don\'t have an account?'
        ],
        
        // Additional keys that might be needed
        [
            'key_name' => 'auth.password_mismatch',
            'category' => 'auth',
            'description' => 'Passwords do not match'
        ],
        [
            'key_name' => 'auth.username_taken',
            'category' => 'auth',
            'description' => 'Username is already taken'
        ],
        [
            'key_name' => 'auth.email_taken',
            'category' => 'auth',
            'description' => 'Email is already registered'
        ],
        [
            'key_name' => 'auth.invalid_credentials',
            'category' => 'auth',
            'description' => 'Invalid email or password'
        ],
        [
            'key_name' => 'auth.account_created',
            'category' => 'auth',
            'description' => 'Account created successfully!'
        ],
        [
            'key_name' => 'auth.login_successful',
            'category' => 'auth',
            'description' => 'Login successful!'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Check if key exists and add if not
    $checkQuery = "SELECT COUNT(*) as count FROM translation_keys WHERE key_name = ?";
    $checkStmt = $db->prepare($checkQuery);
    
    $insertQuery = "INSERT INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    
    foreach ($missingKeys as $key) {
        $checkStmt->execute([$key['key_name']]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$exists) {
            $insertStmt->execute([$key['key_name'], $key['description'], $key['category']]);
            $addedKeys[] = $key['key_name'];
        } else {
            $skippedKeys[] = $key['key_name'];
        }
    }
    
    // Now add English translations for the new keys
    $languageQuery = "SELECT id FROM languages WHERE code = 'en' LIMIT 1";
    $languageStmt = $db->prepare($languageQuery);
    $languageStmt->execute();
    $englishLang = $languageStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($englishLang && !empty($addedKeys)) {
        $englishId = $englishLang['id'];
        
        // Get the key IDs for the new keys
        $placeholders = str_repeat('?,', count($addedKeys) - 1) . '?';
        $keyQuery = "SELECT id, key_name FROM translation_keys WHERE key_name IN ($placeholders)";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute($addedKeys);
        $keyIds = $keyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // English translations mapping
        $englishTranslations = [
            'auth.password_min_length' => 'Password must be at least 6 characters long',
            'auth.create_password_placeholder' => 'Create a secure password',
            'auth.have_account' => 'Already have an account?',
            'auth.sign_in_link' => 'Sign In',
            'auth.no_account' => 'Don\'t have an account?',
            'auth.password_mismatch' => 'Passwords do not match',
            'auth.username_taken' => 'Username is already taken',
            'auth.email_taken' => 'Email is already registered',
            'auth.invalid_credentials' => 'Invalid email or password',
            'auth.account_created' => 'Account created successfully!',
            'auth.login_successful' => 'Login successful!'
        ];
        
        $translationQuery = "INSERT IGNORE INTO translations (language_id, key_id, translation_text) VALUES (?, ?, ?)";
        $translationStmt = $db->prepare($translationQuery);
        
        $addedTranslations = [];
        foreach ($keyIds as $keyData) {
            $keyName = $keyData['key_name'];
            $keyId = $keyData['id'];
            
            if (isset($englishTranslations[$keyName])) {
                $translationStmt->execute([$englishId, $keyId, $englishTranslations[$keyName]]);
                $addedTranslations[] = $keyName;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Missing authentication translation keys processed successfully',
        'added_keys' => $addedKeys,
        'skipped_keys' => $skippedKeys,
        'added_translations' => $addedTranslations ?? [],
        'total_keys_processed' => count($missingKeys)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add missing authentication keys: ' . $e->getMessage()
    ]);
}
?>
