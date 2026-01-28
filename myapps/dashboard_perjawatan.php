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

// ============================================================
// GENERATE BIRTHDAY EVENTS FOR CALENDAR
// ============================================================
$birthdayEvents = [];
$current_year = date('Y');
$year_range_start = 2000;
$year_range_end = $current_year + 15;

// Query untuk dapatkan semua staf dengan maklumat lengkap termasuk gambar
$birthdaySql = "SELECT u.id_user, u.nama, u.no_kp, u.emel, u.telefon, u.gambar,
                       j.jawatan, b.bahagian, g.gred
                FROM users u 
                LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan 
                LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
                LEFT JOIN gred g ON u.id_gred = g.id_gred
                WHERE u.id_status_staf = 1";

try {
    $birthdayStmt = $db->query($birthdaySql);
    while($row = $birthdayStmt->fetch(PDO::FETCH_ASSOC)) {
        // Validate no_kp
        if (empty($row['no_kp']) || strlen($row['no_kp']) < 6) {
            continue; // Skip invalid no_kp
        }
        
        $bulan = substr($row['no_kp'], 2, 2);
        $hari = substr($row['no_kp'], 4, 2);
        $birth_year = substr($row['no_kp'], 0, 2);
        
        // Validate date parts
        if (!is_numeric($bulan) || !is_numeric($hari) || $bulan < 1 || $bulan > 12 || $hari < 1 || $hari > 31) {
            continue; // Skip invalid dates
        }
        
        // Determine birth century (00-24 = 2000s, 25-99 = 1900s)
        $birth_year_full = ($birth_year <= 24) ? '20' . $birth_year : '19' . $birth_year;
        
        // Handle data kosong
        $jawatan = $row['jawatan'] ?? '-';
        $bahagian = $row['bahagian'] ?? '-';
        $gred = $row['gred'] ?? '-';
        $emel = $row['emel'] ?? '-';
        $telefon = $row['telefon'] ?? '-';
        $nama = $row['nama'] ?? 'Nama Tidak Diketahui';
        // Handle gambar - jika ada, tambah path uploads/, jika tiada, kosongkan
        $gambar = !empty($row['gambar']) ? 'uploads/' . $row['gambar'] : '';
    
        // Add birthday for all years
        for ($year = $year_range_start; $year <= $year_range_end; $year++) {
            // Validate date before adding
            if (checkdate((int)$bulan, (int)$hari, $year)) {
                $eventDate = "$year-$bulan-$hari";
                
                $birthdayEvents[] = [
                    'title' => $nama,
                    'start' => $eventDate, 
                    'extendedProps' => [ 
                        'id_user' => $row['id_user'],
                        'jawatan' => $jawatan, 
                        'bahagian' => $bahagian,
                        'gred' => $gred,
                        'emel' => $emel,
                        'telefon' => $telefon,
                        'gambar' => $gambar, // Tambah field gambar
                        'dob' => getTarikhLahir($row['no_kp']),
                        'birth_year' => $birth_year_full,
                        'birth_month' => $bulan,
                        'birth_day' => $hari
                    ]
                ];
            }
        }
    }
} catch (Exception $e) {
    error_log("Calendar error: " . $e->getMessage());
}

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

// Filter parameters
$filter_nama = $_GET['filter_nama'] ?? '';
$filter_no_kp = $_GET['filter_no_kp'] ?? '';
$filter_jawatan = $_GET['filter_jawatan'] ?? '';
$filter_skim = $_GET['filter_skim'] ?? '';
$filter_gred = $_GET['filter_gred'] ?? '';
$filter_bahagian = $_GET['filter_bahagian'] ?? '';
$filter_emel = $_GET['filter_emel'] ?? '';
$filter_telefon = $_GET['filter_telefon'] ?? '';

// Get unique values for dropdown filters
$uniqueJawatan = [];
$uniqueSkim = [];
$uniqueGred = [];
$uniqueBahagian = [];

try {
    // Get unique jawatan - get all unique values regardless of status for better UX
    $stmt = $db->prepare("SELECT DISTINCT j.jawatan FROM users u LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan WHERE j.jawatan IS NOT NULL AND j.jawatan != '' ORDER BY j.jawatan ASC");
    $stmt->execute();
    $uniqueJawatan = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get unique skim
    $stmt = $db->prepare("SELECT DISTINCT j.skim FROM users u LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan WHERE j.skim IS NOT NULL AND j.skim != '' ORDER BY j.skim ASC");
    $stmt->execute();
    $uniqueSkim = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get unique gred
    $stmt = $db->prepare("SELECT DISTINCT g.gred FROM users u LEFT JOIN gred g ON u.id_gred = g.id_gred WHERE g.gred IS NOT NULL AND g.gred != '' ORDER BY g.gred ASC");
    $stmt->execute();
    $uniqueGred = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get unique bahagian
    $stmt = $db->prepare("SELECT DISTINCT b.bahagian FROM users u LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian WHERE b.bahagian IS NOT NULL AND b.bahagian != '' ORDER BY b.bahagian ASC");
    $stmt->execute();
    $uniqueBahagian = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log('Error getting unique values for filters: ' . $e->getMessage());
    // Initialize empty arrays if error occurs
    $uniqueJawatan = [];
    $uniqueSkim = [];
    $uniqueGred = [];
    $uniqueBahagian = [];
}

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
$countParams = [$direktori_staf_status];

// Add filter conditions
if (!empty($filter_nama)) {
    $direktori_staf_sqlCount .= " AND u.nama LIKE ?";
    $countParams[] = "%$filter_nama%";
}
if (!empty($filter_no_kp)) {
    $direktori_staf_sqlCount .= " AND u.no_kp LIKE ?";
    $countParams[] = "%$filter_no_kp%";
}
if (!empty($filter_jawatan)) {
    $direktori_staf_sqlCount .= " AND j.jawatan = ?";
    $countParams[] = $filter_jawatan;
}
if (!empty($filter_skim)) {
    $direktori_staf_sqlCount .= " AND j.skim = ?";
    $countParams[] = $filter_skim;
}
if (!empty($filter_gred)) {
    $direktori_staf_sqlCount .= " AND g.gred = ?";
    $countParams[] = $filter_gred;
}
if (!empty($filter_bahagian)) {
    $direktori_staf_sqlCount .= " AND b.bahagian = ?";
    $countParams[] = $filter_bahagian;
}
if (!empty($filter_emel)) {
    $direktori_staf_sqlCount .= " AND u.emel LIKE ?";
    $countParams[] = "%$filter_emel%";
}
if (!empty($filter_telefon)) {
    $direktori_staf_sqlCount .= " AND u.telefon LIKE ?";
    $countParams[] = "%$filter_telefon%";
}

$direktori_staf_stmt = $db->prepare($direktori_staf_sqlCount);
$direktori_staf_stmt->execute($countParams);
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
$dataParams = [$direktori_staf_status];

