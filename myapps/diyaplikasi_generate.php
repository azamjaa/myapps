<?php
/**
 * DIY Aplikasi Generator - Generate Application from Excel Analysis
 * 
 * @author MyApps KEDA
 * @version 1.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/src/rbac_helper.php';

// Check session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check admin access
$checkAdmin = $db->prepare("SELECT COUNT(*) as cnt FROM user_roles ur 
                            JOIN roles r ON ur.id_role = r.id_role 
                            WHERE ur.id_user = ? AND r.name IN ('admin', 'super_admin')");
$checkAdmin->execute([$_SESSION['user_id']]);
$is_admin = $checkAdmin->fetch()['cnt'] > 0;

if (!$is_admin) {
    header("Location: diyaplikasi_builder.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['generate'])) {
    header("Location: diyaplikasi_builder.php");
    exit();
}

verifyCsrfToken();

// Get analysis from session
if (!isset($_SESSION['nocode_analysis'])) {
    $_SESSION['error_msg'] = "Analisis tidak dijumpai. Sila upload Excel semula.";
    header("Location: diyaplikasi_builder.php");
    exit();
}

$analysis = $_SESSION['nocode_analysis'];

try {
    // Check PhpSpreadsheet
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Try to load autoload
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception("PhpSpreadsheet tidak tersedia");
        }
    }
    
    // Check if file path exists in analysis
    if (!isset($analysis['file_path']) || empty($analysis['file_path'])) {
        throw new Exception("Path fail Excel tidak dijumpai dalam analisis. Sila upload semula.");
    }
    
    $filePath = $analysis['file_path'];
    
    // Try to resolve absolute path if relative
    if (!file_exists($filePath)) {
        // Try relative to current directory
        $relativePath = __DIR__ . '/' . ltrim($filePath, '/');
        if (file_exists($relativePath)) {
            $filePath = $relativePath;
        }
    }
    
    // Get absolute path
    $filePath = realpath($filePath) ?: $filePath;
    
    // Check if file exists
    if (!file_exists($filePath)) {
        throw new Exception("Fail Excel tidak dijumpai di: " . $filePath . ". Sila upload semula.");
    }
    
    // Load Excel file again to import all data using fully qualified class name
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $maxRow = $worksheet->getHighestRow();
    
    // Create table name
    $tableName = 'nocode_data_' . $analysis['app_slug'];
    
    // Create data table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `{$tableName}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            record_data JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Import all data
    $imported = 0;
    $db->beginTransaction();
    
    try {
        for ($row = 2; $row <= $maxRow; $row++) {
            $recordData = [];
            foreach ($analysis['schema'] as $col) {
                $cellValue = $worksheet->getCell($col['column'] . $row)->getValue();
                // Handle different cell value types
                if ($cellValue === null) {
                    $recordData[$col['name']] = '';
                } elseif (is_object($cellValue) && method_exists($cellValue, 'getCalculatedValue')) {
                    // For formula cells
                    $recordData[$col['name']] = trim($cellValue->getCalculatedValue());
                } else {
                    $recordData[$col['name']] = trim((string)$cellValue);
                }
            }
            
            // Skip empty rows
            if (empty(array_filter($recordData, function($v) { return $v !== '' && $v !== null; }))) {
                continue;
            }
            
            $jsonData = json_encode($recordData, JSON_UNESCAPED_UNICODE);
            if ($jsonData === false) {
                error_log("JSON encode error for row $row: " . json_last_error_msg());
                continue;
            }
            
            $stmt = $db->prepare("INSERT INTO `{$tableName}` (record_data) VALUES (?)");
            $stmt->execute([$jsonData]);
            $imported++;
        }
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
    // Save app metadata
    $settings = [
        'colors' => [
            'primary' => '#007bff',
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444',
            'info' => '#3B82F6'
        ],
        'icon' => 'fa-database',
        'chart_type' => 'bar' // default chart type
    ];
    
    $url = 'apps/' . $analysis['app_slug'];
    
    $stmt = $db->prepare("
        INSERT INTO nocode_apps 
        (app_name, app_slug, description, table_name, schema_json, settings_json, id_kategori, url, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
    ");
    
    $stmt->execute([
        $analysis['app_name'],
        $analysis['app_slug'],
        $analysis['description'],
        $tableName,
        json_encode($analysis['schema'], JSON_UNESCAPED_UNICODE),
        json_encode($settings, JSON_UNESCAPED_UNICODE),
        $analysis['id_kategori'] > 0 ? $analysis['id_kategori'] : null,
        $url,
        $_SESSION['user_id']
    ]);
    
    $appId = $db->lastInsertId();
    
    // Jika kategori dipilih, tambah ke direktori aplikasi dengan SSO compliant
    if ($analysis['id_kategori'] > 0) {
        try {
            $insertApp = $db->prepare("
                INSERT INTO aplikasi (nama_aplikasi, id_kategori, keterangan, url, sso_comply, status)
                VALUES (?, ?, ?, ?, 1, 1)
            ");
            $insertApp->execute([
                $analysis['app_name'],
                $analysis['id_kategori'],
                $analysis['description'] ?: 'Aplikasi dijana menggunakan DIY Aplikasi',
                $url
            ]);
        } catch (Exception $e) {
            error_log("Error adding to aplikasi directory: " . $e->getMessage());
        }
    }
    
    // Pastikan semua aplikasi DIY Aplikasi dalam direktori ditanda sso_comply=1
    try {
        $db->exec("UPDATE aplikasi SET sso_comply = 1 WHERE url LIKE 'apps/%' OR url LIKE '%diyaplikasi_app.php%'");
    } catch (Exception $e) {
        error_log("Error updating diyaplikasi sso_comply: " . $e->getMessage());
    }
    
    // Delete temporary file
    if (isset($filePath) && file_exists($filePath)) {
        @unlink($filePath);
    } elseif (isset($analysis['file_path']) && file_exists($analysis['file_path'])) {
        @unlink($analysis['file_path']);
    }
    
    // Clear session
    unset($_SESSION['nocode_analysis']);
    
    $_SESSION['success_msg'] = "Aplikasi '{$analysis['app_name']}' berjaya dijana! {$imported} rekod diimport.";
    header("Location: " . $url);
    exit();
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("NoCode Generate Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Delete temporary file on error
    if (isset($filePath) && file_exists($filePath)) {
        @unlink($filePath);
    } elseif (isset($analysis['file_path']) && file_exists($analysis['file_path'])) {
        @unlink($analysis['file_path']);
    }
    
    $errorMessage = "Ralat menjana aplikasi: " . $e->getMessage();
    $_SESSION['error_msg'] = $errorMessage;
    
    // Ensure headers not sent before redirect
    if (!headers_sent()) {
        header("Location: diyaplikasi_builder.php");
        exit();
    } else {
        // If headers already sent, show error directly
        die("<div style='padding:20px;background:#fee;border:2px solid #f00;margin:20px;'>
            <h3>Ralat Menjana Aplikasi</h3>
            <p>" . htmlspecialchars($errorMessage) . "</p>
            <p><a href='diyaplikasi_builder.php'>Kembali ke DIY Aplikasi</a></p>
            </div>");
    }
}
