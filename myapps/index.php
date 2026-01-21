<script>
            // Buka semua link ke domain luar di browser (tab baru)
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('a').forEach(function(link) {
                    if (
                        link.hostname &&
                        link.hostname !== window.location.hostname
                    ) {
                        link.addEventListener('click', function(e) {
                            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
                                e.preventDefault();
                                window.open(link.href, '_blank');
                            }
                        });
                    }
                });
            });
            </script>

<?php
/**
 * MyApps KEDA
 * Login Page
 * 
 * @version 2.0
 */

// PREVENT BROWSER CACHING
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Error handling
error_reporting(E_ALL);
$is_production = (getenv('APP_ENV') === 'production');
ini_set('display_errors', $is_production ? 0 : 1);

require 'db.php';

// Load optional components with error handling
$hasJWT = file_exists('JWT.php');
$hasAudit = file_exists('audit_functions.php');

if ($hasJWT) {
    require_once 'JWT.php';
}

if ($hasAudit) {
    require_once 'audit_functions.php';
}

$error = '';
$success = '';

if (isset($_POST['login'])) {
    // Rate limiting check (5 attempts per 15 minutes)
    $ip = $_SERVER['REMOTE_ADDR'];
    $limit_key = 'login_attempt_' . $ip;
    $max_attempts = 5;
    $window = 900; // 15 minutes
    
    if (isset($_SESSION[$limit_key])) {
        if (time() - $_SESSION[$limit_key]['timestamp'] < $window) {
            $_SESSION[$limit_key]['count']++;
            if ($_SESSION[$limit_key]['count'] > $max_attempts) {
                $error = 'Terlalu banyak percobaan login gagal. Sila cuba lagi dalam 15 minit.';
            }
        } else {
            $_SESSION[$limit_key] = ['count' => 1, 'timestamp' => time()];
        }
    } else {
        $_SESSION[$limit_key] = ['count' => 1, 'timestamp' => time()];
    }
    
    if (empty($error)) {
        $no_kp = $_POST['no_kp'];
        $password = $_POST['password']; 

        // Find user & security info
            $stmt = $db->prepare("SELECT u.id_user, u.nama, u.emel, u.gambar, l.password_hash, l.tarikh_tukar_katalaluan
                       FROM users u 
                       JOIN login l ON u.id_user = l.id_user 
                       WHERE u.no_kp = ? AND u.id_status_staf = 1");
        $stmt->execute([$no_kp]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $db_hash = $user['password_hash'];
            // Use password_verify directly - no bracket trimming needed
            
            if (password_verify($password, $db_hash)) {
                if ($password === '123456') {
                    $_SESSION['temp_id'] = $user['id_user'];
                    header("Location: tukar_katalaluan_wajib.php");
                    exit();
                }

                // 2. AUTO EXPIRED (90 DAYS)
                $tarikh_last = new DateTime($user['tarikh_tukar_katalaluan']);
                $tarikh_kini = new DateTime();
                if ($tarikh_last->diff($tarikh_kini)->days > 90) {
                    $_SESSION['temp_id'] = $user['id_user'];
                    header("Location: tukar_katalaluan_wajib.php");
                    exit();
                }

                // 3. LOGIN SUCCESS
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['gambar'] = $user['gambar'];

                // Ambil role utama dari user_roles (role dengan id_role paling kecil dianggap utama)
                $roleQ = $db->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.id_role = r.id_role WHERE ur.id_user = ? ORDER BY ur.id_role ASC LIMIT 1");
                $roleQ->execute([$user['id_user']]);
                $mainRole = $roleQ->fetchColumn();
                if ($mainRole) {
                    $_SESSION['role'] = $mainRole;
                } else {
                    // fallback: jika user tidak ada role, set sebagai user_biasa
                    $_SESSION['role'] = 'user_biasa';
                }
                
                // Generate JWT Token if available
                if ($hasJWT && defined('JWT_SECRET_KEY')) {
                    try {
                        $issuedAt = time();
                        $expirationTime = $issuedAt + 3600 * 8; // 8 hours
                        
                        $payload = [
                            'iat' => $issuedAt,
                            'exp' => $expirationTime,
                            'iss' => defined('APP_URL') ? APP_URL : 'http://127.0.0.1/myapps',
                            'aud' => defined('APP_URL') ? APP_URL : 'http://127.0.0.1/myapps',
                            'data' => [
                                'user_id' => $user['id_user'],
                                'username' => $no_kp,
                                'role' => $_SESSION['role'],
                                'email' => $user['emel'] ?? ''
                            ]
                        ];
                        
                        $token = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
                        $_SESSION['sso_token'] = $token;
                    } catch (Exception $e) {
                        error_log("JWT Error: " . $e->getMessage());
                    }
                }
                
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Update last login timestamp and reset login attempts
                $db->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0, locked_until = NULL WHERE id_user = ?")
                   ->execute([$user['id_user']]);

                // Secure Redirect
                $allowed_redirects = [
                    'dashboard_aplikasi.php', 
                    'dashboard_perjawatan.php', 
                    'kalendar.php'
                ];
                
                $redirect_to = 'dashboard_aplikasi.php';
                
                if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                    $requested_redirect = basename($_GET['redirect']);
                    if (in_array($requested_redirect, $allowed_redirects)) {
                        $redirect_to = $requested_redirect;
                    }
                }
                
                // Log successful login if audit available
                if ($hasAudit && function_exists('log_audit')) {
                        log_audit('LOGIN_SUCCESS', 'users', $user['id_user'], null, $user['nama']);
                }
                
                header("Location: " . $redirect_to);
                exit();

            } else {
                $error = 'Kombinasi No. Kad Pengenalan dan Kata Laluan tidak sah.';
                if ($hasAudit && function_exists('log_audit')) {
                    log_audit('LOGIN_FAILED', 'users', null, null, 'Wrong password: ' . $no_kp);
                }
            }
        } else {
            $error = 'Akaun tidak ditemui atau tidak aktif.';
            if ($hasAudit && function_exists('log_audit')) {
                log_audit('LOGIN_FAILED', 'users', null, null, 'User not found: ' . $no_kp);
            }
        }
    }
}

