<?php
/**
 * Phase Management System
 * Handles phase transitions, share limits, and availability checks
 */

class PhaseManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get current active phase
     */
    public function getCurrentPhase() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM phases 
            WHERE is_active = TRUE 
            ORDER BY phase_number ASC 
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total shares sold in a specific phase
     */
    public function getPhaseSharesSold($phaseId) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(ai.shares), 0) as total_shares_sold
            FROM aureus_investments ai
            JOIN investment_packages ip ON ai.package_name = ip.name
            WHERE ip.phase_id = ? AND ai.status = 'completed'
        ");
        $stmt->execute([$phaseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total_shares_sold'];
    }
    
    /**
     * Get pending shares (not yet completed) for a phase
     */
    public function getPhasePendingShares($phaseId) {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(ai.shares), 0) as pending_shares
            FROM aureus_investments ai
            JOIN investment_packages ip ON ai.package_name = ip.name
            WHERE ip.phase_id = ? AND ai.status = 'pending'
        ");
        $stmt->execute([$phaseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['pending_shares'];
    }
    
    /**
     * Check if a phase can accommodate additional shares
     */
    public function canPurchaseShares($phaseId, $requestedShares) {
        $phase = $this->getPhaseById($phaseId);
        if (!$phase) {
            return ['success' => false, 'error' => 'Phase not found'];
        }
        
        if (!$phase['is_active']) {
            return ['success' => false, 'error' => 'Phase is not active'];
        }
        
        $sharesSold = $this->getPhaseSharesSold($phaseId);
        $pendingShares = $this->getPhasePendingShares($phaseId);
        $totalCommitted = $sharesSold + $pendingShares;
        $availableShares = $phase['total_packages_available'] - $totalCommitted;
        
        if ($requestedShares > $availableShares) {
            return [
                'success' => false, 
                'error' => 'Insufficient shares available',
                'available' => $availableShares,
                'requested' => $requestedShares,
                'phase_name' => $phase['name']
            ];
        }
        
        return [
            'success' => true,
            'available' => $availableShares,
            'phase_name' => $phase['name']
        ];
    }
    
    /**
     * Get phase by ID
     */
    public function getPhaseById($phaseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM phases WHERE id = ?");
        $stmt->execute([$phaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update phase statistics after a successful investment
     */
    public function updatePhaseStats($phaseId, $shares, $amount) {
        $stmt = $this->pdo->prepare("
            UPDATE phases SET 
                packages_sold = packages_sold + ?,
                total_revenue = total_revenue + ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$shares, $amount, $phaseId]);
        
        // Check if phase is now sold out and should advance
        $this->checkPhaseCompletion($phaseId);
    }
    
    /**
     * Check if a phase is completed and advance to next phase
     */
    public function checkPhaseCompletion($phaseId) {
        $phase = $this->getPhaseById($phaseId);
        if (!$phase) return false;
        
        $sharesSold = $this->getPhaseSharesSold($phaseId);
        
        // If phase is sold out, advance to next phase
        if ($sharesSold >= $phase['total_packages_available']) {
            return $this->advanceToNextPhase($phase['phase_number']);
        }
        
        return false;
    }
    
    /**
     * Advance to the next phase
     */
    public function advanceToNextPhase($currentPhaseNumber) {
        try {
            $this->pdo->beginTransaction();
            
            // Deactivate current phase
            $stmt = $this->pdo->prepare("
                UPDATE phases SET 
                    is_active = FALSE,
                    end_date = NOW(),
                    updated_at = NOW()
                WHERE phase_number = ?
            ");
            $stmt->execute([$currentPhaseNumber]);
            
            // Activate next phase
            $nextPhaseNumber = $currentPhaseNumber + 1;
            $stmt = $this->pdo->prepare("
                UPDATE phases SET 
                    is_active = TRUE,
                    start_date = NOW(),
                    updated_at = NOW()
                WHERE phase_number = ?
            ");
            $stmt->execute([$nextPhaseNumber]);
            
            // Activate packages for next phase
            $stmt = $this->pdo->prepare("
                UPDATE investment_packages ip
                JOIN phases p ON ip.phase_id = p.id
                SET ip.is_active = TRUE
                WHERE p.phase_number = ?
            ");
            $stmt->execute([$nextPhaseNumber]);
            
            // Deactivate packages for previous phase
            $stmt = $this->pdo->prepare("
                UPDATE investment_packages ip
                JOIN phases p ON ip.phase_id = p.id
                SET ip.is_active = FALSE
                WHERE p.phase_number = ?
            ");
            $stmt->execute([$currentPhaseNumber]);
            
            $this->pdo->commit();
            
            // Log phase advancement
            error_log("Phase advanced from {$currentPhaseNumber} to {$nextPhaseNumber}");
            
            return [
                'success' => true,
                'message' => "Advanced from Phase {$currentPhaseNumber} to Phase {$nextPhaseNumber}",
                'new_phase' => $nextPhaseNumber
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error advancing phase: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get phase statistics for admin dashboard
     */
    public function getPhaseStats() {
        $currentPhase = $this->getCurrentPhase();
        if (!$currentPhase) {
            return null;
        }
        
        $sharesSold = $this->getPhaseSharesSold($currentPhase['id']);
        $pendingShares = $this->getPhasePendingShares($currentPhase['id']);
        $availableShares = $currentPhase['total_packages_available'] - $sharesSold - $pendingShares;
        $completionPercentage = ($sharesSold / $currentPhase['total_packages_available']) * 100;
        
        return [
            'current_phase' => $currentPhase,
            'shares_sold' => $sharesSold,
            'pending_shares' => $pendingShares,
            'available_shares' => $availableShares,
            'completion_percentage' => round($completionPercentage, 2),
            'total_shares' => $currentPhase['total_packages_available']
        ];
    }
    
    /**
     * Get all phases with their statistics
     */
    public function getAllPhasesWithStats() {
        $stmt = $this->pdo->prepare("SELECT * FROM phases ORDER BY phase_number");
        $stmt->execute();
        $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($phases as &$phase) {
            $phase['shares_sold'] = $this->getPhaseSharesSold($phase['id']);
            $phase['pending_shares'] = $this->getPhasePendingShares($phase['id']);
            $phase['available_shares'] = $phase['total_packages_available'] - $phase['shares_sold'] - $phase['pending_shares'];
            $phase['completion_percentage'] = $phase['total_packages_available'] > 0 
                ? round(($phase['shares_sold'] / $phase['total_packages_available']) * 100, 2)
                : 0;
        }
        
        return $phases;
    }
    
    /**
     * Manually advance to a specific phase (admin function)
     */
    public function manualAdvanceToPhase($targetPhaseNumber) {
        try {
            $this->pdo->beginTransaction();
            
            // Deactivate all phases
            $this->pdo->exec("UPDATE phases SET is_active = FALSE, end_date = NOW()");
            
            // Activate target phase
            $stmt = $this->pdo->prepare("
                UPDATE phases SET 
                    is_active = TRUE,
                    start_date = NOW(),
                    updated_at = NOW()
                WHERE phase_number = ?
            ");
            $stmt->execute([$targetPhaseNumber]);
            
            // Update package availability
            $this->pdo->exec("UPDATE investment_packages SET is_active = FALSE");
            
            $stmt = $this->pdo->prepare("
                UPDATE investment_packages ip
                JOIN phases p ON ip.phase_id = p.id
                SET ip.is_active = TRUE
                WHERE p.phase_number = ?
            ");
            $stmt->execute([$targetPhaseNumber]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Manually advanced to Phase {$targetPhaseNumber}"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
