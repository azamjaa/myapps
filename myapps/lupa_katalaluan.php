<?php
require 'db.php';
require 'fungsi_emel.php'; 

if (isset($_POST['reset_request'])) {
    verifyCsrfToken(); // CSRF Protection
    
    $no_kp = $_POST['no_kp'];
    $emel = $_POST['emel'];

    // 1. Cari Staf
    $stmt = $db->prepare("SELECT id_staf, nama FROM staf WHERE no_kp = ? AND emel = ? AND id_status = 1");
    $stmt->execute([$no_kp, $emel]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Jana Token
        $token = bin2hex(random_bytes(32));
        $id_staf = $user['id_staf'];

        // 3. Simpan Token (Valid 24 jam)
        $update = $db->prepare("UPDATE login SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id_staf = ?");
        $update->execute([$token, $id_staf]);

        // 4. Hantar Emel Guna SMTP
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $link = $protocol . "://" . $host . "/myapps/reset_password.php?token=" . $token;
        
        // (Nota: Pastikan link di atas 'myapps' jika folder dah tukar nama, atau 'mystaf' jika belum)
        
        $subjek = "Tetapan Semula Kata Laluan - ID Pengguna KEDA";
        $mesej  = "<div style='font-family: Helvetica, Arial, sans-serif; padding: 20px; color: #333;'>";
        $mesej .= "<h2 style='color: #d32f2f;'>Portal Aplikasi KEDA</h2>";
        $mesej .= "<p>Salam Sejahtera <b>{$user['nama']}</b>,</p>";
        $mesej .= "<p>Kami menerima permintaan untuk menetapkan semula kata laluan bagi <b>Akaun Tunggal KEDA</b> anda.</p>";
        $mesej .= "<p>Sila klik pautan di bawah untuk cipta kata laluan baru:</p>";
        $mesej .= "<p><a href='$link' style='background-color:#d32f2f; color:white; padding:12px 25px; text-decoration:none; border-radius:4px; font-weight:bold; display:inline-block;'>Tetapkan Semula Kata Laluan</a></p>";
        $mesej .= "<p>Atau salin pautan ini: <br><a href='$link'>$link</a></p>";
        $mesej .= "<p><small>Pautan ini sah selama 24 jam.</small></p>";
        $mesej .= "</div>";

        if (hantarEmel($emel, $subjek, $mesej)) {
            $success = "Pautan reset telah dihantar ke emel <b>$emel</b>. Sila semak Inbox atau Spam folder.";
        } else {
            $error = "Gagal menghantar emel. Sila cuba lagi atau hubungi admin.";
        }

    } else {
        $error = "Maklumat No. KP atau Emel tidak ditemui dalam sistem.";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Lupa Kata Laluan - MyApps KEDA</title>
    
    <link rel="icon" type="image/png" href="image/keda.png?v=2">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            /* BACKGROUND DIKEMASKINI (Sama dengan index.php) */
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
            
            <h3 class="fw-bold text-dark">Lupa Kata Laluan?</h3>
            <p class="text-muted small">Masukkan maklumat anda untuk reset</p>
        </div>

        <?php if(isset($error)) { echo "<div class='alert alert-danger text-center small py-2'>$error</div>"; } ?>
        <?php if(isset($success)) { echo "<div class='alert alert-success text-center small py-2'>$success</div>"; } ?>

        <form method="POST">
            <?php echo getCsrfTokenField(); // CSRF Protection ?>
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">NO. KAD PENGENALAN <span class="text-danger">(TANPA "-")</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-id-card text-muted"></i></span>
                    <input type="text" name="no_kp" class="form-control border-start-0" required placeholder="No Kad Pengenalan">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">EMEL BERDAFTAR</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="emel" class="form-control border-start-0" required placeholder="Emel Berdaftar">
                </div>
            </div>
            
            <button type="submit" name="reset_request" class="btn btn-keda w-100 py-2 fw-bold shadow-sm">HANTAR PAUTAN RESET</button>
            
            <div class="text-center mt-4 pt-3 border-top">
                <a href="index.php" class="text-decoration-none text-muted small hover-primary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
                </a>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted" style="font-size: 10px;">&copy; <?php echo date('Y'); ?> Lembaga Kemajuan Wilayah Kedah</small>
        </div>
    </div>
</body>
</html>
