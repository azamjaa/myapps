<?php
/**
 * Batch Spatial Update Script
 * Mengemaskini semua rekod app_submissions yang belum diproses dengan spatial tagging
 * 
 * Usage:
 *   CLI: php batch_spatial_update.php
 *   Web: http://yourdomain/myapps/batch_spatial_update.php
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
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Batch Spatial Update</title>";
    echo "<style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 15px; border-radius: 3px; overflow-x: auto; }
        .progress { margin: 10px 0; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style></head><body>";
    echo "<div class='container'><h2>Batch Spatial Update - Progress Tracker</h2>";
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

try {
    // Get all records that haven't been processed
    // Records without 'spatial_processed_at' in payload JSON
    // Handle both JSON column type and TEXT column type
    $query = "
        SELECT id, payload 
        FROM app_submissions 
        WHERE payload IS NOT NULL 
        AND payload != ''
        AND payload NOT LIKE '%\"spatial_processed_at\"%'
        ORDER BY id ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalRecords = $stmt->rowCount();
    
    if ($isCli) {
        echo "========================================\n";
        echo "Batch Spatial Update Script\n";
        echo "========================================\n";
        echo "Total records to process: $totalRecords\n";
        echo "Starting processing...\n\n";
    } else {
        echo "========================================\n";
        echo "Batch Spatial Update Script\n";
        echo "========================================\n";
        echo "Total records to process: $totalRecords\n";
        echo "Starting processing...\n\n";
        flush();
    }
    
    // Process in batches to avoid memory issues
    $batchSize = 100;
    $currentBatch = 0;
    
    // Re-execute query and process
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentBatch++;
        $processedRecords++;
        
        try {
            // Decode current payload
            $payload = json_decode($row['payload'], true);
            
            if (!$payload) {
                $skippedRecords++;
                if (!$isCli && $processedRecords % 10 == 0) {
                    echo ".";
                    flush();
                }
                continue;
            }
            
            // Check if already has spatial data
            if (isset($payload['spatial_processed_at'])) {
                $skippedRecords++;
                if (!$isCli && $processedRecords % 10 == 0) {
                    echo ".";
                    flush();
                }
                continue;
            }
            
            // Process the row
            $updatedPayload = $processor->processRow($payload);
            
            // If payload was returned as array, encode it
            if (is_array($updatedPayload)) {
                $updatedPayloadJson = json_encode($updatedPayload, JSON_UNESCAPED_UNICODE);
            } else {
                $updatedPayloadJson = $updatedPayload;
            }
            
            // Update database
            $updateStmt = $db->prepare("UPDATE app_submissions SET payload = ? WHERE id = ?");
            $updateStmt->execute([$updatedPayloadJson, $row['id']]);
            
            $updatedRecords++;
            
            // Progress indicator
            if ($isCli) {
                if ($processedRecords % 50 == 0) {
                    echo "Processed: $processedRecords / $totalRecords (Updated: $updatedRecords, Errors: $errorRecords, Skipped: $skippedRecords)\n";
                }
            } else {
                if ($processedRecords % 10 == 0) {
                    echo ".";
                    flush();
                }
                if ($processedRecords % 100 == 0) {
                    echo "\n[$processedRecords/$totalRecords] Updated: $updatedRecords, Errors: $errorRecords, Skipped: $skippedRecords\n";
                    flush();
                }
            }
            
        } catch (Exception $e) {
            $errorRecords++;
            error_log("Error processing record ID {$row['id']}: " . $e->getMessage());
            
            if ($isCli) {
                echo "ERROR [ID: {$row['id']}]: " . $e->getMessage() . "\n";
            } else {
                echo "\n<span class='error'>ERROR [ID: {$row['id']}]: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                flush();
            }
        }
        
        // Memory management
        if ($currentBatch >= $batchSize) {
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            $currentBatch = 0;
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
    
    error_log("Batch spatial update error: " . $e->getMessage());
    exit(1);
}
