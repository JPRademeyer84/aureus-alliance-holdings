<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            handleGetCertificates($db);
            break;
        case 'POST':
            handleGenerateCertificate($db, $input);
            break;
        case 'PUT':
            handleUpdateCertificate($db, $input);
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

function handleGetCertificates($db) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;

        $offset = ($page - 1) * $limit;
        
        // Build query with filters
        $whereConditions = [];
        $params = [];

        if ($status) {
            $whereConditions[] = "sc.generation_status = ?";
            $params[] = $status;
        }

        if ($search) {
            $whereConditions[] = "(sc.certificate_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $query = "SELECT 
            sc.*,
            ai.package_name,
            ai.amount as investment_amount,
            u.username,
            u.email,
            ct.template_name,
            au.username as generated_by_username
        FROM share_certificates sc
        LEFT JOIN aureus_investments ai ON sc.investment_id = ai.id
        LEFT JOIN users u ON sc.user_id = u.id
        LEFT JOIN certificate_templates ct ON sc.template_id = ct.id
        LEFT JOIN admin_users au ON sc.generated_by = au.id
        $whereClause
        ORDER BY sc.created_at DESC
        LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM share_certificates sc
                      LEFT JOIN users u ON sc.user_id = u.id
                      $whereClause";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            'success' => true,
            'certificates' => $certificates,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => (int)$totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to fetch certificates: " . $e->getMessage());
    }
}

function handleGenerateCertificate($db, $input) {
    try {
        // Validate required fields
        if (empty($input['investment_id']) || empty($input['generated_by'])) {
            throw new Exception("Investment ID and generator admin ID are required");
        }

        // Get investment details
        $investmentQuery = "SELECT ai.*, u.username, u.email, u.full_name 
                           FROM aureus_investments ai 
                           LEFT JOIN users u ON ai.user_id = u.id 
                           WHERE ai.id = ?";
        $investmentStmt = $db->prepare($investmentQuery);
        $investmentStmt->execute([$input['investment_id']]);
        $investment = $investmentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$investment) {
            throw new Exception("Investment not found");
        }

        // Check if certificate already exists
        $existingQuery = "SELECT id FROM share_certificates WHERE investment_id = ?";
        $existingStmt = $db->prepare($existingQuery);
        $existingStmt->execute([$input['investment_id']]);
        
        if ($existingStmt->rowCount() > 0) {
            throw new Exception("Certificate already exists for this investment");
        }

        // Get default template or specified template
        $templateId = $input['template_id'] ?? null;
        if (!$templateId) {
            $templateQuery = "SELECT id FROM certificate_templates WHERE is_default = TRUE AND template_type = 'share_certificate' LIMIT 1";
            $templateStmt = $db->prepare($templateQuery);
            $templateStmt->execute();
            $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                throw new Exception("No default template found. Please create a template first.");
            }
            $templateId = $template['id'];
        }

        // Generate unique certificate number
        $certificateNumber = generateCertificateNumber($db);

        // Generate verification hash
        $verificationHash = hash('sha256', $certificateNumber . $investment['id'] . time());

        // Create certificate record
        $insertQuery = "INSERT INTO share_certificates (
            certificate_number, investment_id, user_id, template_id,
            share_quantity, certificate_value, issue_date,
            verification_hash, generation_status, generation_method,
            generated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($insertQuery);
        $stmt->execute([
            $certificateNumber,
            $input['investment_id'],
            $investment['user_id'],
            $templateId,
            $investment['shares'],
            $investment['amount'],
            date('Y-m-d'),
            $verificationHash,
            'pending',
            $input['generation_method'] ?? 'manual',
            $input['generated_by']
        ]);

        $certificateId = $db->lastInsertId();

        // Create verification record
        createVerificationRecord($db, $certificateId, $certificateNumber);

        echo json_encode([
            'success' => true,
            'message' => 'Certificate created successfully',
            'certificate_id' => $certificateId,
            'certificate_number' => $certificateNumber
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to generate certificate: " . $e->getMessage());
    }
}

function generateCertificateNumber($db) {
    $year = date('Y');
    $prefix = "AAH-$year-";
    
    // Get the next sequence number
    $query = "SELECT certificate_number FROM share_certificates 
              WHERE certificate_number LIKE ? 
              ORDER BY certificate_number DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$prefix . '%']);
    $lastCert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastCert) {
        $lastNumber = (int)substr($lastCert['certificate_number'], -6);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
}

function createVerificationRecord($db, $certificateId, $certificateNumber) {
    $verificationCode = strtoupper(substr(md5($certificateNumber . time()), 0, 12));
    $verificationUrl = "https://aureusangels.com/verify/" . $verificationCode;
    
    $query = "INSERT INTO certificate_verifications (
        certificate_id, verification_code, verification_url
    ) VALUES (?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$certificateId, $verificationCode, $verificationUrl]);
}

function handleUpdateCertificate($db, $input) {
    try {
        if (empty($input['id'])) {
            throw new Exception("Certificate ID is required");
        }

        $allowedFields = [
            'generation_status', 'delivery_status', 'legal_status',
            'certificate_image_path', 'certificate_pdf_path',
            'generation_error', 'invalidation_reason'
        ];

        $updateFields = [];
        $updateValues = [];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $input[$field];
            }
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        // Add timestamp fields based on status changes
        if (isset($input['delivery_status']) && $input['delivery_status'] === 'delivered') {
            $updateFields[] = "delivered_at = NOW()";
        }

        if (isset($input['legal_status']) && $input['legal_status'] === 'invalidated') {
            $updateFields[] = "invalidated_at = NOW()";
            if (isset($input['invalidated_by'])) {
                $updateFields[] = "invalidated_by = ?";
                $updateValues[] = $input['invalidated_by'];
            }
        }

        $updateValues[] = $input['id'];
        $query = "UPDATE share_certificates SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute($updateValues);

        echo json_encode([
            'success' => true,
            'message' => 'Certificate updated successfully'
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to update certificate: " . $e->getMessage());
    }
}
?>
