<?php
/**
 * DIY Aplikasi SSO & SSOT Helper
 * Semua aplikasi janaan DIY Aplikasi guna auth ini - SSO (JWT/session) + SSOT (sumber identiti dari DB)
 * 
 * @author MyApps KEDA
 * @version 1.0
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// JWT_SECRET_KEY & APP_URL biasanya sudah ditakrif dalam db.php
if (!defined('JWT_SECRET_KEY')) {
    $cfg = file_exists(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : [];
    define('APP_URL', $cfg['app']['url'] ?? 'http://localhost');
    define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY') ?: (getenv('APP_KEY') ?: 'myapps-keda-jwt-secret-key'));
}
if (!defined('APP_URL')) {
    $cfg = file_exists(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : [];
    define('APP_URL', $cfg['app']['url'] ?? 'http://localhost');
}

require_once __DIR__ . '/JWT.php';

/**
 * SSOT: Dapatkan maklumat user dari DB (satu sumber sahaja)
 */
function nocode_get_user_from_db($db, $user_id) {
    $stmt = $db->prepare("
        SELECT u.id_user, u.nama, u.emel, u.gambar, u.no_kp
        FROM users u
        WHERE u.id_user = ? AND u.aktif = 1
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return null;
    
    // Role dari DB (SSOT)
    $roleStmt = $db->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.id_role = r.id_role WHERE ur.id_user = ? ORDER BY ur.id_role ASC LIMIT 1");
    $roleStmt->execute([$user_id]);
    $user['role'] = $roleStmt->fetchColumn() ?: 'user_biasa';
    
    return $user;
}

/**
 * Sahkan JWT token dan kembalikan payload data
 */
function nocode_verify_jwt_token($token) {
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY, 'HS256');
        return [
            'success' => true,
            'user_id' => $decoded->data->user_id ?? null,
            'username' => $decoded->data->username ?? null,
            'role' => $decoded->data->role ?? 'user_biasa',
            'email' => $decoded->data->email ?? null
        ];
    } catch (Exception $e) {
        return ['success' => false];
    }
}

/**
 * SSO + SSOT: Pastikan user diautentikasi.
 * Terima sama ada (1) session user_id atau (2) session sso_token (JWT).
 * Identiti sentiasa disegerakkan dari DB (SSOT).
 * Redirect ke index.php jika tidak sah.
 */
function nocode_ensure_auth() {
    global $db;
    
    $user_id = null;
    
    // 1) SSO: Semak JWT token dalam session
    if (!empty($_SESSION['sso_token'])) {
        $result = nocode_verify_jwt_token($_SESSION['sso_token']);
        if ($result['success'] && !empty($result['user_id'])) {
            $user_id = (int) $result['user_id'];
        } else {
            // Token tamat atau tidak sah
            unset($_SESSION['sso_token']);
        }
    }
    
    // 2) Fallback: session user_id (login biasa melalui index.php)
    if ($user_id === null && !empty($_SESSION['user_id'])) {
        $user_id = (int) $_SESSION['user_id'];
    }
    
    if ($user_id === null) {
        header('Location: index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
    
    // SSOT: Sentiasa muat maklumat user dari DB (satu sumber)
    $user = nocode_get_user_from_db($db, $user_id);
    if (!$user) {
        session_destroy();
        header('Location: index.php?expired=1');
        exit();
    }
    
    // Segerakkan session dari DB
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['gambar'] = $user['gambar'] ?? '';
    $_SESSION['role'] = $user['role'];
    
    return true;
}

/**
 * SSO + SSOT untuk API: sama seperti nocode_ensure_auth() tetapi return 401 JSON jika gagal.
 * Terima token dari session atau Authorization: Bearer header.
 */
function nocode_ensure_auth_api() {
    global $db;
    
    $user_id = null;
    
    // 1) Session sso_token
    if (!empty($_SESSION['sso_token'])) {
        $result = nocode_verify_jwt_token($_SESSION['sso_token']);
        if ($result['success'] && !empty($result['user_id'])) {
            $user_id = (int) $result['user_id'];
        }
    }
    
    // 2) Authorization Bearer header (untuk panggilan API dengan token)
    if ($user_id === null && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(\S+)/', $auth, $m)) {
            $result = nocode_verify_jwt_token($m[1]);
            if ($result['success'] && !empty($result['user_id'])) {
                $user_id = (int) $result['user_id'];
            }
        }
    }
    
    // 3) Session user_id
    if ($user_id === null && !empty($_SESSION['user_id'])) {
        $user_id = (int) $_SESSION['user_id'];
    }
    
    if ($user_id === null) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Tidak dibenarkan. Sila log masuk.']);
        exit();
    }
    
    // SSOT: Muat user dari DB
    $user = nocode_get_user_from_db($db, $user_id);
    if (!$user) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesi tamat. Sila log masuk semula.']);
        exit();
    }
    
    $_SESSION['user_id'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['gambar'] = $user['gambar'] ?? '';
    $_SESSION['role'] = $user['role'];
    
    return true;
}
