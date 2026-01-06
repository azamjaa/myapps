<?php
/**
 * RBAC API Handler
 * Handles all RBAC operations (Users, Roles, Permissions)
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
require_once '../db.php';
require_once '../security_helper.php';
require_once '../src/rbac_helper.php';

header('Content-Type: application/json');

// Verify user is super admin
$current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);

if (!$current_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User tidak login']);
    exit;
}

try {
    global $pdo;
    $isAdmin = isSuperAdmin($pdo, $current_user);
    if (!$isAdmin) {
        throw new Exception('Akses tidak dibenarkan. Anda bukan super admin.');
    }
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Get action
$action = $_POST['action'] ?? '';

global $pdo;

try {
    if ($action === 'saveUser') {
        $userId = $_POST['userId'] ?? null;
        $userName = $_POST['userName'] ?? null;
        $userEmail = $_POST['userEmail'] ?? null;
        $userRole = $_POST['userRole'] ?? null;
        
        if (!$userName || !$userEmail) {
            throw new Exception('Nama dan email diperlukan');
        }
        
        if ($userId) {
            // Update existing user
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, emel = ? WHERE id_user = ?");
            $stmt->execute([$userName, $userEmail, $userId]);
            
            // Update user role if provided
            if ($userRole && $userRole !== '') {
                // Get role ID
                $roleStmt = $pdo->prepare("SELECT id_role FROM roles WHERE name = ?");
                $roleStmt->execute([$userRole]);
                $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($roleData) {
                    // Delete existing roles first
                    $deleteStmt = $pdo->prepare("DELETE FROM user_roles WHERE id_user = ?");
                    $deleteStmt->execute([$userId]);
                    
                    // Insert new role
                    $insertStmt = $pdo->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (?, ?)");
                    $insertStmt->execute([$userId, $roleData['id_role']]);
                }
            } elseif ($userRole === '') {
                // Remove all roles if empty selection
                $deleteStmt = $pdo->prepare("DELETE FROM user_roles WHERE id_user = ?");
                $deleteStmt->execute([$userId]);
            }
        } else {
            throw new Exception('Pengguna baru tidak boleh dibuat di sini. Sila gunakan sistem pendaftaran yang sedia ada.');
        }
        
        echo json_encode(['success' => true, 'message' => 'User disimpan berjaya']);
    }
    
    elseif ($action === 'saveRole') {
        $roleId = $_POST['roleId'] ?? null;
        $roleName = $_POST['roleName'] ?? null;
        $roleDesc = $_POST['roleDesc'] ?? null;
        
        if (!$roleName) {
            throw new Exception('Nama role diperlukan');
        }
        
        // Normalize role name (lowercase, underscores)
        $normalizedName = strtolower(str_replace(' ', '_', $roleName));
        
        if ($roleId) {
            // Update existing role
            $stmt = $pdo->prepare("UPDATE roles SET name = ?, description = ? WHERE id_role = ?");
            $stmt->execute([$normalizedName, $roleDesc, $roleId]);
        } else {
            // Create new role
            $stmt = $pdo->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
            $stmt->execute([$normalizedName, $roleDesc]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Role disimpan berjaya']);
    }
    
    elseif ($action === 'savePermission') {
        $permissionId = $_POST['permissionId'] ?? null;
        $permissionName = $_POST['permissionName'] ?? null;
        $permissionDesc = $_POST['permissionDesc'] ?? null;
        
        if (!$permissionName) {
            throw new Exception('Nama permission diperlukan');
        }
        
        // Normalize permission name (lowercase, underscores)
        $normalizedName = strtolower(str_replace(' ', '_', $permissionName));
        
        if ($permissionId) {
            // Update existing permission
            $stmt = $pdo->prepare("UPDATE permissions SET name = ?, description = ? WHERE id_permission = ?");
            $stmt->execute([$normalizedName, $permissionDesc, $permissionId]);
        } else {
            // Create new permission
            $stmt = $pdo->prepare("INSERT INTO permissions (name, description) VALUES (?, ?)");
            $stmt->execute([$normalizedName, $permissionDesc]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Permission disimpan berjaya']);
    }
    
    elseif ($action === 'togglePermission') {
        $roleId = $_POST['roleId'] ?? null;
        $permId = $_POST['permId'] ?? null;
        $grant = $_POST['grant'] ?? null;
        
        if (!$roleId || !$permId) {
            throw new Exception('Role ID dan Permission ID diperlukan');
        }
        
        // Check if relationship already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM role_permissions WHERE id_role = ? AND id_permission = ?");
        $checkStmt->execute([$roleId, $permId]);
        $exists = $checkStmt->fetchColumn() > 0;
        
        if ($grant == '1') {
            // Grant permission (insert if not exists)
            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (id_role, id_permission) VALUES (?, ?)");
                $stmt->execute([$roleId, $permId]);
            }
            echo json_encode(['success' => true, 'message' => 'Permission ditambah']);
        } else {
            // Revoke permission (delete if exists)
            if ($exists) {
                $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE id_role = ? AND id_permission = ?");
                $stmt->execute([$roleId, $permId]);
            }
            echo json_encode(['success' => true, 'message' => 'Permission dihapus']);
        }
    }
    
    else {
        throw new Exception('Action tidak diketahui: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    error_log('RBAC API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;
?>
