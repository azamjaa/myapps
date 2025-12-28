<?php
session_start();

// Verify user is logged in and active
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

// Check if user status is active (id_status = 1)
$user_check = $db->prepare("SELECT id_status FROM staf WHERE id_staf = ? LIMIT 1")->execute([$_SESSION['user_id']])->fetch();
if (!$user_check || $user_check['id_status'] != 1) {
    session_destroy();
    header("Location: index.php?akses_ditolak=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyBooking KEDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { background-color: #f8f9fa; overflow-x: hidden; }
        
        /* SIDEBAR STYLING */
        .sidebar { min-height: 100vh; width: 260px; background: #2c3e50; color: white; transition: all 0.3s ease; position: fixed; z-index: 1000; top: 0; left: 0; }
        .sidebar a.nav-link { color: #bdc3c7; text-decoration: none; display: block; padding: 15px 25px; border-left: 4px solid transparent; }
        .sidebar a.nav-link:hover, .sidebar a.nav-link.active { background: #34495e; color: #fff; border-left-color: #3498db; }
        .content { margin-left: 260px; padding: 20px; transition: all 0.3s ease; min-height: 100vh; }
        .sidebar-header { padding: 20px; background: #1a252f; }
        .sidebar-logo { width: 40px; margin-right: 10px; }
        
        /* SIDEBAR TOGGLE */
        body.sidebar-toggled .sidebar { margin-left: -260px; }
        body.sidebar-toggled .content { margin-left: 0; }

        @media (max-width: 768px) {
            .sidebar { margin-left: -260px; }
            .content { margin-left: 0; }
            body.sidebar-toggled .sidebar { margin-left: 0; }
            body.sidebar-toggled::before { content: ""; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; }
        }

        .sidebar-section-title { padding: 20px 25px 10px; font-size: 0.75rem; font-weight: bold; color: #95a5a6; text-transform: uppercase; margin-top: 10px; }
        .sidebar-divider { border-top: 1px solid #34495e; margin: 10px 0; }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header text-center">
        <div class="d-flex align-items-center justify-content-center mb-2">
            <img src="image/keda.png" class="sidebar-logo" onerror="this.src='https://via.placeholder.com/40'">
            <h4 class="mb-0 fw-bold">MyBooking</h4>
        </div>
        <small class="text-muted">Tempahan Bilik Mesyuarat</small>
    </div>
    
    <div class="py-3 text-center border-bottom border-secondary bg-dark bg-opacity-25 px-3">
        <div class="mb-2">
            <?php 
            $profil_pic = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
            if (!empty($_SESSION['gambar'])) {
                $profil_pic = "uploads/" . $_SESSION['gambar'];
            }
            ?>
            <img src="<?php echo $profil_pic; ?>" class="rounded-circle border border-2 border-white" width="70" height="70" style="object-fit: cover;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
        </div>
        
        <div class="text-white fw-bold px-2 mb-1" style="font-size: 0.9rem; line-height: 1.3; word-wrap: break-word;">
            <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
        </div>
        <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
            <?php 
            $roles = getUserRoles($_SESSION['user_id']);
            foreach ($roles as $role):
            ?>
            <span class="badge bg-primary" style="font-size: 0.7rem;"><?php echo $role; ?></span>
            <?php endforeach; ?>
        </div>
        
        <div class="d-grid gap-2 px-2">
            <a href="logout.php" class="btn btn-sm btn-outline-danger text-white" style="font-size: 0.7rem; border-radius: 20px;">
                <i class="fas fa-sign-out-alt me-1"></i> Log Keluar
            </a>
        </div>
    </div>

    <!-- MAIN NAVIGATION -->
    <div class="sidebar-section-title">Menu Utama</div>
    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':''; ?>">
        <i class="fas fa-chart-line me-3"></i> Dashboard
    </a>
    <a href="booking_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_list.php'?'active':''; ?>">
        <i class="fas fa-list me-3"></i> Senarai Tempahan
    </a>
    <a href="booking_calendar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_calendar.php'?'active':''; ?>">
        <i class="fas fa-calendar-alt me-3"></i> Kalendar
    </a>
    <a href="booking_add.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_add.php'?'active':''; ?>">
        <i class="fas fa-plus-circle me-3"></i> Tempahan Baru
    </a>

    <!-- MANAGER & ADMIN SECTION -->
    <?php if (canApprove($_SESSION['user_id'])): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section-title">Menu Pengurus</div>
    <a href="approval_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='approval_list.php'?'active':''; ?>">
        <i class="fas fa-check-square me-3"></i> Persetujuan
    </a>
    <?php endif; ?>

    <!-- ADMIN ONLY SECTION -->
    <?php if (isAdmin($_SESSION['user_id'])): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section-title">Menu Pentadbir</div>
    <a href="bilik_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bilik_list.php' || basename($_SERVER['PHP_SELF']) == 'bilik_add.php' || basename($_SERVER['PHP_SELF']) == 'bilik_edit.php' ? 'active' : ''; ?>">
        <i class="fas fa-door-open me-3"></i> Bilik Mesyuarat
    </a>
    <a href="staf_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='staf_list.php'?'active':''; ?>">
        <i class="fas fa-users me-3"></i> Pengurusan Staf
    </a>
    <?php endif; ?>

</div>

<div class="content">
    
    <div class="top-navbar bg-white p-3 mb-4 shadow-sm rounded d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-secondary border-0 me-3" id="sidebarToggle"><i class="fas fa-bars fa-lg"></i></button>
            <h5 class="mb-0 fw-bold text-dark">MyBooking KEDA</h5>
        </div>

        <div class="d-flex align-items-center">
            <span class="d-none d-md-block me-3 text-muted small text-end">
                Selamat Datang,<br><b><?php echo htmlspecialchars($_SESSION['nama']); ?></b>
            </span>
            <div class="border-start ps-3">
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Log Keluar
                </a>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-toggled');
    });
</script>
