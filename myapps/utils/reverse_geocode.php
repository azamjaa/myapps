<?php
/**
 * Reverse Geocoding Helper
 * Extract DAERAH, PARLIMEN, DUN from GPS coordinates using boundary data
 */

require_once __DIR__ . '/../db.php';

/**
 * Check if coordinates are within Kedah bounds (quick check)
 */
function isWithinKedahBounds($lng, $lat, $bounds) {
    return ($lat >= $bounds['minLat'] && $lat <= $bounds['maxLat'] &&
            $lng >= $bounds['minLng'] && $lng <= $bounds['maxLng']);
}

/**
 * Check if a point is inside a polygon
 * Uses ray casting algorithm
 */
function pointInPolygon($point, $polygon) {
    if (count($polygon) < 3) {
        return false; // Need at least 3 points for a polygon
    }
    
    $x = floatval($point[0]); // longitude
    $y = floatval($point[1]); // latitude
    $inside = false;
    
    $j = count($polygon) - 1;
    for ($i = 0; $i < count($polygon); $i++) {
        $xi = floatval($polygon[$i][0]);
        $yi = floatval($polygon[$i][1]);
        $xj = floatval($polygon[$j][0]);
        $yj = floatval($polygon[$j][1]);
        
        // Handle division by zero
        $denominator = ($yj - $yi);
        if ($denominator != 0) {
            $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / $denominator + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }
        $j = $i;
    }
    
    return $inside;
}

/**
 * Get coordinates from geometry
 */
function getCoordinatesFromGeometry($geometry) {
    if (!$geometry || !isset($geometry['type'])) {
        return null;
    }
    
    switch ($geometry['type']) {
        case 'Point':
            return $geometry['coordinates']; // [lng, lat]
        case 'MultiPoint':
            return $geometry['coordinates'][0] ?? null;
        case 'Polygon':
            // Get centroid or first point
            if (isset($geometry['coordinates'][0][0])) {
                return $geometry['coordinates'][0][0];
            }
            break;
        case 'MultiPolygon':
            if (isset($geometry['coordinates'][0][0][0])) {
                return $geometry['coordinates'][0][0][0];
            }
            break;
        case 'LineString':
            if (isset($geometry['coordinates'][0])) {
                return $geometry['coordinates'][0];
            }
            break;
        case 'MultiLineString':
            if (isset($geometry['coordinates'][0][0])) {
                return $geometry['coordinates'][0][0];
            }
            break;
    }
    
    return null;
}

/**
 * Reverse geocode coordinates to get DAERAH, PARLIMEN, DUN
 */
