<?php
class KYCLevelService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get user's current KYC level
     */
    public function getUserLevel($userId) {
        try {
            $query = "SELECT current_level FROM user_kyc_levels WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['current_level'] : 1; // Default to level 1
        } catch (Exception $e) {
            error_log("Error getting user KYC level: " . $e->getMessage());
            return 1; // Default to level 1 on error
        }
    }
    
    /**
     * Get commission rate based on user's KYC level
     */
    public function getCommissionRate($userId) {
        $level = $this->getUserLevel($userId);
        
        switch ($level) {
            case 1:
                return 0.05; // 5%
            case 2:
                return 0.07; // 7%
            case 3:
                return 0.10; // 10%
            default:
                return 0.05; // Default 5%
        }
    }
    
    /**
     * Get investment limits based on user's KYC level
     */
    public function getInvestmentLimits($userId) {
        $level = $this->getUserLevel($userId);
        
        switch ($level) {
            case 1:
                return [
                    'min' => 25,
                    'max' => 100,
                    'allowed_packages' => ['basic']
                ];
            case 2:
                return [
                    'min' => 25,
                    'max' => 500,
                    'allowed_packages' => ['basic', 'intermediate']
                ];
            case 3:
                return [
                    'min' => 25,
                    'max' => 1000,
                    'allowed_packages' => ['basic', 'intermediate', 'premium']
                ];
            default:
                return [
                    'min' => 25,
                    'max' => 100,
                    'allowed_packages' => ['basic']
                ];
        }
    }
    
    /**
     * Get withdrawal limits based on user's KYC level
     */
    public function getWithdrawalLimits($userId) {
        $level = $this->getUserLevel($userId);
        
        switch ($level) {
            case 1:
                return [
                    'daily' => 1000,
                    'monthly' => 30000
                ];
            case 2:
                return [
                    'daily' => 10000,
                    'monthly' => 300000
                ];
            case 3:
                return [
                    'daily' => -1, // Unlimited
                    'monthly' => -1 // Unlimited
                ];
            default:
                return [
                    'daily' => 1000,
                    'monthly' => 30000
                ];
        }
    }
    
    /**
     * Get NFT purchase limits based on user's KYC level
     */
    public function getNFTLimits($userId) {
        $level = $this->getUserLevel($userId);
        
        switch ($level) {
            case 1:
                return [
                    'monthly' => 10,
                    'total' => 100
                ];
            case 2:
                return [
                    'monthly' => 50,
                    'total' => 500
                ];
            case 3:
                return [
                    'monthly' => -1, // Unlimited
                    'total' => -1 // Unlimited
                ];
            default:
                return [
                    'monthly' => 10,
                    'total' => 100
                ];
        }
    }
    
    /**
     * Check if user can access a specific feature
     */
    public function canAccessFeature($userId, $feature) {
        $level = $this->getUserLevel($userId);
        
        $featureRequirements = [
            'advanced_analytics' => 2,
            'api_access' => 3,
            'priority_support' => 2,
            'vip_support' => 3,
            'white_label_materials' => 3,
            'early_access' => 3
        ];
        
        return $level >= ($featureRequirements[$feature] ?? 1);
    }
    
    /**
     * Get support tier based on user's KYC level
     */
    public function getSupportTier($userId) {
        $level = $this->getUserLevel($userId);
        
        switch ($level) {
            case 1:
                return 'standard';
            case 2:
                return 'priority';
            case 3:
                return 'vip';
            default:
                return 'standard';
        }
    }
    
    /**
     * Check if user can purchase a specific investment package
     */
    public function canPurchasePackage($userId, $packageAmount, $packageType = null) {
        $limits = $this->getInvestmentLimits($userId);
        
        // Check amount limits
        if ($packageAmount < $limits['min'] || $packageAmount > $limits['max']) {
            return false;
        }
        
        // Check package type if specified
        if ($packageType && !in_array($packageType, $limits['allowed_packages'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user can withdraw a specific amount
     */
    public function canWithdraw($userId, $amount, $period = 'daily') {
        $limits = $this->getWithdrawalLimits($userId);
        
        if ($limits[$period] === -1) {
            return true; // Unlimited
        }
        
        // Check current usage (would need additional tracking)
        // For now, just check against the limit
        return $amount <= $limits[$period];
    }
    
    /**
     * Get user's KYC level benefits summary
     */
    public function getLevelBenefits($userId) {
        $level = $this->getUserLevel($userId);
        
        return [
            'level' => $level,
            'commission_rate' => $this->getCommissionRate($userId),
            'investment_limits' => $this->getInvestmentLimits($userId),
            'withdrawal_limits' => $this->getWithdrawalLimits($userId),
            'nft_limits' => $this->getNFTLimits($userId),
            'support_tier' => $this->getSupportTier($userId),
            'features' => [
                'advanced_analytics' => $this->canAccessFeature($userId, 'advanced_analytics'),
                'api_access' => $this->canAccessFeature($userId, 'api_access'),
                'priority_support' => $this->canAccessFeature($userId, 'priority_support'),
                'vip_support' => $this->canAccessFeature($userId, 'vip_support'),
                'white_label_materials' => $this->canAccessFeature($userId, 'white_label_materials'),
                'early_access' => $this->canAccessFeature($userId, 'early_access')
            ]
        ];
    }
    
    /**
     * Initialize user KYC level record if it doesn't exist
     */
    public function initializeUserLevel($userId) {
        try {
            $checkQuery = "SELECT id FROM user_kyc_levels WHERE user_id = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$userId]);
            
            if (!$checkStmt->fetch()) {
                $insertQuery = "INSERT INTO user_kyc_levels (user_id, current_level) VALUES (?, 1)";
                $insertStmt = $this->db->prepare($insertQuery);
                $insertStmt->execute([$userId]);
            }
        } catch (Exception $e) {
            error_log("Error initializing user KYC level: " . $e->getMessage());
        }
    }
    
    /**
     * Update user's KYC level
     */
    public function updateUserLevel($userId, $newLevel, $adminId = null) {
        try {
            $this->initializeUserLevel($userId);
            
            $updateQuery = "UPDATE user_kyc_levels SET 
                current_level = ?,
                level_{$newLevel}_completed_at = NOW(),
                updated_at = NOW()
                WHERE user_id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$newLevel, $userId]);
            
            // Log the level change
            if ($adminId) {
                $logQuery = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) 
                             VALUES (?, 'kyc_level_update', 'user', ?, ?, NOW())";
                $logStmt = $this->db->prepare($logQuery);
                $logStmt->execute([$adminId, $userId, json_encode(['new_level' => $newLevel])]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating user KYC level: " . $e->getMessage());
            return false;
        }
    }
}
?>
