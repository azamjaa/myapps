<?php
require 'db.php';
require 'src/rbac_helper.php';
require_once __DIR__ . '/spatial_processor_engine.php';
include 'header.php';

// Check if user is admin
$current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);
$isAdmin = false;
if ($current_user) {
    $isAdmin = isSuperAdmin($db, $current_user);
}

// Handle file upload
$uploadMessage = '';
$uploadSuccess = false;
if ($isAdmin && isset($_POST['upload_geojson']) && isset($_FILES['geojson_file'])) {
    $file = $_FILES['geojson_file'];
    
    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check file extension
        if ($fileExt === 'geojson') {
            $jsonFolder = __DIR__ . '/json/';
            $targetFile = $jsonFolder . $filename;
            
            // Validate JSON format first
            $jsonContent = file_get_contents($file['tmp_name']);
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['type']) && $data['type'] === 'FeatureCollection') {
                // Get kategori from filename
                $kategori = pathinfo($filename, PATHINFO_FILENAME);
                
                // Import to database
                try {
                    $db->beginTransaction();
                    
                    // Delete existing data for this kategori
                    $deleteStmt = $db->prepare("DELETE FROM geojson_data WHERE kategori = ?");
                    $deleteStmt->execute([$kategori]);
                    
                    // Initialize spatial processor (skip for boundary categories)
                    $processor = null;
                    if (!in_array(strtolower($kategori), ['negeri', 'daerah', 'parlimen', 'dun'])) {
                        $processor = new SpatialAutoTag($db);
                    }
                    
                    // Insert new features
                    $insertStmt = $db->prepare("INSERT INTO geojson_data (kategori, properties, geometry) VALUES (?, ?, ?)");
                    $importedCount = 0;
                    $spatialTaggedCount = 0;
                    
                    foreach ($data['features'] as $feature) {
                        if (!isset($feature['properties']) || !isset($feature['geometry'])) {
                            continue;
                        }
                        
                        $props = $feature['properties'];
                        $geom = $feature['geometry'];
                        
                        // Process spatial tagging if processor is available and geometry is Point
                        if ($processor && $geom['type'] === 'Point' && isset($geom['coordinates'])) {
                            // Extract coordinates
                            $long = floatval($geom['coordinates'][0]);
                            $lat = floatval($geom['coordinates'][1]);
                            
                            // Only process if coordinates are valid and within Kedah bounds
                            if ($lat >= 4.0 && $lat <= 7.0 && $long >= 99.0 && $long <= 101.5) {
                                // Create payload-like structure for processing
                                $payloadData = array_merge($props, [
                                    'lat' => $lat,
                                    'long' => $long
                                ]);
                                
                                // Process with spatial tagging
                                $processedPayload = $processor->processRow($payloadData);
                                
                                // Extract updated properties
                                if (is_string($processedPayload)) {
                                    $processedPayload = json_decode($processedPayload, true);
                                }
                                
                                // Update properties with spatial data
                                if (!empty($processedPayload['auto_daerah']) && empty($props['DAERAH'])) {
                                    $props['DAERAH'] = $processedPayload['auto_daerah'];
                                }
                                if (!empty($processedPayload['auto_parlimen']) && empty($props['PARLIMEN'])) {
                                    $props['PARLIMEN'] = $processedPayload['auto_parlimen'];
                                }
                                if (!empty($processedPayload['auto_dun']) && empty($props['DUN'])) {
                                    $props['DUN'] = $processedPayload['auto_dun'];
                                }
                                
                                if (!empty($processedPayload['auto_daerah']) || !empty($processedPayload['auto_parlimen']) || !empty($processedPayload['auto_dun'])) {
                                    $spatialTaggedCount++;
                                }
                            }
                        }
                        
                        $propsJson = json_encode($props, JSON_UNESCAPED_UNICODE);
                        $geomJson = json_encode($geom, JSON_UNESCAPED_UNICODE);
                        
                        if ($insertStmt->execute([$kategori, $propsJson, $geomJson])) {
                            $importedCount++;
                        }
                    }
                    
                    $db->commit();
                    
                    // Also save file as backup
                    $backupCreated = false;
                    if (file_exists($targetFile)) {
                        $backupFolder = $jsonFolder . 'backup/';
                        if (!is_dir($backupFolder)) {
                            mkdir($backupFolder, 0755, true);
                        }
                        $backupFile = $backupFolder . $filename . '_' . date('YmdHis') . '.geojson';
                        if (copy($targetFile, $backupFile)) {
                            $backupCreated = true;
                        }
                    }
                    
                    // Move uploaded file to json folder as backup
                    move_uploaded_file($file['tmp_name'], $targetFile);
                    
                    $uploadSuccess = true;
                    $uploadMessage = 'File berjaya dimuat naik dan diimport ke database: ' . htmlspecialchars($filename) . ' (' . $importedCount . ' rekod)';
                    if ($spatialTaggedCount > 0) {
                        $uploadMessage .= ' (' . $spatialTaggedCount . ' rekod telah ditag dengan maklumat lokasi)';
                    }
                    if ($backupCreated) {
                        $uploadMessage .= ' (File lama telah dibackup)';
                    }
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $uploadMessage = 'Error semasa import ke database: ' . $e->getMessage();
                }
            } else {
                $uploadMessage = 'Format GeoJSON tidak sah. Sila semak file anda. Error: ' . json_last_error_msg();
            }
        } else {
            $uploadMessage = 'Hanya file .geojson dibenarkan.';
        }
    } else {
        $uploadMessage = 'Error semasa upload: ' . $file['error'];
    }
}

