<?php
header('Content-Type: application/json');

$uploadInfo = [
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'Default system temp dir',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'assets_kyc_dir_exists' => is_dir('../../assets/kyc/'),
    'assets_kyc_dir_writable' => is_writable('../../assets/kyc/'),
    'current_working_directory' => getcwd(),
    'php_version' => phpversion()
];

echo json_encode($uploadInfo, JSON_PRETTY_PRINT);
?>
