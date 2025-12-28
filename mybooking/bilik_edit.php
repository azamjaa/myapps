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

$id_bilik = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get room
if ($id_bilik <= 0) {
    $error = 'ID bilik tidak sah';
} else {
    $room = $db->prepare("SELECT * FROM bilik WHERE id_bilik = ?")->execute([$id_bilik])->fetch();
    
    if (!$room) {
        $error = 'Bilik tidak dijumpai';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    $nama_bilik = $_POST['nama_bilik'] ?? '';
    $lokasi = $_POST['lokasi'] ?? '';
    $kapasiti = intval($_POST['kapasiti'] ?? 0);
    $kemudahan = $_POST['kemudahan'] ?? '';
    $status = intval($_POST['status'] ?? 1);

    // Validation
    if (!$nama_bilik) $error = 'Sila masukkan nama bilik';
    else if (!$lokasi) $error = 'Sila masukkan lokasi';
    else if ($kapasiti <= 0) $error = 'Kapasiti mesti lebih besar dari 0';
    else {
        try {
            $stmt = $db->prepare("UPDATE bilik SET nama_bilik = ?, lokasi = ?, kapasiti = ?, kemudahan = ?, status = ?, tarikh_kemaskini = NOW() 
                                  WHERE id_bilik = ?");
            $stmt->execute([$nama_bilik, $lokasi, $kapasiti, $kemudahan, $status, $id_bilik]);
            
            $success = 'Bilik berjaya dikemaskini';
            // Refresh room data
            $room = $db->prepare("SELECT * FROM bilik WHERE id_bilik = ?")->execute([$id_bilik])->fetch();
        } catch (Exception $e) {
            $error = 'Ralat: ' . $e->getMessage();
        }
    }
}

if (!$room) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . $error . '</div>';
    echo '</div></body></html>';
    exit;
}

$booking_count = $db->prepare("SELECT COUNT(*) as total FROM booking WHERE id_bilik = ? AND status IN (0, 1)")->execute([$id_bilik])->fetch()['total'];
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-edit me-3 text-warning"></i> Kemaskini Bilik Mesyuarat
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

<div class="row mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Tempahan Aktif</small>
                        <strong class="fs-5"><?php echo $booking_count; ?> tempahan</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Tarikh Daftar</small>
                        <strong><?php echo formatDateTime($room['tarikh_daftar']); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Status Sistem</small>
                        <span class="badge bg-<?php echo $room['status'] == 1 ? 'success' : 'secondary'; ?> fs-6">
                            <?php echo $room['status'] == 1 ? 'Aktif' : 'Tidak Aktif'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                               value="<?php echo htmlspecialchars($room['nama_bilik']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="lokasi" class="form-control" 
                               value="<?php echo htmlspecialchars($room['lokasi']); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kapasiti Peserta <span class="text-danger">*</span></label>
                        <input type="number" name="kapasiti" class="form-control" 
                               value="<?php echo $room['kapasiti']; ?>" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status Bilik <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="1" <?php echo $room['status'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                            <option value="0" <?php echo $room['status'] == 0 ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Kemudahan / Peralatan</label>
                        <textarea name="kemudahan" class="form-control" rows="3"><?php echo htmlspecialchars($room['kemudahan']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="col-12">
        <button type="submit" class="btn btn-warning btn-lg w-100">
            <i class="fas fa-save me-2"></i> Simpan Perubahan
        </button>
        <a href="bilik_list.php" class="btn btn-secondary btn-lg w-100 mt-2">
            <i class="fas fa-times-circle me-2"></i> Batal
        </a>
    </div>
</form>

</div><!-- END MAIN CONTENT -->
</body>
</html>
