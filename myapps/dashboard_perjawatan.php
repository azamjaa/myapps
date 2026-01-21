<?php
require 'db.php';

// ============================================================
// EXPORT EXCEL FUNCTIONALITY
// ============================================================
if (isset($_GET['export'])) {
    if (ob_get_length()) ob_end_clean();

    // Get status from parameter (prefer explicit ?status=, fallback to ?direktori_staf_status=), default to 1
    $export_status = isset($_GET['status'])
        ? (int)$_GET['status']
        : (isset($_GET['direktori_staf_status']) ? (int)$_GET['direktori_staf_status'] : 1);

    // Validate allowed status
    $allowed_export_status = [1, 2, 3];
    if (!in_array($export_status, $allowed_export_status, true)) {
        $export_status = 1;
    }
    
    // Map status to label for filename
    $status_labels = [
        1 => 'Masih_Bekerja',
        2 => 'Bersara',
        3 => 'Berhenti'
    ];
    $status_label = $status_labels[$export_status];
    
    $filename = "Direktori_Staf_" . $status_label . "_" . date('Ymd') . ".xls";

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/>
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000000; padding: 5px; }
                th { background-color: #d32f2f; color: white; }
            </style>
          </head><body>';
    
    echo '<table>';
    echo '<tr>
            <th>NAMA</th>
            <th>JAWATAN</th>
            <th>SKIM</th>
            <th>GRED</th>
            <th>BAHAGIAN</th>
            <th>EMEL</th>
          </tr>';
    
    // QUERY EXPORT - Filter by status
    $sqlExport = "SELECT u.nama, u.emel, 
                    j.jawatan, j.skim, g.gred, b.bahagian 
                FROM users u 
                LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
                LEFT JOIN gred g ON u.id_gred = g.id_gred
                LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
                WHERE u.id_status_staf = ?
                ORDER BY u.nama ASC";
    $stmt = $db->prepare($sqlExport);
    $stmt->execute([$export_status]);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['nama'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['jawatan'] ?? '') . '</td>';
        echo '<td style="text-align:center;">' . htmlspecialchars($row['skim'] ?? '') . '</td>';
        echo '<td style="text-align:center;">' . htmlspecialchars($row['gred'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['bahagian'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['emel'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit(); 
}

include 'header.php';

// Statistik
// Kiraan staf mengikut status
$cntStaf = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 1")->fetchColumn();
$cntStafBersara = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 2")->fetchColumn();
$cntStafBerhenti = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 3")->fetchColumn();
$cntJawatan = $db->query("SELECT COUNT(DISTINCT id_jawatan) FROM users WHERE id_status_staf = 1")->fetchColumn();
$cntBahagian = $db->query("SELECT COUNT(DISTINCT id_bahagian) FROM users WHERE id_status_staf = 1")->fetchColumn();
$bulanIni = date('m');
// Fixed: Use prepared statement instead of string concatenation
$stmtBirthday = $db->prepare("SELECT COUNT(*) FROM users WHERE SUBSTRING(no_kp, 3, 2) = ? AND id_status_staf = 1");
$stmtBirthday->execute([$bulanIni]);
$cntBirthday = $stmtBirthday->fetchColumn();

// Kategori Gred
$cntTertinggi = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 1 AND CAST(id_gred AS UNSIGNED) >= 15")->fetchColumn();
$cntPengurusan = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 1 AND CAST(id_gred AS UNSIGNED) BETWEEN 9 AND 14")->fetchColumn();
$cntSokongan1 = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 1 AND CAST(id_gred AS UNSIGNED) BETWEEN 5 AND 8")->fetchColumn();
$cntSokongan2 = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 1 AND CAST(id_gred AS UNSIGNED) BETWEEN 1 AND 4")->fetchColumn();

// Data Chart
$chartBahagian = $db->query("SELECT b.bahagian, COUNT(u.id_user) as total FROM users u JOIN bahagian b ON u.id_bahagian = b.id_bahagian WHERE u.id_status_staf = 1 GROUP BY b.bahagian ORDER BY total DESC")->fetchAll();
// Ambil semua bahagian untuk graf donut (peratus keseluruhan)
$chartBahagianTop = $chartBahagian; // tidak dihadkan, guna semua bahagian
$totalBahagianTop = array_sum(array_column($chartBahagianTop, 'total'));

// Data Chart untuk Jawatan
$chartJawatanData = $db->query("SELECT COALESCE(j.jawatan, 'Tidak Dinyatakan') AS jawatan, COUNT(u.id_user) as total FROM users u LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan WHERE u.id_status_staf = 1 GROUP BY j.jawatan ORDER BY total DESC")->fetchAll();
$totalJawatan = array_sum(array_column($chartJawatanData, 'total'));

// Function untuk dapatkan jawatan mengikut gred range
function getJawatanByGredRange($db, $min, $max = null) {
    $sql = "SELECT COALESCE(j.jawatan, 'Tidak Dinyatakan') AS jawatan, COUNT(u.id_user) as total 
            FROM users u 
            LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan 
            WHERE u.id_status_staf = 1 AND CAST(u.id_gred AS UNSIGNED) >= ?";
    $params = [$min];
    if ($max !== null) {
        $sql .= " AND CAST(u.id_gred AS UNSIGNED) <= ?";
        $params[] = $max;
    }
    $sql .= " GROUP BY j.jawatan ORDER BY total DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Data Jawatan mengikut kategori gred
$chartTertinggiJawatan = getJawatanByGredRange($db, 15, null); // >=15
$totalTertinggiJawatan = array_sum(array_column($chartTertinggiJawatan, 'total'));

$chartPengurusanJawatan = getJawatanByGredRange($db, 9, 14); // 9-14
$totalPengurusanJawatan = array_sum(array_column($chartPengurusanJawatan, 'total'));

$chartSokongan1Jawatan = getJawatanByGredRange($db, 5, 8); // 5-8
$totalSokongan1Jawatan = array_sum(array_column($chartSokongan1Jawatan, 'total'));

$chartSokongan2Jawatan = getJawatanByGredRange($db, 1, 4); // 1-4
$totalSokongan2Jawatan = array_sum(array_column($chartSokongan2Jawatan, 'total'));

// Data Chart Bahagian mengikut kategori gred
function getBahagianByGredRange($db, $min, $max = null) {
    $sql = "SELECT b.bahagian, COUNT(u.id_user) as total 
            FROM users u 
            JOIN bahagian b ON u.id_bahagian = b.id_bahagian 
            WHERE u.id_status_staf = 1 AND CAST(u.id_gred AS UNSIGNED) >= ?";
    $params = [$min];
    if ($max !== null) {
        $sql .= " AND CAST(u.id_gred AS UNSIGNED) <= ?";
        $params[] = $max;
    }
    $sql .= " GROUP BY b.bahagian ORDER BY total DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$chartTertinggi = getBahagianByGredRange($db, 15, null); // >=15
$totalTertinggi = array_sum(array_column($chartTertinggi, 'total'));

$chartPengurusan = getBahagianByGredRange($db, 9, 14); // 9-14
$totalPengurusan = array_sum(array_column($chartPengurusan, 'total'));

$chartSokongan1 = getBahagianByGredRange($db, 5, 8); // 5-8
$totalSokongan1 = array_sum(array_column($chartSokongan1, 'total'));

$chartSokongan2 = getBahagianByGredRange($db, 1, 4); // 1-4
$totalSokongan2 = array_sum(array_column($chartSokongan2, 'total'));

// Data untuk Direktori Staf List View
$direktori_staf_search = $_GET['direktori_staf_search'] ?? '';
$direktori_staf_sort = $_GET['direktori_staf_sort'] ?? 'nama';
$direktori_staf_order = $_GET['direktori_staf_order'] ?? 'ASC';
$direktori_staf_page = isset($_GET['direktori_staf_page']) ? max(1, intval($_GET['direktori_staf_page'])) : 1;
$direktori_staf_status = isset($_GET['direktori_staf_status']) ? intval($_GET['direktori_staf_status']) : 1;

$allowed_staf_sort = ['no_kp', 'nama', 'jawatan', 'skim', 'gred', 'bahagian', 'emel', 'telefon'];
if (!in_array($direktori_staf_sort, $allowed_staf_sort)) { $direktori_staf_sort = 'nama'; }

$allowed_staf_status = [1, 2, 3];
if (!in_array($direktori_staf_status, $allowed_staf_status)) { $direktori_staf_status = 1; }

$direktori_staf_items_per_page = 20;
$direktori_staf_offset = ($direktori_staf_page - 1) * $direktori_staf_items_per_page;

// Query count untuk direktori staf
$direktori_staf_sqlCount = "SELECT COUNT(*) as total FROM users u 
                            LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
                            LEFT JOIN gred g ON u.id_gred = g.id_gred
                            LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
                            WHERE u.id_status_staf = ?";

if (!empty($direktori_staf_search)) {
    $direktori_staf_sqlCount .= " AND (
        u.nama LIKE ? OR 
        u.no_kp LIKE ? OR 
        j.jawatan LIKE ? OR 
        j.skim LIKE ? OR 
        b.bahagian LIKE ? OR 
        g.gred LIKE ?
    )";
}

$direktori_staf_stmt = $db->prepare($direktori_staf_sqlCount);
if (!empty($direktori_staf_search)) {
    $searchParam = "%$direktori_staf_search%";
    $direktori_staf_stmt->execute([$direktori_staf_status, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
} else {
    $direktori_staf_stmt->execute([$direktori_staf_status]);
}
$direktori_staf_total_records = $direktori_staf_stmt->fetch()['total'];
$direktori_staf_total_pages = ceil($direktori_staf_total_records / $direktori_staf_items_per_page);

if ($direktori_staf_page > $direktori_staf_total_pages && $direktori_staf_total_pages > 0) {
    $direktori_staf_page = $direktori_staf_total_pages;
    $direktori_staf_offset = ($direktori_staf_page - 1) * $direktori_staf_items_per_page;
}

// Query staf untuk direktori
$direktori_staf_sql = "SELECT u.*, j.jawatan, j.skim, g.gred, b.bahagian 
                       FROM users u 
                       LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
                       LEFT JOIN gred g ON u.id_gred = g.id_gred
                       LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
                       WHERE u.id_status_staf = ?";

if (!empty($direktori_staf_search)) {
    $direktori_staf_sql .= " AND (
        u.nama LIKE ? OR 
        u.no_kp LIKE ? OR 
        j.jawatan LIKE ? OR 
        j.skim LIKE ? OR 
        b.bahagian LIKE ? OR 
        g.gred LIKE ?
    )";
}

$direktori_staf_sql .= " ORDER BY $direktori_staf_sort $direktori_staf_order LIMIT $direktori_staf_items_per_page OFFSET $direktori_staf_offset";

$direktori_staf_stmt = $db->prepare($direktori_staf_sql);
if (!empty($direktori_staf_search)) {
    $searchParam = "%$direktori_staf_search%";
    $direktori_staf_stmt->execute([$direktori_staf_status, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
} else {
    $direktori_staf_stmt->execute([$direktori_staf_status]);
}
$direktori_staf_data = $direktori_staf_stmt->fetchAll();

// Function untuk sort link dalam direktori staf
function direktoriStafSortLink($col, $currentSort, $currentOrder, $currentSearch, $currentStatus) {
    $newOrder = ($currentSort == $col && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort == $col) ? (($currentOrder == 'ASC') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted opacity-25"></i>';
    $params = http_build_query([
        'direktori_staf_search' => $currentSearch,
        'direktori_staf_status' => $currentStatus,
        'direktori_staf_sort' => $col,
        'direktori_staf_order' => $newOrder,
        'direktori_staf_page' => 1
    ]);
    return "<a href='?$params#direktoriStafContainer' class='text-dark text-decoration-none fw-bold'>$icon</a>";
}

// Get last updated date from users table
$lastUpdated = null;
try {
    // Try to get MAX(updated_at) first
    $dateStmt = $db->query("SELECT MAX(updated_at) as last_updated FROM users WHERE id_status_staf = 1");
    $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
    if ($dateRow && $dateRow['last_updated']) {
        $lastUpdated = $dateRow['last_updated'];
    } else {
        // Fallback to created_at if updated_at doesn't exist
        try {
            $dateStmt = $db->query("SELECT MAX(created_at) as last_updated FROM users WHERE id_status_staf = 1");
            $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
            if ($dateRow && $dateRow['last_updated']) {
                $lastUpdated = $dateRow['last_updated'];
            }
        } catch (Exception $e2) {
            // If created_at also doesn't exist, try to get from audit_log
            try {
                $auditStmt = $db->query("SELECT MAX(created_at) as last_updated FROM audit_log WHERE table_affected = 'users' ORDER BY created_at DESC LIMIT 1");
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
        $dateStmt = $db->query("SELECT MAX(created_at) as last_updated FROM users WHERE id_status_staf = 1");
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
    <style>
    .summary-card-staf {
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        cursor: pointer;
    }
    .summary-card-staf:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    }
    .summary-card-staf.active {
        box-shadow: 0 20px 50px rgba(0,0,0,0.4), 0 10px 25px rgba(0,0,0,0.3) !important;
        transform: scale(1.10);
        z-index: 10;
        border: 3px solid rgba(0,0,0,0.2) !important;
    }
    .summary-card-staf.active:hover {
        transform: scale(1.10) translateY(-3px);
        box-shadow: 0 25px 60px rgba(0,0,0,0.45), 0 15px 30px rgba(0,0,0,0.35) !important;
    }

    /* Gaya tab sama seperti Senarai Aplikasi */
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
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-tachometer-alt me-3 text-primary"></i>Dashboard Perjawatan</h3>
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

    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'view_dashboard')): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardStaf" style="border-left: 5px solid #10B981 !important; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Staf</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntStaf; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); flex-shrink: 0;">
                        <i class="fas fa-users fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardJawatan" style="border-left: 5px solid #F59E0B !important; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Jawatan</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntJawatan; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); flex-shrink: 0;">
                        <i class="fas fa-briefcase fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardBahagian" style="border-left: 5px solid #EF4444 !important; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%) !important; cursor: pointer;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Bahagian</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntBahagian; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); flex-shrink: 0;">
                        <i class="fas fa-building-columns fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardBirthday" style="border-left: 5px solid #4169E1 !important; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Hari Lahir (Bulan Ini)</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntBirthday; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%); flex-shrink: 0;">
                        <i class="fas fa-cake-candles fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardTertinggi" style="border-left: 5px solid #7c3aed !important; background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Pengurusan Tertinggi</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntTertinggi; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); flex-shrink: 0;">
                        <i class="fas fa-user-tie fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardPengurusan" style="border-left: 5px solid #EA3680 !important; background: linear-gradient(135deg, #ffffff 0%, #dbeafe 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Pengurusan & Profesional</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #EA3680 0%, #EA3680 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntPengurusan; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #EA3680 0%, #EA3680 100%); flex-shrink: 0;">
                        <i class="fas fa-user-graduate fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardSokongan1" style="border-left: 5px solid #EE8AF8 !important; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Sokongan 1</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #EE8AF8 0%, #EE8AF8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntSokongan1; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #EE8AF8 0%, #EE8AF8 100%); flex-shrink: 0;">
                        <i class="fas fa-user-cog fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card-staf" id="cardSokongan2" style="border-left: 5px solid #808080 !important; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Sokongan 2</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #808080 0%, #808080 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntSokongan2; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #808080 0%, #808080 100%); flex-shrink: 0;">
                        <i class="fas fa-user fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Staf -->
    <div class="modal fade" id="stafDetailModal" tabindex="-1" aria-labelledby="stafDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="stafDetailModalLabel">
                        <i class="fas fa-user me-2 text-primary"></i>Butiran Staf
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="mb-2"><strong>Nama</strong></div>
                            <div id="detailNama" class="text-dark"></div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-2"><strong>Gambar</strong></div>
                            <div class="d-flex justify-content-center">
                                <img id="detailPhoto" src="image/mawar.png" alt="Foto Staf" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2"><strong>No. KP</strong></div>
                            <div id="detailNoKp" class="text-dark"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2"><strong>Status Staf</strong></div>
                            <div id="detailStatus" class="badge bg-secondary"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2"><strong>Jawatan</strong></div>
                            <div id="detailJawatan" class="text-dark"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2"><strong>Skim</strong></div>
                            <div id="detailSkim" class="text-dark"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2"><strong>Gred</strong></div>
                            <div id="detailGred" class="text-dark"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2"><strong>Bahagian</strong></div>
                            <div id="detailBahagian" class="text-dark"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2"><strong>Emel</strong></div>
                            <div id="detailEmel" class="text-dark"></div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="mb-2"><strong>Telefon</strong></div>
                            <div id="detailTelefon" class="text-dark"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Direktori Staf List View - Muncul di bawah kad-kad apabila kad "Staf" diklik -->
    <div id="direktoriStafContainer" style="display: none; margin-top: 2rem;">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>Senarai Staf
                </h5>
            </div>
            <div class="card-body">
                <!-- Status Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist" id="direktoriStafTabs">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $direktori_staf_status == 1 ? 'active' : ''; ?>" href="?direktori_staf_status=1<?php echo !empty($direktori_staf_search) ? '&direktori_staf_search=' . urlencode($direktori_staf_search) : ''; ?>#direktoriStafContainer">
                            <i class="fas fa-briefcase fa-lg text-success me-2"></i>Masih Bekerja (<?php echo (int)$cntStaf; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $direktori_staf_status == 2 ? 'active' : ''; ?>" href="?direktori_staf_status=2<?php echo !empty($direktori_staf_search) ? '&direktori_staf_search=' . urlencode($direktori_staf_search) : ''; ?>#direktoriStafContainer">
                            <i class="fas fa-star fa-lg text-warning me-2"></i>Bersara (<?php echo (int)$cntStafBersara; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $direktori_staf_status == 3 ? 'active' : ''; ?>" href="?direktori_staf_status=3<?php echo !empty($direktori_staf_search) ? '&direktori_staf_search=' . urlencode($direktori_staf_search) : ''; ?>#direktoriStafContainer">
                            <i class="fas fa-door-open fa-lg text-danger me-2"></i>Berhenti (<?php echo (int)$cntStafBerhenti; ?>)
                        </a>
                    </li>
                </ul>

                <!-- Search Card -->
                <div class="card shadow-sm mb-4 border-0" style="background-color: #f8f9fa;">
                    <div class="card-body">
                        <form method="get" class="mb-0" id="direktoriStafSearchForm">
                            <input type="hidden" name="direktori_staf_status" value="<?php echo htmlspecialchars($direktori_staf_status); ?>">
                            <input type="hidden" name="direktori_staf_sort" value="<?php echo htmlspecialchars($direktori_staf_sort); ?>">
                            <input type="hidden" name="direktori_staf_order" value="<?php echo htmlspecialchars($direktori_staf_order); ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex flex-grow-1 gap-2">
                                    <input type="text" name="direktori_staf_search" class="form-control" placeholder="Cari nama, jawatan, atau bahagian..." value="<?php echo htmlspecialchars($direktori_staf_search); ?>">
                                    <button class="btn btn-primary d-flex align-items-center justify-content-center" type="submit" style="min-width:120px;">
                                        <span class="me-2"><i class="fas fa-search"></i></span>
                                        <span>Cari</span>
                                    </button>
                                </div>
                                <div class="d-flex gap-2 ms-2">
                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'create_user')): ?>
                                        <a href="proses_staf.php?mode=add" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Staf</a>
                                    <?php endif; ?>
                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'export_data')): ?>
                                        <a href="dashboard_perjawatan.php?export=1&status=<?php echo htmlspecialchars($direktori_staf_status); ?>" class="btn btn-success" target="_blank"><i class="fas fa-file-excel"></i> Export Excel</a>
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
                                <th class="py-3">NAMA <?php echo direktoriStafSortLink('nama', $direktori_staf_sort, $direktori_staf_order, $direktori_staf_search, $direktori_staf_status); ?></th>
                                <th class="py-3">JAWATAN <?php echo direktoriStafSortLink('jawatan', $direktori_staf_sort, $direktori_staf_order, $direktori_staf_search, $direktori_staf_status); ?></th>
                                <th class="py-3 text-center">SKIM <?php echo direktoriStafSortLink('skim', $direktori_staf_sort, $direktori_staf_order, $direktori_staf_search, $direktori_staf_status); ?></th>
                                <th class="py-3 text-center">GRED <?php echo direktoriStafSortLink('gred', $direktori_staf_sort, $direktori_staf_order, $direktori_staf_search, $direktori_staf_status); ?></th>
                                <th class="py-3">BAHAGIAN <?php echo direktoriStafSortLink('bahagian', $direktori_staf_sort, $direktori_staf_order, $direktori_staf_search, $direktori_staf_status); ?></th>
                                <th class="py-3 text-center px-3">TINDAKAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($direktori_staf_data) > 0): ?>
                                <?php $bil = ($direktori_staf_page - 1) * $direktori_staf_items_per_page + 1; foreach($direktori_staf_data as $row): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?php echo $bil++; ?></td>
                                    <td>
                                        <a href="javascript:void(0);"
                                           class="nama-link staf-detail-link"
                                           style="color: #0d6efd; font-weight: 600; text-decoration: none;"
                                           data-id="<?php echo (int)$row['id_user']; ?>"
                                           data-nama="<?php echo htmlspecialchars($row['nama']); ?>"
                                           data-nokp="<?php echo htmlspecialchars($row['no_kp'] ?? ''); ?>"
                                           data-jawatan="<?php echo htmlspecialchars($row['jawatan'] ?? '-'); ?>"
                                           data-skim="<?php echo htmlspecialchars($row['skim'] ?? '-'); ?>"
                                           data-gred="<?php echo htmlspecialchars($row['gred'] ?? '-'); ?>"
                                           data-bahagian="<?php echo htmlspecialchars($row['bahagian'] ?? '-'); ?>"
                                           data-emel="<?php echo htmlspecialchars($row['emel'] ?? ''); ?>"
                                           data-telefon="<?php echo htmlspecialchars($row['telefon'] ?? ''); ?>"
                                           data-status="<?php echo (int)$row['id_status_staf']; ?>"
                                           data-foto="<?php echo !empty($row['gambar']) ? 'uploads/' . htmlspecialchars($row['gambar']) : ''; ?>"
                                        >
                                            <?php echo htmlspecialchars($row['nama']); ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($row['jawatan'] ?? '-'); ?></td>
                                    <td class="text-center fw-bold text-secondary"><?php echo htmlspecialchars($row['skim'] ?? '-'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['gred'] ?? '-'); ?></td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($row['bahagian'] ?? '-'); ?></td>
                                    <td class="text-center px-3">
                                        <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_user')): ?>
                                            <a href="proses_staf.php?id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Tiada rekod dijumpai.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($direktori_staf_total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <?php
                        $params = http_build_query([
                            'direktori_staf_search' => $direktori_staf_search,
                            'direktori_staf_status' => $direktori_staf_status,
                            'direktori_staf_sort' => $direktori_staf_sort,
                            'direktori_staf_order' => $direktori_staf_order
                        ]);
                        ?>
                        <li class="page-item <?php echo $direktori_staf_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo $params; ?>&direktori_staf_page=<?php echo $direktori_staf_page - 1; ?>#direktoriStafContainer">Sebelum</a>
                        </li>
                        <?php for($i = 1; $i <= $direktori_staf_total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $direktori_staf_page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo $params; ?>&direktori_staf_page=<?php echo $i; ?>#direktoriStafContainer"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $direktori_staf_page >= $direktori_staf_total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo $params; ?>&direktori_staf_page=<?php echo $direktori_staf_page + 1; ?>#direktoriStafContainer">Selepas</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kalendar Hari Lahir (Bulan Ini) -->
    <div class="row g-4" id="birthdaySection" style="display: none; margin-top: 1rem;">
        <div class="col-12 p-0">
            <iframe src="kalendar.php?embed=1" style="width: 100%; height: calc(100vh - 260px); min-height: calc(100vh - 260px); border: none; background: transparent;" title="Kalendar Hari Lahir"></iframe>
        </div>
    </div>

    <div class="row g-4" id="bahagianChartsSection" style="display: none;">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Bilangan Staf Mengikut Bahagian</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="barChartFullscreenBtn" class="btn btn-outline-primary" onclick="toggleBarChartFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="barChartExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitBarChartFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="barChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Peratus Staf Mengikut Bahagian</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="pieChartFullscreenBtn" class="btn btn-outline-primary" onclick="togglePieChartFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="pieChartExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitPieChartFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="pieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4" id="jawatanChartsSection" style="display: none;">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Bilangan Staf Mengikut Jawatan</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="jawatanBarFullscreenBtn" class="btn btn-outline-primary" onclick="toggleJawatanBarFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="jawatanBarExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitJawatanBarFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="jawatanBarChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Peratus Staf Mengikut Jawatan</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="jawatanPieFullscreenBtn" class="btn btn-outline-primary" onclick="toggleJawatanPieFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="jawatanPieExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitJawatanPieFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="jawatanPieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pengurusan Tertinggi (≥15) -->
    <div class="row g-4" id="tertinggiChartsSection" style="display: none;">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Bilangan Staf Pengurusan Tertinggi Mengikut Bahagian</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="tertinggiBarFullscreenBtn" class="btn btn-outline-primary" onclick="toggleTertinggiBarFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="tertinggiBarExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitTertinggiBarFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="tertinggiBarChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Peratus Staf Pengurusan Tertinggi Mengikut Jawatan</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="tertinggiPieFullscreenBtn" class="btn btn-outline-primary" onclick="toggleTertinggiPieFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="tertinggiPieExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitTertinggiPieFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="tertinggiPieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pengurusan & Profesional (9-14) -->
    <div class="row g-4" id="pengurusanChartsSection" style="display: none;">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Bilangan Staf Pengurusan & Profesional Mengikut Bahagian</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="pengurusanBarFullscreenBtn" class="btn btn-outline-primary" onclick="togglePengurusanBarFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="pengurusanBarExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitPengurusanBarFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="pengurusanBarChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Peratus Staf Pengurusan & Profesional Mengikut Jawatan</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="pengurusanPieFullscreenBtn" class="btn btn-outline-primary" onclick="togglePengurusanPieFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="pengurusanPieExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitPengurusanPieFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="pengurusanPieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sokongan 1 (5-8) -->
    <div class="row g-4" id="sokongan1ChartsSection" style="display: none;">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Bilangan Staf Sokongan 1 Mengikut Bahagian</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="sokongan1BarFullscreenBtn" class="btn btn-outline-primary" onclick="toggleSokongan1BarFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="sokongan1BarExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitSokongan1BarFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="sokongan1BarChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Peratus Staf Sokongan 1 Mengikut Jawatan</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="sokongan1PieFullscreenBtn" class="btn btn-outline-primary" onclick="toggleSokongan1PieFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="sokongan1PieExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitSokongan1PieFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="sokongan1PieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sokongan 2 (1-4) -->
    <div class="row g-4" id="sokongan2ChartsSection" style="display: none;">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Bilangan Staf Sokongan 2 Mengikut Bahagian</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="sokongan2BarFullscreenBtn" class="btn btn-outline-primary" onclick="toggleSokongan2BarFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="sokongan2BarExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitSokongan2BarFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="sokongan2BarChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0">Peratus Staf Sokongan 2 Mengikut Jawatan</h6>
                        <div class="btn-group btn-group-sm">
                            <button id="sokongan2PieFullscreenBtn" class="btn btn-outline-primary" onclick="toggleSokongan2PieFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="sokongan2PieExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitSokongan2PieFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;"><canvas id="sokongan2PieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Set default font for all charts
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;

// Show sections only when specific card is clicked
const stafCard = document.getElementById('cardStaf');
const bahagianCard = document.getElementById('cardBahagian');
const birthdayCard = document.getElementById('cardBirthday');
const jawatanCard = document.getElementById('cardJawatan');
const tertinggiCard = document.getElementById('cardTertinggi');
const pengurusanCard = document.getElementById('cardPengurusan');
const sokongan1Card = document.getElementById('cardSokongan1');
const sokongan2Card = document.getElementById('cardSokongan2');
const direktoriStafSection = document.getElementById('direktoriStafContainer');
const chartsSection = document.getElementById('bahagianChartsSection');
const jawatanSection = document.getElementById('jawatanChartsSection');
const birthdaySection = document.getElementById('birthdaySection');
const tertinggiSection = document.getElementById('tertinggiChartsSection');
const pengurusanSection = document.getElementById('pengurusanChartsSection');
const sokongan1Section = document.getElementById('sokongan1ChartsSection');
const sokongan2Section = document.getElementById('sokongan2ChartsSection');
const summaryCards = document.querySelectorAll('.summary-card-staf');

function hideAllSections() {
    if (direktoriStafSection) direktoriStafSection.style.display = 'none';
    if (chartsSection) chartsSection.style.display = 'none';
    if (jawatanSection) jawatanSection.style.display = 'none';
    if (birthdaySection) birthdaySection.style.display = 'none';
    if (tertinggiSection) tertinggiSection.style.display = 'none';
    if (pengurusanSection) pengurusanSection.style.display = 'none';
    if (sokongan1Section) sokongan1Section.style.display = 'none';
    if (sokongan2Section) sokongan2Section.style.display = 'none';
}
function showDirektoriStaf() {
    hideAllSections();
    if (direktoriStafSection) {
        direktoriStafSection.style.display = 'block';
        setTimeout(() => {
            direktoriStafSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
}
function showBahagianCharts() {
    hideAllSections();
    if (chartsSection) chartsSection.style.display = '';
}
function showJawatanCharts() {
    hideAllSections();
    if (jawatanSection) jawatanSection.style.display = '';
}
function showBirthday() {
    hideAllSections();
    if (birthdaySection) birthdaySection.style.display = '';
}
function showTertinggi() {
    hideAllSections();
    if (tertinggiSection) tertinggiSection.style.display = '';
}
function showPengurusan() {
    hideAllSections();
    if (pengurusanSection) pengurusanSection.style.display = '';
}
function showSokongan1() {
    hideAllSections();
    if (sokongan1Section) sokongan1Section.style.display = '';
}
function showSokongan2() {
    hideAllSections();
    if (sokongan2Section) sokongan2Section.style.display = '';
}

// Hide all sections on initial load
hideAllSections();

// On page load, default to Jawatan card (show jawatan charts)
if (!window.location.hash || window.location.hash === '#direktoriStafContainer') {
    if (window.location.hash === '#direktoriStafContainer') {
        // Show direktori staf if hash exists
        if (direktoriStafSection) {
            direktoriStafSection.style.display = 'block';
            if (stafCard) stafCard.classList.add('active');
            setTimeout(() => {
                direktoriStafSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }
    } else {
        // Default: Show Jawatan charts
        if (jawatanCard) {
            jawatanCard.classList.add('active');
            showJawatanCharts();
        }
    }
}

// Add click listeners
summaryCards.forEach(card => {
    card.addEventListener('click', () => {
        summaryCards.forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        if (card === stafCard) {
            showDirektoriStaf();
        } else if (card === bahagianCard) {
            showBahagianCharts();
        } else if (card === jawatanCard) {
            showJawatanCharts();
        } else if (card === birthdayCard) {
            showBirthday();
        } else if (card === tertinggiCard) {
            showTertinggi();
        } else if (card === pengurusanCard) {
            showPengurusan();
        } else if (card === sokongan1Card) {
            showSokongan1();
        } else if (card === sokongan2Card) {
            showSokongan2();
        } else {
            hideAllSections();
        }
    });
});

// Modal detail staf - klik pada nama
const stafDetailLinks = document.querySelectorAll('.staf-detail-link');
if (stafDetailLinks.length > 0 && typeof bootstrap !== 'undefined') {
    const statusLabel = {
        1: 'Masih Bekerja',
        2: 'Bersara',
        3: 'Berhenti'
    };
    const statusClass = {
        1: 'bg-success',
        2: 'bg-warning text-dark',
        3: 'bg-danger'
    };

    const modalEl = document.getElementById('stafDetailModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    stafDetailLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (!modal) return;
            const ds = link.dataset;

            document.getElementById('detailNama').textContent = ds.nama || '-';
            document.getElementById('detailNoKp').textContent = ds.nokp || '-';
            document.getElementById('detailJawatan').textContent = ds.jawatan || '-';
            document.getElementById('detailSkim').textContent = ds.skim || '-';
            document.getElementById('detailGred').textContent = ds.gred || '-';
            document.getElementById('detailBahagian').textContent = ds.bahagian || '-';
            document.getElementById('detailEmel').textContent = ds.emel || '-';
            document.getElementById('detailTelefon').textContent = ds.telefon || '-';

            const statusSpan = document.getElementById('detailStatus');
            if (statusSpan) {
                const code = parseInt(ds.status || '0', 10);
                statusSpan.textContent = statusLabel[code] || 'Tidak Diketahui';
                statusSpan.className = 'badge ' + (statusClass[code] || 'bg-secondary');
            }

            // Foto: gunakan foto dari dataset jika ada; jika tiada, guna icon mengikut status
            const photoEl = document.getElementById('detailPhoto');
            const photoContainer = photoEl ? photoEl.parentElement : null;
            const statusCode = parseInt(ds.status || '0', 10);
            
            if (photoEl && photoContainer) {
                // Reset container - buang icon jika ada
                photoContainer.innerHTML = '<img id="detailPhoto" src="" alt="Foto Staf" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;">';
                const newPhotoEl = document.getElementById('detailPhoto');
                
                if (ds.foto && ds.foto.trim() !== '') {
                    // Ada foto - guna foto sebenar
                    newPhotoEl.src = ds.foto;
                    newPhotoEl.style.display = 'block';
                } else {
                    // Tiada foto - guna avatar icon mengikut status
                    if (statusCode === 2) {
                        // Bersara - guna avatar dengan warna kuning
                        photoContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center rounded-circle border bg-warning bg-opacity-10" style="width: 100px; height: 100px;"><i class="fas fa-user-circle fa-4x text-warning"></i></div>';
                    } else if (statusCode === 3) {
                        // Berhenti - guna avatar dengan warna merah
                        photoContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center rounded-circle border bg-danger bg-opacity-10" style="width: 100px; height: 100px;"><i class="fas fa-user-circle fa-4x text-danger"></i></div>';
                    } else {
                        // Masih Bekerja - guna default avatar
                        newPhotoEl.src = 'image/mawar.png';
                        newPhotoEl.style.display = 'block';
                    }
                }
            }

            modal.show();
        });
    });
}

// Optional: set default active (no charts shown until Bahagian clicked)
// Biarkan tiada kad aktif pada load; akan aktif selepas user klik

const ctxBar = document.getElementById('barChart').getContext('2d');
const colorPalette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#2e59a7', '#17a2b8', '#20c997', '#ffc107', '#6610f2', '#e83e8c', '#fd7e14', '#6f42c1', '#00d4ff', '#92e7e8', '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#dda15e', '#bc6c25', '#ffd60a', '#003566', '#606c38', '#283618', '#f8b195', '#f67280', '#c06c84', '#6c567b', '#d4af37'];
const numBahagian = <?php echo count($chartBahagian); ?>;
const chartColors = colorPalette.slice(0, numBahagian);

// Convert labels to uppercase
const bahagianiLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartBahagian, 'bahagian'))); ?>;

// Create gradient colors for bar chart
const gradientColors = chartColors.map(color => {
    const gradient = ctxBar.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, color);
    gradient.addColorStop(1, color + 'cc'); // Add transparency at bottom
    return gradient;
});

window.barChart = new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: bahagianiLabels,
        datasets: [{ label: 'Staf', data: <?php echo json_encode(array_column($chartBahagian, 'total')); ?>, backgroundColor: gradientColors, borderRadius: 8, borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

const ctxPie = document.getElementById('pieChart').getContext('2d');
const bahagianTopLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartBahagianTop, 'bahagian'))); ?>;
const bahagianTopData = <?php echo json_encode(array_column($chartBahagianTop, 'total')); ?>;
const totalBahagianTop = <?php echo (int)$totalBahagianTop; ?>;
const bahagianTopPercent = bahagianTopData.map(v => totalBahagianTop > 0 ? (v / totalBahagianTop * 100) : 0);

// Data for Jawatan
const jawatanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartJawatanData, 'jawatan'))); ?>;
const jawatanCounts = <?php echo json_encode(array_column($chartJawatanData, 'total')); ?>;
const totalJawatan = <?php echo (int)$totalJawatan; ?>;
const jawatanPercent = jawatanCounts.map(v => totalJawatan > 0 ? (v / totalJawatan * 100) : 0);

// Data untuk bahagian mengikut kategori gred
const tertinggiLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartTertinggi, 'bahagian'))); ?>;
const tertinggiCounts = <?php echo json_encode(array_column($chartTertinggi, 'total')); ?>;
const totalTertinggi = <?php echo (int)$totalTertinggi; ?>;
const tertinggiPercent = tertinggiCounts.map(v => totalTertinggi > 0 ? (v / totalTertinggi * 100) : 0);

const pengurusanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartPengurusan, 'bahagian'))); ?>;
const pengurusanCounts = <?php echo json_encode(array_column($chartPengurusan, 'total')); ?>;
const totalPengurusan = <?php echo (int)$totalPengurusan; ?>;
const pengurusanPercent = pengurusanCounts.map(v => totalPengurusan > 0 ? (v / totalPengurusan * 100) : 0);

const sokongan1Labels = <?php echo json_encode(array_map('strtoupper', array_column($chartSokongan1, 'bahagian'))); ?>;
const sokongan1Counts = <?php echo json_encode(array_column($chartSokongan1, 'total')); ?>;
const totalSokongan1 = <?php echo (int)$totalSokongan1; ?>;
const sokongan1Percent = sokongan1Counts.map(v => totalSokongan1 > 0 ? (v / totalSokongan1 * 100) : 0);

const sokongan2Labels = <?php echo json_encode(array_map('strtoupper', array_column($chartSokongan2, 'bahagian'))); ?>;
const sokongan2Counts = <?php echo json_encode(array_column($chartSokongan2, 'total')); ?>;
const totalSokongan2 = <?php echo (int)$totalSokongan2; ?>;
const sokongan2Percent = sokongan2Counts.map(v => totalSokongan2 > 0 ? (v / totalSokongan2 * 100) : 0);

