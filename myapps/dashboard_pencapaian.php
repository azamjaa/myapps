<?php
require 'db.php';
require 'src/rbac_helper.php';
require_once __DIR__ . '/spatial_processor_engine.php';

// Check if PhpSpreadsheet is available for Excel processing
$phpspreadsheetAvailable = false;
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    $phpspreadsheetAvailable = true;
} else {
    // Try to load from vendor directory
    // First try safe autoload wrapper (bypasses platform check)
    $safeAutoloadPath = __DIR__ . '/vendor/autoload_safe.php';
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    
    if (file_exists($safeAutoloadPath)) {
        try {
            require_once $safeAutoloadPath;
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $phpspreadsheetAvailable = true;
            }
        } catch (Exception $e) {
            error_log("PhpSpreadsheet safe autoload error: " . $e->getMessage());
        } catch (Error $e) {
            error_log("PhpSpreadsheet safe autoload fatal error: " . $e->getMessage());
        }
    } elseif (file_exists($autoloadPath)) {
        try {
            // Bypass platform check by defining constant before require
            if (!defined('COMPOSER_PLATFORM_CHECK')) {
                define('COMPOSER_PLATFORM_CHECK', false);
            }
            
            // Suppress platform check errors temporarily
            $oldErrorReporting = error_reporting();
            error_reporting($oldErrorReporting & ~E_WARNING & ~E_ERROR);
            
            // Try to catch RuntimeException from platform check
            try {
                require_once $autoloadPath;
            } catch (RuntimeException $e) {
                // Platform check failed - try to continue anyway
                error_log("Platform check bypassed: " . $e->getMessage());
                // Try to load autoload_real directly
                $autoloadRealPath = __DIR__ . '/vendor/composer/autoload_real.php';
                if (file_exists($autoloadRealPath)) {
                    require_once $autoloadRealPath;
                    ComposerAutoloaderInit968e1219b2b6bd1d8dffe00fe3275a77::getLoader();
                }
            }
            
            // Restore error reporting
            error_reporting($oldErrorReporting);
            
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $phpspreadsheetAvailable = true;
            }
        } catch (Exception $e) {
            // Silently fail - PhpSpreadsheet will not be available
            error_log("PhpSpreadsheet autoload error: " . $e->getMessage());
        } catch (Error $e) {
            // Silently fail - PhpSpreadsheet will not be available
            error_log("PhpSpreadsheet autoload fatal error: " . $e->getMessage());
        }
    }
}

include 'header.php';

// Check if user is admin
$current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);
$isAdmin = false;
if ($current_user) {
    $isAdmin = isSuperAdmin($db, $current_user);
}

// Upload functionality has been moved to pengurusan_rekod_dashboard.php

// Keyword Mapping untuk nama profesional berdasarkan kategori
// Mapping ini lebih spesifik untuk mengelakkan duplikasi
function getProfessionalNameFromKategori($kategori) {
    $kategoriLower = strtolower($kategori);
    
    // Check exact matches first (most specific)
    $exactMapping = [
        'keda - bangunan kediaman' => 'Desa KEDA',
        'keda - bantuan kolej keda' => 'Bantuan Kolej KEDA',
        'keda - bantuan bahagian bpk' => 'Bantuan Komuniti',
        'keda - bantuan bahagian bnt' => 'Bantuan Pertanian',
        'keda - bantuan bahagian bpu' => 'Bantuan Usahawan',
        'keda - jalan perhubungan desa' => 'Jalan Perhubungan Desa',
        'keda - industri kecil sederhana' => 'Perniagaan & IKS',
        'keda - lot telah diserahmilik' => 'Lot Telah Diserah Milik',
        'keda - gunatanah' => 'Rekod Guna Tanah',
        'keda - sewaan tanah-tanah keda' => 'Sewaan Tanah KEDA',
        // Also check with original case
        'KEDA - Bangunan Kediaman' => 'Desa KEDA',
        'KEDA - Bantuan Kolej KEDA' => 'Bantuan Kolej KEDA',
        'KEDA - Bantuan Bahagian BPK' => 'Bantuan Komuniti',
        'KEDA - Bantuan Bahagian BNT' => 'Bantuan Pertanian',
        'KEDA - Bantuan Bahagian BPU' => 'Bantuan Usahawan',
        'KEDA - Jalan Perhubungan Desa' => 'Jalan Perhubungan Desa',
        'KEDA - Industri Kecil Sederhana' => 'Perniagaan & IKS',
        'KEDA - Lot Telah Diserahmilik' => 'Lot Telah Diserah Milik',
        'KEDA - Gunatanah' => 'Rekod Guna Tanah',
        'KEDA - Sewaan Tanah-Tanah KEDA' => 'Sewaan Tanah KEDA'
    ];
    
    // Check exact match first
    if (isset($exactMapping[$kategoriLower])) {
        return $exactMapping[$kategoriLower];
    }
    
    // Then check partial matches (less specific)
    $partialMapping = [
        'kediaman' => 'Perumahan KEDA',
        'kolej' => 'Bantuan Kolej KEDA',
        'jalan' => 'Jalan Perhubungan Desa',
        'perhubungan' => 'Jalan Perhubungan Desa',
        'iks' => 'Perniagaan & IKS',
        'perniagaan' => 'Perniagaan & IKS',
        'industri_kecil_sederhana' => 'Perniagaan & IKS',
        'industri kecil sederhana' => 'Perniagaan & IKS',
        'industri kecil' => 'Perniagaan & IKS',
        'kecil sederhana' => 'Perniagaan & IKS',
        'bpu' => 'Bantuan Usahawan',
        'bpk' => 'Bantuan Komuniti',
        'komuniti' => 'Bantuan Komuniti',
        'bnt' => 'Bantuan Pertanian',
        'pertanian' => 'Bantuan Pertanian',
        'gunatanah' => 'Rekod Guna Tanah',
        'guna_tanah' => 'Rekod Guna Tanah',
        'sewaan' => 'Sewaan Tanah KEDA',
        'diserah' => 'Lot Telah Diserah Milik',
        'telah_diserah' => 'Lot Telah Diserah Milik',
        'usahawan' => 'Bantuan Usahawan'
    ];
    
    foreach ($partialMapping as $key => $value) {
        if (stripos($kategoriLower, $key) !== false) {
            return $value;
        }
    }
    
    // Default: format kategori name
    return ucwords(str_replace(['_', '-'], ' ', $kategori));
}

// Get all kategori from database ONLY (exclude sempadan files)
$excludedFiles = ['negeri', 'daerah', 'parlimen', 'dun'];
$cardsData = [];

try {
    // Get all kategori from database
    $stmt = $db->query("SELECT DISTINCT kategori FROM geojson_data WHERE kategori NOT IN ('" . implode("','", $excludedFiles) . "') ORDER BY kategori");
    $kategoriList = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($kategoriList as $kategori) {
        $professionalName = getProfessionalNameFromKategori($kategori);
        
        // Get count from database
        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM geojson_data WHERE kategori = ?");
        $countStmt->execute([$kategori]);
        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $count = intval($countRow['total']);
        
        // Get last updated date for this kategori
        // Check if table has updated_at or created_at field
        $lastUpdated = null;
        try {
            // Try to get MAX(updated_at) first
            $dateStmt = $db->prepare("SELECT MAX(updated_at) as last_updated FROM geojson_data WHERE kategori = ?");
            $dateStmt->execute([$kategori]);
            $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
            if ($dateRow && $dateRow['last_updated']) {
                $lastUpdated = $dateRow['last_updated'];
            } else {
                // Fallback to created_at if updated_at doesn't exist
                $dateStmt = $db->prepare("SELECT MAX(created_at) as last_updated FROM geojson_data WHERE kategori = ?");
                $dateStmt->execute([$kategori]);
                $dateRow = $dateStmt->fetch(PDO::FETCH_ASSOC);
                if ($dateRow && $dateRow['last_updated']) {
                    $lastUpdated = $dateRow['last_updated'];
                }
            }
        } catch (Exception $e) {
            // If columns don't exist, ignore error
            $lastUpdated = null;
        }
        
        // Get all properties to calculate stats
        $totalKos = 0;
        $daerahList = [];
        $propsStmt = $db->prepare("SELECT properties FROM geojson_data WHERE kategori = ?");
        $propsStmt->execute([$kategori]);
        
        while ($propRow = $propsStmt->fetch(PDO::FETCH_ASSOC)) {
            $props = json_decode($propRow['properties'], true);
            if (!$props) continue;
            
            // Calculate KOS
            if (isset($props['KOS'])) {
                $kos = str_replace([',', 'RM', ' '], '', $props['KOS']);
                $totalKos += floatval($kos);
            } elseif (isset($props['Kadar_Sewaan'])) {
                $kos = str_replace([',', 'RM', ' '], '', $props['Kadar_Sewaan']);
                $totalKos += floatval($kos);
            }
            
            // Collect DAERAH
            if (!empty($props['DAERAH'])) {
                $daerahList[$props['DAERAH']] = ($daerahList[$props['DAERAH']] ?? 0) + 1;
            }
        }
        
        $cardsData[] = [
            'filename' => $kategori . '.geojson',
            'kategori' => $kategori,
            'name' => $professionalName,
            'count' => $count,
            'totalKos' => $totalKos,
            'daerahList' => $daerahList,
            'filepath' => 'api_get_geojson_by_kategori.php?kategori=' . urlencode($kategori),
            'lastUpdated' => $lastUpdated
        ];
    }
} catch (Exception $e) {
    error_log("Error loading kategori from database: " . $e->getMessage());
}

// Remove duplicates based on professional name - keep first occurrence
$uniqueCards = [];
$seenNames = [];
foreach ($cardsData as $card) {
    $nameKey = strtolower($card['name']);
    if (!isset($seenNames[$nameKey])) {
        $seenNames[$nameKey] = true;
        $uniqueCards[] = $card;
    }
}
$cardsData = $uniqueCards;

// Limit to 10 cards
$cardsData = array_slice($cardsData, 0, 10);

// Find default card (Profil Perumahan KEDA) untuk auto-load
$defaultCard = null;
foreach ($cardsData as $card) {
    if (stripos($card['name'], 'Profil Perumahan') !== false || 
        stripos($card['kategori'], 'kediaman') !== false ||
        stripos($card['kategori'], 'bangunan_kediaman') !== false) {
        $defaultCard = $card;
        break;
    }
}
// If not found, use first card as default
if (!$defaultCard && count($cardsData) > 0) {
    $defaultCard = $cardsData[0];
}

// Color scheme - 10 warna unik yang berbeza untuk setiap kad
$colorList = [
    ['#10B981', '#059669', '#f0fdf4', 'fa-home'],                    // Hijau - Kediaman
    ['#F59E0B', '#D97706', '#fffbeb', 'fa-store'],                   // Kuning - IKS/Usahawan
    ['#EF4444', '#DC2626', '#fef2f2', 'fa-graduation-cap'],          // Merah - Kolej
    ['#4169E1', '#1E40AF', '#eff6ff', 'fa-hands-helping'],            // Biru - Bantuan Kampung
    ['#7c3aed', '#6366f1', '#e0e7ff', 'fa-paw'],                     // Ungu - Ternakan
    ['#06B6D4', '#0891B2', '#e0f2fe', 'fa-road'],                    // Cyan - Jalan
    ['#EA3680', '#EA3680', '#fdf2f8', 'fa-store'],                   // Pink - IKS
    ['#16A085', '#138D75', '#e8f8f5', 'fa-landmark'],                // Teal - Aset & Tanah
    ['#E67E22', '#D35400', '#fef5e7', 'fa-file-contract'],           // Orange - Sewaan
    ['#95A5A6', '#7F8C8D', '#f8f9fa', 'fa-hand-holding']            // Grey - Diserah
];

// Map kategori ke warna dan ikon yang sesuai
function getColorIcon($idx, $kategori) {
    global $colorList;
    
    // Map kategori ke warna dan ikon yang sesuai - setiap kategori dapat warna unik
    $categoryMap = [
        // Perumahan KEDA
        'KEDIAMAN' => ['#10B981', '#059669', '#f0fdf4', 'fa-home'],
        'BANGUNAN.*KEDIAMAN' => ['#10B981', '#059669', '#f0fdf4', 'fa-home'],
        
        // Bantuan Kolej KEDA - Purple (susunan ke-5)
        'KOLEJ' => ['#7c3aed', '#6366f1', '#e0e7ff', 'fa-graduation-cap'],
        'PENDIDIKAN' => ['#7c3aed', '#6366f1', '#e0e7ff', 'fa-graduation-cap'],
        'BANTUAN.*KOLEJ' => ['#7c3aed', '#6366f1', '#e0e7ff', 'fa-graduation-cap'],
        
        // Bantuan Komuniti - Merah (susunan ke-3)
        'KAMPUNG' => ['#EF4444', '#DC2626', '#fef2f2', 'fa-hands-helping'],
        'KOMUNITI' => ['#EF4444', '#DC2626', '#fef2f2', 'fa-hands-helping'],
        'BPK' => ['#EF4444', '#DC2626', '#fef2f2', 'fa-hands-helping'],
        'BANTUAN.*BAHAGIAN.*BPK' => ['#EF4444', '#DC2626', '#fef2f2', 'fa-hands-helping'],
        
        // Bantuan Pertanian - Kuning (susunan ke-2)
        'PERTANIAN' => ['#F59E0B', '#D97706', '#fffbeb', 'fa-seedling'],
        'BNT' => ['#F59E0B', '#D97706', '#fffbeb', 'fa-seedling'],
        'BANTUAN.*BAHAGIAN.*BNT' => ['#F59E0B', '#D97706', '#fffbeb', 'fa-seedling'],
        'TERNAKAN' => ['#7c3aed', '#6366f1', '#e0e7ff', 'fa-paw'],
        
        // Bantuan Usahawan - Biru (susunan ke-4)
        'USAHAWAN' => ['#4169E1', '#1E40AF', '#eff6ff', 'fa-store'],
        'BPU' => ['#4169E1', '#1E40AF', '#eff6ff', 'fa-store'],
        'BANTUAN.*BAHAGIAN.*BPU' => ['#4169E1', '#1E40AF', '#eff6ff', 'fa-store'],
        
        // Jalan Perhubungan Desa
        'JALAN' => ['#06B6D4', '#0891B2', '#e0f2fe', 'fa-road'],
        'PERHUBUNGAN' => ['#06B6D4', '#0891B2', '#e0f2fe', 'fa-road'],
        'JALAN.*PERHUBUNGAN' => ['#06B6D4', '#0891B2', '#e0f2fe', 'fa-road'],
        
        // Perniagaan & IKS
        'IKS' => ['#EA3680', '#EA3680', '#fdf2f8', 'fa-store'],
        'PERNIAGaan' => ['#EA3680', '#EA3680', '#fdf2f8', 'fa-store'],
        'INDUSTRI.*KECIL.*SEDERHANA' => ['#EA3680', '#EA3680', '#fdf2f8', 'fa-store'],
        
        // Rekod Guna Tanah - Purple
        'GUNA.*TANAH' => ['#9333EA', '#7C3AED', '#faf5ff', 'fa-landmark'],
        'GUNATANAH' => ['#9333EA', '#7C3AED', '#faf5ff', 'fa-landmark'],
        'ASET' => ['#16A085', '#138D75', '#e8f8f5', 'fa-landmark'],
        
        // Sewaan Tanah KEDA
        'SEWAAN' => ['#E67E22', '#D35400', '#fef5e7', 'fa-key'],
        'SEWA' => ['#E67E22', '#D35400', '#fef5e7', 'fa-key'],
        'SEWAAN.*TANAH' => ['#E67E22', '#D35400', '#fef5e7', 'fa-key'],
        
        // Lot Telah Diserah Milik
        'DISERAH' => ['#95A5A6', '#7F8C8D', '#f8f9fa', 'fa-hand-holding'],
        'LOT.*DISERAH' => ['#95A5A6', '#7F8C8D', '#f8f9fa', 'fa-hand-holding'],
        'TELAH.*DISERAH' => ['#95A5A6', '#7F8C8D', '#f8f9fa', 'fa-hand-holding']
    ];
    
    // Check setiap pattern dalam kategori
    $kategoriUpper = strtoupper($kategori);
    foreach ($categoryMap as $pattern => $colorIcon) {
        if (preg_match('/' . $pattern . '/i', $kategoriUpper)) {
            return $colorIcon;
        }
    }
    
    // Fallback: guna index untuk pastikan setiap kad dapat warna berbeza (tidak repeat)
    // Pastikan setiap kad dapat warna yang unik berdasarkan index
    return $colorList[$idx % count($colorList)];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-map-marked-alt me-3 text-primary"></i>Dashboard Pencapaian</h3>
        <?php
        // Get the most recent update date from all categories
        $overallLastUpdated = null;
        try {
            // Try to get MAX(updated_at) from all records
            $overallDateStmt = $db->query("SELECT MAX(updated_at) as last_updated FROM geojson_data");
            $overallDateRow = $overallDateStmt->fetch(PDO::FETCH_ASSOC);
            if ($overallDateRow && $overallDateRow['last_updated']) {
                $overallLastUpdated = $overallDateRow['last_updated'];
            } else {
                // Fallback to created_at
                $overallDateStmt = $db->query("SELECT MAX(created_at) as last_updated FROM geojson_data");
                $overallDateRow = $overallDateStmt->fetch(PDO::FETCH_ASSOC);
                if ($overallDateRow && $overallDateRow['last_updated']) {
                    $overallLastUpdated = $overallDateRow['last_updated'];
                }
            }
        } catch (Exception $e) {
            // If columns don't exist, use current date as fallback
            $overallLastUpdated = date('Y-m-d H:i:s');
        }
        
        if ($overallLastUpdated) {
            $formattedDate = date('d/m/Y H:i', strtotime($overallLastUpdated));
            echo '<small class="text-muted"><i class="fas fa-clock me-1"></i>Rekod dikemaskini: ' . htmlspecialchars($formattedDate) . '</small>';
        }
        ?>
    </div>

    <!-- Summary Cards - Same style as dashboard_staf -->
    <div class="row g-4 mb-4">
        <?php 
        // Track warna yang dah digunakan untuk pastikan setiap kad dapat warna berbeza
        $usedColors = [];
        foreach ($cardsData as $idx => $card): 
            // Dapatkan warna dan ikon berdasarkan kategori
            $colorIcon = getColorIcon($idx, $card['kategori']);
            
            // Pastikan warna tidak repeat - jika dah digunakan, guna warna seterusnya
            $baseColor = $colorIcon[0];
            $attempt = 0;
            while (in_array($baseColor, $usedColors) && $attempt < count($colorList)) {
                $nextIdx = ($idx + $attempt + 1) % count($colorList);
                $colorIcon = $colorList[$nextIdx];
                $baseColor = $colorIcon[0];
                $attempt++;
            }
            $usedColors[] = $baseColor;
        ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2-4">
            <div class="card border-0 shadow-sm h-100 summary-card" 
                 style="border-left: 5px solid <?php echo $colorIcon[0]; ?> !important; 
                        background: linear-gradient(135deg, #ffffff 0%, <?php echo $colorIcon[2]; ?> 100%) !important;
                        cursor: pointer;"
                 data-kategori="<?php echo htmlspecialchars($card['kategori']); ?>"
                 data-filepath="<?php echo htmlspecialchars($card['filepath']); ?>"
                 data-name="<?php echo htmlspecialchars($card['name']); ?>"
                 onclick="if(typeof window.loadCategory === 'function') { window.loadCategory('<?php echo htmlspecialchars($card['kategori']); ?>', '<?php echo htmlspecialchars($card['filepath']); ?>', this); } else { console.error('loadCategory not available'); alert('Function loadCategory belum dimuatkan. Sila refresh page.'); }">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase"><?php echo htmlspecialchars($card['name']); ?></h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, <?php echo $colorIcon[0]; ?> 0%, <?php echo $colorIcon[1]; ?> 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            <?php echo number_format($card['count']); ?>
                        </h2>
                        <?php if ($card['totalKos'] > 0): ?>
                        <p class="text-muted mb-0 small">RM <?php echo number_format($card['totalKos'], 2); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, <?php echo $colorIcon[0]; ?> 0%, <?php echo $colorIcon[1]; ?> 100%); flex-shrink: 0;">
                        <i class="fas <?php echo $colorIcon[3]; ?> fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Content Area -->
    <div class="row g-4">
        <!-- Map Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0" id="mapTitle">
                            <i class="fas fa-th me-2"></i>Peta Negeri Kedah
                        </h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="resetMap()">
                                <i class="fas fa-home me-1"></i>Reset
                            </button>
                            <button id="mapFullscreenBtn" class="btn btn-outline-primary" onclick="toggleFullscreen()">
                                <i class="fas fa-expand me-1"></i>Fullscreen
                            </button>
                            <button id="mapExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitMapFullscreen()" style="display: none;">
                                <i class="fas fa-compress me-1"></i>Exit Fullscreen
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 600px; width: 100%; background: #f0f0f0;"></div>
                </div>
            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0" id="chartTitle">
                            <i class="fas fa-chart-pie me-2"></i>Pecahan Mengikut Daerah, Parlimen dan DUN
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <select id="distributionType" class="form-select form-select-sm" style="width: auto; max-width: 150px;" onchange="updateDistributionChart()">
                                <option value="DAERAH" selected>Daerah</option>
                                <option value="PARLIMEN">Parlimen</option>
                                <option value="DUN">DUN</option>
                            </select>
                            <div class="btn-group btn-group-sm">
                                <button id="chartFullscreenBtn" class="btn btn-outline-primary" onclick="toggleChartFullscreen()">
                                    <i class="fas fa-expand me-1"></i>Fullscreen
                                </button>
                                <button id="chartExitFullscreenBtn" class="btn btn-outline-danger" onclick="exitChartFullscreen()" style="display: none;">
                                    <i class="fas fa-compress me-1"></i>Exit Fullscreen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 600px; position: relative;">
                        <canvas id="daerahChart"></canvas>
                        <div id="chartLoadingMessage" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #666;">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <p>Memuatkan graf...</p>
                        </div>
                        <div id="chartErrorMessage" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>Graf tidak dapat dimuatkan</p>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Records Detail Section -->
        <div class="col-12 mt-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary mb-0" id="recordsTitle">
                            <i class="fas fa-list me-2"></i>Senarai Rekod
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary" id="recordsCount">0 rekod</span>
                            <button class="btn btn-sm btn-success" onclick="exportRecordsToExcel()" id="exportBtn" style="display: none;">
                                <i class="fas fa-file-excel me-1"></i>Export Excel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="recordsContainer">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p>Sila pilih kad dashboard untuk melihat senarai rekod</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS - With fallback -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
      onerror="this.onerror=null; this.href='https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';" />

<style>
.summary-card { transition: all 0.3s ease; position: relative; }
.summary-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important; }
.summary-card.active { 
    box-shadow: 0 20px 50px rgba(0,0,0,0.4), 0 10px 25px rgba(0,0,0,0.3) !important; 
    transform: scale(1.10) !important; 
    z-index: 10 !important;
    border: 3px solid rgba(0,0,0,0.2) !important;
    position: relative !important;
}
.summary-card.active:hover { 
    transform: scale(1.10) translateY(-3px) !important; 
    box-shadow: 0 25px 60px rgba(0,0,0,0.45), 0 15px 30px rgba(0,0,0,0.35) !important;
}
#map { z-index: 1; }
.leaflet-popup-content { max-width: 300px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.data-quality-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin: 5px 0; }
.data-quality-badge.missing { background: #fee; color: #c33; border: 1px solid #fcc; }

/* Records table styling */
#recordsContainer .table {
    font-size: 0.9rem;
    margin-bottom: 0;
}
#recordsContainer .table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}
#recordsContainer .table tbody tr {
    cursor: pointer;
    transition: background-color 0.2s;
}
#recordsContainer .table tbody tr:hover {
    background-color: #f8f9fa;
}
#recordsContainer .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
#recordsContainer .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Custom column for 5 cards per row */
@media (min-width: 992px) {
    .col-lg-2-4 {
        flex: 0 0 20%;
        max-width: 20%;
    }
}
@media (max-width: 991px) {
    .col-lg-2-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
}
@media (max-width: 767px) {
    .col-lg-2-4 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}
