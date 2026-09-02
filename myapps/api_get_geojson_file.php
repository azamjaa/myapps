<?php
// Increase memory limit and execution time for large files
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

// Suppress all errors and warnings for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Start output buffering VERY early - before any includes
if (!ob_get_level()) {
    ob_start();
}

// Suppress any output before JSON - capture db.php output
require 'db.php';

// Clear ALL output buffers completely
$cleared = false;
while (ob_get_level() > 0) {
    ob_end_clean();
    $cleared = true;
}

// Restart output buffer for our JSON output
ob_start();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$filename = $_GET['file'] ?? '';
if (!$filename) {
    echo json_encode(['type' => 'FeatureCollection', 'features' => []]);
    exit;
}

// Decode filename jika ada encoding (handle + and %20 for spaces)
$filename = urldecode($filename);
// Replace + with space (some browsers encode space as +)
$filename = str_replace('+', ' ', $filename);

// Security: hanya allow .geojson files
$filename = basename($filename);
if (!preg_match('/\.geojson$/i', $filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type']);
    exit;
}

// Check if it's an admin boundary file (in root) or data file (in json/)
$filepath = null;
if ($filename === 'mys_admin1.geojson' || $filename === 'mys_admin2.geojson') {
    $filepath = __DIR__ . '/' . $filename;
} else {
    $filepath = __DIR__ . '/json/' . $filename;
}

if (!file_exists($filepath)) {
    http_response_code(404);
    // List available files for debugging
    $jsonDir = __DIR__ . '/json/';
    $availableFiles = [];
    if (is_dir($jsonDir)) {
        $files = glob($jsonDir . '*.geojson');
        foreach ($files as $file) {
            $availableFiles[] = basename($file);
        }
    }
    echo json_encode([
        'error' => 'File not found: ' . $filename,
        'searched_path' => $filepath,
        'available_files' => $availableFiles
    ]);
    exit;
}

$jsonContent = @file_get_contents($filepath);
if ($jsonContent === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot read file: ' . $filename]);
    exit;
}

// Check file size first
$fileSize = filesize($filepath);
if ($fileSize === false || $fileSize > 50 * 1024 * 1024) { // 50MB limit
    http_response_code(500);
    echo json_encode(['error' => 'File too large: ' . $filename . ' (' . round($fileSize / 1024 / 1024, 2) . 'MB)']);
    exit;
}

$data = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    $errorMsg = 'Invalid JSON in file ' . $filename . ': ' . json_last_error_msg();
    // Log first 500 chars for debugging
    if (strlen($jsonContent) > 500) {
        $errorMsg .= ' (First 500 chars: ' . substr($jsonContent, 0, 500) . '...)';
    }
    echo json_encode(['error' => $errorMsg]);
    exit;
}

if (!isset($data['type']) || $data['type'] !== 'FeatureCollection') {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid GeoJSON format']);
    exit;
}

