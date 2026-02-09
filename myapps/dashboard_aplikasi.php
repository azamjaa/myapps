<?php
require 'db.php';

// ============================================================
// EXPORT EXCEL FUNCTIONALITY
// ============================================================
if (isset($_GET['export'])) {
    if (ob_get_length()) ob_end_clean();

    // Dapatkan kategori untuk export (utamakan ?direktori_kategori= , fallback ?kategori= ), kosong = semua
    $export_kat = null;
    if (isset($_GET['direktori_kategori']) && $_GET['direktori_kategori'] !== '') {
        $export_kat = (int)$_GET['direktori_kategori'];
    } elseif (isset($_GET['kategori']) && $_GET['kategori'] !== '') {
        $export_kat = (int)$_GET['kategori'];
    }

    // Valid kategori: 1=Dal, 2=Lua, 3=Guna
    $allowed_kat = [1, 2, 3];
    if (!in_array($export_kat, $allowed_kat, true)) {
        $export_kat = null; // null = semua
    }

    // Label fail ikut kategori
    $kat_labels = [
        1 => 'Aplikasi_Dalaman',
        2 => 'Aplikasi_Luaran',
        3 => 'Aplikasi_Gunasama'
    ];
    $label = $export_kat ? $kat_labels[$export_kat] : 'Semua_Aplikasi';

    $filename = "Direktori_Aplikasi_" . $label . "_" . date('Ymd') . ".xls";

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/>
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000000; padding: 5px; }
                th { background-color: #007bff; color: white; }
            </style>
          </head><body>';
    
    echo '<table>';
    echo '<tr>
            <th>ID</th>
            <th>NAMA APLIKASI</th>
            <th>KATEGORI</th>
            <th>KETERANGAN</th>
            <th>URL</th>
            <th>SSO</th>
          </tr>';
    
    // QUERY EXPORT: tapis kategori jika dipilih
    $sqlExport = "SELECT a.id_aplikasi, a.nama_aplikasi, k.nama_kategori, 
                         a.keterangan, a.url, a.sso_comply
                  FROM aplikasi a 
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  WHERE a.status = 1";
    $params = [];
    if ($export_kat) {
        $sqlExport .= " AND a.id_kategori = ?";
        $params[] = $export_kat;
    }
    $sqlExport .= " ORDER BY a.id_kategori ASC, a.id_aplikasi ASC";
                  
    $stmt = $db->prepare($sqlExport);
    $stmt->execute($params);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id_aplikasi'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_aplikasi'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_kategori'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['keterangan'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['url'] ?? '') . '</td>';
        echo '<td style="text-align:center;">' . ($row['sso_comply'] == 1 ? '✓ SSO' : '') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit(); 
}

include 'header.php';

// Get kategori list for modal
$kategoriList = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

