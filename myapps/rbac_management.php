<?php
require 'db.php';
require 'security_helper.php';

// Check access
$hasAccess = false;
$canEdit = false;
if ($_SESSION['role'] === 'super_admin') {
    $hasAccess = true;
    $canEdit = true;
} else if ($_SESSION['role'] === 'admin') {
    $checkPerm = $db->prepare("SELECT COUNT(*) as cnt FROM user_roles ur \
                               JOIN role_permissions rp ON ur.id_role = rp.id_role \
                               JOIN permissions p ON rp.id_permission = p.id_permission \
                               WHERE ur.id_user = ? AND p.name = 'view_rbac'");
    $checkPerm->execute([$_SESSION['user_id']]);
    $hasAccess = $checkPerm->fetch()['cnt'] > 0;
}
if (!$hasAccess) {
    header("Location: direktori_aplikasi.php");
    exit();
}

// Helper: Audit log
function logAudit($action, $table_name, $record_id, $old_value, $new_value) {
    global $db;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $db->prepare("INSERT INTO rbac_audit (id_user, action, table_name, record_id, old_value, new_value, ip_address, user_agent) \
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $action, $table_name, $record_id, $old_value, $new_value, $ip, $user_agent]);
}

// Assign role
if (isset($_POST['assign_role'])) {
    if (!$canEdit) { header("Location: direktori_aplikasi.php"); exit(); }
    verifyCsrfToken();
    $id_user = intval($_POST['id_user']);
    $id_role = intval($_POST['id_role']);
    $oldRole = $db->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.id_role = r.id_role WHERE ur.id_user = ?");
    $oldRole->execute([$id_user]);
    $oldRoleName = $oldRole->fetch(PDO::FETCH_ASSOC)['name'] ?? 'No Role';
    $db->prepare("DELETE FROM user_roles WHERE id_user = ?")->execute([$id_user]);
    $newRole = $db->prepare("SELECT name FROM roles WHERE id_role = ?");
    $newRole->execute([$id_role]);
    $newRoleName = $newRole->fetchColumn();
    $db->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (?, ?)")->execute([$id_user, $id_role]);
    logAudit('assign_role', 'user_roles', $id_user, $oldRoleName, $newRoleName);
    $_SESSION['success_msg'] = "Role berjaya ditetapkan!";
    header("Location: rbac_management.php");
    exit();
}

// Update permissions
if (isset($_POST['update_permissions'])) {
    if (!$canEdit) { header("Location: direktori_aplikasi.php"); exit(); }
    verifyCsrfToken();
    $id_role = intval($_POST['id_role']);
    $oldPerms = $db->query("SELECT GROUP_CONCAT(p.name) as perm_list FROM role_permissions rp \
                           JOIN permissions p ON rp.id_permission = p.id_permission \
                           WHERE rp.id_role = $id_role")->fetchColumn();
    $db->prepare("DELETE FROM role_permissions WHERE id_role = ?")->execute([$id_role]);
    $newPerms = [];
    if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
        $stmt = $db->prepare("INSERT INTO role_permissions (id_role, id_permission) VALUES (?, ?)");
        foreach ($_POST['permissions'] as $id_permission) {
            $stmt->execute([$id_role, intval($id_permission)]);
            $permName = $db->query("SELECT name FROM permissions WHERE id_permission = " . intval($id_permission))->fetchColumn();
            $newPerms[] = $permName;
        }
    }
    logAudit('update_permissions', 'role_permissions', $id_role, $oldPerms, implode(', ', $newPerms));
    $_SESSION['success_msg'] = "Permissions berjaya dikemaskini!";
    header("Location: rbac_management.php");
    exit();
}

// Change user status
if (isset($_POST['change_user_status'])) {
    if (!$canEdit) { header("Location: rbac_management.php"); exit(); }
    verifyCsrfToken();
    $id_user = intval($_POST['id_user']);
    $new_status = $_POST['new_status'];
    $valid_statuses = ['active', 'inactive', 'suspended'];
    if (!in_array($new_status, $valid_statuses)) throw new Exception("Status tidak sah");
    $oldStatus = $db->query("SELECT status_account FROM users WHERE id_user = $id_user")->fetchColumn() ?? 'active';
    $db->prepare("UPDATE users SET status_account = ? WHERE id_user = ?")->execute([$new_status, $id_user]);
    logAudit('change_user_status', 'users', $id_user, $oldStatus, $new_status);
    $_SESSION['success_msg'] = "Status pengguna berjaya dikemaskini!";
    header("Location: rbac_management.php?tab=user-status");
    exit();
}

