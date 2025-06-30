<?php
// Clean KYC Duplicates - Admin Tool
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

session_start();
setCorsHeaders();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $cleanupResults = [];
    
    // 1. Find and remove duplicate requirements
    $findDuplicatesQuery = "
        SELECT 
            level_id,
            requirement_type,
            requirement_name,
            MIN(id) as keep_id,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(id) as all_ids
        FROM kyc_level_requirements
        GROUP BY level_id, requirement_type, requirement_name
        HAVING COUNT(*) > 1
    ";
    
    $stmt = $db->prepare($findDuplicatesQuery);
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $removedCount = 0;
    foreach ($duplicates as $duplicate) {
        $allIds = explode(',', $duplicate['all_ids']);
        $keepId = $duplicate['keep_id'];
        
        // Remove all except the first one (keep_id)
        foreach ($allIds as $id) {
            if ($id != $keepId) {
                $deleteStmt = $db->prepare("DELETE FROM kyc_level_requirements WHERE id = ?");
                $deleteStmt->execute([$id]);
                $removedCount++;
            }
        }
    }
    
    $cleanupResults['duplicates_removed'] = $removedCount;
    $cleanupResults['duplicate_groups'] = count($duplicates);
    
    // 2. Ensure correct requirements per level
    // First, clear all existing requirements
    $db->exec("DELETE FROM kyc_level_requirements");
    
    // Re-insert correct requirements
    $insertLevel1 = "
        INSERT INTO kyc_level_requirements (level_id, requirement_type, requirement_name, description, sort_order) VALUES
        (1, 'email_verification', 'Email Verification', 'Verify your email address', 1),
        (1, 'phone_verification', 'Phone Verification', 'Verify your phone number', 2),
        (1, 'profile_completion', 'Basic Profile', 'Complete basic profile information', 3)
    ";
    $db->exec($insertLevel1);
    
    $insertLevel2 = "
        INSERT INTO kyc_level_requirements (level_id, requirement_type, requirement_name, description, sort_order) VALUES
        (2, 'document_upload', 'Government ID', 'Upload government-issued ID document', 1),
        (2, 'address_verification', 'Proof of Address', 'Upload proof of address document', 2),
        (2, 'facial_verification', 'Facial Recognition', 'Complete facial recognition verification', 3)
    ";
    $db->exec($insertLevel2);
    
    $insertLevel3 = "
        INSERT INTO kyc_level_requirements (level_id, requirement_type, requirement_name, description, sort_order) VALUES
        (3, 'enhanced_due_diligence', 'Enhanced Due Diligence', 'Additional documentation and verification', 1),
        (3, 'account_activity', 'Account Activity Period', 'Maintain account activity for 30 days minimum', 2)
    ";
    $db->exec($insertLevel3);
    
    $cleanupResults['requirements_reset'] = true;
    
    // 3. Get final count
    $countQuery = "
        SELECT 
            kl.id,
            kl.name,
            COUNT(klr.id) as requirement_count
        FROM kyc_levels kl
        LEFT JOIN kyc_level_requirements klr ON kl.id = klr.level_id
        GROUP BY kl.id, kl.name
        ORDER BY kl.id
    ";
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $finalCounts = $countStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cleanupResults['final_counts'] = $finalCounts;
    
    echo json_encode([
        'success' => true,
        'message' => 'KYC requirements cleaned up successfully',
        'cleanup_results' => $cleanupResults
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Cleanup failed: ' . $e->getMessage()
    ]);
}
?>
