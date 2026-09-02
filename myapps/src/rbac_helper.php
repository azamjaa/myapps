<?php
/**
 * RBAC Helper Functions for Centralized Access Control
 * Sesuai untuk projek MyApps dengan jadual: users, roles, permissions, user_roles, role_permissions, aplikasi
 * Pastikan $pdo ialah instance PDO yang sah
 */

/**
 * Semak jika user mempunyai permission tertentu untuk aplikasi spesifik
 * @param PDO $pdo
 * @param int $user_id
 * @param int $app_id
 * @param string $perm_name
 * @return bool
 */
function checkUserAccess($pdo, $user_id, $app_id, $perm_name) {
    $sql = "SELECT COUNT(*) FROM user_roles ur
            JOIN role_permissions rp ON ur.id_role = rp.id_role
            JOIN permissions p ON rp.id_permission = p.id_permission
            WHERE ur.id_user = :user_id
              AND ur.id_aplikasi = :app_id
              AND p.name = :perm_name
              AND p.id_aplikasi = :app_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'   => $user_id,
        'app_id'    => $app_id,
        'perm_name' => $perm_name
    ]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Pulangkan senarai aplikasi yang user boleh akses berdasarkan role
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getUserApps($pdo, $user_id) {
    $sql = "SELECT DISTINCT a.*
            FROM aplikasi a
            JOIN role_permissions rp ON rp.id_permission IN (
                SELECT id_permission FROM permissions WHERE id_aplikasi = a.id_aplikasi
            )
            JOIN user_roles ur ON rp.id_role = ur.id_role
            WHERE ur.id_user = :user_id
            ORDER BY a.nama_aplikasi ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Semak jika user ialah Super Admin (role_id = 1)
 * @param PDO $pdo
 * @param int $user_id
 * @return bool
 */
function isSuperAdmin($pdo, $user_id) {
    $sql = "SELECT COUNT(*) FROM user_roles WHERE id_user = :user_id AND id_role = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Contoh: Simpan user_type dalam session selepas login
 * (Letak kod ini dalam proses login anda)
 */
// $_SESSION['user_type'] = $user_type; // Contoh: 'STAF' atau 'AWAM'

/**
 * Contoh penggunaan dalam header.php:
 * if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'STAF') { ... }
 */

?>
