<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Build query
$sql = "SELECT b.*, s.nama, bi.nama_bilik 
        FROM booking b
        JOIN staf s ON b.id_staf = s.id_staf
        JOIN bilik bi ON b.id_bilik = bi.id_bilik
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (b.tujuan LIKE ? OR bi.nama_bilik LIKE ? OR s.nama LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($filter_status)) {
    $sql .= " AND b.status = ?";
    $params[] = intval($filter_status);
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM (" . $sql . ") as cnt");
$countStmt->execute($params);
$total_records = $countStmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get data
$sql .= " ORDER BY b.tarikh_mula DESC, b.masa_mula DESC LIMIT $items_per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-list me-3 text-primary"></i> Senarai Tempahan
</h3>

<!-- SEARCH & FILTER -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Cari tujuan, bilik, atau pengurus..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="0" <?php echo $filter_status == '0' ? 'selected' : ''; ?>>Pending</option>
                    <option value="1" <?php echo $filter_status == '1' ? 'selected' : ''; ?>>Approved</option>
                    <option value="2" <?php echo $filter_status == '2' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="3" <?php echo $filter_status == '3' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" type="submit">Cari</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD BUTTON -->
<div class="mb-3">
    <a href="booking_add.php" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i> Tempahan Baru</a>
</div>

<!-- BOOKINGS TABLE -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (count($bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3">Tarikh & Masa</th>
                            <th>Bilik</th>
                            <th>Tujuan</th>
                            <th>Pengurus</th>
                            <th>Peserta</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="fw-bold">
                                <?php echo formatTarikh($booking['tarikh_mula']); ?><br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($booking['masa_mula'])); ?> - <?php echo date('H:i', strtotime($booking['masa_tamat'])); ?></small>
                            </td>
                            <td><strong><?php echo $booking['nama_bilik']; ?></strong></td>
                            <td><?php echo substr($booking['tujuan'], 0, 40); ?></td>
                            <td><?php echo $booking['nama']; ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?php echo $booking['bilangan_peserta']; ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo getStatusColor($booking['status']); ?>">
                                    <?php echo getStatusText($booking['status']); ?>
                                </span>
                            </td>
                            <td class="text-end px-3">
                                <a href="booking_edit.php?id=<?php echo $booking['id_booking']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0);" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                   data-bs-target="#detailModal" onclick="viewDetail(<?php echo htmlspecialchars(json_encode($booking)); ?>)" 
                                   title="Lihat">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-inbox fa-3x opacity-25 mb-3 d-block"></i>
                Tiada tempahan dijumpai
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <nav class="p-3 border-top" aria-label="Navigasi">
        <ul class="pagination mb-0 justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo $filter_status; ?>&page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo $filter_status; ?>&page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo $filter_status; ?>&page=<?php echo $page + 1; ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- DETAIL MODAL -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Tempahan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(booking) {
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Bilik:</strong> ${booking.nama_bilik}
            </div>
            <div class="col-md-6">
                <strong>Pengurus:</strong> ${booking.nama}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Tarikh:</strong> ${new Date(booking.tarikh_mula).toLocaleDateString('ms-MY')}
            </div>
            <div class="col-md-6">
                <strong>Masa:</strong> ${booking.masa_mula} - ${booking.masa_tamat}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Bilangan Peserta:</strong> ${booking.bilangan_peserta}
            </div>
            <div class="col-md-6">
                <strong>Status:</strong> <span class="badge bg-${booking.status == 0 ? 'warning' : booking.status == 1 ? 'success' : 'danger'}">${['Pending', 'Approved', 'Rejected', 'Cancelled'][booking.status]}</span>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <strong>Tujuan:</strong><br>
                ${booking.tujuan}
            </div>
        </div>
        ${booking.nota ? `<div class="row"><div class="col-12"><strong>Nota:</strong><br>${booking.nota}</div></div>` : ''}
    `;
    document.getElementById('detailContent').innerHTML = html;
}
</script>

</div><!-- END MAIN CONTENT -->
</body>
</html>
