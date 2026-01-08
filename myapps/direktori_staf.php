<?php
require 'db.php';

// Dapatkan Role & ID Pengguna Semasa
$role   = $_SESSION['role']; 
$my_id  = $_SESSION['user_id'];

// ============================================================
// 1. LOGIC EXPORT EXCEL (.XLS)
// ============================================================
if (isset($_GET['export'])) {
    if (ob_get_length()) ob_end_clean();

    $filename = "Direktori_Users_MyApps_" . date('Ymd') . ".xls";

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
    
    // QUERY EXPORT
    // Nota: Export dengan kolum: NAMA, JAWATAN, SKIM, GRED, BAHAGIAN, EMEL
        $sqlExport = "SELECT u.nama, u.emel, 
                    j.jawatan, j.skim, g.gred, b.bahagian 
                FROM users u 
                LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
                LEFT JOIN gred g ON u.id_gred = g.id_gred
                LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
                WHERE u.id_status_staf = 1
                ORDER BY u.nama ASC";
        $stmt = $db->query($sqlExport);
    
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

// ============================================================
// 2. PAPARAN HALAMAN WEB
// ============================================================
include 'header.php';

$search = $_GET['search'] ?? '';
$sort   = $_GET['sort'] ?? 'nama';
$order  = $_GET['order'] ?? 'ASC';
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$status = isset($_GET['status']) ? intval($_GET['status']) : 1; // Default: Masih Bekerja (1)

$allowed_sort = ['no_kp', 'nama', 'jawatan', 'skim', 'gred', 'bahagian', 'emel', 'telefon'];
if (!in_array($sort, $allowed_sort)) { $sort = 'nama'; }

$allowed_status = [1, 2, 3]; // 1=Masih Bekerja, 2=Berhenti, 3=Bersara
if (!in_array($status, $allowed_status)) { $status = 1; }

$items_per_page = 20; // Items per halaman
$offset = ($page - 1) * $items_per_page;

// QUERY UNTUK HITUNG JUMLAH REKOD

$sqlCount = "SELECT COUNT(*) as total FROM users u 
             LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
             LEFT JOIN gred g ON u.id_gred = g.id_gred
             LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
             WHERE u.id_status_staf = ? 
             AND (
                u.nama LIKE ? OR 
                u.no_kp LIKE ? OR 
                j.jawatan LIKE ? OR 
                j.skim LIKE ? OR 
                b.bahagian LIKE ? OR 
                g.gred LIKE ?
            )";

$stmt = $db->prepare($sqlCount);
$searchParam = "%$search%";
$stmt->execute([$status, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
$countResult = $stmt->fetch(PDO::FETCH_ASSOC);
$total_records = $countResult['total'];
$total_pages = ceil($total_records / $items_per_page);

// Pastikan page valid
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $items_per_page;
}

// QUERY UTAMA (View) - DENGAN PAGINATION

$sql = "SELECT u.*, j.jawatan, j.skim, g.gred, b.bahagian 
    FROM users u 
    LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
    LEFT JOIN gred g ON u.id_gred = g.id_gred
    LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
    WHERE u.id_status_staf = ? ";

$sql .= " AND (
            u.nama LIKE ? OR 
            u.no_kp LIKE ? OR 
            j.jawatan LIKE ? OR 
            j.skim LIKE ? OR 
            b.bahagian LIKE ? OR 
            g.gred LIKE ?
        )
        ORDER BY $sort $order
        LIMIT $items_per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute([$status, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
$data = $stmt->fetchAll();

function sortLink($col, $currentSort, $currentOrder, $currentSearch, $currentStatus) {
    $newOrder = ($currentSort == $col && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort == $col) ? (($currentOrder == 'ASC') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted opacity-25"></i>';
    return "<a href='?sort=$col&order=$newOrder&search=$currentSearch&status=$currentStatus' class='text-dark text-decoration-none fw-bold'>$icon</a>";
}
?>

<style>
    .cursor-pointer { cursor: pointer; }
    .nama-link { color: #0d6efd; font-weight: 600; text-decoration: none; }
    .nama-link:hover { text-decoration: underline; color: #0a58ca; }
</style>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-users me-3 text-primary"></i>Direktori Staf</h3>

    <!-- Status Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $status == 1 ? 'active' : ''; ?>" href="?status=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                <i class="fas fa-briefcase fa-lg text-success me-2"></i>Masih Bekerja
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $status == 2 ? 'active' : ''; ?>" href="?status=2<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                <i class="fas fa-star fa-lg text-warning me-2"></i>Bersara
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $status == 3 ? 'active' : ''; ?>" href="?status=3<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                <i class="fas fa-door-open fa-lg text-danger me-2"></i>Berhenti
            </a>
        </li>
    </ul>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <form method="get" class="mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-grow-1 gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, jawatan, atau bahagian..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary d-flex align-items-center justify-content-center" type="submit">
                            <span class="me-2"><i class="fas fa-search"></i></span>
                            <span>Cari</span>
                        </button>
                    </div>
                    <div class="d-flex gap-2 ms-2">
                        <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'create_user')): ?>
                            <a href="proses_staf.php?mode=add" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Staf</a>
                        <?php endif; ?>
                        <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'export_data')): ?>
                            <a href="direktori_staf.php?export=1" class="btn btn-success" target="_blank"><i class="fas fa-file-excel"></i> Export Excel</a>
                        <?php endif; ?>
                        <!-- Butang tambah staf dibuang untuk elak warning dan duplikasi -->
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light text-uppercase small">
                        <tr>
                            <th class="py-3 px-3 text-center" width="5%">BIL</th>
                            <th class="py-3">NAMA <?php echo sortLink('nama', $sort, $order, $search, $status); ?></th>
                            <th class="py-3">JAWATAN <?php echo sortLink('jawatan', $sort, $order, $search, $status); ?></th>
                            <th class="py-3 text-center">SKIM <?php echo sortLink('skim', $sort, $order, $search, $status); ?></th>
                            <th class="py-3 text-center">GRED <?php echo sortLink('gred', $sort, $order, $search, $status); ?></th>
                            <th class="py-3">BAHAGIAN <?php echo sortLink('bahagian', $sort, $order, $search, $status); ?></th>
                            <th class="py-3 text-center px-3">TINDAKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($data) > 0): ?>
                            <?php $bil = ($page - 1) * $items_per_page + 1; foreach($data as $row): ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?php echo $bil++; ?></td>
                                <td>
                                    <a href="javascript:void(0);" class="nama-link view-details" 
                                       data-nama="<?php echo $row['nama']; ?>"
                                       data-jawatan="<?php echo $row['jawatan']; ?>"
                                       data-skim="<?php echo $row['skim']; ?>" 
                                       data-gred="<?php echo $row['gred']; ?>"
                                       data-bahagian="<?php echo $row['bahagian']; ?>"
                                       data-emel="<?php echo $row['emel']; ?>"
                                       data-telefon="<?php echo $row['telefon']; ?>"
                                       data-gambar="<?php echo !empty($row['gambar']) ? 'uploads/'.$row['gambar'] : ''; ?>"
                                    >
                                        <?php echo $row['nama']; ?>
                                    </a>
                                </td>
                                <td class="small text-muted"><?php echo $row['jawatan']; ?></td>
                                <td class="text-center fw-bold text-secondary"><?php echo $row['skim']; ?></td>
                                <td class="text-center"><?php echo $row['gred']; ?></td>
                                <td class="small text-muted"><?php echo $row['bahagian']; ?></td>
                                
                                <td class="text-center px-3">
                                    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'edit_user')): ?>
                                        <a href="proses_staf.php?id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-warning" title="Edit / Tukar Status"><i class="fas fa-edit"></i></a>
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

            <!-- PAGINATION -->
            <?php if($total_pages > 1): ?>
            <nav aria-label="Navigasi Halaman" class="p-3 border-top">
                <ul class="pagination mb-0 justify-content-center">
                    <!-- Butang Previous -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&status=<?php echo $status; ?>&page=<?php echo $page - 1; ?>" 
                           <?php echo $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                    </li>

                    <!-- Butang Halaman -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&status=<?php echo $status; ?>&page=1">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&status=<?php echo $status; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&status=<?php echo $status; ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Butang Next -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&status=<?php echo $status; ?>&page=<?php echo $page + 1; ?>" 
                           <?php echo $page >= $total_pages ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                            Seterusnya <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
                <div class="text-center text-muted small mt-2">
                    Halaman <strong><?php echo $page; ?></strong> dari <strong><?php echo $total_pages; ?></strong> 
                    (Jumlah: <strong><?php echo $total_records; ?></strong> rekod)
                </div>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Profil Staf</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div style="width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 2px solid #ddd; overflow: hidden; background-color: #f8f9fa;">
                        <img id="mGambar" 
                             src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" 
                             alt="Profil" 
                             style="width: 100%; 
                                     height: 100%; 
                                     object-fit: cover;
                                     object-position: center;
                                     image-rendering: crisp-edges;
                                     image-rendering: -webkit-optimize-contrast;
                                     -ms-interpolation-mode: nearest-neighbor;">
                    </div>
                    <h5 id="mNama" class="fw-bold mb-1"></h5>
                </div>
                
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <div class="row mb-2"><div class="col-4 text-muted small fw-bold">Jawatan</div><div class="col-8 fw-bold" id="mJawatan"></div></div>
                        <div class="row mb-2"><div class="col-4 text-muted small fw-bold">Skim</div><div class="col-8" id="mSkim"></div></div>
                        <div class="row mb-2"><div class="col-4 text-muted small fw-bold">Gred</div><div class="col-8" id="mGred"></div></div>
                        <div class="row mb-2"><div class="col-4 text-muted small fw-bold">Bahagian</div><div class="col-8" id="mBahagian"></div></div>
                        <hr>
                        <div class="row mb-2"><div class="col-4 text-muted small fw-bold">Emel</div><div class="col-8 text-primary" id="mEmel"></div></div>
                        <div class="row"><div class="col-4 text-muted small fw-bold">Telefon</div><div class="col-8" id="mTelefon"></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailLinks = document.querySelectorAll('.view-details');
    detailLinks.forEach(link => {
        link.addEventListener('click', function() {
            document.getElementById('mNama').textContent = this.getAttribute('data-nama');
            document.getElementById('mJawatan').textContent = this.getAttribute('data-jawatan') || '-';
            document.getElementById('mSkim').textContent = this.getAttribute('data-skim') || '-';
            document.getElementById('mGred').textContent = this.getAttribute('data-gred') || '-';
            document.getElementById('mBahagian').textContent = this.getAttribute('data-bahagian') || '-';
            document.getElementById('mEmel').textContent = this.getAttribute('data-emel') || '-';
            document.getElementById('mTelefon').textContent = this.getAttribute('data-telefon') || '-';
            
            // Set profile image
            const gambar = this.getAttribute('data-gambar');
            const imgEl = document.getElementById('mGambar');
            if (gambar && gambar.trim() !== '') {
                imgEl.src = gambar;
                imgEl.onerror = function() { this.src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'; };
            } else {
                imgEl.src = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
            }
            
            var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
            myModal.show();
        });
    });
});
</script>
</body>
</html>