// Filter untuk sempadan negeri dan daerah - hanya Kedah sahaja
if ($filename === 'mys_admin1.geojson' || $filename === 'mys_admin2.geojson') {
    // Senarai daerah dalam Kedah
    $kedahDistricts = [
        'BALING', 'BANDAR BAHARU', 'KOTA SETAR', 'KUALA MUDA', 'KUBANG PASU',
        'KULIM', 'LANGKAWI', 'PADANG TERAP', 'PENDANG', 'SIK', 'YAN', 'POKOK SENA'
    ];
    
    $filteredFeatures = [];
    
    foreach ($data['features'] as $feature) {
        $props = $feature['properties'] ?? [];
        
        // Untuk mys_admin1.geojson - filter negeri Kedah sahaja
        if ($filename === 'mys_admin1.geojson') {
            if (isset($props['adm1_name']) && strtoupper($props['adm1_name']) === 'KEDAH') {
                $filteredFeatures[] = $feature;
            }
        }
        // Untuk mys_admin2.geojson - filter daerah dalam Kedah sahaja
        elseif ($filename === 'mys_admin2.geojson') {
            if (isset($props['adm1_name']) && strtoupper($props['adm1_name']) === 'KEDAH') {
                $filteredFeatures[] = $feature;
            }
        }
    }
    
    $data['features'] = $filteredFeatures;
}
// Filter untuk data GeoJSON - hanya data dalam sempadan negeri Kedah
// Abaikan point koordinat yang terkeluar dari sempadan negeri Kedah
else {
    // Kedah approximate bounds: [5.0, 99.5] to [6.5, 101.0]
    // Southwest corner: [5.0, 99.5]
    // Northeast corner: [6.5, 101.0]
    $kedahBounds = [
        'minLat' => 5.0,
        'maxLat' => 6.5,
        'minLng' => 99.5,
        'maxLng' => 101.0
    ];
    
    // Senarai daerah dalam Kedah (dalam pelbagai format)
    $kedahDistricts = [
        'BALING', 'BANDAR BAHARU', 'KOTA SETAR', 'KUALA MUDA', 'KUBANG PASU',
        'KULIM', 'LANGKAWI', 'PADANG TERAP', 'PENDANG', 'SIK', 'YAN', 'POKOK SENA',
        // Variasi ejaan
        'BANDARBAHARU', 'KOTASETAR', 'KUALAMUDA', 'KUBANGPASU', 'PADANGTERAP'
    ];
    
    $filteredFeatures = [];
    
    foreach ($data['features'] as $feature) {
        $props = $feature['properties'] ?? [];
        $geom = $feature['geometry'] ?? null;
        
        // Check if coordinates are within Kedah bounds
        $isWithinKedah = false;
        
        if ($geom && isset($geom['type'])) {
            if ($geom['type'] === 'Point' && isset($geom['coordinates'])) {
                // Point geometry: [lng, lat]
                $lng = floatval($geom['coordinates'][0]);
                $lat = floatval($geom['coordinates'][1]);
                
                if ($lat >= $kedahBounds['minLat'] && $lat <= $kedahBounds['maxLat'] &&
                    $lng >= $kedahBounds['minLng'] && $lng <= $kedahBounds['maxLng']) {
                    $isWithinKedah = true;
                }
            } elseif ($geom['type'] === 'Polygon' || $geom['type'] === 'MultiPolygon') {
                // For polygons, check if any coordinate is within Kedah
                $coords = $geom['coordinates'];
                if ($geom['type'] === 'Polygon') {
                    $coords = [$coords]; // Convert to array of arrays for uniform processing
                }
                
                foreach ($coords as $polygon) {
                    foreach ($polygon as $ring) {
                        foreach ($ring as $coord) {
                            $lng = floatval($coord[0]);
                            $lat = floatval($coord[1]);
                            
                            if ($lat >= $kedahBounds['minLat'] && $lat <= $kedahBounds['maxLat'] &&
                                $lng >= $kedahBounds['minLng'] && $lng <= $kedahBounds['maxLng']) {
                                $isWithinKedah = true;
                                break 3; // Break all loops
                            }
                        }
                    }
                }
            }
        }
        
        // Also check DAERAH field if available
        $hasValidDaerah = false;
        if (isset($props['DAERAH']) && !empty($props['DAERAH'])) {
            $daerah = strtoupper(trim($props['DAERAH']));
            foreach ($kedahDistricts as $district) {
                if ($daerah === $district || stripos($daerah, $district) !== false || stripos($district, $daerah) !== false) {
                    $hasValidDaerah = true;
                    break;
                }
            }
        }
        
        // Include feature if:
        // 1. Coordinates are within Kedah bounds, OR
        // 2. Has valid DAERAH field (even if coordinates might be slightly off)
        if ($isWithinKedah || $hasValidDaerah) {
            $filteredFeatures[] = $feature;
        }
        // Abaikan point yang terkeluar dari sempadan Kedah
    }
    
    $data['features'] = $filteredFeatures;
}

// Ensure we output valid JSON
$output = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($output === false) {
    http_response_code(500);
    // Clear any output before error
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode(['error' => 'Failed to encode JSON: ' . json_last_error_msg()]);
    exit;
}

// Get current buffer content and clear it
$buffer = ob_get_contents();
ob_end_clean();

// If there's any content in buffer (shouldn't be), log it but don't output
if (!empty($buffer) && trim($buffer) !== '') {
    error_log("Unexpected output before JSON: " . substr($buffer, 0, 200));
}

// Output only JSON
echo $output;
exit;
?>
