<?php
/**
 * Header untuk Aplikasi DIY Aplikasi yang Dijana
 * Menu sendiri - bukan menu MyApps
 */
if (!isset($nocode_app) || !is_array($nocode_app)) {
    $nocode_app = ['app_name' => 'Aplikasi', 'app_slug' => '', 'description' => ''];
}
$nocode_app_name = $nocode_app['app_name'] ?? 'Aplikasi';
$nocode_app_slug = $nocode_app['app_slug'] ?? '';
$nocode_app_description = $nocode_app['description'] ?? 'Aplikasi KEDA';

// Base path untuk semua URL (fix untuk pretty URL apps/nama_aplikasi)
$BASE = '/myapps/';
$app_url = $BASE . 'apps/' . $nocode_app_slug;
?>
<!DOCTYPE html>
<html lang="ms" data-app-ui="v2">
<head>
    <!-- app-ui-version: 2 | <?php echo date('c'); ?> -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#3b82f6">
    <title><?php echo htmlspecialchars($nocode_app_name); ?></title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link rel="icon" type="image/png" href="<?php echo $BASE; ?>image/keda.png?v=<?php echo time(); ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo $BASE; ?>modern-bootstrap.css?v=<?php echo time(); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; overflow-x: hidden; background-color: #f8fafc !important; }
        
        /* Sidebar - Menu Aplikasi No-Code */
        .nocode-sidebar {
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.15);
        }
        .nocode-sidebar.hidden {
            transform: translateX(-100%);
            width: 260px;
        }
        
        .nocode-sidebar .sidebar-brand {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .nocode-sidebar .sidebar-brand a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nocode-sidebar .sidebar-brand a:hover { color: white; }
        .nocode-sidebar .sidebar-brand .brand-logo {
            width: 40px;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }
        .nocode-sidebar .sidebar-brand .brand-text {
            text-align: left;
        }
        .nocode-sidebar .sidebar-brand .brand-text h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
        }
        .nocode-sidebar .sidebar-brand .brand-text small {
            display: block;
            font-size: 11.5px;
            color: rgba(255,255,255,0.6);
            line-height: 1.3;
        }
        
        .nocode-sidebar .nav-menu {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }
        .nocode-sidebar .nav-menu .nav-item {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .nocode-sidebar .nav-menu .nav-item:hover {
            background: rgba(255,255,255,0.08);
            color: white;
        }
        .nocode-sidebar .nav-menu .nav-item.active {
            background: rgba(59, 130, 246, 0.25);
            border-left-color: #3b82f6;
            color: white;
        }
        .nocode-sidebar .nav-menu .nav-item i {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }
        
        /* User profile block dalam sidebar */
        .nocode-sidebar .user-profile-block {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .nocode-sidebar .user-profile-block .profile-avatar {
            width: 72px;
            height: 72px;
            margin: 0 auto 10px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.4);
            overflow: hidden;
            background: rgba(255,255,255,0.15);
            flex-shrink: 0;
        }
        .nocode-sidebar .user-profile-block .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .nocode-sidebar .user-profile-block .profile-name {
            color: white;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            line-height: 1.3;
            margin-bottom: 8px;
        }
        .nocode-sidebar .user-profile-block .profile-role {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: white;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .nocode-main {
            margin-left: 260px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .nocode-main.expanded {
            margin-left: 0;
        }
        
        .nocode-topbar {
            background: #fff;
            padding: 12px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .nocode-topbar .app-title {
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .nocode-topbar .user-menu .btn-profil {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            color: white;
            border: none;
        }
        .nocode-topbar .user-menu .btn-profil:hover {
            background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%);
            color: white;
        }
        .nocode-topbar .user-menu .btn-password {
            color: #64748b;
            border-color: #e2e8f0;
            background: #fff;
        }
        .nocode-topbar .user-menu .btn-password:hover {
            color: #475569;
            border-color: #cbd5e1;
            background: #f8fafc;
        }
        .nocode-topbar .user-menu .btn-logout {
            color: #dc2626;
            border-color: #fecaca;
            background: #fff;
        }
        .nocode-topbar .user-menu .btn-logout:hover {
            color: #b91c1c;
            background: #fef2f2;
            border-color: #f87171;
        }
        
        @media (max-width: 991px) {
            .nocode-sidebar { transform: translateX(-100%); }
            .nocode-sidebar.active { transform: translateX(0); }
            .nocode-main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Overlay mobile -->
<div class="position-fixed top-0 start-0 w-100 h-100 bg-dark opacity-50 d-none" id="nocodeSidebarOverlay" style="z-index: 999;" onclick="toggleNocodeSidebar()"></div>

<!-- SIDEBAR - Menu Aplikasi No-Code (Logo KEDA seperti MyApps) -->
<nav class="nocode-sidebar" id="nocodeSidebar">
    <div class="sidebar-brand">
        <a href="<?php echo htmlspecialchars($app_url); ?>">
            <img src="<?php echo $BASE; ?>image/keda.png" width="40" class="brand-logo" alt="KEDA">
            <div class="brand-text">
                <h5 class="mb-0 fw-bold text-white"><?php echo htmlspecialchars($nocode_app_name); ?></h5>
                <small><?php echo htmlspecialchars($nocode_app_description); ?></small>
            </div>
        </a>
    </div>
    
    <?php
    $profil_pic = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
    if (!empty($_SESSION['gambar'])) {
        $profil_pic = $BASE . "uploads/" . $_SESSION['gambar'];
    }
    $user_name = $_SESSION['nama'] ?? 'User';
    $user_role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'USER';
    $user_id = $_SESSION['user_id'] ?? 0;
    ?>
    <div class="user-profile-block">
        <div class="profile-avatar">
            <img src="<?php echo htmlspecialchars($profil_pic); ?>" alt="Profil">
        </div>
        <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
        <span class="profile-role"><?php echo htmlspecialchars($user_role); ?></span>
    </div>
    
    <div class="nav-menu">
        <a href="<?php echo htmlspecialchars($app_url); ?>#dashboard" class="nav-item">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="<?php echo htmlspecialchars($app_url); ?>#senarai" class="nav-item">
            <i class="fas fa-list"></i> Senarai Rekod
        </a>
        <a href="<?php echo htmlspecialchars($app_url); ?>#carta" class="nav-item">
            <i class="fas fa-chart-bar"></i> Visualisasi Data
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="nocode-main" id="nocodeMain">
    <div class="nocode-topbar">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-light btn-sm d-lg-none" onclick="toggleNocodeSidebar()" title="Menu">
                <i class="fas fa-bars"></i>
            </button>
            <button type="button" class="btn btn-light btn-sm d-none d-lg-inline-block" id="nocodeToggleSidebar" onclick="toggleNocodeSidebarDesktop()" title="Sembunyikan Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="nocode-app-title mb-0 fw-bold text-secondary"><?php echo htmlspecialchars($nocode_app_name); ?></h5>
        </div>
        <div class="user-menu d-flex flex-wrap align-items-center gap-2">
            <a href="<?php echo $BASE; ?>dashboard_aplikasi.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fas fa-th-large me-1"></i> MyApps
            </a>
            <a href="<?php echo $BASE; ?>proses_staf.php?id=<?php echo (int)$user_id; ?>" class="btn btn-profil btn-sm rounded-pill">
                <i class="fas fa-user-edit me-1"></i> Profil
            </a>
            <a href="<?php echo $BASE; ?>tukar_password.php" class="btn btn-password btn-sm rounded-pill border">
                <i class="fas fa-key me-1"></i> Password
            </a>
            <a href="<?php echo $BASE; ?>logout.php" class="btn btn-logout btn-sm rounded-pill border">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

<script>
function toggleNocodeSidebar() {
    document.getElementById('nocodeSidebar').classList.toggle('active');
    document.getElementById('nocodeSidebarOverlay').classList.toggle('d-none');
}
function toggleNocodeSidebarDesktop() {
    var sidebar = document.getElementById('nocodeSidebar');
    var main = document.getElementById('nocodeMain');
    sidebar.classList.toggle('hidden');
    main.classList.toggle('expanded');
    var btn = document.getElementById('nocodeToggleSidebar');
    if (btn) {
        btn.innerHTML = sidebar.classList.contains('hidden') ? '<i class="fas fa-chevron-right"></i>' : '<i class="fas fa-bars"></i>';
        btn.title = sidebar.classList.contains('hidden') ? 'Tunjuk Menu' : 'Sembunyikan Menu';
    }
    try { localStorage.setItem('nocodeSidebarHidden', sidebar.classList.contains('hidden') ? '1' : '0'); } catch(e) {}
}
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('nocodeSidebarHidden') === '1') {
            document.getElementById('nocodeSidebar').classList.add('hidden');
            document.getElementById('nocodeMain').classList.add('expanded');
            var btn = document.getElementById('nocodeToggleSidebar');
            if (btn) { btn.innerHTML = '<i class="fas fa-chevron-right"></i>'; btn.title = 'Tunjuk Menu'; }
        }
    } catch(e) {}
});

// Buang parameter cache-bust (_=) dari address bar supaya URL kemas (apps/mydesa sahaja)
if (typeof location !== 'undefined' && location.search && location.search.indexOf('_=') !== -1) {
    try { history.replaceState({}, '', location.pathname); } catch(e) {}
}
// Bila user klik Back dari Profil/Password, browser pulih halaman dari bfcache dan imej tak dimuat semula.
// Paksa semula imej logo & profil bila halaman dipulih dari back-forward cache.
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        var sep = function(u) { return u && u.indexOf('?') >= 0 ? '&' : '?'; };
        document.querySelectorAll('.brand-logo, .profile-avatar img').forEach(function(img) {
            var s = img.getAttribute('src');
            if (s) { img.src = s.split('?')[0] + sep(s) + '_=' + Date.now(); }
        });
    }
});
</script>
