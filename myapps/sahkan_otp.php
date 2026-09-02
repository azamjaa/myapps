<?php
require 'db.php';

// Halang akses jika tiada proses login berlaku
if (!isset($_SESSION['temp_id'])) {
    header("Location: index.php");
    exit();
}

$msg = "";

if (isset($_POST['verify_otp'])) {
    verifyCsrfToken(); // CSRF Protection
    
    $otp_input = $_POST['otp'];
    $id_user = $_SESSION['temp_id'];

    // Semak OTP Valid & Belum Expired
    $stmt = $db->prepare("SELECT * FROM login WHERE id_user = ? AND otp_code = ? AND otp_expiry > NOW()");
    $stmt->execute([$id_user, $otp_input]);
    $check = $stmt->fetch();

    if ($check) {
        // OTP Betul! Pindahkan session sementara ke session sebenar
        $_SESSION['user_id'] = $id_user;
        $_SESSION['nama'] = $_SESSION['temp_nama'];
        $_SESSION['role'] = $_SESSION['temp_role'];
        
        // Padam OTP dari database (supaya tak boleh guna semula)
        $db->prepare("UPDATE login SET otp_code = NULL, otp_expiry = NULL WHERE id_user = ?")->execute([$id_user]);

        // Buang session sementara
        unset($_SESSION['temp_id']);
        unset($_SESSION['temp_nama']);
        unset($_SESSION['temp_role']);
        
        // Masuk ke Dashboard
        header("Location: dashboard_perjawatan.php");
        exit();
    } else {
        $msg = "<div class='alert alert-danger text-center small'>Kod OTP salah atau telah tamat tempoh.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Pengesahan OTP - MyApps KEDA</title>
    
    <link rel="icon" type="image/png" href="image/keda.png?v=2">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card-otp { 
            width: 400px; 
            border: none; 
            border-top: 5px solid #d32f2f; /* Merah KEDA */
            border-radius: 10px;
        }
        .btn-keda { background-color: #d32f2f; color: white; }
        .btn-keda:hover { background-color: #b71c1c; color: white; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    
    <div class="card card-otp shadow p-4">
        <div class="text-center mb-4">
            <img src="image/keda.png" width="80" class="mb-3">
            <h4 class="fw-bold text-dark">Pengesahan Dua Faktor</h4>
            <p class="text-muted small">Sila masukkan 6-digit kod OTP yang telah dihantar ke emel rasmi anda.</p>
        </div>
        
        <?php echo $msg; ?>

        <form method="POST">
            <?php echo getCsrfTokenField(); // CSRF Protection ?>
            <div class="mb-4">
                <input type="text" name="otp" class="form-control form-control-lg text-center fw-bold fs-2" maxlength="6" placeholder="000000" required autofocus style="letter-spacing: 5px;">
            </div>
            <button type="submit" name="verify_otp" class="btn btn-keda w-100 py-2 fw-bold shadow-sm">SAHKAN KOD</button>
        </form>
        
        <div class="mt-4 text-center border-top pt-3">
            <a href="index.php" class="text-decoration-none small text-muted hover-primary">
                <i class="fas fa-arrow-left me-1"></i> Batal & Kembali
            </a>
        </div>
    </div>

</body>
</html>
