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
        echo '<div class="alert alert-danger mt-4 ml-4">Akses tidak dibenarkan. Anda bukan super admin.</div>';
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

$permissions = $pdo->query("SELECT * FROM permissions ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- CONTENT AREA -->
<div class="content-area" style="margin-left: 260px; padding: 20px;">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fas fa-lock"></i> RBAC Management (Super Admin)</h3>
        </div>
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
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Users Tab -->
                <div class="tab-pane fade show active" id="users" role="tabpanel">
                    <h5 class="mb-3">Senarai Users</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['id_user']); ?></td>
                                    <td><?php echo htmlspecialchars($u['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($u['emel']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($u['role'] ?? 'No Role'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Roles Tab -->
                <div class="tab-pane fade" id="roles" role="tabpanel">
                    <h5 class="mb-3">Senarai Roles</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Role</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['id_role']); ?></td>
                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Permissions Tab -->
                <div class="tab-pane fade" id="permissions" role="tabpanel">
                    <h5 class="mb-3">Senarai Permissions</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Permission</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permissions as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['id_permission']); ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