@media (max-width: 575px) {
    .col-lg-2-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>

<!-- Turf.js - Load first for reverse geocoding with fallback -->
<script>
// Load Turf.js with fallback
(function() {
    var turfUrls = [
        'https://unpkg.com/@turf/turf@6.5.0/turf.min.js',
        'https://cdn.jsdelivr.net/npm/@turf/turf@6.5.0/turf.min.js'
    ];
    
    var currentIndex = 0;
    
    function loadTurf() {
        if (currentIndex >= turfUrls.length) {
            console.error('Failed to load Turf.js from all CDNs');
            // Create a minimal turf object to prevent errors
            window.turf = {
                point: function(coords) {
                    return { type: 'Feature', geometry: { type: 'Point', coordinates: coords }, properties: {} };
                },
                booleanPointInPolygon: function() { return false; }
            };
            return;
        }
        
        var script = document.createElement('script');
        script.src = turfUrls[currentIndex];
        script.onload = function() {
            console.log('Turf.js loaded successfully from:', turfUrls[currentIndex]);
            // Verify turf is available
            if (typeof turf === 'undefined' || typeof turf.point !== 'function') {
                console.warn('Turf.js loaded but API not available, trying next CDN...');
                currentIndex++;
                loadTurf();
            }
        };
        script.onerror = function() {
            console.warn('Failed to load Turf.js from:', turfUrls[currentIndex]);
            currentIndex++;
            loadTurf();
        };
        document.head.appendChild(script);
    }
    
    loadTurf();
})();
</script>

<!-- Leaflet JS - Load with multiple fallback CDNs -->
<script>
// Load Leaflet dynamically with fallback CDNs
(function() {
    var cdnUrls = [
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
        'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js'
    ];
    
    var currentIndex = 0;
    
    function tryLoadLeaflet() {
        if (currentIndex >= cdnUrls.length) {
            console.error('All CDN sources failed');
            alert('Gagal memuatkan Leaflet library dari semua sumber. Sila semak sambungan internet atau cuba refresh page.');
            return;
        }
        
        var script = document.createElement('script');
        script.src = cdnUrls[currentIndex];
        script.onload = function() {
            console.log('Leaflet script loaded successfully from:', cdnUrls[currentIndex]);
            window.leafletLoaded = true;
            if (window.initializeDashboard) {
                window.initializeDashboard();
            }
        };
        script.onerror = function() {
            console.warn('Failed to load from:', cdnUrls[currentIndex]);
            currentIndex++;
            tryLoadLeaflet();
        };
        document.head.appendChild(script);
    }
    
    tryLoadLeaflet();
})();
</script>

<!-- CRITICAL: Define loadCategory IMMEDIATELY in a separate script block -->
<!-- This ensures it's available before any HTML onclick handlers are parsed -->
<script>
// IMMEDIATE DEFINITION: loadCategory must be available before HTML is parsed
// This prevents "Function loadCategory belum dimuatkan" errors
(function() {
    'use strict';
    
    // Define loadCategory as a placeholder first to ensure it exists
    window.loadCategory = function(kategori, filepath, cardElement) {
        console.warn('loadCategory placeholder called - full function not yet loaded');
        // This will be replaced by the full async function below
        setTimeout(function() {
            if (window.loadCategory && window.loadCategory !== arguments.callee) {
                window.loadCategory(kategori, filepath, cardElement);
            } else {
                alert('Function loadCategory belum dimuatkan. Sila refresh page.');
            }
        }, 100);
    };
    
    console.log('loadCategory placeholder defined');
})();
</script>

<script>
// Define loadCategory EARLY to ensure it's available before onclick handlers
// This must be defined before any HTML onclick handlers try to use it
window.loadCategory = async function(kategori, filepath, cardElement = null) {
    console.log('loadCategory called (global):', kategori, filepath);
    
    // Check if map is initialized
    if (!map) {
        console.error('Map not initialized!');
        alert('Map belum di-initialize. Sila refresh page.');
        return;
    }
    
    // Remove active class from all cards
    document.querySelectorAll('.summary-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Add active class to clicked card
    if (cardElement) {
        cardElement.classList.add('active');
        // Get card name from data attribute
        currentCardName = cardElement.dataset.name || 'Peta Negeri Kedah';
    } else {
        const card = document.querySelector(`[data-kategori="${kategori}"]`);
        if (card) {
            card.classList.add('active');
            currentCardName = card.dataset.name || 'Peta Negeri Kedah';
        }
    }
    
    // Update titles
    updateMapAndChartTitles();
    
    // Reset distribution type to DAERAH by default for each card
    const distributionTypeSelect = document.getElementById('distributionType');
    if (distributionTypeSelect) {
        distributionTypeSelect.value = 'DAERAH';
        console.log('Reset distribution type to DAERAH for card:', kategori);
    }
    
    try {
        console.log('Loading category:', kategori, 'from:', filepath);
        
        // Use the provided filepath (could be database API or file API)
        // Add timeout to prevent hanging
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
        
        const response = await fetch(filepath, {
            signal: controller.signal,
            cache: 'no-cache'
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let data = await response.json();
        
        if (!data || !data.features) {
            throw new Error('Invalid GeoJSON data: missing features array');
        }
        
        // Update count in card with actual count from API (after deduplication)
        if (data._meta && data._meta.features_count !== undefined) {
            const actualCount = data._meta.features_count;
            console.log(`📊 API returned ${actualCount} features for kategori "${kategori}" (${data._meta.duplicate_count || 0} duplicates removed)`);
            
            // Find and update the count in the card element
            if (cardElement) {
                const countElement = cardElement.querySelector('h2.fw-bold');
                if (countElement) {
                    const formattedCount = new Intl.NumberFormat('en-US').format(actualCount);
                    countElement.textContent = formattedCount;
                    // Also update dataset for future reference
                    if (cardElement.dataset) {
                        cardElement.dataset.actualCount = actualCount;
                    }
                    console.log(`✅ Card count updated from database count to actual API count: ${formattedCount}`);
                } else {
                    console.warn('⚠️ Could not find count element in card');
                }
            } else {
                console.warn('⚠️ Card element not provided, cannot update count');
            }
        } else {
            // Fallback: use features.length if _meta is not available
            const actualCount = data.features ? data.features.length : 0;
            console.log(`📊 Using features.length as count: ${actualCount} (no _meta available)`);
            
            if (cardElement && actualCount > 0) {
                const countElement = cardElement.querySelector('h2.fw-bold');
                if (countElement) {
                    const formattedCount = new Intl.NumberFormat('en-US').format(actualCount);
                    countElement.textContent = formattedCount;
                    console.log(`✅ Card count updated using features.length: ${formattedCount}`);
                }
            }
        }
        
        console.log('Data loaded:', data.features.length, 'features');
        
        // Load boundaries for reverse geocoding
        console.log('Loading boundaries for reverse geocoding...');
        if (!boundaryCache.loaded) {
            await loadBoundariesForGeocoding();
        }
        
        // Verify boundaries are loaded before enrichment
        const hasDaerah = boundaryCache.daerah && boundaryCache.daerah.features && boundaryCache.daerah.features.length > 0;
        const hasParlimen = boundaryCache.parlimen && boundaryCache.parlimen.features && boundaryCache.parlimen.features.length > 0;
        const hasDUN = boundaryCache.dun && boundaryCache.dun.features && boundaryCache.dun.features.length > 0;
        
        console.log('📊 Boundary cache status before enrichment:', {
            daerah: hasDaerah ? boundaryCache.daerah.features.length + ' features' : 'missing',
            parlimen: hasParlimen ? boundaryCache.parlimen.features.length + ' features' : 'missing',
            dun: hasDUN ? boundaryCache.dun.features.length + ' features' : 'missing'
        });
        
        // If parlimen or DUN boundaries are missing, try to load them again
        if (!hasParlimen || !hasDUN) {
            console.warn('⚠️ Parlimen or DUN boundaries missing, attempting to reload...');
            // Reset loaded flag to force reload
            boundaryCache.loaded = false;
            await loadBoundariesForGeocoding();
            
            // Verify again after reload
            const hasParlimenAfter = boundaryCache.parlimen && boundaryCache.parlimen.features && boundaryCache.parlimen.features.length > 0;
            const hasDUNAfter = boundaryCache.dun && boundaryCache.dun.features && boundaryCache.dun.features.length > 0;
            
            if (!hasParlimenAfter) {
                console.error('❌ CRITICAL: Parlimen boundaries still not loaded after retry!');
                console.error('💡 This will cause chart to show no data when selecting "Parlimen" distribution.');
            }
            if (!hasDUNAfter) {
                console.error('❌ CRITICAL: DUN boundaries still not loaded after retry!');
                console.error('💡 This will cause chart to show no data when selecting "DUN" distribution.');
            }
        }
        
        // Count features before enrichment
        let beforeEnrichment = {
            withDAERAH: 0,
            withPARLIMEN: 0,
            withDUN: 0,
            withBoth: 0
        };
        data.features.forEach(f => {
            const props = f.properties || {};
            if (props.DAERAH && props.DAERAH.trim() !== '') beforeEnrichment.withDAERAH++;
            if (props.PARLIMEN && props.PARLIMEN.trim() !== '') beforeEnrichment.withPARLIMEN++;
            if (props.DUN && props.DUN.trim() !== '') beforeEnrichment.withDUN++;
            if (props.PARLIMEN && props.PARLIMEN.trim() !== '' && props.DUN && props.DUN.trim() !== '') beforeEnrichment.withBoth++;
        });
        console.log('📊 Before enrichment:', beforeEnrichment);
        
        // Enrich features with reverse geocoding (DAERAH, PARLIMEN, DUN)
        console.log('Enriching features with reverse geocoding...');
        data = enrichFeaturesWithGeocoding(data);
        
        // Count enriched features after enrichment
        let afterEnrichment = {
            withDAERAH: 0,
            withPARLIMEN: 0,
            withDUN: 0,
            withBoth: 0
        };
        data.features.forEach(f => {
            const props = f.properties || {};
            if (props.DAERAH && props.DAERAH.trim() !== '') afterEnrichment.withDAERAH++;
            if (props.PARLIMEN && props.PARLIMEN.trim() !== '') afterEnrichment.withPARLIMEN++;
            if (props.DUN && props.DUN.trim() !== '') afterEnrichment.withDUN++;
            if (props.PARLIMEN && props.PARLIMEN.trim() !== '' && props.DUN && props.DUN.trim() !== '') afterEnrichment.withBoth++;
        });
        console.log('📊 After enrichment:', afterEnrichment);
        
        // Calculate improvement
        const improvement = {
            DAERAH: afterEnrichment.withDAERAH - beforeEnrichment.withDAERAH,
            PARLIMEN: afterEnrichment.withPARLIMEN - beforeEnrichment.withPARLIMEN,
            DUN: afterEnrichment.withDUN - beforeEnrichment.withDUN
        };
        console.log('📊 Improvement:', improvement);
        
        if (afterEnrichment.withPARLIMEN === 0) {
            console.error('❌ CRITICAL: No features have PARLIMEN after enrichment!');
            console.error('💡 This will cause chart to show no data when selecting "Parlimen" distribution.');
            console.error('💡 Possible causes:');
            console.error('   1. Boundary cache for parlimen not loaded');
            console.error('   2. Features have invalid geometry');
            console.error('   3. Features are outside parlimen boundaries');
            
            // Try to enrich again if boundaries are now available
            if (hasParlimen && data.features.length > 0) {
                console.log('🔄 Attempting to enrich again with available parlimen boundaries...');
                console.log('   - Total features to enrich:', data.features.length);
                console.log('   - Parlimen boundaries available:', boundaryCache.parlimen.features.length);
                
                // Re-enrich only features without PARLIMEN
                let reEnrichedCount = 0;
                let reEnrichProcessed = 0;
                data.features.forEach((feature, index) => {
                    reEnrichProcessed++;
                    const props = feature.properties || {};
                    if (!props.PARLIMEN && feature.geometry) {
                        try {
                            const enrichedProps = reverseGeocodePoint(feature, { ...props });
                            if (enrichedProps.PARLIMEN && enrichedProps.PARLIMEN !== props.PARLIMEN) {
                                feature.properties = enrichedProps;
                                reEnrichedCount++;
                                // Log first few successes
                                if (reEnrichedCount <= 5) {
                                    console.log('✅ Re-enriched feature', index, 'with PARLIMEN:', enrichedProps.PARLIMEN);
                                }
                            }
                        } catch (e) {
                            // Only log first few errors
                            if (reEnrichedCount < 5) {
                                console.warn('Error re-enriching feature', index, ':', e);
                            }
                        }
                    }
                });
                console.log('🔄 Re-enrichment complete:', {
                    processed: reEnrichProcessed,
                    enriched: reEnrichedCount,
                    success_rate: ((reEnrichedCount / reEnrichProcessed) * 100).toFixed(1) + '%'
                });
                
                if (reEnrichedCount > 0) {
                    console.log('✅ Re-enriched', reEnrichedCount, 'features with PARLIMEN');
                    // Re-count
                    afterEnrichment.withPARLIMEN = data.features.filter(f => {
                        const props = f.properties || {};
                        return props.PARLIMEN && props.PARLIMEN.trim() !== '';
                    }).length;
                    console.log('📊 Total features with PARLIMEN after re-enrichment:', afterEnrichment.withPARLIMEN);
                } else {
                    console.error('❌ Re-enrichment failed - no features were enriched with PARLIMEN');
                    console.error('💡 This suggests a fundamental issue with reverse geocoding');
                    console.error('💡 Possible solutions:');
                    console.error('   1. Check if boundary data for parlimen exists in database');
                    console.error('   2. Check if features have valid geometry coordinates');
                    console.error('   3. Run batch_spatial_update_geojson.php to enrich data in database');
                }
            } else {
                console.error('❌ Cannot re-enrich: parlimen boundaries not available');
                console.error('   - hasParlimen:', hasParlimen);
                console.error('   - boundaryCache.parlimen:', boundaryCache.parlimen ? 'exists' : 'null');
                console.error('   - features count:', data.features.length);
            }
        }
        if (afterEnrichment.withDUN === 0) {
            console.error('❌ CRITICAL: No features have DUN after enrichment!');
            console.error('💡 This will cause chart to show no data when selecting "DUN" distribution.');
            console.error('💡 Possible causes:');
            console.error('   1. Boundary cache for DUN not loaded');
            console.error('   2. Features have invalid geometry');
            console.error('   3. Features are outside DUN boundaries');
            
            // Try to enrich again if boundaries are now available
            if (hasDUN && data.features.length > 0) {
                console.log('🔄 Attempting to enrich again with available DUN boundaries...');
                console.log('   - Total features to enrich:', data.features.length);
                console.log('   - DUN boundaries available:', boundaryCache.dun.features.length);
                
                // Re-enrich only features without DUN
                let reEnrichedCount = 0;
                let reEnrichProcessed = 0;
                data.features.forEach((feature, index) => {
                    reEnrichProcessed++;
                    const props = feature.properties || {};
                    if (!props.DUN && feature.geometry) {
                        try {
                            const enrichedProps = reverseGeocodePoint(feature, { ...props });
                            if (enrichedProps.DUN && enrichedProps.DUN !== props.DUN) {
                                feature.properties = enrichedProps;
                                reEnrichedCount++;
                                // Log first few successes
                                if (reEnrichedCount <= 5) {
                                    console.log('✅ Re-enriched feature', index, 'with DUN:', enrichedProps.DUN);
                                }
                            }
                        } catch (e) {
                            // Only log first few errors
                            if (reEnrichedCount < 5) {
                                console.warn('Error re-enriching feature', index, ':', e);
                            }
                        }
                    }
                });
                console.log('🔄 Re-enrichment complete:', {
                    processed: reEnrichProcessed,
                    enriched: reEnrichedCount,
                    success_rate: ((reEnrichedCount / reEnrichProcessed) * 100).toFixed(1) + '%'
                });
                
                if (reEnrichedCount > 0) {
                    console.log('✅ Re-enriched', reEnrichedCount, 'features with DUN');
                    // Re-count
                    afterEnrichment.withDUN = data.features.filter(f => {
                        const props = f.properties || {};
                        return props.DUN && props.DUN.trim() !== '';
                    }).length;
                    console.log('📊 Total features with DUN after re-enrichment:', afterEnrichment.withDUN);
                } else {
                    console.error('❌ Re-enrichment failed - no features were enriched with DUN');
                    console.error('💡 This suggests a fundamental issue with reverse geocoding');
                    console.error('💡 Possible solutions:');
                    console.error('   1. Check if boundary data for DUN exists in database');
                    console.error('   2. Check if features have valid geometry coordinates');
                    console.error('   3. Run batch_spatial_update_geojson.php to enrich data in database');
                }
            } else {
                console.error('❌ Cannot re-enrich: DUN boundaries not available');
                console.error('   - hasDUN:', hasDUN);
                console.error('   - boundaryCache.dun:', boundaryCache.dun ? 'exists' : 'null');
                console.error('   - features count:', data.features.length);
            }
        }
        
        if (afterEnrichment.withPARLIMEN > 0 || afterEnrichment.withDUN > 0) {
            console.log(`✅ Berjaya mengisi maklumat lokasi: ${afterEnrichment.withPARLIMEN} rekod dengan PARLIMEN, ${afterEnrichment.withDUN} rekod dengan DUN`);
        }
        
        currentData = data;
        currentChartData = data; // Store for chart updates
        
        console.log('Data stored for chart:', currentChartData.features.length, 'features');
        console.log('Processing', data.features.length, 'features for map display');
        
        // Store data for records display (will be called after map display)
        window.currentRecordsData = data;
        
        // Ensure map is ready
        if (!map) {
            throw new Error('Map belum di-initialize. Sila refresh page.');
        }
        
        // Wait for map to be fully ready
        if (!map.getContainer()) {
            throw new Error('Map container tidak dijumpai.');
        }
        
        // Clear previous layer
        if (currentLayer) {
            try {
                map.removeLayer(currentLayer);
                console.log('Previous layer removed');
            } catch (e) {
                console.warn('Error removing previous layer:', e);
            }
        }
        
        // Filter valid features (must have geometry and within Kedah bounds)
        const validFeatures = data.features.filter(f => {
            if (!f.geometry) {
                console.warn('Feature missing geometry:', f);
                return false;
            }
            
            // For Point geometry, check if coordinates are valid
            if (f.geometry.type === 'Point') {
                if (!f.geometry.coordinates || f.geometry.coordinates.length < 2) {
                    console.warn('Point feature has invalid coordinates:', f.geometry);
                    return false;
                }
                
                const [lng, lat] = f.geometry.coordinates;
                if (typeof lat !== 'number' || typeof lng !== 'number' || 
                    isNaN(lat) || isNaN(lng) || 
                    lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    console.warn('Point feature has invalid lat/lng:', lat, lng);
                    return false;
                }
                
                // Check if point is within Kedah bounds (rough check)
                if (typeof L !== 'undefined') {
                    const kedahBounds = getKedahBounds();
                    if (kedahBounds) {
                        const point = L.latLng(lat, lng);
                        if (!kedahBounds.contains(point)) {
                            console.log('Point terkeluar dari Kedah bounds, diabaikan');
                            return false; // Abaikan point yang terkeluar
                        }
                    }
                }
            } else if (f.geometry.type === 'Polygon' || f.geometry.type === 'MultiPolygon') {
                if (!f.geometry.coordinates || f.geometry.coordinates.length === 0) {
                    console.warn('Polygon feature has empty coordinates:', f.geometry);
                    return false;
                }
                // For polygons, check if any point is within Kedah bounds
                if (typeof L !== 'undefined') {
                    const kedahBounds = getKedahBounds();
                    if (kedahBounds) {
                        let hasPointInKedah = false;
                        const coords = f.geometry.type === 'Polygon' 
                            ? f.geometry.coordinates[0] 
                            : f.geometry.coordinates.flat(2);
                        
                        for (let i = 0; i < coords.length; i += 2) {
                            if (coords.length > i + 1) {
                                const [lng, lat] = coords[i] instanceof Array ? coords[i] : [coords[i], coords[i + 1]];
                                const point = L.latLng(lat, lng);
                                if (kedahBounds.contains(point)) {
                                    hasPointInKedah = true;
                                    break;
                                }
                            }
                        }
                        if (!hasPointInKedah) {
                            console.log('Polygon terkeluar dari Kedah bounds, diabaikan');
                            return false; // Abaikan polygon yang terkeluar
                        }
                    }
                }
            } else if (f.geometry.type === 'LineString' || f.geometry.type === 'MultiLineString') {
                if (!f.geometry.coordinates || f.geometry.coordinates.length === 0) {
                    console.warn('LineString feature has empty coordinates:', f.geometry);
                    return false;
                }
                // For linestrings, check if any point is within Kedah bounds
                if (typeof L !== 'undefined') {
                    const kedahBounds = getKedahBounds();
                    if (kedahBounds) {
                        let hasPointInKedah = false;
                        const coords = f.geometry.type === 'LineString' 
                            ? f.geometry.coordinates 
                            : f.geometry.coordinates.flat();
                        
                        for (let coord of coords) {
                            if (coord && coord.length >= 2) {
                                const [lng, lat] = coord;
                                const point = L.latLng(lat, lng);
                                if (kedahBounds.contains(point)) {
                                    hasPointInKedah = true;
                                    break;
                                }
                            }
                        }
                        if (!hasPointInKedah) {
                            console.log('LineString terkeluar dari Kedah bounds, diabaikan');
                            return false; // Abaikan linestring yang terkeluar
                        }
                    }
                }
            } else {
                console.warn('Unknown or missing geometry type:', f.geometry.type, f);
                return false;
            }
            
            return true;
        });
        
        console.log('Valid features:', validFeatures.length, 'out of', data.features.length);
        
        if (validFeatures.length === 0) {
            // Show helpful error message
            const errorMsg = data.features.length > 0 
                ? `Tiada features dengan geometry yang sah untuk dipaparkan. Semua ${data.features.length} features tidak mempunyai geometry yang valid.`
                : 'Tiada features dalam data.';
            
            console.error('Tiada data untuk dipaparkan:', errorMsg);
            throw new Error(errorMsg);
        }
        
        // Create GeoJSON with only valid features
        const validGeoJSON = {
            type: 'FeatureCollection',
            features: validFeatures
        };
        
        // Create new layer
        try {
            // Keep all features as-is: Points stay as Points, Polygons stay as Polygons
            // Only Point geometry will be displayed as red markers
            // Polygons (lot tanah, jalan, etc.) will stay as polygons with green color
            
            currentLayer = L.geoJSON(validGeoJSON, {
                pointToLayer: function(feature, latlng) {
                    // Hanya proses untuk Point geometry sahaja
                    // Polygon, LineString, dll akan diproses oleh style function
                    if (!feature.geometry || feature.geometry.type !== 'Point') {
                        return null; // Let Leaflet handle non-Point geometries with style function
                    }
                    
                    if (!latlng || !latlng.lat || !latlng.lng) {
                        console.warn('Invalid latlng:', latlng, 'for feature:', feature);
                        return null;
                    }
                    
                    // Create marker dengan warna merah - HANYA untuk Point geometry
                    const marker = L.circleMarker(latlng, {
                        radius: 10,
                        fillColor: '#FF0000', // Merah untuk point GPS
                        color: '#FFFFFF', // Border putih
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 1.0,
                        interactive: true, // Ensure marker is clickable
                        bubblingMouseEvents: false // Prevent events from bubbling to map
                    });
                    
                    // Store feature and props in marker for event handlers
                    marker.feature = feature;
                    const markerProps = feature.properties || {};
                    marker.featureProps = markerProps;
                    
                    // Ensure marker is interactive and on top
                    marker.options.interactive = true;
                    marker.options.bubblingMouseEvents = false;
                    
                    // Add event handlers directly to marker - simplified
                    marker.on('mouseover', function(e) {
                        if (e.originalEvent) {
                            e.originalEvent.stopPropagation();
                            e.originalEvent.preventDefault();
                        }
                        L.DomEvent.stopPropagation(e);
                        const markerProps = this.featureProps || (this.feature && this.feature.properties) || {};
                        console.log('🔵 MOUSEOVER on marker:', this, 'Props:', markerProps);
                        
                        // Clear any existing timeout
                        if (modalTimeout) {
                            clearTimeout(modalTimeout);
                        }
                        
                        // Debounce mouseover to prevent too many modal calls
                        modalTimeout = setTimeout(() => {
                            if (window.showRecordModal) {
                                console.log('🟢 Calling showRecordModal on mouseover with:', markerProps);
                                window.showRecordModal(markerProps);
                            } else {
                                console.error('showRecordModal not available');
                            }
                        }, 300);
                    });
                    
                    marker.on('mouseout', function(e) {
                        if (e.originalEvent) {
                            e.originalEvent.stopPropagation();
                        }
                        L.DomEvent.stopPropagation(e);
                        // Clear timeout on mouseout
                        if (modalTimeout) {
                            clearTimeout(modalTimeout);
                            modalTimeout = null;
                        }
                    });
                    
                    marker.on('click', function(e) {
                        if (e.originalEvent) {
                            e.originalEvent.stopPropagation();
                            e.originalEvent.preventDefault();
                        }
                        L.DomEvent.stopPropagation(e);
                        L.DomEvent.preventDefault(e);
                        const markerProps = this.featureProps || (this.feature && this.feature.properties) || {};
                        console.log('🟡 CLICK on marker:', this, 'Props:', markerProps);
                        
                        // Clear timeout
                        if (modalTimeout) {
                            clearTimeout(modalTimeout);
                            modalTimeout = null;
                        }
                        
                        // Show modal immediately on click
                        if (window.showRecordModal) {
                            console.log('🟢 Calling showRecordModal on marker click with:', markerProps);
                            window.showRecordModal(markerProps);
                        } else {
                            console.error('showRecordModal not available');
                        }
                    });
                    
                    return marker;
                },
                style: function(feature) {
                    if (feature.geometry && feature.geometry.type === 'Point') {
                        return {}; // Point styling handled by pointToLayer
                    }
                    // For polygons and linestrings, use red color scheme
                    return {
                        color: '#FF0000', // Merah untuk polygon/line
                        weight: 3,
                        opacity: 0.7,
                        fillColor: '#FF9999', // Merah muda untuk fill
                        fillOpacity: 0.4
                    };
                },
                onEachFeature: function(feature, layer) {
                    if (!layer) return;
                    
                    const props = feature.properties || {};
                    layer.featureProps = props;
                    layer.feature = feature;
                    
                    const popupContent = generatePopupContent(props);
                    layer.bindPopup(popupContent);
                    
                    // Add mouseover and click handlers for non-Point geometries
                    layer.on('mouseover', function(e) {
                        e.originalEvent && e.originalEvent.stopPropagation();
                        const layerProps = this.featureProps || (this.feature && this.feature.properties) || {};
                        console.log('🔵 MOUSEOVER on layer:', this, 'Props:', layerProps);
                        
                        // Clear any existing timeout
                        if (modalTimeout) {
                            clearTimeout(modalTimeout);
                        }
                        
                        // Debounce mouseover
                        modalTimeout = setTimeout(() => {
                            if (window.showRecordModal) {
                                console.log('🟢 Calling showRecordModal on mouseover with:', layerProps);
                                window.showRecordModal(layerProps);
                            } else {
                                console.error('showRecordModal not available');
                            }
                        }, 300);
                    });
                    
                    layer.on('mouseout', function(e) {
                        e.originalEvent && e.originalEvent.stopPropagation();
                        // Clear timeout on mouseout
                        if (modalTimeout) {
                            clearTimeout(modalTimeout);
                            modalTimeout = null;
                        }
                    });
                    
                    layer.on('click', function(e) {
                        e.originalEvent && e.originalEvent.stopPropagation();
                        const layerProps = this.featureProps || (this.feature && this.feature.properties) || {};
                        console.log('🟡 CLICK on layer:', this, 'Props:', layerProps);
                        
                        // Clear timeout
                        if (modalTimeout) {
                            clearTimeout(modalTimeout);
                            modalTimeout = null;
                        }
                        
                        // Show modal immediately on click
                        if (window.showRecordModal) {
                            console.log('🟢 Calling showRecordModal on click with:', layerProps);
                            window.showRecordModal(layerProps);
                        } else {
                            console.error('showRecordModal not available');
                        }
                    });
                }
            });
            
            // Add to map
            if (currentLayer) {
                currentLayer.addTo(map);
                console.log('Layer added to map successfully. Features:', validFeatures.length);
                
                // Ensure rekod layer is on top (z-index: 500) - above all boundary layers
                // Check if setZIndexOffset method exists before calling it
                if (currentLayer && typeof currentLayer.setZIndexOffset === 'function') {
                    currentLayer.setZIndexOffset(500);
                } else {
                    console.warn('⚠️ setZIndexOffset not available, using bringToFront instead');
                    if (currentLayer && typeof currentLayer.bringToFront === 'function') {
                        currentLayer.bringToFront();
                    }
                }
                
                // Ensure all markers are interactive and on top
                if (currentLayer.eachLayer) {
                    currentLayer.eachLayer(function(layer) {
                        if (layer instanceof L.CircleMarker || layer instanceof L.Marker) {
                            layer.options.interactive = true;
                            layer.options.bubblingMouseEvents = false;
                            // Bring marker to front
                            if (layer.bringToFront) {
                                layer.bringToFront();
                            }
                        }
                    });
                }
                
                // Test: Verify event handlers are attached
                let handlerCount = 0;
                if (currentLayer.eachLayer) {
                    currentLayer.eachLayer(function(layer) {
                        if (layer._events && (layer._events.mouseover || layer._events.click)) {
                            handlerCount++;
                        }
                    });
                    console.log('Event handlers attached to', handlerCount, 'sublayers');
                }
                
                // Force map to update and show layer
                map.invalidateSize();
                
                // Ensure layer is visible - check if layer has any layers
                if (currentLayer.eachLayer) {
                    let layerCount = 0;
                    currentLayer.eachLayer(() => layerCount++);
                    console.log('Layer contains', layerCount, 'sublayers');
                    
                    if (layerCount === 0) {
                        console.warn('WARNING: Layer added but contains no sublayers!');
                    }
                }
                
                // Double check layer is on map
                if (!map.hasLayer(currentLayer)) {
                    console.error('ERROR: Layer not found on map after addTo!');
                    currentLayer.addTo(map);
                }
                
                // Ensure correct layer order (rekod layer on top)
                ensureLayerOrder();
            } else {
                throw new Error('Failed to create layer - currentLayer is null');
            }
            
            // Fit map to Kedah bounds (always center on Kedah)
            try {
                const kedahBounds = getKedahBounds();
                if (kedahBounds) {
                    setTimeout(() => {
                        map.invalidateSize();
                        map.fitBounds(kedahBounds, {
                            padding: [20, 20],
                            animate: true,
                            duration: 0.5
                        });
                        console.log('Map fitted to Kedah bounds (always centered on Kedah)');
                    }, 200);
                }
            } catch (boundsError) {
                console.warn('Error fitting bounds:', boundsError);
                // Continue anyway
            }
            
            // Update chart with DAERAH by default (distribution type already reset in loadCategory)
            // Use setTimeout to ensure data is fully processed before updating chart
            setTimeout(async () => {
                try {
                    // Ensure currentChartData is set before updating chart
                    if (!currentChartData) {
                        console.warn('currentChartData is not set, using currentData');
                        currentChartData = currentData;
                    }
                    
                    // Double check currentChartData is valid
                    if (!currentChartData || !currentChartData.features || currentChartData.features.length === 0) {
                        console.error('Invalid currentChartData:', currentChartData);
                        console.log('Trying to use currentData as fallback:', currentData);
                        if (currentData && currentData.features && currentData.features.length > 0) {
                            currentChartData = currentData;
                        } else {
                            console.error('No valid data available for chart');
                            return;
                        }
                    }
                    
                    // Ensure distribution type is set to DAERAH (reuse variable declared earlier)
                    const distTypeSelect = document.getElementById('distributionType');
                    if (distTypeSelect && distTypeSelect.value !== 'DAERAH') {
                        distTypeSelect.value = 'DAERAH';
                        console.log('✅ Distribution type set to DAERAH');
                    }
                    
                    // Wait a bit more to ensure boundaries are loaded
                    if (!boundaryCache.loaded) {
                        console.log('⏳ Waiting for boundaries to load...');
                        await loadBoundariesForGeocoding();
                    }
                    
                    // Update chart directly - don't wait for user to click dropdown
                    console.log('📊 ===== AUTO-UPDATING CHART =====');
                    console.log('📊 Features count:', currentChartData.features.length);
                    console.log('📊 Distribution type: DAERAH (auto)');
                    console.log('📊 Chart.js available:', typeof Chart !== 'undefined');
                    console.log('📊 Canvas element exists:', document.getElementById('daerahChart') !== null);
                    
                    // Ensure Chart.js is loaded
                    if (typeof Chart === 'undefined') {
                        console.warn('⚠️ Chart.js not loaded yet, waiting...');
                        let retryCount = 0;
                        const maxRetries = 10;
                        const checkChart = setInterval(() => {
                            retryCount++;
                            if (typeof Chart !== 'undefined') {
                                clearInterval(checkChart);
                                console.log('✅ Chart.js loaded after', retryCount * 100, 'ms');
                                updateDaerahChart(currentChartData, 'DAERAH');
                                loadBoundaryLayerByType('DAERAH');
                            } else if (retryCount >= maxRetries) {
                                clearInterval(checkChart);
                                console.error('❌ Chart.js failed to load after', maxRetries * 100, 'ms');
                            }
                        }, 100);
                    } else {
                        // Chart.js is loaded, update chart immediately
                        console.log('✅ Chart.js is ready, updating chart now...');
                        try {
                            updateDaerahChart(currentChartData, 'DAERAH');
                            console.log('✅ updateDaerahChart called successfully');
                        } catch (e) {
                            console.error('❌ Error calling updateDaerahChart:', e);
                        }
                    }
                    
                    // Also load boundary layer
                    try {
                        await loadBoundaryLayerByType('DAERAH');
                        console.log('✅ Boundary layer loaded');
                    } catch (e) {
                        console.error('❌ Error loading boundary layer:', e);
                    }
                    
                    console.log('✅ ===== CHART AUTO-UPDATE COMPLETED =====');
                } catch (chartError) {
                    console.error('❌ Error updating chart:', chartError);
                    console.error('Chart error stack:', chartError.stack);
                }
            }, 500); // Increased delay to ensure data is fully ready
            
            // Log success message
            console.log(`Data dimuatkan: ${validFeatures.length} rekod dipaparkan pada peta`);
            
            console.log('Data loaded successfully:', validFeatures.length, 'features displayed on map');
            
            // Display records in detail section (use enriched data)
            displayRecords(currentData);
            
        } catch (layerError) {
            console.error('Error creating layer:', layerError);
            throw layerError;
        }
        
    } catch (error) {
        console.error('Error loading data:', error);
        console.error('File path:', filepath);
        console.error('Error stack:', error.stack);
        
        let errorMsg = error.message || 'Unknown error';
        if (error.message && (error.message.includes('Failed to fetch') || error.message.includes('NetworkError'))) {
            errorMsg = 'Data tidak dijumpai atau tidak boleh diakses. Sila semak kategori: ' + kategori;
        } else if (error.message && error.message.includes('JSON')) {
            errorMsg = 'Format JSON tidak sah. Sila semak data dalam database untuk kategori: ' + kategori;
        }
        
        console.error('Error loading data:', errorMsg);
    }
};

// CRITICAL: Verify loadCategory is accessible immediately after definition
// This must run synchronously to catch any syntax errors
(function() {
    try {
        const isFunction = typeof window.loadCategory === 'function';
        console.log('loadCategory defined:', isFunction);
        
        if (!isFunction) {
            console.error('CRITICAL: loadCategory is not a function!');
            console.error('This indicates a syntax error in the loadCategory definition above.');
            // Define a minimal fallback
            window.loadCategory = function(kategori, filepath, cardElement) {
                alert('Function loadCategory belum dimuatkan. Sila refresh page.\n\nKemungkinan ada syntax error dalam kod JavaScript.');
                console.error('loadCategory fallback called - original function failed to load');
            };
        } else {
            console.log('✅ loadCategory is available and ready to use');
            // Test that it can be called
            try {
                const testCall = window.loadCategory.toString();
                if (testCall && testCall.length > 0) {
                    console.log('✅ loadCategory function body is valid');
                }
            } catch (e) {
                console.error('Error testing loadCategory:', e);
            }
        }
    } catch (e) {
        console.error('FATAL ERROR in loadCategory verification:', e);
        // Last resort fallback
        window.loadCategory = function(kategori, filepath, cardElement) {
            alert('Function loadCategory belum dimuatkan. Sila refresh page.');
            console.error('loadCategory fallback called - verification failed');
        };
    }
})();

// Also verify when DOM is ready
(function verifyLoadCategory() {
    const checkFunction = function() {
        const isFunction = typeof window.loadCategory === 'function';
        console.log('DOM ready - verifying loadCategory:', isFunction);
        
        if (!isFunction) {
            console.error('CRITICAL: loadCategory is not a function after DOM loaded!');
            // Define fallback
            window.loadCategory = function(kategori, filepath, cardElement) {
                alert('Function loadCategory belum dimuatkan. Sila refresh page.');
                console.error('loadCategory fallback called - original function failed to load');
            };
        } else {
            // Verify it's not the placeholder
            try {
                const funcStr = window.loadCategory.toString();
                if (funcStr.includes('placeholder') || funcStr.includes('arguments.callee')) {
                    console.warn('loadCategory is still placeholder - waiting for full definition...');
                    setTimeout(checkFunction, 500);
                    return;
                }
            } catch (e) {
                console.error('Error checking loadCategory:', e);
            }
            console.log('✅ loadCategory verified and is full function');
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkFunction);
    } else {
        checkFunction();
    }
})();

// Check if Chart.js is loaded
if (typeof Chart === 'undefined') {
    console.error('Chart.js is not loaded!');
    // Try to load Chart.js dynamically
    const chartScript = document.createElement('script');
    chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    chartScript.onload = function() {
        console.log('Chart.js loaded dynamically');
        initializeChartDefaults();
    };
    chartScript.onerror = function() {
        console.error('Failed to load Chart.js');
        alert('Gagal memuatkan Chart.js library. Sila refresh page.');
    };
    document.head.appendChild(chartScript);
} else {
    console.log('Chart.js is loaded');
    initializeChartDefaults();
}

function initializeChartDefaults() {
    // Set default font for all charts - Same as dashboard_staf
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.font.size = 12;
    console.log('Chart defaults initialized');
}

// Global variables
let map, currentLayer = null, daerahChart = null;
let currentData = null;
let currentChartData = null; // Store data for chart updates
let currentBoundaryLayer = null; // Store current boundary layer (daerah/parlimen/dun)
let negeriLayer = null; // Store negeri boundary layer
let isInitialBoundaryLoad = true; // Track if this is the first boundary load
let currentCardName = 'Peta Negeri Kedah'; // Store current card name for titles
let lastModalProps = null; // Store last modal props to prevent duplicate modals
let modalTimeout = null; // Timeout for mouseover modal

// Define showRecordModal early to ensure it's available
console.log('🔧 Defining showRecordModal function...');
window.showRecordModal = function(props) {
    console.log('🟣 showRecordModal CALLED with:', props);
    console.log('Modal element exists:', document.getElementById('recordDetailModal') !== null);
    console.log('Modal body exists:', document.getElementById('recordDetailModalBody') !== null);
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    
    if (!props) {
        console.warn('No properties provided, using empty object');
        props = {};
    }
    
    const modalBody = document.getElementById('recordDetailModalBody');
    if (!modalBody) {
        console.error('❌ Modal body element not found!');
        alert('Modal tidak ditemukan. Sila refresh page.');
        return;
    }
    
    console.log('✅ Modal body found, generating content...');
    
    let html = '<div class="container-fluid">';
    
    // Check for missing data
    const missingFields = [];
    if (!props.DAERAH || props.DAERAH === '') missingFields.push('DAERAH');
    if (!props.DUN || props.DUN === '') missingFields.push('DUN');
    if (!props.PARLIMEN || props.PARLIMEN === '') missingFields.push('PARLIMEN');
    
    // Show success badge if all fields are filled
    if (missingFields.length === 0 && (props.DAERAH || props.PARLIMEN || props.DUN)) {
        html += '<div class="alert alert-success mb-3">';
        html += '<i class="fas fa-check-circle me-2"></i><strong>Maklumat Lokasi Lengkap</strong>';
        html += '</div>';
    } else if (missingFields.length > 0) {
        html += '<div class="alert alert-warning mb-3">';
        html += '<i class="fas fa-exclamation-triangle me-2"></i><strong>Perlu Kemaskini:</strong> ' + missingFields.join(', ');
        html += '</div>';
    }
    
    // Display location fields prominently if available
    if (props.DAERAH || props.PARLIMEN || props.DUN) {
        html += '<div class="card mb-3 border-primary">';
        html += '<div class="card-header bg-primary text-white">';
        html += '<h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Maklumat Lokasi</h6>';
        html += '</div>';
        html += '<div class="card-body">';
        if (props.DAERAH) {
            html += `<div class="mb-2"><i class="fas fa-map-marker-alt text-success me-2"></i><strong>Daerah:</strong> ${props.DAERAH}</div>`;
        }
        if (props.PARLIMEN) {
            html += `<div class="mb-2"><i class="fas fa-landmark text-primary me-2"></i><strong>Parlimen:</strong> ${props.PARLIMEN}</div>`;
        }
        if (props.DUN) {
            html += `<div class="mb-2"><i class="fas fa-building text-info me-2"></i><strong>DUN:</strong> ${props.DUN}</div>`;
        }
        html += '</div>';
        html += '</div>';
    }
    
    // Display all other properties in a table format
    html += '<div class="card">';
    html += '<div class="card-header bg-light">';
    html += '<h6 class="mb-0"><i class="fas fa-list me-2"></i>Maklumat Lengkap</h6>';
    html += '</div>';
    html += '<div class="card-body">';
    html += '<div class="table-responsive">';
    html += '<table class="table table-striped table-hover">';
    html += '<tbody>';
    
    let hasData = false;
    Object.keys(props).forEach(key => {
        if (key === 'DAERAH' || key === 'PARLIMEN' || key === 'DUN') {
            return;
        }
        
        if (props[key] && props[key] !== '') {
            hasData = true;
            let value = props[key];
            if (key === 'KOS' || key === 'Kadar_Sewaan') {
                value = 'RM ' + value;
            }
            html += '<tr>';
            html += `<td class="fw-bold" style="width: 30%;">${key}</td>`;
            html += `<td>${value}</td>`;
            html += '</tr>';
        }
    });
    
    if (!hasData) {
        html += '<tr><td colspan="2" class="text-center text-muted">Tiada maklumat tambahan</td></tr>';
    }
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    modalBody.innerHTML = html;
    console.log('✅ Modal content generated');
    
    // Show modal
    const modalElement = document.getElementById('recordDetailModal');
    if (!modalElement) {
        console.error('❌ Modal element not found!');
        return;
    }
    
    // Try Bootstrap 5
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        console.log('✅ Using Bootstrap 5 modal');
        try {
            const existingBackdrop = document.getElementById('modalBackdrop');
            if (existingBackdrop) existingBackdrop.remove();
            
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.show();
            } else {
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                modal.show();
            }
        } catch (e) {
            console.error('❌ Error showing Bootstrap modal:', e);
            // Fallback: manual display
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            modalElement.setAttribute('aria-hidden', 'false');
            modalElement.setAttribute('aria-modal', 'true');
            modalElement.style.zIndex = '1055';
            
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            backdrop.style.zIndex = '1050';
            document.body.appendChild(backdrop);
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }
    } else {
        console.error('❌ Bootstrap not available, using fallback');
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        modalElement.style.zIndex = '1055';
        
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'modalBackdrop';
        backdrop.style.zIndex = '1050';
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }
    
    console.log('✅ Modal display completed');
};
console.log('✅ showRecordModal function defined:', typeof window.showRecordModal === 'function');

// Boundary data cache for reverse geocoding
let boundaryCache = {
    daerah: null,
    parlimen: null,
    dun: null,
    loaded: false
};

// Function to get Kedah bounds - used to filter and center map
function getKedahBounds() {
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded yet');
        return null;
    }
    return L.latLngBounds(
        [5.0, 99.5],   // Southwest corner
        [6.5, 101.0]  // Northeast corner
    );
}

// Load boundary files for reverse geocoding
async function loadBoundariesForGeocoding() {
    if (boundaryCache.loaded) {
        console.log('Boundaries already loaded');
        return;
    }
    
    // Check if Turf.js is available and has required functions
    if (typeof turf === 'undefined' || typeof turf.point !== 'function' || typeof turf.booleanPointInPolygon !== 'function') {
        console.error('Turf.js not loaded or incomplete! Waiting for Turf.js...');
        // Wait a bit and try again
        await new Promise(resolve => setTimeout(resolve, 1000));
        if (typeof turf === 'undefined' || typeof turf.point !== 'function' || typeof turf.booleanPointInPolygon !== 'function') {
            console.error('Turf.js still not available after waiting');
            return;
        }
    }
    
    console.log('Loading boundaries for reverse geocoding...');
    
    try {
        // Load Daerah boundaries
        const daerahResponse = await fetch('api_get_geojson_by_kategori.php?kategori=daerah');
        if (daerahResponse.ok) {
            const daerahData = await daerahResponse.json();
            if (daerahData.features && daerahData.features.length > 0) {
                boundaryCache.daerah = daerahData;
                console.log('Loaded daerah boundaries:', daerahData.features.length, 'features');
            }
        }
        
        // Load Parlimen boundaries
        const parlimenResponse = await fetch('api_get_geojson_by_kategori.php?kategori=parlimen');
        if (parlimenResponse.ok) {
            const parlimenData = await parlimenResponse.json();
            if (parlimenData.error) {
                console.error('❌ Error loading parlimen boundaries:', parlimenData.error);
            } else if (parlimenData.features && parlimenData.features.length > 0) {
                boundaryCache.parlimen = parlimenData;
                console.log('✅ Loaded parlimen boundaries:', parlimenData.features.length, 'features');
            } else {
                console.warn('⚠️ Parlimen boundaries loaded but empty or invalid:', parlimenData);
            }
        } else {
            console.error('❌ Failed to load parlimen boundaries. Status:', parlimenResponse.status, parlimenResponse.statusText);
        }
        
        // Load DUN boundaries
        const dunResponse = await fetch('api_get_geojson_by_kategori.php?kategori=dun');
        if (dunResponse.ok) {
            const dunData = await dunResponse.json();
            if (dunData.error) {
                console.error('❌ Error loading DUN boundaries:', dunData.error);
            } else if (dunData.features && dunData.features.length > 0) {
                boundaryCache.dun = dunData;
                console.log('✅ Loaded DUN boundaries:', dunData.features.length, 'features');
            } else {
                console.warn('⚠️ DUN boundaries loaded but empty or invalid:', dunData);
            }
        } else {
            console.error('❌ Failed to load DUN boundaries. Status:', dunResponse.status, dunResponse.statusText);
        }
        
        boundaryCache.loaded = true;
        console.log('✅ All boundaries loaded for reverse geocoding');
        console.log('📊 Final boundary cache status:', {
            daerah: boundaryCache.daerah ? boundaryCache.daerah.features?.length || 0 + ' features' : 'missing',
            parlimen: boundaryCache.parlimen ? boundaryCache.parlimen.features?.length || 0 + ' features' : 'missing',
            dun: boundaryCache.dun ? boundaryCache.dun.features?.length || 0 + ' features' : 'missing'
        });
    } catch (error) {
        console.error('Error loading boundaries:', error);
    }
}

// Reverse geocode a feature using Turf.js - supports Point, Polygon, and LineString
function reverseGeocodePoint(feature, properties = {}) {
    // Check if Turf.js is available and has the required functions
    if (typeof turf === 'undefined' || typeof turf.point !== 'function' || typeof turf.booleanPointInPolygon !== 'function') {
        console.warn('Turf.js not available or incomplete for reverse geocoding');
        return properties;
    }
    
    // Check if feature has geometry
    if (!feature || !feature.geometry) {
        return properties;
    }
    
    let lng, lat;
    const geomType = feature.geometry.type;
    
    // Handle different geometry types
    if (geomType === 'Point') {
        // Point geometry - use coordinates directly
        [lng, lat] = feature.geometry.coordinates;
    } else if (geomType === 'Polygon' || geomType === 'MultiPolygon') {
        // Polygon geometry - calculate centroid or use first point
        try {
            // Try to use centroid if available
            if (typeof turf !== 'undefined' && typeof turf.centroid === 'function') {
                const centroid = turf.centroid(feature);
                [lng, lat] = centroid.geometry.coordinates;
            } else {
                // Fallback: calculate simple centroid (average of all coordinates)
                let allCoords = [];
                if (geomType === 'Polygon') {
                    // Get all coordinates from all rings
                    feature.geometry.coordinates.forEach(ring => {
                        allCoords = allCoords.concat(ring);
                    });
                } else {
                    // MultiPolygon - get all coordinates from all polygons
                    feature.geometry.coordinates.forEach(polygon => {
                        polygon.forEach(ring => {
                            allCoords = allCoords.concat(ring);
                        });
                    });
                }
                
                if (allCoords.length > 0) {
                    // Calculate average
                    let sumLng = 0, sumLat = 0;
                    allCoords.forEach(coord => {
                        sumLng += coord[0];
                        sumLat += coord[1];
                    });
                    lng = sumLng / allCoords.length;
                    lat = sumLat / allCoords.length;
                } else {
                    // Ultimate fallback: use first coordinate
                    const coords = geomType === 'Polygon' 
                        ? feature.geometry.coordinates[0]
                        : feature.geometry.coordinates[0][0];
                    if (coords && coords.length > 0) {
                        [lng, lat] = coords[0];
                    } else {
                        return properties;
                    }
                }
            }
        } catch (e) {
            console.warn('Error processing polygon for reverse geocoding:', e);
            // Fallback: use first coordinate
            const coords = geomType === 'Polygon' 
                ? feature.geometry.coordinates[0]
                : feature.geometry.coordinates[0][0];
            if (coords && coords.length > 0) {
                [lng, lat] = coords[0];
            } else {
                return properties;
            }
        }
    } else if (geomType === 'LineString' || geomType === 'MultiLineString') {
        // LineString geometry - use midpoint or first point
        try {
            let coords;
            if (geomType === 'LineString') {
                coords = feature.geometry.coordinates;
            } else {
                // MultiLineString - use first line
                coords = feature.geometry.coordinates[0];
            }
            
            if (coords && coords.length > 0) {
                if (coords.length >= 2 && typeof turf !== 'undefined' && typeof turf.midpoint === 'function') {
                    // Use midpoint if available
                    const start = turf.point(coords[0]);
                    const end = turf.point(coords[coords.length - 1]);
                    const midpoint = turf.midpoint(start, end);
                    [lng, lat] = midpoint.geometry.coordinates;
                } else {
                    // Fallback: use middle point or first point
                    const midIndex = Math.floor(coords.length / 2);
                    [lng, lat] = coords[midIndex];
                }
            } else {
                return properties;
            }
        } catch (e) {
            console.warn('Error processing linestring for reverse geocoding:', e);
            // Fallback: use first coordinate
            const coords = geomType === 'LineString'
                ? feature.geometry.coordinates
                : feature.geometry.coordinates[0];
            if (coords && coords.length > 0) {
                [lng, lat] = coords[0];
            } else {
                return properties;
            }
        }
    } else {
        // Unsupported geometry type
        console.warn('Unsupported geometry type for reverse geocoding:', geomType);
        return properties;
    }
    
    // Validate coordinates
    if (typeof lng !== 'number' || typeof lat !== 'number' || isNaN(lng) || isNaN(lat)) {
        console.warn('Invalid coordinates for reverse geocoding:', lng, lat);
        return properties;
    }
    
    // Safely create turf point
    let turfPoint;
    try {
        turfPoint = turf.point([lng, lat]);
        if (!turfPoint) {
            console.warn('Failed to create turf point');
            return properties;
        }
    } catch (e) {
        console.error('Error creating turf point:', e);
        return properties;
    }
    
    // Check if already has all required fields
    if (properties.DAERAH && properties.PARLIMEN && properties.DUN) {
        return properties; // Already complete
    }
    
    // Find Daerah
    if (!properties.DAERAH && boundaryCache.daerah && boundaryCache.daerah.features) {
        for (const feature of boundaryCache.daerah.features) {
            if (turf.booleanPointInPolygon(turfPoint, feature)) {
                const daerahName = feature.properties?.name || 
                                  feature.properties?.NAME_2 || 
                                  feature.properties?.adm2_name ||
                                  feature.properties?.DAERAH ||
                                  'Unknown';
                properties.DAERAH = daerahName;
                console.log('Found DAERAH:', daerahName, 'for point:', lat, lng);
                break;
            }
        }
    }
    
    // Find Parlimen
    if (!properties.PARLIMEN && boundaryCache.parlimen && boundaryCache.parlimen.features) {
        let foundParlimen = false;
        let checkedCount = 0;
        for (const feature of boundaryCache.parlimen.features) {
            checkedCount++;
            try {
                if (turf.booleanPointInPolygon(turfPoint, feature)) {
                    const parlimenName = feature.properties?.name || 
                                        feature.properties?.NAME_1 || 
                                        feature.properties?.parlimen ||
                                        feature.properties?.PARLIMEN ||
                                        'Unknown';
                    properties.PARLIMEN = parlimenName;
                    foundParlimen = true;
                    // Only log first few matches to avoid console spam
                    if (Math.random() < 0.01) { // Log 1% of matches
                        console.log('✅ Found PARLIMEN:', parlimenName, 'for point:', lat.toFixed(4), lng.toFixed(4));
                    }
                    break;
                }
            } catch (e) {
                // Log errors for first few features only
                if (checkedCount <= 3) {
                    console.warn('Error checking parlimen boundary', checkedCount, ':', e);
                }
            }
        }
        if (!foundParlimen) {
            // Log more details for failures to help debug
            const sampleBoundary = boundaryCache.parlimen.features[0];
            const sampleBounds = sampleBoundary?.geometry?.coordinates?.[0]?.[0];
            if (Math.random() < 0.05) { // Log 5% of failures for better debugging
                console.warn('⚠️ Could not find PARLIMEN for point:', lat.toFixed(4), lng.toFixed(4));
                console.warn('   - Checked', checkedCount, 'out of', boundaryCache.parlimen.features.length, 'boundaries');
                console.warn('   - Point coordinates:', lng, lat);
                if (sampleBounds) {
                    console.warn('   - Sample boundary first point:', sampleBounds[0], sampleBounds[1]);
                }
            }
        }
    } else if (!properties.PARLIMEN) {
        if (Math.random() < 0.05) { // Log 5% of cases
            console.warn('⚠️ Cannot find PARLIMEN: boundary cache not available');
            console.warn('   - boundaryCache.parlimen:', boundaryCache.parlimen ? 'exists' : 'null');
            console.warn('   - boundaryCache.parlimen.features:', boundaryCache.parlimen?.features ? boundaryCache.parlimen.features.length + ' features' : 'null');
        }
    }
    
    // Find DUN
    if (!properties.DUN && boundaryCache.dun && boundaryCache.dun.features) {
        let foundDUN = false;
        let checkedCount = 0;
        for (const feature of boundaryCache.dun.features) {
            checkedCount++;
            try {
                if (turf.booleanPointInPolygon(turfPoint, feature)) {
                    const dunName = feature.properties?.name || 
                                   feature.properties?.NAME_2 || 
                                   feature.properties?.dun ||
                                   feature.properties?.DUN ||
                                   'Unknown';
                    properties.DUN = dunName;
                    foundDUN = true;
                    // Only log first few matches to avoid console spam
                    if (Math.random() < 0.01) { // Log 1% of matches
                        console.log('✅ Found DUN:', dunName, 'for point:', lat.toFixed(4), lng.toFixed(4));
                    }
                    break;
                }
            } catch (e) {
                // Log errors for first few features only
                if (checkedCount <= 3) {
                    console.warn('Error checking DUN boundary', checkedCount, ':', e);
                }
            }
        }
        if (!foundDUN) {
            // Log more details for failures to help debug
            if (Math.random() < 0.05) { // Log 5% of failures for better debugging
                console.warn('⚠️ Could not find DUN for point:', lat.toFixed(4), lng.toFixed(4));
                console.warn('   - Checked', checkedCount, 'out of', boundaryCache.dun.features.length, 'boundaries');
                console.warn('   - Point coordinates:', lng, lat);
            }
        }
    } else if (!properties.DUN) {
        if (Math.random() < 0.05) { // Log 5% of cases
            console.warn('⚠️ Cannot find DUN: boundary cache not available');
            console.warn('   - boundaryCache.dun:', boundaryCache.dun ? 'exists' : 'null');
            console.warn('   - boundaryCache.dun.features:', boundaryCache.dun?.features ? boundaryCache.dun.features.length + ' features' : 'null');
        }
    }
    
    return properties;
}

// Enrich all features in a FeatureCollection with reverse geocoding
function enrichFeaturesWithGeocoding(featureCollection) {
    if (!featureCollection || !featureCollection.features) {
        return featureCollection;
    }
    
    // Check if Turf.js is available and has required functions
    if (typeof turf === 'undefined' || typeof turf.point !== 'function' || typeof turf.booleanPointInPolygon !== 'function') {
        console.warn('Turf.js not available or incomplete, skipping reverse geocoding');
        return featureCollection;
    }
    
    // Check if boundaries are loaded
    if (!boundaryCache.loaded) {
        console.warn('⚠️ Boundaries not loaded yet, attempting to load...');
        // Try to load boundaries if not loaded
        loadBoundariesForGeocoding().then(() => {
            console.log('✅ Boundaries loaded, but enrichment already skipped. Data may need to be reloaded.');
        }).catch(err => {
            console.error('❌ Error loading boundaries:', err);
        });
        return featureCollection;
    }
    
    // Check if boundary data is available
    const hasDaerah = boundaryCache.daerah && boundaryCache.daerah.features && boundaryCache.daerah.features.length > 0;
    const hasParlimen = boundaryCache.parlimen && boundaryCache.parlimen.features && boundaryCache.parlimen.features.length > 0;
    const hasDUN = boundaryCache.dun && boundaryCache.dun.features && boundaryCache.dun.features.length > 0;
    
    console.log('📊 Boundary cache status:', {
        daerah: hasDaerah ? boundaryCache.daerah.features.length + ' features' : 'missing',
        parlimen: hasParlimen ? boundaryCache.parlimen.features.length + ' features' : 'missing',
        dun: hasDUN ? boundaryCache.dun.features.length + ' features' : 'missing'
    });
    
    if (!hasDaerah && !hasParlimen && !hasDUN) {
        console.warn('⚠️ No boundary data available for reverse geocoding');
        // Try to load boundaries if not available
        loadBoundariesForGeocoding().then(() => {
            console.log('✅ Boundaries loaded, but enrichment already skipped. Data may need to be reloaded.');
        }).catch(err => {
            console.error('❌ Error loading boundaries:', err);
        });
        return featureCollection;
    }
    
    console.log('Enriching', featureCollection.features.length, 'features with reverse geocoding...');
    let enrichedCount = 0;
    let fieldsAdded = { DAERAH: 0, PARLIMEN: 0, DUN: 0 };
    let geometryTypeCount = { Point: 0, Polygon: 0, LineString: 0, Other: 0 };
    let processedCount = 0;
    let skippedCount = 0;
    let errorCount = 0;
    
    featureCollection.features.forEach((feature, index) => {
        processedCount++;
        // Process all geometry types: Point, Polygon, LineString
        if (feature.geometry && (feature.geometry.type === 'Point' || 
                                 feature.geometry.type === 'Polygon' || 
                                 feature.geometry.type === 'MultiPolygon' ||
                                 feature.geometry.type === 'LineString' || 
                                 feature.geometry.type === 'MultiLineString')) {
            const geomType = feature.geometry.type;
            if (geomType === 'Point') geometryTypeCount.Point++;
            else if (geomType === 'Polygon' || geomType === 'MultiPolygon') geometryTypeCount.Polygon++;
            else if (geomType === 'LineString' || geomType === 'MultiLineString') geometryTypeCount.LineString++;
            
            const originalProps = feature.properties || {};
            
            // Skip if already has all location data
            if (originalProps.DAERAH && originalProps.PARLIMEN && originalProps.DUN) {
                skippedCount++;
                return; // Already complete, skip processing
            }
            
            let enrichedProps;
            try {
                enrichedProps = reverseGeocodePoint(feature, { ...originalProps });
                
                // Check if any field was added
                if (!originalProps.DAERAH && enrichedProps.DAERAH) {
                    enrichedCount++;
                    fieldsAdded.DAERAH++;
                }
                if (!originalProps.PARLIMEN && enrichedProps.PARLIMEN) {
                    enrichedCount++;
                    fieldsAdded.PARLIMEN++;
                }
                if (!originalProps.DUN && enrichedProps.DUN) {
                    enrichedCount++;
                    fieldsAdded.DUN++;
                }
                
                // Update feature properties with enriched data
                feature.properties = enrichedProps;
            } catch (e) {
                errorCount++;
                // Only log first few errors to avoid console spam
                if (errorCount <= 5) {
                    console.error('Error in reverseGeocodePoint for feature', index, 'Type:', geomType, ':', e);
                }
                // Keep original props if error
            }
        } else {
            geometryTypeCount.Other++;
            skippedCount++;
            // Only log first few unsupported geometry types
            if (geometryTypeCount.Other <= 5) {
                console.warn('Feature', index, 'has unsupported geometry type:', feature.geometry?.type);
            }
        }
    });
    
    console.log('📊 Enrichment Summary:');
    console.log('   - Total features:', featureCollection.features.length);
    console.log('   - Processed:', processedCount);
    console.log('   - Skipped (already complete):', skippedCount);
    console.log('   - Errors:', errorCount);
    console.log('   - Geometry types:', geometryTypeCount);
    console.log('   - Fields added:', fieldsAdded);
    console.log('   - Total enrichments:', enrichedCount);
    
    // Count how many features have PARLIMEN and DUN after enrichment
    let featuresWithParlimen = 0;
    let featuresWithDUN = 0;
    let featuresWithBoth = 0;
    
    featureCollection.features.forEach(f => {
        const props = f.properties || {};
        if (props.PARLIMEN && props.PARLIMEN.trim() !== '') {
            featuresWithParlimen++;
        }
        if (props.DUN && props.DUN.trim() !== '') {
            featuresWithDUN++;
        }
        if (props.PARLIMEN && props.PARLIMEN.trim() !== '' && props.DUN && props.DUN.trim() !== '') {
            featuresWithBoth++;
        }
    });
    
    console.log('📊 After enrichment:', {
        total: featureCollection.features.length,
        withPARLIMEN: featuresWithParlimen,
        withDUN: featuresWithDUN,
        withBoth: featuresWithBoth,
        withoutPARLIMEN: featureCollection.features.length - featuresWithParlimen,
        withoutDUN: featureCollection.features.length - featuresWithDUN
    });
    
    if (featuresWithParlimen === 0) {
        console.warn('⚠️ WARNING: No features have PARLIMEN data after enrichment!');
        console.warn('💡 This may cause chart to show no data when selecting "Parlimen" distribution.');
    }
    if (featuresWithDUN === 0) {
        console.warn('⚠️ WARNING: No features have DUN data after enrichment!');
        console.warn('💡 This may cause chart to show no data when selecting "DUN" distribution.');
    }
    
    return featureCollection;
}

// loadCategory is already defined at the beginning of the script (line ~700)
// This duplicate definition is removed to prevent conflicts
// If you need to modify loadCategory, edit the definition at the beginning of the script

// This duplicate verification is removed - verification is done immediately after loadCategory definition

// Default card data untuk auto-load (Profil Perumahan KEDA)
const defaultCardData = <?php echo $defaultCard ? json_encode($defaultCard, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null'; ?>;
console.log('Default card data:', defaultCardData);

// Initialize card name on page load
if (defaultCardData && defaultCardData.name) {
    currentCardName = defaultCardData.name;
    // Update titles after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(updateMapAndChartTitles, 100);
        });
    } else {
        setTimeout(updateMapAndChartTitles, 100);
    }
}

