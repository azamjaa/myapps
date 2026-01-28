<?php
/**
 * Pengurusan Rekod Dashboard
 * Import GeoJSON atau Excel file dengan format WKT ke jadual geo_boundaries
 * Setiap sempadan wajib dikaitkan dengan salah satu daripada 10 Kategori Kad Dashboard
 * 
 * @author Senior PHP Developer
 * @version 1.0
 */

// Start output buffering early to prevent any output before headers
if (!ob_get_level()) {
    ob_start();
}

// Handle download requests FIRST - before any includes or output
if (isset($_GET['download']) && isset($_GET['category']) && isset($_GET['format'])) {
    // Clear any existing output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/src/rbac_helper.php';
    
    // Check if user is admin (for download access)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);
    $isAdmin = false;
    if ($current_user) {
        $isAdmin = isSuperAdmin($db, $current_user);
    }
    
    if (!$isAdmin) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Akses ditolak. Hanya admin dibenarkan.']);
        exit;
    }
    
    // Create geo_boundaries table if it doesn't exist
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE 'geo_boundaries'");
        if ($tableCheck->rowCount() == 0) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS geo_boundaries (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    boundary_name VARCHAR(255) NOT NULL,
                    boundary_geom GEOMETRY NOT NULL,
                    category VARCHAR(255) NULL,
                    properties JSON NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    SPATIAL INDEX idx_boundary_geom (boundary_geom),
                    INDEX idx_category (category),
                    INDEX idx_boundary_name (boundary_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            // Add properties column if it doesn't exist
            try {
                $checkPropertiesColumn = $db->query("SHOW COLUMNS FROM geo_boundaries LIKE 'properties'");
                if ($checkPropertiesColumn->rowCount() == 0) {
                    $db->exec("ALTER TABLE geo_boundaries ADD COLUMN properties JSON NULL AFTER category");
                }
            } catch (Exception $e) {
                error_log("Error adding properties column: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Error creating geo_boundaries table: " . $e->getMessage());
    }
    
    // 10 Nama Kad Dashboard Pencapaian Kedah (mengikut nama kad sebenar)
    $dashboardCategories = [
        'Desa KEDA',
        'Bantuan Kolej KEDA',
        'Bantuan Komuniti',
        'Bantuan Pertanian',
        'Bantuan Usahawan',
        'Jalan Perhubungan Desa',
        'Perniagaan & IKS',
        'Lot Telah Diserah Milik',
        'Rekod Guna Tanah',
        'Sewaan Tanah KEDA'
    ];
    
    $downloadCategory = trim($_GET['category']);
    $downloadFormat = strtolower(trim($_GET['format'])); // 'geojson' or 'excel'
    
    if (!in_array($downloadCategory, $dashboardCategories)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Kategori tidak sah.']);
        exit;
    }
    
    // Map dashboard category name back to geojson_data kategori
    // Dashboard uses names like "Desa KEDA", but geojson_data uses kategori like "keda - bangunan kediaman"
    $categoryToKategoriMap = [
        'Desa KEDA' => ['keda - bangunan kediaman', 'KEDA - Bangunan Kediaman', 'bangunan_kediaman', 'bangunan kediaman'],
        'Bantuan Kolej KEDA' => ['keda - bantuan kolej keda', 'KEDA - Bantuan Kolej KEDA', 'bantuan_kolej', 'bantuan kolej'],
        'Bantuan Komuniti' => ['keda - bantuan bahagian bpk', 'KEDA - Bantuan Bahagian BPK', 'bantuan_komuniti', 'bantuan komuniti', 'bpk'],
        'Bantuan Pertanian' => ['keda - bantuan bahagian bnt', 'KEDA - Bantuan Bahagian BNT', 'bantuan_pertanian', 'bantuan pertanian', 'bnt'],
        'Bantuan Usahawan' => ['keda - bantuan bahagian bpu', 'KEDA - Bantuan Bahagian BPU', 'bantuan_usahawan', 'bantuan usahawan', 'bpu'],
        'Jalan Perhubungan Desa' => ['keda - jalan perhubungan desa', 'KEDA - Jalan Perhubungan Desa', 'jalan_perhubungan', 'jalan perhubungan'],
        'Perniagaan & IKS' => ['keda - industri kecil sederhana', 'KEDA - Industri Kecil Sederhana', 'industri_kecil_sederhana', 'industri kecil sederhana', 'iks'],
        'Lot Telah Diserah Milik' => ['keda - lot telah diserahmilik', 'KEDA - Lot Telah Diserahmilik', 'lot_telah_diserah', 'lot telah diserah'],
        'Rekod Guna Tanah' => ['keda - gunatanah', 'KEDA - Gunatanah', 'gunatanah', 'guna_tanah', 'guna tanah'],
        'Sewaan Tanah KEDA' => ['keda - sewaan tanah-tanah keda', 'KEDA - Sewaan Tanah-Tanah KEDA', 'sewaan_tanah', 'sewaan tanah']
    ];
    
    // Find matching kategori in geojson_data
    $matchingKategori = null;
    if (isset($categoryToKategoriMap[$downloadCategory])) {
        // Try to find which kategori exists in database
        $possibleKategori = $categoryToKategoriMap[$downloadCategory];
        $placeholders = implode(',', array_fill(0, count($possibleKategori), '?'));
        $checkStmt = $db->prepare("SELECT DISTINCT kategori FROM geojson_data WHERE kategori IN ($placeholders) LIMIT 1");
        $checkStmt->execute($possibleKategori);
        $kategoriRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($kategoriRow) {
            $matchingKategori = $kategoriRow['kategori'];
        }
    }
    
    // If no exact match found, try to find any kategori that might match
    if (!$matchingKategori) {
        // Try partial match - search for kategori that contains keywords from category name
        $searchTerms = explode(' ', strtolower($downloadCategory));
        $searchPattern = '%' . implode('%', $searchTerms) . '%';
        $searchStmt = $db->prepare("SELECT DISTINCT kategori FROM geojson_data WHERE LOWER(kategori) LIKE ? LIMIT 1");
        $searchStmt->execute([$searchPattern]);
        $searchRow = $searchStmt->fetch(PDO::FETCH_ASSOC);
        if ($searchRow) {
            $matchingKategori = $searchRow['kategori'];
        }
    }
    
    // Use matching kategori if found, otherwise use downloadCategory as-is (might work if exact match)
    $queryKategori = $matchingKategori ? $matchingKategori : $downloadCategory;
    
    if (!in_array($downloadFormat, ['geojson', 'excel'])) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Format tidak sah. Hanya GeoJSON atau Excel dibenarkan.']);
        exit;
    }
    
    // Check if PhpSpreadsheet is available for Excel
    $phpspreadsheetAvailable = false;
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $phpspreadsheetAvailable = true;
    } else {
        $phpspreadsheetPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($phpspreadsheetPath)) {
            require_once $phpspreadsheetPath;
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $phpspreadsheetAvailable = true;
            }
        }
    }
    
    try {
        // Ensure properties column exists before querying
        try {
            $checkPropertiesColumn = $db->query("SHOW COLUMNS FROM geo_boundaries LIKE 'properties'");
            if ($checkPropertiesColumn->rowCount() == 0) {
                $db->exec("ALTER TABLE geo_boundaries ADD COLUMN properties JSON NULL AFTER category");
            }
        } catch (Exception $e) {
            error_log("Error checking/adding properties column in download: " . $e->getMessage());
        }
        
        // Allow download even if no records - will return empty file (template)
        // This allows users to download empty template for categories that don't have data yet
        
        if ($downloadFormat === 'geojson') {
            // Get data from geojson_data table (same as dashboard uses)
            // Use queryKategori which is mapped from dashboard category name
            $stmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = ?");
            $stmt->execute([$queryKategori]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Set headers BEFORE any output (even if empty, we'll return empty file)
            // This allows users to download empty template file for categories without data
            // Use application/geo+json for GeoJSON files to distinguish from error JSON responses
            header('Content-Type: application/geo+json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . urlencode($downloadCategory) . '.geojson"');
            header('Cache-Control: no-cache, must-revalidate');
            
            $features = [];
            foreach ($records as $record) {
                // Decode properties and geometry from JSON
                $properties = json_decode($record['properties'], true);
                $geometry = json_decode($record['geometry'], true);
                
                if ($properties !== null && $geometry !== null && json_last_error() === JSON_ERROR_NONE) {
                    // Ensure category is in properties
                    if (!isset($properties['category'])) {
                        $properties['category'] = $downloadCategory;
                    }
                    
                    $features[] = [
                        'type' => 'Feature',
                        'properties' => $properties,
                        'geometry' => $geometry
                    ];
                }
            }
            
            // If no valid features, return empty GeoJSON file instead of error
            if (empty($features)) {
                // Set headers for download
                header('Content-Type: application/geo+json; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . urlencode($downloadCategory) . '.geojson"');
                header('Cache-Control: no-cache, must-revalidate');
                
                // Return empty GeoJSON FeatureCollection
                echo json_encode([
                    'type' => 'FeatureCollection',
                    'features' => []
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                exit;
            }
            
            // Output GeoJSON
            echo json_encode([
                'type' => 'FeatureCollection',
                'features' => $features
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
            
        } elseif ($downloadFormat === 'excel' && $phpspreadsheetAvailable) {
            // Get data from geojson_data table (same as dashboard uses)
            // Use queryKategori which is mapped from dashboard category name
            $stmt = $db->prepare("SELECT properties, geometry FROM geojson_data WHERE kategori = ?");
            $stmt->execute([$queryKategori]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Function to convert GeoJSON geometry to WKT format
            $geojsonToWKT = function($geometry) {
                if (!$geometry || !isset($geometry['type'])) {
                    return null;
                }
                
                $type = strtoupper($geometry['type']);
                $coords = $geometry['coordinates'] ?? null;
                
                if (!$coords) {
                    return null;
                }
                
                switch ($type) {
                    case 'POINT':
                        if (count($coords) >= 2) {
                            return "POINT({$coords[0]} {$coords[1]})";
                        }
                        break;
                        
                    case 'LINESTRING':
                        $points = [];
                        foreach ($coords as $coord) {
                            if (count($coord) >= 2) {
                                $points[] = "{$coord[0]} {$coord[1]}";
                            }
                        }
                        if (!empty($points)) {
                            return "LINESTRING(" . implode(', ', $points) . ")";
                        }
                        break;
                        
                    case 'POLYGON':
                        $rings = [];
                        foreach ($coords as $ring) {
                            $points = [];
                            foreach ($ring as $coord) {
                                if (count($coord) >= 2) {
                                    $points[] = "{$coord[0]} {$coord[1]}";
                                }
                            }
                            if (!empty($points)) {
                                $rings[] = "(" . implode(', ', $points) . ")";
                            }
                        }
                        if (!empty($rings)) {
                            return "POLYGON(" . implode(', ', $rings) . ")";
                        }
                        break;
                        
                    case 'MULTIPOINT':
                        $points = [];
                        foreach ($coords as $coord) {
                            if (count($coord) >= 2) {
                                $points[] = "{$coord[0]} {$coord[1]}";
                            }
                        }
                        if (!empty($points)) {
                            return "MULTIPOINT(" . implode(', ', $points) . ")";
                        }
                        break;
                        
                    case 'MULTILINESTRING':
                        $lines = [];
                        foreach ($coords as $line) {
                            $points = [];
                            foreach ($line as $coord) {
                                if (count($coord) >= 2) {
                                    $points[] = "{$coord[0]} {$coord[1]}";
                                }
                            }
                            if (!empty($points)) {
                                $lines[] = "(" . implode(', ', $points) . ")";
                            }
                        }
                        if (!empty($lines)) {
                            return "MULTILINESTRING(" . implode(', ', $lines) . ")";
                        }
                        break;
                        
                    case 'MULTIPOLYGON':
                        $polygons = [];
                        foreach ($coords as $polygon) {
                            $rings = [];
                            foreach ($polygon as $ring) {
                                $points = [];
                                foreach ($ring as $coord) {
                                    if (count($coord) >= 2) {
                                        $points[] = "{$coord[0]} {$coord[1]}";
                                    }
                                }
                                if (!empty($points)) {
                                    $rings[] = "(" . implode(', ', $points) . ")";
                                }
                            }
                            if (!empty($rings)) {
                                $polygons[] = "(" . implode(', ', $rings) . ")";
                            }
                        }
                        if (!empty($polygons)) {
                            return "MULTIPOLYGON(" . implode(', ', $polygons) . ")";
                        }
                        break;
                }
                
                return null;
            };
            
            // Collect all unique property keys from all records (even if empty, we'll create template)
            $allPropertyKeys = ['Name', 'WKT', 'Category'];
            foreach ($records as $record) {
                if (!empty($record['properties'])) {
                    $props = json_decode($record['properties'], true);
                    if ($props && is_array($props)) {
                        foreach (array_keys($props) as $key) {
                            if (!in_array($key, $allPropertyKeys)) {
                                $allPropertyKeys[] = $key;
                            }
                        }
                    }
                }
            }
            
            // Create Excel spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Function to check if a field should be formatted as text (to preserve leading zeros, etc.)
            $isTextField = function($fieldName) {
                $textFieldPatterns = [
                    // IC / Kad Pengenalan
                    'ic', 'no_kp', 'kad_pengenalan', 'ic_number', 'no_ic', 'nric', 'mykad',
                    // No Rumah / Alamat
                    'no_rumah', 'house_number', 'rumah', 'lot', 'no_lot',
                    // Telefon
                    'no_telefon', 'telefon', 'phone', 'no_phone', 'tel', 'mobile', 'handphone', 'hp',
                    // Akaun / Bank
                    'no_akaun', 'akaun', 'account', 'account_number', 'bank_account',
                    // Siri / ID
                    'no_siri', 'siri', 'serial', 'id_number',
                    // Poskod
                    'poskod', 'postcode', 'zip', 'postal',
                    // Fail / Rujukan
                    'no_fail', 'fail', 'file_number', 'no_rujukan', 'rujukan', 'reference', 'ref',
                    // Lain-lain nombor yang perlu text format
                    'no_pendaftaran', 'registration', 'reg_no', 'no_lesen', 'license',
                    'no_plat', 'plate', 'no_kenderaan', 'vehicle'
                ];
                
                $fieldLower = strtolower($fieldName);
                foreach ($textFieldPatterns as $pattern) {
                    if (strpos($fieldLower, $pattern) !== false) {
                        return true;
                    }
                }
                return false;
            };
            
            // Set headers dynamically
            $col = 'A';
            $lastCol = 'A';
            foreach ($allPropertyKeys as $header) {
                $sheet->setCellValue($col . '1', $header);
                // Set text format for header columns that should be text
                if ($isTextField($header)) {
                    $sheet->getStyle($col . '1')->getNumberFormat()->setFormatCode('@');
                }
                $lastCol = $col;
                $col++;
            }
            $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
            
            $row = 2;
            $validRecords = 0;
            foreach ($records as $record) {
                // Decode properties and geometry from JSON
                $props = json_decode($record['properties'], true);
                $geometry = json_decode($record['geometry'], true);
                
                if ($props !== null && $geometry !== null && json_last_error() === JSON_ERROR_NONE) {
                    // Convert geometry to WKT
                    $wktValue = $geojsonToWKT($geometry);
                    
                    if ($wktValue) {
                        $col = 'A';
                        
                        // Write data for each column in order
                        foreach ($allPropertyKeys as $key) {
                            $cellCoordinate = $col . $row;
                            
                            if ($key === 'Name') {
                                // Get name from properties (try different possible keys)
                                $value = $props['name'] ?? 
                                        $props['NAME'] ?? 
                                        $props['NAMA'] ??
                                        $props['Name'] ??
                                        '';
                                $sheet->setCellValue($cellCoordinate, $value);
                            } elseif ($key === 'WKT') {
                                $value = $wktValue;
                                $sheet->setCellValue($cellCoordinate, $value);
                            } elseif ($key === 'Category') {
                                $value = $downloadCategory;
                                $sheet->setCellValue($cellCoordinate, $value);
                            } else {
                                // Property from JSON
                                $value = isset($props[$key]) ? $props[$key] : '';
                                
                                // Check if this field should be formatted as text
                                if ($isTextField($key)) {
                                    // Set as text format to preserve leading zeros and prevent scientific notation
                                    if (class_exists('\PhpOffice\PhpSpreadsheet\Cell\DataType')) {
                                        $sheet->setCellValueExplicit($cellCoordinate, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                                    } else {
                                        // Fallback: prefix with apostrophe to force text format
                                        $sheet->setCellValue($cellCoordinate, "'" . (string)$value);
                                    }
                                    // Also set number format to text
                                    $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('@');
                                } else {
                                    // Check if value looks like a number that might lose precision (long numbers, leading zeros)
                                    $valueStr = trim((string)$value);
                                    if (!empty($valueStr) && is_numeric($valueStr)) {
                                        // Check if it's a long number (>10 digits) or has leading zeros
                                        $hasLeadingZero = strlen($valueStr) > 1 && $valueStr[0] === '0';
                                        $isLongNumber = strlen($valueStr) > 10;
                                        
                                        if ($hasLeadingZero || $isLongNumber) {
                                            // Long numbers or numbers with leading zeros should be text
                                            if (class_exists('\PhpOffice\PhpSpreadsheet\Cell\DataType')) {
                                                $sheet->setCellValueExplicit($cellCoordinate, $valueStr, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                                            } else {
                                                // Fallback: prefix with apostrophe to force text format
                                                $sheet->setCellValue($cellCoordinate, "'" . $valueStr);
                                            }
                                            $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('@');
                                        } else {
                                            $sheet->setCellValue($cellCoordinate, $value);
                                        }
                                    } else {
                                        // Not a number, just set as normal
                                        $sheet->setCellValue($cellCoordinate, $value);
                                    }
                                }
                            }
                            $col++;
                        }
                    }
                    
                    $row++;
                    $validRecords++;
                }
            }
            
            // Auto-size all columns
            $col = 'A';
            foreach ($allPropertyKeys as $header) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }
            
            // Set headers for download BEFORE output (even if empty, we'll return file with headers)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . urlencode($downloadCategory) . '.xlsx"');
            header('Cache-Control: no-cache, must-revalidate');
            
            // If no valid records, spreadsheet already has headers, just output it
            // If has records, spreadsheet has headers and data, output it
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } else {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => true, 'message' => 'Export Excel memerlukan PhpSpreadsheet library. Sila install melalui Composer: composer install']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Ralat database: ' . htmlspecialchars($e->getMessage())]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Ralat semasa export data: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/src/rbac_helper.php';
require_once __DIR__ . '/utils/reverse_geocode.php';
require_once __DIR__ . '/utils/geocode.php';
require_once __DIR__ . '/utils/text_helper.php';

// Check if PhpSpreadsheet is available for Excel processing
$phpspreadsheetAvailable = false;
$phpspreadsheetError = '';
$phpspreadsheetDebug = [];

// First check if class already exists (might be loaded elsewhere)
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    $phpspreadsheetAvailable = true;
    $phpspreadsheetDebug[] = 'PhpSpreadsheet class sudah wujud (loaded elsewhere)';
} else {
    // Try multiple paths for autoload.php
    $possiblePaths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php'
    ];
    
    $autoloadFound = false;
    foreach ($possiblePaths as $phpspreadsheetPath) {
        if (file_exists($phpspreadsheetPath)) {
            $autoloadFound = true;
            $phpspreadsheetDebug[] = "Found autoload.php at: $phpspreadsheetPath";
            try {
                require_once $phpspreadsheetPath;
                if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                    $phpspreadsheetAvailable = true;
                    $phpspreadsheetDebug[] = 'PhpSpreadsheet class loaded successfully';
                    break;
                } else {
                    $phpspreadsheetDebug[] = 'Autoload loaded but PhpSpreadsheet class not found';
                }
            } catch (Exception $e) {
                $phpspreadsheetError = 'Error loading PhpSpreadsheet: ' . $e->getMessage();
                $phpspreadsheetDebug[] = 'Exception: ' . $e->getMessage();
            } catch (Error $e) {
                $phpspreadsheetError = 'Fatal error loading PhpSpreadsheet: ' . $e->getMessage();
                $phpspreadsheetDebug[] = 'Fatal Error: ' . $e->getMessage();
            }
        } else {
            $phpspreadsheetDebug[] = "Not found: $phpspreadsheetPath";
        }
    }
    
    if (!$autoloadFound) {
        $phpspreadsheetError = 'File vendor/autoload.php tidak ditemui di mana-mana lokasi. Sila jalankan: composer install';
        $phpspreadsheetDebug[] = 'Searched paths: ' . implode(', ', $possiblePaths);
    } elseif (!$phpspreadsheetAvailable) {
        $phpspreadsheetError = 'PhpSpreadsheet class tidak ditemui selepas load autoload.php. Sila pastikan composer install telah dijalankan dengan betul.';
    }
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null);
$isAdmin = false;
if ($current_user) {
    $isAdmin = isSuperAdmin($db, $current_user);
}

if (!$isAdmin) {
    http_response_code(403);
    die('Access denied. Admin only.');
}

// 10 Nama Kad Dashboard Pencapaian Kedah (mengikut nama kad sebenar)
$dashboardCategories = [
    'Desa KEDA',
    'Bantuan Kolej KEDA',
    'Bantuan Komuniti',
    'Bantuan Pertanian',
    'Bantuan Usahawan',
    'Jalan Perhubungan Desa',
    'Perniagaan & IKS',
    'Lot Telah Diserah Milik',
    'Rekod Guna Tanah',
    'Sewaan Tanah KEDA'
];

// Create geo_boundaries table if it doesn't exist
try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'geo_boundaries'");
    if ($tableCheck->rowCount() == 0) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS geo_boundaries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                boundary_name VARCHAR(255) NOT NULL,
                boundary_geom GEOMETRY NOT NULL,
                category VARCHAR(255) NULL,
                properties JSON NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                SPATIAL INDEX idx_boundary_geom (boundary_geom),
                INDEX idx_category (category),
                INDEX idx_boundary_name (boundary_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        // Handle database migration - Add 'category' column if not exists
        try {
            $checkColumn = $db->query("SHOW COLUMNS FROM geo_boundaries LIKE 'category'");
            if ($checkColumn->rowCount() == 0) {
                $db->exec("ALTER TABLE geo_boundaries ADD COLUMN category VARCHAR(255) NULL AFTER boundary_geom");
            }
        } catch (Exception $e) {
            error_log("Error checking/adding category column: " . $e->getMessage());
        }
        
        // Handle database migration - Add 'properties' column if not exists
        try {
            $checkPropertiesColumn = $db->query("SHOW COLUMNS FROM geo_boundaries LIKE 'properties'");
            if ($checkPropertiesColumn->rowCount() == 0) {
                $db->exec("ALTER TABLE geo_boundaries ADD COLUMN properties JSON NULL AFTER category");
            }
        } catch (Exception $e) {
            error_log("Error checking/adding properties column: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log("Error creating/checking geo_boundaries table: " . $e->getMessage());
}

// Initialize variables
$uploadMessage = '';
$uploadSuccess = false;
$importedCount = 0;
$errorCount = 0;
$errors = [];

// Process unified upload (GeoJSON or Excel)
if (isset($_POST['upload_spatial']) && isset($_FILES['spatial_file'])) {
    $file = $_FILES['spatial_file'];
    $selectedCategory = isset($_POST['category']) ? trim($_POST['category']) : '';
    
    // Validation: Category is required
    if (empty($selectedCategory)) {
        $uploadMessage = 'Sila pilih kategori terlebih dahulu sebelum memuat naik file.';
        $uploadSuccess = false;
    } elseif (!in_array($selectedCategory, $dashboardCategories)) {
        $uploadMessage = 'Kategori yang dipilih tidak sah.';
        $uploadSuccess = false;
    } elseif ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Determine file type and process accordingly
        if (in_array($fileExt, ['geojson', 'json'])) {
            // Process as GeoJSON
            // Read file content
            $fileContent = file_get_contents($file['tmp_name']);
            $firstBytes = substr($fileContent, 0, 100);
            
            // Check if file is actually Excel or other binary format
            if (strpos($firstBytes, 'PK') === 0) {
                $uploadMessage = 'File yang dimuat naik bukan format GeoJSON. File Excel tidak dibenarkan di sini.';
                $uploadSuccess = false;
            } else {
                // Validate JSON format
                $data = json_decode($fileContent, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['type']) && $data['type'] === 'FeatureCollection') {
                    try {
                        $db->beginTransaction();
                        
                        // Map dashboard category name to database kategori format
                        $categoryMapping = [
                            'Desa KEDA' => 'keda - bangunan kediaman',
                            'Bantuan Kolej KEDA' => 'keda - bantuan kolej keda',
                            'Bantuan Komuniti' => 'keda - bantuan bahagian bpk',
                            'Bantuan Pertanian' => 'keda - bantuan bahagian bnt',
                            'Bantuan Usahawan' => 'keda - bantuan bahagian bpu',
                            'Jalan Perhubungan Desa' => 'keda - jalan perhubungan desa',
                            'Perniagaan & IKS' => 'keda - industri kecil sederhana',
                            'Lot Telah Diserah Milik' => 'keda - lot telah diserahmilik',
                            'Rekod Guna Tanah' => 'keda - gunatanah',
                            'Sewaan Tanah KEDA' => 'keda - sewaan tanah-tanah keda'
                        ];
                        
                        // Get database kategori from mapping and convert to uppercase
                        $dbKategori = isset($categoryMapping[$selectedCategory]) ? $categoryMapping[$selectedCategory] : strtolower($selectedCategory);
                        $dbKategori = mb_strtoupper(trim($dbKategori), 'UTF-8');
                        
                        // Check and add created_at/updated_at columns if they don't exist
                        try {
                            $checkCreatedAt = $db->query("SHOW COLUMNS FROM geojson_data LIKE 'created_at'");
                            if ($checkCreatedAt->rowCount() == 0) {
                                $db->exec("ALTER TABLE geojson_data ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                                error_log("Added created_at column to geojson_data table");
                            }
                        } catch (Exception $e) {
                            error_log("Error checking/adding created_at column: " . $e->getMessage());
                        }
                        
                        try {
                            $checkUpdatedAt = $db->query("SHOW COLUMNS FROM geojson_data LIKE 'updated_at'");
                            if ($checkUpdatedAt->rowCount() == 0) {
                                $db->exec("ALTER TABLE geojson_data ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                                error_log("Added updated_at column to geojson_data table");
                            }
                        } catch (Exception $e) {
                            error_log("Error checking/adding updated_at column: " . $e->getMessage());
                        }
                        
                        // Check if columns exist before using them in INSERT
                        $hasCreatedAt = false;
                        $hasUpdatedAt = false;
                        try {
                            $colsStmt = $db->query("SHOW COLUMNS FROM geojson_data");
                            $columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
                            $hasCreatedAt = in_array('created_at', $columns);
                            $hasUpdatedAt = in_array('updated_at', $columns);
                        } catch (Exception $e) {
                            error_log("Error checking columns: " . $e->getMessage());
                        }
                        
                        // Helper function to convert GeoJSON to WKT (define in this scope)
                        $geojsonToWKT = function($geometry) {
                            if (!$geometry || !isset($geometry['type'])) {
                                return null;
                            }
                            
                            $type = strtoupper($geometry['type']);
                            $coords = $geometry['coordinates'] ?? null;
                            
                            if (!$coords) {
                                return null;
                            }
                            
                            switch ($type) {
                                case 'POINT':
                                    if (count($coords) >= 2) {
                                        return "POINT({$coords[0]} {$coords[1]})";
                                    }
                                    break;
                                    
                                case 'POLYGON':
                                    $rings = [];
                                    foreach ($coords as $ring) {
                                        $points = [];
                                        foreach ($ring as $coord) {
                                            if (count($coord) >= 2) {
                                                $points[] = "{$coord[0]} {$coord[1]}";
                                            }
                                        }
                                        if (!empty($points)) {
                                            $rings[] = "(" . implode(', ', $points) . ")";
                                        }
                                    }
                                    if (!empty($rings)) {
                                        return "POLYGON(" . implode(', ', $rings) . ")";
                                    }
                                    break;
                                    
                                case 'LINESTRING':
                                    $points = [];
                                    foreach ($coords as $coord) {
                                        if (count($coord) >= 2) {
                                            $points[] = "{$coord[0]} {$coord[1]}";
                                        }
                                    }
                                    if (!empty($points)) {
                                        return "LINESTRING(" . implode(', ', $points) . ")";
                                    }
                                    break;
                            }
                            
                            return null;
                        };
                        
                        // Check if ST_GeomFromGeoJSON is available (MySQL 5.7.5+)
                        // Test with a simple query first
                        $testGeomFunc = false;
                        try {
                            $testStmt = $db->query("SELECT ST_GeomFromGeoJSON('{\"type\":\"Point\",\"coordinates\":[100,5]}') as test");
                            if ($testStmt) {
                                $testGeomFunc = true;
                            }
                        } catch (Exception $e) {
                            error_log("ST_GeomFromGeoJSON not available, using alternative method: " . $e->getMessage());
                        }
                        
                        // Prepare INSERT statement for geojson_data table
                        // Note: geometry column in geojson_data is JSON type, not GEOMETRY type
                        // So we store geometry as JSON string directly, not using ST_GeomFromGeoJSON
                        $insertStmt = $db->prepare("
                            INSERT INTO geojson_data (kategori, properties, geometry) 
                            VALUES (?, ?, ?)
                        ");
                        
                        // Check for duplicates before inserting (check multiple name fields)
                        // We'll check in the loop using a more flexible approach
                        
                        $importedCount = 0;
                        $skippedCount = 0; // Count skipped duplicates
                        $errorCount = 0;
                        $errors = [];
                        
                        foreach ($data['features'] as $index => $feature) {
                            if (!isset($feature['geometry'])) {
                                $errorCount++;
                                $errors[] = "Feature #" . ($index + 1) . ": Tiada geometry";
                                continue;
                            }
                            
                            // Get properties
                            $propertiesJson = null;
                            $recordName = 'Record ' . ($index + 1);
                            if (isset($feature['properties'])) {
                                $props = $feature['properties'];
                                // Convert all text properties to uppercase
                                $props = convertToUppercase($props);
                                
                                $recordName = $props['name'] ?? 
                                             $props['NAME'] ?? 
                                             $props['NAMA'] ?? 
                                             $props['NAME_2'] ?? 
                                             $props['ADM2_NAME'] ?? 
                                             $recordName;
                                // Store all properties as JSON (already converted to uppercase)
                                $propertiesJson = json_encode($props, JSON_UNESCAPED_UNICODE);
                            } else {
                                $propertiesJson = json_encode([], JSON_UNESCAPED_UNICODE);
                            }
                            
                            // Convert geometry to GeoJSON string for ST_GeomFromGeoJSON
                            $geomJson = json_encode($feature['geometry'], JSON_UNESCAPED_UNICODE);
                            $geomForInsert = $geomJson;
                            
                            try {
                                // Validate geometry before inserting
                                if (empty($geomJson) || $geomJson === 'null' || $geomJson === '[]') {
                                    $errorCount++;
                                    $errors[] = "Feature #" . ($index + 1) . " ($recordName): Geometry kosong atau tidak sah";
                                    continue;
                                }
                                
                                // Validate properties JSON
                                if (empty($propertiesJson) || $propertiesJson === 'null') {
                                    $errorCount++;
                                    $errors[] = "Feature #" . ($index + 1) . " ($recordName): Properties kosong atau tidak sah";
                                    continue;
                                }
                                
                                // Smart duplicate detection: Check if record already exists
                                // Check by name in properties (try multiple possible name fields)
                                $checkDuplicateStmt = $db->prepare("
                                    SELECT id FROM geojson_data 
                                    WHERE kategori = ? 
                                    AND (
                                        properties->>'$.name' = ? OR
                                        properties->>'$.NAME' = ? OR
                                        properties->>'$.NAMA' = ? OR
                                        JSON_EXTRACT(properties, '$.name') = JSON_QUOTE(?)
                                    )
                                    LIMIT 1
                                ");
                                $checkDuplicateStmt->execute([$dbKategori, $recordName, $recordName, $recordName, $recordName]);
                                if ($checkDuplicateStmt->fetch()) {
                                    // Duplicate found - skip this record to avoid duplication
                                    $skippedCount++;
                                    continue;
                                }
                                
                                // No duplicate - safe to insert
                                // Parameters: kategori, properties, geometry (timestamps handled by MySQL DEFAULT)
                                if ($insertStmt->execute([$dbKategori, $propertiesJson, $geomForInsert])) {
                                    $importedCount++;
                                } else {
                                    $errorInfo = $insertStmt->errorInfo();
                                    $errorCount++;
                                    $errorMsg = "Feature #" . ($index + 1) . " ($recordName): Gagal insert";
                                    if (isset($errorInfo[2])) {
                                        $errorMsg .= " - " . $errorInfo[2];
                                    }
                                    if (isset($errorInfo[1])) {
                                        $errorMsg .= " [SQL Error Code: " . $errorInfo[1] . "]";
                                    }
                                    $errors[] = $errorMsg;
                                    error_log("GeoJSON Insert Error for Feature #" . ($index + 1) . ": " . print_r($errorInfo, true));
                                }
                            } catch (PDOException $e) {
                                $errorCount++;
                                $errors[] = "Feature #" . ($index + 1) . " ($recordName): Database error - " . $e->getMessage();
                                error_log("GeoJSON Insert PDOException for Feature #" . ($index + 1) . ": " . $e->getMessage());
                            } catch (Exception $e) {
                                $errorCount++;
                                $errors[] = "Feature #" . ($index + 1) . " ($recordName): " . $e->getMessage();
                                error_log("GeoJSON Insert Exception for Feature #" . ($index + 1) . ": " . $e->getMessage());
                            }
                        }
                        
                        $db->commit();
                        
                        $uploadSuccess = true;
                        $uploadMessage = "File GeoJSON berjaya diproses: " . htmlspecialchars($filename) . " ($importedCount rekod baharu diimport";
                        if ($skippedCount > 0) {
                            $uploadMessage .= ", $skippedCount rekod duplikat diabaikan";
                        }
                        $uploadMessage .= ")";
                        if ($errorCount > 0) {
                            $uploadMessage .= " ($errorCount rekod gagal)";
                            // Show more errors (up to 20) and indicate if there are more
                            $errorLimit = 20;
                            $errorMessages = array_slice($errors, 0, $errorLimit);
                            $uploadMessage .= "<br><div class='mt-2'><small class='text-danger'><strong>Butiran Ralat:</strong><br>";
                            $uploadMessage .= implode("<br>", array_map('htmlspecialchars', $errorMessages));
                            if (count($errors) > $errorLimit) {
                                $uploadMessage .= "<br>... dan " . (count($errors) - $errorLimit) . " lagi ralat (semak error log untuk butiran lengkap)";
                            }
                            $uploadMessage .= "</small></div>";
                            
                            // Log all errors to error log for debugging
                            error_log("GeoJSON Upload Errors for file '$filename' (Category: $selectedCategory):");
                            foreach ($errors as $idx => $error) {
                                error_log("  Error #" . ($idx + 1) . ": $error");
                            }
                        }
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        $uploadMessage = 'Error semasa import GeoJSON: ' . $e->getMessage();
                        $uploadSuccess = false;
                    }
                } else {
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $uploadMessage = 'Format GeoJSON tidak sah. Error: ' . json_last_error_msg();
                    } else if (!isset($data['type']) || $data['type'] !== 'FeatureCollection') {
                        $uploadMessage = 'Format GeoJSON tidak sah. File mesti mengandungi FeatureCollection.';
                    } else {
                        $uploadMessage = 'Format GeoJSON tidak sah. Sila semak file anda.';
                    }
                    $uploadSuccess = false;
                }
            }
        } elseif (in_array($fileExt, ['xlsx', 'xls'])) {
            // Process as Excel
            if (!$phpspreadsheetAvailable) {
                $uploadMessage = 'PhpSpreadsheet library tidak ditemui. Sila install melalui Composer: composer require phpoffice/phpspreadsheet';
                $uploadSuccess = false;
            } else {
                try {
                // Load Excel file using PhpSpreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                
                // Find column indices for 'Name' and 'WKT'
                $nameCol = null;
                $wktCol = null;
                
                // Check header row (row 1)
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cellValue = trim($worksheet->getCell($col . '1')->getValue());
                    if (strcasecmp($cellValue, 'Name') === 0) {
                        $nameCol = $col;
                    } elseif (strcasecmp($cellValue, 'WKT') === 0) {
                        $wktCol = $col;
                    }
                }
                
                if (!$nameCol || !$wktCol) {
                    $uploadMessage = 'Kolum "Name" atau "WKT" tidak ditemui dalam file Excel. Sila pastikan header row mengandungi kolum "Name" dan "WKT".';
                    $uploadSuccess = false;
                } else {
                    $db->beginTransaction();
                    
                    // Prepare INSERT statement with ST_GeomFromText
                    $insertStmt = $db->prepare("
                        INSERT INTO geo_boundaries (boundary_name, boundary_geom, category, properties, created_at, updated_at) 
                        VALUES (?, ST_GeomFromText(?, 4326), ?, ?, NOW(), NOW())
                    ");
                    
                    // Check for duplicates before inserting (smart duplicate detection)
                    $checkDuplicateStmt = $db->prepare("
                        SELECT id FROM geo_boundaries 
                        WHERE boundary_name = ? AND category = ?
                        LIMIT 1
                    ");
                    
                    $importedCount = 0;
                    $skippedCount = 0; // Count skipped duplicates
                    $errorCount = 0;
                    $errors = [];
                    
                    // Get all column headers for properties
                    $allColumns = [];
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $headerValue = trim($worksheet->getCell($col . '1')->getValue());
                        if (!empty($headerValue) && strcasecmp($headerValue, 'Name') !== 0 && strcasecmp($headerValue, 'WKT') !== 0) {
                            $allColumns[$col] = $headerValue;
                        }
                    }
                    
                    // Process each row (starting from row 2, assuming row 1 is header)
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $nameValue = trim($worksheet->getCell($nameCol . $row)->getValue());
                        $wktValue = trim($worksheet->getCell($wktCol . $row)->getValue());
                        
                        // Skip empty rows
                        if (empty($nameValue) || empty($wktValue)) {
                            continue;
                        }
                        
                        // Convert name to uppercase
                        $nameValue = mb_strtoupper(trim($nameValue), 'UTF-8');
                        
                        try {
                            // Validate WKT format (basic check)
                            if (stripos($wktValue, 'POINT') === false && 
                                stripos($wktValue, 'LINESTRING') === false && 
                                stripos($wktValue, 'POLYGON') === false &&
                                stripos($wktValue, 'MULTIPOINT') === false &&
                                stripos($wktValue, 'MULTILINESTRING') === false &&
                                stripos($wktValue, 'MULTIPOLYGON') === false) {
                                $errorCount++;
                                $errors[] = "Row $row: Format WKT tidak sah - '$wktValue'";
                                continue;
                            }
                            
                            // Collect all properties from other columns and convert to uppercase
                            $properties = [];
                            foreach ($allColumns as $col => $headerName) {
                                $cellValue = trim($worksheet->getCell($col . $row)->getValue());
                                if ($cellValue !== '') {
                                    // Convert text values to uppercase
                                    if (is_string($cellValue)) {
                                        $properties[$headerName] = mb_strtoupper(trim($cellValue), 'UTF-8');
                                    } else {
                                        $properties[$headerName] = $cellValue;
                                    }
                                }
                            }
                            // Convert all properties to uppercase (recursive)
                            $properties = convertToUppercase($properties);
                            $propertiesJson = !empty($properties) ? json_encode($properties, JSON_UNESCAPED_UNICODE) : null;
                            
                            // Smart duplicate detection: Check if record already exists
                            $checkDuplicateStmt->execute([$nameValue, $selectedCategory]);
                            if ($checkDuplicateStmt->fetch()) {
                                // Duplicate found - skip this record to avoid duplication
                                $skippedCount++;
                                continue;
                            }
                            
                            // No duplicate - safe to insert
                            if ($insertStmt->execute([$nameValue, $wktValue, $selectedCategory, $propertiesJson])) {
                                $importedCount++;
                            } else {
                                $errorCount++;
                                $errors[] = "Row $row: Gagal insert - " . implode(', ', $insertStmt->errorInfo());
                            }
                            
                        } catch (Exception $e) {
                            $errorCount++;
                            $errors[] = "Row $row: " . $e->getMessage();
                        }
                    }
                    
                    $db->commit();
                    
                    $uploadSuccess = true;
                    $uploadMessage = "File Excel berjaya diproses: " . htmlspecialchars($filename) . " ($importedCount rekod baharu diimport";
                    if ($skippedCount > 0) {
                        $uploadMessage .= ", $skippedCount rekod duplikat diabaikan";
                    }
                    $uploadMessage .= ")";
                    if ($errorCount > 0) {
                        $uploadMessage .= " ($errorCount rekod gagal)";
                        if (count($errors) <= 10) {
                            $uploadMessage .= "<br><small class='text-danger'>Errors: " . implode(", ", array_slice($errors, 0, 10)) . "</small>";
                        }
                    }
                }
                
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $uploadMessage = 'Error semasa proses Excel: ' . $e->getMessage();
                    $uploadSuccess = false;
                }
            }
        } else {
            $uploadMessage = 'Format file tidak disokong. Hanya file GeoJSON (.geojson, .json) atau Excel (.xlsx, .xls) dibenarkan. File yang dimuat naik: ' . htmlspecialchars($filename) . ' (Extension: .' . htmlspecialchars($fileExt) . ').';
            $uploadSuccess = false;
        }
    } else {
        $uploadMessage = 'Error semasa upload: ' . $file['error'];
    }
}

// Download handler has been moved to the top of the file (before includes)

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

// Handle AJAX requests for validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    // Only handle validation-related actions
    if (!in_array($action, ['validate', 'delete', 'mark', 'check_missing_data', 'fix_missing_data', 'geocode'])) {
        // Not a validation action, skip
        // Continue with normal page load
    } else {
    header('Content-Type: application/json');
    
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
                    
                    // Convert all properties to uppercase before saving
                    $props = convertToUppercase($props);
                    // Keep metadata fields as is (not uppercase)
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
                        
                        // Convert all properties to uppercase before saving
                        $enrichedProps = convertToUppercase($enrichedProps);
                        
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
}

// Get all categories for filter
$validationCategories = [];
try {
    $stmt = $db->query("SELECT DISTINCT kategori FROM geojson_data WHERE kategori NOT IN ('negeri', 'daerah', 'parlimen', 'dun') ORDER BY kategori");
    $validationCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-file-alt me-3 text-primary"></i>Pengurusan Rekod Dashboard</h3>
    </div>
    

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Column 1: Download Records -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0 text-white">
                        <i class="fas fa-download me-2 text-white"></i>Muat Turun Rekod Dashboard
                    </h4>
                </div>
                <div class="card-body d-flex flex-column">
                    <form method="GET" id="downloadForm" class="d-flex flex-column flex-grow-1">
                        <!-- Step 1: Category Selection (Required) -->
                        <div class="card mb-3 border-warning">
                            <div class="card-header bg-warning text-white py-2">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-tags me-2 text-white"></i>Langkah 1 : Pilih Nama Dashboard <span class="text-white">*</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="download_category" class="form-label fw-bold">
                                        Nama Kad Dashboard <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-lg" id="download_category" name="category" required onchange="enableFormatSelect(this.value)">
                                        <option value="">-- Sila Pilih Dashboard --</option>
                                        <?php foreach ($dashboardCategories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Wajib:</strong> Pilih salah satu daripada 10 dashboard untuk muat turun rekod.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Format Selection -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white py-2">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-file me-2 text-white"></i>Langkah 2 : Pilih Jenis File
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="download_format" class="form-label fw-bold">
                                        Pilih Format <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <select class="form-select form-select-lg" id="download_format" name="format" required style="padding-left: 2.5rem; position: relative; z-index: 100;">
                                            <option value="">-- Sila Pilih Format --</option>
                                            <option value="geojson">GeoJSON</option>
                                            <?php if ($phpspreadsheetAvailable): ?>
                                            <option value="excel">Excel</option>
                                            <?php else: ?>
                                            <option value="excel" disabled>Excel (Tidak Tersedia)</option>
                                            <?php endif; ?>
                                        </select>
                                        <i id="format_icon" class="position-absolute" style="left: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 1; display: none; line-height: 1;"></i>
                                    </div>
                                    <div class="form-text mt-2">
                                        <div class="alert alert-info mb-2">
                                            <i class="fas fa-star me-2 text-warning"></i>
                                            <strong>Disyorkan untuk User Biasa: Excel</strong>
                                            <br><small>Excel lebih mudah digunakan - boleh edit, tambah, padam rekod dengan mudah seperti spreadsheet biasa.</small>
                                        </div>
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Format yang disokong:</strong>
                                        <ul class="mt-2 mb-0">
                                            <li>
                                                <strong class="text-success">📊 Excel (.xlsx, .xls) - Mudah dan Disyorkan</strong>
                                                <br><small class="text-muted">✅ Boleh edit dalam Microsoft Excel atau Google Sheets</small>
                                                <br><small class="text-muted">✅ Interface visual (spreadsheet) - tambah/edit/padam rows dengan mudah</small>
                                                <br><small class="text-muted">✅ Kolum wajib: <strong>"Name"</strong> dan <strong>"WKT"</strong></small>
                                                <br><small class="text-muted">✅ Kolum tambahan akan disimpan sebagai properties</small>
                                                <br><small class="text-muted">📝 Format WKT: <code>POINT(100.5 5.5)</code> atau <code>POLYGON((100 5, 101 5, 101 6, 100 6, 100 5))</code></small>
                                            </li>
                                            <li class="mt-2">
                                                <strong>🗺️ GeoJSON (.geojson, .json) - Untuk Advanced Users</strong>
                                                <br><small class="text-muted">⚠️ Format JSON (text-based) - perlu edit dalam text editor</small>
                                                <br><small class="text-muted">⚠️ Perlu faham struktur JSON - lebih susah untuk user biasa</small>
                                                <br><small class="text-muted">✅ Standard format untuk GIS software (QGIS, ArcGIS, dll)</small>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Download Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" name="download" value="1" class="btn btn-success btn-lg" id="downloadBtn" style="opacity: 0.6; cursor: not-allowed;">
                                <i class="fas fa-download me-2"></i>Muat Turun File
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Column 2: Upload Files -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">
                        <i class="fas fa-upload me-2 text-white"></i>Muat Naik Rekod Dashboard
                    </h4>
                </div>
                <div class="card-body d-flex flex-column">
                    <?php if ($uploadMessage): ?>
                    <?php if ($uploadSuccess): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $uploadMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php else: ?>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof showErrorModal === 'function') {
                            showErrorModal(<?php echo json_encode($uploadMessage); ?>);
                        } else {
                            // Wait for function to be available
                            setTimeout(function() {
                                if (typeof showErrorModal === 'function') {
                                    showErrorModal(<?php echo json_encode($uploadMessage); ?>);
                                } else {
                                    alert(<?php echo json_encode($uploadMessage); ?>);
                                }
                            }, 100);
                        }
                    });
                    </script>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="unifiedUploadForm" class="d-flex flex-column flex-grow-1">
                                <!-- Step 1: Category Selection (Required) -->
                                <div class="card mb-3" style="border: 1px solid #cbd5e1;">
                                    <div class="card-header bg-warning text-white py-2">
                                        <h6 class="mb-0 text-white">
                                            <i class="fas fa-tags me-2 text-white"></i>Langkah 1 : Pilih Nama Dashboard <span class="text-white">*</span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="category" class="form-label fw-bold">
                                                Nama Kad Dashboard <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-lg" id="category" name="category" required>
                                                <option value="">-- Sila Pilih Dashboard --</option>
                                                <?php foreach ($dashboardCategories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                                        <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>Wajib:</strong> Pilih salah satu daripada 10 dashboard untuk muat naik rekod.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Step 2: File Selection -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white py-2">
                                        <h6 class="mb-0 text-white">
                                            <i class="fas fa-file me-2 text-white"></i>Langkah 2 : Pilih Jenis File
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="spatial_file" class="form-label fw-bold">
                                                Pilih File <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" class="form-control form-control-lg" id="spatial_file" name="spatial_file" 
                                                   accept=".geojson,.json,.xlsx,.xls,application/json,application/geo+json,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" 
                                                   required>
                                    <div class="form-text">
                                        <div class="alert alert-info mb-2">
                                            <i class="fas fa-star me-2 text-warning"></i>
                                            <strong>Disyorkan untuk User Biasa: Excel</strong>
                                            <br><small>Excel lebih mudah digunakan - boleh edit, tambah, padam rekod dengan mudah seperti spreadsheet biasa.</small>
                                        </div>
                                        <i class="fas fa-info-circle me-1"></i>
                                        <strong>Format yang disokong:</strong>
                                        <ul class="mt-2 mb-0">
                                            <li>
                                                <strong class="text-success">📊 Excel (.xlsx, .xls) - Mudah dan Disyorkan</strong>
                                                <br><small class="text-muted">✅ Boleh edit dalam Microsoft Excel atau Google Sheets</small>
                                                <br><small class="text-muted">✅ Interface visual (spreadsheet) - tambah/edit/padam rows dengan mudah</small>
                                                <br><small class="text-muted">✅ Kolum wajib: <strong>"Name"</strong> dan <strong>"WKT"</strong></small>
                                                <br><small class="text-muted">✅ Kolum tambahan akan disimpan sebagai properties</small>
                                                <br><small class="text-muted">📝 Format WKT: <code>POINT(100.5 5.5)</code> atau <code>POLYGON((100 5, 101 5, 101 6, 100 6, 100 5))</code></small>
                                            </li>
                                            <li class="mt-2">
                                                <strong>🗺️ GeoJSON (.geojson, .json) - Untuk Advanced Users</strong>
                                                <br><small class="text-muted">⚠️ Format JSON (text-based) - perlu edit dalam text editor</small>
                                                <br><small class="text-muted">⚠️ Perlu faham struktur JSON - lebih susah untuk user biasa</small>
                                                <br><small class="text-muted">✅ Standard format untuk GIS software (QGIS, ArcGIS, dll)</small>
                                            </li>
                                        </ul>
                                    </div>
                                        </div>
                                        
                                        <?php if (!$phpspreadsheetAvailable): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Nota:</strong> PhpSpreadsheet library tidak ditemui. Upload Excel tidak akan berfungsi.
                                            <br><br>
                                            <strong>Kenapa Isu Ini Berlaku?</strong>
                                            <p class="mb-2 mt-2">
                                                PhpSpreadsheet adalah <strong>external library</strong> yang perlu dipasang melalui <strong>Composer</strong>. 
                                                Folder <code>vendor/</code> belum wujud kerana <code>composer install</code> belum dijalankan.
                                            </p>
                                            <strong>Arahan Pemasangan (3 Langkah Mudah):</strong>
                                            <ol class="mb-2 mt-2">
                                                <li><strong>Buka Terminal:</strong> Laragon Terminal atau Command Prompt</li>
                                                <li><strong>Navigate:</strong> <code>cd d:\laragon\www\myapps</code></li>
                                                <li><strong>Install:</strong> <code>composer install</code> (tunggu selesai ~5-10MB)</li>
                                                <li><strong>Refresh:</strong> Refresh halaman ini selepas selesai</li>
                                            </ol>
                                            <div class="alert alert-info mt-2 mb-0">
                                                <i class="fas fa-lightbulb me-2"></i>
                                                <strong>Tip Mudah:</strong> Double-click file <code>install_phpspreadsheet.bat</code> untuk install secara automatik!
                                                <br>Atau rujuk fail <code>CARA_INSTALL.txt</code> untuk arahan langkah demi langkah.
                                            </div>
                                            <?php if (!empty($phpspreadsheetError)): ?>
                                            <div class="mt-2 p-2 bg-light border rounded">
                                                <strong class="text-danger">Error Details:</strong><br>
                                                <small><?php echo htmlspecialchars($phpspreadsheetError); ?></small>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($phpspreadsheetDebug) && $isAdmin): ?>
                                            <details class="mt-2">
                                                <summary class="text-muted" style="cursor: pointer;"><small>Debug Info (Admin Only)</small></summary>
                                                <pre class="mt-2 p-2 bg-light border rounded" style="font-size: 11px;"><?php echo htmlspecialchars(implode("\n", $phpspreadsheetDebug)); ?></pre>
                                            </details>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <small>Atau rujuk fail <code>INSTALL_PHPSPREADSHEET.md</code> untuk arahan lengkap dan troubleshooting.</small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Upload Button -->
                                <div class="d-grid mt-auto">
                                    <button type="submit" name="upload_spatial" class="btn btn-primary btn-lg" id="uploadBtn" style="opacity: 0.6; cursor: not-allowed;">
                                        <i class="fas fa-upload me-2"></i>Upload File
                                    </button>
                                </div>
                            </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white">
                <h4 class="mb-0 text-white">
                    <i class="fas fa-map-marker-alt me-2 text-white"></i>Semak & Betulkan Rekod GPS Luar Sempadan Kedah
                </h4>
            </div>
            <div class="card-body">
                <form id="validateForm">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="validate_kategori" class="form-label">Kategori (Pilihan)</label>
                            <select class="form-select" id="validate_kategori" name="kategori">
                                <option value="">Sila Pilih Kategori</option>
                                <?php foreach ($validationCategories as $cat): ?>
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
                
                <div class="loading mt-3" id="validationLoading" style="display: none;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Memproses...</span>
                        </div>
                        <p class="mt-2">Sedang menyemak rekod...</p>
                    </div>
                </div>
                
                <div id="validationResults" style="display: none;">
                    <div class="card mb-3 mt-3">
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
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Ralat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="errorModalMessage" class="alert alert-danger mb-0">
                    <!-- Error message will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure both cards have same height and buttons align */
.row {
    display: flex;
    align-items: stretch;
}

.col-lg-6 .card {
    display: flex;
    flex-direction: column;
}

.col-lg-6 .card-body {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.col-lg-6 form {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.col-lg-6 .mt-auto {
    margin-top: auto !important;
}
</style>

<script>
// Enable/disable file input and upload button based on category selection
document.addEventListener('DOMContentLoaded', function() {
    // Upload form handlers
    const categorySelect = document.getElementById('category');
    const fileInput = document.getElementById('spatial_file');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('unifiedUploadForm');
    
    function updateUploadFormState() {
        const categorySelected = categorySelect && categorySelect.value !== '';
        
        if (fileInput) {
            fileInput.disabled = !categorySelected;
        }
        
        if (uploadBtn) {
            uploadBtn.disabled = !categorySelected;
            // Always use btn-primary (blue) - same as header
            uploadBtn.classList.remove('btn-secondary', 'btn-success', 'btn-danger');
            uploadBtn.classList.add('btn-primary');
            if (!categorySelected) {
                uploadBtn.style.opacity = '0.6';
                uploadBtn.style.cursor = 'not-allowed';
            } else {
                uploadBtn.style.opacity = '1';
                uploadBtn.style.cursor = 'pointer';
            }
        }
    }
    
    // Initial state
    updateUploadFormState();
    
    // Update when category changes
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            updateUploadFormState();
            
            // Clear file input if category is deselected
            if (!this.value && fileInput) {
                fileInput.value = '';
            }
        });
    }
    
    // Validate before form submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            if (!categorySelect || !categorySelect.value) {
                e.preventDefault();
                showErrorModal('Sila pilih kategori terlebih dahulu sebelum memuat naik file.');
                if (categorySelect) {
                    categorySelect.focus();
                }
                return false;
            }
            
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                showErrorModal('Sila pilih file terlebih dahulu.');
                if (fileInput) {
                    fileInput.focus();
                }
                return false;
            }
        });
    }
    
    // Show file type hint when file is selected
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const fileName = file.name.toLowerCase();
                const isGeoJSON = fileName.endsWith('.geojson') || fileName.endsWith('.json');
                const isExcel = fileName.endsWith('.xlsx') || fileName.endsWith('.xls');
                
                if (!isGeoJSON && !isExcel) {
                    showErrorModal('Format file tidak disokong. Sila pilih file GeoJSON (.geojson) atau Excel (.xlsx, .xls).');
                    this.value = '';
                    updateUploadFormState();
                }
            }
        });
    }
    
    // Download form handlers
    const downloadCategorySelect = document.getElementById('download_category');
    const downloadFormatSelect = document.getElementById('download_format');
    const downloadForm = document.getElementById('downloadForm');
    const downloadBtn = document.getElementById('downloadBtn');
    const phpspreadsheetAvailable = <?php echo $phpspreadsheetAvailable ? 'true' : 'false'; ?>;
    
    function updateDownloadFormState() {
        const categorySelected = downloadCategorySelect && downloadCategorySelect.value !== '';
        const formatSelected = downloadFormatSelect && downloadFormatSelect.value !== '';
        
        // Enable/disable format select based on category selection
        if (downloadFormatSelect) {
            if (!categorySelected) {
                // Visually disable but don't use disabled attribute
                downloadFormatSelect.value = '';
                downloadFormatSelect.style.opacity = '0.6';
                downloadFormatSelect.style.cursor = 'not-allowed';
                downloadFormatSelect.style.pointerEvents = 'none';
                // Hide icon when disabled
                const formatIcon = document.getElementById('format_icon');
                if (formatIcon) {
                    formatIcon.style.display = 'none';
                }
            } else {
                // Enable format select completely
                downloadFormatSelect.removeAttribute('disabled');
                downloadFormatSelect.disabled = false;
                downloadFormatSelect.style.pointerEvents = 'auto';
                downloadFormatSelect.style.cursor = 'pointer';
                downloadFormatSelect.style.opacity = '1';
                downloadFormatSelect.style.backgroundColor = '#fff';
                
                const excelOption = downloadFormatSelect.querySelector('option[value="excel"]');
                if (excelOption) {
                    if (!phpspreadsheetAvailable) {
                        excelOption.disabled = true;
                    } else {
                        excelOption.disabled = false;
                    }
                }
            }
        }
        
        // Enable/disable download button (always green when enabled)
        if (downloadBtn) {
            const canDownload = categorySelected && formatSelected;
            downloadBtn.disabled = !canDownload;
            // Always use btn-success (green) - same as header
            downloadBtn.classList.remove('btn-secondary', 'btn-primary', 'btn-danger');
            downloadBtn.classList.add('btn-success');
            if (!canDownload) {
                downloadBtn.style.opacity = '0.6';
                downloadBtn.style.cursor = 'not-allowed';
            } else {
                downloadBtn.style.opacity = '1';
                downloadBtn.style.cursor = 'pointer';
            }
        }
    }
    
    // Initial state - visually disable but keep functional
    if (downloadFormatSelect) {
        downloadFormatSelect.style.opacity = '0.6';
        downloadFormatSelect.style.cursor = 'not-allowed';
        downloadFormatSelect.style.pointerEvents = 'none';
    }
    updateDownloadFormState();
    
    // Update when category changes
    if (downloadCategorySelect) {
        downloadCategorySelect.addEventListener('change', function() {
            // Clear format selection when category changes
            if (downloadFormatSelect) {
                downloadFormatSelect.value = '';
                // Hide icon when cleared
                const formatIcon = document.getElementById('format_icon');
                if (formatIcon) {
                    formatIcon.style.display = 'none';
                }
            }
            updateDownloadFormState();
            
            // Force enable format select if category is selected
            if (this.value && downloadFormatSelect) {
                setTimeout(function() {
                    downloadFormatSelect.removeAttribute('disabled');
                    downloadFormatSelect.disabled = false;
                    downloadFormatSelect.style.pointerEvents = 'auto';
                    downloadFormatSelect.style.cursor = 'pointer';
                    downloadFormatSelect.style.opacity = '1';
                    // Trigger a reflow
                    downloadFormatSelect.offsetHeight;
                }, 10);
            }
        });
    }
    
    // Update when format changes
    if (downloadFormatSelect) {
        downloadFormatSelect.addEventListener('change', function() {
            // Update icon based on selected format
            const formatIcon = document.getElementById('format_icon');
            if (formatIcon) {
                if (this.value === 'geojson') {
                    formatIcon.className = 'position-absolute fas fa-file-code text-primary';
                    formatIcon.style.display = 'block';
                    formatIcon.style.left = '0.75rem';
                    formatIcon.style.top = '50%';
                    formatIcon.style.transform = 'translateY(-50%)';
                    formatIcon.style.pointerEvents = 'none';
                    formatIcon.style.zIndex = '1';
                } else if (this.value === 'excel') {
                    formatIcon.className = 'position-absolute fas fa-file-excel text-success';
                    formatIcon.style.display = 'block';
                    formatIcon.style.left = '0.75rem';
                    formatIcon.style.top = '50%';
                    formatIcon.style.transform = 'translateY(-50%)';
                    formatIcon.style.pointerEvents = 'none';
                    formatIcon.style.zIndex = '1';
                } else {
                    formatIcon.style.display = 'none';
                }
            }
            updateDownloadFormState();
        });
    }
    
    // Update icon on page load if format is already selected
    if (downloadFormatSelect && downloadFormatSelect.value) {
        const formatIcon = document.getElementById('format_icon');
        if (formatIcon) {
            if (downloadFormatSelect.value === 'geojson') {
                formatIcon.className = 'position-absolute fas fa-file-code text-primary';
                formatIcon.style.display = 'block';
                formatIcon.style.left = '0.75rem';
                formatIcon.style.top = '50%';
                formatIcon.style.transform = 'translateY(-50%)';
                formatIcon.style.pointerEvents = 'none';
                formatIcon.style.zIndex = '1';
            } else if (downloadFormatSelect.value === 'excel') {
                formatIcon.className = 'position-absolute fas fa-file-excel text-success';
                formatIcon.style.display = 'block';
                formatIcon.style.left = '0.75rem';
                formatIcon.style.top = '50%';
                formatIcon.style.transform = 'translateY(-50%)';
                formatIcon.style.pointerEvents = 'none';
                formatIcon.style.zIndex = '1';
            }
        }
    }
    
    // Validate before download form submission and handle errors
    if (downloadForm) {
        downloadForm.addEventListener('submit', function(e) {
            if (!downloadCategorySelect || !downloadCategorySelect.value) {
                e.preventDefault();
                showErrorModal('Sila pilih nama dashboard terlebih dahulu.');
                if (downloadCategorySelect) {
                    downloadCategorySelect.focus();
                }
                return false;
            }
            
            if (!downloadFormatSelect || !downloadFormatSelect.value) {
                e.preventDefault();
                showErrorModal('Sila pilih jenis file (GeoJSON atau Excel) terlebih dahulu.');
                if (downloadFormatSelect) {
                    downloadFormatSelect.focus();
                }
                return false;
            }
            
            // Intercept form submission to handle errors via AJAX
            e.preventDefault();
            
            // Show loading state
            if (downloadBtn) {
                downloadBtn.disabled = true;
                const originalHtml = downloadBtn.innerHTML;
                downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                
                // Restore button after timeout (in case of error)
                setTimeout(() => {
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = originalHtml;
                }, 10000);
            }
            
            const downloadUrl = window.location.href.split('?')[0] + '?download=1&category=' + encodeURIComponent(downloadCategorySelect.value) + '&format=' + encodeURIComponent(downloadFormatSelect.value);
            
            fetch(downloadUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Check if response is JSON error or file download
                const contentType = response.headers.get('content-type') || '';
                const contentDisposition = response.headers.get('content-disposition') || '';
                
                // Clone response for error checking if needed
                const responseClone = response.clone();
                
                // Primary check: If Content-Disposition has 'attachment', it's a file download
                const hasAttachmentHeader = contentDisposition.includes('attachment');
                
                // Check if it's a file download based on headers
                const isFileDownload = hasAttachmentHeader || 
                                      contentType.includes('application/geo+json') ||
                                      contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                
                // Check if it's an error JSON response 
                // (application/json without attachment header AND response is not OK)
                const isErrorJson = !hasAttachmentHeader &&
                                   contentType.includes('application/json') && 
                                   !response.ok;
                
                if (isErrorJson) {
                    // It's an error response - read as JSON
                    return response.json().then(data => {
                        console.log('Error response data:', data);
                        let errorMsg = 'Ralat tidak diketahui.';
                        if (data && typeof data === 'object') {
                            errorMsg = data.message || data.error || JSON.stringify(data);
                        } else if (typeof data === 'string') {
                            errorMsg = data;
                        }
                        throw new Error(errorMsg);
                    }).catch(e => {
                        // If JSON parsing fails, use cloned response to get text
                        console.error('Error parsing JSON response:', e);
                        return responseClone.text().then(text => {
                            console.log('Error response text:', text);
                            throw new Error(text || 'Ralat semasa memproses respons dari server. Status: ' + response.status);
                        });
                    });
                } else if (isFileDownload || response.ok) {
                    // It's a file download - check status first
                    if (!response.ok) {
                        // Use cloned response to read error text
                        return responseClone.text().then(text => {
                            // Try to parse as JSON if possible
                            try {
                                const jsonData = JSON.parse(text);
                                throw new Error(jsonData.message || jsonData.error || 'Ralat semasa muat turun file.');
                            } catch (e) {
                                // If not JSON, show the text or default message
                                const errorMsg = text || 'Ralat semasa muat turun. Status: ' + response.status + ' ' + response.statusText;
                                throw new Error(errorMsg);
                            }
                        });
                    }
                    
                    // Success - trigger download
                    return response.blob().then(blob => {
                        // Trigger download
                        try {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            const extension = downloadFormatSelect.value === 'geojson' ? 'geojson' : 'xlsx';
                            a.download = downloadCategorySelect.value + '.' + extension;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);
                        } catch (downloadError) {
                            console.error('Error triggering download:', downloadError);
                            throw new Error('Ralat semasa memulakan muat turun file: ' + downloadError.message);
                        }
                    });
                } else {
                    // Unknown response type - try to handle as error
                    return responseClone.text().then(text => {
                        throw new Error('Ralat tidak diketahui. Status: ' + response.status + ' ' + response.statusText);
                    });
                }
            })
            .catch(error => {
                console.error('Download error:', error);
                console.error('Error stack:', error.stack);
                
                let errorMsg = 'Ralat tidak diketahui.';
                
                if (error.message) {
                    errorMsg = error.message;
                } else if (error.toString && error.toString() !== '[object Object]') {
                    errorMsg = error.toString();
                }
                
                // Add more context if available
                if (error.response) {
                    errorMsg += ' (Status: ' + error.response.status + ')';
                }
                
                showErrorModal(errorMsg + '<br><br><small>Sila semak console browser (F12) untuk maklumat lanjut.</small>');
            })
            .finally(() => {
                // Restore button state
                if (downloadBtn) {
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = '<i class="fas fa-download me-2"></i>Muat Turun File';
                }
            });
            
            return false;
        });
    }
    
    // Function to show error modal (global function)
    window.showErrorModal = function(message) {
        const errorModalElement = document.getElementById('errorModal');
        const errorMessage = document.getElementById('errorModalMessage');
        if (errorModalElement && errorMessage) {
            errorMessage.innerHTML = typeof message === 'string' ? message.replace(/\n/g, '<br>') : message;
            const errorModal = new bootstrap.Modal(errorModalElement);
            errorModal.show();
        } else {
            // Fallback to alert if modal not found
            alert(typeof message === 'string' ? message : 'Ralat berlaku');
        }
    };
});

