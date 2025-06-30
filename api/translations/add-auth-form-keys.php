<?php
// Add comprehensive authentication form translation keys
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Comprehensive authentication form translation keys
    $authKeys = [
        // Main Auth Page Headers
        [
            'key_name' => 'auth.join_us',
            'category' => 'auth',
            'description' => 'Join Us'
        ],
        [
            'key_name' => 'auth.create_account_investing',
            'category' => 'auth',
            'description' => 'Create your account to start investing'
        ],
        [
            'key_name' => 'auth.sign_in_dashboard',
            'category' => 'auth',
            'description' => 'Sign in to access your investment dashboard'
        ],
        
        // Registration Form
        [
            'key_name' => 'auth.join_alliance',
            'category' => 'auth',
            'description' => 'Join the Aureus Angel Alliance'
        ],
        [
            'key_name' => 'auth.create_investment_account',
            'category' => 'auth',
            'description' => 'Create your investment account'
        ],
        [
            'key_name' => 'auth.username',
            'category' => 'auth',
            'description' => 'Username'
        ],
        [
            'key_name' => 'auth.email',
            'category' => 'auth',
            'description' => 'Email'
        ],
        [
            'key_name' => 'auth.password',
            'category' => 'auth',
            'description' => 'Password'
        ],
        [
            'key_name' => 'auth.confirm_password',
            'category' => 'auth',
            'description' => 'Confirm Password'
        ],
        [
            'key_name' => 'auth.create_account',
            'category' => 'auth',
            'description' => 'Create Account'
        ],
        [
            'key_name' => 'auth.already_have_account',
            'category' => 'auth',
            'description' => 'Already have an account?'
        ],
        [
            'key_name' => 'auth.sign_in',
            'category' => 'auth',
            'description' => 'Sign In'
        ],
        
        // Login Form
        [
            'key_name' => 'auth.welcome_back',
            'category' => 'auth',
            'description' => 'Welcome Back'
        ],
        [
            'key_name' => 'auth.sign_in_account',
            'category' => 'auth',
            'description' => 'Sign in to your account'
        ],
        [
            'key_name' => 'auth.dont_have_account',
            'category' => 'auth',
            'description' => 'Don\'t have an account?'
        ],
        [
            'key_name' => 'auth.sign_up',
            'category' => 'auth',
            'description' => 'Sign up'
        ],
        
        // Form Placeholders
        [
            'key_name' => 'auth.username_placeholder',
            'category' => 'auth',
            'description' => 'jp.rademeyer84@gmail.com'
        ],
        [
            'key_name' => 'auth.email_placeholder',
            'category' => 'auth',
            'description' => 'your@email.com'
        ],
        [
            'key_name' => 'auth.password_placeholder',
            'category' => 'auth',
            'description' => '............'
        ],
        [
            'key_name' => 'auth.confirm_password_placeholder',
            'category' => 'auth',
            'description' => 'Confirm your password'
        ],
        
        // Loading States
        [
            'key_name' => 'auth.creating_account',
            'category' => 'auth',
            'description' => 'Creating account...'
        ],
        [
            'key_name' => 'auth.signing_in',
            'category' => 'auth',
            'description' => 'Signing in...'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Check if key exists and add if not
    $checkQuery = "SELECT COUNT(*) as count FROM translation_keys WHERE key_name = ?";
    $checkStmt = $db->prepare($checkQuery);
    
    $insertQuery = "INSERT INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
    $insertStmt = $db->prepare($insertQuery);
    
    foreach ($authKeys as $key) {
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
    
    if ($englishLang) {
        $englishId = $englishLang['id'];
        
        // Get the key IDs for the new keys
        $keyQuery = "SELECT id, key_name FROM translation_keys WHERE key_name IN (" . 
                   str_repeat('?,', count($addedKeys) - 1) . "?)";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute($addedKeys);
        $keyIds = $keyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // English translations mapping
        $englishTranslations = [
            'auth.join_us' => 'Join Us',
            'auth.create_account_investing' => 'Create your account to start investing',
            'auth.sign_in_dashboard' => 'Sign in to access your investment dashboard',
            'auth.join_alliance' => 'Join the Aureus Angel Alliance',
            'auth.create_investment_account' => 'Create your investment account',
            'auth.username' => 'Username',
            'auth.email' => 'Email',
            'auth.password' => 'Password',
            'auth.confirm_password' => 'Confirm Password',
            'auth.create_account' => 'Create Account',
            'auth.already_have_account' => 'Already have an account?',
            'auth.sign_in' => 'Sign In',
            'auth.welcome_back' => 'Welcome Back',
            'auth.sign_in_account' => 'Sign in to your account',
            'auth.dont_have_account' => 'Don\'t have an account?',
            'auth.sign_up' => 'Sign up',
            'auth.username_placeholder' => 'jp.rademeyer84@gmail.com',
            'auth.email_placeholder' => 'your@email.com',
            'auth.password_placeholder' => '............',
            'auth.confirm_password_placeholder' => 'Confirm your password',
            'auth.creating_account' => 'Creating account...',
            'auth.signing_in' => 'Signing in...'
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
        'message' => 'Authentication form translation keys processed successfully',
        'added_keys' => $addedKeys,
        'skipped_keys' => $skippedKeys,
        'added_translations' => $addedTranslations ?? [],
        'total_keys_processed' => count($authKeys)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add authentication keys: ' . $e->getMessage()
    ]);
}
?>
