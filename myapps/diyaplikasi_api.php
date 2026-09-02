<?php
/**
 * DIY Aplikasi API - Handle CRUD operations for generated apps
 * 
 * @author MyApps KEDA
 * @version 1.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/diyaplikasi_sso_helper.php';

header('Content-Type: application/json; charset=utf-8');

// SSO + SSOT: Sahkan pengguna (session, JWT atau Bearer token)
nocode_ensure_auth_api();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$appSlug = $_GET['app'] ?? $_POST['app'] ?? '';

if (empty($appSlug)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'App slug diperlukan']);
    exit();
}

// Load app metadata
$stmt = $db->prepare("SELECT * FROM nocode_apps WHERE app_slug = ? AND status = 1");
$stmt->execute([$appSlug]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Aplikasi tidak dijumpai']);
    exit();
}

$tableName = $app['table_name'];
$schema = json_decode($app['schema_json'], true);

switch ($action) {
    case 'get':
        // Get single record
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tidak sah']);
            exit();
        }
        
        try {
            $stmt = $db->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $recordData = json_decode($row['record_data'], true);
                echo json_encode([
                    'success' => true,
                    'record' => $recordData,
                    'id' => $row['id'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Rekod tidak dijumpai']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ralat: ' . $e->getMessage()]);
        }
        break;
        
    case 'save':
        // Save record (create or update)
        // Note: CSRF token verification for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken();
        }
        
        $recordId = intval($_POST['record_id'] ?? 0);
        $recordData = [];
        
        // Collect data from form
        foreach ($schema as $col) {
            $colName = $col['name'];
            $value = $_POST[$colName] ?? '';
            
            // Type conversion
            if ($col['type'] === 'number') {
                $value = is_numeric($value) ? floatval($value) : 0;
            } elseif ($col['type'] === 'date') {
                $value = !empty($value) ? $value : null;
            } else {
                $value = trim($value);
            }
            
            $recordData[$colName] = $value;
        }
        
        try {
            if ($recordId > 0) {
                // Update existing
                $stmt = $db->prepare("UPDATE `{$tableName}` SET record_data = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([json_encode($recordData, JSON_UNESCAPED_UNICODE), $recordId]);
                echo json_encode(['success' => true, 'message' => 'Rekod berjaya dikemas kini']);
            } else {
                // Insert new
                $stmt = $db->prepare("INSERT INTO `{$tableName}` (record_data) VALUES (?)");
                $stmt->execute([json_encode($recordData, JSON_UNESCAPED_UNICODE)]);
                echo json_encode(['success' => true, 'message' => 'Rekod berjaya ditambah', 'id' => $db->lastInsertId()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ralat: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        // Delete record
        // Note: CSRF token verification for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrfToken();
        }
        
        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tidak sah']);
            exit();
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM `{$tableName}` WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Rekod berjaya dipadam']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ralat: ' . $e->getMessage()]);
        }
        break;
        
    case 'list':
        // List all records (with pagination)
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        try {
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) FROM `{$tableName}`");
            $total = $countStmt->fetchColumn();
            
            // Get records
            $stmt = $db->prepare("SELECT * FROM `{$tableName}` ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $records = [];
            foreach ($rows as $row) {
                $recordData = json_decode($row['record_data'], true);
                $records[] = [
                    'id' => $row['id'],
                    'data' => $recordData,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'records' => $records,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ralat: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak sah']);
        break;
}