// Keyword Mapping untuk nama profesional berdasarkan kategori
// Mapping ini lebih spesifik untuk mengelakkan duplikasi
function getProfessionalNameFromKategori($kategori) {
    $kategoriLower = strtolower($kategori);
    
    // Check exact matches first (most specific)
    $exactMapping = [
        'keda - bangunan kediaman' => 'Bantuan Perumahan',
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
        'KEDA - Bangunan Kediaman' => 'Bantuan Perumahan',
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
            'filepath' => 'api_get_geojson_by_kategori.php?kategori=' . urlencode($kategori)
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
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-map-marked-alt me-3 text-primary"></i>Dashboard Pencapaian KEDA</h3>

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
                    <div style="height: 600px;">
                        <canvas id="daerahChart"></canvas>
                    </div>
                </div>
                </div>
            </div>
            
            <?php if ($isAdmin): ?>
        <!-- Upload GeoJSON Section - Admin Only - Full Width -->
        <div class="col-12 mt-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-upload me-2"></i>Upload GeoJSON File
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($uploadMessage): ?>
                    <div class="alert alert-<?php echo $uploadSuccess ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $uploadSuccess ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $uploadMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="mb-3">
                            <label for="geojson_file" class="form-label">Pilih File GeoJSON</label>
                            <input type="file" class="form-control" id="geojson_file" name="geojson_file" accept=".geojson" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                File akan menggantikan file sedia ada dengan nama yang sama. File lama akan dibackup secara automatik.
                            </div>
                        </div>
                        <button type="submit" name="upload_geojson" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload File
                        </button>
                    </form>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Hanya admin yang boleh melihat dan menggunakan feature ini.
                        </small>
                    </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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

<!-- Turf.js - Load first for reverse geocoding -->
<script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>

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

<script>
// Set default font for all charts - Same as dashboard_staf
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;

// Global variables
let map, currentLayer = null, daerahChart = null;
let currentData = null;
let currentChartData = null; // Store data for chart updates
let currentBoundaryLayer = null; // Store current boundary layer (daerah/parlimen/dun)
let negeriLayer = null; // Store negeri boundary layer
let isInitialBoundaryLoad = true; // Track if this is the first boundary load
let currentCardName = 'Peta Negeri Kedah'; // Store current card name for titles

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
    
    if (typeof turf === 'undefined') {
        console.error('Turf.js not loaded!');
        return;
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
            if (parlimenData.features && parlimenData.features.length > 0) {
                boundaryCache.parlimen = parlimenData;
                console.log('Loaded parlimen boundaries:', parlimenData.features.length, 'features');
            }
        }
        
        // Load DUN boundaries
        const dunResponse = await fetch('api_get_geojson_by_kategori.php?kategori=dun');
        if (dunResponse.ok) {
            const dunData = await dunResponse.json();
            if (dunData.features && dunData.features.length > 0) {
                boundaryCache.dun = dunData;
                console.log('Loaded DUN boundaries:', dunData.features.length, 'features');
            }
        }
        
        boundaryCache.loaded = true;
        console.log('All boundaries loaded for reverse geocoding');
    } catch (error) {
        console.error('Error loading boundaries:', error);
    }
}

