<?php
/**
 * Modular Enterprise Engine
 *
 * Pustaka standard industri (scaling, tiada logik custom berat):
 * - DataTables.js: Sort, Search, Pagination, Filter (List View)
 * - FullCalendar.js: Calendar View (tempahan bilik, jadual bertugas)
 * - Chart.js: Dashboard/Laporan (report view)
 * - SweetAlert2: Notifikasi simpan/padam yang profesional
 *
 * View Switcher: $_GET['view'] = list|form|calendar|report|edit
 * Form: Modal Bootstrap; CRUD universal; Export: PhpSpreadsheet
 */
require_once __DIR__ . '/db.php';

/**
 * Hantar notifikasi emel (Automation Workflow).
 * Guna PHPMailer jika ada, jika tidak guna mail().
 */
function engine_send_notification_email($to_email, $app_title, $list_url = '') {
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $subject = '[MyApps] Rekod baru: ' . $app_title;
    $message = "Tuan/Puan,\r\n\r\nAda permohonan/rekod baru yang telah disimpan dalam aplikasi:\r\n" . $app_title . "\r\n\r\n";
    if ($list_url !== '') {
        $message .= "Sila layari pautan berikut untuk melihat senarai:\r\n" . $list_url . "\r\n\r\n";
    }
    $message .= "Ini adalah notifikasi automatik.\r\n";
    $from_name = 'MyApps KEDA';
    $headers = [
        'From: ' . $from_name . ' <noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>',
        'Reply-To: ' . $to_email,
        'X-Mailer: PHP/' . PHP_VERSION,
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), $from_name);
            $mail->addAddress($to_email);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->isHTML(false);
            return $mail->send();
        } catch (Exception $e) {
            return @mail($to_email, $subject, $message, implode("\r\n", $headers));
        }
    }
    return @mail($to_email, $subject, $message, implode("\r\n", $headers));
}

$current_user_id = (int) ($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
$app_slug = trim($_GET['app_slug'] ?? $_POST['app_slug'] ?? '');
$is_ajax_fragment = (isset($_GET['ajax']) && $_GET['ajax'] === '1');
$view = trim($_GET['view'] ?? '');
$page_param = trim($_GET['page'] ?? '');
$data_id = (int) ($_GET['id'] ?? $_POST['record_id'] ?? 0);
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');
$form_success = null;
$form_error = null;
$list_success = null;

// PK custom_apps
$custom_app_pk = 'id';
try {
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_apps' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_app_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

// PK custom_app_data (nama kolum PK berbeza mengikut skema, e.g. id / id_data)
$custom_data_pk = 'id';
try {
    $pkDataStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_app_data' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkDataStmt && ($row = $pkDataStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_data_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

// Muat app + metadata
$app_row = null;
$id_custom = 0;
$form_fields = [];
$app_title = '';
$app_settings = [];
$app_modules = [];
if ($app_slug !== '') {
    try {
        $st = $pdo->prepare("SELECT * FROM custom_apps WHERE app_slug = ? LIMIT 1");
        $st->execute([$app_slug]);
        $app_row = $st->fetch(PDO::FETCH_ASSOC);
        if ($app_row) {
            $id_custom = (int) ($app_row[$custom_app_pk] ?? 0);
            $app_title = $app_row['app_name'] ?? $app_row['nama_aplikasi'] ?? $app_row['name'] ?? $app_row['nama'] ?? $app_row['title'] ?? $app_row['app_slug'] ?? $app_slug;
            $meta_raw = $app_row['metadata'] ?? $app_row['meta'] ?? $app_row['form_config'] ?? $app_row['config'] ?? null;
            if (!empty($meta_raw)) {
                $decoded = json_decode($meta_raw, true);
                if (is_array($decoded)) {
                    $form_fields = $decoded['fields'] ?? (isset($decoded[0]['name']) ? $decoded : []);
                    $app_settings = $decoded['settings'] ?? [];
                    $app_modules = $decoded['modules'] ?? [];
                    $dashboard_cards_meta = $decoded['dashboard_cards'] ?? [];
                    $layout_type_raw = $decoded['layout_type'] ?? 'simple_list';
                    $layout_type = strtolower(trim((string) $layout_type_raw));
                    if ($layout_type === 'card') {
                        $layout_type = 'card_view';
                    }
                }
            }
            if (!isset($layout_type)) {
                $layout_type = 'simple_list';
            }
        }
    } catch (PDOException $e) { /* ignore */ }
}
if (!isset($dashboard_cards_meta)) {
    $dashboard_cards_meta = [];
}
if (!isset($layout_type)) {
    $layout_type = 'simple_list';
}

// Multi-Page Application: senarai halaman dari metadata (atau default)
$app_pages = [];
if (!empty($meta_raw)) {
    $decoded_pages = json_decode($meta_raw, true);
    if (is_array($decoded_pages) && !empty($decoded_pages['pages']) && is_array($decoded_pages['pages'])) {
        $app_pages = $decoded_pages['pages'];
    }
}
if (empty($app_pages)) {
    $app_pages = [
        ['id' => 'list', 'type' => 'list', 'label' => 'Senarai', 'icon' => 'fas fa-list'],
        ['id' => 'form', 'type' => 'form', 'label' => 'Tambah Rekod', 'icon' => 'fas fa-plus-circle'],
    ];
    if (!empty($app_settings['enable_dashboard'])) {
        $app_pages[] = ['id' => 'dashboard', 'type' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-chart-pie'];
    }
    if (!empty($app_modules['calendar']['enabled']) || $layout_type === 'calendar') {
        $app_pages[] = ['id' => 'calendar', 'type' => 'calendar', 'label' => 'Kalendar', 'icon' => 'fas fa-calendar-alt'];
    }
}

// Enterprise Multi-View: $_GET['view'] menentukan halaman bila disediakan (list|calendar|dashboard|form)
$page_id = ($view !== '') ? $view : (($page_param !== '') ? $page_param : 'list');
$current_page = null;
foreach ($app_pages as $p) {
    $pid = $p['id'] ?? $p['type'] ?? '';
    if ((string) $pid === (string) $page_id) {
        $current_page = $p;
        break;
    }
}
if (!$current_page) {
    $current_page = isset($app_pages[0]) ? $app_pages[0] : ['id' => 'list', 'type' => 'list', 'label' => 'Senarai', 'icon' => 'fas fa-list'];
    $page_id = $current_page['id'] ?? $current_page['type'] ?? 'list';
}
$page_type = $current_page['type'] ?? 'list';
$view = ($page_type === 'dashboard') ? 'report' : $page_type;
if ($view === '') {
    $view = 'list';
}

// Rekod untuk edit (pre-fill) – muat awal supaya ref_initial boleh guna (page=form&id= / view=edit&id=)
$edit_record = null;
if (($view === 'edit' || $view === 'form') && $data_id > 0 && $id_custom > 0 && $app_row) {
    try {
        $rowEdit = $pdo->prepare("SELECT id, payload FROM custom_app_data WHERE id = ? AND id_custom = ? LIMIT 1");
        $rowEdit->execute([$data_id, $id_custom]);
        $r = $rowEdit->fetch(PDO::FETCH_ASSOC);
        if ($r && !empty($r['payload'])) {
            $edit_record = json_decode($r['payload'], true);
            if (!is_array($edit_record)) $edit_record = null;
        }
    } catch (PDOException $e) { /* ignore */ }
}

$lookup_options = [];
$ref_fields_config = []; // name => [ 'app_id' => int, 'display_field' => string ]
$ref_initial = [];       // name => [ 'id' => string, 'text' => string ] for pre-selected value
if (!empty($form_fields)) {
    foreach ($form_fields as $ff) {
        $t = $ff['type'] ?? 'text';
        $fname = $ff['name'] ?? $ff['key'] ?? '';
        $srcId = (int) ($ff['lookup_app_id'] ?? 0);
        $srcField = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($ff['lookup_field'] ?? ''));
        if ($srcField === '') $srcField = 'id';
        if ($t === 'ref' && $fname && $srcId > 0) {
            $ref_fields_config[$fname] = ['app_id' => $srcId, 'display_field' => $srcField];
            $current_val = (($view === 'edit' || $view === 'form') && is_array($edit_record) && array_key_exists($fname, $edit_record))
                ? $edit_record[$fname] : ($_POST[$fname] ?? null);
            if ($current_val !== null && $current_val !== '') {
                try {
                    $stRef = $pdo->prepare("SELECT `{$custom_data_pk}` AS id, payload FROM custom_app_data WHERE `{$custom_data_pk}` = ? AND id_custom = ? LIMIT 1");
                    $stRef->execute([$current_val, $srcId]);
                    $rowRef = $stRef->fetch(PDO::FETCH_ASSOC);
                    if ($rowRef) {
                        $pl = !empty($rowRef['payload']) ? (json_decode($rowRef['payload'], true) ?: []) : [];
                        $text = $pl[$srcField] ?? ('Rekod #' . $rowRef['id']);
                        if (is_array($text)) $text = implode(', ', $text);
                        $ref_initial[$fname] = ['id' => (string) $rowRef['id'], 'text' => (string) $text];
                    }
                } catch (PDOException $e) { /* ignore */ }
            }
            continue;
        }
        if ($t !== 'lookup') continue;
        if (!$fname || $srcId <= 0 || ($ff['lookup_field'] ?? '') === '') continue;
        try {
            $stLU = $pdo->prepare("SELECT id, payload FROM custom_app_data WHERE id_custom = ? ORDER BY id DESC");
            $stLU->execute([$srcId]);
            $opts = [];
            while ($r = $stLU->fetch(PDO::FETCH_ASSOC)) {
                $p = [];
                if (!empty($r['payload'])) {
                    $p = json_decode($r['payload'], true);
                    if (!is_array($p)) $p = [];
                }
                $label = $p[$srcField] ?? ('Rekod #' . $r['id']);
                if (is_array($label)) $label = implode(', ', $label);
                $opts[] = [
                    'value' => (string) $r['id'],
                    'label' => (string) $label
                ];
            }
            $lookup_options[$fname] = $opts;
        } catch (PDOException $e) {
            // abaikan ralat lookup
        }
    }
}
$has_ref_fields = count($ref_fields_config) > 0;
$engine_ref_data_url = (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false) ? '/myapps/engine_ref_data.php' : 'engine_ref_data.php';
$engine_lookup_details_url = (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false) ? '/myapps/get_lookup_details.php' : 'get_lookup_details.php';
$use_list_table = ($layout_type === 'simple_list');
$use_card_view = ($layout_type === 'card_view');
$use_calendar_layout = ($layout_type === 'calendar');
$calendar_enabled = !empty($app_modules['calendar']['enabled']) || $use_calendar_layout;

$enable_export_excel = !empty($app_settings['enable_export_excel']);
$enable_edit_delete = !empty($app_settings['enable_edit_delete']);
$enable_dashboard = !empty($app_settings['enable_dashboard']);
$calendar_start = $app_modules['calendar']['startField'] ?? null;
$calendar_end = $app_modules['calendar']['endField'] ?? null;
$calendar_title = $app_modules['calendar']['titleField'] ?? null;
// Override dari konfigurasi per-halaman (metadata.pages[].config)
if (!empty($current_page['config']) && is_array($current_page['config'])) {
    $pc = $current_page['config'];
    if ($page_type === 'list' && !empty($pc['layout_type'])) {
        $layout_type = $pc['layout_type'];
    }
    if ($page_type === 'calendar') {
        if (isset($pc['startField'])) $calendar_start = $pc['startField'];
        if (isset($pc['endField'])) $calendar_end = $pc['endField'];
        if (isset($pc['titleField'])) $calendar_title = $pc['titleField'];
    }
}
// Force Card View for Senarai tab (list view)
if ($page_type === 'list') {
    $layout_type = 'card_view';
}
$use_list_table = ($layout_type === 'simple_list');
$use_card_view = ($layout_type === 'card_view');
$use_calendar_layout = ($layout_type === 'calendar');
$calendar_enabled = !empty($app_modules['calendar']['enabled']) || $use_calendar_layout;

// Filter Sidebar: field yang type select atau radio
$filter_fields = array_filter($form_fields, function ($f) {
    $t = $f['type'] ?? 'text';
    return $t === 'select' || $t === 'radio';
});

// Public Access (id_kategori = 2 Luaran)
$id_kategori = (int) ($app_row['id_kategori'] ?? 0);
$is_public = ($id_kategori === 2);
$is_post_create = ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'update' && $action !== 'delete' && $app_slug !== '');
$allow_public_form = $is_public && ($view === 'form' || $is_post_create);
if (!isset($_SESSION['user_id']) && !$allow_public_form) {
    header('Location: index.php');
    exit;
}

$engine_base = (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false)
    ? '/apps/' . urlencode($app_slug) : '/myapps/engine.php?app_slug=' . urlencode($app_slug);
$sep = (strpos($engine_base, '?') !== false) ? '&' : '?';

// --- CRUD: Delete (universal)
if ($action === 'delete' && $data_id > 0 && $id_custom > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $pdo->prepare("DELETE FROM custom_app_data WHERE `{$custom_data_pk}` = ? AND id_custom = ?")->execute([$data_id, $id_custom]);
        header('Location: ' . $engine_base . $sep . 'page=list&deleted=1');
        exit;
    } catch (Exception $e) {
        $form_error = 'Gagal memadam: ' . htmlspecialchars($e->getMessage());
    }
}
if (!empty($_GET['deleted'])) $list_success = 'Rekod telah dipadam.';
if (!empty($_GET['saved'])) $list_success = 'Rekod berjaya disimpan.';
if (!empty($_GET['updated'])) $list_success = 'Rekod berjaya dikemas kini.';
if (!empty($_GET['imported'])) $list_success = 'Aplikasi diimport. Data telah dipindahkan.';

// --- CRUD: Update (universal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update' && $data_id > 0 && $id_custom > 0) {
    try {
        verifyCsrfToken();
        $post_data = $_POST;
        unset($post_data['submit'], $post_data['csrf_token'], $post_data['app_slug'], $post_data['action'], $post_data['record_id']);
        $payload_json = json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pdo->prepare("UPDATE custom_app_data SET payload = ?, created_at = NOW() WHERE id = ? AND id_custom = ?")
            ->execute([$payload_json, $data_id, $id_custom]);
        
        // Execute workflows for 'updated' trigger
        if (file_exists(__DIR__ . '/workflow_processor.php')) {
            require_once __DIR__ . '/workflow_processor.php';
            process_workflows($pdo, $id_custom, $data_id, 'updated', $post_data);
        }
        
        header('Location: ' . $engine_base . $sep . 'page=list&updated=1');
        exit;
    } catch (Exception $e) {
        $form_error = htmlspecialchars($e->getMessage());
    }
}

