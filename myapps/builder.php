<?php
/**
 * No-Code Builder - Cipta aplikasi borang tanpa kod.
 * Gunakan header.php & footer.php; simpan ke custom_apps; id_user_owner dari $_SESSION['id_user'].
 */
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Tarik senarai kategori dari jadual kategori: user mesti pilih satu kategori semasa bina app
$kategoriList = [];
try {
    $kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jadual kategori mungkin tidak wujud atau kolum berbeza
}

// Senarai aplikasi untuk Lookup Record (ambil dari custom_apps)
$lookupApps = [];
try {
    // Dapatkan nama PK jadual custom_apps
    $custom_app_pk = 'id';
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_apps' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_app_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
    $stmtApps = $pdo->query("SELECT `{$custom_app_pk}` AS id, app_slug, app_name FROM custom_apps ORDER BY app_slug ASC");
    while ($app = $stmtApps->fetch(PDO::FETCH_ASSOC)) {
        $label = $app['app_name'] ?? $app['app_slug'] ?? ('App #' . $app['id']);
        $lookupApps[] = [
            'id'    => (int) $app['id'],
            'label' => $label . ' (' . ($app['app_slug'] ?? '-') . ')'
        ];
    }
} catch (PDOException $e) {
    // Jadual custom_apps mungkin belum wujud; abaikan
}

require_once __DIR__ . '/header.php';
?>
<!-- Google Fonts for Professional Look -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- SortableJS for Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

/* ═══════════════════════════════════════════════════════════════════════
   ENHANCED WIZARD PROGRESS - Modern Step Indicator
   ═══════════════════════════════════════════════════════════════════════ */
.builder-wizard-progress {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 24px 32px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
    margin-bottom: 32px;
}

.builder-wizard-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.builder-wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 2;
}

.builder-wizard-step-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.6);
    font-size: 20px;
    transition: all 0.3s;
    margin-bottom: 8px;
}

.builder-wizard-step-label {
    font-size: 12px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.3s;
}

.builder-wizard-step.active .builder-wizard-step-icon {
    background: white;
    border-color: white;
    color: #667eea;
    box-shadow: 0 4px 16px rgba(255, 255, 255, 0.4);
    transform: scale(1.1);
}

.builder-wizard-step.active .builder-wizard-step-label {
    color: white;
}

.builder-wizard-step.completed .builder-wizard-step-icon {
    background: rgba(16, 185, 129, 0.9);
    border-color: rgba(16, 185, 129, 0.9);
    color: white;
}

.builder-wizard-step.completed .builder-wizard-step-label {
    color: rgba(255, 255, 255, 0.9);
}

.builder-wizard-step:hover .builder-wizard-step-icon {
    transform: scale(1.05);
    box-shadow: 0 2px 12px rgba(255, 255, 255, 0.3);
}

.builder-wizard-step-line {
    flex: 1;
    height: 2px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0 16px;
    position: relative;
    top: -16px;
}

.builder-wizard-step.completed ~ .builder-wizard-step-line {
    background: rgba(16, 185, 129, 0.5);
}

.builder-wizard-progress-text {
    text-align: center;
    color: white;
    font-size: 16px;
}

.builder-wizard-progress-text .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

@media (max-width: 768px) {
    .builder-wizard-steps {
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .builder-wizard-step-line {
        display: none;
    }
    
    .builder-wizard-step-label {
        font-size: 10px;
    }
}

/* ═══════════════════════════════════════════════════════════════════════
   MODERN BUILDER UI - AppSheet/Glide/Bubble Style
   ═══════════════════════════════════════════════════════════════════════ */

/* Toolbar */
.builder-toolbar {
    background: white;
    border-bottom: 1px solid #dee2e6;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.builder-toolbar-left,
.builder-toolbar-center,
.builder-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.builder-toolbar-btn {
    background: transparent;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.builder-toolbar-btn:hover {
    background: #f8f9fa;
    color: #0d6efd;
}

.builder-toolbar-btn.active {
    background: #e7f1ff;
    color: #0d6efd;
}

/* 3-Column Workspace */
.builder-workspace {
    display: grid;
    grid-template-columns: 280px 1fr 360px;
    height: calc(100vh - 300px);
    background: #f8f9fa;
    overflow: hidden;
}

/* Sidebars */
.builder-sidebar {
    background: white;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    overflow-x: hidden;
}

.builder-sidebar-right {
    border-right: none;
    border-left: 1px solid #dee2e6;
}

.builder-sidebar-content {
    padding: 20px;
}

.builder-section {
    margin-bottom: 24px;
}

.builder-section-title {
    font-size: 12px;
    font-weight: 700;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
}

/* Component Cards Grid */
.builder-components-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

/* Component Card - Modern Design */
.builder-field-type-card {
    background: white;
    border: 1.5px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 8px;
    text-align: center;
    cursor: move;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    position: relative;
    overflow: hidden;
}

.builder-field-type-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s;
}

.builder-field-type-card:hover {
    border-color: #667eea;
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
}

.builder-field-type-card:hover::before {
    transform: scaleX(1);
}

.builder-field-type-card.dragging {
    opacity: 0.6;
    transform: scale(0.98) rotate(2deg);
}

.builder-field-type-icon {
    font-size: 28px;
    margin-bottom: 6px;
    color: #667eea;
    transition: all 0.2s;
}

.builder-field-type-card:hover .builder-field-type-icon {
    transform: scale(1.1);
    color: #764ba2;
}

.builder-field-type-name {
    font-size: 11px;
    font-weight: 600;
    color: #495057;
    line-height: 1.2;
}

.builder-field-type-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-size: 8px;
    padding: 2px 5px;
    border-radius: 3px;
    font-weight: 700;
    text-transform: uppercase;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

/* Canvas */
.builder-canvas-container {
    background: #f8f9fa;
    overflow-y: auto;
    padding: 20px;
}

.builder-canvas-header {
    background: white;
    padding: 16px 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 0;
}

.builder-canvas-title {
    font-size: 16px;
    font-weight: 700;
    color: #212529;
    display: flex;
    align-items: center;
}

.builder-canvas-badge {
    background: #e7f1ff;
    color: #0d6efd;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.builder-canvas {
    background: white;
    border-radius: 0 0 12px 12px;
    padding: 20px;
    min-height: 500px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* Drop Zone - Modern Style */
.builder-drop-zone {
    min-height: 400px;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    padding: 20px;
    background: linear-gradient(135deg, #fafbfc 0%, #f5f6f8 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.builder-drop-zone.drag-over {
    border-color: #667eea;
    background: linear-gradient(135deg, #f0f3ff 0%, #e7eeff 100%);
    box-shadow: inset 0 0 30px rgba(102, 126, 234, 0.1);
    border-style: solid;
}

.builder-drop-zone-empty {
    text-align: center;
    padding: 80px 20px;
    color: #9ca3af;
}

.builder-drop-zone-empty-icon {
    font-size: 72px;
    color: #d1d5db;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

.builder-drop-zone-empty-title {
    font-size: 18px;
    font-weight: 700;
    color: #6b7280;
    margin-bottom: 8px;
}

.builder-drop-zone-empty-text {
    font-size: 14px;
    color: #9ca3af;
    margin-bottom: 4px;
}

.builder-drop-zone-empty-hint {
    font-size: 12px;
    color: #d1d5db;
    font-style: italic;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-12px); }
}

/* Empty State Component */
.builder-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.builder-empty-state i {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
    display: block;
}

.builder-empty-state p {
    font-size: 13px;
    margin: 0;
}

/* Field Item in Canvas - Modern Card Style */
.dynamic-field-row {
    background: white;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    cursor: move;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.dynamic-field-row::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px 0 0 12px;
    opacity: 0;
    transition: opacity 0.2s;
}

.dynamic-field-row:hover {
    border-color: #667eea;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.12);
    transform: translateY(-2px);
}

.dynamic-field-row:hover::before {
    opacity: 1;
}

.dynamic-field-row.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%);
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
}

.dynamic-field-row.selected::before {
    opacity: 1;
}

.dynamic-field-row.sortable-chosen {
    opacity: 0.7;
    transform: scale(1.03);
    box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
}

.dynamic-field-row.sortable-drag {
    opacity: 0.9;
    transform: rotate(3deg);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
}

.field-drag-handle {
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition: all 0.2s;
    cursor: grab;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.dynamic-field-row:hover .field-drag-handle {
    opacity: 1;
    left: -12px;
}

.field-drag-handle:active {
    cursor: grabbing;
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.field-drag-handle i {
    font-size: 14px;
}

/* Live Preview - Phone Frame Style */
.builder-preview-header {
    margin-bottom: 20px;
}

.builder-preview-device-btns {
    display: flex;
    gap: 6px;
    justify-content: center;
    margin-bottom: 16px;
}

.builder-preview-device-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1.5px solid #e5e7eb;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: #9ca3af;
}

.builder-preview-device-btn:hover {
    border-color: #667eea;
    color: #667eea;
    background: #f5f7ff;
}

.builder-preview-device-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Phone Frame */
.builder-preview-frame {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 32px;
    padding: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    margin: 0 auto;
}

.builder-preview-frame.mobile {
    max-width: 340px;
}

.builder-preview-frame.desktop {
    max-width: 100%;
    border-radius: 16px;
    padding: 8px;
}

.builder-preview-phone {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.1);
}

.builder-preview-notch {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 120px;
    height: 24px;
    background: #1f2937;
    border-radius: 0 0 16px 16px;
    z-index: 10;
}

.builder-preview-content {
    padding: 32px 20px 20px;
    min-height: 550px;
    max-height: 550px;
    overflow-y: auto;
    background: #ffffff;
}

.builder-preview-frame.desktop .builder-preview-notch {
    display: none;
}

.builder-preview-frame.desktop .builder-preview-content {
    padding: 24px;
    min-height: 450px;
}

.builder-preview-field {
    margin-bottom: 20px;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.builder-preview-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.builder-preview-label .required {
    color: #dc3545;
    margin-left: 4px;
}

.builder-preview-input,
.builder-preview-select,
.builder-preview-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    font-family: inherit;
}

.builder-preview-input:focus,
.builder-preview-select:focus,
.builder-preview-textarea:focus {
    border-color: #0d6efd;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.builder-preview-submit {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 24px;
    transition: all 0.2s;
}

.builder-preview-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.3);
}

/* Advanced Field Types Badge */
.advanced-badge {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
}

.layout-badge {
    background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
}

/* Toast Notifications */
.builder-toast {
    position: fixed;
    top: 90px;
    right: 24px;
    background: white;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 9999;
    animation: slideInRight 0.3s ease;
    border-left: 4px solid #198754;
}

.builder-toast.error {
    border-left-color: #dc3545;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.builder-toast-icon {
    font-size: 20px;
    color: #198754;
}

.builder-toast.error .builder-toast-icon {
    color: #dc3545;
}

/* Scrollbar Styling */
.builder-preview-frame::-webkit-scrollbar {
    width: 6px;
}

.builder-preview-frame::-webkit-scrollbar-track {
    background: transparent;
}

.builder-preview-frame::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.builder-preview-frame::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .builder-workspace {
        grid-template-columns: 260px 1fr 340px;
    }
}

@media (max-width: 1200px) {
    .builder-workspace {
        grid-template-columns: 240px 1fr 320px;
    }
    
    .builder-components-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .builder-workspace {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .builder-sidebar {
        display: none;
    }
    
    .builder-sidebar.active {
        display: block;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
        background: white;
    }
    
    .builder-toolbar {
        flex-wrap: wrap;
    }
}

/* Smooth Scrollbar */
.builder-sidebar::-webkit-scrollbar,
.builder-preview-content::-webkit-scrollbar,
.builder-canvas-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.builder-sidebar::-webkit-scrollbar-track,
.builder-preview-content::-webkit-scrollbar-track,
.builder-canvas-container::-webkit-scrollbar-track {
    background: transparent;
}

.builder-sidebar::-webkit-scrollbar-thumb,
.builder-preview-content::-webkit-scrollbar-thumb,
.builder-canvas-container::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.builder-sidebar::-webkit-scrollbar-thumb:hover,
.builder-preview-content::-webkit-scrollbar-thumb:hover,
.builder-canvas-container::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* ═══════════════════════════════════════════════════════════════════════
   STEP 3: STYLING & THEMES
   ═══════════════════════════════════════════════════════════════════════ */

/* Theme Cards Grid */
.builder-theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.builder-theme-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
}

.builder-theme-card:hover {
    border-color: #667eea;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
}

.builder-theme-card.active {
    border-color: #667eea;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    position: relative;
}

.builder-theme-card.active::after {
    content: '';
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
}

.builder-theme-preview {
    height: 120px;
    position: relative;
}

.builder-theme-preview::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40%;
    background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.1) 100%);
}

.builder-theme-info {
    padding: 12px;
    background: white;
}