// Statistik Aplikasi (gabungan aplikasi + custom_apps)
try {
    $cntAplikasi = (int) $db->query("SELECT (SELECT COUNT(*) FROM aplikasi WHERE status = 1) + (SELECT COUNT(*) FROM custom_apps WHERE id_kategori IN (1,2,3)) AS total")->fetchColumn();
    $cntDalaman = (int) $db->query("SELECT (SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 1) + (SELECT COUNT(*) FROM custom_apps WHERE id_kategori = 1) AS total")->fetchColumn();
    $cntLuaran = (int) $db->query("SELECT (SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 2) + (SELECT COUNT(*) FROM custom_apps WHERE id_kategori = 2) AS total")->fetchColumn();
    $cntGunasama = (int) $db->query("SELECT (SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 3) + (SELECT COUNT(*) FROM custom_apps WHERE id_kategori = 3) AS total")->fetchColumn();
    $chartKategori = $db->query("
        SELECT id_kategori, nama_kategori, SUM(total) AS total FROM (
            SELECT k.id_kategori, k.nama_kategori, COUNT(a.id_aplikasi) AS total FROM aplikasi a JOIN kategori k ON a.id_kategori = k.id_kategori WHERE a.status = 1 GROUP BY a.id_kategori, k.nama_kategori
            UNION ALL
            SELECT c.id_kategori, k.nama_kategori, COUNT(*) AS total FROM custom_apps c JOIN kategori k ON c.id_kategori = k.id_kategori WHERE c.id_kategori IN (1,2,3) GROUP BY c.id_kategori, k.nama_kategori
        ) AS u GROUP BY id_kategori, nama_kategori ORDER BY id_kategori ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cntAplikasi = (int) $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1")->fetchColumn();
    $cntDalaman = (int) $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 1")->fetchColumn();
    $cntLuaran = (int) $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 2")->fetchColumn();
    $cntGunasama = (int) $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 3")->fetchColumn();
    $chartKategori = $db->query("SELECT k.id_kategori, k.nama_kategori, COUNT(a.id_aplikasi) as total FROM aplikasi a JOIN kategori k ON a.id_kategori = k.id_kategori WHERE a.status = 1 GROUP BY a.id_kategori, k.nama_kategori ORDER BY a.id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// Data untuk Direktori Aplikasi List View
$direktori_search = $_GET['direktori_search'] ?? '';
$direktori_kategori = $_GET['direktori_kategori'] ?? '';
$direktori_sort = $_GET['direktori_sort'] ?? 'id_kategori';
$direktori_order = $_GET['direktori_order'] ?? 'ASC';
$direktori_page = isset($_GET['direktori_page']) ? max(1, intval($_GET['direktori_page'])) : 1;

$allowed_sort = ['id_kategori', 'id_aplikasi', 'nama_aplikasi', 'keterangan', 'sso_comply'];
if (!in_array($direktori_sort, $allowed_sort)) { $direktori_sort = 'id_kategori'; }

$direktori_items_per_page = 20;
$direktori_offset = ($direktori_page - 1) * $direktori_items_per_page;

// SQL UNION ALL: gabung jadual aplikasi dan custom_apps. Pautan no-code = /myapps/apps/[app_slug]
$direktori_union = "(SELECT a.id_aplikasi, a.nama_aplikasi, a.id_kategori, k.nama_kategori, a.keterangan, a.url, a.sso_comply, a.status, 0 AS is_nocode
                    FROM aplikasi a LEFT JOIN kategori k ON a.id_kategori = k.id_kategori WHERE a.status = 1)
                  UNION ALL
                  (SELECT NULL AS id_aplikasi, c.app_name AS nama_aplikasi, c.id_kategori, k.nama_kategori, 'Borang No-Code' AS keterangan, CONCAT('/myapps/apps/', c.app_slug) AS url, COALESCE(c.sso_ready, 1) AS sso_comply, 1 AS status, 1 AS is_nocode
                    FROM custom_apps c LEFT JOIN kategori k ON c.id_kategori = k.id_kategori WHERE c.id_kategori IN (1, 2, 3))";

// Query count untuk direktori (gabungan aplikasi + custom_apps)
$direktori_sqlCount = "SELECT COUNT(*) AS total FROM ($direktori_union) AS combined WHERE 1=1";
if (!empty($direktori_kategori)) {
    $direktori_sqlCount .= " AND combined.id_kategori = " . intval($direktori_kategori);
}
if (!empty($direktori_search)) {
    $direktori_sqlCount .= " AND (combined.nama_aplikasi LIKE ? OR combined.keterangan LIKE ? OR combined.nama_kategori LIKE ?)";
}

$direktori_stmt = $db->prepare($direktori_sqlCount);
if (!empty($direktori_search)) {
    $searchParam = "%$direktori_search%";
    $direktori_stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $direktori_stmt->execute();
}
$direktori_total_records = (int) $direktori_stmt->fetch()['total'];
$direktori_total_pages = ceil($direktori_total_records / $direktori_items_per_page) ?: 1;

if ($direktori_page > $direktori_total_pages && $direktori_total_pages > 0) {
    $direktori_page = $direktori_total_pages;
    $direktori_offset = ($direktori_page - 1) * $direktori_items_per_page;
}

// Query aplikasi + custom_apps untuk direktori
$direktori_sql = "SELECT * FROM ($direktori_union) AS combined WHERE 1=1";
if (!empty($direktori_kategori)) {
    $direktori_sql .= " AND combined.id_kategori = " . intval($direktori_kategori);
}
if (!empty($direktori_search)) {
    $direktori_sql .= " AND (combined.nama_aplikasi LIKE ? OR combined.keterangan LIKE ? OR combined.nama_kategori LIKE ?)";
}
if ($direktori_sort === 'kategori') {
    $direktori_sql .= " ORDER BY combined.nama_kategori $direktori_order, combined.nama_aplikasi ASC";
} elseif ($direktori_sort === 'id_kategori') {
    $direktori_sql .= " ORDER BY combined.id_kategori $direktori_order, combined.nama_aplikasi ASC";
} else {
    $direktori_sql .= " ORDER BY combined.$direktori_sort $direktori_order";
}
$direktori_sql .= " LIMIT $direktori_items_per_page OFFSET $direktori_offset";

$direktori_stmt = $db->prepare($direktori_sql);
if (!empty($direktori_search)) {
    $searchParam = "%$direktori_search%";
    $direktori_stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $direktori_stmt->execute();
}
$direktori_data = $direktori_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get kategori list untuk direktori
$direktori_kategoriList = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);

// Count by kategori untuk direktori (aplikasi + custom_apps)
$direktori_allApps = [];
try {
    $cntStmt = $db->query("SELECT id_kategori, COUNT(*) AS total FROM (SELECT id_kategori FROM aplikasi WHERE status = 1 UNION ALL SELECT id_kategori FROM custom_apps WHERE id_kategori IN (1,2,3)) AS u GROUP BY id_kategori");
    while ($r = $cntStmt->fetch(PDO::FETCH_NUM)) {
        $direktori_allApps[$r[0]] = (int) $r[1];
    }
} catch (PDOException $e) {
    $direktori_allApps = $db->query("SELECT id_kategori, COUNT(*) as total FROM aplikasi WHERE status = 1 GROUP BY id_kategori")->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Function untuk sort link dalam direktori
function direktoriSortLink($col, $currentSort, $currentOrder, $currentSearch, $currentKat) {
    $sortField = $col;
    if ($col === 'KATEGORI') { $sortField = 'id_kategori'; }
    if ($col === 'APLIKASI') { $sortField = 'nama_aplikasi'; }
    
    $newOrder = ($currentSort == $sortField && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort == $sortField) ? (($currentOrder == 'ASC') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted opacity-25"></i>';
    
    $params = http_build_query([
        'direktori_search' => $currentSearch,
        'direktori_kategori' => $currentKat,
        'direktori_sort' => $sortField,
        'direktori_order' => $newOrder,
        'direktori_page' => 1
    ]);
    
    return "<a href='?$params#direktoriAplikasiContainer' class='text-dark text-decoration-none fw-bold'>$icon</a>";
}

// Get last updated date from aplikasi table
$lastUpdated = null;
try {
    // Try to get MAX(updated_at) first
    $dateStmt = $db->query("SELECT MAX(updated_at) as last_updated FROM aplikasi WHERE status = 1");
    $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
    if ($dateRow && $dateRow['last_updated']) {
        $lastUpdated = $dateRow['last_updated'];
    } else {
        // Fallback to created_at if updated_at doesn't exist
        try {
            $dateStmt = $db->query("SELECT MAX(created_at) as last_updated FROM aplikasi WHERE status = 1");
            $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
            if ($dateRow && $dateRow['last_updated']) {
                $lastUpdated = $dateRow['last_updated'];
            }
        } catch (Exception $e2) {
            // If created_at also doesn't exist, try to get from audit_log
            try {
                $auditStmt = $db->query("SELECT MAX(created_at) as last_updated FROM audit_log WHERE table_affected = 'aplikasi' ORDER BY created_at DESC LIMIT 1");
                $auditRow = $auditStmt->fetch(PDO::FETCH_ASSOC);
                if ($auditRow && $auditRow['last_updated']) {
                    $lastUpdated = $auditRow['last_updated'];
                }
            } catch (Exception $e3) {
                // Use current date as fallback
                $lastUpdated = date('Y-m-d H:i:s');
            }
        }
    }
} catch (Exception $e) {
    // If updated_at column doesn't exist, try other methods
    try {
        $dateStmt = $db->query("SELECT MAX(created_at) as last_updated FROM aplikasi WHERE status = 1");
        $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
        if ($dateRow && $dateRow['last_updated']) {
            $lastUpdated = $dateRow['last_updated'];
        } else {
            // Use current date as fallback
            $lastUpdated = date('Y-m-d H:i:s');
        }
    } catch (Exception $e2) {
        // Use current date as final fallback
        $lastUpdated = date('Y-m-d H:i:s');
    }
}

// Ensure we always have a date to display
if (!$lastUpdated) {
    $lastUpdated = date('Y-m-d H:i:s');
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line me-3 text-primary"></i>Dashboard Aplikasi</h3>
        <?php
        // Always display date - use formatted date or current date
        if ($lastUpdated) {
            $formattedDate = date('d/m/Y H:i', strtotime($lastUpdated));
        } else {
            $formattedDate = date('d/m/Y H:i');
        }
        echo '<small class="text-muted"><i class="fas fa-clock me-1"></i>Rekod dikemaskini: ' . htmlspecialchars($formattedDate) . '</small>';
        ?>
    </div>
    <!-- Clickable Summary Statistics Cards as Tabs -->
    <div class="row g-4 mb-4">
        <!-- ...existing summary cards code... -->
        <?php /* The summary cards code block remains unchanged, just moved inside main content */ ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card" data-bs-toggle="pill" data-bs-target="#semua" role="tab" style="border-left: 5px solid #10B981 !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Senarai Aplikasi</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntAplikasi; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <i class="fas fa-th fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card active" data-bs-toggle="pill" data-bs-target="#dalaman" role="tab" style="border-left: 5px solid #F59E0B !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Aplikasi Dalaman</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntDalaman; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <i class="fas fa-cube fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card" data-bs-toggle="pill" data-bs-target="#luaran" role="tab" style="border-left: 5px solid #EF4444 !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Aplikasi Luaran</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntLuaran; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                        <i class="fas fa-globe fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card" data-bs-toggle="pill" data-bs-target="#gunasama" role="tab" style="border-left: 5px solid #4169E1 !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Aplikasi Gunasama</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntGunasama; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%);">
                        <i class="fas fa-share-alt fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Buang pseudo-element arrow pada summary-card jika ada */
    .summary-card.active::after, .summary-card::after {
        display: none !important;
        content: none !important;
    }
    
    /* Styling untuk direktori aplikasi */
    .nama-link { 
        color: #0d6efd; 
        font-weight: 600; 
        text-decoration: none; 
    }
    .nama-link:hover { 
        text-decoration: underline; 
        color: #0a58ca; 
    }
    .nav-tabs .nav-link {
        color: #666;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 16px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link:hover {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
        background-color: transparent;
    }
    
    /* Hide tab content (grid cards) when direktori is shown */
    body.show-direktori .tab-content > .tab-pane:not(#direktori-semua):not([id^="direktori-kat-"]) {
        display: none !important;
    }
    </style>
    <!-- Tab Content Container -->
    <div class="tab-content" id="kategoriTabContent">
        <!-- ...existing tab panes code... -->
        <!-- The tab panes code block remains unchanged, just moved inside main content -->
        <!-- TAB: SEMUA (Grid Cards View - Hidden when direktori is shown) -->
        <div class="tab-pane fade" id="semua" role="tabpanel">
    
    <?php
    // Paparan direktori aplikasi: UNION ALL aplikasi + custom_apps. Pautan no-code = /myapps/apps/[app_slug]
    // id_kategori: 1=Dalaman, 2=Luaran, 3=Gunasama — aplikasi muncul di tab yang betul
    $unionDalaman = "(SELECT a.id_aplikasi, a.nama_aplikasi, a.id_kategori, a.keterangan, a.url, a.sso_comply, 0 AS is_nocode FROM aplikasi a WHERE a.status = 1 AND a.id_kategori = 1)
                     UNION ALL
                     (SELECT NULL AS id_aplikasi, c.app_name AS nama_aplikasi, c.id_kategori, 'Borang No-Code' AS keterangan, CONCAT('/myapps/apps/', c.app_slug) AS url, COALESCE(c.sso_ready, 1) AS sso_comply, 1 AS is_nocode FROM custom_apps c WHERE c.id_kategori = 1)";
    $unionLuaran  = "(SELECT a.id_aplikasi, a.nama_aplikasi, a.id_kategori, a.keterangan, a.url, a.sso_comply, 0 AS is_nocode FROM aplikasi a WHERE a.status = 1 AND a.id_kategori = 2)
                     UNION ALL
                     (SELECT NULL AS id_aplikasi, c.app_name AS nama_aplikasi, c.id_kategori, 'Borang No-Code' AS keterangan, CONCAT('/myapps/apps/', c.app_slug) AS url, COALESCE(c.sso_ready, 1) AS sso_comply, 1 AS is_nocode FROM custom_apps c WHERE c.id_kategori = 2)";
    $unionGunasama= "(SELECT a.id_aplikasi, a.nama_aplikasi, a.id_kategori, a.keterangan, a.url, a.sso_comply, 0 AS is_nocode FROM aplikasi a WHERE a.status = 1 AND a.id_kategori = 3)
                     UNION ALL
                     (SELECT NULL AS id_aplikasi, c.app_name AS nama_aplikasi, c.id_kategori, 'Borang No-Code' AS keterangan, CONCAT('/myapps/apps/', c.app_slug) AS url, COALESCE(c.sso_ready, 1) AS sso_comply, 1 AS is_nocode FROM custom_apps c WHERE c.id_kategori = 3)";
    try {
        $aplikasiDalaman = $db->query("SELECT * FROM ($unionDalaman) AS u ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
        $aplikasiLuaran  = $db->query("SELECT * FROM ($unionLuaran) AS u ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
        $aplikasiGunasama= $db->query("SELECT * FROM ($unionGunasama) AS u ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $aplikasiDalaman = $db->query("SELECT id_aplikasi, nama_aplikasi, id_kategori, keterangan, url, sso_comply, 0 AS is_nocode FROM aplikasi WHERE status = 1 AND id_kategori = 1 ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
        $aplikasiLuaran  = $db->query("SELECT id_aplikasi, nama_aplikasi, id_kategori, keterangan, url, sso_comply, 0 AS is_nocode FROM aplikasi WHERE status = 1 AND id_kategori = 2 ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
        $aplikasiGunasama= $db->query("SELECT id_aplikasi, nama_aplikasi, id_kategori, keterangan, url, sso_comply, 0 AS is_nocode FROM aplikasi WHERE status = 1 AND id_kategori = 3 ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Icon mapping based on application name - UNIQUE & ATTRACTIVE for each app
    function getAppIcon($nama_aplikasi, $keterangan = '') {
        $nama_lower = strtolower($nama_aplikasi);
        $keterangan_lower = strtolower($keterangan);
        $combined = $nama_lower . ' ' . $keterangan_lower;
        
        // ============================================
        // EXACT APP NAME MAPPING (Priority 1) - UNIQUE ICONS
        // ============================================
        $exactMap = [
            // APLIKASI DALAMAN (From screenshot)
            'kedamap' => 'fa-map-marked-alt',
            'myapps' => 'fa-th-large',
            'mydaftar' => 'fa-file-alt',
            'mygovuc' => 'fa-envelope',
            'mypprs' => 'fa-project-diagram',
            'saga' => 'fa-wallet',
            'smp' => 'fa-graduation-cap',
            'staff portal' => 'fa-users',
            'tms' => 'fa-money-bill-wave',
            
            // APLIKASI LUARAN (From screenshot)
            'daftarkolej' => 'fa-university',
            'ejawatan' => 'fa-briefcase',
            'kedapay' => 'fa-credit-card',
            'mypremis' => 'fa-building',
            'portal keda' => 'fa-door-open',
            'portal kolej keda' => 'fa-school',
            'spbk' => 'fa-file-signature',
            
            // APLIKASI GUNASAMA (From screenshot)
            'ddms' => 'fa-database',
            'ecos' => 'fa-leaf',
            'ekasih' => 'fa-laptop',
            'mymesyuarat' => 'fa-video',
            'myprojek' => 'fa-tasks',
            'myspike' => 'fa-phone',
            'sispaa' => 'fa-clipboard-check',
            'spkpn' => 'fa-chart-pie',
            
            // OTHER COMMON APPS
            'emas care' => 'fa-heartbeat',
            'emas' => 'fa-hospital-user',
            'epelawat' => 'fa-address-card',
            'pelawat' => 'fa-user-friends',
            'eperjalanan' => 'fa-route',
            'perjalanan' => 'fa-plane-departure',
            'etanah' => 'fa-globe-asia',
            'tanah' => 'fa-map',
            'eutiliti' => 'fa-plug',
            'utiliti' => 'fa-bolt',
            'fer' => 'fa-file-invoice',
            'gasset' => 'fa-cubes',
            'gfixed' => 'fa-couch',
            'gintan' => 'fa-box-open',
            'glive' => 'fa-broadcast-tower',
            'gstore' => 'fa-store',
            'hrmis' => 'fa-users-cog',
            'ptt' => 'fa-user-clock',
            'eptt' => 'fa-business-time',
            
            // EDUCATION & TRAINING
            'e-spkb' => 'fa-certificate',
            'spkb' => 'fa-graduation-cap',
            'latihan' => 'fa-chalkboard-teacher',
            'training' => 'fa-user-graduate',
            'kursus' => 'fa-book-open',
            'lms' => 'fa-book-reader',
            
            // OFFICE & DOCUMENTS
            'e-office' => 'fa-briefcase',
            'office' => 'fa-building',
            'dokumen' => 'fa-file-pdf',
            'document' => 'fa-folder-open',
            'surat' => 'fa-envelope-open-text',
            'mail' => 'fa-mail-bulk',
            'tandatangan' => 'fa-signature',
            'signature' => 'fa-pen-fancy',
            
            // PROCUREMENT & PURCHASING
            'e-perolehan' => 'fa-cart-plus',
            'perolehan' => 'fa-shopping-cart',
            'procurement' => 'fa-shopping-basket',
            'tender' => 'fa-gavel',
            'vendor' => 'fa-handshake',
            'kontrak' => 'fa-file-contract',
            'contract' => 'fa-file-signature',
            
            // LEAVE & ATTENDANCE
            'e-cuti' => 'fa-umbrella-beach',
            'cuti' => 'fa-calendar-minus',
            'leave' => 'fa-calendar-alt',
            'kehadiran' => 'fa-user-check',
            'attendance' => 'fa-fingerprint',
            'punch' => 'fa-clock',
            
            // MEETING & COMMUNICATION
            'e-mesyuarat' => 'fa-video',
            'mesyuarat' => 'fa-users',
            'meeting' => 'fa-handshake',
            'conference' => 'fa-phone-volume',
            
            // COMPLAINT & FEEDBACK
            'e-aduan' => 'fa-exclamation-circle',
            'aduan' => 'fa-bullhorn',
            'complaint' => 'fa-comment-dots',
            'feedback' => 'fa-comments',
            'helpdesk' => 'fa-headset',
            'support' => 'fa-life-ring',
            'ticketing' => 'fa-ticket-alt',
            
            // ASSESSMENT & EVALUATION
            'pentaksiran' => 'fa-poll',
            'assessment' => 'fa-tasks',
            'evaluation' => 'fa-star',
            'penilaian' => 'fa-chart-bar',
            
            // VEHICLE & TRANSPORT
            'kenderaan' => 'fa-car-side',
            'vehicle' => 'fa-truck-moving',
            'transport' => 'fa-shuttle-van',
            'parking' => 'fa-parking',
            
            // STORE & WAREHOUSE
            'stor' => 'fa-warehouse',
            'store' => 'fa-store-alt',
            'warehouse' => 'fa-pallet',
            'stock' => 'fa-boxes',
            
            // PORTAL & GENERAL
            'portal' => 'fa-th-large',
            'dashboard' => 'fa-tachometer-alt',
            'cms' => 'fa-edit',
            'website' => 'fa-globe',
            
            // PAYROLL & SALARY
            'payroll' => 'fa-money-check-alt',
            'gaji' => 'fa-money-bill-wave',
            'salary' => 'fa-dollar-sign',
            'wage' => 'fa-hand-holding-usd',
        ];
        
        // Check exact match first (app name only)
        if (isset($exactMap[$nama_lower])) {
            return $exactMap[$nama_lower];
        }
        
        // ============================================
        // KEYWORD MATCHING (Priority 2) - checks both name AND description
        // ============================================
        $keywordMap = [
            // SPECIFIC APP KEYWORDS
            'geospatial' => 'fa-map-marked-alt',
            'aplikasi-aplikasi' => 'fa-th-large',
            'pendaftaran' => 'fa-file-signature',
            'program acara' => 'fa-calendar-alt',
            'google work' => 'fa-envelope',
            'perumahan rakyat' => 'fa-home',
            'projek perumahan' => 'fa-building',
            'kewangan' => 'fa-chart-line',
            'maklumat pelajar' => 'fa-user-graduate',
            'kolej keda' => 'fa-university',
            'slip gaji' => 'fa-money-check-alt',
            'ec form' => 'fa-file-alt',
            'sewaan' => 'fa-key',
            'masuk kolej' => 'fa-door-open',
            'laman web' => 'fa-globe',
            'pasini kolej' => 'fa-school',
            'penyewaan' => 'fa-credit-card',
            'bayaran' => 'fa-wallet',
            'premis' => 'fa-store-alt',
            'tanah' => 'fa-map',
            'permohonan bantuan' => 'fa-hands-helping',
            'dokumen digital' => 'fa-file-pdf',
            'sumber tenaga' => 'fa-solar-panel',
            'kemasukan' => 'fa-laptop-house',
            'icu' => 'fa-procedures',
            'mesyuarat' => 'fa-handshake',
            'udn' => 'fa-network-wired',
            'projek penyelidikan' => 'fa-flask',
            'integrasi kementerian' => 'fa-sitemap',
            'aduan awam' => 'fa-clipboard-check',
            'kampung peringkat' => 'fa-chart-pie',
            'profil kampung' => 'fa-chart-bar',
            
            // MEDICAL & HEALTHCARE
            'klinik' => 'fa-clinic-medical',
            'clinic' => 'fa-hospital-user',
            'kesihatan' => 'fa-notes-medical',
            'medical' => 'fa-stethoscope',
            'hospital' => 'fa-hospital',
            'health' => 'fa-heart',
            'patient' => 'fa-procedures',
            'rawatan' => 'fa-briefcase-medical',
            
            // VISITOR & SECURITY
            'daftar masuk' => 'fa-door-open',
            'gate' => 'fa-door-closed',
            'security' => 'fa-shield-alt',
            'keselamatan' => 'fa-user-shield',
            'access' => 'fa-key',
            
            // LAND, SURVEY & PROPERTY
            'ukur' => 'fa-ruler-combined',
            'survey' => 'fa-map',
            'geospatial' => 'fa-map-marked',
            'gis' => 'fa-globe-americas',
            'mapping' => 'fa-search-location',
            
            // UTILITIES & BILLING
            'bil' => 'fa-receipt',
            'bill' => 'fa-file-invoice-dollar',
            'elektrik' => 'fa-bolt',
            'electric' => 'fa-charging-station',
            'air' => 'fa-tint',
            'water' => 'fa-faucet',
            
            // FINANCE & MONEY
            'vot' => 'fa-coins',
            'vote' => 'fa-balance-scale',
            'kaunter' => 'fa-cash-register',
            'counter' => 'fa-calculator',
            'kutipan' => 'fa-hand-holding-usd',
            'collection' => 'fa-donate',
            'cukai' => 'fa-percentage',
            'tax' => 'fa-file-invoice',
            'bayaran' => 'fa-credit-card',
            'payment' => 'fa-money-bill-alt',
            'resit' => 'fa-receipt',
            'receipt' => 'fa-file-invoice',
            
            // ASSET & EQUIPMENT
            'peralatan' => 'fa-toolbox',
            'equipment' => 'fa-tools',
            'alih' => 'fa-dolly',
            'movable' => 'fa-box-open',
            'tak alih' => 'fa-couch',
            'fixed' => 'fa-home',
            'furnitur' => 'fa-chair',
            'furniture' => 'fa-couch',
            'komputer' => 'fa-laptop',
            'computer' => 'fa-desktop',
            
            // STORE & INVENTORY
            'bekalan' => 'fa-truck-loading',
            'supply' => 'fa-boxes',
            'inventori' => 'fa-clipboard-list',
            'stok' => 'fa-box-open',
            'stock' => 'fa-archive',
            'gudang' => 'fa-warehouse',
            
            // HR & EMPLOYEE
            'penjawat' => 'fa-user-tie',
            'pegawai' => 'fa-id-card',
            'officer' => 'fa-user-shield',
            'pekerja' => 'fa-hard-hat',
            'employee' => 'fa-users',
            'rekod perkhidmatan' => 'fa-file-alt',
            'service record' => 'fa-history',
            
            // PAYROLL & BENEFIT
            'emolumen' => 'fa-money-bill-wave',
            'allowance' => 'fa-hand-holding-usd',
            'elaun' => 'fa-donate',
            'caruman' => 'fa-piggy-bank',
            'contribution' => 'fa-hands-helping',
            'kwsp' => 'fa-university',
            'epf' => 'fa-landmark',
            
            // TIME & ATTENDANCE
            'waktu' => 'fa-clock',
            'time' => 'fa-stopwatch',
            'jam' => 'fa-business-time',
            'hours' => 'fa-hourglass-half',
            'scan' => 'fa-fingerprint',
            'punch card' => 'fa-id-card',
            
            // TRAINING & DEVELOPMENT
            'pembangunan' => 'fa-chart-line',
            'development' => 'fa-seedling',
            'kemahiran' => 'fa-user-graduate',
            'skill' => 'fa-brain',
            'sijil' => 'fa-certificate',
            'certificate' => 'fa-award',
            
            // PERFORMANCE & EVALUATION
            'prestasi' => 'fa-trophy',
            'performance' => 'fa-chart-bar',
            'kpi' => 'fa-bullseye',
            'target' => 'fa-crosshairs',
            'skor' => 'fa-star',
            'score' => 'fa-percentage',
            
            // DOCUMENT & RECORDS
            'fail' => 'fa-folder',
            'file' => 'fa-file-alt',
            'rekod' => 'fa-book',
            'record' => 'fa-database',
            'arkib' => 'fa-archive',
            'archive' => 'fa-box-open',
            'emel' => 'fa-envelope',
            
            // MEETING & BOOKING
            'bilik' => 'fa-door-open',
            'room' => 'fa-building',
            'tempahan' => 'fa-calendar-check',
            'booking' => 'fa-calendar-plus',
            'dewan' => 'fa-landmark',
            'hall' => 'fa-home',
            
            // COMPLAINT & SERVICE
            'khidmat pelanggan' => 'fa-concierge-bell',
            'customer service' => 'fa-user-circle',
            'maklumbalas' => 'fa-comment-alt',
            'tiket' => 'fa-ticket-alt',
            'ticket' => 'fa-tags',
            
            // PROJECT & TASK
            'projek' => 'fa-project-diagram',
            'project' => 'fa-tasks',
            'tugasan' => 'fa-clipboard-list',
            'task' => 'fa-list-check',
            'workflow' => 'fa-sitemap',
            
            // VEHICLE & LOGISTICS
            'pemandu' => 'fa-id-card',
            'driver' => 'fa-user-tie',
            'logistik' => 'fa-truck',
            'logistics' => 'fa-shipping-fast',
            'penghantaran' => 'fa-dolly-flatbed',
            'delivery' => 'fa-truck-moving',
            
            // COMMUNICATION & BROADCAST
            'siaran' => 'fa-rss',
            'broadcast' => 'fa-broadcast-tower',
            'streaming' => 'fa-video',
            'live' => 'fa-signal',
            'berita' => 'fa-newspaper',
            'news' => 'fa-rss-square',
            
            // GENERAL SYSTEM
            'sistem' => 'fa-cogs',
            'system' => 'fa-server',
            'aplikasi' => 'fa-window-maximize',
            'application' => 'fa-desktop',
            'platform' => 'fa-layer-group',
            'modul' => 'fa-puzzle-piece',
            'module' => 'fa-th',
        ];
        
        // Check combined (name + description) for keyword matches
        foreach ($keywordMap as $keyword => $icon) {
            if (strpos($combined, $keyword) !== false) {
                return $icon;
            }
        }
        
        // Default icon for unknown applications
        return 'fa-desktop';
    }
    
    // Function to get unique vibrant color for each app (BRIGHT COLORS like chart)
    // Get application category color based on id_kategori
    function getAppColor($nama_aplikasi, $id_kategori = null) {
        // Gradient colors by CATEGORY (Option 1: Clean & Professional)
        // id_kategori: 1 = Dalaman (Yellow), 2 = Luaran (Red), 3 = Gunasama (Blue)
        
        $categoryColors = [
            1 => ['#F59E0B', '#D97706'], // Aplikasi Dalaman - Amber/Yellow Gradient
            2 => ['#EF4444', '#DC2626'], // Aplikasi Luaran - Red Gradient
            3 => ['#4169E1', '#1E40AF'], // Aplikasi Gunasama - Blue Gradient
        ];
        
        // Return category color, or default to blue if not specified
        return $categoryColors[$id_kategori] ?? ['#4169E1', '#1E40AF'];
    }
    ?>

    <!-- Pengenalan: senarai gabungan aplikasi jabatan + no-code, dipisahkan mengikut kategori -->
    <div class="alert alert-light border mb-4 py-2 small">
        <i class="fas fa-info-circle me-2 text-primary"></i>
        Senarai ini menggabungkan <strong>aplikasi jabatan</strong> (jadual <code>aplikasi</code>) dan <strong>aplikasi no-code</strong> (jadual <code>custom_apps</code>). Aplikasi no-code menggunakan pautan <code>/myapps/apps/[app_slug]</code>. Dipisahkan mengikut kategori: <strong>Dalaman</strong>, <strong>Luaran</strong>, <strong>Gunasama</strong>.
    </div>

    <!-- APLIKASI DALAMAN (id_kategori = 1) -->
    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-dark">
            <i class="fas fa-cube me-2" style="color: #FFD700;"></i>Aplikasi Dalaman
        </h4>
        <div class="row g-2">
            <?php if (empty($aplikasiDalaman)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tiada aplikasi dalaman buat masa ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($aplikasiDalaman as $app): 
                    $appColors = getAppColor($app['nama_aplikasi'], 1);
                ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="<?php echo htmlspecialchars($app['url']); ?>"<?php if (!empty($app['is_nocode'])): ?> class="text-decoration-none"<?php else: ?> target="_blank" class="text-decoration-none"<?php endif; ?>>
                            <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%); flex-shrink: 0;">
                                            <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
            </div>
        </div>
                                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                        <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                    </p>
                                    <?php if ($app['sso_comply'] == 1): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                    <?php endif; ?>
                </div>
            </div>
                        </a>
        </div>
                <?php endforeach; ?>
            <?php endif; ?>
    </div>
</div>

    <!-- APLIKASI LUARAN (id_kategori = 2) -->
    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-dark">
            <i class="fas fa-globe me-2" style="color: #FF4444;"></i>Aplikasi Luaran
        </h4>
        <div class="row g-2">
            <?php if (empty($aplikasiLuaran)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tiada aplikasi luaran buat masa ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($aplikasiLuaran as $app): 
                    $appColors = getAppColor($app['nama_aplikasi'], 2);
                ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="<?php echo htmlspecialchars($app['url']); ?>"<?php if (!empty($app['is_nocode'])): ?> class="text-decoration-none"<?php else: ?> target="_blank" class="text-decoration-none"<?php endif; ?>>
                            <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%); flex-shrink: 0;">
                                            <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                        <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                    </p>
                                    <?php if ($app['sso_comply'] == 1): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
            </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- APLIKASI GUNASAMA (id_kategori = 3) -->
    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-dark">
            <i class="fas fa-share-alt me-2" style="color: #4169E1;"></i>Aplikasi Gunasama
        </h4>
        <div class="row g-2">
            <?php if (empty($aplikasiGunasama)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tiada aplikasi gunasama buat masa ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($aplikasiGunasama as $app): 
                    $appColors = getAppColor($app['nama_aplikasi'], 3);
                ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="<?php echo htmlspecialchars($app['url']); ?>"<?php if (!empty($app['is_nocode'])): ?> class="text-decoration-none"<?php else: ?> target="_blank" class="text-decoration-none"<?php endif; ?>>
                            <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%); flex-shrink: 0;">
                                            <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                        <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                    </p>
                                    <?php if ($app['sso_comply'] == 1): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
            </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
        </div><!-- End TAB: SEMUA -->
        
        <!-- TAB: DALAMAN ONLY -->
        <div class="tab-pane fade show active" id="dalaman" role="tabpanel">
            <div class="mb-5">
                <div class="row g-2">
                    <?php if (empty($aplikasiDalaman)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Tiada aplikasi dalaman buat masa ini.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($aplikasiDalaman as $app): 
                            $appColors = getAppColor($app['nama_aplikasi'], 1);
                        ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <a href="<?php echo htmlspecialchars($app['url']); ?>"<?php if (!empty($app['is_nocode'])): ?> class="text-decoration-none"<?php else: ?> target="_blank" class="text-decoration-none"<?php endif; ?>>
                                    <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%);">
                                                    <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                                <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                            </p>
                                            <?php if ($app['sso_comply'] == 1): ?>
                                                <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End TAB: DALAMAN -->
        
        <!-- TAB: LUARAN ONLY -->
        <div class="tab-pane fade" id="luaran" role="tabpanel">
            <div class="mb-5">
                <div class="row g-2">
                    <?php if (empty($aplikasiLuaran)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Tiada aplikasi luaran buat masa ini.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($aplikasiLuaran as $app): 
                            $appColors = getAppColor($app['nama_aplikasi'], 2);
                        ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <a href="<?php echo htmlspecialchars($app['url']); ?>"<?php if (!empty($app['is_nocode'])): ?> class="text-decoration-none"<?php else: ?> target="_blank" class="text-decoration-none"<?php endif; ?>>
                                    <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%);">
                                                    <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                                <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                            </p>
                                            <?php if ($app['sso_comply'] == 1): ?>
                                                <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
    </div>
</div>
        </div><!-- End TAB: LUARAN -->
        
        <!-- TAB: GUNASAMA ONLY -->
        <div class="tab-pane fade" id="gunasama" role="tabpanel">
            <div class="mb-5">
                <div class="row g-2">
                    <?php if (empty($aplikasiGunasama)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Tiada aplikasi gunasama buat masa ini.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($aplikasiGunasama as $app): 
                            $appColors = getAppColor($app['nama_aplikasi'], 3);
                        ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <a href="<?php echo htmlspecialchars($app['url']); ?>"<?php if (!empty($app['is_nocode'])): ?> class="text-decoration-none"<?php else: ?> target="_blank" class="text-decoration-none"<?php endif; ?>>
                                    <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%);">
                                                    <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                                <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                            </p>
                                            <?php if ($app['sso_comply'] == 1): ?>
                                                <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End TAB: GUNASAMA -->
        
    </div><!-- End Tab Content -->
    
    <!-- Direktori Aplikasi List View - Muncul di bawah kad-kad apabila kad "Senarai Aplikasi" diklik -->
    <div id="direktoriAplikasiContainer" style="display: none; margin-top: 2rem;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>Senarai Aplikasi
                </h5>
            </div>
            <div class="card-body">
                <!-- Nav Tabs untuk Direktori -->
                <ul class="nav nav-tabs mb-4" role="tablist" id="direktoriTabs">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="direktori-semua-tab" data-bs-toggle="tab" href="#direktori-semua" role="tab" onclick="loadDirektoriTab('')">
                            <i class="fas fa-th fa-lg text-success me-2"></i>Semua Aplikasi (<?php echo $direktori_total_records; ?>)
                        </a>
                    </li>
                    <?php foreach ($direktori_kategoriList as $kat): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="direktori-kat-<?php echo $kat['id_kategori']; ?>-tab" data-bs-toggle="tab" href="#direktori-kat-<?php echo $kat['id_kategori']; ?>" role="tab">
                            <?php
                            $icon = 'fa-th'; $color = 'text-success';
                            if ($kat['id_kategori'] == 1) { $icon = 'fa-cube'; $color = 'text-warning'; }
                            elseif ($kat['id_kategori'] == 2) { $icon = 'fa-globe'; $color = 'text-danger'; }
                            elseif ($kat['id_kategori'] == 3) { $icon = 'fa-share-alt'; $color = 'text-primary'; }
                            ?>
                            <i class="fas <?php echo $icon; ?> fa-lg <?php echo $color; ?> me-2"></i><?php echo htmlspecialchars($kat['nama_kategori']); ?> (<?php echo $direktori_allApps[$kat['id_kategori']] ?? 0; ?>)
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab Content untuk Direktori -->
                <div class="tab-content" id="direktoriTabContent">
                    <!-- Semua Aplikasi Tab -->
                    <div class="tab-pane fade show active" id="direktori-semua" role="tabpanel">
                        <!-- Search Card -->
                        <div class="card shadow-sm mb-4 border-0" style="background-color: #f8f9fa;">
                            <div class="card-body">
                                <form method="get" class="mb-0" id="direktoriSearchForm" action="#direktoriAplikasiContainer">
                                    <input type="hidden" name="direktori_kategori" value="<?php echo htmlspecialchars($direktori_kategori); ?>">
                                    <input type="hidden" name="direktori_sort" value="<?php echo htmlspecialchars($direktori_sort); ?>">
                                    <input type="hidden" name="direktori_order" value="<?php echo htmlspecialchars($direktori_order); ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex flex-grow-1 gap-2">
                                            <input type="text" name="direktori_search" class="form-control" placeholder="Cari nama aplikasi, kategori, atau keterangan..." value="<?php echo htmlspecialchars($direktori_search); ?>">
                                            <button class="btn btn-primary d-flex align-items-center justify-content-center" type="submit" style="min-width:120px;">
                                                <span class="me-2"><i class="fas fa-search"></i></span>
                                                <span>Cari</span>
                                            </button>
                                        </div>
                                        <div class="d-flex gap-2 ms-2">
                                            <?php 
                                            // Check if user is admin or super_admin
                                            $checkAdmin = $db->prepare("SELECT COUNT(*) as cnt FROM user_roles ur 
                                                                        JOIN roles r ON ur.id_role = r.id_role 
                                                                        WHERE ur.id_user = ? AND r.name IN ('admin', 'super_admin')");
                                            $checkAdmin->execute([$_SESSION['user_id']]);
                                            $is_admin = $checkAdmin->fetch()['cnt'] > 0;
                                            ?>
                                            <?php if($is_admin): ?>
                                                <?php
                                                $tambahParams = http_build_query([
                                                    'direktori_search' => $direktori_search,
                                                    'direktori_kategori' => $direktori_kategori,
                                                    'direktori_sort' => $direktori_sort,
                                                    'direktori_order' => $direktori_order,
                                                    'direktori_page' => $direktori_page
                                                ]);
                                                ?>
                                                <button type="button" class="btn btn-primary" onclick="openAplikasiAddModal()"><i class="fas fa-plus"></i> Tambah Aplikasi</button>
                                            <?php endif; ?>
                                            <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'export_data')): ?>
                                                <a href="dashboard_aplikasi.php?export=1<?php echo ($direktori_kategori !== '') ? '&direktori_kategori=' . urlencode($direktori_kategori) : ''; ?>" class="btn btn-success" target="_blank"><i class="fas fa-file-excel"></i> Export Excel</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Table View -->
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="bg-light text-uppercase small">
                                    <tr>
                                        <th class="py-3 px-3 text-center" width="5%">BIL</th>
                                        <th class="py-3">APLIKASI <?php echo direktoriSortLink('nama_aplikasi', $direktori_sort, $direktori_order, $direktori_search, $direktori_kategori); ?></th>
                                        <th class="py-3">KATEGORI <?php echo direktoriSortLink('kategori', $direktori_sort, $direktori_order, $direktori_search, $direktori_kategori); ?></th>
                                        <th class="py-3">KETERANGAN <?php echo direktoriSortLink('keterangan', $direktori_sort, $direktori_order, $direktori_search, $direktori_kategori); ?></th>
                                        <th class="py-3 text-center">SSO <?php echo direktoriSortLink('sso_comply', $direktori_sort, $direktori_order, $direktori_search, $direktori_kategori); ?></th>
                                        <th class="py-3 text-center px-3">TINDAKAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($direktori_data) > 0): ?>
                                        <?php $bil = ($direktori_page - 1) * $direktori_items_per_page + 1; foreach($direktori_data as $row): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-muted">
                                                <?php echo $bil++; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($row['url']); ?>"<?php echo !empty($row['is_nocode']) ? '' : ' target="_blank"'; ?> class="nama-link" style="color: #0d6efd; font-weight: 600; text-decoration: none;">
                                                    <?php echo htmlspecialchars($row['nama_aplikasi']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php
                                                // Get color based on kategori id
                                                $badgeColor = '#007bff'; // Default blue
                                                if ($row['id_kategori'] == 1) $badgeColor = '#F59E0B'; // Orange for Dalaman
                                                elseif ($row['id_kategori'] == 2) $badgeColor = '#EF4444'; // Red for Luaran
                                                elseif ($row['id_kategori'] == 3) $badgeColor = '#4169E1'; // Blue for Gunasama
                                                ?>
                                                <span class="badge" style="background-color: <?php echo $badgeColor; ?>; color: white;">
                                                    <?php echo htmlspecialchars($row['nama_kategori'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo htmlspecialchars(substr($row['keterangan'] ?? '', 0, 60)); ?>
                                                <?php echo strlen($row['keterangan'] ?? '') > 60 ? '...' : ''; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($row['sso_comply'] == 1): ?>
                                                    <span class="badge bg-success">✓ SSO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center px-3">
                                                <?php if (!empty($row['is_nocode'])): ?>
                                                    <a href="<?php echo htmlspecialchars($row['url']); ?>" class="btn btn-sm btn-outline-primary" title="Buka borang"><i class="fas fa-external-link-alt"></i></a>
                                                <?php else: ?>
                                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_application')): ?>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="openAplikasiEditModal(<?php echo (int)$row['id_aplikasi']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_aplikasi'])); ?>', <?php echo (int)$row['id_kategori']; ?>, '<?php echo htmlspecialchars(addslashes($row['keterangan'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($row['url'] ?? '')); ?>', <?php echo (int)($row['sso_comply'] ?? 0); ?>, <?php echo (int)($row['status'] ?? 1); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <?php endif; ?>
                                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'delete_application')): ?>
                                                        <a href="javascript:void(0);" class="btn btn-sm btn-danger" title="Padam" onclick="confirmDeleteAplikasi(<?php echo (int)$row['id_aplikasi']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_aplikasi'])); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Tiada aplikasi dijumpai.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINATION -->
                        <?php if($direktori_total_pages > 1): ?>
                        <nav aria-label="Navigasi Halaman" class="p-3 border-top">
                            <ul class="pagination mb-0 justify-content-center">
                                <li class="page-item <?php echo $direktori_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['direktori_page' => $direktori_page - 1])); ?>#direktoriAplikasiContainer">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $direktori_total_pages; $i++): ?>
                                    <li class="page-item <?php echo $direktori_page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['direktori_page' => $i])); ?>#direktoriAplikasiContainer">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $direktori_page >= $direktori_total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['direktori_page' => $direktori_page + 1])); ?>#direktoriAplikasiContainer">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>

                    <!-- Tab untuk setiap kategori dalam direktori -->
                    <?php foreach ($direktori_kategoriList as $kat): 
                        // Query untuk aplikasi dalam kategori ini
                        $direktori_sqlKat = "SELECT a.*, k.nama_kategori 
                                          FROM aplikasi a 
                                          LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
                                          WHERE a.status = 1 AND a.id_kategori = ?
                                          ORDER BY a.id_aplikasi ASC";
                        $direktori_stmtKat = $db->prepare($direktori_sqlKat);
                        $direktori_stmtKat->execute([$kat['id_kategori']]);
                        $direktori_dataKat = $direktori_stmtKat->fetchAll();
                    ?>
                    <div class="tab-pane fade" id="direktori-kat-<?php echo $kat['id_kategori']; ?>" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="bg-light text-uppercase small">
                                    <tr>
                                        <th class="py-3 px-3 text-center" width="5%">BIL</th>
                                        <th class="py-3">APLIKASI</th>
                                        <th class="py-3">KATEGORI</th>
                                        <th class="py-3">KETERANGAN</th>
                                        <th class="py-3 text-center">SSO</th>
                                        <th class="py-3 text-center px-3">TINDAKAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($direktori_dataKat) > 0): ?>
                                        <?php $bil = 1; foreach($direktori_dataKat as $row): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-muted"><?php echo $bil++; ?></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank" class="nama-link" style="color: #0d6efd; font-weight: 600; text-decoration: none;">
                                                    <?php echo htmlspecialchars($row['nama_aplikasi']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php
                                                // Get color based on kategori id
                                                $badgeColor = '#007bff'; // Default blue
                                                if ($row['id_kategori'] == 1) $badgeColor = '#F59E0B'; // Orange for Dalaman
                                                elseif ($row['id_kategori'] == 2) $badgeColor = '#EF4444'; // Red for Luaran
                                                elseif ($row['id_kategori'] == 3) $badgeColor = '#4169E1'; // Blue for Gunasama
                                                ?>
                                                <span class="badge" style="background-color: <?php echo $badgeColor; ?>; color: white;">
                                                    <?php echo htmlspecialchars($row['nama_kategori'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo htmlspecialchars(substr($row['keterangan'] ?? '', 0, 60)); ?>
                                                <?php echo strlen($row['keterangan'] ?? '') > 60 ? '...' : ''; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($row['sso_comply'] == 1): ?>
                                                    <span class="badge bg-success">✓ SSO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center px-3">
                                                <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_application')): ?>
                                                    <button type="button" class="btn btn-sm btn-warning" onclick="openAplikasiEditModal(<?php echo $row['id_aplikasi']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_aplikasi'])); ?>', <?php echo $row['id_kategori']; ?>, '<?php echo htmlspecialchars(addslashes($row['keterangan'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($row['url'] ?? '')); ?>', <?php echo $row['sso_comply'] ?? 0; ?>, <?php echo $row['status'] ?? 1; ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                                <?php endif; ?>
                                                <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'delete_application')): ?>
                                                    <a href="javascript:void(0);" class="btn btn-sm btn-danger" title="Padam" onclick="confirmDeleteAplikasi(<?php echo $row['id_aplikasi']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_aplikasi'])); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Tiada aplikasi dijumpai.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
</div><!-- End Container -->

<style>
/* Summary Card as Clickable Tabs */
.summary-card {
    transition: all 0.3s ease;
    position: relative;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}

.summary-card.active {
    box-shadow: 0 20px 50px rgba(0,0,0,0.4), 0 10px 25px rgba(0,0,0,0.3) !important;
    transform: scale(1.10);
    z-index: 10;
    border: 3px solid rgba(0,0,0,0.2) !important;
}

.summary-card.active:hover {
    transform: scale(1.10) translateY(-3px);
    box-shadow: 0 25px 60px rgba(0,0,0,0.45), 0 15px 30px rgba(0,0,0,0.35) !important;
}

.summary-card.active::after {
    display: none; /* Disable arrow indicator since we're using scale effect */
}

.app-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.app-card .card-body:hover i {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}
</style>

<script>
// Handle active state for summary cards and tab switching
document.addEventListener('DOMContentLoaded', function() {
    const summaryCards = document.querySelectorAll('.summary-card');
    const direktoriContainer = document.getElementById('direktoriAplikasiContainer');
    
    summaryCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            summaryCards.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Get the target tab
            const target = this.getAttribute('data-bs-target');
            
            // Check if clicked card is "Senarai Aplikasi" (target="#semua")
            const isSenaraiAplikasi = target === '#semua';
            
            // Show/hide direktori aplikasi container dan tab content
            if (isSenaraiAplikasi) {
                // Show direktori aplikasi container
                if (direktoriContainer) {
                    direktoriContainer.style.display = 'block';
                    // Add class to body to hide grid cards
                    document.body.classList.add('show-direktori');
                    // Smooth scroll to direktori container
                    setTimeout(() => {
                        direktoriContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
                
                // Hide all tab content (grid cards view) - ensure they're hidden
                document.querySelectorAll('#kategoriTabContent .tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                    pane.style.display = 'none';
                });
            } else {
                // Hide direktori aplikasi container for other cards
                if (direktoriContainer) {
                    direktoriContainer.style.display = 'none';
                }
                
                // Remove class from body to show grid cards
                document.body.classList.remove('show-direktori');
                
                // Show the target tab pane (grid cards view)
                document.querySelectorAll('#kategoriTabContent .tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                    pane.style.display = 'none';
                });
                
                const targetPane = document.querySelector(target);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                    targetPane.style.display = 'block';
                }
            }
        });
    });
    
    // On page load, if "Senarai Aplikasi" card is active or if direktori params exist, show direktori
    const senaraiCard = document.querySelector('.summary-card[data-bs-target="#semua"]');
    const hasDirektoriParams = window.location.search.includes('direktori_') || window.location.hash === '#direktoriAplikasiContainer';
    
    if (hasDirektoriParams && senaraiCard && direktoriContainer) {
        // Paksa aktifkan kad Senarai Aplikasi & tunjuk direktori
        document.querySelectorAll('.summary-card').forEach(c => c.classList.remove('active'));
        senaraiCard.classList.add('active');
        document.body.classList.add('show-direktori');
        direktoriContainer.style.display = 'block';
        document.querySelectorAll('#kategoriTabContent .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
            pane.style.display = 'none';
        });
        setTimeout(() => {
            direktoriContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 200);
    } else if (senaraiCard && senaraiCard.classList.contains('active')) {
        if (direktoriContainer) {
            document.body.classList.add('show-direktori');
            direktoriContainer.style.display = 'block';
            document.querySelectorAll('#kategoriTabContent .tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
                pane.style.display = 'none';
            });
        }
    } else {
        if (direktoriContainer) direktoriContainer.style.display = 'none';
        document.body.classList.remove('show-direktori');
        const dalamanPane = document.querySelector('#dalaman');
        if (dalamanPane) {
            dalamanPane.classList.add('show', 'active');
            dalamanPane.style.display = 'block';
        }
    }
});