// --- Create (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app_slug !== '' && $action !== 'update') {
    try {
        verifyCsrfToken();
        $post_data = $_POST;
        unset($post_data['submit'], $post_data['csrf_token'], $post_data['app_slug']);
        $payload_json = json_encode($post_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $created_by = (int) ($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
        if ($created_by === 0 && !$is_public) {
            throw new Exception('Sesi pengguna tidak sah. Sila login semula.');
        }
        $pdo->prepare("INSERT INTO custom_app_data (id_custom, created_by, payload, created_at) VALUES (?, ?, ?, NOW())")
            ->execute([$id_custom, $created_by, $payload_json]);
        
        $new_record_id = (int) $pdo->lastInsertId();
        
        // Execute workflows for 'created' trigger
        if ($new_record_id > 0 && file_exists(__DIR__ . '/workflow_processor.php')) {
            require_once __DIR__ . '/workflow_processor.php';
            process_workflows($pdo, $id_custom, $new_record_id, 'created', $post_data);
        }
        
        // Automation Workflow: notifikasi emel kepada pegawai jika dikonfigurasi (legacy)
        $notification_email = isset($app_settings['notification_email']) ? trim((string) $app_settings['notification_email']) : '';
        if ($notification_email !== '') {
            $list_url = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['SCRIPT_NAME'] ?? '/myapps/engine.php') . '?app_slug=' . urlencode($app_slug) . '&view=list';
            engine_send_notification_email($notification_email, $app_title, $list_url);
        }
        $redirect_page = ($is_public && $created_by === 0) ? 'page=form' : 'page=list';
        header('Location: ' . $engine_base . $sep . $redirect_page . '&saved=1');
        exit;
    } catch (Exception $e) {
        $form_error = htmlspecialchars($e->getMessage());
    }
}

// Data untuk list / report / calendar
$list_data = [];
if (in_array($view, ['list', 'report', 'calendar'], true) && $id_custom > 0) {
    try {
        $stList = $pdo->prepare("SELECT `{$custom_data_pk}` AS id, payload, created_at, created_by FROM custom_app_data WHERE id_custom = ? ORDER BY `{$custom_data_pk}` DESC");
        $stList->execute([$id_custom]);
        $list_data = $stList->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $form_error = 'Ralat memuat senarai: ' . htmlspecialchars($e->getMessage());
    }
}

// Auto-derive form_fields dari payload keys apabila metadata tiada susunan field yang spesifik (aplikasi hasil import Excel)
// Gunakan kunci dari rekod pertama sebagai susunan tajuk kolum; tambah kunci dari rekod lain yang tiada dalam rekod pertama
if (empty($form_fields) && !empty($list_data)) {
    $order_from_first = [];
    $all_keys = [];
    $first_done = false;
    foreach ($list_data as $row) {
        $pl = [];
        if (!empty($row['payload'])) {
            $pl = json_decode($row['payload'], true);
            if (!is_array($pl)) $pl = [];
        }
        $keys = array_keys($pl);
        if (!$first_done && !empty($keys)) {
            $order_from_first = $keys;
            $first_done = true;
        }
        foreach ($keys as $k) {
            $all_keys[$k] = true;
        }
    }
    $ordered = $order_from_first;
    foreach (array_keys($all_keys) as $k) {
        if (!in_array($k, $ordered, true)) {
            $ordered[] = $k;
        }
    }
    foreach ($ordered as $key) {
        $form_fields[] = [
            'name'   => $key,
            'label'  => ucfirst(str_replace(['_', '-'], ' ', $key)),
            'type'   => 'text',
            'key'    => $key
        ];
    }
}

$allow_public_engine_form = $allow_public_form;
if ($is_ajax_fragment && ($app_slug === '' || !$app_row)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<div class="alert alert-danger m-3">Aplikasi tidak dijumpai.</div>';
    exit;
}
if (!$is_ajax_fragment) {
    require_once __DIR__ . '/header.php';
}

$form_action = (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false) ? '/apps/' . urlencode($app_slug) : '/myapps/engine.php?app_slug=' . urlencode($app_slug);
$fieldVal = function ($name) use ($edit_record, $view) {
    if ($view === 'edit' && is_array($edit_record) && array_key_exists($name, $edit_record)) {
        $v = $edit_record[$name];
        return is_array($v) ? $v : (string) $v;
    }
    return $_POST[$name] ?? '';
};
$fieldChecked = function ($name, $ov) use ($fieldVal) {
    $v = $fieldVal($name);
    if (is_array($v)) return in_array($ov, $v);
    return $v === (string) $ov;
};
?>

