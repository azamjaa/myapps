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
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyApps KEDA</title>
    
    <link rel="icon" type="image/png" href="image/keda.png?v=<?php echo time(); ?>">
    
    <!-- PWA -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#1e293b">
    <link rel="manifest" href="manifest.json">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-',
            corePlugins: { preflight: false }
        }
    </script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Force CSS Reload */
        body { background-color: #f3f4f6; overflow-x: hidden; }

        /* SIDEBAR STYLES */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        /* CONTENT AREA */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 260px);
        }

        /* NAV ITEMS */
        .nav-item {
            display: block; padding: 12px 20px;
            color: #cbd5e1; text-decoration: none;
            transition: all 0.3s; border-left: 4px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white; border-left-color: #3b82f6;
        }
        .nav-item i { width: 25px; margin-right: 10px; text-align: center; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; }
            .sidebar.active { transform: translateX(0); }
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
        #mawarWrapper { position: fixed; bottom: 10px; right: 30px; z-index: 9999; }
        .chat-mascot-container { width: 80px; height: 80px; cursor: pointer; transition: transform 0.3s; }
        .chat-mascot-container:hover { transform: scale(1.1); }
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
        .chat-box { position: fixed; bottom: 110px; right: 30px; width: 380px; height: 500px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 9998; display: none; flex-direction: column; }
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
        <div style="text-align: center;">
            <img src="<?php echo $profil_pic; ?>" 
                 class="rounded-circle border border-2 border-white mb-2" 
                 width="70" 
                 height="70" 
                 loading="lazy"
                 alt="Profile Picture"
                 style="object-fit: cover; 
                         image-rendering: crisp-edges;
                         -ms-interpolation-mode: nearest-neighbor;
                         image-rendering: pixelated;
                         display: inline-block;
                         vertical-align: middle;
                         max-width: 100%;
                         height: auto;">
        </div>
        <div class="text-white fw-bold small text-uppercase"><?php echo $_SESSION['nama']; ?></div>
        <span class="badge bg-primary mt-1"><?php echo strtoupper($_SESSION['role']); ?></span>
    </div>

    <!-- Menu -->
    <div class="py-2">
        <a href="dashboard_aplikasi.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='dashboard_aplikasi.php'?'active':''; ?>">
            <i class="fas fa-chart-line"></i> Dashboard Aplikasi
        </a>
        <a href="dashboard_staf.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='dashboard_staf.php'?'active':''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard Staf
        </a>
        <a href="direktori_aplikasi.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='direktori_aplikasi.php'?'active':''; ?>">
            <i class="fas fa-list"></i> Direktori Aplikasi
        </a>
        <a href="direktori_staf.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='direktori_staf.php'?'active':''; ?>">
            <i class="fas fa-users"></i> Direktori Staf
        </a>
        <a href="kalendar.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='kalendar.php'?'active':''; ?>">
            <i class="fas fa-calendar-alt"></i> Kalendar Hari Lahir
        </a>
        <a href="manual.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF'])=='manual.php'?'active':''; ?>">
            <i class="fas fa-book-open"></i> Manual Pengguna
        </a>
    </div>

    <!-- Actions -->
    <div class="p-3 mt-2">
        <a href="logout.php" class="btn btn-danger w-100 btn-sm">
            <i class="fas fa-sign-out-alt me-1"></i> Keluar
        </a>
    </div>
</nav>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
    <!-- Top Bar Mobile -->
    <div class="d-md-none bg-white p-3 mb-3 shadow-sm rounded d-flex justify-content-between align-items-center">
        <button class="btn btn-light" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h6 class="mb-0 fw-bold">MyApps KEDA</h6>
        <img src="image/keda.png" width="30">
    </div>

    <!-- Desktop Top Bar -->
    <div class="d-none d-md-flex bg-white p-3 mb-4 shadow-sm rounded justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-secondary">
            <?php 
                // Auto title based on filename
                $page = basename($_SERVER['PHP_SELF'], ".php");
                echo ucwords(str_replace("_", " ", $page));
            ?>
        </h5>
        <div class="d-flex gap-2">
            <!-- PWA Install Button -->
            <button id="pwa-install-btn" onclick="installPWA()" class="btn btn-success btn-sm rounded-pill hidden">
                <i class="fas fa-download me-1"></i> Install App
            </button>
            <a href="proses_staf.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                <i class="fas fa-user-edit me-1"></i> Profil
            </a>
            <a href="tukar_password.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fas fa-key me-1"></i> Password
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
        <div class="bg-white p-3 rounded shadow-sm mb-2 border-start border-danger border-4" style="max-width:85%;">
            <strong>Hai! Saya Mawar.</strong><br>
            Ada apa-apa saya boleh bantu? 😊
        </div>
    </div>
    <div class="p-3 bg-white border-top d-flex">
        <input type="text" id="chatInput" class="form-control rounded-pill me-2" placeholder="Taip soalan di sini..." onkeypress="handleEnter(event)">
        <button onclick="hantarMesej()" class="btn btn-danger rounded-circle" style="width: 40px; height: 40px;"><i class="fas fa-paper-plane"></i></button>
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

function toggleChat() {
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
    fetch('chatbot_ajax.php', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(data => {
        var botDiv = document.createElement('div');
        botDiv.className = "bg-white p-2 rounded shadow-sm mb-2 border-start border-danger border-4";
        botDiv.style.maxWidth = "80%";
        botDiv.innerHTML = data;
        body.appendChild(botDiv);
        body.scrollTop = body.scrollHeight;
    });
}
</script>