// Ensure list view stays visible after form submission
const direktoriSearchForm = document.getElementById('direktoriSearchForm');
if (direktoriSearchForm) {
    direktoriSearchForm.addEventListener('submit', function() {
        // Ensure list view is shown after search
        setTimeout(() => {
            if (direktoriContainer) {
                direktoriContainer.style.display = 'block';
                document.body.classList.add('show-direktori');
                const senaraiCard = document.querySelector('.summary-card[data-bs-target="#semua"]');
                if (senaraiCard) senaraiCard.classList.add('active');
            }
        }, 100);
    });
}

// Function to confirm delete aplikasi
function confirmDeleteAplikasi(id, name) {
    if (confirm("Anda pasti mahu padam aplikasi \"" + name + "\"?\n\nTindakan ini tidak boleh dibatalkan.")) {
        // Redirect to proses_aplikasi.php with delete action, preserve current query params
        const currentUrl = window.location.href;
        window.location.href = "proses_aplikasi.php?action=delete&id=" + id + "&redirect=" + encodeURIComponent(currentUrl);
    }
}
</script>

<!-- Modal Add/Edit Aplikasi -->
<div class="modal fade" id="aplikasiAddEditModal" tabindex="-1" aria-labelledby="aplikasiAddEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="aplikasiAddEditModalLabel">
                    <i class="fas fa-plus me-2"></i><span id="aplikasiModalTitle">Tambah Aplikasi Baharu</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="aplikasiAddEditForm" method="POST">
                    <?php echo getCsrfTokenField(); ?>
                    <input type="hidden" name="id_aplikasi" id="aplikasiFormId">
                    <input type="hidden" name="mode" id="aplikasiFormMode" value="add">
                    
                    <!-- Nama Aplikasi -->
                    <div class="mb-3">
                        <label for="aplikasiFormNama" class="form-label fw-bold">Nama Aplikasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="aplikasiFormNama" name="nama_aplikasi" required placeholder="Cth: MyPPRS KEDA">
                    </div>

                    <!-- Kategori & Warna -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="aplikasiFormKategori" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="aplikasiFormKategori" name="id_kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?php echo $kat['id_kategori']; ?>" data-warna="<?php 
                                        $warna = [1 => '#F39C12', 2 => '#E74C3C', 3 => '#6C3483'];
                                        echo $warna[$kat['id_kategori']] ?? '#007bff';
                                    ?>"><?php echo htmlspecialchars($kat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="aplikasiFormWarna" class="form-label fw-bold">Warna Badge <span class="text-muted small">(Auto)</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="aplikasiFormWarna" value="#007bff" disabled>
                                <span class="input-group-text">
                                    <div id="aplikasiFormWarnaPreview" style="width: 24px; height: 24px; background-color: #007bff; border-radius: 3px;"></div>
                                </span>
                            </div>
                            <small class="text-muted d-block mt-1">Warna akan diatur otomatis berdasarkan kategori</small>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-3">
                        <label for="aplikasiFormKeterangan" class="form-label fw-bold">Keterangan</label>
                        <textarea class="form-control" id="aplikasiFormKeterangan" name="keterangan" rows="3" placeholder="Deskripsi aplikasi..."></textarea>
                    </div>

                    <!-- URL -->
                    <div class="mb-3">
                        <label for="aplikasiFormUrl" class="form-label fw-bold">URL/Link Aplikasi</label>
                        <input type="url" class="form-control" id="aplikasiFormUrl" name="url" placeholder="https://...">
                    </div>

                    <!-- SSO Compliant -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="aplikasiFormSso" name="sso_comply">
                            <label class="form-check-label" for="aplikasiFormSso">
                                <strong>SSO Compliant</strong> - Aplikasi ini menyokong Single Sign-On
                            </label>
                        </div>
                    </div>

                    <!-- Status (hanya untuk edit) -->
                    <div class="mb-3" id="aplikasiFormStatusContainer" style="display: none;">
                        <label for="aplikasiFormStatus" class="form-label fw-bold">Status</label>
                        <select class="form-select" id="aplikasiFormStatus" name="status">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                        <small class="text-muted d-block mt-1">Aplikasi dengan status "Tidak Aktif" tidak akan muncul di Direktori Aplikasi</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="aplikasiFormSubmitBtn">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Kategori warna mapping