function reverseGeocode($lng, $lat, $db) {
    $result = [
        'DAERAH' => null,
        'PARLIMEN' => null,
        'DUN' => null
    ];
    
    if (!$lng || !$lat) {
        return $result;
    }
    
    $point = [$lng, $lat];
    
    try {
        // Check DAERAH
        $daerahStmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = 'daerah'");
        $daerahStmt->execute();
        
        while ($row = $daerahStmt->fetch(PDO::FETCH_ASSOC)) {
            $geom = json_decode($row['geometry'], true);
            if (!$geom) continue;
            
            // Handle Polygon and MultiPolygon
            $polygons = [];
            if ($geom['type'] === 'Polygon' && isset($geom['coordinates'][0])) {
                $polygons[] = $geom['coordinates'][0]; // First ring (exterior)
            } elseif ($geom['type'] === 'MultiPolygon') {
                foreach ($geom['coordinates'] as $multiPoly) {
                    if (isset($multiPoly[0])) {
                        $polygons[] = $multiPoly[0]; // First ring of each polygon
                    }
                }
            }
            
            // Check if point is in any polygon
            foreach ($polygons as $polygon) {
                if (pointInPolygon($point, $polygon)) {
                    $props = json_decode($row['properties'], true);
                    $result['DAERAH'] = $props['name'] ?? $props['NAME_2'] ?? $props['adm2_name'] ?? $props['NAMA_DAERAH'] ?? null;
                    break 2; // Break both loops
                }
            }
        }
        
        // Check PARLIMEN
        $parlimenStmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = 'parlimen'");
        $parlimenStmt->execute();
        
        while ($row = $parlimenStmt->fetch(PDO::FETCH_ASSOC)) {
            $geom = json_decode($row['geometry'], true);
            if (!$geom) continue;
            
            // Handle Polygon and MultiPolygon
            $polygons = [];
            if ($geom['type'] === 'Polygon' && isset($geom['coordinates'][0])) {
                $polygons[] = $geom['coordinates'][0]; // First ring (exterior)
            } elseif ($geom['type'] === 'MultiPolygon') {
                foreach ($geom['coordinates'] as $multiPoly) {
                    if (isset($multiPoly[0])) {
                        $polygons[] = $multiPoly[0]; // First ring of each polygon
                    }
                }
            }
            
            // Check if point is in any polygon
            foreach ($polygons as $polygon) {
                if (pointInPolygon($point, $polygon)) {
                    $props = json_decode($row['properties'], true);
                    $result['PARLIMEN'] = $props['name'] ?? $props['NAME_1'] ?? $props['adm1_name'] ?? $props['NAMA_PARLIMEN'] ?? null;
                    break 2; // Break both loops
                }
            }
        }
        
        // Check DUN
        $dunStmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = 'dun'");
        $dunStmt->execute();
        
        while ($row = $dunStmt->fetch(PDO::FETCH_ASSOC)) {
            $geom = json_decode($row['geometry'], true);
            if (!$geom) continue;
            
            // Handle Polygon and MultiPolygon
            $polygons = [];
            if ($geom['type'] === 'Polygon' && isset($geom['coordinates'][0])) {
                $polygons[] = $geom['coordinates'][0]; // First ring (exterior)
            } elseif ($geom['type'] === 'MultiPolygon') {
                foreach ($geom['coordinates'] as $multiPoly) {
                    if (isset($multiPoly[0])) {
                        $polygons[] = $multiPoly[0]; // First ring of each polygon
                    }
                }
            }
            
            // Check if point is in any polygon
            foreach ($polygons as $polygon) {
                if (pointInPolygon($point, $polygon)) {
                    $props = json_decode($row['properties'], true);
                    $result['DUN'] = $props['name'] ?? $props['NAME_1'] ?? $props['adm1_name'] ?? $props['NAMA_DUN'] ?? null;
                    break 2; // Break both loops
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Reverse geocoding error: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Check if record has GPS coordinates
 */
function hasGPS($geometry) {
    if (!$geometry || !isset($geometry['type'])) {
        return false;
    }
    
    $coords = getCoordinatesFromGeometry($geometry);
    if (!$coords || count($coords) < 2) {
        return false;
    }
    
    $lng = floatval($coords[0]);
    $lat = floatval($coords[1]);
    
    // Check if coordinates are valid
    if ($lng == 0 && $lat == 0) {
        return false;
    }
    
    return true;
}

/**
 * Update properties with reverse geocoded data if missing
 */
function enrichPropertiesWithGeocode($properties, $geometry, $db) {
    if (!$properties || !$geometry) {
        return $properties;
    }
    
    // Check if already has all required fields
    $hasDaerah = !empty($properties['DAERAH']);
    $hasParlimen = !empty($properties['PARLIMEN']);
    $hasDun = !empty($properties['DUN']);
    
    if ($hasDaerah && $hasParlimen && $hasDun) {
        return $properties; // Already complete
    }
    
    // Get coordinates from geometry
    $coords = getCoordinatesFromGeometry($geometry);
    if (!$coords) {
        return $properties;
    }
    
    $lng = floatval($coords[0]);
    $lat = floatval($coords[1]);
    
    // Reverse geocode
    $geocodeResult = reverseGeocode($lng, $lat, $db);
    
    // Update properties with missing fields
    if (!$hasDaerah && $geocodeResult['DAERAH']) {
        $properties['DAERAH'] = $geocodeResult['DAERAH'];
    }
    if (!$hasParlimen && $geocodeResult['PARLIMEN']) {
        $properties['PARLIMEN'] = $geocodeResult['PARLIMEN'];
    }
    if (!$hasDun && $geocodeResult['DUN']) {
        $properties['DUN'] = $geocodeResult['DUN'];
    }
    
    return $properties;
}
