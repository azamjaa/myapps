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

// LOAD DATA
$users = $pdo->query("SELECT u.id_user, u.nama, u.emel, r.name as role FROM users u
    LEFT JOIN user_roles ur ON u.id_user = ur.id_user
    LEFT JOIN roles r ON ur.id_role = r.id_role
    ORDER BY u.nama ASC")->fetchAll(PDO::FETCH_ASSOC);

$roles = $pdo->query("SELECT * FROM roles ORDER BY id_role ASC")->fetchAll(PDO::FETCH_ASSOC);

$permissions = $pdo->query("SELECT * FROM permissions ORDER BY id_permission ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- CONTENT AREA -->
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-0">
            <i class="fas fa-user-shield text-primary me-2"></i>Pengurusan Role Based Access Control (RBAC)
        </h3>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="users-tab" data-bs-toggle="tab" href="#users" role="tab">
                        <i class="fas fa-users mr-2"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="roles-tab" data-bs-toggle="tab" href="#roles" role="tab">
                        <i class="fas fa-shield mr-2"></i>Roles
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="permissions-tab" data-bs-toggle="tab" href="#permissions" role="tab">
                        <i class="fas fa-lock mr-2"></i>Permissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab">
                        <i class="fas fa-diagram-project mr-2"></i>Struktur Pengurusan RBAC
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Users Tab -->
                <div class="tab-pane fade show active" id="users" role="tabpanel">
                                        <h5 class="mb-3">Senarai Users</h5>
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
                                                        <th style="width:50px;cursor:pointer;" onclick="sortTable('usersTable', 0)">Bil</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('usersTable', 1)">Nama</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('usersTable', 2)">Email</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('usersTable', 3)">Role</th>
                                                        <th style="width:80px;">Tindakan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $bil=1; foreach ($users as $u): ?>
                                                    <tr>
                                                        <td><?php echo $bil++; ?></td>
                                                        <td><?php echo htmlspecialchars($u['nama']); ?></td>
                                                        <td><?php echo htmlspecialchars($u['emel']); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo htmlspecialchars($u['role'] ?? 'No Role'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if(hasAccess($pdo, $current_user, 1, 'edit_user')): ?>
                                                            <button class="btn btn-warning btn-sm edit-user-btn" title="Edit" 
                                                                data-user-id="<?php echo $u['id_user']; ?>" 
                                                                data-user-name="<?php echo htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                                data-user-email="<?php echo htmlspecialchars($u['emel'], ENT_QUOTES, 'UTF-8'); ?>" 
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
                                                                <label for="userEmail" class="form-label">Email</label>
                                                                <input type="email" class="form-control" id="userEmail" name="userEmail" required>
                                                                <div class="invalid-feedback" style="display: none; color: #dc3545; font-size: 13px; margin-top: 5px;">
                                                                    Email tidak sah atau kosong
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
                                                var email = this.getAttribute('data-user-email');
                                                var role = this.getAttribute('data-user-role');
                                                showEditUserModal(id, name, email, role);
                                            });
                                        });
                                        
                                        function showAddUserModal() {
                                                document.getElementById('userModalLabel').innerText = 'Tambah User';
                                                document.getElementById('userId').value = '';
                                                document.getElementById('userName').value = '';
                                                document.getElementById('userEmail').value = '';
                                                document.getElementById('userRole').value = '';
                                                var modal = new bootstrap.Modal(document.getElementById('userModal'));
                                                modal.show();
                                        }
                                        function showEditUserModal(id, name, email, role) {
                                                document.getElementById('userModalLabel').innerText = 'Ubah User';
                                                document.getElementById('userId').value = id;
                                                document.getElementById('userName').value = name;
                                                document.getElementById('userEmail').value = email;
                                                
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
                                                var userEmail = document.getElementById('userEmail').value;
                                                var userRole = document.getElementById('userRole').value;
                                                var form = document.getElementById('userForm');
                                                
                                                // Semak validation
                                                var isValid = true;
                                                var nameError = form.querySelector('[for="userName"] ~ .invalid-feedback');
                                                var emailError = form.querySelector('[for="userEmail"] ~ .invalid-feedback');
                                                var roleError = form.querySelector('[for="userRole"] ~ .invalid-feedback');
                                                
                                                // Reset error display
                                                nameError.style.display = 'none';
                                                emailError.style.display = 'none';
                                                roleError.style.display = 'none';
                                                
                                                if (!userName) {
                                                    nameError.style.display = 'block';
                                                    isValid = false;
                                                }
                                                if (!userEmail || !userEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                                                    emailError.style.display = 'block';
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
                                                formData.append('userEmail', userEmail);
                                                formData.append('userRole', userRole);
                                                
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
                                        <h5 class="mb-3">Senarai Roles</h5>
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
                                            <table class="table table-hover align-middle sortable-table" id="rolesTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:50px;cursor:pointer;" onclick="sortTable('rolesTable', 0)">Bil</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('rolesTable', 1)">Nama Role</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('rolesTable', 2)">Deskripsi</th>
                                                        <th style="width:80px;">Tindakan</th>
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
                                        <h5 class="mb-3">Senarai Permissions</h5>
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
                                            <table class="table table-hover align-middle sortable-table" id="permissionsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:50px;cursor:pointer;" onclick="sortTable('permissionsTable', 0)">Bil</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('permissionsTable', 1)">Nama Permission</th>
                                                        <th style="cursor:pointer;" onclick="sortTable('permissionsTable', 2)">Deskripsi</th>
                                                        <th style="width:80px;">Tindakan</th>
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
                        <h5 class="mb-4"><i class="fas fa-diagram-project me-2"></i>Cara Kerja Sistem RBAC</h5>
                        
                        <!-- Penjelasan -->
                        <div class="alert alert-info mb-4" role="alert">
                            <h6 class="alert-heading mb-2">📋 RBAC (Role Based Access Control)</h6>
                            <p class="mb-0">Sistem pengurusan akses berdasarkan peranan. MyApps menggunakan kombinasi 3 komponen: <strong>Users → Roles → Permissions</strong></p>
                        </div>

                        <!-- Diagram Aliran -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <div class="card shadow-sm border-0">
                                    <div class="card-body">
                                        <h6 class="card-title mb-4">Aliran Akses Pengguna</h6>
                                        <div style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; gap: 15px;">
                                            <!-- User -->
                                            <div style="text-align: center; flex: 1; min-width: 150px;">
                                                <div style="background: #007bff; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; margin-bottom: 10px;">
                                                    👤 Pengguna (User)
                                                </div>
                                                <small style="color: #666;">Contoh: Ali, Budi, Citra</small>
                                            </div>

                                            <!-- Arrow -->
                                            <div style="font-size: 24px; color: #666;">→</div>

                                            <!-- Role -->
                                            <div style="text-align: center; flex: 1; min-width: 150px;">
                                                <div style="background: #6c757d; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; margin-bottom: 10px;">
                                                    🎭 Peranan (Role)
                                                </div>
                                                <small style="color: #666;">Contoh: Penyedia, Penyemak, Pelulus</small>
                                            </div>

                                            <!-- Arrow -->
                                            <div style="font-size: 24px; color: #666;">→</div>

                                            <!-- Permission -->
                                            <div style="text-align: center; flex: 1; min-width: 150px;">
                                                <div style="background: #28a745; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; margin-bottom: 10px;">
                                                    🔐 Kebenaran (Permission)
                                                </div>
                                                <small style="color: #666;">Contoh: Create, Edit, Delete, Approve</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                            </div>
                        </div>
                    </div>
                </div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportExcel(tableId) {
    var table = document.getElementById(tableId);
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(table);
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