// Reverse geocode a point using Turf.js
function reverseGeocodePoint(point, properties = {}) {
    if (typeof turf === 'undefined') {
        console.warn('Turf.js not available for reverse geocoding');
        return properties;
    }
    
    // Only process Point geometries
    if (!point || !point.geometry || point.geometry.type !== 'Point') {
        return properties;
    }
    
    const [lng, lat] = point.geometry.coordinates;
    const turfPoint = turf.point([lng, lat]);
    
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
        for (const feature of boundaryCache.parlimen.features) {
            if (turf.booleanPointInPolygon(turfPoint, feature)) {
                const parlimenName = feature.properties?.name || 
                                    feature.properties?.NAME_1 || 
                                    feature.properties?.parlimen ||
                                    feature.properties?.PARLIMEN ||
                                    'Unknown';
                properties.PARLIMEN = parlimenName;
                console.log('Found PARLIMEN:', parlimenName, 'for point:', lat, lng);
                break;
            }
        }
    }
    
    // Find DUN
    if (!properties.DUN && boundaryCache.dun && boundaryCache.dun.features) {
        for (const feature of boundaryCache.dun.features) {
            if (turf.booleanPointInPolygon(turfPoint, feature)) {
                const dunName = feature.properties?.name || 
                               feature.properties?.NAME_2 || 
                               feature.properties?.dun ||
                               feature.properties?.DUN ||
                               'Unknown';
                properties.DUN = dunName;
                console.log('Found DUN:', dunName, 'for point:', lat, lng);
                break;
            }
        }
    }
    
    return properties;
}

// Enrich all features in a FeatureCollection with reverse geocoding
function enrichFeaturesWithGeocoding(featureCollection) {
    if (!featureCollection || !featureCollection.features) {
        return featureCollection;
    }
    
    // Check if Turf.js is available
    if (typeof turf === 'undefined') {
        console.warn('Turf.js not available, skipping reverse geocoding');
        return featureCollection;
    }
    
    // Wait for boundaries to load if not loaded yet
    if (!boundaryCache.loaded) {
        console.warn('Boundaries not loaded yet, attempting to load...');
        // Try to load boundaries synchronously (this will be async, but we'll continue)
        loadBoundariesForGeocoding().then(() => {
            console.log('Boundaries loaded, you may need to reload the category');
        });
        return featureCollection;
    }
    
    console.log('Enriching', featureCollection.features.length, 'features with reverse geocoding...');
    let enrichedCount = 0;
    let fieldsAdded = { DAERAH: 0, PARLIMEN: 0, DUN: 0 };
    
    featureCollection.features.forEach((feature, index) => {
        if (feature.geometry && feature.geometry.type === 'Point') {
            const originalProps = feature.properties || {};
            const enrichedProps = reverseGeocodePoint(feature, { ...originalProps });
            
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
            
            feature.properties = enrichedProps;
        }
    });
    
    console.log('Enriched', enrichedCount, 'features with location data:', fieldsAdded);
    return featureCollection;
}