// Initialize map with CartoDB Voyager tiles
function initMap() {
    console.log('=== initMap() called ===');
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Map element not found!');
        alert('Map element tidak dijumpai. Sila refresh page.');
        return;
    }
    console.log('Map element found:', mapElement);
    
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded!');
        alert('Leaflet library tidak dimuatkan. Sila refresh page.');
        return;
    }
    console.log('Leaflet library is loaded');
    
    console.log('Creating map...');
    // Use Kedah bounds for initial view
    const kedahBounds = getKedahBounds();
    if (!kedahBounds) {
        console.error('Cannot get Kedah bounds - Leaflet not loaded');
        return;
    }
    
    map = L.map('map', {
        center: kedahBounds.getCenter(), // Center of Kedah bounds
        zoom: 9,
        zoomControl: true,
        maxBounds: kedahBounds, // Restrict panning to Kedah bounds
        maxBoundsViscosity: 0.5 // Allow slight panning outside but snap back
    });
    
    // Fit Kedah to screen after map is initialized - center in middle of map container
    setTimeout(() => {
        map.invalidateSize(); // Ensure map size is correct
        map.fitBounds(kedahBounds, {
            padding: [20, 20],  // Padding to center Kedah nicely
            animate: false
        });
        console.log('Map centered on Kedah');
    }, 100);
    
    // CartoDB Voyager tiles
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);
    
    // Load Kedah state and district boundaries
    loadKedahBoundaries();
    
    // Load boundaries for reverse geocoding (daerah, parlimen, dun)
    // This must be done early to enable reverse geocoding when data is loaded
    loadBoundariesForGeocoding().then(() => {
        console.log('Boundaries loaded and ready for reverse geocoding');
    }).catch(err => {
        console.error('Error loading boundaries:', err);
    });
    
    // Load default boundary layer (Daerah) after map is ready
    setTimeout(() => {
        loadBoundaryLayerByType('DAERAH');
    }, 1000);
    
    console.log('✅ Map created successfully');
    console.log('Map object:', map);
    console.log('Map container:', map.getContainer());
}

