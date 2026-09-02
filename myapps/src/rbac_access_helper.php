<?php
// RBAC Centralized Access Checker for MyApps
// Usage: include this file in any app, then call check_app_access($user_id, $app_code)

require_once __DIR__ . '/../db.php'; // Adjust path as needed

function check_app_access($user_id, $app_code) {
    global $db;
    // Get app id from code
    $stmt = $db->prepare("SELECT id_aplikasi FROM aplikasi WHERE kod_aplikasi = ? LIMIT 1");
    $stmt->execute([$app_code]);
    $id_aplikasi = $stmt->fetchColumn();
    if (!$id_aplikasi) return false;

    // Get user's role(s)
    $stmt = $db->prepare("SELECT ur.id_role FROM user_roles ur WHERE ur.id_user = ?");
    $stmt->execute([$user_id]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($roles)) return false;

    // Check if any of user's roles have access to this app
    $in = str_repeat('?,', count($roles) - 1) . '?';
    $params = $roles;
    $params[] = $id_aplikasi;
    $sql = "SELECT COUNT(*) FROM application_access WHERE id_role IN ($in) AND id_aplikasi = ?";
    $count = $db->prepare($sql);
    $count->execute($params);
    return $count->fetchColumn() > 0;
}

// Example usage in any app:
// if (!check_app_access($_SESSION['user_id'], 'KOD_APLIKASI')) { die('Akses tidak dibenarkan'); }
