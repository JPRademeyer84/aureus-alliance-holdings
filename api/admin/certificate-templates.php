<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
            handleGetTemplates($db);
            break;
        case 'POST':
            handleCreateTemplate($db, $input);
            break;
        case 'PUT':
            handleUpdateTemplate($db, $input);
            break;
        case 'DELETE':
            handleDeleteTemplate($db, $input);
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

function handleGetTemplates($db) {
    try {
        $query = "SELECT 
            ct.*,
            au.username as created_by_username,
            au2.username as updated_by_username
        FROM certificate_templates ct
        LEFT JOIN admin_users au ON ct.created_by = au.id
        LEFT JOIN admin_users au2 ON ct.updated_by = au2.id
        ORDER BY ct.is_default DESC, ct.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON config for each template
        foreach ($templates as &$template) {
            if ($template['template_config']) {
                $template['template_config'] = json_decode($template['template_config'], true);
            }
        }

        echo json_encode([
            'success' => true,
            'templates' => $templates,
            'count' => count($templates)
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to fetch templates: " . $e->getMessage());
    }
}

function handleCreateTemplate($db, $input) {
    try {
        // Validate required fields
        if (empty($input['template_name']) || empty($input['created_by'])) {
            throw new Exception("Template name and creator are required");
        }

        // Check if template name already exists
        $checkQuery = "SELECT id FROM certificate_templates WHERE template_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$input['template_name']]);
        
        if ($checkStmt->rowCount() > 0) {
            throw new Exception("Template name already exists");
        }

        // If this is set as default, unset other defaults
        if (!empty($input['is_default']) && $input['is_default']) {
            $updateQuery = "UPDATE certificate_templates SET is_default = FALSE WHERE template_type = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$input['template_type'] ?? 'share_certificate']);
        }

        // Insert new template
        $query = "INSERT INTO certificate_templates (
            template_name, template_type, frame_image_path, background_image_path,
            template_config, is_active, is_default, version, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $input['template_name'],
            $input['template_type'] ?? 'share_certificate',
            $input['frame_image_path'] ?? null,
            $input['background_image_path'] ?? null,
            json_encode($input['template_config'] ?? []),
            $input['is_active'] ?? true,
            $input['is_default'] ?? false,
            $input['version'] ?? '1.0',
            $input['created_by']
        ]);

        $templateId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Template created successfully',
            'template_id' => $templateId
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to create template: " . $e->getMessage());
    }
}

function handleUpdateTemplate($db, $input) {
    try {
        if (empty($input['id'])) {
            throw new Exception("Template ID is required");
        }

        // Check if template exists
        $checkQuery = "SELECT id FROM certificate_templates WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$input['id']]);
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception("Template not found");
        }

        // If this is set as default, unset other defaults
        if (!empty($input['is_default']) && $input['is_default']) {
            $updateQuery = "UPDATE certificate_templates SET is_default = FALSE WHERE template_type = ? AND id != ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$input['template_type'] ?? 'share_certificate', $input['id']]);
        }

        // Build update query dynamically
        $updateFields = [];
        $updateValues = [];

        $allowedFields = [
            'template_name', 'template_type', 'frame_image_path', 'background_image_path',
            'is_active', 'is_default', 'version', 'updated_by'
        ];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $input[$field];
            }
        }

        // Handle template_config separately
        if (isset($input['template_config'])) {
            $updateFields[] = "template_config = ?";
            $updateValues[] = json_encode($input['template_config']);
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $updateValues[] = $input['id'];
        $query = "UPDATE certificate_templates SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute($updateValues);

        echo json_encode([
            'success' => true,
            'message' => 'Template updated successfully'
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to update template: " . $e->getMessage());
    }
}

function handleDeleteTemplate($db, $input) {
    try {
        if (empty($input['id'])) {
            throw new Exception("Template ID is required");
        }

        // Check if template is being used by any certificates
        $checkQuery = "SELECT COUNT(*) as count FROM share_certificates WHERE template_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$input['id']]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            throw new Exception("Cannot delete template: it is being used by " . $result['count'] . " certificate(s)");
        }

        // Delete template
        $query = "DELETE FROM certificate_templates WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$input['id']]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Template not found");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);

    } catch (Exception $e) {
        throw new Exception("Failed to delete template: " . $e->getMessage());
    }
}
?>