// Check if session expired
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = 'Sesi tamat tempoh. Sila log masuk semula.';
}

// Check if account is not active
if (isset($_GET['notactive']) && $_GET['notactive'] == 1) {
    $error = 'Akaun anda tidak lagi aktif. Sila hubungi pentadbir sistem.';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MyApps KEDA - Direktori Apliaksi KEDA">
    <meta name="theme-color" content="#d32f2f">
    
    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MyApps KEDA">
    
    <title>Log Masuk - MyApps KEDA</title>
    
    <link rel="icon" type="image/png" href="image/keda.png?v=2">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            /* BACKGROUND (Sama dengan lupa_katalaluan.php) */
            background-color: #1a252f; 
            background-image: url('image/background.jpg');
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            background-size: cover;
        }
        .overlay { background-color: rgba(0, 0, 0, 0); position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        
        /* Style Kad */
        .card-login { 
            background: rgba(255, 255, 255, 0.98); 
            border-radius: 15px; 
            z-index: 2; 
            border-top: 5px solid #d32f2f; /* Merah KEDA */
        }
        /* Butang Merah KEDA */
        .btn-keda { background-color: #d32f2f; color: white; }
        .btn-keda:hover { background-color: #b71c1c; color: white; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 position-relative">
    <div class="overlay"></div>
    <div class="card card-login p-5 shadow-lg" style="width: 400px;">
        <div class="text-center mb-4">
            <img src="image/keda.png" alt="Logo KEDA" style="width: 100px; margin-bottom: 15px;">
            <h3 class="fw-bold text-dark">MyApps</h3>
            <p class="text-muted small">Direktori Aplikasi KEDA</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger text-center small py-2"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success text-center small py-2"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">
                    <?php echo $t['username'] ?? 'NO. KAD PENGENALAN'; ?> 
                    <span class="text-danger">(TANPA "-")</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-id-card text-muted"></i></span>
                    <input type="text" 
                           name="no_kp" 
                           class="form-control border-start-0" 
                           required 
                           placeholder="Contoh: 900101011234"
                           autocomplete="username">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">KATA LALUAN</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" 
                           name="password" 
                           id="passLogin" 
                           class="form-control border-start-0" 
                           required 
                           placeholder="Kata Laluan"
                           autocomplete="current-password">
                    <button type="button" 
                            onclick="togglePassword('passLogin', 'iconLogin')" 
                            class="input-group-text bg-light border-start-0 border-end-0" 
                            style="cursor: pointer;">
                        <i class="fas fa-eye text-muted" id="iconLogin"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" name="login" class="btn btn-keda w-100 py-2 fw-bold shadow-sm">
                <i class="fas fa-sign-in-alt me-2"></i>
                LOG MASUK
            </button>
            
            <div class="text-center mt-4 pt-3 border-top">
                <a href="lupa_katalaluan.php" class="text-decoration-none text-muted small">
                    <i class="fas fa-key me-1"></i> Lupa Kata Laluan?
                </a>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted" style="font-size: 10px;">&copy; <?php echo date('Y'); ?> Lembaga Kemajuan Wilayah Kedah</small>
        </div>
    </div>
    
    <script>
    // Toggle Password Visibility
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    
    // Show PWA install hint if available
    window.addEventListener('beforeinstallprompt', (e) => {
        const btn = document.getElementById('pwa-install-hint');
        if (btn) btn.classList.remove('hidden');
    });
    
    // Fallback PWA install function if pwa-installer.js not loaded
    if (typeof installPWA === 'undefined') {
        function installPWA() {
            alert('PWA installation will be available soon!');
        }
    }
    </script>
</body>
</html>
