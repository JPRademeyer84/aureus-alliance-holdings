<?php
/**
 * Validation Utility Functions
 * Common validation functions for API endpoints
 */

function validateRequired($data, $requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateAmount($amount, $min = 0, $max = null) {
    $amount = (float)$amount;
    
    if ($amount < $min) {
        return false;
    }
    
    if ($max !== null && $amount > $max) {
        return false;
    }
    
    return true;
}

function sanitizeString($string) {
    return trim(htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
}

function validateWalletAddress($address, $chain = null) {
    // Basic wallet address validation
    if (empty($address)) {
        return false;
    }
    
    // Remove whitespace
    $address = trim($address);
    
    // Basic length and character checks
    if (strlen($address) < 20 || strlen($address) > 100) {
        return false;
    }
    
    // Chain-specific validation could be added here
    switch ($chain) {
        case 'ethereum':
        case 'bsc':
        case 'polygon':
            return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
        case 'tron':
            return preg_match('/^T[A-Za-z1-9]{33}$/', $address);
        default:
            // Generic validation
            return preg_match('/^[a-zA-Z0-9]+$/', $address);
    }
}

?>
