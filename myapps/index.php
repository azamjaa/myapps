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
        $stmt = $db->prepare("SELECT s.id_staf, s.nama, s.emel, s.gambar, l.password_hash, l.tarikh_tukar_katalaluan
                               FROM staf s 
                               JOIN login l ON s.id_staf = l.id_staf 
                               WHERE s.no_kp = ? AND s.id_status = 1");
        $stmt->execute([$no_kp]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $db_hash = $user['password_hash'];
            // Use password_verify directly - no bracket trimming needed
            
            if (password_verify($password, $db_hash)) {
                if ($password === '123456') {
                    $_SESSION['temp_id'] = $user['id_staf'];
                    header("Location: tukar_katalaluan_wajib.php");
                    exit();
                }

                // 2. AUTO EXPIRED (90 DAYS)
                $tarikh_last = new DateTime($user['tarikh_tukar_katalaluan']);
                $tarikh_kini = new DateTime();
                if ($tarikh_last->diff($tarikh_kini)->days > 90) {
                    $_SESSION['temp_id'] = $user['id_staf'];
                    header("Location: tukar_katalaluan_wajib.php");
                    exit();
                }

                // 3. LOGIN SUCCESS
                $_SESSION['user_id'] = $user['id_staf'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['gambar'] = $user['gambar'];
                
                // Check if user is admin
                $adminCheck = $db->prepare("SELECT COUNT(*) as admin_count FROM akses WHERE id_staf = ? AND id_level = 3");
                $adminCheck->execute([$user['id_staf']]);
                $isAdmin = $adminCheck->fetch()['admin_count'] > 0;
                $_SESSION['role'] = $isAdmin ? 'admin' : 'user';
                
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
                                'user_id' => $user['id_staf'],
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

                // Secure Redirect
                $allowed_redirects = [
                    'dashboard_aplikasi.php', 
                    'dashboard_staf.php', 
                    'direktori_staf.php', 
                    'direktori_aplikasi.php',
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
                    log_audit('LOGIN_SUCCESS', 'staf', $user['id_staf'], null, $user['nama']);
                }
                
                header("Location: " . $redirect_to);
                exit();

            } else {
                $error = 'Kombinasi No. Kad Pengenalan dan Kata Laluan tidak sah.';
                if ($hasAudit && function_exists('log_audit')) {
                    log_audit('LOGIN_FAILED', 'staf', null, null, 'Wrong password: ' . $no_kp);
                }
            }
        } else {
            $error = 'Akaun tidak ditemui atau tidak aktif.';
            if ($hasAudit && function_exists('log_audit')) {
                log_audit('LOGIN_FAILED', 'staf', null, null, 'User not found: ' . $no_kp);
            }
        }
    }
}

// Check if session expired
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = 'Sesi tamat tempoh. Sila log masuk semula.';
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
    
    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" href="image/keda.png">
    <link rel="apple-touch-icon" href="image/keda.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-card {
            animation: fadeInUp 0.8s ease-out;
        }
        .logo-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-900 via-red-800 to-gray-900 relative overflow-hidden">
    
    <!-- Animated Background -->
    <div class="absolute inset-0 overflow-hidden">
        <img src="image/background.jpg" class="w-full h-full object-cover opacity-100" alt="Background">
    </div>
    
    <!-- Floating Circles -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-red-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
    <div class="absolute bottom-20 right-10 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
    
    <!-- Language Switcher removed - default Bahasa Melayu only -->
    
    <!-- Login Card -->
    <div class="login-card relative z-10 w-full max-w-md mx-4">
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10 border-t-4 border-red-600">
            
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="logo-float inline-block">
                    <img src="image/keda.png" alt="Logo KEDA" class="w-24 h-24 mx-auto mb-4 drop-shadow-lg">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">MyApps</h1>
                <p class="text-sm text-gray-600">Direktori Aplikasi KEDA</p>
            </div>

            <!-- Error Message -->
            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                        <p class="text-sm"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <p class="text-sm"><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-5">
                
                <!-- IC Number -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <?php echo $t['username'] ?? 'NO. KAD PENGENALAN'; ?> 
                        <span class="text-red-600 text-xs">(TANPA "-")</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-id-card text-gray-400"></i>
                        </div>
                        <input type="text" 
                               name="no_kp" 
                               class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-red-500 focus:ring focus:ring-red-200 transition-all outline-none" 
                               required 
                               placeholder="Contoh: 900101011234"
                               autocomplete="username">
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        KATA LALUAN
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" 
                               name="password" 
                               id="passLogin" 
                               class="w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl focus:border-red-500 focus:ring focus:ring-red-200 transition-all outline-none" 
                               required 
                               placeholder="Kata Laluan"
                               autocomplete="current-password">
                        <button type="button" 
                                onclick="togglePassword('passLogin', 'iconLogin')" 
                                class="absolute inset-y-0 right-0 pr-4 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="iconLogin"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        name="login" 
                        class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    LOG MASUK
                </button>
                
                <!-- Forgot Password -->
                <div class="text-center pt-4 border-t border-gray-200">
                    <a href="lupa_katalaluan.php" 
                       class="text-sm text-gray-600 hover:text-red-600 transition-colors inline-flex items-center">
                        <i class="fas fa-key mr-2"></i>
                        Lupa Kata Laluan?
                    </a>
                </div>
            </form>
            
            <!-- Footer -->
            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    &copy; <?php echo date('Y'); ?> Lembaga Kemajuan Wilayah Kedah (KEDA)
                </p>
            </div>
        </div>
        
        <!-- PWA Install Hint -->
        <div class="text-center mt-6">
            <button id="pwa-install-hint" 
                    onclick="installPWA()" 
                    class="hidden bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-xl hover:bg-white/30 transition-all">
                <i class="fas fa-download mr-2"></i>
                Pasang Aplikasi
            </button>
        </div>
    </div>

    <!-- PWA Installer Script -->
    <?php if (file_exists('pwa-installer.js')): ?>
    <script src="pwa-installer.js"></script>
    <?php endif; ?>

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
