<?php
/**
 * Import Excel/CSV (.xlsx, .xls, .csv) seperti AppSheet.
 * Borang muat naik → PhpSpreadsheet → Baris 1 = metadata, Baris 2+ = data → custom_apps + custom_app_data.
 * Progress bar via XHR/fetch dengan streaming NDJSON.
 */
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$current_user_id = (int) ($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
if ($current_user_id === 0) {
    header('Location: index.php');
    exit;
}

// Senarai kategori
$kategoriList = [];
try {
    $kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $kategoriList = [['id_kategori' => 1, 'nama_kategori' => 'Dalaman']];
}

$error = '';

// PK custom_apps
$custom_app_pk = 'id';
try {
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_apps' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_app_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

/**
 * Hantar satu baris NDJSON dan flush (untuk progress stream).
 */
function streamProgress($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

$isStreamRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || !empty($_POST['use_stream']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isStreamRequest) {
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        while (ob_get_level()) ob_end_flush();
    }

    verifyCsrfToken();

    // id_kategori dari pilihan dropdown dalam modal (name="id_kategori")
    $id_kategori = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    if ($id_kategori <= 0 && count($kategoriList) > 0) {
        $id_kategori = (int) $kategoriList[0]['id_kategori'];
    }
    if ($id_kategori <= 0) {
        $id_kategori = 1;
    }

    if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        if ($isStreamRequest) {
            streamProgress(['progress' => 0, 'error' => 'Sila pilih fail (.xlsx, .xls atau .csv) untuk dimuat naik.']);
        } else {
            $error = 'Sila pilih fail (.xlsx, .xls atau .csv) untuk dimuat naik.';
        }
    } else {
        $tmpPath = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExt = ['xlsx', 'xls', 'csv'];
        if (!in_array($ext, $allowedExt, true)) {
            if ($isStreamRequest) {
                streamProgress(['progress' => 0, 'error' => 'Hanya fail .xlsx, .xls dan .csv dibenarkan.']);
            } else {
                $error = 'Hanya fail .xlsx, .xls dan .csv dibenarkan.';
            }
        } elseif (!is_dir(__DIR__ . '/vendor') || !file_exists(__DIR__ . '/vendor/autoload.php')) {
            if ($isStreamRequest) {
                streamProgress(['progress' => 0, 'error' => 'PhpSpreadsheet tidak dipasang. Sila jalankan: composer install']);
            } else {
                $error = 'PhpSpreadsheet tidak dipasang. Sila jalankan: composer install';
            }
        } else {
            require_once __DIR__ . '/vendor/autoload.php';

            try {
                \PhpOffice\PhpSpreadsheet\Shared\StringHelper::setDecimalSeparator('.');
                \PhpOffice\PhpSpreadsheet\Shared\StringHelper::setThousandsSeparator(',');

                if ($isStreamRequest) {
                    streamProgress(['progress' => 2, 'message' => 'Membaca fail...']);
                }

                if ($ext === 'csv') {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
                    $reader->setInputEncoding('UTF-8');
                    $reader->setDelimiter(',');
                    $spreadsheet = $reader->load($tmpPath);
                } else {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
                }
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                if ($highestRow < 2) {
                    if ($isStreamRequest) {
                        streamProgress(['progress' => 0, 'error' => 'Fail mesti ada baris header dan sekurang-kurangnya satu baris data.']);
                    } else {
                        $error = 'Fail mesti ada baris header dan sekurang-kurangnya satu baris data.';
                    }
                } else {
                    $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                    $headers = [];
                    for ($c = 1; $c <= $colIndex; $c++) {
                        $val = $sheet->getCellByColumnAndRow($c, 1)->getValue();
                        $headers[] = trim(is_string($val) ? $val : (string) $val);
                    }

                    // Ekstrak metadata: header → type (Tarikh=date, Emel=email, lain=text)
                    $slugify = function ($label) {
                        $s = preg_replace('/\s+/', '_', trim($label));
                        $s = preg_replace('/[^a-zA-Z0-9_]/', '', $s);
                        $s = strtolower($s);
                        return $s ?: 'column_' . substr(md5($label), 0, 6);
                    };
                    $inferType = function ($label) {
                        $lower = mb_strtolower($label);
                        if (preg_match('/tarikh/i', $lower)) return 'date';
                        if (preg_match('/emel/i', $lower)) return 'email';
                        if (preg_match('/harga|jumlah|amaun|nilai|bilangan|quantity/i', $lower)) return 'number';
                        return 'text';
                    };

                    $fields = [];
                    $seen = [];
                    foreach ($headers as $idx => $label) {
                        $name = $slugify($label);
                        if (isset($seen[$name])) {
                            $name = $name . '_' . ($idx + 1);
                        }
                        $seen[$name] = true;
                        $fields[] = [
                            'name'     => $name,
                            'label'    => $label ?: ('Kolum ' . ($idx + 1)),
                            'type'     => $inferType($label),
                            'required' => false
                        ];
                    }

                    if ($isStreamRequest) {
                        streamProgress(['progress' => 5, 'message' => 'Metadata diekstrak. Mendaftar aplikasi...']);
                    }

                    $app_name = pathinfo($origName, PATHINFO_FILENAME);
                    $app_name = trim(preg_replace('/[^\pL\pN\s\-_]/u', ' ', $app_name)) ?: 'imported_app';
                    $base_slug = strtolower(preg_replace('/\s+/', '-', preg_replace('/[^a-zA-Z0-9_\s\-]/', '', $app_name)));
                    $base_slug = $base_slug ?: 'imported';
                    $app_slug = $base_slug;
                    $suffix = 0;
                    while (true) {
                        $stmt = $pdo->prepare("SELECT 1 FROM custom_apps WHERE app_slug = ? LIMIT 1");
                        $stmt->execute([$app_slug]);
                        if (!$stmt->fetch()) break;
                        $app_slug = $base_slug . '-' . (++$suffix);
                    }

                    $metadata = [
                        'fields'       => $fields,
                        'settings'     => [],
                        'layout_type'  => 'simple_list'
                    ];
                    $metadata_json = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $insApp = $pdo->prepare("INSERT INTO custom_apps (app_slug, app_name, metadata, id_user_owner, id_kategori) VALUES (?, ?, ?, ?, ?)");
                    $insApp->execute([$app_slug, $app_name, $metadata_json, $current_user_id, $id_kategori]);
                    $id_custom = (int) $pdo->lastInsertId();
                    if ($id_custom <= 0) {
                        $stmt = $pdo->prepare("SELECT " . $custom_app_pk . " FROM custom_apps WHERE app_slug = ? LIMIT 1");
                        $stmt->execute([$app_slug]);
                        $r = $stmt->fetch(PDO::FETCH_ASSOC);
                        $id_custom = (int) ($r[$custom_app_pk] ?? 0);
                    }

                    if ($id_custom <= 0) {
                        if ($isStreamRequest) {
                            streamProgress(['progress' => 0, 'error' => 'Gagal mendaftar aplikasi ke pangkalan data.']);
                        } else {
                            $error = 'Gagal mendaftar aplikasi ke pangkalan data.';
                        }
                    } else {
                        if ($isStreamRequest) {
                            streamProgress(['progress' => 10, 'message' => 'Memproses rekod ke jadual JSON...']);
                        }

                        // Automatik proses setiap baris Excel ke jadual custom_app_data (kolum payload = JSON)
                        $insData = $pdo->prepare("INSERT INTO custom_app_data (id_custom, created_by, payload, created_at) VALUES (?, ?, ?, NOW())");
                        $totalRows = $highestRow - 1; // data rows (exclude header)
                        $inserted = 0;
                        $lastProgress = 10;

                        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
                            $payload = [];
                            $hasAny = false;
                            foreach ($fields as $colIdx => $field) {
                                $cellVal = $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getValue();
                                if ($cellVal !== null && $cellVal !== '') $hasAny = true;
                                $payload[$field['name']] = $cellVal === null ? '' : (is_string($cellVal) ? trim($cellVal) : (string) $cellVal);
                            }
                            if ($hasAny) {
                                $payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                $insData->execute([$id_custom, $current_user_id, $payload_json]);
                                $inserted++;
                            }

                            if ($isStreamRequest && $totalRows > 0) {
                                $pct = 10 + (int) round(90 * ($rowNum - 1) / $totalRows);
                                if ($pct >= $lastProgress + 5 || $rowNum === $highestRow) {
                                    $lastProgress = $pct;
                                    streamProgress(['progress' => min(99, $pct), 'message' => 'Baris ' . ($rowNum - 1) . ' / ' . $totalRows]);
                                }
                            }
                        }

                        $redirect = 'engine.php?app_slug=' . urlencode($app_slug) . '&page=list&imported=1';
                        if ($isStreamRequest) {
                            streamProgress(['progress' => 100, 'message' => 'Selesai.', 'success' => true, 'redirect' => $redirect, 'inserted' => $inserted]);
                        } else {
                            header('Location: ' . $redirect);
                            exit;
                        }
                    }
                }
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                if ($isStreamRequest) {
                    streamProgress(['progress' => 0, 'error' => 'Fail tidak boleh dibaca: ' . $e->getMessage()]);
                } else {
                    $error = 'Fail tidak boleh dibaca: ' . $e->getMessage();
                }
            } catch (PDOException $e) {
                if ($isStreamRequest) {
                    streamProgress(['progress' => 0, 'error' => 'Ralat pangkalan data: ' . $e->getMessage()]);
                } else {
                    $error = 'Ralat pangkalan data: ' . $e->getMessage();
                }
                error_log('import_excel PDO: ' . $e->getMessage());
            } catch (Exception $e) {
                if ($isStreamRequest) {
                    streamProgress(['progress' => 0, 'error' => $e->getMessage()]);
                } else {
                    $error = $e->getMessage();
                }
                error_log('import_excel: ' . $e->getMessage());
            }
        }
    }

    if ($isStreamRequest) {
        exit;
    }
}

