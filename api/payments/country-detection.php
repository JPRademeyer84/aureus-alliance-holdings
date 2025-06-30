<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/cors.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleCountryDetection($db);
            break;
        case 'POST':
            handlePaymentMethodSelection($db);
            break;
        default:
            throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleCountryDetection($db) {
    try {
        $userIP = getUserIP();
        $userId = $_GET['user_id'] ?? null;
        $sessionId = $_GET['session_id'] ?? session_id();
        
        // Detect country from IP
        $detectedCountry = detectCountryFromIP($userIP);
        
        // Get payment configuration for detected country
        $paymentConfig = getPaymentConfigForCountry($db, $detectedCountry);
        
        // Log the detection
        logPaymentMethodDetection($db, $userId, $sessionId, $userIP, $detectedCountry, $paymentConfig);
        
        echo json_encode([
            'success' => true,
            'detected_country' => $detectedCountry,
            'ip_address' => $userIP,
            'payment_config' => $paymentConfig,
            'available_methods' => getAvailablePaymentMethods($paymentConfig),
            'recommended_method' => $paymentConfig['default_payment_method'] ?? 'crypto'
        ]);

    } catch (Exception $e) {
        throw new Exception("Country detection failed: " . $e->getMessage());
    }
}

function handlePaymentMethodSelection($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $userId = $input['user_id'] ?? null;
        $sessionId = $input['session_id'] ?? session_id();
        $selectedCountry = $input['country_code'] ?? null;
        $selectedMethod = $input['payment_method'] ?? null;
        $investmentPackage = $input['investment_package'] ?? null;
        $investmentAmount = $input['investment_amount'] ?? null;
        
        if (!$selectedCountry || !$selectedMethod) {
            throw new Exception("Country code and payment method are required");
        }
        
        // Validate payment method is available for country
        $paymentConfig = getPaymentConfigForCountry($db, $selectedCountry);
        $availableMethods = getAvailablePaymentMethods($paymentConfig);
        
        if (!in_array($selectedMethod, $availableMethods)) {
            throw new Exception("Selected payment method not available for this country");
        }
        
        // Get bank account details if bank payment selected
        $bankAccountDetails = null;
        if ($selectedMethod === 'bank') {
            $bankAccountDetails = getBankAccountForCountry($db, $selectedCountry, $paymentConfig['currency_code']);
        }
        
        // Log the selection
        $userIP = getUserIP();
        logPaymentMethodSelection($db, $userId, $sessionId, $userIP, $selectedCountry, $selectedMethod, $investmentPackage, $investmentAmount);
        
        echo json_encode([
            'success' => true,
            'selected_method' => $selectedMethod,
            'country_config' => $paymentConfig,
            'bank_account_details' => $bankAccountDetails,
            'next_steps' => getNextStepsForPaymentMethod($selectedMethod)
        ]);

    } catch (Exception $e) {
        throw new Exception("Payment method selection failed: " . $e->getMessage());
    }
}

function detectCountryFromIP($ip) {
    // Try multiple IP geolocation services for reliability
    $country = null;
    
    // Method 1: ip-api.com (free, reliable)
    try {
        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['countryCode'])) {
                $country = $data['countryCode'];
            }
        }
    } catch (Exception $e) {
        // Continue to next method
    }
    
    // Method 2: ipinfo.io (backup)
    if (!$country) {
        try {
            $response = @file_get_contents("https://ipinfo.io/{$ip}/country");
            if ($response) {
                $country = trim($response);
            }
        } catch (Exception $e) {
            // Continue to next method
        }
    }
    
    // Method 3: CloudFlare header (if available)
    if (!$country && isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
    }
    
    // Convert 2-letter to 3-letter country code
    if ($country && strlen($country) === 2) {
        $country = convertCountryCode($country);
    }
    
    // Default to 'ZZZ' (Other Countries) if detection fails
    return $country ?: 'ZZZ';
}

function convertCountryCode($twoLetterCode) {
    $mapping = [
        'US' => 'USA', 'CA' => 'CAN', 'GB' => 'GBR', 'DE' => 'DEU', 'FR' => 'FRA',
        'AU' => 'AUS', 'JP' => 'JPN', 'SG' => 'SGP', 'CH' => 'CHE', 'NL' => 'NLD',
        'CN' => 'CHN', 'IN' => 'IND', 'RU' => 'RUS', 'TR' => 'TUR', 'ID' => 'IDN',
        'TH' => 'THA', 'VN' => 'VNM', 'BD' => 'BGD', 'PK' => 'PAK', 'EG' => 'EGY'
    ];
    
    return $mapping[$twoLetterCode] ?? 'ZZZ';
}

function getPaymentConfigForCountry($db, $countryCode) {
    $query = "SELECT * FROM country_payment_config WHERE country_code = ? AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute([$countryCode]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If country not found, use default configuration
    if (!$config) {
        $query = "SELECT * FROM country_payment_config WHERE country_code = 'ZZZ' AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $config;
}

function getAvailablePaymentMethods($paymentConfig) {
    $methods = [];
    
    if ($paymentConfig['crypto_payments_allowed']) {
        $methods[] = 'crypto';
    }
    
    if ($paymentConfig['bank_payments_allowed']) {
        $methods[] = 'bank';
    }
    
    return $methods;
}

function getBankAccountForCountry($db, $countryCode, $currencyCode) {
    // First try to find account for specific country and currency
    $query = "SELECT * FROM company_bank_accounts 
              WHERE country_code = ? AND currency_code = ? AND is_active = TRUE 
              ORDER BY is_default DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$countryCode, $currencyCode]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found, try default account for currency
    if (!$account) {
        $query = "SELECT * FROM company_bank_accounts 
                  WHERE currency_code = ? AND is_active = TRUE 
                  ORDER BY is_default DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$currencyCode]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // If still not found, get default USD account
    if (!$account) {
        $query = "SELECT * FROM company_bank_accounts 
                  WHERE is_default = TRUE AND is_active = TRUE LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $account;
}

function getNextStepsForPaymentMethod($method) {
    if ($method === 'crypto') {
        return [
            'step1' => 'Connect your SafePal wallet',
            'step2' => 'Ensure sufficient USDT balance',
            'step3' => 'Confirm transaction details',
            'step4' => 'Complete payment'
        ];
    } else {
        return [
            'step1' => 'Note the bank account details',
            'step2' => 'Make bank transfer with reference number',
            'step3' => 'Upload proof of payment',
            'step4' => 'Wait for admin verification'
        ];
    }
}

function logPaymentMethodDetection($db, $userId, $sessionId, $ip, $detectedCountry, $paymentConfig) {
    try {
        $query = "INSERT INTO payment_method_log (
            user_id, session_id, ip_address, detected_country, 
            available_methods, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $availableMethods = json_encode(getAvailablePaymentMethods($paymentConfig));
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $userId, $sessionId, $ip, $detectedCountry, 
            $availableMethods, $userAgent
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log payment method detection: " . $e->getMessage());
    }
}

function logPaymentMethodSelection($db, $userId, $sessionId, $ip, $selectedCountry, $selectedMethod, $package, $amount) {
    try {
        $query = "UPDATE payment_method_log SET 
            user_selected_country = ?, selected_method = ?, 
            investment_package = ?, investment_amount = ?
            WHERE user_id = ? AND session_id = ? 
            ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $selectedCountry, $selectedMethod, $package, $amount,
            $userId, $sessionId
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log payment method selection: " . $e->getMessage());
    }
}

function getUserIP() {
    // Check for various headers that might contain the real IP
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // CloudFlare
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR even if it's private (for development)
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
?>
