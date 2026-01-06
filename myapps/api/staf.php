<?php
/**
 * Staff API Endpoint - SSOT
 * Handles all staff-related operations
 * 
 * @version 2.0
 */

require_once 'api.php';

class StafAPI extends API {
    
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
     * GET - Retrieve staff data
     */
    private function handleGet() {
        $id = $this->getData('id');
        
        if ($id) {
            // Get single staff
            $this->getStaff($id);
        } else {
            // Get all staff
            $this->getAllStaff();
        }
    }
    
    /**
     * Get single staff by ID
     */
    private function getStaff($id) {
        try {
            $sql = "SELECT * FROM users WHERE id_user = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $staf = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staf) {
                $this->logActivity('VIEW_STAFF', ['staff_id' => $id]);
                $this->sendResponse(200, true, 'Staff found', $staf);
            } else {
                $this->sendResponse(404, false, 'Staff not found');
            }
        } catch (Exception $e) {
            error_log("Staff API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Internal server error');
        }
    }
    
    /**
     * Get all staff with optional filters
     */
    private function getAllStaff() {
        try {
            $search = $this->getData('search');
            $department = $this->getData('department');
            $limit = $this->getData('limit', 100);
            $offset = $this->getData('offset', 0);
            
            $sql = "SELECT * FROM users WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (nama LIKE ? OR no_staf LIKE ? OR emel LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($department) {
                $sql .= " AND bahagian = ?";
                $params[] = $department;
            }
            
            $sql .= " ORDER BY nama ASC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
            $countParams = [];
            if ($search) {
                $countSql .= " AND (nama LIKE ? OR no_staf LIKE ? OR emel LIKE ?)";
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
            }
            if ($department) {
                $countSql .= " AND bahagian = ?";
                $countParams[] = $department;
            }
            
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $this->logActivity('LIST_STAFF', ['count' => count($staff)]);
            $this->sendResponse(200, true, 'Staff list retrieved', [
                'staff' => $staff,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (Exception $e) {
            error_log("Staff API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Internal server error');
        }
    }
    
    /**
     * POST - Create new staff
     */
    private function handlePost() {
        $this->validateRequired(['nama', 'no_staf', 'email']);
        
        try {
            $data = $this->sanitize($this->getData());
            
            // Check for duplicate staff number
            $checkSql = "SELECT id_user FROM users WHERE no_staf = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$data['no_staf']]);
            
            if ($checkStmt->fetch()) {
                $this->sendResponse(409, false, 'Staff number already exists');
            }
            
            $sql = "INSERT INTO users (nama, no_staf, emel, telefon, id_bahagian, id_jawatan, tarikh_lahir, gambar) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nama'],
                $data['no_staf'],
                $data['emel'],
                $data['telefon'] ?? null,
                $data['id_bahagian'] ?? null,
                $data['id_jawatan'] ?? null,
                $data['tarikh_lahir'] ?? null,
                $data['gambar'] ?? null
            ]);
            
            $newId = $this->db->lastInsertId();
            
            $this->logActivity('CREATE_STAFF', ['staff_id' => $newId, 'name' => $data['nama']]);
            $this->sendResponse(201, true, 'Staff created successfully', ['id' => $newId]);
        } catch (Exception $e) {
            error_log("Staff API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Failed to create staff');
        }
    }
    
    /**
     * PUT - Update staff
     */
    private function handlePut() {
        $this->validateRequired(['id']);
        
        try {
            $data = $this->sanitize($this->getData());
            $id = $data['id'];
            
            // Check if staff exists
            $checkSql = "SELECT id_user FROM users WHERE id_user = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                $this->sendResponse(404, false, 'Staff not found');
            }
            
            $sql = "UPDATE users SET 
                    nama = COALESCE(?, nama),
                    emel = COALESCE(?, emel),
                    telefon = COALESCE(?, telefon),
                    id_bahagian = COALESCE(?, id_bahagian),
                    id_jawatan = COALESCE(?, id_jawatan),
                    tarikh_lahir = COALESCE(?, tarikh_lahir),
                    gambar = COALESCE(?, gambar)
                    WHERE id_user = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nama'] ?? null,
                $data['emel'] ?? null,
                $data['telefon'] ?? null,
                $data['id_bahagian'] ?? null,
                $data['id_jawatan'] ?? null,
                $data['tarikh_lahir'] ?? null,
                $data['gambar'] ?? null,
                $id
            ]);
            
            $this->logActivity('UPDATE_STAFF', ['staff_id' => $id]);
            $this->sendResponse(200, true, 'Staff updated successfully');
        } catch (Exception $e) {
            error_log("Staff API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Failed to update staff');
        }
    }
    
    /**
     * DELETE - Remove staff
     */
    private function handleDelete() {
        $this->validateRequired(['id']);
        try {
            $id = $this->getData('id');
            // Check if staff exists
            $checkSql = "SELECT nama FROM users WHERE id_user = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$id]);
            $staff = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$staff) {
                $this->sendResponse(404, false, 'Staff not found');
            }
            // Soft delete: set is_deleted=1
            $sql = "UPDATE users SET is_deleted = 1 WHERE id_user = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $this->logActivity('SOFT_DELETE_STAFF', ['staff_id' => $id, 'name' => $staff['nama']]);
            $this->sendResponse(200, true, 'Staff soft deleted successfully');
        } catch (Exception $e) {
            error_log("Staff API Error: " . $e->getMessage());
            $this->sendResponse(500, false, 'Failed to soft delete staff');
        }
    }
}

// Process the request
$api = new StafAPI();
$api->processRequest();
?>

