<?php
// Fungsi semak akses berdasarkan user, aplikasi, dan nama permission
// Return TRUE jika user ada permission untuk app, FALSE jika tidak
require_once __DIR__ . '/../db.php';

function hasAccess($user_id, $app_id, $permission_name) {
    global $db;
    $sql = "SELECT COUNT(*) FROM user_roles ur
            JOIN roles r ON ur.id_role = r.id_role
            JOIN role_permissions rp ON r.id_role = rp.id_role
            JOIN permissions p ON rp.id_permission = p.id_permission
            WHERE ur.id_user = ?
              AND p.name = ?
              AND (rp.id_app = ? OR rp.id_app IS NULL)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id, $permission_name, $app_id]);
    return $stmt->fetchColumn() > 0;
}
// Contoh penggunaan:
// if (hasAccess($user_id, $app_id, 'view_dashboard')) { ... }
