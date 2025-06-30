<?php
// Ultra simple PHP test
header('Content-Type: application/json');

echo json_encode([
    'status' => 'working',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'PHP is working correctly'
]);
?>