// Add filter conditions
if (!empty($filter_nama)) {
    $direktori_staf_sql .= " AND u.nama LIKE ?";
    $dataParams[] = "%$filter_nama%";
}
if (!empty($filter_no_kp)) {
    $direktori_staf_sql .= " AND u.no_kp LIKE ?";
    $dataParams[] = "%$filter_no_kp%";
}
if (!empty($filter_jawatan)) {
    $direktori_staf_sql .= " AND j.jawatan = ?";
    $dataParams[] = $filter_jawatan;
}
if (!empty($filter_skim)) {
    $direktori_staf_sql .= " AND j.skim = ?";
    $dataParams[] = $filter_skim;
}
if (!empty($filter_gred)) {
    $direktori_staf_sql .= " AND g.gred = ?";
    $dataParams[] = $filter_gred;
}
if (!empty($filter_bahagian)) {
    $direktori_staf_sql .= " AND b.bahagian = ?";
    $dataParams[] = $filter_bahagian;
}
if (!empty($filter_emel)) {
    $direktori_staf_sql .= " AND u.emel LIKE ?";
    $dataParams[] = "%$filter_emel%";
}
if (!empty($filter_telefon)) {
    $direktori_staf_sql .= " AND u.telefon LIKE ?";
    $dataParams[] = "%$filter_telefon%";
}

// Get ALL data for client-side filtering (no server-side filters, no pagination)
$direktori_staf_sql_all = "SELECT u.*, j.jawatan, j.skim, g.gred, b.bahagian 
                           FROM users u 
                           LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
                           LEFT JOIN gred g ON u.id_gred = g.id_gred
                           LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
                           WHERE u.id_status_staf = ?";
$direktori_staf_stmt_all = $db->prepare($direktori_staf_sql_all);
$direktori_staf_stmt_all->execute([$direktori_staf_status]);
$direktori_staf_data_all = $direktori_staf_stmt_all->fetchAll();

// For initial display, use pagination (will be replaced by client-side filtering)
$direktori_staf_sql .= " ORDER BY $direktori_staf_sort $direktori_staf_order LIMIT $direktori_staf_items_per_page OFFSET $direktori_staf_offset";

$direktori_staf_stmt = $db->prepare($direktori_staf_sql);
$direktori_staf_stmt->execute($dataParams);
$direktori_staf_data = $direktori_staf_stmt->fetchAll();

