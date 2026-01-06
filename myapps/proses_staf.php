<?php
require 'db.php';
require 'fungsi_emel.php'; 

// A. TAMBAH STAF BARU
if (isset($_POST['save_new'])) {
    verifyCsrfToken(); // CSRF Protection
    
    // Check RBAC permission for Create User (permission id 1 untuk MyApps direktori)
    // Guna hasAccess() function dari db.php
    $has_create_permission = hasAccess($pdo, $_SESSION['user_id'], 1, 'create_user');
    
    if (!$has_create_permission) {
        echo "<script>alert('⛔ Anda Tidak Dibenarkan Akses Halaman Ini.\n\nHubungi admin untuk mendapat akses.'); window.location='direktori_staf.php';</script>"; exit();
    }
    try {
        $db->beginTransaction();
        
        // Upload Gambar
        $gambar = NULL;
        if (!empty($_FILES['gambar']['name'])) {
            $gambar = uploadGambar($_FILES['gambar']);
        }

        $sql = "INSERT INTO users (no_staf, no_kp, nama, emel, telefon, id_jawatan, id_gred, id_bahagian, gambar, id_status_staf) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['no_staf'], $_POST['no_kp'], strtoupper($_POST['nama']), 
            $_POST['emel'], $_POST['telefon'], $_POST['id_jawatan'], 
            $_POST['id_gred'], $_POST['id_bahagian'], $gambar
        ]);
        
        $new_id = $db->lastInsertId();
        $default_hash = password_hash('123456', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO login (id_user, password_hash) VALUES (?, ?)")->execute([$new_id, $default_hash]);

        $db->commit();
        echo "<script>alert('Berjaya ditambah!'); window.location='direktori_staf.php';</script>";
    } catch (PDOException $e) {
        $db->rollBack();
        echo "<script>alert('Gagal tambah.'); window.history.back();</script>";
    }
}

// B. KEMASKINI STAF
if (isset($_POST['update'])) {
    verifyCsrfToken(); // CSRF Protection
    
    $id = $_POST['id_user'];
    
    // Check RBAC permission - admin boleh edit semua field, user biasa edit field terhad sahaja
    $has_edit_user_permission = hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_user');
    $is_own_profile = ($id == $_SESSION['user_id']);
    
    // User biasa boleh edit profil sendiri sahaja (field terhad)
    // Admin/Super Admin boleh edit sesiapa (full access)
    if (!$has_edit_user_permission && !$is_own_profile) {
        echo "<script>alert('⛔ Anda Tidak Dibenarkan Akses Halaman Ini.\n\nAnda hanya boleh edit profil sendiri.'); window.location='direktori_staf.php';</script>"; exit();
    }

    try {
        $sql_gambar = "";
        $params = [];
        
        if (!empty($_FILES['gambar']['name'])) {
            $nama_gambar = uploadGambar($_FILES['gambar']);
            if ($nama_gambar) {
                $sql_gambar = ", gambar=?";
                $params_gambar = [$nama_gambar];
            }
        }

        if (!$has_edit_user_permission) {
            // User biasa - edit field terhad sahaja (emel, telefon, gred, bahagian)
            $sql = "UPDATE users SET emel=?, telefon=?, id_gred=?, id_bahagian=? $sql_gambar WHERE id_user=?";
            $params = [$_POST['emel'], $_POST['telefon'], $_POST['id_gred'], $_POST['id_bahagian']];
        } else {
            // Admin - edit semua field termasuk status
            $sql = "UPDATE users SET no_staf=?, no_kp=?, nama=?, emel=?, telefon=?, id_jawatan=?, id_gred=?, id_bahagian=?, id_status_staf=? $sql_gambar WHERE id_user=?";
            $params = [$_POST['no_staf'], $_POST['no_kp'], strtoupper($_POST['nama']), $_POST['emel'], $_POST['telefon'], $_POST['id_jawatan'], $_POST['id_gred'], $_POST['id_bahagian'], $_POST['id_status_staf']];
        }

        if (!empty($sql_gambar)) {
            $params = array_merge($params, $params_gambar);
        }
        $params[] = $id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($id == $_SESSION['user_id'] && !empty($sql_gambar)) {
            $_SESSION['gambar'] = $nama_gambar;
        }

        echo "<script>alert('Maklumat dikemaskini!'); window.location='direktori_staf.php';</script>";
    } catch (Exception $e) {
        $error_msg = 'Gagal kemaskini: ' . $e->getMessage();
        echo "<script>alert('".addslashes($error_msg)."'); window.history.back();</script>";
        error_log($error_msg);
    }
}

