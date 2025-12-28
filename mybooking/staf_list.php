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
$filter_role = $_GET['role'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Get roles
$roles = $db->query("SELECT * FROM role ORDER BY id_role")->fetchAll();

// Build query
$sql = "SELECT s.*, 
        GROUP_CONCAT(r.nama_role SEPARATOR ', ') as roles,
        COUNT(DISTINCT b.id_booking) as booking_count
        FROM staf s
        LEFT JOIN akses a ON s.id_staf = a.id_staf
        LEFT JOIN role r ON a.id_role = r.id_role
        LEFT JOIN booking b ON s.id_staf = b.id_staf AND b.status IN (0, 1)
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (s.nama LIKE ? OR s.emel LIKE ? OR s.no_kp LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($filter_role)) {
    $sql .= " AND a.id_role = ?";
    $params[] = intval($filter_role);
}

$sql .= " GROUP BY s.id_staf";

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM (" . $sql . ") as cnt");
$countStmt->execute($params);
$total_records = $countStmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get data
$sql .= " ORDER BY s.nama ASC LIMIT $items_per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$staffs = $stmt->fetchAll();

$error = '';
$success = '';

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_staf = intval($_POST['id_staf'] ?? 0);
    $id_role = intval($_POST['id_role'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$id_staf || !$id_role) {
        $error = 'Parameter tidak sah';
    } else {
        try {
            if ($action == 'add') {
                // Check if already assigned
                $existing = $db->prepare("SELECT * FROM akses WHERE id_staf = ? AND id_role = ?")->execute([$id_staf, $id_role])->fetch();
                if ($existing) {
                    $error = 'Staf sudah ditugaskan dengan peranan ini';
                } else {
                    $stmt = $db->prepare("INSERT INTO akses (id_staf, id_role) VALUES (?, ?)");
                    $stmt->execute([$id_staf, $id_role]);
                    $success = 'Peranan berjaya ditugaskan kepada staf';
                }
            } else if ($action == 'remove') {
                $stmt = $db->prepare("DELETE FROM akses WHERE id_staf = ? AND id_role = ?");
                $stmt->execute([$id_staf, $id_role]);
                $success = 'Peranan berjaya dihapuskan daripada staf';
            }
        } catch (Exception $e) {
            $error = 'Ralat: ' . $e->getMessage();
        }
    }
}
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-users me-3 text-primary"></i> Pengurusan Staf & Peranan
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
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="Cari nama, email, atau No. KP..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">-- Semua Peranan --</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id_role']; ?>" <?php echo $filter_role == $role['id_role'] ? 'selected' : ''; ?>>
                        <?php echo $role['nama_role']; ?>
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
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Jumlah Staf</h6>
                <h3 class="mb-0"><?php echo $total_records; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- STAFF TABLE -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (count($staffs) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3">Nama Staf</th>
                            <th>Email</th>
                            <th>No. KP</th>
                            <th>Peranan</th>
                            <th class="text-center">Tempahan</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffs as $staff): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $staff['nama']; ?></td>
                            <td><small><?php echo $staff['emel']; ?></small></td>
                            <td><small><?php echo $staff['no_kp']; ?></small></td>
                            <td>
                                <?php if (!empty($staff['roles'])): ?>
                                    <?php foreach (explode(', ', $staff['roles']) as $role): ?>
                                    <span class="badge bg-primary"><?php echo $role; ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Tiada Peranan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info"><?php echo $staff['booking_count']; ?></span>
                            </td>
                            <td class="text-end px-3">
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                        data-bs-target="#roleModal" 
                                        onclick="setRoleData(<?php echo htmlspecialchars(json_encode($staff)); ?>, 'add')"
                                        title="Tambah Peranan">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                                <?php if (!empty($staff['roles'])): ?>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                        data-bs-target="#removeRoleModal" 
                                        onclick="setRoleData(<?php echo htmlspecialchars(json_encode($staff)); ?>, 'remove')"
                                        title="Buang Peranan">
                                    <i class="fas fa-minus-circle"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-inbox fa-3x opacity-25 mb-3 d-block"></i>
                Tiada staf dijumpai
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <nav class="p-3 border-top" aria-label="Navigasi">
        <ul class="pagination mb-0 justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&role=<?php echo $filter_role; ?>&page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&role=<?php echo $filter_role; ?>&page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?search=<?php echo urlencode($search); ?>&role=<?php echo $filter_role; ?>&page=<?php echo $page + 1; ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- ADD ROLE MODAL -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Tambah Peranan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p><strong id="staffNameAdd"></strong></p>
                    <label class="form-label fw-bold">Pilih Peranan <span class="text-danger">*</span></label>
                    <select name="id_role" class="form-select" required>
                        <option value="">-- Pilih Peranan --</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id_role']; ?>"><?php echo $role['nama_role']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah</button>
                </div>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id_staf" id="role_id_staf">
            </form>
        </div>
    </div>
</div>

<!-- REMOVE ROLE MODAL -->
<div class="modal fade" id="removeRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Buang Peranan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p><strong id="staffNameRemove"></strong></p>
                    <label class="form-label fw-bold">Pilih Peranan untuk Dibuang <span class="text-danger">*</span></label>
                    <select name="id_role" class="form-select" id="roleSelect" required>
                        <option value="">-- Pilih Peranan --</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Buang</button>
                </div>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="id_staf" id="remove_role_id_staf">
            </form>
        </div>
    </div>
</div>

<script>
function setRoleData(staff, action) {
    if (action == 'add') {
        document.getElementById('staffNameAdd').textContent = staff.nama;
        document.getElementById('role_id_staf').value = staff.id_staf;
    } else if (action == 'remove') {
        document.getElementById('staffNameRemove').textContent = staff.nama;
        document.getElementById('remove_role_id_staf').value = staff.id_staf;
        
        // Populate role select with current roles
        const roleSelect = document.getElementById('roleSelect');
        roleSelect.innerHTML = '<option value="">-- Pilih Peranan --</option>';
        
        const roles = staff.roles ? staff.roles.split(', ') : [];
        <?php foreach ($roles as $role): ?>
            const roleOption = document.createElement('option');
            roleOption.value = '<?php echo $role['id_role']; ?>';
            const roleName = '<?php echo $role['nama_role']; ?>';
            if (roles.includes(roleName)) {
                roleOption.textContent = roleName;
                roleSelect.appendChild(roleOption);
            }
        <?php endforeach; ?>
    }
}
</script>

</div><!-- END MAIN CONTENT -->
</body>
</html>