// Load Kedah state and district boundaries from database
// Simplified version - only load essential boundaries to avoid timeout
async function loadKedahBoundaries() {
    const boundaryLayers = [];
    
    console.log('Loading boundaries (simplified mode)...');
    const startTime = performance.now();
    
    // Timeout function
    const fetchWithTimeout = (url, timeout = 10000) => {
        return Promise.race([
            fetch(url),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout')), timeout)
            )
        ]);
    };
    
    try {
        // Load state boundary (Negeri Kedah) from database ONLY - Essential
        console.log('Loading negeri boundary...');
        try {
            const stateResponse = await fetchWithTimeout('api_get_geojson_by_kategori.php?kategori=negeri', 10000);
        if (stateResponse.ok) {
            const stateData = await stateResponse.json();
            if (stateData.error) {
                console.warn('Error loading negeri from database:', stateData.error);
            } else if (stateData.features && stateData.features.length > 0) {
                negeriLayer = L.geoJSON(stateData, {
                    style: {
                        color: '#1E90FF', // Biru terang untuk sempadan negeri
                        weight: 4,
                        opacity: 1,
                        fillColor: '#87CEEB', // Biru cerah untuk fill negeri
                        fillOpacity: 0.25,
                        dashArray: '5, 5'
                    }
                }).addTo(map).bindTooltip('Negeri Kedah', { permanent: false, direction: 'center' });
                // Ensure negeri layer is at the bottom (z-index: 100)
                negeriLayer.setZIndexOffset(100);
                negeriLayer.bringToBack();
                boundaryLayers.push(negeriLayer);
                console.log('Loaded negeri boundary from database:', stateData.features.length, 'features');
            } else {
                console.warn('No negeri data in database');
            }
        } else {
            console.error('Failed to load negeri from database:', stateResponse.status);
        }
        } catch (err) {
            console.warn('Skipping negeri boundary (timeout or error):', err.message);
        }
        
        // Don't load daerah/parlimen/dun by default - will be loaded based on user selection
        console.log('Boundary layers will be loaded based on distribution selection');
        
        // Fit map to show all boundaries after loading
        if (boundaryLayers.length > 0) {
            const group = new L.featureGroup(boundaryLayers);
            const bounds = group.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds.pad(0.1));
            }
            const endTime = performance.now();
            const loadTime = ((endTime - startTime) / 1000).toFixed(2);
            console.log('Total boundary layers loaded:', boundaryLayers.length, 'in', loadTime, 'seconds');
        } else {
            console.warn('No boundary layers loaded!');
        }
        
        // Default card loading moved to end of function to ensure it always runs
    } catch (error) {
        console.error('Error loading boundaries:', error);
        // Continue anyway - boundaries are optional
        console.log('Continuing without boundaries...');
    }
    
    // Default card loading is now handled in initializeDashboard() to avoid conflicts
    console.log('Boundaries loading completed');
}

// Duplicate definition removed - using the one defined earlier at line 597

// Generate popup content with null solver
function generatePopupContent(props) {
    let html = '<div style="max-width: 300px; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">';
    html += '<h6 class="fw-bold mb-2" style="color: #4169E1;">Maklumat Terperinci</h6>';
    
    // Check for missing data
    const missingFields = [];
    if (!props.DAERAH || props.DAERAH === '') missingFields.push('DAERAH');
    if (!props.DUN || props.DUN === '') missingFields.push('DUN');
    if (!props.PARLIMEN || props.PARLIMEN === '') missingFields.push('PARLIMEN');
    
    // Show success badge if all fields are filled (especially if auto-enriched)
    if (missingFields.length === 0 && (props.DAERAH || props.PARLIMEN || props.DUN)) {
        html += '<div class="data-quality-badge complete mb-2" style="background: #d4edda; color: #155724; padding: 5px; border-radius: 4px;">';
        html += '<i class="fas fa-check-circle me-1"></i>Maklumat Lokasi Lengkap';
        html += '</div>';
    } else if (missingFields.length > 0) {
        html += '<div class="data-quality-badge missing mb-2" style="background: #fff3cd; color: #856404; padding: 5px; border-radius: 4px;">';
        html += '<i class="fas fa-exclamation-triangle me-1"></i>Perlu Kemaskini: ' + missingFields.join(', ');
        html += '</div>';
    }
    
    // Display location fields prominently if available
    if (props.DAERAH || props.PARLIMEN || props.DUN) {
        html += '<div style="background: #e7f3ff; padding: 8px; border-radius: 4px; margin-bottom: 10px;">';
        html += '<strong style="color: #0056b3;">Maklumat Lokasi:</strong><br>';
        if (props.DAERAH) {
            html += `<span style="color: #28a745;"><i class="fas fa-map-marker-alt me-1"></i><strong>Daerah:</strong> ${props.DAERAH}</span><br>`;
        }
        if (props.PARLIMEN) {
            html += `<span style="color: #007bff;"><i class="fas fa-landmark me-1"></i><strong>Parlimen:</strong> ${props.PARLIMEN}</span><br>`;
        }
        if (props.DUN) {
            html += `<span style="color: #6f42c1;"><i class="fas fa-building me-1"></i><strong>DUN:</strong> ${props.DUN}</span>`;
        }
        html += '</div>';
    }
    
    // Display all other properties
    Object.keys(props).forEach(key => {
        // Skip location fields as they're already displayed above
        if (key === 'DAERAH' || key === 'PARLIMEN' || key === 'DUN') {
            return;
        }
        
        if (props[key] && props[key] !== '') {
            let value = props[key];
            if (key === 'KOS' || key === 'Kadar_Sewaan') {
                value = 'RM ' + value;
            }
            html += `<p class="mb-1"><strong>${key}:</strong> ${value}</p>`;
        }
    });
    
    html += '</div>';
    return html;
}

// Function to show record details in modal - Make it globally accessible
// Define early to ensure it's available when needed
// Duplicate definition removed - using the one defined earlier

// Test function to verify modal works - can be called from console
window.testModal = function() {
    console.log('Testing modal display...');
    const testProps = {
        'Nama': 'Test Rekod',
        'DAERAH': 'Kubang Pasu',
        'PARLIMEN': 'Padang Terap',
        'DUN': 'Bukit Lada',
        'Keterangan': 'Ini adalah rekod test untuk memastikan modal berfungsi'
    };
    window.showRecordModal(testProps);
    console.log('Test modal called. If modal appears, everything is working!');
};

// Data Quality functions removed - panel no longer exists

// currentChartData already declared above - removed duplicate

