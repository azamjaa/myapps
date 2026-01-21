<?php
// 1. SEMAKAN SESI & LOGIN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

// 2. PREVENT BROWSER CACHING
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// 3. SAMBUNG DB
require_once 'db.php';

// Pastikan Azam sudah ada session user_id
$current_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
$this_app_id = 12; // Contoh: ID untuk MyApps

// Sidebar aplikasi dinamik berdasarkan akses RBAC
$sidebarApps = [];
if ($current_user) {
    $sql = "SELECT a.id_aplikasi, a.nama_aplikasi, a.icon, a.url
            FROM aplikasi a
            JOIN application_access aa ON a.id_aplikasi = aa.id_aplikasi
            JOIN user_roles ur ON aa.id_role = ur.id_role
            WHERE ur.id_user = ?
            ORDER BY a.nama_aplikasi ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_user]);
    $sidebarApps = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. FORCE LOGOUT IF STAFF IS NOT ACTIVE
if ($current_user) {
    $stmt = $pdo->prepare("SELECT aktif FROM users WHERE id_user = ? LIMIT 1");
    $stmt->execute([$current_user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['aktif']) && $row['aktif'] == 0) {
        session_destroy();
        header("Location: index.php?notactive=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no, maximum-scale=1">
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MyApps KEDA">
    <title>MyApps KEDA</title>
    
    <!-- Content Security Policy - Allow unsafe-eval for Turf.js and Chart.js -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://*.jsdelivr.net https://*.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://*.jsdelivr.net https://*.cloudflare.com; img-src 'self' data: blob: https://cdn-icons-png.flaticon.com https://*.tile.openstreetmap.org https://*.tile.osm.org https://*.basemaps.cartocdn.com; font-src 'self' data: https://cdnjs.cloudflare.com https://*.cloudflare.com; connect-src 'self' http://localhost:11434 https://cdn.jsdelivr.net https://unpkg.com https://*.jsdelivr.net https://*.cloudflare.com https://*.tile.openstreetmap.org https://*.tile.osm.org https://*.basemaps.cartocdn.com; worker-src 'self' blob:; child-src 'self' blob:;">
    
    <link rel="icon" type="image/png" href="image/keda.png?v=<?php echo time(); ?>">
    <link rel="apple-touch-icon" href="image/keda.png">
    
    <!-- PWA -->
    <link rel="manifest" href="manifest.json?v=<?php echo time(); ?>">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS - Removed to avoid production warning -->
    <!-- If needed, install Tailwind CSS properly for production: https://tailwindcss.com/docs/installation -->
    <!--
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-',
            corePlugins: { preflight: false }
        }
    </script>
    -->
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* PWA & MOBILE VIEWPORT FIX */
        * { 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Force CSS Reload */
        body { background-color: #f3f4f6; overflow-x: hidden; }

        /* SIDEBAR STYLES */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: 300px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
            width: 300px;
        }

        /* CONTENT AREA */
        .main-content {
            margin-left: 300px;
            padding: 20px;
            transition: all 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 300px);
        }
        
        .main-content.expanded {
            margin-left: 0;
            width: 100%;
        }

        /* NAV ITEMS */
        .nav-item {
            display: block;
            padding: 6px 16px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            margin: 0;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white; border-left-color: #3b82f6;
        }
        .nav-item i { width: 25px; margin-right: 10px; text-align: center; }
        
        /* SUBMENU ITEMS - Permanent */
        .submenu-item {
            padding-left: 36px !important;
            font-size: 0.95em;
            background: rgba(0,0,0,0.2);
            margin: 0;
        }
        .submenu-item:hover, .submenu-item.active {
            background: rgba(0,0,0,0.3);
            color: white;
            border-left-color: #3b82f6;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { 
                transform: translateX(-100%); 
                width: 100%;
            }
            .main-content { 
                margin-left: 0 !important; 
                width: 100% !important;
                padding: 10px !important;
            }
            .sidebar.active { transform: translateX(0); }
            
            /* MOBILE FIXES */
            body { overflow-x: hidden; }
            .container, .container-fluid { padding: 0 10px; }
            
            /* Card responsiveness */
            .card { margin-bottom: 15px; }
            
            /* Table overflow */
            .table-responsive { display: block; width: 100%; overflow-x: auto; }
            
            /* Chart responsive */
            .chart-container { max-width: 100%; }
            canvas { max-width: 100% !important; }
            
            /* Button groups */
            .btn-group-vertical { width: 100%; }
            .btn-group .btn { flex: 1; }
            
            /* Modal full width */
            .modal-dialog { margin: 10px; }
            .modal-content { border-radius: 10px; }
            
            /* Input responsiveness */
            input, textarea, select { width: 100% !important; }
            
            /* Typography */
            h1, h2, h3, h4, h5, h6 { font-size: 1rem !important; }
            
            /* Column responsive */
            .col-md-3, .col-md-4, .col-md-6 { width: 100% !important; }
            
            /* Remove fixed widths */
            [style*="width: 260px"] { width: 100% !important; }
            [style*="width: calc"] { width: 100% !important; }
        }
        
        /* Extra small devices */
        @media (max-width: 480px) {
            .main-content { padding: 5px !important; }
            .sidebar { width: 90vw; }
            .container-fluid { padding: 0 5px; }
            .card-body { padding: 10px !important; }
        }
        
        /* UTILITY */
        .hidden { display: none !important; }
        
        /* ============================================
           GRADIENT SYSTEM - BUTTONS ONLY
           ============================================ */
        
        /* Gradient Buttons */
        .btn-primary, .btn-success, .btn-danger, .btn-warning, .btn-info {
            background: linear-gradient(135deg, var(--btn-start), var(--btn-end)) !important;
            border: none !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-primary {
            --btn-start: #4169E1;
            --btn-end: #1E40AF;
        }
        
        .btn-success {
            --btn-start: #10B981;
            --btn-end: #059669;
        }
        
        .btn-danger {
            --btn-start: #EF4444;
            --btn-end: #DC2626;
        }
        
        .btn-warning {
            --btn-start: #F59E0B;
            --btn-end: #D97706;
        }
        
        .btn-info {
            --btn-start: #06B6D4;
            --btn-end: #0891B2;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2) !important;
        }
        
        /* CHATBOT */
        #mawarWrapper { position: fixed; bottom: 10px; right: 30px; z-index: 9999; transition: all 0.3s ease; }
        
        /* Expanded state */
        #mawarWrapper:not(.collapsed) { width: auto; height: auto; }
        #mawarWrapper:not(.collapsed) .chat-mascot-container { width: 80px; height: 80px; }
        #mawarWrapper:not(.collapsed) .mawar-label { opacity: 1; visibility: visible; }
        #mawarWrapper:not(.collapsed) .chat-bubble { opacity: 1; visibility: visible; }
        
        /* Collapsed state */
        #mawarWrapper.collapsed { width: 50px; height: 50px; }
        #mawarWrapper.collapsed .chat-mascot-container { width: 50px; height: 50px; }
        #mawarWrapper.collapsed .mawar-label { opacity: 0; visibility: hidden; }
        #mawarWrapper.collapsed .chat-bubble { opacity: 0; visibility: hidden; }
        #mawarWrapper.collapsed .chat-box { display: none !important; }
        
        .chat-mascot-container { cursor: pointer; transition: all 0.3s; position: relative; }
        .chat-mascot-container:hover { transform: scale(1.1); }
        .mawar-label { 
            position: absolute; 
            bottom: -25px; 
            left: 50%; 
            transform: translateX(-50%); 
            white-space: nowrap; 
            font-size: 11px; 
            color: #666;
            font-weight: bold;
            transition: all 0.3s;
        }
        .collapse-mawar-btn { 
            position: absolute; 
            top: -8px; 
            right: -8px; 
            width: 24px; 
            height: 24px; 
            background: #dc3545; 
            color: white; 
            border: none; 
            border-radius: 50%; 
            cursor: pointer; 
            font-size: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: all 0.2s;
            opacity: 0;
            z-index: 10;
        }
        .chat-mascot-container:hover .collapse-mawar-btn { opacity: 1; }
        .collapse-mawar-btn:hover { background: #b71c1c; transform: scale(1.2); }
        .chat-bubble { 
            position: absolute; 
            bottom: 90px; 
            right: 0; 
            background: white; 
            padding: 10px 15px; 
            border-radius: 15px 15px 0 15px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
            font-size: 13px; 
            white-space: nowrap;
            border: 2px solid #dc3545;
            animation: float 2s ease-in-out infinite;
        }
        .chat-bubble::after {
            content: '';
            position: absolute;
            bottom: -8px;
            right: 20px;
            border-width: 8px 8px 0 8px;
            border-style: solid;
            border-color: #dc3545 transparent transparent transparent;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        .ai-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 8px;
            vertical-align: middle;
            border: 2.5px solid #dc3545;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        /* Chat Box */
        .chat-box { 
            position: fixed; 
            bottom: 110px; 
            right: 30px; 
            width: 380px; 
            height: 500px; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); 
            z-index: 10001; 
            display: none; 
            flex-direction: column;
            overflow: hidden;
            max-height: 85vh;
        }
        @media (max-width: 600px) {
            .chat-box {
                width: 85vw !important;
                right: 4vw !important;
                left: auto !important;
                min-width: 0 !important;
                border-radius: 12px;
            }
        }
        
        /* Chat Header - Fixed */
        .chat-box .p-3:first-child {
            flex-shrink: 0;
            min-height: auto;
        }
        
        /* Chat Messages Area - Scrollable */
        #chatBody {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px;
            background-color: #f9f9f9;
        }
        
        /* Chat Input Area - Fixed at bottom */
        .chat-box .d-flex:last-of-type {
            flex-shrink: 0;
            border-top: 1px solid #e0e0e0;
            background-color: white;
            padding: 10px !important;
            gap: 8px;
        }
    </style>
</head>
<body>

<!-- Mobile Overlay -->
<div class="position-fixed top-0 start-0 w-100 h-100 bg-dark opacity-50 d-none" id="sidebarOverlay" onclick="toggleSidebar()" style="z-index: 999;"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="p-4 text-center border-bottom border-secondary">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <img src="image/keda.png" width="40" class="me-2">
            <div class="text-start">
                <h5 class="mb-0 fw-bold text-white">MyApps</h5>
                <small class="text-white-50" style="font-size: 11.5px;">Direktori Aplikasi KEDA</small>
            </div>
        </div>
        
        <!-- Profile -->
        <?php 
            $profil_pic = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
            if (!empty($_SESSION['gambar'])) { $profil_pic = "uploads/" . $_SESSION['gambar']; }
        ?>
        <div style="text-align: center; margin-bottom: 15px;">
            <div style="width: 70px; height: 70px; margin: 0 auto; border-radius: 50%; border: 3px solid white; overflow: hidden; background-color: #f0f0f0; flex-shrink: 0;">
                <img src="<?php echo $profil_pic; ?>" 
                     alt="Profile Picture"
                     style="width: 100%; 
                             height: 100%; 
                             object-fit: cover;
                             object-position: center;
                             image-rendering: crisp-edges;
                             image-rendering: pixelated;
                             image-rendering: -webkit-optimize-contrast;
                             -ms-interpolation-mode: nearest-neighbor;
                             filter: contrast(1.1) brightness(1.05) saturate(1.1);
                             transform: translate(0,0);
                             backface-visibility: hidden;
                             -webkit-backface-visibility: hidden;">
            </div>
        </div>
        <div class="text-white fw-bold small text-uppercase"><?php echo $_SESSION['nama']; ?></div>
        <span class="badge bg-primary mt-1"><?php echo strtoupper($_SESSION['role']); ?></span>
    </div>

    <!-- Menu -->
    <div class="py-2">
        <!-- Dashboard Aplikasi -->
        <a href="dashboard_aplikasi.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='dashboard_aplikasi.php'?'active':''; ?>">
            <i class="fas fa-chart-line"></i> Dashboard Aplikasi
        </a>

        <a href="dashboard_perjawatan.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='dashboard_perjawatan.php'?'active':''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard Perjawatan
        </a>
        <!-- Dashboard Pencapaian KEDA -->
        <a href="dashboard_pencapaian.php" class="nav-item compact-menu <?php echo basename($_SERVER['PHP_SELF'])=='dashboard_pencapaian.php'?'active':''; ?>">
            <i class="fas fa-map-marked-alt"></i> Dashboard Pencapaian
        </a>
        <!-- Pengurusan Rekod Dashboard - Submenu (Admin Only) -->
        <?php 
        // Check if user is admin
        $current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);
        $isAdmin = false;
        if ($current_user) {
            require_once __DIR__ . '/src/rbac_helper.php';
            $isAdmin = isSuperAdmin($pdo, $current_user);
        }
        if ($isAdmin): ?>
        <a href="pengurusan_rekod_dashboard.php" class="nav-item submenu-item <?php echo basename($_SERVER['PHP_SELF'])=='pengurusan_rekod_dashboard.php'?'active':''; ?>">
            <i class="fas fa-file-alt"></i> Pengurusan Rekod Dashboard
        </a>
        <?php endif; ?>
            </div>
        </div>
        <a href="manual.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='manual.php'?'active':''; ?>">
            <i class="fas fa-book-open"></i> Manual Pengguna
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
        <a href="rbac_management.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='rbac_management.php'?'active':''; ?>">
            <i class="fas fa-user-shield"></i> Pengurusan RBAC
        </a>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="p-3 mt-2">
        <!-- Logout button moved to top bar; sidebar button removed -->
    </div>

    <!-- Sidebar aplikasi dinamik berdasarkan akses RBAC -->
    <div class="py-2">
        <ul class="app-list">
            <?php foreach ($sidebarApps as $app): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($app['url']); ?>" title="<?php echo htmlspecialchars($app['nama_aplikasi']); ?>">
                        <img src="<?php echo htmlspecialchars($app['icon']); ?>" alt="<?php echo htmlspecialchars($app['nama_aplikasi']); ?>" style="width:32px;height:32px;">
                        <span><?php echo htmlspecialchars($app['nama_aplikasi']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
    <!-- Top Bar -->
    <div class="top-bar bg-white p-3 mb-4 shadow-sm rounded d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light btn-sm" id="toggleSidebarBtn" onclick="toggleSidebarDesktop()" title="Hide/Show Menu">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 fw-bold text-secondary">MyApps KEDA</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="proses_staf.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                <i class="fas fa-user-edit me-1"></i> Profil
            </a>
            <a href="tukar_password.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fas fa-key me-1"></i> Password
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

<!-- CHATBOT -->
<div id="mawarWrapper">
    <div class="position-relative">
        <div class="chat-bubble text-dark">
            Hai! Saya Mawar.<br>Boleh Saya Bantu?
        </div>
        <div class="chat-mascot-container" onclick="toggleChat()">
            <img src="image/mawar.png" class="w-100 h-100 rounded-circle border border-danger border-3 shadow bg-white" style="object-fit: cover;">
            <button class="collapse-mawar-btn" onclick="collapseMawar(event)" title="Collapse Mawar">
                <i class="fas fa-minus"></i>
            </button>
            <div class="mawar-label">Mawar</div>
        </div>
    </div>
</div>
<div class="chat-box" id="chatBox">
    <div class="p-3 text-white d-flex align-items-center" style="background: linear-gradient(135deg, #dc3545, #c82333);">
        <img src="image/mawar.png" width="35" class="rounded-circle border border-2 me-2 bg-white">
        <div>
            <h6 class="mb-0">Mawar</h6>
            <small style="font-size: 11px;">● Pembantu Digital</small>
        </div>
        <button onclick="toggleChat()" class="btn-close btn-close-white ms-auto"></button>
    </div>
    <div class="flex-grow-1 p-3 bg-light overflow-auto" id="chatBody">
        <div class="bg-white p-2 rounded shadow-sm mb-2 border-start border-danger border-4" style="max-width:85%; font-size: 13px;">
            <strong>Hai! Saya Mawar.</strong><br>
            <small>Ada apa saya boleh bantu? 😊</small>
        </div>
    </div>
    <div class="p-2 bg-white border-top d-flex gap-2" style="margin: 0;">
        <input type="text" id="chatInput" class="form-control rounded-pill" placeholder="Taip soalan..." onkeypress="handleEnter(event)" style="font-size: 13px; padding: 8px 15px;">
        <button onclick="hantarMesej()" class="btn btn-danger rounded-circle flex-shrink-0" style="width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 14px;"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (file_exists('pwa-installer.js')): ?>
<script src="pwa-installer.js"></script>
<?php endif; ?>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    var overlay = document.getElementById('sidebarOverlay');
    overlay.classList.toggle('d-none');
}

function toggleSidebarDesktop() {
    var sidebar = document.getElementById('sidebar');
    var mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('hidden');
    mainContent.classList.toggle('expanded');
    
    // Save preference to localStorage
    var isHidden = sidebar.classList.contains('hidden');
    localStorage.setItem('sidebarHidden', isHidden ? 'true' : 'false');
    
    // Update button icon
    var btn = document.getElementById('toggleSidebarBtn');
    if(isHidden) {
        btn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        btn.title = 'Show Menu';
    } else {
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        btn.title = 'Hide Menu';
    }
}

// Load sidebar preference from localStorage
document.addEventListener('DOMContentLoaded', function() {
    var sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
    if(sidebarHidden) {
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.querySelector('.main-content');
        sidebar.classList.add('hidden');
        mainContent.classList.add('expanded');
        
        var btn = document.getElementById('toggleSidebarBtn');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            btn.title = 'Show Menu';
        }
    }
});

