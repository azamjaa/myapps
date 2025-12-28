<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];
$rooms = $db->query("SELECT * FROM bilik WHERE status = 1 ORDER BY nama_bilik")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_bilik = intval($_POST['id_bilik'] ?? 0);
    $tarikh_mula = $_POST['tarikh_mula'] ?? '';
    $masa_mula = $_POST['masa_mula'] ?? '';
    $masa_tamat = $_POST['masa_tamat'] ?? '';
    $tujuan = $_POST['tujuan'] ?? '';
    $bilangan_peserta = intval($_POST['bilangan_peserta'] ?? 0);

    // Validation
    if (!$id_bilik) $error = 'Sila pilih bilik';
    else if (!$tarikh_mula) $error = 'Sila masukkan tarikh';
    else if (!$masa_mula) $error = 'Sila masukkan masa mula';
    else if (!$masa_tamat) $error = 'Sila masukkan masa tamat';
    else if (strtotime($masa_mula) >= strtotime($masa_tamat)) $error = 'Masa tamat mesti lebih besar dari masa mula';
    else if (!$tujuan) $error = 'Sila masukkan tujuan';
    else if ($bilangan_peserta <= 0) $error = 'Bilangan peserta mesti lebih besar dari 0';
    else {
        // Check capacity
        $room = $db->prepare("SELECT kapasiti FROM bilik WHERE id_bilik = ?")->execute([$id_bilik])->fetch();
        if ($bilangan_peserta > $room['kapasiti']) {
            $error = "Kapasiti bilik hanya " . $room['kapasiti'] . " peserta";
        } else if (!isRoomAvailable($id_bilik, $tarikh_mula, $masa_mula, $masa_tamat)) {
            $error = "Bilik tidak tersedia pada tarikh dan masa tersebut";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO booking (id_bilik, id_staf, tarikh_mula, masa_mula, masa_tamat, tujuan, bilangan_peserta, status, tarikh_tempahan) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$id_bilik, $my_id, $tarikh_mula, $masa_mula, $masa_tamat, $tujuan, $bilangan_peserta]);
                
                $success = 'Tempahan berjaya ditambah. Menunggu kelulusan daripada pengurus.';
                $_POST = [];
            } catch (Exception $e) {
                $error = 'Ralat: ' . $e->getMessage();
            }
        }
    }
}

// Get room details for modal
$roomsJson = json_encode($rooms);
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-plus-circle me-3 text-success"></i> Tempahan Bilik Baru
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
                <h6 class="mb-0 fw-bold">Maklumat Tempahan</h6>
            </div>
            <div class="card-body">
                <!-- ROOM SELECTION -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Bilik <span class="text-danger">*</span></label>
                        <select name="id_bilik" class="form-select" id="roomSelect" required>
                            <option value="">-- Pilih Bilik --</option>
                            <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id_bilik']; ?>" 
                                    data-kapasiti="<?php echo $room['kapasiti']; ?>"
                                    data-kemudahan="<?php echo htmlspecialchars($room['kemudahan']); ?>">
                                <?php echo $room['nama_bilik']; ?> (Kapasiti: <?php echo $room['kapasiti']; ?> peserta)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kemudahan</label>
                        <input type="text" class="form-control" id="kemudahan" readonly>
                    </div>
                </div>

                <!-- DATE & TIME -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tarikh <span class="text-danger">*</span></label>
                        <input type="date" name="tarikh_mula" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Masa Mula <span class="text-danger">*</span></label>
                        <input type="time" name="masa_mula" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Masa Tamat <span class="text-danger">*</span></label>
                        <input type="time" name="masa_tamat" class="form-control" required>
                    </div>
                </div>

                <!-- PURPOSE & PARTICIPANTS -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Tujuan Tempahan <span class="text-danger">*</span></label>
                        <textarea name="tujuan" class="form-control" rows="2" placeholder="Contoh: Mesyuarat Pengarah, Sesi Latihan, dll" required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Bilangan Peserta <span class="text-danger">*</span></label>
                        <input type="number" name="bilangan_peserta" class="form-control" min="1" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BUTTONS -->
    <div class="col-12">
        <button type="submit" class="btn btn-success btn-lg w-100">
            <i class="fas fa-check-circle me-2"></i> Hantar Tempahan
        </button>
        <a href="booking_list.php" class="btn btn-secondary btn-lg w-100 mt-2">
            <i class="fas fa-times-circle me-2"></i> Batal
        </a>
    </div>
</form>

<script>
const roomSelect = document.getElementById('roomSelect');
const kemudahanInput = document.getElementById('kemudahan');

roomSelect.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    kemudahanInput.value = selected.getAttribute('data-kemudahan') || '';
});
</script>

</div><!-- END MAIN CONTENT -->
</body>
</html>
