<?php
/**
 * Batch Update Script untuk Reverse Geocoding
 * Update semua rekod yang tiada DAERAH, PARLIMEN, DUN dengan maklumat dari koordinat GPS
 * 
 * Usage: php utils/batch_update_geocode.php
 * Or access via browser: http://yourdomain/myapps/utils/batch_update_geocode.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/reverse_geocode.php';

// Check if running from CLI or web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Web interface - check if admin
    require_once __DIR__ . '/../src/rbac_helper.php';
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
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Batch Update Geocode</title></head><body>";
    echo "<h2>Batch Update Reverse Geocoding</h2>";
    echo "<pre>";
}

// Excluded categories (boundary data)
$excludedCategories = ['negeri', 'daerah', 'parlimen', 'dun'];

try {
    // Get all categories except boundaries
    $placeholders = implode(',', array_fill(0, count($excludedCategories), '?'));
    $stmt = $db->prepare("SELECT DISTINCT kategori FROM geojson_data WHERE kategori NOT IN ($placeholders)");
    $stmt->execute($excludedCategories);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $totalUpdated = 0;
    $totalProcessed = 0;
    $totalErrors = 0;
    
    foreach ($categories as $kategori) {
        if (!$isCli) {
            echo "\nProcessing kategori: $kategori\n";
            flush();
        }
        
        // Get all records for this category
        $stmt = $db->prepare("SELECT id, properties, geometry FROM geojson_data WHERE kategori = ?");
        $stmt->execute([$kategori]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $totalProcessed++;
            $props = json_decode($row['properties'], true);
            $geom = json_decode($row['geometry'], true);
            
            if (!$props || !$geom) {
                continue;
            }
            
            // Check if already has all required fields
            $hasDaerah = !empty($props['DAERAH']);
            $hasParlimen = !empty($props['PARLIMEN']);
            $hasDun = !empty($props['DUN']);
            
            if ($hasDaerah && $hasParlimen && $hasDun) {
                continue; // Already complete
            }
            
            // Enrich with geocode data
            $enrichedProps = enrichPropertiesWithGeocode($props, $geom, $db);
            
            // Check if anything changed
            $changed = false;
            if (!$hasDaerah && !empty($enrichedProps['DAERAH'])) {
                $changed = true;
            }
            if (!$hasParlimen && !empty($enrichedProps['PARLIMEN'])) {
                $changed = true;
            }
            if (!$hasDun && !empty($enrichedProps['DUN'])) {
                $changed = true;
            }
            
            if ($changed) {
                // Update database
                try {
                    $updateStmt = $db->prepare("UPDATE geojson_data SET properties = ? WHERE id = ?");
                    $updateStmt->execute([json_encode($enrichedProps, JSON_UNESCAPED_UNICODE), $row['id']]);
                    $totalUpdated++;
                    
                    if (!$isCli && $totalUpdated % 10 == 0) {
                        echo ".";
                        flush();
                    }
                } catch (Exception $e) {
                    $totalErrors++;
                    if (!$isCli) {
                        echo "\nError updating record ID {$row['id']}: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
    if (!$isCli) {
        echo "\n\n";
    }
    
    echo "========================================\n";
    echo "Batch Update Complete!\n";
    echo "Total processed: $totalProcessed\n";
    echo "Total updated: $totalUpdated\n";
    echo "Total errors: $totalErrors\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (!$isCli) {
        echo "</pre></body></html>";
    }
    exit(1);
}

if (!$isCli) {
    echo "</pre>";
    echo "<p><a href='../dashboard_pencapaian.php'>Back to Dashboard</a></p>";
    echo "</body></html>";
}
