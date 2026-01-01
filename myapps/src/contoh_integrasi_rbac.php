<?php
// Contoh penggunaan RBAC centralized dalam aplikasi lain
require_once __DIR__ . '/../src/rbac_access_helper.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;
$app_code = 'KOD_APLIKASI_SAYA'; // Gantikan dengan kod aplikasi sebenar

if (!$user_id || !check_app_access($user_id, $app_code)) {
    http_response_code(403);
    echo '<h3>Akses tidak dibenarkan. Sila hubungi admin.</h3>';
    exit;
}
// ... kod aplikasi anda di sini ...