function collapseMawar(event) {
    event.stopPropagation(); // Prevent triggering toggleChat
    var wrapper = document.getElementById('mawarWrapper');
    wrapper.classList.toggle('collapsed');
    
    // Save preference to localStorage
    var isCollapsed = wrapper.classList.contains('collapsed');
    localStorage.setItem('mawarCollapsed', isCollapsed ? 'true' : 'false');
    
    // Close chat box if collapsing
    if(isCollapsed) {
        var chatBox = document.getElementById('chatBox');
        chatBox.style.display = 'none';
    }
}

// Load mawar preference from localStorage
document.addEventListener('DOMContentLoaded', function() {
    var mawarCollapsed = localStorage.getItem('mawarCollapsed') === 'true';
    if(mawarCollapsed) {
        var wrapper = document.getElementById('mawarWrapper');
        wrapper.classList.add('collapsed');
    }
});

function toggleChat() {
    // If Mawar is collapsed, expand it first
    var wrapper = document.getElementById('mawarWrapper');
    if(wrapper.classList.contains('collapsed')) {
        wrapper.classList.remove('collapsed');
        localStorage.setItem('mawarCollapsed', 'false');
    }
    
    var box = document.getElementById('chatBox');
    box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'flex' : 'none';
    if(box.style.display === 'flex') document.getElementById('chatInput').focus();
}