// Update distribution chart based on selected type - Make it globally accessible
window.updateDistributionChart = async function updateDistributionChart() {
    console.log('updateDistributionChart called');
    console.log('currentChartData:', currentChartData ? `${currentChartData.features?.length || 0} features` : 'null');
    
    if (!currentChartData) {
        console.warn('No data available for chart');
        // Try to use currentData as fallback
        if (currentData) {
            console.log('Using currentData as fallback for chart');
            currentChartData = currentData;
        } else {
            console.error('No chart data available and no fallback data');
            return;
        }
    }
    
    // Validate currentChartData
    if (!currentChartData.features || !Array.isArray(currentChartData.features)) {
        console.error('Invalid currentChartData:', currentChartData);
        return;
    }
    
    const distributionType = document.getElementById('distributionType')?.value || 'DAERAH';
    console.log('Updating chart with distribution type:', distributionType);
    
    // Ensure boundaries are loaded before updating chart
    // Check if specific boundary cache is needed and not loaded
    const needsParlimen = (distributionType === 'PARLIMEN' && (!boundaryCache.parlimen || !boundaryCache.parlimen.features || boundaryCache.parlimen.features.length === 0));
    const needsDUN = (distributionType === 'DUN' && (!boundaryCache.dun || !boundaryCache.dun.features || boundaryCache.dun.features.length === 0));
    const needsDaerah = (distributionType === 'DAERAH' && (!boundaryCache.daerah || !boundaryCache.daerah.features || boundaryCache.daerah.features.length === 0));
    
    if (!boundaryCache.loaded || needsParlimen || needsDUN || needsDaerah) {
        console.log('Loading boundaries before updating chart...');
        console.log('Needs Parlimen:', needsParlimen, 'Needs DUN:', needsDUN, 'Needs Daerah:', needsDaerah);
        await loadBoundariesForGeocoding();
        
        // Verify boundaries are loaded
        if (distributionType === 'PARLIMEN') {
            console.log('📊 Parlimen boundaries loaded:', boundaryCache.parlimen ? (boundaryCache.parlimen.features?.length || 0) + ' features' : 'not loaded');
        } else if (distributionType === 'DUN') {
            console.log('📊 DUN boundaries loaded:', boundaryCache.dun ? (boundaryCache.dun.features?.length || 0) + ' features' : 'not loaded');
        } else if (distributionType === 'DAERAH') {
            console.log('📊 Daerah boundaries loaded:', boundaryCache.daerah ? (boundaryCache.daerah.features?.length || 0) + ' features' : 'not loaded');
        }
    }
    
    // Check if data has the required field, if not, try to enrich it
    if (currentChartData && currentChartData.features && currentChartData.features.length > 0) {
        const sampleProps = currentChartData.features[0].properties || {};
        const hasField = sampleProps[distributionType] !== undefined && sampleProps[distributionType] !== null && sampleProps[distributionType] !== '';
        
        // Count how many records have this field
        const recordsWithField = currentChartData.features.filter(f => {
            const props = f.properties || {};
            const value = props[distributionType];
            return value !== undefined && value !== null && value !== '';
        }).length;
        
        console.log('📊 Records with', distributionType + ':', recordsWithField, 'out of', currentChartData.features.length);
        
        // If less than 10% of records have the field, try to enrich data
        if (recordsWithField < currentChartData.features.length * 0.1 && boundaryCache.loaded) {
            console.log('⚠️ Less than 10% of records have', distributionType, '- attempting to enrich data...');
            const enrichedData = enrichFeaturesWithGeocoding(currentChartData);
            
            // Count again after enrichment
            const recordsWithFieldAfter = enrichedData.features.filter(f => {
                const props = f.properties || {};
                const value = props[distributionType];
                return value !== undefined && value !== null && value !== '';
            }).length;
            
            console.log('📊 After enrichment - Records with', distributionType + ':', recordsWithFieldAfter, 'out of', enrichedData.features.length);
            
            // Update currentChartData with enriched data
            currentChartData = enrichedData;
        }
    }
    
    // Update chart directly with currentChartData and distributionType
    // This ensures chart is updated immediately when card is clicked
    console.log('📊 Updating chart directly with distributionType:', distributionType);
    console.log('📊 Current chart data features:', currentChartData.features.length);
    
    // Debug: Check if data has the required field
    if (currentChartData.features && currentChartData.features.length > 0) {
        const sampleProps = currentChartData.features[0].properties || {};
        const hasField = sampleProps[distributionType] !== undefined && sampleProps[distributionType] !== null && sampleProps[distributionType] !== '';
        console.log('📊 Sample record has', distributionType + ':', hasField, sampleProps[distributionType] || 'N/A');
        
        // Count how many records have this field
        const recordsWithField = currentChartData.features.filter(f => {
            const props = f.properties || {};
            const value = props[distributionType];
            return value !== undefined && value !== null && value !== '';
        }).length;
        console.log('📊 Records with', distributionType + ':', recordsWithField, 'out of', currentChartData.features.length);
    }
    
    // Ensure Chart.js is loaded before updating
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js is not loaded! Waiting for Chart.js...');
        // Wait a bit and retry
        setTimeout(() => {
            if (typeof Chart !== 'undefined') {
                console.log('✅ Chart.js loaded, updating chart now...');
                updateDaerahChart(currentChartData, distributionType);
            } else {
                console.error('❌ Chart.js still not loaded after wait');
            }
        }, 500);
    } else {
        // Chart.js is loaded, update chart immediately
        updateDaerahChart(currentChartData, distributionType);
    }
    
    // Load and display corresponding boundary layer on map
    await loadBoundaryLayerByType(distributionType);
    
    console.log('✅ Chart update completed');
}

