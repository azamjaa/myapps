<?php
/**
 * Batch Spatial Update Script untuk geojson_data
 * Mengemaskini semua rekod geojson_data yang belum diproses dengan spatial tagging
 * Menggunakan geo_boundaries table dengan ST_Contains
 * 
 * Usage:
 *   CLI: php batch_spatial_update_geojson.php
 *   Web: http://yourdomain/myapps/batch_spatial_update_geojson.php
 * 
 * @author Senior PHP Developer
 * @version 1.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/spatial_processor_engine.php';

// Check if running from CLI or web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Web interface - check if admin
    require_once __DIR__ . '/src/rbac_helper.php';
    session_start();
    $current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);
    $isAdmin = false;
    if ($current_user) {
        $isAdmin = isSuperAdmin($db, $current_user);
    }
    
    if (!$isAdmin) {
        http_response_code(403);
        die('Access denied. Admin only.');
    }
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Batch Spatial Update GeoJSON</title>";
    echo "<style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 15px; border-radius: 3px; overflow-x: auto; }
        .progress { margin: 10px 0; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style></head><body>";
    echo "<div class='container'><h2>Batch Spatial Update GeoJSON - Progress Tracker</h2>";
    echo "<pre id='output'>";
    // Flush output immediately
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
}

// Initialize processor
$processor = new SpatialAutoTag($db);

// Statistics
$totalRecords = 0;
$processedRecords = 0;
$updatedRecords = 0;
$errorRecords = 0;
$skippedRecords = 0;
$startTime = microtime(true);

// Excluded categories (boundary data)
$excludedCategories = ['negeri', 'daerah', 'parlimen', 'dun'];

try {
    // Get all categories except boundaries
    $placeholders = implode(',', array_fill(0, count($excludedCategories), '?'));
    $stmt = $db->prepare("SELECT DISTINCT kategori FROM geojson_data WHERE kategori NOT IN ($placeholders) ORDER BY kategori");
    $stmt->execute($excludedCategories);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($isCli) {
        echo "========================================\n";
        echo "Batch Spatial Update GeoJSON Script\n";
        echo "========================================\n";
        echo "Total categories to process: " . count($categories) . "\n";
        echo "Starting processing...\n\n";
    } else {
        echo "========================================\n";
        echo "Batch Spatial Update GeoJSON Script\n";
        echo "========================================\n";
        echo "Total categories to process: " . count($categories) . "\n";
        echo "Starting processing...\n\n";
        flush();
    }
    
    foreach ($categories as $kategori) {
        if ($isCli) {
            echo "\nProcessing kategori: $kategori\n";
        } else {
            echo "\nProcessing kategori: $kategori\n";
            flush();
        }
        
        // Get all records for this category
        $stmt = $db->prepare("SELECT id, properties, geometry FROM geojson_data WHERE kategori = ?");
        $stmt->execute([$kategori]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $totalRecords++;
            $processedRecords++;
            
            try {
                // Decode current properties and geometry
                $props = json_decode($row['properties'], true);
                $geom = json_decode($row['geometry'], true);
                
                if (!$props || !$geom) {
                    $skippedRecords++;
                    if (!$isCli && $processedRecords % 10 == 0) {
                        echo ".";
                        flush();
                    }
                    continue;
                }
                
                // Check if already has spatial data
                if (!empty($props['DAERAH']) && !empty($props['PARLIMEN']) && !empty($props['DUN'])) {
                    $skippedRecords++;
                    if (!$isCli && $processedRecords % 10 == 0) {
                        echo ".";
                        flush();
                    }
                    continue;
                }
                
                // Extract coordinates from geometry
                $lat = null;
                $long = null;
                
                if ($geom['type'] === 'Point' && isset($geom['coordinates'])) {
                    $long = floatval($geom['coordinates'][0]);
                    $lat = floatval($geom['coordinates'][1]);
                } elseif (($geom['type'] === 'Polygon' || $geom['type'] === 'MultiPolygon') && isset($geom['coordinates'])) {
                    // For polygons, use first coordinate as centroid approximation
                    if ($geom['type'] === 'Polygon' && isset($geom['coordinates'][0][0])) {
                        $long = floatval($geom['coordinates'][0][0][0]);
                        $lat = floatval($geom['coordinates'][0][0][1]);
                    } elseif ($geom['type'] === 'MultiPolygon' && isset($geom['coordinates'][0][0][0])) {
                        $long = floatval($geom['coordinates'][0][0][0][0]);
                        $lat = floatval($geom['coordinates'][0][0][0][1]);
                    }
                }
                
                // If no coordinates found, skip
                if (!$lat || !$long) {
                    $skippedRecords++;
                    if (!$isCli && $processedRecords % 10 == 0) {
                        echo ".";
                        flush();
                    }
                    continue;
                }
                
                // Validate coordinate ranges (Kedah approximate bounds)
                if ($lat < 4.0 || $lat > 7.0 || $long < 99.0 || $long > 101.5) {
                    $skippedRecords++;
                    if (!$isCli && $processedRecords % 10 == 0) {
                        echo ".";
                        flush();
                    }
                    continue;
                }
                
                // Use spatial processor to find boundaries
                // Create a payload-like structure for processing
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
                
                // Check if any spatial data was added
                $changed = false;
                if (empty($props['DAERAH']) && !empty($processedPayload['auto_daerah'])) {
                    $props['DAERAH'] = $processedPayload['auto_daerah'];
                    $changed = true;
                }
                if (empty($props['PARLIMEN']) && !empty($processedPayload['auto_parlimen'])) {
                    $props['PARLIMEN'] = $processedPayload['auto_parlimen'];
                    $changed = true;
                }
                if (empty($props['DUN']) && !empty($processedPayload['auto_dun'])) {
                    $props['DUN'] = $processedPayload['auto_dun'];
                    $changed = true;
                }
                
                if ($changed) {
                    // Convert all properties to uppercase before saving
                    require_once __DIR__ . '/utils/text_helper.php';
                    if (function_exists('convertToUppercase')) {
                        $props = convertToUppercase($props);
                    }
                    
                    // Update database
                    $propsJson = json_encode($props, JSON_UNESCAPED_UNICODE);
                    $updateStmt = $db->prepare("UPDATE geojson_data SET properties = ? WHERE id = ?");
                    $updateStmt->execute([$propsJson, $row['id']]);
                    
                    $updatedRecords++;
                    
                    // Progress indicator
                    if ($isCli) {
                        if ($updatedRecords % 50 == 0) {
                            echo "Updated: $updatedRecords records...\n";
                        }
                    } else {
                        if ($processedRecords % 10 == 0) {
                            echo ".";
                            flush();
                        }
                        if ($processedRecords % 100 == 0) {
                            echo "\n[$processedRecords] Updated: $updatedRecords, Errors: $errorRecords, Skipped: $skippedRecords\n";
                            flush();
                        }
                    }
                } else {
                    $skippedRecords++;
                }
                
            } catch (Exception $e) {
                $errorRecords++;
                error_log("Error processing record ID {$row['id']} in kategori '$kategori': " . $e->getMessage());
                
                if ($isCli) {
                    echo "ERROR [ID: {$row['id']}]: " . $e->getMessage() . "\n";
                } else {
                    echo "\n<span class='error'>ERROR [ID: {$row['id']}]: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                    flush();
                }
            }
        }
    }
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    // Final summary
    if ($isCli) {
        echo "\n";
        echo "========================================\n";
        echo "Batch Update Complete!\n";
        echo "========================================\n";
        echo "Total records: $totalRecords\n";
        echo "Processed: $processedRecords\n";
        echo "Updated: $updatedRecords\n";
        echo "Skipped: $skippedRecords\n";
        echo "Errors: $errorRecords\n";
        echo "Execution time: {$executionTime}s\n";
        echo "========================================\n";
    } else {
        echo "\n\n";
        echo "========================================\n";
        echo "<span class='success'>Batch Update Complete!</span>\n";
        echo "========================================\n";
        echo "Total records: $totalRecords\n";
        echo "Processed: $processedRecords\n";
        echo "<span class='success'>Updated: $updatedRecords</span>\n";
        echo "Skipped: $skippedRecords\n";
        if ($errorRecords > 0) {
            echo "<span class='error'>Errors: $errorRecords</span>\n";
        } else {
            echo "Errors: $errorRecords\n";
        }
        echo "Execution time: {$executionTime}s\n";
        echo "========================================\n";
        echo "</pre>";
        echo "<p><a href='dashboard_pencapaian.php'>Back to Dashboard</a></p>";
        echo "</div></body></html>";
    }
    
} catch (Exception $e) {
    $errorMsg = "Fatal error: " . $e->getMessage();
    
    if ($isCli) {
        echo "\n$errorMsg\n";
    } else {
        echo "\n<span class='error'>$errorMsg</span>\n";
        echo "</pre>";
        echo "<p><a href='dashboard_pencapaian.php'>Back to Dashboard</a></p>";
        echo "</div></body></html>";
    }
    
    error_log("Batch spatial update geojson error: " . $e->getMessage());
    exit(1);
}