// C. HANTAR WISH BIRTHDAY (DIPERBAIKI: GUNA ID)
if (isset($_POST['send_wish'])) {
    verifyCsrfToken(); // CSRF Protection
    
    $id_target = $_POST['id_user_wish']; // Ambil ID dari form

    // Cari staf guna ID (Lebih Tepat)
    $stmt = $db->prepare("SELECT nama, emel, no_kp FROM users WHERE id_user = ?");
    $stmt->execute([$id_target]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($target && !empty($target['emel'])) {
        $umur = getUmur($target['no_kp']);
        
        $hari = substr($target['no_kp'], 4, 2);
        $bulan = substr($target['no_kp'], 2, 2);
        $tahun_semasa = date('Y');
        $tarikh_sambutan = "$hari/$bulan/$tahun_semasa";

        $subjek = "Selamat Hari Lahir, " . $target['nama'] . "!";
        
        $mesej  = "<div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
        $mesej .= "<h3 style='color: #d32f2f;'>Selamat Hari Lahir! 🎂</h3>";
        $mesej .= "<p>Assalamualaikum & Salam Sejahtera,</p>";
        $mesej .= "<p>Selamat ulang tahun kelahiran diucapkan kepada <b>" . $target['nama'] . "</b> yang ke-<b>" . $umur . "</b> tahun pada <b>" . $tarikh_sambutan . "</b>.</p>";
        $mesej .= "<p>Semoga anda dipanjangkan umur, dimurahkan rezeki, diberi kesihatan yang baik, dipermudahkan segala urusan dan berbahagia selalu hendaknya.</p>";
        $mesej .= "<br><hr>";
        $mesej .= "<p style='font-size: 12px; color: #777;'>Ikhlas daripada,<br>Pengurusan & Rakan Setugas KEDA</p>";
        $mesej .= "</div>";

        if (hantarEmel($target['emel'], $subjek, $mesej)) {
            echo "<script>alert('Ucapan berjaya dihantar ke emel staf!'); window.location='kalendar.php';</script>";
        } else {
            echo "<script>alert('Gagal menghantar emel. Sila cuba lagi.'); window.location='kalendar.php';</script>";
        }
    } else {
        echo "<script>alert('Ralat: Staf tiada emel atau data tidak dijumpai.'); window.location='kalendar.php';</script>";
    }
    exit(); // PENTING: Berhenti di sini supaya tak paparkan form bawah
}

// FUNGSI UPLOAD
function uploadGambar($file) {
    $target_dir = "uploads/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    $check = getimagesize($file["tmp_name"]);
    if($check === false) return false;
    if ($file["size"] > 2000000) return false;
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") return false;

    if (move_uploaded_file($file["tmp_name"], $target_file)) return $new_filename;
    return false;
}

// ============================================================
// 3. PERSEDIAAN PAPARAN BORANG
// ============================================================
include 'header.php';

// Check RBAC permission
$has_create_permission = hasAccess($pdo, $_SESSION['user_id'], 1, 'create_user');
$has_edit_user_permission = hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_user');

// ... (Kod untuk paparan borang tambah/edit staf kekal di sini) ...
$listJawatan = $db->query("SELECT * FROM jawatan ORDER BY jawatan ASC")->fetchAll();
$listGred = $db->query("SELECT * FROM gred ORDER BY gred ASC")->fetchAll();
$listBahagian = $db->query("SELECT * FROM bahagian ORDER BY bahagian ASC")->fetchAll();
$listStatusStaf = $db->query("SELECT * FROM status_staf WHERE aktif = 1 ORDER BY id_status ASC")->fetchAll();

$stafData = [];
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$_GET['id']]);
    $stafData = $stmt->fetch(PDO::FETCH_ASSOC);
    $title = "Kemaskini Profil"; $btnName = "update";
} else {
    $title = "Tambah Staf"; $btnName = "save_new";
}

