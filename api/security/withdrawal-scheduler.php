<?php
/**
 * BUSINESS HOURS WITHDRAWAL SCHEDULER
 * Processes withdrawals Monday-Friday 9AM-4PM on 24-hour basis
 * No automated withdrawals - admin manual processing only
 */

class WithdrawalScheduler {
    private $db;
    private $securityManager;
    
    // Business hours: Monday-Friday 9AM-4PM
    private $businessDays = [1, 2, 3, 4, 5]; // Monday = 1, Friday = 5
    private $businessStartHour = 9;
    private $businessEndHour = 16; // 4PM in 24-hour format
    
    public function __construct($database, $securityManager) {
        $this->db = $database;
        $this->securityManager = $securityManager;
        $this->initializeWithdrawalTables();
    }
    
    private function initializeWithdrawalTables() {
        // Enhanced withdrawal requests table with business hours tracking
        $this->db->exec("CREATE TABLE IF NOT EXISTS secure_withdrawal_requests (
            id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            user_id INT NOT NULL,
            withdrawal_type ENUM('usdt', 'nft') NOT NULL,
            requested_amount_usdt DECIMAL(15, 8) DEFAULT 0.00000000,
            requested_amount_nft INT DEFAULT 0,
            wallet_address VARCHAR(255) NOT NULL,
            request_hash VARCHAR(128) NOT NULL,
            status ENUM('pending', 'queued_for_processing', 'processing', 'completed', 'failed', 'cancelled', 'outside_business_hours') DEFAULT 'pending',
            
            -- Business hours tracking
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            queued_at TIMESTAMP NULL,
            processing_started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            next_business_day TIMESTAMP NULL,
            
            -- Admin processing
            admin_id INT NULL,
            admin_notes TEXT,
            transaction_hash VARCHAR(128),
            blockchain_confirmation_hash VARCHAR(128),
            
            -- Security
            ip_address VARCHAR(45),
            user_agent TEXT,
            security_verification_hash VARCHAR(128),
            
            -- Audit trail
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_requested_at (requested_at),
            INDEX idx_next_business_day (next_business_day),
            INDEX idx_admin_id (admin_id)
        )");
        
        // Withdrawal processing queue for business hours
        $this->db->exec("CREATE TABLE IF NOT EXISTS withdrawal_processing_queue (
            queue_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
            withdrawal_request_id VARCHAR(36) NOT NULL,
            user_id INT NOT NULL,
            priority_level INT DEFAULT 1,
            scheduled_for_date DATE NOT NULL,
            scheduled_for_hour INT NOT NULL,
            queue_status ENUM('scheduled', 'ready_for_admin', 'being_processed', 'completed', 'failed') DEFAULT 'scheduled',
            admin_assigned INT NULL,
            queue_position INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (withdrawal_request_id) REFERENCES secure_withdrawal_requests(id),
            INDEX idx_scheduled_date (scheduled_for_date),
            INDEX idx_queue_status (queue_status),
            INDEX idx_priority (priority_level),
            INDEX idx_admin_assigned (admin_assigned)
        )");
        
        // Business hours configuration
        $this->db->exec("CREATE TABLE IF NOT EXISTS business_hours_config (
            id INT PRIMARY KEY AUTO_INCREMENT,
            day_of_week INT NOT NULL, -- 1=Monday, 7=Sunday
            start_hour INT NOT NULL,
            end_hour INT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            timezone VARCHAR(50) DEFAULT 'UTC',
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_day (day_of_week)
        )");
        
        // Initialize default business hours if not exists
        $this->initializeDefaultBusinessHours();
    }
    
