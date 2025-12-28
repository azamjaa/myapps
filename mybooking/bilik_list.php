<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];

// Check if user is admin
if (!isAdmin($my_id)) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Anda tidak mempunyai kebenaran untuk mengakses halaman ini</div>';
    echo '</div></body></html>';
    exit;
}

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Build query
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM booking WHERE id_bilik = b.id_bilik AND status IN (0, 1)) as booking_count
        FROM bilik b
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (b.nama_bilik LIKE ? OR b.lokasi LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM (" . $sql . ") as cnt");
$countStmt->execute($params);
$total_records = $countStmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get data
$sql .= " ORDER BY b.nama_bilik ASC LIMIT $items_per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$error = '';
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id_bilik = intval($_POST['id_bilik'] ?? 0);
    
    if ($id_bilik <= 0) {
        $error = 'ID bilik tidak sah';
    } else {
        $room = $db->prepare("SELECT * FROM bilik WHERE id_bilik = ?")->execute([$id_bilik])->fetch();
        
        if (!$room) {
            $error = 'Bilik tidak dijumpai';
        } else if ($room['booking_count'] > 0) {
            $error = 'Tidak boleh memadam bilik yang mempunyai tempahan aktif';
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM bilik WHERE id_bilik = ?");
                $stmt->execute([$id_bilik]);
                $success = 'Bilik telah dipadamkan';
            } catch (Exception $e) {
                $error = 'Ralat: ' . $e->getMessage();
            }
        }
    }
}
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-door-open me-3 text-primary"></i> Pengurusan Bilik Mesyuarat
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

<!-- SEARCH & ADD BUTTON -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-9">
                <form class="input-group" method="GET">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Cari nama bilik atau lokasi..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">Cari</button>
                </form>
            </div>
            <div class="col-md-3 text-end">
                <a href="bilik_add.php" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i> Bilik Baru</a>
            </div>
        </div>
    </div>
</div>

<!-- ROOMS TABLE -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (count($rooms) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3">Nama Bilik</th>
                            <th>Lokasi</th>
                            <th class="text-center">Kapasiti</th>
                            <th>Kemudahan</th>
                            <th class="text-center">Tempahan</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $room['nama_bilik']; ?></td>
                            <td><?php echo $room['lokasi']; ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?php echo $room['kapasiti']; ?></span>
                            </td>
                            <td>
                                <small><?php echo substr($room['kemudahan'], 0, 50); ?></small>
                            </td>
                            <td class="text-center">
                                <a href="#" class="text-primary fw-bold" title="Lihat tempahan">
                                    <?php echo $room['booking_count']; ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo $room['status'] == 1 ? 'success' : 'secondary'; ?>">
                                    <?php echo $room['status'] == 1 ? 'Aktif' : 'Tidak Aktif'; ?>
                                </span>
                            </td>
                            <td class="text-end px-3">
                                <a href="bilik_edit.php?id=<?php echo $room['id_bilik']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal" 
                                        onclick="setDeleteData(<?php echo $room['id_bilik']; ?>, '<?php echo htmlspecialchars($room['nama_bilik']); ?>', <?php echo $room['booking_count']; ?>)"
                                        title="Padam" <?php echo $room['booking_count'] > 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-inbox fa-3x opacity-25 mb-3 d-block"></i>
                Tiada bilik dijumpai
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <nav class="p-3 border-top" aria-label="Navigasi">
        <ul class="pagination mb-0 justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Sahkan Pemadaman</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Adakah anda pasti ingin memadamkan bilik berikut?</p>
                    <p class="fs-5 fw-bold text-danger" id="roomName"></p>
                    <p class="text-muted" id="warningMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Padam</button>
                </div>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_bilik" id="delete_id_bilik">
            </form>
        </div>
    </div>
</div>

<script>
function setDeleteData(id, nama, bookingCount) {
    document.getElementById('delete_id_bilik').value = id;
    document.getElementById('roomName').textContent = nama;
    
    if (bookingCount > 0) {
        document.getElementById('warningMessage').innerHTML = 
            '<i class="fas fa-exclamation-triangle me-2 text-warning"></i>Bilik ini mempunyai ' + bookingCount + ' tempahan aktif';
    } else {
        document.getElementById('warningMessage').textContent = 'Tindakan ini tidak boleh dibatalkan';
    }
}
</script>

</div><!-- END MAIN CONTENT -->
</body>
</html>
