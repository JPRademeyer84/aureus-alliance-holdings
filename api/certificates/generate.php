<?php
/**
 * Share Certificate Generation API
 * 
 * Generates printable share certificates with 12-month validity
 */

require_once '../config/database.php';

// Simple CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

function sendErrorResponse($message, $code = 400) {
    sendResponse(false, null, $message, $code);
}

function generateCertificateNumber() {
    return 'AAA-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['investment_id'])) {
        sendErrorResponse('Investment ID is required');
    }

    $investmentId = (int)$input['investment_id'];

    // Get investment details
    $investmentQuery = "
        SELECT 
            ai.*,
            u.username,
            u.email,
            p.name as phase_name
        FROM aureus_investments ai
        LEFT JOIN users u ON ai.user_id = u.id
        LEFT JOIN phases p ON ai.phase_id = p.id
        WHERE ai.id = ?
    ";
    
    $investmentStmt = $db->prepare($investmentQuery);
    $investmentStmt->execute([$investmentId]);
    $investment = $investmentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$investment) {
        sendErrorResponse('Investment not found', 404);
    }

    // Check if certificate already exists
    $existingQuery = "SELECT id FROM share_certificates WHERE investment_id = ?";
    $existingStmt = $db->prepare($existingQuery);
    $existingStmt->execute([$investmentId]);
    
    if ($existingStmt->fetch()) {
        sendErrorResponse('Certificate already exists for this investment', 409);
    }

    // Calculate shares (assuming $1 per share for simplicity)
    $shareValue = 1.00;
    $sharesAmount = (int)$investment['amount']; // 1 share per dollar invested
    $totalValue = $sharesAmount * $shareValue;

    // Generate certificate number
    $certificateNumber = generateCertificateNumber();

    // Calculate expiry date (12 months from now)
    $issueDate = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime('+12 months'));

    // Create certificate metadata
    $metadata = json_encode([
        'generation_date' => $issueDate,
        'investment_package' => $investment['package_name'],
        'investment_amount' => $investment['amount'],
        'phase_name' => $investment['phase_name'],
        'user_details' => [
            'username' => $investment['username'],
            'email' => $investment['email']
        ],
        'certificate_terms' => [
            'validity_period' => '12 months',
            'void_conditions' => 'Certificate becomes null and void upon NFT sale',
            'share_type' => 'Digital Mining Shares',
            'transferable' => false
        ]
    ]);

    // Insert certificate record
    $createQuery = "INSERT INTO share_certificates (
        certificate_number, user_id, investment_id, shares_amount, 
        share_value, total_value, issue_date, expiry_date, 
        is_printed, print_count, is_void, metadata, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE, 0, FALSE, ?, NOW(), NOW())";
    
    $createStmt = $db->prepare($createQuery);
    $success = $createStmt->execute([
        $certificateNumber,
        $investment['user_id'],
        $investmentId,
        $sharesAmount,
        $shareValue,
        $totalValue,
        $issueDate,
        $expiryDate,
        $metadata
    ]);
    
    if (!$success) {
        sendErrorResponse('Failed to create certificate record', 500);
    }

    $certificateId = $db->lastInsertId();

    // Update investment record with certificate reference
    $updateInvestmentQuery = "UPDATE aureus_investments SET certificate_id = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateInvestmentQuery);
    $updateStmt->execute([$certificateId, $investmentId]);

    // Generate PDF certificate (simplified - would use a proper PDF library in production)
    $pdfPath = generateCertificatePDF($certificateId, $certificateNumber, $investment, $sharesAmount, $totalValue, $issueDate, $expiryDate);

    // Update certificate with PDF path
    if ($pdfPath) {
        $updatePdfQuery = "UPDATE share_certificates SET pdf_path = ? WHERE id = ?";
        $updatePdfStmt = $db->prepare($updatePdfQuery);
        $updatePdfStmt->execute([$pdfPath, $certificateId]);
    }

    $responseData = [
        'certificate_id' => $certificateId,
        'certificate_number' => $certificateNumber,
        'shares_amount' => $sharesAmount,
        'total_value' => $totalValue,
        'issue_date' => $issueDate,
        'expiry_date' => $expiryDate,
        'pdf_path' => $pdfPath,
        'investment_details' => [
            'package_name' => $investment['package_name'],
            'amount' => $investment['amount'],
            'user_name' => $investment['username'],
            'user_email' => $investment['email']
        ]
    ];

    sendResponse(true, $responseData, 'Share certificate generated successfully');

} catch (Exception $e) {
    error_log("Certificate generation error: " . $e->getMessage());
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function generateCertificatePDF($certificateId, $certificateNumber, $investment, $sharesAmount, $totalValue, $issueDate, $expiryDate) {
    // Simplified PDF generation - in production, use libraries like TCPDF or FPDF
    $pdfDir = __DIR__ . '/../../certificates/';
    
    // Create directory if it doesn't exist
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $filename = "certificate_{$certificateId}.pdf";
    $filepath = $pdfDir . $filename;
    
    // Generate HTML content for the certificate
    $htmlContent = generateCertificateHTML($certificateNumber, $investment, $sharesAmount, $totalValue, $issueDate, $expiryDate);
    
    // For now, save as HTML (in production, convert to PDF)
    $htmlFilepath = $pdfDir . "certificate_{$certificateId}.html";
    file_put_contents($htmlFilepath, $htmlContent);
    
    return "certificates/certificate_{$certificateId}.html";
}

function generateCertificateHTML($certificateNumber, $investment, $sharesAmount, $totalValue, $issueDate, $expiryDate) {
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Share Certificate - {$certificateNumber}</title>
        <style>
            body { font-family: 'Times New Roman', serif; margin: 40px; background: #f8f9fa; }
            .certificate { background: white; padding: 60px; border: 3px solid #d4af37; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 40px; }
            .company-name { font-size: 32px; font-weight: bold; color: #d4af37; margin-bottom: 10px; }
            .certificate-title { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
            .certificate-number { font-size: 16px; color: #666; }
            .content { margin: 40px 0; line-height: 1.8; }
            .holder-name { font-size: 20px; font-weight: bold; color: #d4af37; }
            .shares-info { background: #f8f9fa; padding: 20px; border-left: 4px solid #d4af37; margin: 20px 0; }
            .footer { margin-top: 40px; display: flex; justify-content: space-between; }
            .signature { text-align: center; }
            .signature-line { border-top: 1px solid #333; width: 200px; margin-top: 40px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-top: 30px; border-radius: 5px; }
            .warning-title { font-weight: bold; color: #856404; }
            .seal { position: absolute; right: 60px; top: 60px; width: 100px; height: 100px; border: 2px solid #d4af37; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
        </style>
    </head>
    <body>
        <div class='certificate'>
            <div class='seal'>
                <div style='text-align: center; font-size: 12px; font-weight: bold; color: #d4af37;'>
                    OFFICIAL<br>SEAL
                </div>
            </div>
            
            <div class='header'>
                <div class='company-name'>AUREUS ANGEL ALLIANCE</div>
                <div class='certificate-title'>SHARE CERTIFICATE</div>
                <div class='certificate-number'>Certificate No: {$certificateNumber}</div>
            </div>
            
            <div class='content'>
                <p>This is to certify that</p>
                <p class='holder-name'>{$investment['username']}</p>
                <p>is the registered holder of</p>
                
                <div class='shares-info'>
                    <strong>{$sharesAmount}</strong> Digital Mining Shares<br>
                    Share Value: $1.00 per share<br>
                    Total Certificate Value: <strong>$" . number_format($totalValue, 2) . "</strong><br>
                    Investment Package: <strong>{$investment['package_name']}</strong>
                </div>
                
                <p>in <strong>Aureus Angel Alliance</strong>, subject to the terms and conditions of the company's articles of association and the rights attaching to the said shares.</p>
                
                <p><strong>Issue Date:</strong> " . date('F j, Y', strtotime($issueDate)) . "</p>
                <p><strong>Expiry Date:</strong> " . date('F j, Y', strtotime($expiryDate)) . "</p>
            </div>
            
            <div class='warning'>
                <div class='warning-title'>IMPORTANT NOTICE:</div>
                This certificate is valid for 12 months from the issue date. Should you sell your NFT shares in the future, this physical certificate will become <strong>NULL AND VOID</strong>. Please keep this certificate in a safe place.
            </div>
            
            <div class='footer'>
                <div class='signature'>
                    <div class='signature-line'></div>
                    <div>Company Secretary</div>
                </div>
                <div class='signature'>
                    <div class='signature-line'></div>
                    <div>Director</div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return $html;
}
?>