// Make loadCategory globally accessible - Define early
// Ensure it's defined before any onclick handlers
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
    
    try {
        console.log('Loading category:', kategori, 'from:', filepath);
        
        // Use the provided filepath (could be database API or file API)
        // Add timeout to prevent hanging
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
        
        const response = await fetch(filepath, { signal: controller.signal });
        clearTimeout(timeoutId);
        
        console.log('Response status:', response.status, response.statusText);
        console.log('Response headers:', response.headers.get('content-type'));
        
        // Get response as text first to check for errors
        const responseText = await response.text();
        console.log('Response length:', responseText.length, 'characters');
        console.log('Response preview (first 200 chars):', responseText.substring(0, 200));
        
        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                errorData = { error: responseText.substring(0, 500) };
            }
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }
        
        // Parse JSON from text
        let data;
        try {
            // Trim whitespace that might cause issues
            const trimmedText = responseText.trim();
            data = JSON.parse(trimmedText);
            console.log('JSON parsed successfully. Features count:', data.features?.length || 0);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response text (first 500 chars):', responseText.substring(0, 500));
            console.error('Response text (last 200 chars):', responseText.substring(Math.max(0, responseText.length - 200)));
            
            // Try to find where JSON might be broken
            const jsonStart = responseText.indexOf('{');
            const jsonEnd = responseText.lastIndexOf('}');
            if (jsonStart > 0 || jsonEnd < responseText.length - 1) {
                console.warn('Possible extra content before/after JSON. Start:', jsonStart, 'End:', jsonEnd, 'Length:', responseText.length);
            }
            
            throw new Error('Format JSON tidak sah. Sila semak data dalam database untuk kategori: ' + kategori + '. Error: ' + jsonError.message);
        }
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Check if data is valid
        if (!data) {
            throw new Error('Tiada data diterima dari server');
        }
        
        // Check for error in response
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Check for warning
        if (data.warning) {
            console.warn('Warning:', data.warning);
        }
        
        // Check if features exist and is array
        if (!data.features) {
            throw new Error('Format data tidak sah: tiada features field');
        }
        
        if (!Array.isArray(data.features)) {
            throw new Error('Format data tidak sah: features bukan array');
        }
        
        if (data.features.length === 0) {
            throw new Error('Tiada rekod dijumpai untuk kategori: ' + kategori);
        }
        
        // Enrich features with reverse geocoding (DAERAH, PARLIMEN, DUN)
        console.log('Enriching features with reverse geocoding...');
        data = enrichFeaturesWithGeocoding(data);
        
        // Count enriched features
        let enrichedCount = 0;
        data.features.forEach(f => {
            const props = f.properties || {};
            if (props.DAERAH || props.PARLIMEN || props.DUN) {
                enrichedCount++;
            }
        });
        
        if (enrichedCount > 0) {
            console.log(`Berjaya mengisi maklumat lokasi untuk ${enrichedCount} rekod`);
        }
        
        currentData = data;
        currentChartData = data; // Store for chart updates
        
        console.log('Processing', data.features.length, 'features for map display');
        
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
            
            // Check coordinates based on geometry type
            if (f.geometry.type === 'Point') {
                if (!f.geometry.coordinates || f.geometry.coordinates.length < 2) {
                    console.warn('Point feature has invalid coordinates:', f.geometry);
                    return false;
                }
                // Validate coordinates are numbers
                const [lng, lat] = f.geometry.coordinates;
                if (typeof lng !== 'number' || typeof lat !== 'number' || 
                    isNaN(lng) || isNaN(lat) || 
                    lng < -180 || lng > 180 || lat < -90 || lat > 90) {
                    console.warn('Point feature has invalid coordinate values:', f.geometry.coordinates);
                    return false;
                }
                
                // Check if point is within Kedah bounds - ABORT if outside
                if (typeof L !== 'undefined') {
                    const kedahBounds = getKedahBounds();
                    if (kedahBounds) {
                        const point = L.latLng(lat, lng);
                        if (!kedahBounds.contains(point)) {
                            console.log('Point terkeluar dari Kedah bounds, diabaikan:', lat, lng);
                            return false; // Abaikan point yang terkeluar
                        }
                    }
                }
            } else if (f.geometry.type === 'Polygon' || f.geometry.type === 'MultiPolygon') {
                if (!f.geometry.coordinates || f.geometry.coordinates.length === 0) {
                    console.warn('Polygon feature has empty coordinates:', f.geometry);
                    return false;
                }
                // For polygons, check if any vertex is within Kedah bounds
                // If polygon is completely outside Kedah, skip it
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
                    
                    // Create marker dengan warna biru cerah - HANYA untuk Point geometry
                    const marker = L.circleMarker(latlng, {
                        radius: 10,
                        fillColor: '#87CEEB', // Biru cerah untuk point GPS
                        color: '#FFFFFF', // Border putih
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 1.0
                    });
                    
                    // Force apply style immediately - pastikan warna biru cerah
                    marker.setStyle({
                        fillColor: '#87CEEB',
                        fillOpacity: 1.0,
                        color: '#FFFFFF',
                        weight: 3,
                        opacity: 1
                    });
                    
                    // Also force after marker is added
                    setTimeout(() => {
                        if (marker && marker.setStyle) {
                            marker.setStyle({
                                fillColor: '#87CEEB',
                                fillOpacity: 1.0,
                                color: '#FFFFFF',
                                weight: 3
                            });
                            // Also update the DOM element directly if needed
                            if (marker._path) {
                                marker._path.setAttribute('fill', '#87CEEB');
                                marker._path.setAttribute('fill-opacity', '1.0');
                                marker._path.setAttribute('stroke', '#FFFFFF');
                            }
                        }
                    }, 50);
                    
                    return marker;
                },
                style: function(feature) {
                    // Skip style untuk Point - pointToLayer akan handle semua point
                    // Skip style untuk Point - pointToLayer akan handle Point geometry
                    if (feature.geometry && feature.geometry.type === 'Point') {
                        return {}; // Return empty untuk point - pointToLayer akan handle
                    }
                    // Style untuk Polygon, LineString, dll - warna biru cerah
                    return {
                        color: '#87CEEB', // Biru cerah untuk peta (polygon/line)
                        weight: 3,
                        opacity: 0.7,
                        fillColor: '#B0E0E6', // Biru cerah muda untuk fill
                        fillOpacity: 0.4
                    };
                },
                onEachFeature: function(feature, layer) {
                    if (!layer) return;
                    
                    // Force biru cerah untuk semua geometry types (rekod GIS)
                    setTimeout(() => {
                        if (layer.setStyle) {
                            if (feature.geometry && feature.geometry.type === 'Point' && layer instanceof L.CircleMarker) {
                                // Point markers - biru cerah dengan border putih
                                layer.setStyle({
                                    fillColor: '#87CEEB',
                                    fillOpacity: 1.0,
                                    color: '#FFFFFF',
                                    weight: 3
                                });
                                // Direct DOM manipulation as fallback
                                if (layer._path) {
                                    layer._path.setAttribute('fill', '#87CEEB');
                                    layer._path.setAttribute('fill-opacity', '1.0');
                                    layer._path.setAttribute('stroke', '#FFFFFF');
                                    layer._path.setAttribute('stroke-width', '3');
                                }
                            } else {
                                // Polygon/LineString - biru cerah
                                layer.setStyle({
                                    color: '#87CEEB',
                                    fillColor: '#B0E0E6',
                                    fillOpacity: 0.4,
                                    weight: 3,
                                    opacity: 0.7
                                });
                                // Direct DOM manipulation for polygon/line
                                if (layer._path) {
                                    layer._path.setAttribute('stroke', '#87CEEB');
                                    layer._path.setAttribute('fill', '#B0E0E6');
                                    layer._path.setAttribute('fill-opacity', '0.4');
                                    layer._path.setAttribute('stroke-opacity', '0.7');
                                }
                            }
                        }
                    }, 100);
                    
                    const props = feature.properties || {};
                    const popupContent = generatePopupContent(props);
                    layer.bindPopup(popupContent);
                }
            });
            
            // Add to map
            if (currentLayer) {
                currentLayer.addTo(map);
                console.log('Layer added to map successfully. Features:', validFeatures.length);
                
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
            } else {
                throw new Error('Failed to create layer - currentLayer is null');
            }
            
        } catch (layerError) {
            console.error('Error creating layer:', layerError);
            console.error('Layer error stack:', layerError.stack);
            
            console.error('Error memaparkan data:', layerError.message);
            throw new Error('Error memaparkan data pada peta: ' + layerError.message);
        }
        
        // Fit map to Kedah bounds - always center on Kedah, ignore points outside
        try {
            // Always use Kedah bounds to keep map centered on Kedah
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
        
        // Update chart with current distribution type and load boundary
        try {
            await updateDistributionChart();
            console.log('Chart and boundary updated successfully');
        } catch (chartError) {
            console.error('Error updating chart:', chartError);
        }
        
        // Log success message
        console.log(`Data dimuatkan: ${validFeatures.length} rekod dipaparkan pada peta`);
        
        console.log('Data loaded successfully:', validFeatures.length, 'features displayed on map');
        
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

// Verify loadCategory is accessible
console.log('loadCategory defined:', typeof window.loadCategory === 'function');
if (typeof window.loadCategory !== 'function') {
    console.error('CRITICAL: loadCategory is not a function!');
}

// Default card data untuk auto-load (Profil Perumahan KEDA)
const defaultCardData = <?php echo $defaultCard ? json_encode($defaultCard, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null'; ?>;

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
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Map element not found!');
        return;
    }
    
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded!');
        alert('Leaflet library tidak dimuatkan. Sila refresh page.');
        return;
    }
    
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
    loadBoundariesForGeocoding();
    
    // Load default boundary layer (Daerah) after map is ready
    setTimeout(() => {
        loadBoundaryLayerByType('DAERAH');
    }, 1000);
    
    console.log('Map created successfully');
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
    
    // Always proceed to load default card even if boundaries failed
    if (defaultCardData && defaultCardData.kategori && defaultCardData.filepath) {
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
            setTimeout(() => {
                if (window.loadCategory) {
                    window.loadCategory(defaultCardData.kategori, defaultCardData.filepath, cardElement);
                } else {
                    console.error('loadCategory not available for default card');
                }
            }, 500);
        }
    }
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

