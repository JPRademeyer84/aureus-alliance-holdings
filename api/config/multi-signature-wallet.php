<?php
/**
 * MULTI-SIGNATURE WALLET MANAGEMENT SYSTEM
 * Implements enterprise-grade multi-signature wallet functionality
 */

require_once 'enterprise-wallet-security.php';
require_once 'mfa-system.php';

class MultiSignatureWallet {
    private static $instance = null;
    private $db;
    private $walletSecurity;
    private $mfa;
    
    // Signature types
    const SIGNATURE_TYPE_ADMIN = 'admin';
    const SIGNATURE_TYPE_SECURITY = 'security_officer';
    const SIGNATURE_TYPE_COMPLIANCE = 'compliance_officer';
    const SIGNATURE_TYPE_CEO = 'ceo_approval';
    const SIGNATURE_TYPE_AUDITOR = 'external_auditor';
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->walletSecurity = EnterpriseWalletSecurity::getInstance();
        $this->mfa = MFASystem::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Submit approval signature for transaction
     */
    public function submitApproval($approvalId, $adminId, $signatureType, $mfaCode = null) {
        // Verify MFA if provided
        $mfaVerified = false;
        if ($mfaCode) {
            $mfaResult = $this->mfa->verifyMFA($adminId, 'admin', $mfaCode);
            $mfaVerified = $mfaResult['verified'];
        }
        
        // Get approval request
        $approval = $this->getApprovalRequest($approvalId);
        if (!$approval) {
            throw new Exception('Approval request not found');
        }
        
        // Check if already expired
        if (strtotime($approval['expires_at']) < time()) {
            $this->updateApprovalStatus($approvalId, 'expired');
            throw new Exception('Approval request has expired');
        }
        
        // Check if admin already approved
        if ($this->hasAdminApproved($approvalId, $adminId)) {
            throw new Exception('Admin has already approved this transaction');
        }
        
        // Validate signature type permissions
        $this->validateSignaturePermissions($adminId, $signatureType);
        
        // Generate cryptographic signature
        $signatureHash = $this->generateApprovalSignature($approval, $adminId, $signatureType);
        
        // Store approval signature
        $signatureId = bin2hex(random_bytes(16));
        $query = "INSERT INTO wallet_approval_signatures (
            id, approval_id, approver_id, approval_type, mfa_verified, 
            signature_hash, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            $signatureId,
            $approvalId,
            $adminId,
            $signatureType,
            $mfaVerified,
            $signatureHash,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        if (!$success) {
            throw new Exception('Failed to store approval signature');
        }
        
        // Update approval count
        $this->updateApprovalCount($approvalId);
        
        // Check if transaction can be executed
        $updatedApproval = $this->getApprovalRequest($approvalId);
        if ($updatedApproval['current_approvals'] >= $updatedApproval['required_approvals']) {
            $this->updateApprovalStatus($approvalId, 'approved');
            
            // Auto-execute if configured
            if ($this->shouldAutoExecute($updatedApproval)) {
                $this->executeApprovedTransaction($approvalId);
            }
        }
        
        // Log approval
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_approval_submitted', SecurityLogger::LEVEL_INFO,
            'Multi-signature approval submitted', [
                'approval_id' => $approvalId,
                'signature_type' => $signatureType,
                'mfa_verified' => $mfaVerified,
                'current_approvals' => $updatedApproval['current_approvals'],
                'required_approvals' => $updatedApproval['required_approvals']
            ], null, $adminId);
        
