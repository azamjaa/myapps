<?php
require 'db.php';
include 'header.php';

// Statistik Aplikasi
$cntAplikasi = $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1")->fetchColumn();

// Count by kategori
$cntDalaman = $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 1")->fetchColumn();
$cntLuaran = $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 2")->fetchColumn();
$cntGunasama = $db->query("SELECT COUNT(*) FROM aplikasi WHERE status = 1 AND id_kategori = 3")->fetchColumn();

// Data Chart - Aplikasi by Kategori
$chartKategori = $db->query("SELECT k.id_kategori, k.nama_kategori, COUNT(a.id_aplikasi) as total FROM aplikasi a JOIN kategori k ON a.id_kategori = k.id_kategori WHERE a.status = 1 GROUP BY a.id_kategori, k.nama_kategori ORDER BY a.id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-chart-line me-3 text-primary"></i>Dashboard Aplikasi</h3>

    <!-- Clickable Summary Statistics Cards as Tabs -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card active" data-bs-toggle="pill" data-bs-target="#semua" role="tab" style="border-left: 5px solid #10B981 !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Jumlah Aplikasi</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntAplikasi; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <i class="fas fa-th fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card" data-bs-toggle="pill" data-bs-target="#dalaman" role="tab" style="border-left: 5px solid #F59E0B !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Aplikasi Dalaman</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntDalaman; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <i class="fas fa-cube fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card" data-bs-toggle="pill" data-bs-target="#luaran" role="tab" style="border-left: 5px solid #EF4444 !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Aplikasi Luaran</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntLuaran; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);">
                        <i class="fas fa-globe fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 summary-card" data-bs-toggle="pill" data-bs-target="#gunasama" role="tab" style="border-left: 5px solid #4169E1 !important; cursor: pointer; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Aplikasi Gunasama</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntGunasama; ?></h2>
                    </div>
                    <div class="p-3 rounded-circle" style="background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%);">
                        <i class="fas fa-share-alt fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content Container -->
    <div class="tab-content" id="kategoriTabContent">
        
        <!-- TAB: SEMUA -->
        <div class="tab-pane fade show active" id="semua" role="tabpanel">
    
    <?php
    // Get all applications grouped by category
    $aplikasiDalaman = $db->query("SELECT * FROM aplikasi WHERE status = 1 AND id_kategori = 1 ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
    $aplikasiLuaran = $db->query("SELECT * FROM aplikasi WHERE status = 1 AND id_kategori = 2 ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
    $aplikasiGunasama = $db->query("SELECT * FROM aplikasi WHERE status = 1 AND id_kategori = 3 ORDER BY nama_aplikasi ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Icon mapping based on application name - UNIQUE & ATTRACTIVE for each app
    function getAppIcon($nama_aplikasi, $keterangan = '') {
        $nama_lower = strtolower($nama_aplikasi);
        $keterangan_lower = strtolower($keterangan);
        $combined = $nama_lower . ' ' . $keterangan_lower;
        
        // ============================================
        // EXACT APP NAME MAPPING (Priority 1) - UNIQUE ICONS
        // ============================================
        $exactMap = [
            // APLIKASI DALAMAN (From screenshot)
            'kedamap' => 'fa-map-marked-alt',
            'myapps' => 'fa-th-large',
            'mydaftar' => 'fa-file-alt',
            'mygovuc' => 'fa-envelope',
            'mypprs' => 'fa-project-diagram',
            'saga' => 'fa-wallet',
            'smp' => 'fa-graduation-cap',
            'staff portal' => 'fa-users',
            'tms' => 'fa-money-bill-wave',
            
            // APLIKASI LUARAN (From screenshot)
            'daftarkolej' => 'fa-university',
            'ejawatan' => 'fa-briefcase',
            'kedapay' => 'fa-credit-card',
            'mypremis' => 'fa-building',
            'portal keda' => 'fa-door-open',
            'portal kolej keda' => 'fa-school',
            'spbk' => 'fa-file-signature',
            
            // APLIKASI GUNASAMA (From screenshot)
            'ddms' => 'fa-database',
            'ecos' => 'fa-leaf',
            'ekasih' => 'fa-laptop',
            'mymesyuarat' => 'fa-video',
            'myprojek' => 'fa-tasks',
            'myspike' => 'fa-phone',
            'sispaa' => 'fa-clipboard-check',
            'spkpn' => 'fa-chart-pie',
            
            // OTHER COMMON APPS
            'emas care' => 'fa-heartbeat',
            'emas' => 'fa-hospital-user',
            'epelawat' => 'fa-address-card',
            'pelawat' => 'fa-user-friends',
            'eperjalanan' => 'fa-route',
            'perjalanan' => 'fa-plane-departure',
            'etanah' => 'fa-globe-asia',
            'tanah' => 'fa-map',
            'eutiliti' => 'fa-plug',
            'utiliti' => 'fa-bolt',
            'fer' => 'fa-file-invoice',
            'gasset' => 'fa-cubes',
            'gfixed' => 'fa-couch',
            'gintan' => 'fa-box-open',
            'glive' => 'fa-broadcast-tower',
            'gstore' => 'fa-store',
            'hrmis' => 'fa-users-cog',
            'ptt' => 'fa-user-clock',
            'eptt' => 'fa-business-time',
            
            // EDUCATION & TRAINING
            'e-spkb' => 'fa-certificate',
            'spkb' => 'fa-graduation-cap',
            'latihan' => 'fa-chalkboard-teacher',
            'training' => 'fa-user-graduate',
            'kursus' => 'fa-book-open',
            'lms' => 'fa-book-reader',
            
            // OFFICE & DOCUMENTS
            'e-office' => 'fa-briefcase',
            'office' => 'fa-building',
            'dokumen' => 'fa-file-pdf',
            'document' => 'fa-folder-open',
            'surat' => 'fa-envelope-open-text',
            'mail' => 'fa-mail-bulk',
            'tandatangan' => 'fa-signature',
            'signature' => 'fa-pen-fancy',
            
            // PROCUREMENT & PURCHASING
            'e-perolehan' => 'fa-cart-plus',
            'perolehan' => 'fa-shopping-cart',
            'procurement' => 'fa-shopping-basket',
            'tender' => 'fa-gavel',
            'vendor' => 'fa-handshake',
            'kontrak' => 'fa-file-contract',
            'contract' => 'fa-file-signature',
            
            // LEAVE & ATTENDANCE
            'e-cuti' => 'fa-umbrella-beach',
            'cuti' => 'fa-calendar-minus',
            'leave' => 'fa-calendar-alt',
            'kehadiran' => 'fa-user-check',
            'attendance' => 'fa-fingerprint',
            'punch' => 'fa-clock',
            
            // MEETING & COMMUNICATION
            'e-mesyuarat' => 'fa-video',
            'mesyuarat' => 'fa-users',
            'meeting' => 'fa-handshake',
            'conference' => 'fa-phone-volume',
            
            // COMPLAINT & FEEDBACK
            'e-aduan' => 'fa-exclamation-circle',
            'aduan' => 'fa-bullhorn',
            'complaint' => 'fa-comment-dots',
            'feedback' => 'fa-comments',
            'helpdesk' => 'fa-headset',
            'support' => 'fa-life-ring',
            'ticketing' => 'fa-ticket-alt',
            
            // ASSESSMENT & EVALUATION
            'pentaksiran' => 'fa-poll',
            'assessment' => 'fa-tasks',
            'evaluation' => 'fa-star',
            'penilaian' => 'fa-chart-bar',
            
            // VEHICLE & TRANSPORT
            'kenderaan' => 'fa-car-side',
            'vehicle' => 'fa-truck-moving',
            'transport' => 'fa-shuttle-van',
            'parking' => 'fa-parking',
            
            // STORE & WAREHOUSE
            'stor' => 'fa-warehouse',
            'store' => 'fa-store-alt',
            'warehouse' => 'fa-pallet',
            'stock' => 'fa-boxes',
            
            // PORTAL & GENERAL
            'portal' => 'fa-th-large',
            'dashboard' => 'fa-tachometer-alt',
            'cms' => 'fa-edit',
            'website' => 'fa-globe',
            
            // PAYROLL & SALARY
            'payroll' => 'fa-money-check-alt',
            'gaji' => 'fa-money-bill-wave',
            'salary' => 'fa-dollar-sign',
            'wage' => 'fa-hand-holding-usd',
        ];
        
        // Check exact match first (app name only)
        if (isset($exactMap[$nama_lower])) {
            return $exactMap[$nama_lower];
        }
        
        // ============================================
        // KEYWORD MATCHING (Priority 2) - checks both name AND description
        // ============================================
        $keywordMap = [
            // SPECIFIC APP KEYWORDS
            'geospatial' => 'fa-map-marked-alt',
            'aplikasi-aplikasi' => 'fa-th-large',
            'pendaftaran' => 'fa-file-signature',
            'program acara' => 'fa-calendar-alt',
            'google work' => 'fa-envelope',
            'perumahan rakyat' => 'fa-home',
            'projek perumahan' => 'fa-building',
            'kewangan' => 'fa-chart-line',
            'maklumat pelajar' => 'fa-user-graduate',
            'kolej keda' => 'fa-university',
            'slip gaji' => 'fa-money-check-alt',
            'ec form' => 'fa-file-alt',
            'sewaan' => 'fa-key',
            'masuk kolej' => 'fa-door-open',
            'laman web' => 'fa-globe',
            'pasini kolej' => 'fa-school',
            'penyewaan' => 'fa-credit-card',
            'bayaran' => 'fa-wallet',
            'premis' => 'fa-store-alt',
            'tanah' => 'fa-map',
            'permohonan bantuan' => 'fa-hands-helping',
            'dokumen digital' => 'fa-file-pdf',
            'sumber tenaga' => 'fa-solar-panel',
            'kemasukan' => 'fa-laptop-house',
            'icu' => 'fa-procedures',
            'mesyuarat' => 'fa-handshake',
            'udn' => 'fa-network-wired',
            'projek penyelidikan' => 'fa-flask',
            'integrasi kementerian' => 'fa-sitemap',
            'aduan awam' => 'fa-clipboard-check',
            'kampung peringkat' => 'fa-chart-pie',
            'profil kampung' => 'fa-chart-bar',
            
            // MEDICAL & HEALTHCARE
            'klinik' => 'fa-clinic-medical',
            'clinic' => 'fa-hospital-user',
            'kesihatan' => 'fa-notes-medical',
            'medical' => 'fa-stethoscope',
            'hospital' => 'fa-hospital',
            'health' => 'fa-heart',
            'patient' => 'fa-procedures',
            'rawatan' => 'fa-briefcase-medical',
            
            // VISITOR & SECURITY
            'daftar masuk' => 'fa-door-open',
            'gate' => 'fa-door-closed',
            'security' => 'fa-shield-alt',
            'keselamatan' => 'fa-user-shield',
            'access' => 'fa-key',
            
            // LAND, SURVEY & PROPERTY
            'ukur' => 'fa-ruler-combined',
            'survey' => 'fa-map',
            'geospatial' => 'fa-map-marked',
            'gis' => 'fa-globe-americas',
            'mapping' => 'fa-search-location',
            
            // UTILITIES & BILLING
            'bil' => 'fa-receipt',
            'bill' => 'fa-file-invoice-dollar',
            'elektrik' => 'fa-bolt',
            'electric' => 'fa-charging-station',
            'air' => 'fa-tint',
            'water' => 'fa-faucet',
            
            // FINANCE & MONEY
            'vot' => 'fa-coins',
            'vote' => 'fa-balance-scale',
            'kaunter' => 'fa-cash-register',
            'counter' => 'fa-calculator',
            'kutipan' => 'fa-hand-holding-usd',
            'collection' => 'fa-donate',
            'cukai' => 'fa-percentage',
            'tax' => 'fa-file-invoice',
            'bayaran' => 'fa-credit-card',
            'payment' => 'fa-money-bill-alt',
            'resit' => 'fa-receipt',
            'receipt' => 'fa-file-invoice',
            
            // ASSET & EQUIPMENT
            'peralatan' => 'fa-toolbox',
            'equipment' => 'fa-tools',
            'alih' => 'fa-dolly',
            'movable' => 'fa-box-open',
            'tak alih' => 'fa-couch',
            'fixed' => 'fa-home',
            'furnitur' => 'fa-chair',
            'furniture' => 'fa-couch',
            'komputer' => 'fa-laptop',
            'computer' => 'fa-desktop',
            
            // STORE & INVENTORY
            'bekalan' => 'fa-truck-loading',
            'supply' => 'fa-boxes',
            'inventori' => 'fa-clipboard-list',
            'stok' => 'fa-box-open',
            'stock' => 'fa-archive',
            'gudang' => 'fa-warehouse',
            
            // HR & EMPLOYEE
            'penjawat' => 'fa-user-tie',
            'pegawai' => 'fa-id-card',
            'officer' => 'fa-user-shield',
            'pekerja' => 'fa-hard-hat',
            'employee' => 'fa-users',
            'rekod perkhidmatan' => 'fa-file-alt',
            'service record' => 'fa-history',
            
            // PAYROLL & BENEFIT
            'emolumen' => 'fa-money-bill-wave',
            'allowance' => 'fa-hand-holding-usd',
            'elaun' => 'fa-donate',
            'caruman' => 'fa-piggy-bank',
            'contribution' => 'fa-hands-helping',
            'kwsp' => 'fa-university',
            'epf' => 'fa-landmark',
            
            // TIME & ATTENDANCE
            'waktu' => 'fa-clock',
            'time' => 'fa-stopwatch',
            'jam' => 'fa-business-time',
            'hours' => 'fa-hourglass-half',
            'scan' => 'fa-fingerprint',
            'punch card' => 'fa-id-card',
            
            // TRAINING & DEVELOPMENT
            'pembangunan' => 'fa-chart-line',
            'development' => 'fa-seedling',
            'kemahiran' => 'fa-user-graduate',
            'skill' => 'fa-brain',
            'sijil' => 'fa-certificate',
            'certificate' => 'fa-award',
            
            // PERFORMANCE & EVALUATION
            'prestasi' => 'fa-trophy',
            'performance' => 'fa-chart-bar',
            'kpi' => 'fa-bullseye',
            'target' => 'fa-crosshairs',
            'skor' => 'fa-star',
            'score' => 'fa-percentage',
            
            // DOCUMENT & RECORDS
            'fail' => 'fa-folder',
            'file' => 'fa-file-alt',
            'rekod' => 'fa-book',
            'record' => 'fa-database',
            'arkib' => 'fa-archive',
            'archive' => 'fa-box-open',
            'emel' => 'fa-envelope',
            
            // MEETING & BOOKING
            'bilik' => 'fa-door-open',
            'room' => 'fa-building',
            'tempahan' => 'fa-calendar-check',
            'booking' => 'fa-calendar-plus',
            'dewan' => 'fa-landmark',
            'hall' => 'fa-home',
            
            // COMPLAINT & SERVICE
            'khidmat pelanggan' => 'fa-concierge-bell',
            'customer service' => 'fa-user-circle',
            'maklumbalas' => 'fa-comment-alt',
            'tiket' => 'fa-ticket-alt',
            'ticket' => 'fa-tags',
            
            // PROJECT & TASK
            'projek' => 'fa-project-diagram',
            'project' => 'fa-tasks',
            'tugasan' => 'fa-clipboard-list',
            'task' => 'fa-list-check',
            'workflow' => 'fa-sitemap',
            
            // VEHICLE & LOGISTICS
            'pemandu' => 'fa-id-card',
            'driver' => 'fa-user-tie',
            'logistik' => 'fa-truck',
            'logistics' => 'fa-shipping-fast',
            'penghantaran' => 'fa-dolly-flatbed',
            'delivery' => 'fa-truck-moving',
            
            // COMMUNICATION & BROADCAST
            'siaran' => 'fa-rss',
            'broadcast' => 'fa-broadcast-tower',
            'streaming' => 'fa-video',
            'live' => 'fa-signal',
            'berita' => 'fa-newspaper',
            'news' => 'fa-rss-square',
            
            // GENERAL SYSTEM
            'sistem' => 'fa-cogs',
            'system' => 'fa-server',
            'aplikasi' => 'fa-window-maximize',
            'application' => 'fa-desktop',
            'platform' => 'fa-layer-group',
            'modul' => 'fa-puzzle-piece',
            'module' => 'fa-th',
        ];
        
        // Check combined (name + description) for keyword matches
        foreach ($keywordMap as $keyword => $icon) {
            if (strpos($combined, $keyword) !== false) {
                return $icon;
            }
        }
        
        // Default icon for unknown applications
        return 'fa-desktop';
    }
    
    // Function to get unique vibrant color for each app (BRIGHT COLORS like chart)
    // Get application category color based on id_kategori
    function getAppColor($nama_aplikasi, $id_kategori = null) {
        // Gradient colors by CATEGORY (Option 1: Clean & Professional)
        // id_kategori: 1 = Dalaman (Yellow), 2 = Luaran (Red), 3 = Gunasama (Blue)
        
        $categoryColors = [
            1 => ['#F59E0B', '#D97706'], // Aplikasi Dalaman - Amber/Yellow Gradient
            2 => ['#EF4444', '#DC2626'], // Aplikasi Luaran - Red Gradient
            3 => ['#4169E1', '#1E40AF'], // Aplikasi Gunasama - Blue Gradient
        ];
        
        // Return category color, or default to blue if not specified
        return $categoryColors[$id_kategori] ?? ['#4169E1', '#1E40AF'];
    }
    ?>

    <!-- APLIKASI DALAMAN -->
    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-dark">
            <i class="fas fa-cube me-2" style="color: #FFD700;"></i>Aplikasi Dalaman
        </h4>
        <div class="row g-2">
            <?php if (empty($aplikasiDalaman)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tiada aplikasi dalaman buat masa ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($aplikasiDalaman as $app): 
                    $appColors = getAppColor($app['nama_aplikasi'], 1);
                ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%); flex-shrink: 0;">
                                            <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
            </div>
        </div>
                                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                        <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                    </p>
                                    <?php if ($app['sso_comply'] == 1): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                    <?php endif; ?>
                </div>
            </div>
                        </a>
        </div>
                <?php endforeach; ?>
            <?php endif; ?>
    </div>
</div>

    <!-- APLIKASI LUARAN -->
    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-dark">
            <i class="fas fa-globe me-2" style="color: #FF4444;"></i>Aplikasi Luaran
        </h4>
        <div class="row g-2">
            <?php if (empty($aplikasiLuaran)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tiada aplikasi luaran buat masa ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($aplikasiLuaran as $app): 
                    $appColors = getAppColor($app['nama_aplikasi'], 2);
                ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%); flex-shrink: 0;">
                                            <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                        <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                    </p>
                                    <?php if ($app['sso_comply'] == 1): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
            </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- APLIKASI GUNASAMA -->
    <div class="mb-5">
        <h4 class="mb-3 fw-bold text-dark">
            <i class="fas fa-share-alt me-2" style="color: #4169E1;"></i>Aplikasi Gunasama
        </h4>
        <div class="row g-2">
            <?php if (empty($aplikasiGunasama)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Tiada aplikasi gunasama buat masa ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($aplikasiGunasama as $app): 
                    $appColors = getAppColor($app['nama_aplikasi'], 3);
                ?>
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%); flex-shrink: 0;">
                                            <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                        <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                    </p>
                                    <?php if ($app['sso_comply'] == 1): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
            </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
        </div><!-- End TAB: SEMUA -->
        
        <!-- TAB: DALAMAN ONLY -->
        <div class="tab-pane fade" id="dalaman" role="tabpanel">
            <div class="mb-5">
                <div class="row g-2">
                    <?php if (empty($aplikasiDalaman)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Tiada aplikasi dalaman buat masa ini.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($aplikasiDalaman as $app): 
                            $appColors = getAppColor($app['nama_aplikasi'], 1);
                        ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%);">
                                                    <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                                <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                            </p>
                                            <?php if ($app['sso_comply'] == 1): ?>
                                                <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End TAB: DALAMAN -->
        
        <!-- TAB: LUARAN ONLY -->
        <div class="tab-pane fade" id="luaran" role="tabpanel">
            <div class="mb-5">
                <div class="row g-2">
                    <?php if (empty($aplikasiLuaran)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Tiada aplikasi luaran buat masa ini.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($aplikasiLuaran as $app): 
                            $appColors = getAppColor($app['nama_aplikasi'], 2);
                        ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%);">
                                                    <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                                <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                            </p>
                                            <?php if ($app['sso_comply'] == 1): ?>
                                                <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
    </div>
</div>
        </div><!-- End TAB: LUARAN -->
        
        <!-- TAB: GUNASAMA ONLY -->
        <div class="tab-pane fade" id="gunasama" role="tabpanel">
            <div class="mb-5">
                <div class="row g-2">
                    <?php if (empty($aplikasiGunasama)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Tiada aplikasi gunasama buat masa ini.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($aplikasiGunasama as $app): 
                            $appColors = getAppColor($app['nama_aplikasi'], 3);
                        ?>
                            <div class="col-md-2 col-sm-4 col-6">
                                <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 app-card" style="border-left: 3px solid <?php echo $appColors[0]; ?> !important; transition: all 0.3s ease;">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $appColors[0]; ?> 0%, <?php echo $appColors[1]; ?> 100%);">
                                                    <i class="fas <?php echo getAppIcon($app['nama_aplikasi'], $app['keterangan'] ?? ''); ?> fa-lg text-white"></i>
                                                </div>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['nama_aplikasi']); ?></h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($app['keterangan'] ?? 'Aplikasi KEDA', 0, 40)); ?>
                                                <?php echo strlen($app['keterangan'] ?? '') > 40 ? '...' : ''; ?>
                                            </p>
                                            <?php if ($app['sso_comply'] == 1): ?>
                                                <span class="badge bg-success mt-1" style="font-size: 0.65rem;"><i class="fas fa-shield-alt me-1"></i>SSO</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End TAB: GUNASAMA -->
        
    </div><!-- End Tab Content -->
</div><!-- End Container -->

<style>
/* Summary Card as Clickable Tabs */
.summary-card {
    transition: all 0.3s ease;
    position: relative;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}

.summary-card.active {
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
    transform: scale(1.02);
}

.summary-card.active::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-top: 10px solid #fff;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.app-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.app-card .card-body:hover i {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}
</style>

<script>
// Handle active state for summary cards and tab switching
document.addEventListener('DOMContentLoaded', function() {
    const summaryCards = document.querySelectorAll('.summary-card');
    
    summaryCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            summaryCards.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Get the target tab
            const target = this.getAttribute('data-bs-target');
            
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Show the target tab pane
            const targetPane = document.querySelector(target);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
});
</script>

</body>
</html>

<?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'view_dashboard')): ?>
<!-- Paparan dashboard aplikasi di sini -->
<?php endif; ?>