// Function untuk sort link dalam direktori staf
function direktoriStafSortLink($col, $currentSort, $currentOrder, $currentSearch, $currentStatus) {
    global $filter_nama, $filter_no_kp, $filter_jawatan, $filter_skim, $filter_gred, $filter_bahagian, $filter_emel, $filter_telefon;
    $newOrder = ($currentSort == $col && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort == $col) ? (($currentOrder == 'ASC') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted opacity-25"></i>';
    $params = http_build_query([
        'direktori_staf_status' => $currentStatus,
        'direktori_staf_sort' => $col,
        'direktori_staf_order' => $newOrder,
        'direktori_staf_page' => 1,
        'filter_nama' => $filter_nama,
        'filter_no_kp' => $filter_no_kp,
        'filter_jawatan' => $filter_jawatan,
        'filter_skim' => $filter_skim,
        'filter_gred' => $filter_gred,
        'filter_bahagian' => $filter_bahagian,
        'filter_emel' => $filter_emel,
        'filter_telefon' => $filter_telefon
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

    <!-- Modal Add/Edit Staf -->
    <div class="modal fade" id="stafAddEditModal" tabindex="-1" aria-labelledby="stafAddEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="stafAddEditModalLabel">
                        <i class="fas fa-user-edit me-2"></i><span id="stafModalTitle">Tambah Staf</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stafAddEditForm" method="POST" enctype="multipart/form-data">
                        <?php echo getCsrfTokenField(); ?>
                        <input type="hidden" name="id_user" id="stafFormIdUser">
                        <input type="hidden" name="mode" id="stafFormMode" value="add">
                        
                        <!-- Gambar Profil -->
                        <div class="row mb-4 align-items-center">
                            <div class="col-md-3 text-center">
                                <img id="stafFormPhotoPreview" src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="rounded-circle border" width="100" height="100" style="object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-bold small">Muat Naik Gambar Profil (Pilihan)</label>
                                <input type="file" name="gambar" id="stafFormGambar" class="form-control" accept="image/*" onchange="previewStafImage(this)">
                            </div>
                        </div>
                        <hr>

                        <!-- No. Staf & No. KP -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">No. Staf <span class="text-danger">*</span></label>
                                <input type="text" name="no_staf" id="stafFormNoStaf" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">No. KP <span class="text-danger">*</span></label>
                                <input type="text" name="no_kp" id="stafFormNoKp" class="form-control" required>
                            </div>
                        </div>

                        <!-- Nama -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Penuh <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="stafFormNama" class="form-control" required>
                        </div>

                        <!-- Jawatan -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Jawatan <span class="text-danger">*</span></label>
                            <select name="id_jawatan" id="stafFormJawatan" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php 
                                $listJawatan = $db->query("SELECT * FROM jawatan ORDER BY jawatan ASC")->fetchAll();
                                foreach($listJawatan as $j): 
                                ?>
                                    <option value="<?php echo $j['id_jawatan']; ?>"><?php echo htmlspecialchars($j['jawatan']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Gred & Bahagian -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Gred <span class="text-danger">*</span></label>
                                <select name="id_gred" id="stafFormGred" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php 
                                    $listGred = $db->query("SELECT * FROM gred ORDER BY gred ASC")->fetchAll();
                                    foreach($listGred as $g): 
                                    ?>
                                        <option value="<?php echo $g['id_gred']; ?>"><?php echo htmlspecialchars($g['gred']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bahagian <span class="text-danger">*</span></label>
                                <select name="id_bahagian" id="stafFormBahagian" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php 
                                    $listBahagian = $db->query("SELECT * FROM bahagian ORDER BY bahagian ASC")->fetchAll();
                                    foreach($listBahagian as $b): 
                                    ?>
                                        <option value="<?php echo $b['id_bahagian']; ?>"><?php echo htmlspecialchars($b['bahagian']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status Staf -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Status Staf <span class="text-danger">*</span></label>
                            <select name="id_status_staf" id="stafFormStatus" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php 
                                $listStatusStaf = $db->query("SELECT * FROM status_staf WHERE aktif = 1 ORDER BY id_status ASC")->fetchAll();
                                foreach($listStatusStaf as $s): 
                                ?>
                                    <option value="<?php echo $s['id_status']; ?>"><?php echo htmlspecialchars($s['nama_status']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Emel & Telefon -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Emel</label>
                                <input type="email" name="emel" id="stafFormEmel" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Telefon</label>
                                <input type="text" name="telefon" id="stafFormTelefon" class="form-control">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="stafFormSubmitBtn">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
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
                    <?php
                    $tabParams = http_build_query([
                        'filter_nama' => $filter_nama,
                        'filter_no_kp' => $filter_no_kp,
                        'filter_jawatan' => $filter_jawatan,
                        'filter_skim' => $filter_skim,
                        'filter_gred' => $filter_gred,
                        'filter_bahagian' => $filter_bahagian,
                        'filter_emel' => $filter_emel,
                        'filter_telefon' => $filter_telefon
                    ]);
                    ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $direktori_staf_status == 1 ? 'active' : ''; ?>" href="?direktori_staf_status=1&<?php echo $tabParams; ?>#direktoriStafContainer">
                            <i class="fas fa-briefcase fa-lg text-success me-2"></i>Masih Bekerja (<?php echo (int)$cntStaf; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $direktori_staf_status == 2 ? 'active' : ''; ?>" href="?direktori_staf_status=2&<?php echo $tabParams; ?>#direktoriStafContainer">
                            <i class="fas fa-star fa-lg text-warning me-2"></i>Bersara (<?php echo (int)$cntStafBersara; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $direktori_staf_status == 3 ? 'active' : ''; ?>" href="?direktori_staf_status=3&<?php echo $tabParams; ?>#direktoriStafContainer">
                            <i class="fas fa-door-open fa-lg text-danger me-2"></i>Berhenti (<?php echo (int)$cntStafBerhenti; ?>)
                        </a>
                    </li>
                </ul>

                <!-- Filter Form -->
                <div class="card shadow-sm mb-4 border-0" style="background-color: #f8f9fa;">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="row g-2">
                                <!-- Row 1 -->
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">NAMA</label>
                                    <input type="text" id="filter_nama" class="form-control form-control-sm" placeholder="Filter NAMA" onkeyup="applyStafFilters()">
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">NO_KP</label>
                                    <input type="text" id="filter_no_kp" class="form-control form-control-sm" placeholder="Filter NO_KP" onkeyup="applyStafFilters()">
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">JAWATAN</label>
                                    <select id="filter_jawatan" class="form-select form-select-sm" style="padding-right: 2.8rem; text-align-last: left; background-position: right 0.4rem center;" onchange="applyStafFilters()">
                                        <option value="">Semua JAWATAN</option>
                                        <?php foreach($uniqueJawatan as $jawatan): ?>
                                            <option value="<?php echo htmlspecialchars($jawatan); ?>"><?php echo htmlspecialchars($jawatan); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">SKIM</label>
                                    <select id="filter_skim" class="form-select form-select-sm" style="padding-right: 2.8rem; text-align-last: left; background-position: right 0.4rem center;" onchange="applyStafFilters()">
                                        <option value="">Semua SKIM</option>
                                        <?php foreach($uniqueSkim as $skim): ?>
                                            <option value="<?php echo htmlspecialchars($skim); ?>"><?php echo htmlspecialchars($skim); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">GRED</label>
                                    <select id="filter_gred" class="form-select form-select-sm" style="padding-right: 2.8rem; text-align-last: left; background-position: right 0.4rem center;" onchange="applyStafFilters()">
                                        <option value="">Semua GRED</option>
                                        <?php foreach($uniqueGred as $gred): ?>
                                            <option value="<?php echo htmlspecialchars($gred); ?>"><?php echo htmlspecialchars($gred); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">BAHAGIAN</label>
                                    <select id="filter_bahagian" class="form-select form-select-sm" style="padding-right: 2.8rem; text-align-last: left; background-position: right 0.4rem center;" onchange="applyStafFilters()">
                                        <option value="">Semua BAHAGIAN</option>
                                        <?php foreach($uniqueBahagian as $bahagian): ?>
                                            <option value="<?php echo htmlspecialchars($bahagian); ?>"><?php echo htmlspecialchars($bahagian); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-2">
                                <!-- Row 2 -->
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">EMEL</label>
                                    <input type="text" id="filter_emel" class="form-control form-control-sm" placeholder="Filter EMEL" onkeyup="applyStafFilters()">
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label small text-muted mb-1">TELEFON</label>
                                    <input type="text" id="filter_telefon" class="form-control form-control-sm" placeholder="Filter TELEFON" onkeyup="applyStafFilters()">
                                </div>
                                <div class="col-md-3 col-lg-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-warning w-100" onclick="clearStafFilters()">
                                        <i class="fas fa-times me-1"></i>Clear Filter
                                    </button>
                                </div>
                                <div class="col-md-3 col-lg-2 d-flex align-items-end">
                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'create_user')): ?>
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="openStafAddModal()">
                                            <i class="fas fa-plus me-1"></i>Tambah Staf
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3 col-lg-2 d-flex align-items-end">
                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'export_data')): ?>
                                        <a href="dashboard_perjawatan.php?export=1&status=<?php echo htmlspecialchars($direktori_staf_status); ?>" class="btn btn-sm btn-success w-100" target="_blank">
                                            <i class="fas fa-file-excel me-1"></i>Export Excel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Count Display -->
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="text-muted small">
                                Menunjukkan <strong id="stafShowingFrom">0</strong> - <strong id="stafShowingTo">0</strong> daripada <strong id="stafTotalRecords">0</strong> rekod
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-sm" id="stafTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="py-3 px-3 text-center" width="5%" style="cursor: pointer;" onclick="sortStafRecords('#')">BIL <i class="fas fa-sort"></i></th>
                                <th class="py-3" style="cursor: pointer;" onclick="sortStafRecords('nama')">NAMA <i class="fas fa-sort"></i></th>
                                <th class="py-3" style="cursor: pointer;" onclick="sortStafRecords('jawatan')">JAWATAN <i class="fas fa-sort"></i></th>
                                <th class="py-3 text-center" style="cursor: pointer;" onclick="sortStafRecords('skim')">SKIM <i class="fas fa-sort"></i></th>
                                <th class="py-3 text-center" style="cursor: pointer;" onclick="sortStafRecords('gred')">GRED <i class="fas fa-sort"></i></th>
                                <th class="py-3" style="cursor: pointer;" onclick="sortStafRecords('bahagian')">BAHAGIAN <i class="fas fa-sort"></i></th>
                                <th class="py-3 text-center px-3">TINDAKAN</th>
                            </tr>
                        </thead>
                        <tbody id="stafTableBody">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pass all staf data to JavaScript -->
                <script>
                window.stafData = <?php echo json_encode($direktori_staf_data_all, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                window.stafCurrentStatus = <?php echo $direktori_staf_status; ?>;
                </script>

                <!-- Pagination (Client-side) -->
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0" id="stafPagination">
                        <!-- Pagination will be populated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Kalendar Hari Lahir (Bulan Ini) -->
    <div class="row g-4" id="birthdaySection" style="display: none; margin-top: 1rem;">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>Kalendar Hari Lahir
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div id="birthdayCalendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Birthday Event Details -->
    <div class="modal fade" id="birthdayEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: #d32f2f;">
                    <h3 class="modal-title"><i class="fas fa-user-tag me-3"></i>Profil Staf</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center border-end d-flex flex-column justify-content-center align-items-center">
                            <div class="mb-3 d-flex justify-content-center align-items-center">
                                <img id="birthdayModalPhoto" src="image/mawar.png" alt="Foto Staf" class="rounded-circle border" style="width: 120px; height: 120px; object-fit: cover; display: none;">
                                <i id="birthdayModalIcon" class="fas fa-user-circle fa-6x text-secondary"></i>
                            </div>
                            <h5 id="birthdayModalNama" class="fw-bold text-dark text-uppercase mb-2"></h5>
                            <span id="birthdayModalGredBadge" class="badge bg-success mb-3"></span>
                            <p class="text-muted small mb-0">Umur: <span id="birthdayModalUmur" class="fw-bold text-dark"></span> Tahun</p>
                            <p class="text-muted small">Tarikh Lahir: <span id="birthdayModalDob" class="fw-bold text-dark"></span></p>
                        </div>

                        <div class="col-md-8">
                            <table class="table table-sm table-borderless mt-2">
                                <tr><td width="30%" class="text-muted fw-bold">Jawatan</td><td width="5%">:</td><td id="birthdayModalJawatan" class="fw-bold"></td></tr>
                                <tr><td class="text-muted fw-bold">Bahagian</td><td>:</td><td id="birthdayModalBahagian"></td></tr>
                                <tr><td class="text-muted fw-bold">Gred</td><td>:</td><td id="birthdayModalGred"></td></tr>
                                <tr><td colspan="3"><hr class="my-2"></td></tr>
                                <tr><td class="text-muted fw-bold"><i class="fas fa-envelope me-2"></i>Emel</td><td>:</td><td id="birthdayModalEmel" class="text-primary"></td></tr>
                                <tr><td class="text-muted fw-bold"><i class="fas fa-phone me-2"></i>Telefon</td><td>:</td><td id="birthdayModalTelefon"></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <form method="POST" action="proses_staf.php" class="w-100 d-flex justify-content-end">
                        <?php echo getCsrfTokenField(); ?>
                        <input type="hidden" name="id_user_wish" id="birthdayInputIdUserWish">
                        <input type="hidden" name="from_embed" value="0">
                        <?php if ($_SESSION['role'] === 'super_admin'): ?>
                            <button type="submit" name="send_wish" class="btn btn-success btn-sm">
                                <i class="fas fa-birthday-cake me-2"></i> Hantar Ucapan
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary btn-sm ms-2" data-bs-dismiss="modal">Tutup</button>
                    </form>
                </div>
            </div>
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
// Debug: Check if birthdaySection exists
if (!birthdaySection) {
    console.warn('⚠️ birthdaySection element not found! Check if ID exists in HTML.');
} else {
    console.log('✅ birthdaySection element found:', birthdaySection);
}
const tertinggiSection = document.getElementById('tertinggiChartsSection');
const pengurusanSection = document.getElementById('pengurusanChartsSection');
const sokongan1Section = document.getElementById('sokongan1ChartsSection');
const sokongan2Section = document.getElementById('sokongan2ChartsSection');
const summaryCards = document.querySelectorAll('.summary-card-staf');

// Helper function to scroll to element smoothly with offset
function scrollToElement(element, offset = 100) {
    if (!element) {
        console.error('❌ scrollToElement: element is null or undefined');
        return;
    }
    
    console.log('📍 Scrolling to element:', element.id || element.className);
    
    // Ensure element is visible - use 'block' to override inline 'display: none'
    if (element.style.display === 'none' || element.style.display === '') {
        element.style.display = 'block';
        console.log('✅ Element display changed to block');
    }
    
    // Wait for DOM to update
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            try {
                const topBar = document.querySelector('.top-bar');
                const topBarHeight = topBar ? topBar.offsetHeight : 80;
                const elementRect = element.getBoundingClientRect();
                const elementPosition = elementRect.top;
                const offsetPosition = elementPosition + window.pageYOffset - topBarHeight - offset;
                
                console.log('📊 Scroll info:', {
                    topBarHeight,
                    elementPosition,
                    currentScrollY: window.pageYOffset,
                    targetScrollY: Math.max(0, offsetPosition),
                    elementVisible: elementRect.height > 0
                });
                
                // Scroll to position
                window.scrollTo({
                    top: Math.max(0, offsetPosition),
                    behavior: 'smooth'
                });
                
                // Fallback: Use scrollIntoView after a delay
                setTimeout(() => {
                    if (element.getBoundingClientRect().top < topBarHeight + offset) {
                        console.log('🔄 Using fallback scrollIntoView');
                        element.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start',
                            inline: 'nearest'
                        });
                    }
                }, 200);
            } catch (e) {
                console.error('❌ Error scrolling to element:', e);
                // Last resort: simple scrollIntoView
                try {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } catch (e2) {
                    console.error('❌ Even fallback scroll failed:', e2);
                }
            }
        });
    });
}

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
        // Initialize staf data if not already initialized
        if (typeof window.stafData !== 'undefined' && window.stafData.length > 0 && stafAllData.length === 0) {
            stafAllData = window.stafData.map((row, idx) => ({
                ...row,
                _originalIndex: idx
            }));
            stafFilteredData = [...stafAllData];
            stafSortedData = [...stafAllData];
            applyStafFilters();
        }
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
    if (birthdaySection) {
        // Show the section first - use 'block' to override inline 'display: none'
        birthdaySection.style.display = 'block';
        
        console.log('✅ Birthday section displayed:', birthdaySection.style.display);
        console.log('📍 Birthday section element:', birthdaySection);
        
        // Calendar is now embedded directly, no need to reload iframe
        // Calendar will initialize automatically when section is shown
        console.log('✅ Birthday section displayed - calendar will initialize automatically');
        
        // Update URL hash
        window.location.hash = 'birthdaySection';
        
        // Wait a bit for DOM to update, then scroll
        setTimeout(() => {
            // Scroll to birthday section using helper function
            scrollToElement(birthdaySection, 20);
        }, 200);
    } else {
        console.error('❌ birthdaySection element not found!');
    }
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

// Function to clear all filters
function clearFilters() {
    const form = document.getElementById('direktoriStafFilterForm');
    if (form) {
        // Clear all filter inputs
        const inputs = form.querySelectorAll('input[type="text"], select');
        inputs.forEach(input => {
            input.value = '';
        });
        // Submit form to reload without filters
        form.submit();
    }
}

// ============================================
// STAF LIST VIEW - CLIENT-SIDE FILTERING
// ============================================
let stafAllData = [];
let stafFilteredData = [];
let stafSortedData = [];
let stafCurrentPage = 1;
let stafRecordsPerPage = 20;
let stafSortColumn = null;
let stafSortDirection = 'asc';

// Initialize staf data when direktori staf section is shown
document.addEventListener('DOMContentLoaded', function() {
    // Check if direktori staf section is visible
    const direktoriStafSection = document.getElementById('direktoriStafContainer');
    if (direktoriStafSection && (direktoriStafSection.style.display !== 'none' || window.location.hash === '#direktoriStafContainer')) {
        if (typeof window.stafData !== 'undefined' && window.stafData.length > 0) {
            stafAllData = window.stafData.map((row, idx) => ({
                ...row,
                _originalIndex: idx
            }));
            stafFilteredData = [...stafAllData];
            stafSortedData = [...stafAllData];
            applyStafFilters();
        }
    }
});

// Function to apply filters (real-time)
function applyStafFilters() {
    // Get all filter values
    const filters = {
        nama: document.getElementById('filter_nama')?.value.trim().toLowerCase() || '',
        no_kp: document.getElementById('filter_no_kp')?.value.trim().toLowerCase() || '',
        jawatan: document.getElementById('filter_jawatan')?.value.trim() || '',
        skim: document.getElementById('filter_skim')?.value.trim() || '',
        gred: document.getElementById('filter_gred')?.value.trim() || '',
        bahagian: document.getElementById('filter_bahagian')?.value.trim() || '',
        emel: document.getElementById('filter_emel')?.value.trim().toLowerCase() || '',
        telefon: document.getElementById('filter_telefon')?.value.trim().toLowerCase() || ''
    };
    
    // Filter data
    stafFilteredData = stafAllData.filter(row => {
        // Text filters (partial match, case-insensitive)
        if (filters.nama && !String(row.nama || '').toLowerCase().includes(filters.nama)) return false;
        if (filters.no_kp && !String(row.no_kp || '').toLowerCase().includes(filters.no_kp)) return false;
        if (filters.emel && !String(row.emel || '').toLowerCase().includes(filters.emel)) return false;
        if (filters.telefon && !String(row.telefon || '').toLowerCase().includes(filters.telefon)) return false;
        
        // Dropdown filters (exact match)
        if (filters.jawatan && String(row.jawatan || '').trim() !== filters.jawatan) return false;
        if (filters.skim && String(row.skim || '').trim() !== filters.skim) return false;
        if (filters.gred && String(row.gred || '').trim() !== filters.gred) return false;
        if (filters.bahagian && String(row.bahagian || '').trim() !== filters.bahagian) return false;
        
        return true;
    });
    
    // Apply sorting
    applyStafSorting();
    
    // Reset to first page
    stafCurrentPage = 1;
    
    // Update display
    updateStafDisplay();
}

// Function to apply sorting
function applyStafSorting() {
    if (!stafSortColumn) {
        stafSortedData = [...stafFilteredData];
        return;
    }
    
    stafSortedData = [...stafFilteredData].sort((a, b) => {
        let aVal, bVal;
        
        if (stafSortColumn === '#') {
            aVal = a._originalIndex;
            bVal = b._originalIndex;
        } else {
            aVal = a[stafSortColumn] || '';
            bVal = b[stafSortColumn] || '';
        }
        
        // Convert to string for comparison
        aVal = String(aVal).toLowerCase();
        bVal = String(bVal).toLowerCase();
        
        // Try numeric comparison first
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return stafSortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        if (aVal < bVal) return stafSortDirection === 'asc' ? -1 : 1;
        if (aVal > bVal) return stafSortDirection === 'asc' ? 1 : -1;
        return 0;
    });
}

// Function to sort records
function sortStafRecords(column) {
    if (stafSortColumn === column) {
        stafSortDirection = stafSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        stafSortColumn = column;
        stafSortDirection = 'asc';
    }
    
    applyStafSorting();
    updateStafDisplay();
    updateStafSortIcons();
}

// Function to update sort icons
function updateStafSortIcons() {
    const headers = document.querySelectorAll('#stafTable thead th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-sort';
            icon.style.opacity = '0.3';
        }
    });
    
    if (stafSortColumn) {
        const headerIndex = stafSortColumn === '#' ? 0 : Array.from(document.querySelectorAll('#stafTable thead th')).findIndex(th => {
            const onclick = th.getAttribute('onclick');
            return onclick && onclick.includes(`'${stafSortColumn}'`);
        });
        
        if (headerIndex >= 0) {
            const header = document.querySelectorAll('#stafTable thead th')[headerIndex];
            const icon = header.querySelector('i');
            if (icon) {
                icon.className = stafSortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                icon.style.opacity = '1';
            }
        }
    }
}