// Update Doughnut Chart by Daerah/Parlimen/DUN - Same style as dashboard_staf
function updateDaerahChart(data, fieldType = 'DAERAH') {
    console.log('📊 updateDaerahChart called with fieldType:', fieldType);
    console.log('📊 Data features count:', data?.features?.length || 0);
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js is not loaded! Cannot update chart.');
        const errorMsg = document.getElementById('chartErrorMessage');
        if (errorMsg) {
            errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Chart.js tidak dimuatkan</p>';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    let count = {}; // Changed from const to let to allow reassignment
    let totalRecords = 0;
    let recordsWithData = 0;
    let recordsWithoutData = 0;
    
    // Validate data input
    if (!data || !data.features || !Array.isArray(data.features)) {
        console.error('❌ Invalid data provided to updateDaerahChart:', data);
        const errorMsg = document.getElementById('chartErrorMessage');
        if (errorMsg) {
            errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Data tidak sah untuk graf</p>';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    if (data.features.length === 0) {
        console.warn('⚠️ No features in data for chart');
        const errorMsg = document.getElementById('chartErrorMessage');
        if (errorMsg) {
            errorMsg.innerHTML = '<i class="fas fa-info-circle fa-2x mb-2"></i><p>Tiada data untuk dipaparkan</p>';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    // Count records by field type
    // Use a temporary map to deduplicate case-insensitive keys
    const countMap = new Map(); // Map<uppercase_key, {original: first_occurrence, count: number}>
    
    data.features.forEach(feature => {
        if (!feature || !feature.properties) {
            console.warn('Feature missing properties:', feature);
            return;
        }
        
        totalRecords++;
        const value = feature.properties?.[fieldType];
        
        // Check if value is empty, null, undefined, or just whitespace
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            recordsWithoutData++;
            const tiadaDataKey = 'Tiada Data';
            if (!countMap.has(tiadaDataKey)) {
                countMap.set(tiadaDataKey, { original: tiadaDataKey, count: 0 });
            }
            countMap.get(tiadaDataKey).count++;
        } else {
            recordsWithData++;
            // Normalize value (trim whitespace)
            const normalizedValue = typeof value === 'string' ? value.trim() : String(value);
            
            // Use case-insensitive key to prevent duplicates like "KULIM" and "kulim"
            const countKey = normalizedValue.toUpperCase();
            
            // Store first occurrence as canonical name, accumulate count
            if (!countMap.has(countKey)) {
                countMap.set(countKey, { original: normalizedValue, count: 0 });
            }
            countMap.get(countKey).count++;
        }
    });
    
    // Convert map to count object using canonical names
    count = {};
    countMap.forEach((value, key) => {
        count[value.original] = value.count;
    });
    
    // Log summary
    console.log('📊 Chart data summary for', fieldType + ':');
    console.log('  - Total records:', totalRecords);
    console.log('  - Records with data:', recordsWithData);
    console.log('  - Records without data:', recordsWithoutData);
    console.log('  - Unique values found (after case-insensitive deduplication):', Object.keys(count).filter(k => k !== 'Tiada Data').length);
    console.log('  - Sample count keys:', Object.keys(count).filter(k => k !== 'Tiada Data').slice(0, 10));
    
    // If no records at all, return early
    if (totalRecords === 0) {
        console.warn('No records found in data');
        return;
    }
    
    // Get all boundaries from cache to ensure all appear in chart
    let allBoundaries = [];
    console.log('📊 Checking boundary cache for', fieldType);
    console.log('📊 boundaryCache.loaded:', boundaryCache.loaded);
    console.log('📊 boundaryCache.daerah:', boundaryCache.daerah ? (boundaryCache.daerah.features?.length || 0) + ' features' : 'missing');
    console.log('📊 boundaryCache.parlimen:', boundaryCache.parlimen ? (boundaryCache.parlimen.features?.length || 0) + ' features' : 'missing');
    console.log('📊 boundaryCache.dun:', boundaryCache.dun ? (boundaryCache.dun.features?.length || 0) + ' features' : 'missing');
    
    // Debug: Check what field values exist in data
    const uniqueFieldValues = new Set();
    data.features.forEach(f => {
        const value = f.properties?.[fieldType];
        if (value && value.trim() !== '') {
            uniqueFieldValues.add(value.trim());
        }
    });
    console.log('📊 Unique', fieldType, 'values in data:', uniqueFieldValues.size, Array.from(uniqueFieldValues).slice(0, 10));
    
    if (fieldType === 'DAERAH' && boundaryCache.daerah && boundaryCache.daerah.features) {
        const rawBoundaries = boundaryCache.daerah.features.map(f => {
            const name = f.properties?.name || 
                        f.properties?.NAME_2 || 
                        f.properties?.adm2_name ||
                        f.properties?.DAERAH ||
                        f.properties?.NAMA_DAERAH ||
                        null;
            return name ? name.trim() : null;
        }).filter(n => n !== null);
        
        // Deduplicate boundaries (case-insensitive) - keep first occurrence
        const seenBoundaries = new Map(); // Map<uppercase, original>
        rawBoundaries.forEach(boundary => {
            const upperKey = boundary.toUpperCase();
            if (!seenBoundaries.has(upperKey)) {
                seenBoundaries.set(upperKey, boundary);
            }
        });
        allBoundaries = Array.from(seenBoundaries.values());
        console.log('📊 Found', rawBoundaries.length, 'daerah boundaries in cache (raw),', allBoundaries.length, 'after deduplication');
    } else if (fieldType === 'PARLIMEN' && boundaryCache.parlimen && boundaryCache.parlimen.features) {
        const rawBoundaries = boundaryCache.parlimen.features.map(f => {
            const name = f.properties?.name || 
                        f.properties?.NAME_1 || 
                        f.properties?.parlimen ||
                        f.properties?.PARLIMEN ||
                        f.properties?.NAMA_PARLIMEN ||
                        null;
            return name ? name.trim() : null;
        }).filter(n => n !== null);
        
        // Deduplicate boundaries (case-insensitive) - keep first occurrence
        const seenBoundaries = new Map(); // Map<uppercase, original>
        rawBoundaries.forEach(boundary => {
            const upperKey = boundary.toUpperCase();
            if (!seenBoundaries.has(upperKey)) {
                seenBoundaries.set(upperKey, boundary);
            }
        });
        allBoundaries = Array.from(seenBoundaries.values());
        console.log('📊 Found', rawBoundaries.length, 'parlimen boundaries in cache (raw),', allBoundaries.length, 'after deduplication');
    } else if (fieldType === 'DUN' && boundaryCache.dun && boundaryCache.dun.features) {
        const rawBoundaries = boundaryCache.dun.features.map(f => {
            const name = f.properties?.name || 
                        f.properties?.NAME_2 || 
                        f.properties?.dun ||
                        f.properties?.DUN ||
                        f.properties?.NAMA_DUN ||
                        null;
            return name ? name.trim() : null;
        }).filter(n => n !== null);
        
        // Deduplicate boundaries (case-insensitive) - keep first occurrence
        const seenBoundaries = new Map(); // Map<uppercase, original>
        rawBoundaries.forEach(boundary => {
            const upperKey = boundary.toUpperCase();
            if (!seenBoundaries.has(upperKey)) {
                seenBoundaries.set(upperKey, boundary);
            }
        });
        allBoundaries = Array.from(seenBoundaries.values());
        console.log('📊 Found', rawBoundaries.length, 'dun boundaries in cache (raw),', allBoundaries.length, 'after deduplication');
    } else {
        console.warn('⚠️ Boundary cache not available for', fieldType, '- will show only boundaries with records');
        // Try to load boundaries if not loaded
        if (!boundaryCache.loaded || 
            (fieldType === 'PARLIMEN' && (!boundaryCache.parlimen || !boundaryCache.parlimen.features)) ||
            (fieldType === 'DUN' && (!boundaryCache.dun || !boundaryCache.dun.features)) ||
            (fieldType === 'DAERAH' && (!boundaryCache.daerah || !boundaryCache.daerah.features))) {
            console.log('🔄 Attempting to load boundaries for', fieldType);
            // Load boundaries asynchronously (but don't wait - show chart with available data)
            loadBoundariesForGeocoding().then(() => {
                console.log('✅ Boundaries loaded, but chart already rendered. User can refresh chart if needed.');
            }).catch(err => {
                console.error('❌ Error loading boundaries:', err);
            });
        }
    }
    
    // Normalize existing counts to match boundary names (case-insensitive matching)
    // Only include boundaries that have records (count > 0) AND exist in boundary cache
    // Declare boundaryCaseMap and normalizedCount outside the if block to ensure they're accessible
    let boundaryCaseMap = new Map(); // Map<uppercase, original>
    let normalizedCount = {}; // Declare outside if block to ensure it's accessible everywhere
    
    if (allBoundaries.length > 0) {
        // Reset normalizedCount for this iteration
        normalizedCount = {};
        const boundaryMap = {}; // Map to normalize names (case-insensitive)
        const unmatchedNames = []; // Track names that don't match any boundary
        
        // Helper function to normalize names for matching (remove spaces, slashes, special chars)
        const normalizeName = (name) => {
            if (!name) return '';
            return name
                .trim()
                .toUpperCase()
                .replace(/["']/g, '') // Remove quotes
                .replace(/\s*\/\s*/g, '/') // Normalize slash with spaces: "KULIM/ BANDAR" -> "KULIM/BANDAR"
                .replace(/\s+/g, ' ') // Normalize multiple spaces to single space
                .replace(/\//g, ' ') // Replace slash with space
                .replace(/\./g, '') // Remove dots
                .replace(/-/g, ' ') // Replace dash with space
                .replace(/\s+/g, '') // Remove all spaces
                .replace(/[^A-Z0-9]/g, ''); // Remove all non-alphanumeric
        };
        
        // Helper function to expand common abbreviations
        const expandAbbreviations = (name) => {
            if (!name) return [name];
            const upper = name.toUpperCase();
            const variations = [name, upper];
            
            // Common abbreviations mapping
            const abbrevMap = {
                'BKT': 'BUKIT',
                'B': 'BUKIT',
                'SG': 'SUNGAI',
                'S': 'SUNGAI',
                'TG': 'TANJUNG',
                'T': 'TANJUNG',
                'KT': 'KETIL',
                'K': 'KETIL',
                'BB': 'BANDAR BAHARU',
                'B.BAHARU': 'BANDAR BAHARU',
                'BBAHARU': 'BANDAR BAHARU'
            };
            
            // Try to expand abbreviations
            let expanded = upper;
            for (const [abbrev, full] of Object.entries(abbrevMap)) {
                // Replace standalone abbreviations (with word boundaries)
                expanded = expanded.replace(new RegExp(`\\b${abbrev}\\b`, 'g'), full);
            }
            if (expanded !== upper) {
                variations.push(expanded);
            }
            
            return variations;
        };
        
        // Manual mapping for known variations (case-insensitive)
        const manualMapping = {
            'PARLIMEN': {
                // Langkawi - P.002
                'LANGKAWI': 'P.002 LANGKAWI',
                // Kubang Pasu - P.003
                'KUBANG PASU': 'P.003 KUBANG PASU',
                // Padang Terap - P.004
                'PADANG TERAP': 'P.004 PADANG TERAP',
                // Pokok Sena - P.005
                'POKOK SENA': 'P.005 POKOK SENA',
                // Alor Setar - P.006
                'ALOR SETAR': 'P.006 ALOR SETAR',
                // Kuala Muda - P.007
                'KUALA MUDA': 'P.007 KUALA MUDA',
                'KUALA MUDA/YAN': 'P.007 KUALA MUDA',
                // Pendang - P.008
                'PENDANG': 'P.008 PENDANG',
                // Jerai - P.009
                'JERAI': 'P.009 JERAI',
                // Sik - P.010
                'SIK': 'P.010 SIK',
                // Baling - P.011
                'BALING': 'P.011 BALING',
                'BALIIG': 'P.011 BALING', // Typo fix
                // Padang Serai - P.012
                'PADANG SERAI': 'P.012 PADANG SERAI',
                // Kulim/Bandar Baharu - P.013
                'KULIM': 'P.013 KULIM/BANDAR BAHARU',
                'KULIM/B.BAHARU': 'P.013 KULIM/BANDAR BAHARU',
                'KULIM / B.BAHARU': 'P.013 KULIM/BANDAR BAHARU',
                'KULIM / BANDAR BAHARU': 'P.013 KULIM/BANDAR BAHARU',
                'KULIM/BANDAR BAHARU': 'P.013 KULIM/BANDAR BAHARU',
                '"KULIM/ BANDAR BAHARU"': 'P.013 KULIM/BANDAR BAHARU', // Handle quotes and space after slash
                'KULIM/ BANDAR BAHARU': 'P.013 KULIM/BANDAR BAHARU', // Handle space after slash
                'BANDAR BAHARU': 'P.013 KULIM/BANDAR BAHARU',
                'BANDAR BHARU': 'P.013 KULIM/BANDAR BAHARU',
                // Sungai Petani - P.014
                'SUNGAI PETANI': 'P.014 SUNGAI PETANI',
                'SG PETANI': 'P.014 SUNGAI PETANI',
                // Merbok - P.015
                'MERBOK': 'P.015 MERBOK',
                // Yan - P.016
                'YAN': 'P.016 YAN',
                // Jerlun - P.017 (if exists)
                'JERLUN': 'P.017 JERLUN',
                // Jitra (should be in Kubang Pasu parlimen)
                'JITRA': 'P.003 KUBANG PASU',
                // Serdang - P.018 (if exists)
                'SERDANG': 'P.018 SERDANG'
            },
            'DUN': {
                // Langkawi
                'AYER HANGAT': 'AYER HANGAT',
                'KUAH': 'KUAH',
                // Kubang Pasu
                'JITRA': 'JITRA',
                'BUKIT KAYU HITAM': 'BUKIT KAYU HITAM',
                'BKT KAYU HITAM': 'BUKIT KAYU HITAM',
                'BKT KAYU HITAM': 'BUKIT KAYU HITAM',
                // Padang Terap
                'BELANTEK': 'BELANTEK',
                'BELANTIK': 'BELANTEK',
                'BUKIT LADA': 'BUKIT LADA',
                'BKT LADA': 'BUKIT LADA',
                'BUKIT LADA': 'BUKIT LADA',
                'BUKIT LADA': 'BUKIT LADA',
                // Kuala Muda
                'TANJUNG DAWAI': 'TANJUNG DAWAI',
                'TG DAWAI': 'TANJUNG DAWAI',
                'TANJONG DAWAI': 'TANJUNG DAWAI',
                'SUNGAI TIANG': 'SUNGAI TIANG',
                'SG TIANG': 'SUNGAI TIANG',
                'SG. TIANG': 'SUNGAI TIANG',
                'SUNGAI TIANG': 'SUNGAI TIANG',
                'SUNGAI TIANG': 'SUNGAI TIANG',
                'PANTAI MERDEKA': 'PANTAI MERDEKA',
                'PANTAI MERDEKA': 'PANTAI MERDEKA',
                'MERBOK': 'MERBOK',
                'SUNGAI LIMAU': 'SUNGAI LIMAU',
                'BAYU': 'BAYU',
                'GUAR CHEMPEDAK': 'GUAR CHEMPEDAK',
                'TOKAI': 'TOKAI',
                'SUNGAI PETANI': 'SUNGAI PETANI',
                'BAKAR ARANG': 'BAKAR ARANG',
                'SIDAM': 'SIDAM',
                'GURUN': 'GURUN',
                'DERGA': 'DERGA',
                // Pendang
                'BUKIT PINANG': 'BUKIT PINANG',
                'BKT PIANG': 'BUKIT PINANG',
                'BUKIT PINANG': 'BUKIT PINANG',
                // Bandar Baharu
                'B.BAHARU': 'BANDAR BAHARU',
                'BANDAR BAHARU': 'BANDAR BAHARU',
                'BANDAR BAHARU': 'BANDAR BAHARU',
                'KULIM/BANDAR BAHARU': 'BANDAR BAHARU', // For DUN, split compound names
                // Padang Terap
                'KUALA NERANG': 'KUALA NERANG',
                'PEDU': 'PEDU',
                // Kulim
                'KULIM': 'KULIM',
                'MERBAU PULAS': 'MERBAU PULAS',
                'LUNAS': 'LUNAS',
                // Sik
                'JENERI': 'JENERI',
                'JENIANG': 'JENIANG',
                // Baling
                'BUKIT SELAMBAU': 'BUKIT SELAMBAU',
                'KUALA KETIL': 'KUALA KETIL',
                'KUALA KETIL': 'KUALA KETIL',
                'KUALA KETIL': 'KUALA KETIL',
                'KUPANG': 'KUPANG',
                // Padang Serai
                'PADANG SERAI': 'PADANG SERAI'
            }
        };
        
        // Create map of all boundaries (normalized) - but don't initialize with 0
        // Also create a case-insensitive map to prevent duplicates
        // boundaryCaseMap already declared above
        boundaryCaseMap.clear(); // Clear any existing entries
        allBoundaries.forEach(boundary => {
            const normalized = boundary.trim();
            const normalizedKey = normalizeName(normalized);
            const upperKey = normalized.toUpperCase();
            
            // Store case-insensitive mapping (keep first occurrence)
            if (!boundaryCaseMap.has(upperKey)) {
                boundaryCaseMap.set(upperKey, normalized);
            }
            
            // Store both normalized key (for matching) and original (for display)
            if (!boundaryMap[normalizedKey]) {
                boundaryMap[normalizedKey] = []; // Array to handle multiple variations
            }
            boundaryMap[normalizedKey].push(normalized);
            // Don't initialize with 0 - only add if there are records
        });
        
        console.log('📊 boundaryCaseMap initialized with', boundaryCaseMap.size, 'entries');
        
        // Log expected count for validation
        const expectedCount = fieldType === 'DAERAH' ? 12 : fieldType === 'PARLIMEN' ? 15 : 36;
        if (allBoundaries.length !== expectedCount) {
            console.warn(`⚠️ Expected ${expectedCount} ${fieldType} boundaries, but found ${allBoundaries.length} in cache`);
        }
        
        // Log boundary names for debugging
        console.log('📊 Boundary names in cache:', allBoundaries.slice(0, 10), '... (showing first 10)');
        console.log('📊 Total boundary names in cache:', allBoundaries.length);
        console.log('📊 All boundary names (for matching reference):', allBoundaries);
        
        // Also log normalized versions for debugging
        const normalizedBoundaries = allBoundaries.map(b => normalizeName(b));
        console.log('📊 Normalized boundary names:', normalizedBoundaries.slice(0, 10), '... (showing first 10)');
        
        // Merge existing counts with boundary names (improved matching)
        Object.keys(count).forEach(key => {
            if (key === 'Tiada Data') {
                normalizedCount[key] = count[key];
            } else {
                const keyTrimmed = key.trim();
                let keyToMatch = keyTrimmed;
                
                // Step 1: Try manual mapping first (case-insensitive)
                if (manualMapping[fieldType]) {
                    const keyUpper = keyTrimmed.toUpperCase().replace(/["']/g, ''); // Remove quotes
                    // Try exact match first
                    if (manualMapping[fieldType][keyUpper]) {
                        keyToMatch = manualMapping[fieldType][keyUpper];
                        console.log(`🔧 Manual mapping: "${keyTrimmed}" -> "${keyToMatch}"`);
                    } else {
                        // Try to find similar key (fuzzy match in mapping)
                        for (const [mapKey, mapValue] of Object.entries(manualMapping[fieldType])) {
                            if (keyUpper === mapKey || keyUpper.includes(mapKey) || mapKey.includes(keyUpper)) {
                                keyToMatch = mapValue;
                                console.log(`🔧 Manual mapping (fuzzy): "${keyTrimmed}" -> "${keyToMatch}"`);
                                break;
                            }
                        }
                    }
                }
                
                // Step 1.5: If manual mapping returns a value with "P.XXX" prefix, try to match with or without prefix
                // This handles cases where boundary cache might have "P.002 LANGKAWI" or just "LANGKAWI"
                if (keyToMatch && keyToMatch.startsWith('P.')) {
                    // Try to match with prefix first, if fails, try without prefix
                    const nameWithoutPrefix = keyToMatch.replace(/^P\.\d+\s+/i, '').trim();
                    // We'll try both in the matching step below
                }
                
                // Step 2: Try abbreviation expansion
                let expandedVariations = expandAbbreviations(keyToMatch);
                
                // Step 2.5: If keyToMatch has "P.XXX" prefix, also try without prefix
                if (keyToMatch && /^P\.\d+/i.test(keyToMatch)) {
                    const nameWithoutPrefix = keyToMatch.replace(/^P\.\d+\s+/i, '').trim();
                    expandedVariations.push(nameWithoutPrefix);
                    // Also try variations of name without prefix
                    expandedVariations = expandedVariations.concat(expandAbbreviations(nameWithoutPrefix));
                }
                
                // Step 3: Try to find matching boundary
                let matched = false;
                let matchedBoundary = null;
                
                // Try each variation
                for (const variation of expandedVariations) {
                    const variationNormalized = normalizeName(variation);
                    const variationUpper = variation.trim().toUpperCase();
                    
                    // First try exact normalized match
                    if (boundaryMap[variationNormalized]) {
                        matchedBoundary = boundaryMap[variationNormalized][0]; // Use first variation
                        matched = true;
                        break;
                    }
                    
                    // Try case-insensitive match with all boundaries
                    for (const [boundaryNormalized, boundaryOriginals] of Object.entries(boundaryMap)) {
                        // Check if any variation matches
                        for (const boundaryOriginal of boundaryOriginals) {
                            const boundaryUpper = boundaryOriginal.toUpperCase();
                            
                            // Exact case-insensitive match
                            if (variationUpper === boundaryUpper) {
                                matchedBoundary = boundaryOriginal;
                                matched = true;
                                break;
                            }
                            
                            // Try match without "P.XXX" prefix (if boundary has prefix)
                            const boundaryWithoutPrefix = boundaryUpper.replace(/^P\.\d+\s+/, '').trim();
                            if (variationUpper === boundaryWithoutPrefix || boundaryWithoutPrefix === variationUpper) {
                                matchedBoundary = boundaryOriginal;
                                matched = true;
                                break;
                            }
                            
                            // Try normalized match
                            const boundaryNormalizedCheck = normalizeName(boundaryOriginal);
                            if (variationNormalized === boundaryNormalizedCheck) {
                                matchedBoundary = boundaryOriginal;
                                matched = true;
                                break;
                            }
                        }
                        if (matched) break;
                    }
                    
                    if (matched) break;
                }
                
                // Step 4: Try fuzzy matching (contains/partial match) as last resort
                if (!matched) {
                    const keyUpper = keyToMatch.toUpperCase();
                    for (const [boundaryNormalized, boundaryOriginals] of Object.entries(boundaryMap)) {
                        for (const boundaryOriginal of boundaryOriginals) {
                            const boundaryUpper = boundaryOriginal.toUpperCase();
                            // Check if one contains the other (for compound names)
                            if (keyUpper.includes(boundaryUpper) || boundaryUpper.includes(keyUpper)) {
                                // Only match if length is similar (avoid false matches)
                                const lengthDiff = Math.abs(keyUpper.length - boundaryUpper.length);
                                if (lengthDiff <= 5) { // Allow up to 5 character difference
                                    matchedBoundary = boundaryOriginal;
                                    matched = true;
                                    console.log(`🔍 Fuzzy matched "${keyTrimmed}" -> "${matchedBoundary}"`);
                                    break;
                                }
                            }
                        }
                        if (matched) break;
                    }
                }
                
                // If matched, add to normalized count
                // Use case-insensitive key to prevent duplicates
                if (matched && matchedBoundary && count[key] > 0) {
                    // Normalize matchedBoundary to case-insensitive key to prevent duplicates
                    const matchedBoundaryUpper = matchedBoundary.trim().toUpperCase();
                    
                    // Find canonical name from boundaryCaseMap if available
                    // If boundaryCaseMap exists and has the key, use it; otherwise use matchedBoundary as is
                    let canonicalBoundary = matchedBoundary;
                    if (typeof boundaryCaseMap !== 'undefined' && boundaryCaseMap && boundaryCaseMap.has && boundaryCaseMap.has(matchedBoundaryUpper)) {
                        canonicalBoundary = boundaryCaseMap.get(matchedBoundaryUpper);
                    }
                    
                    // Use canonical name as key (case-insensitive deduplication)
                    // Ensure we have a valid key
                    if (canonicalBoundary && canonicalBoundary.trim() !== '') {
                        normalizedCount[canonicalBoundary] = (normalizedCount[canonicalBoundary] || 0) + count[key];
                        
                        // Log first few matches for debugging
                        if (Object.keys(normalizedCount).length <= 10) {
                            console.log(`✅ Matched "${keyTrimmed}" -> "${canonicalBoundary}" (original: "${matchedBoundary}")`);
                        }
                    } else {
                        console.warn(`⚠️ Invalid canonicalBoundary for "${keyTrimmed}" -> "${matchedBoundary}"`);
                    }
                } else if (!matched && count[key] > 0) {
                    // If no match found, log as unmatched (don't add to chart)
                    unmatchedNames.push(keyTrimmed);
                    // Only log first 15 unmatched to avoid console spam
                    if (unmatchedNames.length <= 15) {
                        console.warn(`⚠️ ${fieldType} name "${keyTrimmed}" not found in boundary cache. Skipping from chart.`);
                    }
                }
            }
        });
        
        // Replace count with normalized count (only boundaries with records AND in cache)
        // First, check for duplicates in normalizedCount (case-insensitive) and merge them
        const duplicateCheck = new Map(); // Map<uppercase, {key: original, count: number}>
        const duplicateKeys = [];
        
        console.log(`📊 Processing ${Object.keys(normalizedCount).length} keys from normalizedCount into duplicateCheck`);
        
        Object.keys(normalizedCount).forEach(key => {
            if (key === 'Tiada Data') {
                // Keep "Tiada Data" as is
                if (!duplicateCheck.has('TIADA DATA')) {
                    duplicateCheck.set('TIADA DATA', { key: key, count: 0 });
                }
                duplicateCheck.get('TIADA DATA').count += normalizedCount[key];
            } else {
                const normalizedKey = key.trim().toUpperCase();
                if (duplicateCheck.has(normalizedKey)) {
                    duplicateKeys.push(key);
                    console.warn(`⚠️ Duplicate detected in normalizedCount: "${key}" and "${duplicateCheck.get(normalizedKey).key}"`);
                }
                
                if (!duplicateCheck.has(normalizedKey)) {
                    duplicateCheck.set(normalizedKey, { key: key, count: 0 });
                }
                duplicateCheck.get(normalizedKey).count += normalizedCount[key];
            }
        });
        
        console.log(`📊 duplicateCheck now has ${duplicateCheck.size} entries`);
        console.log(`📊 duplicateCheck sample:`, Array.from(duplicateCheck.entries()).slice(0, 5).map(([k, v]) => `${k} -> ${v.key}: ${v.count}`));
        
        if (duplicateKeys.length > 0) {
            console.error(`❌ Found ${duplicateKeys.length} duplicate keys in normalizedCount! Merging them...`);
        }
        
        // Always rebuild normalizedCount from duplicateCheck to ensure no duplicates
        normalizedCount = {};
        duplicateCheck.forEach((value, normalizedKey) => {
            normalizedCount[value.key] = value.count;
        });
        
        // Log before clearing count
        console.log(`📊 Before assignment - normalizedCount has ${Object.keys(normalizedCount).length} keys`);
        console.log(`📊 normalizedCount sample:`, Object.keys(normalizedCount).slice(0, 5).map(k => `${k}: ${normalizedCount[k]}`));
        console.log(`📊 duplicateCheck size: ${duplicateCheck.size}`);
        
        // Safety check: Don't clear count if normalizedCount is empty
        if (Object.keys(normalizedCount).length === 0) {
            console.error(`❌ CRITICAL: normalizedCount is empty after deduplication! Keeping original count.`);
            console.error(`❌ Original count had ${Object.keys(count).length} keys`);
            console.error(`❌ duplicateCheck has ${duplicateCheck.size} entries`);
            console.error(`❌ This means all data was lost during deduplication!`);
            // Don't clear count - keep original data
            // But log what we have in original count
            console.log(`📊 Original count keys:`, Object.keys(count).slice(0, 10));
        } else {
            // Clear and assign only if we have data
            const originalCountKeys = Object.keys(count).length;
            Object.keys(count).forEach(key => delete count[key]);
            Object.assign(count, normalizedCount);
            console.log(`✅ Count object updated: ${originalCountKeys} keys -> ${Object.keys(count).length} keys from normalizedCount`);
            console.log(`✅ Count object sample after update:`, Object.entries(count).slice(0, 5).map(([k, v]) => `${k}: ${v}`));
        }
        
        const normalizedCountResult = Object.keys(normalizedCount).filter(k => k !== 'Tiada Data' && normalizedCount[k] > 0).length;
        console.log(`✅ Normalized boundaries for ${fieldType}: ${normalizedCountResult} boundaries with records (out of ${allBoundaries.length} total in cache)`);
        
        // Final duplicate check BEFORE assigning to count - log if any duplicates found
        const finalCheck = new Set();
        const finalDuplicates = [];
        Object.keys(normalizedCount).filter(k => k !== 'Tiada Data').forEach(key => {
            const upperKey = key.trim().toUpperCase();
            if (finalCheck.has(upperKey)) {
                finalDuplicates.push(key);
            } else {
                finalCheck.add(upperKey);
            }
        });
        if (finalDuplicates.length > 0) {
            console.error(`❌ CRITICAL: Found ${finalDuplicates.length} duplicate keys in normalizedCount before assignment!`, finalDuplicates);
        } else {
            console.log(`✅ No duplicates found in normalizedCount - all ${normalizedCountResult} boundaries are unique`);
        }
        
        console.log(`📊 Final count keys (all):`, Object.keys(normalizedCount).filter(k => k !== 'Tiada Data'));
        console.log(`📊 Final count keys (first 15):`, Object.keys(normalizedCount).filter(k => k !== 'Tiada Data').slice(0, 15));
        console.log(`📊 Final count object after assignment:`, Object.keys(count).filter(k => k !== 'Tiada Data').slice(0, 10));
        
        if (unmatchedNames.length > 0) {
            console.warn(`⚠️ ${unmatchedNames.length} ${fieldType} names not matched:`, unmatchedNames);
        }
        
        // Validate final count
        if (fieldType === 'PARLIMEN' && normalizedCountResult > 15) {
            console.error(`❌ ERROR: Found ${normalizedCountResult} parlimen in chart, but maximum should be 15!`);
            console.error('This indicates a normalization issue. Please check boundary cache and data.');
        }
    } else {
        // If no boundaries in cache, filter out entries with 0 count
        Object.keys(count).forEach(key => {
            if (count[key] === 0 && key !== 'Tiada Data') {
                delete count[key];
            }
        });
        console.warn(`⚠️ No boundaries found in cache for ${fieldType}. Showing only boundaries with records, but may not be accurate.`);
    }
    
    // Log count object before filtering
    console.log(`📊 Count object before filtering: ${Object.keys(count).length} keys`);
    console.log(`📊 Count object entries before filtering:`, Object.entries(count).slice(0, 10).map(([k, v]) => `${k}: ${v}`));
    
    // Filter out entries with 0 count (except "Tiada Data" which should only show if there are records without data)
    Object.keys(count).forEach(key => {
        if (count[key] === 0 && key !== 'Tiada Data') {
            // Remove boundaries with 0 count, but keep "Tiada Data" if it exists
            delete count[key];
        }
    });
    
    console.log(`📊 Count object after filtering: ${Object.keys(count).length} keys`);
    
    // Sort labels - put "Tiada Data" at the end if it exists
    // Sort boundaries alphabetically, then by count descending
    const sortedEntries = Object.entries(count).sort((a, b) => {
        if (a[0] === 'Tiada Data') return 1;
        if (b[0] === 'Tiada Data') return -1;
        
        // If both have same count, sort alphabetically
        if (a[1] === b[1]) {
            return a[0].localeCompare(b[0]);
        }
        
        // Sort by count descending
        return b[1] - a[1];
    });
    
    // Filter out any entries with 0 count after sorting (safety check)
    // BUT keep "Tiada Data" even if count is 0 (to show that there are records but no location data)
    const filteredEntries = sortedEntries.filter(([key, value]) => {
        if (key === 'Tiada Data') {
            // Keep "Tiada Data" if there are records without data
            return recordsWithoutData > 0;
        }
        return value > 0;
    });
    
    // Check if we have any data to display
    console.log(`📊 Checking data for chart - filteredEntries.length: ${filteredEntries.length}`);
    console.log(`📊 Count object keys: ${Object.keys(count).length}`);
    console.log(`📊 Count object sample:`, Object.entries(count).slice(0, 5));
    console.log(`📊 Total records: ${totalRecords}, With data: ${recordsWithData}, Without data: ${recordsWithoutData}`);
    
    if (filteredEntries.length === 0) {
        console.warn(`⚠️ No data available for ${fieldType} chart`);
        console.warn(`⚠️ Count object has ${Object.keys(count).length} keys`);
        console.warn(`⚠️ Sorted entries: ${sortedEntries.length}, Filtered entries: ${filteredEntries.length}`);
        
        const ctx = document.getElementById('daerahChart');
        const errorMsg = document.getElementById('chartErrorMessage');
        
        // Show helpful message instead of just hiding chart
        if (errorMsg) {
            if (totalRecords === 0) {
                errorMsg.innerHTML = '<i class="fas fa-info-circle fa-2x mb-2"></i><p>Tiada rekod untuk kategori ini</p>';
            } else if (recordsWithoutData === totalRecords) {
                const fieldName = fieldType === 'PARLIMEN' ? 'Parlimen' : (fieldType === 'DUN' ? 'DUN' : 'Daerah');
                errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p><strong>Semua rekod tiada maklumat ' + fieldName + '</strong><br><br>Total rekod: ' + totalRecords + '<br>Rekod dengan data: 0<br>Rekod tanpa data: ' + recordsWithoutData + '<br><br>Sila jalankan reverse geocoding untuk mengisi maklumat lokasi.<br>Atau semak sama ada data properties mengandungi field "' + fieldType + '".</p>';
            } else {
                errorMsg.innerHTML = '<i class="fas fa-info-circle fa-2x mb-2"></i><p>Tiada data untuk dipaparkan dalam graf<br><br>Count object: ' + Object.keys(count).length + ' keys<br>Sorted entries: ' + sortedEntries.length + '<br>Filtered entries: ' + filteredEntries.length + '</p>';
            }
            errorMsg.style.display = 'block';
        }
        
        if (ctx && daerahChart) {
            daerahChart.destroy();
            daerahChart = null;
        }
        return;
    }
    
    const labels = filteredEntries.map(([key]) => key.toUpperCase());
    const values = filteredEntries.map(([, value]) => value);
    
    // Ensure we have labels and values before creating chart
    if (labels.length === 0 || values.length === 0) {
        console.warn('No data to display in chart');
        const ctx = document.getElementById('daerahChart');
        if (ctx && daerahChart) {
            daerahChart.destroy();
            daerahChart = null;
        }
        return;
    }
    
    // Count boundaries with records (excluding "TIADA DATA")
    const chartBoundariesCount = labels.filter(l => l !== 'TIADA DATA').length;
    console.log(`📊 Chart updated: ${chartBoundariesCount} ${fieldType} with records (only showing boundaries that have data)`);
    
    const ctx = document.getElementById('daerahChart');
    
    if (!ctx) {
        console.error('❌ Chart canvas element not found!');
        console.error('Looking for element with id="daerahChart"');
        // Try to find the canvas element
        const canvasElements = document.querySelectorAll('canvas');
        console.log('Found canvas elements:', canvasElements.length);
        canvasElements.forEach((el, idx) => {
            console.log(`Canvas ${idx}: id="${el.id}", parent="${el.parentElement?.id || 'none'}"`);
        });
        
        // Show error message
        const errorMsg = document.getElementById('chartErrorMessage');
        if (errorMsg) {
            errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Canvas element tidak dijumpai</p>';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    console.log('✅ Canvas element found:', ctx);
    console.log('✅ Canvas parent:', ctx.parentElement?.id || 'none');
    
    // Hide loading and error messages
    let loadingMsg = document.getElementById('chartLoadingMessage');
    const errorMsg = document.getElementById('chartErrorMessage');
    if (loadingMsg) loadingMsg.style.display = 'none';
    if (errorMsg) {
        errorMsg.style.display = 'none';
        errorMsg.innerHTML = ''; // Clear previous error message
    }
    
    console.log('✅ Chart canvas element found:', ctx);
    console.log('✅ Chart.js available:', typeof Chart !== 'undefined');
    
    if (daerahChart) {
        console.log('Destroying existing chart');
        daerahChart.destroy();
        daerahChart = null;
    }
    
    // Get 2D context for gradient creation
    const chartCtx = ctx.getContext('2d');
    
    // Helper function to adjust color brightness
    function adjustBrightness(color, percent) {
        const num = parseInt(color.replace("#",""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    }
    
    // Extended color palette - 40 warna berbeza untuk setiap daerah
    const colorPalette = [
        '#4169E1', '#10B981', '#06B6D4', '#F59E0B', '#EF4444', 
        '#7c3aed', '#EA3680', '#EE8AF8', '#808080', '#FF6B6B',
        '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA15E',
        '#BC6C25', '#FFD60A', '#003566', '#606C38', '#283618',
        '#F8B195', '#F67280', '#C06C84', '#6C567B', '#D4AF37',
        '#9B59B6', '#3498DB', '#1ABC9C', '#E67E22', '#E74C3C',
        '#16A085', '#27AE60', '#2980B9', '#8E44AD', '#F39C12',
        '#C0392B', '#D35400', '#F1C40F', '#2ECC71', '#34495E',
        '#95A5A6', '#E91E63', '#00BCD4', '#FF9800', '#795548'
    ];
    
    // Create unique colors untuk setiap daerah - setiap satu dapat warna berbeza
    const colors = [];
    for (let i = 0; i < labels.length; i++) {
        const baseColor = colorPalette[i % colorPalette.length];
        
        // Create gradient untuk setiap warna dengan darker shade di bottom
        try {
            const g = chartCtx.createLinearGradient(0, 0, 0, 400);
            g.addColorStop(0, baseColor);
            const darkerColor = adjustBrightness(baseColor, -20);
            g.addColorStop(1, darkerColor);
            colors.push(g);
        } catch (e) {
            // Fallback to solid color jika gradient fails
            colors.push(baseColor);
        }
    }
    
    // Update chart title based on field type
    const fieldNames = {
        'DAERAH': 'Daerah',
        'PARLIMEN': 'Parlimen',
        'DUN': 'DUN'
    };
    const fieldName = fieldNames[fieldType] || 'Daerah';
    
    // Use grey color for "Tiada Data" segment (only if it exists and has count > 0)
    const finalColors = labels.map((label, index) => {
        if (label === 'TIADA DATA') {
            return '#95A5A6'; // Grey color for missing data
        }
        // All other labels should have count > 0 (already filtered)
        return colors[index] || colorPalette[index % colorPalette.length];
    });
    
    // Add subtitle with statistics
    const subtitleText = `Total: ${totalRecords} | Dengan Data: ${recordsWithData} | Tiada Data: ${recordsWithoutData}`;
    
    // Log information about chart segments (only boundaries with records)
    const finalBoundariesCount = labels.filter(l => l !== 'TIADA DATA').length;
    console.log(`📊 Chart showing ${finalBoundariesCount} ${fieldName} with records`);
    
    // Log warning if there are records without data
    if (recordsWithoutData > 0) {
        console.warn(`⚠️ ${recordsWithoutData} rekod (${((recordsWithoutData/totalRecords)*100).toFixed(1)}%) tiada maklumat ${fieldName}.`);
        console.warn('💡 Sila jalankan batch_spatial_update_geojson.php untuk memproses data yang belum ditag.');
    }
    
    // Store fieldType and labels for onClick handler
    const chartFieldType = fieldType;
    const chartLabels = labels;
    
    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not available! Cannot create chart.');
        const errorMsg = document.getElementById('chartErrorMessage');
        if (errorMsg) {
            errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Chart.js tidak dimuatkan</p>';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    console.log('📊 Creating chart with', labels.length, 'labels and', values.length, 'values');
    console.log('📊 Labels:', labels.slice(0, 10), '... (showing first 10)');
    console.log('📊 Values:', values.slice(0, 10), '... (showing first 10)');
    console.log('📊 Total values sum:', values.reduce((a, b) => a + b, 0));
    
    // Validate data before creating chart
    if (values.length === 0 || values.every(v => v === 0)) {
        console.error('❌ CRITICAL: All values are zero or empty! Cannot create chart.');
        const errorMsg = document.getElementById('chartErrorMessage');
        if (errorMsg) {
            errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Semua nilai adalah kosong. Tiada data untuk dipaparkan.</p>';
            errorMsg.style.display = 'block';
        }
        return;
    }
    
    // Show loading message (reuse variable declared earlier)
    loadingMsg = document.getElementById('chartLoadingMessage');
    if (loadingMsg) loadingMsg.style.display = 'block';
    
    try {
        console.log('📊 Attempting to create Chart.js instance...');
        daerahChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: finalColors,
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    const elementIndex = elements[0].index;
                    const selectedLabel = chartLabels[elementIndex];
                    
                    // Don't filter if "TIADA DATA" is clicked
                    if (selectedLabel === 'TIADA DATA') {
                        console.log('Tiada Data clicked, showing all records');
                        // Show all records
                        filterDataByBoundary(null, chartFieldType);
                        return;
                    }
                    
                    console.log('Chart segment clicked:', selectedLabel, 'Field:', chartFieldType);
                    
                    // Filter data by selected boundary
                    filterDataByBoundary(selectedLabel, chartFieldType);
                }
            },
            plugins: {
                title: {
                    display: false
                },
                subtitle: {
                    display: true,
                    text: subtitleText,
                    font: { size: 11, style: 'italic' },
                    color: recordsWithoutData > 0 ? '#dc3545' : '#28a745',
                    padding: { bottom: 10 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 11 },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                const dataset = data.datasets[0];
                                const total = dataset.data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => {
                                    const value = dataset.data[i];
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return {
                                        text: `${label}: ${value} rekod (${percentage}%)`,
                                        fillStyle: dataset.backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        },
                        onClick: function(e, legendItem) {
                            const index = legendItem.index;
                            const selectedLabel = chartLabels[index];
                            
                            if (selectedLabel === 'TIADA DATA') {
                                console.log('Tiada Data clicked in legend, showing all records');
                                filterDataByBoundary(null, chartFieldType);
                                return;
                            }
                            
                            console.log('Legend clicked:', selectedLabel, 'Field:', chartFieldType);
                            filterDataByBoundary(selectedLabel, chartFieldType);
                        }
                    },
                    onHover: function(e, legendItem) {
                        e.native.target.style.cursor = 'pointer';
                    },
                    onLeave: function(e, legendItem) {
                        e.native.target.style.cursor = 'default';
                    }
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        title: function(context) {
                            return context[0].label || '';
                        },
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return [
                                `Bilangan Rekod: ${value}`,
                                `Peratus: ${percentage}%`
                            ];
                        },
                        footer: function(tooltipItems) {
                            const total = tooltipItems[0].dataset.data.reduce((a, b) => a + b, 0);
                            return `Jumlah: ${total} rekod`;
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#fff',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    boxPadding: 6
                }
            }
        }
    });
    
    // Hide loading message
    const loadingMsg2 = document.getElementById('chartLoadingMessage');
    if (loadingMsg2) loadingMsg2.style.display = 'none';
    
    console.log('✅ Chart created successfully:', daerahChart ? 'Yes' : 'No');
    if (daerahChart) {
        console.log('✅ Chart type:', daerahChart.config.type);
        console.log('✅ Chart data points:', daerahChart.data.datasets[0]?.data?.length || 0);
        console.log('✅ Chart labels:', daerahChart.data.labels?.length || 0);
        // Force chart to update/redraw
        daerahChart.update('active');
        daerahChart.update();
        console.log('Chart updated/redrawn');
    }
    } catch (chartError) {
        console.error('❌ Error creating chart:', chartError);
        console.error('❌ Chart error details:', {
            labels: labels.length,
            values: values.length,
            ctx: ctx ? 'Found' : 'Not found',
            Chart: typeof Chart !== 'undefined' ? 'Available' : 'Not available',
            error: chartError.message,
            stack: chartError.stack
        });
        console.error('❌ Chart error stack:', chartError.stack);
        console.error('❌ Labels sample:', labels.slice(0, 5));
        console.error('❌ Values sample:', values.slice(0, 5));
        
        // Show error message
        const loadingMsg3 = document.getElementById('chartLoadingMessage');
        const errorMsg = document.getElementById('chartErrorMessage');
        if (loadingMsg3) loadingMsg3.style.display = 'none';
        if (errorMsg) {
            let errorDetails = chartError.message || 'Tidak dapat membuat graf';
            if (chartError.stack) {
                errorDetails += '<br><small>' + chartError.stack.split('\n').slice(0, 2).join('<br>') + '</small>';
            }
            errorMsg.innerHTML = `<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p><strong>Ralat semasa mencipta graf:</strong><br>${errorDetails}</p>`;
            errorMsg.style.display = 'block';
        }
    }
}

// Function to filter data by boundary (daerah/parlimen/dun) and update chart
// When user clicks on a segment, show breakdown by next level
function filterDataByBoundary(selectedValue, fieldType) {
    if (!currentChartData || !currentChartData.features) {
        console.warn('No chart data available for filtering');
        return;
    }
    
    // Get all boundaries from cache for matching
    let allBoundaries = [];
    if (fieldType === 'DAERAH' && boundaryCache.daerah && boundaryCache.daerah.features) {
        allBoundaries = boundaryCache.daerah.features.map(f => {
            const name = f.properties?.name || 
                        f.properties?.NAME_2 || 
                        f.properties?.adm2_name ||
                        f.properties?.DAERAH ||
                        f.properties?.NAMA_DAERAH ||
                        null;
            return name ? name.trim() : null;
        }).filter(n => n !== null);
    } else if (fieldType === 'PARLIMEN' && boundaryCache.parlimen && boundaryCache.parlimen.features) {
        allBoundaries = boundaryCache.parlimen.features.map(f => {
            const name = f.properties?.name || 
                        f.properties?.NAME_1 || 
                        f.properties?.parlimen ||
                        f.properties?.PARLIMEN ||
                        f.properties?.NAMA_PARLIMEN ||
                        null;
            return name ? name.trim() : null;
        }).filter(n => n !== null);
    } else if (fieldType === 'DUN' && boundaryCache.dun && boundaryCache.dun.features) {
        allBoundaries = boundaryCache.dun.features.map(f => {
            const name = f.properties?.name || 
                        f.properties?.NAME_2 || 
                        f.properties?.dun ||
                        f.properties?.DUN ||
                        f.properties?.NAMA_DUN ||
                        null;
            return name ? name.trim() : null;
        }).filter(n => n !== null);
    }
    
    console.log('Filtering data by:', selectedValue, 'Field:', fieldType);
    console.log('Available boundaries in cache:', allBoundaries.length);
    
    // If no value selected, show all data with current field type
    let filteredData = currentChartData;
    let nextFieldType = fieldType;
    
    if (selectedValue && selectedValue !== 'TIADA DATA') {
        // Normalize selected value (remove extra spaces, trim)
        // Chart labels are in UPPERCASE, so we need to match with original case from cache
        const normalizedValue = selectedValue.trim();
        
        console.log('🔍 Filtering by:', normalizedValue, 'in field:', fieldType);
        console.log('🔍 Total features to filter:', currentChartData.features.length);
        
        // First, try to find the original boundary name from cache (chart uses uppercase, but properties might use original case)
        let matchedBoundaryName = normalizedValue;
        if (allBoundaries.length > 0) {
            for (const boundary of allBoundaries) {
                if (boundary.toUpperCase() === normalizedValue.toUpperCase()) {
                    matchedBoundaryName = boundary; // Use original case from cache
                    console.log('✅ Found matching boundary in cache:', matchedBoundaryName, '(chart label:', normalizedValue + ')');
                    break;
                }
            }
        }
        
        // Filter features that match the selected boundary
        filteredData = {
            type: 'FeatureCollection',
            features: currentChartData.features.filter(feature => {
                const props = feature.properties || {};
                const fieldValue = props[fieldType];
                
                if (!fieldValue) {
                    return false;
                }
                
                // Normalize comparison (case-insensitive, trim spaces)
                const fieldValueStr = String(fieldValue).trim();
                const normalizedValueStr = normalizedValue.trim();
                const matchedBoundaryStr = matchedBoundaryName.trim();
                
                // Try exact match (case-insensitive) with chart label
                if (fieldValueStr.toUpperCase() === normalizedValueStr.toUpperCase()) {
                    return true;
                }
                
                // Try exact match (case-insensitive) with original boundary name from cache
                if (fieldValueStr.toUpperCase() === matchedBoundaryStr.toUpperCase()) {
                    return true;
                }
                
                // Try exact match with original case (in case property has exact match)
                if (fieldValueStr === matchedBoundaryStr) {
                    return true;
                }
                
                return false;
            })
        };
        
        console.log('✅ Filtered to', filteredData.features.length, 'records matching', normalizedValue);
        
        // Debug: Show sample of filtered records
        if (filteredData.features.length > 0) {
            console.log('📋 Sample filtered record:', {
                name: filteredData.features[0].properties?.name || 'N/A',
                [fieldType]: filteredData.features[0].properties?.[fieldType] || 'N/A'
            });
        } else {
            console.warn('⚠️ No records matched filter. Checking why...');
            // Debug: Show sample of all records to see what values they have
            if (currentChartData.features.length > 0) {
                const sampleProps = currentChartData.features[0].properties || {};
                console.log('📋 Sample record properties:', {
                    name: sampleProps.name || 'N/A',
                    [fieldType]: sampleProps[fieldType] || 'N/A',
                    allProps: Object.keys(sampleProps).filter(k => k.includes(fieldType) || k.includes(fieldType.toLowerCase()))
                });
                console.log('📋 Looking for:', normalizedValue, '(or cache name:', matchedBoundaryName + ')');
                console.log('📋 Available values in data:', 
                    [...new Set(currentChartData.features
                        .map(f => (f.properties?.[fieldType] || '').toString().trim())
                        .filter(v => v !== ''))].slice(0, 10)
                );
            }
        }
        
        // If no records found, try partial matching (contains)
        if (filteredData.features.length === 0) {
            console.log('⚠️ Trying partial match as fallback...');
            filteredData = {
                type: 'FeatureCollection',
                features: currentChartData.features.filter(feature => {
                    const props = feature.properties || {};
                    const fieldValue = props[fieldType];
                    
                    if (!fieldValue) {
                        return false;
                    }
                    
                    const fieldValueStr = String(fieldValue).trim().toUpperCase();
                    const searchValue = normalizedValue.toUpperCase();
                    const matchedBoundaryUpper = matchedBoundaryName.toUpperCase();
                    
                    // Try exact match first
                    if (fieldValueStr === searchValue || fieldValueStr === matchedBoundaryUpper) {
                        return true;
                    }
                    
                    // Try contains match (in case of extra spaces or formatting)
                    if (fieldValueStr.includes(searchValue) || searchValue.includes(fieldValueStr) ||
                        fieldValueStr.includes(matchedBoundaryUpper) || matchedBoundaryUpper.includes(fieldValueStr)) {
                        return true;
                    }
                    
                    return false;
                })
            };
            console.log('✅ Partial match found', filteredData.features.length, 'records');
        }
        
        // Determine next level for breakdown
        if (fieldType === 'DAERAH') {
            // If clicked on daerah, show breakdown by PARLIMEN
            nextFieldType = 'PARLIMEN';
        } else if (fieldType === 'PARLIMEN') {
            // If clicked on parlimen, show breakdown by DUN
            nextFieldType = 'DUN';
        } else if (fieldType === 'DUN') {
            // If clicked on DUN, keep showing DUN (or could show by category)
            nextFieldType = 'DUN';
        }
    } else {
        console.log('Showing all records');
    }
    
    // Update chart with filtered data using next level
    updateDaerahChart(filteredData, nextFieldType);
    
    // Update map to show only filtered records
    if (filteredData.features.length > 0) {
        displayDataOnMap(filteredData);
    } else {
        // Clear map if no records
        if (currentLayer) {
            map.removeLayer(currentLayer);
            currentLayer = null;
        }
        console.warn('No records to display after filtering');
    }
}

// Function to display data on map (extracted from loadCategory for reuse)
function displayDataOnMap(data) {
    if (!map || !data || !data.features) {
        console.error('Invalid parameters for displayDataOnMap');
        return;
    }
    
    // Clear previous layer
    if (currentLayer) {
        try {
            map.removeLayer(currentLayer);
        } catch (e) {
            console.warn('Error removing previous layer:', e);
        }
    }
    
    // Filter valid features
    const validFeatures = data.features.filter(f => {
        if (!f.geometry) return false;
        
        // Basic validation - can add more if needed
        return true;
    });
    
    if (validFeatures.length === 0) {
        console.warn('No valid features to display');
        return;
    }
    
    // Create GeoJSON layer
    const validGeoJSON = {
        type: 'FeatureCollection',
        features: validFeatures
    };
    
    try {
        currentLayer = L.geoJSON(validGeoJSON, {
            pointToLayer: function(feature, latlng) {
                if (feature.geometry && feature.geometry.type === 'Point') {
                    const marker = L.circleMarker(latlng, {
                        radius: 10,
                        fillColor: '#FF0000',
                        color: '#FFFFFF',
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 1.0
                    });
                    
                    marker.feature = feature;
                    marker.featureProps = feature.properties || {};
                    
                    // Add event handlers
                    marker.on('click', function(e) {
                        const props = this.featureProps || {};
                        if (window.showRecordModal) {
                            window.showRecordModal(props);
                        }
                    });
                    
                    return marker;
                }
                return null;
            },
            style: function(feature) {
                if (feature.geometry && feature.geometry.type === 'Point') {
                    return {};
                }
                return {
                    color: '#FF0000',
                    weight: 3,
                    opacity: 0.7,
                    fillColor: '#FF9999',
                    fillOpacity: 0.4
                };
            },
            onEachFeature: function(feature, layer) {
                if (!layer) return;
                
                const props = feature.properties || {};
                layer.featureProps = props;
                layer.feature = feature;
                
                const popupContent = generatePopupContent(props);
                layer.bindPopup(popupContent);
                
                layer.on('click', function(e) {
                    const layerProps = this.featureProps || {};
                    if (window.showRecordModal) {
                        window.showRecordModal(layerProps);
                    }
                });
            }
        });
        
        currentLayer.addTo(map);
        
        // Ensure rekod layer is on top (z-index: 500) - above all boundary layers
        // Check if setZIndexOffset method exists before calling it
        if (currentLayer && typeof currentLayer.setZIndexOffset === 'function') {
            currentLayer.setZIndexOffset(500);
        } else {
            console.warn('⚠️ setZIndexOffset not available, using bringToFront instead');
            if (currentLayer && typeof currentLayer.bringToFront === 'function') {
                currentLayer.bringToFront();
            }
        }
        
        // Ensure correct layer order
        ensureLayerOrder();
        
        // Fit map to bounds
        if (validFeatures.length > 0) {
            try {
                const bounds = currentLayer.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [20, 20] });
                }
            } catch (e) {
                console.warn('Error fitting bounds:', e);
            }
        }
        
        console.log('Displayed', validFeatures.length, 'records on map');
    } catch (e) {
        console.error('Error displaying data on map:', e);
    }
}

// Function to ensure correct layer order: Negeri < Daerah < Parlimen < DUN < Rekod
function ensureLayerOrder() {
    if (!map) return;
    
    console.log('🔧 Ensuring correct layer order...');
    
    // Bring layers to correct order (bottom to top):
    // 1. Negeri (bottom) - z-index: 100
    if (negeriLayer && map.hasLayer(negeriLayer)) {
        if (typeof negeriLayer.setZIndexOffset === 'function') {
            negeriLayer.setZIndexOffset(100);
        }
        if (typeof negeriLayer.bringToBack === 'function') {
            negeriLayer.bringToBack();
        }
        console.log('✅ Negeri layer set to back (z-index: 100)');
    }
    
    // 2. Current boundary layer (daerah/parlimen/dun) - above negeri, z-index: 200-400
    if (currentBoundaryLayer && map.hasLayer(currentBoundaryLayer)) {
        const zIndex = 200; // Base z-index for boundaries
        if (typeof currentBoundaryLayer.setZIndexOffset === 'function') {
            currentBoundaryLayer.setZIndexOffset(zIndex);
        }
        if (negeriLayer && map.hasLayer(negeriLayer)) {
            currentBoundaryLayer.bringToFront();
            negeriLayer.bringToBack();
        } else {
            currentBoundaryLayer.bringToBack();
        }
        console.log('✅ Boundary layer set (z-index:', zIndex + ')');
    }
    
    // 3. Rekod layer (top) - above all boundaries, z-index: 500
    if (currentLayer && map.hasLayer(currentLayer)) {
        // Check if setZIndexOffset method exists before calling it
        // Note: setZIndexOffset is only available for LayerGroup, not for individual layers
        // For FeatureGroup or GeoJSON layers, we use bringToFront instead
        if (currentLayer.setZIndexOffset && typeof currentLayer.setZIndexOffset === 'function') {
            try {
                currentLayer.setZIndexOffset(500);
            } catch (e) {
                // Silently ignore if method doesn't work for this layer type
                // This is normal for some Leaflet layer types
            }
        }
        // Always try bringToFront as it works for most layer types
        if (currentLayer.bringToFront && typeof currentLayer.bringToFront === 'function') {
            currentLayer.bringToFront();
        }
        
        // Also ensure all markers within the layer are on top
        if (currentLayer.eachLayer) {
            currentLayer.eachLayer(function(layer) {
                if (layer instanceof L.CircleMarker || layer instanceof L.Marker) {
                    if (layer.bringToFront) {
                        layer.bringToFront();
                    }
                }
            });
        }
        console.log('✅ Rekod layer set to front (z-index: 500)');
    }
    
    console.log('✅ Layer order ensured');
}

// Global variables for records management
let recordsData = [];
let filteredRecords = [];
let sortedRecords = [];
let currentPage = 1;
let recordsPerPage = 20;
let sortColumn = null;
let sortDirection = 'asc';
let filters = {};

// Function to display all records in detail section
function displayRecords(data) {
    if (!data || !data.features || data.features.length === 0) {
        const container = document.getElementById('recordsContainer');
        if (container) {
            container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-info-circle fa-2x mb-3"></i><p>Tiada rekod untuk dipaparkan</p></div>';
        }
        const countBadge = document.getElementById('recordsCount');
        if (countBadge) {
            countBadge.textContent = '0 rekod';
        }
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.style.display = 'none';
        }
        return;
    }
    
    const container = document.getElementById('recordsContainer');
    const countBadge = document.getElementById('recordsCount');
    const exportBtn = document.getElementById('exportBtn');
    const recordsTitle = document.getElementById('recordsTitle');
    
    if (!container) {
        console.error('Records container not found');
        return;
    }
    
    // Store data globally
    recordsData = data.features.map((f, idx) => ({
        ...f,
        _originalIndex: idx
    }));
    window.currentRecordsData = data;
    
    // Reset filters and sorting
    filters = {};
    sortColumn = null;
    sortDirection = 'asc';
    currentPage = 1;
    
    // Update title
    if (recordsTitle) {
        recordsTitle.innerHTML = `<i class="fas fa-list me-2"></i>Senarai Rekod - ${currentCardName || 'Dashboard'}`;
    }
    
    // Get all unique property keys
    const allPropertyKeys = new Set();
    recordsData.forEach(f => {
        const props = f.properties || {};
        Object.keys(props).forEach(key => {
            allPropertyKeys.add(key);
        });
    });
    
    // Sort keys by frequency (most common first)
    const sortedKeys = Array.from(allPropertyKeys).sort((a, b) => {
        const countA = recordsData.filter(f => f.properties?.[a] && f.properties[a] !== '').length;
        const countB = recordsData.filter(f => f.properties?.[b] && f.properties[b] !== '').length;
        return countB - countA;
    });
    
    // Always include these columns first
    const priorityColumns = ['name', 'NAME', 'NAMA', 'DAERAH', 'PARLIMEN', 'DUN'];
    const otherColumns = sortedKeys.filter(k => !priorityColumns.includes(k));
    const displayColumns = [];
    const seenColumns = new Set(); // Track seen columns to prevent duplicates (case-insensitive)
    
    // Helper to add column only if not already seen (case-insensitive)
    const addColumnIfNotSeen = (col) => {
        if (!col) return;
        const upperKey = col.toUpperCase();
        if (!seenColumns.has(upperKey)) {
            seenColumns.add(upperKey);
            displayColumns.push(col);
        } else {
            console.warn(`⚠️ Duplicate column detected and skipped: "${col}" (already have "${Array.from(seenColumns).find(s => s.toUpperCase() === upperKey)}")`);
        }
    };
    
    // Add priority columns (if exist) - use the one that exists
    const nameColumn = priorityColumns.find(col => sortedKeys.includes(col)) || 'name';
    if (nameColumn && sortedKeys.includes(nameColumn)) {
        addColumnIfNotSeen(nameColumn);
    }
    
    // Add location columns (check case-insensitive to avoid duplicates)
    if (sortedKeys.includes('DAERAH')) {
        addColumnIfNotSeen('DAERAH');
    } else if (sortedKeys.some(k => k.toUpperCase() === 'DAERAH')) {
        // Find the actual key with different case
        const daerahKey = sortedKeys.find(k => k.toUpperCase() === 'DAERAH');
        addColumnIfNotSeen(daerahKey);
    }
    
    if (sortedKeys.includes('PARLIMEN')) {
        addColumnIfNotSeen('PARLIMEN');
    } else if (sortedKeys.some(k => k.toUpperCase() === 'PARLIMEN')) {
        const parlimenKey = sortedKeys.find(k => k.toUpperCase() === 'PARLIMEN');
        addColumnIfNotSeen(parlimenKey);
    }
    
    if (sortedKeys.includes('DUN')) {
        addColumnIfNotSeen('DUN');
    } else if (sortedKeys.some(k => k.toUpperCase() === 'DUN')) {
        const dunKey = sortedKeys.find(k => k.toUpperCase() === 'DUN');
        addColumnIfNotSeen(dunKey);
    }
    
    // Add other columns (limit to 10 most common, skip if already added)
    otherColumns.slice(0, 10).forEach(col => {
        addColumnIfNotSeen(col);
    });
    
    // Final deduplication check (safety)
    const finalDisplayColumns = [];
    const finalSeen = new Set();
    displayColumns.forEach(col => {
        const upperKey = col.toUpperCase();
        if (!finalSeen.has(upperKey)) {
            finalSeen.add(upperKey);
            finalDisplayColumns.push(col);
        }
    });
    
    // Store columns globally for use in updateRecordsDisplay
    window.recordsDisplayColumns = finalDisplayColumns;
    
    console.log('📊 Display columns determined:', finalDisplayColumns);
    console.log('📊 Total unique columns:', finalDisplayColumns.length);
    
    // Show export button
    if (exportBtn) {
        exportBtn.style.display = 'inline-block';
    }
    
    // Create filter row
    let html = '<div class="mb-3">';
    html += '<div class="row g-2">';
    displayColumns.forEach(col => {
        const displayName = col === 'name' || col === 'NAME' || col === 'NAMA' ? 'Nama' : col;
        html += '<div class="col-md-3 col-lg-2">';
        html += `<input type="text" class="form-control form-control-sm" id="filter_${col}" placeholder="Filter ${displayName}" onkeyup="applyFilters()">`;
        html += '</div>';
    });
    html += '<div class="col-md-3 col-lg-2">';
    html += '<button class="btn btn-sm btn-outline-secondary w-100" onclick="clearFilters()"><i class="fas fa-times me-1"></i>Clear</button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    // Create table
    html += '<div class="table-responsive" style="max-height: 600px; overflow-y: auto;">';
    html += '<table class="table table-striped table-hover table-sm" id="recordsTable">';
    html += '<thead class="table-light sticky-top">';
    html += '<tr>';
    html += '<th style="width: 50px; cursor: pointer;" onclick="sortRecords(\'#\')"># <i class="fas fa-sort"></i></th>';
    
    displayColumns.forEach(col => {
        const displayName = col === 'name' || col === 'NAME' || col === 'NAMA' ? 'Nama' : col;
        html += `<th style="cursor: pointer;" onclick="sortRecords('${col}')">${displayName} <i class="fas fa-sort"></i></th>`;
    });
    
    html += '<th style="width: 80px;">Tindakan</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody id="recordsTableBody">';
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    
    // Pagination
    html += '<div class="d-flex justify-content-between align-items-center mt-3">';
    html += '<div>';
    html += `<span class="text-muted">Menunjukkan <strong id="showingFrom">0</strong> - <strong id="showingTo">0</strong> daripada <strong id="totalRecords">0</strong> rekod</span>`;
    html += '</div>';
    html += '<nav>';
    html += '<ul class="pagination pagination-sm mb-0" id="pagination">';
    html += '</ul>';
    html += '</nav>';
    html += '</div>';
    
    container.innerHTML = html;
    
    // Apply initial display (after table is created)
    applyFilters();
    
    console.log('✅ Records displayed:', recordsData.length, 'records');
}

// Function to apply filters
function applyFilters() {
    // Get all filter inputs
    const filterInputs = document.querySelectorAll('[id^="filter_"]');
    filters = {};
    
    filterInputs.forEach(input => {
        const field = input.id.replace('filter_', '');
        const value = input.value.trim().toLowerCase();
        if (value) {
            filters[field] = value;
        }
    });
    
    // Apply filters
    filteredRecords = recordsData.filter(f => {
        const props = f.properties || {};
        for (const [field, filterValue] of Object.entries(filters)) {
            const propValue = props[field];
            if (propValue === undefined || propValue === null) {
                return false;
            }
            const propStr = String(propValue).toLowerCase();
            if (!propStr.includes(filterValue)) {
                return false;
            }
        }
        return true;
    });
    
    // Apply sorting
    applySorting();
    
    // Reset to first page
    currentPage = 1;
    
    // Update display
    updateRecordsDisplay();
}

// Function to clear all filters
function clearFilters() {
    const filterInputs = document.querySelectorAll('[id^="filter_"]');
    filterInputs.forEach(input => {
        input.value = '';
    });
    filters = {};
    applyFilters();
}

// Function to sort records
function sortRecords(column) {
    if (sortColumn === column) {
        // Toggle direction
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = column;
        sortDirection = 'asc';
    }
    
    applySorting();
    updateRecordsDisplay();
    updateSortIcons();
}

// Function to apply sorting
function applySorting() {
    if (!sortColumn) {
        sortedRecords = [...filteredRecords];
        return;
    }
    
    sortedRecords = [...filteredRecords].sort((a, b) => {
        let aVal, bVal;
        
        if (sortColumn === '#') {
            aVal = a._originalIndex;
            bVal = b._originalIndex;
        } else {
            aVal = a.properties?.[sortColumn];
            bVal = b.properties?.[sortColumn];
        }
        
        // Handle null/undefined
        if (aVal === null || aVal === undefined) aVal = '';
        if (bVal === null || bVal === undefined) bVal = '';
        
        // Convert to string for comparison
        aVal = String(aVal).toLowerCase();
        bVal = String(bVal).toLowerCase();
        
        // Try numeric comparison first
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
        return 0;
    });
}

// Function to update sort icons
function updateSortIcons() {
    const headers = document.querySelectorAll('#recordsTable thead th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-sort';
            icon.style.opacity = '0.3';
        }
    });
    
    if (sortColumn) {
        const headerIndex = sortColumn === '#' ? 0 : Array.from(document.querySelectorAll('#recordsTable thead th')).findIndex(th => {
            const onclick = th.getAttribute('onclick');
            return onclick && onclick.includes(`'${sortColumn}'`);
        });
        
        if (headerIndex >= 0) {
            const header = document.querySelectorAll('#recordsTable thead th')[headerIndex];
            const icon = header.querySelector('i');
            if (icon) {
                icon.className = sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
                icon.style.opacity = '1';
            }
        }
    }
}

// Function to update records display
function updateRecordsDisplay() {
    const tbody = document.getElementById('recordsTableBody');
    const showingFrom = document.getElementById('showingFrom');
    const showingTo = document.getElementById('showingTo');
    const totalRecords = document.getElementById('totalRecords');
    const countBadge = document.getElementById('recordsCount');
    
    if (!tbody) return;
    
    const total = sortedRecords.length;
    const totalPages = Math.ceil(total / recordsPerPage);
    
    // Update count badge
    if (countBadge) {
        countBadge.textContent = `${total} rekod`;
    }
    
    // Update total records
    if (totalRecords) {
        totalRecords.textContent = total;
    }
    
    // Calculate pagination
    const startIndex = (currentPage - 1) * recordsPerPage;
    const endIndex = Math.min(startIndex + recordsPerPage, total);
    const pageRecords = sortedRecords.slice(startIndex, endIndex);
    
    // Update showing range
    if (showingFrom) showingFrom.textContent = total > 0 ? startIndex + 1 : 0;
    if (showingTo) showingTo.textContent = endIndex;
    
    // Get columns from stored global variable or from table header
    let columns = window.recordsDisplayColumns || [];
    if (columns.length === 0) {
        // Fallback: get from table header
        const headers = Array.from(document.querySelectorAll('#recordsTable thead th'));
        const rawColumns = headers.slice(1, -1).map(th => {
            const onclick = th.getAttribute('onclick');
            if (onclick) {
                const match = onclick.match(/sortRecords\('([^']+)'\)/);
                return match ? match[1] : null;
            }
            return null;
        }).filter(c => c !== null);
        
        // Deduplicate columns (case-insensitive)
        const seenCols = new Set();
        columns = [];
        rawColumns.forEach(col => {
            const upperKey = col.toUpperCase();
            if (!seenCols.has(upperKey)) {
                seenCols.add(upperKey);
                columns.push(col);
            }
        });
        console.warn('⚠️ Using fallback columns from table header (deduplicated):', columns);
    }
    
    // Final safety check: deduplicate columns (case-insensitive)
    const finalColumns = [];
    const finalSeen = new Set();
    columns.forEach(col => {
        const upperKey = col.toUpperCase();
        if (!finalSeen.has(upperKey)) {
            finalSeen.add(upperKey);
            finalColumns.push(col);
        } else {
            console.warn(`⚠️ Duplicate column detected in updateRecordsDisplay: "${col}" - skipping`);
        }
    });
    columns = finalColumns;
    
    // Clear tbody
    tbody.innerHTML = '';
    
    // Add rows
    pageRecords.forEach((feature, pageIndex) => {
        const props = feature.properties || {};
        const originalIndex = feature._originalIndex;
        const name = props.name || props.NAME || props.NAMA || `Rekod ${originalIndex + 1}`;
        
        const row = document.createElement('tr');
        row.style.cursor = 'pointer';
        
        // Row number
        let rowHtml = `<td>${startIndex + pageIndex + 1}</td>`;
        
        // Data columns
        columns.forEach(col => {
            const value = props[col];
            let displayValue = '-';
            if (value !== undefined && value !== null && value !== '') {
                if (col === 'KOS' || col === 'Kadar_Sewaan') {
                    displayValue = 'RM ' + formatNumber(value);
                } else {
                    displayValue = escapeHtml(String(value));
                }
            }
            rowHtml += `<td>${displayValue}</td>`;
        });
        
        // Action button
        rowHtml += '<td>';
        rowHtml += `<button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); showRecordDetails(${originalIndex})" title="Lihat Detail">`;
        rowHtml += '<i class="fas fa-eye"></i>';
        rowHtml += '</button>';
        rowHtml += '</td>';
        
        row.innerHTML = rowHtml;
        
        // Add click event
        row.addEventListener('click', function(e) {
            if (!e.target.closest('button')) {
                showRecordDetails(originalIndex);
            }
        });
        
        tbody.appendChild(row);
    });
    
    // Update pagination
    updatePagination(totalPages);
}

// Function to update pagination
function updatePagination(totalPages) {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;
    
    pagination.innerHTML = '';
    
    if (totalPages <= 1) {
        return;
    }
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${currentPage - 1}); return false;">Previous</a>`;
    pagination.appendChild(prevLi);
    
    // Page numbers
    const maxPagesToShow = 7;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToPage(1); return false;">1</a>`;
        pagination.appendChild(firstLi);
        
        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsisLi);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${i}); return false;">${i}</a>`;
        pagination.appendChild(li);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsisLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${totalPages}); return false;">${totalPages}</a>`;
        pagination.appendChild(lastLi);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${currentPage + 1}); return false;">Next</a>`;
    pagination.appendChild(nextLi);
}

