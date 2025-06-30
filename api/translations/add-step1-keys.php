<?php
// Add Step 1 "Create Your Account" translation keys
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Create Your Account' => 'Crea Tu Cuenta',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Regístrate en menos de 2 minutos solo con tu email. No se requiere verificación compleja para comenzar.'
        ],
        'French' => [
            'Create Your Account' => 'Créez Votre Compte',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Inscrivez-vous en moins de 2 minutes avec juste votre email. Aucune vérification complexe requise pour commencer.'
        ],
        'German' => [
            'Create Your Account' => 'Erstellen Sie Ihr Konto',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Melden Sie sich in unter 2 Minuten mit nur Ihrer E-Mail an. Keine komplexe Verifizierung erforderlich, um zu beginnen.'
        ],
        'Portuguese' => [
            'Create Your Account' => 'Crie Sua Conta',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Cadastre-se em menos de 2 minutos apenas com seu email. Nenhuma verificação complexa necessária para começar.'
        ],
        'Italian' => [
            'Create Your Account' => 'Crea Il Tuo Account',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Registrati in meno di 2 minuti con solo la tua email. Nessuna verifica complessa richiesta per iniziare.'
        ],
        'Russian' => [
            'Create Your Account' => 'Создайте Свой Аккаунт',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Зарегистрируйтесь менее чем за 2 минуты, используя только ваш email. Сложная верификация не требуется для начала работы.'
        ],
        'Chinese' => [
            'Create Your Account' => '创建您的账户',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => '仅用您的电子邮件在2分钟内注册。无需复杂验证即可开始。'
        ],
        'Japanese' => [
            'Create Your Account' => 'アカウントを作成',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'メールアドレスだけで2分以内に登録できます。開始するのに複雑な認証は必要ありません。'
        ],
        'Arabic' => [
            'Create Your Account' => 'أنشئ حسابك',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'سجل في أقل من دقيقتين باستخدام بريدك الإلكتروني فقط. لا يتطلب تحقق معقد للبدء.'
        ],
        'Ukrainian' => [
            'Create Your Account' => 'Створіть Свій Обліковий Запис',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Зареєструйтеся менш ніж за 2 хвилини, використовуючи лише ваш email. Складна верифікація не потрібна для початку роботи.'
        ],
        'Hindi' => [
            'Create Your Account' => 'अपना खाता बनाएं',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'केवल अपने ईमेल के साथ 2 मिनट से कम में साइन अप करें। शुरू करने के लिए कोई जटिल सत्यापन आवश्यक नहीं।'
        ],
        'Urdu' => [
            'Create Your Account' => 'اپنا اکاؤنٹ بنائیں',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'صرف اپنے ای میل کے ساتھ 2 منٹ سے کم میں سائن اپ کریں۔ شروع کرنے کے لیے کوئی پیچیدہ تصدیق درکار نہیں۔'
        ],
        'Bengali' => [
            'Create Your Account' => 'আপনার অ্যাকাউন্ট তৈরি করুন',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'শুধুমাত্র আপনার ইমেইল দিয়ে ২ মিনিটের কম সময়ে সাইন আপ করুন। শুরু করার জন্য কোনো জটিল যাচাইকরণের প্রয়োজন নেই।'
        ],
        'Korean' => [
            'Create Your Account' => '계정 생성',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => '이메일만으로 2분 이내에 가입하세요. 시작하는데 복잡한 인증이 필요하지 않습니다.'
        ],
        'Malay' => [
            'Create Your Account' => 'Cipta Akaun Anda',
            'Sign up in under 2 minutes with just your email. No complex verification required to get started.' => 'Daftar dalam masa kurang dari 2 minit dengan hanya email anda. Tiada pengesahan kompleks diperlukan untuk bermula.'
        ]
    ];
    
    if (isset($translations[$targetLanguage][$text])) {
        return $translations[$targetLanguage][$text];
    }
    
    return $text; // Return original if no translation found
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 1 translation keys to add
    $step1Keys = [
        [
            'key_name' => 'homepage.steps.step1.title',
            'category' => 'homepage',
            'description' => 'Create Your Account'
        ],
        [
            'key_name' => 'homepage.steps.step1.description',
            'category' => 'homepage',
            'description' => 'Sign up in under 2 minutes with just your email. No complex verification required to get started.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($step1Keys as $keyData) {
        // Check if key already exists
        $checkQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyData['key_name']]);
        
        if ($checkStmt->fetch()) {
            $skippedKeys[] = $keyData['key_name'] . ' (already exists)';
            continue;
        }
        
        // Insert new translation key
        $insertQuery = "INSERT INTO translation_keys (key_name, category, description, created_at) 
                       VALUES (?, ?, ?, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            $keyData['key_name'],
            $keyData['category'],
            $keyData['description']
        ]);
        
        $keyId = $db->lastInsertId();
        $addedKeys[] = [
            'id' => $keyId,
            'key_name' => $keyData['key_name'],
            'description' => $keyData['description']
        ];
        
        // Add English translation (default)
        $englishLangQuery = "SELECT id FROM languages WHERE code = 'en'";
        $englishLangStmt = $db->prepare($englishLangQuery);
        $englishLangStmt->execute();
        $englishLang = $englishLangStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($englishLang) {
            $translationQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                               VALUES (?, ?, ?, TRUE, NOW())";
            $translationStmt = $db->prepare($translationQuery);
            $translationStmt->execute([
                $keyId,
                $englishLang['id'],
                $keyData['description']
            ]);
        }
    }
    
    // Add translations for all other languages
    if (!empty($addedKeys)) {
        $langQuery = "SELECT id, name, code FROM languages WHERE code != 'en' AND is_active = TRUE";
        $langStmt = $db->prepare($langQuery);
        $langStmt->execute();
        $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $translationsAdded = [];
        
        foreach ($languages as $language) {
            foreach ($addedKeys as $key) {
                // Get AI translation
                $translation = translateWithAI($key['description'], $language['name']);
                
                // Insert translation
                $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                               VALUES (?, ?, ?, TRUE, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$key['id'], $language['id'], $translation]);
                
                $translationsAdded[] = [
                    'key_name' => $key['key_name'],
                    'language' => $language['name'],
                    'language_code' => $language['code'],
                    'original' => $key['description'],
                    'translation' => $translation
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Step 1 translation keys processed successfully',
        'results' => [
            'added_keys' => $addedKeys,
            'skipped_keys' => $skippedKeys,
            'added_count' => count($addedKeys),
            'skipped_count' => count($skippedKeys),
            'translations_added' => isset($translationsAdded) ? count($translationsAdded) : 0
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add Step 1 translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