// Function to update display
function updateStafDisplay() {
    const tbody = document.getElementById('stafTableBody');
    const showingFrom = document.getElementById('stafShowingFrom');
    const showingTo = document.getElementById('stafShowingTo');
    const totalRecords = document.getElementById('stafTotalRecords');
    
    if (!tbody) return;
    
    const total = stafSortedData.length;
    const totalPages = Math.ceil(total / stafRecordsPerPage);
    
    // Update count
    if (totalRecords) totalRecords.textContent = total;
    
    // Calculate pagination
    const startIndex = (stafCurrentPage - 1) * stafRecordsPerPage;
    const endIndex = Math.min(startIndex + stafRecordsPerPage, total);
    const pageRecords = stafSortedData.slice(startIndex, endIndex);
    
    // Update showing range
    if (showingFrom) showingFrom.textContent = total > 0 ? startIndex + 1 : 0;
    if (showingTo) showingTo.textContent = endIndex;
    
    // Clear tbody
    tbody.innerHTML = '';
    
    // Add rows
    pageRecords.forEach((row, pageIndex) => {
        const tr = document.createElement('tr');
        const bil = startIndex + pageIndex + 1;
        
        let rowHtml = `<td class="text-center fw-bold text-muted">${bil}</td>`;
        rowHtml += `<td>
            <a href="javascript:void(0);"
               class="nama-link staf-detail-link"
               style="color: #0d6efd; font-weight: 600; text-decoration: none;"
               data-id="${row.id_user || ''}"
               data-nama="${(row.nama || '').replace(/"/g, '&quot;')}"
               data-nokp="${(row.no_kp || '').replace(/"/g, '&quot;')}"
               data-jawatan="${(row.jawatan || '-').replace(/"/g, '&quot;')}"
               data-skim="${(row.skim || '-').replace(/"/g, '&quot;')}"
               data-gred="${(row.gred || '-').replace(/"/g, '&quot;')}"
               data-bahagian="${(row.bahagian || '-').replace(/"/g, '&quot;')}"
               data-emel="${(row.emel || '').replace(/"/g, '&quot;')}"
               data-telefon="${(row.telefon || '').replace(/"/g, '&quot;')}"
               data-status="${row.id_status_staf || ''}"
               data-foto="${(row.gambar ? 'uploads/' + row.gambar : '').replace(/"/g, '&quot;')}"
            >
                ${(row.nama || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
            </a>
        </td>`;
        rowHtml += `<td class="small text-muted">${(row.jawatan || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`;
        rowHtml += `<td class="text-center fw-bold text-secondary">${(row.skim || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`;
        rowHtml += `<td class="text-center">${(row.gred || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`;
        rowHtml += `<td class="small text-muted">${(row.bahagian || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`;
        rowHtml += `<td class="text-center px-3">`;
        <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_user')): ?>
        rowHtml += `<button type="button" class="btn btn-sm btn-warning" onclick="openStafEditModal(${row.id_user || ''})" title="Edit"><i class="fas fa-edit"></i></button>`;
        <?php endif; ?>
        rowHtml += `</td>`;
        
        tr.innerHTML = rowHtml;
        tbody.appendChild(tr);
    });
    
    if (total === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Tiada rekod dijumpai.</td></tr>';
    }
    
    // Update pagination
    updateStafPagination(totalPages);
    
    // Re-attach click listeners for detail modal
    attachStafDetailListeners();
}

