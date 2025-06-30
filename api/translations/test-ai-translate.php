<?php
// Simple test script for AI translation
header('Content-Type: application/json');

// Simulate POST data
$_POST = [
    'text' => 'Account Settings',
    'target_language' => 'Spanish',
    'language_code' => 'es',
    'key_id' => 126
];

// Set REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture output
ob_start();
include 'ai-translate.php';
$output = ob_get_clean();

echo "AI Translation Test Result:\n";
echo $output;
?>
