<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create tables silently
    ob_start();
    $database->createTables();
    ob_end_clean();
    
    echo "Creating test KYC documents...\n";
    
    // First, let's check if we have any users
    $userQuery = "SELECT id, username, email FROM users LIMIT 3";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found. Creating test users first...\n";
        
        // Create test users
        $createUserQuery = "INSERT INTO users (id, username, email, password, created_at) VALUES (?, ?, ?, ?, NOW())";
        $createUserStmt = $db->prepare($createUserQuery);
        
        $testUsers = [
            ['test-user-1', 'testuser1', 'test1@example.com', password_hash('password123', PASSWORD_DEFAULT)],
            ['test-user-2', 'testuser2', 'test2@example.com', password_hash('password123', PASSWORD_DEFAULT)],
            ['test-user-3', 'testuser3', 'test3@example.com', password_hash('password123', PASSWORD_DEFAULT)]
        ];
        
        foreach ($testUsers as $user) {
            $createUserStmt->execute($user);
        }
        
        // Re-fetch users
        $userStmt->execute();
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "Found " . count($users) . " users\n";
    
    // Create test KYC documents
    $insertQuery = "INSERT INTO kyc_documents (id, user_id, type, filename, original_name, upload_date, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)";
    $insertStmt = $db->prepare($insertQuery);
    
    $documentTypes = ['passport', 'drivers_license', 'national_id', 'proof_of_address'];
    $statuses = ['pending', 'pending', 'pending', 'approved', 'rejected'];
    
    $documentCount = 0;
    foreach ($users as $user) {
        foreach ($documentTypes as $index => $type) {
            $docId = 'doc-' . $user['id'] . '-' . $type;
            $filename = $type . '_' . $user['username'] . '.jpg';
            $originalName = ucfirst(str_replace('_', ' ', $type)) . ' - ' . $user['username'] . '.jpg';
            $status = $statuses[$index % count($statuses)];
            
            $insertStmt->execute([
                $docId,
                $user['id'],
                $type,
                $filename,
                $originalName,
                $status
            ]);
            
            $documentCount++;
            echo "Created document: $originalName ($status)\n";
        }
    }
    
    echo "\nSuccessfully created $documentCount test KYC documents!\n";
    
    // Show summary
    $summaryQuery = "SELECT status, COUNT(*) as count FROM kyc_documents GROUP BY status";
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDocument Status Summary:\n";
    foreach ($summary as $row) {
        echo "- {$row['status']}: {$row['count']} documents\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