// Data Jawatan mengikut kategori gred (untuk donut chart)
const tertinggiJawatanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartTertinggiJawatan, 'jawatan'))); ?>;
const tertinggiJawatanCounts = <?php echo json_encode(array_column($chartTertinggiJawatan, 'total')); ?>;
const totalTertinggiJawatan = <?php echo (int)$totalTertinggiJawatan; ?>;
const tertinggiJawatanPercent = tertinggiJawatanCounts.map(v => totalTertinggiJawatan > 0 ? (v / totalTertinggiJawatan * 100) : 0);

const pengurusanJawatanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartPengurusanJawatan, 'jawatan'))); ?>;
const pengurusanJawatanCounts = <?php echo json_encode(array_column($chartPengurusanJawatan, 'total')); ?>;
const totalPengurusanJawatan = <?php echo (int)$totalPengurusanJawatan; ?>;
const pengurusanJawatanPercent = pengurusanJawatanCounts.map(v => totalPengurusanJawatan > 0 ? (v / totalPengurusanJawatan * 100) : 0);

const sokongan1JawatanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartSokongan1Jawatan, 'jawatan'))); ?>;
const sokongan1JawatanCounts = <?php echo json_encode(array_column($chartSokongan1Jawatan, 'total')); ?>;
const totalSokongan1Jawatan = <?php echo (int)$totalSokongan1Jawatan; ?>;
const sokongan1JawatanPercent = sokongan1JawatanCounts.map(v => totalSokongan1Jawatan > 0 ? (v / totalSokongan1Jawatan * 100) : 0);

