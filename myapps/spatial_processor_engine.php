<?php
/**
 * Spatial Auto-Tagging Engine
 * Menggunakan MySQL ST_Contains untuk mencari Daerah, Parlimen, DUN berdasarkan koordinat GPS
 * 
 * @author Senior PHP Developer
 * @version 1.0
 */

require_once __DIR__ . '/db.php';

class SpatialAutoTag {
    
    private $pdo;
    
    /**
     * Constructor
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo = null) {
        global $db;
        $this->pdo = $pdo ?? $db;
    }
    
    /**
     * Process a single row of data and add spatial tags
     * @param array $rowData Row data containing 'lat' and 'long' in payload
     * @return array Updated row data with spatial tags
     */
    public function processRow($rowData) {
        // Extract payload if it's a string (JSON)
        if (is_string($rowData)) {
            $rowData = json_decode($rowData, true);
        }
        
        // If rowData is an array with 'payload' key, extract it
        if (isset($rowData['payload']) && is_string($rowData['payload'])) {
            $payload = json_decode($rowData['payload'], true);
        } elseif (isset($rowData['payload']) && is_array($rowData['payload'])) {
            $payload = $rowData['payload'];
        } else {
            // Assume rowData itself is the payload
            $payload = $rowData;
        }
        
        // Check if already processed
        if (isset($payload['spatial_processed_at'])) {
            return $rowData; // Already processed
        }
        
        // Extract coordinates
        $lat = null;
        $long = null;
        
        // Try different possible key names
        if (isset($payload['lat'])) {
            $lat = floatval($payload['lat']);
        } elseif (isset($payload['latitude'])) {
            $lat = floatval($payload['latitude']);
        } elseif (isset($payload['Lat'])) {
            $lat = floatval($payload['Lat']);
        }
        
        if (isset($payload['long'])) {
            $long = floatval($payload['long']);
        } elseif (isset($payload['lng'])) {
            $long = floatval($payload['lng']);
        } elseif (isset($payload['longitude'])) {
            $long = floatval($payload['longitude']);
        } elseif (isset($payload['Long'])) {
            $long = floatval($payload['Long']);
        }
        
        // Validate coordinates
        if (!$lat || !$long) {
            // No coordinates found - mark as processed but with error
            $payload['spatial_processed_at'] = date('Y-m-d H:i:s');
            $payload['spatial_error'] = 'Koordinat GPS tidak ditemui';
            
            // Update the rowData structure
            if (isset($rowData['payload']) && is_string($rowData['payload'])) {
                $rowData['payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
            } elseif (isset($rowData['payload'])) {
                $rowData['payload'] = $payload;
            } else {
                $rowData = $payload;
            }
            
            return $rowData;
        }
        
        // Validate coordinate ranges (Kedah approximate bounds)
        // Kedah: Lat ~5.0-6.5, Long ~99.5-101.0
        if ($lat < 4.0 || $lat > 7.0 || $long < 99.0 || $long > 101.5) {
            $payload['spatial_processed_at'] = date('Y-m-d H:i:s');
            $payload['spatial_error'] = 'Koordinat berada di luar sempadan Kedah';
            
            if (isset($rowData['payload']) && is_string($rowData['payload'])) {
                $rowData['payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
            } elseif (isset($rowData['payload'])) {
                $rowData['payload'] = $payload;
            } else {
                $rowData = $payload;
            }
            
            return $rowData;
        }
        
        // Perform spatial lookup
        try {
            $spatialData = $this->findSpatialBoundaries($long, $lat);
            
            // Update payload with spatial data
            if ($spatialData['daerah']) {
                $payload['auto_daerah'] = $spatialData['daerah'];
            }
            if ($spatialData['parlimen']) {
                $payload['auto_parlimen'] = $spatialData['parlimen'];
            }
            if ($spatialData['dun']) {
                $payload['auto_dun'] = $spatialData['dun'];
            }
            
            $payload['spatial_processed_at'] = date('Y-m-d H:i:s');
            
            // Clear any previous error
            unset($payload['spatial_error']);
            
        } catch (Exception $e) {
            error_log("Spatial processing error: " . $e->getMessage());
            $payload['spatial_processed_at'] = date('Y-m-d H:i:s');
            $payload['spatial_error'] = 'Ralat semasa pemprosesan spatial: ' . $e->getMessage();
        }
        
        // Update the rowData structure
        if (isset($rowData['payload']) && is_string($rowData['payload'])) {
            $rowData['payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        } elseif (isset($rowData['payload'])) {
            $rowData['payload'] = $payload;
        } else {
            $rowData = $payload;
        }
        
        return $rowData;
    }
    
    /**
     * Find spatial boundaries (Daerah, Parlimen, DUN) for given coordinates
     * Uses MySQL ST_Contains with geo_boundaries table
     * 
     * Expected table structure (geo_boundaries):
     *   - boundary_geom: GEOMETRY type
     *   - boundary_type: VARCHAR (values: 'daerah', 'parlimen', 'dun')
     *   - nama_daerah: VARCHAR (or adjust column name as needed)
     *   - nama_parlimen: VARCHAR (or adjust column name as needed)
     *   - nama_dun: VARCHAR (or adjust column name as needed)
     * 
     * @param float $longitude Longitude
     * @param float $latitude Latitude
     * @return array Array with 'daerah', 'parlimen', 'dun' keys
     */
    private function findSpatialBoundaries($longitude, $latitude) {
        $result = [
            'daerah' => null,
            'parlimen' => null,
            'dun' => null
        ];
        
        // Create POINT geometry string (MySQL format: POINT(longitude latitude))
        // Note: MySQL uses longitude first, then latitude
        $pointWKT = "POINT($longitude $latitude)";
        
        try {
            // Find DAERAH
            // Adjust column names (nama_daerah, boundary_type) based on your actual table structure
            $stmt = $this->pdo->prepare("
                SELECT nama_daerah
                FROM geo_boundaries
                WHERE boundary_type = 'daerah'
                AND ST_Contains(boundary_geom, ST_GeomFromText(:point, 4326))
                LIMIT 1
            ");
            $stmt->execute([':point' => $pointWKT]);
            $daerah = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($daerah && isset($daerah['nama_daerah'])) {
                $result['daerah'] = $daerah['nama_daerah'];
            }
            
            // Find PARLIMEN
            // Adjust column names (nama_parlimen, boundary_type) based on your actual table structure
            $stmt = $this->pdo->prepare("
                SELECT nama_parlimen
                FROM geo_boundaries
                WHERE boundary_type = 'parlimen'
                AND ST_Contains(boundary_geom, ST_GeomFromText(:point, 4326))
                LIMIT 1
            ");
            $stmt->execute([':point' => $pointWKT]);
            $parlimen = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($parlimen && isset($parlimen['nama_parlimen'])) {
                $result['parlimen'] = $parlimen['nama_parlimen'];
            }
            
            // Find DUN
            // Adjust column names (nama_dun, boundary_type) based on your actual table structure
            $stmt = $this->pdo->prepare("
                SELECT nama_dun
                FROM geo_boundaries
                WHERE boundary_type = 'dun'
                AND ST_Contains(boundary_geom, ST_GeomFromText(:point, 4326))
                LIMIT 1
            ");
            $stmt->execute([':point' => $pointWKT]);
            $dun = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dun && isset($dun['nama_dun'])) {
                $result['dun'] = $dun['nama_dun'];
            }
            
        } catch (PDOException $e) {
            error_log("Spatial query error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
        
        return $result;
    }
}