// Function to go to specific page
function goToPage(page) {
    const totalPages = Math.ceil(sortedRecords.length / recordsPerPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    updateRecordsDisplay();
    
    // Scroll to top of table
    const container = document.getElementById('recordsContainer');
    if (container) {
        const table = container.querySelector('.table-responsive');
        if (table) {
            table.scrollTop = 0;
        }
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper function to format numbers
function formatNumber(num) {
    if (num === null || num === undefined) return '0.00';
    const numStr = String(num).replace(/[^\d.-]/g, '');
    const numVal = parseFloat(numStr);
    if (isNaN(numVal)) return '0.00';
    return numVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Function to show record details
function showRecordDetails(originalIndex) {
    if (!window.currentRecordsData || !window.currentRecordsData.features || 
        !window.currentRecordsData.features[originalIndex]) {
        console.error('Record not found at index:', originalIndex);
        return;
    }
    
    const feature = window.currentRecordsData.features[originalIndex];
    const props = feature.properties || {};
    
    if (window.showRecordModal) {
        window.showRecordModal(props);
    } else {
        console.error('showRecordModal not available');
        alert('Detail rekod:\n\n' + JSON.stringify(props, null, 2));
    }
}

// Function to export records to Excel (XLS)
function exportRecordsToExcel() {
    // Export filtered and sorted data (what user sees)
    const dataToExport = sortedRecords.length > 0 ? sortedRecords : recordsData;
    
    if (!dataToExport || dataToExport.length === 0) {
        alert('Tiada data untuk diexport');
        return;
    }
    
    // Get all unique property keys from all records
    const allKeys = new Set();
    dataToExport.forEach(f => {
        const props = f.properties || {};
        Object.keys(props).forEach(key => allKeys.add(key));
    });
    
    const keys = Array.from(allKeys).sort();
    
    // Create CSV content (Excel-compatible)
    let csv = keys.map(k => `"${k}"`).join(',') + '\n';
    
    // Add data rows
    dataToExport.forEach(f => {
        const props = f.properties || {};
        const row = keys.map(key => {
            const value = props[key];
            if (value === null || value === undefined) return '""';
            // Escape quotes and wrap in quotes
            const str = String(value).replace(/"/g, '""');
            return `"${str}"`;
        }).join(',');
        csv += row + '\n';
    });
    
    // Create download link (use Excel MIME type)
    const blob = new Blob(['\ufeff' + csv], { type: 'application/vnd.ms-excel;charset=utf-8;' }); // BOM for Excel
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    const filename = `${currentCardName || 'records'}_${Object.keys(filters).length > 0 ? 'filtered_' : ''}${new Date().toISOString().split('T')[0]}.xls`;
    link.download = filename;
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('✅ Excel (XLS) exported:', dataToExport.length, 'records');
}

// Load boundary layer based on distribution type
async function loadBoundaryLayerByType(distributionType) {
    if (!map) {
        console.error('Map not initialized');
        return;
    }
    
    // Remove current boundary layer if exists
    if (currentBoundaryLayer) {
        map.removeLayer(currentBoundaryLayer);
        currentBoundaryLayer = null;
    }
    
    // Map distribution type to kategori
    const typeMap = {
        'DAERAH': 'daerah',
        'PARLIMEN': 'parlimen',
        'DUN': 'dun'
    };
    
    const kategori = typeMap[distributionType];
    if (!kategori) {
        console.warn('Unknown distribution type:', distributionType);
        return;
    }
    
    console.log('Loading boundary layer for:', kategori);
    
    try {
        const fetchWithTimeout = (url, timeout = 15000) => {
            return Promise.race([
                fetch(url),
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Timeout')), timeout)
                )
            ]);
        };
        
        const response = await fetchWithTimeout(`api_get_geojson_by_kategori.php?kategori=${kategori}`, 15000);
        
        if (response.ok) {
            const data = await response.json();
            if (data.error) {
                console.warn('Error loading boundary from database:', data.error);
                return;
            }
            
            if (data.features && data.features.length > 0) {
                // Define styles based on type - semua biru terang
                const styles = {
                    'daerah': {
                        color: '#1E90FF', // Biru terang untuk sempadan daerah
                        weight: 3,
                        opacity: 0.8,
                        fillColor: '#87CEEB', // Biru cerah untuk fill daerah
                        fillOpacity: 0.2
                    },
                    'parlimen': {
                        color: '#1E90FF', // Biru terang untuk sempadan parlimen
                        weight: 2.5,
                        opacity: 0.7,
                        fillColor: '#87CEEB', // Biru cerah untuk fill parlimen
                        fillOpacity: 0.15
                    },
                    'dun': {
                        color: '#1E90FF', // Biru terang untuk sempadan dun
                        weight: 2,
                        opacity: 0.6,
                        fillColor: '#87CEEB', // Biru cerah untuk fill dun
                        fillOpacity: 0.12
                    }
                };
                
                const style = styles[kategori] || styles['daerah'];
                
                // Property name mappings
                const nameFields = {
                    'daerah': ['name', 'NAME_2', 'adm2_name', 'NAMA_DAERAH'],
                    'parlimen': ['name', 'NAME_1', 'parlimen', 'NAMA_PARLIMEN'],
                    'dun': ['name', 'NAME_2', 'dun', 'NAMA_DUN']
                };
                
                const nameFieldList = nameFields[kategori] || nameFields['daerah'];
                
                currentBoundaryLayer = L.geoJSON(data, {
                    style: style,
                    onEachFeature: function(feature, layer) {
                        const props = feature.properties || {};
                        let boundaryName = 'Unknown';
                        for (const field of nameFieldList) {
                            if (props[field]) {
                                boundaryName = props[field];
                                break;
                            }
                        }
                        layer.bindTooltip(boundaryName, { permanent: false });
                    }
                }).addTo(map);
                
                // Set z-index based on boundary type to ensure correct layer order:
                // Negeri (100) < Daerah (200) < Parlimen (300) < DUN (400) < Rekod (500)
                const zIndexMap = {
                    'daerah': 200,
                    'parlimen': 300,
                    'dun': 400
                };
                const zIndex = zIndexMap[kategori] || 200;
                if (typeof currentBoundaryLayer.setZIndexOffset === 'function') {
                    currentBoundaryLayer.setZIndexOffset(zIndex);
                } else {
                    console.warn('⚠️ setZIndexOffset not available for boundary layer');
                }
                
                // Ensure correct layer order
                ensureLayerOrder();
                
                console.log(`Loaded ${kategori} boundaries:`, data.features.length, 'features');
                
                // Only fit bounds on initial load (first time loading Daerah)
                // After that, keep the same zoom/position when switching boundaries
                if (isInitialBoundaryLoad && distributionType === 'DAERAH') {
                    // Use Kedah bounds to maintain consistent view
                    const kedahBounds = getKedahBounds();
                    if (kedahBounds) {
                        setTimeout(() => {
                            map.fitBounds(kedahBounds, { 
                                padding: [20, 20],
                                animate: false 
                            });
                        }, 100);
                    }
                    isInitialBoundaryLoad = false; // Mark as no longer initial load
                }
                // For subsequent boundary changes, don't change zoom/position
            } else {
                console.warn(`No ${kategori} data in database`);
            }
        } else {
            console.error(`Failed to load ${kategori} from database:`, response.status);
        }
    } catch (err) {
        console.error(`Error loading ${kategori} boundaries:`, err.message);
    }
}

// Reset map view - always center on Kedah
function resetMap() {
    const kedahBounds = getKedahBounds();
    if (!kedahBounds) {
        console.error('Cannot reset map - Kedah bounds not available');
        return;
    }
    map.invalidateSize(); // Ensure map size is correct
    map.fitBounds(kedahBounds, {
        padding: [20, 20],  // Padding to center Kedah nicely
        animate: true,
        duration: 0.5
    });
}

// Toggle fullscreen for map
function toggleFullscreen() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container not found');
        return;
    }
    
    // Find the card containing the map
    let mapCard = mapContainer.closest('.card');
    if (!mapCard) {
        // Fallback: find by traversing up the DOM
        let parent = mapContainer.parentElement;
        while (parent && !parent.classList.contains('card')) {
            parent = parent.parentElement;
        }
        mapCard = parent;
    }
    
    if (!mapCard) {
        console.error('Map card not found');
        return;
    }
    
    if (!document.fullscreenElement) {
        mapCard.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Exit fullscreen for map
function exitMapFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    }
}

// Handle fullscreen change for map
function handleMapFullscreenChange() {
    const mapContainer = document.getElementById('map');
    const mapCard = mapContainer ? mapContainer.closest('.card') : null;
    const fullscreenBtn = document.getElementById('mapFullscreenBtn');
    const exitFullscreenBtn = document.getElementById('mapExitFullscreenBtn');
    
    if (!mapContainer || !mapCard) {
        return;
    }
    
    if (document.fullscreenElement && document.fullscreenElement === mapCard) {
        // Entered fullscreen - make map fit to screen
        // Make card fill entire screen
        mapCard.style.width = '100vw';
        mapCard.style.height = '100vh';
        mapCard.style.margin = '0';
        mapCard.style.borderRadius = '0';
        
        // Make card body fill available space
        const cardBody = mapCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)'; // Subtract header height
            cardBody.style.padding = '0';
        }
        
        // Make map container fill available space
        mapContainer.style.height = '100%';
        mapContainer.style.width = '100%';
        
        // Show/hide buttons
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        
        // Resize map after a short delay to ensure DOM is updated
        setTimeout(() => {
            if (map) {
                map.invalidateSize();
                // Fit to Kedah bounds
                const kedahBounds = getKedahBounds();
                if (kedahBounds) {
                    map.fitBounds(kedahBounds, {
                        padding: [20, 20],
                        animate: false
                    });
                }
            }
        }, 100);
    } else {
        // Exited fullscreen - reset to original size
        mapCard.style.width = '';
        mapCard.style.height = '';
        mapCard.style.margin = '';
        mapCard.style.borderRadius = '';
        
        const cardBody = mapCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        
        mapContainer.style.height = '600px';
        mapContainer.style.width = '';
        
        // Show/hide buttons
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        
        // Resize map after a short delay
        setTimeout(() => {
            if (map) {
                map.invalidateSize();
            }
        }, 100);
    }
}