require_once __DIR__ . '/header.php';
?>

<style>
.import-excel-card .card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
.import-excel-card .card-header { border-radius: 16px 16px 0 0; border: none; padding: 1rem 1.5rem; }
.import-excel-zone { border: 2px dashed #cbd5e0; border-radius: 16px; padding: 2.5rem; text-align: center; background: #f8fafc; transition: border-color .2s, background .2s; cursor: pointer; }
.import-excel-zone:hover, .import-excel-zone.dragover { border-color: #4a90e2; background: #eff6ff; }
.import-excel-zone.has-file { border-color: #22c55e; background: #f0fdf4; }
.import-excel-zone .zone-icon { font-size: 3rem; color: #94a3b8; margin-bottom: 0.75rem; }
.import-excel-zone.has-file .zone-icon { color: #22c55e; }
.import-excel-zone .zone-text { color: #64748b; font-size: 1rem; }
.import-excel-zone .zone-hint { color: #94a3b8; font-size: 0.875rem; margin-top: 0.5rem; }
.import-excel-progress { height: 28px; border-radius: 14px; overflow: hidden; }
.import-excel-progress .progress-bar { font-size: 0.8rem; font-weight: 600; }
.import-success-box { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; border-radius: 16px; padding: 1.5rem; }
</style>

<div class="container-fluid import-excel-card">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-file-excel me-2"></i>
                    <h5 class="card-title mb-0">Import Excel ke Aplikasi</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">Muat naik fail <strong>.xlsx</strong>, <strong>.xls</strong> atau <strong>.csv</strong>. Baris pertama = header (nama medan). Baris seterusnya = data. Metadata (jenis medan) dijana automatik: &quot;Tarikh&quot; → date, &quot;Emel&quot; → email, &quot;Harga&quot;/&quot;Jumlah&quot; → number.</p>

                    <?php if ($error !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                    <?php endif; ?>

                    <div id="importFormSection">
                        <form id="importExcelForm" method="post" enctype="multipart/form-data" action="import_excel.php">
                            <?php echo getCsrfTokenField(); ?>
                            <input type="hidden" name="use_stream" value="1">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="id_kategori" class="form-label fw-semibold">Kategori</label>
                                    <select class="form-select form-select-lg" id="id_kategori" name="id_kategori">
                                        <?php foreach ($kategoriList as $kat): ?>
                                        <option value="<?php echo (int) $kat['id_kategori']; ?>"><?php echo htmlspecialchars($kat['nama_kategori']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Fail Excel / CSV (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                                    <div class="import-excel-zone rounded-3" id="dropZone">
                                        <input type="file" class="d-none" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                        <div class="zone-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                        <div class="zone-text" id="zoneText">Seret fail ke sini atau klik untuk pilih</div>
                                        <div class="zone-hint">Fail .xlsx, .xls atau .csv • Baris pertama = header</div>
                                    </div>
                                </div>
                                <div id="progressSection" class="col-12 d-none">
                                    <label class="form-label fw-semibold">Status</label>
                                    <div class="progress import-excel-progress">
                                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                    <p id="progressMessage" class="small text-muted mt-2 mb-0"></p>
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg px-4" id="btnSubmit" disabled>
                                        <i class="fas fa-upload me-1"></i> Muat naik & Import
                                    </button>
                                    <a href="dashboard_aplikasi.php" class="btn btn-outline-secondary btn-lg">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div id="successSection" class="d-none">
                        <div class="import-success-box text-center">
                            <div class="mb-3"><i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i></div>
                            <h5 class="text-success mb-2">Import Selesai</h5>
                            <p id="successMessage" class="text-muted mb-4">Rekod telah disimpan ke aplikasi.</p>
                            <a href="#" id="btnGoToApp" class="btn btn-success btn-lg px-4">
                                <i class="fas fa-external-link-alt me-1"></i> Go to App
                            </a>
                            <div class="mt-3">
                                <a href="import_excel.php" class="btn btn-outline-primary btn-sm">Import fail lain</a>
                                <a href="dashboard_aplikasi.php" class="btn btn-outline-secondary btn-sm ms-2">Dashboard Aplikasi</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('importExcelForm');
    var fileInput = document.getElementById('file');
    var dropZone = document.getElementById('dropZone');
    var zoneText = document.getElementById('zoneText');
    var progressSection = document.getElementById('progressSection');
    var progressBar = document.getElementById('progressBar');
    var progressMessage = document.getElementById('progressMessage');
    var btnSubmit = document.getElementById('btnSubmit');
    var importFormSection = document.getElementById('importFormSection');
    var successSection = document.getElementById('successSection');
    var successMessage = document.getElementById('successMessage');
    var btnGoToApp = document.getElementById('btnGoToApp');

    dropZone.addEventListener('click', function() { fileInput.click(); });
    fileInput.addEventListener('change', function() {
        var f = fileInput.files && fileInput.files[0];
        if (f) {
            zoneText.textContent = f.name;
            dropZone.classList.add('has-file');
            btnSubmit.disabled = false;
        } else {
            zoneText.textContent = 'Seret fail ke sini atau klik untuk pilih';
            dropZone.classList.remove('has-file');
            btnSubmit.disabled = true;
        }
    });
    dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function() { dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        var files = e.dataTransfer && e.dataTransfer.files;
        if (files && files.length) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    function showSuccess(data) {
        importFormSection.classList.add('d-none');
        successSection.classList.remove('d-none');
        var msg = (data.inserted !== undefined) ? data.inserted + ' rekod berjaya diimport.' : 'Rekod telah disimpan ke aplikasi.';
        successMessage.textContent = msg;
        if (data.redirect) btnGoToApp.href = data.redirect;
    }

    function updateProgress(data) {
        if (data.error) {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
            progressBar.style.width = '100%';
            progressBar.textContent = 'Ralat';
            progressMessage.textContent = data.error;
            btnSubmit.disabled = false;
            return true;
        }
        if (typeof data.progress !== 'undefined') {
            progressBar.style.width = data.progress + '%';
            progressBar.setAttribute('aria-valuenow', data.progress);
            progressBar.textContent = data.progress + '%';
        }
        if (data.message) progressMessage.textContent = data.message;
        if (data.success && data.redirect) {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';
            progressMessage.textContent = 'Selesai.';
            showSuccess(data);
            return true;
        }
        return false;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Sila pilih fail .xlsx, .xls atau .csv');
            return;
        }
        var fd = new FormData(form);
        fd.set('use_stream', '1');
        progressSection.classList.remove('d-none');
        btnSubmit.disabled = true;
        progressBar.classList.remove('bg-danger', 'bg-success');
        progressBar.classList.add('progress-bar-animated');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressMessage.textContent = 'Menghantar fail...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);

        xhr.upload.addEventListener('progress', function(ev) {
            if (ev.lengthComputable && ev.total > 0) {
                var pct = Math.round((ev.loaded / ev.total) * 50);
                progressBar.style.width = pct + '%';
                progressBar.setAttribute('aria-valuenow', pct);
                progressBar.textContent = pct + '%';
                progressMessage.textContent = 'Muat naik: ' + ev.loaded + ' / ' + ev.total + ' bait';
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status !== 200) {
                progressMessage.textContent = 'Ralat: ' + xhr.status;
                btnSubmit.disabled = false;
                return;
            }
            var text = xhr.responseText || '';
            var lastData = null;
            text.split('\n').forEach(function(line) {
                line = line.trim();
                if (!line) return;
                try {
                    var data = JSON.parse(line);
                    lastData = data;
                    updateProgress(data);
                } catch (err) {}
            });
            if (lastData && lastData.success && lastData.redirect) {
                showSuccess(lastData);
            }
            btnSubmit.disabled = false;
        });

        xhr.addEventListener('error', function() {
            progressMessage.textContent = 'Ralat rangkaian. Sila cuba lagi.';
            btnSubmit.disabled = false;
        });

        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(fd);
    });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
