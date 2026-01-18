<?php
/**
 * API endpoint untuk mendapatkan GeoJSON data dari database berdasarkan kategori
 * Digunakan untuk semua kategori termasuk sempadan (negeri, daerah, parlimen, dun) 
 * dan data utama (bangunan_kediaman, bantuan_usahawan, etc)
 * 
 * NOTE: Reverse geocoding disabled by default untuk performance
 * Gunakan batch_update_geocode.php untuk update rekod sedia ada
 */

// Prevent any output before JSON
if (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

require 'db.php';

// Clear any output buffer
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$kategori = $_GET['kategori'] ?? '';

if (!$kategori) {
    echo json_encode(['type' => 'FeatureCollection', 'features' => []]);
    exit;
}

try {
    // Get data from database ONLY - no file fallback
    $stmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = ?");
    $stmt->execute([$kategori]);
    
    $features = [];
    $errorCount = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Decode properties
        $props = json_decode($row['properties'], true);
        $propsError = json_last_error();
        
        // Reset error state before next decode
        if ($propsError !== JSON_ERROR_NONE) {
            error_log("JSON decode error for properties in kategori '$kategori': " . json_last_error_msg());
            $errorCount++;
            continue;
        }
        
        // Decode geometry
        $geom = json_decode($row['geometry'], true);
        $geomError = json_last_error();
        
        if ($geomError !== JSON_ERROR_NONE) {
            error_log("JSON decode error for geometry in kategori '$kategori': " . json_last_error_msg());
            $errorCount++;
            continue;
        }
        
        // Only add if both decoded successfully
        if ($props !== null && $geom !== null) {
            // Reverse geocoding disabled by default untuk performance
            // Jika perlu enable, uncomment di bawah dan pastikan boundary data tidak terlalu besar
            // if (!in_array(strtolower($kategori), ['negeri', 'daerah', 'parlimen', 'dun'])) {
            //     require 'utils/reverse_geocode.php';
            //     $props = enrichPropertiesWithGeocode($props, $geom, $db);
            // }
            
            $features[] = [
                'type' => 'Feature',
                'properties' => $props,
                'geometry' => $geom
            ];
        }
    }
    
    // Prepare response
    $response = [
        'type' => 'FeatureCollection',
        'features' => $features
    ];
    
    // Add warning if there were decode errors
    if ($errorCount > 0) {
        $response['warning'] = "$errorCount records skipped due to JSON decode errors";
    }
    
    // Return response
    $output = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($output === false) {
        throw new Exception('Failed to encode JSON: ' . json_last_error_msg());
    }
    
    // Clear any remaining output and send response
    ob_clean();
    echo $output;
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    $errorResponse = json_encode([
        'type' => 'FeatureCollection',
        'features' => [],
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($errorResponse === false) {
        // Fallback if JSON encoding fails
        header('Content-Type: text/plain');
        echo 'Error: ' . $e->getMessage();
    } else {
        echo $errorResponse;
    }
    ob_end_flush();
    exit;
}
?>
