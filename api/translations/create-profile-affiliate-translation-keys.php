<?php
// Create comprehensive translation keys for profile, affiliate, and support (English → Spanish focus)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Simple database connection for command line execution
try {
    $host = 'localhost';
    $dbname = 'aureus_angels';
    $username = 'root';
    $password = '';

    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("set names utf8");
    
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
    
    // Profile, affiliate, and support translation keys
    $translationKeys = [
        // User Profile
        'personal_information' => [
            'category' => 'user_profile',
            'english' => 'Personal Information',
            'spanish' => 'Información Personal'
        ],
        'profile_picture' => [
            'category' => 'user_profile',
            'english' => 'Profile Picture',
            'spanish' => 'Foto de Perfil'
        ],
        'upload_photo' => [
            'category' => 'user_profile',
            'english' => 'Upload Photo',
            'spanish' => 'Subir Foto'
        ],
        'change_password' => [
            'category' => 'user_profile',
            'english' => 'Change Password',
            'spanish' => 'Cambiar Contraseña'
        ],
        'current_password' => [
            'category' => 'user_profile',
            'english' => 'Current Password',
            'spanish' => 'Contraseña Actual'
        ],
        'new_password' => [
            'category' => 'user_profile',
            'english' => 'New Password',
            'spanish' => 'Nueva Contraseña'
        ],
        'confirm_new_password' => [
            'category' => 'user_profile',
            'english' => 'Confirm New Password',
            'spanish' => 'Confirmar Nueva Contraseña'
        ],
        'save_changes' => [
            'category' => 'user_profile',
            'english' => 'Save Changes',
            'spanish' => 'Guardar Cambios'
        ],
        'profile_updated_successfully' => [
            'category' => 'user_profile',
            'english' => 'Profile updated successfully',
            'spanish' => 'Perfil actualizado exitosamente'
        ],
        
        // KYC Verification
        'kyc_verification' => [
            'category' => 'kyc',
            'english' => 'KYC Verification',
            'spanish' => 'Verificación KYC'
        ],
        'identity_verification' => [
            'category' => 'kyc',
            'english' => 'Identity Verification',
            'spanish' => 'Verificación de Identidad'
        ],
        'upload_id_document' => [
            'category' => 'kyc',
            'english' => 'Upload ID Document',
            'spanish' => 'Subir Documento de Identidad'
        ],
        'drivers_license' => [
            'category' => 'kyc',
            'english' => 'Driver\'s License',
            'spanish' => 'Licencia de Conducir'
        ],
        'national_id' => [
            'category' => 'kyc',
            'english' => 'National ID',
            'spanish' => 'Cédula Nacional'
        ],
        'passport' => [
            'category' => 'kyc',
            'english' => 'Passport',
            'spanish' => 'Pasaporte'
        ],
        'verification_pending' => [
            'category' => 'kyc',
            'english' => 'Verification Pending',
            'spanish' => 'Verificación Pendiente'
        ],
        'verification_approved' => [
            'category' => 'kyc',
            'english' => 'Verification Approved',
            'spanish' => 'Verificación Aprobada'
        ],
        'verification_rejected' => [
            'category' => 'kyc',
            'english' => 'Verification Rejected',
            'spanish' => 'Verificación Rechazada'
        ],
        
        // Affiliate Program
        'referral_link' => [
            'category' => 'affiliate',
            'english' => 'Referral Link',
            'spanish' => 'Enlace de Referido'
        ],
        'copy_link' => [
            'category' => 'affiliate',
            'english' => 'Copy Link',
            'spanish' => 'Copiar Enlace'
        ],
        'share_on_social_media' => [
            'category' => 'affiliate',
            'english' => 'Share on Social Media',
            'spanish' => 'Compartir en Redes Sociales'
        ],
        'referral_statistics' => [
            'category' => 'affiliate',
            'english' => 'Referral Statistics',
            'spanish' => 'Estadísticas de Referidos'
        ],
        'total_referrals' => [
            'category' => 'affiliate',
            'english' => 'Total Referrals',
            'spanish' => 'Total de Referidos'
        ],
        'active_referrals' => [
            'category' => 'affiliate',
            'english' => 'Active Referrals',
            'spanish' => 'Referidos Activos'
        ],
        'commission_earned' => [
            'category' => 'affiliate',
            'english' => 'Commission Earned',
            'spanish' => 'Comisión Ganada'
        ],
        'pending_commissions' => [
            'category' => 'affiliate',
            'english' => 'Pending Commissions',
            'spanish' => 'Comisiones Pendientes'
        ],
        'commission_history' => [
            'category' => 'affiliate',
            'english' => 'Commission History',
            'spanish' => 'Historial de Comisiones'
        ],
        'withdraw_commissions' => [
            'category' => 'affiliate',
            'english' => 'Withdraw Commissions',
            'spanish' => 'Retirar Comisiones'
        ],
        'minimum_withdrawal' => [
            'category' => 'affiliate',
            'english' => 'Minimum Withdrawal',
            'spanish' => 'Retiro Mínimo'
        ],
        'withdrawal_address' => [
            'category' => 'affiliate',
            'english' => 'Withdrawal Address',
            'spanish' => 'Dirección de Retiro'
        ],
        
        // Support & Contact
        'contact_subject' => [
            'category' => 'support',
            'english' => 'Subject',
            'spanish' => 'Asunto'
        ],
        'message' => [
            'category' => 'support',
            'english' => 'Message',
            'spanish' => 'Mensaje'
        ],
        'send_message' => [
            'category' => 'support',
            'english' => 'Send Message',
            'spanish' => 'Enviar Mensaje'
        ],
        'live_chat' => [
            'category' => 'support',
            'english' => 'Live Chat',
            'spanish' => 'Chat en Vivo'
        ],
        'start_chat' => [
            'category' => 'support',
            'english' => 'Start Chat',
            'spanish' => 'Iniciar Chat'
        ],
        'chat_with_support' => [
            'category' => 'support',
            'english' => 'Chat with Support',
            'spanish' => 'Chatear con Soporte'
        ],
        'support_hours' => [
            'category' => 'support',
            'english' => 'Support Hours',
            'spanish' => 'Horarios de Soporte'
        ],
        'monday_to_friday' => [
            'category' => 'support',
            'english' => 'Monday to Friday',
            'spanish' => 'Lunes a Viernes'
        ],
        'business_hours' => [
            'category' => 'support',
            'english' => '9:00 AM - 6:00 PM EST',
            'spanish' => '9:00 AM - 6:00 PM EST'
        ],
        'offline_message' => [
            'category' => 'support',
            'english' => 'Leave an offline message',
            'spanish' => 'Dejar un mensaje fuera de línea'
        ],
        
        // Gold Diggers Club / Leaderboard
        'leaderboard' => [
            'category' => 'leaderboard',
            'english' => 'Leaderboard',
            'spanish' => 'Tabla de Clasificación'
        ],
        'rank' => [
            'category' => 'leaderboard',
            'english' => 'Rank',
            'spanish' => 'Rango'
        ],
        'username' => [
            'category' => 'leaderboard',
            'english' => 'Username',
            'spanish' => 'Nombre de Usuario'
        ],
        'total_referrals' => [
            'category' => 'leaderboard',
            'english' => 'Total Referrals',
            'spanish' => 'Total de Referidos'
        ],
        'bonus_pool_share' => [
            'category' => 'leaderboard',
            'english' => 'Bonus Pool Share',
            'spanish' => 'Participación en Fondo de Bonificación'
        ],
        'qualification_period' => [
            'category' => 'leaderboard',
            'english' => 'Qualification Period',
            'spanish' => 'Período de Calificación'
        ],
        'minimum_referrals_required' => [
            'category' => 'leaderboard',
            'english' => 'Minimum $2,500 in direct referrals required',
            'spanish' => 'Se requiere un mínimo de $2,500 en referidos directos'
        ],
        
        // Portfolio & Investment History
        'investment_date' => [
            'category' => 'portfolio',
            'english' => 'Investment Date',
            'spanish' => 'Fecha de Inversión'
        ],
        'package_name' => [
            'category' => 'portfolio',
            'english' => 'Package Name',
            'spanish' => 'Nombre del Paquete'
        ],
        'amount_invested' => [
            'category' => 'portfolio',
            'english' => 'Amount Invested',
            'spanish' => 'Monto Invertido'
        ],
        'current_value' => [
            'category' => 'portfolio',
            'english' => 'Current Value',
            'spanish' => 'Valor Actual'
        ],
        'roi_percentage' => [
            'category' => 'portfolio',
            'english' => 'ROI %',
            'spanish' => 'ROI %'
        ],
        'status' => [
            'category' => 'portfolio',
            'english' => 'Status',
            'spanish' => 'Estado'
        ],
        'maturity_date' => [
            'category' => 'portfolio',
            'english' => 'Maturity Date',
            'spanish' => 'Fecha de Vencimiento'
        ],
        'days_remaining' => [
            'category' => 'portfolio',
            'english' => 'Days Remaining',
            'spanish' => 'Días Restantes'
        ],
        
        // NFT Coupons
        'coupon_code' => [
            'category' => 'nft_coupons',
            'english' => 'Coupon Code',
            'spanish' => 'Código de Cupón'
        ],
        'redeem_coupon' => [
            'category' => 'nft_coupons',
            'english' => 'Redeem Coupon',
            'spanish' => 'Canjear Cupón'
        ],
        'enter_coupon_code' => [
            'category' => 'nft_coupons',
            'english' => 'Enter coupon code',
            'spanish' => 'Ingresa el código de cupón'
        ],
        'coupon_redeemed_successfully' => [
            'category' => 'nft_coupons',
            'english' => 'Coupon redeemed successfully!',
            'spanish' => '¡Cupón canjeado exitosamente!'
        ],
        'invalid_coupon_code' => [
            'category' => 'nft_coupons',
            'english' => 'Invalid coupon code',
            'spanish' => 'Código de cupón inválido'
        ],
        'coupon_already_used' => [
            'category' => 'nft_coupons',
            'english' => 'Coupon already used',
            'spanish' => 'Cupón ya utilizado'
        ],
        
        // General Actions
        'edit' => [
            'category' => 'general_actions',
            'english' => 'Edit',
            'spanish' => 'Editar'
        ],
        'delete' => [
            'category' => 'general_actions',
            'english' => 'Delete',
            'spanish' => 'Eliminar'
        ],
        'cancel' => [
            'category' => 'general_actions',
            'english' => 'Cancel',
            'spanish' => 'Cancelar'
        ],
        'confirm' => [
            'category' => 'general_actions',
            'english' => 'Confirm',
            'spanish' => 'Confirmar'
        ],
        'submit' => [
            'category' => 'general_actions',
            'english' => 'Submit',
            'spanish' => 'Enviar'
        ],
        'close' => [
            'category' => 'general_actions',
            'english' => 'Close',
            'spanish' => 'Cerrar'
        ],
        'back' => [
            'category' => 'general_actions',
            'english' => 'Back',
            'spanish' => 'Atrás'
        ],
        'next' => [
            'category' => 'general_actions',
            'english' => 'Next',
            'spanish' => 'Siguiente'
        ],
        'previous' => [
            'category' => 'general_actions',
            'english' => 'Previous',
            'spanish' => 'Anterior'
        ],
        'refresh' => [
            'category' => 'general_actions',
            'english' => 'Refresh',
            'spanish' => 'Actualizar'
        ]
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
        'message' => 'Profile, affiliate, and support translation keys created successfully',
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
