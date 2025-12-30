<?php
/**
 * Application API Endpoint - SSOT
 * Handles all application-related operations
 * 
 * @version 2.0
 */

require_once 'api.php';

class AplikasiAPI extends API {
    
    public function processRequest() {
        $this->requireAuth();
        
        switch ($this->requestMethod) {
            case 'GET':
                $this->handleGet();
                break;
            case 'POST':
                $this->handlePost();
                break;
            case 'PUT':
                $this->handlePut();
                break;
            case 'DELETE':
                $this->handleDelete();
                break;
            default:
                $this->sendResponse(405, false, 'Method not allowed');
        }
    }
    
    /**
     * GET - Retrieve application data
     */
    private function handleGet() {
        $id = $this->getData('id');
        
        if ($id) {
            $this->getApplication($id);
        } else {
            $this->getAllApplications();
        }
    }
    
    /**
     * Get single application by ID
     */
    private function getApplication($id) {
        try {
            $sql = "SELECT * FROM aplikasi WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($app) {
                $this->logActivity('VIEW_APPLICATION', ['app_id' => $id]);
                $this->sendResponse(200, true, 'Application found', $app);
            } else {
                $this->sendResponse(404, false, 'Application not found');
            }
        } catch (Exception $e) {
            error_log("Application API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Internal server error');
        }
    }
    
    /**
     * Get all applications with optional filters
     */
    private function getAllApplications() {
        try {
            $search = $this->getData('search');
            $type = $this->getData('type');
            $limit = $this->getData('limit', 100);
            $offset = $this->getData('offset', 0);
            
            $sql = "SELECT * FROM aplikasi WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (nama_sistem LIKE ? OR pemilik LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($type) {
                $sql .= " AND jenis = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY nama_sistem ASC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM aplikasi WHERE 1=1";
            $countParams = [];
            if ($search) {
                $countSql .= " AND (nama_sistem LIKE ? OR pemilik LIKE ?)";
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
            }
            if ($type) {
                $countSql .= " AND jenis = ?";
                $countParams[] = $type;
            }
            
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $this->logActivity('LIST_APPLICATIONS', ['count' => count($apps)]);
            $this->sendResponse(200, true, 'Application list retrieved', [
                'applications' => $apps,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (Exception $e) {
            error_log("Application API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Internal server error');
        }
    }
    
    /**
     * POST - Create new application
     */
    private function handlePost() {
        $this->validateRequired(['nama_sistem', 'pemilik']);
        
        try {
            $data = $this->sanitize($this->getData());
            
            $sql = "INSERT INTO aplikasi (nama_sistem, jenis, pemilik, url, penerangan) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nama_sistem'],
                $data['jenis'] ?? null,
                $data['pemilik'],
                $data['url'] ?? null,
                $data['penerangan'] ?? null
            ]);
            
            $newId = $this->db->lastInsertId();
            
            $this->logActivity('CREATE_APPLICATION', ['app_id' => $newId, 'name' => $data['nama_sistem']]);
            $this->sendResponse(201, true, 'Application created successfully', ['id' => $newId]);
        } catch (Exception $e) {
            error_log("Application API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Failed to create application');
        }
    }
    
    /**
     * PUT - Update application
     */
    private function handlePut() {
        $this->validateRequired(['id']);
        
        try {
            $data = $this->sanitize($this->getData());
            $id = $data['id'];
            
            // Check if application exists
            $checkSql = "SELECT id FROM aplikasi WHERE id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                $this->sendResponse(404, false, 'Application not found');
            }
            
            $sql = "UPDATE aplikasi SET 
                    nama_sistem = COALESCE(?, nama_sistem),
                    jenis = COALESCE(?, jenis),
                    pemilik = COALESCE(?, pemilik),
                    url = COALESCE(?, url),
                    penerangan = COALESCE(?, penerangan)
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nama_sistem'] ?? null,
                $data['jenis'] ?? null,
                $data['pemilik'] ?? null,
                $data['url'] ?? null,
                $data['penerangan'] ?? null,
                $id
            ]);
            
            $this->logActivity('UPDATE_APPLICATION', ['app_id' => $id]);
            $this->sendResponse(200, true, 'Application updated successfully');
        } catch (Exception $e) {
            error_log("Application API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Failed to update application');
        }
    }
    
    /**
     * DELETE - Remove application
     */
    private function handleDelete() {
        $this->validateRequired(['id']);
        
        try {
            $id = $this->getData('id');
            
            // Check if application exists
            $checkSql = "SELECT nama_sistem FROM aplikasi WHERE id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$id]);
            $app = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$app) {
                $this->sendResponse(404, false, 'Application not found');
            }
            
            $sql = "DELETE FROM aplikasi WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            $this->logActivity('DELETE_APPLICATION', ['app_id' => $id, 'name' => $app['nama_sistem']]);
            $this->sendResponse(200, true, 'Application deleted successfully');
        } catch (Exception $e) {
            error_log("Application API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Failed to delete application');
        }
    }
}

// Process the request
$api = new AplikasiAPI();
$api->processRequest();
?>

