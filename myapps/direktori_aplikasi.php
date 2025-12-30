<?php
require 'db.php';

// ============================================================
// 1. LOGIC EXPORT EXCEL - DAFTAR APLIKASI
// ============================================================
if (isset($_GET['export'])) {
    if (ob_get_length()) ob_end_clean();

    $filename = "Direktori_Aplikasi_MyApps_" . date('Ymd') . ".xls";

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
    
    // QUERY EXPORT
    $sqlExport = "SELECT a.id_aplikasi, a.nama_aplikasi, k.nama_kategori, 
                         a.keterangan, a.url, a.sso_comply
                  FROM aplikasi a 
                  LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
                  WHERE a.status = 1
                  ORDER BY a.id_kategori ASC, a.id_aplikasi ASC, a.status DESC";
                  
    $stmt = $db->query($sqlExport);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id_aplikasi'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_aplikasi'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_kategori'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars(substr($row['keterangan'] ?? '', 0, 50)) . '</td>';
        echo '<td>' . htmlspecialchars($row['url'] ?? '') . '</td>';
        echo '<td style="text-align:center;">' . ($row['sso_comply'] == 1 ? '✓ SSO' : '') . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit(); 
}

include 'header.php';

$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$sort = $_GET['sort'] ?? 'id_kategori';
$order = $_GET['order'] ?? 'ASC';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$allowed_sort = ['id_kategori', 'id_aplikasi', 'nama_aplikasi', 'kategori', 'tarikh_daftar'];
if (!in_array($sort, $allowed_sort)) { $sort = 'id_kategori'; }

$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Query count
$sqlCount = "SELECT COUNT(*) as total FROM aplikasi a 
             LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
             WHERE 1=1";

if (!empty($kategori_filter)) {
    $sqlCount .= " AND a.id_kategori = " . intval($kategori_filter);
}
if (!empty($search)) {
    $sqlCount .= " AND (a.nama_aplikasi LIKE ? OR a.keterangan LIKE ? OR k.nama_kategori LIKE ?)";
}

$stmt = $db->prepare($sqlCount);
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $items_per_page;
}

// Query aplikasi
$sql = "SELECT a.*, k.nama_kategori 
        FROM aplikasi a 
        LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
        WHERE 1=1";

if (!empty($kategori_filter)) {
    $sql .= " AND a.id_kategori = " . intval($kategori_filter);
}
if (!empty($search)) {
    $sql .= " AND (a.nama_aplikasi LIKE ? OR a.keterangan LIKE ? OR k.nama_kategori LIKE ?)";
}

// Handle sorting with proper field mapping
if ($sort === 'kategori') {
    $sql .= " ORDER BY k.nama_kategori $order, a.id_aplikasi ASC";
} elseif ($sort === 'id_kategori') {
    $sql .= " ORDER BY a.id_kategori $order, a.id_aplikasi ASC";
} elseif ($sort === 'id_aplikasi') {
    $sql .= " ORDER BY a.id_aplikasi $order";
} else {
    $sql .= " ORDER BY $sort $order";
}
$sql .= " LIMIT $items_per_page OFFSET $offset";

$stmt = $db->prepare($sql);
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$data = $stmt->fetchAll();

// Get kategori list
$kategoriList = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);

// Count by kategori
$allApps = $db->query("SELECT id_kategori, COUNT(*) as total FROM aplikasi GROUP BY id_kategori")->fetchAll(PDO::FETCH_KEY_PAIR);

