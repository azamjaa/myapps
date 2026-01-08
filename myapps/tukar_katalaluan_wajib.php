<?php
require 'db.php';
require 'fungsi_emel.php'; // Wajib ada

if (!isset($_SESSION['temp_id'])) {
    header("Location: index.php");
    exit();
}

// Tarik data user (Nama, Emel, Role) guna temp_id
$stmtInfo = $db->prepare("SELECT u.nama, u.emel, ur.id_role, l.password_hash 
FROM users u
JOIN login l ON u.id_user = l.id_user
LEFT JOIN user_roles ur ON u.id_user = ur.id_user AND ur.id_aplikasi = 1
WHERE u.id_user = ?");
$stmtInfo->execute([$_SESSION['temp_id']]);
$userInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

$msg = "";

if (isset($_POST['change_pass'])) {
    verifyCsrfToken(); // CSRF Protection
    
    $new_pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $old_hash = trim($userInfo['password_hash'], "[]");

    // Validasi
    if (strlen($new_pass) < 12) {
        $msg = "<div class='alert alert-warning'>Kata laluan mesti sekurang-kurangnya <b>12 aksara</b>.</div>";
    } elseif (!preg_match('/[\W_]/', $new_pass)) {
        $msg = "<div class='alert alert-warning'>Kata laluan mesti mengandungi simbol.</div>";
    } elseif ($new_pass === '123456') {
        $msg = "<div class='alert alert-warning'>Anda tidak boleh menggunakan kata laluan standard ini.</div>";
    } elseif (password_verify($new_pass, $old_hash)) {
        $msg = "<div class='alert alert-danger'>Anda tidak dibenarkan menggunakan semula kata laluan lama.</div>";
    } elseif ($new_pass === $confirm) {
        
        // A. Update Password
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE login SET password_hash = ?, tarikh_tukar_katalaluan = NOW() WHERE id_user = ?");
        $stmt->execute([$hash, $_SESSION['temp_id']]);

        // B. JANA OTP TERUS
        $otp = rand(100000, 999999);
        $db->prepare("UPDATE login SET otp_code = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id_user = ?")->execute([$otp, $_SESSION['temp_id']]);

        // C. Hantar Emel OTP
        $subjek = "Kod OTP - MyApps KEDA";
        $mesej = "<h3>Hai {$userInfo['nama']},</h3>";
        $mesej .= "<p>Kata laluan anda berjaya dikemaskini.</p>";
        $mesej .= "<p>Kod OTP anda ialah:</p>";
        $mesej .= "<h1 style='color:#d32f2f; letter-spacing:5px;'>$otp</h1>";

        if (hantarEmel($userInfo['emel'], $subjek, $mesej)) {
            // D. Kemaskini Session Info & Redirect
            $_SESSION['temp_nama'] = $userInfo['nama'];
            $_SESSION['temp_role'] = ($userInfo['id_role'] == 1) ? 'admin' : 'user';
            
            echo "<script>alert('Kata laluan berjaya dikemaskini! Sila masukkan kod OTP.'); window.location='sahkan_otp.php';</script>";
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Gagal menghantar OTP. Sila hubungi IT.</div>";
        }

    } else {
        $msg = "<div class='alert alert-danger'>Kata laluan tidak sepadan.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <title>Kemaskini Keselamatan - MyApps KEDA</title>
    <link rel="icon" type="image/png" href="image/keda.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow border-0 p-4" style="width: 100%; border-top: 5px solid #d32f2f;">
        <div class="text-center mb-3">
            <img src="image/keda.png" width="60" class="mb-2">
            <h4 class="mb-1 text-danger fw-bold">Polisi Kata Laluan Baru</h4>
            <p class="text-muted small text-center">Demi keselamatan, sila tetapkan kata laluan yang kukuh.</p>
            <div class="alert alert-light border text-start small p-2">
                <ul class="mb-0 ps-3">
                    <li>Minima <b>12 aksara</b> & <b>Simbol</b></li>
                    <li>Tidak boleh sama dengan kata laluan lama</li>
                </ul>
            </div>
        </div>
        
        <?php echo $msg; ?>

        <form method="POST">
            <?php echo getCsrfTokenField(); // CSRF Protection ?>
            <div class="mb-3">
                <label class="form-label fw-bold small">Kata Laluan Baru</label>
                <div class="input-group">
                    <input type="password" name="password" id="pass1" class="form-control border-end-0" required minlength="12" placeholder="Minima 12 aksara & simbol">
                    <span class="input-group-text bg-white border-start-0" style="cursor: pointer;" onclick="togglePassword('pass1', 'icon1')">
                        <i class="fas fa-eye text-muted" id="icon1"></i>
                    </span>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold small">Sahkan Kata Laluan</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="pass2" class="form-control border-end-0" required placeholder="Ulang kata laluan">
                    <span class="input-group-text bg-white border-start-0" style="cursor: pointer;" onclick="togglePassword('pass2', 'icon2')">
                        <i class="fas fa-eye text-muted" id="icon2"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" name="change_pass" class="btn w-100 fw-bold text-white" style="background-color: #d32f2f;">Simpan & Dapatkan OTP</button>
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
    </div>
</body>
</html>
