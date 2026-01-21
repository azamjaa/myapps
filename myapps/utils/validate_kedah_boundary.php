<?php
/**
 * Utility script to validate and fix GPS records that are outside Kedah state boundary
 * 
 * Usage: Access via web browser or run via CLI
 * 
 * This script:
 * 1. Checks all records in geojson_data against Kedah state boundary
 * 2. Identifies records outside the boundary
 * 3. Provides options to mark, delete, or manually fix them
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/reverse_geocode.php';
require_once __DIR__ . '/geocode.php';

// Kedah approximate bounds for quick validation
$kedahBounds = [
    'minLat' => 5.0,
    'maxLat' => 6.5,
    'minLng' => 99.5,
    'maxLng' => 101.0
];

/**
 * Check if point is within Kedah state boundary polygon (accurate check)
 */
function isWithinKedahBoundary($lng, $lat, $db) {
    try {
        // Get negeri boundary from database
        $stmt = $db->prepare("SELECT geometry FROM geojson_data WHERE kategori = 'negeri' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['geometry'])) {
            // No boundary data, fallback to bounds check
            global $kedahBounds;
            return isWithinKedahBounds($lng, $lat, $kedahBounds);
        }
        
        $geometry = json_decode($row['geometry'], true);
        if (!$geometry) {
            return false;
        }
        
        $point = [$lng, $lat];
        
        // Handle Polygon and MultiPolygon
        if ($geometry['type'] === 'Polygon' && isset($geometry['coordinates'][0])) {
            return pointInPolygon($point, $geometry['coordinates'][0]);
        } elseif ($geometry['type'] === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $multiPoly) {
                if (isset($multiPoly[0]) && pointInPolygon($point, $multiPoly[0])) {
                    return true;
                }
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking Kedah boundary: " . $e->getMessage());
        // Fallback to bounds check
        global $kedahBounds;
        return isWithinKedahBounds($lng, $lat, $kedahBounds);
    }
}

/**
 * Validate all records and find those outside boundary or missing GPS
 */
function validateRecords($db, $kategori = null, $includeMissingGPS = true) {
    global $kedahBounds;
    
    $invalidRecords = [];
    $missingGPSRecords = [];
    $totalChecked = 0;
    
    try {
        // Build query
        $query = "SELECT id, kategori, properties, geometry FROM geojson_data";
        $params = [];
        
        if ($kategori) {
            $query .= " WHERE kategori = ?";
            $params[] = $kategori;
        }
        
        // Exclude boundary categories
        if ($kategori) {
            $query .= " AND kategori NOT IN ('negeri', 'daerah', 'parlimen', 'dun')";
        } else {
            $query .= " WHERE kategori NOT IN ('negeri', 'daerah', 'parlimen', 'dun')";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $totalChecked++;
            
            $geometry = json_decode($row['geometry'], true);
            $props = json_decode($row['properties'], true);
            $name = $props['name'] ?? $props['NAMA'] ?? $props['Name'] ?? 'N/A';
            
            // Check if has GPS
            $hasGPS = hasGPS($geometry);
            
            if (!$hasGPS) {
                if ($includeMissingGPS) {
                    // Check if has address information
                    $address = buildAddressString($props);
                    if ($address) {
                        $missingGPSRecords[] = [
                            'id' => $row['id'],
                            'kategori' => $row['kategori'],
                            'lng' => null,
                            'lat' => null,
                            'name' => $name,
                            'reason' => 'Tiada GPS - ada alamat',
                            'address' => $address
                        ];
                    } else {
                        $missingGPSRecords[] = [
                            'id' => $row['id'],
                            'kategori' => $row['kategori'],
                            'lng' => null,
                            'lat' => null,
                            'name' => $name,
                            'reason' => 'Tiada GPS - tiada alamat',
                            'address' => null
                        ];
                    }
                }
                continue;
            }
            
            $coords = getCoordinatesFromGeometry($geometry);
            if (!$coords || count($coords) < 2) {
                continue;
            }
            
            $lng = floatval($coords[0]);
            $lat = floatval($coords[1]);
            
            // Quick bounds check first
            if (!isWithinKedahBounds($lng, $lat, $kedahBounds)) {
                // Definitely outside bounds - check if has address to geocode
                $address = buildAddressString($props);
                $invalidRecords[] = [
                    'id' => $row['id'],
                    'kategori' => $row['kategori'],
                    'lng' => $lng,
                    'lat' => $lat,
                    'name' => $name,
                    'reason' => 'GPS luar sempadan Kedah',
                    'address' => $address,
                    'can_geocode' => !empty($address)
                ];
            } else {
                // Within bounds, but check against actual boundary polygon for accuracy
                if (!isWithinKedahBoundary($lng, $lat, $db)) {
                    $address = buildAddressString($props);
                    $invalidRecords[] = [
                        'id' => $row['id'],
                        'kategori' => $row['kategori'],
                        'lng' => $lng,
                        'lat' => $lat,
                        'name' => $name,
                        'reason' => 'GPS luar sempadan Kedah (polygon)',
                        'address' => $address,
                        'can_geocode' => !empty($address)
                    ];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error validating records: " . $e->getMessage());
    }
    
    // Combine missing GPS and invalid GPS records
    $allInvalid = array_merge($missingGPSRecords, $invalidRecords);
    
    return [
        'total' => $totalChecked,
        'invalid' => $allInvalid,
        'count' => count($allInvalid),
        'missing_gps' => count($missingGPSRecords),
        'invalid_gps' => count($invalidRecords)
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($action === 'validate') {
            $kategori = $_POST['kategori'] ?? null;
            $result = validateRecords($db, $kategori);
            $response = [
                'success' => true,
                'data' => $result
            ];
        } elseif ($action === 'delete') {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids) || !is_array($ids)) {
                $response['message'] = 'Tiada ID rekod yang dipilih';
            } else {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("DELETE FROM geojson_data WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $response = [
                    'success' => true,
                    'message' => 'Berjaya memadam ' . count($ids) . ' rekod',
                    'deleted' => count($ids)
                ];
            }
        } elseif ($action === 'mark') {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids) || !is_array($ids)) {
                $response['message'] = 'Tiada ID rekod yang dipilih';
            } else {
                // Mark records by adding a flag in properties
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("SELECT id, properties FROM geojson_data WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                
                $updated = 0;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $props = json_decode($row['properties'], true);
                    if (!$props) {
                        $props = [];
                    }
                    $props['_invalid_location'] = true;
                    $props['_invalid_location_date'] = date('Y-m-d H:i:s');
                    
                    $updateStmt = $db->prepare("UPDATE geojson_data SET properties = ? WHERE id = ?");
                    $updateStmt->execute([json_encode($props, JSON_UNESCAPED_UNICODE), $row['id']]);
                    $updated++;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Berjaya menandakan ' . $updated . ' rekod sebagai tidak sah',
                    'marked' => $updated
                ];
            }
        } elseif ($action === 'check_missing_data') {
            // Check for records with missing DAERAH/PARLIMEN/DUN
            $kategori = $_POST['kategori'] ?? null;
            
            $query = "SELECT id, kategori, properties, geometry FROM geojson_data";
            $params = [];
            
            if ($kategori) {
                $query .= " WHERE kategori = ?";
                $params[] = $kategori;
            } else {
                $query .= " WHERE 1=1";
            }
            
            $query .= " AND kategori NOT IN ('negeri', 'daerah', 'parlimen', 'dun')";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $missingDataRecords = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $props = json_decode($row['properties'], true);
                if (!$props) {
                    $props = [];
                }
                
                $hasDaerah = !empty($props['DAERAH']);
                $hasParlimen = !empty($props['PARLIMEN']);
                $hasDun = !empty($props['DUN']);
                
                if (!$hasDaerah || !$hasParlimen || !$hasDun) {
                    $missingFields = [];
                    if (!$hasDaerah) $missingFields[] = 'DAERAH';
                    if (!$hasParlimen) $missingFields[] = 'PARLIMEN';
                    if (!$hasDun) $missingFields[] = 'DUN';
                    
                    $missingDataRecords[] = [
                        'id' => $row['id'],
                        'kategori' => $row['kategori'],
                        'name' => $props['name'] ?? $props['NAMA'] ?? $props['Name'] ?? 'N/A',
                        'missing_fields' => $missingFields,
                        'has_gps' => hasGPS(json_decode($row['geometry'], true)),
                        'gps_coords' => getCoordinatesFromGeometry(json_decode($row['geometry'], true))
                    ];
                }
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'records' => $missingDataRecords,
                    'count' => count($missingDataRecords)
                ]
            ];
        } elseif ($action === 'fix_missing_data') {
            // Fix missing DAERAH/PARLIMEN/DUN using reverse geocoding
            $ids = $_POST['ids'] ?? [];
            if (empty($ids) || !is_array($ids)) {
                $response['message'] = 'Tiada ID rekod yang dipilih';
            } else {
                require_once __DIR__ . '/reverse_geocode.php';
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("SELECT id, properties, geometry FROM geojson_data WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                
                $successCount = 0;
                $failCount = 0;
                $errors = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    try {
                        $props = json_decode($row['properties'], true);
                        $geom = json_decode($row['geometry'], true);
                        
                        if (!$props || !$geom) {
                            $failCount++;
                            $errors[] = 'ID ' . $row['id'] . ': Tiada properties atau geometry';
                            continue;
                        }
                        
                        // Enrich with reverse geocoding
                        $enrichedProps = enrichPropertiesWithGeocode($props, $geom, $db);
                        
                        // Check if anything changed
                        $changed = false;
                        if (empty($props['DAERAH']) && !empty($enrichedProps['DAERAH'])) {
                            $changed = true;
                        }
                        if (empty($props['PARLIMEN']) && !empty($enrichedProps['PARLIMEN'])) {
                            $changed = true;
                        }
                        if (empty($props['DUN']) && !empty($enrichedProps['DUN'])) {
                            $changed = true;
                        }
                        
                        if ($changed) {
                            // Update database
                            $updateStmt = $db->prepare("UPDATE geojson_data SET properties = ? WHERE id = ?");
                            $updateStmt->execute([json_encode($enrichedProps, JSON_UNESCAPED_UNICODE), $row['id']]);
                            $successCount++;
                        } else {
                            $failCount++;
                            $errors[] = 'ID ' . $row['id'] . ': Tidak dapat mendapatkan maklumat lokasi (GPS mungkin tiada atau luar sempadan)';
                        }
                    } catch (Exception $e) {
                        $failCount++;
                        $errors[] = 'ID ' . $row['id'] . ': ' . $e->getMessage();
                    }
                }
                
                $response = [
                    'success' => true,
                    'message' => "Berjaya mengemaskini $successCount rekod" . ($failCount > 0 ? ", $failCount gagal" : ""),
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'errors' => array_slice($errors, 0, 10)
                ];
            }
        } elseif ($action === 'geocode') {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids) || !is_array($ids)) {
                $response['message'] = 'Tiada ID rekod yang dipilih';
            } else {
                // Geocode selected records
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("SELECT id, properties, geometry FROM geojson_data WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                
                $successCount = 0;
                $failCount = 0;
                $errors = [];
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $result = geocodeRecord($row, $db);
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failCount++;
                        $errors[] = 'ID ' . $row['id'] . ': ' . $result['message'];
                    }
                    
                    // Rate limiting: sleep 1 second between geocoding requests
                    sleep(1);
                }
                
                $response = [
                    'success' => true,
                    'message' => "Berjaya mengemaskini GPS untuk $successCount rekod" . ($failCount > 0 ? ", $failCount gagal" : ""),
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'errors' => array_slice($errors, 0, 10) // Limit errors shown
                ];
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'Ralat: ' . $e->getMessage();
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Get all categories for filter
$categories = [];
try {
    $stmt = $db->query("SELECT DISTINCT kategori FROM geojson_data WHERE kategori NOT IN ('negeri', 'daerah', 'parlimen', 'dun') ORDER BY kategori");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semak & Betulkan Rekod GPS Luar Sempadan Kedah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .invalid-record {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .invalid-record:hover {
            background-color: #ffe69c;
        }
        .coords {
            font-family: monospace;
            color: #dc3545;
        }
        .loading {
            display: none;
        }
        .loading.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="fas fa-map-marker-alt text-danger"></i> Semak & Betulkan Rekod GPS Luar Sempadan Kedah</h2>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="validateForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="kategori" class="form-label">Kategori (Pilihan)</label>
                                    <select class="form-select" id="kategori" name="kategori">
                                        <option value="">Sila Pilih Dashboard</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="includeMissingGPS" checked>
                                        <label class="form-check-label" for="includeMissingGPS">
                                            <i class="fas fa-map-marker-alt"></i> Termasuk rekod tanpa GPS
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Semak Rekod
                                    </button>
                                    <button type="button" class="btn btn-info" id="checkMissingDataBtn">
                                        <i class="fas fa-info-circle"></i> Semak Tiada Rekod Alamat
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="loading" id="loading">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Memproses...</span>
                        </div>
                        <p class="mt-2">Sedang menyemak rekod...</p>
                    </div>
                </div>
                
                <div id="results" style="display: none;">
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Rekod Ditemui: <span id="invalidCount">0</span> / <span id="totalCount">0</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <button class="btn btn-sm btn-outline-primary" id="selectAll">
                                    <i class="fas fa-check-square"></i> Pilih Semua
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" id="deselectAll">
                                    <i class="fas fa-square"></i> Nyahpilih Semua
                                </button>
                                <button class="btn btn-sm btn-danger" id="deleteSelected">
                                    <i class="fas fa-trash"></i> Padam Rekod Terpilih
                                </button>
                                <button class="btn btn-sm btn-warning" id="markSelected">
                                    <i class="fas fa-flag"></i> Tandakan Sebagai Tidak Sah
                                </button>
                                <button class="btn btn-sm btn-success" id="geocodeSelected">
                                    <i class="fas fa-map-marker-alt"></i> Betulkan GPS dari Alamat
                                </button>
                            </div>
                            <div id="recordsList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentRecords = [];
        
        document.getElementById('validateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const kategori = document.getElementById('kategori').value;
            const includeMissingGPS = document.getElementById('includeMissingGPS').checked;
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            
            loading.classList.add('show');
            results.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('action', 'validate');
                if (kategori) {
                    formData.append('kategori', kategori);
                }
                formData.append('includeMissingGPS', includeMissingGPS ? '1' : '0');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentRecords = data.data.invalid;
                    displayResults(data.data);
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat menyemak rekod'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            } finally {
                loading.classList.remove('show');
            }
        });
        
        function displayResults(data) {
            document.getElementById('invalidCount').textContent = data.count;
            document.getElementById('totalCount').textContent = data.total;
            
            const recordsList = document.getElementById('recordsList');
            
            if (data.count === 0) {
                recordsList.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Tiada rekod yang berada di luar sempadan Kedah.</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr>';
                html += '<th><input type="checkbox" id="selectAllCheckbox"></th>';
                html += '<th>ID</th><th>Kategori</th><th>Nama</th><th>Koordinat</th><th>Sebab</th><th>Alamat</th>';
                html += '</tr></thead><tbody>';
                
                data.invalid.forEach(record => {
                    html += '<tr class="invalid-record">';
                    html += '<td><input type="checkbox" class="record-checkbox" value="' + record.id + '"' + 
                            (record.can_geocode !== false ? ' data-can-geocode="true"' : '') + '></td>';
                    html += '<td>' + record.id + '</td>';
                    html += '<td>' + escapeHtml(record.kategori) + '</td>';
                    html += '<td>' + escapeHtml(record.name) + '</td>';
                    if (record.lng !== null && record.lat !== null) {
                        html += '<td class="coords">' + record.lng.toFixed(6) + ', ' + record.lat.toFixed(6) + '</td>';
                    } else {
                        html += '<td class="coords text-muted">Tiada GPS</td>';
                    }
                    html += '<td><span class="badge bg-warning">' + escapeHtml(record.reason) + '</span></td>';
                    if (record.address) {
                        html += '<td><small class="text-muted">' + escapeHtml(record.address.substring(0, 50)) + 
                                (record.address.length > 50 ? '...' : '') + '</small></td>';
                    } else {
                        html += '<td><small class="text-danger">Tiada alamat</small></td>';
                    }
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                recordsList.innerHTML = html;
                
                // Setup checkbox handlers
                document.getElementById('selectAllCheckbox').addEventListener('change', function() {
                    document.querySelectorAll('.record-checkbox').forEach(cb => {
                        cb.checked = this.checked;
                    });
                });
            }
            
            document.getElementById('results').style.display = 'block';
        }
        
        document.getElementById('selectAll').addEventListener('click', function() {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        });
        
        document.getElementById('deselectAll').addEventListener('click', function() {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        });
        
        document.getElementById('deleteSelected').addEventListener('click', async function() {
            const selected = Array.from(document.querySelectorAll('.record-checkbox:checked')).map(cb => cb.value);
            
            if (selected.length === 0) {
                alert('Sila pilih sekurang-kurangnya satu rekod untuk dipadam.');
                return;
            }
            
            if (!confirm('Adakah anda pasti mahu memadam ' + selected.length + ' rekod? Tindakan ini tidak boleh dibatalkan.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                selected.forEach(id => formData.append('ids[]', id));
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Berjaya memadam ' + data.deleted + ' rekod.');
                    document.getElementById('validateForm').dispatchEvent(new Event('submit'));
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat memadam rekod'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            }
        });
        
        document.getElementById('markSelected').addEventListener('click', async function() {
            const selected = Array.from(document.querySelectorAll('.record-checkbox:checked')).map(cb => cb.value);
            
            if (selected.length === 0) {
                alert('Sila pilih sekurang-kurangnya satu rekod untuk ditandakan.');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'mark');
                selected.forEach(id => formData.append('ids[]', id));
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Berjaya menandakan ' + data.marked + ' rekod sebagai tidak sah.');
                    document.getElementById('validateForm').dispatchEvent(new Event('submit'));
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat menandakan rekod'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            }
        });
        
        document.getElementById('geocodeSelected').addEventListener('click', async function() {
            const selected = Array.from(document.querySelectorAll('.record-checkbox:checked'))
                .filter(cb => cb.dataset.canGeocode === 'true')
                .map(cb => cb.value);
            
            if (selected.length === 0) {
                alert('Sila pilih sekurang-kurangnya satu rekod yang mempunyai maklumat alamat untuk dibetulkan.');
                return;
            }
            
            if (!confirm('Adakah anda pasti mahu betulkan GPS untuk ' + selected.length + ' rekod berdasarkan alamat? Proses ini mungkin mengambil masa yang lama (1 saat setiap rekod).')) {
                return;
            }
            
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'geocode');
                selected.forEach(id => formData.append('ids[]', id));
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let message = data.message;
                    if (data.errors && data.errors.length > 0) {
                        message += '\n\nRalat:\n' + data.errors.slice(0, 5).join('\n');
                        if (data.errors.length > 5) {
                            message += '\n... dan ' + (data.errors.length - 5) + ' lagi';
                        }
                    }
                    alert(message);
                    document.getElementById('validateForm').dispatchEvent(new Event('submit'));
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat membetulkan GPS'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
        
        document.getElementById('checkMissingDataBtn').addEventListener('click', async function() {
            const kategori = document.getElementById('kategori').value;
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            
            loading.classList.add('show');
            results.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('action', 'check_missing_data');
                if (kategori) {
                    formData.append('kategori', kategori);
                }
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayMissingDataResults(data.data);
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat menyemak rekod'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            } finally {
                loading.classList.remove('show');
            }
        });
        
        function displayMissingDataResults(data) {
            document.getElementById('invalidCount').textContent = data.count;
            document.getElementById('totalCount').textContent = data.count;
            
            const recordsList = document.getElementById('recordsList');
            
            if (data.count === 0) {
                recordsList.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Semua rekod mempunyai maklumat lokasi lengkap (DAERAH, PARLIMEN, DUN).</div>';
            } else {
                let html = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Ditemui ' + data.count + ' rekod yang tiada maklumat lokasi lengkap.</div>';
                html += '<div class="table-responsive"><table class="table table-hover"><thead><tr>';
                html += '<th><input type="checkbox" id="selectAllCheckbox2"></th>';
                html += '<th>ID</th><th>Kategori</th><th>Nama</th><th>Field Tiada</th><th>Ada GPS</th>';
                html += '</tr></thead><tbody>';
                
                data.records.forEach(record => {
                    html += '<tr class="invalid-record">';
                    html += '<td><input type="checkbox" class="record-checkbox" value="' + record.id + '"></td>';
                    html += '<td>' + record.id + '</td>';
                    html += '<td>' + escapeHtml(record.kategori) + '</td>';
                    html += '<td>' + escapeHtml(record.name) + '</td>';
                    html += '<td><span class="badge bg-danger">' + escapeHtml(record.missing_fields.join(', ')) + '</span></td>';
                    html += '<td>' + (record.has_gps ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-warning">Tidak</span>') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                html += '<div class="mt-3">';
                html += '<button class="btn btn-sm btn-success" id="fixMissingDataBtn">';
                html += '<i class="fas fa-magic"></i> Betulkan Rekod Terpilih (Reverse Geocoding)';
                html += '</button>';
                html += '</div>';
                recordsList.innerHTML = html;
                
                // Setup checkbox handlers
                const selectAllCheckbox = document.getElementById('selectAllCheckbox2');
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        document.querySelectorAll('.record-checkbox').forEach(cb => {
                            cb.checked = this.checked;
                        });
                    });
                }
                
                // Setup fix button
                const fixBtn = document.getElementById('fixMissingDataBtn');
                if (fixBtn) {
                    fixBtn.addEventListener('click', async function() {
                        const selected = Array.from(document.querySelectorAll('.record-checkbox:checked'))
                            .filter(cb => cb.dataset.canFix === 'true')
                            .map(cb => cb.value);
                        
                        if (selected.length === 0) {
                            alert('Sila pilih sekurang-kurangnya satu rekod yang mempunyai GPS untuk dibetulkan.');
                            return;
                        }
                        
                        if (!confirm('Adakah anda pasti mahu betulkan maklumat lokasi untuk ' + selected.length + ' rekod? Proses ini akan menggunakan reverse geocoding berdasarkan GPS koordinat.')) {
                            return;
                        }
                        
                        const btn = this;
                        const originalText = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                        
                        try {
                            const formData = new FormData();
                            formData.append('action', 'fix_missing_data');
                            selected.forEach(id => formData.append('ids[]', id));
                            
                            const response = await fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                let message = data.message;
                                if (data.errors && data.errors.length > 0) {
                                    message += '\n\nRalat:\n' + data.errors.slice(0, 5).join('\n');
                                    if (data.errors.length > 5) {
                                        message += '\n... dan ' + (data.errors.length - 5) + ' lagi';
                                    }
                                }
                                alert(message);
                                document.getElementById('checkMissingDataBtn').dispatchEvent(new Event('click'));
                            } else {
                                alert('Ralat: ' + (data.message || 'Tidak dapat membetulkan rekod'));
                            }
                        } catch (error) {
                            alert('Ralat: ' + error.message);
                        } finally {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                    });
                }
            }
            
            document.getElementById('results').style.display = 'block';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
