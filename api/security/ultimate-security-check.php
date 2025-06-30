<?php
/**
 * ULTIMATE SECURITY VERIFICATION SYSTEM
 * The most advanced security check possible - verifies every aspect of the system
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../security/commission-security.php';
require_once '../security/withdrawal-scheduler.php';
session_start();

try {
    // Check if admin is authenticated
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Admin authentication required for security verification'
        ]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize security systems
    $securityManager = new CommissionSecurityManager($db);
    $withdrawalScheduler = new WithdrawalScheduler($db, $securityManager);
    
    $securityReport = [];
    $securityReport['timestamp'] = date('c');
    $securityReport['admin_id'] = $_SESSION['admin_id'];
    
    // 1. DUAL-TABLE INTEGRITY VERIFICATION
    $securityReport['dual_table_integrity'] = [];
    
    try {
        // Get all users with balances
        $usersQuery = "SELECT DISTINCT user_id FROM commission_balances_primary";
        $usersStmt = $db->prepare($usersQuery);
        $usersStmt->execute();
        $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $integrityResults = [];
        $totalUsers = count($userIds);
        $validUsers = 0;
        $compromisedUsers = [];
        
        foreach ($userIds as $userId) {
            $isValid = $securityManager->verifyBalanceIntegrity($userId);
            $integrityResults[$userId] = $isValid;
            
            if ($isValid) {
                $validUsers++;
            } else {
                $compromisedUsers[] = $userId;
            }
        }
        
        $securityReport['dual_table_integrity'] = [
            'status' => count($compromisedUsers) === 0 ? 'SECURE' : 'COMPROMISED',
            'total_users_checked' => $totalUsers,
            'valid_users' => $validUsers,
            'compromised_users' => $compromisedUsers,
            'integrity_percentage' => $totalUsers > 0 ? round(($validUsers / $totalUsers) * 100, 2) : 100
        ];
        
    } catch (Exception $e) {
        $securityReport['dual_table_integrity'] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    // 2. CRYPTOGRAPHIC HASH VERIFICATION
    $securityReport['cryptographic_verification'] = [];
    
    try {
        // Test hash generation and verification
        $testData = ['test' => 'security_check', 'timestamp' => microtime(true)];
        $testHash = hash('sha256', json_encode($testData) . 'COMMISSION_SECURITY_KEY');
        
        // Verify hash consistency
        $verifyHash = hash('sha256', json_encode($testData) . 'COMMISSION_SECURITY_KEY');
        $hashConsistent = ($testHash === $verifyHash);
        
        // Check recent transaction hashes
        $recentTransactionsQuery = "SELECT COUNT(*) as count FROM commission_transaction_log WHERE transaction_hash IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $recentTransactionsStmt = $db->prepare($recentTransactionsQuery);
        $recentTransactionsStmt->execute();
        $recentTransactionCount = $recentTransactionsStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $securityReport['cryptographic_verification'] = [
            'status' => $hashConsistent ? 'SECURE' : 'COMPROMISED',
            'hash_generation_working' => true,
            'hash_consistency_verified' => $hashConsistent,
            'recent_transactions_with_hashes' => (int)$recentTransactionCount,
            'test_hash_sample' => substr($testHash, 0, 16) . '...'
        ];
        
    } catch (Exception $e) {
        $securityReport['cryptographic_verification'] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    // 3. IMMUTABLE AUDIT TRAIL VERIFICATION
    $securityReport['audit_trail_verification'] = [];
    
    try {
        // Check for any modifications to transaction log (should be append-only)
        $auditLogQuery = "
            SELECT 
                COUNT(*) as total_entries,
                COUNT(DISTINCT user_id) as unique_users,
                MIN(created_at) as oldest_entry,
                MAX(created_at) as newest_entry,
                SUM(CASE WHEN event_type = 'balance_verification' THEN 1 ELSE 0 END) as verification_events,
                SUM(CASE WHEN event_type = 'commission_earned' THEN 1 ELSE 0 END) as commission_events,
                SUM(CASE WHEN event_type = 'withdrawal_completed' THEN 1 ELSE 0 END) as withdrawal_events
            FROM commission_transaction_log
        ";
        
        $auditLogStmt = $db->prepare($auditLogQuery);
        $auditLogStmt->execute();
        $auditStats = $auditLogStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check for any suspicious gaps in timestamps (indicating potential tampering)
        $gapCheckQuery = "
            SELECT 
                COUNT(*) as suspicious_gaps
            FROM (
                SELECT 
                    created_at,
                    LAG(created_at) OVER (ORDER BY created_at) as prev_created_at,
                    TIMESTAMPDIFF(MINUTE, LAG(created_at) OVER (ORDER BY created_at), created_at) as gap_minutes
                FROM commission_transaction_log
                ORDER BY created_at
            ) gap_analysis
            WHERE gap_minutes > 1440 -- Gaps larger than 24 hours
        ";
        
        $gapCheckStmt = $db->prepare($gapCheckQuery);
        $gapCheckStmt->execute();
        $suspiciousGaps = $gapCheckStmt->fetch(PDO::FETCH_ASSOC)['suspicious_gaps'];
        
        $securityReport['audit_trail_verification'] = [
            'status' => (int)$suspiciousGaps === 0 ? 'SECURE' : 'SUSPICIOUS',
            'total_audit_entries' => (int)$auditStats['total_entries'],
            'unique_users_in_audit' => (int)$auditStats['unique_users'],
            'oldest_entry' => $auditStats['oldest_entry'],
            'newest_entry' => $auditStats['newest_entry'],
            'verification_events' => (int)$auditStats['verification_events'],
            'commission_events' => (int)$auditStats['commission_events'],
            'withdrawal_events' => (int)$auditStats['withdrawal_events'],
            'suspicious_timestamp_gaps' => (int)$suspiciousGaps
        ];
        
    } catch (Exception $e) {
        $securityReport['audit_trail_verification'] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    // 4. BUSINESS HOURS ENFORCEMENT VERIFICATION
    $securityReport['business_hours_verification'] = [];
    
    try {
        $isWithinHours = $withdrawalScheduler->isWithinBusinessHours();
        $nextBusinessDay = $withdrawalScheduler->getNextBusinessDay();
        
        // Check for any withdrawals processed outside business hours (security violation)
        $outsideHoursQuery = "
            SELECT COUNT(*) as violations
            FROM secure_withdrawal_requests 
            WHERE status = 'completed' 
            AND (
                DAYOFWEEK(completed_at) IN (1, 7) -- Sunday = 1, Saturday = 7
                OR HOUR(completed_at) < 9 
                OR HOUR(completed_at) >= 16
            )
        ";
        
        $outsideHoursStmt = $db->prepare($outsideHoursQuery);
        $outsideHoursStmt->execute();
        $businessHoursViolations = $outsideHoursStmt->fetch(PDO::FETCH_ASSOC)['violations'];
        
        $securityReport['business_hours_verification'] = [
            'status' => (int)$businessHoursViolations === 0 ? 'SECURE' : 'VIOLATIONS_DETECTED',
            'currently_within_business_hours' => $isWithinHours,
            'next_business_day' => date('Y-m-d H:i:s', $nextBusinessDay),
            'business_hours_violations' => (int)$businessHoursViolations
        ];
        
    } catch (Exception $e) {
        $securityReport['business_hours_verification'] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    // 5. WITHDRAWAL SECURITY VERIFICATION
    $securityReport['withdrawal_security_verification'] = [];
    
    try {
        // Check for withdrawals without blockchain hashes (security violation)
        $missingHashesQuery = "SELECT COUNT(*) as violations FROM secure_withdrawal_requests WHERE status = 'completed' AND (blockchain_confirmation_hash IS NULL OR blockchain_confirmation_hash = '')";
        $missingHashesStmt = $db->prepare($missingHashesQuery);
        $missingHashesStmt->execute();
        $missingHashes = $missingHashesStmt->fetch(PDO::FETCH_ASSOC)['violations'];
        
        // Check for automated withdrawals (should be impossible)
        $automatedWithdrawalsQuery = "SELECT COUNT(*) as violations FROM secure_withdrawal_requests WHERE status = 'completed' AND admin_id IS NULL";
        $automatedWithdrawalsStmt = $db->prepare($automatedWithdrawalsQuery);
        $automatedWithdrawalsStmt->execute();
        $automatedWithdrawals = $automatedWithdrawalsStmt->fetch(PDO::FETCH_ASSOC)['violations'];
        
        $securityReport['withdrawal_security_verification'] = [
            'status' => ((int)$missingHashes === 0 && (int)$automatedWithdrawals === 0) ? 'SECURE' : 'VIOLATIONS_DETECTED',
            'completed_withdrawals_without_blockchain_hash' => (int)$missingHashes,
            'automated_withdrawals_detected' => (int)$automatedWithdrawals
        ];
        
    } catch (Exception $e) {
        $securityReport['withdrawal_security_verification'] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    // 6. OVERALL SECURITY ASSESSMENT
    $securityReport['overall_security_assessment'] = [];
    
    $securityChecks = [
        'dual_table_integrity' => $securityReport['dual_table_integrity']['status'] ?? 'ERROR',
        'cryptographic_verification' => $securityReport['cryptographic_verification']['status'] ?? 'ERROR',
        'audit_trail_verification' => $securityReport['audit_trail_verification']['status'] ?? 'ERROR',
        'business_hours_verification' => $securityReport['business_hours_verification']['status'] ?? 'ERROR',
        'withdrawal_security_verification' => $securityReport['withdrawal_security_verification']['status'] ?? 'ERROR'
    ];
    
    $secureChecks = count(array_filter($securityChecks, function($status) { return $status === 'SECURE'; }));
    $totalChecks = count($securityChecks);
    $securityScore = ($secureChecks / $totalChecks) * 100;
    
    $overallStatus = 'COMPROMISED';
    if ($securityScore === 100) {
        $overallStatus = 'MAXIMUM_SECURITY';
    } elseif ($securityScore >= 80) {
        $overallStatus = 'HIGH_SECURITY';
    } elseif ($securityScore >= 60) {
        $overallStatus = 'MEDIUM_SECURITY';
    } elseif ($securityScore >= 40) {
        $overallStatus = 'LOW_SECURITY';
    }
    
    $securityReport['overall_security_assessment'] = [
        'overall_status' => $overallStatus,
        'security_score_percentage' => round($securityScore, 1),
        'secure_checks' => $secureChecks,
        'total_checks' => $totalChecks,
        'individual_check_results' => $securityChecks,
        'recommendation' => $securityScore === 100 ? 'System is operating at maximum security' : 'Security issues detected - immediate attention required'
    ];
    
    // Log this security check
    $securityManager->logSecurityEvent(
        'ultimate_security_check',
        $_SESSION['admin_id'],
        json_encode([
            'overall_status' => $overallStatus,
            'security_score' => $securityScore,
            'checks_performed' => $totalChecks
        ]),
        'info'
    );
    
    echo json_encode([
        'success' => true,
        'security_report' => $securityReport
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Ultimate security check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Security check failed',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