function handleEnter(e) { if(e.key === 'Enter') hantarMesej(); }

function hantarMesej() {
    var input = document.getElementById('chatInput');
    var msg = input.value.trim();
    if(!msg) return;
    
    var body = document.getElementById('chatBody');
    var div = document.createElement('div');
    div.className = "bg-danger text-white p-2 rounded shadow-sm mb-2 ms-auto";
    div.style.maxWidth = "80%";
    div.textContent = msg;
    body.appendChild(div);
    input.value = '';
    body.scrollTop = body.scrollHeight;

    var formData = new FormData();
    formData.append('mesej', msg);
    // Papar status sementara supaya pengguna tahu sistem sedang memproses
    var thinkingDiv = document.createElement('div');
    thinkingDiv.className = "bg-white p-2 rounded shadow-sm mb-2 border-start border-warning border-4 text-muted d-flex align-items-center";
    thinkingDiv.style.maxWidth = "80%";
    thinkingDiv.innerHTML = '<span class="ai-spinner"></span><span>Mawar sedang berfikir...</span>';
    body.appendChild(thinkingDiv);
    body.scrollTop = body.scrollHeight;

    fetch('chatbot_ajax.php', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(data => {
        thinkingDiv.className = "bg-white p-2 rounded shadow-sm mb-2 border-start border-danger border-4";
        thinkingDiv.innerHTML = data;
        body.scrollTop = body.scrollHeight;
    })
    .catch(() => {
        thinkingDiv.className = "bg-white p-2 rounded shadow-sm mb-2 border-start border-danger border-4";
        thinkingDiv.textContent = "Tidak dapat menghubungi chatbot. Sila semak sambungan anda.";
        body.scrollTop = body.scrollHeight;
    });
}

// SERVICE WORKER AUTO-UPDATE
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('service-worker.js?v=' + Date.now()).then(function(registration) {
            // Check for updates setiap 5 minit
            setInterval(function() {
                registration.update();
            }, 5 * 60 * 1000); // 5 minutes
        }).catch(function(err) {
            console.log('ServiceWorker registration failed: ', err);
        });
    });
    
    // Unregister old service workers untuk force clear cache
    navigator.serviceWorker.getRegistrations().then(registrations => {
        for(let registration of registrations) {
            registration.unregister();
        }
    });
}

// AUTO-REFRESH page jika ada PWA update
window.addEventListener('load', function() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.controller?.postMessage({type: 'SKIP_WAITING'});
    }
});
</script>