const kategoriWarna = {
    1: '#F39C12',  // Dalaman (Orange)
    2: '#E74C3C',  // Luaran (Red)
    3: '#6C3483'   // Gunasama (Purple)
};

// Function untuk buka modal Add Aplikasi
function openAplikasiAddModal() {
    // Reset form
    document.getElementById('aplikasiAddEditForm').reset();
    document.getElementById('aplikasiFormId').value = '';
    document.getElementById('aplikasiFormMode').value = 'add';
    document.getElementById('aplikasiModalTitle').textContent = 'Tambah Aplikasi Baharu';
    document.getElementById('aplikasiFormStatusContainer').style.display = 'none';
    document.getElementById('aplikasiFormWarna').value = '#007bff';
    document.getElementById('aplikasiFormWarnaPreview').style.backgroundColor = '#007bff';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('aplikasiAddEditModal'));
    modal.show();
}

// Function untuk buka modal Edit Aplikasi
function openAplikasiEditModal(id, nama, kategori, keterangan, url, sso, status) {
    if (!id) {
        alert('ID Aplikasi tidak sah!');
        return;
    }
    
    // Populate form
    document.getElementById('aplikasiFormId').value = id;
    document.getElementById('aplikasiFormMode').value = 'edit';
    document.getElementById('aplikasiModalTitle').textContent = 'Edit Aplikasi';
    document.getElementById('aplikasiFormNama').value = nama || '';
    document.getElementById('aplikasiFormKategori').value = kategori || '';
    document.getElementById('aplikasiFormKeterangan').value = keterangan || '';
    document.getElementById('aplikasiFormUrl').value = url || '';
    document.getElementById('aplikasiFormSso').checked = (sso == 1);
    document.getElementById('aplikasiFormStatus').value = status || 1;
    document.getElementById('aplikasiFormStatusContainer').style.display = 'block';
    
    // Update warna berdasarkan kategori
    const warna = kategoriWarna[kategori] || '#007bff';
    document.getElementById('aplikasiFormWarna').value = warna;
    document.getElementById('aplikasiFormWarnaPreview').style.backgroundColor = warna;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('aplikasiAddEditModal'));
    modal.show();
}

