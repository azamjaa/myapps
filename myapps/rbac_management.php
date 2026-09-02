<?php
require 'header.php';
require 'security_helper.php';
require_once 'src/rbac_helper.php';

// Gunakan $_SESSION['user_id'] seperti di header.php (line 7)
// Atau gunakan $_SESSION['id_user'] jika sudah diset
$current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);

// Semak isSuperAdmin
try {
    $isAdmin = isSuperAdmin($pdo, $current_user);
    if (!$isAdmin) {
        echo '<div class="alert alert-danger mt-4 ml-4">⛔ <strong>Anda Tidak Dibenarkan Akses Halaman Ini.</strong><br>Hanya Super Admin yang boleh mengurus RBAC.</div>';
        exit;
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger mt-4 ml-4">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// GET SORT PARAMETERS
$sort_users = $_GET['sort_users'] ?? 'nama';
$order_users = $_GET['order_users'] ?? 'ASC';
$sort_roles = $_GET['sort_roles'] ?? 'name';
$order_roles = $_GET['order_roles'] ?? 'ASC';
$sort_permissions = $_GET['sort_permissions'] ?? 'name';
$order_permissions = $_GET['order_permissions'] ?? 'ASC';

// VALIDATE SORT
$allowed_sorts_users = ['id_user', 'nama', 'jawatan', 'skim', 'gred', 'bahagian', 'role'];
$allowed_sorts_roles = ['id_role', 'name', 'description'];
$allowed_sorts_permissions = ['id_permission', 'name', 'description'];
$allowed_order = ['ASC', 'DESC'];

if (!in_array($sort_users, $allowed_sorts_users)) $sort_users = 'nama';
if (!in_array($order_users, $allowed_order)) $order_users = 'ASC';
if (!in_array($sort_roles, $allowed_sorts_roles)) $sort_roles = 'name';
if (!in_array($order_roles, $allowed_order)) $order_roles = 'ASC';
if (!in_array($sort_permissions, $allowed_sorts_permissions)) $sort_permissions = 'name';
if (!in_array($order_permissions, $allowed_order)) $order_permissions = 'ASC';

// SORT FIELD MAPPING
$sort_users_map = [
    'id_user' => 'u.id_user',
    'nama' => 'u.nama',
    'jawatan' => 'j.jawatan',
    'skim' => 'j.skim',
    'gred' => 'g.gred',
    'bahagian' => 'b.bahagian',
    'role' => 'r.name'
];
$sort_field_users = $sort_users_map[$sort_users] ?? 'u.nama';


// Senarai aplikasi untuk dropdown
$aplikasiList = $pdo->query("SELECT id_aplikasi, nama_aplikasi FROM aplikasi ORDER BY nama_aplikasi")->fetchAll(PDO::FETCH_ASSOC);

$users = $pdo->query("SELECT u.id_user, u.nama, j.jawatan, j.skim, g.gred, b.bahagian, r.name as role, ur.id_aplikasi 
    FROM users u
    LEFT JOIN user_roles ur ON u.id_user = ur.id_user
    LEFT JOIN roles r ON ur.id_role = r.id_role
    LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
    LEFT JOIN gred g ON u.id_gred = g.id_gred
    LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
    WHERE u.id_status_staf = 1
    ORDER BY $sort_field_users $order_users")->fetchAll(PDO::FETCH_ASSOC);

$roles = $pdo->query("SELECT * FROM roles ORDER BY $sort_roles $order_roles")->fetchAll(PDO::FETCH_ASSOC);

$permissions = $pdo->query("SELECT * FROM permissions ORDER BY $sort_permissions $order_permissions")->fetchAll(PDO::FETCH_ASSOC);

// SORT LINK FUNCTION
function sortLinkRBAC($col, $currentSort, $currentOrder, $paramPrefix) {
    $newOrder = ($currentSort == $col && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    $icon = ($currentSort == $col) ? (($currentOrder == 'ASC') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted opacity-25"></i>';
    return "<a href='?sort_{$paramPrefix}=$col&order_{$paramPrefix}=$newOrder' class='text-dark text-decoration-none fw-bold'>$icon</a>";
}
?>

<!-- CONTENT AREA -->
<div class="container-fluid">
    <!-- Header -->
    <h3 class="mb-2 fw-bold text-dark"><i class="fas fa-user-shield fa-lg text-primary me-3"></i>Pengurusan Role Based Access Control (RBAC)</h3>
    
    <div class="alert alert-info mb-4 py-3" role="alert">
        <strong><i class="fas fa-info-circle me-2"></i>RBAC untuk semua aplikasi</strong><br>
        Halaman ini mengurus akses <strong>MyApps</strong> dan <strong>semua aplikasi janaan DIY Aplikasi</strong> (contoh: MyDesa).
        Dalam tab <strong>Users</strong>, apabila anda tambah/ubah role untuk user, pilih <strong>Aplikasi</strong> dalam dropdown – senarai itu termasuk aplikasi DIY Aplikasi yang telah ditambah ke direktori. Pilih aplikasi tersebut (cth. MyDesa) dan assign role; hanya user yang diberi role untuk app itu boleh akses app berkenaan. Matriks di tab "Struktur Pengurusan RBAC" menerangkan permission untuk halaman MyApps; akses ke aplikasi DIY Aplikasi dikawal melalui assign <strong>User → Role → Aplikasi</strong> di tab Users.
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="users-tab" data-bs-toggle="tab" href="#users" role="tab">
                        <i class="fas fa-users fa-lg text-success me-2"></i>Users
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="roles-tab" data-bs-toggle="tab" href="#roles" role="tab">
                        <i class="fas fa-user-shield fa-lg text-warning me-2"></i>Roles
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="permissions-tab" data-bs-toggle="tab" href="#permissions" role="tab">
                        <i class="fas fa-lock fa-lg text-danger me-2"></i>Permissions
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab">
                        <i class="fas fa-diagram-project fa-lg text-primary me-2"></i>Struktur Pengurusan RBAC
                    </a>
                </li>

            </ul>
            
            <style>
                .nav-tabs .nav-link {
                    color: #000000;
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

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Users Tab -->
                <div class="tab-pane fade show active" id="users" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex flex-grow-1 gap-2">
                                                <input type="text" class="form-control" id="searchUsers" placeholder="Cari nama, email, atau role...">
                                                <button class="btn btn-primary" type="button" style="min-width:120px;" onclick="filterTable('usersTable', document.getElementById('searchUsers').value)"><i class="fas fa-search"></i> Cari</button>
                                            </div>
                                            <div class="d-flex gap-2 ms-2">
                                                <?php if(hasAccess($pdo, $current_user, 1, 'export_data')): ?>
                                                <button class="btn btn-success" style="min-width:120px;" onclick="exportExcel('usersTable')"><i class="fas fa-file-excel"></i> Export Excel</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped align-middle mb-0" id="usersTable">
                                                <thead class="bg-light text-uppercase small">
                                                    <tr>
                                                        <th class="py-3 px-3 text-center" width="5%">Bil</th>
                                                        <th class="py-3">Nama <?php echo sortLinkRBAC('nama', $sort_users, $order_users, 'users'); ?></th>
                                                        <th class="py-3">Jawatan <?php echo sortLinkRBAC('jawatan', $sort_users, $order_users, 'users'); ?></th>
                                                        <th class="py-3 text-center">Skim <?php echo sortLinkRBAC('skim', $sort_users, $order_users, 'users'); ?></th>
                                                        <th class="py-3 text-center">Gred <?php echo sortLinkRBAC('gred', $sort_users, $order_users, 'users'); ?></th>
                                                        <th class="py-3">Bahagian <?php echo sortLinkRBAC('bahagian', $sort_users, $order_users, 'users'); ?></th>
                                                        <th class="py-3">Role <?php echo sortLinkRBAC('role', $sort_users, $order_users, 'users'); ?></th>
                                                        <th style="width:80px; text-align:center;">Tindakan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $bil=1; foreach ($users as $u): ?>
                                                    <tr>
                                                        <td><?php echo $bil++; ?></td>
                                                        <td><?php echo htmlspecialchars($u['nama']); ?></td>
                                                        <td><?php echo htmlspecialchars($u['jawatan'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($u['skim'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($u['gred'] ?? '-'); ?></td>
                                                        <td><?php echo htmlspecialchars($u['bahagian'] ?? '-'); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo htmlspecialchars($u['role'] ?? 'No Role'); ?>
                                                            </span>
                                                        </td>
                                                        <td style="text-align:center;">
                                                            <?php if(hasAccess($pdo, $current_user, 1, 'edit_user')): ?>
                                                            <button class="btn btn-warning btn-sm edit-user-btn" title="Edit" 
                                                                data-user-id="<?php echo $u['id_user']; ?>" 
                                                                data-user-name="<?php echo htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                                data-user-jawatan="<?php echo htmlspecialchars($u['jawatan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                                                data-user-bahagian="<?php echo htmlspecialchars($u['bahagian'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                                                data-user-role="<?php echo htmlspecialchars($u['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- Modal Tambah/Ubah User -->
                                        <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="userModalLabel">Tambah/Ubah User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form id="userForm">
                                                            <input type="hidden" id="userId" name="userId">
                                                            <div class="mb-3">
                                                                <label for="userName" class="form-label">Nama</label>
                                                                <input type="text" class="form-control" id="userName" name="userName" required>
                                                                <div class="invalid-feedback" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                                                                    Nama tidak boleh kosong
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="userJawatan" class="form-label">Jawatan</label>
                                                                <input type="text" class="form-control" id="userJawatan" name="userJawatan" required>
                                                                <div class="invalid-feedback" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                                                                    Jawatan tidak boleh kosong
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="userBahagian" class="form-label">Bahagian</label>
                                                                <input type="text" class="form-control" id="userBahagian" name="userBahagian" required>
                                                                <div class="invalid-feedback" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                                                                    Bahagian tidak boleh kosong
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="userApp" class="form-label">Aplikasi</label>
                                                                <select class="form-select" id="userApp" name="userApp" required>
                                                                    <option value="">-- Pilih Aplikasi --</option>
                                                                    <?php foreach ($aplikasiList as $app): ?>
                                                                    <option value="<?php echo $app['id_aplikasi']; ?>">
                                                                        <?php echo htmlspecialchars($app['nama_aplikasi']); ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="invalid-feedback" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                                                                    Sila pilih aplikasi
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="userRole" class="form-label">Role</label>
                                                                <select class="form-select" id="userRole" name="userRole" required>
                                                                    <option value="">-- Pilih Role --</option>
                                                                    <?php foreach ($roles as $role): ?>
                                                                    <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role['name']))); ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="invalid-feedback" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                                                                    Sila pilih role
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="button" class="btn btn-primary" onclick="submitUserForm()">Simpan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                        // Attach event listeners untuk semua edit buttons
                                        document.querySelectorAll('.edit-user-btn').forEach(btn => {
                                            btn.addEventListener('click', function() {
                                                var id = this.getAttribute('data-user-id');
                                                var name = this.getAttribute('data-user-name');
                                                var jawatan = this.getAttribute('data-user-jawatan');
                                                var bahagian = this.getAttribute('data-user-bahagian');
                                                var role = this.getAttribute('data-user-role');
                                                showEditUserModal(id, name, jawatan, bahagian, role);
                                            });
                                        });
                                        
                                        function showAddUserModal() {
                                                document.getElementById('userModalLabel').innerText = 'Tambah User';
                                                document.getElementById('userId').value = '';
                                                document.getElementById('userName').value = '';
                                                document.getElementById('userJawatan').value = '';
                                                document.getElementById('userBahagian').value = '';
                                                document.getElementById('userRole').value = '';
                                                var modal = new bootstrap.Modal(document.getElementById('userModal'));
                                                modal.show();
                                        }
                                        function showEditUserModal(id, name, jawatan, bahagian, role) {
                                                document.getElementById('userModalLabel').innerText = 'Ubah User';
                                                document.getElementById('userId').value = id;
                                                document.getElementById('userName').value = name;
                                                document.getElementById('userJawatan').value = jawatan;
                                                document.getElementById('userBahagian').value = bahagian;
                                                // Set role value properly
                                                var roleSelect = document.getElementById('userRole');
                                                if (role && role !== 'null') {
                                                    roleSelect.value = role;
                                                } else {
                                                    roleSelect.value = '';
                                                }
                                                var modal = new bootstrap.Modal(document.getElementById('userModal'));
                                                modal.show();
                                        }
                                        function submitUserForm() {
                                                var userId = document.getElementById('userId').value;
                                                var userName = document.getElementById('userName').value;
                                                var userJawatan = document.getElementById('userJawatan').value;
                                                var userBahagian = document.getElementById('userBahagian').value;
                                                var userRole = document.getElementById('userRole').value;
                                                var form = document.getElementById('userForm');
                                                var userApp = document.getElementById('userApp').value;
                                                // Semak validation
                                                var isValid = true;
                                                var nameError = form.querySelector('[for="userName"] ~ .invalid-feedback');
                                                var jawatanError = form.querySelector('[for="userJawatan"] ~ .invalid-feedback');
                                                var bahagianError = form.querySelector('[for="userBahagian"] ~ .invalid-feedback');
                                                var appError = form.querySelector('[for="userApp"] ~ .invalid-feedback');
                                                var roleError = form.querySelector('[for="userRole"] ~ .invalid-feedback');
                                                // Reset error display
                                                nameError.style.display = 'none';
                                                jawatanError.style.display = 'none';
                                                bahagianError.style.display = 'none';
                                                appError.style.display = 'none';
                                                roleError.style.display = 'none';
                                                if (!userName) {
                                                    nameError.style.display = 'block';
                                                    isValid = false;
                                                }
                                                if (!userJawatan) {
                                                    jawatanError.style.display = 'block';
                                                    isValid = false;
                                                }
                                                if (!userBahagian) {
                                                    bahagianError.style.display = 'block';
                                                    isValid = false;
                                                }
                                                if (!userApp) {
                                                    appError.style.display = 'block';
                                                    isValid = false;
                                                }
                                                if (!userRole) {
                                                    roleError.style.display = 'block';
                                                    isValid = false;
                                                }
                                                if (!isValid) {
                                                    return;
                                                }
                                                var formData = new FormData();
                                                formData.append('action', 'saveUser');
                                                formData.append('userId', userId);
                                                formData.append('userName', userName);
                                                formData.append('userJawatan', userJawatan);
                                                formData.append('userBahagian', userBahagian);
                                                formData.append('userRole', userRole);
                                                formData.append('appId', userApp);
                                                fetch('api/rbac.php', {
                                                    method: 'POST',
                                                    body: formData
                                                })
                                                .then(response => {
                                                    if (!response.ok) {
                                                        throw new Error('HTTP ' + response.status);
                                                    }
                                                    return response.json();
                                                })
                                                .then(data => {
                                                    if (data.success) {
                                                        alert('User disimpan berjaya');
                                                        location.reload();
                                                    } else {
                                                        alert('Ralat: ' + (data.message || 'Ralat tidak diketahui'));
                                                    }
                                                    var modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                                                    modal.hide();
                                                })
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    alert('Ralat menyimpan user: ' + error.message);
                                                    var modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                                                    modal.hide();
                                                });
                                        }
                                        </script>
                </div>

                <!-- Roles Tab -->
                <div class="tab-pane fade" id="roles" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex flex-grow-1 gap-2">
                                                <input type="text" class="form-control" id="searchRoles" placeholder="Cari nama atau deskripsi role...">
                                                <button class="btn btn-primary" type="button" style="min-width:120px;" onclick="filterTable('rolesTable', document.getElementById('searchRoles').value)"><i class="fas fa-search"></i> Cari</button>
                                            </div>
                                            <div class="d-flex gap-2 ms-2">
                                                <?php if(hasAccess($pdo, $current_user, 1, 'manage_roles')): ?>
                                                <button class="btn btn-primary" style="min-width:150px;" onclick="showAddRoleModal()"><i class="fas fa-plus"></i> Tambah Role</button>
                                                <?php endif; ?>
                                                <button class="btn btn-success" style="min-width:120px;" onclick="exportExcel('rolesTable')"><i class="fas fa-file-excel"></i> Export Excel</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped align-middle sortable-table" id="rolesTable">
                                                <thead class="bg-light text-uppercase small">
                                                    <tr>
                                                        <th class="py-3 px-3 text-center" width="5%">Bil</th>
                                                        <th class="py-3">Nama Role <?php echo sortLinkRBAC('name', $sort_roles, $order_roles, 'roles'); ?></th>
                                                        <th class="py-3">Deskripsi <?php echo sortLinkRBAC('description', $sort_roles, $order_roles, 'roles'); ?></th>
                                                        <th style="width:80px; text-align:center;">Tindakan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $bil=1; foreach ($roles as $r): ?>
                                                    <tr>
                                                        <td><?php echo $bil++; ?></td>
                                                        <td><?php 
                                                            $roleName = str_replace('_', ' ', $r['name']);
                                                            $roleName = ucwords($roleName);
                                                            echo htmlspecialchars($roleName); 
                                                        ?></td>
                                                        <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                                                        <td>
                                                            <?php if(hasAccess($pdo, $current_user, 1, 'manage_roles')): ?>
                                                            <button class="btn btn-warning btn-sm" title="Edit" onclick="showEditRoleModal(<?php echo $r['id_role']; ?>, '<?php echo htmlspecialchars(addslashes($r['name'])); ?>', '<?php echo htmlspecialchars(addslashes($r['description'])); ?>')">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- Modal Tambah/Ubah Role -->
                                        <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="roleModalLabel">Tambah/Ubah Role</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form id="roleForm">
                                                            <input type="hidden" id="roleId" name="roleId">
                                                            <div class="mb-3">
                                                                <label for="roleName" class="form-label">Nama Role</label>
                                                                <input type="text" class="form-control" id="roleName" name="roleName" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="roleDesc" class="form-label">Deskripsi</label>
                                                                <input type="text" class="form-control" id="roleDesc" name="roleDesc">
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="button" class="btn btn-primary" onclick="submitRoleForm()">Simpan</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                        function showAddRoleModal() {
                                                document.getElementById('roleModalLabel').innerText = 'Tambah Role';
                                                document.getElementById('roleId').value = '';
                                                document.getElementById('roleName').value = '';
                                                document.getElementById('roleDesc').value = '';
                                                var modal = new bootstrap.Modal(document.getElementById('roleModal'));
                                                modal.show();
                                        }
                                        function showEditRoleModal(id, name, desc) {
                                                document.getElementById('roleModalLabel').innerText = 'Ubah Role';
                                                document.getElementById('roleId').value = id;
                                                document.getElementById('roleName').value = name;
                                                document.getElementById('roleDesc').value = desc;
                                                var modal = new bootstrap.Modal(document.getElementById('roleModal'));
                                                modal.show();
                                        }
                                        function submitRoleForm() {
                                                var roleId = document.getElementById('roleId').value;
                                                var roleName = document.getElementById('roleName').value;
                                                var roleDesc = document.getElementById('roleDesc').value;
                                                
                                                if (!roleName) {
                                                    alert('Nama role diperlukan');
                                                    return;
                                                }
                                                
                                                var formData = new FormData();
                                                formData.append('action', 'saveRole');
                                                formData.append('roleId', roleId);
                                                formData.append('roleName', roleName);
                                                formData.append('roleDesc', roleDesc);
                                                
                                                fetch('api/rbac.php', {
                                                    method: 'POST',
                                                    body: formData
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        alert('Role disimpan berjaya');
                                                        location.reload();
                                                    } else {
                                                        alert('Ralat: ' + data.message);
                                                    }
                                                    var modal = bootstrap.Modal.getInstance(document.getElementById('roleModal'));
                                                    modal.hide();
                                                })
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    alert('Ralat menyimpan role');
                                                    var modal = bootstrap.Modal.getInstance(document.getElementById('roleModal'));
                                                    modal.hide();
                                                });
                                        }
                                        </script>
                </div>

                <!-- Permissions Tab -->
                <div class="tab-pane fade" id="permissions" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex flex-grow-1 gap-2">
                            <input type="text" class="form-control" id="searchPermissions" placeholder="Cari nama atau deskripsi permission...">
                            <button class="btn btn-primary" type="button" style="min-width:120px;" onclick="filterTable('permissionsTable', document.getElementById('searchPermissions').value)"><i class="fas fa-search"></i> Cari</button>
                        </div>
                        <div class="d-flex gap-2 ms-2">
                            <?php if(hasAccess($pdo, $current_user, 1, 'manage_roles')): ?>
                            <button class="btn btn-primary" style="min-width:180px;" onclick="showAddPermissionModal()"><i class="fas fa-plus"></i> Tambah Permission</button>
                            <?php endif; ?>
                            <button class="btn btn-success" style="min-width:120px;" onclick="exportExcel('permissionsTable')"><i class="fas fa-file-excel"></i> Export Excel</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle sortable-table" id="permissionsTable">
                            <thead class="bg-light text-uppercase small">
                                <tr>
                                    <th class="py-3 px-3 text-center" width="5%">Bil</th>
                                    <th class="py-3">Nama Permission <?php echo sortLinkRBAC('name', $sort_permissions, $order_permissions, 'permissions'); ?></th>
                                    <th class="py-3">Deskripsi <?php echo sortLinkRBAC('description', $sort_permissions, $order_permissions, 'permissions'); ?></th>
                                    <th style="width:80px; text-align:center;">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $bil=1; foreach ($permissions as $p): ?>
                                <tr>
                                    <td><?php echo $bil++; ?></td>
                                    <td><?php 
                                        $permName = str_replace('_', ' ', $p['name']);
                                        $permName = ucwords($permName);
                                        echo htmlspecialchars($permName); 
                                    ?></td>
                                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                    <td>
                                        <?php if(hasAccess($pdo, $current_user, 1, 'manage_roles')): ?>
                                        <button class="btn btn-warning btn-sm" title="Edit" onclick="showEditPermissionModal(<?php echo $p['id_permission']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>', '<?php echo htmlspecialchars(addslashes($p['description'])); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Modal Tambah/Ubah Permission -->
                    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="permissionModalLabel">Tambah/Ubah Permission</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="permissionForm">
                                        <input type="hidden" id="permissionId" name="permissionId">
                                        <div class="mb-3">
                                            <label for="permissionName" class="form-label">Nama Permission</label>
                                            <input type="text" class="form-control" id="permissionName" name="permissionName" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="permissionDesc" class="form-label">Deskripsi</label>
                                            <input type="text" class="form-control" id="permissionDesc" name="permissionDesc">
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    <button type="button" class="btn btn-primary" onclick="submitPermissionForm()">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                    function showAddPermissionModal() {
                            document.getElementById('permissionModalLabel').innerText = 'Tambah Permission';
                            document.getElementById('permissionId').value = '';
                            document.getElementById('permissionName').value = '';
                            document.getElementById('permissionDesc').value = '';
                            var modal = new bootstrap.Modal(document.getElementById('permissionModal'));
                            modal.show();
                    }
                    function showEditPermissionModal(id, name, desc) {
                            document.getElementById('permissionModalLabel').innerText = 'Ubah Permission';
                            document.getElementById('permissionId').value = id;
                            document.getElementById('permissionName').value = name;
                            document.getElementById('permissionDesc').value = desc;
                            var modal = new bootstrap.Modal(document.getElementById('permissionModal'));
                            modal.show();
                    }
                    function submitPermissionForm() {
                            var permissionId = document.getElementById('permissionId').value;
                            var permissionName = document.getElementById('permissionName').value;
                            var permissionDesc = document.getElementById('permissionDesc').value;
                            
                            if (!permissionName) {
                                alert('Nama permission diperlukan');
                                return;
                            }
                            
                            var formData = new FormData();
                            formData.append('action', 'savePermission');
                            formData.append('permissionId', permissionId);
                            formData.append('permissionName', permissionName);
                            formData.append('permissionDesc', permissionDesc);
                            
                            fetch('api/rbac.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Permission disimpan berjaya');
                                    location.reload();
                                } else {
                                    alert('Ralat: ' + data.message);
                                }
                                var modal = bootstrap.Modal.getInstance(document.getElementById('permissionModal'));
                                modal.hide();
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Ralat menyimpan permission');
                                var modal = bootstrap.Modal.getInstance(document.getElementById('permissionModal'));
                                modal.hide();
                            });
                    }
                    </script>
                </div>


                <!-- RBAC Overview Tab -->
                <div class="tab-pane fade" id="overview" role="tabpanel">
                    <div class="container-fluid py-4">
                        


                        <!-- Matriks Roles & Permissions -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <h6 class="mb-3">📊 Matriks Kesemua Roles & Permissions:</h6>
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Klik setiap cell untuk toggle permission (✓/✗)
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" style="font-size: 13px;" id="rbacMatrix">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 40px; text-align: center;"><strong>Bil</strong></th>
                                                <th style="width: 150px;"><strong>Role / Peranan</strong></th>
                                                <?php foreach($permissions as $perm): ?>
                                                <th class="text-center" style="cursor: pointer;"><small><?php echo htmlspecialchars($perm['name']); ?></small></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $roleNum = 1;
                                            foreach($roles as $role): 
                                                // Get permissions untuk role ini
                                                $stmt = $pdo->prepare("SELECT id_permission FROM role_permissions WHERE id_role = ?");
                                                $stmt->execute([$role['id_role']]);
                                                $rolePerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                                $rolePermIds = array_flip($rolePerms);
                                                
                                                // Colors untuk role
                                                $colors = [
                                                    1 => '#fff3cd', // Super Admin
                                                    2 => '#e7d4f5', // Admin
                                                    3 => '#e2f0fe', // Penyedia
                                                    4 => '#fff4e6', // Penyemak
                                                    5 => '#e8f5e9', // Pelulus
                                                    6 => '#f3e5f5'  // User Biasa
                                                ];
                                            ?>
                                            <tr style="background-color: <?php echo $colors[$role['id_role']] ?? '#ffffff'; ?>;">
                                                <td style="text-align: center; font-weight: bold;"><?php echo $roleNum; ?></td>
                                                <td><strong><?php echo htmlspecialchars($role['name']); ?></strong><br>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($role['description'] ?? ''); ?></span>
                                                </td>
                                                <?php foreach($permissions as $perm):
                                                    $hasPermission = isset($rolePermIds[$perm['id_permission']]);
                                                ?>
                                                <td class="text-center rbac-cell" 
                                                    data-role-id="<?php echo $role['id_role']; ?>" 
                                                    data-perm-id="<?php echo $perm['id_permission']; ?>"
                                                    style="cursor: pointer; padding: 8px; transition: all 0.2s; user-select: none;"
                                                    title="Klik untuk toggle permission">
                                                    <span class="perm-status" style="font-size: 16px; font-weight: bold;">
                                                        <?php if($hasPermission): ?>
                                                            <span style="color: green;">✓</span>
                                                        <?php else: ?>
                                                            <span style="color: red;">✗</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php 
                                            $roleNum++;
                                            endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">✓ = Boleh akses | ✗ = Tidak boleh akses</small>
                            </div>
                        </div>

                        <!-- Pages & Permissions Mapping -->
                        <div class="row mt-4 mb-4">
                            <div class="col-12">
                                <h6 class="mb-3">📄 Pages & Permissions Mapping:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" style="font-size: 13px;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 200px;"><strong>Page / Menu</strong></th>
                                                <th><strong>Permissions Required</strong></th>
                                                <th><strong>Description</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Dashboard Aplikasi</strong><br><code>dashboard_aplikasi.php</code></td>
                                                <td>
                                                    <span class="badge bg-primary">view_dashboard</span><br>
                                                    <span class="badge bg-success">create_application</span> (Tambah Aplikasi)<br>
                                                    <span class="badge bg-warning">edit_application</span> (Edit Aplikasi - termasuk tukar status)<br>
                                                    <span class="badge bg-info">export_data</span> (Export Excel)
                                                </td>
                                                <td>Paparan statistik aplikasi, senarai aplikasi dengan filter, carian, sort, dan export. Admin boleh tambah/edit aplikasi. <strong>Nota:</strong> Sistem menggunakan soft delete - aplikasi tidak dipadam, hanya status ditukar kepada "Tidak Aktif" (status = 0).</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dashboard Perjawatan</strong><br><code>dashboard_perjawatan.php</code></td>
                                                <td>
                                                    <span class="badge bg-primary">view_dashboard</span><br>
                                                    <span class="badge bg-success">create_user</span> (Tambah Staf)<br>
                                                    <span class="badge bg-warning">edit_user</span> (Edit Staf)<br>
                                                    <span class="badge bg-info">export_data</span> (Export Excel)
                                                </td>
                                                <td>Paparan statistik staf, direktori staf dengan filter (Masih Bekerja/Bersara/Berhenti), kalendar hari lahir, dan graf kategori. Admin boleh tambah/edit staf.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Dashboard Pencapaian</strong><br><code>dashboard_pencapaian.php</code></td>
                                                <td>
                                                    <span class="badge bg-primary">view_dashboard</span><br>
                                                    <span class="badge bg-info">export_data</span> (Export Excel)
                                                </td>
                                                <td>Visualisasi data geospatial dengan peta interaktif, graf donut (Daerah/Parlimen/DUN), dan senarai rekod detail dengan pagination, sort, dan filter.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Pengurusan Rekod Dashboard</strong><br><code>pengurusan_rekod_dashboard.php</code></td>
                                                <td>
                                                    <span class="badge bg-danger">super_admin</span> (Admin sahaja)
                                                </td>
                                                <td>Upload/download data GeoJSON/Excel untuk Dashboard Pencapaian. Semak & betulkan rekod GPS luar sempadan Kedah. Isi maklumat lokasi (DAERAH/PARLIMEN/DUN) menggunakan reverse geocoding.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Pengurusan RBAC</strong><br><code>rbac_management.php</code></td>
                                                <td>
                                                    <span class="badge bg-danger">super_admin</span> (Super Admin sahaja)
                                                </td>
                                                <td>Menguruskan users, roles, permissions, dan matriks akses. Toggle permissions untuk roles dengan klik pada matriks.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Manual Pengguna</strong><br><code>manual.php</code></td>
                                                <td>
                                                    <span class="badge bg-secondary">Semua pengguna</span>
                                                </td>
                                                <td>Dokumentasi lengkap untuk semua features dan fungsi dalam sistem MyApps KEDA.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Proses Aplikasi</strong><br><code>proses_aplikasi.php</code></td>
                                                <td>
                                                    <span class="badge bg-success">create_application</span> (Tambah)<br>
                                                    <span class="badge bg-warning">edit_application</span> (Edit)
                                                </td>
                                                <td>Form untuk tambah/edit aplikasi. Redirect kembali ke Dashboard Aplikasi selepas simpan.</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Proses Staf</strong><br><code>proses_staf.php</code></td>
                                                <td>
                                                    <span class="badge bg-success">create_user</span> (Tambah)<br>
                                                    <span class="badge bg-warning">edit_user</span> (Edit - Admin full access, User edit sendiri sahaja dengan akses terhad)
                                                </td>
                                                <td>Form untuk tambah/edit staf. User biasa hanya boleh edit profil sendiri (emel, telefon, gred, bahagian). Redirect kembali ke Dashboard Perjawatan selepas simpan.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Menu Structure -->
                        <div class="row mt-4 mb-4">
                            <div class="col-12">
                                <h6 class="mb-3">📋 Struktur Menu Sidebar:</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <ul style="list-style: none; padding-left: 0;">
                                            <li class="mb-2"><i class="fas fa-chart-line text-primary me-2"></i><strong>Dashboard Aplikasi</strong> - Statistik dan senarai aplikasi</li>
                                            <li class="mb-2"><i class="fas fa-tachometer-alt text-success me-2"></i><strong>Dashboard Perjawatan</strong> - Statistik staf, direktori staf, kalendar</li>
                                            <li class="mb-2"><i class="fas fa-map-marked-alt text-danger me-2"></i><strong>Dashboard Pencapaian</strong> - Visualisasi data geospatial</li>
                                            <li class="mb-2" style="padding-left: 20px;"><i class="fas fa-file-alt text-warning me-2"></i><strong>Pengurusan Rekod Dashboard</strong> <span class="badge bg-danger">Admin</span> - Upload/download data</li>
                                            <li class="mb-2"><i class="fas fa-book-open text-info me-2"></i><strong>Manual Pengguna</strong> - Dokumentasi sistem</li>
                                            <li class="mb-2"><i class="fas fa-user-shield text-danger me-2"></i><strong>Pengurusan RBAC</strong> <span class="badge bg-danger">Super Admin</span> - Kawalan akses</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Common Permissions -->
                        <div class="row mt-4 mb-4">
                            <div class="col-12">
                                <h6 class="mb-3">🔐 Senarai Permissions Umum:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <strong>User Management</strong>
                                            </div>
                                            <div class="card-body">
                                                <ul class="mb-0">
                                                    <li><code>create_user</code> - Tambah staf baharu</li>
                                                    <li><code>edit_user</code> - Edit maklumat staf</li>
                                                    <li><code>delete_user</code> - Padam staf (jika ada)</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-success text-white">
                                                <strong>Application Management</strong>
                                            </div>
                                            <div class="card-body">
                                                <ul class="mb-0">
                                                    <li><code>create_application</code> - Tambah aplikasi baharu</li>
                                                    <li><code>edit_application</code> - Edit maklumat aplikasi dan tukar status (Aktif/Tidak Aktif)</li>
                                                    <li class="text-muted"><small><i class="fas fa-info-circle"></i> Sistem menggunakan soft delete - aplikasi tidak dipadam, hanya status ditukar kepada "Tidak Aktif"</small></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-info text-white">
                                                <strong>Data Access</strong>
                                            </div>
                                            <div class="card-body">
                                                <ul class="mb-0">
                                                    <li><code>view_dashboard</code> - Lihat dashboard</li>
                                                    <li><code>export_data</code> - Export data ke Excel</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-warning text-dark">
                                                <strong>System Administration</strong>
                                            </div>
                                            <div class="card-body">
                                                <ul class="mb-0">
                                                    <li><code>manage_roles</code> - Urus roles dan permissions</li>
                                                    <li><code>super_admin</code> - Akses penuh (Super Admin sahaja)</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cara Mengubah Akses -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-success" role="alert">
                                    <h6 class="alert-heading mb-2">💡 Bagaimana Mengubah Akses Pengguna?</h6>
                                    <ol style="margin: 0; padding-left: 20px; font-size: 14px;">
                                        <li>Buka tab <strong>Users</strong></li>
                                        <li>Cari pengguna yang nak ubah akses</li>
                                        <li>Klik butang <strong>Edit</strong> (ikon pensil)</li>
                                        <li>Pilih <strong>Role</strong> baru dari dropdown (rujuk matriks di atas untuk lihat permissions)</li>
                                        <li>Klik <strong>Simpan</strong></li>
                                        <li>Pengguna sekarang akan dapat akses mengikut role baru</li>
                                    </ol>
                                </div>
                                <div class="alert alert-info mt-3" role="alert">
                                    <h6 class="alert-heading mb-2">💡 Bagaimana Toggle Permission untuk Role?</h6>
                                    <ol style="margin: 0; padding-left: 20px; font-size: 14px;">
                                        <li>Buka tab <strong>Struktur Pengurusan RBAC</strong></li>
                                        <li>Lihat matriks Roles & Permissions</li>
                                        <li>Klik pada cell (✓/✗) untuk toggle permission</li>
                                        <li>Permission akan ditambah/dihapus secara automatik</li>
                                        <li>Semua pengguna dengan role tersebut akan dapat akses baru dengan segera</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportExcel(tableId) {
    var table = document.getElementById(tableId);
    var wb = XLSX.utils.book_new();
    
    // Clone table untuk exclude column "Tindakan"
    var clonedTable = table.cloneNode(true);
    var rows = clonedTable.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        // Get semua cells dalam row
        var cells = row.querySelectorAll('th, td');
        // Buang cell terakhir (Tindakan column)
        if (cells.length > 0) {
            cells[cells.length - 1].remove();
        }
    });
    
    var ws = XLSX.utils.table_to_sheet(clonedTable);
    XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
    XLSX.writeFile(wb, tableId + ".xls");
}
// Simple table sort function for all tables
function sortTable(tableId, col) {
    var table = document.getElementById(tableId);
    var switching = true;
    var dir = "asc";
    var switchcount = 0;
    while (switching) {
        switching = false;
        var rows = table.rows;
        for (var i = 1; i < (rows.length - 1); i++) {
            var shouldSwitch = false;
            var x = rows[i].getElementsByTagName("TD")[col];
            var y = rows[i + 1].getElementsByTagName("TD")[col];
            if (dir == "asc") {
                if (x.innerText.toLowerCase() > y.innerText.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerText.toLowerCase() < y.innerText.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
// Table filter for search box
function filterTable(tableId, searchValue) {
    var input = searchValue.toLowerCase();
    var table = document.getElementById(tableId);
    var trs = table.getElementsByTagName('tr');
    for (var i = 1; i < trs.length; i++) {
        var tds = trs[i].getElementsByTagName('td');
        var found = false;
        for (var j = 0; j < tds.length-1; j++) { // exclude last column (Tindakan)
            if (tds[j].innerText.toLowerCase().indexOf(input) > -1) {
                found = true;
                break;
            }
        }
        trs[i].style.display = found ? '' : 'none';
    }
}

// RBAC Matrix Interactive
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effect dan click handler untuk RBAC cells
    const rbacCells = document.querySelectorAll('.rbac-cell');
    
    rbacCells.forEach(cell => {
        // Hover effect
        cell.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0, 0, 0, 0.05)';
            this.style.transform = 'scale(1.05)';
        });
        
        cell.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
        
        // Click handler untuk toggle permission
        cell.addEventListener('click', function() {
            const roleId = this.getAttribute('data-role-id');
            const permId = this.getAttribute('data-perm-id');
            const statusSpan = this.querySelector('.perm-status');
            const isGranted = statusSpan.textContent.trim() === '✓';
            
            // AJAX call untuk toggle permission
            const formData = new FormData();
            formData.append('action', 'togglePermission');
            formData.append('roleId', roleId);
            formData.append('permId', permId);
            formData.append('grant', isGranted ? '0' : '1');
            
            fetch('api/rbac.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update display
                    if (isGranted) {
                        statusSpan.innerHTML = '<span style="color: red;">✗</span>';
                    } else {
                        statusSpan.innerHTML = '<span style="color: green;">✓</span>';
                    }
                    
                    // Show notification
                    const action = isGranted ? 'dihapus' : 'ditambah';
                    showNotification('Permission ' + action + ' berjaya', 'success');
                } else {
                    showNotification('Ralat: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ralat semasa mengubah permission', 'danger');
            });
        });
    });
});

// Notification function

// Simpan tab aktif ke localStorage dan restore bila reload
document.addEventListener('DOMContentLoaded', function() {
    // Restore tab aktif dari localStorage
    var lastTab = localStorage.getItem('rbac_active_tab');
    if (lastTab) {
        var triggerEl = document.querySelector('[href="' + lastTab + '"]');
        if (triggerEl) {
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
    // Simpan tab aktif bila user tukar tab
    var tabLinks = document.querySelectorAll('.nav-tabs .nav-link');
    tabLinks.forEach(function(link) {
        link.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem('rbac_active_tab', e.target.getAttribute('href'));
        });
    });
});

function showNotification(message, type = 'info') {
    const alertClass = 'alert-' + type;
    const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; min-width: 300px; z-index: 9999;">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
    
    const container = document.createElement('div');
    container.innerHTML = alertHtml;
    document.body.appendChild(container.firstElementChild);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.textContent.includes(message)) {
                alert.remove();
            }
        });
    }, 3000);
}
</script>

<?php require 'footer.php'; ?>