// Global function to enable format select (called from inline onchange)
function enableFormatSelect(categoryValue) {
    const formatSelect = document.getElementById('download_format');
    if (!formatSelect) {
        console.error('Format select not found!');
        return;
    }
    
    if (categoryValue && categoryValue !== '') {
        // Completely enable the select - don't use disabled attribute
        formatSelect.removeAttribute('disabled');
        formatSelect.disabled = false;
        formatSelect.style.pointerEvents = 'auto';
        formatSelect.style.cursor = 'pointer';
        formatSelect.style.opacity = '1';
        formatSelect.style.backgroundColor = '#fff';
        formatSelect.style.zIndex = '100';
        
        console.log('Format select enabled. Can click?', formatSelect.style.pointerEvents);
    } else {
        formatSelect.value = '';
        formatSelect.style.opacity = '0.6';
        formatSelect.style.cursor = 'not-allowed';
        formatSelect.style.pointerEvents = 'none';
        const formatIcon = document.getElementById('format_icon');
        if (formatIcon) {
            formatIcon.style.display = 'none';
        }
    }
}

// Validation functionality
(function() {
    let currentRecords = [];
    
    const validateForm = document.getElementById('validateForm');
    if (validateForm) {
        validateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const kategori = document.getElementById('validate_kategori').value;
            const includeMissingGPS = document.getElementById('includeMissingGPS').checked;
            const loading = document.getElementById('validationLoading');
            const results = document.getElementById('validationResults');
            
            loading.style.display = 'block';
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
                loading.style.display = 'none';
            }
        });
    }
    
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
                html += '<tr class="invalid-record" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">';
                html += '<td><input type="checkbox" class="record-checkbox" value="' + record.id + '"' + 
                        (record.can_geocode !== false ? ' data-can-geocode="true"' : '') + '></td>';
                html += '<td>' + record.id + '</td>';
                html += '<td>' + escapeHtml(record.kategori) + '</td>';
                html += '<td>' + escapeHtml(record.name) + '</td>';
                if (record.lng !== null && record.lat !== null) {
                    html += '<td class="coords" style="font-family: monospace; color: #dc3545;">' + record.lng.toFixed(6) + ', ' + record.lat.toFixed(6) + '</td>';
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
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    document.querySelectorAll('.record-checkbox').forEach(cb => {
                        cb.checked = this.checked;
                    });
                });
            }
        }
        
        document.getElementById('validationResults').style.display = 'block';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Action buttons
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const deleteSelectedBtn = document.getElementById('deleteSelected');
    const markSelectedBtn = document.getElementById('markSelected');
    const geocodeSelectedBtn = document.getElementById('geocodeSelected');
    const checkMissingDataBtn = document.getElementById('checkMissingDataBtn');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = true);
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) selectAllCheckbox.checked = true;
        });
    }
    
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = false);
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
        });
    }
    
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', async function() {
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
                    validateForm.dispatchEvent(new Event('submit'));
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat memadam rekod'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            }
        });
    }
    
    if (markSelectedBtn) {
        markSelectedBtn.addEventListener('click', async function() {
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
                    validateForm.dispatchEvent(new Event('submit'));
                } else {
                    alert('Ralat: ' + (data.message || 'Tidak dapat menandakan rekod'));
                }
            } catch (error) {
                alert('Ralat: ' + error.message);
            }
        });
    }
    
    if (geocodeSelectedBtn) {
        geocodeSelectedBtn.addEventListener('click', async function() {
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
                    validateForm.dispatchEvent(new Event('submit'));
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
    }
    
    if (checkMissingDataBtn) {
        checkMissingDataBtn.addEventListener('click', async function() {
            const kategori = document.getElementById('validate_kategori').value;
            const loading = document.getElementById('validationLoading');
            const results = document.getElementById('validationResults');
            
            loading.style.display = 'block';
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
                loading.style.display = 'none';
            }
        });
    }
    
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
                html += '<tr class="invalid-record" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">';
                html += '<td><input type="checkbox" class="record-checkbox" value="' + record.id + '" data-can-fix="' + (record.has_gps ? 'true' : 'false') + '"></td>';
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
                            checkMissingDataBtn.dispatchEvent(new Event('click'));
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
        
        document.getElementById('validationResults').style.display = 'block';
    }
})();
</script>

<?php include 'footer.php'; ?>
