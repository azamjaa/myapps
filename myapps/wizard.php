<?php
/**
 * Wizard - Cipta aplikasi 4 langkah (Stepped Wizard).
 * Langkah 1: Source Data (Blank Form | Excel Upload | Borang Fizikal/Scan) - Excel: baca header + simpan data ke custom_app_data bila publish.
 * Langkah 2: Cipta Halaman (Multi-page: Nama + Layout List/Card/Calendar).
 * Langkah 3: Dashboard Builder (Widgets e.g. Kad Statistik).
 * Langkah 4: Pengesahan & Publish - Nama App, Slug, Kategori → simpan metadata ke custom_apps (JSON) + import data Excel ke custom_app_data.
 */
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$current_user_id = (int) ($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
$kategoriList = [];
try {
    $kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $kategoriList = [['id_kategori' => 1, 'nama_kategori' => 'Dalaman'], ['id_kategori' => 2, 'nama_kategori' => 'Luaran'], ['id_kategori' => 3, 'nama_kategori' => 'Gunasama']];
}

$custom_app_pk = 'id';
try {
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_apps' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_app_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

// --- Action: Parse Excel (baca header + semua baris; simpan baris dalam session untuk insert bila publish)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'parse_excel') {
    header('Content-Type: application/json; charset=utf-8');
    $out = ['success' => false, 'fields' => []];
    verifyCsrfToken();
    if (empty($_FILES['excel_file']['tmp_name']) || !is_uploaded_file($_FILES['excel_file']['tmp_name'])) {
        $out['message'] = 'Sila pilih fail Excel.';
        echo json_encode($out);
        exit;
    }
    $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'xlsx') {
        $out['message'] = 'Hanya fail .xlsx dibenarkan.';
        echo json_encode($out);
        exit;
    }
    if (!is_file(__DIR__ . '/vendor/autoload.php')) {
        $out['message'] = 'PhpSpreadsheet tidak dipasang. Sila jalankan: composer install';
        echo json_encode($out);
        exit;
    }
    require_once __DIR__ . '/vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
    try {
        $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        if ($highestRow < 1) {
            $out['message'] = 'Fail tiada data.';
            echo json_encode($out);
            exit;
        }
        $colIndex = Coordinate::columnIndexFromString($highestColumn);
        $headers = [];
        for ($c = 1; $c <= $colIndex; $c++) {
            $val = $sheet->getCellByColumnAndRow($c, 1)->getValue();
            $headers[] = trim(is_string($val) ? $val : (string) $val);
        }
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
            if (isset($seen[$name])) $name = $name . '_' . ($idx + 1);
            $seen[$name] = true;
            $fields[] = ['name' => $name, 'label' => $label ?: ('Kolum ' . ($idx + 1)), 'type' => $inferType($label), 'required' => false];
        }
        $rows = [];
        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $payload = [];
            $hasAny = false;
            foreach ($fields as $colIdx => $field) {
                $cellVal = $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getValue();
                if ($cellVal !== null && $cellVal !== '') $hasAny = true;
                $payload[$field['name']] = $cellVal === null ? '' : (is_string($cellVal) ? trim($cellVal) : (string) $cellVal);
            }
            if ($hasAny) $rows[] = $payload;
        }
        $_SESSION['wizard_excel_fields'] = $fields;
        $_SESSION['wizard_excel_rows'] = $rows;
        $out['success'] = true;
        $out['fields'] = $fields;
        $out['rows_count'] = count($rows);
    } catch (Exception $e) {
        $out['message'] = 'Ralat baca Excel: ' . $e->getMessage();
    }
    echo json_encode($out);
    exit;
}

