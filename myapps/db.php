<?php
// SEMUA SETTING SESSION MESTI SEBELUM session_start()
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// PHP SECURITY SETTINGS (moved from .htaccess for FastCGI compatibility)
$is_production = (getenv('APP_ENV') === 'production');
ini_set('expose_php', 0); // Hide PHP version
ini_set('display_errors', $is_production ? 0 : 1); // Hide errors in production
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1); // Log errors instead
ini_set('upload_max_filesize', '2M'); // File upload limit
ini_set('post_max_size', '2M'); // POST data limit

// SECURITY HEADERS
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if ($is_production) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// SET ZON MASA
date_default_timezone_set('Asia/Kuala_Lumpur');

// ============================================================
// CSRF PROTECTION FUNCTIONS
// ============================================================
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfTokenField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

function verifyCsrfToken() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        http_response_code(403);
        die('CSRF token validation failed. Sila refresh page dan cuba lagi.');
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('CSRF token validation failed. Sila refresh page dan cuba lagi.');
    }
}

// 1. FUNGSI PEMBANTU (WAJIB LETAK DI ATAS)
function getUmur($no_kp) {
    if (empty($no_kp) || strlen($no_kp) < 12) return 0;
    $tahun = substr($no_kp, 0, 2);
    $prefix = ($tahun > date('y')) ? "19" : "20";
    return date('Y') - ($prefix . $tahun);
}

function getTarikhLahir($no_kp) {
    if (empty($no_kp) || strlen($no_kp) < 12) return "-";
    $hari = substr($no_kp, 4, 2);
    $bulan = substr($no_kp, 2, 2);
    $tahun = substr($no_kp, 0, 2);
    $prefix = ($tahun > date('y')) ? "19" : "20";
    return "$hari/$bulan/$prefix$tahun";
}

// 2. LOAD CONFIGURATION
try {
    $config = require_once __DIR__ . '/config.php';
} catch (Exception $e) {
    // Fallback if config.php has issues
    $config = [
        'database' => [
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'Noor@z@m1982',
            'database' => 'myapps'
        ],
        'app' => [
            'debug' => false
        ]
    ];
    error_log("Config load error: " . $e->getMessage());
}

// 3. SAMBUNGAN DATABASE
$host = $config['database']['host'];
$user = $config['database']['username'];
$pass = $config['database']['password']; 
$dbname = $config['database']['database']; 

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo = $db; // Gunakan $pdo sebagai variable global
} catch(PDOException $e) {
    // Don't expose detailed error in production
    if ($config['app']['debug']) {
        die("Database Connection Error: " . $e->getMessage());
    } else {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Maaf, sistem mengalami masalah teknikal. Sila hubungi admin.");
    }
}

/**
 * Fungsi Semak Akses RBAC Enterprise
 * @param int $user_id ID pengguna dari session
 * @param int $app_id ID aplikasi (rujuk table aplikasi) - tidak digunakan untuk permission check, hanya untuk backward compatibility
 * @param string $perm_name Nama permission (contoh: 'create_user', 'edit_user', 'manage_roles')
 */
function hasAccess($pdo, $user_id, $app_id, $perm_name) {
    $sql = "SELECT COUNT(*) 
            FROM user_roles ur
            JOIN role_permissions rp ON ur.id_role = rp.id_role
            JOIN permissions p ON rp.id_permission = p.id_permission
            WHERE ur.id_user = :user_id 
            AND ur.id_aplikasi = :app_id
            AND p.name = :perm_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'   => $user_id,
        'app_id'    => $app_id,
        'perm_name' => $perm_name
    ]);
    return $stmt->fetchColumn() > 0;
}
?>