const sokongan2JawatanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartSokongan2Jawatan, 'jawatan'))); ?>;
const sokongan2JawatanCounts = <?php echo json_encode(array_column($chartSokongan2Jawatan, 'total')); ?>;
const totalSokongan2Jawatan = <?php echo (int)$totalSokongan2Jawatan; ?>;
const sokongan2JawatanPercent = sokongan2JawatanCounts.map(v => totalSokongan2Jawatan > 0 ? (v / totalSokongan2Jawatan * 100) : 0);
// Create gradient colors for pie chart (ikut bilangan bahagian)
const pieGradients = bahagianTopLabels.map((_, idx) => {
    const baseColor = colorPalette[idx % colorPalette.length];
    const g = ctxPie.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, baseColor);
    g.addColorStop(1, baseColor + 'cc'); // sedikit transparent
    return g;
});

window.pieChart = new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: bahagianTopLabels,
        datasets: [{ 
            data: bahagianTopPercent, 
            backgroundColor: pieGradients,
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 11 },
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                return {
                                    text: label + ' (' + value.toFixed(1) + '%)',
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const idx = context.dataIndex;
                        const percent = context.parsed.toFixed(1);
                        const count = bahagianTopData[idx] ?? 0;
                        return `${context.label}: ${count} staf (${percent}%)`;
                    }
                }
            }
        }
    }
});

