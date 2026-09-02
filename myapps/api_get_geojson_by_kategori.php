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

// Normalize kategori to lowercase for matching
$kategoriLower = strtolower(trim($kategori));

try {
    // Get data from database ONLY - no file fallback
    // Try exact match first, then case-insensitive match if no results
    $stmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = ?");
    $stmt->execute([$kategori]);
    $rowCount = $stmt->rowCount();
    
    // If no results with exact match, try case-insensitive match
    if ($rowCount === 0) {
        $stmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE LOWER(TRIM(kategori)) = ?");
        $stmt->execute([$kategoriLower]);
        $rowCount = $stmt->rowCount();
        
        if ($rowCount > 0) {
            error_log("API: Found $rowCount records for kategori '$kategori' using case-insensitive match (searched for: '$kategoriLower')");
        } else {
            // Log available kategori for debugging
            $allKategoriStmt = $db->query("SELECT DISTINCT kategori FROM geojson_data ORDER BY kategori");
            $allKategori = $allKategoriStmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("API: No records found for kategori '$kategori'. Available kategori: " . implode(', ', array_slice($allKategori, 0, 20)));
        }
    } else {
        error_log("API: Found $rowCount records for kategori '$kategori' using exact match");
    }
    
    $features = [];
    $errorCount = 0;
    $totalRows = 0;
    $duplicateCount = 0;
    $seenFeatures = []; // Track seen features to prevent duplicates
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalRows++;
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
            // Create unique identifier for duplicate detection
            // Use name + geometry hash to identify duplicates
            $name = $props['name'] ?? $props['NAME'] ?? $props['NAMA'] ?? '';
            $geomHash = md5(json_encode($geom));
            $uniqueKey = strtolower(trim($name)) . '|' . $geomHash;
            
            // Check for duplicates
            if (isset($seenFeatures[$uniqueKey])) {
                $duplicateCount++;
                continue; // Skip duplicate
            }
            
            $seenFeatures[$uniqueKey] = true;
            
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
    
    // Add metadata for debugging
    $response['_meta'] = [
        'kategori' => $kategori,
        'total_rows' => $totalRows,
        'features_count' => count($features),
        'error_count' => $errorCount,
        'duplicate_count' => $duplicateCount
    ];
    
    // Add warnings if there were issues
    $warnings = [];
    if ($errorCount > 0) {
        $warnings[] = "$errorCount records skipped due to JSON decode errors";
    }
    if ($duplicateCount > 0) {
        $warnings[] = "$duplicateCount duplicate records removed";
    }
    if (!empty($warnings)) {
        $response['warning'] = implode(', ', $warnings);
    }
    
    // Log summary
    if (count($features) === 0 && $totalRows === 0) {
        error_log("API: No data found for kategori '$kategori' (searched as: '$kategori' and '$kategoriLower')");
    } else {
        $logMsg = "API: Returning " . count($features) . " features for kategori '$kategori' (processed $totalRows rows";
        if ($errorCount > 0) {
            $logMsg .= ", $errorCount errors";
        }
        if ($duplicateCount > 0) {
            $logMsg .= ", $duplicateCount duplicates removed";
        }
        $logMsg .= ")";
        error_log($logMsg);
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