function sortLink($col, $currentSort, $currentOrder, $currentSearch, $currentKat) {
    // Map display names to sort field names
    $sortField = $col;
    if ($col === 'KATEGORI') { $sortField = 'id_kategori'; }
    if ($col === 'APLIKASI') { $sortField = 'id_aplikasi'; }
    
    $newOrder = ($currentSort == $sortField && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort == $sortField) ? (($currentOrder == 'ASC') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted opacity-25"></i>';
    return "<a href='?sort=$sortField&order=$newOrder&search=$currentSearch&kategori=$currentKat' class='text-dark text-decoration-none fw-bold'>$icon</a>";
}
?>

<style>
    .cursor-pointer { cursor: pointer; }
    .nama-link { color: #0d6efd; font-weight: 600; text-decoration: none; }
    .nama-link:hover { text-decoration: underline; color: #0a58ca; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">
            <i class="fas fa-list me-2 text-primary"></i> Direktori Aplikasi
        </h3>
        <div class="d-flex gap-2">
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="proses_aplikasi.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Tambah Aplikasi
                </a>
            <?php endif; ?>
            <a href="?export=1" class="btn btn-success" target="_blank">
                <i class="fas fa-file-excel me-2"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Filter by Kategori -->
    <div class="mb-4">
        <div class="btn-group btn-group-sm" role="group">
            <a href="?kategori=" class="btn btn-outline-primary <?php echo empty($kategori_filter) ? 'active' : ''; ?>">
                <i class="fas fa-list me-1"></i> Semua (<?php echo $total_records; ?>)
            </a>
            <?php foreach ($kategoriList as $kat): ?>
                <a href="?kategori=<?php echo $kat['id_kategori']; ?>" 
                   class="btn btn-outline-primary <?php echo ($kategori_filter == $kat['id_kategori']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                    (<?php echo $allApps[$kat['id_kategori']] ?? 0; ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search Card -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <form class="row g-2">
                <div class="col-md-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama aplikasi, kategori, atau keterangan..." value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori_filter); ?>">
                        <button class="btn btn-primary px-4" type="submit">Cari</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table View -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light text-uppercase small">
                        <tr>
                            <th class="py-3 px-3 text-center" width="5%">BIL</th>
                            <th class="py-3">APLIKASI <?php echo sortLink('APLIKASI', $sort, $order, $search, $kategori_filter); ?></th>
                            <th class="py-3">KATEGORI <?php echo sortLink('KATEGORI', $sort, $order, $search, $kategori_filter); ?></th>
                            <th class="py-3">KETERANGAN</th>
                            <th class="py-3 text-center">SSO</th>
                            <th class="py-3 text-end px-3">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($data) > 0): ?>
                            <?php $bil = ($page - 1) * $items_per_page + 1; foreach($data as $row): ?>
                            <tr <?php echo ($row['status'] == 0) ? 'style="background-color: #f9f9f9;"' : ''; ?>>
                                <td class="text-center fw-bold text-muted" <?php echo ($row['status'] == 0) ? 'style="color: #bbb;"' : ''; ?>>
                                    <?php echo $bil++; ?><?php echo ($row['status'] == 0) ? ' <i class="fas fa-ban text-danger ms-2" title="Tidak Aktif"></i>' : ''; ?>
                                </td>
                                <td <?php echo ($row['status'] == 0) ? 'style="color: #bbb;"' : ''; ?>>
                                    <a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank" class="nama-link" <?php echo ($row['status'] == 0) ? 'style="pointer-events: none; color: #999;"' : ''; ?>>
                                        <?php echo htmlspecialchars($row['nama_aplikasi']); ?>
                                        <?php echo ($row['status'] == 0) ? ' <span class="badge bg-danger ms-2">Tidak Aktif</span>' : ''; ?>
                                    </a>
                                </td>
                                <td <?php echo ($row['status'] == 0) ? 'style="filter: grayscale(80%) opacity(0.7);"' : ''; ?>>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($row['warna_bg'] ?? '#007bff'); ?>; color: white;">
                                        <?php echo htmlspecialchars($row['nama_kategori'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="small text-muted" <?php echo ($row['status'] == 0) ? 'style="color: #bbb;"' : ''; ?>>
                                    <?php echo htmlspecialchars(substr($row['keterangan'] ?? '', 0, 60)); ?>
                                    <?php echo strlen($row['keterangan'] ?? '') > 60 ? '...' : ''; ?>
                                </td>
                                <td class="text-center" <?php echo ($row['status'] == 0) ? 'style="color: #bbb;"' : ''; ?>>
                                    <?php if ($row['sso_comply'] == 1): ?>
                                        <span class="badge bg-success">✓ SSO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end px-3" style="opacity: 1 !important; pointer-events: auto; position: relative; z-index: 10;">
                                    <?php if($_SESSION['role'] === 'admin'): ?>
                                        <a href="proses_aplikasi.php?id=<?php echo $row['id_aplikasi']; ?>" class="btn btn-sm btn-warning" title="Edit" style="pointer-events: auto;"><i class="fas fa-edit"></i></a>
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
            <?php if($total_pages > 1): ?>
            <nav aria-label="Navigasi Halaman" class="p-3 border-top">
                <ul class="pagination mb-0 justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
