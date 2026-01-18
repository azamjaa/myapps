<?php
/**
 * Upload Processor dengan Spatial Auto-Tagging
 * Memproses fail Excel dan menambah spatial tags secara real-time
 * 
 * @author Senior PHP Developer
 * @version 1.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/spatial_processor_engine.php';

// Check if user is authenticated (adjust based on your auth system)
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
    http_response_code(401);
    die('Unauthorized. Please login first.');
}

// Check if file was uploaded
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
    http_response_code(400);
    die('No file uploaded.');
}

// Verify CSRF token if available
if (function_exists('verifyCsrfToken') && isset($_POST['csrf_token'])) {
    verifyCsrfToken();
}

// Initialize processor
$processor = new SpatialAutoTag($db);

// Configuration
$allowedExtensions = ['xlsx', 'xls'];
$maxFileSize = 10 * 1024 * 1024; // 10MB
$uploadDir = __DIR__ . '/uploads/temp/';

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get uploaded file
$file = $_FILES['excel_file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Validate file
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileError !== UPLOAD_ERR_OK) {
    die('File upload error: ' . $fileError);
}

if (!in_array($fileExtension, $allowedExtensions)) {
    die('Invalid file type. Only Excel files (.xlsx, .xls) are allowed.');
}

if ($fileSize > $maxFileSize) {
    die('File size exceeds maximum limit of 10MB.');
}

// Check if PhpSpreadsheet is available
// If not, you may need to install it via Composer: composer require phpoffice/phpspreadsheet
if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    // Try to load manually if not using Composer
    $phpspreadsheetPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($phpspreadsheetPath)) {
        require_once $phpspreadsheetPath;
    } else {
        die('PhpSpreadsheet library not found. Please install via Composer: composer require phpoffice/phpspreadsheet');
    }
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

try {
    // Load Excel file
    $spreadsheet = IOFactory::load($fileTmpName);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    
    // Get headers (first row)
    $headers = [];
    for ($col = 'A'; $col <= $highestColumn; $col++) {
        $cellValue = $worksheet->getCell($col . '1')->getValue();
        $headers[] = trim($cellValue);
    }
    
    // Statistics
    $totalRows = $highestRow - 1; // Exclude header
    $processedRows = 0;
    $savedRows = 0;
    $errorRows = 0;
    $errors = [];
    
    // Process each row
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];
        $colIndex = 0;
        
        // Read row data
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $cellValue = $worksheet->getCell($col . $row)->getValue();
            $headerName = $headers[$colIndex] ?? "column_$colIndex";
            $rowData[$headerName] = $cellValue;
            $colIndex++;
        }
        
        // Skip empty rows
        if (empty(array_filter($rowData))) {
            continue;
        }
        
        $processedRows++;
        
        try {
            // Convert row data to payload format
            // Adjust this based on your actual Excel structure
            $payload = [];
            
            // Map Excel columns to payload
            // Example mapping - adjust based on your actual columns
            foreach ($rowData as $key => $value) {
                // Normalize key names
                $normalizedKey = strtolower(trim($key));
                
                // Map common variations
                if (in_array($normalizedKey, ['lat', 'latitude', 'latitud'])) {
                    $payload['lat'] = floatval($value);
                } elseif (in_array($normalizedKey, ['long', 'lng', 'longitude', 'longitud', 'lon'])) {
                    $payload['long'] = floatval($value);
                } else {
                    // Keep other fields as-is
                    $payload[$key] = $value;
                }
            }
            
            // Process with Spatial Auto-Tagging BEFORE saving to database
            $processedPayload = $processor->processRow($payload);
            
            // Ensure processedPayload is an array
            if (is_string($processedPayload)) {
                $processedPayload = json_decode($processedPayload, true);
            }
            
            // Prepare data for database insertion
            $payloadJson = json_encode($processedPayload, JSON_UNESCAPED_UNICODE);
            
            // Insert into app_submissions table
            // Adjust column names based on your actual table structure
            $insertStmt = $db->prepare("
                INSERT INTO app_submissions (
                    payload,
                    created_at,
                    created_by
                ) VALUES (
                    :payload,
                    NOW(),
                    :user_id
                )
            ");
            
            $user_id = $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? null;
            
            $insertStmt->execute([
                ':payload' => $payloadJson,
                ':user_id' => $user_id
            ]);
            
            $savedRows++;
            
        } catch (Exception $e) {
            $errorRows++;
            $errors[] = [
                'row' => $row,
                'error' => $e->getMessage()
            ];
            error_log("Error processing row $row: " . $e->getMessage());
        }
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'File processed successfully',
        'statistics' => [
            'total_rows' => $totalRows,
            'processed_rows' => $processedRows,
            'saved_rows' => $savedRows,
            'error_rows' => $errorRows
        ],
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error processing file: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Upload processor error: " . $e->getMessage());
}