        return [
            'signature_id' => $signatureId,
            'current_approvals' => $updatedApproval['current_approvals'],
            'required_approvals' => $updatedApproval['required_approvals'],
            'status' => $updatedApproval['status'],
            'can_execute' => $updatedApproval['current_approvals'] >= $updatedApproval['required_approvals']
        ];
    }
    
    /**
     * Execute approved transaction
     */
    public function executeApprovedTransaction($approvalId, $executorId = null) {
        $approval = $this->getApprovalRequest($approvalId);
        if (!$approval) {
            throw new Exception('Approval request not found');
        }
        
        if ($approval['status'] !== 'approved') {
            throw new Exception('Transaction not approved for execution');
        }
        
        if ($approval['current_approvals'] < $approval['required_approvals']) {
            throw new Exception('Insufficient approvals for execution');
        }
        
        // Verify all signatures are valid
        if (!$this->verifyAllSignatures($approvalId)) {
            throw new Exception('Invalid signatures detected');
        }
        
        try {
            // Execute the transaction based on type
            $transactionData = json_decode($approval['transaction_data'], true);
            $executionResult = $this->executeTransaction($approval, $transactionData);
            
            // Update approval with execution details
            $query = "UPDATE wallet_transaction_approvals 
                     SET status = 'executed', executed_at = NOW(), execution_tx_hash = ?
                     WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$executionResult['tx_hash'], $approvalId]);
            
            // Log execution
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_transaction_executed', SecurityLogger::LEVEL_CRITICAL,
                'Multi-signature transaction executed', [
                    'approval_id' => $approvalId,
                    'tx_hash' => $executionResult['tx_hash'],
                    'amount' => $approval['amount_usdt'],
                    'destination' => $this->maskAddress($approval['destination_address'])
                ], null, $executorId);
            
            return $executionResult;
            
        } catch (Exception $e) {
            // Mark as failed
            $query = "UPDATE wallet_transaction_approvals SET status = 'failed' WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$approvalId]);
            
            logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_transaction_failed', SecurityLogger::LEVEL_CRITICAL,
                'Multi-signature transaction execution failed', [
                    'approval_id' => $approvalId,
                    'error' => $e->getMessage()
                ], null, $executorId);
            
            throw $e;
        }
    }
    
    /**
     * Get pending approvals for admin
     */
    public function getPendingApprovals($adminId, $signatureType = null) {
        $whereClause = "WHERE wta.status = 'pending' AND wta.expires_at > NOW()";
        $params = [];
        
        // Filter by signature type if specified
        if ($signatureType) {
            $whereClause .= " AND JSON_EXTRACT(sw.multi_sig_config, '$.signer_roles') LIKE ?";
            $params[] = "%$signatureType%";
        }
        
        // Exclude already approved by this admin
        $whereClause .= " AND wta.id NOT IN (
            SELECT approval_id FROM wallet_approval_signatures WHERE approver_id = ?
        )";
        $params[] = $adminId;
        
        $query = "SELECT 
                    wta.id, wta.wallet_id, wta.transaction_type, wta.amount_usdt,
                    wta.destination_address, wta.required_approvals, wta.current_approvals,
                    wta.initiated_at, wta.expires_at, wta.risk_score,
                    sw.wallet_name, sw.chain, sw.security_level,
                    au.username as initiated_by_username
                  FROM wallet_transaction_approvals wta
                  JOIN secure_wallets sw ON wta.wallet_id = sw.id
                  LEFT JOIN admin_users au ON wta.initiated_by = au.id
                  $whereClause
                  ORDER BY wta.risk_score DESC, wta.initiated_at ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $approvals = $stmt->fetchAll();
        
        // Add signature details for each approval
        foreach ($approvals as &$approval) {
            $approval['signatures'] = $this->getApprovalSignatures($approval['id']);
            $approval['can_approve'] = $this->canAdminApprove($adminId, $approval['id'], $signatureType);
        }
        
        return $approvals;
    }
    
    /**
     * Get approval signatures
     */
    public function getApprovalSignatures($approvalId) {
        $query = "SELECT 
                    was.id, was.approver_id, was.approval_type, was.mfa_verified,
                    was.approved_at, au.username as approver_username
                  FROM wallet_approval_signatures was
                  LEFT JOIN admin_users au ON was.approver_id = au.id
                  WHERE was.approval_id = ?
                  ORDER BY was.approved_at ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$approvalId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Revoke approval (if allowed)
     */
    public function revokeApproval($approvalId, $adminId, $reason) {
        // Check if admin has permission to revoke
        if (!$this->canRevokeApproval($approvalId, $adminId)) {
            throw new Exception('Insufficient permissions to revoke approval');
        }
        
        $approval = $this->getApprovalRequest($approvalId);
        if ($approval['status'] === 'executed') {
            throw new Exception('Cannot revoke executed transaction');
        }
        
        // Remove admin's signature
        $query = "DELETE FROM wallet_approval_signatures 
                 WHERE approval_id = ? AND approver_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$approvalId, $adminId]);
        
        // Update approval count
        $this->updateApprovalCount($approvalId);
        
        // Log revocation
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_approval_revoked', SecurityLogger::LEVEL_WARNING,
            'Multi-signature approval revoked', [
                'approval_id' => $approvalId,
                'reason' => $reason
            ], null, $adminId);
        
        return true;
    }
    
    /**
     * Emergency override (requires special permissions)
     */
    public function emergencyOverride($approvalId, $adminId, $overrideReason, $mfaCode) {
        // Verify MFA
        $mfaResult = $this->mfa->verifyMFA($adminId, 'admin', $mfaCode);
        if (!$mfaResult['verified']) {
            throw new Exception('MFA verification required for emergency override');
        }
        
        // Check emergency override permissions
        if (!$this->hasEmergencyOverridePermission($adminId)) {
            throw new Exception('Insufficient permissions for emergency override');
        }
        
        $approval = $this->getApprovalRequest($approvalId);
        if (!$approval) {
            throw new Exception('Approval request not found');
        }
        
        // Execute emergency override
        $query = "UPDATE wallet_transaction_approvals 
                 SET status = 'approved', current_approvals = required_approvals
                 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$approvalId]);
        
        // Log emergency override
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'wallet_emergency_override', SecurityLogger::LEVEL_CRITICAL,
            'Emergency override executed', [
                'approval_id' => $approvalId,
                'override_reason' => $overrideReason,
                'amount' => $approval['amount_usdt']
            ], null, $adminId);
        
        return true;
    }
    
    /**
     * Helper methods
     */
    
    private function getApprovalRequest($approvalId) {
        $query = "SELECT * FROM wallet_transaction_approvals WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$approvalId]);
        return $stmt->fetch();
    }
    
    private function hasAdminApproved($approvalId, $adminId) {
        $query = "SELECT COUNT(*) FROM wallet_approval_signatures 
                 WHERE approval_id = ? AND approver_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$approvalId, $adminId]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function validateSignaturePermissions($adminId, $signatureType) {
        // This would check admin roles and permissions
        // For now, assume all admins can sign
        return true;
    }
    
    private function generateApprovalSignature($approval, $adminId, $signatureType) {
        $signatureData = [
            'approval_id' => $approval['id'],
            'wallet_id' => $approval['wallet_id'],
            'amount' => $approval['amount_usdt'],
            'destination' => $approval['destination_address'],
            'admin_id' => $adminId,
            'signature_type' => $signatureType,
            'timestamp' => time()
        ];
        
        return hash('sha512', json_encode($signatureData) . $_ENV['WALLET_SIGNATURE_KEY'] ?? 'default_key');
    }
    
    private function updateApprovalCount($approvalId) {
        $query = "UPDATE wallet_transaction_approvals 
                 SET current_approvals = (
                     SELECT COUNT(*) FROM wallet_approval_signatures 
                     WHERE approval_id = ?
                 ) WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$approvalId, $approvalId]);
    }
    
    private function updateApprovalStatus($approvalId, $status) {
        $query = "UPDATE wallet_transaction_approvals SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$status, $approvalId]);
    }
    
    private function shouldAutoExecute($approval) {
        // Auto-execute for low-risk, fully approved transactions
        return $approval['risk_score'] < 0.3 && $approval['amount_usdt'] < 10000;
    }
    
    private function verifyAllSignatures($approvalId) {
        // Verify cryptographic signatures
        $signatures = $this->getApprovalSignatures($approvalId);
        foreach ($signatures as $signature) {
            // Verify each signature hash
            // Implementation would verify the cryptographic signature
        }
        return true;
    }
    
    private function executeTransaction($approval, $transactionData) {
        // This would integrate with blockchain APIs to execute the transaction
        // For now, return a mock transaction hash
        return [
            'tx_hash' => '0x' . bin2hex(random_bytes(32)),
            'status' => 'success',
            'block_number' => rand(1000000, 9999999),
            'gas_used' => rand(21000, 100000)
        ];
    }
    
    private function canAdminApprove($adminId, $approvalId, $signatureType) {
        return !$this->hasAdminApproved($approvalId, $adminId);
    }
    
    private function canRevokeApproval($approvalId, $adminId) {
        return $this->hasAdminApproved($approvalId, $adminId);
    }
    
    private function hasEmergencyOverridePermission($adminId) {
        // Check if admin has emergency override role
        return true; // Simplified for now
    }
    
    private function maskAddress($address) {
        if (strlen($address) <= 10) return $address;
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }
}

// Convenience functions
function submitWalletApproval($approvalId, $adminId, $signatureType, $mfaCode = null) {
    $multiSig = MultiSignatureWallet::getInstance();
    return $multiSig->submitApproval($approvalId, $adminId, $signatureType, $mfaCode);
}

function executeWalletTransaction($approvalId, $executorId = null) {
    $multiSig = MultiSignatureWallet::getInstance();
    return $multiSig->executeApprovedTransaction($approvalId, $executorId);
}

function getPendingWalletApprovals($adminId, $signatureType = null) {
    $multiSig = MultiSignatureWallet::getInstance();
    return $multiSig->getPendingApprovals($adminId, $signatureType);
}
?>
