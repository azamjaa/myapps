<?php
/**
 * DIY Aplikasi Builder
 * User boleh upload Excel, sistem akan analisis struktur dan jana aplikasi
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
    header("Location: dashboard_aplikasi.php");
    exit();
}

// Create tables if not exist
try {
    // Table untuk metadata aplikasi yang dijana
    $db->exec("
        CREATE TABLE IF NOT EXISTS nocode_apps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            app_name VARCHAR(255) NOT NULL,
            app_slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT NULL,
            table_name VARCHAR(100) NOT NULL UNIQUE,
            schema_json JSON NOT NULL COMMENT 'Struktur kolum dari Excel',
            settings_json JSON NULL COMMENT 'Settings aplikasi (colors, icons, etc)',
            id_kategori INT NULL COMMENT 'Kategori dari direktori aplikasi',
            url VARCHAR(500) NULL COMMENT 'URL aplikasi yang dijana',
            status TINYINT DEFAULT 1,
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (app_slug),
            INDEX idx_status (status),
            INDEX idx_kategori (id_kategori)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table untuk rekod (fleksibel - semua rekod dalam JSON)
    // Setiap aplikasi akan ada table sendiri dengan nama: nocode_data_{app_slug}
    // Struktur: id, record_data JSON, created_at, updated_at
    
} catch (Exception $e) {
    error_log("Error creating nocode tables: " . $e->getMessage());
}

// Handle Excel upload and analysis
$uploadMessage = '';
$uploadSuccess = false;
$analysisResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    verifyCsrfToken();
    
    try {
        $file = $_FILES['excel_file'];
        $app_name = trim($_POST['app_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $id_kategori = intval($_POST['id_kategori'] ?? 0);
        
        if (empty($app_name)) {
            throw new Exception("Nama aplikasi diperlukan");
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Ralat memuat naik fail: " . $file['error']);
        }
        
        // Check PhpSpreadsheet availability
        $phpspreadsheetAvailable = class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet');
        
        if (!$phpspreadsheetAvailable) {
            // Try to load autoload
            if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                require_once __DIR__ . '/vendor/autoload.php';
                $phpspreadsheetAvailable = class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet');
            }
        }
        
        if (!$phpspreadsheetAvailable) {
            throw new Exception("PhpSpreadsheet tidak tersedia. Sila install menggunakan install_phpspreadsheet.bat");
        }
        
        // Save uploaded file to permanent location FIRST before analysis
        $uploadDir = __DIR__ . '/uploads/nocode_temp/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Gagal membuat direktori upload: " . $uploadDir);
            }
        }
        
        if (!is_writable($uploadDir)) {
            throw new Exception("Direktori upload tidak boleh ditulis: " . $uploadDir);
        }
        
        // Generate app slug for file naming
        $app_slug_temp = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $app_name));
        $app_slug_temp = preg_replace('/^_+|_+$/', '', $app_slug_temp);
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $savedFileName = 'nocode_' . $app_slug_temp . '_' . time() . '.' . $fileExtension;
        $savedFilePath = $uploadDir . $savedFileName;
        
        // Copy file to permanent location (don't move yet, keep original for analysis)
        if (!copy($file['tmp_name'], $savedFilePath)) {
            $error = error_get_last();
            throw new Exception("Gagal menyimpan fail Excel: " . ($error['message'] ?? 'Unknown error'));
        }
        
        // Verify file was saved
        if (!file_exists($savedFilePath)) {
            throw new Exception("Fail tidak dijumpai selepas simpan: " . $savedFilePath);
        }
        
        // Load Excel file using the saved file path
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($savedFilePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Analyze structure
        $headers = [];
        $sampleData = [];
        $maxRow = $worksheet->getHighestRow();
        $maxCol = $worksheet->getHighestColumn();
        $colIndex = 0;
        
        // Get headers (first row)
        for ($col = 'A'; $col <= $maxCol; $col++) {
            $cellValue = $worksheet->getCell($col . '1')->getValue();
            if (!empty($cellValue)) {
                $headers[] = [
                    'column' => $col,
                    'name' => trim($cellValue),
                    'index' => $colIndex++
                ];
            }
        }
        
        if (empty($headers)) {
            throw new Exception("Tiada header dijumpai dalam Excel. Pastikan baris pertama mengandungi nama kolum.");
        }
        
        // Get sample data (next 5 rows)
        for ($row = 2; $row <= min(7, $maxRow); $row++) {
            $rowData = [];
            foreach ($headers as $header) {
                $cellValue = $worksheet->getCell($header['column'] . $row)->getValue();
                $rowData[$header['name']] = $cellValue !== null ? trim($cellValue) : '';
            }
            if (!empty(array_filter($rowData))) {
                $sampleData[] = $rowData;
            }
        }
        
        // Detect data types
        $schema = [];
        foreach ($headers as $header) {
            $colName = $header['name'];
            $detectedType = 'text'; // default
            $sampleValues = array_column($sampleData, $colName);
            $nonEmptyValues = array_filter($sampleValues, function($v) { return !empty($v); });
            
            if (!empty($nonEmptyValues)) {
                // Check if numeric
                $numericCount = 0;
                $dateCount = 0;
                foreach ($nonEmptyValues as $val) {
                    if (is_numeric($val)) {
                        $numericCount++;
                    }
                    if (preg_match('/\d{4}[-\/]\d{2}[-\/]\d{2}/', $val) || preg_match('/\d{2}[-\/]\d{2}[-\/]\d{4}/', $val)) {
                        $dateCount++;
                    }
                }
                
                if ($numericCount / count($nonEmptyValues) > 0.8) {
                    $detectedType = 'number';
                } elseif ($dateCount / count($nonEmptyValues) > 0.5) {
                    $detectedType = 'date';
                }
            }
            
            $schema[] = [
                'name' => $colName,
                'type' => $detectedType,
                'column' => $header['column'],
                'index' => $header['index']
            ];
        }
        
        // Generate app slug (final)
        $app_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $app_name));
        $app_slug = preg_replace('/^_+|_+$/', '', $app_slug);
        
        // Check if slug exists
        $checkSlug = $db->prepare("SELECT COUNT(*) FROM nocode_apps WHERE app_slug = ?");
        $checkSlug->execute([$app_slug]);
        if ($checkSlug->fetchColumn() > 0) {
            $app_slug .= '_' . time();
        }
        
        // Rename file if slug changed
        if ($app_slug !== $app_slug_temp) {
            $newFileName = 'nocode_' . $app_slug . '_' . time() . '.' . $fileExtension;
            $newFilePath = $uploadDir . $newFileName;
            if (rename($savedFilePath, $newFilePath)) {
                $savedFilePath = $newFilePath;
                $savedFileName = $newFileName;
            }
        }
        
        // Ensure absolute path
        $savedFilePath = realpath($savedFilePath) ?: $savedFilePath;
        
        // Save analysis result for preview
        $analysisResult = [
            'app_name' => $app_name,
            'app_slug' => $app_slug,
            'description' => $description,
            'id_kategori' => $id_kategori,
            'headers' => $headers,
            'schema' => $schema,
            'sample_data' => $sampleData,
            'total_rows' => $maxRow - 1, // Exclude header
            'file_path' => $savedFilePath, // Absolute path
            'file_name' => $savedFileName
        ];
        
        // Store in session for next step (generation)
        $_SESSION['nocode_analysis'] = $analysisResult;
        
        $uploadSuccess = true;
        $uploadMessage = "Analisis struktur Excel berjaya! " . count($headers) . " kolum dijumpai, " . ($maxRow - 1) . " rekod.";
        
    } catch (Exception $e) {
        $uploadMessage = "Ralat: " . $e->getMessage();
        $uploadSuccess = false;
    }
}

// Get kategori list
$kategoriList = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get list of generated apps
$generatedApps = $db->query("SELECT na.*, k.nama_kategori 
                             FROM nocode_apps na 
                             LEFT JOIN kategori k ON na.id_kategori = k.id_kategori 
                             ORDER BY na.created_at DESC 
                             LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 fw-bold text-dark">
            <i class="fas fa-magic me-3 text-primary"></i>DIY Aplikasi
        </h3>
    </div>
    
    <?php if ($uploadMessage): ?>
    <div class="alert alert-<?php echo $uploadSuccess ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $uploadSuccess ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
        <?php echo htmlspecialchars($uploadMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <!-- Upload Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Langkah 1: Upload Excel & Analisis Struktur
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <?php echo getCsrfTokenField(); ?>
                        
                        <div class="mb-3">
                            <label for="app_name" class="form-label fw-bold">Nama Aplikasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="app_name" name="app_name" required 
                                   placeholder="Cth: Sistem Pengurusan Staf">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Keterangan</label>
                            <textarea class="form-control" id="description" name="description" rows="2" 
                                      placeholder="Deskripsi aplikasi yang akan dijana..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_kategori" class="form-label fw-bold">Kategori Direktori</label>
                            <select class="form-select" id="id_kategori" name="id_kategori">
                                <option value="0">-- Pilih Kategori (Pilihan) --</option>
                                <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?php echo $kat['id_kategori']; ?>">
                                        <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Aplikasi akan dipautkan ke direktori jika kategori dipilih</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excel_file" class="form-label fw-bold">Fail Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-lg" id="excel_file" name="excel_file" 
                                   accept=".xlsx,.xls" required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Baris pertama mesti mengandungi nama kolum (header). Format: .xlsx atau .xls
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search me-2"></i>Analisis Struktur Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Analysis Result / Generation Section -->
        <div class="col-lg-6">
            <?php if ($analysisResult): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Langkah 2: Semak Struktur & Jana Aplikasi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="fw-bold">Struktur Dijumpai:</h6>
                            <ul class="list-group">
                                <?php foreach ($analysisResult['schema'] as $col): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?php echo htmlspecialchars($col['name']); ?></strong>
                                        <small class="text-muted ms-2">(<?php echo $col['type']; ?>)</small>
                                    </span>
                                    <span class="badge bg-primary"><?php echo $col['column']; ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <p class="mb-0">
                                <strong>Total Rekod:</strong> <?php echo number_format($analysisResult['total_rows']); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Slug Aplikasi:</strong> <code><?php echo htmlspecialchars($analysisResult['app_slug']); ?></code>
                            </p>
                        </div>
                        
                        <form method="POST" action="diyaplikasi_generate.php" id="generateForm">
                            <?php echo getCsrfTokenField(); ?>
                            <input type="hidden" name="generate" value="1">
                            <button type="submit" class="btn btn-success btn-lg w-100" id="generateBtn">
                                <i class="fas fa-rocket me-2"></i>Jana Aplikasi Sekarang
                            </button>
                        </form>
                        
                        <script>
                        document.getElementById('generateForm').addEventListener('submit', function(e) {
                            const btn = document.getElementById('generateBtn');
                            btn.disabled = true;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menjana aplikasi...';
                        });
                        </script>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Maklumat
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Upload fail Excel untuk mula analisis struktur.</p>
                        <p class="mb-0"><strong>Ciri-ciri:</strong></p>
                        <ul>
                            <li>Analisis struktur kolum automatik</li>
                            <li>Jana dashboard dengan visualisasi</li>
                            <li>Jana list view dengan CRUD</li>
                            <li>Integrasi dengan direktori aplikasi</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- List of Generated Apps -->
    <?php if (!empty($generatedApps)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-list me-2 text-primary"></i>Aplikasi yang Dijana
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Aplikasi</th>
                                    <th>Slug</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Dicipta</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($generatedApps as $app): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($app['app_name']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($app['app_slug']); ?></code></td>
                                    <td>
                                        <?php if ($app['nama_kategori']): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($app['nama_kategori']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($app['status'] == 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <?php
                                            $appDisplayUrl = 'apps/' . $app['app_slug'];
                                            $appLinkUrl = (isset($BASE) ? $BASE : '/myapps/') . $appDisplayUrl;
                                        ?>
                                            <a href="<?php echo htmlspecialchars($appLinkUrl); ?>" target="_blank" class="btn btn-sm btn-primary" title="<?php echo htmlspecialchars($appDisplayUrl); ?>">
                                                <i class="fas fa-external-link-alt"></i> Buka
                                            </a>
                                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($appDisplayUrl); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
