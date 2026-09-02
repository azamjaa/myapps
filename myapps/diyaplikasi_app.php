<?php
/**
 * DIY Aplikasi - Memaparkan aplikasi yang dijana dengan dashboard dan list view
 * 
 * @author MyApps KEDA
 * @version 1.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/diyaplikasi_sso_helper.php';

// Elak browser cache: sentiasa dapat versi baharu tanpa CTRL+F5
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Vary: *');

// SSO + SSOT: Sahkan pengguna (session atau JWT), identiti dari DB
nocode_ensure_auth();

// Get app slug
$appSlug = $_GET['app'] ?? '';

if (empty($appSlug)) {
    header("Location: diyaplikasi_builder.php");
    exit();
}

$req = $_SERVER['REQUEST_URI'] ?? '';

// Redirect ke pretty URL jika user akses melalui diyaplikasi_app.php?app=xxx
if (strpos($req, 'diyaplikasi_app.php') !== false && preg_match('/^[a-zA-Z0-9_-]+$/', $appSlug)) {
    header('Location: apps/' . $appSlug . '?_=' . time(), true, 302);
    exit();
}

// PASTI satu paparan sahaja (baharu): bila tiada _= , redirect ke URL dengan _= supaya browser tak guna cache lama
if (!isset($_GET['_'])) {
    $path = parse_url($req, PHP_URL_PATH);
    if (!$path || $path === '/' || $path === '') {
        $path = '/myapps/apps/' . $appSlug;
    }
    header('Cache-Control: no-store, no-cache');
    header('Location: ' . $path . '?_=' . time(), true, 302);
    exit();
}

// Load app metadata
$stmt = $db->prepare("SELECT * FROM nocode_apps WHERE app_slug = ? AND status = 1");
$stmt->execute([$appSlug]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    header("Location: diyaplikasi_builder.php");
    exit();
}

// Parse schema and settings
$schema = json_decode($app['schema_json'], true);
$settings = json_decode($app['settings_json'], true);
$tableName = $app['table_name'];

// Get all records
$records = [];
try {
    $stmt = $db->query("SELECT * FROM `{$tableName}` ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        $recordData = json_decode($row['record_data'], true);
        if ($recordData) {
            $records[] = [
                'id' => $row['id'],
                'data' => $recordData,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error loading records: " . $e->getMessage());
}

// ---------- Export Excel (HTML table .xls seperti Dashboard Perjawatan) ----------
if (isset($_GET['export']) && $_GET['export'] == '1') {
    if (ob_get_length()) ob_end_clean();
    $safeAppName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $appSlug);
    $filename = $safeAppName . '_' . date('Ymd') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $displayNameRaw = function($n) {
        $s = str_replace(['-', '_'], ' ', $n);
        return ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $s))));
    };
    $displayAlias = ['nama kampung' => 'Nama Desa', 'nama_kampung' => 'Nama Desa'];
    $displayNameExport = function($n) use ($displayNameRaw, $displayAlias) {
        $k = strtolower(trim(str_replace(['_', '-'], ' ', $n)));
        return $displayAlias[$k] ?? $displayNameRaw($n);
    };
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta http-equiv="content-type" content="text/html; charset=UTF-8"/>';
    echo '<style>table{border-collapse:collapse;width:100%;}th,td{border:1px solid #000;padding:5px;}th{background:#2563eb;color:#fff;}</style></head><body><table>';
    echo '<tr>';
    echo '<th>#</th>';
    foreach (array_slice($schema, 0, 10) as $col) {
        echo '<th>' . htmlspecialchars($displayNameExport($col['name'])) . '</th>';
    }
    echo '</tr>';
    foreach ($records as $i => $rec) {
        $d = $rec['data'] ?? [];
        echo '<tr><td>' . ($i + 1) . '</td>';
        foreach (array_slice($schema, 0, 10) as $col) {
            $v = $d[$col['name']] ?? '';
            echo '<td>' . htmlspecialchars((string)$v) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit();
}

// Kolum untuk list view (filter + jadual): maksimum 8 kolum pertama
$listViewColumns = array_slice($schema, 0, 8);
$filterUniqueValues = [];
foreach ($listViewColumns as $col) {
    $name = $col['name'];
    $vals = [];
    foreach ($records as $r) {
        $v = trim((string)($r['data'][$name] ?? ''));
        if ($v !== '') $vals[$v] = true;
    }
    $filterUniqueValues[$name] = array_keys($vals);
    sort($filterUniqueValues[$name]);
}
// Kolum paparan jadual (5 pertama + Tindakan)
$tableDisplayColumns = array_slice($schema, 0, 5);

// ========== DASHBOARD BEROTAK (benchmark Dashboard Perjawatan) ==========
// Tajuk kad ikut kesesuaian (bukan sebijik dari Excel): ganti _/-, format huruf, maksud kekal.
$totalRecords = count($records);
$colorList = [
    ['#10B981', '#059669', 'fa-users'],
    ['#F59E0B', '#D97706', 'fa-briefcase'],
    ['#EF4444', '#DC2626', 'fa-building'],
    ['#3B82F6', '#1E40AF', 'fa-chart-line'],
    ['#8B5CF6', '#6D28D9', 'fa-database'],
    ['#EC4899', '#DB2777', 'fa-list'],
    ['#14B8A6', '#0D9488', 'fa-layer-group'],
    ['#6366F1', '#4F46E5', 'fa-chart-pie']
];
// Papar nama sesuai: NOMBOR_LOT → Nombor Lot; alias tertentu (nama kampung → nama desa)
$displayNameRaw = function($name) {
    $s = str_replace(['-', '_'], ' ', $name);
    $s = preg_replace('/\s+/', ' ', trim($s));
    return ucwords(strtolower($s));
};
$displayAlias = [ 'nama kampung' => 'Nama Desa', 'nama_kampung' => 'Nama Desa' ];
$displayName = function($name) use ($displayNameRaw, $displayAlias) {
    $key = strtolower(trim(str_replace(['_', '-'], ' ', $name)));
    $key = preg_replace('/\s+/', ' ', $key);
    return $displayAlias[$key] ?? $displayNameRaw($name);
};

// Kolum nombor yang TIDAK sesuai untuk kad ringkasan (identifier/rujukan, bukan metrik)
$skipNumericForCard = function($colName) {
    $n = strtolower(trim(str_replace(['_', '-'], ' ', $colName)));
    if (in_array($n, ['id', 'objectid', 'object id', 'objek id'])) return true;
    if (in_array($n, ['nombor lot', 'no lot', 'nombor rumah', 'no rumah'])) return true;
    if (preg_match('/^nombor\s+(lot|rumah)$/', $n) || preg_match('/^no\s+(lot|rumah)$/', $n)) return true;
    if (preg_match('/^(no|nombor)\s*(siri|rujukan|fail|pt)$/', $n)) return true;
    return false;
};

$stats = [];
$cardIndex = 0;
$chartsByColumn = []; // kolum -> [labels, counts] untuk detail carta bila klik kad

// 1) Kad Total Rekod (sentiasa)
$stats[] = [
    'name' => 'Total Rekod',
    'display_name' => 'Total Rekod',
    'value' => $totalRecords,
    'count' => $totalRecords,
    'color' => $colorList[0][0],
    'color2' => $colorList[0][1],
    'icon' => $colorList[0][2],
    'stat_type' => 'total',
    'column' => null
];
$cardIndex = 1;

// 2) Kad untuk kolum nombor yang sesuai sahaja (jumlah yang bermakna: cukai, jumlah, dll. – skip ID, objectid, nombor lot, nombor rumah)
foreach ($schema as $col) {
    if ($col['type'] !== 'number' || $cardIndex >= 8) continue;
    $colName = $col['name'];
    if ($skipNumericForCard($colName)) continue;
    $sum = 0;
    $count = 0;
    foreach ($records as $record) {
        $value = $record['data'][$colName] ?? 0;
        if (is_numeric($value)) {
            $sum += floatval($value);
            $count++;
        }
    }
    if ($count > 0) {
        $c = $colorList[$cardIndex % count($colorList)];
        $stats[] = [
            'name' => $colName,
            'display_name' => $displayName($colName),
            'value' => $sum,
            'count' => $count,
            'color' => $c[0],
            'color2' => $c[1],
            'icon' => $c[2],
            'stat_type' => 'numeric',
            'column' => $colName
        ];
        $cardIndex++;
    }
}

// 3) Kad untuk kolum kategori + bangunkan data carta per kolum (untuk klik kad)
foreach ($schema as $col) {
    $colName = $col['name'];
    $countByValue = [];
    foreach ($records as $record) {
        $v = trim((string)($record['data'][$colName] ?? ''));
        $label = $v === '' ? '(Kosong)' : $v;
        $countByValue[$label] = ($countByValue[$label] ?? 0) + 1;
    }
    $numLabels = count($countByValue);
    if ($numLabels >= 2 && $numLabels <= 80) {
        arsort($countByValue);
        $chartsByColumn[$colName] = [
            'labels' => array_map($displayName, array_keys($countByValue)),
            'counts' => array_values($countByValue)
        ];
    }
    if ($cardIndex >= 8) continue;
    $values = [];
    foreach ($records as $record) {
        $v = trim((string)($record['data'][$colName] ?? ''));
        if ($v !== '') $values[$v] = true;
    }
    $distinct = count($values);
    if ($distinct <= 0 || $distinct > 200) continue;
    $c = $colorList[$cardIndex % count($colorList)];
    $stats[] = [
        'name' => $colName,
        'display_name' => $displayName($colName),
        'value' => $distinct,
        'count' => $distinct,
        'color' => $c[0],
        'color2' => $c[1],
        'icon' => $c[2],
        'stat_type' => 'category',
        'column' => $colName
    ];
    $cardIndex++;
}

// 4) Carta lalai: pilih kolum terbaik untuk Bar + Donut
$chartByColumn = null;
$chartLabels = [];
$chartCounts = [];
if (!empty($chartsByColumn)) {
    $best = null;
    foreach ($chartsByColumn as $colName => $data) {
        $n = count($data['labels']);
        if ($best === null || abs($n - 20) < abs(count($chartCounts) - 20)) {
            $best = $colName;
            $chartLabels = $data['labels'];
            $chartCounts = $data['counts'];
        }
    }
    if ($best !== null) $chartByColumn = $best;
}
if ($chartByColumn === null && !empty($stats)) {
    $chartLabels = array_map($displayName, array_slice(array_column($stats, 'name'), 0, 12));
    $chartCounts = array_slice(array_column($stats, 'value'), 0, 12);
    $chartByColumn = 'Ringkasan';
}

// Set app info for DIY Aplikasi header (menu sendiri - logo KEDA + keterangan seperti MyApps)
$nocode_app = [
    'app_name' => $app['app_name'],
    'app_slug' => $appSlug,
    'description' => !empty($app['description']) ? $app['description'] : 'Aplikasi KEDA'
];
include 'diyaplikasi_header.php';
?>

<div class="container-fluid mt-4 px-4" id="dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark">
            <i class="fas <?php echo htmlspecialchars($settings['icon'] ?? 'fa-database'); ?> me-3 text-primary"></i>
            <?php echo htmlspecialchars($app['app_name']); ?>
        </h3>
        <small class="text-muted">
            <i class="fas fa-clock me-1"></i>
            Rekod dikemaskini: <?php echo date('d/m/Y H:i'); ?>
        </small>
    </div>
    
    <?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Summary Cards: saiz besar & style klik sama Dashboard Aplikasi / Perjawatan (3 kad sebaris) -->
    <style>
    .summary-card-nocode {
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .summary-card-nocode:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
    }
    .summary-card-nocode.active {
        box-shadow: 0 20px 50px rgba(0,0,0,0.4), 0 10px 25px rgba(0,0,0,0.3) !important;
        transform: scale(1.10);
        z-index: 10;
        border: 3px solid rgba(0,0,0,0.2) !important;
    }
    .summary-card-nocode.active:hover {
        transform: scale(1.10) translateY(-3px);
        box-shadow: 0 25px 60px rgba(0,0,0,0.45), 0 15px 30px rgba(0,0,0,0.35) !important;
    }
    </style>
    <div class="row g-4 mb-4">
        <?php foreach ($stats as $idx => $stat): 
            $disp = $stat['display_name'] ?? str_replace('-', ' ', $stat['name']);
            $st = $stat['stat_type'] ?? 'total';
            $col = $stat['column'] ?? '';
        ?>
        <div class="col-md-3 col-sm-6 col-6">
            <div class="card border-0 shadow-sm h-100 summary-card-nocode" 
                 data-stat-type="<?php echo htmlspecialchars($st); ?>"
                 data-column="<?php echo htmlspecialchars($col); ?>"
                 data-index="<?php echo $idx; ?>"
                 style="border-left: 5px solid <?php echo $stat['color']; ?> !important; 
                        background: linear-gradient(135deg, #ffffff 0%, <?php echo $stat['color']; ?>08 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between py-4 px-4">
                    <div>
                        <h6 class="text-muted text-uppercase"><?php echo htmlspecialchars($disp); ?></h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, <?php echo $stat['color']; ?> 0%, <?php echo $stat['color2']; ?> 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            <?php 
                            $isInt = ($stat['value'] == (int)$stat['value']);
                            echo $isInt ? number_format((int)$stat['value'], 0) : number_format($stat['value'], 2); 
                            ?>
                        </h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, <?php echo $stat['color']; ?> 0%, <?php echo $stat['color2']; ?> 100%); flex-shrink: 0;">
                        <i class="fas <?php echo $stat['icon']; ?> fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Detail Carta (tunjuk bila klik kad kategori; Bar + Donut) -->
    <div class="row g-4 mb-4" id="detailCartaSection" style="display: none;">
        <div class="col-12"><h5 class="mb-3 fw-bold text-primary"><i class="fas fa-chart-bar me-2"></i><span id="detailCartaTitle">Visualisasi Data</span></h5></div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Bilangan Rekod Mengikut <span id="detailBarTitle">Kategori</span></h6>
                </div>
                <div class="card-body">
                    <div style="height: 380px;"><canvas id="detailBarChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Peratus Rekod Mengikut <span id="detailDonutTitle">Kategori</span></h6>
                </div>
                <div class="card-body">
                    <div style="height: 380px;"><canvas id="detailDonutChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Visualisasi Data lalai (Bar + Donut) -->
    <div class="row g-4 mb-4" id="carta">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>Bilangan Rekod Mengikut <?php echo htmlspecialchars($displayName($chartByColumn ?? 'Kategori')); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 380px; position: relative;"><canvas id="barChartNoCode"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>Peratus Rekod Mengikut <?php echo htmlspecialchars($displayName($chartByColumn ?? 'Kategori')); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 380px; position: relative;"><canvas id="donutChartNoCode"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Senarai Rekod (standard style seperti list view perjawatan) -->
    <div class="row g-4">
        <div class="col-12" id="senarai">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-list me-2 text-primary"></i>Senarai Rekod
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <div class="card shadow-sm mb-4 border-0" style="background-color: #f8f9fa;">
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="row g-2">
                                    <?php foreach ($listViewColumns as $col): ?>
                                    <?php
                                    $colName = $col['name'];
                                    $uniqueVals = $filterUniqueValues[$colName] ?? [];
                                    // Gunakan dropdown bila ada pilihan (1–80 nilai unik); lebih dari itu kekal input teks
                                    $useSelect = count($uniqueVals) >= 1 && count($uniqueVals) <= 80;
                                    $labelDisplay = $displayName($colName);
                                    ?>
                                    <div class="col-md-3 col-lg-2">
                                        <label class="form-label small text-muted mb-1"><?php echo htmlspecialchars($labelDisplay); ?></label>
                                        <?php if ($useSelect): ?>
                                        <select id="filter_<?php echo htmlspecialchars($colName); ?>" class="form-select form-select-sm" style="padding-right: 2.8rem; text-align-last: left; background-position: right 0.4rem center;" onchange="applyNoCodeFilters()">
                                            <option value="">Semua <?php echo htmlspecialchars($labelDisplay); ?></option>
                                            <?php foreach ($uniqueVals as $uv): ?>
                                            <option value="<?php echo htmlspecialchars($uv); ?>"><?php echo htmlspecialchars($uv); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php else: ?>
                                        <input type="text" id="filter_<?php echo htmlspecialchars($colName); ?>" class="form-control form-control-sm" placeholder="Filter <?php echo htmlspecialchars($labelDisplay); ?>" onkeyup="applyNoCodeFilters()">
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-3 col-lg-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-warning w-100" onclick="clearNoCodeFilters()">
                                            <i class="fas fa-times me-1"></i>Clear Filter
                                        </button>
                                    </div>
                                    <div class="col-md-3 col-lg-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="showAddModal()">
                                            <i class="fas fa-plus me-1"></i>Tambah Rekod
                                        </button>
                                    </div>
                                    <div class="col-md-3 col-lg-2 d-flex align-items-end">
                                        <a href="<?php echo isset($BASE) ? $BASE : '/myapps/'; ?>diyaplikasi_app.php?app=<?php echo htmlspecialchars(urlencode($appSlug)); ?>&export=1" class="btn btn-sm btn-success w-100" target="_blank">
                                            <i class="fas fa-file-excel me-1"></i>Export Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted small">
                                    Menunjukkan <strong id="nocodeShowingFrom">0</strong> - <strong id="nocodeShowingTo">0</strong> daripada <strong id="nocodeTotalRecords">0</strong> rekod
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-striped table-hover table-sm" id="nocodeTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="py-3 px-3 text-center" width="5%">BIL</th>
                                    <?php foreach ($tableDisplayColumns as $col): ?>
                                    <th class="py-3"><?php echo htmlspecialchars($displayName($col['name'])); ?></th>
                                    <?php endforeach; ?>
                                    <th class="py-3 text-center px-3">TINDAKAN</th>
                                </tr>
                            </thead>
                            <tbody id="nocodeTableBody">
                                <!-- Diisi oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div id="nocodePagination" class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Tambah Rekod</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="recordForm">
                    <input type="hidden" id="recordId" name="record_id">
                    <?php foreach ($schema as $col): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo htmlspecialchars($displayName($col['name'])); ?></label>
                        <?php if ($col['type'] === 'date'): ?>
                            <input type="date" class="form-control" name="<?php echo htmlspecialchars($col['name']); ?>" 
                                   id="field_<?php echo htmlspecialchars($col['name']); ?>">
                        <?php elseif ($col['type'] === 'number'): ?>
                            <input type="number" class="form-control" name="<?php echo htmlspecialchars($col['name']); ?>" 
                                   id="field_<?php echo htmlspecialchars($col['name']); ?>" step="any">
                        <?php else: ?>
                            <input type="text" class="form-control" name="<?php echo htmlspecialchars($col['name']); ?>" 
                                   id="field_<?php echo htmlspecialchars($col['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveRecord()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
// Data carta auto dari rekod (Bilangan & Peratus mengikut kategori)
const chartLabels = <?php echo json_encode($chartLabels); ?>;
const chartCounts = <?php echo json_encode($chartCounts); ?>;
const colorPalette = ['#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#EC4899','#14B8A6','#6366F1','#F97316','#84CC16','#06B6D4','#A855F7'];
const barColors = chartLabels.map(function(_, i) { return colorPalette[i % colorPalette.length]; });
// Data carta per kolum (bila klik kad kategori → detail carta)
const chartsByColumn = <?php echo json_encode($chartsByColumn); ?>;

// List view: rekod + kolum paparan + pagination
const noCodeRecords = <?php echo json_encode($records); ?>;
const noCodeTableColumns = <?php echo json_encode(array_map(function($c){ return $c['name']; }, $tableDisplayColumns)); ?>;
const noCodeFilterColumns = <?php echo json_encode(array_map(function($c){ return $c['name']; }, $listViewColumns)); ?>;
const noCodePageSize = 20;
let noCodeCurrentPage = 1;
let noCodeFilteredRecords = [];

function displayNameCell(name) {
    var key = (name || '').replace(/[-_]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
    if (key === 'nama kampung') return 'Nama Desa';
    var s = (name || '').replace(/[-_]/g, ' ').replace(/\s+/g, ' ').trim();
    return s.split(' ').map(function(w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); }).join(' ');
}
function applyNoCodeFilters() {
    var filters = {};
    noCodeFilterColumns.forEach(function(col) {
        var el = document.getElementById('filter_' + col);
        if (el) filters[col] = (el.value || '').trim().toLowerCase();
    });
    noCodeFilteredRecords = noCodeRecords.filter(function(rec) {
        var d = rec.data || {};
        for (var c in filters) {
            if (!filters[c]) continue;
            var val = (d[c] ?? '').toString().trim().toLowerCase();
            if (val.indexOf(filters[c]) === -1) return false;
        }
        return true;
    });
    noCodeCurrentPage = 1;
    renderNoCodeTable();
}
function clearNoCodeFilters() {
    noCodeFilterColumns.forEach(function(col) {
        var el = document.getElementById('filter_' + col);
        if (el) el.value = '';
    });
    noCodeFilteredRecords = noCodeRecords.slice();
    noCodeCurrentPage = 1;
    renderNoCodeTable();
}
function renderNoCodeTable() {
    var list = noCodeFilteredRecords;
    var total = list.length;
    var from = (noCodeCurrentPage - 1) * noCodePageSize;
    var to = Math.min(from + noCodePageSize, total);
    var pageRows = list.slice(from, to);
    var tbody = document.getElementById('nocodeTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (total === 0) {
        tbody.innerHTML = '<tr><td colspan="' + (noCodeTableColumns.length + 2) + '" class="text-center text-muted py-4">Tiada rekod dijumpai</td></tr>';
    } else {
        pageRows.forEach(function(rec, idx) {
            var rowNum = from + idx + 1;
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="text-center">' + rowNum + '</td>';
            noCodeTableColumns.forEach(function(col) {
                var v = (rec.data && rec.data[col]) ?? '';
                var disp = displayNameCell(String(v));
                if (disp.length > 30) disp = disp.substring(0, 30) + '...';
                tr.innerHTML += '<td>' + escapeHtml(disp) + '</td>';
            });
            tr.innerHTML += '<td class="text-center"><button class="btn btn-sm btn-warning" onclick="editRecord(' + rec.id + ')" title="Edit"><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-danger" onclick="deleteRecord(' + rec.id + ')" title="Padam"><i class="fas fa-trash"></i></button></td>';
            tbody.appendChild(tr);
        });
    }
    var fromEl = document.getElementById('nocodeShowingFrom');
    var toEl = document.getElementById('nocodeShowingTo');
    var totalEl = document.getElementById('nocodeTotalRecords');
    if (fromEl) fromEl.textContent = total ? (from + 1) : 0;
    if (toEl) toEl.textContent = to;
    if (totalEl) totalEl.textContent = total;
    var totalPages = Math.max(1, Math.ceil(total / noCodePageSize));
    var pagEl = document.getElementById('nocodePagination');
    if (pagEl) {
        if (totalPages <= 1) {
            pagEl.innerHTML = '';
        } else {
            pagEl.innerHTML = '<span class="text-muted small">Halaman ' + noCodeCurrentPage + ' daripada ' + totalPages + '</span>' +
                '<div><button type="button" class="btn btn-sm btn-outline-secondary me-1" ' + (noCodeCurrentPage <= 1 ? 'disabled' : '') + ' onclick="noCodeCurrentPage--; renderNoCodeTable();">Sebelum</button>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" ' + (noCodeCurrentPage >= totalPages ? 'disabled' : '') + ' onclick="noCodeCurrentPage++; renderNoCodeTable();">Seterusnya</button></div>';
        }
    }
}
function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}
// Init list view
document.addEventListener('DOMContentLoaded', function() {
    noCodeFilteredRecords = noCodeRecords.slice();
    renderNoCodeTable();
});

if (chartLabels.length > 0 && document.getElementById('barChartNoCode')) {
    new Chart(document.getElementById('barChartNoCode').getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartLabels.map(function(l) { return l.length > 25 ? l.substring(0, 22) + '...' : l; }),
            datasets: [{ label: 'Bilangan', data: chartCounts, backgroundColor: barColors, borderRadius: 8, borderWidth: 0 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'x',
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
}
if (chartLabels.length > 0 && document.getElementById('donutChartNoCode')) {
    new Chart(document.getElementById('donutChartNoCode').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{ data: chartCounts, backgroundColor: barColors, borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            var p = (ctx.raw / ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0) * 100).toFixed(1);
                            return ctx.label + ' (' + p + '%)';
                        }
                    }
                }
            }
        }
    });
}

// Klik kad → detail rekod (scroll ke senarai) atau detail carta (Bar + Donut) seperti Dashboard Perjawatan
let detailBarChartInst = null, detailDonutChartInst = null;
function displayNameCol(name) {
    var key = (name || '').replace(/[-_]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
    if (key === 'nama kampung') return 'Nama Desa';
    var s = (name || '').replace(/[-_]/g, ' ').replace(/\s+/g, ' ').trim();
    return s.split(' ').map(function(w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); }).join(' ');
}
function showDetailChart(columnName) {
    var data = chartsByColumn && chartsByColumn[columnName];
    if (!data || !data.labels || data.labels.length === 0) return;
    var title = displayNameCol(columnName);
    document.getElementById('detailCartaTitle').textContent = 'Detail: ' + title;
    document.getElementById('detailBarTitle').textContent = title;
    document.getElementById('detailDonutTitle').textContent = title;
    document.getElementById('detailCartaSection').style.display = '';
    var cartaEl = document.getElementById('carta');
    if (cartaEl) cartaEl.style.display = 'none';
    var labels = data.labels, counts = data.counts;
    var colors = labels.map(function(_, i) { return colorPalette[i % colorPalette.length]; });
    var barShort = labels.map(function(l) { return l.length > 25 ? l.substring(0, 22) + '...' : l; });
    var ctxBar = document.getElementById('detailBarChart');
    var ctxDonut = document.getElementById('detailDonutChart');
    if (!ctxBar || !ctxDonut) return;
    if (detailBarChartInst) { detailBarChartInst.destroy(); detailBarChartInst = null; }
    if (detailDonutChartInst) { detailDonutChartInst.destroy(); detailDonutChartInst = null; }
    detailBarChartInst = new Chart(ctxBar.getContext('2d'), {
        type: 'bar',
        data: { labels: barShort, datasets: [{ label: 'Bilangan', data: counts, backgroundColor: colors, borderRadius: 8, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'x', scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
    detailDonutChartInst = new Chart(ctxDonut.getContext('2d'), {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: counts, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: function(ctx) {
                    var t = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                    var p = t ? (ctx.raw / t * 100).toFixed(1) : 0;
                    return ctx.label + ' (' + p + '%)';
                }}}
            }
        }
    });
    document.getElementById('detailCartaSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function showDetailRekod() {
    document.getElementById('detailCartaSection').style.display = 'none';
    var cartaEl = document.getElementById('carta');
    if (cartaEl) cartaEl.style.display = '';
    var el = document.getElementById('senarai');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
document.querySelectorAll('.summary-card-nocode').forEach(function(card) {
    card.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.summary-card-nocode').forEach(function(c){ c.classList.remove('active'); });
        card.classList.add('active');
        var statType = card.getAttribute('data-stat-type') || 'total';
        var column = card.getAttribute('data-column') || '';
        if (statType === 'category' && column && chartsByColumn && chartsByColumn[column]) {
            showDetailChart(column);
        } else {
            showDetailRekod();
        }
    });
});

// Modal functions
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Rekod';
    document.getElementById('recordForm').reset();
    document.getElementById('recordId').value = '';
    new bootstrap.Modal(document.getElementById('recordModal')).show();
}

function editRecord(id) {
    var base = '<?php echo isset($BASE) ? $BASE : "/myapps/"; ?>';
    fetch(base + 'diyaplikasi_api.php?action=get&app=<?php echo htmlspecialchars($appSlug); ?>&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Rekod';
                document.getElementById('recordId').value = id;
                const recordData = data.record;
                <?php foreach ($schema as $col): ?>
                document.getElementById('field_<?php echo htmlspecialchars($col['name']); ?>').value = recordData['<?php echo htmlspecialchars($col['name']); ?>'] || '';
                <?php endforeach; ?>
                new bootstrap.Modal(document.getElementById('recordModal')).show();
            }
        });
}

function saveRecord() {
    var base = '<?php echo isset($BASE) ? $BASE : "/myapps/"; ?>';
    const formData = new FormData(document.getElementById('recordForm'));
    formData.append('action', 'save');
    formData.append('app', '<?php echo htmlspecialchars($appSlug); ?>');
    formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
    
    fetch(base + 'diyaplikasi_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ralat: ' + (data.message || 'Gagal menyimpan'));
        }
    });
}

function deleteRecord(id) {
    if (confirm('Anda pasti mahu padam rekod ini?')) {
        var base = '<?php echo isset($BASE) ? $BASE : "/myapps/"; ?>';
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('app', '<?php echo htmlspecialchars($appSlug); ?>');
        formData.append('id', id);
        formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
        
        fetch(base + 'diyaplikasi_api.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ralat: ' + (data.message || 'Gagal memadam'));
                }
            });
    }
}
</script>

</div><!-- end .nocode-main -->

<?php include 'footer.php'; ?>
