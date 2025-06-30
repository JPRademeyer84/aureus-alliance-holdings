<?php
// Create translation keys for profile update functionality
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get English and Spanish language IDs
    $langQuery = "SELECT id, code FROM languages WHERE code IN ('en', 'es')";
    $langStmt = $db->prepare($langQuery);
    $langStmt->execute();
    $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $englishId = null;
    $spanishId = null;
    
    foreach ($languages as $lang) {
        if ($lang['code'] === 'en') $englishId = $lang['id'];
        if ($lang['code'] === 'es') $spanishId = $lang['id'];
    }
    
    if (!$englishId || !$spanishId) {
        throw new Exception('English or Spanish language not found in database');
    }
    
    // Translation keys for profile functionality
    $translationKeys = [
        // Profile sections
        'personal_information' => ['category' => 'profile', 'english' => 'Personal Information', 'spanish' => 'Información Personal'],
        'basic_information' => ['category' => 'profile', 'english' => 'Basic Information', 'spanish' => 'Información Básica'],
        'contact_social_media' => ['category' => 'profile', 'english' => 'Contact & Social Media', 'spanish' => 'Contacto y Redes Sociales'],
        
        // Profile fields
        'full_name' => ['category' => 'profile', 'english' => 'Full Name', 'spanish' => 'Nombre Completo'],
        'whatsapp_number' => ['category' => 'profile', 'english' => 'WhatsApp Number', 'spanish' => 'Número de WhatsApp'],
        'telegram_username' => ['category' => 'profile', 'english' => 'Telegram Username', 'spanish' => 'Usuario de Telegram'],
        'twitter_handle' => ['category' => 'profile', 'english' => 'Twitter Handle', 'spanish' => 'Usuario de Twitter'],
        'instagram_handle' => ['category' => 'profile', 'english' => 'Instagram Handle', 'spanish' => 'Usuario de Instagram'],
        'linkedin_profile' => ['category' => 'profile', 'english' => 'LinkedIn Profile', 'spanish' => 'Perfil de LinkedIn'],
        
        // Profile actions
        'saving' => ['category' => 'profile', 'english' => 'Saving...', 'spanish' => 'Guardando...'],
        'save_changes' => ['category' => 'profile', 'english' => 'Save Changes', 'spanish' => 'Guardar Cambios'],
        'edit_profile' => ['category' => 'profile', 'english' => 'Edit Profile', 'spanish' => 'Editar Perfil'],
        'cancel' => ['category' => 'profile', 'english' => 'Cancel', 'spanish' => 'Cancelar'],
        
        // Profile messages
        'profile_updated' => ['category' => 'profile', 'english' => 'Profile Updated', 'spanish' => 'Perfil Actualizado'],
        'profile_update_success' => ['category' => 'profile', 'english' => 'Your profile has been updated successfully', 'spanish' => 'Tu perfil ha sido actualizado exitosamente'],
        'validation_error' => ['category' => 'profile', 'english' => 'Validation Error', 'spanish' => 'Error de Validación'],
        'username_email_required' => ['category' => 'profile', 'english' => 'Username and email are required', 'spanish' => 'El nombre de usuario y email son requeridos'],
        'failed_to_save_profile' => ['category' => 'profile', 'english' => 'Failed to save profile', 'spanish' => 'Error al guardar perfil'],
        
        // Account settings
        'account_settings' => ['category' => 'profile', 'english' => 'Account Settings', 'spanish' => 'Configuración de Cuenta'],
        'manage_account_wallet' => ['category' => 'profile', 'english' => 'Manage your account and wallet connections', 'spanish' => 'Administra tu cuenta y conexiones de billetera'],
        'profile_information' => ['category' => 'profile', 'english' => 'Profile Information', 'spanish' => 'Información del Perfil'],
        'member_since' => ['category' => 'profile', 'english' => 'Member Since', 'spanish' => 'Miembro Desde'],
        'account_status' => ['category' => 'profile', 'english' => 'Account Status', 'spanish' => 'Estado de Cuenta'],
        'active_investor' => ['category' => 'profile', 'english' => 'Active Investor', 'spanish' => 'Inversionista Activo'],
        
        // Wallet settings
        'wallet_connection' => ['category' => 'profile', 'english' => 'Wallet Connection', 'spanish' => 'Conexión de Billetera'],
        'connect_wallet_start_investing' => ['category' => 'profile', 'english' => 'Connect your wallet to start investing and track your portfolio', 'spanish' => 'Conecta tu billetera para comenzar a invertir y rastrear tu portafolio'],
        'wallet_connected' => ['category' => 'profile', 'english' => 'Wallet Connected', 'spanish' => 'Billetera Conectada'],
        'address' => ['category' => 'profile', 'english' => 'Address:', 'spanish' => 'Dirección:'],
        'usdt_balance' => ['category' => 'profile', 'english' => 'USDT Balance:', 'spanish' => 'Saldo USDT:'],
        'network' => ['category' => 'profile', 'english' => 'Network:', 'spanish' => 'Red:'],
        'disconnect' => ['category' => 'profile', 'english' => 'Disconnect', 'spanish' => 'Desconectar'],
        'switch_wallet' => ['category' => 'profile', 'english' => 'Switch Wallet', 'spanish' => 'Cambiar Billetera'],
        
        // Security settings
        'security_settings' => ['category' => 'profile', 'english' => 'Security Settings', 'spanish' => 'Configuración de Seguridad'],
        'security_features_coming_soon' => ['category' => 'profile', 'english' => 'Security features coming soon...', 'spanish' => 'Funciones de seguridad próximamente...'],
        'two_factor_authentication' => ['category' => 'profile', 'english' => 'Two-Factor Authentication', 'spanish' => 'Autenticación de Dos Factores'],
        'add_extra_security_layer' => ['category' => 'profile', 'english' => 'Add an extra layer of security to your account', 'spanish' => 'Agrega una capa extra de seguridad a tu cuenta'],
        'enable_2fa_coming_soon' => ['category' => 'profile', 'english' => 'Enable 2FA (Coming Soon)', 'spanish' => 'Habilitar 2FA (Próximamente)'],
        'password_change' => ['category' => 'profile', 'english' => 'Password Change', 'spanish' => 'Cambio de Contraseña'],
        'update_account_password' => ['category' => 'profile', 'english' => 'Update your account password', 'spanish' => 'Actualiza la contraseña de tu cuenta'],
        'change_password_coming_soon' => ['category' => 'profile', 'english' => 'Change Password (Coming Soon)', 'spanish' => 'Cambiar Contraseña (Próximamente)'],
        
        // Network names
        'polygon' => ['category' => 'profile', 'english' => 'Polygon', 'spanish' => 'Polygon'],
        'bsc' => ['category' => 'profile', 'english' => 'BSC', 'spanish' => 'BSC'],
        'ethereum' => ['category' => 'profile', 'english' => 'Ethereum', 'spanish' => 'Ethereum'],
        'unknown' => ['category' => 'profile', 'english' => 'Unknown', 'spanish' => 'Desconocido'],
        
        // Common
        'zero_decimal' => ['category' => 'profile', 'english' => '0.00', 'spanish' => '0,00'],
        'usdt' => ['category' => 'profile', 'english' => 'USDT', 'spanish' => 'USDT']
    ];
    
    $createdKeys = 0;
    $createdTranslations = 0;
    
    foreach ($translationKeys as $keyName => $data) {
        // Insert translation key
        $keyQuery = "INSERT INTO translation_keys (key_name, description, category) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     description = VALUES(description), 
                     category = VALUES(category)";
        $keyStmt = $db->prepare($keyQuery);
        $keyStmt->execute([$keyName, $data['english'], $data['category']]);
        
        // Get the key ID
        $keyId = $db->lastInsertId();
        if ($keyId == 0) {
            // Key already exists, get its ID
            $getKeyQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
            $getKeyStmt = $db->prepare($getKeyQuery);
            $getKeyStmt->execute([$keyName]);
            $keyId = $getKeyStmt->fetchColumn();
        } else {
            $createdKeys++;
        }
        
        // Insert English translation
        $englishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                        VALUES (?, ?, ?, TRUE) 
                        ON DUPLICATE KEY UPDATE 
                        translation_text = VALUES(translation_text), 
                        is_approved = TRUE";
        $englishStmt = $db->prepare($englishQuery);
        $englishStmt->execute([$keyId, $englishId, $data['english']]);
        if ($db->lastInsertId() > 0) $createdTranslations++;
        
        // Insert Spanish translation
        $spanishQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved) 
                        VALUES (?, ?, ?, TRUE) 
                        ON DUPLICATE KEY UPDATE 
                        translation_text = VALUES(translation_text), 
                        is_approved = TRUE";
        $spanishStmt = $db->prepare($spanishQuery);
        $spanishStmt->execute([$keyId, $spanishId, $data['spanish']]);
        if ($db->lastInsertId() > 0) $createdTranslations++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile translation keys created successfully',
        'keys_processed' => count($translationKeys),
        'new_keys_created' => $createdKeys,
        'new_translations_created' => $createdTranslations,
        'categories' => array_unique(array_column($translationKeys, 'category'))
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
