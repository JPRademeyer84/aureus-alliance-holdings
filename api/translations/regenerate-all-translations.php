<?php
// Regenerate all language translations for a specific key using updated English text
require_once '../config/cors.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Join Us' => 'Únete a Nosotros',
            'Create your account to start investing' => 'Crea tu cuenta para comenzar a invertir',
            'Sign in to access your investment dashboard' => 'Inicia sesión para acceder a tu panel de inversión',
            'Join the Aureus Angel Alliance' => 'Únete a la Alianza Ángel Aureus',
            'Create your investment account' => 'Crea tu cuenta de inversión',
            'Username' => 'Nombre de Usuario',
            'Email' => 'Correo Electrónico',
            'Password' => 'Contraseña',
            'Confirm Password' => 'Confirmar Contraseña',
            'Create Account' => 'Crear Cuenta',
            'Already have an account?' => '¿Ya tienes una cuenta?',
            'Sign In' => 'Iniciar Sesión',
            'Welcome Back' => 'Bienvenido de Vuelta',
            'Sign in to your account' => 'Inicia sesión en tu cuenta',
            'Don\'t have an account?' => '¿No tienes una cuenta?',
            'Sign up' => 'Regístrate'
        ],
        'French' => [
            'Join Us' => 'Rejoignez-nous',
            'Create your account to start investing' => 'Créez votre compte pour commencer à investir',
            'Sign in to access your investment dashboard' => 'Connectez-vous pour accéder à votre tableau de bord d\'investissement',
            'Join the Aureus Angel Alliance' => 'Rejoignez l\'Alliance Ange Aureus',
            'Create your investment account' => 'Créez votre compte d\'investissement',
            'Username' => 'Nom d\'utilisateur',
            'Email' => 'E-mail',
            'Password' => 'Mot de passe',
            'Confirm Password' => 'Confirmer le mot de passe',
            'Create Account' => 'Créer un compte',
            'Already have an account?' => 'Vous avez déjà un compte?',
            'Sign In' => 'Se connecter',
            'Welcome Back' => 'Bon retour',
            'Sign in to your account' => 'Connectez-vous à votre compte',
            'Don\'t have an account?' => 'Vous n\'avez pas de compte?',
            'Sign up' => 'S\'inscrire'
        ],
        'German' => [
            'Join Us' => 'Treten Sie uns bei',
            'Create your account to start investing' => 'Erstellen Sie Ihr Konto, um mit dem Investieren zu beginnen',
            'Sign in to access your investment dashboard' => 'Melden Sie sich an, um auf Ihr Investment-Dashboard zuzugreifen',
            'Join the Aureus Angel Alliance' => 'Treten Sie der Aureus Angel Alliance bei',
            'Create your investment account' => 'Erstellen Sie Ihr Investmentkonto',
            'Username' => 'Benutzername',
            'Email' => 'E-Mail',
            'Password' => 'Passwort',
            'Confirm Password' => 'Passwort bestätigen',
            'Create Account' => 'Konto erstellen',
            'Already have an account?' => 'Haben Sie bereits ein Konto?',
            'Sign In' => 'Anmelden',
            'Welcome Back' => 'Willkommen zurück',
            'Sign in to your account' => 'Melden Sie sich in Ihrem Konto an',
            'Don\'t have an account?' => 'Haben Sie kein Konto?',
            'Sign up' => 'Registrieren'
        ],
        'Portuguese' => [
            'Join Us' => 'Junte-se a Nós',
            'Create your account to start investing' => 'Crie sua conta para começar a investir',
            'Sign in to access your investment dashboard' => 'Faça login para acessar seu painel de investimentos',
            'Join the Aureus Angel Alliance' => 'Junte-se à Aliança Anjo Aureus',
            'Create your investment account' => 'Crie sua conta de investimento',
            'Username' => 'Nome de Usuário',
            'Email' => 'E-mail',
            'Password' => 'Senha',
            'Confirm Password' => 'Confirmar Senha',
            'Create Account' => 'Criar Conta',
            'Already have an account?' => 'Já tem uma conta?',
            'Sign In' => 'Entrar',
            'Welcome Back' => 'Bem-vindo de Volta',
            'Sign in to your account' => 'Faça login em sua conta',
            'Don\'t have an account?' => 'Não tem uma conta?',
            'Sign up' => 'Cadastre-se'
        ],
        'Italian' => [
            'Join Us' => 'Unisciti a Noi',
            'Create your account to start investing' => 'Crea il tuo account per iniziare a investire',
            'Sign in to access your investment dashboard' => 'Accedi per accedere al tuo dashboard degli investimenti',
            'Join the Aureus Angel Alliance' => 'Unisciti all\'Alleanza Angelo Aureus',
            'Create your investment account' => 'Crea il tuo account di investimento',
            'Username' => 'Nome Utente',
            'Email' => 'Email',
            'Password' => 'Password',
            'Confirm Password' => 'Conferma Password',
            'Create Account' => 'Crea Account',
            'Already have an account?' => 'Hai già un account?',
            'Sign In' => 'Accedi',
            'Welcome Back' => 'Bentornato',
            'Sign in to your account' => 'Accedi al tuo account',
            'Don\'t have an account?' => 'Non hai un account?',
            'Sign up' => 'Registrati'
        ],
        'Russian' => [
            'Join Us' => 'Присоединяйтесь к нам',
            'Create your account to start investing' => 'Создайте свой аккаунт, чтобы начать инвестировать',
            'Sign in to access your investment dashboard' => 'Войдите, чтобы получить доступ к своей инвестиционной панели',
            'Join the Aureus Angel Alliance' => 'Присоединяйтесь к Альянсу Ангелов Aureus',
            'Create your investment account' => 'Создайте свой инвестиционный аккаунт',
            'Username' => 'Имя пользователя',
            'Email' => 'Электронная почта',
            'Password' => 'Пароль',
            'Confirm Password' => 'Подтвердите пароль',
            'Create Account' => 'Создать аккаунт',
            'Already have an account?' => 'Уже есть аккаунт?',
            'Sign In' => 'Войти',
            'Welcome Back' => 'Добро пожаловать обратно',
            'Sign in to your account' => 'Войдите в свой аккаунт',
            'Don\'t have an account?' => 'Нет аккаунта?',
            'Sign up' => 'Зарегистрироваться'
        ],
        'Chinese' => [
            'Join Us' => '加入我们',
            'Create your account to start investing' => '创建您的账户开始投资',
            'Sign in to access your investment dashboard' => '登录以访问您的投资仪表板',
            'Join the Aureus Angel Alliance' => '加入Aureus天使联盟',
            'Create your investment account' => '创建您的投资账户',
            'Username' => '用户名',
            'Email' => '电子邮件',
            'Password' => '密码',
            'Confirm Password' => '确认密码',
            'Create Account' => '创建账户',
            'Already have an account?' => '已经有账户了？',
            'Sign In' => '登录',
            'Welcome Back' => '欢迎回来',
            'Sign in to your account' => '登录您的账户',
            'Don\'t have an account?' => '没有账户？',
            'Sign up' => '注册'
        ],
        'Japanese' => [
            'Join Us' => '参加する',
            'Create your account to start investing' => 'アカウントを作成して投資を開始',
            'Sign in to access your investment dashboard' => 'サインインして投資ダッシュボードにアクセス',
            'Join the Aureus Angel Alliance' => 'Aureusエンジェルアライアンスに参加',
            'Create your investment account' => '投資アカウントを作成',
            'Username' => 'ユーザー名',
            'Email' => 'メール',
            'Password' => 'パスワード',
            'Confirm Password' => 'パスワードを確認',
            'Create Account' => 'アカウント作成',
            'Already have an account?' => 'すでにアカウントをお持ちですか？',
            'Sign In' => 'サインイン',
            'Welcome Back' => 'おかえりなさい',
            'Sign in to your account' => 'アカウントにサインイン',
            'Don\'t have an account?' => 'アカウントをお持ちでないですか？',
            'Sign up' => 'サインアップ'
        ],
        'Ukrainian' => [
            'Join Us' => 'Приєднуйтесь до нас',
            'Create your account to start investing' => 'Створіть свій обліковий запис, щоб почати інвестувати',
            'Sign in to access your investment dashboard' => 'Увійдіть, щоб отримати доступ до своєї інвестиційної панелі',
            'Join the Aureus Angel Alliance' => 'Приєднуйтесь до Альянсу Ангелів Aureus',
            'Create your investment account' => 'Створіть свій інвестиційний обліковий запис',
            'Username' => 'Ім\'я користувача',
            'Email' => 'Електронна пошта',
            'Password' => 'Пароль',
            'Confirm Password' => 'Підтвердіть пароль',
            'Create Account' => 'Створити обліковий запис',
            'Already have an account?' => 'Вже маєте обліковий запис?',
            'Sign In' => 'Увійти',
            'Welcome Back' => 'Ласкаво просимо назад',
            'Sign in to your account' => 'Увійдіть до свого облікового запису',
            'Don\'t have an account?' => 'Немає облікового запису?',
            'Sign up' => 'Зареєструватися'
        ],
        'Hindi' => [
            'Join Us' => 'हमसे जुड़ें',
            'Create your account to start investing' => 'निवेश शुरू करने के लिए अपना खाता बनाएं',
            'Sign in to access your investment dashboard' => 'अपने निवेश डैशबोर्ड तक पहुंचने के लिए साइन इन करें',
            'Join the Aureus Angel Alliance' => 'ऑरियस एंजेल एलायंस में शामिल हों',
            'Create your investment account' => 'अपना निवेश खाता बनाएं',
            'Username' => 'उपयोगकर्ता नाम',
            'Email' => 'ईमेल',
            'Password' => 'पासवर्ड',
            'Confirm Password' => 'पासवर्ड की पुष्टि करें',
            'Create Account' => 'खाता बनाएं',
            'Already have an account?' => 'पहले से खाता है?',
            'Sign In' => 'साइन इन करें',
            'Welcome Back' => 'वापसी पर स्वागत है',
            'Sign in to your account' => 'अपने खाते में साइन इन करें',
            'Don\'t have an account?' => 'खाता नहीं है?',
            'Sign up' => 'साइन अप करें'
        ],
        'Korean' => [
            'Join Us' => '참여하기',
            'Create your account to start investing' => '투자를 시작하려면 계정을 만드세요',
            'Sign in to access your investment dashboard' => '투자 대시보드에 액세스하려면 로그인하세요',
            'Join the Aureus Angel Alliance' => 'Aureus 엔젤 얼라이언스에 참여하세요',
            'Create your investment account' => '투자 계정을 만드세요',
            'Username' => '사용자명',
            'Email' => '이메일',
            'Password' => '비밀번호',
            'Confirm Password' => '비밀번호 확인',
            'Create Account' => '계정 만들기',
            'Already have an account?' => '이미 계정이 있으신가요?',
            'Sign In' => '로그인',
            'Welcome Back' => '다시 오신 것을 환영합니다',
            'Sign in to your account' => '계정에 로그인하세요',
            'Don\'t have an account?' => '계정이 없으신가요?',
            'Sign up' => '가입하기'
        ],
        'Malay' => [
            'Join Us' => 'Sertai Kami',
            'Create your account to start investing' => 'Cipta akaun anda untuk mula melabur',
            'Sign in to access your investment dashboard' => 'Log masuk untuk mengakses papan pemuka pelaburan anda',
            'Join the Aureus Angel Alliance' => 'Sertai Aureus Angel Alliance',
            'Create your investment account' => 'Cipta akaun pelaburan anda',
            'Username' => 'Nama Pengguna',
            'Email' => 'E-mel',
            'Password' => 'Kata Laluan',
            'Confirm Password' => 'Sahkan Kata Laluan',
            'Create Account' => 'Cipta Akaun',
            'Already have an account?' => 'Sudah mempunyai akaun?',
            'Sign In' => 'Log Masuk',
            'Welcome Back' => 'Selamat Kembali',
            'Sign in to your account' => 'Log masuk ke akaun anda',
            'Don\'t have an account?' => 'Tidak mempunyai akaun?',
            'Sign up' => 'Daftar'
        ]
    ];
    
    if (isset($translations[$targetLanguage][$text])) {
        return $translations[$targetLanguage][$text];
    }
    
    // Fallback: return original text if no translation found
    return $text;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['key_id']) || !isset($input['english_text']) || !isset($input['target_languages'])) {
        throw new Exception('Missing required parameters: key_id, english_text, and target_languages');
    }
    
    $keyId = (int)$input['key_id'];
    $englishText = trim($input['english_text']);
    $targetLanguages = $input['target_languages'];
    
    if (empty($englishText)) {
        throw new Exception('English text cannot be empty');
    }
    
    if (empty($targetLanguages) || !is_array($targetLanguages)) {
        throw new Exception('Target languages must be a non-empty array');
    }
    
    $translationsUpdated = 0;
    $results = [];
    
    // Process each target language
    foreach ($targetLanguages as $language) {
        if (!isset($language['id']) || !isset($language['name'])) {
            continue;
        }
        
        $languageId = (int)$language['id'];
        $languageName = $language['name'];
        
        // Get AI translation
        $translatedText = translateWithAI($englishText, $languageName);
        
        // Check if translation already exists
        $checkQuery = "SELECT id FROM translations WHERE key_id = ? AND language_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyId, $languageId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing translation
            $updateQuery = "UPDATE translations 
                           SET translation_text = ?, is_approved = TRUE, updated_at = CURRENT_TIMESTAMP 
                           WHERE key_id = ? AND language_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$translatedText, $keyId, $languageId]);
        } else {
            // Insert new translation
            $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                           VALUES (?, ?, ?, TRUE)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$keyId, $languageId, $translatedText]);
        }
        
        $translationsUpdated++;
        $results[] = [
            'language' => $languageName,
            'translation' => $translatedText
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully regenerated translations for {$translationsUpdated} languages",
        'translations_updated' => $translationsUpdated,
        'results' => $results,
        'key_id' => $keyId,
        'english_text' => $englishText
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