<div class="container-fluid">
    <?php if ($app_slug === ''): ?>
        <div class="alert alert-warning shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            Sila nyatakan <code>app_slug</code> dalam URL.
        </div>
    <?php elseif (!$app_row): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Aplikasi &quot;<?php echo htmlspecialchars($app_slug); ?>&quot; tidak dijumpai.
        </div>
    <?php elseif (empty($form_fields)): ?>
        <div class="alert alert-info shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            Tiada metadata borang. Konfigurasikan metadata dalam jadual <code>custom_apps</code>.
        </div>
    <?php else: ?>
        <!-- AppSheet-style Mobile App: CSS -->
        <style>
        .engine-appsheet { max-width: 480px; margin: 0 auto; min-height: 100vh; padding-bottom: 100px; background: #f5f6fa; }
        .engine-appsheet .card, .engine-appsheet .alert { border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,.08); border: none; }
        .engine-appsheet .btn { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .engine-appsheet .form-control, .engine-appsheet .form-select { border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .engine-appsheet .modal-content { border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,.2); border: none; }
        .engine-appsheet .modal-header { border-radius: 20px 20px 0 0; }
        .engine-appsheet .list-card-item { display: flex; align-items: center; gap: 14px; padding: 14px 16px; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.06); margin-bottom: 10px; border: none; text-align: left; width: 100%; }
        .engine-appsheet .list-card-item .list-card-avatar { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .engine-appsheet .list-card-item .list-card-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; box-shadow: 0 2px 8px rgba(74,144,226,.35); }
        .engine-appsheet .list-card-item .list-card-body { flex: 1; min-width: 0; }
        .engine-appsheet .list-card-item .list-card-title { font-weight: 600; color: #1a1a2e; margin-bottom: 2px; font-size: 0.95rem; }
        .engine-appsheet .list-card-item .list-card-meta { font-size: 0.8rem; color: #6c757d; }
        .engine-appsheet .engine-bottom-nav { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); max-width: 480px; width: 100%; background: #fff; border-radius: 24px 24px 0 0; box-shadow: 0 -4px 24px rgba(0,0,0,.12); padding: 8px 12px 12px; padding-bottom: max(12px, env(safe-area-inset-bottom)); z-index: 1020; display: flex; justify-content: space-around; align-items: center; }
        .engine-appsheet .engine-bottom-nav a { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px 16px; color: #6c757d; text-decoration: none; font-size: 0.7rem; border-radius: 12px; transition: all .2s; min-width: 72px; }
        .engine-appsheet .engine-bottom-nav a:hover { color: #4a90e2; background: rgba(74,144,226,.08); }
        .engine-appsheet .engine-bottom-nav a.active { color: #4a90e2; font-weight: 600; }
        .engine-appsheet .engine-bottom-nav a i { font-size: 1.25rem; margin-bottom: 4px; }
        .engine-appsheet .engine-fab { position: fixed; bottom:  calc(56px + max(12px, env(safe-area-inset-bottom))); right: 50%; margin-right: -240px; width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); color: #fff; border: none; box-shadow: 0 6px 20px rgba(74,144,226,.5); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; z-index: 1019; transition: transform .2s, box-shadow .2s; }
        .engine-appsheet .engine-fab:hover { color: #fff; transform: scale(1.05); box-shadow: 0 8px 24px rgba(74,144,226,.55); }
        .engine-appsheet .engine-fab:focus { color: #fff; }
        @media (max-width: 520px) { .engine-appsheet .engine-fab { right: 20px; margin-right: 0; } }
        .engine-appsheet .engine-app-title { font-size: 1.1rem; font-weight: 700; color: #1a1a2e; padding: 12px 0; margin-bottom: 8px; }
        </style>
        <div class="engine-appsheet">
        <div class="engine-app-title"><i class="fas fa-cube me-2 text-primary"></i><?php echo htmlspecialchars($app_title); ?></div>
        <!-- Bottom Navigation Bar (Multi-Page: dari metadata.pages) -->
        <nav class="engine-bottom-nav">
            <?php foreach ($app_pages as $p): $pid = $p['id'] ?? $p['type'] ?? ''; $plabel = $p['label'] ?? $pid; $picon = $p['icon'] ?? 'fas fa-circle'; $active = ((string) $pid === (string) $page_id); ?>
            <a href="<?php echo htmlspecialchars($engine_base . $sep . 'page=' . urlencode($pid)); ?>" class="engine-nav-link <?php echo $active ? 'active' : ''; ?>" data-page="<?php echo htmlspecialchars($pid); ?>"><i class="<?php echo htmlspecialchars($picon); ?>"></i> <?php echo htmlspecialchars($plabel); ?></a>
            <?php endforeach; ?>
        </nav>
        <!-- Floating Action Button: Tambah Data (sembunyi pada Form View) -->
        <?php if ($page_type !== 'form'): ?>
        <button type="button" class="engine-fab" data-bs-toggle="modal" data-bs-target="#engineFormModal" data-engine-mode="add" title="Tambah Data"><i class="fas fa-plus"></i></button>
        <?php endif; ?>

        <!-- Pustaka standard: SweetAlert2 untuk notifikasi profesional -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <?php ob_start(); ?>
        <?php if ($view === 'list'): ?>
        <?php if ($use_calendar_layout): ?>
        <!-- List View: Layout = Calendar (FullCalendar.js) -->
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kalendar</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#engineFormModal" data-engine-mode="add"><i class="fas fa-plus me-1"></i> Tambah Rekod Baru</button>
                    <?php if ($enable_export_excel): ?>
                    <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false) ? '/myapps/engine_export_excel.php' : 'engine_export_excel.php'; ?>?app_slug=<?php echo urlencode($app_slug); ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i> Eksport ke Excel</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div id="engineCalendarList"></div>
            </div>
        </div>
        <?php
        $startF = $calendar_start;
        $endF = $calendar_end;
        if (!$startF && count($form_fields) > 0) {
            foreach ($form_fields as $f) { if (in_array($f['type'] ?? '', ['date', 'datetime-local'], true)) { $startF = $f['name'] ?? $f['key'] ?? ''; break; } }
        }
        $titleF = $calendar_title;
        if (!$titleF && count($form_fields) > 0) { $titleF = $form_fields[0]['name'] ?? $form_fields[0]['key'] ?? ''; }
        $cal_events = [];
        foreach ($list_data as $row) {
            $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
            $start = $startF ? ($pl[$startF] ?? $row['created_at'] ?? '') : ($row['created_at'] ?? '');
            $end = $endF ? ($pl[$endF] ?? null) : null;
            $title = $titleF ? ($pl[$titleF] ?? 'Rekod #' . $row['id']) : ('Rekod #' . $row['id']);
            if ($start) { $cal_events[] = ['id' => $row['id'], 'title' => $title, 'start' => $start, 'end' => $end ?: $start]; }
        }
        ?>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/ms.global.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calEl = document.getElementById('engineCalendarList');
            if (calEl && typeof FullCalendar !== 'undefined') {
                var events = <?php echo json_encode($cal_events); ?>;
                new FullCalendar.Calendar(calEl, { initialView: 'dayGridMonth', locale: 'ms', events: events, headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' } }).render();
            }
        });
        </script>
        <?php elseif ($use_card_view): ?>
        <!-- List View: Layout = Card (senarai kad dengan ikon/imej kiri, gaya AppSheet) -->
        <div class="row g-3">
            <?php if (count($filter_fields) > 0): ?>
            <div class="col-md-3 col-lg-2">
                <div class="card shadow-sm sticky-top" style="top: 1rem;">
                    <div class="card-header bg-light py-2"><h6 class="mb-0"><i class="fas fa-filter me-1"></i> Penapis</h6></div>
                    <div class="card-body py-2">
                        <?php foreach ($filter_fields as $ff):
                            $fn = $ff['name'] ?? $ff['key'] ?? ''; $fl = $ff['label'] ?? $fn; $fopts = $ff['options'] ?? []; $ft = $ff['type'] ?? 'select';
                            if (empty($fn)) continue; $fid = 'filter_card_' . preg_replace('/[^a-z0-9_]/i', '_', $fn);
                        ?>
                        <div class="mb-3">
                            <label for="<?php echo $fid; ?>" class="form-label small"><?php echo htmlspecialchars($fl); ?></label>
                            <select id="<?php echo $fid; ?>" class="form-select form-select-sm engine-card-filter" data-column="<?php echo htmlspecialchars($fn); ?>">
                                <option value="">-- Semua --</option>
                                <?php foreach ((array) $fopts as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; ?>
                                <option value="<?php echo htmlspecialchars($ov); ?>"><?php echo htmlspecialchars($ol); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="engineCardFilterReset">Set Semula</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?php echo count($filter_fields) > 0 ? 'col-md-9 col-lg-10' : 'col-12'; ?>">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($form_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2"><?php echo $form_error; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>
                        <?php if ($enable_export_excel): ?>
                        <div class="d-flex justify-content-end mb-2">
                            <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false) ? '/myapps/engine_export_excel.php' : 'engine_export_excel.php'; ?>?app_slug=<?php echo urlencode($app_slug); ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i> Eksport Excel</a>
                        </div>
                        <?php endif; ?>
                        <div id="engineCardGrid">
                            <?php
                            $image_keys = ['image', 'foto', 'img', 'picture', 'gambar', 'photo', 'url'];
                            $first_field_name = $form_fields[0]['name'] ?? $form_fields[0]['key'] ?? '';
                            foreach ($list_data as $row):
                                $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
                                $row_id = (int) $row['id'];
                                $created_at = $row['created_at'] ?? '';
                                $payload_esc = htmlspecialchars(json_encode($pl, JSON_UNESCAPED_UNICODE));
                                $card_image_url = null;
                                foreach ($image_keys as $ik) {
                                    if (!empty($pl[$ik]) && is_string($pl[$ik]) && (preg_match('#^https?://#i', $pl[$ik]) || preg_match('#^/?#', $pl[$ik]))) {
                                        $card_image_url = $pl[$ik];
                                        break;
                                    }
                                }
                                $title_val = $first_field_name ? ($pl[$first_field_name] ?? 'Rekod #' . $row_id) : ('Rekod #' . $row_id);
                                if (is_array($title_val)) $title_val = implode(', ', $title_val);
                                $meta_parts = [];
                                foreach (array_slice($form_fields, 1, 3) as $f) {
                                    $nm = $f['name'] ?? $f['key'] ?? '';
                                    $v = $pl[$nm] ?? null;
                                    if ($v !== null && $v !== '') $meta_parts[] = (is_array($v) ? implode(', ', $v) : (string) $v);
                                }
                                $meta_str = implode(' · ', $meta_parts);
                            ?>
                            <div class="engine-card-col engine-list-card-wrapper" data-id="<?php echo $row_id; ?>" data-payload="<?php echo $payload_esc; ?>">
                                <div class="list-card-item">
                                    <?php if ($card_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($card_image_url); ?>" class="list-card-avatar" alt="">
                                    <?php else: ?>
                                    <div class="list-card-icon"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                    <div class="list-card-body">
                                        <div class="list-card-title"><?php echo htmlspecialchars($title_val); ?></div>
                                        <?php if ($meta_str !== ''): ?>
                                        <div class="list-card-meta"><?php echo htmlspecialchars($meta_str); ?></div>
                                        <?php endif; ?>
                                        <div class="list-card-meta"><?php echo htmlspecialchars($created_at); ?></div>
                                    </div>
                                    <?php if ($enable_edit_delete): ?>
                                    <div class="d-flex flex-shrink-0 gap-1">
                                        <a href="<?php echo htmlspecialchars($engine_base . $sep . 'page=form&id=' . (int)$row_id); ?>" class="btn btn-outline-primary btn-sm" title="Kemaskini"><i class="fas fa-edit"></i></a>
                                        <form method="post" action="<?php echo htmlspecialchars($engine_base . $sep . 'action=delete&id=' . $row_id); ?>" class="d-inline engine-delete-form">
                                            <?php echo getCsrfTokenField(); ?>
                                            <input type="hidden" name="app_slug" value="<?php echo htmlspecialchars($app_slug); ?>">
                                            <button type="button" class="btn btn-outline-danger btn-sm engine-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var params = new URLSearchParams(window.location.search);
            if (params.has('saved') && params.get('saved') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rekod berjaya disimpan.', timer: 3000, showConfirmButton: false }); params.delete('saved'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            if (params.has('updated') && params.get('updated') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rekod berjaya dikemas kini.', timer: 3000, showConfirmButton: false }); params.delete('updated'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            if (params.has('deleted') && params.get('deleted') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rekod telah dipadam.', timer: 3000, showConfirmButton: false }); params.delete('deleted'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            if (params.has('imported') && params.get('imported') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Aplikasi diimport. Data telah dipindahkan.', timer: 4000, showConfirmButton: false }); params.delete('imported'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            document.getElementById('engineCardGrid') && document.getElementById('engineCardGrid').addEventListener('click', function(e) {
                var btn = e.target.closest('.engine-btn-delete');
                if (btn) {
                    var form = btn.closest('form');
                    if (typeof Swal !== 'undefined') { Swal.fire({ title: 'Hapus rekod?', text: 'Tindakan ini tidak boleh dibatalkan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Batal', confirmButtonText: 'Ya, hapus' }).then(function(r) { if (r.isConfirmed) form.submit(); }); } else { if (confirm('Hapus rekod ini?')) form.submit(); }
                    return;
                }
                btn = e.target.closest('.engine-btn-edit');
                if (btn) {
                    var col = btn.closest('.engine-card-col');
                    var id = col.getAttribute('data-id');
                    var payloadStr = col.getAttribute('data-payload');
                    var payload = {};
                    try { payload = payloadStr ? JSON.parse(payloadStr) : {}; } catch(err) {}
                    var form = document.getElementById('engineFormModalForm');
                    if (form) {
                        form.querySelector('#engineFormAction').value = 'update';
                        form.querySelector('#engineFormRecordId').value = id;
                        form.reset();
                        form.querySelector('#engineFormAction').value = 'update';
                        form.querySelector('#engineFormRecordId').value = id;
                        Object.keys(payload).forEach(function(k) {
                            var el = form.querySelector('[name="' + k + '"]');
                            if (el) { if (el.type === 'checkbox' || el.type === 'radio') el.checked = Array.isArray(payload[k]) ? payload[k].indexOf(el.value) >= 0 : (el.value === String(payload[k])); else el.value = Array.isArray(payload[k]) ? (payload[k][0] || '') : payload[k]; }
                        });
                        <?php if ($has_ref_fields): ?>
                        if (window.engineRefLoadInitialValues) window.engineRefLoadInitialValues(form, payload);
                        <?php endif; ?>
                        typeof bootstrap !== 'undefined' && bootstrap.Modal.getOrCreateInstance(document.getElementById('engineFormModal')).show();
                    }
                }
            });
            document.querySelectorAll('.engine-card-filter').forEach(function(sel) {
                sel.addEventListener('change', function() {
                    var col = this.getAttribute('data-column');
                    var val = this.value;
                    document.querySelectorAll('#engineCardGrid .engine-card-col').forEach(function(card) {
                        var p = {}; try { p = JSON.parse(card.getAttribute('data-payload') || '{}'); } catch(e) {}
                        var match = !val || (p[col] === val || String(p[col]) === val);
                        card.style.display = match ? '' : 'none';
                    });
                });
            });
            var resetBtn = document.getElementById('engineCardFilterReset');
            if (resetBtn) resetBtn.addEventListener('click', function() { document.querySelectorAll('.engine-card-filter').forEach(function(s) { s.value = ''; }); document.querySelectorAll('#engineCardGrid .engine-card-col').forEach(function(c) { c.style.display = ''; }); });
        })();
        </script>
        <?php else: ?>
        <!-- List View: Simple List = DataTables (carian, filter, pagination 20) -->
        <div class="row g-3">
            <?php if (count($filter_fields) > 0): ?>
            <div class="col-md-3 col-lg-2">
                <div class="card shadow-sm sticky-top" style="top: 1rem;">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="fas fa-filter me-1"></i> Penapis</h6>
                    </div>
                    <div class="card-body py-2">
                        <?php foreach ($filter_fields as $ff):
                            $fn = $ff['name'] ?? $ff['key'] ?? '';
                            $fl = $ff['label'] ?? $fn;
                            $fopts = $ff['options'] ?? [];
                            $ft = $ff['type'] ?? 'select';
                            if (empty($fn)) continue;
                            $fid = 'filter_' . preg_replace('/[^a-z0-9_]/i', '_', $fn);
                        ?>
                        <div class="mb-3">
                            <label for="<?php echo $fid; ?>" class="form-label small"><?php echo htmlspecialchars($fl); ?></label>
                            <?php if ($ft === 'radio'): ?>
                            <div class="filter-radio-group">
                                <select id="<?php echo $fid; ?>" class="form-select form-select-sm engine-filter-select" data-column="<?php echo htmlspecialchars($fn); ?>">
                                    <option value="">-- Semua --</option>
                                    <?php foreach ((array) $fopts as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; ?>
                                    <option value="<?php echo htmlspecialchars($ov); ?>"><?php echo htmlspecialchars($ol); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <select id="<?php echo $fid; ?>" class="form-select form-select-sm engine-filter-select" data-column="<?php echo htmlspecialchars($fn); ?>">
                                <option value="">-- Semua --</option>
                                <?php foreach ((array) $fopts as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; ?>
                                <option value="<?php echo htmlspecialchars($ov); ?>"><?php echo htmlspecialchars($ol); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="engineFilterReset">Set Semula</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?php echo count($filter_fields) > 0 ? 'col-md-9 col-lg-10' : 'col-12'; ?>">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($form_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2">
                            <?php echo $form_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        <?php if ($enable_export_excel): ?>
                        <div class="d-flex justify-content-end mb-2">
                            <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/apps/') !== false) ? '/myapps/engine_export_excel.php' : 'engine_export_excel.php'; ?>?app_slug=<?php echo urlencode($app_slug); ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i> Eksport Excel</a>
                        </div>
                        <?php endif; ?>
                        <div class="table-responsive">
                        <table id="engineListTable" class="table table-striped table-hover table-bordered w-100" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach ($form_fields as $f): $fl = $f['label'] ?? $f['name'] ?? $f['key'] ?? ''; ?>
                                    <th><?php echo htmlspecialchars($fl); ?></th>
                                    <?php endforeach; ?>
                                    <th>Tarikh</th>
                                    <?php if ($enable_edit_delete): ?><th class="no-sort text-end">Tindakan</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($list_data as $row):
                                $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
                                $row_id = (int) $row['id'];
                                $created_at = $row['created_at'] ?? '';
                            ?>
                                <tr data-id="<?php echo $row_id; ?>" data-payload="<?php echo htmlspecialchars(json_encode($pl, JSON_UNESCAPED_UNICODE)); ?>">
                                    <?php foreach ($form_fields as $f): $fn = $f['name'] ?? $f['key'] ?? ''; $v = $pl[$fn] ?? ''; if (is_array($v)) $v = implode(', ', $v); ?>
                                    <td><?php echo htmlspecialchars((string)$v); ?></td>
                                    <?php endforeach; ?>
                                    <td><?php echo htmlspecialchars($created_at); ?></td>
                                    <?php if ($enable_edit_delete): ?>
                                    <td class="text-end">
                                        <a href="<?php echo htmlspecialchars($engine_base . $sep . 'page=form&id=' . $row_id . '&view=list'); ?>" class="btn btn-outline-primary btn-sm" title="Kemaskini"><i class="fas fa-edit"></i></a>
                                        <form method="post" action="<?php echo htmlspecialchars($engine_base . $sep . 'action=delete&id=' . $row_id); ?>" class="d-inline engine-delete-form">
                                            <?php echo getCsrfTokenField(); ?>
                                            <input type="hidden" name="app_slug" value="<?php echo htmlspecialchars($app_slug); ?>">
                                            <button type="button" class="btn btn-outline-danger btn-sm engine-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <div id="engineListCards" class="d-none">
                            <?php
                            $image_keys = ['image', 'foto', 'img', 'picture', 'gambar', 'photo', 'url'];
                            $colCount = count($form_fields);
                            $first_field_name = $form_fields[0]['name'] ?? $form_fields[0]['key'] ?? '';
                            foreach ($list_data as $idx => $row):
                                $pl = [];
                                if (!empty($row['payload'])) {
                                    $pl = json_decode($row['payload'], true);
                                    if (!is_array($pl)) $pl = [];
                                }
                                $row_id = (int) $row['id'];
                                $created_at = $row['created_at'] ?? '';
                                $payload_esc = htmlspecialchars(json_encode($pl, JSON_UNESCAPED_UNICODE));
                                $card_image_url = null;
                                foreach ($image_keys as $ik) {
                                    if (!empty($pl[$ik]) && is_string($pl[$ik]) && (preg_match('#^https?://#i', $pl[$ik]) || preg_match('#^/?#', $pl[$ik]))) {
                                        $card_image_url = $pl[$ik];
                                        break;
                                    }
                                }
                                $title_val = $first_field_name ? ($pl[$first_field_name] ?? 'Rekod #' . $row_id) : ('Rekod #' . $row_id);
                                if (is_array($title_val)) $title_val = implode(', ', $title_val);
                                $meta_parts = [];
                                foreach (array_slice($form_fields, 1, 3) as $f) {
                                    $nm = $f['name'] ?? $f['key'] ?? '';
                                    $v = $pl[$nm] ?? null;
                                    if ($v !== null && $v !== '') $meta_parts[] = (is_array($v) ? implode(', ', $v) : (string) $v);
                                }
                                $meta_str = implode(' · ', $meta_parts);
                            ?>
                            <div class="engine-list-card-wrapper" data-id="<?php echo $row_id; ?>" data-payload="<?php echo $payload_esc; ?>">
                                <div class="list-card-item">
                                    <?php if ($card_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($card_image_url); ?>" class="list-card-avatar" alt="">
                                    <?php else: ?>
                                    <div class="list-card-icon"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                    <div class="list-card-body">
                                        <div class="list-card-title"><?php echo htmlspecialchars($title_val); ?></div>
                                        <?php if ($meta_str !== ''): ?>
                                        <div class="list-card-meta"><?php echo htmlspecialchars($meta_str); ?></div>
                                        <?php endif; ?>
                                        <div class="list-card-meta"><?php echo htmlspecialchars($created_at); ?></div>
                                    </div>
                                    <?php if ($enable_edit_delete): ?>
                                    <div class="d-flex flex-shrink-0 gap-1">
                                        <a href="<?php echo htmlspecialchars($engine_base . $sep . 'page=form&id=' . (int)$row_id); ?>" class="btn btn-outline-primary btn-sm" title="Kemaskini"><i class="fas fa-edit"></i></a>
                                        <form method="post" action="<?php echo htmlspecialchars($engine_base . $sep . 'action=delete&id=' . $row_id); ?>" class="d-inline engine-delete-form">
                                            <?php echo getCsrfTokenField(); ?>
                                            <input type="hidden" name="app_slug" value="<?php echo htmlspecialchars($app_slug); ?>">
                                            <button type="button" class="btn btn-outline-danger btn-sm engine-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; // end Simple List (cards) ?>

        <!-- Modal: Borang Tambah/Kemaskini (kongsi untuk Table dan Card view) -->
        <div class="modal fade" id="engineFormModal" tabindex="-1" aria-labelledby="engineFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="engineFormModalLabel"><i class="fas fa-wpforms me-2"></i><?php echo htmlspecialchars($app_title); ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($is_public && $current_user_id === 0): ?>
                        <div class="alert alert-info py-2 small">Aplikasi awam. Anda boleh isi tanpa log masuk. Untuk melihat senarai, sila log masuk.</div>
                        <?php endif; ?>
                        <form id="engineFormModalForm" method="post" action="<?php echo htmlspecialchars($form_action); ?>">
                            <?php echo getCsrfTokenField(); ?>
                            <input type="hidden" name="app_slug" value="<?php echo htmlspecialchars($app_slug); ?>">
                            <input type="hidden" name="action" id="engineFormAction" value="">
                            <input type="hidden" name="record_id" id="engineFormRecordId" value="">
                            <?php
                            foreach ($form_fields as $field):
                                $name = $field['name'] ?? $field['key'] ?? '';
                                $label = $field['label'] ?? $name;
                                $type = $field['type'] ?? 'text';
                                $required = !empty($field['required']);
                                $placeholder = $field['placeholder'] ?? '';
                                $options = $field['options'] ?? [];
                                if (empty($name)) continue;
                                $safe_name = htmlspecialchars($name);
                                $safe_label = htmlspecialchars($label);
                                $val = $fieldVal($name);
                                $valStr = is_array($val) ? '' : htmlspecialchars($val);
                            ?>
                            <div class="mb-3">
                                <label for="modal_<?php echo $safe_name; ?>" class="form-label"><?php echo $safe_label; ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></label>
                                <?php if ($type === 'textarea'): ?>
                                <textarea name="<?php echo $safe_name; ?>" id="modal_<?php echo $safe_name; ?>" class="form-control" rows="<?php echo (int)($field['rows'] ?? 3); ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php if ($required) echo ' required'; ?>><?php echo $valStr; ?></textarea>
                                <?php elseif ($type === 'lookup'): ?>
                                <?php $srcOpts = $lookup_options[$name] ?? []; $lookup_app_id = (int) ($field['lookup_app_id'] ?? 0); ?>
                                <select name="<?php echo $safe_name; ?>" id="modal_<?php echo $safe_name; ?>" class="form-select engine-lookup-select" data-lookup-app-id="<?php echo $lookup_app_id; ?>" <?php if ($required) echo ' required'; ?>>
                                    <option value="">-- Pilih Rekod --</option>
                                    <?php foreach ($srcOpts as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt['value']); ?>" <?php echo ($val !== '' && (string)$val === (string)$opt['value']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['label']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php elseif ($type === 'ref'): ?>
                                <?php $refCfg = $ref_fields_config[$name] ?? null; if ($refCfg): ?>
                                <select name="<?php echo $safe_name; ?>" id="modal_<?php echo $safe_name; ?>" class="form-select engine-ref-select" data-ref-app-id="<?php echo (int) $refCfg['app_id']; ?>" data-ref-field="<?php echo htmlspecialchars($refCfg['display_field']); ?>" <?php if ($required) echo ' required'; ?>>
                                    <option value="">-- Cari rekod dari aplikasi rujukan --</option>
                                    <?php if (isset($ref_initial[$name])): ?>
                                    <option value="<?php echo htmlspecialchars($ref_initial[$name]['id']); ?>" selected><?php echo htmlspecialchars($ref_initial[$name]['text']); ?></option>
                                    <?php endif; ?>
                                </select>
                                <?php endif; ?>
                                <?php elseif ($type === 'select'): ?>
                                <select name="<?php echo $safe_name; ?>" id="modal_<?php echo $safe_name; ?>" class="form-select" <?php if ($required) echo ' required'; ?>>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ((array) $options as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; ?>
                                    <option value="<?php echo htmlspecialchars($ov); ?>" <?php echo $val === (string) $ov ? ' selected' : ''; ?>><?php echo htmlspecialchars($ol); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php elseif ($type === 'checkbox' || $type === 'radio'): $is_radio = ($type === 'radio');
                                    foreach ((array) ($options ?: [['value' => '1', 'label' => $label]]) as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; $id_opt = 'modal_' . $safe_name . '_' . preg_replace('/[^a-z0-9]/i', '_', $ov); ?>
                                    <div class="form-check"><input type="<?php echo $is_radio ? 'radio' : 'checkbox'; ?>" name="<?php echo $safe_name; ?><?php echo $is_radio ? '' : '[]'; ?>" value="<?php echo htmlspecialchars($ov); ?>" id="<?php echo $id_opt; ?>" class="form-check-input" <?php if ($required && $is_radio) echo ' required'; ?> <?php echo $fieldChecked($name, $ov) ? ' checked' : ''; ?>><label class="form-check-label" for="<?php echo $id_opt; ?>"><?php echo htmlspecialchars($ol); ?></label></div>
                                <?php endforeach; ?>
                                <?php else: $input_type = in_array($type, ['email', 'number', 'tel', 'url', 'date', 'datetime-local'], true) ? $type : 'text'; ?>
                                <input type="<?php echo $input_type; ?>" name="<?php echo $safe_name; ?>" id="modal_<?php echo $safe_name; ?>" class="form-control" value="<?php echo $valStr; ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php if ($required) echo ' required'; ?>>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" form="engineFormModalForm" name="submit" value="1" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('engineFormModal') && document.getElementById('engineFormModal').addEventListener('show.bs.modal', function(e) {
            if (e.relatedTarget && e.relatedTarget.getAttribute('data-engine-mode') === 'add') {
                var form = document.getElementById('engineFormModalForm');
                if (form) { var a = form.querySelector('#engineFormAction'); var r = form.querySelector('#engineFormRecordId'); if (a) a.value = ''; if (r) r.value = ''; form.reset(); if (a) a.value = ''; if (r) r.value = ''; }
            }
        });
        </script>
        <?php if ($use_list_table): ?>
        <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.0.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/datatables.net@2.0.8/js/dataTables.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.0.8/js/dataTables.bootstrap5.min.js"></script>
        <script>
        (function() {
            var params = new URLSearchParams(window.location.search);
            if (params.has('saved') && params.get('saved') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rekod berjaya disimpan.', timer: 3000, showConfirmButton: false }); params.delete('saved'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            if (params.has('updated') && params.get('updated') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rekod berjaya dikemas kini.', timer: 3000, showConfirmButton: false }); params.delete('updated'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            if (params.has('deleted') && params.get('deleted') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rekod telah dipadam.', timer: 3000, showConfirmButton: false }); params.delete('deleted'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }
            if (params.has('imported') && params.get('imported') === '1' && typeof Swal !== 'undefined') { Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Aplikasi diimport. Data telah dipindahkan.', timer: 4000, showConfirmButton: false }); params.delete('imported'); history.replaceState({}, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '')); }

            var tableEl = document.getElementById('engineListTable');
            var formFieldNames = <?php echo json_encode(array_map(function($f) { return $f['name'] ?? $f['key'] ?? ''; }, $form_fields)); ?>;
            if (tableEl && typeof window.DataTable !== 'undefined') {
                var dt = new DataTable(tableEl, {
                    pageLength: 20,
                    lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
                    searching: true,
                    ordering: true,
                    order: [[formFieldNames.length, 'desc']],
                    columnDefs: [{ targets: 'no-sort', orderable: false }],
                    language: { emptyTable: 'Tiada data', search: 'Cari:', lengthMenu: 'Papar _MENU_ rekod', info: 'Paparan _START_ hingga _END_ dari _TOTAL_ rekod', paginate: { first: 'Pertama', last: 'Akhir', next: 'Seterusnya', previous: 'Sebelumnya' } }
                });
                document.querySelectorAll('.engine-filter-select').forEach(function(sel) {
                    var colName = sel.getAttribute('data-column');
                    var colIdx = formFieldNames.indexOf(colName);
                    if (colIdx === -1) return;
                    sel.addEventListener('change', function() {
                        var val = this.value;
                        dt.column(colIdx).search(val ? '^' + val.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$' : '', true, false).draw();
                    });
                });
                var resetBtn = document.getElementById('engineFilterReset');
                if (resetBtn) resetBtn.addEventListener('click', function() {
                    document.querySelectorAll('.engine-filter-select').forEach(function(s) { s.value = ''; });
                    dt.columns().search('').draw();
                });
            }

            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.engine-btn-delete');
                if (btn) {
                    var form = btn.closest('form');
                    if (form && (btn.closest('#engineListTable') || btn.closest('#engineListCards'))) {
                        if (typeof Swal !== 'undefined') { Swal.fire({ title: 'Hapus rekod?', text: 'Tindakan ini tidak boleh dibatalkan.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Batal', confirmButtonText: 'Ya, hapus' }).then(function(r) { if (r.isConfirmed) form.submit(); }); } else { if (confirm('Hapus rekod ini?')) form.submit(); }
                    }
                    return;
                }
                var cardEdit = e.target.closest('.engine-list-card-wrapper');
                if (cardEdit) {
                    var id = cardEdit.getAttribute('data-id');
                    var payloadStr = cardEdit.getAttribute('data-payload');
                    var payload = {};
                    try { payload = payloadStr ? JSON.parse(payloadStr) : {}; } catch(err) {}
                    var form = document.getElementById('engineFormModalForm');
                    if (form) {
                        form.querySelector('#engineFormAction').value = 'update';
                        form.querySelector('#engineFormRecordId').value = id;
                        form.reset();
                        form.querySelector('#engineFormAction').value = 'update';
                        form.querySelector('#engineFormRecordId').value = id;
                        Object.keys(payload).forEach(function(k) {
                            var el = form.querySelector('[name="' + k + '"]');
                            if (el) { if (el.type === 'checkbox' || el.type === 'radio') el.checked = Array.isArray(payload[k]) ? payload[k].indexOf(el.value) >= 0 : (el.value === String(payload[k])); else el.value = Array.isArray(payload[k]) ? (payload[k][0] || '') : payload[k]; }
                        });
                        <?php if ($has_ref_fields): ?>
                        if (window.engineRefLoadInitialValues) window.engineRefLoadInitialValues(form, payload);
                        <?php endif; ?>
                        if (typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(document.getElementById('engineFormModal')).show();
                    }
                }
            });
        })();
        </script>
        <?php endif; ?>

        <?php endif; // end view === list ?>

        <?php if ($view === 'calendar' && $calendar_enabled): ?>
        <!-- Module Calendar: FullCalendar -->
        <div class="card shadow-sm">
            <div class="card-header bg-light"><h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kalendar</h6></div>
            <div class="card-body">
                <div id="engineCalendar"></div>
            </div>
        </div>
        <?php
        $startF = $calendar_start;
        $endF = $calendar_end;
        if (!$startF && count($form_fields) > 0) {
            foreach ($form_fields as $f) { if (in_array($f['type'] ?? '', ['date', 'datetime-local'], true)) { $startF = $f['name'] ?? $f['key'] ?? ''; break; } }
        }
        $titleF = $calendar_title;
        if (!$titleF && count($form_fields) > 0) { $titleF = $form_fields[0]['name'] ?? $form_fields[0]['key'] ?? ''; }
        $cal_events = [];
        foreach ($list_data as $row) {
            $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
            $start = $startF ? ($pl[$startF] ?? $row['created_at'] ?? '') : ($row['created_at'] ?? '');
            $end = $endF ? ($pl[$endF] ?? null) : null;
            $title = $titleF ? ($pl[$titleF] ?? 'Rekod #' . $row['id']) : ('Rekod #' . $row['id']);
            if ($start) {
                $cal_events[] = ['id' => $row['id'], 'title' => $title, 'start' => $start, 'end' => $end ?: $start];
            }
        }
        ?>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/ms.global.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calEl = document.getElementById('engineCalendar');
            if (calEl && typeof FullCalendar !== 'undefined') {
                var events = <?php echo json_encode($cal_events); ?>;
                new FullCalendar.Calendar(calEl, {
                    initialView: 'dayGridMonth',
                    locale: 'ms',
                    events: events,
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' }
                }).render();
            }
        });
        </script>
        <?php endif; ?>

        <?php if ($view === 'report'): ?>
        <!-- Dashboard: widget dari metadata (AdminLTE info-box) + COUNT/SUM/AVG real-time dari custom_app_data -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <style>
        .info-box { display: flex; align-items: stretch; min-height: 90px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.12); margin-bottom: 12px; overflow: hidden; }
        .info-box .info-box-icon { width: 70px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.75rem; flex-shrink: 0; }
        .info-box .info-box-icon.bg-info { background: #17a2b8; }
        .info-box .info-box-icon.bg-success { background: #28a745; }
        .info-box .info-box-icon.bg-warning { background: #ffc107; color: #1f2d3d; }
        .info-box .info-box-icon.bg-primary { background: #007bff; }
        .info-box .info-box-content { flex: 1; padding: 10px 15px; display: flex; flex-direction: column; justify-content: center; }
        .info-box .info-box-text { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; margin-bottom: 2px; }
        .info-box .info-box-number { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; }
        .engine-infobox { display: flex; align-items: stretch; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); margin-bottom: 12px; overflow: hidden; border: none; }
        .engine-infobox .engine-infobox-icon { width: 70px; min-height: 70px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; flex-shrink: 0; }
        .engine-infobox .engine-infobox-icon.bg-primary { background: linear-gradient(135deg, #4a90e2, #357abd); }
        .engine-infobox .engine-infobox-icon.bg-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .engine-infobox .engine-infobox-icon.bg-info { background: linear-gradient(135deg, #17a2b8, #138496); }
        .engine-infobox .engine-infobox-icon.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .engine-infobox .engine-infobox-content { flex: 1; padding: 12px 16px; display: flex; flex-direction: column; justify-content: center; }
        .engine-infobox .engine-infobox-text { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; margin-bottom: 2px; }
        .engine-infobox .engine-infobox-number { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; }
        </style>
        <div class="card shadow-sm">
            <div class="card-header bg-light"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Dashboard</h6></div>
            <div class="card-body">
                <?php
                $total_records = count($list_data);
                $this_month = 0;
                $first_select_label = '';
                $first_select_counts = [];
                foreach ($list_data as $row) {
                    $created = $row['created_at'] ?? '';
                    if ($created && substr($created, 0, 7) === date('Y-m')) $this_month++;
                }
                if (count($form_fields) > 0) {
                    $fs = null;
                    foreach ($form_fields as $f) { if (in_array($f['type'] ?? '', ['select', 'radio'], true)) { $fs = $f; break; } }
                    if ($fs) {
                        $first_select_label = $fs['label'] ?? $fs['name'] ?? $fs['key'] ?? '';
                        $fn = $fs['name'] ?? $fs['key'] ?? '';
                        foreach ($list_data as $row) {
                            $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
                            $val = $pl[$fn] ?? '-';
                            $first_select_counts[$val] = ($first_select_counts[$val] ?? 0) + 1;
                        }
                        arsort($first_select_counts);
                    }
                }
                $info_box_colors = ['bg-info', 'bg-success', 'bg-warning', 'bg-primary'];
                $info_box_icons = ['fas fa-chart-line', 'fas fa-database', 'fas fa-calculator', 'fas fa-list-ol'];
                if (!empty($dashboard_cards_meta) && is_array($dashboard_cards_meta)):
                    $widget_index = 0;
                ?>
                <div class="row g-2 mb-4">
                    <?php foreach ($dashboard_cards_meta as $card):
                        $title = $card['title'] ?? $card['label'] ?? 'Widget';
                        $field = $card['field'] ?? '';
                        $agg = strtolower($card['aggregation'] ?? $card['agg'] ?? 'count');
                        $value = 0;
                        if ($agg === 'count') {
                            $value = $total_records;
                        } else {
                            $nums = [];
                            foreach ($list_data as $row) {
                                $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
                                $v = $field !== '' ? ($pl[$field] ?? null) : null;
                                if ($v !== null && $v !== '' && is_numeric(str_replace([',', ' '], '', $v))) {
                                    $nums[] = (float) str_replace([',', ' '], '', $v);
                                }
                            }
                            if ($agg === 'sum') {
                                $value = array_sum($nums);
                            } elseif ($agg === 'average' && count($nums) > 0) {
                                $value = array_sum($nums) / count($nums);
                            }
                        }
                        $color_class = $info_box_colors[$widget_index % count($info_box_colors)];
                        $icon_class = $info_box_icons[$widget_index % count($info_box_icons)];
                        $widget_index++;
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon <?php echo $color_class; ?>"><i class="<?php echo $icon_class; ?>"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo htmlspecialchars($title); ?></span>
                                <span class="info-box-number"><?php echo $agg === 'average' && $value != (int)$value ? number_format($value, 2) : number_format($value); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="row g-2 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="engine-infobox">
                            <div class="engine-infobox-icon bg-primary"><i class="fas fa-database"></i></div>
                            <div class="engine-infobox-content"><span class="engine-infobox-text">Jumlah Rekod</span><span class="engine-infobox-number"><?php echo $total_records; ?></span></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="engine-infobox">
                            <div class="engine-infobox-icon bg-success"><i class="fas fa-calendar-check"></i></div>
                            <div class="engine-infobox-content"><span class="engine-infobox-text">Bulan Ini</span><span class="engine-infobox-number"><?php echo $this_month; ?></span></div>
                        </div>
                    </div>
                    <?php if (!empty($first_select_counts)): ?>
                    <div class="col-6 col-md-3">
                        <div class="engine-infobox">
                            <div class="engine-infobox-icon bg-info"><i class="fas fa-tag"></i></div>
                            <div class="engine-infobox-content"><span class="engine-infobox-text"><?php echo htmlspecialchars($first_select_label ?: 'Kategori'); ?></span><span class="engine-infobox-number"><?php echo count($first_select_counts); ?></span></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (count($list_data) > 0 && count($form_fields) > 0):
                    $firstField = $form_fields[0];
                    $fn = $firstField['name'] ?? $firstField['key'] ?? '';
                    $counts = [];
                    foreach ($list_data as $row) {
                        $pl = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
                        $val = $pl[$fn] ?? '-';
                        $counts[$val] = ($counts[$val] ?? 0) + 1;
                    }
                    arsort($counts);
                    $labels = array_keys(array_slice($counts, 0, 10, true));
                    $values = array_values(array_slice($counts, 0, 10, true));
                    $colors = ['rgba(74, 144, 226, 0.8)', 'rgba(40, 167, 69, 0.8)', 'rgba(23, 162, 184, 0.8)', 'rgba(255, 193, 7, 0.8)', 'rgba(220, 53, 69, 0.8)', 'rgba(111, 66, 193, 0.8)', 'rgba(253, 126, 20, 0.8)', 'rgba(32, 201, 151, 0.8)', 'rgba(102, 16, 242, 0.8)', 'rgba(214, 51, 132, 0.8)'];
                ?>
                <div class="row">
                    <div class="col-md-6 mb-3"><div class="chart-container" style="position:relative;height:260px"><canvas id="engineReportChart"></canvas></div></div>
                    <div class="col-md-6 mb-3"><div class="chart-container" style="position:relative;height:260px"><canvas id="engineReportChartPie"></canvas></div></div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Chart !== 'undefined') {
                        var labels = <?php echo json_encode(array_map('htmlspecialchars', $labels)); ?>;
                        var values = <?php echo json_encode($values); ?>;
                        var colors = <?php echo json_encode(array_slice($colors, 0, count($labels))); ?>;
                        new Chart(document.getElementById('engineReportChart'), {
                            type: 'bar',
                            data: { labels: labels, datasets: [{ label: '<?php echo htmlspecialchars(addslashes($firstField['label'] ?? $fn)); ?>', data: values, backgroundColor: 'rgba(59, 130, 246, 0.7)' }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                        });
                        new Chart(document.getElementById('engineReportChartPie'), {
                            type: 'doughnut',
                            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2 }] },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                        });
                    }
                });
                </script>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($view === 'form'): ?>
        <!-- Full-page Borang (untuk akses awam / pautan langsung) -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-wpforms me-2"></i><?php echo htmlspecialchars($app_title); ?></h5></div>
            <div class="card-body">
                <?php if ($is_public && $current_user_id === 0): ?>
                <div class="alert alert-info py-2">Aplikasi awam. Anda boleh isi tanpa log masuk. Untuk melihat senarai, sila log masuk.</div>
                <?php endif; ?>
                <?php if (!empty($_GET['saved'])): ?>
                <div class="alert alert-success py-2 d-none" id="engineFormSavedMsg">Rekod berjaya disimpan.</div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berjaya',
                            text: 'Rekod berjaya disimpan.',
                            confirmButtonText: '<?php echo $current_user_id ? 'Lihat senarai' : 'Hantar lagi'; ?>'
                        }).then(function() {
                            window.location.href = '<?php echo $current_user_id ? $engine_base . $sep . 'page=list' : $engine_base . $sep . 'page=form'; ?>';
                        });
                    }
                });
                </script>
                <?php endif; ?>
                <?php if ($form_error): ?>
                <div class="alert alert-danger py-2"><?php echo $form_error; ?></div>
                <?php endif; ?>
                <form id="engineFullPageForm" method="post" action="<?php echo htmlspecialchars($form_action); ?>">
                    <?php echo getCsrfTokenField(); ?>
                    <input type="hidden" name="app_slug" value="<?php echo htmlspecialchars($app_slug); ?>">
                    <?php foreach ($form_fields as $field):
                        $name = $field['name'] ?? $field['key'] ?? '';
                        $label = $field['label'] ?? $name;
                        $type = $field['type'] ?? 'text';
                        $required = !empty($field['required']);
                        $placeholder = $field['placeholder'] ?? '';
                        $options = $field['options'] ?? [];
                        if (empty($name)) continue;
                        $val = $fieldVal($name);
                        $valStr = is_array($val) ? '' : htmlspecialchars($val);
                    ?>
                    <div class="mb-3">
                        <label for="fp_<?php echo htmlspecialchars($name); ?>" class="form-label"><?php echo htmlspecialchars($label); ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <?php if ($type === 'textarea'): ?>
                        <textarea name="<?php echo htmlspecialchars($name); ?>" id="fp_<?php echo htmlspecialchars($name); ?>" class="form-control" rows="<?php echo (int)($field['rows'] ?? 3); ?>" <?php if ($required) echo ' required'; ?>><?php echo $valStr; ?></textarea>
                        <?php elseif ($type === 'lookup'): ?>
                        <?php $srcOpts = $lookup_options[$name] ?? []; $lookup_app_id = (int) ($field['lookup_app_id'] ?? 0); ?>
                        <select name="<?php echo htmlspecialchars($name); ?>" id="fp_<?php echo htmlspecialchars($name); ?>" class="form-select engine-lookup-select" data-lookup-app-id="<?php echo $lookup_app_id; ?>" <?php if ($required) echo ' required'; ?>>
                            <option value="">-- Pilih Rekod --</option>
                            <?php foreach ($srcOpts as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt['value']); ?>" <?php echo ($val !== '' && (string)$val === (string)$opt['value']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opt['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif ($type === 'ref'): ?>
                        <?php $refCfg = $ref_fields_config[$name] ?? null; if ($refCfg): ?>
                        <select name="<?php echo htmlspecialchars($name); ?>" id="fp_<?php echo htmlspecialchars($name); ?>" class="form-select engine-ref-select" data-ref-app-id="<?php echo (int) $refCfg['app_id']; ?>" data-ref-field="<?php echo htmlspecialchars($refCfg['display_field']); ?>" <?php if ($required) echo ' required'; ?>>
                            <option value="">-- Cari rekod dari aplikasi rujukan --</option>
                            <?php if (isset($ref_initial[$name])): ?>
                            <option value="<?php echo htmlspecialchars($ref_initial[$name]['id']); ?>" selected><?php echo htmlspecialchars($ref_initial[$name]['text']); ?></option>
                            <?php endif; ?>
                        </select>
                        <?php endif; ?>
                        <?php elseif ($type === 'select'): ?>
                        <select name="<?php echo htmlspecialchars($name); ?>" id="fp_<?php echo htmlspecialchars($name); ?>" class="form-select" <?php if ($required) echo ' required'; ?>>
                            <option value="">-- Pilih --</option>
                            <?php foreach ((array)$options as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; ?>
                            <option value="<?php echo htmlspecialchars($ov); ?>" <?php echo $val === (string)$ov ? ' selected' : ''; ?>><?php echo htmlspecialchars($ol); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif ($type === 'checkbox' || $type === 'radio'): $is_radio = ($type === 'radio');
                            foreach ((array)($options ?: [['value'=>'1','label'=>$label]]) as $opt): $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : $opt; $ol = is_array($opt) ? ($opt['label'] ?? $ov) : $opt; ?>
                        <div class="form-check"><input type="<?php echo $is_radio ? 'radio' : 'checkbox'; ?>" name="<?php echo htmlspecialchars($name); ?><?php echo $is_radio ? '' : '[]'; ?>" value="<?php echo htmlspecialchars($ov); ?>" id="fp_<?php echo htmlspecialchars($name); ?>_<?php echo preg_replace('/[^a-z0-9]/i', '_', $ov); ?>" class="form-check-input" <?php echo $fieldChecked($name, $ov) ? ' checked' : ''; ?>><label class="form-check-label" for="fp_<?php echo htmlspecialchars($name); ?>_<?php echo preg_replace('/[^a-z0-9]/i', '_', $ov); ?>"><?php echo htmlspecialchars($ol); ?></label></div>
                        <?php endforeach; ?>
                        <?php else: $input_type = in_array($type, ['email','number','tel','url','date','datetime-local'], true) ? $type : 'text'; ?>
                        <input type="<?php echo $input_type; ?>" name="<?php echo htmlspecialchars($name); ?>" id="fp_<?php echo htmlspecialchars($name); ?>" class="form-control" value="<?php echo $valStr; ?>" <?php if ($required) echo ' required'; ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" name="submit" value="1" class="btn btn-primary"><i class="fas fa-save me-1"></i> Hantar</button>
                    <?php if ($current_user_id): ?>
                    <a href="<?php echo htmlspecialchars($engine_base . $sep . 'view=list'); ?>" class="btn btn-outline-secondary ms-2">Kembali ke Senarai</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($view === 'edit' && $edit_record !== null): ?>
        <!-- Legacy: Edit page (boleh redirect ke list + open modal) -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white"><h5 class="mb-0">Kemaskini Rekod</h5></div>
            <div class="card-body">
                <p class="text-muted">Gunakan butang &quot;Kemaskini&quot; pada baris dalam Senarai untuk mengedit. <a href="<?php echo htmlspecialchars($engine_base . $sep . 'page=list'); ?>">Kembali ke Senarai</a></p>
            </div>
        </div>
        <?php endif; ?>
        <?php
        $engine_main_content = ob_get_clean();
        if ($is_ajax_fragment) {
            header('Content-Type: text/html; charset=utf-8');
            echo $engine_main_content;
            exit;
        }
        ?>
        <div id="engine-main-content"><?php echo $engine_main_content; ?></div>
        <!-- Auto-Fill Lookup: bila user pilih rekod, isi field lain yang nama sepadan dengan payload -->
        <script>
        (function() {
            var lookupDetailsUrl = <?php echo json_encode($engine_lookup_details_url); ?>;
            function escapeSelector(s) {
                return s.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            }
            function fillFormFromPayload(form, payload, skipElement) {
                if (!form || !payload || typeof payload !== 'object') return;
                for (var key in payload) {
                    if (!payload.hasOwnProperty(key)) continue;
                    var val = payload[key];
                    var esc = escapeSelector(key);
                    var els = form.querySelectorAll('[name="' + esc + '"], [name="' + esc + '[]"]');
                    for (var i = 0; i < els.length; i++) {
                        var el = els[i];
                        if (el === skipElement) continue;
                        var currentVal = (el.type === 'checkbox' || el.type === 'radio') ? el.checked : (el.value || '').trim();
                        if (el.type === 'checkbox') {
                            if (!currentVal) el.checked = Array.isArray(val) ? val.indexOf(el.value) !== -1 : (String(val) === el.value);
                        } else if (el.type === 'radio') {
                            if (!currentVal) el.checked = (String(val) === el.value);
                        } else {
                            if (currentVal === '') el.value = Array.isArray(val) ? (val[0] || '') : (val == null ? '' : String(val));
                        }
                    }
                }
            }
            document.addEventListener('change', function(e) {
                if (!e.target || !e.target.classList || !e.target.classList.contains('engine-lookup-select')) return;
                var sel = e.target;
                var recordId = sel.value;
                var appId = sel.getAttribute('data-lookup-app-id');
                if (!recordId || !appId) return;
                var form = sel.closest('form');
                if (!form) return;
                var url = lookupDetailsUrl + (lookupDetailsUrl.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(recordId) + '&app_id=' + encodeURIComponent(appId);
                fetch(url).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.success && data.payload) fillFormFromPayload(form, data.payload, sel);
                }).catch(function() {});
            });
        })();
        </script>
        <?php if ($has_ref_fields): ?>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap5-theme.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <script>
        (function() {
            var refDataUrl = <?php echo json_encode($engine_ref_data_url); ?>;
            window.engineRefLoadInitialValues = function(form, payload) {
                if (!form || !payload) return;
                var refSelects = form.querySelectorAll('.engine-ref-select');
                refSelects.forEach(function(sel) {
                    var name = sel.getAttribute('name');
                    var val = payload[name];
                    if (val === undefined || val === null || val === '') return;
                    val = String(val);
                    var appId = sel.getAttribute('data-ref-app-id');
                    var field = sel.getAttribute('data-ref-field');
                    if (!appId || !field) return;
                    var url = refDataUrl + (refDataUrl.indexOf('?') >= 0 ? '&' : '?') + 'app_id=' + encodeURIComponent(appId) + '&display_field=' + encodeURIComponent(field) + '&id=' + encodeURIComponent(val);
                    fetch(url).then(function(r) { return r.json(); }).then(function(data) {
                        if (data.results && data.results[0]) {
                            var opt = new Option(data.results[0].text, data.results[0].id, true, true);
                            sel.appendChild(opt);
                            if (window.jQuery) try { jQuery(sel).trigger('change'); } catch(e) {}
                        }
                    }).catch(function() {});
                });
            };
            function initRefSelect2() {
                jQuery('.engine-ref-select').each(function() {
                    var el = jQuery(this);
                    if (el.data('select2')) return;
                    el.select2({
                        theme: 'bootstrap-5',
                        placeholder: '-- Cari rekod dari aplikasi rujukan --',
                        allowClear: true,
                        width: '100%',
                        ajax: {
                            url: refDataUrl,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    app_id: el.data('ref-app-id'),
                                    display_field: el.data('ref-field'),
                                    q: params.term || '',
                                    page: params.page || 1
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.results || [],
                                    pagination: { more: data.pagination && data.pagination.more }
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 0
                    });
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initRefSelect2);
            } else {
                initRefSelect2();
            }
            jQuery(document).on('shown.bs.modal', '#engineFormModal', function() {
                initRefSelect2();
            });
        })();
        </script>
        <?php endif; ?>
        <!-- Offline: simpan borang ke localStorage, hantar automatik apabila sambungan pulih -->
        <script>
        (function() {
            var appSlug = <?php echo json_encode($app_slug); ?>;
            var formAction = <?php echo json_encode($form_action); ?>;
            if (!appSlug) return;
            var DRAFT_KEY = 'engine_draft_' + appSlug;
            var PENDING_KEY = 'engine_pending_' + appSlug;
            var debounceTimer;

            function serializeForm(form) {
                var data = {};
                var els = form.querySelectorAll('input, select, textarea');
                for (var i = 0; i < els.length; i++) {
                    var el = els[i];
                    var name = el.name;
                    if (!name || el.type === 'submit') continue;
                    if (el.type === 'checkbox') {
                        if (el.checked) {
                            if (name.indexOf('[]') !== -1) {
                                var key = name.replace('[]', '');
                                if (!data[key]) data[key] = [];
                                data[key].push(el.value);
                            } else {
                                data[name] = el.value;
                            }
                        }
                    } else if (el.type === 'radio') {
                        if (el.checked) data[name] = el.value;
                    } else {
                        data[name] = el.value;
                    }
                }
                return data;
            }

            function applyToForm(form, data) {
                if (!form || !data) return;
                for (var key in data) {
                    var val = data[key];
                    var els = form.querySelectorAll('[name="' + key + '"], [name="' + key + '[]"]');
                    if (els.length === 0) continue;
                    if (Array.isArray(val)) {
                        els.forEach(function(el) {
                            if (el.type === 'checkbox') el.checked = val.indexOf(el.value) !== -1;
                        });
                    } else if (els.length === 1) {
                        var el = els[0];
                        if (el.type === 'checkbox' || el.type === 'radio') el.checked = (el.value === String(val)); else el.value = val || '';
                    } else {
                        els.forEach(function(el) {
                            if (el.type === 'checkbox') el.checked = val.indexOf(el.value) !== -1; else if (el.type === 'radio') el.checked = (el.value === String(val)); else el.value = val || '';
                        });
                    }
                }
            }

            function saveDraft(form) {
                if (!form || !navigator.onLine) return;
                try {
                    var data = serializeForm(form);
                    localStorage.setItem(DRAFT_KEY, JSON.stringify({ savedAt: Date.now(), data: data }));
                } catch (e) {}
            }

            function clearDraft() {
                try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
            }

            function savePendingAndNotify(form) {
                try {
                    var data = serializeForm(form);
                    if (!data.submit) data.submit = '1';
                    localStorage.setItem(PENDING_KEY, JSON.stringify({ savedAt: Date.now(), formAction: form.action || formAction, data: data }));
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Data disimpan secara tempatan. Akan dihantar apabila sambungan pulih.', timer: 4000, showConfirmButton: false });
                    } else {
                        alert('Data disimpan secara tempatan. Akan dihantar apabila sambungan pulih.');
                    }
                } catch (e) {
                    alert('Data disimpan secara tempatan. Akan dihantar apabila sambungan pulih.');
                }
            }

            function submitPending() {
                try {
                    var raw = localStorage.getItem(PENDING_KEY);
                    if (!raw) return;
                    var stored = JSON.parse(raw);
                    if (!stored || !stored.data || !stored.formAction) return;
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = stored.formAction;
                    form.style.display = 'none';
                    for (var k in stored.data) {
                        var val = stored.data[k];
                        if (val === undefined || val === null) val = '';
                        if (Array.isArray(val)) {
                            for (var j = 0; j < val.length; j++) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = k.indexOf('[]') !== -1 ? k : (k + '[]');
                                input.value = String(val[j]);
                                form.appendChild(input);
                            }
                        } else {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = k;
                            input.value = String(val);
                            form.appendChild(input);
                        }
                    }
                    document.body.appendChild(form);
                    localStorage.removeItem(PENDING_KEY);
                    clearDraft();
                    form.submit();
                } catch (e) {}
            }

            function attachForm(form) {
                if (!form) return;
                form.addEventListener('submit', function(e) {
                    if (navigator.onLine) {
                        clearDraft();
                        return;
                    }
                    e.preventDefault();
                    savePendingAndNotify(form);
                });
                form.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() { saveDraft(form); }, 1000);
                });
                form.addEventListener('change', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() { saveDraft(form); }, 500);
                });
            }

            window.addEventListener('online', function() {
                if (navigator.onLine) submitPending();
            });

            function init() {
                var modalForm = document.getElementById('engineFormModalForm');
                var fullForm = document.getElementById('engineFullPageForm');
                attachForm(modalForm);
                attachForm(fullForm);
                if (navigator.onLine) {
                    submitPending();
                } else {
                    try {
                        var draftRaw = localStorage.getItem(DRAFT_KEY);
                        if (draftRaw) {
                            var draft = JSON.parse(draftRaw);
                            var form = fullForm || modalForm;
                            if (form && draft.data) applyToForm(form, draft.data);
                        }
                    } catch (e) {}
                }
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
        })();
        </script>
        <!-- AJAX View Switcher: Senarai / Tambah tanpa refresh halaman -->
        <script>
        (function() {
            var engineAjaxBase = <?php echo json_encode($engine_base . $sep); ?>;
            var container = document.getElementById('engine-main-content');
            var navLinks = document.querySelectorAll('.engine-nav-link');
            function setActivePage(pageId) {
                navLinks.forEach(function(a) {
                    a.classList.toggle('active', (a.getAttribute('data-page') || '') === pageId);
                });
            }
            function loadPage(pageId) {
                var url = engineAjaxBase + (engineAjaxBase.indexOf('?') >= 0 ? '&' : '') + 'ajax=1&page=' + encodeURIComponent(pageId);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.text(); })
                    .then(function(html) {
                        if (container) container.innerHTML = html;
                        setActivePage(pageId);
                        var fab = document.querySelector('.engine-fab');
                        if (fab) fab.style.display = (pageId === 'form') ? 'none' : '';
                        var newUrl = engineAjaxBase + 'page=' + encodeURIComponent(pageId);
                        history.pushState({ page: pageId }, '', newUrl);
                    })
                    .catch(function() {
                        window.location.href = engineAjaxBase + 'page=' + encodeURIComponent(pageId);
                    });
            }
            document.querySelector('.engine-appsheet') && document.querySelector('.engine-appsheet').addEventListener('click', function(e) {
                var a = e.target.closest('.engine-nav-link');
                if (!a) return;
                e.preventDefault();
                var pageId = a.getAttribute('data-page');
                if (pageId) loadPage(pageId);
            });
            window.addEventListener('popstate', function(e) {
                var pageId = (e.state && e.state.page) || (new URLSearchParams(window.location.search)).get('page') || 'list';
                loadPage(pageId);
            });
        })();
        </script>
        </div><!-- .engine-appsheet -->
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
