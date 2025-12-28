<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];
$id_booking = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get booking
if ($id_booking <= 0) {
    $error = 'ID tempahan tidak sah';
} else {
    $booking = $db->prepare("SELECT b.*, s.nama as nama_pengurus, bi.nama_bilik, bi.kapasiti
                            FROM booking b
                            JOIN staf s ON b.id_staf = s.id_staf
                            JOIN bilik bi ON b.id_bilik = bi.id_bilik
                            WHERE b.id_booking = ?")->execute([$id_booking])->fetch();
    
    if (!$booking) {
        $error = 'Tempahan tidak dijumpai';
    } else if ($booking['id_staf'] != $my_id && !canApprove($my_id)) {
        $error = 'Anda tidak mempunyai kebenaran untuk mengedit tempahan ini';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    // Only allow edit if pending (status = 0) or if user is approver
    if ($booking['status'] != 0 && !canApprove($my_id)) {
        $error = 'Hanya tempahan yang pending boleh diubah. Hantar permintaan pembatalan jika perlu.';
    } else {
        $tarikh_mula = $_POST['tarikh_mula'] ?? '';
        $masa_mula = $_POST['masa_mula'] ?? '';
        $masa_tamat = $_POST['masa_tamat'] ?? '';
        $tujuan = $_POST['tujuan'] ?? '';
        $bilangan_peserta = intval($_POST['bilangan_peserta'] ?? 0);

        // Validation
        if (!$tarikh_mula) $error = 'Sila masukkan tarikh';
        else if (!$masa_mula) $error = 'Sila masukkan masa mula';
        else if (!$masa_tamat) $error = 'Sila masukkan masa tamat';
        else if (strtotime($masa_mula) >= strtotime($masa_tamat)) $error = 'Masa tamat mesti lebih besar dari masa mula';
        else if (!$tujuan) $error = 'Sila masukkan tujuan';
        else if ($bilangan_peserta <= 0) $error = 'Bilangan peserta mesti lebih besar dari 0';
        else if ($bilangan_peserta > $booking['kapasiti']) {
            $error = "Kapasiti bilik hanya " . $booking['kapasiti'] . " peserta";
        } else if (!isRoomAvailable($booking['id_bilik'], $tarikh_mula, $masa_mula, $masa_tamat, $id_booking)) {
            $error = "Bilik tidak tersedia pada tarikh dan masa tersebut";
        } else {
            try {
                $stmt = $db->prepare("UPDATE booking SET tarikh_mula = ?, masa_mula = ?, masa_tamat = ?, tujuan = ?, bilangan_peserta = ?, tarikh_kemaskini = NOW() 
                                      WHERE id_booking = ?");
                $stmt->execute([$tarikh_mula, $masa_mula, $masa_tamat, $tujuan, $bilangan_peserta, $id_booking]);
                
                $success = 'Tempahan berjaya dikemaskini';
                // Refresh booking data
                $booking = $db->prepare("SELECT b.*, s.nama as nama_pengurus, bi.nama_bilik, bi.kapasiti
                                        FROM booking b
                                        JOIN staf s ON b.id_staf = s.id_staf
                                        JOIN bilik bi ON b.id_bilik = bi.id_bilik
                                        WHERE b.id_booking = ?")->execute([$id_booking])->fetch();
            } catch (Exception $e) {
                $error = 'Ralat: ' . $e->getMessage();
            }
        }
    }
}

if (!$booking) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . $error . '</div>';
    echo '</div></body></html>';
    exit;
}
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-edit me-3 text-warning"></i> Edit Tempahan
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
                    <div class="col-md-3">
                        <small class="text-muted d-block">Bilik</small>
                        <strong><?php echo $booking['nama_bilik']; ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Pengurus</small>
                        <strong><?php echo $booking['nama_pengurus']; ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-<?php echo getStatusColor($booking['status']); ?> fs-6">
                            <?php echo getStatusText($booking['status']); ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Tarikh Tempahan</small>
                        <strong><?php echo formatDateTime($booking['tarikh_kemaskini']); ?></strong>
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
                <h6 class="mb-0 fw-bold">Maklumat Tempahan</h6>
            </div>
            <div class="card-body">
                <!-- DATE & TIME -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tarikh <span class="text-danger">*</span></label>
                        <input type="date" name="tarikh_mula" class="form-control" 
                               value="<?php echo $booking['tarikh_mula']; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Masa Mula <span class="text-danger">*</span></label>
                        <input type="time" name="masa_mula" class="form-control" 
                               value="<?php echo $booking['masa_mula']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Masa Tamat <span class="text-danger">*</span></label>
                        <input type="time" name="masa_tamat" class="form-control" 
                               value="<?php echo $booking['masa_tamat']; ?>" required>
                    </div>
                </div>

                <!-- PURPOSE & PARTICIPANTS -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Tujuan Tempahan <span class="text-danger">*</span></label>
                        <textarea name="tujuan" class="form-control" rows="2" required><?php echo htmlspecialchars($booking['tujuan']); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Bilangan Peserta <span class="text-danger">*</span></label>
                        <input type="number" name="bilangan_peserta" class="form-control" 
                               value="<?php echo $booking['bilangan_peserta']; ?>" min="1" required>
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
        <a href="booking_list.php" class="btn btn-secondary btn-lg w-100 mt-2">
            <i class="fas fa-times-circle me-2"></i> Batal
        </a>
    </div>
</form>

</div><!-- END MAIN CONTENT -->
</body>
</html>