// Data Quality functions removed - panel no longer exists

// currentChartData already declared above - removed duplicate

// Update distribution chart based on selected type - Make it globally accessible
window.updateDistributionChart = async function updateDistributionChart() {
    if (!currentChartData) {
        console.warn('No data available for chart');
        return;
    }
    
    const distributionType = document.getElementById('distributionType')?.value || 'DAERAH';
    
    // Update chart
    updateDaerahChart(currentChartData, distributionType);
    
    // Load and display corresponding boundary layer on map
    await loadBoundaryLayerByType(distributionType);
}

// Update Doughnut Chart by Daerah/Parlimen/DUN - Same style as dashboard_staf
function updateDaerahChart(data, fieldType = 'DAERAH') {
    const count = {};
    let totalRecords = 0;
    let recordsWithData = 0;
    let recordsWithoutData = 0;
    
    data.features.forEach(feature => {
        totalRecords++;
        const value = feature.properties?.[fieldType];
        
        // Check if value is empty, null, undefined, or just whitespace
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            recordsWithoutData++;
            count['Tiada Data'] = (count['Tiada Data'] || 0) + 1;
        } else {
            recordsWithData++;
        count[value] = (count[value] || 0) + 1;
        }
    });
    
    // Sort labels - put "Tiada Data" at the end if it exists
    const sortedEntries = Object.entries(count).sort((a, b) => {
        if (a[0] === 'Tiada Data') return 1;
        if (b[0] === 'Tiada Data') return -1;
        return b[1] - a[1]; // Sort by count descending
    });
    
    const labels = sortedEntries.map(([key]) => key.toUpperCase());
    const values = sortedEntries.map(([, value]) => value);
    
    const ctx = document.getElementById('daerahChart');
    
    if (!ctx) {
        console.error('Chart canvas element not found');
        return;
    }
    
    if (daerahChart) {
        daerahChart.destroy();
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
    
    // Use grey color for "Tiada Data" segment
    const finalColors = labels.map((label, index) => {
        if (label === 'TIADA DATA') {
            return '#95A5A6'; // Grey color for missing data
        }
        return colors[index] || colorPalette[index % colorPalette.length];
    });
    
    // Add subtitle with statistics
    const subtitleText = `Total: ${totalRecords} | Dengan Data: ${recordsWithData} | Tiada Data: ${recordsWithoutData}`;
    
    // Log warning if there are records without data
    if (recordsWithoutData > 0) {
        console.warn(`⚠️ ${recordsWithoutData} rekod (${((recordsWithoutData/totalRecords)*100).toFixed(1)}%) tiada maklumat ${fieldName}.`);
        console.warn('💡 Sila jalankan batch_spatial_update_geojson.php untuk memproses data yang belum ditag.');
    }
    
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
        
        mapContainer.style.height = '';
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
            chartContainer.style.height = '';
            chartContainer.style.width = '';
        }
        
        // Reset canvas container div
        const canvasContainer = chartCanvas.parentElement;
        if (canvasContainer && canvasContainer !== chartContainer) {
            canvasContainer.style.height = '';
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
    console.log('Initializing dashboard...');
    console.log('Leaflet available:', typeof L !== 'undefined');
    console.log('Map element exists:', document.getElementById('map') !== null);
    
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
    console.log('Map initialized:', map);
    
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
    if (!window.dashboardInitialized && typeof L !== 'undefined') {
        console.log('Initializing from window.onload...');
        window.initializeDashboard();
    }
});

// Handle upload form submission
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('geojson_file');
            const submitBtn = uploadForm.querySelector('button[type="submit"]');
            
            if (fileInput && fileInput.files.length > 0) {
                // Show loading state
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
                    
                    // Re-enable after 5 seconds (in case of error)
                    setTimeout(function() {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 5000);
                }
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>
