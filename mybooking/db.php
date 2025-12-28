<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "Noor@z@m1982";

try {
    $db = new PDO(
        'mysql:host=' . $host . ';dbname=mybooking;charset=utf8mb4',
        $user,
        $pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions (only if not already defined)
if (!function_exists('formatTarikh')) {
function formatTarikh($tarikh) {
    if (empty($tarikh)) return "-";
    return date('d/m/Y', strtotime($tarikh));
}
}

if (!function_exists('formatDateTime')) {
function formatDateTime($datetime) {
    if (empty($datetime)) return "-";
    return date('d/m/Y H:i', strtotime($datetime));
}
}

if (!function_exists('getRoleName')) {
function getRoleName($id_role) {
    global $db;
    $stmt = $db->prepare("SELECT nama_role FROM role WHERE id_role = ?");
    $stmt->execute([$id_role]);
    $result = $stmt->fetch();
    return $result ? $result['nama_role'] : 'Unknown';
}
}

if (!function_exists('getStatusText')) {
function getStatusText($status) {
    $statuses = [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Rejected',
        3 => 'Cancelled'
    ];
    return $statuses[$status] ?? 'Unknown';
}
}

if (!function_exists('getStatusColor')) {
function getStatusColor($status) {
    $colors = [
        0 => 'warning',
        1 => 'success',
        2 => 'danger',
        3 => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}
}

if (!function_exists('hasRole')) {
function hasRole($id_staf, $id_role) {
    global $db;
    $stmt = $db->prepare("SELECT id_akses FROM akses WHERE id_staf = ? AND id_role = ?");
    $stmt->execute([$id_staf, $id_role]);
    return $stmt->fetch() ? true : false;
}
}

if (!function_exists('getUserRoles')) {
function getUserRoles($id_staf) {
    global $db;
    $stmt = $db->prepare("
        SELECT r.nama_role FROM akses a
        JOIN role r ON a.id_role = r.id_role
        WHERE a.id_staf = ?
        ORDER BY r.id_role
    ");
    $stmt->execute([$id_staf]);
    $roles = $stmt->fetchAll();
    return array_column($roles, 'nama_role');
}
}

if (!function_exists('canApprove')) {
function canApprove($id_staf) {
    return hasRole($id_staf, 2) || hasRole($id_staf, 3); // Manager or Admin
}
}

if (!function_exists('isAdmin')) {
function isAdmin($id_staf) {
    return hasRole($id_staf, 3); // Admin only
}
}

if (!function_exists('isRoomAvailable')) {
function isRoomAvailable($id_bilik, $tarikh, $masa_mula, $masa_tamat, $exclude_id_booking = null) {
    global $db;
    
    $sql = "SELECT COUNT(*) as count FROM booking 
            WHERE id_bilik = ? 
            AND tarikh_mula = ? 
            AND status IN (0, 1)
            AND (
                (masa_mula < ? AND masa_tamat > ?)
            )";
    
    $params = [$id_bilik, $tarikh, $masa_tamat, $masa_mula];
    
    if ($exclude_id_booking) {
        $sql .= " AND id_booking != ?";
        $params[] = $exclude_id_booking;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['count'] == 0;
}
}
?>