.builder-theme-name {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.builder-theme-badge {
    font-size: 11px;
    color: #10b981;
    font-weight: 600;
}

/* Style Preview */
.builder-style-preview {
    background: #f9fafb;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e5e7eb;
}

.preview-header {
    background: var(--preview-primary, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    padding: 20px;
    text-align: center;
    color: white;
}

.preview-title {
    font-size: 18px;
    font-weight: 700;
}

.preview-content {
    padding: 20px;
}

.preview-field {
    margin-bottom: 16px;
}

.preview-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.preview-field input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: var(--preview-radius, 12px);
    font-size: 14px;
    transition: all 0.2s;
}

.preview-field input:focus {
    border-color: var(--preview-primary-color, #667eea);
    outline: none;
}

.preview-button-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.preview-btn {
    padding: 10px 20px;
    border: none;
    border-radius: var(--preview-radius, 12px);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.preview-btn-primary {
    background: var(--preview-primary-color, #667eea);
    color: white;
}

.preview-btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.preview-btn-secondary {
    background: white;
    color: #6b7280;
    border: 2px solid #e5e7eb;
}

.preview-btn-secondary:hover {
    border-color: #d1d5db;
}

/* ═══════════════════════════════════════════════════════════════════════
   STEP 5: FEATURE CARDS
   ═══════════════════════════════════════════════════════════════════════ */
.builder-feature-card {
    display: block;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    height: 100%;
}

.builder-feature-card:hover {
    border-color: #667eea;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
}

.builder-feature-checkbox {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.builder-feature-checkbox:checked ~ .builder-feature-icon {
    transform: scale(1.1);
}

.builder-feature-checkbox:checked + .builder-feature-icon + .builder-feature-name {
    color: #667eea;
}

.builder-feature-card:has(.builder-feature-checkbox:checked) {
    border-color: #667eea;
    background: linear-gradient(135deg, #faf5ff 0%, #f5f3ff 100%);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
}

.builder-feature-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: white;
    font-size: 28px;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.builder-feature-name {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 6px;
    transition: all 0.2s;
}

.builder-feature-desc {
    font-size: 11px;
    color: #6b7280;
    line-height: 1.4;
}

/* Gradient Accents */
.builder-canvas-header {
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.loading {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-tools me-2"></i>
                    <h5 class="card-title mb-0">No-Code Builder - Cipta Aplikasi Borang</h5>
                </div>
                <div class="card-body">
                    <div id="builderAlert" class="alert d-none" role="alert"></div>

                    <form id="builderForm">
                        <?php echo getCsrfTokenField(); ?>

                        <!-- ENHANCED WIZARD PROGRESS - 5 Steps -->
                        <div class="mb-4">
                            <div class="builder-wizard-progress">
                                <div class="builder-wizard-steps">
                                    <div class="builder-wizard-step active" data-step="1">
                                        <div class="builder-wizard-step-icon">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div class="builder-wizard-step-label">Asas</div>
                                    </div>
                                    <div class="builder-wizard-step-line"></div>
                                    <div class="builder-wizard-step" data-step="2">
                                        <div class="builder-wizard-step-icon">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <div class="builder-wizard-step-label">Data</div>
                                    </div>
                                    <div class="builder-wizard-step-line"></div>
                                    <div class="builder-wizard-step" data-step="3">
                                        <div class="builder-wizard-step-icon">
                                            <i class="fas fa-palette"></i>
                                        </div>
                                        <div class="builder-wizard-step-label">Styling</div>
                                    </div>
                                    <div class="builder-wizard-step-line"></div>
                                    <div class="builder-wizard-step" data-step="4">
                                        <div class="builder-wizard-step-icon">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                        <div class="builder-wizard-step-label">Pages</div>
                                    </div>
                                    <div class="builder-wizard-step-line"></div>
                                    <div class="builder-wizard-step" data-step="5">
                                        <div class="builder-wizard-step-icon">
                                            <i class="fas fa-magic"></i>
                                        </div>
                                        <div class="builder-wizard-step-label">Features</div>
                                    </div>
                                </div>
                                <div class="builder-wizard-progress-text">
                                    <span id="wizardStepTitle" class="fw-bold">Maklumat Asas</span>
                                    <span class="text-muted ms-2">Langkah <span id="wizardStepNum">1</span> / 5</span>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content" id="builderTabContent">
                            <!-- Step 1: Asas - ENHANCED UI -->
                            <div class="tab-pane fade show active" id="content-maklumat" role="tabpanel" data-wizard-step="1">
                                <div class="row justify-content-center">
                                    <div class="col-lg-10">
                                        <!-- Welcome Card -->
                                        <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <div class="card-body text-white text-center py-5">
                                                <div class="mb-3">
                                                    <i class="fas fa-rocket" style="font-size: 64px; opacity: 0.9;"></i>
                                                </div>
                                                <h3 class="fw-bold mb-2">Create Your No-Code App</h3>
                                                <p class="mb-0 opacity-75">Build professional applications without writing code</p>
                                            </div>
                                        </div>

                                        <!-- App Info Card -->
                                        <div class="card mb-4 border-0 shadow-sm">
                                            <div class="card-body p-4">
                                                <h6 class="fw-bold mb-4" style="color: #667eea;">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    App Information
                                                </h6>
                                                <div class="row g-4">
                                                    <div class="col-md-12">
                                                        <label for="nama_aplikasi" class="form-label fw-bold">
                                                            App Name <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" 
                                                               class="form-control form-control-lg" 
                                                               id="nama_aplikasi" 
                                                               name="nama_aplikasi" 
                                                               placeholder="Example: Customer Feedback Form" 
                                                               required
                                                               style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 14px 18px;">
                                                        <small class="text-muted">This will be the main title of your application</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="url_slug" class="form-label fw-bold">
                                                            URL Slug <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="url_slug" 
                                                               name="url_slug" 
                                                               placeholder="customer-feedback-form" 
                                                               required 
                                                               style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 12px 16px;">
                                                        <div class="mt-2 p-3 rounded" style="background: #f0f9ff; border-left: 4px solid #0ea5e9;">
                                                            <small class="text-muted">
                                                                <i class="fas fa-link me-1"></i>
                                                                Your app URL: <code class="bg-white px-2 py-1 rounded">/apps/<span id="slugPreview" style="color: #0ea5e9; font-weight: 600;">slug-anda</span></code>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="id_kategori" class="form-label fw-bold">
                                                            Category <span class="text-danger">*</span>
                                                        </label>
                                                        <select class="form-select" 
                                                                id="id_kategori" 
                                                                name="id_kategori" 
                                                                required
                                                                style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 12px 16px;">
                                                            <option value="">-- Select Category --</option>
                                                            <?php foreach ($kategoriList as $kat): ?>
                                                                <option value="<?php echo (int) $kat['id_kategori']; ?>">
                                                                    <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small class="text-muted">
                                                            <i class="fas fa-folder me-1"></i>
                                                            Internal, External or Shared
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Tips -->
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                                                    <div class="card-body text-center p-4">
                                                        <i class="fas fa-lightbulb mb-3" style="font-size: 32px; color: #f59e0b;"></i>
                                                        <h6 class="fw-bold">Quick Start</h6>
                                                        <p class="small mb-0" style="color: #78350f;">Use drag & drop to build forms instantly</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
                                                    <div class="card-body text-center p-4">
                                                        <i class="fas fa-mobile-alt mb-3" style="font-size: 32px; color: #0ea5e9;"></i>
                                                        <h6 class="fw-bold">Mobile Ready</h6>
                                                        <p class="small mb-0" style="color: #075985;">Apps work perfectly on all devices</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
                                                    <div class="card-body text-center p-4">
                                                        <i class="fas fa-bolt mb-3" style="font-size: 32px; color: #10b981;"></i>
                                                        <h6 class="fw-bold">Instant Deploy</h6>
                                                        <p class="small mb-0" style="color: #065f46;">Publish and share in seconds</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 2: Data (Fields) - VISUAL BUILDER ala AppSheet/Glide/Bubble -->
                            <div class="tab-pane fade" id="content-medan" role="tabpanel" data-wizard-step="2">
                                <!-- Toolbar -->
                                <div class="builder-toolbar">
                                    <div class="builder-toolbar-left">
                                        <button type="button" class="builder-toolbar-btn active" id="btnShowComponents">
                                            <i class="fas fa-th-large"></i>
                                            <span>Components</span>
                                        </button>
                                        <button type="button" class="builder-toolbar-btn" id="btnShowProperties">
                                            <i class="fas fa-sliders-h"></i>
                                            <span>Properties</span>
                                        </button>
                                    </div>
                                    <div class="builder-toolbar-center">
                                        <div class="builder-preview-device-btns">
                                            <button class="builder-preview-device-btn" data-device="mobile" title="Mobile View">
                                                <i class="fas fa-mobile-alt"></i>
                                            </button>
                                            <button class="builder-preview-device-btn active" data-device="desktop" title="Desktop View">
                                                <i class="fas fa-desktop"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="builder-toolbar-right">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenImportExcelModal" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Import Excel
                                        </button>
                                    </div>
                                </div>

                                <!-- 3-Column Layout -->
                                <div class="builder-workspace">
                                    <!-- LEFT SIDEBAR: Components Library -->
                                    <div class="builder-sidebar builder-sidebar-left" id="sidebarComponents">
                                        <div class="builder-sidebar-content">
                                            <div class="builder-section">
                                                <div class="builder-section-title">
                                                    <i class="fas fa-cube me-2"></i>
                                                    Basic Fields
                                                </div>
                                                <div class="builder-components-grid" id="basicFieldsGrid">
                                                    <!-- JS populated -->
                                                </div>
                                            </div>

                                            <div class="builder-section">
                                                <div class="builder-section-title">
                                                    <i class="fas fa-star me-2"></i>
                                                    Advanced
                                                </div>
                                                <div class="builder-components-grid" id="advancedFieldsGrid">
                                                    <!-- JS populated -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Properties Panel (hidden by default) -->
                                    <div class="builder-sidebar builder-sidebar-left" id="sidebarProperties" style="display: none;">
                                        <div class="builder-sidebar-content">
                                            <div class="builder-section">
                                                <div class="builder-section-title">
                                                    <i class="fas fa-edit me-2"></i>
                                                    Field Properties
                                                </div>
                                                <div id="propertiesPanel">
                                                    <div class="builder-empty-state">
                                                        <i class="fas fa-hand-pointer"></i>
                                                        <p>Select a field to edit properties</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CENTER: Canvas -->
                                    <div class="builder-canvas-container">
                                        <div class="builder-canvas-header">
                                            <div class="builder-canvas-title">
                                                <i class="fas fa-mobile-alt me-2"></i>
                                                <span id="canvasAppName">Form Builder</span>
                                            </div>
                                            <div class="builder-canvas-badge">
                                                <span id="fieldCount">0</span> fields
                                            </div>
                                        </div>
                                        <div class="builder-canvas" id="builderCanvas">
                                            <div class="builder-drop-zone" id="dynamicFieldsContainer">
                                                <div class="builder-drop-zone-empty" id="dropZoneEmpty">
                                                    <div class="builder-drop-zone-empty-icon">
                                                        <i class="fas fa-magic"></i>
                                                    </div>
                                                    <div class="builder-drop-zone-empty-title">
                                                        Start Building Your Form
                                                    </div>
                                                    <div class="builder-drop-zone-empty-text">
                                                        Drag components from the left sidebar
                                                    </div>
                                                    <div class="builder-drop-zone-empty-hint">
                                                        or double-click any component to add
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT SIDEBAR: Live Preview -->
                                    <div class="builder-sidebar builder-sidebar-right">
                                        <div class="builder-sidebar-content">
                                            <div class="builder-preview-header">
                                                <div class="builder-section-title">
                                                    <i class="fas fa-eye me-2"></i>
                                                    Live Preview
                                                </div>
                                            </div>
                                            <div class="builder-preview-frame desktop" id="previewFrame">
                                                <div class="builder-preview-phone">
                                                    <div class="builder-preview-notch"></div>
                                                    <div class="builder-preview-content" id="previewContent">
                                                        <div class="builder-empty-state">
                                                            <i class="fas fa-eye"></i>
                                                            <p>Live preview will appear here</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 3: STYLING & THEMES (NEW!) -->
                            <div class="tab-pane fade" id="content-styling" role="tabpanel" data-wizard-step="3">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <!-- Theme Selection -->
                                        <div class="card mb-4">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="fas fa-palette me-2"></i>Choose Theme</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="builder-theme-grid" id="themeGrid">
                                                    <div class="builder-theme-card active" data-theme="modern-blue">
                                                        <div class="builder-theme-preview" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                                        <div class="builder-theme-info">
                                                            <div class="builder-theme-name">Modern Blue</div>
                                                            <div class="builder-theme-badge"><i class="fas fa-check-circle"></i> Default</div>
                                                        </div>
                                                    </div>
                                                    <div class="builder-theme-card" data-theme="ocean-green">
                                                        <div class="builder-theme-preview" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"></div>
                                                        <div class="builder-theme-info">
                                                            <div class="builder-theme-name">Ocean Green</div>
                                                        </div>
                                                    </div>
                                                    <div class="builder-theme-card" data-theme="sunset-orange">
                                                        <div class="builder-theme-preview" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);"></div>
                                                        <div class="builder-theme-info">
                                                            <div class="builder-theme-name">Sunset Orange</div>
                                                        </div>
                                                    </div>
                                                    <div class="builder-theme-card" data-theme="royal-purple">
                                                        <div class="builder-theme-preview" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);"></div>
                                                        <div class="builder-theme-info">
                                                            <div class="builder-theme-name">Royal Purple</div>
                                                        </div>
                                                    </div>
                                                    <div class="builder-theme-card" data-theme="midnight-dark">
                                                        <div class="builder-theme-preview" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);"></div>
                                                        <div class="builder-theme-info">
                                                            <div class="builder-theme-name">Midnight Dark</div>
                                                        </div>
                                                    </div>
                                                    <div class="builder-theme-card" data-theme="cherry-red">
                                                        <div class="builder-theme-preview" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);"></div>
                                                        <div class="builder-theme-info">
                                                            <div class="builder-theme-name">Cherry Red</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Color Customization -->
                                        <div class="card mb-4">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="fas fa-fill-drip me-2"></i>Custom Colors</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-bold">Primary Color</label>
                                                        <input type="color" class="form-control form-control-color" id="colorPrimary" value="#667eea">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-bold">Secondary Color</label>
                                                        <input type="color" class="form-control form-control-color" id="colorSecondary" value="#10b981">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-bold">Accent Color</label>
                                                        <input type="color" class="form-control form-control-color" id="colorAccent" value="#f59e0b">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Font Selection -->
                                        <div class="card mb-4">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="fas fa-font me-2"></i>Typography</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold">Font Family</label>
                                                        <select class="form-select" id="fontFamily">
                                                            <option value="Inter">Inter (Default)</option>
                                                            <option value="Roboto">Roboto</option>
                                                            <option value="Poppins">Poppins</option>
                                                            <option value="Montserrat">Montserrat</option>
                                                            <option value="Open Sans">Open Sans</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold">Border Radius</label>
                                                        <select class="form-select" id="borderRadius">
                                                            <option value="8px">Small (8px)</option>
                                                            <option value="12px" selected>Medium (12px)</option>
                                                            <option value="16px">Large (16px)</option>
                                                            <option value="24px">Extra Large (24px)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Style Preview -->
                                    <div class="col-lg-4">
                                        <div class="card sticky-top" style="top: 20px;">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Style Preview</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="builder-style-preview" id="stylePreview">
                                                    <div class="preview-header">
                                                        <div class="preview-title">Your App</div>
                                                    </div>
                                                    <div class="preview-content">
                                                        <div class="preview-field">
                                                            <label>Sample Field</label>
                                                            <input type="text" placeholder="Enter text...">
                                                        </div>
                                                        <div class="preview-button-group">
                                                            <button class="preview-btn preview-btn-primary">Primary Button</button>
                                                            <button class="preview-btn preview-btn-secondary">Secondary</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Pages (layout pilih dalam setiap page) -->
                            <div class="tab-pane fade" id="content-design" role="tabpanel" data-wizard-step="4">
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-file-alt me-1"></i> Pages (pilihan)</h6>
                                        <button type="button" class="btn btn-primary btn-sm" id="btnTambahPage"><i class="fas fa-plus me-1"></i> Tambah Page</button>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Tambah halaman (Dashboard, Senarai, Borang, Kalendar). Kosongkan = guna lalai.</p>
                                        <div id="pagesContainer"></div>
                                        <p class="text-muted small mb-0 mt-2" id="pagesHint">Tiada page. Klik "Tambah Page" atau biar kosong.</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 5: Features - ENHANCED with Beautiful Cards -->
                            <div class="tab-pane fade" id="content-settings" role="tabpanel" data-wizard-step="5">
                                <div class="row justify-content-center">
                                    <div class="col-lg-10">
                                        <!-- Feature Cards Grid -->
                                        <div class="card mb-4 border-0 shadow-sm">
                                            <div class="card-header bg-light border-0">
                                                <h6 class="mb-0 fw-bold" style="color: #667eea;">
                                                    <i class="fas fa-magic me-2"></i>
                                                    Enable Features
                                                </h6>
                                            </div>
                                            <div class="card-body p-4">
                                                <p class="text-muted small mb-4">Select features to enable for your app</p>
                                                <div class="row g-3">
                                                    <!-- Export Excel -->
                                                    <div class="col-md-6 col-lg-3">
                                                        <label class="builder-feature-card">
                                                            <input type="checkbox" class="builder-feature-checkbox" id="setting_enable_export_excel" name="setting_enable_export_excel" value="1">
                                                            <div class="builder-feature-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                                                <i class="fas fa-file-excel"></i>
                                                            </div>
                                                            <div class="builder-feature-name">Export Excel</div>
                                                            <div class="builder-feature-desc">Download data as spreadsheet</div>
                                                        </label>
                                                    </div>
                                                    
                                                    <!-- Search -->
                                                    <div class="col-md-6 col-lg-3">
                                                        <label class="builder-feature-card">
                                                            <input type="checkbox" class="builder-feature-checkbox" id="setting_enable_search" name="setting_enable_search" value="1">
                                                            <div class="builder-feature-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                                                                <i class="fas fa-search"></i>
                                                            </div>
                                                            <div class="builder-feature-name">Search</div>
                                                            <div class="builder-feature-desc">Filter and find records</div>
                                                        </label>
                                                    </div>
                                                    
                                                    <!-- CRUD -->
                                                    <div class="col-md-6 col-lg-3">
                                                        <label class="builder-feature-card">
                                                            <input type="checkbox" class="builder-feature-checkbox" id="setting_enable_edit_delete" name="setting_enable_edit_delete" value="1">
                                                            <div class="builder-feature-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                                                                <i class="fas fa-edit"></i>
                                                            </div>
                                                            <div class="builder-feature-name">Edit & Delete</div>
                                                            <div class="builder-feature-desc">Full CRUD operations</div>
                                                        </label>
                                                    </div>
                                                    
                                                    <!-- Dashboard -->
                                                    <div class="col-md-6 col-lg-3">
                                                        <label class="builder-feature-card">
                                                            <input type="checkbox" class="builder-feature-checkbox" id="setting_enable_dashboard" name="setting_enable_dashboard" value="1">
                                                            <div class="builder-feature-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                                                                <i class="fas fa-chart-pie"></i>
                                                            </div>
                                                            <div class="builder-feature-name">Dashboard</div>
                                                            <div class="builder-feature-desc">Charts and analytics</div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Automation & Notifications -->
                                        <div class="card mb-4 border-0 shadow-sm">
                                            <div class="card-header bg-light border-0">
                                                <h6 class="mb-0 fw-bold" style="color: #667eea;">
                                                    <i class="fas fa-bolt me-2"></i>
                                                    Automation & Workflows
                                                </h6>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="row g-4">
                                                    <div class="col-md-12">
                                                        <label for="setting_notification_email" class="form-label fw-bold">
                                                            <i class="fas fa-envelope me-2"></i>
                                                            Email Notification
                                                        </label>
                                                        <input type="email" 
                                                               class="form-control" 
                                                               id="setting_notification_email" 
                                                               name="setting_notification_email" 
                                                               placeholder="admin@example.com"
                                                               style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 12px 16px;">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Receive email when new record is submitted
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ready to Publish -->
                                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
                                            <div class="card-body text-center py-4">
                                                <i class="fas fa-check-circle mb-3" style="font-size: 48px; color: #10b981;"></i>
                                                <h5 class="fw-bold mb-2" style="color: #065f46;">Ready to Launch!</h5>
                                                <p class="mb-0" style="color: #047857;">Click "Simpan Aplikasi" to publish your no-code app</p>
                                            </div>
                                        </div>

                                        <!-- Import Excel Notice -->
                                        <div class="alert alert-info border-0 shadow-sm mt-3" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-info-circle me-3 mt-1" style="font-size: 20px; color: #1e40af;"></i>
                                                <div>
                                                    <h6 class="fw-bold mb-2" style="color: #1e3a8a;">💡 Nak Import dari Excel?</h6>
                                                    <p class="mb-2 small" style="color: #1e40af;">
                                                        Untuk cipta aplikasi dari fail Excel (auto-extract fields dari header), 
                                                        gunakan <strong>Wizard Builder</strong> yang lebih sesuai.
                                                    </p>
                                                    <a href="wizard.php" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-magic me-1"></i> Buka Wizard Builder
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="metadata_json" id="metadata_json" value="{}">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-top pt-3">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" id="wizardPrevBtn" style="display: none;"><i class="fas fa-chevron-left me-1"></i> Sebelumnya</button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="wizardNextBtn"><span class="btn-text">Seterusnya</span> <i class="fas fa-chevron-right ms-1"></i></button>
                                <button type="submit" class="btn btn-success" id="btnSimpanAplikasi" style="display: none;"><i class="fas fa-save me-1"></i> Simpan Aplikasi</button>
                                <button type="button" class="btn btn-outline-secondary" id="btnBatalBuilder" data-bs-toggle="modal" data-bs-target="#modalBatalBuilder">Batal</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Muat naik Excel (kekal dalam builder) -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-labelledby="modalImportExcelLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalImportExcelLabel"><i class="fas fa-file-excel me-2"></i> Muat naik Excel / CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Fail <strong>.xlsx</strong>, <strong>.xls</strong> atau <strong>.csv</strong>. Baris pertama = header. Metadata dijana automatik.</p>
                <div id="modalImportFormSection">
                    <form id="modalImportExcelForm" method="post" enctype="multipart/form-data" action="import_excel.php">
                        <?php echo getCsrfTokenField(); ?>
                        <input type="hidden" name="use_stream" value="1">
                        <div class="mb-3">
                            <label for="modalImportIdKategori" class="form-label">Kategori</label>
                            <select class="form-select" id="modalImportIdKategori" name="id_kategori">
                                <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?php echo (int) $kat['id_kategori']; ?>"><?php echo htmlspecialchars($kat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fail (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-4 text-center import-modal-dropzone" id="modalImportDropZone" style="cursor:pointer; border-style: dashed;">
                                <input type="file" class="d-none" id="modalImportFile" name="file" accept=".xlsx,.xls,.csv" required>
                                <div class="text-muted mb-1"><i class="fas fa-cloud-upload-alt fa-2x"></i></div>
                                <div id="modalImportZoneText">Seret fail ke sini atau klik untuk pilih</div>
                                <small class="text-muted">Baris pertama = header</small>
                            </div>
                        </div>
                        <div id="modalImportProgressSection" class="d-none mb-3">
                            <label class="form-label">Status</label>
                            <div class="progress" style="height: 24px;">
                                <div id="modalImportProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                            </div>
                            <p id="modalImportProgressMessage" class="small text-muted mt-1 mb-0"></p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary" id="modalImportBtnSubmit" disabled><i class="fas fa-upload me-1"></i> Muat naik & Import</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </form>
                </div>
                <div id="modalImportSuccessSection" class="d-none text-center py-3">
                    <div class="mb-3"><i class="fas fa-check-circle text-success fa-3x"></i></div>
                    <h6 class="text-success">Import selesai</h6>
                    <p id="modalImportSuccessMessage" class="text-muted small mb-2"></p>
                    <p class="text-muted small mb-3">Rekod telah diproses automatik ke jadual (data disimpan sebagai JSON). Aplikasi baharu telah dicipta. Klik <strong>Pergi ke App</strong> untuk lihat senarai.</p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="#" id="modalImportBtnGoToApp" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-external-link-alt me-1"></i> Pergi ke App</a>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="modalImportBtnImportLain">Import fail lain</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Batal (kekal dalam builder) -->
<div class="modal fade" id="modalBatalBuilder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Batal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 small">Keluar dari builder dan kembali ke Dashboard Aplikasi?</p>
            </div>
            <div class="modal-footer">
                <a href="dashboard_aplikasi.php" class="btn btn-outline-secondary btn-sm">Ya, keluar</a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Tidak</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Pilih Ikon (untuk Page) -->
<div class="modal fade" id="modalIconPicker" tabindex="-1" aria-labelledby="modalIconPickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalIconPickerLabel"><i class="fas fa-icons me-2"></i> Pilih Ikon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Klik ikon untuk pilih. Font Awesome 5 Solid (fas).</p>
                <input type="text" class="form-control form-control-sm mb-3" id="modalIconPickerSearch" placeholder="Cari ikon (cth: user, chart, file)..." autocomplete="off">
                <div id="modalIconPickerGrid" class="row g-2" style="max-height: 60vh; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<script>
const LOOKUP_APPS = <?php echo json_encode($lookupApps); ?>;
(function() {
    // ═══════════════════════════════════════════════════════════════════════
    // ENHANCED FIELD TYPES - Basic + Advanced + Layout
    // ═══════════════════════════════════════════════════════════════════════
    const FIELD_TYPES = {
        basic: [
            { value: 'text', label: 'Text', icon: 'fas fa-font', desc: 'Single line' },
            { value: 'email', label: 'Email', icon: 'fas fa-envelope', desc: 'Email address' },
            { value: 'number', label: 'Number', icon: 'fas fa-hashtag', desc: 'Numeric input' },
            { value: 'tel', label: 'Phone', icon: 'fas fa-phone', desc: 'Phone number' },
            { value: 'date', label: 'Date', icon: 'fas fa-calendar', desc: 'Date picker' },
            { value: 'time', label: 'Time', icon: 'fas fa-clock', desc: 'Time picker' },
            { value: 'datetime-local', label: 'DateTime', icon: 'fas fa-calendar-alt', desc: 'Date & time' },
            { value: 'textarea', label: 'Long Text', icon: 'fas fa-align-left', desc: 'Multi-line' },
            { value: 'select', label: 'Dropdown', icon: 'fas fa-caret-square-down', desc: 'Select list' },
            { value: 'checkbox', label: 'Checkbox', icon: 'fas fa-check-square', desc: 'Multi choice' },
            { value: 'radio', label: 'Radio', icon: 'fas fa-dot-circle', desc: 'Single choice' },
            { value: 'lookup', label: 'Lookup', icon: 'fas fa-search', desc: 'From other app' },
            { value: 'ref', label: 'Reference', icon: 'fas fa-link', desc: 'App reference' }
        ],
        advanced: [
            { value: 'file', label: 'File Upload', icon: 'fas fa-file-upload', desc: 'Documents' },
            { value: 'image', label: 'Image', icon: 'fas fa-image', desc: 'Photo upload' },
            { value: 'url', label: 'URL', icon: 'fas fa-globe', desc: 'Website link' },
            { value: 'color', label: 'Color', icon: 'fas fa-palette', desc: 'Color picker' },
            { value: 'range', label: 'Slider', icon: 'fas fa-sliders-h', desc: 'Range slider' }
        ]
    };

    const INPUT_TYPES = [...FIELD_TYPES.basic, ...FIELD_TYPES.advanced];

    let fieldIndex = 0;
    let fieldsData = [];

    // ═══════════════════════════════════════════════════════════════════════
    // INITIALIZE COMPONENT GRIDS
    // ═══════════════════════════════════════════════════════════════════════
    function initComponentGrids() {
        const basicGrid = document.getElementById('basicFieldsGrid');
        const advancedGrid = document.getElementById('advancedFieldsGrid');
        
        if (!basicGrid || !advancedGrid) return;

        // Populate Basic Fields
        FIELD_TYPES.basic.forEach(fieldType => {
            const card = createComponentCard(fieldType);
            basicGrid.appendChild(card);
        });

        // Populate Advanced Fields
        FIELD_TYPES.advanced.forEach(fieldType => {
            const card = createComponentCard(fieldType);
            advancedGrid.appendChild(card);
        });
    }

    function createComponentCard(fieldType) {
        const card = document.createElement('div');
        card.className = 'builder-field-type-card';
        card.draggable = true;
        card.dataset.fieldType = fieldType.value;
        
        const badgeHtml = fieldType.badge ? `<span class="builder-field-type-badge">${fieldType.badge}</span>` : '';
        
        card.innerHTML = `
            ${badgeHtml}
            <div class="builder-field-type-icon"><i class="${fieldType.icon}"></i></div>
            <div class="builder-field-type-name">${fieldType.label}</div>
        `;

        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        
        // Double click to add
        card.addEventListener('dblclick', () => {
            addFieldFromPalette(fieldType.value);
        });

        return card;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOOLBAR SWITCHING
    // ═══════════════════════════════════════════════════════════════════════
    function setupToolbar() {
        const btnShowComponents = document.getElementById('btnShowComponents');
        const btnShowProperties = document.getElementById('btnShowProperties');
        const sidebarComponents = document.getElementById('sidebarComponents');
        const sidebarProperties = document.getElementById('sidebarProperties');

        if (!btnShowComponents || !btnShowProperties) return;

        btnShowComponents.addEventListener('click', () => {
            btnShowComponents.classList.add('active');
            btnShowProperties.classList.remove('active');
            sidebarComponents.style.display = 'block';
            sidebarProperties.style.display = 'none';
        });

        btnShowProperties.addEventListener('click', () => {
            btnShowProperties.classList.add('active');
            btnShowComponents.classList.remove('active');
            sidebarProperties.style.display = 'block';
            sidebarComponents.style.display = 'none';
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DRAG & DROP HANDLERS
    // ═══════════════════════════════════════════════════════════════════════
    function handleDragStart(e) {
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('fieldType', e.target.dataset.fieldType);
        e.target.classList.add('dragging');
    }

    function handleDragEnd(e) {
        e.target.classList.remove('dragging');
    }

    function setupDropZone() {
        const dropZone = document.getElementById('dynamicFieldsContainer');
        if (!dropZone) return;

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', e => {
            if (e.target === dropZone) {
                dropZone.classList.remove('drag-over');
            }
        });

        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            
            const fieldType = e.dataTransfer.getData('fieldType');
            if (fieldType) {
                addFieldFromPalette(fieldType);
            }
        });

        // Make fields sortable
        new Sortable(dropZone, {
            animation: 150,
            handle: '.field-drag-handle, .dynamic-field-row',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            filter: '.builder-drop-zone-empty',
            onEnd: function() {
                rebuildFieldsDataFromDOM();
                updatePreview();
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADD FIELD FROM PALETTE
    // ═══════════════════════════════════════════════════════════════════════
    function addFieldFromPalette(fieldType) {
        const fieldDef = INPUT_TYPES.find(f => f.value === fieldType);
        if (!fieldDef) return;

        const fieldData = {
            index: fieldIndex++,
            type: fieldType,
            label: fieldDef.label,
            name: `field_${fieldIndex}`,
            required: false,
            placeholder: '',
            options: (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') ? ['Option 1', 'Option 2', 'Option 3'] : [],
            lookup_app_id: '',
            lookup_field: ''
        };

        fieldsData.push(fieldData);
        renderFieldInCanvas(fieldData);
        updateDropZoneEmpty();
        updateFieldCount();
        updatePreview();
        showToast('✓ Added: ' + fieldDef.label, 'success');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RENDER FIELD IN CANVAS
    // ═══════════════════════════════════════════════════════════════════════
    function renderFieldInCanvas(fieldData) {
        const container = document.getElementById('dynamicFieldsContainer');
        const row = buildFieldRow(fieldData);
        container.appendChild(row);
    }

    function updateDropZoneEmpty() {
        const empty = document.getElementById('dropZoneEmpty');
        const container = document.getElementById('dynamicFieldsContainer');
        const hasFields = container.querySelectorAll('.dynamic-field-row').length > 0;
        
        if (empty) {
            empty.style.display = hasFields ? 'none' : 'block';
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE LIVE PREVIEW
    // ═══════════════════════════════════════════════════════════════════════
    function updatePreview() {
        const previewContent = document.getElementById('previewContent');
        if (!previewContent) return;

        if (fieldsData.length === 0) {
            previewContent.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #adb5bd;">
                    <i class="fas fa-eye" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px;"></i>
                    <p style="font-size: 14px; margin: 0;">Preview akan muncul di sini</p>
                </div>
            `;
            return;
        }

        const appName = document.getElementById('nama_aplikasi').value || 'Aplikasi Anda';
        let html = `<h5 style="font-size: 20px; font-weight: 700; margin-bottom: 24px; color: #212529;">${appName}</h5>`;

        fieldsData.forEach(field => {
            html += `
                <div class="builder-preview-field">
                    <label class="builder-preview-label">
                        ${field.label}
                        ${field.required ? '<span class="required">*</span>' : ''}
                    </label>
                    ${renderPreviewInput(field)}
                </div>
            `;
        });

        html += `<button class="builder-preview-submit">Hantar</button>`;
        previewContent.innerHTML = html;
    }

    function renderPreviewInput(field) {
        switch (field.type) {
            case 'textarea':
                return `<textarea class="builder-preview-textarea" rows="3" placeholder="${field.placeholder || field.label}"></textarea>`;
            case 'select':
                return `<select class="builder-preview-select"><option>-- Pilih --</option>${field.options.map(opt => `<option>${opt}</option>`).join('')}</select>`;
            case 'checkbox':
                return field.options.map(opt => `<label style="display: block; margin-bottom: 8px;"><input type="checkbox"> ${opt}</label>`).join('');
            case 'radio':
                return field.options.map(opt => `<label style="display: block; margin-bottom: 8px;"><input type="radio" name="${field.name}"> ${opt}</label>`).join('');
            case 'file':
            case 'image':
                return `<input type="file" class="builder-preview-input">`;
            case 'color':
                return `<input type="color" class="builder-preview-input" style="height: 50px;">`;
            case 'range':
                return `<input type="range" class="builder-preview-input" min="0" max="100">`;
            default:
                return `<input type="${field.type}" class="builder-preview-input" placeholder="${field.placeholder || field.label}">`;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REBUILD FIELDS DATA FROM DOM (after drag reorder)
    // ═══════════════════════════════════════════════════════════════════════
    function rebuildFieldsDataFromDOM() {
        const rows = document.querySelectorAll('.dynamic-field-row');
        const newData = [];
        
        rows.forEach(row => {
            const index = parseInt(row.dataset.index);
            const field = fieldsData.find(f => f.index === index);
            if (field) {
                // Update from inputs
                const labelEl = row.querySelector('.field-label');
                const typeEl = row.querySelector('.field-type');
                const reqEl = row.querySelector('.field-required');
                
                if (labelEl) field.label = labelEl.value.trim() || field.label;
                if (typeEl) field.type = typeEl.value;
                if (reqEl) field.required = reqEl.checked;
                
                newData.push(field);
            }
        });
        
        fieldsData = newData;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TOAST NOTIFICATION
    // ═══════════════════════════════════════════════════════════════════════
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `builder-toast ${type}`;
        toast.innerHTML = `
            <div class="builder-toast-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            </div>
            <div>${message}</div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DEVICE PREVIEW SWITCHER
    // ═══════════════════════════════════════════════════════════════════════
    function setupDeviceSwitcher() {
        document.querySelectorAll('.builder-preview-device-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.builder-preview-device-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const device = this.dataset.device;
                const frame = document.getElementById('previewFrame');
                frame.className = `builder-preview-frame ${device}`;
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPDATE FIELD COUNT BADGE
    // ═══════════════════════════════════════════════════════════════════════
    function updateFieldCount() {
        const badge = document.getElementById('fieldCount');
        if (badge) {
            badge.textContent = fieldsData.length;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SYNC APP NAME
    // ═══════════════════════════════════════════════════════════════════════
    document.getElementById('nama_aplikasi').addEventListener('input', function() {
        const canvasAppName = document.getElementById('canvasAppName');
        if (canvasAppName) {
            canvasAppName.textContent = this.value || 'Form Builder';
        }
        updatePreview();
    });

    // Slug preview
    document.getElementById('url_slug').addEventListener('input', function() {
        const v = (this.value || 'slug-anda').trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
        document.getElementById('slugPreview').textContent = v || 'slug-anda';
    });

    // Slug: no spaces, lowercase
    document.getElementById('url_slug').addEventListener('blur', function() {
        this.value = this.value.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
    });

    // --- Stepped Wizard (EXPANDED to 5 Steps!) ---
    var wizardStepNames = ['Asas', 'Data', 'Styling', 'Pages', 'Features'];
    var currentWizardStep = 1;
    var totalWizardSteps = 5;

    function showWizardStep(step) {
        console.log('showWizardStep called with step:', step);
        
        step = Math.max(1, Math.min(totalWizardSteps, step));
        currentWizardStep = step;
        
        console.log('Showing wizard step:', step);
        
        // Update content panes
        document.querySelectorAll('#builderTabContent .tab-pane').forEach(function(pane) {
            var paneStep = parseInt(pane.getAttribute('data-wizard-step'), 10);
            console.log('Checking pane with step:', paneStep, 'against', step);
            if (paneStep === step) {
                pane.classList.add('show', 'active');
                pane.classList.remove('fade');
                console.log('Activated pane:', pane.id);
            } else {
                pane.classList.remove('show', 'active');
                pane.classList.add('fade');
            }
        });
        
        // Update wizard step indicators
        document.querySelectorAll('.builder-wizard-step').forEach(function(stepEl) {
            var s = parseInt(stepEl.getAttribute('data-step'), 10);
            stepEl.classList.remove('active', 'completed');
            if (s === step) {
                stepEl.classList.add('active');
            } else if (s < step) {
                stepEl.classList.add('completed');
            }
        });
        
        // Update text
        var stepNum = document.getElementById('wizardStepNum');
        if (stepNum) stepNum.textContent = step;
        var stepTitle = document.getElementById('wizardStepTitle');
        if (stepTitle) {
            const titles = ['Maklumat Asas', 'Build Form Fields', 'Customize Styling', 'Configure Pages', 'Enable Features'];
            stepTitle.textContent = titles[step - 1] || wizardStepNames[step - 1];
        }
        
        // Update navigation buttons
        var prevBtn = document.getElementById('wizardPrevBtn');
        var nextBtn = document.getElementById('wizardNextBtn');
        var simpanBtn = document.getElementById('btnSimpanAplikasi');
        if (prevBtn) prevBtn.style.display = step === 1 ? 'none' : 'inline-block';
        if (nextBtn) { 
            nextBtn.style.display = step === totalWizardSteps ? 'none' : 'inline-block'; 
        }
        if (simpanBtn) simpanBtn.style.display = step === totalWizardSteps ? 'inline-block' : 'none';
        
        // Initialize step-specific features
        if (step === 2 && !builderInitialized) {
            setTimeout(initBuilder, 100);
        }
        if (step === 3) {
            setTimeout(initStylingStep, 100);
        }
    }

    document.getElementById('wizardNextBtn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Next button clicked, current step:', currentWizardStep);
        
        // Validate Step 1 before proceeding
        if (currentWizardStep === 1) {
            const namaApp = document.getElementById('nama_aplikasi');
            const urlSlug = document.getElementById('url_slug');
            const kategori = document.getElementById('id_kategori');
            
            if (!namaApp || !namaApp.value.trim()) {
                alert('Sila masukkan Nama Aplikasi');
                if (namaApp) namaApp.focus();
                return;
            }
            
            if (!urlSlug || !urlSlug.value.trim()) {
                alert('Sila masukkan URL Slug');
                if (urlSlug) urlSlug.focus();
                return;
            }
            
            if (!kategori || !kategori.value) {
                alert('Sila pilih Kategori');
                if (kategori) kategori.focus();
                return;
            }
        }
        
        if (currentWizardStep < totalWizardSteps) {
            console.log('Moving to step:', currentWizardStep + 1);
            showWizardStep(currentWizardStep + 1);
        }
    });
    
    document.getElementById('wizardPrevBtn').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Prev button clicked, current step:', currentWizardStep);
        
        if (currentWizardStep > 1) {
            console.log('Moving to step:', currentWizardStep - 1);
            showWizardStep(currentWizardStep - 1);
        }
    });
    
    // Click on visual wizard steps
    document.querySelectorAll('.builder-wizard-step').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var s = parseInt(this.getAttribute('data-step'), 10);
            if (s) showWizardStep(s);
        });
    });

    function slugifyLabel(label) {
        return (label || 'field').trim()
            .toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^a-z0-9_]/g, '');
    }

    function buildFieldRow(fieldData) {
        const row = document.createElement('div');
        row.className = 'dynamic-field-row';
        row.dataset.index = fieldData.index;
        
        const fieldDef = INPUT_TYPES.find(f => f.value === fieldData.type) || INPUT_TYPES[0];
        const lookupOptionsHtml = (LOOKUP_APPS || []).map(function(a) {
            return '<option value=\"' + a.id + '\">' + a.label.replace(/\"/g, '&quot;') + '</option>';
        }).join('');
        
        const showLookupConfig = fieldData.type === 'lookup' || fieldData.type === 'ref';
        const showOptionsConfig = fieldData.type === 'select' || fieldData.type === 'radio' || fieldData.type === 'checkbox';
        
        row.innerHTML = `
            <div class="field-drag-handle" title="Drag to reorder">
                <i class="fas fa-grip-vertical"></i>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">
                        <i class="${fieldDef.icon} me-1"></i>Label Field
                    </label>
                    <input type="text" class="form-control form-control-sm field-label" 
                           placeholder="Contoh: Nama Penuh" 
                           data-index="${fieldData.index}"
                           value="${fieldData.label}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Jenis Input</label>
                    <select class="form-select form-select-sm field-type" data-index="${fieldData.index}">
                        ${INPUT_TYPES.map(t => '<option value="' + t.value + '"' + (t.value === fieldData.type ? ' selected' : '') + '>' + t.label + '</option>').join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input field-required" type="checkbox" 
                               id="req_${fieldData.index}" 
                               data-index="${fieldData.index}"
                               ${fieldData.required ? 'checked' : ''}>
                        <label class="form-check-label small" for="req_${fieldData.index}">Wajib</label>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-outline-primary btn-sm me-1" 
                            onclick="duplicateField(${fieldData.index})" title="Duplicate">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-field" 
                            data-index="${fieldData.index}" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
            
            ${showOptionsConfig ? `
            <div class="row g-2 mt-2">
                <div class="col-12">
                    <label class="form-label small fw-bold">Options (comma separated)</label>
                    <input type="text" class="form-control form-control-sm field-options" 
                           placeholder="Option 1, Option 2, Option 3" 
                           data-index="${fieldData.index}"
                           value="${fieldData.options.join(', ')}">
                </div>
            </div>` : ''}
            
            ${showLookupConfig ? `
            <div class="row g-2 mt-2 lookup-ref-config">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Aplikasi Sumber</label>
                    <select class="form-select form-select-sm field-lookup-app" data-index="${fieldData.index}">
                        <option value="">-- Pilih Aplikasi --</option>
                        ${lookupOptionsHtml}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Field Paparan</label>
                    <input type="text" class="form-control form-control-sm field-lookup-field" 
                           placeholder="Contoh: nama_peralatan" 
                           data-index="${fieldData.index}"
                           value="${fieldData.lookup_field}">
                </div>
            </div>` : ''}
        `;
        
        // Event listeners
        setTimeout(() => {
            const labelInput = row.querySelector('.field-label');
            const typeSelect = row.querySelector('.field-type');
            const reqCheck = row.querySelector('.field-required');
            const optionsInput = row.querySelector('.field-options');
            const removeBtn = row.querySelector('.btn-remove-field');
            
            if (labelInput) {
                labelInput.addEventListener('input', () => {
                    rebuildFieldsDataFromDOM();
                    updatePreview();
                });
            }
            
            if (typeSelect) {
                typeSelect.addEventListener('change', () => {
                    rebuildFieldsDataFromDOM();
                    updatePreview();
                    // Re-render to show/hide config sections
                    setTimeout(() => {
                        const container = document.getElementById('dynamicFieldsContainer');
                        row.remove();
                        const updatedField = fieldsData.find(f => f.index === fieldData.index);
                        if (updatedField) {
                            const newRow = buildFieldRow(updatedField);
                            container.appendChild(newRow);
                        }
                    }, 10);
                });
            }
            
            if (reqCheck) {
                reqCheck.addEventListener('change', () => {
                    rebuildFieldsDataFromDOM();
                    updatePreview();
                });
            }
            
            if (optionsInput) {
                optionsInput.addEventListener('input', () => {
                    const field = fieldsData.find(f => f.index === fieldData.index);
                    if (field) {
                        field.options = optionsInput.value.split(',').map(o => o.trim()).filter(o => o);
                        updatePreview();
                    }
                });
            }
            
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    if (confirm('Delete field: ' + fieldData.label + '?')) {
                        row.remove();
                        fieldsData = fieldsData.filter(f => f.index !== fieldData.index);
                        updateDropZoneEmpty();
                        updateFieldCount();
                        updatePreview();
                        showToast('✓ Field deleted', 'success');
                    }
                });
            }
        }, 10);
        
        return row;
    }
    
    // Duplicate field function
    window.duplicateField = function(index) {
        const field = fieldsData.find(f => f.index === index);
        if (!field) return;
        
        const newField = {
            ...field,
            index: fieldIndex++,
            name: `field_${fieldIndex}`,
            label: field.label + ' (Copy)'
        };
        
        fieldsData.push(newField);
        renderFieldInCanvas(newField);
        updateFieldCount();
        updatePreview();
        showToast('✓ Field duplicated', 'success');
    };

    function collectMetadata() {
        // Rebuild from DOM first to get latest values
        rebuildFieldsDataFromDOM();
        
        const meta = [];
        fieldsData.forEach(function(field) {
            if (!field.label || field.label.trim() === '') return;
            
            const name = slugifyLabel(field.label) || field.name;
            const item = {
                name: name,
                label: field.label,
                type: field.type,
                required: field.required
            };
            
            if (field.placeholder) item.placeholder = field.placeholder;
            
            if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
                if (field.options && field.options.length > 0) {
                    item.options = field.options;
                }
            }
            
            if (field.type === 'lookup' || field.type === 'ref') {
                if (field.lookup_app_id) item.lookup_app_id = field.lookup_app_id;
                if (field.lookup_field) item.lookup_field = field.lookup_field;
            }
            
            meta.push(item);
        });
        
        return meta;
    }

    function collectSettings() {
        var notificationEl = document.getElementById('setting_notification_email');
        return {
            enable_search: document.getElementById('setting_enable_search').checked,
            enable_export_excel: document.getElementById('setting_enable_export_excel').checked,
            enable_edit_delete: document.getElementById('setting_enable_edit_delete').checked,
            enable_dashboard: document.getElementById('setting_enable_dashboard').checked,
            notification_email: (notificationEl && notificationEl.value) ? notificationEl.value.trim() : ''
        };
    }

    function collectLayoutType() {
        return 'simple_list';
    }

    const PAGE_TYPES = [
        { value: 'dashboard', label: 'Dashboard', icon: 'fas fa-chart-pie' },
        { value: 'list', label: 'Senarai (cth: Senarai Aset)', icon: 'fas fa-list' },
        { value: 'form', label: 'Borang (cth: Borang Aduan)', icon: 'fas fa-plus-circle' },
        { value: 'calendar', label: 'Kalendar', icon: 'fas fa-calendar-alt' },
        { value: 'report', label: 'Report', icon: 'fas fa-file-alt' }
    ];

    var currentIconInputForPicker = null;
    // Senarai ikon Font Awesome 5 Free Solid (fas) - banyak pilihan untuk page/app
    const PAGE_ICON_LIST = [
        'fas fa-address-book', 'fas fa-address-card', 'fas fa-adjust', 'fas fa-align-center', 'fas fa-align-justify', 'fas fa-align-left', 'fas fa-align-right',
        'fas fa-ambulance', 'fas fa-anchor', 'fas fa-angle-double-down', 'fas fa-angle-double-left', 'fas fa-angle-double-right', 'fas fa-angle-double-up',
        'fas fa-angle-down', 'fas fa-angle-left', 'fas fa-angle-right', 'fas fa-angle-up', 'fas fa-archive', 'fas fa-arrow-alt-circle-down', 'fas fa-arrow-alt-circle-left',
        'fas fa-arrow-alt-circle-right', 'fas fa-arrow-alt-circle-up', 'fas fa-arrow-down', 'fas fa-arrow-left', 'fas fa-arrow-right', 'fas fa-arrow-up',
        'fas fa-arrows-alt', 'fas fa-arrows-alt-h', 'fas fa-arrows-alt-v', 'fas fa-asterisk', 'fas fa-at', 'fas fa-award', 'fas fa-backspace', 'fas fa-ban',
        'fas fa-barcode', 'fas fa-bars', 'fas fa-battery-empty', 'fas fa-battery-full', 'fas fa-battery-half', 'fas fa-battery-quarter', 'fas fa-battery-three-quarters',
        'fas fa-bed', 'fas fa-beer', 'fas fa-bell', 'fas fa-bell-slash', 'fas fa-bicycle', 'fas fa-binoculars', 'fas fa-birthday-cake', 'fas fa-bold', 'fas fa-bolt',
        'fas fa-bomb', 'fas fa-book', 'fas fa-book-open', 'fas fa-bookmark', 'fas fa-box', 'fas fa-box-open', 'fas fa-boxes', 'fas fa-briefcase', 'fas fa-briefcase-medical',
        'fas fa-broadcast-tower', 'fas fa-broom', 'fas fa-bug', 'fas fa-building', 'fas fa-bullhorn', 'fas fa-bullseye', 'fas fa-bus', 'fas fa-calculator',
        'fas fa-calendar', 'fas fa-calendar-alt', 'fas fa-calendar-check', 'fas fa-calendar-minus', 'fas fa-calendar-plus', 'fas fa-calendar-times', 'fas fa-camera',
        'fas fa-camera-retro', 'fas fa-capsules', 'fas fa-car', 'fas fa-caret-down', 'fas fa-caret-left', 'fas fa-caret-right', 'fas fa-caret-square-down',
        'fas fa-caret-square-left', 'fas fa-caret-square-right', 'fas fa-caret-square-up', 'fas fa-caret-up', 'fas fa-cart-arrow-down', 'fas fa-cart-plus',
        'fas fa-certificate', 'fas fa-chalkboard', 'fas fa-chalkboard-teacher', 'fas fa-chart-area', 'fas fa-chart-bar', 'fas fa-chart-line', 'fas fa-chart-pie',
        'fas fa-check', 'fas fa-check-circle', 'fas fa-check-double', 'fas fa-check-square', 'fas fa-chess', 'fas fa-chess-bishop', 'fas fa-chess-board', 'fas fa-chess-king',
        'fas fa-chess-knight', 'fas fa-chess-pawn', 'fas fa-chess-queen', 'fas fa-chess-rook', 'fas fa-chevron-circle-down', 'fas fa-chevron-circle-left',
        'fas fa-chevron-circle-right', 'fas fa-chevron-circle-up', 'fas fa-chevron-down', 'fas fa-chevron-left', 'fas fa-chevron-right', 'fas fa-chevron-up',
        'fas fa-child', 'fas fa-circle', 'fas fa-circle-notch', 'fas fa-clipboard', 'fas fa-clipboard-check', 'fas fa-clipboard-list', 'fas fa-clock', 'fas fa-clone',
        'fas fa-closed-captioning', 'fas fa-cloud', 'fas fa-cloud-download-alt', 'fas fa-cloud-upload-alt', 'fas fa-code', 'fas fa-code-branch', 'fas fa-coffee',
        'fas fa-cog', 'fas fa-cogs', 'fas fa-coins', 'fas fa-columns', 'fas fa-comment', 'fas fa-comment-alt', 'fas fa-comment-dots', 'fas fa-comment-medical',
        'fas fa-comment-slash', 'fas fa-comments', 'fas fa-compact-disc', 'fas fa-compass', 'fas fa-compress', 'fas fa-compress-alt', 'fas fa-compress-arrows-alt',
        'fas fa-concierge-bell', 'fas fa-cookie', 'fas fa-cookie-bite', 'fas fa-copy', 'fas fa-copyright', 'fas fa-couch', 'fas fa-credit-card', 'fas fa-crop',
        'fas fa-crop-alt', 'fas fa-cross', 'fas fa-crosshairs', 'fas fa-crow', 'fas fa-crown', 'fas fa-crutch', 'fas fa-cube', 'fas fa-cubes', 'fas fa-cut',
        'fas fa-database', 'fas fa-deaf', 'fas fa-desktop', 'fas fa-diagnoses', 'fas fa-dice', 'fas fa-dice-five', 'fas fa-dice-four', 'fas fa-dice-one',
        'fas fa-dice-six', 'fas fa-dice-three', 'fas fa-dice-two', 'fas fa-digital-tachograph', 'fas fa-directions', 'fas fa-divide', 'fas fa-dizzy', 'fas fa-dna',
        'fas fa-dollar-sign', 'fas fa-dolly', 'fas fa-dolly-flatbed', 'fas fa-donate', 'fas fa-door-closed', 'fas fa-door-open', 'fas fa-dot-circle', 'fas fa-dove',
        'fas fa-download', 'fas fa-drafting-compass', 'fas fa-dragon', 'fas fa-draw-polygon', 'fas fa-drum', 'fas fa-drum-steelpan', 'fas fa-dumbbell', 'fas fa-edit',
        'fas fa-eject', 'fas fa-ellipsis-h', 'fas fa-ellipsis-v', 'fas fa-envelope', 'fas fa-envelope-open', 'fas fa-envelope-open-text', 'fas fa-envelope-square',
        'fas fa-equals', 'fas fa-eraser', 'fas fa-euro-sign', 'fas fa-exchange-alt', 'fas fa-exclamation', 'fas fa-exclamation-circle', 'fas fa-exclamation-triangle',
        'fas fa-expand', 'fas fa-expand-alt', 'fas fa-expand-arrows-alt', 'fas fa-external-link-alt', 'fas fa-external-link-square-alt', 'fas fa-eye', 'fas fa-eye-dropper',
        'fas fa-eye-slash', 'fas fa-fast-backward', 'fas fa-fast-forward', 'fas fa-fax', 'fas fa-feather', 'fas fa-feather-alt', 'fas fa-female', 'fas fa-fighter-jet',
        'fas fa-file', 'fas fa-file-alt', 'fas fa-file-archive', 'fas fa-file-audio', 'fas fa-file-code', 'fas fa-file-contract', 'fas fa-file-csv', 'fas fa-file-download',
        'fas fa-file-excel', 'fas fa-file-export', 'fas fa-file-image', 'fas fa-file-import', 'fas fa-file-invoice', 'fas fa-file-invoice-dollar', 'fas fa-file-medical',
        'fas fa-file-medical-alt', 'fas fa-file-pdf', 'fas fa-file-powerpoint', 'fas fa-file-prescription', 'fas fa-file-signature', 'fas fa-file-upload', 'fas fa-file-video',
        'fas fa-file-word', 'fas fa-fill', 'fas fa-fill-drip', 'fas fa-film', 'fas fa-filter', 'fas fa-fingerprint', 'fas fa-fire', 'fas fa-fire-alt', 'fas fa-fire-extinguisher',
        'fas fa-first-aid', 'fas fa-fish', 'fas fa-fist-raised', 'fas fa-flag', 'fas fa-flag-checkered', 'fas fa-flag-usa', 'fas fa-flask', 'fas fa-folder', 'fas fa-folder-minus',
        'fas fa-folder-open', 'fas fa-folder-plus', 'fas fa-font', 'fas fa-football-ball', 'fas fa-forward', 'fas fa-frog', 'fas fa-frown', 'fas fa-frown-open',
        'fas fa-funnel-dollar', 'fas fa-futbol', 'fas fa-gamepad', 'fas fa-gas-pump', 'fas fa-gavel', 'fas fa-gem', 'fas fa-genderless', 'fas fa-ghost', 'fas fa-gift',
        'fas fa-gifts', 'fas fa-glass-martini', 'fas fa-glass-martini-alt', 'fas fa-glass-cheers', 'fas fa-glasses', 'fas fa-globe', 'fas fa-globe-africa', 'fas fa-globe-americas',
        'fas fa-globe-asia', 'fas fa-globe-europe', 'fas fa-graduation-cap', 'fas fa-greater-than', 'fas fa-greater-than-equal', 'fas fa-grip-horizontal', 'fas fa-grip-lines',
        'fas fa-grip-lines-vertical', 'fas fa-grip-vertical', 'fas fa-guitar', 'fas fa-h-square', 'fas fa-hammer', 'fas fa-hamsa', 'fas fa-hand-holding', 'fas fa-hand-holding-heart',
        'fas fa-hand-holding-usd', 'fas fa-hand-lizard', 'fas fa-hand-paper', 'fas fa-hand-peace', 'fas fa-hand-point-down', 'fas fa-hand-point-left', 'fas fa-hand-point-right',
        'fas fa-hand-point-up', 'fas fa-hand-pointer', 'fas fa-hand-rock', 'fas fa-hand-scissors', 'fas fa-hand-spock', 'fas fa-hands', 'fas fa-hands-helping', 'fas fa-handshake',
        'fas fa-hdd', 'fas fa-heading', 'fas fa-headphones', 'fas fa-headphones-alt', 'fas fa-headset', 'fas fa-heart', 'fas fa-heart-broken', 'fas fa-helicopter', 'fas fa-highlighter',
        'fas fa-hiking', 'fas fa-hippo', 'fas fa-history', 'fas fa-hockey-puck', 'fas fa-home', 'fas fa-horse', 'fas fa-horse-head', 'fas fa-hospital', 'fas fa-hospital-alt',
        'fas fa-hospital-symbol', 'fas fa-hot-tub', 'fas fa-hotel', 'fas fa-hourglass', 'fas fa-hourglass-end', 'fas fa-hourglass-half', 'fas fa-hourglass-start', 'fas fa-house-damage',
        'fas fa-i-cursor', 'fas fa-id-badge', 'fas fa-id-card', 'fas fa-id-card-alt', 'fas fa-image', 'fas fa-images', 'fas fa-inbox', 'fas fa-indent', 'fas fa-industry',
        'fas fa-infinity', 'fas fa-info', 'fas fa-info-circle', 'fas fa-italic', 'fas fa-key', 'fas fa-keyboard', 'fas fa-kiss', 'fas fa-kiss-beam', 'fas fa-kiss-wink-heart',
        'fas fa-kiwi-bird', 'fas fa-landmark', 'fas fa-language', 'fas fa-laptop', 'fas fa-laptop-code', 'fas fa-laptop-medical', 'fas fa-laugh', 'fas fa-laugh-beam',
        'fas fa-laugh-squint', 'fas fa-laugh-wink', 'fas fa-layer-group', 'fas fa-leaf', 'fas fa-lemon', 'fas fa-less-than', 'fas fa-less-than-equal', 'fas fa-level-down-alt',
        'fas fa-level-up-alt', 'fas fa-life-ring', 'fas fa-lightbulb', 'fas fa-link', 'fas fa-lira-sign', 'fas fa-list', 'fas fa-list-alt', 'fas fa-list-ol', 'fas fa-list-ul',
        'fas fa-location-arrow', 'fas fa-lock', 'fas fa-lock-open', 'fas fa-long-arrow-alt-down', 'fas fa-long-arrow-alt-left', 'fas fa-long-arrow-alt-right', 'fas fa-long-arrow-alt-up',
        'fas fa-low-vision', 'fas fa-luggage-cart', 'fas fa-magic', 'fas fa-magnet', 'fas fa-mail-bulk', 'fas fa-male', 'fas fa-map', 'fas fa-map-marked', 'fas fa-map-marked-alt',
        'fas fa-map-marker', 'fas fa-map-marker-alt', 'fas fa-map-pin', 'fas fa-map-signs', 'fas fa-marker', 'fas fa-mars', 'fas fa-mars-double', 'fas fa-mars-stroke',
        'fas fa-mars-stroke-h', 'fas fa-mars-stroke-v', 'fas fa-mask', 'fas fa-medal', 'fas fa-medkit', 'fas fa-meh', 'fas fa-meh-blank', 'fas fa-meh-rolling-eyes', 'fas fa-memory',
        'fas fa-menorah', 'fas fa-mercury', 'fas fa-meteor', 'fas fa-microchip', 'fas fa-microphone', 'fas fa-microphone-alt', 'fas fa-microphone-alt-slash', 'fas fa-microphone-slash',
        'fas fa-microscope', 'fas fa-minus', 'fas fa-minus-circle', 'fas fa-minus-square', 'fas fa-mitten', 'fas fa-mobile', 'fas fa-mobile-alt', 'fas fa-money-bill',
        'fas fa-money-bill-alt', 'fas fa-money-bill-wave', 'fas fa-money-bill-wave-alt', 'fas fa-money-check', 'fas fa-money-check-alt', 'fas fa-monument', 'fas fa-moon',
        'fas fa-mortar-pestle', 'fas fa-mosque', 'fas fa-motorcycle', 'fas fa-mountain', 'fas fa-mouse-pointer', 'fas fa-mug-hot', 'fas fa-music', 'fas fa-network-wired',
        'fas fa-neuter', 'fas fa-newspaper', 'fas fa-not-equal', 'fas fa-notes-medical', 'fas fa-object-group', 'fas fa-object-ungroup', 'fas fa-oil-can', 'fas fa-om',
        'fas fa-otter', 'fas fa-outdent', 'fas fa-pager', 'fas fa-paint-brush', 'fas fa-paint-roller', 'fas fa-palette', 'fas fa-pallet', 'fas fa-paper-plane', 'fas fa-paperclip',
        'fas fa-parachute-box', 'fas fa-paragraph', 'fas fa-parking', 'fas fa-passport', 'fas fa-pastafarianism', 'fas fa-paste', 'fas fa-pause', 'fas fa-pause-circle',
        'fas fa-paw', 'fas fa-peace', 'fas fa-pen', 'fas fa-pen-alt', 'fas fa-pen-fancy', 'fas fa-pen-nib', 'fas fa-pen-square', 'fas fa-pencil-alt', 'fas fa-people-carry',
        'fas fa-percent', 'fas fa-percentage', 'fas fa-person-booth', 'fas fa-phone', 'fas fa-phone-alt', 'fas fa-phone-slash', 'fas fa-phone-square', 'fas fa-phone-square-alt',
        'fas fa-phone-volume', 'fas fa-photo-video', 'fas fa-piggy-bank', 'fas fa-pills', 'fas fa-place-of-worship', 'fas fa-plane', 'fas fa-plane-arrival', 'fas fa-plane-departure',
        'fas fa-play', 'fas fa-play-circle', 'fas fa-plug', 'fas fa-plus', 'fas fa-plus-circle', 'fas fa-plus-square', 'fas fa-podcast', 'fas fa-poll', 'fas fa-poll-h',
        'fas fa-poo', 'fas fa-poo-storm', 'fas fa-portrait', 'fas fa-pound-sign', 'fas fa-power-off', 'fas fa-pray', 'fas fa-praying-hands', 'fas fa-prescription',
        'fas fa-prescription-bottle', 'fas fa-prescription-bottle-alt', 'fas fa-print', 'fas fa-procedures', 'fas fa-project-diagram', 'fas fa-puzzle-piece', 'fas fa-quran',
        'fas fa-radiation', 'fas fa-radiation-alt', 'fas fa-rainbow', 'fas fa-random', 'fas fa-receipt', 'fas fa-record-video', 'fas fa-recycle', 'fas fa-redo', 'fas fa-redo-alt',
        'fas fa-registered', 'fas fa-remove-format', 'fas fa-reply', 'fas fa-reply-all', 'fas fa-restroom', 'fas fa-retweet', 'fas fa-ribbon', 'fas fa-ring', 'fas fa-road',
        'fas fa-robot', 'fas fa-rocket', 'fas fa-route', 'fas fa-rss', 'fas fa-rss-square', 'fas fa-ruble-sign', 'fas fa-ruler', 'fas fa-ruler-combined', 'fas fa-ruler-horizontal',
        'fas fa-ruler-vertical', 'fas fa-running', 'fas fa-rupee-sign', 'fas fa-sad-cry', 'fas fa-sad-tear', 'fas fa-satellite', 'fas fa-satellite-dish', 'fas fa-save',
        'fas fa-school', 'fas fa-screwdriver', 'fas fa-scroll', 'fas fa-sd-card', 'fas fa-search', 'fas fa-search-dollar', 'fas fa-search-location', 'fas fa-search-minus',
        'fas fa-search-plus', 'fas fa-seedling', 'fas fa-server', 'fas fa-shapes', 'fas fa-share', 'fas fa-share-alt', 'fas fa-share-alt-square', 'fas fa-share-square',
        'fas fa-shekel-sign', 'fas fa-shield-alt', 'fas fa-ship', 'fas fa-shipping-fast', 'fas fa-shoe-prints', 'fas fa-shopping-bag', 'fas fa-shopping-basket', 'fas fa-shopping-cart',
        'fas fa-shower', 'fas fa-shuttle-van', 'fas fa-sign', 'fas fa-sign-in-alt', 'fas fa-sign-language', 'fas fa-sign-out-alt', 'fas fa-signal', 'fas fa-signature',
        'fas fa-sim-card', 'fas fa-sitemap', 'fas fa-skating', 'fas fa-skiing', 'fas fa-skiing-nordic', 'fas fa-skull', 'fas fa-skull-crossbones', 'fas fa-slash', 'fas fa-sliders-h',
        'fas fa-smile', 'fas fa-smile-beam', 'fas fa-smile-wink', 'fas fa-smog', 'fas fa-smoking', 'fas fa-smoking-ban', 'fas fa-sms', 'fas fa-snowflake', 'fas fa-socks',
        'fas fa-solar-panel', 'fas fa-sort', 'fas fa-sort-alpha-down', 'fas fa-sort-alpha-down-alt', 'fas fa-sort-alpha-up', 'fas fa-sort-alpha-up-alt', 'fas fa-sort-amount-down',
        'fas fa-sort-amount-down-alt', 'fas fa-sort-amount-up', 'fas fa-sort-amount-up-alt', 'fas fa-sort-down', 'fas fa-sort-numeric-down', 'fas fa-sort-numeric-down-alt',
        'fas fa-sort-numeric-up', 'fas fa-sort-numeric-up-alt', 'fas fa-sort-up', 'fas fa-spa', 'fas fa-space-shuttle', 'fas fa-spell-check', 'fas fa-spider', 'fas fa-spinner',
        'fas fa-splotch', 'fas fa-spray-can', 'fas fa-square', 'fas fa-square-full', 'fas fa-square-root-alt', 'fas fa-stamp', 'fas fa-star', 'fas fa-star-and-crescent',
        'fas fa-star-half', 'fas fa-star-half-alt', 'fas fa-star-of-life', 'fas fa-step-backward', 'fas fa-step-forward', 'fas fa-stethoscope', 'fas fa-sticky-note', 'fas fa-stop',
        'fas fa-stop-circle', 'fas fa-stopwatch', 'fas fa-store', 'fas fa-store-alt', 'fas fa-stream', 'fas fa-street-view', 'fas fa-strikethrough', 'fas fa-stroopwafel',
        'fas fa-subscript', 'fas fa-subway', 'fas fa-suitcase', 'fas fa-suitcase-rolling', 'fas fa-sun', 'fas fa-superscript', 'fas fa-surprise', 'fas fa-swatchbook',
        'fas fa-swimmer', 'fas fa-synagogue', 'fas fa-sync', 'fas fa-sync-alt', 'fas fa-syringe', 'fas fa-table', 'fas fa-table-tennis', 'fas fa-tablet', 'fas fa-tablet-alt',
        'fas fa-tablets', 'fas fa-tachometer-alt', 'fas fa-tag', 'fas fa-tags', 'fas fa-tape', 'fas fa-tasks', 'fas fa-taxi', 'fas fa-teeth', 'fas fa-teeth-open',
        'fas fa-temperature-high', 'fas fa-temperature-low', 'fas fa-tenge', 'fas fa-terminal', 'fas fa-text-height', 'fas fa-text-width', 'fas fa-th', 'fas fa-th-large', 'fas fa-th-list',
        'fas fa-theater-masks', 'fas fa-thermometer', 'fas fa-thermometer-empty', 'fas fa-thermometer-full', 'fas fa-thermometer-half', 'fas fa-thermometer-quarter', 'fas fa-thumbs-down',
        'fas fa-thumbs-up', 'fas fa-thumbtack', 'fas fa-ticket-alt', 'fas fa-times', 'fas fa-times-circle', 'fas fa-tint', 'fas fa-tint-slash', 'fas fa-tired', 'fas fa-toggle-off',
        'fas fa-toggle-on', 'fas fa-toilet-paper', 'fas fa-toolbox', 'fas fa-tooth', 'fas fa-torah', 'fas fa-torii-gate', 'fas fa-tractor', 'fas fa-trademark', 'fas fa-traffic-light',
        'fas fa-train', 'fas fa-tram', 'fas fa-transgender', 'fas fa-transgender-alt', 'fas fa-trash', 'fas fa-trash-alt', 'fas fa-trash-restore', 'fas fa-trash-restore-alt',
        'fas fa-tree', 'fas fa-trophy', 'fas fa-truck', 'fas fa-truck-loading', 'fas fa-truck-monster', 'fas fa-truck-moving', 'fas fa-tshirt', 'fas fa-tty', 'fas fa-tv',
        'fas fa-umbrella', 'fas fa-umbrella-beach', 'fas fa-underline', 'fas fa-undo', 'fas fa-undo-alt', 'fas fa-universal-access', 'fas fa-university', 'fas fa-unlink',
        'fas fa-unlock', 'fas fa-unlock-alt', 'fas fa-upload', 'fas fa-user', 'fas fa-user-alt', 'fas fa-user-alt-slash', 'fas fa-user-astronaut', 'fas fa-user-check',
        'fas fa-user-circle', 'fas fa-user-clock', 'fas fa-user-cog', 'fas fa-user-edit', 'fas fa-user-friends', 'fas fa-user-graduate', 'fas fa-user-injured', 'fas fa-user-lock',
        'fas fa-user-md', 'fas fa-user-minus', 'fas fa-user-ninja', 'fas fa-user-plus', 'fas fa-user-secret', 'fas fa-user-shield', 'fas fa-user-slash', 'fas fa-user-tag',
        'fas fa-user-tie', 'fas fa-users', 'fas fa-users-cog', 'fas fa-users-slash', 'fas fa-utensil-spoon', 'fas fa-utensils', 'fas fa-vector-square', 'fas fa-venus',
        'fas fa-venus-double', 'fas fa-venus-mars', 'fas fa-vial', 'fas fa-vials', 'fas fa-video', 'fas fa-video-slash', 'fas fa-vihara', 'fas fa-voicemail', 'fas fa-volleyball-ball',
        'fas fa-volume-down', 'fas fa-volume-mute', 'fas fa-volume-off', 'fas fa-volume-up', 'fas fa-vote-yea', 'fas fa-vr-cardboard', 'fas fa-walking', 'fas fa-wallet',
        'fas fa-warehouse', 'fas fa-water', 'fas fa-weight', 'fas fa-weight-hanging', 'fas fa-wheelchair', 'fas fa-wifi', 'fas fa-wind', 'fas fa-window-close', 'fas fa-window-maximize',
        'fas fa-window-minimize', 'fas fa-window-restore', 'fas fa-wine-bottle', 'fas fa-wine-glass', 'fas fa-wine-glass-alt', 'fas fa-won-sign', 'fas fa-wrench', 'fas fa-x-ray',
        'fas fa-yen-sign', 'fas fa-yin-yang'
    ];

    function slugifyPageId(str) {
        return (str || '').trim().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_-]/g, '') || 'page';
    }

    let pageIndex = 0;

    function buildPageRow(index, data) {
        data = data || {};
        const row = document.createElement('div');
        row.className = 'page-row border rounded p-3 mb-3 bg-light';
        row.dataset.pageIndex = index;
        const typeOptions = PAGE_TYPES.map(function(t) {
            return '<option value="' + t.value + '" data-icon="' + (t.icon || '') + '">' + t.label + '</option>';
        }).join('');
        row.innerHTML = `
            <div class="row g-2 align-items-center">
                <div class="col-md-2 col-lg-1">
                    <label class="form-label small">Page</label>
                    <span class="page-order-badge badge bg-secondary align-middle">-</span>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Nama Halaman</label>
                    <input type="text" class="form-control form-control-sm page-name" placeholder="cth: Senarai Aset, Borang Aduan" data-page-index="${index}" value="${(data.label || '').replace(/"/g, '&quot;')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Jenis</label>
                    <select class="form-select form-select-sm page-type" data-page-index="${index}">
                        ${typeOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ikon</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm page-icon" placeholder="fas fa-list" data-page-index="${index}" value="${(data.icon || '').replace(/"/g, '&quot;')}" readonly>
                        <button type="button" class="btn btn-outline-secondary btn-pick-icon" data-page-index="${index}" title="Pilih ikon"><i class="fas fa-icons"></i></button>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#pageConfig-${index}" title="Tetapan halaman: Kalendar (medan tarikh), Senarai (layout jadual/kad)"><i class="fas fa-cog"></i></button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-page" data-page-index="${index}" title="Buang"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
            <div class="page-config collapse mt-2" id="pageConfig-${index}" data-page-index="${index}">
                <div class="card card-body bg-white">
                    <h6 class="small text-muted mb-2">Tetapan halaman (pilihan)</h6>
                    <p class="small text-muted mb-2">Jika <strong>Jenis = Kalendar</strong>: isi medan tarikh mula/tamat dan tajuk. Jika <strong>Jenis = Senarai</strong>: pilih paparan Jadual atau Kad.</p>
                    <div class="page-config-list"></div>
                    <div class="page-config-calendar d-none">
                        <div class="row g-2">
                            <div class="col-md-4"><label class="form-label small">Medan Tarikh Mula</label><input type="text" class="form-control form-control-sm config-calendar-start" placeholder="nama_field"></div>
                            <div class="col-md-4"><label class="form-label small">Medan Tarikh Tamat</label><input type="text" class="form-control form-control-sm config-calendar-end" placeholder="nama_field"></div>
                            <div class="col-md-4"><label class="form-label small">Medan Tajuk</label><input type="text" class="form-control form-control-sm config-calendar-title" placeholder="nama_field"></div>
                        </div>
                    </div>
                    <div class="page-config-list-layout d-none">
                        <label class="form-label small">Layout senarai</label>
                        <select class="form-select form-select-sm config-list-layout">
                            <option value="simple_list">Simple List (Jadual)</option>
                            <option value="card_view">Card View</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
        if (data.type) {
            const sel = row.querySelector('.page-type');
            if (sel) sel.value = data.type;
        }
        if (data.config) {
            const cfg = data.config;
            if (cfg.startField) row.querySelector('.config-calendar-start').value = cfg.startField;
            if (cfg.endField) row.querySelector('.config-calendar-end').value = cfg.endField;
            if (cfg.titleField) row.querySelector('.config-calendar-title').value = cfg.titleField;
            if (cfg.layout_type) row.querySelector('.config-list-layout').value = cfg.layout_type;
        }
        return row;
    }

    function updatePageOrderBadges() {
        document.querySelectorAll('.page-row').forEach(function(row, i) {
            const badge = row.querySelector('.page-order-badge');
            if (badge) badge.textContent = (i + 1);
        });
    }

    function togglePageConfigByType(row) {
        const typeSel = row.querySelector('.page-type');
        const configBlock = row.querySelector('.page-config');
        const calBlock = row.querySelector('.page-config-calendar');
        const listLayoutBlock = row.querySelector('.page-config-list-layout');
        const type = typeSel ? typeSel.value : '';
        if (calBlock) calBlock.classList.add('d-none');
        if (listLayoutBlock) listLayoutBlock.classList.add('d-none');
        if (type === 'calendar' && calBlock) calBlock.classList.remove('d-none');
        if (type === 'list' && listLayoutBlock) listLayoutBlock.classList.remove('d-none');
    }

    function collectPages() {
        const rows = document.querySelectorAll('.page-row');
        const seen = {};
        const pages = [];
        rows.forEach(function(row, i) {
            const nameEl = row.querySelector('.page-name');
            const typeEl = row.querySelector('.page-type');
            const iconEl = row.querySelector('.page-icon');
            const label = (nameEl && nameEl.value) ? nameEl.value.trim() : '';
            const type = (typeEl && typeEl.value) ? typeEl.value : 'list';
            let id = slugifyPageId(label);
            if (!id || id === 'page') id = type + '_' + i;
            if (seen[id]) {
                let n = 1;
                while (seen[id + '_' + n]) n++;
                id = id + '_' + n;
            }
            seen[id] = true;
            const icon = (iconEl && iconEl.value) ? iconEl.value.trim() : (PAGE_TYPES.find(function(t) { return t.value === type; }) || {}).icon || 'fas fa-circle';
            const page = { id: id, type: type, label: label || (typeEl && typeEl.options[typeEl.selectedIndex] ? typeEl.options[typeEl.selectedIndex].text : type), icon: icon };
            if (type === 'calendar') {
                const startF = row.querySelector('.config-calendar-start');
                const endF = row.querySelector('.config-calendar-end');
                const titleF = row.querySelector('.config-calendar-title');
                if (startF && startF.value.trim()) page.config = page.config || {};
                if (page.config) {
                    if (startF && startF.value.trim()) page.config.startField = startF.value.trim();
                    if (endF && endF.value.trim()) page.config.endField = endF.value.trim();
                    if (titleF && titleF.value.trim()) page.config.titleField = titleF.value.trim();
                }
            }
            if (type === 'list') {
                const layoutSel = row.querySelector('.config-list-layout');
                if (layoutSel && layoutSel.value && layoutSel.value !== 'simple_list') {
                    page.config = page.config || {};
                    page.config.layout_type = layoutSel.value;
                }
            }
            pages.push(page);
        });
        return pages;
    }

    function buildFullMetadata() {
        return {
            fields: collectMetadata(),
            settings: collectSettings(),
            layout_type: collectLayoutType(),
            pages: collectPages()
        };
    }

    function updateHiddenMetadata() {
        document.getElementById('metadata_json').value = JSON.stringify(buildFullMetadata());
    }

    (function initIconPickerModal() {
        var grid = document.getElementById('modalIconPickerGrid');
        var searchInput = document.getElementById('modalIconPickerSearch');
        if (!grid) return;
        PAGE_ICON_LIST.forEach(function(iconClass) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm p-2 icon-picker-item';
            btn.setAttribute('data-icon', iconClass);
            btn.title = iconClass;
            btn.innerHTML = '<i class="' + iconClass + '"></i>';
            grid.appendChild(btn);
        });
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var q = (this.value || '').trim().toLowerCase().replace(/\s+/g, ' ');
                grid.querySelectorAll('.icon-picker-item').forEach(function(btn) {
                    var iconClass = (btn.getAttribute('data-icon') || '').toLowerCase();
                    var show = !q || iconClass.indexOf(q) !== -1;
                    btn.style.display = show ? '' : 'none';
                });
            });
        }
        document.getElementById('modalIconPicker').addEventListener('show.bs.modal', function() {
            if (searchInput) { searchInput.value = ''; searchInput.dispatchEvent(new Event('input')); }
        });
    })();
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-pick-icon');
        if (btn) {
            var row = btn.closest('.page-row');
            var inp = row ? row.querySelector('.page-icon') : null;
            if (inp) {
                currentIconInputForPicker = inp;
                var modalEl = document.getElementById('modalIconPicker');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }
        }
    });
    document.getElementById('modalIconPickerGrid').addEventListener('click', function(e) {
        var btn = e.target.closest('[data-icon]');
        if (btn && currentIconInputForPicker) {
            currentIconInputForPicker.value = btn.getAttribute('data-icon');
            var modalEl = document.getElementById('modalIconPicker');
            if (modalEl && typeof bootstrap !== 'undefined') {
                var m = bootstrap.Modal.getInstance(modalEl);
                if (m) m.hide();
            }
            updateHiddenMetadata();
        }
    });

    // ═══════════════════════════════════════════════════════════════════════
    // INITIALIZE BUILDER ON STEP 2
    // ═══════════════════════════════════════════════════════════════════════
    let builderInitialized = false;
    
    function initBuilder() {
        if (builderInitialized) return;
        
        initComponentGrids();
        setupToolbar();
        setupDropZone();
        setupDeviceSwitcher();
        updateFieldCount();
        
        builderInitialized = true;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STEP 3: STYLING & THEMES
    // ═══════════════════════════════════════════════════════════════════════
    let stylingInitialized = false;
    let currentTheme = {
        primary: '#667eea',
        secondary: '#10b981',
        accent: '#f59e0b',
        font: 'Inter',
        radius: '12px'
    };

    function initStylingStep() {
        if (stylingInitialized) return;
        
        // Theme selection
        document.querySelectorAll('.builder-theme-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.builder-theme-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                
                const theme = this.dataset.theme;
                applyTheme(theme);
            });
        });
        
        // Color pickers
        const colorPrimary = document.getElementById('colorPrimary');
        const colorSecondary = document.getElementById('colorSecondary');
        const colorAccent = document.getElementById('colorAccent');
        
        if (colorPrimary) {
            colorPrimary.addEventListener('input', function() {
                currentTheme.primary = this.value;
                updateStylePreview();
            });
        }
        
        if (colorSecondary) {
            colorSecondary.addEventListener('input', function() {
                currentTheme.secondary = this.value;
                updateStylePreview();
            });
        }
        
        if (colorAccent) {
            colorAccent.addEventListener('input', function() {
                currentTheme.accent = this.value;
                updateStylePreview();
            });
        }
        
        // Font and radius
        const fontFamily = document.getElementById('fontFamily');
        const borderRadius = document.getElementById('borderRadius');
        
        if (fontFamily) {
            fontFamily.addEventListener('change', function() {
                currentTheme.font = this.value;
                updateStylePreview();
            });
        }
        
        if (borderRadius) {
            borderRadius.addEventListener('change', function() {
                currentTheme.radius = this.value;
                updateStylePreview();
            });
        }
        
        updateStylePreview();
        stylingInitialized = true;
    }

    function applyTheme(themeName) {
        const themes = {
            'modern-blue': { primary: '#667eea', secondary: '#10b981', accent: '#f59e0b' },
            'ocean-green': { primary: '#10b981', secondary: '#059669', accent: '#0891b2' },
            'sunset-orange': { primary: '#f59e0b', secondary: '#ea580c', accent: '#ef4444' },
            'royal-purple': { primary: '#8b5cf6', secondary: '#6d28d9', accent: '#a855f7' },
            'midnight-dark': { primary: '#1e293b', secondary: '#0f172a', accent: '#64748b' },
            'cherry-red': { primary: '#ef4444', secondary: '#dc2626', accent: '#f87171' }
        };
        
        const theme = themes[themeName] || themes['modern-blue'];
        currentTheme.primary = theme.primary;
        currentTheme.secondary = theme.secondary;
        currentTheme.accent = theme.accent;
        
        document.getElementById('colorPrimary').value = theme.primary;
        document.getElementById('colorSecondary').value = theme.secondary;
        document.getElementById('colorAccent').value = theme.accent;
        
        updateStylePreview();
    }

    function updateStylePreview() {
        const preview = document.getElementById('stylePreview');
        if (!preview) return;
        
        preview.style.setProperty('--preview-primary', `linear-gradient(135deg, ${currentTheme.primary} 0%, ${currentTheme.secondary} 100%)`);
        preview.style.setProperty('--preview-primary-color', currentTheme.primary);
        preview.style.setProperty('--preview-radius', currentTheme.radius);
        preview.style.fontFamily = currentTheme.font;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTO INITIALIZATION
    // ═══════════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Builder: DOMContentLoaded');
        
        // Pre-initialize components for Step 2
        setTimeout(function() {
            const basicGrid = document.getElementById('basicFieldsGrid');
            const container = document.getElementById('dynamicFieldsContainer');
            
            console.log('Builder: Checking elements', { basicGrid, container });
            
            if (basicGrid && container && !builderInitialized) {
                console.log('Builder: Initializing...');
                initComponentGrids();
                setupToolbar();
                setupDropZone();
                setupDeviceSwitcher();
                updateFieldCount();
                builderInitialized = true;
                console.log('Builder: Initialized successfully');
            }
        }, 500);
    });

    function addPageRow(data) {
        const container = document.getElementById('pagesContainer');
        const hint = document.getElementById('pagesHint');
        if (hint) hint.classList.add('d-none');
        const row = buildPageRow(pageIndex++, data);
        container.appendChild(row);

        const typeSel = row.querySelector('.page-type');
        const iconInp = row.querySelector('.page-icon');

        typeSel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const defaultIcon = opt && opt.getAttribute('data-icon');
            if (defaultIcon && (!iconInp.value || iconInp.value.trim() === '')) iconInp.value = defaultIcon;
            togglePageConfigByType(row);
            updateHiddenMetadata();
        });
        row.querySelector('.page-name').addEventListener('input', updateHiddenMetadata);
        row.querySelector('.page-name').addEventListener('change', updateHiddenMetadata);
        iconInp.addEventListener('input', updateHiddenMetadata);
        row.querySelectorAll('.config-calendar-start, .config-calendar-end, .config-calendar-title, .config-list-layout').forEach(function(el) {
            el.addEventListener('change', updateHiddenMetadata);
            el.addEventListener('input', updateHiddenMetadata);
        });
        togglePageConfigByType(row);

        row.querySelector('.btn-remove-page').addEventListener('click', function() {
            row.remove();
            updatePageOrderBadges();
            updateHiddenMetadata();
            if (!container.querySelector('.page-row')) {
                if (hint) hint.classList.remove('d-none');
            }
        });
        updatePageOrderBadges();
        updateHiddenMetadata();
    }

    document.getElementById('btnTambahPage').addEventListener('click', function() {
        addPageRow({});
    });

    // Default: satu setup page siap keluar bila sampai step Pages
    addPageRow({ label: 'Dashboard', type: 'dashboard', icon: 'fas fa-chart-pie' });

    // DEBUG: Add direct button click listener
    document.getElementById('btnSimpanAplikasi').addEventListener('click', function(e) {
        console.log('🔘 BUTTON SIMPAN CLICKED!', {
            buttonVisible: this.style.display,
            buttonDisabled: this.disabled,
            formExists: !!document.getElementById('builderForm'),
            eventType: e.type
        });
    });

    document.getElementById('builderForm').addEventListener('submit', function(e) {
        console.log('🔥 FORM SUBMIT EVENT FIRED!');
        e.preventDefault();
        console.log('🔥 Form submit triggered!');
        
        const nama = document.getElementById('nama_aplikasi').value.trim();
        const slug = document.getElementById('url_slug').value.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
        const idKategori = document.getElementById('id_kategori').value;
        
        console.log('📝 Form values:', { nama, slug, idKategori });
        
        if (!nama) {
            console.log('❌ Validation failed: Nama Aplikasi empty');
            showAlert('Sila isi Nama Aplikasi.', 'danger');
            return;
        }
        if (!slug) {
            console.log('❌ Validation failed: URL Slug empty');
            showAlert('Sila isi URL Slug (tanpa ruang, huruf kecil sahaja).', 'danger');
            return;
        }
        if (!idKategori) {
            console.log('❌ Validation failed: Kategori not selected');
            showAlert('Sila pilih Kategori.', 'danger');
            return;
        }
        
        console.log('✅ Basic validation passed');
        updateHiddenMetadata();
        const fullMeta = buildFullMetadata();
        console.log('📦 Metadata built:', fullMeta);
        
        // Validate fields - allow blank form tetapi beri warning
        if (!fullMeta.fields || fullMeta.fields.length === 0) {
            console.log('⚠️ No fields detected - showing confirm dialog');
            // Confirm dengan user jika mereka sengaja nak buat blank form
            const confirmBlank = confirm('Aplikasi ini tidak mempunyai medan borang. Adakah anda ingin terus publish sebagai borang kosong?\n\n' +
                'Klik OK untuk terus (borang kosong).\n' +
                'Klik Cancel untuk kembali dan tambah medan.');
            if (!confirmBlank) {
                console.log('❌ User cancelled blank form confirmation');
                return; // User cancel - kembali untuk tambah fields
            }
            console.log('✅ User confirmed blank form');
            // User OK - terus publish blank form
        } else {
            console.log('✅ Fields detected:', fullMeta.fields.length);
        }

        console.log('🚀 Starting fetch to builder_save.php...');
        const formData = new FormData(this);
        formData.set('nama_aplikasi', nama);
        formData.set('url_slug', slug);
        formData.set('metadata_json', JSON.stringify(fullMeta));

        const btn = document.getElementById('btnSimpanAplikasi');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        fetch('builder_save.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { 
            console.log('📡 Fetch response received:', r.status, r.statusText);
            return r.json().catch(function() { 
                console.log('❌ Failed to parse JSON response');
                return { success: false, message: 'Ralat tindak balas pelayan.' }; 
            }); 
        })
        .then(function(data) {
            console.log('📨 Server response:', data);
            if (data.success) {
                console.log('✅ Save successful!');
                showAlert('Aplikasi berjaya disimpan. Anda boleh akses di: /apps/' + slug, 'success');
                document.getElementById('builderForm').reset();
                document.getElementById('metadata_json').value = '{}';
                document.getElementById('slugPreview').textContent = 'slug-anda';
                document.querySelectorAll('.dynamic-field-row').forEach(function(el) { el.remove(); });
                fieldIndex = 0;
                const hint = document.getElementById('dynamicFieldsContainer').querySelector('.text-muted.small');
                if (hint) hint.classList.remove('d-none');
                document.querySelectorAll('.page-row').forEach(function(el) { el.remove(); });
                pageIndex = 0;
                const pagesHint = document.getElementById('pagesHint');
                if (pagesHint) pagesHint.classList.remove('d-none');
                addPageRow({ label: 'Dashboard', type: 'dashboard', icon: 'fas fa-chart-pie' });
            } else {
                console.log('❌ Server returned error:', data.message);
                showAlert(data.message || 'Gagal menyimpan aplikasi.', 'danger');
            }
        })
        .catch(function(err) {
            console.log('💥 Fetch error:', err);
            showAlert('Ralat rangkaian: ' + (err.message || 'Sila cuba lagi.'), 'danger');
        })
        .finally(function() {
            console.log('🏁 Fetch completed');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Aplikasi';
        });
    });

    function showAlert(message, type) {
        const el = document.getElementById('builderAlert');
        el.className = 'alert alert-' + type + ' alert-dismissible fade show';
        el.innerHTML = message + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        el.classList.remove('d-none');
    }

    // --- Modal Muat naik Excel (kekal dalam builder) ---
    (function() {
        var modal = document.getElementById('modalImportExcel');
        var form = document.getElementById('modalImportExcelForm');
        var fileInput = document.getElementById('modalImportFile');
        var dropZone = document.getElementById('modalImportDropZone');
        var zoneText = document.getElementById('modalImportZoneText');
        var progressSection = document.getElementById('modalImportProgressSection');
        var progressBar = document.getElementById('modalImportProgressBar');
        var progressMessage = document.getElementById('modalImportProgressMessage');
        var btnSubmit = document.getElementById('modalImportBtnSubmit');
        var formSection = document.getElementById('modalImportFormSection');
        var successSection = document.getElementById('modalImportSuccessSection');
        var successMessage = document.getElementById('modalImportSuccessMessage');
        var btnGoToApp = document.getElementById('modalImportBtnGoToApp');
        var btnImportLain = document.getElementById('modalImportBtnImportLain');

        function resetModalImportState() {
            formSection.classList.remove('d-none');
            successSection.classList.add('d-none');
            progressSection.classList.add('d-none');
            form.reset();
            fileInput.value = '';
            zoneText.textContent = 'Seret fail ke sini atau klik untuk pilih';
            dropZone.classList.remove('border-success', 'bg-success bg-opacity-10');
            btnSubmit.disabled = true;
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressBar.classList.remove('bg-danger', 'bg-success');
            progressBar.classList.add('progress-bar-animated');
        }

        if (modal) {
            modal.addEventListener('show.bs.modal', resetModalImportState);
            modal.addEventListener('hidden.bs.modal', resetModalImportState);
        }

        dropZone.addEventListener('click', function() { fileInput.click(); });
        fileInput.addEventListener('change', function() {
            var f = fileInput.files && fileInput.files[0];
            if (f) {
                zoneText.textContent = f.name;
                dropZone.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                btnSubmit.disabled = false;
            } else {
                zoneText.textContent = 'Seret fail ke sini atau klik untuk pilih';
                dropZone.classList.remove('border-success', 'bg-success', 'bg-opacity-10');
                btnSubmit.disabled = true;
            }
        });
        dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.style.borderColor = '#0d6efd'; });
        dropZone.addEventListener('dragleave', function() { dropZone.style.borderColor = ''; });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '';
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        btnImportLain.addEventListener('click', function() {
            resetModalImportState();
        });

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
                    var pct = Math.min(50, Math.round((ev.loaded / ev.total) * 50));
                    progressBar.style.width = pct + '%';
                    progressBar.textContent = pct + '%';
                    progressMessage.textContent = 'Muat naik: ' + ev.loaded + ' / ' + ev.total + ' bait';
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status !== 200) {
                    progressBar.classList.add('bg-danger');
                    progressBar.style.width = '100%';
                    progressBar.textContent = 'Ralat';
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
                        if (data.error) {
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-danger');
                            progressBar.style.width = '100%';
                            progressBar.textContent = 'Ralat';
                            progressMessage.textContent = data.error;
                            btnSubmit.disabled = false;
                            return;
                        }
                        if (typeof data.progress !== 'undefined') {
                            progressBar.style.width = data.progress + '%';
                            progressBar.textContent = data.progress + '%';
                        }
                        if (data.message) progressMessage.textContent = data.message;
                        if (data.success && data.redirect) {
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-success');
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                            progressMessage.textContent = 'Selesai.';
                            formSection.classList.add('d-none');
                            successSection.classList.remove('d-none');
                            successMessage.textContent = (data.inserted !== undefined) ? data.inserted + ' rekod telah diproses ke jadual JSON.' : 'Rekod telah diproses ke jadual (JSON).';
                            btnGoToApp.href = data.redirect;
                            btnGoToApp.target = '_blank';
                            btnSubmit.disabled = false;
                        }
                    } catch (err) {}
                });
            });

            xhr.addEventListener('error', function() {
                progressBar.classList.add('bg-danger');
                progressBar.style.width = '100%';
                progressBar.textContent = 'Ralat';
                progressMessage.textContent = 'Ralat rangkaian. Sila cuba lagi.';
                btnSubmit.disabled = false;
            });
            xhr.send(fd);
        });
    })();
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