$currentUserRole = $_SESSION['role'];
$readOnlyText = ($currentUserRole == 'user') ? 'readonly style="background-color: #e9ecef;"' : '';
$disabledJawatan = ($currentUserRole == 'user') ? 'disabled' : '';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mt-4 mb-5 border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-user-edit me-2"></i> <?php echo $title; ?>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo getCsrfTokenField(); // CSRF Protection ?>
                        <?php if(isset($stafData['id_user'])): ?>
                            <input type="hidden" name="id_user" value="<?php echo $stafData['id_user']; ?>">
                        <?php endif; ?>

                        <div class="row mb-4 align-items-center">
                            <div class="col-md-3 text-center">
                                <?php 
                                    $img = !empty($stafData['gambar']) ? "uploads/".$stafData['gambar'] : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png"; 
                                ?>
                                <img src="<?php echo $img; ?>" class="rounded-circle border" width="100" height="100" style="object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-bold small">Muat Naik Gambar Profil (Pilihan)</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">No. Staf</label>
                                <input type="text" name="no_staf" class="form-control" value="<?php echo $stafData['no_staf'] ?? ''; ?>" required <?php echo $readOnlyText; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">No. KP</label>
                                <input type="text" name="no_kp" class="form-control" value="<?php echo $stafData['no_kp'] ?? ''; ?>" required <?php echo $readOnlyText; ?>>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Penuh</label>
                            <input type="text" name="nama" class="form-control" value="<?php echo $stafData['nama'] ?? ''; ?>" required <?php echo $readOnlyText; ?>>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold small">Jawatan</label>
                                <select name="id_jawatan" class="form-select" required <?php echo $disabledJawatan; ?>>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach($listJawatan as $j): ?>
                                        <option value="<?php echo $j['id_jawatan']; ?>" <?php echo (isset($stafData['id_jawatan']) && $stafData['id_jawatan'] == $j['id_jawatan']) ? 'selected' : ''; ?>><?php echo $j['jawatan']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if($currentUserRole == 'user'): ?><input type="hidden" name="id_jawatan" value="<?php echo $stafData['id_jawatan']; ?>"><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Gred</label>
                                <select name="id_gred" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach($listGred as $g): ?>
                                        <option value="<?php echo $g['id_gred']; ?>" <?php echo (isset($stafData['id_gred']) && $stafData['id_gred'] == $g['id_gred']) ? 'selected' : ''; ?>><?php echo $g['gred']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Bahagian</label>
                                <select name="id_bahagian" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach($listBahagian as $b): ?>
                                        <option value="<?php echo $b['id_bahagian']; ?>" <?php echo (isset($stafData['id_bahagian']) && $stafData['id_bahagian'] == $b['id_bahagian']) ? 'selected' : ''; ?>><?php echo $b['bahagian']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Status Staf</label>
                                <select name="id_status_staf" class="form-select" required <?php echo $disabledJawatan; ?>>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach($listStatusStaf as $s): ?>
                                        <option value="<?php echo $s['id_status']; ?>" <?php echo (isset($stafData['id_status_staf']) && $stafData['id_status_staf'] == $s['id_status']) ? 'selected' : ''; ?>><?php echo $s['nama_status']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if($currentUserRole == 'user'): ?><input type="hidden" name="id_status_staf" value="<?php echo $stafData['id_status_staf']; ?>"><?php endif; ?>
                            </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Emel</label>
                                <input type="email" name="emel" class="form-control" value="<?php echo $stafData['emel'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Telefon</label>
                                <input type="text" name="telefon" class="form-control" value="<?php echo $stafData['telefon'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="direktori_staf.php" class="btn btn-light border">Batal</a>
                            <button type="submit" name="<?php echo $btnName; ?>" class="btn btn-primary px-4">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
