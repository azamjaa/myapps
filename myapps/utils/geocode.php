<?php
/**
 * Geocoding Helper
 * Convert address to GPS coordinates using OpenStreetMap Nominatim API
 * 
 * This is a free service, but please respect rate limits:
 * - Maximum 1 request per second
 * - Include User-Agent header
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/reverse_geocode.php';

/**
 * Build address string from properties
 */
function buildAddressString($properties) {
    if (!$properties || !is_array($properties)) {
        return null;
    }
    
    $addressParts = [];
    
    // Try various address field names
    $addressFields = [
        'ALAMAT', 'alamat', 'Alamat', 'ADDRESS', 'address', 'Address',
        'JALAN', 'jalan', 'Jalan', 'STREET', 'street', 'Street',
        'ALAMAT_LENGKAP', 'alamat_lengkap', 'Alamat_Lengkap'
    ];
    
    $fullAddress = null;
    foreach ($addressFields as $field) {
        if (!empty($properties[$field])) {
            $fullAddress = trim($properties[$field]);
            break;
        }
    }
    
    // If we have a full address, use it
    if ($fullAddress) {
        // Add location context if available
        $locationParts = [];
        
        // Add bandar/bandaraya
        $cityFields = ['BANDAR', 'bandar', 'Bandar', 'BANDARAYA', 'bandaraya', 'Bandaraya', 'CITY', 'city'];
        foreach ($cityFields as $field) {
            if (!empty($properties[$field])) {
                $locationParts[] = trim($properties[$field]);
                break;
            }
        }
        
        // Add poskod
        $postcodeFields = ['POSKOD', 'poskod', 'Poskod', 'POSTCODE', 'postcode', 'Postcode', 'ZIP', 'zip'];
        foreach ($postcodeFields as $field) {
            if (!empty($properties[$field])) {
                $locationParts[] = trim($properties[$field]);
                break;
            }
        }
        
        // Add daerah
        if (!empty($properties['DAERAH'])) {
            $locationParts[] = trim($properties['DAERAH']);
        }
        
        // Add negeri (always Kedah for this system)
        $locationParts[] = 'Kedah';
        $locationParts[] = 'Malaysia';
        
        // Combine
        $addressString = $fullAddress;
        if (!empty($locationParts)) {
            $addressString .= ', ' . implode(', ', $locationParts);
        }
        
        return $addressString;
    }
    
    // Build address from parts if no full address
    $parts = [];
    
    // No rumah/lot
    $houseFields = ['NO_RUMAH', 'no_rumah', 'No_Rumah', 'NO_LOT', 'no_lot', 'No_Lot', 'HOUSE_NUMBER', 'house_number'];
    foreach ($houseFields as $field) {
        if (!empty($properties[$field])) {
            $parts[] = trim($properties[$field]);
            break;
        }
    }
    
    // Jalan
    $streetFields = ['JALAN', 'jalan', 'Jalan', 'STREET', 'street', 'Street', 'JLN', 'jln'];
    foreach ($streetFields as $field) {
        if (!empty($properties[$field])) {
            $parts[] = trim($properties[$field]);
            break;
        }
    }
    
    // Taman/Kampung
    $areaFields = ['TAMAN', 'taman', 'Taman', 'KAMPUNG', 'kampung', 'Kampung', 'KG', 'kg', 'KAMPONG', 'kampong'];
    foreach ($areaFields as $field) {
        if (!empty($properties[$field])) {
            $parts[] = trim($properties[$field]);
            break;
        }
    }
    
    // Bandar
    $cityFields = ['BANDAR', 'bandar', 'Bandar', 'BANDARAYA', 'bandaraya', 'Bandaraya', 'CITY', 'city'];
    foreach ($cityFields as $field) {
        if (!empty($properties[$field])) {
            $parts[] = trim($properties[$field]);
            break;
        }
    }
    
    // Poskod
    $postcodeFields = ['POSKOD', 'poskod', 'Poskod', 'POSTCODE', 'postcode', 'Postcode'];
    foreach ($postcodeFields as $field) {
        if (!empty($properties[$field])) {
            $parts[] = trim($properties[$field]);
            break;
        }
    }
    
    // Daerah
    if (!empty($properties['DAERAH'])) {
        $parts[] = trim($properties['DAERAH']);
    }
    
    // Negeri
    $parts[] = 'Kedah';
    $parts[] = 'Malaysia';
    
    if (empty($parts)) {
        return null;
    }
    
    return implode(', ', $parts);
}

/**
 * Geocode address to GPS coordinates using Nominatim
 * 
 * @param string $address Address string
 * @return array|null ['lng' => float, 'lat' => float] or null on failure
 */
