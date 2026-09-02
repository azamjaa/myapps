<?php
require 'db.php';
include 'header.php';

$msg = "";

// Proses Tukar
if (isset($_POST['change_pass'])) {
    verifyCsrfToken(); // CSRF Protection
    
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $id_user = $_SESSION['user_id'] ?? null;

    if (!$id_user) {
        $msg = "<div class='alert alert-danger'>Ralat: Session tidak sah. Sila login semula.</div>";
    } else {
        // 1. Ambil Password Lama dari DB
        $stmt = $db->prepare("SELECT password_hash FROM login WHERE id_user = ?");
        $stmt->execute([$id_user]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $msg = "<div class='alert alert-danger'>Ralat: Akaun tidak ditemui.</div>";
        } else {
            $db_hash = trim($user['password_hash'], "[]");

            // 2. Validasi
            // A. Check Password Lama
            if (!password_verify($current_pass, $db_hash)) {
                $msg = "<div class='alert alert-danger'>Kata laluan semasa salah.</div>";
            }
            // B. Check Kekuatan Password Baru
            elseif (strlen($new_pass) < 12) {
                $msg = "<div class='alert alert-warning'>Kata laluan baru mesti sekurang-kurangnya 12 aksara.</div>";
            }
            elseif (!preg_match('/[\W_]/', $new_pass)) {
                $msg = "<div class='alert alert-warning'>Kata laluan baru mesti mengandungi simbol (!@#$).</div>";
            }
            // C. Check Sama dengan Lama
            elseif ($current_pass === $new_pass) {
                $msg = "<div class='alert alert-warning'>Kata laluan baru tidak boleh sama dengan yang lama.</div>";
            }
            // D. Check Pengesahan
            elseif ($new_pass !== $confirm_pass) {
                $msg = "<div class='alert alert-danger'>Pengesahan kata laluan tidak sepadan.</div>";
            }
            // E. Lulus & Simpan
            else {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                // Update password & reset tarikh luput 90 hari
                $update = $db->prepare("UPDATE login SET password_hash = ?, tarikh_tukar_katalaluan = NOW() WHERE id_user = ?");
                
                if ($update->execute([$new_hash, $id_user])) {
                    echo "<script>alert('Kata laluan berjaya ditukar! Sila log masuk semula.'); window.location='logout.php';</script>";
                    exit();
                } else {
                    $msg = "<div class='alert alert-danger'>Ralat sistem. Sila cuba lagi.</div>";
                }
            }
        }
    }
}
?>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-lock me-3 text-primary"></i>Tukar Kata Laluan</h3>
                </div>
                <div class="card-body p-4">
                    
                    <?php echo $msg; ?>

                    <div class="alert alert-light border small mb-3">
                        <i class="fas fa-shield-alt text-primary me-1"></i> 
                        Syarat: Minima <b>12 aksara</b> dan wajib ada <b>simbol</b>.
                    </div>

                    <form method="POST">
                        <?php echo getCsrfTokenField(); // CSRF Protection ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Kata Laluan Semasa</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="pass0" class="form-control border-end-0" required>
                                <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePass('pass0', 'icon0')">
                                    <i class="fas fa-eye text-muted" id="icon0"></i>
                                </span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Kata Laluan Baru</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="pass1" class="form-control border-end-0" required minlength="12">
                                <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePass('pass1', 'icon1')">
                                    <i class="fas fa-eye text-muted" id="icon1"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Sahkan Kata Laluan Baru</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="pass2" class="form-control border-end-0" required>
                                <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePass('pass2', 'icon2')">
                                    <i class="fas fa-eye text-muted" id="icon2"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="change_pass" class="btn btn-primary fw-bold">Simpan Kata Laluan</button>
                            <a href="dashboard_perjawatan.php" class="btn btn-light text-muted">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, iconId) {
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
