<?php
/**
 * No-Code Builder - Simpan aplikasi ke jadual custom_apps.
 * Menerima POST: nama_aplikasi, url_slug, metadata_json, id_kategori. id_user_owner dari $_SESSION['id_user'].
 * Jadual custom_apps perlu ada kolum: app_slug, name, metadata, id_user_owner, id_kategori.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$out = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $out['message'] = 'Kaedah tidak dibenarkan.';
    echo json_encode($out);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        $out['message'] = 'Sesi tidak sah. Sila login semula.';
        echo json_encode($out);
        exit;
    }

    $id_user_owner = (int) ($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
    if ($id_user_owner === 0) {
        $out['message'] = 'Sesi pengguna tidak sah. Sila login semula.';
        echo json_encode($out);
        exit;
    }

    verifyCsrfToken();

    $nama_aplikasi = trim($_POST['nama_aplikasi'] ?? '');
    $url_slug = trim($_POST['url_slug'] ?? '');
    $url_slug = strtolower(preg_replace('/\s+/', '-', $url_slug));
    $url_slug = preg_replace('/[^a-z0-9_-]/', '', $url_slug);
    $metadata_json = $_POST['metadata_json'] ?? '[]';
    $id_kategori = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;

    if ($nama_aplikasi === '') {
        $out['message'] = 'Nama Aplikasi wajib diisi.';
        echo json_encode($out);
        exit;
    }
    if ($url_slug === '') {
        $out['message'] = 'URL Slug wajib diisi (tanpa ruang, huruf kecil sahaja).';
        echo json_encode($out);
        exit;
    }
    if ($id_kategori <= 0) {
        $out['message'] = 'Sila pilih Kategori.';
        echo json_encode($out);
        exit;
    }

    $meta = json_decode($metadata_json, true);
    if (!is_array($meta)) {
        $out['message'] = 'Metadata borang tidak sah.';
        echo json_encode($out);
        exit;
    }

    // Semak slug unik
    $stmt = $pdo->prepare("SELECT 1 FROM custom_apps WHERE app_slug = ? LIMIT 1");
    $stmt->execute([$url_slug]);
    if ($stmt->fetch()) {
        $out['message'] = 'URL Slug sudah digunakan. Sila pilih slug lain.';
        echo json_encode($out);
        exit;
    }

    // Simpan ke custom_apps. Kolum: app_slug, app_name, metadata, id_user_owner, id_kategori
    $ins = $pdo->prepare("INSERT INTO custom_apps (app_slug, app_name, metadata, id_user_owner, id_kategori) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$url_slug, $nama_aplikasi, $metadata_json, $id_user_owner, $id_kategori]);

    $out['success'] = true;
    $out['message'] = 'Aplikasi berjaya disimpan.';
    $out['slug'] = $url_slug;
} catch (PDOException $e) {
    $out['message'] = 'Ralat database: ' . $e->getMessage();
    error_log('builder_save.php PDO: ' . $e->getMessage());
} catch (Exception $e) {
    $out['message'] = $e->getMessage();
    error_log('builder_save.php: ' . $e->getMessage());
}

echo json_encode($out);