// Update warna ketika kategori berubah
document.addEventListener('DOMContentLoaded', function() {
    const kategoriSelect = document.getElementById('aplikasiFormKategori');
    if (kategoriSelect) {
        kategoriSelect.addEventListener('change', function() {
            const selectedKategori = this.value;
            const selectedOption = this.options[this.selectedIndex];
            const warna = selectedOption.getAttribute('data-warna') || kategoriWarna[selectedKategori] || '#007bff';
            
            document.getElementById('aplikasiFormWarna').value = warna;
            document.getElementById('aplikasiFormWarnaPreview').style.backgroundColor = warna;
        });
    }
    
    // Handle form submission
    const aplikasiForm = document.getElementById('aplikasiAddEditForm');
    const submitBtn = document.getElementById('aplikasiFormSubmitBtn');
    
    if (submitBtn && aplikasiForm) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!aplikasiForm.checkValidity()) {
                aplikasiForm.reportValidity();
                return;
            }
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            
            // Create FormData
            const formData = new FormData(aplikasiForm);
            const mode = document.getElementById('aplikasiFormMode').value;
            
            if (mode === 'add') {
                // Add mode - no id needed
            } else {
                formData.append('id_aplikasi', document.getElementById('aplikasiFormId').value);
            }
            
            // Submit via AJAX
            fetch('proses_aplikasi.php' + (mode === 'edit' ? '?id=' + document.getElementById('aplikasiFormId').value : ''), {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Extract message from alert script if present
                const successMatch = data.match(/alert\(['"]([^'"]+)['"]\)/);
                const message = successMatch ? successMatch[1] : '';
                
                // Check if response contains success message
                if (data.includes('Berjaya') || data.includes('berjaya') || message.includes('Berjaya')) {
                    // Close modal first
                    const modal = bootstrap.Modal.getInstance(document.getElementById('aplikasiAddEditModal'));
                    if (modal) modal.hide();
                    
                    // Show success message
                    if (message) {
                        alert(message);
                    }
                    
                    // Reload page to show updated data
                    setTimeout(() => {
                        const currentHash = window.location.hash;
                        const currentParams = window.location.search;
                        window.location.href = 'dashboard_aplikasi.php' + currentParams + currentHash;
                    }, 500);
                } else if (data.includes('Gagal') || data.includes('gagal') || message.includes('Gagal')) {
                    alert(message || 'Gagal menyimpan data! Sila cuba lagi.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
                } else {
                    // Try to parse as JSON if possible
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('aplikasiAddEditModal'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                const currentHash = window.location.hash;
                                const currentParams = window.location.search;
                                window.location.href = 'dashboard_aplikasi.php' + currentParams + currentHash;
                            }, 500);
                        } else {
                            alert(jsonData.message || 'Gagal menyimpan data!');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
                        }
                    } catch (e) {
                        // Not JSON, might be HTML response
                        if (!data.includes('error') && !data.includes('Gagal')) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('aplikasiAddEditModal'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                const currentHash = window.location.hash;
                                const currentParams = window.location.search;
                                window.location.href = 'dashboard_aplikasi.php' + currentParams + currentHash;
                            }, 500);
                        } else {
                            alert('Gagal menyimpan data! Sila cuba lagi.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error submitting form:', error);
                alert('Ralat menghantar data! Sila cuba lagi.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
            });
        });
    }
});
</script>

</body>
</html>

<?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'view_dashboard')): ?>
<!-- Paparan dashboard aplikasi di sini -->
<?php endif; ?>
