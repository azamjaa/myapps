<?php
/**
 * One-time fix: Kemas kini URL aplikasi ke format ringkas apps/nama_aplikasi
 * - nocode_app.php?app=x -> apps/x
 * - diyaplikasi_app.php?app=x -> apps/x
 * - Kemas kini jadual aplikasi DAN nocode_apps
 * Jalankan sekali dalam browser, kemudian boleh padam fail ini.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');

echo '<h3>Kemas kini URL aplikasi janaan no-code → apps/slug</h3>';

// 1) Jadual aplikasi (direktori aplikasi)
$stmt = $db->query("SELECT id_aplikasi, nama_aplikasi, url FROM aplikasi WHERE status = 1 AND (url LIKE '%nocode_app.php%' OR url LIKE '%diyaplikasi_app.php%')");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
foreach ($rows as $row) {
    $url = $row['url'];
    $newUrl = $url;
    if (preg_match('/[?&]app=([a-zA-Z0-9_-]+)/', $url, $m)) {
        $newUrl = 'apps/' . $m[1];
    }
    if ($newUrl !== $url) {
        $db->prepare("UPDATE aplikasi SET url = ? WHERE id_aplikasi = ?")->execute([$newUrl, $row['id_aplikasi']]);
        $updated++;
        echo '<p><strong>aplikasi</strong> ' . htmlspecialchars($row['nama_aplikasi']) . ': <code>' . htmlspecialchars($url) . '</code> → <code>' . htmlspecialchars($newUrl) . '</code></p>';
    }
}
echo '<p><strong>Jadual aplikasi:</strong> ' . $updated . ' rekod dikemas kini.</p>';

// 2) Jadual nocode_apps: pastikan url = apps/app_slug
$stmt2 = $db->query("SELECT id, app_name, app_slug, url FROM nocode_apps WHERE status = 1 AND (url IS NULL OR url NOT LIKE 'apps/%')");
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$updated2 = 0;
foreach ($rows2 as $row) {
    $newUrl = 'apps/' . $row['app_slug'];
    $db->prepare("UPDATE nocode_apps SET url = ? WHERE id = ?")->execute([$newUrl, $row['id']]);
    $updated2++;
    echo '<p><strong>nocode_apps</strong> ' . htmlspecialchars($row['app_name']) . ': <code>' . htmlspecialchars($row['url'] ?? '(null)') . '</code> → <code>' . htmlspecialchars($newUrl) . '</code></p>';
}
echo '<p><strong>Jadual nocode_apps:</strong> ' . $updated2 . ' rekod dikemas kini.</p>';

if ($updated === 0 && $updated2 === 0) {
    echo '<p><strong>Semua URL sudah dalam format <code>apps/nama_aplikasi</code>.</strong></p>';
} else {
    echo '<p><strong>Selesai.</strong> mydesa dan aplikasi lain kini menggunakan URL format <code>apps/mydesa</code>, <code>apps/myjpd</code>, dsb.</p>';
}
echo '<p><a href="dashboard_aplikasi.php">Dashboard Aplikasi</a> | <a href="diyaplikasi_builder.php">DIY Aplikasi</a> | <a href="apps/mydesa">Buka MyDesa</a></p>';
