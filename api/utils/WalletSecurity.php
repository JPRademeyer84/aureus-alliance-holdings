<?php

class WalletSecurity {
    
    /**
     * Generate a random salt for encryption
     */
    public static function generateSalt(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Hash a wallet address with salt using AES-256-CBC encryption
     */
    public static function hashWalletAddress(string $address, string $salt): string {
        // Use a combination of salt and environment-specific key
        $key = hash('sha256', $salt . self::getEncryptionKey());
        
        // Generate a random IV for each encryption
        $iv = random_bytes(16);
        
        // Encrypt the address
        $encrypted = openssl_encrypt($address, 'AES-256-CBC', $key, 0, $iv);
        
        // Combine IV and encrypted data, then base64 encode
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt a hashed wallet address
     */
    public static function decryptWalletAddress(string $hashedAddress, string $salt): string {
        // Decode the base64 data
        $data = base64_decode($hashedAddress);
        
        // Extract IV (first 16 bytes) and encrypted data
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        // Recreate the key
        $key = hash('sha256', $salt . self::getEncryptionKey());
        
        // Decrypt the address
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception('Failed to decrypt wallet address');
        }
        
        return $decrypted;
    }
    
    /**
     * Get encryption key from environment or generate a default one
     * In production, this should be stored in environment variables
     */
    private static function getEncryptionKey(): string {
        // Try to get from environment variable first
        $envKey = getenv('WALLET_ENCRYPTION_KEY');
        if ($envKey) {
            return $envKey;
        }
        
        // Fallback to a server-specific key (not recommended for production)
        // In production, you should set WALLET_ENCRYPTION_KEY environment variable
        return hash('sha256', $_SERVER['SERVER_NAME'] ?? 'aureus-angel-alliance' . 'wallet-security-key-2024');
    }
    
    /**
     * Validate wallet address format
     */
    public static function validateWalletAddress(string $address, string $chain): bool {
        switch (strtolower($chain)) {
            case 'ethereum':
            case 'bsc':
            case 'polygon':
                // Ethereum-style address validation
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
                
            case 'tron':
                // TRON address validation (starts with T and is 34 characters)
                return preg_match('/^T[a-zA-Z0-9]{33}$/', $address) === 1;
                
            default:
                return false;
        }
    }
    
    /**
     * Sanitize wallet address input
     */
    public static function sanitizeWalletAddress(string $address): string {
        // Remove any whitespace
        $address = trim($address);
        
        // Convert to lowercase for Ethereum-style addresses
        if (str_starts_with($address, '0x')) {
            return strtolower($address);
        }
        
        return $address;
    }
    
    /**
     * Generate a secure audit log entry for wallet operations
     */
    public static function generateAuditLog(string $operation, string $chain, string $adminId, array $additionalData = []): array {
        return [
            'operation' => $operation,
            'chain' => $chain,
            'admin_id' => $adminId,
            'timestamp' => date('c'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'additional_data' => $additionalData
        ];
    }
    
    /**
     * Verify admin permissions for wallet operations
     */
    public static function verifyAdminPermissions(PDO $db, string $adminId): bool {
        try {
            $query = "SELECT id FROM admin_users WHERE id = ? AND created_at IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute([$adminId]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Admin permission verification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a masked version of wallet address for display purposes
     */
    public static function maskWalletAddress(string $address): string {
        if (strlen($address) < 10) {
            return str_repeat('*', strlen($address));
        }
        
        $start = substr($address, 0, 6);
        $end = substr($address, -4);
        $middle = str_repeat('*', strlen($address) - 10);
        
        return $start . $middle . $end;
    }
    
    /**
     * Generate a secure backup of wallet data
     */
    public static function generateSecureBackup(array $walletData, string $adminId): array {
        $backup = [
            'version' => '1.0',
            'created_at' => date('c'),
            'created_by' => $adminId,
            'checksum' => '',
            'wallets' => []
        ];
        
        foreach ($walletData as $wallet) {
            $backup['wallets'][] = [
                'chain' => $wallet['chain'],
                'address_hash' => $wallet['address_hash'],
                'salt' => $wallet['salt'],
                'is_active' => $wallet['is_active'],
                'created_at' => $wallet['created_at']
            ];
        }
        
        // Generate checksum for integrity verification
        $backup['checksum'] = hash('sha256', json_encode($backup['wallets']) . $adminId);
        
        return $backup;
    }
    
    /**
     * Verify backup integrity
     */
    public static function verifyBackupIntegrity(array $backup): bool {
        if (!isset($backup['wallets'], $backup['checksum'], $backup['created_by'])) {
            return false;
        }
        
        $expectedChecksum = hash('sha256', json_encode($backup['wallets']) . $backup['created_by']);
        return hash_equals($expectedChecksum, $backup['checksum']);
    }
}
?>