// --- Action: Publish (simpan metadata ke custom_apps + import baris Excel ke custom_app_data jika ada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish') {
    header('Content-Type: application/json; charset=utf-8');
    $out = ['success' => false, 'message' => ''];
    try {
        verifyCsrfToken();
        if ($current_user_id <= 0) {
            $out['message'] = 'Sesi tidak sah.';
            echo json_encode($out);
            exit;
        }
        $nama_aplikasi = trim($_POST['nama_aplikasi'] ?? '');
        $url_slug = trim($_POST['url_slug'] ?? '');
        $url_slug = strtolower(preg_replace('/\s+/', '-', preg_replace('/[^a-zA-Z0-9_\s\-]/', '', $url_slug)));
        $id_kategori = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
        $metadata_json = $_POST['metadata_json'] ?? '{}';
        if ($nama_aplikasi === '') {
            $out['message'] = 'Nama Aplikasi wajib diisi.';
            echo json_encode($out);
            exit;
        }
        if ($url_slug === '') {
            $out['message'] = 'URL Slug wajib diisi.';
            echo json_encode($out);
            exit;
        }
        if ($id_kategori <= 0 && count($kategoriList) > 0) {
            $id_kategori = (int) $kategoriList[0]['id_kategori'];
        }
        if ($id_kategori <= 0) $id_kategori = 1;
        $meta = json_decode($metadata_json, true);
        if (!is_array($meta)) {
            $out['message'] = 'Metadata tidak sah.';
            echo json_encode($out);
            exit;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM custom_apps WHERE app_slug = ? LIMIT 1");
        $stmt->execute([$url_slug]);
        if ($stmt->fetch()) {
            $out['message'] = 'URL Slug sudah digunakan. Sila pilih slug lain.';
            echo json_encode($out);
            exit;
        }
        $ins = $pdo->prepare("INSERT INTO custom_apps (app_slug, app_name, metadata, id_user_owner, id_kategori) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$url_slug, $nama_aplikasi, $metadata_json, $current_user_id, $id_kategori]);
        $id_custom = (int) $pdo->lastInsertId();
        if ($id_custom <= 0) {
            $r = $pdo->prepare("SELECT " . $custom_app_pk . " FROM custom_apps WHERE app_slug = ? LIMIT 1");
            $r->execute([$url_slug]);
            $row = $r->fetch(PDO::FETCH_ASSOC);
            $id_custom = (int) ($row[$custom_app_pk] ?? 0);
        }
        $inserted = 0;
        $had_excel = isset($_POST['had_excel']) && (int) $_POST['had_excel'] === 1;
        if ($id_custom > 0 && $had_excel && !empty($_SESSION['wizard_excel_rows']) && is_array($_SESSION['wizard_excel_rows'])) {
            $insData = $pdo->prepare("INSERT INTO custom_app_data (id_custom, created_by, payload, created_at) VALUES (?, ?, ?, NOW())");
            foreach ($_SESSION['wizard_excel_rows'] as $payload) {
                $payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $insData->execute([$id_custom, $current_user_id, $payload_json]);
                $inserted++;
            }
            unset($_SESSION['wizard_excel_rows'], $_SESSION['wizard_excel_fields']);
        }
        $out['success'] = true;
        $out['message'] = 'Aplikasi berjaya diterbitkan.' . ($inserted > 0 ? ' ' . $inserted . ' rekod dari Excel telah diimport.' : '');
        $out['slug'] = $url_slug;
        $out['url'] = 'apps/' . urlencode($url_slug);
    } catch (Exception $e) {
        $out['message'] = $e->getMessage();
        error_log('wizard publish: ' . $e->getMessage());
    }
    echo json_encode($out);
    exit;
}

require_once __DIR__ . '/header.php';
?>

<style>
.wizard-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
.wizard-step-item { padding: 0.75rem 1rem; border-radius: 12px; transition: all .2s; }
.wizard-step-item.active { background: #eff6ff; color: #1d4ed8; font-weight: 600; }
.wizard-step-item.done { color: #16a34a; }
.wizard-step-item .step-num { width: 28px; height: 28px; border-radius: 50%; background: #e5e7eb; color: #6b7280; display: inline-flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600; }
.wizard-step-item.active .step-num { background: #3b82f6; color: #fff; }
.wizard-step-item.done .step-num { background: #22c55e; color: #fff; }
.wizard-source-option { border: 2px solid #e5e7eb; border-radius: 16px; padding: 1.5rem; cursor: pointer; transition: all .2s; height: 100%; }
.wizard-source-option:hover { border-color: #93c5fd; background: #f8fafc; }
.wizard-source-option.selected { border-color: #3b82f6; background: #eff6ff; }
.wizard-source-option input { position: absolute; opacity: 0; }
.wizard-panel { display: none; }
.wizard-panel.active { display: block; animation: fadeIn .25s ease; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.wizard-row-item { background: #f8fafc; border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 0.5rem; }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card wizard-card shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h4 class="mb-2"><i class="fas fa-layer-group me-2 text-primary"></i>Wizard Cipta Aplikasi</h4>
                    <p class="text-muted small mb-4">5 Fasa: Identiti → Data → Halaman → Workflow → Deploy.</p>

                    <div class="mb-4">
                        <div class="progress mb-2" style="height: 6px;">
                            <div id="wizardProgress" class="progress-bar bg-primary" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-between">
                            <button type="button" class="wizard-step-item active border-0 bg-transparent text-start" data-step="1"><span class="step-num me-2">1</span>Identiti</button>
                            <button type="button" class="wizard-step-item border-0 bg-transparent text-start" data-step="2"><span class="step-num me-2">2</span>Data</button>
                            <button type="button" class="wizard-step-item border-0 bg-transparent text-start" data-step="3"><span class="step-num me-2">3</span>Halaman</button>
                            <button type="button" class="wizard-step-item border-0 bg-transparent text-start" data-step="4"><span class="step-num me-2">4</span>Workflow</button>
                            <button type="button" class="wizard-step-item border-0 bg-transparent text-start" data-step="5"><span class="step-num me-2">5</span>Deploy</button>
                        </div>
                    </div>

                    <div id="builderAlert" class="alert d-none" role="alert"></div>

                    <!-- Langkah 1: Identiti & Sumber Data -->
                    <div id="panel1" class="wizard-panel active">
                        <h6 class="text-muted mb-3">Fasa 1: Identiti & Sumber Data</h6>
                        
                        <!-- Identiti Aplikasi -->
                        <div class="mb-4">
                            <label for="app_name_step1" class="form-label fw-semibold">Nama Aplikasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="app_name_step1" placeholder="Contoh: Sistem Aduan Awam">
                            <small class="text-muted">Nama ini akan dipaparkan di dashboard</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="app_category_step1" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="app_category_step1">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?php echo (int)$k['id_kategori']; ?>"><?php echo htmlspecialchars($k['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Dalaman / Luaran / Gunasama</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Slug URL (Auto-generated)</label>
                            <input type="text" class="form-control" id="app_slug_step1" readonly>
                            <small class="text-muted">URL: <code>myapps/apps/<span id="slugPreview">-</span></code></small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Sumber Data -->
                        <h6 class="text-muted mb-3">Sumber Data</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="wizard-source-option d-block position-relative" id="opt-blank">
                                    <input type="radio" name="wizard_source" value="blank" checked>
                                    <div class="text-center">
                                        <i class="fas fa-file fa-2x text-muted mb-2"></i>
                                        <div class="fw-semibold">Blank Form</div>
                                        <small class="text-muted">Borang kosong, isi medan kemudian</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="wizard-source-option d-block position-relative" id="opt-excel">
                                    <input type="radio" name="wizard_source" value="excel">
                                    <div class="text-center">
                                        <i class="fas fa-file-excel fa-2x text-success mb-2"></i>
                                        <div class="fw-semibold">Excel Upload</div>
                                        <small class="text-muted">Muat naik .xlsx; header + data disimpan</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="wizard-source-option d-block position-relative" id="opt-fizikal">
                                    <input type="radio" name="wizard_source" value="fizikal">
                                    <div class="text-center">
                                        <i class="fas fa-clipboard-list fa-2x text-info mb-2"></i>
                                        <div class="fw-semibold">Borang Fizikal/Scan</div>
                                        <small class="text-muted">Data dari borang fizikal atau imbasan</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div id="excelUploadZone" class="mb-4" style="display: none;">
                            <label class="form-label">Muat naik fail Excel (.xlsx)</label>
                            <input type="file" class="form-control" id="excel_file" accept=".xlsx">
                            <small class="text-muted">Baris pertama = header. Data baris seterusnya akan disimpan ke custom_app_data apabila anda Publish.</small>
                        </div>
                        
                        <div id="manualFieldsZone" class="mb-4" style="display: none;">
                            <label class="form-label fw-semibold">Bina Field Secara Manual</label>
                            <div id="manualFieldsList"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddManualField"><i class="fas fa-plus me-1"></i> Tambah Field</button>
                        </div>
                    </div>

                    <!-- Langkah 2: Definisi Data (Hybrid Skeleton) -->
                    <div id="panel2" class="wizard-panel">
                        <h6 class="text-muted mb-3">Fasa 2: Cipta Halaman (Multi-page)</h6>
                        <p class="small text-muted mb-3">Tambah lebih dari satu halaman. Setiap halaman: Nama Halaman dan Jenis Layout.</p>
                        <div id="pagesList"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddPage"><i class="fas fa-plus me-1"></i> Tambah Halaman</button>
                    </div>

                    <!-- Langkah 3: Dashboard Builder (Widgets) -->
                    <div id="panel3" class="wizard-panel">
                        <h6 class="text-muted mb-3">Dashboard Builder – Widgets</h6>
                        <p class="small text-muted mb-3">Pilih widget untuk Dashboard. Contoh: <strong>Kad Statistik</strong> – pilih rekod/medan mana nak kira (e.g. Jumlah Aduan).</p>
                        <div id="dashboardWidgetsList"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddWidget"><i class="fas fa-plus me-1"></i> Tambah Kad Statistik</button>
                        <p class="small text-muted mt-2 mb-0" id="dashboardNoFields" style="display: none;">Tiada medan. Pilih Excel Upload dalam Langkah 1 untuk medan automatik.</p>
                    </div>

                    <!-- Langkah 4: Workflow & Automation -->
                    <div id="panel4" class="wizard-panel">
                        <h6 class="text-muted mb-3">Workflow & Automation (If-This-Then-That)</h6>
                        <p class="small text-muted mb-3">Bina logik automasi: Apabila rekod ditambah/dikemaskini, semak syarat, dan jalankan aksi (emel notifikasi).</p>
                        <div id="workflowsList"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddWorkflow"><i class="fas fa-plus me-1"></i> Tambah Workflow</button>
                        <p class="small text-muted mt-2 mb-0" id="workflowNoFields" style="display: none;">Tiada medan untuk workflow. Sila pilih Excel Upload atau Bina Manual dalam Langkah 1.</p>
                    </div>

                    <!-- Langkah 5: Pengesahan & Publish -->
                    <div id="panel5" class="wizard-panel">
                        <h6 class="text-muted mb-3">Pengesahan & Publish</h6>
                        <p class="small text-muted mb-3">Masukkan Nama App, Slug URL, dan Kategori. Semua tetapan disimpan ke lajur <code>metadata</code> jadual custom_apps (JSON).</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="final_nama" class="form-label">Nama App <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="final_nama" placeholder="Contoh: Aduan Awam">
                            </div>
                            <div class="col-md-6">
                                <label for="final_slug" class="form-label">Slug URL <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="final_slug" placeholder="aduan-awam" pattern="[a-z0-9_-]+">
                                <small class="text-muted">Aplikasi: <code>myapps/apps/<span id="finalSlugPreview">slug</span></code></small>
                            </div>
                            <div class="col-md-6">
                                <label for="final_kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="final_kategori">
                                    <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?php echo (int)$k['id_kategori']; ?>"><?php echo htmlspecialchars($k['nama_kategori']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Dalaman / Luaran / Gunasama</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn btn-success btn-lg px-4" id="btnPublish"><i class="fas fa-check me-1"></i> Deploy Application</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary" id="wizardPrev" style="display: none;"><i class="fas fa-chevron-left me-1"></i> Sebelumnya</button>
                        <button type="button" class="btn btn-primary ms-auto" id="wizardNext">Seterusnya <i class="fas fa-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="wizardForm"><?php echo getCsrfTokenField(); ?></form>

<script>
(function() {
    var currentStep = 1;
    var totalSteps = 5;
    var wizardFields = [];
    var wizardSummaryCards = [];
    var wizardWorkflows = [];
    var hadExcelUpload = false;

    // Auto-generate slug dari nama aplikasi
    document.getElementById('app_name_step1').addEventListener('input', function() {
        var nama = this.value.trim();
        var slug = nama.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9_-]/g, '');
        document.getElementById('app_slug_step1').value = slug;
        document.getElementById('slugPreview').textContent = slug || '-';
    });

    function updateProgress() {
        var pct = (currentStep / totalSteps) * 100;
        var el = document.getElementById('wizardProgress');
        if (el) { el.style.width = pct + '%'; el.setAttribute('aria-valuenow', pct); }
        document.querySelectorAll('.wizard-step-item').forEach(function(item) {
            var s = parseInt(item.getAttribute('data-step'), 10);
            item.classList.remove('active', 'done');
            if (s === currentStep) item.classList.add('active');
            else if (s < currentStep) item.classList.add('done');
        });
        document.querySelectorAll('.wizard-panel').forEach(function(panel) {
            panel.classList.toggle('active', parseInt(panel.id.replace('panel', ''), 10) === currentStep);
        });
        document.getElementById('wizardPrev').style.display = currentStep === 1 ? 'none' : 'inline-block';
        document.getElementById('wizardNext').style.display = currentStep === totalSteps ? 'none' : 'inline-block';
    }

    document.querySelectorAll('.wizard-step-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var s = parseInt(this.getAttribute('data-step'), 10);
            if (s >= 1 && s <= totalSteps) { currentStep = s; updateProgress(); }
            if (currentStep === 3) renderDashboardWidgets();
            if (currentStep === 4) renderWorkflows();
        });
    });

    document.getElementById('wizardNext').addEventListener('click', function() {
        if (currentStep === 1) {
            // Validasi Fasa 1
            var nama = document.getElementById('app_name_step1').value.trim();
            var kategori = document.getElementById('app_category_step1').value;
            if (!nama) {
                alert('Sila isi Nama Aplikasi.');
                return;
            }
            if (!kategori) {
                alert('Sila pilih Kategori.');
                return;
            }
            
            var source = document.querySelector('input[name="wizard_source"]:checked').value;
            if (source === 'excel') {
                var fileInput = document.getElementById('excel_file');
                if (!fileInput.files || !fileInput.files[0]) {
                    alert('Sila pilih fail Excel.');
                    return;
                }
                var fd = new FormData();
                fd.append('action', 'parse_excel');
                fd.append('csrf_token', document.querySelector('#wizardForm input[name="csrf_token"]').value);
                fd.append('excel_file', fileInput.files[0]);
                fetch('wizard.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.fields) {
                            wizardFields = data.fields;
                            hadExcelUpload = true;
                            currentStep = 2;
                            updateProgress();
                            renderDashboardWidgets();
                            // Terus ke step seterusnya tanpa alert/modal
                            // data.rows_count baris akan diimport bila publish
                        } else {
                            alert(data.message || 'Gagal baca Excel.');
                        }
                    })
                    .catch(function() { alert('Ralat rangkaian.'); });
                return;
            } else if (source === 'fizikal') {
                // Gunakan manual fields dari manualFieldsList
                wizardFields = collectManualFields();
                if (wizardFields.length === 0) {
                    alert('Sila tambah sekurang-kurangnya 1 field untuk borang fizikal.');
                    return;
                }
                hadExcelUpload = false;
            } else {
                // Blank form mode
                hadExcelUpload = false;
            }
        }
        if (currentStep < totalSteps) { currentStep++; updateProgress(); }
        if (currentStep === 3) renderDashboardWidgets();
        if (currentStep === 4) renderWorkflows();
    });

    document.getElementById('wizardPrev').addEventListener('click', function() {
        if (currentStep > 1) { currentStep--; updateProgress(); }
    });

    ['opt-blank', 'opt-excel', 'opt-fizikal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', function() {
            document.querySelectorAll('.wizard-source-option').forEach(function(o) { o.classList.remove('selected'); });
            this.classList.add('selected');
            var selected = document.querySelector('input[name="wizard_source"]:checked').value;
            document.getElementById('excelUploadZone').style.display = selected === 'excel' ? 'block' : 'none';
            document.getElementById('manualFieldsZone').style.display = selected === 'fizikal' ? 'block' : 'none';
        });
    });
    document.getElementById('opt-blank').click();

    // Manual Field Builder
    function addManualFieldRow(data) {
        data = data || {};
        var row = document.createElement('div');
        row.className = 'wizard-row-item d-flex align-items-center gap-2 flex-wrap mb-2';
        row.innerHTML = '<input type="text" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Nama Field" value="' + (data.name || '').replace(/"/g, '&quot;') + '">' +
            '<input type="text" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Label" value="' + (data.label || '').replace(/"/g, '&quot;') + '">' +
            '<select class="form-select form-select-sm" style="max-width: 120px;"><option value="text"' + (data.type === 'text' ? ' selected' : '') + '>Text</option><option value="date"' + (data.type === 'date' ? ' selected' : '') + '>Date</option><option value="number"' + (data.type === 'number' ? ' selected' : '') + '>Number</option><option value="select"' + (data.type === 'select' ? ' selected' : '') + '>Select</option></select>' +
            '<button type="button" class="btn btn-outline-danger btn-sm btn-remove-manual-field"><i class="fas fa-times"></i></button>';
        document.getElementById('manualFieldsList').appendChild(row);
        row.querySelector('.btn-remove-manual-field').addEventListener('click', function() { row.remove(); });
    }
    document.getElementById('btnAddManualField').addEventListener('click', function() { addManualFieldRow({}); });
    
    function collectManualFields() {
        var fields = [];
        document.querySelectorAll('#manualFieldsList .wizard-row-item').forEach(function(row) {
            var inputs = row.querySelectorAll('input');
            var select = row.querySelector('select');
            var name = inputs[0] && inputs[0].value ? inputs[0].value.trim() : '';
            var label = inputs[1] && inputs[1].value ? inputs[1].value.trim() : '';
            var type = select ? select.value : 'text';
            if (name && label) {
                fields.push({ name: name, label: label, type: type, required: false });
            }
        });
        return fields;
    }

    function addPageRow(data) {
        data = data || {};
        var row = document.createElement('div');
        row.className = 'wizard-row-item d-flex align-items-center gap-2 flex-wrap';
        row.innerHTML = '<input type="text" class="form-control form-control-sm flex-grow-1" style="max-width: 220px;" placeholder="Nama Halaman" value="' + (data.label || '').replace(/"/g, '&quot;') + '">' +
            '<select class="form-select form-select-sm" style="max-width: 180px;"><option value="list"' + (data.layout === 'list' ? ' selected' : '') + '>Table View</option><option value="card"' + (data.layout === 'card' ? ' selected' : '') + '>Card View</option><option value="calendar"' + (data.layout === 'calendar' ? ' selected' : '') + '>Calendar View</option></select>' +
            '<button type="button" class="btn btn-outline-danger btn-sm btn-remove-page"><i class="fas fa-times"></i></button>';
        document.getElementById('pagesList').appendChild(row);
        row.querySelector('.btn-remove-page').addEventListener('click', function() { row.remove(); });
    }
    document.getElementById('btnAddPage').addEventListener('click', function() { addPageRow({}); });
    addPageRow({ label: 'Senarai', layout: 'list' });
    addPageRow({ label: 'Borang', layout: 'list' });

    function addWidgetRow(data) {
        data = data || {};
        var row = document.createElement('div');
        row.className = 'wizard-row-item d-flex align-items-center gap-2 flex-wrap';
        var fieldOpts = wizardFields.length ? wizardFields.map(function(f) {
            return '<option value="' + f.name.replace(/"/g, '&quot;') + '"' + (data.field === f.name ? ' selected' : '') + '>' + (f.label || f.name) + '</option>';
        }).join('') : '<option value="">-- Tiada medan --</option>';
        row.innerHTML = '<span class="small text-muted">Kad Statistik:</span>' +
            '<input type="text" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Contoh: Jumlah Aduan" value="' + (data.title || '').replace(/"/g, '&quot;') + '">' +
            '<select class="form-select form-select-sm" style="max-width: 160px;">' + fieldOpts + '</select>' +
            '<select class="form-select form-select-sm" style="max-width: 120px;"><option value="count"' + (data.agg === 'count' ? ' selected' : '') + '>Kira</option><option value="sum"' + (data.agg === 'sum' ? ' selected' : '') + '>Jumlah</option><option value="average"' + (data.agg === 'average' ? ' selected' : '') + '>Purata</option></select>' +
            '<button type="button" class="btn btn-outline-danger btn-sm btn-remove-widget"><i class="fas fa-times"></i></button>';
        document.getElementById('dashboardWidgetsList').appendChild(row);
        row.querySelector('.btn-remove-widget').addEventListener('click', function() { row.remove(); });
    }
    function renderDashboardWidgets() {
        var container = document.getElementById('dashboardWidgetsList');
        var noFields = document.getElementById('dashboardNoFields');
        container.innerHTML = '';
        if (wizardFields.length === 0) {
            noFields.style.display = 'block';
            return;
        }
        noFields.style.display = 'none';
        wizardSummaryCards.forEach(function(c) { addWidgetRow(c); });
    }
    document.getElementById('btnAddWidget').addEventListener('click', function() {
        if (wizardFields.length === 0) { alert('Tiada medan. Pilih Excel Upload dalam Langkah 1.'); return; }
        addWidgetRow({});
    });

    // Workflow Builder
    function addWorkflowRow(data) {
        data = data || {};
        var row = document.createElement('div');
        row.className = 'wizard-row-item mb-3 p-3 border rounded';
        var fieldOpts = wizardFields.length ? wizardFields.map(function(f) {
            return '<option value="' + f.name.replace(/"/g, '&quot;') + '"' + (data.condition_field === f.name ? ' selected' : '') + '>' + (f.label || f.name) + '</option>';
        }).join('') : '<option value="">-- Tiada medan --</option>';
        row.innerHTML = '<div class="mb-2"><strong>Workflow Rule</strong></div>' +
            '<div class="row g-2 mb-2">' +
            '<div class="col-md-6"><label class="form-label small">Trigger</label><select class="form-select form-select-sm workflow-trigger"><option value="created"' + (data.trigger === 'created' ? ' selected' : '') + '>Apabila Ditambah</option><option value="updated"' + (data.trigger === 'updated' ? ' selected' : '') + '>Apabila Dikemaskini</option></select></div>' +
            '<div class="col-md-6"><label class="form-label small">Condition Field</label><select class="form-select form-select-sm workflow-condition-field">' + fieldOpts + '</select></div>' +
            '</div>' +
            '<div class="row g-2 mb-2">' +
            '<div class="col-md-4"><label class="form-label small">Operator</label><select class="form-select form-select-sm workflow-operator"><option value="=="' + (data.condition_operator === '==' ? ' selected' : '') + '>Sama dengan (==)</option><option value="!="' + (data.condition_operator === '!=' ? ' selected' : '') + '>Tidak sama (!=)</option><option value=">"' + (data.condition_operator === '>' ? ' selected' : '') + '>Lebih besar (&gt;)</option><option value="<"' + (data.condition_operator === '<' ? ' selected' : '') + '>Lebih kecil (&lt;)</option></select></div>' +
            '<div class="col-md-8"><label class="form-label small">Nilai</label><input type="text" class="form-control form-control-sm workflow-condition-value" placeholder="Contoh: Rosak" value="' + (data.condition_value || '').replace(/"/g, '&quot;') + '"></div>' +
            '</div>' +
            '<div class="mb-2"><label class="form-label small">Action: Hantar Emel Kepada</label><input type="email" class="form-control form-control-sm workflow-email" placeholder="admin@example.com" value="' + (data.action_email || '').replace(/"/g, '&quot;') + '"></div>' +
            '<button type="button" class="btn btn-outline-danger btn-sm btn-remove-workflow"><i class="fas fa-times me-1"></i> Buang Workflow</button>';
        document.getElementById('workflowsList').appendChild(row);
        row.querySelector('.btn-remove-workflow').addEventListener('click', function() { row.remove(); });
    }
    function renderWorkflows() {
        var container = document.getElementById('workflowsList');
        var noFields = document.getElementById('workflowNoFields');
        container.innerHTML = '';
        if (wizardFields.length === 0) {
            noFields.style.display = 'block';
            return;
        }
        noFields.style.display = 'none';
        wizardWorkflows.forEach(function(w) { addWorkflowRow(w); });
    }
    document.getElementById('btnAddWorkflow').addEventListener('click', function() {
        if (wizardFields.length === 0) { alert('Tiada medan untuk workflow. Sila pilih Excel Upload atau Bina Manual dalam Langkah 1.'); return; }
        addWorkflowRow({});
    });

    document.getElementById('final_slug').addEventListener('input', function() {
        var v = this.value.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
        document.getElementById('finalSlugPreview').textContent = v || 'slug';
    });

    function collectPages() {
        var pages = [];
        document.querySelectorAll('#pagesList .wizard-row-item').forEach(function(row, i) {
            var nameInp = row.querySelector('input[type="text"]');
            var layoutSel = row.querySelector('select');
            var label = nameInp && nameInp.value ? nameInp.value.trim() : '';
            var layout = layoutSel ? layoutSel.value : 'list';
            if (!label) label = layout === 'list' ? 'Senarai' : (layout === 'card' ? 'Card' : 'Kalendar');
            var id = label.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '') || (layout + '_' + i);
            var type = layout === 'calendar' ? 'calendar' : 'list';
            var icon = layout === 'list' ? 'fas fa-list' : (layout === 'card' ? 'fas fa-th-large' : 'fas fa-calendar-alt');
            var page = { id: id, type: type, label: label, icon: icon };
            if (layout === 'card') page.config = { layout_type: 'card_view' };
            pages.push(page);
        });
        if (pages.length === 0) pages = [{ id: 'list', type: 'list', label: 'Senarai', icon: 'fas fa-list' }, { id: 'form', type: 'form', label: 'Borang', icon: 'fas fa-plus-circle' }];
        return pages;
    }
    function collectWidgets() {
        var cards = [];
        document.querySelectorAll('#dashboardWidgetsList .wizard-row-item').forEach(function(row) {
            var titleInp = row.querySelector('input[type="text"]');
            var selects = row.querySelectorAll('select');
            var title = titleInp && titleInp.value ? titleInp.value.trim() : '';
            if (selects.length >= 2 && selects[0].value && selects[1].value) {
                cards.push({ title: title || selects[0].options[selects[0].selectedIndex].text, field: selects[0].value, aggregation: selects[1].value });
            }
        });
        return cards;
    }
    function collectWorkflows() {
        var workflows = [];
        document.querySelectorAll('#workflowsList .wizard-row-item').forEach(function(row) {
            var trigger = row.querySelector('.workflow-trigger').value;
            var condField = row.querySelector('.workflow-condition-field').value;
            var condOp = row.querySelector('.workflow-operator').value;
            var condVal = row.querySelector('.workflow-condition-value').value.trim();
            var email = row.querySelector('.workflow-email').value.trim();
            if (trigger && condField && condOp && email) {
                workflows.push({
                    trigger: trigger,
                    condition_field: condField,
                    condition_operator: condOp,
                    condition_value: condVal,
                    action_email: email
                });
            }
        });
        return workflows;
    }
    function buildMetadata() {
        var pages = collectPages();
        var widgets = collectWidgets();
        var workflows = collectWorkflows();
        return {
            fields: wizardFields,
            pages: pages,
            layout_type: (pages[0] && pages[0].type === 'list') ? 'simple_list' : (pages[0] && pages[0].type === 'card' ? 'card_view' : 'calendar'),
            settings: {
                enable_dashboard: widgets.length > 0,
                enable_search: true,
                enable_export_excel: true,
                enable_edit_delete: true
            },
            dashboard_cards: widgets,
            workflows: workflows
        };
    }

    document.getElementById('btnPublish').addEventListener('click', function() {
        var nama = document.getElementById('app_name_step1').value.trim();
        var slug = document.getElementById('app_slug_step1').value.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
        var kategori = document.getElementById('app_category_step1').value;
        if (!nama) { alert('Sila isi Nama App.'); return; }
        if (!slug) { alert('Sila isi Slug URL.'); return; }
        if (!kategori) { alert('Sila pilih Kategori.'); return; }
        
        // Validate fields - jika tiada fields dan bukan blank form, show error
        var source = document.querySelector('input[name="wizard_source"]:checked').value;
        if (wizardFields.length === 0 && source !== 'blank') {
            alert('Tiada medan dijumpai. Sila pastikan Excel telah diupload atau fields manual telah ditambah.');
            return;
        }
        
        var meta = buildMetadata();
        var fd = new FormData();
        fd.append('action', 'publish');
        fd.append('csrf_token', document.querySelector('#wizardForm input[name="csrf_token"]').value);
        fd.append('nama_aplikasi', nama);
        fd.append('url_slug', slug);
        fd.append('id_kategori', kategori);
        fd.append('metadata_json', JSON.stringify(meta));
        fd.append('had_excel', hadExcelUpload ? '1' : '0');
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menerbitkan...';
        fetch('wizard.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Berjaya', text: data.message }).then(function() {
                            window.location.href = data.url || ('apps/' + encodeURIComponent(data.slug));
                        });
                    } else {
                        alert(data.message);
                        window.location.href = data.url || ('apps/' + encodeURIComponent(data.slug));
                    }
                } else {
                    alert(data.message || 'Gagal menerbitkan.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Deploy Application';
                }
            })
            .catch(function() {
                alert('Ralat rangkaian.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Deploy Application';
            });
    });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
