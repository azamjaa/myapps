<?php
require 'db.php';

// Check if user is admin
$checkAdmin = $db->prepare("SELECT COUNT(*) as cnt FROM akses WHERE id_staf = ? AND id_level = 3");
$checkAdmin->execute([$_SESSION['user_id']]);
$is_admin = $checkAdmin->fetch()['cnt'] > 0;

if (!$is_admin) {
    header("Location: direktori_aplikasi.php");
    exit();
}

// Define warna mapping untuk kategori
$kategori_warna = [
    1 => '#F39C12',  // Dalaman (Orange)
    2 => '#1ABC9C',  // Luaran (Teal)
    3 => '#6C3483'   // Gunasama (Purple)
];

$id_aplikasi = intval($_GET['id'] ?? 0);
$edit_mode = ($id_aplikasi > 0);
$aplikasi_data = null;
$error_msg = '';
$page_title = $edit_mode ? 'Edit Aplikasi' : 'Tambah Aplikasi Baharu';

// If edit mode, load existing data
if ($edit_mode) {
    $stmt = $db->prepare("SELECT * FROM aplikasi WHERE id_aplikasi = ?");
    $stmt->execute([$id_aplikasi]);
    $aplikasi_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aplikasi_data) {
        header("Location: direktori_aplikasi.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(); // CSRF Protection
    
    try {
        $nama_aplikasi = trim($_POST['nama_aplikasi'] ?? '');
        $id_kategori = intval($_POST['id_kategori'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $warna_bg = $kategori_warna[$id_kategori] ?? '#007bff';
        $sso_comply = isset($_POST['sso_comply']) ? 1 : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

        // Validate required fields
        if (empty($nama_aplikasi) || $id_kategori <= 0) {
            throw new Exception("Nama aplikasi dan kategori diperlukan");
        }
        
        // Validate URL if provided
        if (!empty($url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("URL tidak sah");
            }
        }
        
        // Sanitize text fields
        $nama_aplikasi = htmlspecialchars($nama_aplikasi, ENT_QUOTES, 'UTF-8');
        $keterangan = htmlspecialchars($keterangan, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        if ($edit_mode) {
            // Update existing
            $update = $db->prepare("UPDATE aplikasi SET nama_aplikasi = ?, id_kategori = ?, keterangan = ?, url = ?, warna_bg = ?, sso_comply = ?, status = ? WHERE id_aplikasi = ?");
            $update->execute([$nama_aplikasi, $id_kategori, $keterangan, $url, $warna_bg, $sso_comply, $status, $id_aplikasi]);
            $_SESSION['success_msg'] = "Aplikasi '$nama_aplikasi' berjaya dikemas kini!";
        } else {
            // Insert new
            $insert = $db->prepare("INSERT INTO aplikasi (nama_aplikasi, id_kategori, keterangan, url, warna_bg, sso_comply, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, 1)");
            $insert->execute([$nama_aplikasi, $id_kategori, $keterangan, $url, $warna_bg, $sso_comply]);
            $_SESSION['success_msg'] = "Aplikasi '$nama_aplikasi' berjaya ditambah!";
        }

        header("Location: direktori_aplikasi.php");
        exit();

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

include 'header.php';

// Get kategori list
$kategoriList = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mt-4 mb-5 border-0">
                <div class="card-header <?php echo $edit_mode ? 'bg-warning' : 'bg-primary'; ?> text-white fw-bold">
                    <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus'; ?> me-2"></i> <?php echo $page_title; ?>
                </div>
                <div class="card-body p-4">
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php echo getCsrfTokenField(); // CSRF Protection ?>
                <div class="mb-3">
                    <label for="nama_aplikasi" class="form-label fw-bold">Nama Aplikasi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_aplikasi" name="nama_aplikasi" required 
                           placeholder="Cth: MyPPRS KEDA" value="<?php echo htmlspecialchars($aplikasi_data['nama_aplikasi'] ?? ''); ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_kategori" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_kategori" name="id_kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?php echo $kat['id_kategori']; ?>" 
                                        <?php echo ($aplikasi_data && $aplikasi_data['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="warna_bg" class="form-label fw-bold">Warna Badge <span class="text-muted small">(Auto)</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="warna_display_text" 
                                   value="<?php echo htmlspecialchars($aplikasi_data['warna_bg'] ?? '#007bff'); ?>" disabled>
                            <span class="input-group-text">
                                <div id="warna_preview" style="width: 24px; height: 24px; background-color: <?php echo htmlspecialchars($aplikasi_data['warna_bg'] ?? '#007bff'); ?>; border-radius: 3px;"></div>
                            </span>
                        </div>
                        <small class="text-muted d-block mt-1">Warna akan diatur otomatis berdasarkan kategori</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label fw-bold">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                              placeholder="Deskripsi aplikasi..."><?php echo htmlspecialchars($aplikasi_data['keterangan'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="url" class="form-label fw-bold">URL/Link Aplikasi</label>
                    <input type="url" class="form-control" id="url" name="url" placeholder="https://..." 
                           value="<?php echo htmlspecialchars($aplikasi_data['url'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sso_comply" name="sso_comply"
                               <?php echo ($aplikasi_data && $aplikasi_data['sso_comply'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sso_comply">
                            <strong>SSO Compliant</strong> - Aplikasi ini menyokong Single Sign-On
                        </label>
                    </div>
                </div>

                <?php if ($edit_mode): ?>
                <div class="mb-3">
                    <label for="status" class="form-label fw-bold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="1" <?php echo ($aplikasi_data && $aplikasi_data['status'] == 1) ? 'selected' : ''; ?>>
                            <i class="fas fa-check-circle"></i> Aktif
                        </option>
                        <option value="0" <?php echo ($aplikasi_data && $aplikasi_data['status'] == 0) ? 'selected' : ''; ?>>
                            <i class="fas fa-times-circle"></i> Tidak Aktif
                        </option>
                    </select>
                    <small class="text-muted d-block mt-1">Aplikasi dengan status "Tidak Aktif" tidak akan muncul di Direktori Aplikasi</small>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn <?php echo $edit_mode ? 'btn-warning' : 'btn-primary'; ?> px-4">
                        <i class="fas fa-save me-2"></i> <?php echo $edit_mode ? 'Kemaskini' : 'Simpan'; ?> Aplikasi
                    </button>
                    <a href="direktori_aplikasi.php" class="btn btn-secondary px-4">
                        <i class="fas fa-times me-2"></i> Batal
                    </a>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Define warna mapping
const kategoriWarna = {
    1: '#F39C12',  // Dalaman
    2: '#1ABC9C',  // Luaran
    3: '#6C3483'   // Gunasama
};

// Update warna display ketika kategori berubah
document.getElementById('id_kategori').addEventListener('change', function() {
    const selectedKategori = this.value;
    const warna = kategoriWarna[selectedKategori] || '#007bff';
    
    document.getElementById('warna_display_text').value = warna;
    document.getElementById('warna_preview').style.backgroundColor = warna;
});

// Initialize on page load
window.addEventListener('DOMContentLoaded', function() {
    const selectedKategori = document.getElementById('id_kategori').value;
    if (selectedKategori) {
        const warna = kategoriWarna[selectedKategori] || '#007bff';
        document.getElementById('warna_display_text').value = warna;
        document.getElementById('warna_preview').style.backgroundColor = warna;
    }
});
</script>

</body>
</html>