function geocodeAddress($address) {
    if (empty($address)) {
        return null;
    }
    
    // Rate limiting: sleep 1 second between requests
    static $lastRequestTime = 0;
    $timeSinceLastRequest = microtime(true) - $lastRequestTime;
    if ($timeSinceLastRequest < 1.0) {
        usleep((1.0 - $timeSinceLastRequest) * 1000000);
    }
    
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $address,
        'format' => 'json',
        'limit' => 1,
        'countrycodes' => 'my', // Limit to Malaysia
        'addressdetails' => 1
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'KEDA-Spatial-System/1.0', // Required by Nominatim
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode !== 200) {
        error_log("Geocoding error for address '$address': HTTP $httpCode, Error: $error");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !is_array($data) || empty($data)) {
        error_log("Geocoding: No results for address '$address'");
        return null;
    }
    
    $result = $data[0];
    $lng = floatval($result['lon'] ?? 0);
    $lat = floatval($result['lat'] ?? 0);
    
    if (!$lng || !$lat) {
        return null;
    }
    
    $lastRequestTime = microtime(true);
    
    return [
        'lng' => $lng,
        'lat' => $lat,
        'display_name' => $result['display_name'] ?? $address
    ];
}

/**
 * Update geometry with new GPS coordinates
 */
function updateGeometryWithGPS($geometry, $lng, $lat) {
    if (!$geometry || !isset($geometry['type'])) {
        // Create new Point geometry
        return [
            'type' => 'Point',
            'coordinates' => [$lng, $lat]
        ];
    }
    
    // Update existing geometry based on type
    switch ($geometry['type']) {
        case 'Point':
            $geometry['coordinates'] = [$lng, $lat];
            break;
            
        case 'MultiPoint':
            if (isset($geometry['coordinates'][0])) {
                $geometry['coordinates'][0] = [$lng, $lat];
            } else {
                $geometry['coordinates'] = [[$lng, $lat]];
            }
            break;
            
        case 'Polygon':
            // Update first point of first ring
            if (isset($geometry['coordinates'][0][0])) {
                $geometry['coordinates'][0][0] = [$lng, $lat];
            }
            break;
            
        case 'MultiPolygon':
            // Update first point of first polygon's first ring
            if (isset($geometry['coordinates'][0][0][0])) {
                $geometry['coordinates'][0][0][0] = [$lng, $lat];
            }
            break;
            
        case 'LineString':
            if (isset($geometry['coordinates'][0])) {
                $geometry['coordinates'][0] = [$lng, $lat];
            }
            break;
            
        case 'MultiLineString':
            if (isset($geometry['coordinates'][0][0])) {
                $geometry['coordinates'][0][0] = [$lng, $lat];
            }
            break;
            
        default:
            // For unknown types, create Point
            $geometry = [
                'type' => 'Point',
                'coordinates' => [$lng, $lat]
            ];
    }
    
    return $geometry;
}

/**
 * Geocode a single record
 */
function geocodeRecord($record, $db) {
    $id = $record['id'];
    $properties = json_decode($record['properties'], true);
    $geometry = json_decode($record['geometry'], true);
    
    if (!$properties) {
        return [
            'success' => false,
            'message' => 'Tiada properties untuk rekod ID ' . $id
        ];
    }
    
    // Build address string
    $address = buildAddressString($properties);
    if (!$address) {
        return [
            'success' => false,
            'message' => 'Tiada maklumat alamat untuk rekod ID ' . $id
        ];
    }
    
    // Geocode address
    $gpsResult = geocodeAddress($address);
    if (!$gpsResult) {
        return [
            'success' => false,
            'message' => 'Tidak dapat mendapatkan GPS untuk alamat: ' . $address
        ];
    }
    
    $lng = $gpsResult['lng'];
    $lat = $gpsResult['lat'];
    
    // Verify GPS is within Kedah bounds
    $kedahBounds = [
        'minLat' => 5.0,
        'maxLat' => 6.5,
        'minLng' => 99.5,
        'maxLng' => 101.0
    ];
    if (!isWithinKedahBounds($lng, $lat, $kedahBounds)) {
        return [
            'success' => false,
            'message' => 'GPS yang diperolehi berada di luar sempadan Kedah: ' . $lng . ', ' . $lat
        ];
    }
    
    // Update geometry
    $newGeometry = updateGeometryWithGPS($geometry, $lng, $lat);
    
    // Update properties with geocoding info
    $properties['_geocoded'] = true;
    $properties['_geocoded_date'] = date('Y-m-d H:i:s');
    $properties['_geocoded_address'] = $address;
    $properties['_geocoded_location'] = $gpsResult['display_name'] ?? $address;
    
    // Update database
    try {
        $updateStmt = $db->prepare("
            UPDATE geojson_data 
            SET geometry = ?, properties = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([
            json_encode($newGeometry, JSON_UNESCAPED_UNICODE),
            json_encode($properties, JSON_UNESCAPED_UNICODE),
            $id
        ]);
        
        return [
            'success' => true,
            'message' => 'Berjaya mengemaskini GPS untuk rekod ID ' . $id,
            'lng' => $lng,
            'lat' => $lat,
            'address' => $address
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Ralat database: ' . $e->getMessage()
        ];
    }
}