// Function to update pagination
function updateStafPagination(totalPages) {
    const pagination = document.getElementById('stafPagination');
    if (!pagination) return;
    
    pagination.innerHTML = '';
    
    if (totalPages <= 1) {
        return;
    }
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${stafCurrentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToStafPage(${stafCurrentPage - 1}); return false;">Sebelum</a>`;
    pagination.appendChild(prevLi);
    
    // Page numbers
    const maxPagesToShow = 7;
    let startPage = Math.max(1, stafCurrentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToStafPage(1); return false;">1</a>`;
        pagination.appendChild(firstLi);
        
        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsisLi);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === stafCurrentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToStafPage(${i}); return false;">${i}</a>`;
        pagination.appendChild(li);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsisLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToStafPage(${totalPages}); return false;">${totalPages}</a>`;
        pagination.appendChild(lastLi);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${stafCurrentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToStafPage(${stafCurrentPage + 1}); return false;">Selepas</a>`;
    pagination.appendChild(nextLi);
}

// Function to go to specific page
function goToStafPage(page) {
    const totalPages = Math.ceil(stafSortedData.length / stafRecordsPerPage);
    if (page < 1 || page > totalPages) return;
    stafCurrentPage = page;
    updateStafDisplay();
    
    // Scroll to top of table
    const table = document.getElementById('stafTable');
    if (table) {
        table.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Function to clear all filters
function clearStafFilters() {
    document.getElementById('filter_nama').value = '';
    document.getElementById('filter_no_kp').value = '';
    document.getElementById('filter_jawatan').value = '';
    document.getElementById('filter_skim').value = '';
    document.getElementById('filter_gred').value = '';
    document.getElementById('filter_bahagian').value = '';
    document.getElementById('filter_emel').value = '';
    document.getElementById('filter_telefon').value = '';
    applyStafFilters();
}

// Function to attach detail modal listeners
function attachStafDetailListeners() {
    const detailLinks = document.querySelectorAll('.staf-detail-link');
    if (detailLinks.length > 0 && typeof bootstrap !== 'undefined') {
        detailLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const ds = this.dataset;
                document.getElementById('detailNama').textContent = ds.nama || '-';
                document.getElementById('detailNoKp').textContent = ds.nokp || '-';
                document.getElementById('detailJawatan').textContent = ds.jawatan || '-';
                document.getElementById('detailSkim').textContent = ds.skim || '-';
                document.getElementById('detailGred').textContent = ds.gred || '-';
                document.getElementById('detailBahagian').textContent = ds.bahagian || '-';
                document.getElementById('detailEmel').textContent = ds.emel || '-';
                document.getElementById('detailTelefon').textContent = ds.telefon || '-';
                
                const statusText = ds.status == '1' ? 'Masih Bekerja' : ds.status == '2' ? 'Bersara' : 'Berhenti';
                const statusClass = ds.status == '1' ? 'bg-success' : ds.status == '2' ? 'bg-warning' : 'bg-danger';
                document.getElementById('detailStatus').textContent = statusText;
                document.getElementById('detailStatus').className = 'badge ' + statusClass;
                
                if (ds.foto) {
                    document.getElementById('detailPhoto').src = ds.foto;
                } else {
                    document.getElementById('detailPhoto').src = 'image/mawar.png';
                }
                
                const modal = new bootstrap.Modal(document.getElementById('stafDetailModal'));
                modal.show();
            });
        });
    }
}

