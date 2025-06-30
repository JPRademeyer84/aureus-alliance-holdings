<?php
/**
 * Fix CORS Credentials Headers Across All APIs
 * Adds Access-Control-Allow-Credentials: true to all API files
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔧 Fix CORS Credentials Headers</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007cba; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Fix CORS Credentials Headers</h1>

<?php
function fixCorsInFile($filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Check if file already has Access-Control-Allow-Credentials
    if (strpos($content, 'Access-Control-Allow-Credentials') !== false) {
        return ['success' => true, 'message' => 'Already has credentials header'];
    }
    
    // Look for existing CORS headers and add credentials
    if (strpos($content, 'Access-Control-Allow-Origin') !== false) {
        // Find the position after Access-Control-Allow-Headers
        $pattern = '/header\s*\(\s*["\']Access-Control-Allow-Headers:[^"\']*["\']\s*\)\s*;?/i';
        
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $credentialsHeader = "\nheader(\"Access-Control-Allow-Credentials: true\");";
            $content = substr_replace($content, $credentialsHeader, $insertPos, 0);
        } else {
            // If no Allow-Headers found, add after Allow-Origin
            $pattern = '/header\s*\(\s*["\']Access-Control-Allow-Origin:[^"\']*["\']\s*\)\s*;?/i';
            
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertPos = $matches[0][1] + strlen($matches[0][0]);
                $credentialsHeader = "\nheader(\"Access-Control-Allow-Credentials: true\");";
                $content = substr_replace($content, $credentialsHeader, $insertPos, 0);
            }
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            return ['success' => true, 'message' => 'Added credentials header'];
        } else {
            return ['success' => false, 'error' => 'Could not find insertion point'];
        }
    }
    
    return ['success' => false, 'error' => 'No CORS headers found'];
}

function scanAndFixDirectory($directory) {
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            $relativePath = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $filePath);
            
            // Skip this script itself
            if (basename($filePath) === 'fix-cors-credentials.php') {
                continue;
            }
            
            // Only process API files
            if (strpos($relativePath, 'api' . DIRECTORY_SEPARATOR) === 0) {
                $result = fixCorsInFile($filePath);
                $results[] = [
                    'file' => $relativePath,
                    'result' => $result
                ];
            }
        }
    }
    
    return $results;
}

echo "<div class='section'>";
echo "<h2>🔍 Scanning API Directory</h2>";

$apiDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api';
$results = scanAndFixDirectory($apiDirectory);

echo "<p>Found " . count($results) . " API files to check.</p>";

$fixed = 0;
$alreadyFixed = 0;
$errors = 0;

foreach ($results as $result) {
    $file = $result['file'];
    $res = $result['result'];
    
    if ($res['success']) {
        if ($res['message'] === 'Added credentials header') {
            echo "<p class='success'>✅ Fixed: $file</p>";
            $fixed++;
        } else {
            echo "<p class='info'>→ $file: {$res['message']}</p>";
            $alreadyFixed++;
        }
    } else {
        echo "<p class='error'>❌ $file: {$res['error']}</p>";
        $errors++;
    }
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>📊 Summary</h2>";
echo "<p class='success'>✅ Fixed: $fixed files</p>";
echo "<p class='info'>→ Already had credentials: $alreadyFixed files</p>";
echo "<p class='error'>❌ Errors: $errors files</p>";
echo "</div>";

// Test specific problematic files
echo "<div class='section'>";
echo "<h2>🎯 Testing Specific Files</h2>";

$testFiles = [
    'api/users/enhanced-profile.php',
    'api/referrals/gold-diggers-leaderboard.php',
    'api/payments/manual-payment.php',
    'api/wallets/active.php'
];

foreach ($testFiles as $testFile) {
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $testFile;
    
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        
        if (strpos($content, 'Access-Control-Allow-Credentials') !== false) {
            echo "<p class='success'>✅ $testFile: Has credentials header</p>";
        } else {
            echo "<p class='error'>❌ $testFile: Missing credentials header</p>";
            
            // Try to fix it
            $result = fixCorsInFile($fullPath);
            if ($result['success']) {
                echo "<p class='success'>✅ $testFile: Fixed!</p>";
            } else {
                echo "<p class='error'>❌ $testFile: Could not fix - {$result['error']}</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ $testFile: File not found</p>";
    }
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li>Refresh your React app (Ctrl+F5)</li>";
echo "<li>Check browser console for CORS errors</li>";
echo "<li>Test the manual payment system</li>";
echo "<li>If issues persist, check individual API responses</li>";
echo "</ol>";
echo "</div>";
?>

</body>
</html>