// Jawatan Bar Chart
const ctxJawatanBar = document.getElementById('jawatanBarChart').getContext('2d');
const jawatanColors = colorPalette.slice(0, jawatanLabels.length);
const jawatanBarGradients = jawatanColors.map(color => {
    const g = ctxJawatanBar.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, color);
    g.addColorStop(1, color + 'cc');
    return g;
});

window.jawatanBarChart = new Chart(ctxJawatanBar, {
    type: 'bar',
    data: {
        labels: jawatanLabels,
        datasets: [{
            label: 'Staf',
            data: jawatanCounts,
            backgroundColor: jawatanBarGradients,
            borderRadius: 8,
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

// Jawatan Pie Chart
const ctxJawatanPie = document.getElementById('jawatanPieChart').getContext('2d');
const jawatanPieGradients = jawatanLabels.map((_, idx) => {
    const baseColor = colorPalette[idx % colorPalette.length];
    const g = ctxJawatanPie.createLinearGradient(0, 0, 0, 400);
    g.addColorStop(0, baseColor);
    g.addColorStop(1, baseColor + 'cc');
    return g;
});

window.jawatanPieChart = new Chart(ctxJawatanPie, {
    type: 'doughnut',
    data: {
        labels: jawatanLabels,
        datasets: [{
            data: jawatanPercent,
            backgroundColor: jawatanPieGradients,
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 11 },
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                return {
                                    text: label + ' (' + value.toFixed(1) + '%)',
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const idx = context.dataIndex;
                        const percent = context.parsed.toFixed(1);
                        const count = jawatanCounts[idx] ?? 0;
                        return `${context.label}: ${count} staf (${percent}%)`;
                    }
                }
            }
        }
    }
});

// Helper to build gradients
function buildGradients(ctx, labels) {
    return labels.map((_, idx) => {
        const baseColor = colorPalette[idx % colorPalette.length];
        const g = ctx.createLinearGradient(0, 0, 0, 400);
        g.addColorStop(0, baseColor);
        g.addColorStop(1, baseColor + 'cc');
        return g;
    });
}

function createBarChart(ctx, labels, data) {
    const grads = buildGradients(ctx, labels);
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Staf',
                data,
                backgroundColor: grads,
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
}

function createPieChart(ctx, labels, dataRaw, dataPercent) {
    const grads = buildGradients(ctx, labels);
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: dataPercent,
                backgroundColor: grads,
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 11 },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    return {
                                        text: label + ' (' + value.toFixed(1) + '%)',
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const idx = context.dataIndex;
                            const percent = context.parsed.toFixed(1);
                            const count = dataRaw[idx] ?? 0;
                            return `${context.label}: ${count} staf (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Charts: Pengurusan Tertinggi
window.tertinggiBarChart = createBarChart(
    document.getElementById('tertinggiBarChart').getContext('2d'),
    tertinggiLabels,
    tertinggiCounts
);
window.tertinggiPieChart = createPieChart(
    document.getElementById('tertinggiPieChart').getContext('2d'),
    tertinggiJawatanLabels,
    tertinggiJawatanCounts,
    tertinggiJawatanPercent
);

// Charts: Pengurusan & Profesional
window.pengurusanBarChart = createBarChart(
    document.getElementById('pengurusanBarChart').getContext('2d'),
    pengurusanLabels,
    pengurusanCounts
);
window.pengurusanPieChart = createPieChart(
    document.getElementById('pengurusanPieChart').getContext('2d'),
    pengurusanJawatanLabels,
    pengurusanJawatanCounts,
    pengurusanJawatanPercent
);

// Charts: Sokongan 1
window.sokongan1BarChart = createBarChart(
    document.getElementById('sokongan1BarChart').getContext('2d'),
    sokongan1Labels,
    sokongan1Counts
);
window.sokongan1PieChart = createPieChart(
    document.getElementById('sokongan1PieChart').getContext('2d'),
    sokongan1JawatanLabels,
    sokongan1JawatanCounts,
    sokongan1JawatanPercent
);

// Charts: Sokongan 2
window.sokongan2BarChart = createBarChart(
    document.getElementById('sokongan2BarChart').getContext('2d'),
    sokongan2Labels,
    sokongan2Counts
);
window.sokongan2PieChart = createPieChart(
    document.getElementById('sokongan2PieChart').getContext('2d'),
    sokongan2JawatanLabels,
    sokongan2JawatanCounts,
    sokongan2JawatanPercent
);

// Toggle fullscreen for bar chart
function toggleBarChartFullscreen() {
    const chartCanvas = document.getElementById('barChart');
    if (!chartCanvas) {
        console.error('Bar chart canvas not found');
        return;
    }
    
    let chartCard = chartCanvas.closest('.card');
    if (!chartCard) {
        let parent = chartCanvas.parentElement;
        while (parent && !parent.classList.contains('card')) {
            parent = parent.parentElement;
        }
        chartCard = parent;
    }
    
    if (!chartCard) {
        console.error('Bar chart card not found');
        return;
    }
    
    if (!document.fullscreenElement) {
        chartCard.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Exit fullscreen for bar chart
function exitBarChartFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    }
}

// Handle fullscreen change for bar chart
function handleBarChartFullscreenChange() {
    const chartCanvas = document.getElementById('barChart');
    const chartContainer = chartCanvas ? chartCanvas.parentElement : null;
    const chartCard = chartCanvas ? chartCanvas.closest('.card') : null;
    const fullscreenBtn = document.getElementById('barChartFullscreenBtn');
    const exitFullscreenBtn = document.getElementById('barChartExitFullscreenBtn');
    
    if (!chartCanvas || !chartContainer || !chartCard) {
        return;
    }
    
    if (document.fullscreenElement && document.fullscreenElement === chartCard) {
        // Entered fullscreen
        chartCard.style.width = '100vw';
        chartCard.style.height = '100vh';
        chartCard.style.margin = '0';
        chartCard.style.borderRadius = '0';
        
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)';
            cardBody.style.padding = '20px';
        }
        
        if (chartContainer) {
            chartContainer.style.height = '100%';
            chartContainer.style.width = '100%';
        }
        
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        
        // Resize chart
        if (window.barChart) {
            setTimeout(() => {
                window.barChart.resize();
            }, 100);
        }
    } else {
        // Exited fullscreen - reset to original size
        chartCard.style.width = '';
        chartCard.style.height = '';
        chartCard.style.margin = '';
        chartCard.style.borderRadius = '';
        
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        
        if (chartContainer) {
            chartContainer.style.height = '600px';
            chartContainer.style.width = '';
        }
        
        // Reset canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '600px';
            canvasContainer.style.width = '';
        }
        
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        
        // Resize chart to original size
        if (window.barChart) {
            setTimeout(() => {
                window.barChart.resize();
            }, 100);
        }
    }
}

// Toggle fullscreen for pie chart
function togglePieChartFullscreen() {
    const chartCanvas = document.getElementById('pieChart');
    if (!chartCanvas) {
        console.error('Pie chart canvas not found');
        return;
    }
    
    let chartCard = chartCanvas.closest('.card');
    if (!chartCard) {
        let parent = chartCanvas.parentElement;
        while (parent && !parent.classList.contains('card')) {
            parent = parent.parentElement;
        }
        chartCard = parent;
    }
    
    if (!chartCard) {
        console.error('Pie chart card not found');
        return;
    }
    
    if (!document.fullscreenElement) {
        chartCard.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Exit fullscreen for pie chart
function exitPieChartFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    }
}

// Handle fullscreen change for pie chart
function handlePieChartFullscreenChange() {
    const chartCanvas = document.getElementById('pieChart');
    const chartContainer = chartCanvas ? chartCanvas.parentElement : null;
    const chartCard = chartCanvas ? chartCanvas.closest('.card') : null;
    const fullscreenBtn = document.getElementById('pieChartFullscreenBtn');
    const exitFullscreenBtn = document.getElementById('pieChartExitFullscreenBtn');
    
    if (!chartCanvas || !chartContainer || !chartCard) {
        return;
    }
    
    if (document.fullscreenElement && document.fullscreenElement === chartCard) {
        // Entered fullscreen
        chartCard.style.width = '100vw';
        chartCard.style.height = '100vh';
        chartCard.style.margin = '0';
        chartCard.style.borderRadius = '0';
        
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)';
            cardBody.style.padding = '20px';
        }
        
        if (chartContainer) {
            chartContainer.style.height = '100%';
            chartContainer.style.width = '100%';
        }
        
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        
        // Resize chart
        if (window.pieChart) {
            setTimeout(() => {
                window.pieChart.resize();
            }, 100);
        }
    } else {
        // Exited fullscreen - reset to original size
        chartCard.style.width = '';
        chartCard.style.height = '';
        chartCard.style.margin = '';
        chartCard.style.borderRadius = '';
        
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        
        if (chartContainer) {
            chartContainer.style.height = '600px';
            chartContainer.style.width = '';
        }
        
        // Reset canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '600px';
            canvasContainer.style.width = '';
        }
        
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        
        // Resize chart to original size
        if (window.pieChart) {
            setTimeout(() => {
                window.pieChart.resize();
            }, 100);
        }
    }
}

// Jawatan Bar Fullscreen
function toggleJawatanBarFullscreen() {
    const chartCanvas = document.getElementById('jawatanBarChart');
    if (!chartCanvas) return;
    let chartCard = chartCanvas.closest('.card');
    if (!chartCard) {
        let parent = chartCanvas.parentElement;
        while (parent && !parent.classList.contains('card')) parent = parent.parentElement;
        chartCard = parent;
    }
    if (!chartCard) return;
    if (!document.fullscreenElement) {
        chartCard.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
}
function exitJawatanBarFullscreen() {
    if (document.fullscreenElement) document.exitFullscreen();
}
function handleJawatanBarFullscreenChange() {
    const chartCanvas = document.getElementById('jawatanBarChart');
    const chartContainer = chartCanvas ? chartCanvas.parentElement : null;
    const chartCard = chartCanvas ? chartCanvas.closest('.card') : null;
    const fullscreenBtn = document.getElementById('jawatanBarFullscreenBtn');
    const exitFullscreenBtn = document.getElementById('jawatanBarExitFullscreenBtn');
    if (!chartCanvas || !chartContainer || !chartCard) return;
    if (document.fullscreenElement && document.fullscreenElement === chartCard) {
        chartCard.style.width = '100vw';
        chartCard.style.height = '100vh';
        chartCard.style.margin = '0';
        chartCard.style.borderRadius = '0';
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)';
            cardBody.style.padding = '20px';
        }
        chartContainer.style.height = '100%';
        chartContainer.style.width = '100%';
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        if (window.jawatanBarChart) setTimeout(() => window.jawatanBarChart.resize(), 100);
    } else {
        // Exited fullscreen - reset to original size
        chartCard.style.width = '';
        chartCard.style.height = '';
        chartCard.style.margin = '';
        chartCard.style.borderRadius = '';
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        if (chartContainer) {
            chartContainer.style.height = '600px';
            chartContainer.style.width = '';
        }
        // Reset canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '600px';
            canvasContainer.style.width = '';
        }
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        // Resize chart to original size
        if (window.jawatanBarChart) setTimeout(() => window.jawatanBarChart.resize(), 100);
    }
}

// Jawatan Pie Fullscreen
function toggleJawatanPieFullscreen() {
    const chartCanvas = document.getElementById('jawatanPieChart');
    if (!chartCanvas) return;
    let chartCard = chartCanvas.closest('.card');
    if (!chartCard) {
        let parent = chartCanvas.parentElement;
        while (parent && !parent.classList.contains('card')) parent = parent.parentElement;
        chartCard = parent;
    }
    if (!chartCard) return;
    if (!document.fullscreenElement) {
        chartCard.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
}
function exitJawatanPieFullscreen() {
    if (document.fullscreenElement) document.exitFullscreen();
}
function handleJawatanPieFullscreenChange() {
    const chartCanvas = document.getElementById('jawatanPieChart');
    const chartContainer = chartCanvas ? chartCanvas.parentElement : null;
    const chartCard = chartCanvas ? chartCanvas.closest('.card') : null;
    const fullscreenBtn = document.getElementById('jawatanPieFullscreenBtn');
    const exitFullscreenBtn = document.getElementById('jawatanPieExitFullscreenBtn');
    if (!chartCanvas || !chartContainer || !chartCard) return;
    if (document.fullscreenElement && document.fullscreenElement === chartCard) {
        chartCard.style.width = '100vw';
        chartCard.style.height = '100vh';
        chartCard.style.margin = '0';
        chartCard.style.borderRadius = '0';
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)';
            cardBody.style.padding = '20px';
        }
        chartContainer.style.height = '100%';
        chartContainer.style.width = '100%';
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        if (window.jawatanPieChart) setTimeout(() => window.jawatanPieChart.resize(), 100);
    } else {
        // Exited fullscreen - reset to original size
        chartCard.style.width = '';
        chartCard.style.height = '';
        chartCard.style.margin = '';
        chartCard.style.borderRadius = '';
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        if (chartContainer) {
            chartContainer.style.height = '600px';
            chartContainer.style.width = '';
        }
        // Reset canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '600px';
            canvasContainer.style.width = '';
        }
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        // Resize chart to original size
        if (window.jawatanPieChart) setTimeout(() => window.jawatanPieChart.resize(), 100);
    }
}

// ---------- Fullscreen helpers for kategori gred charts ----------
function toggleFullscreenGeneric(canvasId) {
    const chartCanvas = document.getElementById(canvasId);
    if (!chartCanvas) return;
    let chartCard = chartCanvas.closest('.card');
    if (!chartCard) {
        let parent = chartCanvas.parentElement;
        while (parent && !parent.classList.contains('card')) parent = parent.parentElement;
        chartCard = parent;
    }
    if (!chartCard) return;
    if (!document.fullscreenElement) {
        chartCard.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
}

function handleFullscreenGeneric(canvasId, fullscreenBtnId, exitBtnId, chartInstance) {
    const chartCanvas = document.getElementById(canvasId);
    const chartContainer = chartCanvas ? chartCanvas.parentElement : null;
    const chartCard = chartCanvas ? chartCanvas.closest('.card') : null;
    const fullscreenBtn = document.getElementById(fullscreenBtnId);
    const exitFullscreenBtn = document.getElementById(exitBtnId);
    if (!chartCanvas || !chartContainer || !chartCard) return;

    if (document.fullscreenElement && document.fullscreenElement === chartCard) {
        chartCard.style.width = '100vw';
        chartCard.style.height = '100vh';
        chartCard.style.margin = '0';
        chartCard.style.borderRadius = '0';
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)';
            cardBody.style.padding = '20px';
        }
        chartContainer.style.height = '100%';
        chartContainer.style.width = '100%';
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        if (chartInstance) setTimeout(() => chartInstance.resize(), 100);
    } else {
        chartCard.style.width = '';
        chartCard.style.height = '';
        chartCard.style.margin = '';
        chartCard.style.borderRadius = '';
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        if (chartContainer) {
            chartContainer.style.height = '600px';
            chartContainer.style.width = '';
        }
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '600px';
            canvasContainer.style.width = '';
        }
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        if (chartInstance) setTimeout(() => chartInstance.resize(), 100);
    }
}

// Pengurusan Tertinggi
function toggleTertinggiBarFullscreen() { toggleFullscreenGeneric('tertinggiBarChart'); }
function exitTertinggiBarFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handleTertinggiBarFullscreenChange() {
    handleFullscreenGeneric('tertinggiBarChart', 'tertinggiBarFullscreenBtn', 'tertinggiBarExitFullscreenBtn', window.tertinggiBarChart);
}
function toggleTertinggiPieFullscreen() { toggleFullscreenGeneric('tertinggiPieChart'); }
function exitTertinggiPieFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handleTertinggiPieFullscreenChange() {
    handleFullscreenGeneric('tertinggiPieChart', 'tertinggiPieFullscreenBtn', 'tertinggiPieExitFullscreenBtn', window.tertinggiPieChart);
}

// Pengurusan & Profesional
function togglePengurusanBarFullscreen() { toggleFullscreenGeneric('pengurusanBarChart'); }
function exitPengurusanBarFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handlePengurusanBarFullscreenChange() {
    handleFullscreenGeneric('pengurusanBarChart', 'pengurusanBarFullscreenBtn', 'pengurusanBarExitFullscreenBtn', window.pengurusanBarChart);
}
function togglePengurusanPieFullscreen() { toggleFullscreenGeneric('pengurusanPieChart'); }
function exitPengurusanPieFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handlePengurusanPieFullscreenChange() {
    handleFullscreenGeneric('pengurusanPieChart', 'pengurusanPieFullscreenBtn', 'pengurusanPieExitFullscreenBtn', window.pengurusanPieChart);
}

// Sokongan 1
function toggleSokongan1BarFullscreen() { toggleFullscreenGeneric('sokongan1BarChart'); }
function exitSokongan1BarFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handleSokongan1BarFullscreenChange() {
    handleFullscreenGeneric('sokongan1BarChart', 'sokongan1BarFullscreenBtn', 'sokongan1BarExitFullscreenBtn', window.sokongan1BarChart);
}
function toggleSokongan1PieFullscreen() { toggleFullscreenGeneric('sokongan1PieChart'); }
function exitSokongan1PieFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handleSokongan1PieFullscreenChange() {
    handleFullscreenGeneric('sokongan1PieChart', 'sokongan1PieFullscreenBtn', 'sokongan1PieExitFullscreenBtn', window.sokongan1PieChart);
}

// Sokongan 2
function toggleSokongan2BarFullscreen() { toggleFullscreenGeneric('sokongan2BarChart'); }
function exitSokongan2BarFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handleSokongan2BarFullscreenChange() {
    handleFullscreenGeneric('sokongan2BarChart', 'sokongan2BarFullscreenBtn', 'sokongan2BarExitFullscreenBtn', window.sokongan2BarChart);
}
function toggleSokongan2PieFullscreen() { toggleFullscreenGeneric('sokongan2PieChart'); }
function exitSokongan2PieFullscreen() { if (document.fullscreenElement) document.exitFullscreen(); }
function handleSokongan2PieFullscreenChange() {
    handleFullscreenGeneric('sokongan2PieChart', 'sokongan2PieFullscreenBtn', 'sokongan2PieExitFullscreenBtn', window.sokongan2PieChart);
}
// Add event listeners for fullscreen changes
document.addEventListener('fullscreenchange', function() {
    handleBarChartFullscreenChange();
    handlePieChartFullscreenChange();
    handleJawatanBarFullscreenChange();
    handleJawatanPieFullscreenChange();
    handleTertinggiBarFullscreenChange();
    handleTertinggiPieFullscreenChange();
    handlePengurusanBarFullscreenChange();
    handlePengurusanPieFullscreenChange();
    handleSokongan1BarFullscreenChange();
    handleSokongan1PieFullscreenChange();
    handleSokongan2BarFullscreenChange();
    handleSokongan2PieFullscreenChange();
});
document.addEventListener('webkitfullscreenchange', function() {
    handleBarChartFullscreenChange();
    handlePieChartFullscreenChange();
    handleJawatanBarFullscreenChange();
    handleJawatanPieFullscreenChange();
    handleTertinggiBarFullscreenChange();
    handleTertinggiPieFullscreenChange();
    handlePengurusanBarFullscreenChange();
    handlePengurusanPieFullscreenChange();
    handleSokongan1BarFullscreenChange();
    handleSokongan1PieFullscreenChange();
    handleSokongan2BarFullscreenChange();
    handleSokongan2PieFullscreenChange();
});
document.addEventListener('mozfullscreenchange', function() {
    handleBarChartFullscreenChange();
    handlePieChartFullscreenChange();
    handleJawatanBarFullscreenChange();
    handleJawatanPieFullscreenChange();
    handleTertinggiBarFullscreenChange();
    handleTertinggiPieFullscreenChange();
    handlePengurusanBarFullscreenChange();
    handlePengurusanPieFullscreenChange();
    handleSokongan1BarFullscreenChange();
    handleSokongan1PieFullscreenChange();
    handleSokongan2BarFullscreenChange();
    handleSokongan2PieFullscreenChange();
});
document.addEventListener('MSFullscreenChange', function() {
    handleBarChartFullscreenChange();
    handlePieChartFullscreenChange();
    handleJawatanBarFullscreenChange();
    handleJawatanPieFullscreenChange();
    handleTertinggiBarFullscreenChange();
    handleTertinggiPieFullscreenChange();
    handlePengurusanBarFullscreenChange();
    handlePengurusanPieFullscreenChange();
    handleSokongan1BarFullscreenChange();
    handleSokongan1PieFullscreenChange();
    handleSokongan2BarFullscreenChange();
    handleSokongan2PieFullscreenChange();
});
</script>
</div></body></html>
