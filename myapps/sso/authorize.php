
<?php
// sso/authorize.php
// 1. SETUP DATABASE & SESSION
session_start();

// Cari fail db.php
if (file_exists('../db.php')) {
    require_once '../db.php';
} elseif (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    die("Ralat Kritikal: Fail sambungan database (db.php) tidak dijumpai.");
}

// Standardisasi variable connection kepada $pdo
if (isset($db)) {
    $pdo = $db;
} elseif (isset($conn) && $conn instanceof PDO) {
    $pdo = $conn;
}
if (!isset($pdo)) {
    die("Ralat Database: Pembolehubah sambungan PDO tidak ditemui.");
}

// Helper: HTML escape
function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
function generateAuthCode($length = 32) { return bin2hex(random_bytes($length / 2)); }

// 2. TERIMA PARAMETER OAUTH2
$client_id    = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$response_type = $_GET['response_type'] ?? '';
$state        = $_GET['state'] ?? '';
$error_msg    = '';

// 3. VALIDASI APLIKASI (CLIENT)
if (empty($client_id) || empty($redirect_uri)) {
    $error_msg = "Ralat: Parameter client_id atau redirect_uri hilang.";
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM aplikasi WHERE app_key = ? AND status = 1 LIMIT 1");
        $stmt->execute([$client_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$app) {
            $error_msg = "Ralat: Aplikasi tidak sah atau tidak aktif.";
        }
        // Optional: Validasi redirect_uri
        // if (isset($app['redirect_uri']) && trim($app['redirect_uri']) !== trim($redirect_uri)) {
        //     $error_msg = "Redirect URI tidak sepadan.";
        // }
    } catch (PDOException $e) {
        $error_msg = "Ralat Sistem: " . $e->getMessage();
    }
}

// 4. PROSES POST (LOGIN SUBMISSION)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_msg)) {
    $no_kp = trim($_POST['no_kp'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($no_kp) || empty($password)) {
        $error_msg = "Sila isi No. Kad Pengenalan dan Kata Laluan.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_user FROM users WHERE no_kp = ? LIMIT 1");
            $stmt->execute([$no_kp]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $stmtLogin = $pdo->prepare("SELECT password_hash FROM login WHERE id_user = ? LIMIT 1");
                $stmtLogin->execute([$user['id_user']]);
                $loginData = $stmtLogin->fetch(PDO::FETCH_ASSOC);
                if ($loginData && password_verify($password, $loginData['password_hash'])) {
                    // LOGIN BERJAYA: Jana Auth Code
                    $auth_code = generateAuthCode(32);
                    $expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $stmtInsert = $pdo->prepare("INSERT INTO oauth_codes (client_id, user_id, authorization_code, redirect_uri, expires_at) VALUES (?, ?, ?, ?, ?)");
                    $stmtInsert->execute([$client_id, $user['id_user'], $auth_code, $redirect_uri, $expire]);
                    // Redirect
                    $redirect = $redirect_uri . '?code=' . urlencode($auth_code) . '&state=' . urlencode($state);
                    header('Location: ' . $redirect);
                    exit;
                }
            }
            $error_msg = "No. KP atau Kata Laluan salah.";
        } catch (PDOException $e) {
            $error_msg = "Ralat Sistem: " . $e->getMessage();
        }
    }
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
    <title>Log Masuk SSO - MyApps KEDA</title>
    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" href="../image/keda.png">
    <link rel="apple-touch-icon" href="../image/keda.png">
    <!-- PWA Manifest -->
    <link rel="manifest" href="../manifest.json">
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
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-900 via-red-800 to-gray-900 relative overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0 w-full h-full overflow-hidden -z-10">
        <img src="../image/background.jpg" class="w-full h-full object-cover opacity-100" alt="Background">
        <!-- Floating Circles -->
        <div class="absolute top-20 left-10 w-72 h-72 bg-red-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute bottom-20 right-10 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
    </div>
    <!-- Login Card -->
    <div class="login-card relative z-10 w-full max-w-md mx-4">
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10 border-t-4 border-red-600">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="logo-float inline-block">
                    <img src="../image/keda.png" alt="Logo KEDA" class="w-24 h-24 mx-auto mb-4 drop-shadow-lg">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">MyApps SSO</h1>
                <p class="text-sm text-gray-600">Direktori Aplikasi KEDA</p>
            </div>
            <!-- Error Message -->
            <?php if($error_msg): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                        <p class="text-sm"><?= h($error_msg) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5">
                <!-- IC Number -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        NO. KAD PENGENALAN <span class="text-red-600 text-xs">(TANPA "-")</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-id-card text-gray-400"></i>
                        </div>
                        <input type="text" name="no_kp" class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-red-500 focus:ring focus:ring-red-200 transition-all outline-none" required placeholder="Contoh: 900101011234" autocomplete="username" value="<?= h($_POST['no_kp'] ?? '') ?>">
                    </div>
                </div>
                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">KATA LALUAN</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" id="passLogin" class="w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl focus:border-red-500 focus:ring focus:ring-red-200 transition-all outline-none" required placeholder="Kata Laluan" autocomplete="current-password">
                        <button type="button" onclick="togglePassword('passLogin', 'iconLogin')" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="iconLogin"></i>
                        </button>
                    </div>
                </div>
                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    LOG MASUK
                </button>
            </form>
            <!-- Footer -->
            <div class="text-center mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500">&copy; <?= date('Y'); ?> Lembaga Kemajuan Wilayah Kedah (KEDA)</p>
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
    </script>
</body>
</html>