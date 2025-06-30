<?php
// Simple packages endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Database connection
    $pdo = new PDO(
        'mysql:host=localhost;port=3506;dbname=aureus_angels;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all packages
        try {
            $stmt = $pdo->query("SELECT * FROM investment_packages ORDER BY price ASC");
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure all numeric fields are properly formatted
            foreach ($packages as &$package) {
                $package['price'] = (float)($package['price'] ?? 0);
                $package['shares'] = (int)($package['shares'] ?? 0);
                $package['roi'] = (float)($package['roi'] ?? 0);
                $package['annual_dividends'] = (float)($package['annual_dividends'] ?? 0);
                $package['quarter_dividends'] = (float)($package['quarter_dividends'] ?? 0);
                
                // Parse bonuses JSON
                if (isset($package['bonuses']) && is_string($package['bonuses'])) {
                    $package['bonuses'] = json_decode($package['bonuses'], true) ?: [];
                } else {
                    $package['bonuses'] = [];
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Packages retrieved successfully',
                'data' => $packages
            ]);
            
        } catch (Exception $e) {
            // If table doesn't exist, return default packages
            $defaultPackages = [
                [
                    'id' => 1,
                    'name' => 'Starter',
                    'price' => 50.00,
                    'shares' => 2,
                    'roi' => 400.00,
                    'annual_dividends' => 200.00,
                    'quarter_dividends' => 50.00,
                    'icon' => 'star',
                    'icon_color' => 'bg-green-500',
                    'bonuses' => ['Community Discord Access', 'Guaranteed Common NFT Card']
                ],
                [
                    'id' => 2,
                    'name' => 'Bronze',
                    'price' => 100.00,
                    'shares' => 10,
                    'roi' => 800.00,
                    'annual_dividends' => 800.00,
                    'quarter_dividends' => 200.00,
                    'icon' => 'square',
                    'icon_color' => 'bg-amber-700',
                    'bonuses' => ['All Starter Bonuses', 'Guaranteed Uncommon NFT Card', 'Early Game Access', 'Priority Support']
                ],
                [
                    'id' => 3,
                    'name' => 'Silver',
                    'price' => 250.00,
                    'shares' => 30,
                    'roi' => 2000.00,
                    'annual_dividends' => 2500.00,
                    'quarter_dividends' => 625.00,
                    'icon' => 'circle',
                    'icon_color' => 'bg-gray-300',
                    'bonuses' => ['All Bronze Bonuses', 'Guaranteed Epic NFT Card', 'Exclusive Game Events Access', 'VIP Game Benefits']
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Default packages loaded (database table not found)',
                'data' => $defaultPackages
            ]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create new package
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO investment_packages 
            (name, price, shares, roi, annual_dividends, quarter_dividends, icon, icon_color, bonuses) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $bonuses = json_encode($input['bonuses'] ?? []);
        
        $stmt->execute([
            $input['name'],
            $input['price'],
            $input['shares'],
            $input['roi'],
            $input['annual_dividends'],
            $input['quarter_dividends'],
            $input['icon'] ?? 'star',
            $input['icon_color'] ?? 'bg-blue-500',
            $bonuses
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package created successfully',
            'data' => ['id' => $pdo->lastInsertId()]
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update package
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Package ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE investment_packages 
            SET name=?, price=?, shares=?, roi=?, annual_dividends=?, quarter_dividends=?, icon=?, icon_color=?, bonuses=?
            WHERE id=?
        ");
        
        $bonuses = json_encode($input['bonuses'] ?? []);
        
        $stmt->execute([
            $input['name'],
            $input['price'],
            $input['shares'],
            $input['roi'],
            $input['annual_dividends'],
            $input['quarter_dividends'],
            $input['icon'] ?? 'star',
            $input['icon_color'] ?? 'bg-blue-500',
            $bonuses,
            $input['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package updated successfully'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete package
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            echo json_encode(['success' => false, 'error' => 'Package ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM investment_packages WHERE id=?");
        $stmt->execute([$input['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
