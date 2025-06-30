<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Create translation-related tables
    $tables_created = [];
    $errors = [];
    
    // 1. Create languages table
    try {
        $query = "CREATE TABLE IF NOT EXISTS languages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            native_name VARCHAR(100) NOT NULL,
            flag VARCHAR(10) NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($query);
        $tables_created[] = 'languages';
        
        // Insert default languages if table is empty
        $checkQuery = "SELECT COUNT(*) as count FROM languages";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        $langCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($langCount == 0) {
            $languages = [
                ['en', 'English', 'English', '🇺🇸', true, true, 1],
                ['es', 'Spanish', 'Español', '🇪🇸', false, true, 2],
                ['fr', 'French', 'Français', '🇫🇷', false, true, 3],
                ['de', 'German', 'Deutsch', '🇩🇪', false, true, 4],
                ['it', 'Italian', 'Italiano', '🇮🇹', false, true, 5],
                ['pt', 'Portuguese', 'Português', '🇵🇹', false, true, 6],
                ['ru', 'Russian', 'Русский', '🇷🇺', false, true, 7],
                ['zh', 'Chinese', '中文', '🇨🇳', false, true, 8],
                ['ja', 'Japanese', '日本語', '🇯🇵', false, true, 9],
                ['ko', 'Korean', '한국어', '🇰🇷', false, true, 10],
                ['ar', 'Arabic', 'العربية', '🇸🇦', false, true, 11],
                ['hi', 'Hindi', 'हिन्दी', '🇮🇳', false, true, 12],
                ['ur', 'Urdu', 'اردو', '🇵🇰', false, true, 13],
                ['bn', 'Bengali', 'বাংলা', '🇧🇩', false, true, 14],
                ['uk', 'Ukrainian', 'Українська', '🇺🇦', false, true, 15]
            ];
            
            $insertQuery = "INSERT INTO languages (code, name, native_name, flag, is_default, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            
            foreach ($languages as $lang) {
                $insertStmt->execute($lang);
            }
            $tables_created[] = 'languages (with default data)';
        }
        
    } catch (Exception $e) {
        $errors[] = 'languages table: ' . $e->getMessage();
    }
    
    // 2. Create translation_keys table
    try {
        $query = "CREATE TABLE IF NOT EXISTS translation_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            category VARCHAR(100) NOT NULL DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_key_name (key_name)
        )";
        $db->exec($query);
        $tables_created[] = 'translation_keys';
        
        // Insert some default translation keys if table is empty
        $checkQuery = "SELECT COUNT(*) as count FROM translation_keys";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        $keyCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($keyCount == 0) {
            $keys = [
                ['nav.investment', 'Investment menu item', 'navigation'],
                ['nav.affiliate', 'Affiliate menu item', 'navigation'],
                ['nav.benefits', 'Benefits menu item', 'navigation'],
                ['nav.about', 'About menu item', 'navigation'],
                ['nav.contact', 'Contact menu item', 'navigation'],
                ['nav.signin', 'Sign In menu item', 'navigation'],
                ['hero.title', 'Main hero title', 'hero'],
                ['hero.subtitle', 'Hero subtitle', 'hero'],
                ['hero.cta_primary', 'Primary call to action button', 'hero'],
                ['hero.cta_secondary', 'Secondary call to action button', 'hero'],
                ['auth.welcome_back', 'Welcome back message', 'authentication'],
                ['auth.signin_subtitle', 'Sign in subtitle', 'authentication'],
                ['auth.email', 'Email field label', 'authentication'],
                ['auth.password', 'Password field label', 'authentication'],
                ['dashboard.welcome', 'Dashboard welcome message', 'dashboard'],
                ['dashboard.total_investments', 'Total investments label', 'dashboard'],
                ['dashboard.portfolio_value', 'Portfolio value label', 'dashboard'],
                ['dashboard.commission_earnings', 'Commission earnings label', 'dashboard'],
                ['dashboard.available_balance', 'Available balance label', 'dashboard'],
                ['common.loading', 'Loading message', 'common'],
                ['common.success', 'Success message', 'common'],
                ['common.error', 'Error message', 'common'],
                ['common.save', 'Save button', 'common'],
                ['common.cancel', 'Cancel button', 'common'],
                ['common.edit', 'Edit button', 'common'],
                ['common.delete', 'Delete button', 'common']
            ];
            
            $insertQuery = "INSERT INTO translation_keys (key_name, description, category) VALUES (?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            
            foreach ($keys as $key) {
                $insertStmt->execute($key);
            }
            $tables_created[] = 'translation_keys (with default data)';
        }
        
    } catch (Exception $e) {
        $errors[] = 'translation_keys table: ' . $e->getMessage();
    }
    
    // 3. Create translations table
    try {
        $query = "CREATE TABLE IF NOT EXISTS translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_id INT NOT NULL,
            language_id INT NOT NULL,
            translation_text TEXT NOT NULL,
            is_approved BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_translation (key_id, language_id),
            FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
            FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
            INDEX idx_key_language (key_id, language_id),
            INDEX idx_is_approved (is_approved)
        )";
        $db->exec($query);
        $tables_created[] = 'translations';
        
        // Insert English translations for default keys if none exist
        $checkQuery = "SELECT COUNT(*) as count FROM translations t 
                      JOIN languages l ON t.language_id = l.id 
                      WHERE l.code = 'en'";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute();
        $transCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($transCount == 0) {
            // Get English language ID
            $langQuery = "SELECT id FROM languages WHERE code = 'en'";
            $langStmt = $db->prepare($langQuery);
            $langStmt->execute();
            $englishLang = $langStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($englishLang) {
                $englishId = $englishLang['id'];
                
                // Get all translation keys
                $keysQuery = "SELECT id, key_name FROM translation_keys";
                $keysStmt = $db->prepare($keysQuery);
                $keysStmt->execute();
                $keys = $keysStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $englishTexts = [
                    'nav.investment' => 'Investment',
                    'nav.affiliate' => 'Affiliate',
                    'nav.benefits' => 'Benefits',
                    'nav.about' => 'About',
                    'nav.contact' => 'Contact',
                    'nav.signin' => 'Sign In',
                    'hero.title' => 'Become an Angel Investor',
                    'hero.subtitle' => 'in the Future of Digital Gold',
                    'hero.cta_primary' => 'Invest Now',
                    'hero.cta_secondary' => 'Learn More',
                    'auth.welcome_back' => 'Welcome Back',
                    'auth.signin_subtitle' => 'Sign in to your account',
                    'auth.email' => 'Email',
                    'auth.password' => 'Password',
                    'dashboard.welcome' => 'Welcome back',
                    'dashboard.total_investments' => 'Total Investments',
                    'dashboard.portfolio_value' => 'Portfolio Value',
                    'dashboard.commission_earnings' => 'Commission Earnings',
                    'dashboard.available_balance' => 'Available Balance',
                    'common.loading' => 'Loading...',
                    'common.success' => 'Success',
                    'common.error' => 'Error',
                    'common.save' => 'Save',
                    'common.cancel' => 'Cancel',
                    'common.edit' => 'Edit',
                    'common.delete' => 'Delete'
                ];
                
                $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) VALUES (?, ?, ?, TRUE)";
                $insertStmt = $db->prepare($insertQuery);
                
                foreach ($keys as $key) {
                    $keyName = $key['key_name'];
                    $englishText = $englishTexts[$keyName] ?? $keyName;
                    $insertStmt->execute([$key['id'], $englishId, $englishText]);
                }
                $tables_created[] = 'translations (with English defaults)';
            }
        }
        
    } catch (Exception $e) {
        $errors[] = 'translations table: ' . $e->getMessage();
    }
    
    $response = [
        'success' => count($errors) === 0,
        'message' => count($errors) === 0 ? 'Translation tables setup completed successfully' : 'Some errors occurred during setup',
        'tables_created' => $tables_created,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s'),
        'database_info' => [
            'connection' => 'OK',
            'host' => 'localhost:3506',
            'database' => 'aureus_angels'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Translation tables setup failed',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