    private function initializeDefaultBusinessHours() {
        $checkQuery = "SELECT COUNT(*) as count FROM business_hours_config";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute();
        $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count == 0) {
            // Insert Monday-Friday 9AM-4PM
            for ($day = 1; $day <= 5; $day++) {
                $insertQuery = "INSERT INTO business_hours_config (day_of_week, start_hour, end_hour) VALUES (?, ?, ?)";
                $insertStmt = $this->db->prepare($insertQuery);
                $insertStmt->execute([$day, 9, 16]);
            }
        }
    }
    
    /**
     * Check if current time is within business hours
     */
    public function isWithinBusinessHours($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $dayOfWeek = (int)date('N', $timestamp); // 1=Monday, 7=Sunday
        $hour = (int)date('G', $timestamp); // 24-hour format
        
        // Check if it's a business day
        $businessHoursQuery = "SELECT start_hour, end_hour FROM business_hours_config WHERE day_of_week = ? AND is_active = TRUE";
        $businessHoursStmt = $this->db->prepare($businessHoursQuery);
        $businessHoursStmt->execute([$dayOfWeek]);
        $businessHours = $businessHoursStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$businessHours) {
            return false; // Not a business day
        }
        
        return ($hour >= $businessHours['start_hour'] && $hour < $businessHours['end_hour']);
    }
    
    /**
     * Get next business day timestamp
     */
    public function getNextBusinessDay($fromTimestamp = null) {
        if ($fromTimestamp === null) {
            $fromTimestamp = time();
        }
        
        $nextDay = $fromTimestamp;
        
        // Find next business day
        for ($i = 0; $i < 7; $i++) {
            $nextDay = strtotime('+1 day', $nextDay);
            $dayOfWeek = (int)date('N', $nextDay);
            
            $businessDayQuery = "SELECT start_hour FROM business_hours_config WHERE day_of_week = ? AND is_active = TRUE";
            $businessDayStmt = $this->db->prepare($businessDayQuery);
            $businessDayStmt->execute([$dayOfWeek]);
            $businessDay = $businessDayStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($businessDay) {
                // Set to start of business hours
                return strtotime(date('Y-m-d', $nextDay) . ' ' . $businessDay['start_hour'] . ':00:00');
            }
        }
        
        return false; // No business day found (shouldn't happen)
    }
    
    /**
     * Submit withdrawal request with business hours validation
     */
    public function submitWithdrawalRequest($userId, $withdrawalType, $amountUsdt, $amountNft, $walletAddress) {
        $this->db->beginTransaction();
        
        try {
            // Verify user balance integrity
            $userBalance = $this->securityManager->getSecureUserBalance($userId);
            
            // Validate withdrawal amount
            if ($withdrawalType === 'usdt') {
                if ($amountUsdt <= 0 || $amountUsdt > $userBalance['available_usdt_balance']) {
                    throw new Exception('Invalid USDT withdrawal amount or insufficient balance');
                }
            } elseif ($withdrawalType === 'nft') {
                if ($amountNft <= 0 || $amountNft > $userBalance['available_nft_balance']) {
                    throw new Exception('Invalid NFT withdrawal amount or insufficient balance');
                }
            }
            
            // Generate security hashes
            $requestData = [
                'user_id' => $userId,
                'type' => $withdrawalType,
                'amount_usdt' => $amountUsdt,
                'amount_nft' => $amountNft,
                'wallet' => $walletAddress,
                'timestamp' => microtime(true)
            ];
            
            $requestHash = hash('sha256', json_encode($requestData) . 'WITHDRAWAL_SECURITY_KEY');
            $securityVerificationHash = hash('sha512', json_encode($requestData) . $userId . time());
            
            // Determine status based on business hours
            $currentTime = time();
            $status = $this->isWithinBusinessHours($currentTime) ? 'pending' : 'outside_business_hours';
            $nextBusinessDay = $this->isWithinBusinessHours($currentTime) ? null : date('Y-m-d H:i:s', $this->getNextBusinessDay($currentTime));
            
            // Generate withdrawal ID
            $withdrawalId = uniqid('withdrawal_', true);

            // Insert withdrawal request
            $insertQuery = "INSERT INTO secure_withdrawal_requests (
                id, user_id, withdrawal_type, requested_amount_usdt, requested_amount_nft,
                wallet_address, request_hash, status, next_business_day,
                ip_address, user_agent, security_verification_hash
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->execute([
                $withdrawalId, $userId, $withdrawalType, $amountUsdt, $amountNft,
                $walletAddress, $requestHash, $status, $nextBusinessDay,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $securityVerificationHash
            ]);
            
            // If within business hours, add to processing queue
            if ($status === 'pending') {
                $this->addToProcessingQueue($withdrawalId, $userId, $currentTime);
            }
            
            // Log transaction
            $this->securityManager->logTransaction(
                $userId, 
                'withdrawal_requested', 
                $amountUsdt, 
                $amountNft, 
                $userBalance['available_usdt_balance'], 
                $userBalance['available_nft_balance'],
                $userBalance['available_usdt_balance'], 
                $userBalance['available_nft_balance'],
                null, 
                null, 
                $withdrawalId
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'withdrawal_id' => $withdrawalId,
                'status' => $status,
                'message' => $status === 'pending' 
                    ? 'Withdrawal request submitted and queued for processing'
                    : 'Withdrawal request submitted. Will be processed on next business day: ' . $nextBusinessDay,
                'next_business_day' => $nextBusinessDay
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Add withdrawal to processing queue
     */
    private function addToProcessingQueue($withdrawalId, $userId, $timestamp) {
        $scheduledDate = date('Y-m-d', $timestamp);
        $scheduledHour = (int)date('G', $timestamp);
        
        // Get queue position
        $positionQuery = "SELECT COALESCE(MAX(queue_position), 0) + 1 as next_position FROM withdrawal_processing_queue WHERE scheduled_for_date = ?";
        $positionStmt = $this->db->prepare($positionQuery);
        $positionStmt->execute([$scheduledDate]);
        $position = $positionStmt->fetch(PDO::FETCH_ASSOC)['next_position'];
        
        $queueQuery = "INSERT INTO withdrawal_processing_queue (
            withdrawal_request_id, user_id, scheduled_for_date, scheduled_for_hour, queue_position
        ) VALUES (?, ?, ?, ?, ?)";
        
        $queueStmt = $this->db->prepare($queueQuery);
        $queueStmt->execute([$withdrawalId, $userId, $scheduledDate, $scheduledHour, $position]);
    }
    
    /**
     * Get pending withdrawals for admin processing (business hours only)
     */
    public function getPendingWithdrawalsForAdmin() {
        if (!$this->isWithinBusinessHours()) {
            return [
                'success' => false,
                'message' => 'Withdrawal processing is only available during business hours (Monday-Friday 9AM-4PM)',
                'withdrawals' => []
            ];
        }
        
        $query = "
            SELECT 
                swr.*,
                u.username,
                u.email,
                wpq.queue_position,
                wpq.priority_level
            FROM secure_withdrawal_requests swr
            LEFT JOIN users u ON swr.user_id = u.id
            LEFT JOIN withdrawal_processing_queue wpq ON swr.id = wpq.withdrawal_request_id
            WHERE swr.status IN ('pending', 'queued_for_processing')
            AND (wpq.scheduled_for_date = CURDATE() OR swr.status = 'pending')
            ORDER BY wpq.priority_level DESC, wpq.queue_position ASC, swr.requested_at ASC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'withdrawals' => $withdrawals,
            'business_hours_active' => true,
            'current_time' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Admin processes withdrawal with blockchain hash
     */
    public function adminProcessWithdrawal($withdrawalId, $adminId, $status, $transactionHash, $blockchainHash, $adminNotes) {
        if (!$this->isWithinBusinessHours()) {
            throw new Exception('Withdrawal processing is only available during business hours');
        }
        
        $this->db->beginTransaction();
        
        try {
            // Get withdrawal request
            $withdrawalQuery = "SELECT * FROM secure_withdrawal_requests WHERE id = ?";
            $withdrawalStmt = $this->db->prepare($withdrawalQuery);
            $withdrawalStmt->execute([$withdrawalId]);
            $withdrawal = $withdrawalStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$withdrawal) {
                throw new Exception('Withdrawal request not found');
            }
            
            // Verify user balance integrity
            $userBalance = $this->securityManager->getSecureUserBalance($withdrawal['user_id']);
            
            if ($status === 'completed') {
                // Deduct from user balance
                $newUsdtBalance = $userBalance['available_usdt_balance'] - $withdrawal['requested_amount_usdt'];
                $newNftBalance = $userBalance['available_nft_balance'] - $withdrawal['requested_amount_nft'];
                $newUsdtWithdrawn = $userBalance['total_usdt_withdrawn'] + $withdrawal['requested_amount_usdt'];
                $newNftRedeemed = $userBalance['total_nft_redeemed'] + $withdrawal['requested_amount_nft'];
                
                // Update secure balance
                $this->securityManager->updateUserBalance(
                    $withdrawal['user_id'],
                    $userBalance['total_usdt_earned'],
                    $userBalance['total_nft_earned'],
                    $newUsdtBalance,
                    $newNftBalance,
                    $newUsdtWithdrawn,
                    $newNftRedeemed,
                    $withdrawalId,
                    $adminId
                );
            }
            
            // Update withdrawal request
            $updateQuery = "UPDATE secure_withdrawal_requests SET 
                status = ?, admin_id = ?, admin_notes = ?, transaction_hash = ?, 
                blockchain_confirmation_hash = ?, completed_at = NOW(),
                processing_started_at = COALESCE(processing_started_at, NOW())
                WHERE id = ?";
            
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([
                $status, $adminId, $adminNotes, $transactionHash, 
                $blockchainHash, $withdrawalId
            ]);
            
            // Update queue status
            $queueUpdateQuery = "UPDATE withdrawal_processing_queue SET 
                queue_status = 'completed', admin_assigned = ? 
                WHERE withdrawal_request_id = ?";
            $queueUpdateStmt = $this->db->prepare($queueUpdateQuery);
            $queueUpdateStmt->execute([$adminId, $withdrawalId]);
            
            // Log transaction
            $this->securityManager->logTransaction(
                $withdrawal['user_id'],
                $status === 'completed' ? 'withdrawal_completed' : 'withdrawal_failed',
                $withdrawal['requested_amount_usdt'],
                $withdrawal['requested_amount_nft'],
                $userBalance['available_usdt_balance'],
                $userBalance['available_nft_balance'],
                $status === 'completed' ? $newUsdtBalance : $userBalance['available_usdt_balance'],
                $status === 'completed' ? $newNftBalance : $userBalance['available_nft_balance'],
                $blockchainHash,
                $adminId,
                $withdrawalId
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "Withdrawal {$status} successfully",
                'withdrawal_id' => $withdrawalId,
                'transaction_hash' => $transactionHash,
                'blockchain_hash' => $blockchainHash
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
?>