// Function to update map and chart titles based on current card name
function updateMapAndChartTitles() {
    const mapTitleEl = document.getElementById('mapTitle');
    const chartTitleEl = document.getElementById('chartTitle');
    
    if (mapTitleEl) {
        mapTitleEl.innerHTML = `<i class="fas fa-th me-2"></i>${currentCardName}`;
    }
    
    if (chartTitleEl) {
        chartTitleEl.innerHTML = `<i class="fas fa-chart-pie me-2"></i>${currentCardName}`;
    }
}

// Add event listeners for map fullscreen changes
document.addEventListener('fullscreenchange', function() {
    handleMapFullscreenChange();
    handleChartFullscreenChange(); // Also handle chart if needed
});
document.addEventListener('webkitfullscreenchange', function() {
    handleMapFullscreenChange();
    handleChartFullscreenChange();
});
document.addEventListener('mozfullscreenchange', function() {
    handleMapFullscreenChange();
    handleChartFullscreenChange();
});
document.addEventListener('MSFullscreenChange', function() {
    handleMapFullscreenChange();
    handleChartFullscreenChange();
});

// Toggle fullscreen for chart
function toggleChartFullscreen() {
    // Find the chart card by finding the parent card of the chart canvas
    const chartCanvas = document.getElementById('daerahChart');
    if (!chartCanvas) {
        console.error('Chart canvas not found');
        return;
    }
    
    // Find the closest card element
    let chartCard = chartCanvas.closest('.card');
    if (!chartCard) {
        // Fallback: find by traversing up the DOM
        let parent = chartCanvas.parentElement;
        while (parent && !parent.classList.contains('card')) {
            parent = parent.parentElement;
        }
        chartCard = parent;
    }
    
    if (!chartCard) {
        console.error('Chart card not found');
        return;
    }
    
    if (!document.fullscreenElement) {
        chartCard.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Exit fullscreen for chart
function exitChartFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    }
}

// Handle fullscreen change for chart
function handleChartFullscreenChange() {
    const chartCanvas = document.getElementById('daerahChart');
    const chartContainer = chartCanvas ? chartCanvas.parentElement : null;
    const chartCard = chartCanvas ? chartCanvas.closest('.card') : null;
    const fullscreenBtn = document.getElementById('chartFullscreenBtn');
    const exitFullscreenBtn = document.getElementById('chartExitFullscreenBtn');
    
    if (!chartCanvas || !daerahChart || !chartContainer || !chartCard) {
        return;
    }
    
    if (document.fullscreenElement && document.fullscreenElement === chartCard) {
        // Entered fullscreen - make chart fit to screen and enlarge fonts
        // Make card fill entire screen
        chartCard.style.width = '100vw';
        chartCard.style.height = '100vh';
        chartCard.style.margin = '0';
        chartCard.style.borderRadius = '0';
        
        // Make card body fill available space
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = 'calc(100vh - 80px)'; // Subtract header height
            cardBody.style.padding = '20px';
        }
        
        // Make chart container div fill available space
        if (chartContainer) {
            chartContainer.style.height = '100%';
            chartContainer.style.width = '100%';
        }
        
        // Also resize the canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '100%';
            canvasContainer.style.width = '100%';
        }
        
        // Show/hide buttons
        if (fullscreenBtn) fullscreenBtn.style.display = 'none';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'inline-block';
        
        // Update chart with larger fonts
        if (daerahChart) {
            // Title is hidden, so no need to update title font size
            daerahChart.options.plugins.subtitle.font.size = 20;
            daerahChart.options.plugins.legend.labels.font.size = 18;
            daerahChart.options.plugins.legend.labels.padding = 25;
            daerahChart.options.plugins.tooltip.titleFont = { size: 20 };
            daerahChart.options.plugins.tooltip.bodyFont = { size: 18 };
            daerahChart.options.plugins.tooltip.padding = 18;
            daerahChart.resize();
        }
    } else {
        // Exited fullscreen - reset to original size
        chartCard.style.width = '';
        chartCard.style.height = '';
        chartCard.style.margin = '';
        chartCard.style.borderRadius = '';
        
        const cardBody = chartCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.style.height = '';
            cardBody.style.padding = '';
        }
        
        if (chartContainer) {
            chartContainer.style.height = '600px';
            chartContainer.style.width = '';
        }
        
        // Reset canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '600px';
            canvasContainer.style.width = '';
        }
        
        // Show/hide buttons
        if (fullscreenBtn) fullscreenBtn.style.display = 'inline-block';
        if (exitFullscreenBtn) exitFullscreenBtn.style.display = 'none';
        
        // Reset chart fonts to original
        if (daerahChart) {
            // Title is hidden, so no need to reset title font size
            daerahChart.options.plugins.subtitle.font.size = 11;
            daerahChart.options.plugins.legend.labels.font.size = 11;
            daerahChart.options.plugins.legend.labels.padding = 15;
            daerahChart.options.plugins.tooltip.titleFont = { size: 14 };
            daerahChart.options.plugins.tooltip.bodyFont = { size: 12 };
            daerahChart.options.plugins.tooltip.padding = 12;
            daerahChart.resize();
        }
    }
}

// Event listeners are now handled in the map fullscreen section above

// Function to initialize everything
window.initializeDashboard = function() {
    console.log('=== Initializing dashboard ===');
    console.log('Leaflet available:', typeof L !== 'undefined');
    console.log('Map element exists:', document.getElementById('map') !== null);
    console.log('Chart canvas exists:', document.getElementById('daerahChart') !== null);
    console.log('Default card data:', defaultCardData);
    
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded! Retrying in 500ms...');
        setTimeout(window.initializeDashboard, 500);
        return;
    }
    
    // Prevent multiple initializations
    if (window.dashboardInitialized) {
        console.log('Dashboard already initialized');
        return;
    }
    window.dashboardInitialized = true;
    
    console.log('Initializing map...');
    initMap();
    
    // Wait a bit for map to be fully initialized
    setTimeout(() => {
        console.log('=== Map initialization check ===');
        console.log('Map object:', map);
        if (map) {
            console.log('Map container:', map.getContainer() ? 'Ready' : 'Not ready');
            console.log('Map bounds:', map.getBounds());
            // Ensure map is visible
            map.invalidateSize();
            console.log('✅ Map is ready');
        } else {
            console.error('❌ Map is null after initMap()!');
        }
    }, 500);
    
    // Add click event listeners to cards
    document.querySelectorAll('.summary-card').forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const kategori = this.dataset.kategori;
            const filepath = this.dataset.filepath;
            console.log('Card clicked via event listener:', kategori, filepath);
            if (kategori && filepath) {
                if (window.loadCategory) {
                    window.loadCategory(kategori, filepath, this);
                } else {
                    console.error('loadCategory function not available');
                    alert('Function loadCategory belum dimuatkan. Sila refresh page.');
                }
            } else {
                console.error('Missing data attributes:', this.dataset);
            }
        });
    });
    
    console.log('Event listeners attached to', document.querySelectorAll('.summary-card').length, 'cards');
    
    // Load default card after everything is ready
    if (defaultCardData && defaultCardData.kategori && defaultCardData.filepath) {
        console.log('Preparing to auto-load default card:', defaultCardData.name);
        
        // Wait for map to be ready before loading default card
        const loadDefaultCard = () => {
            if (!map) {
                console.log('Map not ready yet, retrying in 200ms...');
                setTimeout(loadDefaultCard, 200);
                return;
            }
            
            // Ensure map container is ready
            if (!map.getContainer()) {
                console.log('Map container not ready yet, retrying in 200ms...');
                setTimeout(loadDefaultCard, 200);
                return;
            }
            
            console.log('Auto-loading default card:', defaultCardData.name);
            const cardElement = document.querySelector(`[data-kategori="${defaultCardData.kategori}"]`);
            if (cardElement) {
                document.querySelectorAll('.summary-card').forEach(card => {
                    card.classList.remove('active');
                });
                cardElement.classList.add('active');
                // Set current card name
                if (defaultCardData.name) {
                    currentCardName = defaultCardData.name;
                    updateMapAndChartTitles();
                }
                
                // Wait a bit more to ensure everything is ready
                setTimeout(() => {
                    console.log('=== Loading default card ===');
                    console.log('loadCategory available:', typeof window.loadCategory === 'function');
                    console.log('Map available:', map !== null);
                    console.log('Card element:', cardElement);
                    console.log('Default card kategori:', defaultCardData.kategori);
                    console.log('Default card filepath:', defaultCardData.filepath);
                    
                    if (window.loadCategory && map) {
                        console.log('✅ All prerequisites ready, loading default card data...');
                        window.loadCategory(defaultCardData.kategori, defaultCardData.filepath, cardElement);
                    } else {
                        console.error('❌ Prerequisites not ready for default card');
                        if (!map) {
                            console.error('Map is null!');
                        }
                        if (!window.loadCategory) {
                            console.error('loadCategory function not available!');
                        }
                    }
                }, 800);
            } else {
                console.warn('Default card element not found:', defaultCardData.kategori);
            }
        };
        
        // Start loading after a short delay to ensure map is initialized
        setTimeout(loadDefaultCard, 1500);
    }
};

// Initialize on page load - try multiple methods
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Leaflet to load
        if (window.leafletLoaded && typeof L !== 'undefined') {
            window.initializeDashboard();
        } else {
            setTimeout(function() {
                if (typeof L !== 'undefined') {
                    window.initializeDashboard();
                }
            }, 500);
        }
    });
} else {
    // DOM already loaded
    if (window.leafletLoaded && typeof L !== 'undefined') {
        window.initializeDashboard();
    } else {
        setTimeout(function() {
            if (typeof L !== 'undefined') {
                window.initializeDashboard();
            }
        }, 500);
    }
}

// Also try window.onload as backup
window.addEventListener('load', function() {
    // Verify loadCategory is available
    console.log('Window loaded - verifying loadCategory:', typeof window.loadCategory === 'function');
    if (typeof window.loadCategory !== 'function') {
        console.error('CRITICAL: loadCategory is not a function after window load!');
        alert('Function loadCategory belum dimuatkan. Sila refresh page.');
        return;
    }
    
    // Always try to initialize if not already done
    if (!window.dashboardInitialized) {
        console.log('Dashboard not initialized yet, initializing from window.onload...');
        if (typeof L !== 'undefined') {
            window.initializeDashboard();
        } else {
            console.log('Leaflet not ready, waiting...');
            let retryCount = 0;
            const checkLeaflet = setInterval(() => {
                retryCount++;
                if (typeof L !== 'undefined') {
                    clearInterval(checkLeaflet);
                    window.initializeDashboard();
                } else if (retryCount > 20) {
                    clearInterval(checkLeaflet);
                    console.error('Leaflet failed to load after 10 seconds');
                    alert('Peta tidak dapat dimuatkan. Sila refresh page.');
                }
            }, 500);
        }
    } else {
        // Dashboard already initialized - check if default card needs to be loaded
        if (map && defaultCardData && defaultCardData.kategori && defaultCardData.filepath) {
            console.log('Dashboard initialized, checking if default card needs to be loaded...');
            const cardElement = document.querySelector(`[data-kategori="${defaultCardData.kategori}"]`);
            if (cardElement && !cardElement.classList.contains('active')) {
                console.log('Default card not active, loading now...');
                document.querySelectorAll('.summary-card').forEach(card => {
                    card.classList.remove('active');
                });
                cardElement.classList.add('active');
                if (defaultCardData.name) {
                    currentCardName = defaultCardData.name;
                    updateMapAndChartTitles();
                }
                if (window.loadCategory) {
                    setTimeout(() => {
                        window.loadCategory(defaultCardData.kategori, defaultCardData.filepath, cardElement);
                    }, 500);
                }
            }
        }
    }
});

// Handle upload form submission
document.addEventListener('DOMContentLoaded', function() {
});
</script>

<!-- Modal for Record Details -->
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="recordDetailModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Maklumat Terperinci Rekod
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="recordDetailModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
