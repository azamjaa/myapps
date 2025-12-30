<?php
// IMPROVE SESSION SECURITY (MUST BE SET BEFORE session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// PHP SECURITY SETTINGS (moved from .htaccess for FastCGI compatibility)
ini_set('expose_php', 0); // Hide PHP version
ini_set('display_errors', 0); // Don't show errors to users
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1); // Log errors instead
ini_set('upload_max_filesize', '2M'); // File upload limit
ini_set('post_max_size', '2M'); // POST data limit

// MULA SESI JIKA BELUM ADA
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
    $db = $db; // Alias untuk kemudahan
} catch(PDOException $e) {
    // Don't expose detailed error in production
    if ($config['app']['debug']) {
        die("Database Connection Error: " . $e->getMessage());
    } else {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Maaf, sistem mengalami masalah teknikal. Sila hubungi admin.");
    }
}
?>
