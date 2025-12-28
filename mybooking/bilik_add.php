<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];

// Check if user is admin
if (!isAdmin($my_id)) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Anda tidak mempunyai kebenaran untuk mengakses halaman ini</div>';
    echo '</div></body></html>';
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_bilik = $_POST['nama_bilik'] ?? '';
    $lokasi = $_POST['lokasi'] ?? '';
    $kapasiti = intval($_POST['kapasiti'] ?? 0);
    $kemudahan = $_POST['kemudahan'] ?? '';

    // Validation
    if (!$nama_bilik) $error = 'Sila masukkan nama bilik';
    else if (!$lokasi) $error = 'Sila masukkan lokasi';
    else if ($kapasiti <= 0) $error = 'Kapasiti mesti lebih besar dari 0';
    else {
        try {
            $stmt = $db->prepare("INSERT INTO bilik (nama_bilik, lokasi, kapasiti, kemudahan, status, tarikh_daftar) 
                                  VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$nama_bilik, $lokasi, $kapasiti, $kemudahan]);
            
            $success = 'Bilik berjaya ditambah';
            $_POST = [];
        } catch (Exception $e) {
            $error = 'Ralat: ' . $e->getMessage();
        }
    }
}
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-plus-circle me-3 text-success"></i> Bilik Mesyuarat Baru
</h3>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-bold">Maklumat Bilik</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nama Bilik <span class="text-danger">*</span></label>
                        <input type="text" name="nama_bilik" class="form-control" 
                               placeholder="Contoh: Bilik Mesyuarat A" value="<?php echo htmlspecialchars($_POST['nama_bilik'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="lokasi" class="form-control" 
                               placeholder="Contoh: Bangunan A, Tingkat 3" value="<?php echo htmlspecialchars($_POST['lokasi'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kapasiti Peserta <span class="text-danger">*</span></label>
                        <input type="number" name="kapasiti" class="form-control" 
                               placeholder="Contoh: 20" min="1" value="<?php echo $_POST['kapasiti'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Kemudahan / Peralatan</label>
                        <textarea name="kemudahan" class="form-control" rows="3" 
                                  placeholder="Contoh: Projector, Whiteboard, TV, Air-cond"><?php echo htmlspecialchars($_POST['kemudahan'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="col-12">
        <button type="submit" class="btn btn-success btn-lg w-100">
            <i class="fas fa-check-circle me-2"></i> Simpan Bilik
        </button>
        <a href="bilik_list.php" class="btn btn-secondary btn-lg w-100 mt-2">
            <i class="fas fa-times-circle me-2"></i> Batal
        </a>
    </div>
</form>

</div><!-- END MAIN CONTENT -->
</body>
</html>