// Function to handle hash navigation
function handleHashNavigation() {
    const hash = window.location.hash;
    
    if (hash === '#direktoriStafContainer' || window.location.search.includes('direktori_staf_')) {
        // Show direktori staf if hash exists or query params present
        hideAllSections();
        if (direktoriStafSection) {
            direktoriStafSection.style.display = 'block';
            if (stafCard) {
                summaryCards.forEach(c => c.classList.remove('active'));
                stafCard.classList.add('active');
            }
            // Initialize staf data if not already initialized
            if (typeof window.stafData !== 'undefined' && window.stafData.length > 0 && stafAllData.length === 0) {
                stafAllData = window.stafData.map((row, idx) => ({
                    ...row,
                    _originalIndex: idx
                }));
                stafFilteredData = [...stafAllData];
                stafSortedData = [...stafAllData];
                applyStafFilters();
            }
            setTimeout(() => {
                direktoriStafSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);
        }
    } else if (hash === '#birthdaySection') {
        // Show birthday calendar if hash exists
        hideAllSections();
        if (birthdaySection) {
            // Use 'block' to override inline 'display: none'
            birthdaySection.style.display = 'block';
            
            if (birthdayCard) {
                summaryCards.forEach(c => c.classList.remove('active'));
                birthdayCard.classList.add('active');
            }
            
            // Calendar is now embedded directly, no need to reload iframe
            // Calendar will initialize automatically when section is shown
            console.log('✅ Birthday section displayed from hash - calendar will initialize automatically');
            
            // Wait a bit for DOM to update, then scroll
            setTimeout(() => {
                scrollToElement(birthdaySection, 20);
            }, 100);
        } else {
            console.error('❌ birthdaySection not found when handling hash!');
        }
    } else {
        // Default: Show Jawatan charts
        hideAllSections();
        if (jawatanCard) {
            summaryCards.forEach(c => c.classList.remove('active'));
            jawatanCard.classList.add('active');
            showJawatanCharts();
        }
    }
}

// Hide all sections on initial load
hideAllSections();

// On page load, check for hash or query params
// Use DOMContentLoaded to ensure all elements are ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Check hash immediately and also after a short delay
        handleHashNavigation();
        setTimeout(handleHashNavigation, 200);
    });
} else {
    // DOM already loaded
    handleHashNavigation();
    setTimeout(handleHashNavigation, 200);
}

// Also check immediately if hash exists (for fast redirects)
if (window.location.hash) {
    setTimeout(handleHashNavigation, 50);
}

// Also listen for hash changes (e.g., when navigating back or redirect)
window.addEventListener('hashchange', function() {
    handleHashNavigation();
});

// Also check hash on page load (in case page loads with hash already in URL)
window.addEventListener('load', function() {
    if (window.location.hash) {
        setTimeout(handleHashNavigation, 200);
    }
});

// Add click listeners
summaryCards.forEach(card => {
    card.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
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
        
        return false;
    });
});

// NOTE:
// Kad "Hari Lahir" sudah dikawal oleh handler umum `summaryCards.forEach(...)`
// di atas. Sebelum ini ada kod tambahan yang clone elemen dan pasang event
// listener berasingan untuk kad tersebut. Kesan sampingan:
//  - `summaryCards` masih merujuk kepada elemen lama yang sudah dibuang
//  - kelas `.active` pada kad baharu tidak dibuang bila kad lain diklik
//  - hasilnya, kad kalendar nampak sentiasa besar / shadow tebal
//
// Kod khas itu dibuang supaya semua kad ringkasan guna logik yang sama,
// dan kelas `.active` akan toggle dengan betul bila bertukar kad.

