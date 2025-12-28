<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];

// Check if user is manager or admin
if (!canApprove($my_id)) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Anda tidak mempunyai kebenaran untuk mengakses halaman ini</div>';
    echo '</div></body></html>';
    exit;
}

$search = $_GET['search'] ?? '';
$filter_room = $_GET['room'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Build query
$sql = "SELECT b.*, s.nama, bi.nama_bilik, 
        (SELECT COUNT(*) FROM approval WHERE id_booking = b.id_booking AND status IN (1, 2)) as approval_count
        FROM booking b
        JOIN staf s ON b.id_staf = s.id_staf
        JOIN bilik bi ON b.id_bilik = bi.id_bilik
        WHERE b.status = 0"; // 0 = Pending

$params = [];

if (!empty($search)) {
    $sql .= " AND (s.nama LIKE ? OR b.tujuan LIKE ? OR bi.nama_bilik LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($filter_room)) {
    $sql .= " AND b.id_bilik = ?";
    $params[] = intval($filter_room);
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM (" . $sql . ") as cnt");
$countStmt->execute($params);
$total_records = $countStmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get data
$sql .= " ORDER BY b.tarikh_mula ASC LIMIT $items_per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get rooms for filter
$rooms = $db->query("SELECT * FROM bilik WHERE status = 1 ORDER BY nama_bilik")->fetchAll();

$error = '';
$success = '';

// Handle approval
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id_booking = intval($_POST['id_booking'] ?? 0);
    $status = intval($_POST['status'] ?? 0); // 1 = Approved, 2 = Rejected

    if (!$id_booking || !in_array($status, [1, 2])) {
        $error = 'Parameter tidak sah';
    } else {
        $booking = $db->prepare("SELECT * FROM booking WHERE id_booking = ?")->execute([$id_booking])->fetch();
        
        if (!$booking || $booking['status'] != 0) {
            $error = 'Tempahan tidak sah atau sudah diproses';
        } else {
            try {
                // Update booking status
                $stmt = $db->prepare("UPDATE booking SET status = ? WHERE id_booking = ?");
                $stmt->execute([$status, $id_booking]);

                // Record approval
                $stmt = $db->prepare("INSERT INTO approval (id_booking, id_approver, status, tarikh_approval) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$id_booking, $my_id, $status]);

                $success = $status == 1 ? 'Tempahan telah diluluskan' : 'Tempahan telah ditolak';
            } catch (Exception $e) {
                $error = 'Ralat: ' . $e->getMessage();
            }
        }
    }
}
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-check-square me-3 text-success"></i> Senarai Persetujuan Tempahan
</h3>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- SEARCH & FILTER -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Cari pengurus, tujuan, atau bilik..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="room" class="form-select">
                    <option value="">-- Semua Bilik --</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo $room['id_bilik']; ?>" <?php echo $filter_room == $room['id_bilik'] ? 'selected' : ''; ?>>
                        <?php echo $room['nama_bilik']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Cari</button>
            </div>
        </form>
    </div>
</div>

<!-- STATS -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Tempahan Pending</h6>
                <h3 class="mb-0"><?php echo $total_records; ?></h3>
            </div>
        </div>
    </div>
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
                            <th>Pengurus</th>
                            <th>Tujuan</th>
                            <th class="text-center">Peserta</th>
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
                            <td><?php echo $booking['nama']; ?></td>
                            <td><?php echo substr($booking['tujuan'], 0, 40); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?php echo $booking['bilangan_peserta']; ?></span>
                            </td>
                            <td class="text-end px-3">
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                        data-bs-target="#approvalModal" 
                                        onclick="setApprovalData(<?php echo htmlspecialchars(json_encode($booking)); ?>, 1)"
                                        title="Luluskan">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                        data-bs-target="#approvalModal" 
                                        onclick="setApprovalData(<?php echo htmlspecialchars(json_encode($booking)); ?>, 2)"
                                        title="Tolak">
                                    <i class="fas fa-times-circle"></i>
                                </button>
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
                Tiada tempahan pending
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <nav class="p-3 border-top" aria-label="Navigasi">
        <ul class="pagination mb-0 justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&room=<?php echo $filter_room; ?>&page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&room=<?php echo $filter_room; ?>&page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&room=<?php echo $filter_room; ?>&page=<?php echo $page + 1; ?>">Next</a>
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

<!-- APPROVAL MODAL -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Persetujuan Tempahan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p><strong id="bookingInfo"></strong></p>
                    <p>Adakah anda pasti untuk <span id="actionText"></span> tempahan ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Sahkan</button>
                </div>
                <input type="hidden" name="id_booking" id="approval_id_booking">
                <input type="hidden" name="status" id="approval_status">
            </form>
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
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <strong>Tujuan:</strong><br>
                ${booking.tujuan}
            </div>
        </div>
    `;
    document.getElementById('detailContent').innerHTML = html;
}

function setApprovalData(booking, status) {
    document.getElementById('approval_id_booking').value = booking.id_booking;
    document.getElementById('approval_status').value = status;
    document.getElementById('bookingInfo').textContent = booking.nama + ' - ' + booking.nama_bilik + ' (' + new Date(booking.tarikh_mula).toLocaleDateString('ms-MY') + ')';
    document.getElementById('actionText').textContent = status == 1 ? 'LULUSKAN' : 'TOLAK';
}
</script>

</div><!-- END MAIN CONTENT -->
</body>
</html>
