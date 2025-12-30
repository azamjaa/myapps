<?php
require 'db.php';
require 'fungsi_emel.php'; // Wajib ada untuk hantar OTP

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$validToken = false;
$userInfo = null;

// 1. Semak Token & Tarik Data User (Nama, Emel)
if ($token) {
    $sql = "SELECT s.id_staf, s.nama, s.emel, l.password_hash
            FROM login l
            JOIN staf s ON l.id_staf = s.id_staf
            WHERE l.reset_token = ? AND l.reset_token_expiry > NOW()";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$token]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userInfo) {
        $validToken = true;
        $id_staf = $userInfo['id_staf'];
        $old_hash = trim($userInfo['password_hash'], "[]");
    }
}

// 2. Proses Tukar Password & Auto Trigger OTP
if (isset($_POST['change_password']) && $validToken) {
    verifyCsrfToken(); // CSRF Protection
    
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validasi
    if (strlen($new_pass) < 12) {
        $error = "Kata laluan mesti sekurang-kurangnya 12 aksara.";
    } elseif (!preg_match('/[\W_]/', $new_pass)) {
        $error = "Kata laluan mesti mengandungi simbol.";
    } elseif (password_verify($new_pass, $old_hash)) {
        $error = "Anda tidak boleh menggunakan semula kata laluan lama.";
    } elseif ($new_pass === $confirm_pass) {
        
        // A. Update Password Baru & Matikan Token
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE login SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL, tarikh_tukar_katalaluan = NOW() WHERE id_staf = ?");
        $update->execute([$hashed_password, $id_staf]);

        // B. JANA OTP TERUS (Tak perlu login balik)
        $otp = rand(100000, 999999);
        $db->prepare("UPDATE login SET otp_code = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id_staf = ?")->execute([$otp, $id_staf]);

        // C. Hantar Emel OTP
        $subjek = "Kod OTP - MyApps KEDA";
        $mesej  = "<h3>Hai {$userInfo['nama']},</h3>";
        $mesej .= "<p>Kata laluan anda berjaya ditukar.</p>";
        $mesej .= "<p>Untuk melengkapkan log masuk, kod OTP anda ialah:</p>";
        $mesej .= "<h1 style='color:#d32f2f; letter-spacing:5px;'>$otp</h1>";

        if (hantarEmel($userInfo['emel'], $subjek, $mesej)) {
            // D. Set Session Sementara & Redirect ke Sahkan OTP
            $_SESSION['temp_id'] = $id_staf;
            $_SESSION['temp_nama'] = $userInfo['nama'];
            
            echo "<script>alert('Kata laluan berjaya ditukar! Sila masukkan kod OTP yang dihantar ke emel.'); window.location='sahkan_otp.php';</script>";
            exit();
        } else {
            $error = "Kata laluan ditukar tetapi gagal hantar OTP. Sila Log Masuk manual.";
        }

    } else {
        $error = "Kata laluan tidak sepadan.";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Cipta Kata Laluan Baru</title>
    <link rel="icon" type="image/png" href="image/keda.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #e9ecef; }
        .card-login { max-width: 450px; margin: 80px auto; padding: 30px; border-radius: 10px; border-top: 5px solid #d32f2f; }
        .btn-keda { background-color: #d32f2f; color: white; }
        .btn-keda:hover { background-color: #b71c1c; color: white; }
    </style>
</head>
<body>
    <div class="card card-login shadow bg-white">
        <div class="text-center mb-4">
            <img src="image/keda.png" width="80" class="mb-3">
            <h4 class="text-dark fw-bold">Kata Laluan Baru</h4>
            <div class="alert alert-info small py-2 mt-2 text-start">
                Syarat Keselamatan:
                <ul class="mb-0 ps-3">
                    <li>Minima <b>12 aksara</b></li>
                    <li>Wajib ada <b>Simbol</b> (!@#$)</li>
                    <li>Bukan kata laluan lama</li>
                </ul>
            </div>
        </div>

        <?php if (!$validToken): ?>
            <div class="alert alert-danger text-center">
                Pautan reset tidak sah atau telah tamat tempoh.<br>
                <a href="lupa_katalaluan.php" class="fw-bold">Minta pautan baru</a>
            </div>
        <?php else: ?>
            
            <?php if(isset($error)) { echo "<div class='alert alert-danger small'>$error</div>"; } ?>

            <form method="POST">
                <?php echo getCsrfTokenField(); // CSRF Protection ?>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Kata Laluan Baru</label>
                    <div class="input-group">
                        <input type="password" name="password" id="newPass" class="form-control border-end-0" required minlength="12" placeholder="Minima 12 aksara">
                        <span class="input-group-text bg-white border-start-0" style="cursor: pointer;" onclick="togglePassword('newPass', 'icon1')">
                            <i class="fas fa-eye text-muted" id="icon1"></i>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Sahkan Kata Laluan</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPass" class="form-control border-end-0" required placeholder="Ulang kata laluan">
                        <span class="input-group-text bg-white border-start-0" style="cursor: pointer;" onclick="togglePassword('confirmPass', 'icon2')">
                            <i class="fas fa-eye text-muted" id="icon2"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-keda w-100 fw-bold py-2">TUKAR KATA LALUAN</button>
            </form>

            <script>
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

        <?php endif; ?>
    </div>
</body>
</html>