// Ensure list view stays visible after form submission
const direktoriStafSearchForm = document.getElementById('direktoriStafSearchForm');
if (direktoriStafSearchForm) {
    direktoriStafSearchForm.addEventListener('submit', function() {
        // Ensure list view is shown after search
        setTimeout(() => {
            if (direktoriStafSection) {
                direktoriStafSection.style.display = 'block';
                if (stafCard) stafCard.classList.add('active');
            }
        }, 100);
    });
}

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

<!-- JavaScript untuk Handle Add/Edit Staf Modal -->
<script>
// Function untuk preview gambar sebelum upload
function previewStafImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('stafFormPhotoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Function untuk buka modal Add Staf
function openStafAddModal() {
    // Reset form
    document.getElementById('stafAddEditForm').reset();
    document.getElementById('stafFormIdUser').value = '';
    document.getElementById('stafFormMode').value = 'add';
    document.getElementById('stafModalTitle').textContent = 'Tambah Staf';
    document.getElementById('stafFormPhotoPreview').src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('stafAddEditModal'));
    modal.show();
}

// Function untuk buka modal Edit Staf
function openStafEditModal(idUser) {
    if (!idUser) {
        alert('ID Staf tidak sah!');
        return;
    }
    
    // Cari data dari window.stafData yang sudah dimuatkan
    let staf = null;
    if (typeof window.stafData !== 'undefined' && Array.isArray(window.stafData)) {
        staf = window.stafData.find(s => s.id_user == idUser);
    }
    
    if (!staf) {
        // Jika data tidak dijumpai, load via AJAX
        fetch(`api/staf.php?id=${idUser}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    populateEditForm(data.data);
                    const modal = new bootstrap.Modal(document.getElementById('stafAddEditModal'));
                    modal.show();
                } else {
                    alert('Gagal memuatkan data staf!');
                }
            })
            .catch(error => {
                console.error('Error loading staf data:', error);
                alert('Ralat memuatkan data staf!');
            });
        return;
    }
    
    // Populate form dengan data yang sudah ada
    populateEditForm(staf);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('stafAddEditModal'));
    modal.show();
}

// Function untuk populate form edit
function populateEditForm(staf) {
    document.getElementById('stafFormIdUser').value = staf.id_user || '';
    document.getElementById('stafFormMode').value = 'edit';
    document.getElementById('stafModalTitle').textContent = 'Kemaskini Profil Staf';
    document.getElementById('stafFormNoStaf').value = staf.no_staf || '';
    document.getElementById('stafFormNoKp').value = staf.no_kp || '';
    document.getElementById('stafFormNama').value = staf.nama || '';
    document.getElementById('stafFormJawatan').value = staf.id_jawatan || '';
    document.getElementById('stafFormGred').value = staf.id_gred || '';
    document.getElementById('stafFormBahagian').value = staf.id_bahagian || '';
    document.getElementById('stafFormStatus').value = staf.id_status_staf || '';
    document.getElementById('stafFormEmel').value = staf.emel || '';
    document.getElementById('stafFormTelefon').value = staf.telefon || '';
    
    // Set gambar preview
    if (staf.gambar) {
        document.getElementById('stafFormPhotoPreview').src = 'uploads/' + staf.gambar;
    } else {
        document.getElementById('stafFormPhotoPreview').src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
    }
}

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const stafForm = document.getElementById('stafAddEditForm');
    const submitBtn = document.getElementById('stafFormSubmitBtn');
    
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!stafForm.checkValidity()) {
                stafForm.reportValidity();
                return;
            }
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
            
            // Create FormData
            const formData = new FormData(stafForm);
            const mode = document.getElementById('stafFormMode').value;
            
            if (mode === 'add') {
                formData.append('save_new', '1');
            } else {
                formData.append('update', '1');
            }
            
            // Submit via AJAX
            fetch('proses_staf.php', {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('stafAddEditModal'));
                    if (modal) modal.hide();
                    
                    // Show success message
                    if (message) {
                        alert(message);
                    }
                    
                    // Reload page to show updated data
                    setTimeout(() => {
                        const currentHash = window.location.hash;
                        const currentParams = window.location.search;
                        window.location.href = 'dashboard_perjawatan.php' + currentParams + currentHash;
                    }, 500);
                } else if (data.includes('Gagal') || data.includes('gagal') || data.includes('Tidak Dibenarkan') || message.includes('Gagal')) {
                    // Error - show alert
                    alert(message || 'Gagal menyimpan data! Sila cuba lagi.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
                } else {
                    // Try to parse as JSON if possible
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('stafAddEditModal'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                const currentHash = window.location.hash;
                                const currentParams = window.location.search;
                                window.location.href = 'dashboard_perjawatan.php' + currentParams + currentHash;
                            }, 500);
                        } else {
                            alert(jsonData.message || 'Gagal menyimpan data!');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan';
                        }
                    } catch (e) {
                        // Not JSON, might be HTML response - assume success if no error keywords
                        if (!data.includes('error') && !data.includes('Gagal')) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('stafAddEditModal'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                const currentHash = window.location.hash;
                                const currentParams = window.location.search;
                                window.location.href = 'dashboard_perjawatan.php' + currentParams + currentHash;
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

<!-- FullCalendar CSS and JS for Birthday Calendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js' onerror="console.error('Failed to load FullCalendar main.js')"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js' onerror="console.error('Failed to load FullCalendar locales')"></script>

<style>
    /* Birthday Calendar Styles */
    #birthdayCalendar {
        min-height: 500px;
        width: 100%;
        position: relative;
        display: block !important;
        visibility: visible !important;
    }
    
    #birthdaySection .fc { 
        font-family: inherit;
        display: block !important;
    }
    
    #birthdaySection .fc-view-harness {
        min-height: 400px !important;
    }
    
    #birthdaySection .fc-event { cursor: pointer; }
    #birthdaySection .fc-button-primary { background-color: #d32f2f !important; border-color: #b71c1c !important; }
    #birthdaySection .fc-button-primary:hover { background-color: #b71c1c !important; }
    #birthdaySection .fc-button-active { background-color: #a00000 !important; }
    #birthdaySection .fc-col-header-cell.fc-day-sun { color: red; }
    
    /* Fix text truncation in calendar events - allow text wrapping */
    #birthdaySection .fc-event-title {
        white-space: normal !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.3 !important;
        padding: 2px 4px !important;
        font-size: 11px !important;
    }
    
    #birthdaySection .fc-daygrid-event {
        white-space: normal !important;
    }
    
    #birthdaySection .fc-daygrid-day-frame {
        min-height: 70px !important;
    }
    
    /* Hide event dot and time */
    #birthdaySection .fc-daygrid-event-dot {
        display: none !important;
    }
    
    #birthdaySection .fc-event-time {
        display: none !important;
    }
    
    /* MOBILE RESPONSIVE */
    @media (max-width: 768px) {
        #birthdaySection .fc-toolbar {
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 5px;
        }
        
        #birthdaySection .fc-button {
            padding: 6px 10px !important;
            font-size: 12px !important;
            margin: 2px !important;
        }
    }
</style>

<script>
// Initialize Birthday Calendar
(function() {
    'use strict';
    
    var birthdayCalendarEl = null;
    var birthdayCalendar = null;
    
    function initBirthdayCalendar() {
        birthdayCalendarEl = document.getElementById('birthdayCalendar');
        
        if (!birthdayCalendarEl) {
            // Calendar element not found yet, retry after a delay
            setTimeout(initBirthdayCalendar, 100);
            return;
        }
        
        // Check if FullCalendar is loaded
        var retryCount = 0;
        var maxRetries = 25; // 25 * 200ms = 5 seconds
        
        function checkFullCalendar() {
            if (typeof FullCalendar !== 'undefined') {
                createBirthdayCalendar();
            } else if (retryCount < maxRetries) {
                retryCount++;
                setTimeout(checkFullCalendar, 200);
            } else {
                console.error('FullCalendar library failed to load after 5 seconds!');
                birthdayCalendarEl.innerHTML = '<div class="alert alert-danger">Error: FullCalendar library tidak dapat dimuatkan. Sila refresh halaman.</div>';
            }
        }
        
        function createBirthdayCalendar() {
            // Get events data from PHP
            var eventsData = <?php echo json_encode($birthdayEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            console.log('📅 Birthday Calendar Events loaded:', eventsData.length);
            
            if (eventsData.length === 0) {
                console.warn('⚠️ No birthday events found! Calendar will still render.');
            } else {
                console.log('📅 Sample events (first 5):', eventsData.slice(0, 5));
            }
            
            try {
                // Set initial date to current month
                var currentDate = new Date();
                var initialDate = currentDate.getFullYear() + '-' + String(currentDate.getMonth() + 1).padStart(2, '0') + '-01';
                
                var calendarConfig = {
                    initialView: 'dayGridMonth',
                    locale: 'ms',
                    initialDate: initialDate,
                    events: eventsData,
                    headerToolbar: { 
                        left: 'prev,next today', 
                        center: 'title', 
                        right: 'dayGridMonth,listMonth' 
                    },
                    buttonText: { 
                        today: 'Hari Ini', 
                        month: 'Bulan', 
                        list: 'Senarai',
                        prev: '←',
                        next: '→'
                    },
                    dayCellClassNames: function(arg) {
                        if(arg.date.getDay() === 0) return 'fc-col-header-cell-sunday';
                    },
                    dayHeaderFormat: { weekday: 'short' },
                    dayCellContent: function(arg) {
                        return arg.dayNumberText;
                    },
                    eventDisplay: 'block', 
                    eventColor: '#3788d8',
                    
                    eventContent: function(arg) {
                        try {
                            var title = arg.event.title || '';
                            if (!title) return { html: '' };
                            
                            var escapedTitle = String(title).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
                            return {
                                html: '<div class="fc-event-title" title="' + escapedTitle + '">' + title + '</div>'
                            };
                        } catch(e) {
                            console.error('Event content error:', e);
                            return { html: arg.event.title || '' };
                        }
                    },
                    
                    dayMaxEvents: 10,

                    eventClick: function(info) {
                        info.jsEvent.preventDefault(); 
                        var p = info.event.extendedProps;
                        
                        // Calculate age based on clicked year
                        var event_date = info.event.start;
                        var clicked_year = new Date(event_date).getFullYear();
                        var birth_year = parseInt(p.birth_year);
                        var age = clicked_year - birth_year;

                        document.getElementById('birthdayModalNama').textContent = info.event.title;
                        document.getElementById('birthdayModalJawatan').textContent = p.jawatan;
                        document.getElementById('birthdayModalBahagian').textContent = p.bahagian;
                        document.getElementById('birthdayModalGred').textContent = p.gred;
                        document.getElementById('birthdayModalGredBadge').textContent = "Gred " + p.gred;
                        document.getElementById('birthdayModalEmel').textContent = p.emel;
                        document.getElementById('birthdayModalTelefon').textContent = p.telefon;
                        document.getElementById('birthdayModalDob').textContent = p.dob;
                        document.getElementById('birthdayModalUmur').textContent = age;
                        
                        // Handle gambar staf
                        const photoEl = document.getElementById('birthdayModalPhoto');
                        const iconEl = document.getElementById('birthdayModalIcon');
                        
                        if (photoEl && iconEl) {
                            if (p.gambar && p.gambar.trim() !== '') {
                                // Ada gambar - paparkan gambar dan sembunyikan icon
                                photoEl.src = p.gambar;
                                photoEl.style.display = 'block';
                                iconEl.style.display = 'none';
                                
                                // Handle jika gambar gagal load
                                photoEl.onerror = function() {
                                    console.warn('Gambar staf gagal dimuatkan:', p.gambar);
                                    this.style.display = 'none';
                                    iconEl.style.display = 'block';
                                };
                                
                                // Pastikan gambar dimuatkan dengan betul
                                photoEl.onload = function() {
                                    console.log('Gambar staf berjaya dimuatkan:', p.gambar);
                                };
                            } else {
                                // Tiada gambar - paparkan icon dan sembunyikan gambar
                                photoEl.style.display = 'none';
                                iconEl.style.display = 'block';
                            }
                        }
                        
                        // Set ID untuk form
                        document.getElementById('birthdayInputIdUserWish').value = p.id_user;

                        var myModal = new bootstrap.Modal(document.getElementById('birthdayEventModal'));
                        myModal.show();
                    }
                };
                
                birthdayCalendar = new FullCalendar.Calendar(birthdayCalendarEl, calendarConfig);
                birthdayCalendar.render();
                console.log('✅ Birthday Calendar rendered successfully with', eventsData.length, 'events');
                
                // Force calendar to update display
                setTimeout(function() {
                    birthdayCalendar.updateSize();
                }, 100);
                
            } catch(e) {
                console.error('❌ Error creating/rendering birthday calendar:', e);
                birthdayCalendarEl.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
            }
        }
        
        // Start checking for FullCalendar
        checkFullCalendar();
    }
    
    // Initialize calendar when birthday section is shown
    function initCalendarWhenShown() {
        var birthdaySection = document.getElementById('birthdaySection');
        if (birthdaySection && birthdaySection.style.display !== 'none') {
            if (!birthdayCalendar) {
                initBirthdayCalendar();
            } else {
                // Calendar already exists, just update size
                setTimeout(function() {
                    birthdayCalendar.updateSize();
                }, 100);
            }
        }
    }
    
    // Try to initialize immediately if section is visible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initCalendarWhenShown, 500);
        });
    } else {
        setTimeout(initCalendarWhenShown, 500);
    }
    
    // Also initialize when showBirthday is called
    var originalShowBirthday = window.showBirthday;
    if (originalShowBirthday) {
        window.showBirthday = function() {
            originalShowBirthday();
            setTimeout(function() {
                if (!birthdayCalendar) {
                    initBirthdayCalendar();
                } else {
                    birthdayCalendar.updateSize();
                }
            }, 300);
        };
    }
})();
</script>

</div></body></html>