// Change status staf
if (isset($_POST['change_status_staf'])) {
    if (!$canEdit) { header("Location: rbac_management.php"); exit(); }
    verifyCsrfToken();
    $id_user = intval($_POST['id_user']);
    $new_status_staf = $_POST['new_status_staf'];
    $valid_statuses = ['Masih Bekerja', 'Berhenti', 'Bersara', 'Cuti Panjang'];
    if (!in_array($new_status_staf, $valid_statuses)) throw new Exception("Status staf tidak sah");
    $oldStatusStaf = $db->query("SELECT status_staf FROM users WHERE id_user = $id_user")->fetchColumn() ?? 'Masih Bekerja';
    $db->prepare("UPDATE users SET status_staf = ? WHERE id_user = ?")->execute([$new_status_staf, $id_user]);
    $new_account_status = null;
    if (in_array($new_status_staf, ['Berhenti', 'Bersara', 'Cuti Panjang'])) {
        $new_account_status = 'inactive';
        $db->prepare("UPDATE users SET status_account = 'inactive' WHERE id_user = ?")->execute([$id_user]);
    } elseif ($new_status_staf === 'Masih Bekerja') {
        $new_account_status = 'active';
        $db->prepare("UPDATE users SET status_account = 'active' WHERE id_user = ?")->execute([$id_user]);
    }
    $auditMsg = "Status Staf: $oldStatusStaf → $new_status_staf";
    if ($new_account_status) $auditMsg .= " | Account auto-synced to: $new_account_status";
    logAudit('change_status_staf', 'users', $id_user, $oldStatusStaf, $auditMsg);
    $_SESSION['success_msg'] = "Status staf berjaya dikemaskini! Account status auto-synced.";
    header("Location: rbac_management.php?tab=user-status");
    exit();
}

// AJAX: Get all apps & access for role
if (isset($_GET['action']) && $_GET['action'] === 'get_app_access') {
    header('Content-Type: application/json');
    $role_id = intval($_GET['role_id']);
    $allApps = $db->query("SELECT * FROM aplikasi ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
    $assignedApps = $db->query("SELECT id_aplikasi FROM application_access WHERE id_role = $role_id")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['apps' => $allApps, 'assigned' => $assignedApps]);
    exit();
}

// Save app access
if (isset($_POST['save_app_access']) && isset($_POST['selected_role_id'])) {
    verifyCsrfToken();
    $roleId = intval($_POST['selected_role_id']);
    $selectedApps = isset($_POST['app_access']) ? $_POST['app_access'] : [];
    $db->prepare("DELETE FROM application_access WHERE id_role = ?")->execute([$roleId]);
    if (!empty($selectedApps)) {
        $stmt = $db->prepare("INSERT INTO application_access (id_role, id_aplikasi) VALUES (?, ?)");
        foreach ($selectedApps as $id_aplikasi) {
            $stmt->execute([$roleId, intval($id_aplikasi)]);
        }
    }
    echo '<div class="alert alert-success">Akses aplikasi berjaya dikemaskini untuk role!</div>';
}

include 'header.php';
$sort = $_GET['sort'] ?? 'nama';
$order = $_GET['order'] ?? 'ASC';
$user_sort_fields = ['id_user', 'nama', 'no_kp', 'role', 'status_account', 'status_staf', 'last_login', 'emel'];
$audit_sort_fields = ['created_at', 'action', 'ip_address'];
$allowed_sorts = array_merge($user_sort_fields, $audit_sort_fields);
if (!in_array($sort, $allowed_sorts)) $sort = 'nama';
if (!in_array($order, ['ASC', 'DESC'])) $order = 'ASC';
$sql = "SELECT u.id_user, u.nama, u.no_kp, u.status_account, u.status_staf, u.last_login, u.emel, r.name as role
        FROM users u
        LEFT JOIN user_roles ur ON u.id_user = ur.id_user
        LEFT JOIN roles r ON ur.id_role = r.id_role
        ORDER BY ";
if (in_array($sort, $user_sort_fields)) {
    if ($sort === 'role') {
        $sql .= "r.name $order, u.nama ASC";
    } else {
        $sql .= "u.$sort $order";
    }
} else {
    $sql .= "u.nama ASC";
}
$users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$roles = $db->query("SELECT * FROM roles ORDER BY id_role ASC")->fetchAll(PDO::FETCH_ASSOC);
$permissions = $db->query("SELECT * FROM permissions ORDER BY id_permission ASC")->fetchAll(PDO::FETCH_ASSOC);
$rolePermissions = $db->query("SELECT id_role, id_permission FROM role_permissions")->fetchAll(PDO::FETCH_ASSOC);
$rolePermMap = [];
foreach ($rolePermissions as $rp) {
    if (!isset($rolePermMap[$rp['id_role']])) $rolePermMap[$rp['id_role']] = [];
    $rolePermMap[$rp['id_role']][] = $rp['id_permission'];
}
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);
function getSortLink($field, $label, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentSort === $field) {
        $icon = ($currentOrder === 'ASC') ? ' <i class="fas fa-sort-up ms-2"></i>' : ' <i class="fas fa-sort-down ms-2"></i>';
    } else {
        $icon = ' <i class="fas fa-sort text-muted opacity-50 ms-2"></i>';
    }
    $tab = isset($_GET['tab']) ? '&tab=' . htmlspecialchars($_GET['tab']) : '';
    return "<a href='?sort=$field&order=$newOrder$tab' class='text-dark text-decoration-none fw-bold' style='cursor: pointer;'>$label$icon</a>";
}
function formatRoleName($roleName) {
    if (empty($roleName)) return 'No Role';
    $formatted = str_replace('_', ' ', $roleName);
    return ucwords($formatted);
}
?>
<!-- ... UI HTML & Bootstrap 5 code for all 6 tabs, search, sort, and JS ... -->
