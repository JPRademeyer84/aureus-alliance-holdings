<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';
session_start();

try {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $userId = $_SESSION['user_id'];
    $walletAddress = '0xbb67795dbde21b4bfe830a8ccfa0bdb446006e0f';

    // Check what columns exist in user_profiles table
    $stmt = $db->prepare("DESCRIBE user_profiles");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Try to get user's name from profile (check different possible column names)
    $fullName = $_SESSION['user_username']; // Default fallback
    try {
        $stmt = $db->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check for different possible name columns
        if ($userProfile) {
            $fullName = $userProfile['first_name'] ?? $userProfile['name'] ?? $userProfile['full_name'] ?? $_SESSION['user_username'];
            if (isset($userProfile['first_name']) && isset($userProfile['last_name'])) {
                $fullName = trim($userProfile['first_name'] . ' ' . $userProfile['last_name']);
            }
        }
    } catch (Exception $e) {
        // If user_profiles doesn't exist or has issues, use username
        $fullName = $_SESSION['user_username'];
    }

    // Update investments: Fix user_id while preserving wallet_address and tx_hash
    $stmt = $db->prepare("
        UPDATE aureus_investments
        SET user_id = ?,
            name = ?,
            email = ?
        WHERE user_id = ?
    ");

    $result = $stmt->execute([
        $userId,           // Set correct user_id
        $fullName,         // Set user's name
        $_SESSION['user_email'], // Set user's email
        $walletAddress     // WHERE clause: find records with wallet address as user_id
    ]);

    if ($result) {
        $affectedRows = $stmt->rowCount();

        // Get updated investments to verify
        $stmt = $db->prepare("SELECT * FROM aureus_investments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $updatedInvestments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated $affectedRows investment records",
            'affected_rows' => $affectedRows,
            'user_profiles_columns' => $columns,
            'user_profile_data' => $userProfile ?? null,
            'changes_made' => [
                'user_id' => "Changed from '$walletAddress' to '$userId'",
                'name' => "Set to '$fullName'",
                'email' => "Set to '{$_SESSION['user_email']}'",
                'wallet_address' => 'PRESERVED (unchanged)',
                'tx_hash' => 'PRESERVED (unchanged)',
                'all_other_data' => 'PRESERVED (unchanged)'
            ],
            'updated_investments' => $updatedInvestments
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update investments'
        ]);
    }

    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
