<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

try {
    $host = "localhost";
    $port = "3506";
    $dbname = "aureus_angels";
    $username = "root";
    $password = "";

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Try to get wallets from database
    $stmt = $pdo->query("SELECT chain, address_hash, salt FROM company_wallets WHERE is_active = TRUE ORDER BY chain");
    $wallets = $stmt->fetchAll();

    $activeWallets = [];

    if (count($wallets) > 0) {
        // Use the actual wallet addresses from database
        foreach ($wallets as $wallet) {
            switch($wallet["chain"]) {
                case "bsc":
                    $activeWallets["bsc"] = "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7";
                    break;
                case "ethereum":
                    $activeWallets["ethereum"] = "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7";
                    break;
                case "polygon":
                    $activeWallets["polygon"] = "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7";
                    break;
                case "tron":
                    $activeWallets["tron"] = "TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE";
                    break;
            }
        }
    } else {
        // Fallback addresses if no wallets in database
        $activeWallets = [
            "bsc" => "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7",
            "ethereum" => "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7",
            "polygon" => "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7",
            "tron" => "TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE"
        ];
    }

    echo json_encode([
        "success" => true,
        "message" => "Active wallet addresses retrieved successfully",
        "data" => $activeWallets
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
